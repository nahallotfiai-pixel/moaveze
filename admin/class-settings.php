<?php
/**
 * Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Settings {

    private $tabs = array();

    public function __construct() {
        $this->tabs = array(
            'general'        => 'تنظیمات عمومی',
            'monetization'   => 'درآمدزایی',
            'display'        => 'نمایش',
            'map'            => 'نقشه',
            'notifications'  => 'اعلان‌ها',
            'privacy'        => 'حریم خصوصی',
            'chat'           => 'چت داخلی',
            'integration'    => 'یکپارچه‌سازی',
            'pages'          => 'صفحات',
            'ai'             => 'هوش مصنوعی',
            'addons'         => 'امکانات تکمیلی',
        );
    }

    /**
     * Register all settings
     */
    public function register_settings() {
        // General Settings
        register_setting('moaveze_general', 'moaveze_enable_exchange');
        register_setting('moaveze_general', 'moaveze_require_login');
        register_setting('moaveze_general', 'moaveze_auto_approve');
        register_setting('moaveze_general', 'moaveze_listings_per_page');

        // Monetization Settings
        register_setting('moaveze_monetization', 'moaveze_monetization_mode');
        register_setting('moaveze_monetization', 'moaveze_subscription_price');
        register_setting('moaveze_monetization', 'moaveze_per_contact_price');
        register_setting('moaveze_monetization', 'moaveze_vip_enabled');
        register_setting('moaveze_monetization', 'moaveze_boost_enabled');
        register_setting('moaveze_monetization', 'moaveze_boost_price');

        // Display Settings
        register_setting('moaveze_display', 'moaveze_show_in_houzez');
        register_setting('moaveze_display', 'moaveze_exchange_badge');
        register_setting('moaveze_display', 'moaveze_dark_mode');
        register_setting('moaveze_display', 'moaveze_primary_color');
        register_setting('moaveze_display', 'moaveze_cards_style');

        // Map Settings
        register_setting('moaveze_map', 'moaveze_map_center_lat');
        register_setting('moaveze_map', 'moaveze_map_center_lng');
        register_setting('moaveze_map', 'moaveze_map_zoom');
        register_setting('moaveze_map', 'moaveze_map_style');

        // Notification Settings
        register_setting('moaveze_notifications', 'moaveze_email_notifications');
        register_setting('moaveze_notifications', 'moaveze_sms_notifications');
        register_setting('moaveze_notifications', 'moaveze_sms_api_key');
        register_setting('moaveze_notifications', 'moaveze_push_notifications');

        // Privacy Settings
        register_setting('moaveze_privacy', 'moaveze_hide_contact_info');
        register_setting('moaveze_privacy', 'moaveze_contact_visible_to');

        // Chat Settings
        register_setting('moaveze_chat', 'moaveze_chat_enabled');
        register_setting('moaveze_chat', 'moaveze_chat_require_approval');
        register_setting('moaveze_chat', 'moaveze_chat_auto_close_days');

        // Integration Settings
        register_setting('moaveze_integration', 'moaveze_houzez_sync');
        register_setting('moaveze_integration', 'moaveze_import_existing');

        // Pages (which real WP Page each plugin flow points to)
        register_setting('moaveze_pages', 'moaveze_page_submit');
        register_setting('moaveze_pages', 'moaveze_page_listings');

        // Add-on Features (each independently toggleable per user request)
        register_setting('moaveze_addons', 'moaveze_addon_favorites');
        register_setting('moaveze_addons', 'moaveze_addon_report_listing');
        register_setting('moaveze_addons', 'moaveze_addon_compare_listings');
        register_setting('moaveze_addons', 'moaveze_addon_qr_code');
        register_setting('moaveze_addons', 'moaveze_addon_price_alerts');
        register_setting('moaveze_addons', 'moaveze_addon_success_stories');
        register_setting('moaveze_addons', 'moaveze_addon_weekly_report');
    }


    /**
     * Render settings page
     */
    public function render_settings_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap moaveze-settings-wrap">
            <h1><span class="dashicons dashicons-randomize"></span> تنظیمات معاوضه پلاس</h1>

            <nav class="nav-tab-wrapper moaveze-tabs">
                <?php foreach ($this->tabs as $tab_id => $tab_label) : ?>
                    <a href="<?php echo admin_url('admin.php?page=moaveze-settings&tab=' . $tab_id); ?>"
                       class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="moaveze-settings-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('moaveze_' . $current_tab);
                    $this->render_tab_content($current_tab);
                    submit_button('ذخیره تنظیمات');
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render tab content
     */
    private function render_tab_content($tab) {
        switch ($tab) {
            case 'general':
                $this->render_general_tab();
                break;
            case 'monetization':
                $this->render_monetization_tab();
                break;
            case 'display':
                $this->render_display_tab();
                break;
            case 'map':
                $this->render_map_tab();
                break;
            case 'notifications':
                $this->render_notifications_tab();
                break;
            case 'privacy':
                $this->render_privacy_tab();
                break;
            case 'chat':
                $this->render_chat_tab();
                break;
            case 'integration':
                $this->render_integration_tab();
                break;
            case 'pages':
                $this->render_pages_tab();
                break;
            case 'ai':
                $this->render_ai_tab();
                break;
            case 'addons':
                $this->render_addons_tab();
                break;
        }
    }

    /**
     * General Tab
     */
    private function render_general_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_enable_exchange">فعال‌سازی سیستم معاوضه</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_enable_exchange" name="moaveze_enable_exchange" value="yes" <?php checked(get_option('moaveze_enable_exchange'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">فعال یا غیرفعال کردن کل سیستم معاوضه</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_require_login">الزام ورود برای مشاهده</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_require_login" name="moaveze_require_login" value="yes" <?php checked(get_option('moaveze_require_login'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">آیا کاربران باید برای مشاهده آگهی‌ها وارد شوند؟</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_auto_approve">تأیید خودکار آگهی‌ها</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_auto_approve" name="moaveze_auto_approve" value="yes" <?php checked(get_option('moaveze_auto_approve'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">آگهی‌ها بدون بررسی مشاور منتشر شوند؟</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_listings_per_page">تعداد آگهی در هر صفحه</label></th>
                <td>
                    <input type="number" id="moaveze_listings_per_page" name="moaveze_listings_per_page" value="<?php echo esc_attr(get_option('moaveze_listings_per_page', 12)); ?>" min="4" max="48" step="4">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Monetization Tab
     */
    private function render_monetization_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_monetization_mode">مدل درآمدزایی</label></th>
                <td>
                    <select id="moaveze_monetization_mode" name="moaveze_monetization_mode">
                        <option value="free" <?php selected(get_option('moaveze_monetization_mode'), 'free'); ?>>رایگان (بدون محدودیت)</option>
                        <option value="consultant" <?php selected(get_option('moaveze_monetization_mode'), 'consultant'); ?>>فقط از طریق مشاوران</option>
                        <option value="subscription" <?php selected(get_option('moaveze_monetization_mode'), 'subscription'); ?>>اشتراکی</option>
                        <option value="per_contact" <?php selected(get_option('moaveze_monetization_mode'), 'per_contact'); ?>>پرداخت به ازای هر تماس</option>
                        <option value="hybrid" <?php selected(get_option('moaveze_monetization_mode'), 'hybrid'); ?>>ترکیبی (سفارشی هر آگهی)</option>
                    </select>
                    <p class="description">در حالت ترکیبی می‌توانید برای هر آگهی مدل متفاوتی تنظیم کنید</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_subscription_price">قیمت اشتراک ماهانه (تومان)</label></th>
                <td>
                    <input type="number" id="moaveze_subscription_price" name="moaveze_subscription_price" value="<?php echo esc_attr(get_option('moaveze_subscription_price', 0)); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_per_contact_price">هزینه هر تماس (تومان)</label></th>
                <td>
                    <input type="number" id="moaveze_per_contact_price" name="moaveze_per_contact_price" value="<?php echo esc_attr(get_option('moaveze_per_contact_price', 0)); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_vip_enabled">آگهی VIP</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_vip_enabled" name="moaveze_vip_enabled" value="yes" <?php checked(get_option('moaveze_vip_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">امکان خرید آگهی VIP (نمایش در بالای لیست)</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_boost_enabled">بوست آگهی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_boost_enabled" name="moaveze_boost_enabled" value="yes" <?php checked(get_option('moaveze_boost_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">امکان بالا بردن آگهی با پرداخت</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Display Tab
     */
    private function render_display_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_dark_mode">حالت تاریک</label></th>
                <td>
                    <select id="moaveze_dark_mode" name="moaveze_dark_mode">
                        <option value="auto" <?php selected(get_option('moaveze_dark_mode'), 'auto'); ?>>خودکار (بر اساس سیستم کاربر)</option>
                        <option value="light" <?php selected(get_option('moaveze_dark_mode'), 'light'); ?>>همیشه روشن</option>
                        <option value="dark" <?php selected(get_option('moaveze_dark_mode'), 'dark'); ?>>همیشه تاریک</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_primary_color">رنگ اصلی</label></th>
                <td>
                    <input type="text" id="moaveze_primary_color" name="moaveze_primary_color" value="<?php echo esc_attr(get_option('moaveze_primary_color', '#6366f1')); ?>" class="moaveze-color-picker">
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_cards_style">استایل کارت‌ها</label></th>
                <td>
                    <select id="moaveze_cards_style" name="moaveze_cards_style">
                        <option value="modern" <?php selected(get_option('moaveze_cards_style'), 'modern'); ?>>مدرن</option>
                        <option value="classic" <?php selected(get_option('moaveze_cards_style'), 'classic'); ?>>کلاسیک</option>
                        <option value="minimal" <?php selected(get_option('moaveze_cards_style'), 'minimal'); ?>>مینیمال</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_exchange_badge">نمایش برچسب معاوضه</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_exchange_badge" name="moaveze_exchange_badge" value="yes" <?php checked(get_option('moaveze_exchange_badge'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">نمایش برچسب "امکان معاوضه" روی آگهی‌ها</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Map Tab
     */
    private function render_map_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label>مرکز نقشه (تبریز)</label></th>
                <td>
                    <label>عرض: <input type="text" name="moaveze_map_center_lat" value="<?php echo esc_attr(get_option('moaveze_map_center_lat', '38.0962')); ?>" size="12"></label>
                    <label>طول: <input type="text" name="moaveze_map_center_lng" value="<?php echo esc_attr(get_option('moaveze_map_center_lng', '46.2738')); ?>" size="12"></label>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_map_zoom">زوم پیش‌فرض</label></th>
                <td>
                    <input type="range" id="moaveze_map_zoom" name="moaveze_map_zoom" min="8" max="18" value="<?php echo esc_attr(get_option('moaveze_map_zoom', '12')); ?>">
                    <span id="zoom_value"><?php echo esc_html(get_option('moaveze_map_zoom', '12')); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_map_style">استایل نقشه</label></th>
                <td>
                    <select id="moaveze_map_style" name="moaveze_map_style">
                        <option value="default" <?php selected(get_option('moaveze_map_style'), 'default'); ?>>پیش‌فرض</option>
                        <option value="dark" <?php selected(get_option('moaveze_map_style'), 'dark'); ?>>تاریک</option>
                        <option value="satellite" <?php selected(get_option('moaveze_map_style'), 'satellite'); ?>>ماهواره‌ای</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Notifications Tab
     */
    private function render_notifications_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_email_notifications">اعلان ایمیلی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_email_notifications" name="moaveze_email_notifications" value="yes" <?php checked(get_option('moaveze_email_notifications'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_sms_notifications">اعلان پیامکی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_sms_notifications" name="moaveze_sms_notifications" value="yes" <?php checked(get_option('moaveze_sms_notifications'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_sms_api_key">کلید API پیامکی</label></th>
                <td>
                    <input type="text" id="moaveze_sms_api_key" name="moaveze_sms_api_key" value="<?php echo esc_attr(get_option('moaveze_sms_api_key')); ?>" class="regular-text" placeholder="API Key سرویس پیامکی">
                    <p class="description">از سرویس‌های کاوه‌نگار، ملی‌پیامک و... پشتیبانی می‌شود</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_push_notifications">اعلان Push</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_push_notifications" name="moaveze_push_notifications" value="yes" <?php checked(get_option('moaveze_push_notifications'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Privacy Tab
     */
    private function render_privacy_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_hide_contact_info">مخفی‌سازی اطلاعات تماس</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_hide_contact_info" name="moaveze_hide_contact_info" value="yes" <?php checked(get_option('moaveze_hide_contact_info'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">اطلاعات تماس مالکین از دید عموم مخفی باشد</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_contact_visible_to">نمایش اطلاعات تماس به</label></th>
                <td>
                    <select id="moaveze_contact_visible_to" name="moaveze_contact_visible_to">
                        <option value="admin" <?php selected(get_option('moaveze_contact_visible_to'), 'admin'); ?>>فقط مدیر</option>
                        <option value="consultant" <?php selected(get_option('moaveze_contact_visible_to'), 'consultant'); ?>>مدیر و مشاوران</option>
                        <option value="subscriber" <?php selected(get_option('moaveze_contact_visible_to'), 'subscriber'); ?>>مشترکین VIP</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Chat Tab
     */
    private function render_chat_tab() {
        ?>
        <div class="moaveze-notice moaveze-notice-warning">
            <span class="dashicons dashicons-shield"></span>
            <p>سیستم چت فقط با فعال‌سازی مدیر قابل استفاده است تا از ردوبدل اطلاعات محرمانه بدون نظارت جلوگیری شود.</p>
        </div>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_chat_enabled">فعال‌سازی چت داخلی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_chat_enabled" name="moaveze_chat_enabled" value="yes" <?php checked(get_option('moaveze_chat_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">فعال‌سازی سیستم پیام‌رسان داخلی بین کاربران (تحت نظارت مدیر)</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_chat_require_approval">نیاز به تأیید پیام</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_chat_require_approval" name="moaveze_chat_require_approval" value="yes" <?php checked(get_option('moaveze_chat_require_approval'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">پیام‌ها قبل از ارسال به مقصد نیاز به تأیید مدیر داشته باشند</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_chat_auto_close_days">بستن خودکار مکالمه (روز)</label></th>
                <td>
                    <input type="number" id="moaveze_chat_auto_close_days" name="moaveze_chat_auto_close_days" value="<?php echo esc_attr(get_option('moaveze_chat_auto_close_days', 7)); ?>" min="1" max="30">
                    <p class="description">مکالمات بدون فعالیت بعد از این تعداد روز بسته می‌شوند</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * AI Valuation Tab
     *
     * IMPORTANT: everything configured here powers a STAFF-ONLY feature
     * (see includes/modules/class-ai-valuation.php) - the "ارزش‌گذاری
     * هوش مصنوعی" metabox that only site admins and the
     * 'moaveze_consultant' role can see on the exchange listing edit
     * screen. There is no public/visitor-facing AI feature.
     */
    private function render_ai_tab() {
        $providers = Moaveze_AI_Valuation::get_providers();
        $active_provider = get_option('moaveze_ai_active_provider', 'gemini');
        ?>
        <div class="moaveze-notice moaveze-notice-warning">
            <span class="dashicons dashicons-lock"></span>
            <p>این بخش فقط برای «ارزش‌گذاری ملک توسط مدیر/مشاوران تبریز هوم» استفاده می‌شود و هیچ‌گاه به کاربران عادی سایت نمایش داده نمی‌شود.</p>
        </div>

        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_ai_valuation_enabled">فعال‌سازی ارزش‌گذاری با هوش مصنوعی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_ai_valuation_enabled" name="moaveze_ai_valuation_enabled" value="yes" <?php checked(get_option('moaveze_ai_valuation_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">در صورت غیرفعال بودن، فقط ارزش‌گذاری دستی توسط مشاوران در دسترس خواهد بود</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_ai_active_provider">ارائه‌دهنده فعال</label></th>
                <td>
                    <select id="moaveze_ai_active_provider" name="moaveze_ai_active_provider">
                        <?php foreach ($providers as $key => $p) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($active_provider, $key); ?>><?php echo esc_html($p['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">فقط یک ارائه‌دهنده در هر لحظه فعال است؛ برای تعویض کافیست ارائه‌دهنده دیگری فعال کنید (بدون نیاز به حذف تنظیمات قبلی)</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_ai_grounding_enabled">جست‌وجوی واقعی وب (Grounding)</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_ai_grounding_enabled" name="moaveze_ai_grounding_enabled" value="yes" <?php checked(get_option('moaveze_ai_grounding_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">
                        در صورت فعال بودن (فقط برای Google Gemini قابل استفاده است)، هوش مصنوعی به‌صورت واقعی در وب جست‌وجو می‌کند (از جمله آگهی‌های مشابه در دیوار و سایت‌های دیگر) و برای هر مورد مشابه، لینک واقعی و قابل بررسی منبع را هم ارائه می‌دهد - نه فقط یک مثال احتمالی بر اساس دانش قبلی مدل.
                        اگر غیرفعال باشد یا ارائه‌دهنده فعال Gemini نباشد، موارد مشابه صرفاً بر اساس دانش کلی مدل هستند و لینک واقعی ندارند (این به‌وضوح در نتیجه نمایش داده می‌شود).
                    </p>
                </td>
            </tr>
        </table>

        <h3 style="margin-top:24px;">ارائه‌دهندگان هوش مصنوعی</h3>
        <p class="description">هر ارائه‌دهنده را جداگانه فعال/غیرفعال و پیکربندی کنید.</p>

        <?php foreach ($providers as $key => $p) : ?>
            <div class="moaveze-ai-provider-card" data-provider="<?php echo esc_attr($key); ?>">
                <div class="ai-provider-header">
                    <label class="moaveze-switch">
                        <input type="checkbox" name="moaveze_ai_<?php echo esc_attr($key); ?>_enabled" value="yes" <?php checked(get_option("moaveze_ai_{$key}_enabled"), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <strong><?php echo esc_html($p['label']); ?></strong>
                    <button type="button" class="button button-small moaveze-ai-test-btn" data-provider="<?php echo esc_attr($key); ?>">تست اتصال</button>
                    <span class="ai-test-result"></span>
                </div>
                <div class="ai-provider-fields">
                    <?php if ($key !== 'cloudflare_ai') : ?>
                        <div class="moaveze-field">
                            <label>کلید API (API Key)</label>
                            <input type="password" name="moaveze_ai_<?php echo esc_attr($key); ?>_api_key" value="<?php echo esc_attr(get_option("moaveze_ai_{$key}_api_key")); ?>" class="regular-text" dir="ltr" autocomplete="off">
                        </div>
                    <?php else : ?>
                        <div class="moaveze-field">
                            <label>کلید API (API Token)</label>
                            <input type="password" name="moaveze_ai_<?php echo esc_attr($key); ?>_api_key" value="<?php echo esc_attr(get_option("moaveze_ai_{$key}_api_key")); ?>" class="regular-text" dir="ltr" autocomplete="off">
                        </div>
                        <div class="moaveze-field">
                            <label>Account ID</label>
                            <input type="text" name="moaveze_ai_cloudflare_ai_account_id" value="<?php echo esc_attr(get_option('moaveze_ai_cloudflare_ai_account_id')); ?>" class="regular-text" dir="ltr">
                        </div>
                    <?php endif; ?>
                    <div class="moaveze-field">
                        <label>آدرس API <?php echo $key === 'custom' ? '(الزامی)' : '(اختیاری - در صورت خالی بودن از مقدار پیش‌فرض استفاده می‌شود)'; ?></label>
                        <input type="text" class="regular-text moaveze-ai-url-input" name="moaveze_ai_<?php echo esc_attr($key); ?>_url" value="<?php echo esc_attr(get_option("moaveze_ai_{$key}_url")); ?>" dir="ltr" placeholder="<?php echo esc_attr($p['default_url']); ?>">
                    </div>
                    <div class="moaveze-field">
                        <label for="moaveze_ai_<?php echo esc_attr($key); ?>_model">مدل</label>
                        <button type="button" class="button button-small moaveze-ai-fetch-models-btn" data-provider="<?php echo esc_attr($key); ?>">
                            <span class="dashicons dashicons-update"></span> دریافت لیست مدل‌های موجود
                        </button>
                        <input type="text" id="moaveze_ai_<?php echo esc_attr($key); ?>_model" class="regular-text moaveze-ai-model-input" name="moaveze_ai_<?php echo esc_attr($key); ?>_model" value="<?php echo esc_attr(get_option("moaveze_ai_{$key}_model")); ?>" dir="ltr" placeholder="<?php echo esc_attr($p['default_model']); ?>" data-provider="<?php echo esc_attr($key); ?>">
                        <select class="moaveze-ai-model-select" style="display:none;margin-top:6px;width:100%;" dir="ltr"></select>
                        <p class="moaveze-ai-models-status description"></p>
                        <?php if ($key === 'gemini' && stripos(get_option("moaveze_ai_{$key}_model"), '-lite') !== false) : ?>
                            <p class="description moaveze-lite-model-warning" style="color:#92400e;background:#fef3c7;padding:8px 12px;border-radius:8px;">
                                ⚠ مدل‌های «Lite» (مثل Flash-Lite) برای سرعت و مصرف کم طراحی شده‌اند، نه برای دقت تحلیلی بالا - برای <strong>ارزش‌گذاری ملک</strong> (که به استدلال چندمرحله‌ای دقیق نیاز دارد) توصیه نمی‌شود و ممکن است اعداد نامنطقی یا کمتر دقیق ارائه دهد. برای بهترین نتیجه، مدل استاندارد (بدون Lite) مثل <code>gemini-2.5-flash</code> یا <code>gemini-2.5-pro</code> را انتخاب کنید. اگر فقط به دلیل محدودیت نرخ (rate limit) از Lite استفاده می‌کنید، پس از رفع ازدحام به مدل استاندارد برگردید.
                            </p>
                        <?php endif; ?>
                        <p class="description">با کلیک روی «دریافت لیست مدل‌های موجود»، مدل‌های واقعاً در دسترس این حساب به همراه وضعیت رایگان/پولی یا هزینه تقریبی نمایش داده می‌شود. انتخاب یک مدل از لیست بلافاصله و جداگانه ذخیره می‌شود (نیازی به زدن «ذخیره تنظیمات» فقط برای این فیلد نیست).</p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <h3 style="margin-top:30px;">دور زدن محدودیت دسترسی (میزبانی ایران)</h3>
        <p class="description">چون اکثر سایت‌ها در ایران میزبانی می‌شوند و ممکن است دسترسی مستقیم به این سرویس‌ها محدود باشد، یکی (یا هر دو) روش زیر را می‌توانید فعال کنید.</p>

        <div class="moaveze-ai-bypass-grid">
            <div class="moaveze-ai-provider-card">
                <div class="ai-provider-header">
                    <label class="moaveze-switch">
                        <input type="checkbox" name="moaveze_ai_proxy_enabled" value="yes" <?php checked(get_option('moaveze_ai_proxy_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <strong>پروکسی HTTP / Xray</strong>
                </div>
                <div class="ai-provider-fields">
                    <div class="moaveze-field">
                        <label>آدرس پروکسی (کانفیگ Xray/HTTP/SOCKS)</label>
                        <input type="text" name="moaveze_ai_proxy_url" value="<?php echo esc_attr(get_option('moaveze_ai_proxy_url')); ?>" class="regular-text" dir="ltr" placeholder="http://127.0.0.1:2080 یا socks5://127.0.0.1:1080">
                        <p class="description">فقط محل واردکردن آدرس پروکسی است؛ این افزونه خودش سرویس Xray را اجرا نمی‌کند - باید از قبل روی سرور در حال اجرا باشد.</p>
                    </div>
                </div>
            </div>

            <div class="moaveze-ai-provider-card">
                <div class="ai-provider-header">
                    <label class="moaveze-switch">
                        <input type="checkbox" name="moaveze_ai_worker_enabled" value="yes" <?php checked(get_option('moaveze_ai_worker_enabled'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <strong>رله از طریق Cloudflare Worker</strong>
                </div>
                <div class="ai-provider-fields">
                    <div class="moaveze-field">
                        <label>آدرس Cloudflare Worker</label>
                        <input type="text" name="moaveze_ai_worker_url" value="<?php echo esc_attr(get_option('moaveze_ai_worker_url')); ?>" class="regular-text" dir="ltr" placeholder="https://your-worker.your-subdomain.workers.dev">
                    </div>
                    <div class="moaveze-field">
                        <label>رمز مشترک (اختیاری، برای امنیت بیشتر)</label>
                        <input type="password" name="moaveze_ai_worker_secret" value="<?php echo esc_attr(get_option('moaveze_ai_worker_secret')); ?>" class="regular-text" dir="ltr" autocomplete="off">
                    </div>
                    <p class="description">در صورت فعال بودن این گزینه، تمام درخواست‌های هوش مصنوعی از طریق Worker شما ارسال می‌شود و تنظیمات پروکسی HTTP نادیده گرفته می‌شود.</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add-on Features Tab - master on/off switches for every optional
     * feature suggested, so the site owner can enable only what they
     * want (per explicit request: "بقیه امکانات هم از طریق تنظیمات بشه
     * فعال یا غیر فعال کرد").
     */
    private function render_addons_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_addon_favorites">علاقه‌مندی‌ها (Favorites)</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_favorites" name="moaveze_addon_favorites" value="yes" <?php checked(get_option('moaveze_addon_favorites', 'yes'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">امکان ذخیره آگهی‌های مورد علاقه (بدون نیاز به ثبت‌نام)</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_addon_report_listing">گزارش تخلف آگهی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_report_listing" name="moaveze_addon_report_listing" value="yes" <?php checked(get_option('moaveze_addon_report_listing'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">دکمه «گزارش این آگهی» روی صفحه هر آگهی برای گزارش تخلف یا محتوای مشکوک</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_addon_compare_listings">مقایسه آگهی‌ها</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_compare_listings" name="moaveze_addon_compare_listings" value="yes" <?php checked(get_option('moaveze_addon_compare_listings'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">امکان انتخاب ۲ تا ۳ آگهی و مشاهده جدول مقایسه‌ای مشخصات</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_addon_qr_code">QR Code برای آگهی</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_qr_code" name="moaveze_addon_qr_code" value="yes" <?php checked(get_option('moaveze_addon_qr_code'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">دکمه دانلود QR Code آگهی برای چاپ و نصب روی تابلوی ملک</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_addon_price_alerts">هشدار قیمت در لیست آرزو</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_price_alerts" name="moaveze_addon_price_alerts" value="yes" <?php checked(get_option('moaveze_addon_price_alerts')); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">اطلاع‌رسانی (ایمیل/پیامک) وقتی ملکی زیر قیمت مشخص‌شده در لیست آرزو ثبت شود</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_addon_success_stories">معاوضه‌های موفق (Success Stories)</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_success_stories" name="moaveze_addon_success_stories" value="yes" <?php checked(get_option('moaveze_addon_success_stories')); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">نمایش صفحه عمومی معاوضه‌های موفق انجام‌شده (بدون افشای هویت طرفین)</p>
                </td>
            </tr>
            <tr>
                <th><label for="moaveze_addon_weekly_report">گزارش هفتگی ایمیلی به مالکین</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_addon_weekly_report" name="moaveze_addon_weekly_report" value="yes" <?php checked(get_option('moaveze_addon_weekly_report')); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">ارسال خلاصه بازدید و پیشنهادات هفتگی به صاحبان آگهی از طریق ایمیل</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * صفحات (Pages) Tab
     *
     * Lets the admin see/change which real WP Page each plugin flow
     * points to, and re-create either one on demand - fixes both the
     * "/exchange/ را نمی‌توان ویرایش کرد" question (explains why, and
     * offers the closest editable equivalent) and the
     * "/submit-exchange/ صفحه‌ای موجود نیست" 404 (the page is now a
     * real, always-present, fully editable WP page - see
     * includes/class-pages.php).
     */
    private function render_pages_tab() {
        $submit_page_id = (int) get_option('moaveze_page_submit', 0);
        $listings_page_id = (int) get_option('moaveze_page_listings', 0);
        $all_pages = get_pages(array('sort_column' => 'post_title'));
        ?>
        <div class="moaveze-notice" style="background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;">
            <span class="dashicons dashicons-info"></span>
            <p>
                آرشیو <code>/exchange/</code> یک صفحه خودکار وردپرسی برای نوع پست «معاوضه‌ها» است و مثل صفحات معمولی در «صفحات» وردپرس قابل ویرایش نیست (این محدودیت وردپرس است، نه افزونه) - اما محتوای آن از طریق قالب <code>templates/archive-exchange.php</code> این افزونه کنترل می‌شود.
                در عوض، صفحه «ثبت آگهی» و صفحه لیست آگهی‌ها زیر، صفحات واقعی و کاملاً قابل ویرایش در پیشخوان هستند.
            </p>
        </div>

        <table class="form-table moaveze-form-table">
            <tr>
                <th>صفحه «ثبت آگهی معاوضه»</th>
                <td>
                    <select name="moaveze_page_submit">
                        <option value="0">— هیچ (بازگشت به آرشیو /exchange/) —</option>
                        <?php foreach ($all_pages as $p) : ?>
                            <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($submit_page_id, $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($submit_page_id && get_post($submit_page_id)) : ?>
                        <a href="<?php echo esc_url(get_permalink($submit_page_id)); ?>" target="_blank" class="button button-small">مشاهده</a>
                        <a href="<?php echo esc_url(get_edit_post_link($submit_page_id, 'raw')); ?>" target="_blank" class="button button-small">ویرایش در پیشخوان</a>
                    <?php endif; ?>
                    <button type="button" class="button moaveze-recreate-page-btn" data-page-key="submit">بازسازی صفحه پیش‌فرض</button>
                    <p class="description">این صفحه باید حاوی شورت‌کد <code>[moaveze_submit_form]</code> باشد تا فرم ثبت آگهی نمایش داده شود.</p>
                </td>
            </tr>
            <tr>
                <th>صفحه «لیست آگهی‌های معاوضه»</th>
                <td>
                    <select name="moaveze_page_listings">
                        <option value="0">— هیچ (بازگشت به آرشیو /exchange/) —</option>
                        <?php foreach ($all_pages as $p) : ?>
                            <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($listings_page_id, $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($listings_page_id && get_post($listings_page_id)) : ?>
                        <a href="<?php echo esc_url(get_permalink($listings_page_id)); ?>" target="_blank" class="button button-small">مشاهده</a>
                        <a href="<?php echo esc_url(get_edit_post_link($listings_page_id, 'raw')); ?>" target="_blank" class="button button-small">ویرایش در پیشخوان</a>
                    <?php endif; ?>
                    <button type="button" class="button moaveze-recreate-page-btn" data-page-key="listings">بازسازی صفحه پیش‌فرض</button>
                    <p class="description">این صفحه باید حاوی شورت‌کد <code>[moaveze_listings]</code> باشد تا لیست آگهی‌ها نمایش داده شود.</p>
                </td>
            </tr>
        </table>
        <div id="moaveze-recreate-page-result" style="margin-top:10px;"></div>
        <script>
        jQuery(function($) {
            $('.moaveze-recreate-page-btn').on('click', function() {
                var $btn = $(this).prop('disabled', true);
                var key = $btn.data('page-key');
                $.post(ajaxurl, { action: 'moaveze_recreate_pages', nonce: moavezeAdmin.nonce, page_key: key }, function(r) {
                    $btn.prop('disabled', false);
                    if (r.success) {
                        $('#moaveze-recreate-page-result').html('<p style="color:#16a34a;">✓ ' + r.data.message + ' - صفحه را ذخیره کرده و رفرش کنید.</p>');
                    } else {
                        $('#moaveze-recreate-page-result').html('<p style="color:#dc2626;">✗ خطا</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Integration Tab
     */
    private function render_integration_tab() {
        ?>
        <table class="form-table moaveze-form-table">
            <tr>
                <th><label for="moaveze_houzez_sync">همگام‌سازی با Houzez</label></th>
                <td>
                    <label class="moaveze-switch">
                        <input type="checkbox" id="moaveze_houzez_sync" name="moaveze_houzez_sync" value="yes" <?php checked(get_option('moaveze_houzez_sync'), 'yes'); ?>>
                        <span class="moaveze-slider"></span>
                    </label>
                    <p class="description">نمایش آگهی‌های معاوضه در لیست اصلی Houzez با برچسب "امکان معاوضه"</p>
                </td>
            </tr>
            <tr>
                <th>وارد کردن از Houzez</th>
                <td>
                    <button type="button" id="moaveze_import_houzez" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> وارد کردن ملک‌های موجود
                    </button>
                    <p class="description">ملک‌های فعلی Houzez را به سیستم معاوضه اضافه کنید</p>
                </td>
            </tr>
        </table>
        <?php
    }
}
