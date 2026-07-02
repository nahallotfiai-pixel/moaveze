<?php
/**
 * Notifications Module
 * Email, SMS, and Push notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Notifications {

    public function __construct() {
        add_action('moaveze_new_offer', array($this, 'notify_new_offer'), 10, 2);
        add_action('moaveze_new_match', array($this, 'notify_new_match'), 10, 2);
        add_action('transition_post_status', array($this, 'notify_status_change'), 10, 3);
        add_action('wp_ajax_moaveze_get_notifications', array($this, 'get_user_notifications'));
        add_action('wp_ajax_moaveze_mark_read', array($this, 'mark_as_read'));
    }

    /**
     * Send notification
     */
    public function send($user_id, $type, $title, $message, $data = array()) {
        global $wpdb;

        // Store in DB
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_notifications',
            array(
                'user_id' => $user_id,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'data'    => wp_json_encode($data),
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );

        // Email notification
        if (get_option('moaveze_email_notifications') === 'yes') {
            $user = get_userdata($user_id);
            if ($user && $user->user_email) {
                $this->send_email($user->user_email, $title, $message);
            }
        }

        // SMS notification
        if (get_option('moaveze_sms_notifications') === 'yes') {
            $phone = get_user_meta($user_id, 'phone', true);
            if ($phone) {
                $this->send_sms($phone, $message);
            }
        }
    }

    /**
     * Notify on new offer
     */
    public function notify_new_offer($offer_id, $exchange_id) {
        global $wpdb;
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d",
            $exchange_id
        ));

        if (!$exchange) return;

        // Notify admin
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $this->send(
                $admin->ID,
                'new_offer',
                'پیشنهاد معاوضه جدید',
                sprintf('پیشنهاد جدیدی برای آگهی "%s" دریافت شد', get_the_title($exchange->post_id)),
                array('offer_id' => $offer_id, 'exchange_id' => $exchange_id)
            );
        }

        // Notify consultants
        $consultants = get_users(array('role' => 'moaveze_consultant'));
        foreach ($consultants as $consultant) {
            $this->send(
                $consultant->ID,
                'new_offer',
                'پیشنهاد معاوضه جدید',
                sprintf('پیشنهاد جدیدی برای آگهی "%s" ثبت شد', get_the_title($exchange->post_id)),
                array('offer_id' => $offer_id)
            );
        }
    }

    /**
     * Notify on new match
     */
    public function notify_new_match($match_id, $data) {
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $this->send(
                $admin->ID,
                'new_match',
                'تطابق جدید!',
                'تطابق جدیدی بین دو آگهی معاوضه یافت شد',
                array('match_id' => $match_id)
            );
        }
    }

    /**
     * Notify on status change
     */
    public function notify_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'moaveze_exchange') return;
        if ($new_status === $old_status) return;

        if ($new_status === 'publish' && $old_status === 'pending') {
            $this->send(
                $post->post_author,
                'listing_approved',
                'آگهی شما تأیید شد',
                sprintf('آگهی "%s" تأیید و منتشر شد', $post->post_title),
                array('post_id' => $post->ID)
            );
        }
    }

    /**
     * Get user notifications via AJAX
     */
    public function get_user_notifications() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('وارد شوید');
        }

        global $wpdb;
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_notifications 
             WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
            get_current_user_id()
        ));

        $unread = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_notifications 
             WHERE user_id = %d AND is_read = 0",
            get_current_user_id()
        ));

        wp_send_json_success(array(
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ));
    }

    /**
     * Mark notification as read
     */
    public function mark_as_read() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        $id = absint($_POST['notification_id'] ?? 0);

        if ($id) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'moaveze_notifications',
                array('is_read' => 1),
                array('id' => $id, 'user_id' => get_current_user_id())
            );
        }

        wp_send_json_success();
    }

    /**
     * Send email
     */
    private function send_email($to, $subject, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $html_message = $this->email_template($subject, $message);
        wp_mail($to, $subject . ' | تبریز هوم', $html_message, $headers);
    }

    /**
     * Email template
     */
    private function email_template($title, $content) {
        return '<!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8"></head>
        <body style="font-family:Tahoma,Arial;direction:rtl;background:#f5f5f5;padding:20px;">
        <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
            <div style="text-align:center;margin-bottom:20px;">
                <h2 style="color:#6366f1;">معاوضه پلاس | تبریز هوم</h2>
            </div>
            <h3 style="color:#1e293b;">' . esc_html($title) . '</h3>
            <p style="color:#475569;line-height:1.8;">' . esc_html($content) . '</p>
            <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0;">
            <p style="color:#94a3b8;font-size:12px;text-align:center;">
                این ایمیل از طرف سیستم معاوضه تبریز هوم ارسال شده است.
            </p>
        </div>
        </body></html>';
    }

    /**
     * Send SMS via configured API
     */
    private function send_sms($phone, $message) {
        $api_key = get_option('moaveze_sms_api_key');
        if (!$api_key) return;

        // Kavenegar-style API (can be customized)
        $url = "https://api.kavenegar.com/v1/{$api_key}/sms/send.json";
        wp_remote_post($url, array(
            'body' => array(
                'receptor' => $phone,
                'message'  => $message,
            ),
            'timeout' => 10,
        ));
    }
}

new Moaveze_Notifications();
