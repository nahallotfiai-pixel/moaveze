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

        // ROOT-CAUSE FIX, ATTEMPT 2 - kept as a harmless fallback: a
        // plain GET link handler (see handle_convert_via_link()). This
        // was the fix for a confirmed "400 Bad Request" on
        // admin-ajax.php's POST body. It is STILL wired up here, but
        // the site owner then reported this ALSO silently does nothing
        // (page just reloads, no notice, no conversion) - meaning
        // whatever is intercepting requests on this server is not
        // limited to admin-ajax.php POST bodies specifically; it
        // appears to silently strip unrecognized parameters from
        // requests more broadly (GET query args included), with no
        // error at all, which is why nothing visibly happened.
        add_action('admin_init', array($this, 'handle_convert_via_link'));

        // ROOT-CAUSE FIX, ATTEMPT 3 (the reliable one): rather than
        // inventing yet another custom request shape that the same
        // opaque interception layer could just as easily strip too,
        // piggyback on the ONE mechanism we know for certain already
        // works on this exact server: saving a Houzez property via its
        // own standard edit-screen "به‌روزرسانی/انتشار" button (the
        // site owner actively edits/saves properties through Houzez's
        // UI every day with no issue). A checkbox in the metabox below
        // is now submitted as part of that same, already-functioning
        // save request and processed here via the native save_post
        // hook - no AJAX, no custom query string, no separate request
        // of any kind that a filtering layer could target in isolation.
        add_action('save_post_property', array($this, 'handle_convert_via_metabox_save'));

        // ROOT-CAUSE FIX for "متاباکس معاوضه پلاس اصلاً دیده نمی‌شود،
        // حتی با لینک انکور": the site owner's screenshot showed the
        // sidebar 'side' context on this specific edit screen only
        // ever rendering a small, fixed set of boxes ("اطلاعات بیوار",
        // "ViraSEO") - our metabox never appeared there at all, and
        // the #moaveze_send_to_exchange anchor link did not even
        // scroll to it, strongly suggesting Houzez's customized post
        // editor renders/repositions 'side' metaboxes via its own JS
        // after the initial page load (so the anchor target doesn't
        // exist yet when the browser tries to jump to it - and our box
        // may not be getting rendered into that sidebar region by
        // Houzez's own template at all). Rather than fight an opaque,
        // JS-driven sidebar layout, render the SAME checkbox directly
        // into the main content column immediately after the post
        // title - a plain, server-side-rendered, universally supported
        // WordPress core hook that is guaranteed to be visible the
        // instant the page loads, no scrolling or JS timing involved.
        add_action('edit_form_after_title', array($this, 'render_convert_banner_after_title'));

        // ROOT-CAUSE FIX, ATTEMPT 4 - admin-post.php form handler:
        // admin-post.php is WordPress core's dedicated handler for
        // custom form actions via standard <form method="post">. Unlike
        // admin-ajax.php (which returned 400) or custom GET params
        // (which were silently stripped) or edit_form_after_title (which
        // Houzez's custom editor never fires), this is a simple HTML
        // form POST to a different, core-standard endpoint that
        // WordPress itself uses for settings forms, exports, etc.
        add_action('admin_post_moaveze_convert_property_form', array($this, 'handle_convert_form_submit'));
    }

    /**
     * Handle the standard HTML form submit from admin-post.php
     * (action=moaveze_convert_property_form).
     */
    public function handle_convert_form_submit() {
        $property_id = absint($_POST['property_id'] ?? 0);

        if (!$property_id || get_post_type($property_id) !== 'property') {
            wp_die('شناسه ملک نامعتبر است. <a href="' . esc_url(admin_url('admin.php?page=moaveze-properties')) . '">بازگشت</a>');
        }

        if (!current_user_can('manage_options')) {
            wp_die('دسترسی ندارید.');
        }

        check_admin_referer('moaveze_convert_property_' . $property_id, '_moaveze_convert_nonce');

        $redirect_url = admin_url('admin.php?page=moaveze-properties');

        // Already converted?
        $existing_exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
        if ($existing_exchange_id && get_post($existing_exchange_id)) {
            wp_safe_redirect(add_query_arg('moaveze_convert_notice', 'already', $redirect_url));
            exit;
        }

        // Do the conversion
        $result = $this->import_single_property($property_id);

        wp_safe_redirect(add_query_arg('moaveze_convert_notice', $result ? 'success' : 'failed', $redirect_url));
        exit;
    }

    /**
     * Renders the "تبدیل به معاوضه" checkbox/status directly under the
     * property title - see the edit_form_after_title hookup above for
     * why this replaced the sidebar metabox as the primary, reliable
     * home for this action.
     */
    public function render_convert_banner_after_title($post) {
        if (!$post || $post->post_type !== 'property') return;
        if (!current_user_can('manage_options')) return;

        $exchange_id = get_post_meta($post->ID, '_moaveze_exchange_linked', true);
        wp_nonce_field('moaveze_send_to_exchange', 'moaveze_send_to_exchange_nonce');
        ?>
        <div class="moaveze-convert-banner" style="margin:16px 0;padding:16px 20px;border-radius:10px;border:2px solid #6366f1;background:#eef2ff;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <?php if ($exchange_id && get_post($exchange_id)) : ?>
                <div style="display:flex;align-items:center;gap:8px;color:#065f46;font-weight:700;">
                    <span class="dashicons dashicons-yes-alt"></span> معاوضه پلاس: این ملک به آگهی معاوضه متصل است
                </div>
                <a href="<?php echo esc_url(get_edit_post_link($exchange_id)); ?>" class="button button-primary" target="_blank">
                    مشاهده آگهی معاوضه
                </a>
            <?php else : ?>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;color:#3730a3;flex:1;min-width:280px;">
                    <input type="checkbox" name="moaveze_convert_to_exchange" value="1" style="width:18px;height:18px;">
                    <span>
                        <span class="dashicons dashicons-randomize"></span>
                        معاوضه پلاس: این ملک را هنگام کلیک روی «به‌روزرسانی» به آگهی معاوضه تبدیل کن
                    </span>
                </label>
                <span style="color:#6366f1;font-size:12px;">تیک بزنید و سپس دکمه «به‌روزرسانی» بالای صفحه را کلیک کنید</span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Reliable, non-AJAX, non-custom-request conversion path: runs as
     * part of WordPress's own native post-save flow when a Houzez
     * property is saved via its normal edit screen "به‌روزرسانی" button
     * (see the save_post_property hookup in the constructor for why
     * this is the one mechanism guaranteed to actually fire on this
     * server, given the AJAX and GET-link attempts both failed
     * silently).
     */
    public function handle_convert_via_metabox_save($property_id) {
        // Skip autosaves/revisions - only act on a real, explicit save.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($property_id)) return;

        if (empty($_POST['moaveze_convert_to_exchange'])) return;
        if (!isset($_POST['moaveze_send_to_exchange_nonce']) ||
            !wp_verify_nonce($_POST['moaveze_send_to_exchange_nonce'], 'moaveze_send_to_exchange')) {
            return;
        }
        if (!current_user_can('manage_options')) return;

        // Already converted - nothing to do.
        $existing_exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
        if ($existing_exchange_id && get_post($existing_exchange_id)) return;

        $this->import_single_property($property_id);
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
                <?php
                // ROOT-CAUSE FIX: both a jQuery/admin-ajax.php button
                // AND a plain GET link were silently blocked/stripped
                // by something on this server before ever reaching our
                // PHP code (confirmed by the site owner: one showed a
                // hard "400 Bad Request" in DevTools, the other did
                // literally nothing but reload the page). This
                // checkbox instead rides along inside the SAME request
                // that Houzez's own native "به‌روزرسانی"/"انتشار" button
                // already submits every time - a request mechanism
                // already proven to work reliably on this exact site -
                // and is processed by handle_convert_via_metabox_save()
                // on the standard save_post_property hook.
                ?>
                <label style="display:flex;align-items:flex-start;gap:6px;margin-top:10px;cursor:pointer;">
                    <input type="checkbox" name="moaveze_convert_to_exchange" value="1" style="margin-top:3px;">
                    <span>
                        این ملک را هنگام ذخیره (کلیک روی «به‌روزرسانی») به آگهی معاوضه تبدیل کن
                    </span>
                </label>
                <p class="description" style="margin-top:6px;">
                    تیک را بزنید، سپس دکمه «به‌روزرسانی» بالای صفحه را کلیک کنید.
                </p>
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
