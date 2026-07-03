<?php
/**
 * AI-Assisted Property Valuation
 *
 * IMPORTANT SCOPE NOTE (per explicit user requirement): this entire
 * module is restricted to Tabriz Home's OWN staff only - site admins
 * and the 'moaveze_consultant' role. It is NEVER exposed to regular
 * site visitors or property owners. There is no public-facing UI,
 * shortcode, or endpoint for it anywhere.
 *
 * Two valuation modes, both admin/consultant-only:
 *   1. Manual valuation - a consultant types in their own assessed
 *      value + notes.
 *   2. AI-assisted valuation - the consultant clicks a button, and the
 *      configured AI provider is asked to estimate a fair value based
 *      on district, area, year built, features, etc, optionally after
 *      "researching" comparable listings (the AI is instructed to draw
 *      on its general knowledge of the Tabriz/Divar market - this
 *      plugin does NOT scrape Divar directly, since that would require
 *      external scraping infrastructure outside a WordPress plugin's
 *      normal capabilities. The AI is asked to behave as if it did
 *      that research and ground its estimate in comparable pricing
 *      patterns it is aware of).
 *
 * Supported AI providers (each independently enabled/disabled in
 * Settings > هوش مصنوعی):
 *   - Google Gemini
 *   - OpenAI ChatGPT
 *   - Hermes (via OpenRouter-compatible or custom endpoint)
 *   - z.ai
 *   - Cloudflare Workers AI
 *   - Custom API URL (any OpenAI-compatible chat-completions endpoint)
 *
 * Iran-hosting connectivity workarounds (each independently toggle-
 * able, mutually usable together):
 *   - HTTP/SOCKS proxy (e.g. an Xray config) - just a config string
 *     field, this plugin does not implement a proxy client itself,
 *     it only points PHP's HTTP requests through the given proxy.
 *   - Cloudflare Worker relay - route the AI request through a
 *     Cloudflare Worker URL the site owner deploys themselves, which
 *     forwards to the real AI provider from outside Iran.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_AI_Valuation {

    /**
     * Provider metadata: default endpoint + model, used for both the
     * settings screen (dropdown/labels) and the actual HTTP call.
     */
    public static function get_providers() {
        return array(
            'gemini' => array(
                'label'          => 'Google Gemini',
                'default_url'    => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key}',
                'default_model'  => 'gemini-1.5-flash',
                'auth_style'     => 'query_key',
            ),
            'chatgpt' => array(
                'label'          => 'OpenAI ChatGPT',
                'default_url'    => 'https://api.openai.com/v1/chat/completions',
                'default_model'  => 'gpt-4o-mini',
                'auth_style'     => 'bearer',
            ),
            'hermes' => array(
                'label'          => 'Hermes',
                'default_url'    => 'https://openrouter.ai/api/v1/chat/completions',
                'default_model'  => 'nousresearch/hermes-3-llama-3.1-405b',
                'auth_style'     => 'bearer',
            ),
            'zai' => array(
                'label'          => 'z.ai',
                'default_url'    => 'https://api.z.ai/api/paas/v4/chat/completions',
                'default_model'  => 'glm-4-plus',
                'auth_style'     => 'bearer',
            ),
            'cloudflare_ai' => array(
                'label'          => 'Cloudflare Workers AI',
                'default_url'    => 'https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/run/{model}',
                'default_model'  => '@cf/meta/llama-3.1-8b-instruct',
                'auth_style'     => 'bearer',
            ),
            'custom' => array(
                'label'          => 'آدرس سفارشی (OpenAI-compatible)',
                'default_url'    => '',
                'default_model'  => '',
                'auth_style'     => 'bearer',
            ),
        );
    }

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'add_valuation_metabox'));
        add_action('wp_ajax_moaveze_run_ai_valuation', array($this, 'ajax_run_ai_valuation'));
        add_action('wp_ajax_moaveze_save_manual_valuation', array($this, 'ajax_save_manual_valuation'));
        add_action('wp_ajax_moaveze_apply_valuation', array($this, 'ajax_apply_valuation'));
        add_action('wp_ajax_moaveze_test_ai_connection', array($this, 'ajax_test_ai_connection'));
    }

    /**
     * Only site admins and consultants may ever reach this module's
     * functionality - enforced on every single AJAX handler below, in
     * addition to the metabox only rendering for those roles.
     */
    private function current_user_is_staff() {
        return current_user_can('manage_options') || current_user_can('moaveze_verify_listings');
    }


    /**
     * Register all AI/proxy settings (rendered by the new "هوش مصنوعی"
     * settings tab added in class-settings.php).
     */
    public function register_settings() {
        // Master toggle + active provider
        register_setting('moaveze_ai', 'moaveze_ai_valuation_enabled');
        register_setting('moaveze_ai', 'moaveze_ai_active_provider');

        // Per-provider enable + credentials
        foreach (array_keys(self::get_providers()) as $key) {
            register_setting('moaveze_ai', "moaveze_ai_{$key}_enabled");
            register_setting('moaveze_ai', "moaveze_ai_{$key}_api_key");
            register_setting('moaveze_ai', "moaveze_ai_{$key}_url");
            register_setting('moaveze_ai', "moaveze_ai_{$key}_model");
        }
        // Cloudflare Workers AI needs an account ID too
        register_setting('moaveze_ai', 'moaveze_ai_cloudflare_ai_account_id');

        // Connectivity workarounds (independently toggleable)
        register_setting('moaveze_ai', 'moaveze_ai_proxy_enabled');
        register_setting('moaveze_ai', 'moaveze_ai_proxy_url');
        register_setting('moaveze_ai', 'moaveze_ai_worker_enabled');
        register_setting('moaveze_ai', 'moaveze_ai_worker_url');
        register_setting('moaveze_ai', 'moaveze_ai_worker_secret');
    }

    /**
     * Add the "ارزش‌گذاری هوش مصنوعی" meta box to the exchange listing
     * edit screen - admin/consultant only (hidden entirely for anyone
     * else, including the listing's own author).
     */
    public function add_valuation_metabox() {
        if (!$this->current_user_is_staff()) return;

        add_meta_box(
            'moaveze_ai_valuation',
            'ارزش‌گذاری ملک (فقط مشاوران تبریز هوم)',
            array($this, 'render_valuation_metabox'),
            'moaveze_exchange',
            'normal',
            'high'
        );
    }

    /**
     * Render the valuation metabox: manual input + AI-assisted button,
     * plus a history of past valuations for this listing.
     */
    public function render_valuation_metabox($post) {
        global $wpdb;
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d", $post->ID
        ));

        $ai_enabled = get_option('moaveze_ai_valuation_enabled') === 'yes';
        $active_provider = get_option('moaveze_ai_active_provider', 'gemini');
        $providers = self::get_providers();
        $provider_ready = $ai_enabled && get_option("moaveze_ai_{$active_provider}_enabled") === 'yes';

        $history = $exchange ? $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_valuations WHERE exchange_id = %d ORDER BY created_at DESC LIMIT 10",
            $exchange->id
        )) : array();

        wp_nonce_field('moaveze_valuation', 'moaveze_valuation_nonce');
        ?>
        <div class="moaveze-valuation-box" data-exchange-id="<?php echo esc_attr($exchange->id ?? 0); ?>" data-post-id="<?php echo esc_attr($post->ID); ?>">
            <p class="description" style="margin-bottom:14px;">
                <span class="dashicons dashicons-shield" style="color:#6366f1;"></span>
                این بخش فقط برای مدیران و مشاوران تبریز هوم قابل مشاهده و استفاده است و به کاربران سایت نشان داده نمی‌شود.
            </p>

            <div class="valuation-current">
                <strong>ارزش فعلی ثبت‌شده در آگهی:</strong>
                <span class="valuation-current-value"><?php echo esc_html(Moaveze_Helpers::short_price($exchange->property_value ?? 0)); ?></span>
            </div>

            <div class="valuation-tabs">
                <button type="button" class="valuation-tab-btn active" data-tab="manual">ارزش‌گذاری دستی</button>
                <button type="button" class="valuation-tab-btn" data-tab="ai" <?php echo !$provider_ready ? 'disabled' : ''; ?>>
                    ارزش‌گذاری با هوش مصنوعی
                    <?php if (!$provider_ready) : ?><small>(غیرفعال)</small><?php endif; ?>
                </button>
            </div>

            <div class="valuation-tab-content" data-tab-content="manual">
                <div class="valuation-field-grid">
                    <div class="moaveze-field">
                        <label>ارزش پیشنهادی مشاور (تومان)</label>
                        <input type="text" class="moaveze-price-input valuation-manual-value" placeholder="مثال: 15000000000">
                    </div>
                </div>
                <div class="moaveze-field">
                    <label>یادداشت / دلیل ارزش‌گذاری</label>
                    <textarea class="valuation-manual-notes" rows="2" placeholder="مثال: با توجه به موقعیت و بازسازی، ارزش واقعی بالاتر از ثبت مالک است."></textarea>
                </div>
                <button type="button" class="button button-primary valuation-save-manual-btn">
                    <span class="dashicons dashicons-yes"></span> ذخیره ارزش‌گذاری دستی
                </button>
            </div>

            <div class="valuation-tab-content" data-tab-content="ai" style="display:none;">
                <?php if (!$provider_ready) : ?>
                    <p class="description">
                        سیستم هوش مصنوعی فعال نیست یا ارائه‌دهنده انتخاب‌شده تنظیم نشده است. از
                        <a href="<?php echo admin_url('admin.php?page=moaveze-settings&tab=ai'); ?>">تنظیمات &gt; هوش مصنوعی</a>
                        فعال‌سازی کنید.
                    </p>
                <?php else : ?>
                    <p class="description">
                        هوش مصنوعی (<?php echo esc_html($providers[$active_provider]['label'] ?? $active_provider); ?>) با توجه به منطقه،
                        متراژ، سال ساخت، امکانات و دانش خود از بازار مسکن تبریز، ارزش پیشنهادی ارائه می‌دهد.
                    </p>
                    <button type="button" class="button button-primary valuation-run-ai-btn">
                        <span class="dashicons dashicons-superhero-alt"></span> دریافت پیشنهاد هوش مصنوعی
                    </button>
                    <div class="valuation-ai-result" style="display:none;"></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($history)) : ?>
                <div class="valuation-history">
                    <h4>تاریخچه ارزش‌گذاری‌ها</h4>
                    <?php foreach ($history as $v) : ?>
                        <div class="valuation-history-item">
                            <span class="vh-type <?php echo esc_attr($v->type); ?>">
                                <?php echo $v->type === 'ai' ? '🤖 هوش مصنوعی' : '👤 دستی'; ?>
                                <?php if ($v->type === 'ai' && $v->ai_provider) echo ' (' . esc_html($providers[$v->ai_provider]['label'] ?? $v->ai_provider) . ')'; ?>
                            </span>
                            <span class="vh-value">
                                <?php echo esc_html(Moaveze_Helpers::short_price($v->manual_value ?: $v->suggested_value)); ?>
                            </span>
                            <span class="vh-date"><?php echo esc_html(Moaveze_Helpers::jalali_date($v->created_at, 'Y/m/d H:i')); ?></span>
                            <?php if ($v->status === 'applied') : ?>
                                <span class="vh-applied">✓ اعمال شده روی آگهی</span>
                            <?php else : ?>
                                <button type="button" class="button button-small valuation-apply-btn" data-valuation-id="<?php echo esc_attr($v->id); ?>">
                                    اعمال روی آگهی
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }


    /**
     * AJAX: save a manual (human) valuation entry.
     */
    public function ajax_save_manual_valuation() {
        check_ajax_referer('moaveze_valuation', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $exchange_id = absint($_POST['exchange_id'] ?? 0);
        $post_id = absint($_POST['post_id'] ?? 0);
        $value = absint(str_replace(array(',', ' ', '٬'), '', $_POST['value'] ?? ''));
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$exchange_id || !$value) {
            wp_send_json_error('لطفاً مبلغ ارزش‌گذاری را وارد کنید');
        }

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'moaveze_valuations', array(
            'exchange_id'    => $exchange_id,
            'post_id'        => $post_id,
            'type'           => 'manual',
            'manual_value'   => $value,
            'manual_notes'   => $notes,
            'status'         => 'pending',
            'created_by'     => get_current_user_id(),
        ));

        wp_send_json_success(array(
            'message'       => 'ارزش‌گذاری دستی ذخیره شد',
            'valuation_id'  => $wpdb->insert_id,
            'value_short'   => Moaveze_Helpers::short_price($value),
        ));
    }

    /**
     * AJAX: apply a saved valuation (manual or AI) as the listing's
     * official property_value - a deliberate, explicit consultant
     * action (never automatic) so an AI/manual suggestion can never
     * silently overwrite what the property owner submitted.
     */
    public function ajax_apply_valuation() {
        check_ajax_referer('moaveze_valuation', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $valuation_id = absint($_POST['valuation_id'] ?? 0);
        global $wpdb;
        $valuation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_valuations WHERE id = %d", $valuation_id
        ));
        if (!$valuation) wp_send_json_error('ارزش‌گذاری یافت نشد');

        $new_value = $valuation->manual_value ?: $valuation->suggested_value;
        if (!$new_value) wp_send_json_error('مقدار ارزش نامعتبر است');

        update_post_meta($valuation->post_id, '_moaveze_property_value', $new_value);
        $wpdb->update(
            $wpdb->prefix . 'moaveze_exchanges',
            array('property_value' => $new_value),
            array('id' => $valuation->exchange_id)
        );
        $wpdb->update(
            $wpdb->prefix . 'moaveze_valuations',
            array('status' => 'applied'),
            array('id' => $valuation_id)
        );

        wp_send_json_success(array(
            'message' => 'ارزش جدید روی آگهی اعمال شد',
            'value_short' => Moaveze_Helpers::short_price($new_value),
        ));
    }

    /**
     * AJAX: quick "test connection" button in the settings screen, so
     * an admin can verify their API key/proxy/worker setup works
     * before relying on it for real valuations.
     */
    public function ajax_test_ai_connection() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی ندارید');

        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $result = $this->call_ai_provider($provider, 'یک جمله کوتاه به فارسی بنویس که تست اتصال موفق بوده است.');

        if ($result['success']) {
            wp_send_json_success(array('message' => 'اتصال موفق بود', 'reply' => mb_substr($result['text'], 0, 200)));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }


    /**
     * AJAX: the main "دریافت پیشنهاد هوش مصنوعی" action - builds a
     * structured prompt from the listing's real data, sends it to the
     * active AI provider, parses the (requested-JSON) response, and
     * stores it as a pending valuation for the consultant to review.
     */
    public function ajax_run_ai_valuation() {
        check_ajax_referer('moaveze_valuation', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        if (get_option('moaveze_ai_valuation_enabled') !== 'yes') {
            wp_send_json_error('ارزش‌گذاری هوش مصنوعی غیرفعال است');
        }

        $exchange_id = absint($_POST['exchange_id'] ?? 0);
        global $wpdb;
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $exchange_id
        ));
        if (!$exchange) wp_send_json_error('آگهی یافت نشد');

        $provider = get_option('moaveze_ai_active_provider', 'gemini');
        if (get_option("moaveze_ai_{$provider}_enabled") !== 'yes') {
            wp_send_json_error('ارائه‌دهنده انتخاب‌شده فعال نیست');
        }

        $prompt = $this->build_valuation_prompt($exchange);
        $result = $this->call_ai_provider($provider, $prompt, true);

        if (!$result['success']) {
            wp_send_json_error(array('message' => 'خطا در ارتباط با هوش مصنوعی: ' . $result['error']));
        }

        $parsed = $this->parse_valuation_response($result['text']);

        $wpdb->insert($wpdb->prefix . 'moaveze_valuations', array(
            'exchange_id'      => $exchange_id,
            'post_id'          => $exchange->post_id,
            'type'             => 'ai',
            'ai_provider'      => $provider,
            'suggested_value'  => $parsed['value'],
            'suggested_min'    => $parsed['min'],
            'suggested_max'    => $parsed['max'],
            'confidence'       => $parsed['confidence'],
            'reasoning'        => $parsed['reasoning'],
            'comparables'      => wp_json_encode($parsed['comparables']),
            'raw_response'     => $result['text'],
            'status'           => 'pending',
            'created_by'       => get_current_user_id(),
        ));

        wp_send_json_success(array(
            'valuation_id' => $wpdb->insert_id,
            'value'        => $parsed['value'],
            'value_short'  => Moaveze_Helpers::short_price($parsed['value']),
            'min_short'    => $parsed['min'] ? Moaveze_Helpers::short_price($parsed['min']) : null,
            'max_short'    => $parsed['max'] ? Moaveze_Helpers::short_price($parsed['max']) : null,
            'confidence'   => $parsed['confidence'],
            'reasoning'    => $parsed['reasoning'],
            'comparables'  => $parsed['comparables'],
            'provider'     => self::get_providers()[$provider]['label'] ?? $provider,
        ));
    }

    /**
     * Build the structured Persian prompt describing the listing and
     * asking the AI to reason like an experienced Tabriz real-estate
     * consultant, grounded in comparable listings it is aware of (e.g.
     * from Divar-style classifieds), and to answer in a strict JSON
     * shape we can reliably parse.
     */
    private function build_valuation_prompt($exchange) {
        $type_terms = wp_get_post_terms($exchange->post_id, 'moaveze_property_type', array('fields' => 'names'));
        $feature_terms = wp_get_post_terms($exchange->post_id, 'moaveze_feature', array('fields' => 'names'));
        $year_built = get_post_meta($exchange->post_id, '_moaveze_year_built', true);
        $rooms = get_post_meta($exchange->post_id, '_moaveze_rooms', true);
        $floor = get_post_meta($exchange->post_id, '_moaveze_floor', true);

        $details = array(
            'شهر'          => 'تبریز',
            'منطقه/محله'   => $exchange->district ?: 'نامشخص',
            'نوع ملک'      => $type_terms[0] ?? $exchange->property_type,
            'متراژ'        => $exchange->area_sqm . ' متر مربع',
            'تعداد اتاق'   => $rooms ?: 'نامشخص',
            'طبقه'         => $floor ?: 'نامشخص',
            'سال ساخت'     => $year_built ?: 'نامشخص',
            'امکانات'      => !empty($feature_terms) ? implode('، ', $feature_terms) : 'ثبت نشده',
            'ارزش ثبت‌شده توسط مالک' => number_format($exchange->property_value) . ' تومان',
        );

        $details_text = '';
        foreach ($details as $label => $value) {
            $details_text .= "- {$label}: {$value}\n";
        }

        return <<<PROMPT
تو یک کارشناس باتجربه ارزیابی املاک و مستغلات در شهر تبریز، ایران هستی که با قیمت‌های واقعی بازار مسکن تبریز (از جمله آگهی‌های مشابه در سایت‌های نیازمندی مثل دیوار) کاملاً آشنایی داری.

با توجه به مشخصات زیر یک ملک، ارزش واقعی و منصفانه آن را به تومان تخمین بزن. عوامل مهم مؤثر بر قیمت مانند منطقه، متراژ، سال ساخت، طبقه و امکانات را در نظر بگیر و ارزش را با آگهی‌های مشابهی که از این مناطق و مشخصات در بازار می‌شناسی مقایسه کن.

مشخصات ملک:
{$details_text}

پاسخ را دقیقاً و فقط به‌صورت یک JSON با این ساختار بده (بدون هیچ توضیح اضافه یا متن قبل/بعد از JSON):

{
  "estimated_value": <عدد به تومان، بدون جداکننده هزارگان>,
  "min_value": <کمترین مقدار محدوده منطقی به تومان>,
  "max_value": <بیشترین مقدار محدوده منطقی به تومان>,
  "confidence": "<یکی از: بالا, متوسط, پایین>",
  "reasoning": "<توضیح کوتاه فارسی، حداکثر ۴-۵ جمله، درباره دلیل این ارزش‌گذاری و مقایسه با موارد مشابه>",
  "comparable_notes": "<یک یا دو نمونه فرضی از محدوده قیمتی موارد مشابه در همین منطقه که می‌شناسی، به فارسی>"
}
PROMPT;
    }

    /**
     * Parse the AI's JSON response defensively - AI responses sometimes
     * wrap JSON in markdown code fences or add stray text, so we
     * extract the first {...} block before decoding.
     */
    private function parse_valuation_response($text) {
        $json_start = strpos($text, '{');
        $json_end = strrpos($text, '}');
        $json_str = ($json_start !== false && $json_end !== false)
            ? substr($text, $json_start, $json_end - $json_start + 1)
            : $text;

        $data = json_decode($json_str, true);

        return array(
            'value'       => isset($data['estimated_value']) ? absint($data['estimated_value']) : 0,
            'min'         => isset($data['min_value']) ? absint($data['min_value']) : null,
            'max'         => isset($data['max_value']) ? absint($data['max_value']) : null,
            'confidence'  => $data['confidence'] ?? 'نامشخص',
            'reasoning'   => $data['reasoning'] ?? mb_substr($text, 0, 500),
            'comparables' => $data['comparable_notes'] ?? '',
        );
    }


    /**
     * Dispatch a prompt to the given provider, honoring the
     * proxy/worker connectivity settings, and return a normalized
     * ['success' => bool, 'text' => string, 'error' => string] result.
     *
     * @param string $provider     Provider key from get_providers().
     * @param string $prompt       The user/system prompt text.
     * @param bool   $json_mode    Whether to hint the API for JSON output (where supported).
     */
    private function call_ai_provider($provider, $prompt, $json_mode = false) {
        $providers = self::get_providers();
        if (!isset($providers[$provider])) {
            return array('success' => false, 'text' => '', 'error' => 'ارائه‌دهنده نامعتبر است');
        }

        // If the Cloudflare Worker relay is enabled, ALL provider
        // traffic is routed through it instead of calling the provider
        // directly - the worker is expected to forward the request
        // (and inject its own provider credentials, or pass through
        // ours) from outside Iran. This is the "جایگزین دور زدن
        // محدودیت" option requested, independent of the HTTP proxy.
        if (get_option('moaveze_ai_worker_enabled') === 'yes' && get_option('moaveze_ai_worker_url')) {
            return $this->call_via_cloudflare_worker($provider, $prompt, $json_mode);
        }

        $api_key = get_option("moaveze_ai_{$provider}_api_key");
        $url = get_option("moaveze_ai_{$provider}_url") ?: $providers[$provider]['default_url'];
        $model = get_option("moaveze_ai_{$provider}_model") ?: $providers[$provider]['default_model'];

        if ($provider !== 'cloudflare_ai' && !$api_key) {
            return array('success' => false, 'text' => '', 'error' => 'کلید API تنظیم نشده است');
        }

        switch ($provider) {
            case 'gemini':
                return $this->call_gemini($url, $model, $api_key, $prompt);
            case 'cloudflare_ai':
                return $this->call_cloudflare_ai($url, $model, $api_key, $prompt);
            default: // chatgpt, hermes, zai, custom - all OpenAI-compatible
                return $this->call_openai_compatible($url, $model, $api_key, $prompt, $json_mode);
        }
    }

    /**
     * Build the wp_remote_post() $args array with the HTTP proxy applied
     * if the "پروکسی HTTP (Xray)" option is enabled - this is the field
     * requested for bypassing Iran hosting restrictions by pointing
     * outbound requests through e.g. a local Xray/HTTP proxy config.
     * WordPress core's HTTP API (via the underlying cURL/streams
     * transport) natively supports the CURLOPT_PROXY-equivalent
     * 'proxy' arg, so no extra library is needed here.
     */
    private function http_args($extra = array()) {
        $args = array_merge(array('timeout' => 45), $extra);

        if (get_option('moaveze_ai_proxy_enabled') === 'yes') {
            $proxy_url = trim(get_option('moaveze_ai_proxy_url', ''));
            if ($proxy_url) {
                // e.g. "http://127.0.0.1:2080" or "socks5://127.0.0.1:1080"
                $args['proxy'] = $proxy_url;
            }
        }

        return $args;
    }

    /**
     * Google Gemini (generateContent API)
     */
    private function call_gemini($url, $model, $api_key, $prompt) {
        $endpoint = str_replace(array('{model}', '{api_key}'), array($model, $api_key), $url);

        $response = wp_remote_post($endpoint, $this->http_args(array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode(array(
                'contents' => array(
                    array('parts' => array(array('text' => $prompt))),
                ),
            )),
        )));

        if (is_wp_error($response)) {
            return array('success' => false, 'text' => '', 'error' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            return array('success' => false, 'text' => '', 'error' => $body['error']['message'] ?? 'پاسخ نامعتبر از Gemini');
        }

        return array('success' => true, 'text' => $text, 'error' => '');
    }

    /**
     * OpenAI-compatible chat-completions endpoint - used for ChatGPT,
     * Hermes (OpenRouter or similar), z.ai, and any custom URL, since
     * they all follow the same request/response shape.
     */
    private function call_openai_compatible($url, $model, $api_key, $prompt, $json_mode = false) {
        if (!$url) {
            return array('success' => false, 'text' => '', 'error' => 'آدرس API تنظیم نشده است');
        }

        $body = array(
            'model'    => $model,
            'messages' => array(
                array('role' => 'user', 'content' => $prompt),
            ),
            'temperature' => 0.3,
        );
        if ($json_mode) {
            $body['response_format'] = array('type' => 'json_object');
        }

        $response = wp_remote_post($url, $this->http_args(array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => wp_json_encode($body),
        )));

        if (is_wp_error($response)) {
            return array('success' => false, 'text' => '', 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $text = $data['choices'][0]['message']['content'] ?? null;

        if (!$text) {
            return array('success' => false, 'text' => '', 'error' => $data['error']['message'] ?? 'پاسخ نامعتبر از سرویس هوش مصنوعی');
        }

        return array('success' => true, 'text' => $text, 'error' => '');
    }

    /**
     * Cloudflare Workers AI (direct, non-relay usage)
     */
    private function call_cloudflare_ai($url, $model, $api_key, $prompt) {
        $account_id = get_option('moaveze_ai_cloudflare_ai_account_id');
        $endpoint = str_replace(array('{account_id}', '{model}'), array($account_id, $model), $url);

        $response = wp_remote_post($endpoint, $this->http_args(array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => wp_json_encode(array(
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
            )),
        )));

        if (is_wp_error($response)) {
            return array('success' => false, 'text' => '', 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $text = $data['result']['response'] ?? null;

        if (!$text) {
            return array('success' => false, 'text' => '', 'error' => $data['errors'][0]['message'] ?? 'پاسخ نامعتبر از Cloudflare Workers AI');
        }

        return array('success' => true, 'text' => $text, 'error' => '');
    }

    /**
     * Relay the request through a self-hosted Cloudflare Worker instead
     * of calling the AI provider directly - the alternative "دور زدن
     * محدودیت" option. The worker is expected to accept
     * { provider, api_key, model, prompt } and return { text } (or
     * { error }) after forwarding to the real provider on its end.
     * An optional shared secret header authenticates the site to the
     * worker so it can't be freely abused by third parties.
     */
    private function call_via_cloudflare_worker($provider, $prompt, $json_mode) {
        $worker_url = get_option('moaveze_ai_worker_url');
        $secret = get_option('moaveze_ai_worker_secret');
        $providers = self::get_providers();

        $headers = array('Content-Type' => 'application/json');
        if ($secret) {
            $headers['X-Worker-Secret'] = $secret;
        }

        $response = wp_remote_post($worker_url, array(
            'timeout' => 45,
            'headers' => $headers,
            'body'    => wp_json_encode(array(
                'provider'  => $provider,
                'api_key'   => get_option("moaveze_ai_{$provider}_api_key"),
                'model'     => get_option("moaveze_ai_{$provider}_model") ?: $providers[$provider]['default_model'],
                'account_id'=> get_option('moaveze_ai_cloudflare_ai_account_id'),
                'prompt'    => $prompt,
                'json_mode' => $json_mode,
            )),
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'text' => '', 'error' => $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($data['text'])) {
            return array('success' => false, 'text' => '', 'error' => $data['error'] ?? 'پاسخ نامعتبر از Cloudflare Worker');
        }

        return array('success' => true, 'text' => $data['text'], 'error' => '');
    }
}

new Moaveze_AI_Valuation();
