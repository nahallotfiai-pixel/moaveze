<?php
/**
 * Public Property Valuation
 *
 * Per explicit user request: "یک قسمت مجزای دیگر داشته باشیم که کاربر
 * با وارد کردن مشخصات ملک خود ارزش‌گذاری ملک خودش رو ببینه ولی دیگه
 * کاربر غیر مدیر یا مشاور لینک موارد مشابهش رو نبینه فقط مدیر و
 * مشاور بتونه ببینه. این قسمت قابلیت داشتن درگاه پرداخت رو داشته
 * باشد که از طریق تنظیمات بتونیم فعال یا غیر فعال کنیم یا قیمت رو
 * تعیین کنیم."
 *
 * Provides:
 *   - [moaveze_public_valuation] shortcode (renders the full form + result UI)
 *   - AJAX handler for running the valuation (reuses the same AI infra)
 *   - Optional payment gate before showing results (ZarinPal / IDPay)
 *   - Settings fields in Settings > امکانات تکمیلی
 *   - Source URLs / comparables links HIDDEN from non-staff users
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Public_Valuation {

    public function __construct() {
        add_shortcode('moaveze_public_valuation', array($this, 'render_shortcode'));
        add_action('wp_ajax_moaveze_public_valuation_run', array($this, 'ajax_run'));
        add_action('wp_ajax_nopriv_moaveze_public_valuation_run', array($this, 'ajax_run'));
        add_action('wp_ajax_moaveze_public_valuation_verify_payment', array($this, 'ajax_verify_payment'));
        add_action('wp_ajax_nopriv_moaveze_public_valuation_verify_payment', array($this, 'ajax_verify_payment'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        register_setting('moaveze_addons', 'moaveze_public_valuation_enabled');
        register_setting('moaveze_addons', 'moaveze_public_valuation_payment_required');
        register_setting('moaveze_addons', 'moaveze_public_valuation_price');
        register_setting('moaveze_addons', 'moaveze_public_valuation_gateway');
        register_setting('moaveze_addons', 'moaveze_public_valuation_zarinpal_merchant');
        register_setting('moaveze_addons', 'moaveze_public_valuation_idpay_api_key');
    }

    /**
     * Is this feature enabled and ready?
     */
    private function is_enabled() {
        return get_option('moaveze_public_valuation_enabled', 'no') === 'yes';
    }

    /**
     * Is payment required before showing results?
     */
    private function is_payment_required() {
        return get_option('moaveze_public_valuation_payment_required', 'no') === 'yes';
    }

    /**
     * Is the current user staff (admin/consultant)?
     */
    private function is_staff() {
        return current_user_can('manage_options') || current_user_can('moaveze_verify_listings');
    }

    /**
     * Render the public valuation form shortcode.
     */
    public function render_shortcode($atts) {
        if (!$this->is_enabled()) {
            return '<div class="moaveze-wrapper"><p>این بخش در حال حاضر غیرفعال است.</p></div>';
        }

        $payment_required = $this->is_payment_required() && !$this->is_staff();
        $price = absint(get_option('moaveze_public_valuation_price', 50000));

        ob_start();
        ?>
        <div class="moaveze-wrapper moaveze-public-valuation-wrapper">
            <div class="moaveze-public-valuation-form-container">
                <h2 class="moaveze-public-valuation-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l9 4.5v9L12 20l-9-4.5v-9z"/><path d="M12 8v8M8 10l4-2 4 2"/></svg>
                    ارزش‌گذاری آنلاین ملک شما
                </h2>
                <p class="moaveze-public-valuation-subtitle">مشخصات ملک خود را وارد کنید تا ارزش تقریبی آن بر اساس تحلیل هوش مصنوعی و داده‌های بازار مسکن تبریز برآورد شود.</p>

                <?php if ($payment_required) : ?>
                    <div class="moaveze-payment-notice">
                        <span class="dashicons dashicons-lock"></span>
                        هزینه ارزش‌گذاری: <strong><?php echo esc_html(Moaveze_Helpers::short_price($price)); ?> تومان</strong>
                        <small>(پرداخت پس از مشاهده نتیجه اولیه، برای دسترسی به تحلیل کامل)</small>
                    </div>
                <?php endif; ?>

                <form id="moaveze-public-valuation-form" class="moaveze-form">
                    <?php wp_nonce_field('moaveze_public_valuation', 'moaveze_pv_nonce'); ?>

                    <div class="moaveze-field-grid-2">
                        <div class="moaveze-field">
                            <label for="pv-property-type">نوع ملک <span class="required">*</span></label>
                            <select id="pv-property-type" name="property_type" required>
                                <option value="">انتخاب کنید...</option>
                                <option value="آپارتمان">آپارتمان</option>
                                <option value="ویلایی">ویلایی</option>
                                <option value="تجاری">تجاری</option>
                                <option value="زمین">زمین</option>
                                <option value="دوبلکس">دوبلکس</option>
                                <option value="پنت‌هاوس">پنت‌هاوس</option>
                            </select>
                        </div>
                        <div class="moaveze-field">
                            <label for="pv-district">منطقه/محله <span class="required">*</span></label>
                            <input type="text" id="pv-district" name="district" required placeholder="مثال: رشدیه، ولیعصر، ائل‌گلی...">
                        </div>
                    </div>

                    <div class="moaveze-field-grid-3">
                        <div class="moaveze-field">
                            <label for="pv-area">متراژ (متر مربع) <span class="required">*</span></label>
                            <input type="number" id="pv-area" name="area" required min="20" max="5000" placeholder="مثال: 140">
                        </div>
                        <div class="moaveze-field">
                            <label for="pv-rooms">تعداد اتاق خواب</label>
                            <input type="number" id="pv-rooms" name="rooms" min="0" max="20" placeholder="مثال: 3">
                        </div>
                        <div class="moaveze-field">
                            <label for="pv-year">سال ساخت (شمسی)</label>
                            <input type="number" id="pv-year" name="year_built" min="1350" max="1410" placeholder="مثال: 1398">
                        </div>
                    </div>

                    <div class="moaveze-field-grid-3">
                        <div class="moaveze-field">
                            <label for="pv-floor">طبقه</label>
                            <input type="number" id="pv-floor" name="floor" min="0" max="50" placeholder="مثال: 3">
                        </div>
                        <div class="moaveze-field">
                            <label for="pv-total-floors">تعداد کل طبقات</label>
                            <input type="number" id="pv-total-floors" name="total_floors" min="1" max="50" placeholder="مثال: 8">
                        </div>
                        <div class="moaveze-field">
                            <label for="pv-address">آدرس تقریبی</label>
                            <input type="text" id="pv-address" name="address" placeholder="خیابان، کوچه...">
                        </div>
                    </div>

                    <div class="moaveze-field">
                        <label>امکانات</label>
                        <div class="moaveze-checkbox-grid">
                            <?php
                            $features = array('پارکینگ', 'آسانسور', 'انباری', 'بالکن', 'استخر', 'نگهبانی', 'لابی', 'سونا');
                            foreach ($features as $f) :
                            ?>
                                <label class="moaveze-checkbox-card">
                                    <input type="checkbox" name="features[]" value="<?php echo esc_attr($f); ?>">
                                    <div class="checkbox-content"><span><?php echo esc_html($f); ?></span></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="moaveze-btn moaveze-btn-primary moaveze-btn-lg" id="pv-submit-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l9 4.5v9L12 20l-9-4.5v-9z"/></svg>
                        دریافت ارزش‌گذاری
                    </button>
                </form>

                <div id="moaveze-public-valuation-result" style="display:none;"></div>
            </div>
        </div>

        <script>
        jQuery(function($) {
            $('#moaveze-public-valuation-form').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#pv-submit-btn').prop('disabled', true).html('<span class="loading-spinner-sm"></span> در حال تحلیل...');
                const $result = $('#moaveze-public-valuation-result');

                $.post(moavezePlus.ajaxUrl, {
                    action: 'moaveze_public_valuation_run',
                    nonce: $('[name="moaveze_pv_nonce"]').val(),
                    property_type: $('#pv-property-type').val(),
                    district: $('#pv-district').val(),
                    area: $('#pv-area').val(),
                    rooms: $('#pv-rooms').val(),
                    year_built: $('#pv-year').val(),
                    floor: $('#pv-floor').val(),
                    total_floors: $('#pv-total-floors').val(),
                    address: $('#pv-address').val(),
                    features: $('input[name="features[]"]:checked').map(function(){ return $(this).val(); }).get(),
                }, function(response) {
                    $btn.prop('disabled', false).html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l9 4.5v9L12 20l-9-4.5v-9z"/></svg> دریافت ارزش‌گذاری');
                    if (response.success) {
                        $result.html(response.data.html).slideDown(300);
                        $('html, body').animate({ scrollTop: $result.offset().top - 100 }, 400);
                    } else {
                        alert((response.data && response.data.message) || response.data || 'خطا در ارزش‌گذاری');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html('دریافت ارزش‌گذاری');
                    alert('خطا در ارتباط با سرور');
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: run a public valuation for a user-submitted property description.
     */
    public function ajax_run() {
        check_ajax_referer('moaveze_public_valuation', 'nonce');

        if (!$this->is_enabled()) {
            wp_send_json_error('این بخش غیرفعال است');
        }

        $area = absint($_POST['area'] ?? 0);
        $district = sanitize_text_field($_POST['district'] ?? '');
        $property_type = sanitize_text_field($_POST['property_type'] ?? '');

        if (!$area || !$district || !$property_type) {
            wp_send_json_error(array('message' => 'لطفاً حداقل نوع ملک، منطقه و متراژ را وارد کنید'));
        }

        // Build a temporary exchange-like object for the prompt builder
        $exchange = (object) array(
            'id'             => 0,
            'post_id'        => 0,
            'property_value' => 0, // user doesn't declare a price - that's what they want to find out
            'area_sqm'       => $area,
            'district'       => $district,
            'property_type'  => $property_type,
        );

        // Build prompt manually since we have no real post to attach meta to
        $rooms = sanitize_text_field($_POST['rooms'] ?? '');
        $floor = sanitize_text_field($_POST['floor'] ?? '');
        $total_floors = sanitize_text_field($_POST['total_floors'] ?? '');
        $year_built = sanitize_text_field($_POST['year_built'] ?? '');
        $address = sanitize_text_field($_POST['address'] ?? '');
        $features = !empty($_POST['features']) ? array_map('sanitize_text_field', (array) $_POST['features']) : array();

        $ai = new Moaveze_AI_Valuation();
        if (!$ai->is_ready()) {
            wp_send_json_error(array('message' => 'سیستم ارزش‌گذاری هوش مصنوعی در حال حاضر فعال نیست. لطفاً بعداً تلاش کنید.'));
        }

        // Build a custom prompt for this public request
        $current_year_jalali = Moaveze_Helpers::jalali_date(current_time('mysql'), 'Y');
        $building_age = ($year_built && $current_year_jalali) ? max(0, (int) $current_year_jalali - (int) $year_built) : null;

        $details = array(
            'شهر'                       => 'تبریز',
            'منطقه/محله'                => $district,
            'آدرس تقریبی'               => $address ?: 'ثبت نشده',
            'نوع ملک'                   => $property_type,
            'متراژ زیربنا'              => $area . ' متر مربع',
            'تعداد اتاق خواب'           => $rooms ?: 'نامشخص',
            'طبقه'                      => ($floor ?: 'نامشخص') . ($total_floors ? " از {$total_floors} طبقه" : ''),
            'سال ساخت (شمسی)'           => $year_built ?: 'نامشخص',
            'قدمت بنا (سال)'            => $building_age !== null ? $building_age : 'نامشخص',
            'امکانات و ویژگی‌ها'        => !empty($features) ? implode('، ', $features) : 'ثبت نشده',
        );

        $details_text = '';
        foreach ($details as $label => $value) {
            $details_text .= "- {$label}: {$value}\n";
        }

        $prompt = <<<PROMPT
تو یک کارشناس رسمی ارزیابی املاک در شهر تبریز هستی. بر اساس مشخصات زیر، ارزش تقریبی این ملک را تخمین بزن.

روش: قیمت پایه هر متر مربع برای این منطقه و نوع ملک → تعدیل بر اساس سال ساخت، طبقه، امکانات → ارزش نهایی.

مشخصات ملک:
{$details_text}

پاسخ را فقط به‌صورت JSON بده:
{
  "estimated_value": <عدد تومان>,
  "min_value": <عدد تومان>,
  "max_value": <عدد تومان>,
  "price_per_sqm": <عدد تومان>,
  "confidence": "<بالا/متوسط/پایین>",
  "methodology_summary": "<۲ جمله: قیمت پایه + عوامل تعدیل>",
  "reasoning": "<۳-۴ جمله تحلیل>"
}
PROMPT;

        $result = $ai->ask($prompt, true);
        if (!$result['success']) {
            wp_send_json_error(array('message' => 'خطا در ارتباط با هوش مصنوعی: ' . $result['error']));
        }

        $parsed = $this->parse_response($result['text']);
        $is_staff = $this->is_staff();

        // Build the result HTML
        $html = $this->render_result_html($parsed, $is_staff);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Parse the AI JSON response (same defensive approach as the main valuation module).
     */
    private function parse_response($text) {
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
            'price_per_sqm' => isset($data['price_per_sqm']) ? absint($data['price_per_sqm']) : null,
            'confidence'  => $data['confidence'] ?? 'نامشخص',
            'methodology' => $data['methodology_summary'] ?? '',
            'reasoning'   => $data['reasoning'] ?? '',
        );
    }

    /**
     * Render the result HTML shown to the user.
     * Source links / comparables are NOT shown to non-staff users.
     */
    private function render_result_html($parsed, $is_staff) {
        $value_short = Moaveze_Helpers::short_price($parsed['value']);
        $min_short = $parsed['min'] ? Moaveze_Helpers::short_price($parsed['min']) : '';
        $max_short = $parsed['max'] ? Moaveze_Helpers::short_price($parsed['max']) : '';
        $per_sqm_short = $parsed['price_per_sqm'] ? Moaveze_Helpers::short_price($parsed['price_per_sqm']) : '';

        ob_start();
        ?>
        <div class="moaveze-public-valuation-result-box">
            <div class="pv-result-header">
                <span class="pv-confidence">میزان اطمینان: <?php echo esc_html($parsed['confidence']); ?></span>
                <span class="pv-provider">🤖 تحلیل هوش مصنوعی</span>
            </div>

            <div class="pv-result-value"><?php echo esc_html($value_short); ?> تومان</div>

            <?php if ($min_short && $max_short) : ?>
                <div class="pv-result-range">محدوده منطقی: <?php echo esc_html($min_short); ?> تا <?php echo esc_html($max_short); ?></div>
            <?php endif; ?>

            <?php if ($per_sqm_short) : ?>
                <div class="pv-result-per-sqm">قیمت پیشنهادی هر متر: <strong><?php echo esc_html($per_sqm_short); ?></strong></div>
            <?php endif; ?>

            <?php if ($parsed['methodology']) : ?>
                <div class="pv-result-methodology">
                    <strong>روش محاسبه:</strong> <?php echo esc_html($parsed['methodology']); ?>
                </div>
            <?php endif; ?>

            <?php if ($parsed['reasoning']) : ?>
                <div class="pv-result-reasoning"><?php echo esc_html($parsed['reasoning']); ?></div>
            <?php endif; ?>

            <div class="pv-result-disclaimer">
                <p>⚠ این ارزش‌گذاری صرفاً یک تخمین اولیه بر اساس تحلیل هوش مصنوعی است و جایگزین نظر کارشناس رسمی نمی‌شود. برای ارزش‌گذاری دقیق‌تر با مشاوران تبریز هوم تماس بگیرید.</p>
            </div>

            <?php if (!$is_staff) : ?>
                <div class="pv-staff-only-notice">
                    <p>📋 لینک آگهی‌های مشابه و تحلیل تفصیلی فقط برای مشاوران تبریز هوم قابل مشاهده است.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: verify payment callback (placeholder for ZarinPal/IDPay integration).
     */
    public function ajax_verify_payment() {
        // Will be implemented when payment gateway details are confirmed
        wp_send_json_error('درگاه پرداخت هنوز پیکربندی نشده است');
    }
}

new Moaveze_Public_Valuation();
