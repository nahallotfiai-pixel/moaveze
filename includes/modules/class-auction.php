<?php
/**
 * Auction/Bidding System - Phase 2
 * Best-offer mode where multiple users bid on an exchange
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Auction {

    public function __construct() {
        add_action('wp_ajax_moaveze_start_auction', array($this, 'start_auction'));
        add_action('wp_ajax_moaveze_place_bid', array($this, 'place_bid'));
        add_action('wp_ajax_moaveze_get_auction_bids', array($this, 'get_auction_bids'));
        add_action('wp_ajax_moaveze_end_auction', array($this, 'end_auction'));
        add_action('wp_ajax_nopriv_moaveze_get_auction_bids', array($this, 'get_auction_bids'));
        add_shortcode('moaveze_auctions', array($this, 'render_auctions_page'));
    }

    /**
     * Start an auction on an exchange listing (admin)
     */
    public function start_auction() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی ندارید');

        $post_id = absint($_POST['post_id']);
        $duration_days = absint($_POST['duration'] ?? 7);
        $min_bid = absint(str_replace(array(',', ' '), '', $_POST['min_bid'] ?? '0'));


        $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));

        update_post_meta($post_id, '_moaveze_auction_enabled', '1');
        update_post_meta($post_id, '_moaveze_auction_end', $end_date);
        update_post_meta($post_id, '_moaveze_auction_min_bid', $min_bid);
        update_post_meta($post_id, '_moaveze_auction_status', 'active');

        wp_send_json_success(array(
            'message' => sprintf('مزایده برای %d روز فعال شد', $duration_days),
            'end_date' => $end_date,
        ));
    }

    /**
     * Place a bid
     */
    public function place_bid() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'برای شرکت در مزایده وارد شوید'));
        }

        $post_id = absint($_POST['post_id']);
        $bid_cash = absint(str_replace(array(',', ' '), '', $_POST['bid_cash'] ?? '0'));
        $bid_description = sanitize_textarea_field($_POST['bid_description'] ?? '');
        $bid_assets = sanitize_textarea_field($_POST['bid_assets'] ?? '');

        // Check auction is active
        $auction_status = get_post_meta($post_id, '_moaveze_auction_status', true);
        $auction_end = get_post_meta($post_id, '_moaveze_auction_end', true);

        if ($auction_status !== 'active') {
            wp_send_json_error(array('message' => 'این مزایده فعال نیست'));
        }

        if ($auction_end && strtotime($auction_end) < time()) {
            update_post_meta($post_id, '_moaveze_auction_status', 'ended');
            wp_send_json_error(array('message' => 'مهلت مزایده به پایان رسیده'));
        }

        // Check minimum bid
        $min_bid = get_post_meta($post_id, '_moaveze_auction_min_bid', true);
        if ($min_bid && $bid_cash < $min_bid) {
            wp_send_json_error(array(
                'message' => sprintf('حداقل مبلغ پیشنهادی %s تومان است', number_format($min_bid))
            ));
        }

        // Prevent duplicate pending bids
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}moaveze_offers 
             WHERE exchange_id = (SELECT id FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d LIMIT 1)
             AND from_user_id = %d AND offer_type = 'auction_bid' AND status = 'pending'",
            $post_id, get_current_user_id()
        ));

        if ($existing) {
            // Update existing bid
            $wpdb->update(
                $wpdb->prefix . 'moaveze_offers',
                array(
                    'cash_offered'   => $bid_cash,
                    'assets_offered' => $bid_assets,
                    'message'        => $bid_description,
                    'offer_details'  => wp_json_encode(array(
                        'bid_cash'        => $bid_cash,
                        'bid_description' => $bid_description,
                        'bid_assets'      => $bid_assets,
                        'updated_at'      => current_time('mysql'),
                    )),
                ),
                array('id' => $existing)
            );
            wp_send_json_success(array('message' => 'پیشنهاد شما بروزرسانی شد'));
        }


        // Get exchange ID
        $exchange_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d LIMIT 1", $post_id
        ));

        if (!$exchange_id) wp_send_json_error(array('message' => 'آگهی یافت نشد'));

        // Insert new bid
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_offers',
            array(
                'exchange_id'      => $exchange_id,
                'from_user_id'     => get_current_user_id(),
                'offer_type'       => 'auction_bid',
                'cash_offered'     => $bid_cash,
                'assets_offered'   => $bid_assets,
                'message'          => $bid_description,
                'offer_details'    => wp_json_encode(array(
                    'bid_cash'        => $bid_cash,
                    'bid_description' => $bid_description,
                    'bid_assets'      => $bid_assets,
                    'bid_time'        => current_time('mysql'),
                )),
                'status'           => 'pending',
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );

        // Count total bids
        $bid_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers 
             WHERE exchange_id = %d AND offer_type = 'auction_bid'",
            $exchange_id
        ));

        // Notify admin
        do_action('moaveze_new_offer', $wpdb->insert_id, $exchange_id);

        wp_send_json_success(array(
            'message'   => 'پیشنهاد شما ثبت شد! پس از اتمام مزایده نتیجه اعلام خواهد شد.',
            'bid_count' => $bid_count,
        ));
    }

    /**
     * Get auction bids (public: only counts; admin: full details)
     */
    public function get_auction_bids() {
        $post_id = absint($_POST['post_id'] ?? $_GET['post_id'] ?? 0);
        if (!$post_id) wp_send_json_error('شناسه نامعتبر');

        global $wpdb;
        $exchange_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d", $post_id
        ));

        $bid_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers 
             WHERE exchange_id = %d AND offer_type = 'auction_bid'",
            $exchange_id
        ));

        $max_bid = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(cash_offered) FROM {$wpdb->prefix}moaveze_offers 
             WHERE exchange_id = %d AND offer_type = 'auction_bid'",
            $exchange_id
        ));

        $auction_end = get_post_meta($post_id, '_moaveze_auction_end', true);
        $remaining = max(0, strtotime($auction_end) - time());

        $response = array(
            'bid_count'      => (int) $bid_count,
            'highest_bid'    => (int) $max_bid,
            'end_date'       => $auction_end,
            'remaining_secs' => $remaining,
            'status'         => get_post_meta($post_id, '_moaveze_auction_status', true),
        );

        // Admin gets full bid list
        if (current_user_can('manage_options')) {
            $bids = $wpdb->get_results($wpdb->prepare(
                "SELECT o.*, u.display_name FROM {$wpdb->prefix}moaveze_offers o
                 LEFT JOIN {$wpdb->users} u ON o.from_user_id = u.ID
                 WHERE o.exchange_id = %d AND o.offer_type = 'auction_bid'
                 ORDER BY o.cash_offered DESC",
                $exchange_id
            ));
            $response['bids'] = $bids;
        }

        wp_send_json_success($response);
    }


    /**
     * End auction and select winner
     */
    public function end_auction() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('دسترسی ندارید');

        $post_id = absint($_POST['post_id']);
        $winner_id = absint($_POST['winner_offer_id'] ?? 0);

        update_post_meta($post_id, '_moaveze_auction_status', 'ended');

        if ($winner_id) {
            global $wpdb;
            // Mark winner
            $wpdb->update(
                $wpdb->prefix . 'moaveze_offers',
                array('status' => 'accepted', 'responded_at' => current_time('mysql')),
                array('id' => $winner_id)
            );

            // Get winner user
            $winner = $wpdb->get_row($wpdb->prepare(
                "SELECT from_user_id, cash_offered FROM {$wpdb->prefix}moaveze_offers WHERE id = %d", $winner_id
            ));

            if ($winner) {
                update_post_meta($post_id, '_moaveze_auction_winner', $winner->from_user_id);
                update_post_meta($post_id, '_moaveze_auction_winning_bid', $winner->cash_offered);

                // Notify winner
                $notif = new Moaveze_Notifications();
                $notif->send($winner->from_user_id, 'auction_update',
                    'تبریک! شما برنده مزایده شدید! 🎉',
                    sprintf('پیشنهاد شما برای «%s» با مبلغ %s تومان پذیرفته شد.',
                        get_the_title($post_id), number_format($winner->cash_offered)),
                    array('post_id' => $post_id, 'url' => get_permalink($post_id))
                );

                // Reject others
                $exchange_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d", $post_id
                ));
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}moaveze_offers SET status = 'rejected', responded_at = NOW()
                     WHERE exchange_id = %d AND offer_type = 'auction_bid' AND id != %d AND status = 'pending'",
                    $exchange_id, $winner_id
                ));
            }
        }

        wp_send_json_success(array('message' => 'مزایده خاتمه یافت' . ($winner_id ? ' و برنده مشخص شد' : '')));
    }

    /**
     * Render auctions page [moaveze_auctions]
     */
    public function render_auctions_page() {
        $query = new WP_Query(array(
            'post_type'   => 'moaveze_exchange',
            'post_status' => 'publish',
            'meta_key'    => '_moaveze_auction_enabled',
            'meta_value'  => '1',
            'orderby'     => 'date',
            'order'       => 'DESC',
            'posts_per_page' => 12,
        ));

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-auctions-header">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    مزایده‌های معاوضه
                </h2>
                <p>بهترین پیشنهاد خود را ارسال کنید. پس از پایان مهلت، بهترین پیشنهاد انتخاب می‌شود.</p>
            </div>

            <?php if ($query->have_posts()) : ?>
                <div class="moaveze-listings-grid">
                    <?php while ($query->have_posts()) : $query->the_post();
                        $post_id = get_the_ID();
                        $value = get_post_meta($post_id, '_moaveze_property_value', true);
                        $end = get_post_meta($post_id, '_moaveze_auction_end', true);
                        $status = get_post_meta($post_id, '_moaveze_auction_status', true);
                        $remaining = max(0, strtotime($end) - time());
                        $days = floor($remaining / 86400);
                        $hours = floor(($remaining % 86400) / 3600);
                    ?>
                        <div class="moaveze-card">
                            <div class="moaveze-card-image">
                                <?php if (has_post_thumbnail()) : the_post_thumbnail('medium_large'); endif; ?>
                                <div class="moaveze-card-overlay">
                                    <span class="exchange-type-badge" style="background:#dc2626;">
                                        <?php echo $status === 'active' ? "⏰ {$days} روز و {$hours} ساعت" : '⛔ پایان یافته'; ?>
                                    </span>
                                </div>
                                <span class="moaveze-badge-featured" style="background:#dc2626;">مزایده</span>
                            </div>
                            <div class="moaveze-card-body">
                                <h3 class="moaveze-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <div class="moaveze-card-footer">
                                    <span class="moaveze-card-price"><?php echo number_format($value); ?> <small>تومان</small></span>
                                    <a href="<?php the_permalink(); ?>" class="moaveze-btn moaveze-btn-sm moaveze-btn-outline">
                                        <?php echo $status === 'active' ? 'شرکت در مزایده' : 'مشاهده نتیجه'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="moaveze-empty-state">
                    <p>فعلاً مزایده فعالی وجود ندارد.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}

new Moaveze_Auction();
