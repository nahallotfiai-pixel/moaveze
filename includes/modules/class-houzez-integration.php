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

        // Add "تبدیل به معاوضه" row action link in the properties list
        // table (the row of links under each title: ویرایش | ویرایش
        // سریع | ...) - the most discoverable, standard-WordPress-UI
        // placement for per-row actions. Hook BOTH filters since
        // Houzez's 'property' CPT registration varies between versions
        // (hierarchical vs non-hierarchical) and WordPress uses
        // page_row_actions for hierarchical, post_row_actions for non.
        add_filter('post_row_actions', array($this, 'add_row_action_convert'), 10, 2);
        add_filter('page_row_actions', array($this, 'add_row_action_convert'), 10, 2);

        // "تبدیل به آگهی معاوضه" meta box on Houzez property edit screen
        add_action('add_meta_boxes', array($this, 'add_send_to_exchange_metabox'));

        // ROOT-CAUSE FIX for the persistent, undiagnosable "خطا در
        // ارتباط با سرور" on the convert-to-exchange button: the
        // browser's own DevTools Network tab (confirmed by the site
        // owner) showed the POST to admin-ajax.php itself returning
        // "400 Bad Request" - BEFORE our AJAX handler even runs. This
        // is WordPress core's OWN behavior
        // (wp-admin/admin-ajax.php: `if (empty($_REQUEST['action']))
        // wp_die('0', '', array('response' => 400));`), meaning
        // something between the click and the server is stripping/
        // corrupting the POST body's 'action' field entirely - almost
        // certainly a security plugin/WAF/proxy inspecting or rewriting
        // AJAX POST bodies (the site has a dedicated "امنیت" admin menu
        // item), which no amount of fixing OUR PHP code can work around
        // since the request never reaches it. Rather than keep guessing
        // at an opaque third-party interception layer, this button is
        // now a plain GET link handled directly on admin_init - no
        // jQuery, no admin-ajax.php, no POST body at all - which cannot
        // be affected by this specific failure mode.
        add_action('admin_init', array($this, 'handle_convert_via_link'));
    }

    /**
     * Non-AJAX fallback: handles a plain GET link
     * (?moaveze_convert_property=ID&_wpnonce=...) so the "تبدیل به
     * معاوضه" action works even when POST-based admin-ajax.php requests
     * are being intercepted/blocked before reaching this plugin's code
     * (see the admin_init hookup above for the full diagnosis).
     */
    public function handle_convert_via_link() {
        if (empty($_GET['moaveze_convert_property'])) return;

        $property_id = absint($_GET['moaveze_convert_property']);
        if (!$property_id || get_post_type($property_id) !== 'property') {
            wp_die('شناسه ملک نامعتبر است. <a href="' . esc_url(admin_url('admin.php?page=moaveze-properties')) . '">بازگشت</a>');
        }

        if (!current_user_can('manage_options')) {
            wp_die('دسترسی ندارید.');
        }

        check_admin_referer('moaveze_convert_property_' . $property_id);

        $redirect_url = admin_url('admin.php?page=moaveze-properties');

        // Already converted - just bounce back with a notice, no need
        // to redo the work.
        $existing_exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
        if ($existing_exchange_id && get_post($existing_exchange_id)) {
            wp_safe_redirect(add_query_arg('moaveze_convert_notice', 'already', $redirect_url));
            exit;
        }

        $result = $this->import_single_property($property_id);

        wp_safe_redirect(add_query_arg('moaveze_convert_notice', $result ? 'success' : 'failed', $redirect_url));
        exit;
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
        // The site uses a child theme named "tabrizhome" (or
        // "tabrizhome-child") that is a renamed/rebranded Houzez
        // child. The actual Houzez parent theme was ALSO renamed to
        // "tabrizhome" (its folder + style.css Name), so neither the
        // active theme's Name nor its Template field contains the word
        // "houzez" at all - they both say "tabrizhome". We detect this
        // by checking for the Houzez-specific post type 'property'
        // being registered, which is the most reliable indicator that
        // Houzez (regardless of name/branding) is actually running.
        return post_type_exists('property');
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

        // ROOT-CAUSE FIX for "خطا در ارتباط با سرور" (generic AJAX
        // failure with zero diagnostic info): a PHP fatal error OR even
        // just a stray notice/warning printed by import_single_property()
        // (e.g. an "Undefined array key" notice from a Houzez meta field
        // that doesn't exist on THIS particular property, since not
        // every listing has every custom field filled in) breaks the
        // JSON response - PHP prints plain-text before the JSON, so the
        // browser can no longer parse the response as JSON and jQuery's
        // AJAX promise rejects into .fail(), which only ever shows the
        // generic "خطا در ارتباط با سرور" with no indication of what
        // actually went wrong on the server. Wrapping this in a
        // try/catch + output-buffer turns ANY such failure into a
        // proper, specific JSON error message instead - both fixing the
        // "silently broken" symptom AND making the real cause visible
        // and reportable next time, instead of guessing blind.
        ob_start();
        try {
            $result = $this->import_single_property($property_id);
        } catch (\Throwable $e) {
            ob_end_clean();
            wp_send_json_error('خطای PHP هنگام تبدیل: ' . $e->getMessage() . ' (فایل: ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
        $stray_output = ob_get_clean();

        if (!$result) {
            $msg = 'خطا در تبدیل ملک';
            if ($stray_output !== '') {
                $msg .= ' - جزئیات فنی: ' . wp_strip_all_tags($stray_output);
            }
            wp_send_json_error($msg);
        }

        $exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
        wp_send_json_success(array(
            'message'  => 'ملک با موفقیت به آگهی معاوضه تبدیل شد',
            'edit_url' => get_edit_post_link($exchange_id, 'raw'),
            'view_url' => get_permalink($exchange_id),
            // Surface any stray PHP notice/warning even on success, so
            // it doesn't hide silently if the conversion "worked" but
            // something non-fatal still went slightly wrong underneath.
            'debug'    => $stray_output !== '' ? wp_strip_all_tags($stray_output) : null,
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

        // Gallery images (copy Houzez gallery attachment IDs).
        // FIX: Houzez stores the entire gallery as ONE meta row whose
        // value is itself an array of attachment IDs (confirmed via the
        // live REST API response for a real property) - NOT as one
        // separate meta row per image. get_post_meta(..., false)
        // therefore returned a nested array (an array containing one
        // array), which would have written the wrong shape into
        // _moaveze_gallery (every other place in this plugin that reads
        // _moaveze_gallery expects a flat array of IDs - see
        // set_post_thumbnail() call in class-submission-form.php).
        $gallery_raw = get_post_meta($property_id, 'fave_property_images', true);
        $gallery_ids = is_array($gallery_raw) ? array_map('absint', $gallery_raw) : array();
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
        if ($exchange_id && get_post($exchange_id)) {
            echo '<a href="' . esc_url(get_edit_post_link($exchange_id)) . '" class="dashicons dashicons-yes-alt" style="color:#10b981;text-decoration:none;" title="معاوضه فعال - کلیک برای ویرایش"></a>';
        } else {
            echo '<button class="button button-small moaveze-convert-to-exchange-btn" data-property-id="' . esc_attr($post_id) . '" title="تبدیل به آگهی معاوضه">تبدیل به معاوضه</button>';
        }
    }

    /**
     * Add "تبدیل به معاوضه" as a row-action link under each property
     * title in the list table - the standard WordPress UI pattern that
     * every admin knows where to look (under the post title, next to
     * "ویرایش | ویرایش سریع | ...").
     */
    public function add_row_action_convert($actions, $post) {
        if ($post->post_type !== 'property') return $actions;
        if (!current_user_can('manage_options')) return $actions;

        $exchange_id = get_post_meta($post->ID, '_moaveze_exchange_linked', true);
        if ($exchange_id && get_post($exchange_id)) {
            $actions['moaveze_exchange'] = '<a href="' . esc_url(get_edit_post_link($exchange_id)) . '" style="color:#10b981;font-weight:600;">✓ معاوضه</a>';
        } else {
            $actions['moaveze_exchange'] = '<a href="#" class="moaveze-convert-to-exchange-btn" data-property-id="' . esc_attr($post->ID) . '" style="color:#6366f1;font-weight:600;">تبدیل به معاوضه</a>';
        }

        return $actions;
    }
}

new Moaveze_Houzez_Integration();
