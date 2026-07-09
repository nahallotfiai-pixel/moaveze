<?php
/**
 * Moaveze Plus - Frontend Dashboard (Consultant Panel)
 *
 * Registers a standalone dashboard accessible at /moaveze-panel/
 * that provides consultants and admins a modern interface outside wp-admin.
 *
 * @package Moaveze_Plus
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Frontend_Dashboard {

    /** @var string Default dashboard slug */
    const DEFAULT_SLUG = 'moaveze-panel';

    /** @var string Query var name */
    const QUERY_VAR = 'moaveze_panel';

    /**
     * Constructor - register all hooks
     */
    public function __construct() {
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_dashboard_request'));
        add_action('admin_menu', array($this, 'add_panel_link_to_admin_menu'), 99);
    }

    /**
     * Get the dashboard slug (configurable via option)
     *
     * @return string
     */
    public static function get_slug() {
        return get_option('moaveze_panel_slug', self::DEFAULT_SLUG);
    }

    /**
     * Get the full URL to the frontend dashboard
     *
     * @return string
     */
    public static function get_url() {
        return home_url('/' . self::get_slug() . '/');
    }

    /**
     * Register the rewrite rule for the dashboard URL
     */
    public function register_rewrite_rules() {
        $slug = self::get_slug();
        add_rewrite_rule(
            '^' . preg_quote($slug, '/') . '/?$',
            'index.php?' . self::QUERY_VAR . '=1',
            'top'
        );
    }

    /**
     * Add our custom query var so WordPress recognizes it
     *
     * @param array $vars Existing query vars
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    /**
     * Intercept the request and render the dashboard if our query var is set
     */
    public function handle_dashboard_request() {
        if (!get_query_var(self::QUERY_VAR)) {
            return;
        }

        // Access control: must be logged in
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(self::get_url()));
            exit;
        }

        // Access control: must have manage_options OR moaveze_consultant role
        $user = wp_get_current_user();
        $has_access = current_user_can('manage_options')
            || in_array('moaveze_consultant', (array) $user->roles, true)
            || current_user_can('moaveze_manage_offers');

        if (!$has_access) {
            status_header(403);
            $this->render_forbidden();
            exit;
        }

        // Render the full standalone dashboard
        $this->render_dashboard();
        exit;
    }

    /**
     * Render the standalone dashboard page
     */
    private function render_dashboard() {
        // Prepare data needed by the view
        $dashboard_data = array(
            'rest_url'    => rest_url('moaveze/v1/'),
            'nonce'       => wp_create_nonce('wp_rest'),
            'admin_nonce' => wp_create_nonce('moaveze_admin_nonce'),
            'user'        => array(
                'id'           => get_current_user_id(),
                'display_name' => wp_get_current_user()->display_name,
                'avatar'       => get_avatar_url(get_current_user_id(), array('size' => 80)),
                'roles'        => (array) wp_get_current_user()->roles,
                'is_admin'     => current_user_can('manage_options'),
            ),
            'admin_url'  => admin_url(),
            'home_url'   => home_url('/'),
            'plugin_url' => MOAVEZE_PLUS_URL,
            'logout_url' => wp_logout_url(home_url('/')),
        );

        // Load the view (outputs complete HTML)
        include MOAVEZE_PLUS_PATH . 'frontend/views/dashboard-app.php';
    }

    /**
     * Render the 403 forbidden page
     */
    private function render_forbidden() {
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دسترسی غیرمجاز - معاوضه پلاس</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            direction: rtl;
        }
        .forbidden-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 48px;
            text-align: center;
            max-width: 420px;
        }
        .forbidden-box .icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .forbidden-box h1 {
            font-size: 22px;
            color: #1e1b4b;
            margin-bottom: 12px;
        }
        .forbidden-box p {
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .forbidden-box a {
            display: inline-block;
            background: #6366f1;
            color: #fff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .forbidden-box a:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <div class="forbidden-box">
        <div class="icon">🔒</div>
        <h1>دسترسی غیرمجاز</h1>
        <p>شما مجوز دسترسی به پنل مشاوران را ندارید. این بخش فقط برای مشاوران و مدیران سایت قابل دسترسی است.</p>
        <a href="<?php echo esc_url(home_url('/')); ?>">بازگشت به سایت</a>
    </div>
</body>
</html>
        <?php
    }

    /**
     * Add a "پنل مشاوران" link in the wp-admin menu pointing to the frontend dashboard
     */
    public function add_panel_link_to_admin_menu() {
        add_submenu_page(
            'moaveze-plus',
            'پنل مشاوران',
            '<span style="color:#a78bfa;">&#9733;</span> پنل مشاوران',
            'manage_options',
            'moaveze-consultant-panel-redirect',
            array($this, 'admin_redirect_to_panel')
        );
    }

    /**
     * Redirect from wp-admin submenu to the frontend panel
     */
    public function admin_redirect_to_panel() {
        wp_redirect(self::get_url());
        exit;
    }

    /**
     * Flush rewrite rules (call on plugin activation)
     */
    public static function flush_rules() {
        $instance = new self();
        $instance->register_rewrite_rules();
        flush_rewrite_rules();
    }
}

// Initialize
new Moaveze_Frontend_Dashboard();
