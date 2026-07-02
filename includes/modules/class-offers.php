<?php
/**
 * Offer System - Phase 2
 * Enhanced with modal UI support, status tracking, counter-offers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Offers {

    public function __construct() {
        add_action('wp_ajax_moaveze_send_offer', array($this, 'send_offer'));
        add_action('wp_ajax_moaveze_respond_offer', array($this, 'respond_offer'));
        add_action('wp_ajax_moaveze_get_my_offers', array($this, 'get_my_offers'));
        add_action('wp_ajax_moaveze_get_received_offers', array($this, 'get_received_offers'));
        add_action('wp_ajax_moaveze_get_offer_details', array($this, 'get_offer_details'));
        add_action('wp_ajax_moaveze_counter_offer', array($this, 'counter_offer'));
        add_action('wp_ajax_moaveze_withdraw_offer', array($this, 'withdraw_offer'));
        add_shortcode('moaveze_my_offers', array($this, 'render_my_offers_page'));
    }

    /**
     * Send an offer via AJAX (enhanced)
     */
    public function send_offer() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'برای ارسال پیشنهاد باید وارد شوید', 'code' => 'login_required'));
        }

        $exchange_id = absint($_POST['exchange_id']);
        $offer_type = sanitize_text_field($_POST['offer_type'] ?? 'direct');

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $cash_offered = absint(str_replace(array(',', ' ', '٬'), '', $_POST['cash_offered'] ?? ''));
        $assets_offered = sanitize_textarea_field($_POST['assets_offered'] ?? '');
        $from_exchange_id = absint($_POST['from_exchange_id'] ?? 0);
        $desired_cash = absint(str_replace(array(',', ' ', '٬'), '', $_POST['desired_cash'] ?? ''));

        global $wpdb;

        // Check exchange exists and is active
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d AND status = 'active'",
            $exchange_id
        ));

        if (!$exchange) {
            wp_send_json_error(array('message' => 'آگهی مورد نظر فعال نیست یا یافت نشد'));
        }

        // Prevent self-offer
        if ($exchange->user_id == get_current_user_id()) {
            wp_send_json_error(array('message' => 'امکان ارسال پیشنهاد برای آگهی خودتان وجود ندارد'));
        }

        // Check for duplicate pending offer
        $duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}moaveze_offers 
             WHERE exchange_id = %d AND from_user_id = %d AND status = 'pending'",
            $exchange_id, get_current_user_id()
        ));

        if ($duplicate) {
            wp_send_json_error(array('message' => 'شما قبلاً یک پیشنهاد فعال برای این آگهی دارید'));
        }

        // Build offer details
        $offer_details = array(
            'message'       => $message,
            'assets'        => $assets_offered,
            'desired_cash'  => $desired_cash,
            'offer_type'    => $offer_type,
            'timestamp'     => current_time('mysql'),
        );

        // If offering own property
        if ($from_exchange_id) {
            $my_exchange = $wpdb->get_row($wpdb->prepare(
                "SELECT post_id, property_value, property_type, district, area_sqm 
                 FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d AND user_id = %d",
                $from_exchange_id, get_current_user_id()
            ));
            if ($my_exchange) {
                $offer_details['my_property'] = array(
                    'title'  => get_the_title($my_exchange->post_id),
                    'value'  => $my_exchange->property_value,
                    'type'   => $my_exchange->property_type,
                    'district' => $my_exchange->district,
                    'area'   => $my_exchange->area_sqm,
                );
            }
        }


        // Insert offer
        $result = $wpdb->insert(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'exchange_id'      => $exchange_id,
                'from_user_id'     => get_current_user_id(),
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

        if ($result) {
            $offer_id = $wpdb->insert_id;

            // Trigger notification
            do_action('moaveze_new_offer', $offer_id, $exchange_id);

            wp_send_json_success(array(
                'message'  => 'پیشنهاد شما با موفقیت ارسال شد! به‌زودی بررسی خواهد شد.',
                'offer_id' => $offer_id,
            ));
        } else {
            wp_send_json_error(array('message' => 'خطا در ارسال پیشنهاد. لطفاً مجدداً تلاش کنید.'));
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
        $response = sanitize_text_field($_POST['response']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!in_array($response, array('accepted', 'rejected', 'negotiating'))) {
            wp_send_json_error('پاسخ نامعتبر');
        }

        global $wpdb;
        $offer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_offers WHERE id = %d", $offer_id
        ));

        if (!$offer) {
            wp_send_json_error('پیشنهاد یافت نشد');
        }

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

        // Notify the offer sender
        $status_labels = array(
            'accepted'    => 'قبول شد',
            'rejected'    => 'رد شد',
            'negotiating' => 'در حال مذاکره',
        );

        $notification = new Moaveze_Notifications();
        $notification->send(
            $offer->from_user_id,
            'offer_' . $response,
            'وضعیت پیشنهاد: ' . $status_labels[$response],
            sprintf('پیشنهاد شما برای آگهی "%s" %s.', get_the_title($offer->exchange_id), $status_labels[$response]),
            array('offer_id' => $offer_id, 'response' => $response, 'notes' => $notes)
        );

        wp_send_json_success(array(
            'message' => 'پاسخ با موفقیت ثبت شد و به کاربر اطلاع‌رسانی شد',
            'status'  => $response,
        ));
    }


    /**
     * Counter offer
     */
    public function counter_offer() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('moaveze_manage_offers')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $offer_id = absint($_POST['offer_id']);
        $counter_message = sanitize_textarea_field($_POST['counter_message']);
        $counter_cash = absint(str_replace(array(',', ' '), '', $_POST['counter_cash'] ?? ''));

        global $wpdb;
        $offer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_offers WHERE id = %d", $offer_id
        ));

        if (!$offer) wp_send_json_error('پیشنهاد یافت نشد');

        // Update original offer status
        $wpdb->update(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'status'      => 'countered',
                'admin_notes' => 'پیشنهاد متقابل ارسال شد: ' . $counter_message,
                'responded_at' => current_time('mysql'),
            ),
            array('id' => $offer_id)
        );

        // Create counter-offer entry
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'exchange_id'      => $offer->exchange_id,
                'from_user_id'     => get_current_user_id(),
                'from_exchange_id' => null,
                'offer_type'       => 'counter',
                'offer_details'    => wp_json_encode(array(
                    'original_offer_id' => $offer_id,
                    'counter_cash'      => $counter_cash,
                    'message'           => $counter_message,
                )),
                'cash_offered'     => $counter_cash,
                'message'          => $counter_message,
                'status'           => 'pending',
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );

        // Notify user
        $notification = new Moaveze_Notifications();
        $notification->send(
            $offer->from_user_id,
            'counter_offer',
            'پیشنهاد متقابل دریافت شد',
            'مشاوران ما یک پیشنهاد متقابل برای شما ارسال کرده‌اند. لطفاً بررسی فرمایید.',
            array('offer_id' => $offer_id)
        );

        wp_send_json_success(array('message' => 'پیشنهاد متقابل ارسال شد'));
    }

    /**
     * Withdraw an offer
     */
    public function withdraw_offer() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error('وارد شوید');

        $offer_id = absint($_POST['offer_id']);

        global $wpdb;
        $offer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_offers WHERE id = %d AND from_user_id = %d",
            $offer_id, get_current_user_id()
        ));

        if (!$offer) wp_send_json_error('پیشنهاد یافت نشد');
        if ($offer->status !== 'pending') {
            wp_send_json_error('فقط پیشنهادات در انتظار قابل لغو هستند');
        }

        $wpdb->update(
            $wpdb->prefix . 'moaveze_offers',
            array('status' => 'withdrawn'),
            array('id' => $offer_id)
        );

        wp_send_json_success(array('message' => 'پیشنهاد شما لغو شد'));
    }


    /**
     * Get user's sent offers via AJAX
     */
    public function get_my_offers() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error('وارد شوید');

        global $wpdb;
        $offers = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, e.post_id, e.property_value, e.property_type, e.district, e.area_sqm
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             WHERE o.from_user_id = %d
             ORDER BY o.created_at DESC",
            get_current_user_id()
        ));

        $formatted = array();
        foreach ($offers as $offer) {
            $formatted[] = $this->format_offer($offer);
        }

        $stats = array(
            'total'       => count($formatted),
            'pending'     => count(array_filter($formatted, function($o) { return $o['status'] === 'pending'; })),
            'accepted'    => count(array_filter($formatted, function($o) { return $o['status'] === 'accepted'; })),
            'rejected'    => count(array_filter($formatted, function($o) { return $o['status'] === 'rejected'; })),
            'negotiating' => count(array_filter($formatted, function($o) { return $o['status'] === 'negotiating'; })),
        );

        wp_send_json_success(array('offers' => $formatted, 'stats' => $stats));
    }

    /**
     * Get received offers (for property owners / admin)
     */
    public function get_received_offers() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('moaveze_manage_offers')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $exchange_id = absint($_POST['exchange_id'] ?? 0);

        global $wpdb;
        $where = "WHERE o.status != 'withdrawn'";
        if ($exchange_id) {
            $where .= $wpdb->prepare(" AND o.exchange_id = %d", $exchange_id);
        }

        $offers = $wpdb->get_results(
            "SELECT o.*, e.post_id, e.property_value, e.property_type, e.district, e.area_sqm,
                    u.display_name as sender_name
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             LEFT JOIN {$wpdb->users} u ON o.from_user_id = u.ID
             $where
             ORDER BY o.created_at DESC
             LIMIT 50"
        );

        $formatted = array();
        foreach ($offers as $offer) {
            $f = $this->format_offer($offer);
            $f['sender_name'] = $offer->sender_name ?? 'کاربر مهمان';
            $formatted[] = $f;
        }

        wp_send_json_success(array('offers' => $formatted));
    }


    /**
     * Get single offer details
     */
    public function get_offer_details() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        $offer_id = absint($_POST['offer_id']);

        global $wpdb;
        $offer = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, e.post_id, e.property_value, e.property_type, e.district
             FROM {$wpdb->prefix}moaveze_offers o
             JOIN {$wpdb->prefix}moaveze_exchanges e ON o.exchange_id = e.id
             WHERE o.id = %d",
            $offer_id
        ));

        if (!$offer) wp_send_json_error('پیشنهاد یافت نشد');

        // Security: only sender, admin, or consultant can see details
        $can_view = (
            $offer->from_user_id == get_current_user_id() ||
            current_user_can('manage_options') ||
            current_user_can('moaveze_manage_offers')
        );

        if (!$can_view) wp_send_json_error('دسترسی ندارید');

        $details = json_decode($offer->offer_details, true);

        wp_send_json_success(array(
            'offer'   => $this->format_offer($offer),
            'details' => $details,
            'timeline' => $this->get_offer_timeline($offer_id),
        ));
    }

    /**
     * Get offer timeline (history of status changes)
     */
    private function get_offer_timeline($offer_id) {
        global $wpdb;
        $offer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_offers WHERE id = %d", $offer_id
        ));

        $timeline = array();

        $timeline[] = array(
            'status' => 'submitted',
            'label'  => 'ارسال پیشنهاد',
            'date'   => $offer->created_at,
            'icon'   => 'send',
        );

        if ($offer->responded_at) {
            $status_labels = array(
                'accepted'    => 'قبول شده',
                'rejected'    => 'رد شده',
                'negotiating' => 'در حال مذاکره',
                'countered'   => 'پیشنهاد متقابل',
            );
            $timeline[] = array(
                'status' => $offer->status,
                'label'  => $status_labels[$offer->status] ?? $offer->status,
                'date'   => $offer->responded_at,
                'notes'  => $offer->admin_notes,
                'icon'   => $offer->status === 'accepted' ? 'check' : ($offer->status === 'rejected' ? 'x' : 'message'),
            );
        }

        return $timeline;
    }

    /**
     * Format offer for frontend
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

        return array(
            'id'             => $offer->id,
            'exchange_id'    => $offer->exchange_id,
            'title'          => get_the_title($offer->post_id),
            'property_value' => $offer->property_value,
            'property_type'  => $offer->property_type,
            'district'       => $offer->district,
            'area'           => $offer->area_sqm ?? 0,
            'offer_type'     => $offer->offer_type,
            'cash_offered'   => $offer->cash_offered,
            'assets_offered' => $offer->assets_offered,
            'message'        => $offer->message,
            'status'         => $offer->status,
            'status_label'   => $status_info['label'],
            'status_color'   => $status_info['color'],
            'admin_notes'    => $offer->admin_notes,
            'date'           => $offer->created_at,
            // Jalali relative time instead of Gregorian human_time_diff()
            'date_human'     => Moaveze_Helpers::jalali_time_diff($offer->created_at),
            'responded_at'   => $offer->responded_at,
            'url'            => get_permalink($offer->post_id),
            'image'          => get_the_post_thumbnail_url($offer->post_id, 'thumbnail'),
        );
    }


    /**
     * Render My Offers page [moaveze_my_offers]
     */
    public function render_my_offers_page() {
        if (!is_user_logged_in()) {
            return '<div class="moaveze-wrapper"><div class="moaveze-login-required">
                <p>برای مشاهده پیشنهادات خود باید وارد شوید.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="moaveze-btn moaveze-btn-primary">ورود به حساب</a>
            </div></div>';
        }

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-my-offers-container">
                <div class="moaveze-offers-header">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        پیشنهادات من
                    </h2>
                </div>

                <!-- Stats Cards -->
                <div class="moaveze-offers-stats" id="offers-stats">
                    <div class="offer-stat-card" data-filter="all">
                        <span class="stat-num" id="stat-total">-</span>
                        <span class="stat-lbl">کل</span>
                    </div>
                    <div class="offer-stat-card warning" data-filter="pending">
                        <span class="stat-num" id="stat-pending">-</span>
                        <span class="stat-lbl">در انتظار</span>
                    </div>
                    <div class="offer-stat-card success" data-filter="accepted">
                        <span class="stat-num" id="stat-accepted">-</span>
                        <span class="stat-lbl">قبول شده</span>
                    </div>
                    <div class="offer-stat-card info" data-filter="negotiating">
                        <span class="stat-num" id="stat-negotiating">-</span>
                        <span class="stat-lbl">مذاکره</span>
                    </div>
                    <div class="offer-stat-card danger" data-filter="rejected">
                        <span class="stat-num" id="stat-rejected">-</span>
                        <span class="stat-lbl">رد شده</span>
                    </div>
                </div>

                <!-- Offers List -->
                <div class="moaveze-offers-list" id="offers-list">
                    <div class="moaveze-loading">
                        <div class="loading-spinner"></div>
                        <p>در حال بارگذاری...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Moaveze_Offers();
