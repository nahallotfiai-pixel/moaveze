<?php
/**
 * Notifications Module - Phase 2
 * Beautiful email templates, notification center, per-user preferences
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Notifications {

    private $notification_types = array(
        'new_offer'        => array('label' => 'پیشنهاد جدید', 'icon' => '📩'),
        'offer_accepted'   => array('label' => 'قبول پیشنهاد', 'icon' => '✅'),
        'offer_rejected'   => array('label' => 'رد پیشنهاد', 'icon' => '❌'),
        'counter_offer'    => array('label' => 'پیشنهاد متقابل', 'icon' => '🔄'),
        'new_match'        => array('label' => 'تطابق جدید', 'icon' => '🎯'),
        'listing_approved' => array('label' => 'تأیید آگهی', 'icon' => '✓'),
        'listing_rejected' => array('label' => 'رد آگهی', 'icon' => '✗'),
        'wishlist_match'   => array('label' => 'ملک رویایی شما', 'icon' => '⭐'),
        'auction_update'   => array('label' => 'بروزرسانی مزایده', 'icon' => '🔔'),
        'system'           => array('label' => 'سیستمی', 'icon' => 'ℹ️'),
    );

    public function __construct() {
        add_action('moaveze_new_offer', array($this, 'notify_new_offer'), 10, 2);
        add_action('moaveze_new_match', array($this, 'notify_new_match'), 10, 2);
        add_action('transition_post_status', array($this, 'notify_status_change'), 10, 3);

        // AJAX endpoints
        add_action('wp_ajax_moaveze_get_notifications', array($this, 'get_user_notifications'));
        add_action('wp_ajax_moaveze_mark_read', array($this, 'mark_as_read'));
        add_action('wp_ajax_moaveze_mark_all_read', array($this, 'mark_all_read'));
        add_action('wp_ajax_moaveze_get_unread_count', array($this, 'get_unread_count'));
        add_action('wp_ajax_moaveze_save_notification_prefs', array($this, 'save_preferences'));

        // Notification center shortcode
        add_shortcode('moaveze_notifications', array($this, 'render_notification_center'));

        // Add notification bell to frontend
        add_action('wp_footer', array($this, 'render_notification_bell'));
    }


    /**
     * Send notification with preferences check
     */
    public function send($user_id, $type, $title, $message, $data = array()) {
        // Check user preferences
        if (!$this->user_wants_notification($user_id, $type)) {
            return false;
        }

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

        $notification_id = $wpdb->insert_id;

        // Get user's delivery preferences
        $prefs = $this->get_user_preferences($user_id);

        // Email
        if ($prefs['email'] && get_option('moaveze_email_notifications') === 'yes') {
            $user = get_userdata($user_id);
            if ($user && $user->user_email) {
                $this->send_beautiful_email($user->user_email, $type, $title, $message, $data);
            }
        }

        // SMS
        if ($prefs['sms'] && get_option('moaveze_sms_notifications') === 'yes') {
            $phone = get_user_meta($user_id, 'phone', true) ?: get_user_meta($user_id, 'billing_phone', true);
            if ($phone) {
                $this->send_sms($phone, $title . ': ' . $message);
            }
        }

        return $notification_id;
    }

    /**
     * Check if user wants this notification type
     */
    private function user_wants_notification($user_id, $type) {
        $prefs = get_user_meta($user_id, '_moaveze_notification_prefs', true);
        if (!$prefs || !is_array($prefs)) return true; // Default: all enabled

        $disabled = $prefs['disabled_types'] ?? array();
        return !in_array($type, $disabled);
    }

    /**
     * Get user delivery preferences
     */
    private function get_user_preferences($user_id) {
        $prefs = get_user_meta($user_id, '_moaveze_notification_prefs', true);
        return wp_parse_args($prefs ?: array(), array(
            'email'          => true,
            'sms'            => true,
            'push'           => true,
            'disabled_types' => array(),
        ));
    }


    /**
     * Notify on new offer
     */
    public function notify_new_offer($offer_id, $exchange_id) {
        global $wpdb;
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $exchange_id
        ));
        if (!$exchange) return;

        $title_text = get_the_title($exchange->post_id);

        // Notify all admins
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $this->send($admin->ID, 'new_offer', 'پیشنهاد معاوضه جدید',
                sprintf('پیشنهاد جدیدی برای «%s» دریافت شد. بررسی کنید.', $title_text),
                array('offer_id' => $offer_id, 'exchange_id' => $exchange_id, 'post_id' => $exchange->post_id)
            );
        }

        // Notify consultants
        $consultants = get_users(array('role' => 'moaveze_consultant'));
        foreach ($consultants as $c) {
            $this->send($c->ID, 'new_offer', 'پیشنهاد جدید',
                sprintf('پیشنهاد جدید برای «%s» ثبت شد.', $title_text),
                array('offer_id' => $offer_id)
            );
        }
    }

    /**
     * Notify on new match found
     */
    public function notify_new_match($match_id, $data) {
        // Notify admins
        $admins = get_users(array('role' => 'administrator'));
        $score = $data['score'] ?? ($data['match_score'] ?? 0);
        foreach ($admins as $admin) {
            $this->send($admin->ID, 'new_match', 'تطابق جدید یافت شد!',
                sprintf('تطابق %s%% بین دو آگهی معاوضه شناسایی شد. پیگیری کنید.', $score),
                array('match_id' => $match_id, 'data' => $data)
            );
        }
    }

    /**
     * Notify on post status change
     */
    public function notify_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'moaveze_exchange') return;
        if ($new_status === $old_status) return;

        if ($new_status === 'publish' && $old_status === 'pending') {
            $this->send($post->post_author, 'listing_approved', 'آگهی شما تأیید شد! ✅',
                sprintf('آگهی «%s» تأیید و منتشر شد. اکنون دیگران می‌توانند آن را مشاهده کنند.', $post->post_title),
                array('post_id' => $post->ID, 'url' => get_permalink($post->ID))
            );
        } elseif ($new_status === 'draft' && $old_status === 'pending') {
            $reason = get_post_meta($post->ID, '_moaveze_reject_reason', true);
            $this->send($post->post_author, 'listing_rejected', 'آگهی نیاز به بازبینی دارد',
                sprintf('آگهی «%s» رد شد. دلیل: %s', $post->post_title, $reason ?: 'لطفاً اصلاح و مجدداً ارسال کنید.'),
                array('post_id' => $post->ID, 'reason' => $reason)
            );
        }
    }


    /**
     * Send beautiful HTML email
     */
    private function send_beautiful_email($to, $type, $subject, $message, $data = array()) {
        $type_info = $this->notification_types[$type] ?? array('icon' => 'ℹ️', 'label' => 'اطلاع‌رسانی');
        $icon = $type_info['icon'];
        $cta_url = '';
        $cta_text = '';

        if (!empty($data['post_id'])) {
            $cta_url = get_permalink($data['post_id']);
            $cta_text = 'مشاهده آگهی';
        } elseif (!empty($data['url'])) {
            $cta_url = $data['url'];
            $cta_text = 'مشاهده';
        }

        $html = '<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="UTF-8">
        <meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
        <body style="margin:0;padding:0;font-family:Tahoma,Arial,sans-serif;direction:rtl;background:#f1f5f9;">
        <div style="max-width:580px;margin:30px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);padding:30px;text-align:center;">
                <div style="font-size:36px;margin-bottom:8px;">' . $icon . '</div>
                <h1 style="color:#fff;font-size:18px;margin:0;font-weight:700;">معاوضه پلاس | تبریز هوم</h1>
            </div>
            <!-- Body -->
            <div style="padding:30px;">
                <h2 style="color:#1e293b;font-size:18px;margin:0 0 12px;">' . esc_html($subject) . '</h2>
                <p style="color:#475569;font-size:14px;line-height:2;margin:0 0 20px;">' . esc_html($message) . '</p>';

        if ($cta_url && $cta_text) {
            $html .= '<div style="text-align:center;margin:24px 0;">
                <a href="' . esc_url($cta_url) . '" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;box-shadow:0 4px 15px rgba(99,102,241,0.3);">' . esc_html($cta_text) . '</a>
            </div>';
        }

        $html .= '</div>
            <!-- Footer -->
            <div style="background:#f8fafc;padding:20px 30px;border-top:1px solid #e2e8f0;text-align:center;">
                <p style="color:#94a3b8;font-size:11px;margin:0;">این ایمیل از طرف سیستم معاوضه ملک تبریز هوم ارسال شده است.</p>
                <p style="color:#94a3b8;font-size:11px;margin:4px 0 0;"><a href="' . home_url('/notification-settings/') . '" style="color:#6366f1;">تنظیمات اعلان‌ها</a></p>
            </div>
        </div>
        </body></html>';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $icon . ' ' . $subject . ' | تبریز هوم', $html, $headers);
    }

    /**
     * Send SMS
     */
    private function send_sms($phone, $message) {
        $api_key = get_option('moaveze_sms_api_key');
        if (!$api_key) return;

        wp_remote_post("https://api.kavenegar.com/v1/{$api_key}/sms/send.json", array(
            'body'    => array('receptor' => $phone, 'message' => mb_substr($message, 0, 140)),
            'timeout' => 10,
        ));
    }


    /**
     * AJAX: Get user notifications
     */
    public function get_user_notifications() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('وارد شوید');

        $page = absint($_POST['page'] ?? 1);
        $per_page = 15;
        $offset = ($page - 1) * $per_page;

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_notifications';

        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            get_current_user_id(), $per_page, $offset
        ));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d", get_current_user_id()
        ));

        $unread = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0", get_current_user_id()
        ));

        $formatted = array();
        foreach ($notifications as $n) {
            $type_info = $this->notification_types[$n->type] ?? array('icon' => 'ℹ️', 'label' => 'سیستمی');
            $formatted[] = array(
                'id'        => $n->id,
                'type'      => $n->type,
                'icon'      => $type_info['icon'],
                'label'     => $type_info['label'],
                'title'     => $n->title,
                'message'   => $n->message,
                'data'      => json_decode($n->data, true),
                'is_read'   => (bool) $n->is_read,
                'date'      => $n->created_at,
                'date_human' => human_time_diff(strtotime($n->created_at)) . ' پیش',
            );
        }

        wp_send_json_success(array(
            'notifications' => $formatted,
            'unread_count'  => (int) $unread,
            'total'         => (int) $total,
            'pages'         => ceil($total / $per_page),
            'current_page'  => $page,
        ));
    }

    /**
     * AJAX: Mark single notification as read
     */
    public function mark_as_read() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        $id = absint($_POST['notification_id'] ?? 0);
        if (!$id) wp_send_json_error();

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'moaveze_notifications',
            array('is_read' => 1),
            array('id' => $id, 'user_id' => get_current_user_id())
        );
        wp_send_json_success();
    }

    /**
     * AJAX: Mark all as read
     */
    public function mark_all_read() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'moaveze_notifications',
            array('is_read' => 1),
            array('user_id' => get_current_user_id(), 'is_read' => 0)
        );
        wp_send_json_success(array('message' => 'همه خوانده شد'));
    }

    /**
     * AJAX: Get unread count (for bell badge)
     */
    public function get_unread_count() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_notifications WHERE user_id = %d AND is_read = 0",
            get_current_user_id()
        ));
        wp_send_json_success(array('count' => (int) $count));
    }

    /**
     * AJAX: Save notification preferences
     */
    public function save_preferences() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error();

        $prefs = array(
            'email'          => !empty($_POST['email']),
            'sms'            => !empty($_POST['sms']),
            'push'           => !empty($_POST['push']),
            'disabled_types' => isset($_POST['disabled_types']) ? array_map('sanitize_text_field', $_POST['disabled_types']) : array(),
        );

        update_user_meta(get_current_user_id(), '_moaveze_notification_prefs', $prefs);
        wp_send_json_success(array('message' => 'تنظیمات ذخیره شد'));
    }


    /**
     * Render notification bell (floating) in footer
     */
    public function render_notification_bell() {
        if (!is_user_logged_in()) return;
        ?>
        <div class="moaveze-notification-bell" id="moaveze-bell">
            <button class="bell-trigger" id="bell-trigger" aria-label="اعلان‌ها">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
                <span class="bell-badge" id="bell-badge" style="display:none;">0</span>
            </button>
            <div class="bell-dropdown" id="bell-dropdown" style="display:none;">
                <div class="bell-dropdown-header">
                    <h4>اعلان‌ها</h4>
                    <button id="mark-all-read-btn" class="mark-all-btn">خواندن همه</button>
                </div>
                <div class="bell-dropdown-body" id="bell-notifications-list">
                    <div class="moaveze-loading"><div class="loading-spinner"></div></div>
                </div>
                <div class="bell-dropdown-footer">
                    <a href="<?php echo home_url('/notifications/'); ?>">مشاهده همه</a>
                </div>
            </div>
        </div>
        <script>
        jQuery(function($){
            // Load unread count
            function loadBadge(){
                $.post(moavezePlus.ajaxUrl, {action:'moaveze_get_unread_count',nonce:moavezePlus.nonce}, function(r){
                    if(r.success && r.data.count > 0){
                        $('#bell-badge').text(r.data.count).show();
                    } else {
                        $('#bell-badge').hide();
                    }
                });
            }
            loadBadge();
            setInterval(loadBadge, 60000);

            // Toggle dropdown
            $('#bell-trigger').on('click', function(e){
                e.stopPropagation();
                var dd = $('#bell-dropdown');
                dd.toggle();
                if(dd.is(':visible')) loadNotifications();
            });
            $(document).on('click', function(e){
                if(!$(e.target).closest('#moaveze-bell').length) $('#bell-dropdown').hide();
            });

            function loadNotifications(){
                $.post(moavezePlus.ajaxUrl, {action:'moaveze_get_notifications',nonce:moavezePlus.nonce}, function(r){
                    if(r.success){
                        var html = '';
                        r.data.notifications.slice(0,7).forEach(function(n){
                            html += '<div class="bell-notification-item '+(n.is_read?'':'unread')+'" data-id="'+n.id+'">'
                                + '<span class="n-icon">'+n.icon+'</span>'
                                + '<div class="n-body"><strong>'+n.title+'</strong><p>'+n.message.substring(0,60)+'</p><small>'+n.date_human+'</small></div>'
                                + '</div>';
                        });
                        if(!html) html = '<p style="text-align:center;padding:20px;color:#94a3b8;">اعلانی ندارید</p>';
                        $('#bell-notifications-list').html(html);
                    }
                });
            }

            // Mark all as read
            $('#mark-all-read-btn').on('click', function(){
                $.post(moavezePlus.ajaxUrl, {action:'moaveze_mark_all_read',nonce:moavezePlus.nonce}, function(){
                    $('#bell-badge').hide();
                    $('.bell-notification-item').removeClass('unread');
                });
            });

            // Click notification = mark as read
            $(document).on('click', '.bell-notification-item.unread', function(){
                var id = $(this).data('id');
                $(this).removeClass('unread');
                $.post(moavezePlus.ajaxUrl, {action:'moaveze_mark_read',nonce:moavezePlus.nonce,notification_id:id});
            });
        });
        </script>
        <?php
    }

    /**
     * Render notification center shortcode [moaveze_notifications]
     */
    public function render_notification_center() {
        if (!is_user_logged_in()) {
            return '<div class="moaveze-wrapper"><p>برای مشاهده اعلان‌ها وارد شوید.</p></div>';
        }
        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-notification-center" id="notification-center">
                <div class="nc-header">
                    <h2>مرکز اعلان‌ها</h2>
                    <button id="nc-mark-all" class="moaveze-btn moaveze-btn-ghost moaveze-btn-sm">خواندن همه</button>
                </div>
                <div class="nc-list" id="nc-list">
                    <div class="moaveze-loading"><div class="loading-spinner"></div><p>در حال بارگذاری...</p></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Moaveze_Notifications();
