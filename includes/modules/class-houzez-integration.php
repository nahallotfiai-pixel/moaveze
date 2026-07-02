<?php
/**
 * Houzez Theme Integration
 * Connects the exchange system with Houzez theme listings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Houzez_Integration {

    public function __construct() {
        // Only run if Houzez is active
        if (!$this->is_houzez_active()) return;

        add_action('wp_ajax_moaveze_import_from_houzez', array($this, 'import_from_houzez'));
        add_filter('the_content', array($this, 'add_exchange_badge_to_content'), 20);
        add_action('houzez_after_property_title', array($this, 'show_exchange_badge'));
        add_filter('manage_property_posts_columns', array($this, 'add_exchange_column'));
        add_action('manage_property_posts_custom_column', array($this, 'exchange_column_content'), 10, 2);

        // NEW: "Send to Exchange" meta box directly on the single Houzez
        // property edit screen. Previously the ONLY way to link a property
        // to the exchange system was the small button in the admin LIST
        // column - there was no way to do it from inside the property's
        // own edit screen at all.
        add_action('add_meta_boxes', array($this, 'add_send_to_exchange_metabox'));
    }

    /**
     * Register the "ارسال به معاوضه" meta box on the Houzez property
     * edit screen.
     */
    public function add_send_to_exchange_metabox() {
        add_meta_box(
            'moaveze_send_to_exchange',
            'معاوضه پلاس',
            array($this, 'render_send_to_exchange_metabox'),
            'property',
            'side',
            'high'
        );
    }

    /**
     * Render the metabox content: either a "already linked" status with a
     * link to the exchange listing, or a button to create the link.
     */
    public function render_send_to_exchange_metabox($post) {
        $exchange_id = get_post_meta($post->ID, '_moaveze_exchange_linked', true);
        wp_nonce_field('moaveze_send_to_exchange', 'moaveze_send_to_exchange_nonce');
        ?>
        <div class="moaveze-houzez-metabox">
            <?php if ($exchange_id && get_post($exchange_id)) : ?>
                <p class="status-linked">
                    <span class="dashicons dashicons-yes-alt"></span> این ملک به معاوضه متصل است
                </p>
                <a href="<?php echo esc_url(get_edit_post_link($exchange_id)); ?>" class="button button-small" target="_blank">
                    مشاهده آگهی معاوضه
                </a>
            <?php else : ?>
                <p class="status-not-linked">
                    این ملک هنوز در سیستم معاوضه ثبت نشده است.
                </p>
                <button type="button" class="button button-primary button-small moaveze-send-to-exchange-btn" data-property-id="<?php echo esc_attr($post->ID); ?>">
                    <span class="dashicons dashicons-randomize"></span> ارسال به معاوضه
                </button>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Check if Houzez theme is active
     */
    private function is_houzez_active() {
        $theme = wp_get_theme();
        return (
            strpos(strtolower($theme->get('Name')), 'houzez') !== false ||
            strpos(strtolower($theme->get('Template')), 'houzez') !== false
        );
    }

    /**
     * Import a Houzez property to exchange system
     */
    public function import_from_houzez() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $property_id = absint($_POST['property_id'] ?? 0);

        if (!$property_id) {
            // Bulk import
            $properties = get_posts(array(
                'post_type'      => 'property',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            ));

            $imported = 0;
            foreach ($properties as $property) {
                if ($this->import_single_property($property->ID)) {
                    $imported++;
                }
            }

            wp_send_json_success(array(
                'message' => sprintf('%d ملک با موفقیت وارد شد', $imported),
                'count'   => $imported,
            ));
        } else {
            if ($this->import_single_property($property_id)) {
                wp_send_json_success(array('message' => 'ملک با موفقیت وارد شد'));
            } else {
                wp_send_json_error(array('message' => 'خطا در واردسازی'));
            }
        }
    }

    /**
     * Import a single Houzez property
     */
    private function import_single_property($property_id) {
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') return false;

        // Check if already imported
        $existing = get_posts(array(
            'post_type'  => 'moaveze_exchange',
            'meta_key'   => '_moaveze_houzez_source_id',
            'meta_value' => $property_id,
        ));
        if (!empty($existing)) return false;

        // Get Houzez meta
        $price = get_post_meta($property_id, 'fave_property_price', true);
        $size = get_post_meta($property_id, 'fave_property_size', true);
        $rooms = get_post_meta($property_id, 'fave_property_rooms', true);
        $lat = get_post_meta($property_id, 'fave_property_location', true);
        $address = get_post_meta($property_id, 'fave_property_map_address', true);

        // Parse lat/lng from Houzez format
        $coords = explode(',', $lat);
        $latitude = isset($coords[0]) ? trim($coords[0]) : '';
        $longitude = isset($coords[1]) ? trim($coords[1]) : '';

        // Create exchange post
        $exchange_id = wp_insert_post(array(
            'post_title'   => $property->post_title,
            'post_content' => $property->post_content,
            'post_type'    => 'moaveze_exchange',
            'post_status'  => 'publish',
            'post_author'  => $property->post_author,
        ));

        if (is_wp_error($exchange_id)) return false;

        // Copy thumbnail
        $thumb_id = get_post_thumbnail_id($property_id);
        if ($thumb_id) {
            set_post_thumbnail($exchange_id, $thumb_id);
        }

        // Save meta
        update_post_meta($exchange_id, '_moaveze_property_value', absint($price));
        update_post_meta($exchange_id, '_moaveze_area_sqm', absint($size));
        update_post_meta($exchange_id, '_moaveze_rooms', absint($rooms));
        update_post_meta($exchange_id, '_moaveze_latitude', $latitude);
        update_post_meta($exchange_id, '_moaveze_longitude', $longitude);
        update_post_meta($exchange_id, '_moaveze_address', $address);
        update_post_meta($exchange_id, '_moaveze_exchange_type', 'flexible');
        update_post_meta($exchange_id, '_moaveze_houzez_source_id', $property_id);
        update_post_meta($exchange_id, '_moaveze_verified', '1');

        // Link back
        update_post_meta($property_id, '_moaveze_exchange_linked', $exchange_id);

        return true;
    }

    /**
     * Add exchange badge to Houzez property content
     *
     * FIX: this used to check the option 'moaveze_show_in_houzez', but the
     * Integration settings tab actually saves/registers a DIFFERENT option
     * key: 'moaveze_houzez_sync'. Toggling "همگام‌سازی با Houzez" in
     * Settings > یکپارچه‌سازی therefore had NO effect on this badge at all -
     * it always used whatever the default value of the unrelated option
     * happened to be. Now both checks use the same 'moaveze_houzez_sync' key
     * that the settings UI actually saves.
     */
    public function add_exchange_badge_to_content($content) {
        if (!is_singular('property')) return $content;
        if (get_option('moaveze_houzez_sync') !== 'yes') return $content;

        $exchange_id = get_post_meta(get_the_ID(), '_moaveze_exchange_linked', true);
        if (!$exchange_id) return $content;

        $badge = '<div class="moaveze-houzez-badge">';
        $badge .= '<a href="' . get_permalink($exchange_id) . '" class="moaveze-exchange-link">';
        $badge .= '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>';
        $badge .= ' امکان معاوضه | مشاهده شرایط';
        $badge .= '</a></div>';

        return $badge . $content;
    }

    /**
     * Show exchange badge on Houzez listing (archive/listing cards).
     * This one correctly checks the dedicated 'moaveze_exchange_badge'
     * display-tab option (separate concern from the sync toggle above) -
     * left as-is, just documenting the distinction so it's not confused
     * with the sync-option bug fixed above.
     */
    public function show_exchange_badge() {
        if (get_option('moaveze_exchange_badge') !== 'yes') return;
        if (get_option('moaveze_houzez_sync') !== 'yes') return;

        $exchange_id = get_post_meta(get_the_ID(), '_moaveze_exchange_linked', true);
        if (!$exchange_id) return;

        echo '<span class="moaveze-inline-badge">قابل معاوضه</span>';
    }

    /**
     * Add exchange column to Houzez properties list
     */
    public function add_exchange_column($columns) {
        $columns['moaveze_exchange'] = 'معاوضه';
        return $columns;
    }

    /**
     * Exchange column content
     */
    public function exchange_column_content($column, $post_id) {
        if ($column !== 'moaveze_exchange') return;

        $exchange_id = get_post_meta($post_id, '_moaveze_exchange_linked', true);
        if ($exchange_id) {
            echo '<span class="dashicons dashicons-yes-alt" style="color:#10b981;" title="معاوضه فعال"></span>';
        } else {
            echo '<button class="button button-small moaveze-add-to-exchange" data-property-id="' . esc_attr($post_id) . '">افزودن</button>';
        }
    }
}

new Moaveze_Houzez_Integration();
