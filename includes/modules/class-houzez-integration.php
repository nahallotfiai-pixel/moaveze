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
        add_action('wp_ajax_moaveze_convert_to_exchange', array($this, 'ajax_convert_to_exchange'));
        add_filter('the_content', array($this, 'add_exchange_badge_to_content'), 20);
        add_action('houzez_after_property_title', array($this, 'show_exchange_badge'));
        add_filter('manage_property_posts_columns', array($this, 'add_exchange_column'));
        add_action('manage_property_posts_custom_column', array($this, 'exchange_column_content'), 10, 2);

        // "تبدیل به آگهی معاوضه" meta box on Houzez property edit screen
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
     * AJAX: "تبدیل به آگهی معاوضه" button clicked on a Houzez property
     * edit screen - creates a full moaveze_exchange post from the
     * property's data (price, area, rooms, year, floor, features,
     * gallery, location) and links the two posts together.
     */
    public function ajax_convert_to_exchange() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $property_id = absint($_POST['property_id'] ?? 0);
        if (!$property_id || get_post_type($property_id) !== 'property') {
            wp_send_json_error('شناسه ملک نامعتبر است');
        }

        // Check if already converted
        $existing_exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
        if ($existing_exchange_id && get_post($existing_exchange_id)) {
            wp_send_json_error(array(
                'message'  => 'این ملک قبلاً به سیستم معاوضه تبدیل شده است',
                'edit_url' => get_edit_post_link($existing_exchange_id, 'raw'),
            ));
        }

        $result = $this->import_single_property($property_id);
        if (!$result) {
            wp_send_json_error('خطا در تبدیل ملک');
        }

        $exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
        wp_send_json_success(array(
            'message'  => 'ملک با موفقیت به آگهی معاوضه تبدیل شد',
            'edit_url' => get_edit_post_link($exchange_id, 'raw'),
            'view_url' => get_permalink($exchange_id),
        ));
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

        // Year built (Houzez meta key: fave_property_year)
        $year_built = get_post_meta($property_id, 'fave_property_year', true);
        if ($year_built) {
            update_post_meta($exchange_id, '_moaveze_year_built', sanitize_text_field($year_built));
        }

        // Floor (Houzez custom field - stored as "X از Y")
        $floor_raw = get_post_meta($property_id, 'fave_f5eb6c866568d9', true);
        if ($floor_raw) {
            // Try to parse "X از Y" format
            if (preg_match('/(\d+)\s*از\s*(\d+)/u', $floor_raw, $m)) {
                update_post_meta($exchange_id, '_moaveze_floor', $m[1]);
                update_post_meta($exchange_id, '_moaveze_total_floors', $m[2]);
            } else {
                update_post_meta($exchange_id, '_moaveze_floor', sanitize_text_field($floor_raw));
            }
        }

        // Features: map Houzez property_feature taxonomy terms to
        // moaveze_feature taxonomy (creating terms if they don't exist)
        $houzez_features = get_the_terms($property_id, 'property_feature');
        if ($houzez_features && !is_wp_error($houzez_features)) {
            $feature_names = wp_list_pluck($houzez_features, 'name');
            foreach ($feature_names as $fname) {
                // Find or create the equivalent moaveze_feature term
                $existing = get_term_by('name', $fname, 'moaveze_feature');
                if (!$existing) {
                    wp_insert_term($fname, 'moaveze_feature');
                }
            }
            wp_set_object_terms($exchange_id, $feature_names, 'moaveze_feature');
        }

        // Gallery images (copy Houzez gallery attachment IDs)
        $gallery_ids = get_post_meta($property_id, 'fave_property_images', false);
        if (!empty($gallery_ids)) {
            update_post_meta($exchange_id, '_moaveze_gallery', $gallery_ids);
        }

        // Map Houzez property_type → moaveze_property_type
        $houzez_type = get_the_terms($property_id, 'property_type');
        if ($houzez_type && !is_wp_error($houzez_type)) {
            $type_name = $houzez_type[0]->name;
            $moaveze_type = get_term_by('name', $type_name, 'moaveze_property_type');
            if (!$moaveze_type) {
                $inserted = wp_insert_term($type_name, 'moaveze_property_type');
                if (!is_wp_error($inserted)) {
                    $moaveze_type = get_term($inserted['term_id'], 'moaveze_property_type');
                }
            }
            if ($moaveze_type) {
                wp_set_object_terms($exchange_id, array($moaveze_type->term_id), 'moaveze_property_type');
            }
        }

        // Map Houzez property_area → moaveze_district
        $houzez_area = get_the_terms($property_id, 'property_area');
        if ($houzez_area && !is_wp_error($houzez_area)) {
            $area_name = $houzez_area[0]->name;
            $moaveze_district = get_term_by('name', $area_name, 'moaveze_district');
            if (!$moaveze_district) {
                $inserted = wp_insert_term($area_name, 'moaveze_district');
                if (!is_wp_error($inserted)) {
                    $moaveze_district = get_term($inserted['term_id'], 'moaveze_district');
                }
            }
            if ($moaveze_district) {
                wp_set_object_terms($exchange_id, array($moaveze_district->term_id), 'moaveze_district');
            }
        }

        // Link back
        update_post_meta($property_id, '_moaveze_exchange_linked', $exchange_id);

        // Insert into moaveze_exchanges table so the matching algorithm
        // can find and compare this listing with others.
        global $wpdb;
        $type_name = '';
        if ($houzez_type && !is_wp_error($houzez_type)) $type_name = $houzez_type[0]->name;
        $district_name = '';
        if ($houzez_area && !is_wp_error($houzez_area)) $district_name = $houzez_area[0]->name;

        $wpdb->insert(
            $wpdb->prefix . 'moaveze_exchanges',
            array(
                'post_id'        => $exchange_id,
                'user_id'        => $property->post_author,
                'property_type'  => $type_name,
                'property_value' => absint($price),
                'area_sqm'       => absint($size),
                'rooms'          => absint($rooms),
                'exchange_type'  => 'flexible',
                'district'       => $district_name,
                'status'         => 'active',
                'verified'       => 1,
            )
        );

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
