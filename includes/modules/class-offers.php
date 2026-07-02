<?php
/**
 * Offer System
 * Handles exchange proposals between users
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Offers {

    public function __construct() {
        add_action('wp_ajax_moaveze_send_offer', array($this, 'send_offer'));
        add_action('wp_ajax_moaveze_respond_offer', array($this, 'respond_offer'));
        add_action('wp_ajax_moaveze_get_my_offers', array($this, 'get_my_offers'));
    }

    /**
     * Send an offer
     */
    public function send_offer() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'برای ارسال پیشنهاد باید وارد شوید'));
        }

        $exchange_id = absint($_POST['exchange_id']);
        $offer_type = sanitize_text_field($_POST['offer_type'] ?? 'direct');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $cash_offered = absint(str_replace(array(',', ' '), '', $_POST['cash_offered'] ?? ''));
        $assets_offered = sanitize_textarea_field($_POST['assets_offered'] ?? '');
        $from_exchange_id = absint($_POST['from_exchange_id'] ?? 0);

        global $wpdb;

        // Check if exchange exists
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d AND status = 'active'",
            $exchange_id
        ));

        if (!$exchange) {
            wp_send_json_error(array('message' => 'آگهی مورد نظر یافت نشد'));
        }

        // Prevent self-offer
        if ($exchange->user_id === get_current_user_id()) {
            wp_send_json_error(array('message' => 'امکان ارسال پیشنهاد برای آگهی خودتان نیست'));
        }

        // Insert offer
        $result = $wpdb->insert(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'exchange_id'      => $exchange_id,
                'from_user_id'     => get_current_user_id(),
                'from_exchange_id' => $from_exchange_id ?: null,
                'offer_type'       => $offer_type,
                'offer_details'    => wp_json_encode(array(
                    'message' => $message,
                    'assets'  => $assets_offered,
                )),
                'cash_offered'     => $cash_offered,
                'assets_offered'   => $assets_offered,
                'message'          => $message,
                'status'           => 'pending',
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        if ($result) {
            // Notify admin
            do_action('moaveze_new_offer', $wpdb->insert_id, $exchange_id);

            wp_send_json_success(array('message' => 'پیشنهاد شما با موفقیت ارسال شد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در ارسال پیشنهاد'));
        }
    }

    /**
     * Respond to an offer (admin/consultant)
     */
    public function respond_offer() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('moaveze_manage_offers')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $offer_id = absint($_POST['offer_id']);
        $response = sanitize_text_field($_POST['response']); // accepted, rejected
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'status'       => $response,
                'admin_notes'  => $notes,
                'responded_at' => current_time('mysql'),
            ),
            array('id' => $offer_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        wp_send_json_success(array('message' => 'پاسخ ثبت شد'));
    }

    /**
     * Get user's offers
     */
    public function get_my_offers() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('وارد شوید');
        }

        global $wpdb;
        $offers = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, e.post_id, e.property_value, e.property_type, e.district 
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             WHERE o.from_user_id = %d
             ORDER BY o.created_at DESC",
            get_current_user_id()
        ));

        $formatted = array();
        foreach ($offers as $offer) {
            $formatted[] = array(
                'id'          => $offer->id,
                'title'       => get_the_title($offer->post_id),
                'value'       => $offer->property_value,
                'type'        => $offer->offer_type,
                'cash'        => $offer->cash_offered,
                'status'      => $offer->status,
                'date'        => $offer->created_at,
                'url'         => get_permalink($offer->post_id),
            );
        }

        wp_send_json_success(array('offers' => $formatted));
    }
}

new Moaveze_Offers();
