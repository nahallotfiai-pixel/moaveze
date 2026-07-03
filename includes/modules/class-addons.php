<?php
/**
 * Add-on Features
 * Report Listing, Compare Listings (data endpoint), QR Code (data endpoint)
 * Each feature is independently toggled from Settings > امکانات تکمیلی.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Addons {

    public function __construct() {
        add_action('wp_ajax_moaveze_report_listing', array($this, 'ajax_report_listing'));
        add_action('wp_ajax_nopriv_moaveze_report_listing', array($this, 'ajax_report_listing'));
        add_action('wp_ajax_moaveze_get_compare_data', array($this, 'ajax_get_compare_data'));
        add_action('wp_ajax_nopriv_moaveze_get_compare_data', array($this, 'ajax_get_compare_data'));

        // Admin: reports list page under the main menu
        add_action('admin_menu', array($this, 'add_reports_menu'), 20);
    }

    /**
     * AJAX: record a "گزارش تخلف" submission from a visitor, and
     * notify admins so they can review it quickly.
     */
    public function ajax_report_listing() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (get_option('moaveze_addon_report_listing') !== 'yes') {
            wp_send_json_error('این قابلیت غیرفعال است');
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $details = sanitize_textarea_field($_POST['details'] ?? '');

        if (!$post_id || get_post_type($post_id) !== 'moaveze_exchange') {
            wp_send_json_error('آگهی نامعتبر است');
        }

        $reports = get_post_meta($post_id, '_moaveze_reports', true);
        $reports = is_array($reports) ? $reports : array();
        $reports[] = array(
            'reason'    => $reason,
            'details'   => $details,
            'user_id'   => get_current_user_id(),
            'ip'        => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'date'      => current_time('mysql'),
        );
        update_post_meta($post_id, '_moaveze_reports', $reports);
        update_post_meta($post_id, '_moaveze_reports_count', count($reports));

        // Notify admins
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $notif = new Moaveze_Notifications();
            $notif->send(
                $admin->ID,
                'system',
                'گزارش تخلف روی یک آگهی',
                sprintf('آگهی «%s» توسط یک کاربر گزارش شد. دلیل: %s', get_the_title($post_id), $reason ?: 'نامشخص'),
                array('post_id' => $post_id)
            );
        }

        wp_send_json_success(array('message' => 'گزارش شما ثبت شد و توسط تیم بررسی خواهد شد. سپاسگزاریم.'));
    }

    /**
     * AJAX: fetch comparable spec data for 2-3 listings selected via
     * the compare checkboxes, for the [moaveze compare] table.
     */
    public function ajax_get_compare_data() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (get_option('moaveze_addon_compare_listings') !== 'yes') {
            wp_send_json_error('این قابلیت غیرفعال است');
        }

        $ids = isset($_POST['ids']) ? array_map('absint', (array) $_POST['ids']) : array();
        $ids = array_slice(array_filter($ids), 0, 3); // max 3 listings

        if (empty($ids)) wp_send_json_success(array('items' => array()));

        $items = array();
        foreach ($ids as $id) {
            if (get_post_type($id) !== 'moaveze_exchange' || get_post_status($id) !== 'publish') continue;

            $type_terms = get_the_terms($id, 'moaveze_property_type');
            $district_terms = get_the_terms($id, 'moaveze_district');
            $feature_terms = wp_get_post_terms($id, 'moaveze_feature', array('fields' => 'names'));
            $value = (int) get_post_meta($id, '_moaveze_property_value', true);
            $area = (int) get_post_meta($id, '_moaveze_area_sqm', true);

            $items[] = array(
                'id'          => $id,
                'title'       => get_the_title($id),
                'url'         => get_permalink($id),
                'image'       => get_the_post_thumbnail_url($id, 'medium'),
                'value'       => $value,
                'value_short' => Moaveze_Helpers::short_price($value),
                'area'        => $area,
                'price_per_sqm' => $area ? Moaveze_Helpers::short_price(round($value / $area), false) : '—',
                'rooms'       => get_post_meta($id, '_moaveze_rooms', true) ?: '—',
                'floor'       => get_post_meta($id, '_moaveze_floor', true) ?: '—',
                'year_built'  => get_post_meta($id, '_moaveze_year_built', true) ?: '—',
                'type'        => $type_terms ? $type_terms[0]->name : '—',
                'district'    => $district_terms ? $district_terms[0]->name : '—',
                'features'    => $feature_terms,
                'verified'    => (bool) get_post_meta($id, '_moaveze_verified', true),
            );
        }

        wp_send_json_success(array('items' => $items));
    }

    /**
     * Admin submenu: list all reported listings for quick review.
     */
    public function add_reports_menu() {
        add_submenu_page(
            'moaveze-plus',
            'گزارش‌های تخلف',
            'گزارش‌های تخلف',
            'manage_options',
            'moaveze-reports',
            array($this, 'render_reports_page')
        );
    }

    public function render_reports_page() {
        $posts = get_posts(array(
            'post_type'   => 'moaveze_exchange',
            'numberposts' => -1,
            'meta_key'    => '_moaveze_reports_count',
            'orderby'     => 'meta_value_num',
            'order'       => 'DESC',
            'meta_query'  => array(array('key' => '_moaveze_reports_count', 'value' => 0, 'compare' => '>')),
        ));
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-flag"></span> گزارش‌های تخلف آگهی‌ها</h1>
            <?php if (empty($posts)) : ?>
                <p>هیچ گزارشی ثبت نشده است.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>آگهی</th><th>تعداد گزارش</th><th>آخرین دلیل</th><th>عملیات</th></tr></thead>
                    <tbody>
                    <?php foreach ($posts as $post) :
                        $reports = get_post_meta($post->ID, '_moaveze_reports', true) ?: array();
                        $last = end($reports);
                    ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></td>
                            <td><?php echo count($reports); ?></td>
                            <td><?php echo esc_html($last['reason'] ?? '—'); ?></td>
                            <td><a href="<?php echo get_permalink($post->ID); ?>" target="_blank" class="button button-small">مشاهده آگهی</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}

new Moaveze_Addons();
