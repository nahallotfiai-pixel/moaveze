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
                // NOTE: the free tier of every Gemini model (including
                // this default) has a shared, low per-minute rate limit
                // that is completely independent of remaining credit/
                // quota balance - "high demand"/503 errors from Google
                // happen even with plenty of credit left, simply because
                // too many requests hit that specific model within a
                // short window. Use the "دریافت لیست مدل‌های موجود"
                // button in Settings > هوش مصنوعی to switch to
                // 'gemini-2.0-flash-lite' or another available model,
                // which has its own SEPARATE rate-limit bucket and often
                // recovers faster - see is_google_rate_limit_error()
                // below for how this is now explained to the consultant
                // in-context rather than showing Google's raw message.
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
        add_action('wp_ajax_moaveze_fetch_ai_models', array($this, 'ajax_fetch_ai_models'));
        add_action('wp_ajax_moaveze_save_ai_model', array($this, 'ajax_save_ai_model'));
        add_action('wp_ajax_moaveze_delete_valuation', array($this, 'ajax_delete_valuation'));
        add_action('wp_ajax_moaveze_get_valuation_details', array($this, 'ajax_get_valuation_details'));
    }

    /**
     * AJAX: permanently delete one valuation history entry - per
     * explicit user request ("بتوان نتایج قبلی تحلیل ها رو هم پاک کرد
     * یا مشاهده کرد"). Refuses to delete an entry that is currently
     * "applied" (published as the listing's expert value) without an
     * explicit confirm flag, so a consultant can't accidentally delete
     * the record backing what's currently shown live on the site.
     */
    public function ajax_delete_valuation() {
        check_ajax_referer('moaveze_valuation', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $valuation_id = absint($_POST['valuation_id'] ?? 0);
        $force = !empty($_POST['force']);
        global $wpdb;

        $valuation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_valuations WHERE id = %d", $valuation_id
        ));
        if (!$valuation) wp_send_json_error('ارزش‌گذاری یافت نشد');

        if ($valuation->status === 'applied' && !$force) {
            wp_send_json_error(array(
                'message'          => 'این ارزش‌گذاری هم‌اکنون روی آگهی منتشر شده است. اگر حذف شود، کادر «قیمت کارشناسی» هم از صفحه آگهی حذف خواهد شد.',
                'requires_confirm' => true,
            ));
        }

        $wpdb->delete($wpdb->prefix . 'moaveze_valuations', array('id' => $valuation_id));

        // If the deleted entry was the one currently published on the
        // listing, also clear the expert-value display meta so the
        // frontend card disappears immediately instead of pointing at
        // an orphaned/deleted record.
        if ($valuation->status === 'applied') {
            delete_post_meta($valuation->post_id, '_moaveze_expert_value');
            delete_post_meta($valuation->post_id, '_moaveze_expert_min');
            delete_post_meta($valuation->post_id, '_moaveze_expert_max');
            delete_post_meta($valuation->post_id, '_moaveze_expert_value_type');
            delete_post_meta($valuation->post_id, '_moaveze_expert_value_date');
            if (class_exists('Moaveze_Meta_Fields')) {
                Moaveze_Meta_Fields::purge_listing_cache($valuation->post_id);
            }
        }

        wp_send_json_success(array('message' => 'ارزش‌گذاری حذف شد'));
    }

    /**
     * AJAX: fetch full details of one past valuation for the "مشاهده"
     * (view) button in the history list - returns everything needed to
     * re-render the same rich result box shown right after running a
     * fresh AI valuation (methodology, reasoning, structured
     * comparables), without needing to re-run the AI or dig into the
     * database manually.
     */
    public function ajax_get_valuation_details() {
        check_ajax_referer('moaveze_valuation', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $valuation_id = absint($_POST['valuation_id'] ?? 0);
        global $wpdb;
        $v = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_valuations WHERE id = %d", $valuation_id
        ));
        if (!$v) wp_send_json_error('ارزش‌گذاری یافت نشد');

        $comparables_data = json_decode($v->comparables ?: '{}', true);
        $comparables = !empty($comparables_data['items']) && is_array($comparables_data['items'])
            ? $this->format_comparables_for_display($comparables_data['items'])
            : array();

        $providers = self::get_providers();

        wp_send_json_success(array(
            'type'                => $v->type,
            'provider'            => $v->ai_provider ? ($providers[$v->ai_provider]['label'] ?? $v->ai_provider) : null,
            'value_short'         => Moaveze_Helpers::short_price($v->manual_value ?: $v->suggested_value),
            'min_short'           => ($v->manual_min_value ?: $v->suggested_min) ? Moaveze_Helpers::short_price($v->manual_min_value ?: $v->suggested_min) : null,
            'max_short'           => ($v->manual_max_value ?: $v->suggested_max) ? Moaveze_Helpers::short_price($v->manual_max_value ?: $v->suggested_max) : null,
            'price_per_sqm_short' => !empty($comparables_data['price_per_sqm']) ? Moaveze_Helpers::short_price($comparables_data['price_per_sqm']) : null,
            'confidence'          => $v->confidence,
            'reasoning'           => $v->reasoning ?: $v->manual_notes,
            'grounded'            => !empty($comparables_data['grounded']),
            'comparables'         => $comparables,
            'all_grounded_sources' => $comparables_data['all_grounded_sources'] ?? array(),
            'status'              => $v->status,
            'date'                => Moaveze_Helpers::jalali_date($v->created_at, 'Y/m/d H:i'),
        ));
    }

    /**
     * AJAX: instantly persist the model chosen from the dynamically-
     * fetched list, the moment it's selected - independent of the big
     * "ذخیره تنظیمات" settings form submit.
     *
     * WHY THIS EXISTS: the site owner reported that picking a model from
     * the dynamic dropdown and then saving via the normal settings form
     * resulted in the field being empty again after the page reloaded.
     * Rather than leave that dependent on the full options.php
     * settings-API round trip (multiple moving parts: the correct
     * settings group nonce, the field being inside the <form>, the
     * browser correctly serializing every input, etc.), this saves the
     * exact same option (moaveze_ai_{provider}_model) directly and
     * immediately - the same defensive pattern already used for the
     * feature checkboxes (see Moaveze_Meta_Fields::ajax_toggle_feature).
     * The value is also still written into the visible text field so
     * the full settings form save (if used) carries the same value too.
     */
    public function ajax_save_ai_model() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $model = sanitize_text_field($_POST['model'] ?? '');

        $providers = self::get_providers();
        if (!isset($providers[$provider])) {
            wp_send_json_error('ارائه‌دهنده نامعتبر است');
        }
        if (!$model) {
            wp_send_json_error('نام مدل نامعتبر است');
        }

        update_option("moaveze_ai_{$provider}_model", $model);

        // Read back what's actually in the DB now, so the UI can prove
        // (not just assume) the value really persisted.
        $saved_value = get_option("moaveze_ai_{$provider}_model");

        wp_send_json_success(array(
            'message'     => 'مدل "' . $model . '" برای ' . $providers[$provider]['label'] . ' ذخیره شد',
            'saved_value' => $saved_value,
        ));
    }

    /**
     * Static hints used to label fetched models as رایگان (free) /
     * پولی (paid) / cost-per-token where the provider's own API doesn't
     * return that info directly (e.g. OpenAI's /v1/models endpoint has
     * no pricing field at all). Matched by substring against the
     * model's id, longest/most-specific match wins implicitly because
     * we check in array order. This is intentionally best-effort - the
     * "test اتصال" button and each model's raw id are always shown too,
     * so nothing is ever hidden from the site owner.
     */
    private static function get_model_pricing_hints() {
        return array(
            // Gemini - Google grants a genuinely free daily quota for
            // "flash"/"flash-lite" models; "pro" models are paid-only.
            'gemini-1.5-flash-lite' => 'رایگان (سهمیه روزانه Google) - سریع اما ساده‌تر، برای ارزش‌گذاری دقیق توصیه نمی‌شود',
            'gemini-1.5-flash'   => 'رایگان (سهمیه روزانه Google)',
            'gemini-1.5-pro'     => 'پولی',
            'gemini-2.0-flash-lite' => 'رایگان (سهمیه روزانه Google) - سریع اما ساده‌تر، برای ارزش‌گذاری دقیق توصیه نمی‌شود',
            'gemini-2.0-flash'   => 'رایگان (سهمیه روزانه Google)',
            'gemini-2.0-pro'     => 'پولی',
            'gemini-2.5-flash-lite' => 'رایگان (سهمیه روزانه Google) - سریع اما ساده‌تر، برای ارزش‌گذاری دقیق توصیه نمی‌شود',
            'gemini-2.5-flash'   => 'رایگان (سهمیه روزانه Google)',
            'gemini-2.5-pro'     => 'پولی',
            // OpenAI - never free via API.
            'gpt-4o-mini'        => 'پولی (ارزان)',
            'gpt-4o'             => 'پولی',
            'gpt-4-turbo'        => 'پولی',
            'gpt-3.5-turbo'      => 'پولی (ارزان)',
            'o1-mini'            => 'پولی',
            'o1'                 => 'پولی (گران)',
        );
    }

    /**
     * AJAX: dynamically fetch the list of models actually available for
     * a provider right now (per explicit user request: "هر مدلی که زده
     * میشه داینامیک مدل های موجود بارگذاری بشه ... رایگان و غیر رایگان
     * بودنش مشخص بشه یا مصرف کردیتش"). Uses the API key/account id the
     * admin has typed into the settings form (even if not saved yet),
     * so they can test before hitting "ذخیره تنظیمات".
     */
    public function ajax_fetch_ai_models() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $account_id = sanitize_text_field($_POST['account_id'] ?? '');
        $url_override = sanitize_text_field($_POST['url'] ?? '');

        $providers = self::get_providers();
        if (!isset($providers[$provider])) {
            wp_send_json_error('ارائه‌دهنده نامعتبر است');
        }

        // Fall back to the already-saved key/account if the admin didn't
        // type a new one into the (password-masked) field.
        if (!$api_key) $api_key = get_option("moaveze_ai_{$provider}_api_key");
        if (!$account_id) $account_id = get_option('moaveze_ai_cloudflare_ai_account_id');

        $result = $this->fetch_models_for_provider($provider, $api_key, $account_id, $url_override);

        if (!$result['success']) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success(array('models' => $result['models']));
    }

    /**
     * Provider-specific model-listing logic. Returns
     * ['success' => bool, 'models' => [['id','label','pricing_note','is_free'], ...], 'error' => string].
     */
    private function fetch_models_for_provider($provider, $api_key, $account_id, $url_override) {
        $hints = self::get_model_pricing_hints();
        $note_for = function ($model_id) use ($hints) {
            foreach ($hints as $needle => $note) {
                if (stripos($model_id, $needle) !== false) return $note;
            }
            return 'هزینه نامشخص - قبل از استفاده تست کنید';
        };

        switch ($provider) {
            case 'gemini':
                if (!$api_key) return array('success' => false, 'models' => array(), 'error' => 'ابتدا کلید API را وارد کنید');
                $response = wp_remote_get(
                    'https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode($api_key),
                    $this->http_args()
                );
                if (is_wp_error($response)) return array('success' => false, 'models' => array(), 'error' => $response->get_error_message());
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (empty($body['models'])) {
                    return array('success' => false, 'models' => array(), 'error' => $body['error']['message'] ?? 'پاسخ نامعتبر از Gemini');
                }
                $models = array();
                foreach ($body['models'] as $m) {
                    if (empty($m['name']) || strpos($m['name'], 'gemini') === false) continue;
                    // supportedGenerationMethods should include generateContent
                    if (!empty($m['supportedGenerationMethods']) && !in_array('generateContent', $m['supportedGenerationMethods'], true)) continue;
                    $id = str_replace('models/', '', $m['name']);
                    $note = $note_for($id);
                    $models[] = array(
                        'id' => $id,
                        'label' => ($m['displayName'] ?? $id),
                        'pricing_note' => $note,
                        'is_free' => (stripos($note, 'رایگان') === 0),
                    );
                }
                return array('success' => true, 'models' => $models, 'error' => '');

            case 'chatgpt':
                if (!$api_key) return array('success' => false, 'models' => array(), 'error' => 'ابتدا کلید API را وارد کنید');
                $response = wp_remote_get('https://api.openai.com/v1/models', $this->http_args(array(
                    'headers' => array('Authorization' => 'Bearer ' . $api_key),
                )));
                if (is_wp_error($response)) return array('success' => false, 'models' => array(), 'error' => $response->get_error_message());
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (empty($body['data'])) {
                    return array('success' => false, 'models' => array(), 'error' => $body['error']['message'] ?? 'پاسخ نامعتبر از OpenAI');
                }
                $models = array();
                foreach ($body['data'] as $m) {
                    $id = $m['id'] ?? '';
                    // Only chat-capable model families - filter out
                    // embeddings/whisper/tts/dall-e/moderation noise.
                    if (!$id || !preg_match('/^(gpt-|o1|o3|chatgpt)/', $id)) continue;
                    $models[] = array(
                        'id' => $id,
                        'label' => $id,
                        'pricing_note' => $note_for($id),
                        'is_free' => false, // OpenAI API is never free
                    );
                }
                usort($models, fn($a, $b) => strcmp($a['id'], $b['id']));
                return array('success' => true, 'models' => $models, 'error' => '');

            case 'hermes':
                // OpenRouter publicly lists ALL models with real per-token
                // pricing (and explicitly marks free-tier models with a
                // ":free" id suffix / zero pricing) - no API key required
                // just to list them.
                $response = wp_remote_get('https://openrouter.ai/api/v1/models', $this->http_args());
                if (is_wp_error($response)) return array('success' => false, 'models' => array(), 'error' => $response->get_error_message());
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (empty($body['data'])) {
                    return array('success' => false, 'models' => array(), 'error' => 'پاسخ نامعتبر از OpenRouter');
                }
                $models = array();
                foreach ($body['data'] as $m) {
                    $id = $m['id'] ?? '';
                    if (!$id) continue;
                    // Only surface Hermes models (this provider slot is
                    // labeled "Hermes") plus keep the list manageable.
                    if (stripos($id, 'hermes') === false) continue;
                    $prompt_cost = (float) ($m['pricing']['prompt'] ?? 0);
                    $completion_cost = (float) ($m['pricing']['completion'] ?? 0);
                    $is_free = (substr($id, -5) === ':free' || ($prompt_cost === 0.0 && $completion_cost === 0.0));
                    $note = $is_free
                        ? 'رایگان'
                        : sprintf('%.2f$ / میلیون توکن ورودی', $prompt_cost * 1000000);
                    $models[] = array(
                        'id' => $id,
                        'label' => $m['name'] ?? $id,
                        'pricing_note' => $note,
                        'is_free' => $is_free,
                    );
                }
                if (empty($models)) {
                    // Fall back to showing everything if nothing matched
                    // "hermes" (OpenRouter catalog changes over time).
                    foreach ($body['data'] as $m) {
                        $id = $m['id'] ?? '';
                        if (!$id) continue;
                        $prompt_cost = (float) ($m['pricing']['prompt'] ?? 0);
                        $is_free = (substr($id, -5) === ':free' || $prompt_cost === 0.0);
                        $models[] = array(
                            'id' => $id,
                            'label' => $m['name'] ?? $id,
                            'pricing_note' => $is_free ? 'رایگان' : sprintf('%.2f$ / میلیون توکن ورودی', $prompt_cost * 1000000),
                            'is_free' => $is_free,
                        );
                    }
                }
                return array('success' => true, 'models' => array_slice($models, 0, 60), 'error' => '');

            case 'cloudflare_ai':
                if (!$api_key || !$account_id) {
                    return array('success' => false, 'models' => array(), 'error' => 'ابتدا کلید API و Account ID را وارد کنید');
                }
                $response = wp_remote_get(
                    "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/models/search?task=Text%20Generation",
                    $this->http_args(array('headers' => array('Authorization' => 'Bearer ' . $api_key)))
                );
                if (is_wp_error($response)) return array('success' => false, 'models' => array(), 'error' => $response->get_error_message());
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (empty($body['result'])) {
                    return array('success' => false, 'models' => array(), 'error' => $body['errors'][0]['message'] ?? 'پاسخ نامعتبر از Cloudflare');
                }
                $models = array();
                foreach ($body['result'] as $m) {
                    $id = $m['name'] ?? '';
                    if (!$id) continue;
                    $models[] = array(
                        'id' => $id,
                        'label' => $id,
                        // Cloudflare Workers AI bills in "Neurons"; exact
                        // free-quota status changes by plan, so we surface
                        // it as informational rather than a hard yes/no.
                        'pricing_note' => 'بر اساس Neuron مصرفی (پلن Cloudflare شما)',
                        'is_free' => false,
                    );
                }
                return array('success' => true, 'models' => $models, 'error' => '');

            case 'zai':
            case 'custom':
                // Best-effort: try the OpenAI-compatible /models endpoint
                // derived from the configured chat-completions URL.
                $base_url = $url_override ?: get_option("moaveze_ai_{$provider}_url") ?: self::get_providers()[$provider]['default_url'];
                if (!$base_url) {
                    return array('success' => false, 'models' => array(), 'error' => 'ابتدا آدرس API را وارد کنید تا امکان دریافت لیست مدل‌ها بررسی شود');
                }
                $models_url = preg_replace('#/chat/completions/?$#', '/models', rtrim($base_url, '/'));
                $response = wp_remote_get($models_url, $this->http_args(array(
                    'headers' => $api_key ? array('Authorization' => 'Bearer ' . $api_key) : array(),
                )));
                if (is_wp_error($response)) {
                    return array('success' => false, 'models' => array(), 'error' => 'این ارائه‌دهنده از دریافت خودکار لیست مدل‌ها پشتیبانی نکرد؛ نام مدل را دستی وارد کنید.');
                }
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (empty($body['data'])) {
                    return array('success' => false, 'models' => array(), 'error' => 'این ارائه‌دهنده از دریافت خودکار لیست مدل‌ها پشتیبانی نکرد؛ نام مدل را دستی وارد کنید.');
                }
                $models = array();
                foreach ($body['data'] as $m) {
                    $id = $m['id'] ?? '';
                    if (!$id) continue;
                    $models[] = array(
                        'id' => $id,
                        'label' => $id,
                        'pricing_note' => 'هزینه نامشخص - قبل از استفاده تست کنید',
                        'is_free' => false,
                    );
                }
                return array('success' => true, 'models' => $models, 'error' => '');

            default:
                return array('success' => false, 'models' => array(), 'error' => 'ارائه‌دهنده نامعتبر است');
        }
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
     * PUBLIC wrapper: is the AI valuation system configured and ready
     * to answer a prompt right now? Reused by other staff-only AI
     * features that share this same provider/proxy/worker
     * infrastructure - specifically Moaveze_AI_Suggestions (see
     * includes/modules/class-ai-suggestions.php), which asks the AI to
     * suggest deal structures for matches and chain swaps instead of
     * property valuations.
     */
    public function is_ready() {
        if (get_option('moaveze_ai_valuation_enabled') !== 'yes') return false;
        $provider = get_option('moaveze_ai_active_provider', 'gemini');
        return get_option("moaveze_ai_{$provider}_enabled") === 'yes';
    }

    /**
     * PUBLIC wrapper around call_ai_provider() using whichever provider
     * is currently configured as active in Settings > هوش مصنوعی - lets
     * other staff-only AI features (match/chain suggestions) reuse the
     * exact same provider dispatch, proxy, and Cloudflare Worker relay
     * logic without duplicating it.
     *
     * @return array ['success' => bool, 'text' => string, 'error' => string]
     */
    public function ask($prompt, $json_mode = true) {
        if (!$this->is_ready()) {
            return array('success' => false, 'text' => '', 'error' => 'ارزش‌گذاری هوش مصنوعی غیرفعال است یا ارائه‌دهنده تنظیم نشده است');
        }
        $provider = get_option('moaveze_ai_active_provider', 'gemini');
        return $this->call_ai_provider($provider, $prompt, $json_mode);
    }

    /**
     * PUBLIC: name of the currently-active provider, for display
     * purposes in other modules (e.g. "پیشنهاد Google Gemini").
     */
    public function get_active_provider_label() {
        $provider = get_option('moaveze_ai_active_provider', 'gemini');
        $providers = self::get_providers();
        return $providers[$provider]['label'] ?? $provider;
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

        // Real web-search grounding (currently supported for Gemini
        // only, via Google's native "Grounding with Google Search"
        // tool - see call_gemini()) - lets the AI actually search the
        // live web for comparable listings instead of relying purely on
        // its training-data knowledge, so every comparable example can
        // cite a real, clickable, verifiable source URL rather than a
        // plausible-sounding but fabricated one.
        register_setting('moaveze_ai', 'moaveze_ai_grounding_enabled');
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
                <strong>ارزش ثبت‌شده توسط مالک (تغییرناپذیر از این بخش):</strong>
                <span class="valuation-current-value"><?php echo esc_html(Moaveze_Helpers::short_price($exchange->property_value ?? 0)); ?></span>
            </div>
            <?php
            $expert_value = get_post_meta($post->ID, '_moaveze_expert_value', true);
            if ($expert_value) :
                $expert_min = get_post_meta($post->ID, '_moaveze_expert_min', true);
                $expert_max = get_post_meta($post->ID, '_moaveze_expert_max', true);
                ?>
                <div class="valuation-current valuation-current-expert">
                    <strong>قیمت کارشناسی فعلاً منتشرشده روی آگهی:</strong>
                    <span class="valuation-current-value">
                        <?php echo esc_html(Moaveze_Helpers::short_price($expert_value)); ?>
                        <?php if ($expert_min && $expert_max) : ?>
                            <small>(محدوده: <?php echo esc_html(Moaveze_Helpers::short_price($expert_min)); ?> تا <?php echo esc_html(Moaveze_Helpers::short_price($expert_max)); ?>)</small>
                        <?php endif; ?>
                    </span>
                    <p class="description">این مقدار جدا از ارزش مالک، در کادر مجزای «قیمت کارشناسی» روی صفحه آگهی نمایش داده می‌شود و هرگز جای قیمت مالک را نمی‌گیرد.</p>
                </div>
            <?php endif; ?>

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
                        <label>قیمت کارشناسی (تومان)</label>
                        <input type="text" class="moaveze-price-input valuation-manual-value" placeholder="مثال: 15000000000">
                    </div>
                    <div class="moaveze-field">
                        <label>حداقل محدوده (اختیاری)</label>
                        <input type="text" class="moaveze-price-input valuation-manual-min" placeholder="مثال: 14000000000">
                    </div>
                    <div class="moaveze-field">
                        <label>حداکثر محدوده (اختیاری)</label>
                        <input type="text" class="moaveze-price-input valuation-manual-max" placeholder="مثال: 16000000000">
                    </div>
                </div>
                <div class="moaveze-field">
                    <label>یادداشت / دلیل ارزش‌گذاری</label>
                    <textarea class="valuation-manual-notes" rows="2" placeholder="مثال: با توجه به موقعیت و بازسازی، ارزش واقعی بالاتر از ثبت مالک است."></textarea>
                </div>
                <p class="description">این قیمت هرگز جای «ارزش ملک» که مالک ثبت کرده را نمی‌گیرد؛ بعد از اعمال، در کادر مجزای «قیمت کارشناسی» روی صفحه آگهی نمایش داده می‌شود.</p>
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
                        متراژ، سال ساخت، امکانات، مختصات جغرافیایی و دانش خود از بازار مسکن تبریز، ارزش پیشنهادی ارائه می‌دهد.
                    </p>
                    <?php if ($this->is_grounding_active()) : ?>
                        <p class="description" style="color:#065f46;background:#d1fae5;padding:8px 12px;border-radius:8px;">
                            🔍 جست‌وجوی واقعی وب فعال است - هوش مصنوعی آگهی‌های واقعی مشابه (از جمله دیوار) را جست‌وجو می‌کند و لینک واقعی هر مورد را ارائه می‌دهد.
                        </p>
                    <?php else : ?>
                        <p class="description" style="color:#92400e;background:#fef3c7;padding:8px 12px;border-radius:8px;">
                            📚 جست‌وجوی وب غیرفعال است - نمونه‌های مشابه صرفاً بر اساس دانش قبلی مدل خواهند بود و لینک واقعی نخواهند داشت.
                            برای فعال‌سازی به <a href="<?php echo admin_url('admin.php?page=moaveze-settings&tab=ai'); ?>">تنظیمات &gt; هوش مصنوعی</a> بروید (فقط با Gemini و بدون رله Cloudflare Worker کار می‌کند).
                        </p>
                    <?php endif; ?>
                    <?php
                    $active_model = get_option("moaveze_ai_{$active_provider}_model") ?: $providers[$active_provider]['default_model'];
                    if ($active_provider === 'gemini' && stripos($active_model, '-lite') !== false) :
                    ?>
                        <p class="description" style="color:#92400e;background:#fef3c7;padding:8px 12px;border-radius:8px;">
                            ⚠ مدل فعلی (<code><?php echo esc_html($active_model); ?></code>) یک مدل «Lite» است که برای دقت تحلیلی بالا (مثل ارزش‌گذاری ملک) بهینه نشده و ممکن است اعداد کمتر دقیقی ارائه دهد. در صورت امکان به یک مدل استاندارد (بدون Lite) از
                            <a href="<?php echo admin_url('admin.php?page=moaveze-settings&tab=ai'); ?>">تنظیمات &gt; هوش مصنوعی</a> تغییر دهید.
                        </p>
                    <?php endif; ?>
                    <button type="button" class="button button-primary valuation-run-ai-btn">
                        <span class="dashicons dashicons-superhero-alt"></span> دریافت پیشنهاد هوش مصنوعی
                    </button>
                    <div class="valuation-ai-result" style="display:none;"></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($history)) : ?>
                <div class="valuation-history">
                    <h4>تاریخچه ارزش‌گذاری‌ها (<?php echo count($history); ?>)</h4>
                    <?php foreach ($history as $v) : ?>
                        <div class="valuation-history-item" data-valuation-id="<?php echo esc_attr($v->id); ?>">
                            <span class="vh-type <?php echo esc_attr($v->type); ?>">
                                <?php echo $v->type === 'ai' ? '🤖 هوش مصنوعی' : '👤 دستی'; ?>
                                <?php if ($v->type === 'ai' && $v->ai_provider) echo ' (' . esc_html($providers[$v->ai_provider]['label'] ?? $v->ai_provider) . ')'; ?>
                            </span>
                            <span class="vh-value">
                                <?php echo esc_html(Moaveze_Helpers::short_price($v->manual_value ?: $v->suggested_value)); ?>
                            </span>
                            <span class="vh-date"><?php echo esc_html(Moaveze_Helpers::jalali_date($v->created_at, 'Y/m/d H:i')); ?></span>
                            <span class="vh-actions">
                                <?php if ($v->status === 'applied') : ?>
                                    <span class="vh-applied">✓ اعمال شده روی آگهی</span>
                                <?php else : ?>
                                    <button type="button" class="button button-small valuation-apply-btn" data-valuation-id="<?php echo esc_attr($v->id); ?>">
                                        اعمال روی آگهی
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="button button-small valuation-view-btn" data-valuation-id="<?php echo esc_attr($v->id); ?>" title="مشاهده جزئیات کامل">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                                <button type="button" class="button button-small valuation-delete-btn" data-valuation-id="<?php echo esc_attr($v->id); ?>" title="حذف این ارزش‌گذاری">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </span>
                        </div>
                        <div class="valuation-history-details" data-valuation-id="<?php echo esc_attr($v->id); ?>" style="display:none;"></div>
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
        $digit_clean = fn($s) => absint(preg_replace('/[^\d]/', '', (string) $s));
        $value = $digit_clean($_POST['value'] ?? '');
        $min_value = !empty($_POST['min_value']) ? $digit_clean($_POST['min_value']) : null;
        $max_value = !empty($_POST['max_value']) ? $digit_clean($_POST['max_value']) : null;
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$exchange_id || !$value) {
            wp_send_json_error('لطفاً مبلغ ارزش‌گذاری را وارد کنید');
        }

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'moaveze_valuations', array(
            'exchange_id'      => $exchange_id,
            'post_id'          => $post_id,
            'type'             => 'manual',
            'manual_value'     => $value,
            'manual_min_value' => $min_value,
            'manual_max_value' => $max_value,
            'manual_notes'     => $notes,
            'status'           => 'pending',
            'created_by'       => get_current_user_id(),
        ));

        wp_send_json_success(array(
            'message'       => 'ارزش‌گذاری دستی ذخیره شد',
            'valuation_id'  => $wpdb->insert_id,
            'value_short'   => Moaveze_Helpers::short_price($value),
        ));
    }

    /**
     * AJAX: apply a saved valuation (manual or AI) to the listing.
     *
     * IMPORTANT (per explicit user correction): this must NEVER replace
     * the owner's own declared "ارزش ملک" (_moaveze_property_value /
     * moaveze_exchanges.property_value). The consultant/AI valuation is
     * always stored and displayed as a SEPARATE "قیمت کارشناسی" (expert
     * price / range) box - see _moaveze_expert_value* post meta below
     * and the dedicated card rendered in templates/single-exchange.php.
     * "Applying" a valuation now means: publish it as the listing's
     * official expert-assessed value/range, visible to everyone
     * alongside (never instead of) the owner's original price.
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

        $expert_value = $valuation->manual_value ?: $valuation->suggested_value;
        $expert_min = $valuation->manual_min_value ?: $valuation->suggested_min;
        $expert_max = $valuation->manual_max_value ?: $valuation->suggested_max;
        if (!$expert_value) wp_send_json_error('مقدار ارزش نامعتبر است');

        // Stored on POST META only (a display-layer addition) - the
        // owner's original price in _moaveze_property_value / the
        // moaveze_exchanges.property_value column is left completely
        // untouched.
        update_post_meta($valuation->post_id, '_moaveze_expert_value', $expert_value);
        update_post_meta($valuation->post_id, '_moaveze_expert_min', $expert_min ?: '');
        update_post_meta($valuation->post_id, '_moaveze_expert_max', $expert_max ?: '');
        update_post_meta($valuation->post_id, '_moaveze_expert_value_type', $valuation->type);
        update_post_meta($valuation->post_id, '_moaveze_expert_value_date', current_time('mysql'));

        $wpdb->update(
            $wpdb->prefix . 'moaveze_valuations',
            array('status' => 'applied'),
            array('id' => $valuation_id)
        );

        wp_send_json_success(array(
            'message' => 'قیمت کارشناسی به‌صورت جداگانه روی آگهی نمایش داده شد (قیمت مالک تغییر نکرد)',
            'value_short' => Moaveze_Helpers::short_price($expert_value),
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
        $parsed['comparables'] = $this->verify_comparable_urls($parsed['comparables'], $result['grounded_urls'] ?? array());
        $parsed['grounded'] = $this->is_grounding_active();

        $wpdb->insert($wpdb->prefix . 'moaveze_valuations', array(
            'exchange_id'      => $exchange_id,
            'post_id'          => $exchange->post_id,
            'type'             => 'ai',
            'ai_provider'      => $provider,
            'suggested_value'  => $parsed['value'],
            'suggested_min'    => $parsed['min'],
            'suggested_max'    => $parsed['max'],
            'confidence'       => $parsed['confidence'],
            'reasoning'        => $parsed['methodology'] ? ($parsed['methodology'] . "\n\n" . $parsed['reasoning']) : $parsed['reasoning'],
            'comparables'      => wp_json_encode(array(
                'price_per_sqm'        => $parsed['price_per_sqm'],
                'grounded'             => $parsed['grounded'],
                'items'                => $parsed['comparables'],
                'all_grounded_sources' => array_map(function ($s) {
                    return array(
                        'title'   => $s['title'] ?: 'منبع بدون عنوان',
                        'url'     => $s['resolved'] ?: $s['redirect'],
                        'is_dead' => $s['is_dead'],
                    );
                }, $result['grounded_urls'] ?? array()),
            )),
            'raw_response'     => $result['text'],
            'status'           => 'pending',
            'created_by'       => get_current_user_id(),
        ));

        wp_send_json_success(array(
            'valuation_id'  => $wpdb->insert_id,
            'value'         => $parsed['value'],
            'value_short'   => Moaveze_Helpers::short_price($parsed['value']),
            'min_short'     => $parsed['min'] ? Moaveze_Helpers::short_price($parsed['min']) : null,
            'max_short'     => $parsed['max'] ? Moaveze_Helpers::short_price($parsed['max']) : null,
            'price_per_sqm_short' => $parsed['price_per_sqm'] ? Moaveze_Helpers::short_price($parsed['price_per_sqm']) : null,
            'confidence'    => $parsed['confidence'],
            'methodology'   => $parsed['methodology'],
            'reasoning'     => $parsed['reasoning'],
            'grounded'      => $parsed['grounded'],
            'comparables'   => $this->format_comparables_for_display($parsed['comparables']),
            // The FULL raw list of every source Google's search really
            // retrieved for this answer, regardless of whether the AI
            // matched it to a specific comparable above - shown as
            // independent proof a real search happened, per the user's
            // explicit request to be able to verify this ("تا ما بفهمیم
            // واقعا استناد میکنه یا نه").
            'all_grounded_sources' => array_map(function ($s) {
                return array(
                    'title'   => $s['title'] ?: 'منبع بدون عنوان',
                    'url'     => $s['resolved'] ?: $s['redirect'],
                    'is_dead' => $s['is_dead'],
                );
            }, $result['grounded_urls'] ?? array()),
            'provider'      => self::get_providers()[$provider]['label'] ?? $provider,
        ));
    }

    /**
     * Shape a comparables array (description/price_total/price_per_sqm/
     * source_url/url_status) for the AJAX JSON response - shared
     * between the "just ran AI" and "viewing past history" code paths
     * so both always render identically.
     */
    private function format_comparables_for_display($comparables) {
        return array_map(function ($c) {
            return array(
                'description'         => $c['description'],
                'price_total_short'   => $c['price_total'] ? Moaveze_Helpers::short_price($c['price_total']) : null,
                'price_per_sqm_short' => $c['price_per_sqm'] ? Moaveze_Helpers::short_price($c['price_per_sqm']) : null,
                'source_url'          => $c['source_url'] ?? '',
                // Per explicit user request: the comparable listing's
                // own publish/update date, as reported by the AI from
                // the real page it visited (empty if not available/not
                // grounded) - shown next to each comparable so the
                // consultant can judge how current it is.
                'listing_date'        => $c['listing_date'] ?? '',
                // 'verified'            = real page URL resolved, Google-confirmed, and still LIVE when checked
                // 'verified_dead'       = Google-confirmed and resolved, but the page itself now returns 404/410/451 (e.g. a Divar listing removed after being sold) - still real proof a search happened, just an outdated result
                // 'verified_unresolved' = Google-confirmed but only Google's redirect link is available (page resolution failed)
                // 'unverified'          = model gave a link Google doesn't independently confirm
                // 'none'                = no link at all (grounding was off)
                'url_status'          => $c['url_status'] ?? 'none',
            );
        }, $comparables);
    }

    /**
     * Resolve the listing's district/property-type as reliably as
     * possible, with a defensive fallback chain:
     *   1. moaveze_exchanges.district / .property_type columns (now
     *      correct for anything saved after the taxonomy-slug bug fix -
     *      see Moaveze_Submission_Form::resolve_term_name()).
     *   2. The post's own moaveze_district / moaveze_property_type
     *      TAXONOMY terms (authoritative source, always correct even
     *      for legacy rows where the exchanges-table column might still
     *      hold stale/bad data from before the fix).
     *   3. Explicitly "نامشخص" only as an absolute last resort - this
     *      is what previously caused the AI to say "به دلیل عدم تعیین
     *      دقیق منطقه/محله ... ارزش‌گذاری با عدم قطعیت بالایی همراه
     *      است"، since the taxonomy-slug bug meant these were very
     *      often actually empty at the time.
     */
    private function resolve_district_and_type($exchange) {
        $district_terms = get_the_terms($exchange->post_id, 'moaveze_district');
        $type_terms = get_the_terms($exchange->post_id, 'moaveze_property_type');

        $district = $exchange->district ?: '';
        if (!$district && $district_terms && !is_wp_error($district_terms)) {
            $district = $district_terms[0]->name;
        }

        $property_type = $exchange->property_type ?: '';
        if (!$property_type && $type_terms && !is_wp_error($type_terms)) {
            $property_type = $type_terms[0]->name;
        }

        return array(
            'district'      => $district ?: 'نامشخص',
            'property_type' => $property_type ?: 'نامشخص',
        );
    }

    /**
     * Build the structured Persian prompt describing the listing and
     * asking the AI to reason like a certified, methodical Tabriz
     * real-estate appraiser (کارشناس رسمی ارزیابی املاک), grounded in
     * comparable listings it is aware of (e.g. from Divar-style
     * classifieds), and to answer in a strict, structured JSON shape
     * (including a real comparables ARRAY, not just a free-text note)
     * we can reliably parse and render in a technical, professional
     * format for the consultant.
     */
    private function build_valuation_prompt($exchange) {
        $resolved = $this->resolve_district_and_type($exchange);
        $feature_terms = wp_get_post_terms($exchange->post_id, 'moaveze_feature', array('fields' => 'names'));
        $year_built = get_post_meta($exchange->post_id, '_moaveze_year_built', true);
        $rooms = get_post_meta($exchange->post_id, '_moaveze_rooms', true);
        $floor = get_post_meta($exchange->post_id, '_moaveze_floor', true);
        $total_floors = get_post_meta($exchange->post_id, '_moaveze_total_floors', true);
        $address = get_post_meta($exchange->post_id, '_moaveze_address', true);
        // Real geo-coordinates saved on the listing (map picker /
        // Houzez import) - per explicit user request ("موقعیت
        // جغرافیایی ثبت شده در آگهی هم در قیمت گذاری قید شود"), these
        // are now included so the AI can reason about the EXACT
        // micro-location within the district, not just the district
        // name, and (when grounding is enabled) search near these
        // coordinates specifically.
        $lat = get_post_meta($exchange->post_id, '_moaveze_latitude', true);
        $lng = get_post_meta($exchange->post_id, '_moaveze_longitude', true);
        $area = (int) $exchange->area_sqm;
        $owner_value = (int) $exchange->property_value;
        $owner_price_per_sqm = $area > 0 ? round($owner_value / $area) : 0;
        $current_year_jalali = Moaveze_Helpers::jalali_date(current_time('mysql'), 'Y');
        $building_age = ($year_built && $current_year_jalali) ? max(0, (int) $current_year_jalali - (int) $year_built) : null;
        $grounding_active = $this->is_grounding_active();

        $details = array(
            'شهر'                       => 'تبریز',
            'منطقه/محله'                => $resolved['district'],
            'آدرس تقریبی'               => $address ?: 'ثبت نشده',
            'مختصات جغرافیایی دقیق (Lat, Lng)' => ($lat && $lng) ? "{$lat}, {$lng}" : 'ثبت نشده',
            'نوع ملک'                   => $resolved['property_type'],
            'متراژ زیربنا'              => $area . ' متر مربع',
            'تعداد اتاق خواب'           => $rooms ?: 'نامشخص',
            'طبقه'                      => ($floor !== '' ? $floor : 'نامشخص') . ($total_floors ? " از {$total_floors} طبقه" : ''),
            'سال ساخت (شمسی)'           => $year_built ?: 'نامشخص',
            'قدمت بنا (سال)'            => $building_age !== null ? $building_age : 'نامشخص',
            'امکانات و ویژگی‌ها'        => !empty($feature_terms) ? implode('، ', $feature_terms) : 'ثبت نشده',
            'ارزش ثبت‌شده توسط مالک'    => number_format($owner_value) . ' تومان',
            'قیمت هر متر (بر اساس ادعای مالک)' => $owner_price_per_sqm ? number_format($owner_price_per_sqm) . ' تومان/متر' : 'نامشخص',
        );

        $details_text = '';
        foreach ($details as $label => $value) {
            $details_text .= "- {$label}: {$value}\n";
        }

        // Two very different instruction sets depending on whether real
        // web search (grounding) is actually available for this call:
        //   - GROUNDED: the model can genuinely search the live web
        //     (e.g. Divar), so we REQUIRE a real, working source_url for
        //     every comparable, and explicitly forbid fabricating one.
        //   - NOT GROUNDED: the model can only draw on its training
        //     data, so we REQUIRE it to be explicit that comparables are
        //     illustrative estimates with NO real source_url, rather
        //     than letting it invent a plausible-looking but fake link
        //     (per explicit user request: "باید دقیقا استناد کنه به
        //     لینک اونم ... تا ما بفهمیم واقعا استناد میکنه یا نه").
        $location_instruction = ($lat && $lng)
            ? "با استفاده از مختصات جغرافیایی دقیق ملک (بالا)، موقعیت ریز آن را نسبت به خیابان‌های اصلی، مراکز خرید، و کیفیت محل در نظر بگیر - نه فقط نام کلی منطقه."
            : "مختصات دقیق ثبت نشده؛ فقط بر اساس نام منطقه/محله تخمین بزن و این محدودیت را در reasoning ذکر کن.";

        if ($grounding_active) {
            $comparable_instruction = <<<TXT
۴. با استفاده از ابزار جست‌وجوی وب که در اختیار داری، آگهی‌های واقعی و در حال حاضر منتشرشده (مخصوصاً در دیوار - divar.ir - و در صورت امکان شیپور) برای ملک‌های مشابه نزدیک به همین منطقه/مختصات را واقعاً جست‌وجو و پیدا کن.
۵. برای هر نمونه مشابهی که ارائه می‌دهی، حتماً و بدون استثنا لینک واقعی و کامل آن آگهی (source_url) را هم بده - این لینک باید از نتایج جست‌وجوی واقعی تو باشد، نه یک لینک ساختگی یا تخمینی. اگر برای یک مورد نتوانستی لینک واقعی پیدا کنی، آن مورد را در پاسخ نگذار.
۶. اگر بعد از جست‌وجو هیچ نمونه واقعی مرتبطی پیدا نکردی، آرایه comparables را خالی برگردان و این موضوع را صادقانه در reasoning بگو - هرگز نمونه ساختگی با لینک تقلبی نساز.
TXT;
        } else {
            $comparable_instruction = <<<TXT
۴. چون قابلیت جست‌وجوی زنده وب برای این درخواست فعال نیست، حداقل ۲ الی ۳ نمونه مشابه بر اساس دانش کلی‌ات از بازار تبریز ارائه بده، اما این نمونه‌ها را با source_url خالی (رشته خالی "") برگردان - به هیچ عنوان لینک ساختگی یا تخمینی برای source_url نساز، چون کارشناس باید بتواند تشخیص دهد کدام موارد واقعاً منبع قابل‌بررسی دارند و کدام صرفاً مثال‌های احتمالی هستند.
TXT;
        }

        return <<<PROMPT
تو یک کارشناس رسمی و باتجربه ارزیابی املاک و مستغلات (کارشناس کانون ارزیابان) در شهر تبریز، ایران هستی. روش کار تو کاملاً فنی، حرفه‌ای و مبتنی بر داده است - نه یک تخمین سطحی. با قیمت‌های واقعی بازار مسکن تبریز و روند قیمت هر منطقه کاملاً آشنایی داری.

روش ارزیابی که باید دنبال کنی (رویکرد مقایسه‌ای/Sales Comparison Approach):
۱. ابتدا میانگین قیمت هر متر مربع را برای «همین منطقه» و «همین نوع ملک» تخمین بزن. {$location_instruction}
۲. سپس با توجه به عوامل تعدیل‌کننده (Adjustment Factors) زیر، این قیمت پایه هر متر را برای این ملک خاص تعدیل کن:
   - سال ساخت / قدمت بنا (بنای نوساز معمولاً ۱۰ تا ۲۵ درصد نسبت به بنای قدیمی‌تر در همان منطقه صرافه بیشتری دارد)
   - طبقه و تعداد کل طبقات (طبقات میانی معمولاً ارزش بالاتری نسبت به همکف یا طبقه آخر بدون آسانسور دارند)
   - امکانات (آسانسور، پارکینگ، انباری و... هرکدام معمولاً چند درصد به ارزش می‌افزایند)
   - متراژ (واحدهای بسیار کوچک یا بسیار بزرگ نسبت به میانگین منطقه معمولاً قیمت هر متر متفاوتی دارند)
   - موقعیت دقیق در منطقه (نزدیکی به خیابان اصلی، امکانات رفاهی، حمل‌ونقل عمومی)
۳. حاصل‌ضرب قیمت پایه تعدیل‌شده هر متر در متراژ را به‌عنوان ارزش نهایی محاسبه کن.
{$comparable_instruction}
۷. اگر منطقه یا نوع ملک نامشخص بود، این را در بخش reasoning صریحاً بگو و سطح اطمینان (confidence) را متناسب با آن پایین‌تر تنظیم کن؛ در غیر این صورت از عبارات کلی و مبهم مثل "عدم قطعیت بالا" خودداری کن و مستقیماً بر اساس داده‌های داده‌شده تحلیل کن.

مشخصات کامل ملک:
{$details_text}

پاسخ را دقیقاً و فقط به‌صورت یک JSON با این ساختار بده (بدون هیچ توضیح اضافه، مقدمه، یا متن قبل/بعد از JSON، و بدون markdown code fence):

{
  "estimated_value": <عدد به تومان، بدون جداکننده هزارگان - ارزش نهایی تخمینی>,
  "min_value": <کمترین مقدار محدوده منطقی به تومان>,
  "max_value": <بیشترین مقدار محدوده منطقی به تومان>,
  "price_per_sqm": <قیمت پیشنهادی هر متر مربع به تومان، پس از تمام تعدیل‌ها>,
  "confidence": "<یکی از: بالا, متوسط, پایین>",
  "methodology_summary": "<۲-۳ جمله فارسی: قیمت پایه هر متر منطقه که فرض کردی + مهم‌ترین عوامل تعدیل‌کننده که اعمال کردی و جهت هرکدام (مثبت/منفی)>",
  "reasoning": "<تحلیل فنی و مبتنی بر داده، حداکثر ۵-۶ جمله فارسی، درباره دلیل این ارزش‌گذاری، مقایسه با قیمت ادعایی مالک، و هرگونه ریسک یا نقطه ضعف در داده‌های موجود>",
  "comparables": [
    {"description": "<توضیح کوتاه نمونه مشابه اول: منطقه، متراژ، سال ساخت>", "price_total": <عدد تومان>, "price_per_sqm": <عدد تومان>, "source_url": "<لینک کامل و واقعی آگهی منبع، یا رشته خالی اگر واقعی نیست>", "listing_date": "<تاریخ انتشار یا آخرین بروزرسانی این آگهی، اگر از صفحه واقعی آن قابل مشاهده بود (میلادی یا شمسی، هرکدام که در صفحه نوشته شده)؛ در غیر این صورت رشته خالی>"}
  ]
}
PROMPT;
    }

    /**
     * Is real web-search grounding actually going to be used for the
     * NEXT AI call? True only when the admin has enabled it AND the
     * currently-active provider is Gemini (the only provider this
     * plugin currently wires up Google's native Search-grounding tool
     * for - see call_gemini()). Used both to select which prompt
     * instructions to send and to accurately label the result as
     * "واقعاً جست‌وجوشده" vs "صرفاً بر اساس دانش قبلی مدل" so the
     * consultant is never misled about whether a link is real.
     */
    /**
     * Translate Google's raw Gemini API error text into an explicit,
     * actionable Persian message - specifically to answer "آیا این
     * ارور به خاطر خطای خود افزونه است؟" (is this our bug?). Google's
     * "This model is currently experiencing high demand" (a 503
     * UNAVAILABLE response) is a well-documented, TEMPORARY overload
     * of that SPECIFIC free-tier model on Google's servers, completely
     * unrelated to the site's remaining API credit/quota, and not
     * something this plugin can fix from its side - it means "try
     * again in a bit" or "switch to a different model" (each model has
     * its own separate capacity/rate-limit pool, so a less-loaded model
     * like gemini-2.0-flash-lite often works immediately even while
     * gemini-1.5-flash is overloaded).
     */
    private function translate_gemini_error($raw_message) {
        $lower = strtolower($raw_message);
        // NOTE: uses strpos() rather than str_contains() (PHP 8.0+)
        // since this plugin declares "Requires PHP: 7.4" in its header.
        $contains = function ($haystack, $needle) {
            return strpos($haystack, $needle) !== false;
        };

        if ($contains($lower, 'high demand') || $contains($lower, 'overloaded') || $contains($lower, 'unavailable')) {
            return 'این خطا از سمت خود Google است، نه باگ افزونه: مدل انتخابی شما (Gemini) موقتاً با ترافیک بالا مواجه شده و سرورهای گوگل پاسخ نمی‌دهند. '
                . 'این موضوع ارتباطی به اعتبار/کردیت باقیمانده شما ندارد. '
                . 'راهکار: (۱) چند دقیقه بعد دوباره تلاش کنید، یا (۲) از تنظیمات > هوش مصنوعی، روی «دریافت لیست مدل‌های موجود» کلیک کنید و یک مدل دیگر مثل gemini-2.0-flash-lite را انتخاب کنید - '
                . 'هر مدل ظرفیت جداگانه‌ای دارد و معمولاً وقتی یک مدل شلوغ است، مدل دیگر بلافاصله در دسترس است. (متن اصلی خطای گوگل: "' . $raw_message . '")';
        }

        if ($contains($lower, 'quota') || $contains($lower, 'rate limit') || $contains($lower, 'resource_exhausted')) {
            return 'این خطا از سمت خود Google است، نه باگ افزونه: سهمیه رایگان این مدل برای این دقیقه/روز به پایان رسیده است. '
                . 'راهکار: کمی صبر کنید، یا مدل دیگری انتخاب کنید، یا (در صورت نیاز به استفاده بیشتر) یک حساب پولی/billing برای این کلید API فعال کنید. '
                . '(متن اصلی خطای گوگل: "' . $raw_message . '")';
        }

        return $raw_message;
    }

    private function is_grounding_active() {
        if (get_option('moaveze_ai_grounding_enabled') !== 'yes') return false;
        // Grounding is only wired up for the direct Gemini call path;
        // if the Cloudflare Worker relay is active, we can't guarantee
        // the tool is forwarded correctly, so treat it as unavailable.
        if (get_option('moaveze_ai_worker_enabled') === 'yes') return false;
        $provider = get_option('moaveze_ai_active_provider', 'gemini');
        return $provider === 'gemini';
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

        // Comparables are now a structured ARRAY of {description,
        // price_total, price_per_sqm} objects (previously a single
        // free-text string) - build a human-readable summary for
        // display while keeping the structured data available too.
        $comparables_structured = array();
        if (!empty($data['comparables']) && is_array($data['comparables'])) {
            foreach ($data['comparables'] as $c) {
                if (!is_array($c)) continue;
                $raw_url = trim($c['source_url'] ?? '');
                $comparables_structured[] = array(
                    'description'   => $c['description'] ?? '',
                    'price_total'   => isset($c['price_total']) ? absint($c['price_total']) : null,
                    'price_per_sqm' => isset($c['price_per_sqm']) ? absint($c['price_per_sqm']) : null,
                    // Only kept if it's a real, well-formed URL - see
                    // verify_comparable_urls() below, which additionally
                    // cross-checks each URL against Google's own
                    // grounding-verified list before trusting it.
                    'source_url'    => filter_var($raw_url, FILTER_VALIDATE_URL) ? $raw_url : '',
                    // Per explicit user request: show the comparable
                    // listing's own publish/update date (as read
                    // directly off its real page during the search) so
                    // the consultant can judge how current a comparable
                    // actually is - not derived/guessed, only ever
                    // whatever the model reports seeing on the page.
                    'listing_date'  => sanitize_text_field($c['listing_date'] ?? ''),
                );
            }
        }

        return array(
            'value'         => isset($data['estimated_value']) ? absint($data['estimated_value']) : 0,
            'price_per_sqm' => isset($data['price_per_sqm']) ? absint($data['price_per_sqm']) : null,
            'methodology'   => $data['methodology_summary'] ?? '',
            'min'           => isset($data['min_value']) ? absint($data['min_value']) : null,
            'max'           => isset($data['max_value']) ? absint($data['max_value']) : null,
            'confidence'    => $data['confidence'] ?? 'نامشخص',
            'reasoning'     => $data['reasoning'] ?? mb_substr($text, 0, 500),
            // Structured array of {description, price_total, price_per_sqm,
            // source_url} (previously a single free-text
            // "comparable_notes" string with no links at all) - stored
            // as-is (json-encoded) in the DB `comparables` column and
            // rendered as a real mini-table with clickable links in the
            // metabox/history.
            'comparables'   => $comparables_structured,
        );
    }

    /**
     * Cross-check every comparable's self-reported source_url against
     * the list of URLs Google's grounding tool actually confirms were
     * retrieved for this answer (see call_gemini() docblock). This
     * exists because a language model can still WRITE a plausible-
     * looking URL in its JSON output even when asked not to fabricate
     * one - the model's own text output is not proof it actually
     * visited that page. Only a URL that also appears in the
     * independently-returned groundingMetadata is labeled "verified"
     * (✓ لینک تأییدشده توسط جست‌وجوی گوگل); anything else is downgraded
     * to "ادعا شده - تأیید نشده" so the consultant can tell the
     * difference at a glance, per the explicit user request to be able
     * to tell "آیا واقعا استناد میکنه یا نه".
     */
    private function verify_comparable_urls($comparables, $grounded_sources) {
        // Normalize both sides (strip protocol/www/trailing slash) for a
        // resilient-but-still-meaningful comparison, since the model's
        // quoted URL and Google's own redirect-link value can differ in
        // trivial formatting while pointing at the same real page.
        $normalize = function ($url) {
            $url = preg_replace('#^https?://(www\.)?#i', '', trim($url));
            return rtrim($url, '/');
        };

        foreach ($comparables as &$c) {
            if (empty($c['source_url'])) {
                $c['url_status'] = 'none';
                continue;
            }
            // The model can only ever have echoed back Google's own
            // redirect-link value (it has no access to the real
            // underlying URL either - see resolve_redirect_url()
            // docblock), so we match against the REDIRECT link, then
            // substitute in the already-resolved real destination URL
            // for display, so the consultant sees the raw listing link
            // rather than Google's opaque wrapper.
            $normalized_claimed = $normalize($c['source_url']);
            $match = null;
            foreach ($grounded_sources as $source) {
                if ($normalize($source['redirect']) === $normalized_claimed) {
                    $match = $source;
                    break;
                }
            }

            if ($match && $match['resolved']) {
                $c['url_status'] = $match['is_dead'] ? 'verified_dead' : 'verified';
                $c['source_url'] = $match['resolved'];
            } elseif ($match) {
                // Google confirmed this source was really used, but our
                // own redirect-resolution attempt failed (e.g. a
                // transient network error) - still mark it verified
                // and fall back to the redirect link itself rather
                // than silently dropping a genuinely-confirmed source.
                $c['url_status'] = 'verified_unresolved';
                $c['source_url'] = $match['redirect'];
            } else {
                $c['url_status'] = 'unverified';
            }
        }

        return $comparables;
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
                return $this->call_gemini($url, $model, $api_key, $prompt, $this->is_grounding_active());
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
     * Google Gemini (generateContent API).
     *
     * When $use_grounding is true, this enables Google's native
     * "Grounding with Google Search" tool (the `tools: [{google_search:
     * {}}]` parameter documented at
     * https://ai.google.dev/gemini-api/docs/grounding) so the model
     * genuinely searches the live web (including real estate listing
     * sites like Divar) before answering, instead of only drawing on
     * its static training data. The API additionally returns
     * `groundingMetadata.groundingChunks[].web.uri` - the REAL source
     * URLs it actually used - which we extract and pass back alongside
     * the answer text so the calling code can cross-check the model's
     * self-reported comparable links against a list of URLs Google
     * confirms were genuinely retrieved for this specific answer.
     */
    private function call_gemini($url, $model, $api_key, $prompt, $use_grounding = false) {
        $endpoint = str_replace(array('{model}', '{api_key}'), array($model, $api_key), $url);

        $request_body = array(
            'contents' => array(
                array('parts' => array(array('text' => $prompt))),
            ),
        );
        if ($use_grounding) {
            $request_body['tools'] = array(array('google_search' => new stdClass()));
        }

        $response = wp_remote_post($endpoint, $this->http_args(array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($request_body),
        )));

        if (is_wp_error($response)) {
            return array('success' => false, 'text' => '', 'error' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $candidate = $body['candidates'][0] ?? array();
        $text = $candidate['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            $raw_message = $body['error']['message'] ?? 'پاسخ نامعتبر از Gemini';
            return array('success' => false, 'text' => '', 'error' => $this->translate_gemini_error($raw_message));
        }

        // Extract the URLs Google's grounding tool actually used for
        // this answer, then RESOLVE each one ourselves to its real,
        // final destination page - see resolve_redirect_url() docblock
        // for why this extra step is necessary (Google's API only ever
        // returns its own opaque tracking-redirect link, never the raw
        // underlying page URL, per Google's own documented limitation).
        $grounded_urls = array();
        $chunks = $candidate['groundingMetadata']['groundingChunks'] ?? array();
        foreach ($chunks as $chunk) {
            if (empty($chunk['web']['uri'])) continue;
            $redirect_url = $chunk['web']['uri'];
            $resolved = $this->resolve_redirect_url($redirect_url);
            $grounded_urls[] = array(
                'redirect'  => $redirect_url,
                'resolved'  => $resolved['url'] ?: '',
                // Was the resolved page still actually live when we
                // just checked it? See resolve_redirect_url() docblock
                // for why this check exists - a real example the user
                // hit was a resolved divar.ir/v/... link that returned
                // HTTP 410 Gone (the listing had since been deleted/
                // sold on Divar's side) - a link Google's search
                // genuinely found at crawl time can still go dead by
                // the time a consultant clicks it later, since Divar
                // listings are frequently removed once sold/expired.
                'is_dead'   => $resolved['is_dead'],
                'title'     => $chunk['web']['title'] ?? '',
            );
        }

        return array('success' => true, 'text' => $text, 'error' => '', 'grounded_urls' => $grounded_urls);
    }

    /**
     * Follow an HTTP redirect chain server-side and return the FINAL
     * destination URL - used to unwrap Google's
     * "vertexaisearch.cloud.google.com/grounding-api-redirect/..."
     * tracking links into the actual raw listing URL (e.g. a real
     * divar.ir link) the user explicitly asked to see, instead of
     * Google's opaque wrapper link.
     *
     * BACKGROUND: this wrapper-link behavior is a documented, currently
     * unresolved limitation of Google's Gemini grounding API - the
     * groundingChunks[].web.uri field NEVER contains the raw source
     * URL directly, only this redirect link (confirmed via an open
     * feature request on Google's own AI developer forum:
     * https://discuss.ai.google.dev/t/feature-request-provide-actual-source-urls-in-grounding-metadata/107352).
     * The redirect link is still a genuine, Google-issued, clickable
     * link that correctly forwards to the real page when opened - it
     * is not fake or broken - but showing that long opaque token to a
     * consultant instead of the real listing URL is exactly the "لینک
     * خام" problem the user reported, so we resolve it ourselves here.
     */
    private function resolve_redirect_url($url) {
        // A realistic browser User-Agent is important here - some
        // sites (including classifieds sites) block requests carrying
        // WordPress's default HTTP API user-agent string.
        $response = wp_remote_get($url, array(
            'timeout'     => 12,
            'redirection' => 5,
            'sslverify'   => false,
            'headers'     => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            ),
        ));

        if (is_wp_error($response)) {
            return array('url' => null, 'is_dead' => null); // couldn't even check - unknown, not necessarily dead
        }

        $status_code = wp_remote_retrieve_response_code($response);
        // DEAD-LINK DETECTION: per user report, a resolved divar.ir
        // link Gemini's search genuinely retrieved could still be a
        // blank/removed page by the time it's clicked (HTTP 410 Gone
        // in the example, confirmed by directly checking the URL - the
        // listing had been deleted/sold on Divar's side since the
        // search happened). 404/410/451 are unambiguous "this specific
        // page no longer exists" signals; treat anything else as alive
        // (a real classifieds site legitimately returns other codes
        // for unrelated reasons, e.g. temporary redirects, bot
        // challenges, etc, which shouldn't be mislabeled as "dead").
        $is_dead = in_array($status_code, array(404, 410, 451), true);

        // WordPress's HTTP API (backed by the Requests library since
        // WP 4.6) tracks the final URL reached after following the
        // full redirect chain on the wrapped response object - this is
        // the only reliable way to get it via wp_remote_get().
        $final_url = null;
        $http_response = $response['http_response'] ?? null;
        if ($http_response instanceof WP_HTTP_Requests_Response) {
            $requests_response = $http_response->get_response_object();
            if (!empty($requests_response->url) && $requests_response->url !== $url) {
                $final_url = $requests_response->url;
            }
        }

        return array('url' => $final_url, 'is_dead' => $is_dead);
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
