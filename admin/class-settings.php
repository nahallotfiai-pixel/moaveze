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
