<?php
/**
 * Admin Main Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('manage_moaveze_exchange_posts_columns', array($this, 'custom_columns'));
        add_action('manage_moaveze_exchange_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            'معاوضه پلاس',
            'معاوضه پلاس',
            'manage_options',
            'moaveze-plus',
            array($this, 'render_dashboard'),
            'dashicons-randomize',
            25
        );

        // Rename the auto-created first submenu item from "معاوضه پلاس" to "داشبورد"
        add_submenu_page(
            'moaveze-plus',
            'داشبورد معاوضه پلاس',
            'داشبورد',
            'manage_options',
            'moaveze-plus'
        );

        // NOTE: The "آگهی‌ها" (All Exchanges) submenu is added automatically
        // by WordPress because the moaveze_exchange CPT is registered with
        // 'show_in_menu' => 'moaveze-plus' (see class-post-types.php).
        // Do NOT manually add_submenu_page() for edit.php?post_type=moaveze_exchange
        // here, or it will show up twice in the sidebar.

        // Matches
        add_submenu_page(
            'moaveze-plus',
            'تطابق‌ها',
            'تطابق‌ها',
            'manage_options',
            'moaveze-matches',
            array($this, 'render_matches_page')
        );

        // Offers
        add_submenu_page(
            'moaveze-plus',
            'پیشنهادات',
            'پیشنهادات',
            'manage_options',
            'moaveze-offers',
            array($this, 'render_offers_page')
        );


        // Chain Swaps
        add_submenu_page(
            'moaveze-plus',
            'معاوضه زنجیره‌ای',
            'معاوضه زنجیره‌ای',
            'manage_options',
            'moaveze-chains',
            array($this, 'render_chains_page')
        );

        // Consultants Panel
        add_submenu_page(
            'moaveze-plus',
            'پنل مشاوران',
            'مشاوران',
            'manage_options',
            'moaveze-consultants',
            array($this, 'render_consultants_page')
        );

        // Monetization
        add_submenu_page(
            'moaveze-plus',
            'درآمدزایی',
            'درآمدزایی',
            'manage_options',
            'moaveze-monetization',
            array($this, 'render_monetization_page')
        );

        // Settings
        add_submenu_page(
            'moaveze-plus',
            'تنظیمات',
            'تنظیمات',
            'manage_options',
            'moaveze-settings',
            array(new Moaveze_Settings(), 'render_settings_page')
        );

        // Analytics
        add_submenu_page(
            'moaveze-plus',
            'آنالیتیکس',
            'آنالیتیکس',
            'manage_options',
            'moaveze-analytics',
            array($this, 'render_analytics_page')
        );
    }

    /**
     * Admin init
     */
    public function admin_init() {
        // Register settings
        $settings = new Moaveze_Settings();
        $settings->register_settings();
    }

    /**
     * Render Dashboard
     */
    public function render_dashboard() {
        include MOAVEZE_PLUS_PATH . 'admin/views/dashboard.php';
    }

    /**
     * Render Matches Page
     */
    public function render_matches_page() {
        include MOAVEZE_PLUS_PATH . 'admin/views/matches.php';
    }

    /**
     * Render Offers Page
     */
    public function render_offers_page() {
        include MOAVEZE_PLUS_PATH . 'admin/views/offers.php';
    }

    /**
     * Render Chains Page
     */
    public function render_chains_page() {
        include MOAVEZE_PLUS_PATH . 'admin/views/chains.php';
    }

    /**
     * Render Consultants Page
     */
    public function render_consultants_page() {
        include MOAVEZE_PLUS_PATH . 'admin/views/consultants.php';
    }

    /**
     * Render Monetization Page
     */
    public function render_monetization_page() {
        include MOAVEZE_PLUS_PATH . 'admin/views/monetization.php';
    }

    /**
     * Render Analytics Page
     */
    public function render_analytics_page() {
        include MOAVEZE_PLUS_PATH . 'admin/views/analytics.php';
    }

    /**
     * Custom columns for exchange listings
     */
    public function custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'عنوان آگهی';
        $new_columns['property_type'] = 'نوع ملک';
        $new_columns['property_value'] = 'ارزش';
        $new_columns['district'] = 'منطقه';
        $new_columns['exchange_type'] = 'نوع معاوضه';
        $new_columns['status'] = 'وضعیت';
        $new_columns['verified'] = 'تأیید';
        $new_columns['date'] = 'تاریخ';
        return $new_columns;
    }

    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'property_type':
                $terms = get_the_terms($post_id, 'moaveze_property_type');
                echo $terms ? esc_html($terms[0]->name) : '—';
                break;

            case 'property_value':
                $value = get_post_meta($post_id, '_moaveze_property_value', true);
                echo $value ? number_format($value) . ' تومان' : '—';
                break;

            case 'district':
                $terms = get_the_terms($post_id, 'moaveze_district');
                echo $terms ? esc_html($terms[0]->name) : '—';
                break;

            case 'exchange_type':
                $types = array(
                    'property_only'  => 'ملک با ملک',
                    'property_cash'  => 'ملک + نقد',
                    'property_car'   => 'ملک + خودرو',
                    'property_mixed' => 'ترکیبی',
                    'flexible'       => 'انعطاف‌پذیر',
                );
                $type = get_post_meta($post_id, '_moaveze_exchange_type', true);
                echo isset($types[$type]) ? $types[$type] : '—';
                break;

            case 'verified':
                $verified = get_post_meta($post_id, '_moaveze_verified', true);
                echo $verified ? '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>' : '<span class="dashicons dashicons-minus" style="color:#dba617;"></span>';
                break;

            case 'status':
                $status = get_post_status($post_id);
                $statuses = array(
                    'publish' => '<span style="color:#00a32a;">فعال</span>',
                    'pending' => '<span style="color:#dba617;">در انتظار</span>',
                    'draft'   => '<span style="color:#787c82;">پیش‌نویس</span>',
                );
                echo isset($statuses[$status]) ? $statuses[$status] : $status;
                break;
        }
    }
}

// Initialize Admin
new Moaveze_Admin();
