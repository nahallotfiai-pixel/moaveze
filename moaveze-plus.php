<?php
/**
 * Plugin Name: معاوضه پلاس (Moaveze Plus)
 * Plugin URI: https://tabrizhome.com
 * Description: سیستم پیشرفته معاوضه ملک با قابلیت تطبیق هوشمند، معاوضه زنجیره‌ای، نقشه تعاملی و مدیریت کامل
 * Version: 3.1.0
 * Author: تبریز هوم
 * Author URI: https://tabrizhome.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: moaveze-plus
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
// NOTE: this is now ONLY the human-readable version shown in
// wp-admin > Plugins (and used as the DB-upgrade-check fallback) - it
// has NO effect on browser caching of CSS/JS anymore. See
// moaveze_asset_version() below, which busts the cache automatically
// using each file's own last-modified time, so this number no longer
// needs to be bumped on every change just to force a cache refresh.
// It should still be bumped for real releases so admins can see at a
// glance that the plugin was updated (Plugins list, changelog, etc).
define('MOAVEZE_PLUS_VERSION', '3.1.0');
define('MOAVEZE_PLUS_FILE', __FILE__);
define('MOAVEZE_PLUS_PATH', plugin_dir_path(__FILE__));
define('MOAVEZE_PLUS_URL', plugin_dir_url(__FILE__));
define('MOAVEZE_PLUS_BASENAME', plugin_basename(__FILE__));
define('MOAVEZE_PLUS_DB_VERSION', '1.4.0'); // bumped: added ai_suggestion* columns to moaveze_matches/moaveze_chains (AI-assisted deal-structure suggestions for matches + chain swaps)

/**
 * ROOT-CAUSE FIX for "my fix didn't show up on the live site" reports:
 * every single CSS/JS asset in this plugin was enqueued with the
 * STATIC constant MOAVEZE_PLUS_VERSION ('1.0.0') as its cache-busting
 * version string. Since that constant was never bumped despite dozens
 * of commits changing these exact files, every browser (and any
 * server-side/CDN caching layer) kept serving the OLD cached file
 * indefinitely under the URL "...ai-valuation.js?ver=1.0.0" - the
 * server-side PHP logic was correctly updated on every deploy, but the
 * JS/CSS that renders its results was not, because nothing ever told
 * the browser the file had changed.
 *
 * Fix: this helper returns the asset file's own last-modified
 * timestamp as its version, so EVERY future code change automatically
 * busts the cache with zero manual steps - there is no version number
 * to remember to bump ever again. Falls back to MOAVEZE_PLUS_VERSION
 * only if the file can't be found (e.g. mid-deployment edge case).
 *
 * @param string $relative_path Path relative to the plugin root, e.g. 'assets/js/admin/ai-valuation.js'.
 */
function moaveze_asset_version($relative_path) {
    $full_path = MOAVEZE_PLUS_PATH . $relative_path;
    $mtime = @filemtime($full_path);
    return $mtime ? (string) $mtime : MOAVEZE_PLUS_VERSION;
}

/**
 * Main Plugin Class
 */
final class Moaveze_Plus {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core
        require_once MOAVEZE_PLUS_PATH . 'includes/class-helpers.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/class-database.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/class-post-types.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/class-taxonomies.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/class-meta-fields.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/class-pages.php';

        // Admin
        if (is_admin()) {
            require_once MOAVEZE_PLUS_PATH . 'admin/class-admin.php';
            require_once MOAVEZE_PLUS_PATH . 'admin/class-settings.php';
            require_once MOAVEZE_PLUS_PATH . 'admin/class-dashboard.php';
            require_once MOAVEZE_PLUS_PATH . 'admin/class-consultant-panel.php';
        }

        // Frontend
        require_once MOAVEZE_PLUS_PATH . 'frontend/class-frontend.php';
        require_once MOAVEZE_PLUS_PATH . 'frontend/class-submission-form.php';
        require_once MOAVEZE_PLUS_PATH . 'frontend/class-listings.php';
        require_once MOAVEZE_PLUS_PATH . 'frontend/class-map.php';
        require_once MOAVEZE_PLUS_PATH . 'frontend/class-shortcodes.php';
        require_once MOAVEZE_PLUS_PATH . 'frontend/class-dashboard.php';

        // Sample Data
        require_once MOAVEZE_PLUS_PATH . 'includes/class-sample-data.php';

        // Modules
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-matching.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-offers.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-monetization.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-notifications.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-houzez-integration.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-wishlist.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-auction.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-payment.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-price-estimator.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-favorites.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-ai-valuation.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-ai-suggestions.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-public-valuation.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-sms-melipayamak.php';
        require_once MOAVEZE_PLUS_PATH . 'includes/modules/class-addons.php';

        // REST API
        require_once MOAVEZE_PLUS_PATH . 'includes/api/class-rest-api.php';

        // Elementor Integration
        if (did_action('elementor/loaded')) {
            require_once MOAVEZE_PLUS_PATH . 'includes/elementor/class-elementor-loader.php';
            Moaveze_Elementor_Loader::get_instance();
        } else {
            add_action('elementor/loaded', function() {
                require_once MOAVEZE_PLUS_PATH . 'includes/elementor/class-elementor-loader.php';
                Moaveze_Elementor_Loader::get_instance();
            });
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(MOAVEZE_PLUS_FILE, array($this, 'activate'));
        register_deactivation_hook(MOAVEZE_PLUS_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array('Moaveze_Database', 'maybe_upgrade'));
        add_action('init', array('Moaveze_Taxonomies', 'maybe_add_new_terms'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        Moaveze_Database::create_tables();

        // Register post types (for flush)
        Moaveze_Post_Types::register();
        Moaveze_Taxonomies::register();

        // Create the real, editable wp-admin Pages for "ثبت آگهی" and
        // "لیست آگهی‌ها" (see includes/class-pages.php) - fixes the
        // /submit-exchange/ 404 by making sure a real page actually
        // exists at activation time, instead of only being linked to.
        Moaveze_Pages::maybe_create_default_pages();
        Moaveze_Frontend_Dashboard::flush_rules();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        $this->set_default_options();

        // Store plugin version
        update_option('moaveze_plus_version', MOAVEZE_PLUS_VERSION);
        update_option('moaveze_plus_db_version', MOAVEZE_PLUS_DB_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('moaveze-plus', false, dirname(MOAVEZE_PLUS_BASENAME) . '/languages');

        // Initialize components
        Moaveze_Post_Types::register();
        Moaveze_Taxonomies::register();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Main styles
        wp_enqueue_style(
            'moaveze-plus-main',
            MOAVEZE_PLUS_URL . 'assets/css/frontend/main.css',
            array(),
            moaveze_asset_version('assets/css/frontend/main.css')
        );

        // Form styles
        wp_enqueue_style(
            'moaveze-plus-form',
            MOAVEZE_PLUS_URL . 'assets/css/frontend/form.css',
            array('moaveze-plus-main'),
            moaveze_asset_version('assets/css/frontend/form.css')
        );

        // Map styles
        wp_enqueue_style(
            'moaveze-plus-map',
            MOAVEZE_PLUS_URL . 'assets/css/frontend/map.css',
            array('moaveze-plus-main'),
            moaveze_asset_version('assets/css/frontend/map.css')
        );

        // Leaflet Map
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

        // Main JS
        wp_enqueue_script(
            'moaveze-plus-main',
            MOAVEZE_PLUS_URL . 'assets/js/frontend/main.js',
            array('jquery', 'leaflet'),
            moaveze_asset_version('assets/js/frontend/main.js'),
            true
        );

        // Form JS
        wp_enqueue_script(
            'moaveze-plus-form',
            MOAVEZE_PLUS_URL . 'assets/js/frontend/form.js',
            array('jquery', 'moaveze-plus-main'),
            moaveze_asset_version('assets/js/frontend/form.js'),
            true
        );

        // Map JS
        wp_enqueue_script(
            'moaveze-plus-map',
            MOAVEZE_PLUS_URL . 'assets/js/frontend/map.js',
            array('jquery', 'leaflet', 'moaveze-plus-main'),
            moaveze_asset_version('assets/js/frontend/map.js'),
            true
        );

        // Offers styles & JS
        wp_enqueue_style(
            'moaveze-plus-offers',
            MOAVEZE_PLUS_URL . 'assets/css/frontend/offers.css',
            array('moaveze-plus-main'),
            moaveze_asset_version('assets/css/frontend/offers.css')
        );

        wp_enqueue_script(
            'moaveze-plus-offers',
            MOAVEZE_PLUS_URL . 'assets/js/frontend/offers.js',
            array('jquery', 'moaveze-plus-main'),
            moaveze_asset_version('assets/js/frontend/offers.js'),
            true
        );

        // Single listing page (redesigned v2): gallery lightbox, share/copy-link
        wp_enqueue_style(
            'moaveze-plus-single',
            MOAVEZE_PLUS_URL . 'assets/css/frontend/single.css',
            array('moaveze-plus-main'),
            moaveze_asset_version('assets/css/frontend/single.css')
        );

        wp_enqueue_script(
            'moaveze-plus-single',
            MOAVEZE_PLUS_URL . 'assets/js/frontend/single.js',
            array('jquery', 'moaveze-plus-main'),
            moaveze_asset_version('assets/js/frontend/single.js'),
            true
        );

        // Favorites/bookmarks (localStorage-based, works for guests too)
        // Gated behind its own settings toggle (Settings > امکانات
        // تکمیلی), default 'yes' since it already existed before the
        // add-on toggle system was introduced.
        if (get_option('moaveze_addon_favorites', 'yes') === 'yes') {
            wp_enqueue_style(
                'moaveze-plus-favorites',
                MOAVEZE_PLUS_URL . 'assets/css/frontend/favorites.css',
                array('moaveze-plus-main'),
                moaveze_asset_version('assets/css/frontend/favorites.css')
            );

            wp_enqueue_script(
                'moaveze-plus-favorites',
                MOAVEZE_PLUS_URL . 'assets/js/frontend/favorites.js',
                array('jquery', 'moaveze-plus-main'),
                moaveze_asset_version('assets/js/frontend/favorites.js'),
                true
            );
        }

        // Add-on features: report listing, compare listings, QR code -
        // each independently toggled from Settings > امکانات تکمیلی.
        if (get_option('moaveze_addon_report_listing') === 'yes'
            || get_option('moaveze_addon_compare_listings') === 'yes'
            || get_option('moaveze_addon_qr_code') === 'yes') {
            wp_enqueue_style(
                'moaveze-plus-addons',
                MOAVEZE_PLUS_URL . 'assets/css/frontend/addons.css',
                array('moaveze-plus-main'),
                moaveze_asset_version('assets/css/frontend/addons.css')
            );
            wp_enqueue_script(
                'moaveze-plus-addons',
                MOAVEZE_PLUS_URL . 'assets/js/frontend/addons.js',
                array('jquery', 'moaveze-plus-main'),
                moaveze_asset_version('assets/js/frontend/addons.js'),
                true
            );
            wp_localize_script('moaveze-plus-addons', 'moavezeAddons', array(
                'reportEnabled'  => get_option('moaveze_addon_report_listing') === 'yes',
                'compareEnabled' => get_option('moaveze_addon_compare_listings') === 'yes',
                'qrEnabled'      => get_option('moaveze_addon_qr_code') === 'yes',
            ));
        }

        // Localize scripts
        wp_localize_script('moaveze-plus-main', 'moavezePlus', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('moaveze/v1/'),
            'nonce' => wp_create_nonce('moaveze_plus_nonce'),
            'mapCenter' => array(
                'lat' => get_option('moaveze_map_center_lat', '38.0962'),
                'lng' => get_option('moaveze_map_center_lng', '46.2738'),
            ),
            'mapZoom' => get_option('moaveze_map_zoom', '12'),
            // Property types & districts for the inline "register your
            // property" mini-form inside the offer modal (offers.js), so
            // it doesn't need an extra AJAX round trip just to populate
            // two <select> lists.
            'propertyTypes' => array_map(function ($t) {
                return array('id' => $t->term_id, 'slug' => $t->slug, 'name' => $t->name);
            }, get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false)) ?: array()),
            'districts' => array_map(function ($t) {
                return array('id' => $t->term_id, 'slug' => $t->slug, 'name' => $t->name);
            }, get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false)) ?: array()),
            'strings' => array(
                'loading' => 'در حال بارگذاری...',
                'error' => 'خطایی رخ داد',
                'success' => 'عملیات با موفقیت انجام شد',
                'confirm_delete' => 'آیا مطمئن هستید؟',
                'select_location' => 'موقعیت را روی نقشه انتخاب کنید',
                'no_results' => 'نتیجه‌ای یافت نشد',
                'match_found' => 'تطابق جدید پیدا شد!',
            ),
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Load on: our own plugin pages, moaveze_exchange edit/list screens,
        // AND the Houzez "property" CPT edit/list screens (needed for the
        // "افزودن به معاوضه" button/metabox integration to work there).
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $post_type = $screen ? $screen->post_type : get_post_type();

        $is_moaveze_page   = strpos($hook, 'moaveze') !== false;
        $is_moaveze_cpt    = $post_type === 'moaveze_exchange';
        $is_houzez_property = $post_type === 'property';

        if (!$is_moaveze_page && !$is_moaveze_cpt && !$is_houzez_property) {
            return;
        }

        wp_enqueue_style(
            'moaveze-plus-admin',
            MOAVEZE_PLUS_URL . 'assets/css/admin/admin.css',
            array(),
            moaveze_asset_version('assets/css/admin/admin.css')
        );

        wp_enqueue_script(
            'moaveze-plus-admin',
            MOAVEZE_PLUS_URL . 'assets/js/admin/admin.js',
            array('jquery', 'wp-color-picker'),
            moaveze_asset_version('assets/js/admin/admin.js'),
            true
        );

        // AI Valuation admin JS (metabox on the listing edit screen +
        // "test connection" button on Settings > هوش مصنوعی) - staff-only
        // surfaces, but the script itself is harmless to load anywhere
        // in wp-admin since it only binds to elements that don't exist
        // outside those specific screens.
        wp_enqueue_script(
            'moaveze-plus-ai-valuation',
            MOAVEZE_PLUS_URL . 'assets/js/admin/ai-valuation.js',
            array('jquery'),
            moaveze_asset_version('assets/js/admin/ai-valuation.js'),
            true
        );

        wp_localize_script('moaveze-plus-admin', 'moavezeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('moaveze_admin_nonce'),
        ));
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            // General
            'moaveze_enable_exchange' => 'yes',
            'moaveze_require_login' => 'no',
            'moaveze_auto_approve' => 'no',
            'moaveze_listings_per_page' => 12,

            // Map
            'moaveze_map_center_lat' => '38.0962',
            'moaveze_map_center_lng' => '46.2738',
            'moaveze_map_zoom' => '12',
            'moaveze_map_style' => 'default',

            // Monetization
            'moaveze_monetization_mode' => 'consultant', // consultant, subscription, hybrid
            'moaveze_subscription_price' => '0',
            'moaveze_per_contact_price' => '0',
            'moaveze_vip_enabled' => 'no',
            'moaveze_boost_enabled' => 'no',

            // Chat
            'moaveze_chat_enabled' => 'no', // Admin controlled
            'moaveze_chat_require_approval' => 'yes',

            // Notifications
            'moaveze_email_notifications' => 'yes',
            'moaveze_sms_notifications' => 'no',
            'moaveze_push_notifications' => 'no',

            // Display
            // NOTE: 'moaveze_houzez_sync' (registered/saved by the
            // Settings > یکپارچه‌سازی tab) is the option actually read by
            // Moaveze_Houzez_Integration - see class-houzez-integration.php.
            // 'moaveze_show_in_houzez' is kept only for backwards
            // compatibility with any old data and is not read anywhere
            // anymore.
            'moaveze_show_in_houzez' => 'yes',
            'moaveze_houzez_sync'    => 'yes',
            'moaveze_exchange_badge' => 'yes',
            'moaveze_dark_mode' => 'auto',

            // Privacy
            'moaveze_hide_contact_info' => 'yes',
            'moaveze_contact_visible_to' => 'admin', // admin, consultant, subscriber
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
}

/**
 * Initialize Plugin
 */
function moaveze_plus() {
    return Moaveze_Plus::get_instance();
}

// Start the plugin
moaveze_plus();
