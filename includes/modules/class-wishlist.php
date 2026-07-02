<?php
/**
 * Wishlist System - Phase 2
 * Users save desired property criteria and get notified on match
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Wishlist {

    public function __construct() {
        add_action('wp_ajax_moaveze_save_wishlist', array($this, 'save_wishlist'));
        add_action('wp_ajax_moaveze_get_wishlist', array($this, 'get_wishlist'));
        add_action('wp_ajax_moaveze_delete_wishlist', array($this, 'delete_wishlist'));
        add_action('save_post_moaveze_exchange', array($this, 'check_wishlists_on_new_listing'), 30, 2);
        add_shortcode('moaveze_wishlist', array($this, 'render_wishlist_page'));
    }

    /**
     * Save wishlist item
     */
    public function save_wishlist() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(array('message' => 'وارد شوید'));

        global $wpdb;

        $data = array(
            'user_id'              => get_current_user_id(),
            'desired_property_type' => sanitize_text_field($_POST['desired_property_type'] ?? ''),
            'desired_min_value'    => absint(str_replace(array(',', ' '), '', $_POST['desired_min_value'] ?? '')),
            'desired_max_value'    => absint(str_replace(array(',', ' '), '', $_POST['desired_max_value'] ?? '')),
            'desired_districts'    => !empty($_POST['desired_districts']) ? wp_json_encode(array_map('sanitize_text_field', $_POST['desired_districts'])) : null,
            'desired_min_area'     => absint($_POST['desired_min_area'] ?? 0),
            'desired_max_area'     => absint($_POST['desired_max_area'] ?? 0),
            'desired_rooms'        => absint($_POST['desired_rooms'] ?? 0),
            'additional_criteria'  => wp_json_encode(array(
                'exchange_type' => sanitize_text_field($_POST['exchange_type'] ?? ''),
                'notes'         => sanitize_textarea_field($_POST['notes'] ?? ''),
            )),
            'notify_on_match'      => 1,
            'status'               => 'active',
        );


        $id = absint($_POST['wishlist_id'] ?? 0);

        if ($id) {
            // Update existing
            $wpdb->update(
                $wpdb->prefix . 'moaveze_wishlist',
                $data,
                array('id' => $id, 'user_id' => get_current_user_id())
            );
            wp_send_json_success(array('message' => 'لیست آرزو بروزرسانی شد', 'id' => $id));
        } else {
            // Check max wishlist items (3 per user)
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_wishlist WHERE user_id = %d AND status = 'active'",
                get_current_user_id()
            ));
            if ($count >= 3) {
                wp_send_json_error(array('message' => 'حداکثر ۳ مورد لیست آرزو مجاز است'));
            }

            $wpdb->insert($wpdb->prefix . 'moaveze_wishlist', $data);
            wp_send_json_success(array('message' => 'به لیست آرزو اضافه شد! وقتی ملک مناسب ثبت شود مطلع می‌شوید.', 'id' => $wpdb->insert_id));
        }
    }

    /**
     * Get user's wishlist
     */
    public function get_wishlist() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('وارد شوید');

        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_wishlist WHERE user_id = %d AND status = 'active' ORDER BY created_at DESC",
            get_current_user_id()
        ));

        $formatted = array();
        foreach ($items as $item) {
            $districts = json_decode($item->desired_districts, true) ?: array();
            $criteria = json_decode($item->additional_criteria, true) ?: array();
            $formatted[] = array(
                'id'            => $item->id,
                'property_type' => $item->desired_property_type,
                'min_value'     => $item->desired_min_value,
                'max_value'     => $item->desired_max_value,
                'districts'     => $districts,
                'min_area'      => $item->desired_min_area,
                'max_area'      => $item->desired_max_area,
                'rooms'         => $item->desired_rooms,
                'exchange_type' => $criteria['exchange_type'] ?? '',
                'notes'         => $criteria['notes'] ?? '',
                'created'       => $item->created_at,
            );
        }

        wp_send_json_success(array('items' => $formatted, 'count' => count($formatted)));
    }

    /**
     * Delete wishlist item
     */
    public function delete_wishlist() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        $id = absint($_POST['wishlist_id'] ?? 0);

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'moaveze_wishlist',
            array('status' => 'deleted'),
            array('id' => $id, 'user_id' => get_current_user_id())
        );
        wp_send_json_success(array('message' => 'حذف شد'));
    }


    /**
     * Check wishlists when new listing is published
     */
    public function check_wishlists_on_new_listing($post_id, $post) {
        if ($post->post_status !== 'publish') return;

        global $wpdb;
        $exchange = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE post_id = %d", $post_id
        ));
        if (!$exchange) return;

        // Get all active wishlists
        $wishlists = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}moaveze_wishlist WHERE status = 'active' AND notify_on_match = 1"
        );

        foreach ($wishlists as $wish) {
            // Don't notify the listing owner
            if ($wish->user_id == $exchange->user_id) continue;

            $match_score = $this->calculate_wishlist_match($wish, $exchange);

            if ($match_score >= 60) {
                // Notify user
                $notif = new Moaveze_Notifications();
                $notif->send(
                    $wish->user_id,
                    'wishlist_match',
                    'ملک مطابق لیست آرزوی شما یافت شد! ⭐',
                    sprintf('آگهی «%s» با %d%% تطابق با لیست آرزوی شما ثبت شده است. همین الان مشاهده کنید!',
                        $post->post_title, $match_score),
                    array('post_id' => $post_id, 'score' => $match_score, 'url' => get_permalink($post_id))
                );
            }
        }
    }

    /**
     * Calculate wishlist match score
     */
    private function calculate_wishlist_match($wish, $exchange) {
        $score = 0;
        $factors = 0;

        // Type match
        if ($wish->desired_property_type) {
            $factors += 25;
            if ($wish->desired_property_type === $exchange->property_type) {
                $score += 25;
            }
        }

        // Value range
        $factors += 30;
        $in_range = true;
        if ($wish->desired_min_value && $exchange->property_value < $wish->desired_min_value) $in_range = false;
        if ($wish->desired_max_value && $exchange->property_value > $wish->desired_max_value) $in_range = false;
        if ($in_range) $score += 30;

        // District
        $desired_districts = json_decode($wish->desired_districts, true) ?: array();
        if (!empty($desired_districts)) {
            $factors += 20;
            if (in_array($exchange->district, $desired_districts)) $score += 20;
        }

        // Area
        if ($wish->desired_min_area || $wish->desired_max_area) {
            $factors += 15;
            $area_ok = true;
            if ($wish->desired_min_area && $exchange->area_sqm < $wish->desired_min_area) $area_ok = false;
            if ($wish->desired_max_area && $exchange->area_sqm > $wish->desired_max_area) $area_ok = false;
            if ($area_ok) $score += 15;
        }

        // Rooms
        if ($wish->desired_rooms) {
            $factors += 10;
            if ($exchange->rooms >= $wish->desired_rooms) $score += 10;
        }

        if ($factors === 0) return 50; // No criteria = 50% default
        return round(($score / $factors) * 100);
    }


    /**
     * Render wishlist page [moaveze_wishlist]
     */
    public function render_wishlist_page() {
        if (!is_user_logged_in()) {
            return '<div class="moaveze-wrapper"><p>برای استفاده از لیست آرزو وارد شوید.</p></div>';
        }

        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false));
        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false));

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-wishlist-container">
                <div class="wishlist-header">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        لیست آرزو
                    </h2>
                    <p>مشخصات ملک رویایی‌تان را ثبت کنید تا به محض ثبت ملک مشابه، مطلع شوید.</p>
                </div>

                <!-- Wishlist Form -->
                <div class="wishlist-form-card" id="wishlist-form-card">
                    <h3>افزودن به لیست آرزو</h3>
                    <form id="wishlist-form">
                        <div class="moaveze-field-group moaveze-field-grid-2">
                            <div class="moaveze-field">
                                <label>نوع ملک مورد نظر</label>
                                <select name="desired_property_type">
                                    <option value="">همه انواع</option>
                                    <?php if (!is_wp_error($types)) : foreach ($types as $t) : ?>
                                        <option value="<?php echo esc_attr($t->name); ?>"><?php echo esc_html($t->name); ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                            <div class="moaveze-field">
                                <label>مناطق مورد نظر</label>
                                <select name="desired_districts[]" multiple>
                                    <?php if (!is_wp_error($districts)) : foreach ($districts as $d) : ?>
                                        <option value="<?php echo esc_attr($d->name); ?>"><?php echo esc_html($d->name); ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="moaveze-field-group moaveze-field-grid-2">
                            <div class="moaveze-field">
                                <label>حداقل ارزش (تومان)</label>
                                <input type="text" name="desired_min_value" class="moaveze-price-input" placeholder="مثال: 10000000000">
                            </div>
                            <div class="moaveze-field">
                                <label>حداکثر ارزش (تومان)</label>
                                <input type="text" name="desired_max_value" class="moaveze-price-input" placeholder="مثال: 25000000000">
                            </div>
                        </div>
                        <div class="moaveze-field-group moaveze-field-grid-3">
                            <div class="moaveze-field">
                                <label>حداقل متراژ</label>
                                <input type="number" name="desired_min_area" placeholder="مثال: 100">
                            </div>
                            <div class="moaveze-field">
                                <label>حداکثر متراژ</label>
                                <input type="number" name="desired_max_area" placeholder="مثال: 200">
                            </div>
                            <div class="moaveze-field">
                                <label>حداقل اتاق</label>
                                <input type="number" name="desired_rooms" min="0" max="10" placeholder="مثال: 2">
                            </div>
                        </div>
                        <div class="moaveze-field-group">
                            <div class="moaveze-field moaveze-field-full">
                                <label>توضیحات اضافی</label>
                                <textarea name="notes" rows="2" placeholder="هر شرط دیگری دارید اینجا بنویسید..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="moaveze-btn moaveze-btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            ذخیره در لیست آرزو
                        </button>
                    </form>
                </div>

                <!-- Existing Wishlist Items -->
                <div class="wishlist-items" id="wishlist-items">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </div>
        <script>
        jQuery(function($){
            // Load existing items
            function loadWishlist(){
                $.post(moavezePlus.ajaxUrl, {action:'moaveze_get_wishlist', nonce:moavezePlus.nonce}, function(r){
                    if(r.success){
                        var html = '';
                        r.data.items.forEach(function(item){
                            html += '<div class="wishlist-item-card"><div class="wi-body">'
                                + '<strong>' + (item.property_type || 'هر نوع ملک') + '</strong>'
                                + '<span>' + (item.min_value ? MoavezePlus.formatPriceShort(item.min_value, false) + ' تا ' : '') + (item.max_value ? MoavezePlus.formatPriceShort(item.max_value) : '') + '</span>'
                                + (item.districts.length ? '<span>مناطق: ' + item.districts.join('، ') + '</span>' : '')
                                + '</div><button class="delete-wishlist-btn moaveze-btn moaveze-btn-ghost moaveze-btn-sm" data-id="'+item.id+'">حذف</button></div>';
                        });
                        if(!html) html = '<p style="text-align:center;color:#94a3b8;padding:20px;">هنوز موردی ثبت نکرده‌اید.</p>';
                        $('#wishlist-items').html(html);
                    }
                });
            }
            loadWishlist();

            // Submit form
            $('#wishlist-form').on('submit', function(e){
                e.preventDefault();
                var data = $(this).serializeArray();
                data.push({name:'action',value:'moaveze_save_wishlist'});
                data.push({name:'nonce',value:moavezePlus.nonce});
                // Clean prices
                data.forEach(function(d){ if(d.name.includes('value')) d.value = d.value.replace(/[^\d]/g,''); });
                $.post(moavezePlus.ajaxUrl, $.param(data), function(r){
                    if(r.success){ MoavezePlus.showToast(r.data.message,'success'); loadWishlist(); $('#wishlist-form')[0].reset(); }
                    else MoavezePlus.showToast(r.data.message||'خطا','error');
                });
            });

            // Delete
            $(document).on('click', '.delete-wishlist-btn', function(){
                if(!confirm('حذف شود؟')) return;
                var id = $(this).data('id');
                $.post(moavezePlus.ajaxUrl, {action:'moaveze_delete_wishlist',nonce:moavezePlus.nonce,wishlist_id:id}, function(r){
                    if(r.success) loadWishlist();
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

new Moaveze_Wishlist();
