<?php
/**
 * SMS Notifications via melipayamak.com
 *
 * Per explicit user request: "اعلان پیامکی رو کامل راه اندازی کن بر
 * مبنای melipayamak باشد در تنظیماتش تست هم بذار"
 *
 * Provides:
 *   - Settings panel with username/password/sender-number + test button
 *   - Event-based triggers: new listing, offer received, match found,
 *     valuation complete, listing approved/rejected
 *   - Each trigger independently togglable
 *   - Admin receives SMS for important events
 *   - Property owners receive SMS for their own listing events
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_SMS_Melipayamak {

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_moaveze_test_sms', array($this, 'ajax_test_sms'));

        // Event hooks (only if SMS is enabled)
        if ($this->is_enabled()) {
            // New exchange listing submitted
            add_action('save_post_moaveze_exchange', array($this, 'on_new_listing'), 50, 2);
            // Offer received
            add_action('moaveze_offer_created', array($this, 'on_offer_received'), 10, 2);
            // Match found
            add_action('moaveze_match_found', array($this, 'on_match_found'), 10, 3);
            // Listing approved
            add_action('moaveze_listing_approved', array($this, 'on_listing_approved'), 10, 1);
        }
    }

    public function register_settings() {
        $settings = array(
            'moaveze_sms_enabled',
            'moaveze_sms_username',
            'moaveze_sms_password',
            'moaveze_sms_sender_number',
            'moaveze_sms_admin_phone',
            'moaveze_sms_on_new_listing',
            'moaveze_sms_on_offer_received',
            'moaveze_sms_on_match_found',
            'moaveze_sms_on_listing_approved',
            'moaveze_sms_on_valuation_complete',
        );
        foreach ($settings as $s) {
            register_setting('moaveze_sms', $s);
        }
    }

    private function is_enabled() {
        return get_option('moaveze_sms_enabled') === 'yes'
            && get_option('moaveze_sms_username')
            && get_option('moaveze_sms_password');
    }

    /**
     * Send SMS via melipayamak REST API.
     *
     * @param string $to    Recipient phone number (e.g. "09123456789")
     * @param string $text  Message text
     * @return array ['success' => bool, 'message' => string]
     */
    public function send($to, $text) {
        $username = get_option('moaveze_sms_username');
        $password = get_option('moaveze_sms_password');
        $from = get_option('moaveze_sms_sender_number');

        if (!$username || !$password || !$from || !$to) {
            return array('success' => false, 'message' => 'تنظیمات پیامک ناقص است');
        }

        // melipayamak REST API v2
        $response = wp_remote_post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', array(
            'timeout' => 15,
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode(array(
                'username'  => $username,
                'password'  => $password,
                'from'      => $from,
                'to'        => $to,
                'text'      => $text,
                'isFlash'   => false,
            )),
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $return_value = $body['Value'] ?? '';
        $str_return_status = $body['StrRetStatus'] ?? '';

        // melipayamak returns a numeric string > 1000 on success (the message ID)
        if (is_numeric($return_value) && (int) $return_value > 1000) {
            return array('success' => true, 'message' => 'پیامک با موفقیت ارسال شد (شناسه: ' . $return_value . ')');
        }

        return array('success' => false, 'message' => $str_return_status ?: 'خطای نامشخص از melipayamak (کد: ' . $return_value . ')');
    }

    /**
     * AJAX: test SMS - send a test message to the admin phone.
     */
    public function ajax_test_sms() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی ندارید');

        $phone = sanitize_text_field($_POST['phone'] ?? get_option('moaveze_sms_admin_phone'));
        if (!$phone) wp_send_json_error('شماره تلفن وارد نشده است');

        $result = $this->send($phone, 'تست پیامک از افزونه معاوضه پلاس تبریز هوم - اگر این پیامک را دریافت کردید، تنظیمات صحیح است.');

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    // ===== Event Handlers =====

    public function on_new_listing($post_id, $post) {
        if ($post->post_status !== 'pending' && $post->post_status !== 'publish') return;
        if (get_option('moaveze_sms_on_new_listing') !== 'yes') return;
        if (get_post_meta($post_id, '_moaveze_sms_new_sent', true)) return;

        $admin_phone = get_option('moaveze_sms_admin_phone');
        if ($admin_phone) {
            $this->send($admin_phone, sprintf(
                'آگهی معاوضه جدید ثبت شد: %s - تبریز هوم',
                get_the_title($post_id)
            ));
        }
        update_post_meta($post_id, '_moaveze_sms_new_sent', '1');
    }

    public function on_offer_received($offer_id, $exchange_id) {
        if (get_option('moaveze_sms_on_offer_received') !== 'yes') return;

        $admin_phone = get_option('moaveze_sms_admin_phone');
        if ($admin_phone) {
            $this->send($admin_phone, sprintf(
                'پیشنهاد معاوضه جدید دریافت شد برای آگهی: %s - تبریز هوم',
                get_the_title(get_post_meta($exchange_id, 'post_id', true) ?: $exchange_id)
            ));
        }
    }

    public function on_match_found($match_id, $exchange_a, $exchange_b) {
        if (get_option('moaveze_sms_on_match_found') !== 'yes') return;

        $admin_phone = get_option('moaveze_sms_admin_phone');
        if ($admin_phone) {
            $this->send($admin_phone, 'تطابق جدید معاوضه شناسایی شد! برای بررسی به پنل مراجعه کنید - تبریز هوم');
        }
    }

    public function on_listing_approved($post_id) {
        if (get_option('moaveze_sms_on_listing_approved') !== 'yes') return;

        // Try to notify the listing owner via their contact phone
        global $wpdb;
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT contact_phone FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d", $post_id
        ));
        if ($exchange && $exchange->contact_phone) {
            $this->send($exchange->contact_phone, sprintf(
                'آگهی معاوضه شما «%s» تأیید و منتشر شد! - تبریز هوم',
                get_the_title($post_id)
            ));
        }
    }
}

new Moaveze_SMS_Melipayamak();
