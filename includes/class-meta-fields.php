<?php
/**
 * Meta Fields Handler
 * Manages all custom meta fields for exchange listings
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Meta_Fields {

    /**
     * Initialize
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_moaveze_exchange', array(__CLASS__, 'save_meta'));
        add_action('pre_get_posts', array(__CLASS__, 'hide_private_reciprocal_listings'));
        add_action('wp_ajax_moaveze_repair_feature_terms', array(__CLASS__, 'ajax_repair_feature_terms'));
        add_action('wp_ajax_moaveze_toggle_feature', array(__CLASS__, 'ajax_toggle_feature'));
        add_action('wp_ajax_moaveze_repair_type_district_terms', array(__CLASS__, 'ajax_repair_type_district_terms'));
    }

    /**
     * ONE-TIME REPAIR TOOL (dashboard button): fixes listings affected
     * by the "نوع ملک درج نمیشه" bug - wp_set_object_terms() was called
     * with the raw SLUG from the property-type/district <select>
     * (e.g. "apartment" or a percent-encoded Persian slug) instead of
     * resolving it to the real term first. That call treats a plain
     * string as a term NAME to match-or-CREATE, so on any listing where
     * the slug didn't happen to exactly equal an existing term's name,
     * WordPress silently created a brand-new garbage term (literally
     * named after the slug) and attached THAT instead of the real,
     * correctly-seeded Persian term - which is why the listing then
     * showed no proper نوع ملک/منطقه at all (the garbage term's "name"
     * being an obscure/empty-looking slug string is easy to miss in
     * templates that only ever render $terms[0]->name).
     *
     * For every moaveze_property_type / moaveze_district term whose
     * slug is NOT the normal WordPress sanitize_title() of its own
     * name (i.e. it looks like it was created FROM a slug rather than a
     * real name - a strong signal something went through the buggy
     * code path), this:
     *   1. Tries to find the "real" term with a matching slug pattern
     *      is not reliable, so instead this simply reports every such
     *      suspicious term for manual admin review rather than guessing
     *      a destructive auto-fix (property type text can't be safely
     *      reverse-engineered from a slug alone in every case).
     * It ALSO fixes the more common, safe case: posts that have ZERO
     * moaveze_property_type or moaveze_district terms at all (the
     * garbage term was created but for some reason not attached, or the
     * field was left completely empty) - these are just listed so the
     * admin can quickly open and re-save them from wp-admin, where the
     * dropdown will now save correctly thanks to the code fix.
     */
    public static function ajax_repair_type_district_terms() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $suspicious_terms = array();
        foreach (array('moaveze_property_type', 'moaveze_district') as $taxonomy) {
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
            if (is_wp_error($terms)) continue;
            foreach ($terms as $term) {
                // A term created BY THE BUG has, as its "name", the raw
                // string that was submitted as a <select> VALUE - which
                // is that same term's own slug. For Persian-language
                // terms, WordPress stores taxonomy slugs as raw percent-
                // encoded UTF-8 (e.g. "%d8%a2%d9%be..." for "آپارتمان" -
                // the same encoding visible in this site's own exchange/
                // listing permalinks), so a garbage term's "name" looks
                // like a %XX-encoded string or a plain ascii slug -
                // never real Persian text. Also flag exact name===slug
                // matches (covers any edge case with ASCII-only slugs).
                $looks_like_raw_slug = (bool) preg_match('/%[0-9a-f]{2}/i', $term->name)
                    || preg_match('/^[a-z0-9\-_]+$/i', $term->name);
                if ($term->name === $term->slug || $looks_like_raw_slug) {
                    $suspicious_terms[] = array(
                        'taxonomy' => $taxonomy,
                        'term_id'  => $term->term_id,
                        'name'     => $term->name,
                        'count'    => $term->count,
                        'edit_url' => admin_url("edit-tags.php?action=edit&taxonomy={$taxonomy}&tag_ID={$term->term_id}&post_type=moaveze_exchange"),
                    );
                }
            }
        }

        $missing_type_or_district = get_posts(array(
            'post_type'   => 'moaveze_exchange',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields'      => 'ids',
            'tax_query'   => array(
                'relation' => 'OR',
                array('taxonomy' => 'moaveze_property_type', 'operator' => 'NOT EXISTS'),
                array('taxonomy' => 'moaveze_district', 'operator' => 'NOT EXISTS'),
            ),
        ));

        wp_send_json_success(array(
            'suspicious_terms'  => $suspicious_terms,
            'missing_posts'     => array_map(function ($id) {
                return array('id' => $id, 'title' => get_the_title($id), 'edit_url' => get_edit_post_link($id, 'raw'));
            }, $missing_type_or_district),
            'message' => sprintf(
                '%d ترم مشکوک (شبیه به slug) و %d آگهی بدون نوع ملک/منطقه پیدا شد.',
                count($suspicious_terms),
                count($missing_type_or_district)
            ),
        ));
    }

    /**
     * Purge any known full-page/object caching plugin for a single
     * listing after its data changes. This exists because on a
     * (very common) Iran-hosted WordPress setup, a caching plugin such
     * as LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache, or
     * SG Optimizer can keep serving the OLD rendered HTML of a listing
     * page for hours/days even though the underlying taxonomy/meta was
     * already updated correctly - which looks IDENTICAL, from the
     * site owner's point of view, to "the checkbox fix doesn't work".
     * This is deliberately defensive (checks function/class existence)
     * so it's a no-op and harmless on sites with no cache plugin.
     */
    public static function purge_listing_cache($post_id) {
        clean_post_cache($post_id);
        wp_cache_delete($post_id, 'post_meta');

        $permalink = get_permalink($post_id);

        // LiteSpeed Cache
        if (class_exists('LiteSpeed\Purge')) {
            \LiteSpeed\Purge::purge_post($post_id);
        } elseif (function_exists('do_action')) {
            do_action('litespeed_purge_post', $post_id);
        }

        // WP Rocket
        if (function_exists('rocket_clean_post')) {
            rocket_clean_post($post_id);
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_post')) {
            w3tc_flush_post($post_id);
        }

        // WP Super Cache
        if (function_exists('wp_cache_post_change')) {
            wp_cache_post_change($post_id);
        }
        if ($permalink && function_exists('wpsc_delete_url_cache')) {
            wpsc_delete_url_cache($permalink);
        }

        // SiteGround Optimizer
        if (class_exists('SiteGround_Optimizer\Supercacher\Supercacher')) {
            \SiteGround_Optimizer\Supercacher\Supercacher::purge_cache_request();
        }

        // Generic: let any other caching plugin hook into this action.
        do_action('moaveze_purge_listing_cache', $post_id, $permalink);
    }

    /**
     * AJAX: instant single-feature toggle - clicking a feature checkbox
     * in the wp-admin metabox saves it immediately via AJAX instead of
     * waiting for the admin to click "به‌روزرسانی"/"Update" on the whole
     * post. This removes an entire class of "I checked the box but it
     * still doesn't show" reports that were actually just the admin not
     * having saved the post yet, or the Update click failing silently
     * for an unrelated reason elsewhere on the screen.
     */
    public static function ajax_toggle_feature() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $key = sanitize_key($_POST['feature_key'] ?? '');
        $checked = !empty($_POST['checked']) && $_POST['checked'] !== 'false';

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('دسترسی ندارید');
        }

        $feature_map = self::get_feature_taxonomy_map();
        if (!isset($feature_map[$key])) {
            wp_send_json_error('ویژگی نامعتبر است');
        }

        update_post_meta($post_id, '_moaveze_' . $key, $checked ? '1' : '0');

        // Re-sync the FULL feature term list from all boolean meta (not
        // just this one key), so this stays perfectly consistent with
        // what save_meta() does on a full post save.
        $active_feature_terms = array();
        foreach ($feature_map as $k => $term_name) {
            if (get_post_meta($post_id, '_moaveze_' . $k, true) === '1') {
                $active_feature_terms[] = $term_name;
            }
        }
        wp_set_object_terms($post_id, $active_feature_terms, 'moaveze_feature', false);
        self::purge_listing_cache($post_id);

        // Return the just-verified state read fresh from the DB (not
        // just an echo of what was sent) - this is also used by the
        // "وضعیت فعلی" diagnostic readout in the metabox to prove the
        // save actually happened.
        $fresh_terms = wp_list_pluck(get_the_terms($post_id, 'moaveze_feature') ?: array(), 'name');
        wp_send_json_success(array(
            'message'      => $checked ? 'ذخیره شد: ' . $feature_map[$key] . ' فعال شد' : 'ذخیره شد: ' . $feature_map[$key] . ' غیرفعال شد',
            'active_terms' => $fresh_terms,
        ));
    }

    /**
     * Get all meta field keys
     */
    public static function get_fields() {
        return array(
            // Property Details
            'property_value'       => array('type' => 'number', 'label' => 'ارزش ملک (تومان)'),
            'area_sqm'             => array('type' => 'number', 'label' => 'متراژ (متر مربع)'),
            'rooms'                => array('type' => 'number', 'label' => 'تعداد اتاق'),
            'floor'                => array('type' => 'number', 'label' => 'طبقه'),
            'total_floors'         => array('type' => 'number', 'label' => 'تعداد طبقات'),
            'year_built'           => array('type' => 'number', 'label' => 'سال ساخت'),
            'parking'              => array('type' => 'checkbox', 'label' => 'پارکینگ'),
            'elevator'             => array('type' => 'checkbox', 'label' => 'آسانسور'),
            'storage'              => array('type' => 'checkbox', 'label' => 'انباری'),
            'balcony'              => array('type' => 'checkbox', 'label' => 'بالکن'),
            // NEW: these two existed as checkbox options in the FRONTEND
            // submission form (see class-submission-form.php) but had no
            // corresponding editable field in the wp-admin metabox at
            // all, so admins had no way to toggle them for a listing.
            'pool'                 => array('type' => 'checkbox', 'label' => 'استخر'),
            'security'             => array('type' => 'checkbox', 'label' => 'نگهبانی'),

            // Location
            'latitude'             => array('type' => 'text', 'label' => 'عرض جغرافیایی'),
            'longitude'            => array('type' => 'text', 'label' => 'طول جغرافیایی'),
            'address'              => array('type' => 'textarea', 'label' => 'آدرس'),

            // Exchange Conditions
            'exchange_type'        => array('type' => 'select', 'label' => 'نوع معاوضه'),
            'desired_property_type' => array('type' => 'select', 'label' => 'نوع ملک مورد نظر'),
            'desired_min_value'    => array('type' => 'number', 'label' => 'حداقل ارزش مورد نظر'),
            'desired_max_value'    => array('type' => 'number', 'label' => 'حداکثر ارزش مورد نظر'),
            'cash_difference'      => array('type' => 'number', 'label' => 'مابه‌التفاوت نقدی'),
            'cash_direction'       => array('type' => 'select', 'label' => 'جهت مابه‌التفاوت'),
            'additional_assets'    => array('type' => 'textarea', 'label' => 'دارایی‌های اضافی'),

            // Contact (Private)
            'contact_name'         => array('type' => 'text', 'label' => 'نام مالک', 'private' => true),
            'contact_phone'        => array('type' => 'text', 'label' => 'شماره تماس', 'private' => true),
            'contact_email'        => array('type' => 'email', 'label' => 'ایمیل', 'private' => true),

            // Status
            'verified'             => array('type' => 'checkbox', 'label' => 'تأیید شده'),
            'featured'             => array('type' => 'checkbox', 'label' => 'ویژه'),
        );
    }


    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'moaveze_property_details',
            'مشخصات ملک',
            array(__CLASS__, 'render_property_details_box'),
            'moaveze_exchange',
            'normal',
            'high'
        );

        add_meta_box(
            'moaveze_exchange_conditions',
            'شرایط معاوضه',
            array(__CLASS__, 'render_exchange_conditions_box'),
            'moaveze_exchange',
            'normal',
            'high'
        );

        add_meta_box(
            'moaveze_contact_info',
            'اطلاعات تماس (محرمانه)',
            array(__CLASS__, 'render_contact_info_box'),
            'moaveze_exchange',
            'side',
            'high'
        );

        add_meta_box(
            'moaveze_status_box',
            'وضعیت آگهی',
            array(__CLASS__, 'render_status_box'),
            'moaveze_exchange',
            'side',
            'default'
        );
    }

    /**
     * Maps a feature meta-field key to its Persian taxonomy term name.
     * This is the single source of truth used both when SAVING (to sync
     * checkboxes -> moaveze_feature terms) and when RENDERING the admin
     * checkboxes (to read the correct checked state whether the listing
     * was created via wp-admin or via the frontend submission form).
     */
    private static function get_feature_taxonomy_map() {
        return array(
            'parking'  => 'پارکینگ',
            'elevator' => 'آسانسور',
            'storage'  => 'انباری',
            'balcony'  => 'بالکن',
            'pool'     => 'استخر',
            'security' => 'نگهبانی',
        );
    }

    /**
     * Render property details meta box
     */
    public static function render_property_details_box($post) {
        wp_nonce_field('moaveze_save_meta', 'moaveze_meta_nonce');
        $fields = self::get_fields();
        $property_fields = array(
            'property_value', 'area_sqm', 'rooms', 'floor',
            'total_floors', 'year_built', 'parking', 'elevator',
            'storage', 'balcony', 'pool', 'security', 'latitude', 'longitude', 'address'
        );

        // Listings created via the frontend form only ever set the
        // moaveze_feature TAXONOMY terms, never the individual
        // _moaveze_parking/_moaveze_elevator/... meta booleans below.
        // So when rendering the admin checkboxes we must fall back to
        // "is there a matching taxonomy term?" for any feature whose
        // boolean meta was never explicitly saved - otherwise the admin
        // UI would incorrectly show an unchecked box for a feature the
        // listing actually has.
        $feature_map = self::get_feature_taxonomy_map();
        $existing_terms = wp_list_pluck(get_the_terms($post->ID, 'moaveze_feature') ?: array(), 'name');

        echo '<div class="moaveze-meta-box">';
        foreach ($property_fields as $key) {
            $value = get_post_meta($post->ID, '_moaveze_' . $key, true);

            if (isset($feature_map[$key]) && $value === '') {
                // No boolean meta saved yet - infer from taxonomy instead.
                $value = in_array($feature_map[$key], $existing_terms, true) ? '1' : '0';
            }

            $field = $fields[$key];
            $is_feature = isset($feature_map[$key]);
            self::render_field($key, $field, $value, $is_feature ? $post->ID : null);
        }
        echo '</div>';

        // ===== Diagnostic readout ("وضعیت فعلی امکانات") =====
        // Shows EXACTLY which moaveze_feature taxonomy terms are
        // currently saved for this post, read fresh with no caching, so
        // the admin can immediately verify a checkbox toggle actually
        // took effect on THIS screen without needing to check the live
        // front-end page (which may itself be served from a page cache -
        // see purge_listing_cache()).
        echo '<div class="moaveze-feature-debug-box" id="moaveze-feature-debug-box">';
        echo '<strong>وضعیت فعلی امکانات ذخیره‌شده (به‌صورت زنده):</strong> ';
        echo '<span id="moaveze-feature-debug-list">' . (empty($existing_terms) ? '<em>هیچ ویژگی‌ای ثبت نشده</em>' : esc_html(implode('، ', $existing_terms))) . '</span>';
        echo '<p class="description">این خط بلافاصله بعد از کلیک روی هر تیک به‌روزرسانی می‌شود (بدون نیاز به دکمه «به‌روزرسانی») و مستقیماً از پایگاه داده خوانده می‌شود. اگر امکانات اینجا صحیح است ولی در صفحه سایت دیده نمی‌شود، مشکل از کش صفحه سایت است، نه از این افزونه (این افزونه به‌صورت خودکار تلاش می‌کند کش افزونه‌های رایج را هم پاک کند).</p>';
        echo '</div>';
    }

    /**
     * Render exchange conditions meta box
     */
    public static function render_exchange_conditions_box($post) {
        $fields = self::get_fields();
        $exchange_fields = array(
            'exchange_type', 'desired_property_type',
            'desired_min_value', 'desired_max_value',
            'cash_difference', 'cash_direction', 'additional_assets'
        );

        echo '<div class="moaveze-meta-box">';
        foreach ($exchange_fields as $key) {
            $value = get_post_meta($post->ID, '_moaveze_' . $key, true);
            $field = $fields[$key];
            self::render_field($key, $field, $value);
        }
        echo '</div>';
    }

    /**
     * Render contact info meta box
     */
    public static function render_contact_info_box($post) {
        $fields = self::get_fields();
        $contact_fields = array('contact_name', 'contact_phone', 'contact_email');

        echo '<div class="moaveze-meta-box moaveze-private">';
        echo '<p class="description" style="color:#d63384;"><span class="dashicons dashicons-lock"></span> این اطلاعات فقط برای مدیران قابل مشاهده است</p>';
        foreach ($contact_fields as $key) {
            $value = get_post_meta($post->ID, '_moaveze_' . $key, true);
            $field = $fields[$key];
            self::render_field($key, $field, $value);
        }
        echo '</div>';
    }

    /**
     * Render status meta box
     */
    public static function render_status_box($post) {
        $verified = get_post_meta($post->ID, '_moaveze_verified', true);
        $featured = get_post_meta($post->ID, '_moaveze_featured', true);

        echo '<div class="moaveze-meta-box">';
        echo '<p><label><input type="checkbox" name="moaveze_verified" value="1" ' . checked($verified, '1', false) . '> تأیید شده توسط مشاور</label></p>';
        echo '<p><label><input type="checkbox" name="moaveze_featured" value="1" ' . checked($featured, '1', false) . '> آگهی ویژه</label></p>';
        echo '</div>';
    }

    /**
     * Render a single field
     */
    private static function render_field($key, $field, $value, $instant_save_post_id = null) {
        $name = 'moaveze_' . $key;
        echo '<div class="moaveze-field">';
        echo '<label for="' . esc_attr($name) . '">' . esc_html($field['label']) . '</label>';

        switch ($field['type']) {
            case 'textarea':
                echo '<textarea id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" rows="3">' . esc_textarea($value) . '</textarea>';
                break;
            case 'checkbox':
                $extra_attrs = '';
                if ($instant_save_post_id) {
                    // Feature checkboxes (پارکینگ/آسانسور/...) save
                    // THEMSELVES instantly via AJAX the moment they're
                    // clicked - see the .moaveze-instant-feature handler
                    // in assets/js/admin/admin.js - so toggling one no
                    // longer depends on the admin remembering to click
                    // "به‌روزرسانی" on the whole post at all.
                    $extra_attrs = ' class="moaveze-instant-feature" data-post-id="' . esc_attr($instant_save_post_id) . '" data-feature-key="' . esc_attr($key) . '"';
                }
                echo '<input type="checkbox" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="1" ' . checked($value, '1', false) . $extra_attrs . '>';
                if ($instant_save_post_id) {
                    echo ' <span class="moaveze-instant-save-status" data-feature-key="' . esc_attr($key) . '"></span>';
                }
                break;
            case 'select':
                echo '<select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
                echo '<option value="">انتخاب کنید</option>';
                $options = self::get_select_options($key);
                foreach ($options as $opt_value => $opt_label) {
                    echo '<option value="' . esc_attr($opt_value) . '" ' . selected($value, $opt_value, false) . '>' . esc_html($opt_label) . '</option>';
                }
                echo '</select>';
                break;
            default:
                echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        }

        echo '</div>';
    }

    /**
     * Get select options
     */
    private static function get_select_options($key) {
        $options = array();

        switch ($key) {
            case 'exchange_type':
                $options = array(
                    'property_only'     => 'ملک با ملک',
                    'property_cash'     => 'ملک + مابه‌التفاوت نقدی',
                    'property_car'      => 'ملک + خودرو',
                    'property_mixed'    => 'ترکیبی (ملک + نقد + دارایی)',
                    'flexible'          => 'انعطاف‌پذیر (بررسی پیشنهادات)',
                );
                break;
            case 'cash_direction':
                $options = array(
                    'give' => 'می‌دهم',
                    'receive' => 'می‌گیرم',
                );
                break;
            case 'desired_property_type':
                // NOTE: unlike property_type/district (real taxonomy
                // relationships), "desired_property_type" is stored as
                // plain post META (_moaveze_desired_property_type) and
                // displayed VERBATIM on the frontend (see
                // templates/single-exchange.php). So this option's
                // value must be the term's actual Persian NAME, not its
                // slug - using the slug here would silently re-introduce
                // the same "raw slug shown on the listing page" bug via
                // the wp-admin edit screen, even after the frontend
                // submission form's version of this field was fixed to
                // resolve to a name (see resolve_term_name() in
                // class-submission-form.php).
                $terms = get_terms(array(
                    'taxonomy'   => 'moaveze_property_type',
                    'hide_empty' => false,
                ));
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $options[$term->name] = $term->name;
                    }
                }
                break;
        }

        return $options;
    }

    /**
     * Globally hide "private" reciprocal listings (registered by a user
     * only for a specific offer, not for general matching/browsing) from
     * ALL public-facing queries of the moaveze_exchange post type -
     * archive, [moaveze_listings], [moaveze_featured]/[moaveze_recent],
     * the map AJAX endpoint, REST API, etc.
     *
     * This is done centrally via pre_get_posts instead of editing every
     * individual WP_Query call, so it can never be accidentally missed
     * in a new shortcode/template added later.
     */
    public static function hide_private_reciprocal_listings($query) {
        if (is_admin() && !wp_doing_ajax()) return; // don't affect wp-admin listing screens
        if ($query->get('post_type') !== 'moaveze_exchange') return;

        $meta_query = $query->get('meta_query') ?: array();
        $meta_query[] = array(
            'relation' => 'OR',
            array('key' => '_moaveze_visibility', 'compare' => 'NOT EXISTS'),
            array('key' => '_moaveze_visibility', 'value' => 'private', 'compare' => '!='),
        );
        $query->set('meta_query', $meta_query);
    }

    /**
     * ONE-TIME REPAIR TOOL (dashboard button): fixes listings that were
     * created BEFORE the English-slug-vs-Persian-term bug fix - i.e. any
     * moaveze_exchange post whose "moaveze_feature" taxonomy terms still
     * literally read "parking"/"elevator"/etc. in English (this is
     * exactly the bug the user found live on tabrizhome.com - the fix
     * applied earlier only prevents the bug for NEW submissions going
     * forward, it does not retroactively repair listings that already
     * have the bad English terms saved).
     *
     * For every English slug term that still exists in moaveze_feature:
     *   1. Re-assign every post that has that English term to the
     *      correct Persian term instead (creating the Persian term if
     *      it doesn't exist yet).
     *   2. Delete the now-empty English term entirely so it can never
     *      show up again.
     * Also re-syncs each affected post's boolean checkbox meta
     * (_moaveze_parking, etc.) so the wp-admin metabox checkboxes match.
     */
    public static function ajax_repair_feature_terms() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $feature_map = self::get_feature_taxonomy_map(); // english_key => persian_name
        $fixed_posts = array();
        $removed_terms = array();

        foreach ($feature_map as $english_key => $persian_name) {
            // Does a term literally named with the English slug exist?
            $bad_term = get_term_by('name', $english_key, 'moaveze_feature');
            if (!$bad_term) continue;

            // Find (or create) the correct Persian term.
            $good_term = get_term_by('name', $persian_name, 'moaveze_feature');
            if (!$good_term) {
                $inserted = wp_insert_term($persian_name, 'moaveze_feature');
                if (is_wp_error($inserted)) continue;
                $good_term = get_term($inserted['term_id'], 'moaveze_feature');
            }

            // Every post currently tagged with the bad English term.
            $affected_posts = get_posts(array(
                'post_type'      => 'moaveze_exchange',
                'post_status'    => 'any',
                'numberposts'    => -1,
                'fields'         => 'ids',
                'tax_query'      => array(array(
                    'taxonomy' => 'moaveze_feature',
                    'field'    => 'term_id',
                    'terms'    => $bad_term->term_id,
                )),
            ));

            foreach ($affected_posts as $post_id) {
                wp_set_object_terms($post_id, array($good_term->term_id), 'moaveze_feature', true);
                wp_remove_object_terms($post_id, $bad_term->term_id, 'moaveze_feature');
                update_post_meta($post_id, '_moaveze_' . $english_key, '1');
                if (!in_array($post_id, $fixed_posts, true)) {
                    $fixed_posts[] = $post_id;
                }
            }

            wp_delete_term($bad_term->term_id, 'moaveze_feature');
            $removed_terms[] = $english_key;
        }

        wp_send_json_success(array(
            'message'       => empty($removed_terms)
                ? 'هیچ ویژگی خرابی پیدا نشد؛ همه چیز از قبل درست بود.'
                : sprintf('%d آگهی اصلاح شد و %d ویژگی انگلیسی خراب حذف شد.', count($fixed_posts), count($removed_terms)),
            'fixed_posts'   => count($fixed_posts),
            'removed_terms' => $removed_terms,
        ));
    }

    /**
     * Save meta data
     */
    public static function save_meta($post_id) {
        if (!isset($_POST['moaveze_meta_nonce']) || !wp_verify_nonce($_POST['moaveze_meta_nonce'], 'moaveze_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = self::get_fields();

        foreach ($fields as $key => $field) {
            $name = 'moaveze_' . $key;
            $meta_key = '_moaveze_' . $key;

            if ($field['type'] === 'checkbox') {
                $value = isset($_POST[$name]) ? '1' : '0';
            } else {
                $value = isset($_POST[$name]) ? sanitize_text_field($_POST[$name]) : '';
            }

            update_post_meta($post_id, $meta_key, $value);
        }

        // BUG FIX: the wp-admin "امکانات" checkboxes (پارکینگ/آسانسور/...)
        // only ever updated their individual boolean meta fields above,
        // but the single listing page renders features purely from the
        // moaveze_feature TAXONOMY (see templates/single-exchange.php ->
        // get_the_terms($post_id, 'moaveze_feature')). The two were never
        // connected, so toggling a checkbox in wp-admin had literally no
        // visible effect on the frontend - exactly the bug reported.
        // Now every save re-syncs the taxonomy from the current checkbox
        // state, using wp_set_object_terms() in REPLACE mode (4th arg
        // false) so unchecking a box also removes that term.
        $feature_map = self::get_feature_taxonomy_map();
        $active_feature_terms = array();
        foreach ($feature_map as $key => $term_name) {
            if (get_post_meta($post_id, '_moaveze_' . $key, true) === '1') {
                $active_feature_terms[] = $term_name;
            }
        }
        wp_set_object_terms($post_id, $active_feature_terms, 'moaveze_feature', false);

        // See purge_listing_cache() docblock above: without this, a
        // caching plugin on the (typically Iran-hosted) live site can
        // keep serving the pre-edit HTML of the listing page for a long
        // time even though the taxonomy above was just updated correctly
        // - which is indistinguishable, from the site owner's point of
        // view, from "the fix doesn't work".
        self::purge_listing_cache($post_id);
    }
}

// Initialize
Moaveze_Meta_Fields::init();
