<?php
/**
 * Favorites / Bookmarks
 *
 * Lets any visitor (logged in or not) save listings they're interested
 * in for later, without needing an account - a low-friction feature
 * that keeps casual browsers coming back to the site instead of losing
 * track of listings they liked. Uses localStorage on the client, with
 * a lightweight REST-ish AJAX endpoint to fetch full card data for a
 * given list of IDs (so the favorites page can render real listing
 * cards, not just raw IDs from localStorage).
 *
 * For logged-in users, favorites are ALSO synced to user meta so they
 * follow the user across devices/browsers - localStorage remains the
 * source of truth for guests.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Favorites {

    public function __construct() {
        add_action('wp_ajax_moaveze_get_favorites', array($this, 'get_favorites'));
        add_action('wp_ajax_nopriv_moaveze_get_favorites', array($this, 'get_favorites'));
        add_action('wp_ajax_moaveze_sync_favorites', array($this, 'sync_favorites'));
        add_shortcode('moaveze_favorites', array($this, 'render_favorites_page'));
    }

    /**
     * AJAX: given a list of post IDs (from localStorage), return
     * formatted card data for each still-published listing.
     */
    public function get_favorites() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        $ids = isset($_POST['ids']) ? array_map('absint', (array) $_POST['ids']) : array();
        $ids = array_filter($ids);

        if (empty($ids)) {
            wp_send_json_success(array('items' => array()));
        }

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'post__in'       => $ids,
            'orderby'        => 'post__in',
            'posts_per_page' => 100,
        ));

        $items = array();
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $type_terms = get_the_terms($id, 'moaveze_property_type');
            $district_terms = get_the_terms($id, 'moaveze_district');

            $items[] = array(
                'id'       => $id,
                'title'    => get_the_title($id),
                'url'      => get_permalink($id),
                'image'    => get_the_post_thumbnail_url($id, 'medium'),
                'value'    => (int) get_post_meta($id, '_moaveze_property_value', true),
                'area'     => (int) get_post_meta($id, '_moaveze_area_sqm', true),
                'type'     => $type_terms ? $type_terms[0]->name : '',
                'district' => $district_terms ? $district_terms[0]->name : '',
                'verified' => (bool) get_post_meta($id, '_moaveze_verified', true),
            );
        }
        wp_reset_postdata();

        wp_send_json_success(array('items' => $items));
    }

    /**
     * AJAX: for logged-in users, persist their current localStorage
     * favorites list to user meta so it's available on other
     * devices/browsers next time they log in.
     */
    public function sync_favorites() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_success(array('synced' => false));

        $ids = isset($_POST['ids']) ? array_map('absint', (array) $_POST['ids']) : array();
        update_user_meta(get_current_user_id(), '_moaveze_favorites', array_values(array_unique(array_filter($ids))));

        wp_send_json_success(array('synced' => true, 'count' => count($ids)));
    }

    /**
     * Get a logged-in user's server-side saved favorite IDs (used to
     * pre-seed localStorage on login so their list follows them).
     */
    public static function get_user_favorite_ids($user_id) {
        $ids = get_user_meta($user_id, '_moaveze_favorites', true);
        return is_array($ids) ? $ids : array();
    }

    /**
     * Render the [moaveze_favorites] "علاقه‌مندی‌های من" page.
     */
    public function render_favorites_page() {
        $server_ids = is_user_logged_in() ? self::get_user_favorite_ids(get_current_user_id()) : array();

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-favorites-container">
                <div class="favorites-header">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                        علاقه‌مندی‌های من
                    </h2>
                    <p>آگهی‌هایی که نشان‌ کرده‌اید، بدون نیاز به ثبت‌نام، اینجا ذخیره می‌شوند.</p>
                </div>
                <div class="moaveze-listings-grid" id="favorites-grid">
                    <div class="moaveze-loading"><div class="loading-spinner"></div></div>
                </div>
            </div>
        </div>
        <script>
        window.moavezeServerFavoriteIds = <?php echo wp_json_encode(array_map('intval', $server_ids)); ?>;
        </script>
        <?php
        return ob_get_clean();
    }
}

new Moaveze_Favorites();
