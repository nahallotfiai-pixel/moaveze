<?php
/**
 * Monetization Module
 * Handles subscriptions, per-contact payment, VIP, and boosting
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Monetization {

    public function __construct() {
        add_action('wp_ajax_moaveze_request_contact', array($this, 'request_contact'));
        add_action('wp_ajax_moaveze_boost_listing', array($this, 'boost_listing'));
        add_action('wp_ajax_moaveze_subscribe', array($this, 'subscribe'));
    }

    /**
     * Check if user can view contacts
     */
    public static function can_view_contacts($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return false;

        // Admin always can
        if (user_can($user_id, 'manage_options')) return true;

        // Consultant can
        if (user_can($user_id, 'moaveze_view_contacts')) return true;

        $mode = get_option('moaveze_monetization_mode', 'consultant');
        $visible_to = get_option('moaveze_contact_visible_to', 'admin');

        switch ($visible_to) {
            case 'admin':
                return false;
            case 'consultant':
                return user_can($user_id, 'moaveze_view_contacts');
            case 'subscriber':
                return self::has_active_subscription($user_id);
        }

        return false;
    }

    /**
     * Check active subscription
     */
    public static function has_active_subscription($user_id) {
        global $wpdb;
        $sub = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_subscriptions 
             WHERE user_id = %d AND status = 'active' AND expires_at > NOW()
             ORDER BY expires_at DESC LIMIT 1",
            $user_id
        ));
        return !empty($sub);
    }

    /**
     * Request to view contact info (per-contact payment)
     */
    public function request_contact() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'برای مشاهده اطلاعات تماس باید وارد شوید'));
        }

        $exchange_id = absint($_POST['exchange_id']);
        $user_id = get_current_user_id();
        $mode = get_option('moaveze_monetization_mode', 'consultant');

        // Check per-listing monetization override
        $listing_mode = get_post_meta($exchange_id, '_moaveze_monetization_mode', true);
        if ($listing_mode) $mode = $listing_mode;

        switch ($mode) {
            case 'free':
                $this->grant_contact_view($exchange_id, $user_id, 0);
                break;

            case 'consultant':
                wp_send_json_success(array(
                    'type'    => 'consultant',
                    'message' => 'برای دریافت اطلاعات تماس با مشاوران ما تماس بگیرید',
                ));
                break;

            case 'subscription':
                if (self::has_active_subscription($user_id)) {
                    $this->grant_contact_view($exchange_id, $user_id, 0);
                } else {
                    wp_send_json_success(array(
                        'type'    => 'subscription_required',
                        'message' => 'برای مشاهده اطلاعات تماس نیاز به اشتراک دارید',
                        'price'   => get_option('moaveze_subscription_price', 0),
                    ));
                }
                break;

            case 'per_contact':
                $price = get_option('moaveze_per_contact_price', 0);
                // Check if already paid
                global $wpdb;
                $already_paid = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}moaveze_contact_views 
                     WHERE exchange_id = %d AND user_id = %d",
                    $exchange_id, $user_id
                ));
                if ($already_paid) {
                    $this->grant_contact_view($exchange_id, $user_id, 0);
                } else {
                    wp_send_json_success(array(
                        'type'    => 'payment_required',
                        'message' => 'برای مشاهده اطلاعات تماس پرداخت کنید',
                        'price'   => $price,
                    ));
                }
                break;

            case 'hybrid':
                // Custom per listing - handled above
                wp_send_json_success(array(
                    'type'    => 'contact_admin',
                    'message' => 'برای دریافت اطلاعات تماس با پشتیبانی تماس بگیرید',
                ));
                break;
        }
    }

    /**
     * Grant contact view access
     */
    private function grant_contact_view($exchange_id, $user_id, $amount) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'moaveze_contact_views',
            array(
                'exchange_id'    => $exchange_id,
                'user_id'        => $user_id,
                'view_type'      => 'contact',
                'payment_amount' => $amount,
            ),
            array('%d', '%d', '%s', '%d')
        );

        // Get contact info
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT contact_name, contact_phone, contact_email FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d",
            $exchange_id
        ));

        wp_send_json_success(array(
            'type'    => 'contact_revealed',
            'contact' => array(
                'name'  => $exchange->contact_name,
                'phone' => $exchange->contact_phone,
                'email' => $exchange->contact_email,
            ),
        ));
    }

    /**
     * Boost a listing
     */
    public function boost_listing() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (get_option('moaveze_boost_enabled') !== 'yes') {
            wp_send_json_error(array('message' => 'بوست آگهی غیرفعال است'));
        }

        $post_id = absint($_POST['post_id']);
        $days = absint($_POST['days'] ?? 7);

        $boost_until = date('Y-m-d H:i:s', strtotime("+$days days"));
        update_post_meta($post_id, '_moaveze_boost_until', $boost_until);
        update_post_meta($post_id, '_moaveze_featured', '1');

        wp_send_json_success(array('message' => "آگهی شما تا $days روز در صدر لیست قرار خواهد گرفت"));
    }

    /**
     * Create subscription
     */
    public function subscribe() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'لطفاً وارد شوید'));
        }

        $plan = sanitize_text_field($_POST['plan'] ?? 'monthly');
        $durations = array('monthly' => 30, 'quarterly' => 90, 'yearly' => 365);
        $days = $durations[$plan] ?? 30;

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_subscriptions',
            array(
                'user_id'        => get_current_user_id(),
                'plan_type'      => $plan,
                'starts_at'      => current_time('mysql'),
                'expires_at'     => date('Y-m-d H:i:s', strtotime("+$days days")),
                'status'         => 'active',
                'payment_amount' => get_option('moaveze_subscription_price', 0),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );

        wp_send_json_success(array('message' => 'اشتراک شما فعال شد'));
    }
}

new Moaveze_Monetization();
