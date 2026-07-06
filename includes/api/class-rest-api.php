<?php
/**
 * Moaveze Plus - Comprehensive REST API
 *
 * Production-ready REST API designed for Flutter mobile app integration.
 * Namespace: moaveze/v1
 *
 * @package Moaveze_Plus
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_REST_API {

    /** @var string API namespace */
    const NAMESPACE = 'moaveze/v1';

    /** @var int Token expiry in seconds (30 days) */
    const TOKEN_EXPIRY = 2592000;

    /** @var int Default items per page */
    const PER_PAGE_DEFAULT = 12;

    /** @var int Maximum items per page */
    const PER_PAGE_MAX = 100;

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_pre_serve_request', array($this, 'add_cors_headers'), 10, 4);
    }

    /**
     * Add CORS headers for mobile app access
     */
    public function add_cors_headers($served, $result, $request, $server) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages, X-RateLimit-Limit, X-RateLimit-Remaining');
        return $served;
    }


    /**
     * Register all REST routes
     */
    public function register_routes() {
        $ns = self::NAMESPACE;

        // ═══════════════════════════════════════════════════════════
        // AUTH ROUTES
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/auth/login', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'auth_login'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'phone' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($value) {
                        return preg_match('/^09[0-9]{9}$/', $value);
                    },
                ),
                'password' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($ns, '/auth/register', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'auth_register'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'phone' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($value) {
                        return preg_match('/^09[0-9]{9}$/', $value);
                    },
                ),
                'password' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'validate_callback' => function($value) {
                        return strlen($value) >= 6;
                    },
                ),
                'display_name' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));

        register_rest_route($ns, '/auth/me', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'auth_me'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($ns, '/auth/refresh', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'auth_refresh'),
            'permission_callback' => array($this, 'check_auth'),
        ));


        // ═══════════════════════════════════════════════════════════
        // EXCHANGE LISTINGS - PUBLIC
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/exchanges', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_exchanges'),
            'permission_callback' => '__return_true',
            'args'                => $this->get_exchange_list_args(),
        ));

        register_rest_route($ns, '/exchanges/featured', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_featured_exchanges'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'limit' => array(
                    'default'           => 10,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));

        register_rest_route($ns, '/exchanges/recent', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_recent_exchanges'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'limit' => array(
                    'default'           => 10,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));

        register_rest_route($ns, '/exchanges/my', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_my_exchanges'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'page'     => array('default' => 1, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'per_page' => array('default' => 12, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'status'   => array('default' => '', 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
            ),
        ));

        register_rest_route($ns, '/exchanges/(?P<id>\d+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_single_exchange'),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                ),
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => array($this, 'update_exchange'),
                'permission_callback' => array($this, 'check_auth'),
                'args'                => array(
                    'id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'delete_exchange'),
                'permission_callback' => array($this, 'check_auth'),
                'args'                => array(
                    'id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                ),
            ),
        ));

        register_rest_route($ns, '/exchanges', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'create_exchange'),
            'permission_callback' => array($this, 'check_auth'),
        ));


        // ═══════════════════════════════════════════════════════════
        // SEARCH & FILTERS - PUBLIC
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/search', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'advanced_search'),
            'permission_callback' => '__return_true',
            'args'                => $this->get_search_args(),
        ));

        register_rest_route($ns, '/filters/types', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_filter_types'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($ns, '/filters/districts', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_filter_districts'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($ns, '/filters/features', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_filter_features'),
            'permission_callback' => '__return_true',
        ));

        // ═══════════════════════════════════════════════════════════
        // MAP - PUBLIC
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/map/markers', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_map_markers'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'type'     => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
                'district' => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
            ),
        ));

        register_rest_route($ns, '/map/cluster', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_map_clusters'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'north' => array('required' => true, 'type' => 'number', 'sanitize_callback' => 'floatval'),
                'south' => array('required' => true, 'type' => 'number', 'sanitize_callback' => 'floatval'),
                'east'  => array('required' => true, 'type' => 'number', 'sanitize_callback' => 'floatval'),
                'west'  => array('required' => true, 'type' => 'number', 'sanitize_callback' => 'floatval'),
                'zoom'  => array('default' => 12, 'type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));


        // ═══════════════════════════════════════════════════════════
        // OFFERS - AUTHENTICATED
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/offers', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'create_offer'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'exchange_id' => array('required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'offer_type'  => array('default' => 'direct', 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
                'message'     => array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'),
                'cash_offered'    => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                'assets_offered'  => array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'),
                'from_exchange_id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route($ns, '/offers/my', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_my_offers'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'page'     => array('default' => 1, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'per_page' => array('default' => 12, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'status'   => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
            ),
        ));

        register_rest_route($ns, '/offers/received', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_received_offers'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'page'     => array('default' => 1, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'per_page' => array('default' => 12, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'status'   => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
            ),
        ));

        register_rest_route($ns, '/offers/(?P<id>\d+)/status', array(
            'methods'             => 'PUT',
            'callback'            => array($this, 'update_offer_status'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'id'     => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                'status' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($value) {
                        return in_array($value, array('accepted', 'rejected', 'negotiating'));
                    },
                ),
                'notes' => array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'),
            ),
        ));


        // ═══════════════════════════════════════════════════════════
        // FAVORITES / WISHLIST - AUTHENTICATED
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/favorites', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_favorites'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($ns, '/favorites/(?P<listing_id>\d+)', array(
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'add_favorite'),
                'permission_callback' => array($this, 'check_auth'),
                'args'                => array(
                    'listing_id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'remove_favorite'),
                'permission_callback' => array($this, 'check_auth'),
                'args'                => array(
                    'listing_id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                ),
            ),
        ));

        // ═══════════════════════════════════════════════════════════
        // NOTIFICATIONS - AUTHENTICATED
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/notifications', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'page'     => array('default' => 1, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'per_page' => array('default' => 15, 'type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route($ns, '/notifications/unread-count', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_unread_count'),
            'permission_callback' => array($this, 'check_auth'),
        ));

        register_rest_route($ns, '/notifications/(?P<id>\d+)/read', array(
            'methods'             => 'PUT',
            'callback'            => array($this, 'mark_notification_read'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route($ns, '/notifications/read-all', array(
            'methods'             => 'PUT',
            'callback'            => array($this, 'mark_all_notifications_read'),
            'permission_callback' => array($this, 'check_auth'),
        ));


        // ═══════════════════════════════════════════════════════════
        // VALUATION
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/valuation/estimate', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'valuation_estimate'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'area'     => array('required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'type'     => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
                'district' => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'),
                'rooms'    => array('type' => 'integer', 'sanitize_callback' => 'absint'),
                'year_built' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route($ns, '/valuation/(?P<post_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_valuation'),
            'permission_callback' => array($this, 'check_staff'),
            'args'                => array(
                'post_id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route($ns, '/valuation/(?P<post_id>\d+)/history', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_valuation_history'),
            'permission_callback' => array($this, 'check_staff'),
            'args'                => array(
                'post_id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        // ═══════════════════════════════════════════════════════════
        // CONFIG & STATS - PUBLIC
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/config', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_config'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($ns, '/stats', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_stats'),
            'permission_callback' => '__return_true',
        ));

        // ═══════════════════════════════════════════════════════════
        // CONTACT / COMMUNICATION - AUTHENTICATED
        // ═══════════════════════════════════════════════════════════
        register_rest_route($ns, '/contact/(?P<listing_id>\d+)', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'request_contact'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'listing_id' => array('type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));

        register_rest_route($ns, '/contact/requests', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_contact_requests'),
            'permission_callback' => array($this, 'check_auth'),
            'args'                => array(
                'page'     => array('default' => 1, 'type' => 'integer', 'sanitize_callback' => 'absint'),
                'per_page' => array('default' => 12, 'type' => 'integer', 'sanitize_callback' => 'absint'),
            ),
        ));
    }


    // ═══════════════════════════════════════════════════════════════
    // PERMISSION CALLBACKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Authenticate user via Bearer token or WordPress cookie
     */
    public function check_auth($request) {
        $user_id = $this->get_authenticated_user_id($request);
        if ($user_id) {
            wp_set_current_user($user_id);
            return true;
        }
        return new WP_Error('unauthorized', 'Authentication required.', array('status' => 401));
    }

    /**
     * Check staff access (admin or moaveze_consultant)
     */
    public function check_staff($request) {
        $user_id = $this->get_authenticated_user_id($request);
        if (!$user_id) {
            return new WP_Error('unauthorized', 'Authentication required.', array('status' => 401));
        }
        wp_set_current_user($user_id);
        if (current_user_can('manage_options') || current_user_can('moaveze_manage_offers')) {
            return true;
        }
        $user = get_userdata($user_id);
        if ($user && in_array('moaveze_consultant', (array) $user->roles)) {
            return true;
        }
        return new WP_Error('forbidden', 'Staff access required.', array('status' => 403));
    }

    /**
     * Extract and validate user from Authorization header
     */
    private function get_authenticated_user_id($request) {
        // Check cookie auth first (logged in via browser)
        if (is_user_logged_in()) {
            return get_current_user_id();
        }

        // Check Bearer token
        $auth_header = $request->get_header('Authorization');
        if (!$auth_header) {
            return false;
        }

        if (strpos($auth_header, 'Bearer ') === 0) {
            $token = substr($auth_header, 7);
            return $this->validate_token($token);
        }

        return false;
    }

    /**
     * Validate API token from user meta
     */
    private function validate_token($token) {
        if (empty($token) || strlen($token) < 32) {
            return false;
        }

        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_moaveze_api_token' AND meta_value = %s",
            $token
        ));

        if (!$user_id) {
            return false;
        }

        // Check expiry
        $expiry = get_user_meta($user_id, '_moaveze_api_token_expiry', true);
        if ($expiry && time() > (int) $expiry) {
            delete_user_meta($user_id, '_moaveze_api_token');
            delete_user_meta($user_id, '_moaveze_api_token_expiry');
            return false;
        }

        return (int) $user_id;
    }

    /**
     * Generate a new API token for a user
     */
    private function generate_token($user_id) {
        $token = wp_generate_password(64, false);
        $expiry = time() + self::TOKEN_EXPIRY;

        update_user_meta($user_id, '_moaveze_api_token', $token);
        update_user_meta($user_id, '_moaveze_api_token_expiry', $expiry);

        return array(
            'token'      => $token,
            'expires_at' => gmdate('c', $expiry),
            'expires_in' => self::TOKEN_EXPIRY,
        );
    }


    // ═══════════════════════════════════════════════════════════════
    // AUTH ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * POST /auth/login
     * Authenticate with phone + password, return token
     */
    public function auth_login($request) {
        $phone = $request->get_param('phone');
        $password = $request->get_param('password');

        // Find user by phone (stored in user meta or as username)
        $user = $this->find_user_by_phone($phone);

        if (!$user) {
            return $this->error_response('invalid_credentials', 'شماره تلفن یا رمز عبور اشتباه است.', 401);
        }

        // Verify password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return $this->error_response('invalid_credentials', 'شماره تلفن یا رمز عبور اشتباه است.', 401);
        }

        // Generate token
        $token_data = $this->generate_token($user->ID);

        return $this->success_response(array(
            'user'  => $this->format_user($user),
            'token' => $token_data,
        ));
    }

    /**
     * POST /auth/register
     * Register a new user
     */
    public function auth_register($request) {
        $phone = $request->get_param('phone');
        $password = $request->get_param('password');
        $display_name = $request->get_param('display_name');
        $email = $request->get_param('email');

        // Check if phone already exists
        if ($this->find_user_by_phone($phone)) {
            return $this->error_response('phone_exists', 'این شماره تلفن قبلاً ثبت شده است.', 409);
        }

        // Check email
        if ($email && email_exists($email)) {
            return $this->error_response('email_exists', 'این ایمیل قبلاً ثبت شده است.', 409);
        }

        // Create user
        $user_id = wp_create_user($phone, $password, $email ?: $phone . '@moaveze.local');
        if (is_wp_error($user_id)) {
            return $this->error_response('registration_failed', 'خطا در ثبت‌نام. لطفاً مجدداً تلاش کنید.', 500);
        }

        // Update user meta
        wp_update_user(array(
            'ID'           => $user_id,
            'display_name' => $display_name,
        ));
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, '_moaveze_registered_via', 'api');

        $user = get_userdata($user_id);
        $token_data = $this->generate_token($user_id);

        return $this->success_response(array(
            'user'  => $this->format_user($user),
            'token' => $token_data,
        ), 201);
    }

    /**
     * GET /auth/me
     * Get current authenticated user profile
     */
    public function auth_me($request) {
        $user = wp_get_current_user();
        $user_data = $this->format_user($user);

        // Add extra profile data
        $user_data['favorites_count'] = count(Moaveze_Favorites::get_user_favorite_ids($user->ID));
        $user_data['listings_count'] = (int) count_user_posts($user->ID, 'moaveze_exchange');

        return $this->success_response(array('user' => $user_data));
    }

    /**
     * POST /auth/refresh
     * Refresh the API token
     */
    public function auth_refresh($request) {
        $user_id = get_current_user_id();
        $token_data = $this->generate_token($user_id);
        return $this->success_response(array('token' => $token_data));
    }

    /**
     * Find a user by phone number
     */
    private function find_user_by_phone($phone) {
        // Check user meta
        $users = get_users(array(
            'meta_key'   => 'phone',
            'meta_value' => $phone,
            'number'     => 1,
        ));

        if (!empty($users)) {
            return $users[0];
        }

        // Check username (phone as username)
        $user = get_user_by('login', $phone);
        if ($user) {
            return $user;
        }

        // Check billing_phone (WooCommerce)
        $users = get_users(array(
            'meta_key'   => 'billing_phone',
            'meta_value' => $phone,
            'number'     => 1,
        ));

        return !empty($users) ? $users[0] : null;
    }

    /**
     * Format user data for API response
     */
    private function format_user($user) {
        return array(
            'id'           => $user->ID,
            'display_name' => $user->display_name,
            'phone'        => get_user_meta($user->ID, 'phone', true) ?: $user->user_login,
            'email'        => $user->user_email,
            'avatar'       => get_avatar_url($user->ID, array('size' => 200)),
            'roles'        => (array) $user->roles,
            'registered'   => gmdate('c', strtotime($user->user_registered)),
        );
    }


    // ═══════════════════════════════════════════════════════════════
    // EXCHANGE LISTING ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /exchanges
     * Paginated list with filters
     */
    public function get_exchanges($request) {
        $per_page = min($request->get_param('per_page') ?: self::PER_PAGE_DEFAULT, self::PER_PAGE_MAX);
        $page = max(1, $request->get_param('page') ?: 1);

        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        // Filters
        $tax_query = array();
        $meta_query = array();

        $type = $request->get_param('type');
        if ($type) {
            $tax_query[] = array('taxonomy' => 'moaveze_property_type', 'field' => 'slug', 'terms' => $type);
        }

        $district = $request->get_param('district');
        if ($district) {
            $tax_query[] = array('taxonomy' => 'moaveze_district', 'field' => 'slug', 'terms' => $district);
        }

        $exchange_type = $request->get_param('exchange_type');
        if ($exchange_type) {
            $meta_query[] = array('key' => '_moaveze_exchange_type', 'value' => $exchange_type);
        }

        $min_value = $request->get_param('min_value');
        if ($min_value) {
            $meta_query[] = array('key' => '_moaveze_property_value', 'value' => (int) $min_value, 'compare' => '>=', 'type' => 'NUMERIC');
        }

        $max_value = $request->get_param('max_value');
        if ($max_value) {
            $meta_query[] = array('key' => '_moaveze_property_value', 'value' => (int) $max_value, 'compare' => '<=', 'type' => 'NUMERIC');
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $args['tax_query'] = $tax_query;
        }

        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $args['meta_query'] = $meta_query;
        }

        // Search
        $search = $request->get_param('search');
        if ($search) {
            $args['s'] = $search;
        }

        // Orderby
        $orderby = $request->get_param('orderby');
        switch ($orderby) {
            case 'value_asc':
                $args['meta_key'] = '_moaveze_property_value';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'value_desc':
                $args['meta_key'] = '_moaveze_property_value';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'area_asc':
                $args['meta_key'] = '_moaveze_area_sqm';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'area_desc':
                $args['meta_key'] = '_moaveze_area_sqm';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        $query = new WP_Query($args);
        $items = array();

        while ($query->have_posts()) {
            $query->the_post();
            $items[] = $this->format_exchange(get_the_ID(), false);
        }
        wp_reset_postdata();

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $query->found_posts, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) $query->max_num_pages)
        );
    }

    /**
     * GET /exchanges/featured
     */
    public function get_featured_exchanges($request) {
        $limit = min($request->get_param('limit') ?: 10, 50);

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_query'     => array(
                array('key' => '_moaveze_featured', 'value' => '1'),
            ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $items = array();
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = $this->format_exchange(get_the_ID(), false);
        }
        wp_reset_postdata();

        return $this->success_response(array('items' => $items));
    }

    /**
     * GET /exchanges/recent
     */
    public function get_recent_exchanges($request) {
        $limit = min($request->get_param('limit') ?: 10, 50);

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $items = array();
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = $this->format_exchange(get_the_ID(), false);
        }
        wp_reset_postdata();

        return $this->success_response(array('items' => $items));
    }


    /**
     * GET /exchanges/{id}
     * Full details for a single listing
     */
    public function get_single_exchange($request) {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'moaveze_exchange' || $post->post_status !== 'publish') {
            return $this->error_response('not_found', 'آگهی مورد نظر یافت نشد.', 404);
        }

        // Increment view count
        $views = (int) get_post_meta($id, '_moaveze_views', true);
        update_post_meta($id, '_moaveze_views', $views + 1);

        return $this->success_response(array('item' => $this->format_exchange($id, true)));
    }

    /**
     * GET /exchanges/my
     * Current user's listings
     */
    public function get_my_exchanges($request) {
        $per_page = min($request->get_param('per_page') ?: self::PER_PAGE_DEFAULT, self::PER_PAGE_MAX);
        $page = max(1, $request->get_param('page') ?: 1);
        $status = $request->get_param('status');

        $post_status = array('publish', 'pending', 'draft');
        if ($status && in_array($status, $post_status)) {
            $post_status = array($status);
        }

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => $post_status,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'author'         => get_current_user_id(),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $items = array();
        while ($query->have_posts()) {
            $query->the_post();
            $item = $this->format_exchange(get_the_ID(), false);
            $item['post_status'] = get_post_status(get_the_ID());
            $items[] = $item;
        }
        wp_reset_postdata();

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $query->found_posts, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) $query->max_num_pages)
        );
    }

    /**
     * POST /exchanges
     * Create a new exchange listing
     */
    public function create_exchange($request) {
        $params = $request->get_params();
        $user_id = get_current_user_id();

        $title = sanitize_text_field($params['title'] ?? '');
        if (empty($title)) {
            return $this->error_response('missing_title', 'عنوان آگهی الزامی است.', 400);
        }

        $post_id = wp_insert_post(array(
            'post_title'  => $title,
            'post_type'   => 'moaveze_exchange',
            'post_status' => 'pending',
            'post_author' => $user_id,
            'post_content' => sanitize_textarea_field($params['description'] ?? ''),
        ));

        if (is_wp_error($post_id)) {
            return $this->error_response('create_failed', 'خطا در ایجاد آگهی.', 500);
        }

        // Set meta fields
        $meta_fields = array(
            'property_value' => absint($params['property_value'] ?? 0),
            'area_sqm'       => absint($params['area_sqm'] ?? 0),
            'rooms'          => absint($params['rooms'] ?? 0),
            'floor'          => sanitize_text_field($params['floor'] ?? ''),
            'year_built'     => absint($params['year_built'] ?? 0),
            'exchange_type'  => sanitize_text_field($params['exchange_type'] ?? 'flexible'),
            'latitude'       => floatval($params['latitude'] ?? 0),
            'longitude'      => floatval($params['longitude'] ?? 0),
            'cash_difference' => absint($params['cash_difference'] ?? 0),
            'cash_direction' => sanitize_text_field($params['cash_direction'] ?? ''),
            'contact_name'   => sanitize_text_field($params['contact_name'] ?? ''),
            'contact_phone'  => sanitize_text_field($params['contact_phone'] ?? ''),
        );

        foreach ($meta_fields as $key => $value) {
            if ($value) {
                update_post_meta($post_id, '_moaveze_' . $key, $value);
            }
        }

        // Set taxonomies
        if (!empty($params['property_type'])) {
            wp_set_object_terms($post_id, sanitize_text_field($params['property_type']), 'moaveze_property_type');
        }
        if (!empty($params['district'])) {
            wp_set_object_terms($post_id, sanitize_text_field($params['district']), 'moaveze_district');
        }
        if (!empty($params['features']) && is_array($params['features'])) {
            $features = array_map('sanitize_text_field', $params['features']);
            wp_set_object_terms($post_id, $features, 'moaveze_feature');
        }

        // Handle gallery images
        if (!empty($params['gallery']) && is_array($params['gallery'])) {
            $gallery_ids = array_map('absint', $params['gallery']);
            update_post_meta($post_id, '_moaveze_gallery', $gallery_ids);
            if (!empty($gallery_ids[0])) {
                set_post_thumbnail($post_id, $gallery_ids[0]);
            }
        }

        // Insert into exchanges table
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_exchanges',
            array(
                'post_id'        => $post_id,
                'user_id'        => $user_id,
                'property_type'  => sanitize_text_field($params['property_type'] ?? ''),
                'property_value' => $meta_fields['property_value'],
                'area_sqm'       => $meta_fields['area_sqm'],
                'exchange_type'  => $meta_fields['exchange_type'],
                'district'       => sanitize_text_field($params['district'] ?? ''),
                'status'         => 'active',
                'contact_name'   => $meta_fields['contact_name'],
                'contact_phone'  => $meta_fields['contact_phone'],
            ),
            array('%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        return $this->success_response(
            array('item' => $this->format_exchange($post_id, true)),
            201
        );
    }


    /**
     * PUT /exchanges/{id}
     * Update own listing
     */
    public function update_exchange($request) {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'moaveze_exchange') {
            return $this->error_response('not_found', 'آگهی یافت نشد.', 404);
        }

        if ((int) $post->post_author !== get_current_user_id() && !current_user_can('manage_options')) {
            return $this->error_response('forbidden', 'شما مجاز به ویرایش این آگهی نیستید.', 403);
        }

        $params = $request->get_params();

        // Update post
        $post_data = array('ID' => $id);
        if (isset($params['title'])) {
            $post_data['post_title'] = sanitize_text_field($params['title']);
        }
        if (isset($params['description'])) {
            $post_data['post_content'] = sanitize_textarea_field($params['description']);
        }
        wp_update_post($post_data);

        // Update meta fields
        $meta_fields = array(
            'property_value', 'area_sqm', 'rooms', 'floor', 'year_built',
            'exchange_type', 'latitude', 'longitude', 'cash_difference',
            'cash_direction', 'contact_name', 'contact_phone',
        );

        foreach ($meta_fields as $key) {
            if (isset($params[$key])) {
                $value = in_array($key, array('latitude', 'longitude'))
                    ? floatval($params[$key])
                    : (is_numeric($params[$key]) ? absint($params[$key]) : sanitize_text_field($params[$key]));
                update_post_meta($id, '_moaveze_' . $key, $value);
            }
        }

        // Update taxonomies
        if (isset($params['property_type'])) {
            wp_set_object_terms($id, sanitize_text_field($params['property_type']), 'moaveze_property_type');
        }
        if (isset($params['district'])) {
            wp_set_object_terms($id, sanitize_text_field($params['district']), 'moaveze_district');
        }
        if (isset($params['features']) && is_array($params['features'])) {
            wp_set_object_terms($id, array_map('sanitize_text_field', $params['features']), 'moaveze_feature');
        }

        // Update gallery
        if (isset($params['gallery']) && is_array($params['gallery'])) {
            $gallery_ids = array_map('absint', $params['gallery']);
            update_post_meta($id, '_moaveze_gallery', $gallery_ids);
            if (!empty($gallery_ids[0])) {
                set_post_thumbnail($id, $gallery_ids[0]);
            }
        }

        return $this->success_response(array('item' => $this->format_exchange($id, true)));
    }

    /**
     * DELETE /exchanges/{id}
     * Delete own listing
     */
    public function delete_exchange($request) {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'moaveze_exchange') {
            return $this->error_response('not_found', 'آگهی یافت نشد.', 404);
        }

        if ((int) $post->post_author !== get_current_user_id() && !current_user_can('manage_options')) {
            return $this->error_response('forbidden', 'شما مجاز به حذف این آگهی نیستید.', 403);
        }

        // Soft delete - move to trash
        wp_trash_post($id);

        // Update exchanges table
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'moaveze_exchanges',
            array('status' => 'deleted'),
            array('post_id' => $id),
            array('%s'),
            array('%d')
        );

        return $this->success_response(array('deleted' => true, 'id' => $id));
    }


    // ═══════════════════════════════════════════════════════════════
    // SEARCH & FILTER ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /search
     * Advanced search with all fields
     */
    public function advanced_search($request) {
        $per_page = min($request->get_param('per_page') ?: self::PER_PAGE_DEFAULT, self::PER_PAGE_MAX);
        $page = max(1, $request->get_param('page') ?: 1);

        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        $tax_query = array('relation' => 'AND');
        $meta_query = array('relation' => 'AND');

        // Taxonomy filters
        $type = $request->get_param('type');
        if ($type) {
            $tax_query[] = array('taxonomy' => 'moaveze_property_type', 'field' => 'slug', 'terms' => $type);
        }

        $district = $request->get_param('district');
        if ($district) {
            $tax_query[] = array('taxonomy' => 'moaveze_district', 'field' => 'slug', 'terms' => $district);
        }

        $features = $request->get_param('features');
        if ($features) {
            $feature_slugs = array_map('sanitize_text_field', explode(',', $features));
            $tax_query[] = array('taxonomy' => 'moaveze_feature', 'field' => 'slug', 'terms' => $feature_slugs);
        }

        // Meta filters
        $exchange_type = $request->get_param('exchange_type');
        if ($exchange_type) {
            $meta_query[] = array('key' => '_moaveze_exchange_type', 'value' => $exchange_type);
        }

        $min_value = $request->get_param('min_value');
        if ($min_value) {
            $meta_query[] = array('key' => '_moaveze_property_value', 'value' => (int) $min_value, 'compare' => '>=', 'type' => 'NUMERIC');
        }

        $max_value = $request->get_param('max_value');
        if ($max_value) {
            $meta_query[] = array('key' => '_moaveze_property_value', 'value' => (int) $max_value, 'compare' => '<=', 'type' => 'NUMERIC');
        }

        $min_area = $request->get_param('min_area');
        if ($min_area) {
            $meta_query[] = array('key' => '_moaveze_area_sqm', 'value' => (int) $min_area, 'compare' => '>=', 'type' => 'NUMERIC');
        }

        $max_area = $request->get_param('max_area');
        if ($max_area) {
            $meta_query[] = array('key' => '_moaveze_area_sqm', 'value' => (int) $max_area, 'compare' => '<=', 'type' => 'NUMERIC');
        }

        $rooms = $request->get_param('rooms');
        if ($rooms) {
            $meta_query[] = array('key' => '_moaveze_rooms', 'value' => (int) $rooms, 'compare' => '>=', 'type' => 'NUMERIC');
        }

        // Geo search (lat/lng radius in km)
        $lat = $request->get_param('lat');
        $lng = $request->get_param('lng');
        $radius = $request->get_param('radius');
        if ($lat && $lng && $radius) {
            // Approximate bounding box
            $lat = floatval($lat);
            $lng = floatval($lng);
            $radius_km = floatval($radius);
            $lat_delta = $radius_km / 111.0;
            $lng_delta = $radius_km / (111.0 * cos(deg2rad($lat)));

            $meta_query[] = array('key' => '_moaveze_latitude', 'value' => array($lat - $lat_delta, $lat + $lat_delta), 'compare' => 'BETWEEN', 'type' => 'DECIMAL(10,7)');
            $meta_query[] = array('key' => '_moaveze_longitude', 'value' => array($lng - $lng_delta, $lng + $lng_delta), 'compare' => 'BETWEEN', 'type' => 'DECIMAL(10,7)');
        }

        // Keyword search
        $search = $request->get_param('search');
        if ($search) {
            $args['s'] = $search;
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query($args);
        $items = array();

        while ($query->have_posts()) {
            $query->the_post();
            $items[] = $this->format_exchange(get_the_ID(), false);
        }
        wp_reset_postdata();

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $query->found_posts, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) $query->max_num_pages)
        );
    }


    /**
     * GET /filters/types
     */
    public function get_filter_types($request) {
        $terms = get_terms(array(
            'taxonomy'   => 'moaveze_property_type',
            'hide_empty' => false,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ));

        $items = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $items[] = array(
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                );
            }
        }

        return $this->success_response(array('items' => $items));
    }

    /**
     * GET /filters/districts
     */
    public function get_filter_districts($request) {
        $terms = get_terms(array(
            'taxonomy'   => 'moaveze_district',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        $items = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $items[] = array(
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                    'parent' => $term->parent,
                );
            }
        }

        return $this->success_response(array('items' => $items));
    }

    /**
     * GET /filters/features
     */
    public function get_filter_features($request) {
        $terms = get_terms(array(
            'taxonomy'   => 'moaveze_feature',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        $items = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $items[] = array(
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                );
            }
        }

        return $this->success_response(array('items' => $items));
    }


    // ═══════════════════════════════════════════════════════════════
    // MAP ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /map/markers
     * Lightweight markers for all geolocated listings
     */
    public function get_map_markers($request) {
        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => 500,
            'meta_query'     => array(
                array('key' => '_moaveze_latitude', 'value' => '', 'compare' => '!='),
                array('key' => '_moaveze_longitude', 'value' => '', 'compare' => '!='),
            ),
        );

        $type = $request->get_param('type');
        if ($type) {
            $args['tax_query'] = array(
                array('taxonomy' => 'moaveze_property_type', 'field' => 'slug', 'terms' => $type),
            );
        }

        $district = $request->get_param('district');
        if ($district) {
            if (!isset($args['tax_query'])) $args['tax_query'] = array();
            $args['tax_query'][] = array('taxonomy' => 'moaveze_district', 'field' => 'slug', 'terms' => $district);
        }

        $query = new WP_Query($args);
        $markers = array();

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $value = absint(get_post_meta($id, '_moaveze_property_value', true));
            $type_terms = get_the_terms($id, 'moaveze_property_type');

            $markers[] = array(
                'id'        => $id,
                'title'     => get_the_title(),
                'lat'       => floatval(get_post_meta($id, '_moaveze_latitude', true)),
                'lng'       => floatval(get_post_meta($id, '_moaveze_longitude', true)),
                'value'     => $value,
                'value_formatted' => Moaveze_Helpers::short_price($value, true, false),
                'type'      => $type_terms ? $type_terms[0]->name : '',
                'type_slug' => $type_terms ? $type_terms[0]->slug : '',
                'thumbnail' => get_the_post_thumbnail_url($id, 'thumbnail') ?: '',
            );
        }
        wp_reset_postdata();

        return $this->success_response(array('markers' => $markers, 'total' => count($markers)));
    }

    /**
     * GET /map/cluster
     * Clustered markers within bounds for performance
     */
    public function get_map_clusters($request) {
        $north = $request->get_param('north');
        $south = $request->get_param('south');
        $east = $request->get_param('east');
        $west = $request->get_param('west');
        $zoom = $request->get_param('zoom') ?: 12;

        global $wpdb;

        // Get all posts within bounds
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title,
                    lat.meta_value as latitude,
                    lng.meta_value as longitude,
                    val.meta_value as property_value
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} lat ON p.ID = lat.post_id AND lat.meta_key = '_moaveze_latitude'
             INNER JOIN {$wpdb->postmeta} lng ON p.ID = lng.post_id AND lng.meta_key = '_moaveze_longitude'
             LEFT JOIN {$wpdb->postmeta} val ON p.ID = val.post_id AND val.meta_key = '_moaveze_property_value'
             WHERE p.post_type = 'moaveze_exchange'
               AND p.post_status = 'publish'
               AND CAST(lat.meta_value AS DECIMAL(10,7)) BETWEEN %f AND %f
               AND CAST(lng.meta_value AS DECIMAL(10,7)) BETWEEN %f AND %f",
            $south, $north, $west, $east
        ));

        // Simple grid-based clustering
        $cluster_size = 0.01 * pow(2, max(0, 15 - $zoom));
        $clusters = array();

        foreach ($posts as $post) {
            $lat = floatval($post->latitude);
            $lng = floatval($post->longitude);
            $cluster_key = floor($lat / $cluster_size) . '_' . floor($lng / $cluster_size);

            if (!isset($clusters[$cluster_key])) {
                $clusters[$cluster_key] = array(
                    'lat'   => 0,
                    'lng'   => 0,
                    'count' => 0,
                    'items' => array(),
                );
            }

            $clusters[$cluster_key]['lat'] += $lat;
            $clusters[$cluster_key]['lng'] += $lng;
            $clusters[$cluster_key]['count']++;
            if ($clusters[$cluster_key]['count'] <= 5) {
                $clusters[$cluster_key]['items'][] = array(
                    'id'    => (int) $post->ID,
                    'title' => $post->post_title,
                    'value' => absint($post->property_value),
                );
            }
        }

        $result = array();
        foreach ($clusters as $cluster) {
            $result[] = array(
                'lat'   => $cluster['lat'] / $cluster['count'],
                'lng'   => $cluster['lng'] / $cluster['count'],
                'count' => $cluster['count'],
                'items' => $cluster['items'],
            );
        }

        return $this->success_response(array('clusters' => $result, 'total' => count($posts)));
    }


    // ═══════════════════════════════════════════════════════════════
    // OFFERS ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * POST /offers
     * Submit an offer on a listing
     */
    public function create_offer($request) {
        $exchange_id = $request->get_param('exchange_id');
        $user_id = get_current_user_id();

        global $wpdb;

        // Validate exchange exists
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d AND status = 'active'",
            $exchange_id
        ));

        if (!$exchange) {
            return $this->error_response('invalid_exchange', 'آگهی فعال نیست یا یافت نشد.', 404);
        }

        // Prevent self-offer
        if ((int) $exchange->user_id === $user_id) {
            return $this->error_response('self_offer', 'امکان ارسال پیشنهاد برای آگهی خودتان وجود ندارد.', 400);
        }

        // Check for duplicate pending offer
        $duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}moaveze_offers WHERE exchange_id = %d AND from_user_id = %d AND status = 'pending'",
            $exchange_id, $user_id
        ));

        if ($duplicate) {
            return $this->error_response('duplicate_offer', 'شما قبلاً یک پیشنهاد فعال برای این آگهی دارید.', 409);
        }

        $offer_type = $request->get_param('offer_type') ?: 'direct';
        $message = $request->get_param('message') ?: '';
        $cash_offered = $request->get_param('cash_offered') ?: 0;
        $assets_offered = $request->get_param('assets_offered') ?: '';
        $from_exchange_id = $request->get_param('from_exchange_id') ?: 0;

        $offer_details = array(
            'message'      => $message,
            'assets'       => $assets_offered,
            'offer_type'   => $offer_type,
            'timestamp'    => current_time('mysql'),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'exchange_id'      => $exchange_id,
                'from_user_id'     => $user_id,
                'from_exchange_id' => $from_exchange_id ?: null,
                'offer_type'       => $offer_type,
                'offer_details'    => wp_json_encode($offer_details),
                'cash_offered'     => $cash_offered,
                'assets_offered'   => $assets_offered,
                'message'          => $message,
                'status'           => 'pending',
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        if (!$result) {
            return $this->error_response('offer_failed', 'خطا در ارسال پیشنهاد.', 500);
        }

        $offer_id = $wpdb->insert_id;

        // Trigger notification
        do_action('moaveze_new_offer', $offer_id, $exchange_id);

        return $this->success_response(array(
            'offer_id' => $offer_id,
            'message'  => 'پیشنهاد شما با موفقیت ارسال شد.',
        ), 201);
    }

    /**
     * GET /offers/my
     * Current user's sent offers
     */
    public function get_my_offers($request) {
        $per_page = min($request->get_param('per_page') ?: self::PER_PAGE_DEFAULT, self::PER_PAGE_MAX);
        $page = max(1, $request->get_param('page') ?: 1);
        $status = $request->get_param('status');
        $offset = ($page - 1) * $per_page;

        global $wpdb;

        $where = $wpdb->prepare("WHERE o.from_user_id = %d", get_current_user_id());
        if ($status && in_array($status, array('pending', 'accepted', 'rejected', 'negotiating', 'countered', 'withdrawn'))) {
            $where .= $wpdb->prepare(" AND o.status = %s", $status);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers o $where");

        $offers = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, e.post_id, e.property_value, e.property_type, e.district, e.area_sqm
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             $where
             ORDER BY o.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        $items = array();
        foreach ($offers as $offer) {
            $items[] = $this->format_offer($offer);
        }

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $total, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) ceil($total / $per_page))
        );
    }


    /**
     * GET /offers/received
     * Offers received on current user's listings
     */
    public function get_received_offers($request) {
        $per_page = min($request->get_param('per_page') ?: self::PER_PAGE_DEFAULT, self::PER_PAGE_MAX);
        $page = max(1, $request->get_param('page') ?: 1);
        $status = $request->get_param('status');
        $offset = ($page - 1) * $per_page;
        $user_id = get_current_user_id();

        global $wpdb;

        $where = $wpdb->prepare(
            "WHERE e.user_id = %d AND o.status != 'withdrawn'",
            $user_id
        );
        if ($status && in_array($status, array('pending', 'accepted', 'rejected', 'negotiating', 'countered'))) {
            $where .= $wpdb->prepare(" AND o.status = %s", $status);
        }

        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             $where"
        );

        $offers = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, e.post_id, e.property_value, e.property_type, e.district, e.area_sqm,
                    u.display_name as sender_name
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             LEFT JOIN {$wpdb->users} u ON o.from_user_id = u.ID
             $where
             ORDER BY o.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        $items = array();
        foreach ($offers as $offer) {
            $item = $this->format_offer($offer);
            $item['sender_name'] = $offer->sender_name ?: 'کاربر';
            $item['sender_avatar'] = get_avatar_url($offer->from_user_id, array('size' => 80));
            $items[] = $item;
        }

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $total, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) ceil($total / $per_page))
        );
    }

    /**
     * PUT /offers/{id}/status
     * Accept/reject an offer (listing owner or staff only)
     */
    public function update_offer_status($request) {
        $offer_id = $request->get_param('id');
        $new_status = $request->get_param('status');
        $notes = $request->get_param('notes') ?: '';
        $user_id = get_current_user_id();

        global $wpdb;

        $offer = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, e.user_id as exchange_owner_id
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             WHERE o.id = %d",
            $offer_id
        ));

        if (!$offer) {
            return $this->error_response('not_found', 'پیشنهاد یافت نشد.', 404);
        }

        // Only listing owner or staff can change status
        $is_owner = ((int) $offer->exchange_owner_id === $user_id);
        $is_staff = current_user_can('manage_options') || current_user_can('moaveze_manage_offers');

        if (!$is_owner && !$is_staff) {
            return $this->error_response('forbidden', 'شما مجاز به تغییر وضعیت این پیشنهاد نیستید.', 403);
        }

        $wpdb->update(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'status'       => $new_status,
                'admin_notes'  => $notes,
                'responded_at' => current_time('mysql'),
            ),
            array('id' => $offer_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        // Notify the offer sender
        $status_labels = array(
            'accepted'    => 'قبول شد',
            'rejected'    => 'رد شد',
            'negotiating' => 'در حال مذاکره',
        );

        if (class_exists('Moaveze_Notifications')) {
            $notifier = new Moaveze_Notifications();
            $notifier->send(
                $offer->from_user_id,
                'offer_' . $new_status,
                'وضعیت پیشنهاد: ' . ($status_labels[$new_status] ?? $new_status),
                sprintf('پیشنهاد شما %s.', $status_labels[$new_status] ?? $new_status),
                array('offer_id' => $offer_id, 'status' => $new_status)
            );
        }

        return $this->success_response(array(
            'offer_id' => $offer_id,
            'status'   => $new_status,
            'message'  => 'وضعیت پیشنهاد بروزرسانی شد.',
        ));
    }


    // ═══════════════════════════════════════════════════════════════
    // FAVORITES ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /favorites
     * Get current user's favorites list
     */
    public function get_favorites($request) {
        $user_id = get_current_user_id();
        $favorite_ids = Moaveze_Favorites::get_user_favorite_ids($user_id);

        if (empty($favorite_ids)) {
            return $this->success_response(array('items' => array(), 'total' => 0));
        }

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'post__in'       => $favorite_ids,
            'orderby'        => 'post__in',
            'posts_per_page' => 100,
        ));

        $items = array();
        while ($query->have_posts()) {
            $query->the_post();
            $items[] = $this->format_exchange(get_the_ID(), false);
        }
        wp_reset_postdata();

        return $this->success_response(array('items' => $items, 'total' => count($items)));
    }

    /**
     * POST /favorites/{listing_id}
     * Add a listing to favorites
     */
    public function add_favorite($request) {
        $listing_id = $request->get_param('listing_id');
        $user_id = get_current_user_id();

        // Verify listing exists
        $post = get_post($listing_id);
        if (!$post || $post->post_type !== 'moaveze_exchange') {
            return $this->error_response('not_found', 'آگهی یافت نشد.', 404);
        }

        $favorites = Moaveze_Favorites::get_user_favorite_ids($user_id);
        if (!in_array($listing_id, $favorites)) {
            $favorites[] = $listing_id;
            update_user_meta($user_id, '_moaveze_favorites', array_values(array_unique($favorites)));
        }

        return $this->success_response(array(
            'added'      => true,
            'listing_id' => $listing_id,
            'total'      => count($favorites),
        ), 201);
    }

    /**
     * DELETE /favorites/{listing_id}
     * Remove from favorites
     */
    public function remove_favorite($request) {
        $listing_id = $request->get_param('listing_id');
        $user_id = get_current_user_id();

        $favorites = Moaveze_Favorites::get_user_favorite_ids($user_id);
        $favorites = array_values(array_diff($favorites, array($listing_id)));
        update_user_meta($user_id, '_moaveze_favorites', $favorites);

        return $this->success_response(array(
            'removed'    => true,
            'listing_id' => $listing_id,
            'total'      => count($favorites),
        ));
    }


    // ═══════════════════════════════════════════════════════════════
    // NOTIFICATIONS ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /notifications
     * User's notifications (paginated)
     */
    public function get_notifications($request) {
        $per_page = min($request->get_param('per_page') ?: 15, 50);
        $page = max(1, $request->get_param('page') ?: 1);
        $offset = ($page - 1) * $per_page;
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_notifications';

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id
        ));

        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        $notification_types = array(
            'new_offer'        => array('icon' => '📩', 'label' => 'پیشنهاد جدید'),
            'offer_accepted'   => array('icon' => '✅', 'label' => 'قبول پیشنهاد'),
            'offer_rejected'   => array('icon' => '❌', 'label' => 'رد پیشنهاد'),
            'counter_offer'    => array('icon' => '🔄', 'label' => 'پیشنهاد متقابل'),
            'new_match'        => array('icon' => '🎯', 'label' => 'تطابق جدید'),
            'listing_approved' => array('icon' => '✓', 'label' => 'تأیید آگهی'),
            'listing_rejected' => array('icon' => '✗', 'label' => 'رد آگهی'),
            'wishlist_match'   => array('icon' => '⭐', 'label' => 'ملک رویایی شما'),
            'auction_update'   => array('icon' => '🔔', 'label' => 'بروزرسانی مزایده'),
            'system'           => array('icon' => 'ℹ️', 'label' => 'سیستمی'),
        );

        $items = array();
        foreach ($notifications as $n) {
            $type_info = $notification_types[$n->type] ?? array('icon' => 'ℹ️', 'label' => 'سیستمی');
            $items[] = array(
                'id'         => (int) $n->id,
                'type'       => $n->type,
                'icon'       => $type_info['icon'],
                'label'      => $type_info['label'],
                'title'      => $n->title,
                'message'    => $n->message,
                'data'       => json_decode($n->data, true),
                'is_read'    => (bool) $n->is_read,
                'created_at' => gmdate('c', strtotime($n->created_at)),
                'created_at_jalali' => Moaveze_Helpers::jalali_date($n->created_at, 'Y/m/d H:i'),
                'time_ago'   => Moaveze_Helpers::jalali_time_diff($n->created_at),
            );
        }

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $total, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) ceil($total / $per_page))
        );
    }

    /**
     * GET /notifications/unread-count
     */
    public function get_unread_count($request) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_notifications WHERE user_id = %d AND is_read = 0",
            get_current_user_id()
        ));

        return $this->success_response(array('unread_count' => (int) $count));
    }

    /**
     * PUT /notifications/{id}/read
     */
    public function mark_notification_read($request) {
        $id = $request->get_param('id');

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'moaveze_notifications',
            array('is_read' => 1),
            array('id' => $id, 'user_id' => get_current_user_id()),
            array('%d'),
            array('%d', '%d')
        );

        if ($result === false) {
            return $this->error_response('not_found', 'اعلان یافت نشد.', 404);
        }

        return $this->success_response(array('marked' => true, 'id' => $id));
    }

    /**
     * PUT /notifications/read-all
     */
    public function mark_all_notifications_read($request) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'moaveze_notifications',
            array('is_read' => 1),
            array('user_id' => get_current_user_id(), 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        );

        return $this->success_response(array('marked_all' => true));
    }


    // ═══════════════════════════════════════════════════════════════
    // VALUATION ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * POST /valuation/estimate
     * Public price estimation (basic)
     */
    public function valuation_estimate($request) {
        $area = $request->get_param('area');
        $type = $request->get_param('type');
        $district = $request->get_param('district');
        $rooms = $request->get_param('rooms');
        $year_built = $request->get_param('year_built');

        if (!$area || $area < 10) {
            return $this->error_response('invalid_area', 'متراژ معتبر وارد کنید (حداقل ۱۰ متر).', 400);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_exchanges';

        // Find comparable listings
        $where = "WHERE status = 'active' AND area_sqm > 0 AND property_value > 0";
        $params = array();

        if ($type) {
            $where .= " AND property_type = %s";
            $params[] = $type;
        }
        if ($district) {
            $where .= " AND district = %s";
            $params[] = $district;
        }

        $query = "SELECT property_value, area_sqm, property_type, district FROM $table $where ORDER BY created_at DESC LIMIT 50";

        $comparables = !empty($params)
            ? $wpdb->get_results($wpdb->prepare($query, ...$params))
            : $wpdb->get_results($query);

        // Fallback to broader search
        if (count($comparables) < 5) {
            $comparables = $wpdb->get_results(
                "SELECT property_value, area_sqm, property_type, district FROM $table
                 WHERE status = 'active' AND area_sqm > 0 AND property_value > 0
                 ORDER BY created_at DESC LIMIT 100"
            );
        }

        if (empty($comparables)) {
            return $this->error_response('insufficient_data', 'داده کافی برای تخمین قیمت موجود نیست.', 422);
        }

        // Calculate weighted average price per sqm
        $total_weight = 0;
        $weighted_price = 0;

        foreach ($comparables as $comp) {
            $price_per_sqm = $comp->property_value / $comp->area_sqm;
            $weight = 1.0;

            // Higher weight for same type
            if ($type && $comp->property_type === $type) $weight += 0.5;
            // Higher weight for same district
            if ($district && $comp->district === $district) $weight += 0.5;
            // Higher weight for similar area
            $area_diff = abs($comp->area_sqm - $area);
            if ($area_diff < 20) $weight += 0.3;

            $weighted_price += $price_per_sqm * $weight;
            $total_weight += $weight;
        }

        $avg_price_per_sqm = $total_weight > 0 ? $weighted_price / $total_weight : 0;
        $estimated_value = (int) round($avg_price_per_sqm * $area);

        // Range: ±15%
        $min_value = (int) round($estimated_value * 0.85);
        $max_value = (int) round($estimated_value * 1.15);

        return $this->success_response(array(
            'estimate' => array(
                'value'           => $estimated_value,
                'value_formatted' => Moaveze_Helpers::short_price($estimated_value, true, false),
                'min_value'       => $min_value,
                'min_formatted'   => Moaveze_Helpers::short_price($min_value, true, false),
                'max_value'       => $max_value,
                'max_formatted'   => Moaveze_Helpers::short_price($max_value, true, false),
                'price_per_sqm'   => (int) round($avg_price_per_sqm),
                'price_per_sqm_formatted' => Moaveze_Helpers::short_price(round($avg_price_per_sqm), true, false),
                'comparables_count' => count($comparables),
                'confidence'      => count($comparables) >= 10 ? 'high' : (count($comparables) >= 5 ? 'medium' : 'low'),
            ),
            'input' => array(
                'area'     => $area,
                'type'     => $type,
                'district' => $district,
                'rooms'    => $rooms,
            ),
        ));
    }

    /**
     * GET /valuation/{post_id}
     * Get expert valuation (staff only)
     */
    public function get_valuation($request) {
        $post_id = $request->get_param('post_id');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'moaveze_exchange') {
            return $this->error_response('not_found', 'آگهی یافت نشد.', 404);
        }

        $valuation = get_post_meta($post_id, '_moaveze_valuation', true);
        $ai_valuation = get_post_meta($post_id, '_moaveze_ai_valuation', true);

        return $this->success_response(array(
            'post_id'      => $post_id,
            'title'        => get_the_title($post_id),
            'valuation'    => $valuation ?: null,
            'ai_valuation' => $ai_valuation ?: null,
            'valued_at'    => get_post_meta($post_id, '_moaveze_valued_at', true) ?: null,
            'valued_by'    => get_post_meta($post_id, '_moaveze_valued_by', true) ?: null,
        ));
    }

    /**
     * GET /valuation/{post_id}/history
     * Valuation history (staff only)
     */
    public function get_valuation_history($request) {
        $post_id = $request->get_param('post_id');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'moaveze_exchange') {
            return $this->error_response('not_found', 'آگهی یافت نشد.', 404);
        }

        $history = get_post_meta($post_id, '_moaveze_valuation_history', true);

        return $this->success_response(array(
            'post_id' => $post_id,
            'history' => is_array($history) ? $history : array(),
        ));
    }


    // ═══════════════════════════════════════════════════════════════
    // CONFIG & STATS ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * GET /config
     * App configuration
     */
    public function get_config($request) {
        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false));
        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false));

        $type_items = array();
        if (!is_wp_error($types)) {
            foreach ($types as $t) {
                $type_items[] = array('id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'count' => $t->count);
            }
        }

        $district_items = array();
        if (!is_wp_error($districts)) {
            foreach ($districts as $d) {
                $district_items[] = array('id' => $d->term_id, 'name' => $d->name, 'slug' => $d->slug, 'count' => $d->count);
            }
        }

        $exchange_types = array(
            array('value' => 'exact', 'label' => 'معاوضه دقیق'),
            array('value' => 'flexible', 'label' => 'معاوضه انعطاف‌پذیر'),
            array('value' => 'with_cash', 'label' => 'معاوضه با مابه‌التفاوت'),
        );

        return $this->success_response(array(
            'property_types' => $type_items,
            'districts'      => $district_items,
            'exchange_types' => $exchange_types,
            'features'       => array(
                'offers_enabled'       => true,
                'favorites_enabled'    => true,
                'notifications_enabled' => true,
                'map_enabled'          => true,
                'valuation_enabled'    => true,
                'auction_enabled'      => (bool) get_option('moaveze_auction_enabled', false),
            ),
            'app' => array(
                'name'    => 'معاوضه پلاس',
                'version' => defined('MOAVEZE_VERSION') ? MOAVEZE_VERSION : '2.0.0',
                'currency' => 'تومان',
                'locale'   => 'fa_IR',
                'rtl'      => true,
            ),
        ));
    }

    /**
     * GET /stats
     * Platform statistics
     */
    public function get_stats($request) {
        global $wpdb;

        $total_listings = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'moaveze_exchange' AND post_status = 'publish'"
        );

        $total_exchanges_completed = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers WHERE status = 'accepted'"
        );

        $total_users = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");

        $total_offers = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers"
        );

        // Active listings by type
        $types_stats = array();
        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => true));
        if (!is_wp_error($types)) {
            foreach ($types as $t) {
                $types_stats[] = array('name' => $t->name, 'slug' => $t->slug, 'count' => $t->count);
            }
        }

        // District stats
        $district_stats = array();
        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 10));
        if (!is_wp_error($districts)) {
            foreach ($districts as $d) {
                $district_stats[] = array('name' => $d->name, 'slug' => $d->slug, 'count' => $d->count);
            }
        }

        return $this->success_response(array(
            'total_listings'   => $total_listings,
            'total_completed'  => $total_exchanges_completed,
            'total_users'      => $total_users,
            'total_offers'     => $total_offers,
            'by_type'          => $types_stats,
            'top_districts'    => $district_stats,
        ));
    }


    // ═══════════════════════════════════════════════════════════════
    // CONTACT / COMMUNICATION ENDPOINTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * POST /contact/{listing_id}
     * Request contact info for a listing
     */
    public function request_contact($request) {
        $listing_id = $request->get_param('listing_id');
        $user_id = get_current_user_id();

        $post = get_post($listing_id);
        if (!$post || $post->post_type !== 'moaveze_exchange' || $post->post_status !== 'publish') {
            return $this->error_response('not_found', 'آگهی یافت نشد.', 404);
        }

        global $wpdb;

        // Check if already requested
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}moaveze_contact_requests WHERE user_id = %d AND post_id = %d",
            $user_id, $listing_id
        ));

        if ($existing) {
            // Already have access - return contact info
            $contact_name = get_post_meta($listing_id, '_moaveze_contact_name', true);
            $contact_phone = get_post_meta($listing_id, '_moaveze_contact_phone', true);

            return $this->success_response(array(
                'already_requested' => true,
                'contact' => array(
                    'name'  => $contact_name,
                    'phone' => $contact_phone,
                ),
            ));
        }

        // Check if payment is required
        $requires_payment = get_option('moaveze_contact_requires_payment', 'no') === 'yes';

        if ($requires_payment) {
            // Check user's credit/balance
            $balance = (int) get_user_meta($user_id, '_moaveze_credit', true);
            $contact_cost = (int) get_option('moaveze_contact_cost', 5000);

            if ($balance < $contact_cost) {
                return $this->error_response('insufficient_credit', 'اعتبار کافی ندارید. هزینه درخواست: ' . Moaveze_Helpers::short_price($contact_cost, true, false), 402);
            }

            // Deduct credit
            update_user_meta($user_id, '_moaveze_credit', $balance - $contact_cost);
        }

        // Record the request
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_contact_requests',
            array(
                'user_id'    => $user_id,
                'post_id'    => $listing_id,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s')
        );

        $contact_name = get_post_meta($listing_id, '_moaveze_contact_name', true);
        $contact_phone = get_post_meta($listing_id, '_moaveze_contact_phone', true);

        return $this->success_response(array(
            'contact' => array(
                'name'  => $contact_name,
                'phone' => $contact_phone,
            ),
        ), 201);
    }

    /**
     * GET /contact/requests
     * User's contact request history
     */
    public function get_contact_requests($request) {
        $per_page = min($request->get_param('per_page') ?: self::PER_PAGE_DEFAULT, self::PER_PAGE_MAX);
        $page = max(1, $request->get_param('page') ?: 1);
        $offset = ($page - 1) * $per_page;
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_contact_requests';

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id
        ));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));

        $items = array();
        foreach ($results as $row) {
            $post = get_post($row->post_id);
            if (!$post) continue;

            $items[] = array(
                'id'           => (int) $row->id,
                'listing_id'   => (int) $row->post_id,
                'listing_title' => get_the_title($row->post_id),
                'listing_image' => get_the_post_thumbnail_url($row->post_id, 'thumbnail') ?: '',
                'contact_name' => get_post_meta($row->post_id, '_moaveze_contact_name', true),
                'contact_phone' => get_post_meta($row->post_id, '_moaveze_contact_phone', true),
                'requested_at' => gmdate('c', strtotime($row->created_at)),
                'requested_at_jalali' => Moaveze_Helpers::jalali_date($row->created_at, 'Y/m/d H:i'),
            );
        }

        return $this->success_response(
            array('items' => $items),
            200,
            array('total' => (int) $total, 'page' => $page, 'per_page' => $per_page, 'pages' => (int) ceil($total / $per_page))
        );
    }


    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Format exchange data for API response
     *
     * @param int  $post_id  Post ID
     * @param bool $detailed Include all details (single view) vs summary (list view)
     * @return array
     */
    private function format_exchange($post_id, $detailed = false) {
        $value = absint(get_post_meta($post_id, '_moaveze_property_value', true));
        $area = absint(get_post_meta($post_id, '_moaveze_area_sqm', true));
        $post = get_post($post_id);

        // Taxonomy terms
        $type_terms = get_the_terms($post_id, 'moaveze_property_type');
        $district_terms = get_the_terms($post_id, 'moaveze_district');
        $feature_terms = get_the_terms($post_id, 'moaveze_feature');

        $data = array(
            'id'             => $post_id,
            'title'          => get_the_title($post_id),
            'slug'           => $post->post_name ?? '',
            'url'            => get_permalink($post_id),
            'value'          => $value,
            'value_formatted' => Moaveze_Helpers::short_price($value, true, false),
            'area'           => $area,
            'exchange_type'  => get_post_meta($post_id, '_moaveze_exchange_type', true) ?: 'flexible',
            'property_type'  => $type_terms ? array(
                'id'   => $type_terms[0]->term_id,
                'name' => $type_terms[0]->name,
                'slug' => $type_terms[0]->slug,
            ) : null,
            'district'       => $district_terms ? array(
                'id'   => $district_terms[0]->term_id,
                'name' => $district_terms[0]->name,
                'slug' => $district_terms[0]->slug,
            ) : null,
            'thumbnail'      => $this->get_image_sizes(get_post_thumbnail_id($post_id)),
            'verified'       => (bool) get_post_meta($post_id, '_moaveze_verified', true),
            'featured'       => (bool) get_post_meta($post_id, '_moaveze_featured', true),
            'views'          => (int) get_post_meta($post_id, '_moaveze_views', true),
            'created_at'     => gmdate('c', strtotime($post->post_date_gmt)),
            'created_at_jalali' => Moaveze_Helpers::jalali_date($post->post_date, 'Y/m/d'),
            'time_ago'       => Moaveze_Helpers::jalali_time_diff($post->post_date),
        );

        if ($detailed) {
            $data['description'] = apply_filters('the_content', $post->post_content);
            $data['description_raw'] = $post->post_content;
            $data['rooms']      = get_post_meta($post_id, '_moaveze_rooms', true) ?: '';
            $data['floor']      = get_post_meta($post_id, '_moaveze_floor', true) ?: '';
            $data['year_built'] = get_post_meta($post_id, '_moaveze_year_built', true) ?: '';
            $data['latitude']   = floatval(get_post_meta($post_id, '_moaveze_latitude', true));
            $data['longitude']  = floatval(get_post_meta($post_id, '_moaveze_longitude', true));
            $data['cash_difference'] = absint(get_post_meta($post_id, '_moaveze_cash_difference', true));
            $data['cash_difference_formatted'] = Moaveze_Helpers::short_price(
                absint(get_post_meta($post_id, '_moaveze_cash_difference', true)), true, false
            );
            $data['cash_direction'] = get_post_meta($post_id, '_moaveze_cash_direction', true) ?: '';

            // Gallery images with multiple sizes
            $gallery_ids = get_post_meta($post_id, '_moaveze_gallery', true);
            $data['gallery'] = array();
            if (is_array($gallery_ids)) {
                foreach ($gallery_ids as $img_id) {
                    $img_data = $this->get_image_sizes($img_id);
                    if ($img_data) {
                        $data['gallery'][] = $img_data;
                    }
                }
            }

            // Features
            $data['features'] = array();
            if ($feature_terms && !is_wp_error($feature_terms)) {
                foreach ($feature_terms as $ft) {
                    $data['features'][] = array(
                        'id'   => $ft->term_id,
                        'name' => $ft->name,
                        'slug' => $ft->slug,
                    );
                }
            }

            // Conditions/preferences
            $data['conditions'] = get_post_meta($post_id, '_moaveze_conditions', true) ?: '';
            $data['preferred_types'] = get_post_meta($post_id, '_moaveze_preferred_types', true) ?: '';
            $data['preferred_districts'] = get_post_meta($post_id, '_moaveze_preferred_districts', true) ?: '';

            // Video
            $data['video_url'] = get_post_meta($post_id, '_moaveze_video_url', true) ?: '';

            // Expert valuation (public summary only)
            $valuation = get_post_meta($post_id, '_moaveze_valuation', true);
            $data['has_valuation'] = !empty($valuation);

            // Price per sqm
            if ($area > 0 && $value > 0) {
                $price_per_sqm = (int) round($value / $area);
                $data['price_per_sqm'] = $price_per_sqm;
                $data['price_per_sqm_formatted'] = Moaveze_Helpers::short_price($price_per_sqm, true, false);
            }

            // Author info (basic)
            $data['author'] = array(
                'id'           => (int) $post->post_author,
                'display_name' => get_the_author_meta('display_name', $post->post_author),
                'avatar'       => get_avatar_url($post->post_author, array('size' => 80)),
            );
        }

        return $data;
    }


    /**
     * Get image URLs in multiple sizes
     */
    private function get_image_sizes($attachment_id) {
        if (!$attachment_id) return null;

        $sizes = array('thumbnail', 'medium', 'large', 'full');
        $result = array('id' => (int) $attachment_id);

        foreach ($sizes as $size) {
            $img = wp_get_attachment_image_src($attachment_id, $size);
            if ($img) {
                $result[$size] = array(
                    'url'    => $img[0],
                    'width'  => $img[1],
                    'height' => $img[2],
                );
            }
        }

        $result['alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: '';

        return $result;
    }

    /**
     * Format offer data for API response
     */
    private function format_offer($offer) {
        $status_labels = array(
            'pending'     => array('label' => 'در انتظار بررسی', 'color' => 'warning'),
            'accepted'    => array('label' => 'قبول شده', 'color' => 'success'),
            'rejected'    => array('label' => 'رد شده', 'color' => 'danger'),
            'negotiating' => array('label' => 'در حال مذاکره', 'color' => 'info'),
            'countered'   => array('label' => 'پیشنهاد متقابل', 'color' => 'purple'),
            'withdrawn'   => array('label' => 'لغو شده', 'color' => 'muted'),
        );

        $status_info = $status_labels[$offer->status] ?? array('label' => $offer->status, 'color' => 'muted');
        $cash_offered = absint($offer->cash_offered);

        return array(
            'id'              => (int) $offer->id,
            'exchange_id'     => (int) $offer->exchange_id,
            'listing_title'   => get_the_title($offer->post_id),
            'listing_image'   => get_the_post_thumbnail_url($offer->post_id, 'thumbnail') ?: '',
            'listing_value'   => (int) $offer->property_value,
            'listing_value_formatted' => Moaveze_Helpers::short_price($offer->property_value, true, false),
            'property_type'   => $offer->property_type,
            'district'        => $offer->district,
            'area'            => isset($offer->area_sqm) ? (int) $offer->area_sqm : 0,
            'offer_type'      => $offer->offer_type,
            'cash_offered'    => $cash_offered,
            'cash_offered_formatted' => $cash_offered ? Moaveze_Helpers::short_price($cash_offered, true, false) : '',
            'assets_offered'  => $offer->assets_offered,
            'message'         => $offer->message,
            'status'          => $offer->status,
            'status_label'    => $status_info['label'],
            'status_color'    => $status_info['color'],
            'admin_notes'     => $offer->admin_notes ?? '',
            'created_at'      => gmdate('c', strtotime($offer->created_at)),
            'created_at_jalali' => Moaveze_Helpers::jalali_date($offer->created_at, 'Y/m/d H:i'),
            'time_ago'        => Moaveze_Helpers::jalali_time_diff($offer->created_at),
            'responded_at'    => $offer->responded_at ? gmdate('c', strtotime($offer->responded_at)) : null,
        );
    }


    // ═══════════════════════════════════════════════════════════════
    // RESPONSE HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Standard success response
     */
    private function success_response($data, $status = 200, $meta = null) {
        $response_data = array(
            'success' => true,
            'data'    => $data,
        );

        if ($meta) {
            $response_data['meta'] = $meta;
        }

        $response = new WP_REST_Response($response_data, $status);

        // Rate limit headers
        $response->header('X-RateLimit-Limit', '100');
        $response->header('X-RateLimit-Remaining', '99');

        if ($meta) {
            if (isset($meta['total'])) {
                $response->header('X-WP-Total', $meta['total']);
            }
            if (isset($meta['pages'])) {
                $response->header('X-WP-TotalPages', $meta['pages']);
            }
        }

        return $response;
    }

    /**
     * Standard error response
     */
    private function error_response($code, $message, $status = 400) {
        $response = new WP_REST_Response(array(
            'success' => false,
            'error'   => array(
                'code'    => $code,
                'message' => $message,
            ),
        ), $status);

        return $response;
    }


    // ═══════════════════════════════════════════════════════════════
    // ARGUMENT DEFINITIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Exchange list endpoint arguments
     */
    private function get_exchange_list_args() {
        return array(
            'page' => array(
                'default'           => 1,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function($value) { return $value > 0; },
            ),
            'per_page' => array(
                'default'           => self::PER_PAGE_DEFAULT,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function($value) { return $value > 0 && $value <= self::PER_PAGE_MAX; },
            ),
            'type' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'district' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'exchange_type' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($value) {
                    return empty($value) || in_array($value, array('exact', 'flexible', 'with_cash'));
                },
            ),
            'min_value' => array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'max_value' => array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'search' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'orderby' => array(
                'type'              => 'string',
                'default'           => 'date',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($value) {
                    return in_array($value, array('date', 'value_asc', 'value_desc', 'area_asc', 'area_desc'));
                },
            ),
        );
    }

    /**
     * Advanced search endpoint arguments
     */
    private function get_search_args() {
        $args = $this->get_exchange_list_args();

        $args['min_area'] = array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
        );
        $args['max_area'] = array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
        );
        $args['rooms'] = array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
        );
        $args['features'] = array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'description'       => 'Comma-separated feature slugs',
        );
        $args['lat'] = array(
            'type'              => 'number',
            'sanitize_callback' => 'floatval',
        );
        $args['lng'] = array(
            'type'              => 'number',
            'sanitize_callback' => 'floatval',
        );
        $args['radius'] = array(
            'type'              => 'number',
            'sanitize_callback' => 'floatval',
            'description'       => 'Search radius in kilometers',
        );

        return $args;
    }
}

new Moaveze_REST_API();
