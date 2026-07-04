<?php
/**
 * Editable Front-End Pages
 *
 * ROOT CAUSE OF TWO USER-REPORTED BUGS THIS FILE FIXES:
 *
 *   1. "چرا این صفحه رو نمیشه ویرایش کرد" (https://tabrizhome.com/exchange/)
 *      - /exchange/ is the moaveze_exchange CPT's auto-generated
 *      archive (has_archive => true in class-post-types.php), rendered
 *      by templates/archive-exchange.php via the archive_template
 *      filter. A CPT archive like this is NOT a real row in
 *      wp_posts/Pages > All Pages - there is nothing to click "ویرایش"
 *      on in wp-admin, by WordPress design, no matter what this plugin
 *      does. It stays exactly as-is (so any links already shared/
 *      indexed by Google keep working).
 *
 *   2. "ثبت آگهی به این صفحه اشاره میکنه که صفحه ای موجود نیست"
 *      (https://tabrizhome.com/submit-exchange/) - the "ثبت آگهی
 *      معاوضه" button on the archive template was hardcoded to link to
 *      home_url('/submit-exchange/'), but NOTHING ever created a page
 *      at that URL - not on activation, not anywhere. It 404s because
 *      it genuinely doesn't exist.
 *
 * FIX: on activation (and self-healingly on every already-active
 * install, the same maybe_* pattern already used for taxonomies/DB
 * upgrades), auto-create two REAL, fully wp-admin-editable WordPress
 * Pages:
 *   - "ثبت آگهی معاوضه" containing the [moaveze_submit_form] shortcode
 *   - "لیست آگهی‌های معاوضه" containing the [moaveze_listings] shortcode
 *
 * Because these are real wp_posts rows of post_type 'page', the site
 * owner can open them in Pages > All Pages and edit/rename/restyle
 * them exactly like any other page (add text above/below the
 * shortcode, change the title, edit with a page builder, etc) - the
 * shortcode itself keeps rendering the live plugin functionality.
 *
 * Settings > صفحات lets the admin point either "role" (submit form /
 * listings) at ANY existing page instead (e.g. one they build
 * themselves with the same shortcode inside a custom design), or
 * re-create the defaults if a page was ever deleted.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Pages {

    public static function init() {
        add_action('init', array(__CLASS__, 'maybe_create_default_pages'), 30);
        add_action('wp_ajax_moaveze_recreate_pages', array(__CLASS__, 'ajax_recreate_pages'));
    }

    /**
     * Definition of every auto-managed page: option name that stores
     * its page ID, default title, and shortcode content.
     */
    private static function get_page_definitions() {
        return array(
            'submit' => array(
                'option'    => 'moaveze_page_submit',
                'title'     => 'ثبت آگهی معاوضه',
                'shortcode' => '[moaveze_submit_form]',
            ),
            'listings' => array(
                'option'    => 'moaveze_page_listings',
                'title'     => 'لیست آگهی‌های معاوضه',
                'shortcode' => '[moaveze_listings show_map="yes" show_filters="yes"]',
            ),
        );
    }

    /**
     * Self-healing creation: runs on every page load (cheap - it's just
     * an option read after the first time) but only actually creates
     * anything if the stored page ID is missing OR points to a page
     * that no longer exists (e.g. the admin deleted it by mistake).
     * This mirrors Moaveze_Taxonomies::maybe_add_new_terms() so it also
     * fixes already-active installs without requiring reactivation.
     */
    public static function maybe_create_default_pages() {
        foreach (self::get_page_definitions() as $key => $def) {
            $page_id = (int) get_option($def['option'], 0);
            $page = $page_id ? get_post($page_id) : null;

            if ($page && $page->post_type === 'page' && $page->post_status !== 'trash') {
                continue; // already exists and is valid
            }

            self::create_page($key, $def);
        }
    }

    /**
     * AJAX: "بازسازی صفحات" button in Settings > صفحات - lets the admin
     * force-recreate either page on demand (e.g. after accidentally
     * deleting one, or to reset it back to just the shortcode).
     */
    public static function ajax_recreate_pages() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $only = sanitize_key($_POST['page_key'] ?? '');
        $definitions = self::get_page_definitions();
        $created = array();

        foreach ($definitions as $key => $def) {
            if ($only && $key !== $only) continue;
            $page_id = self::create_page($key, $def, true);
            $created[$key] = array(
                'id'        => $page_id,
                'edit_url'  => get_edit_post_link($page_id, 'raw'),
                'view_url'  => get_permalink($page_id),
            );
        }

        wp_send_json_success(array(
            'message' => 'صفحه(های) درخواستی بازسازی شدند',
            'pages'   => $created,
        ));
    }

    /**
     * Create (or force-recreate) one managed page and store its ID.
     *
     * @param bool $force If true, creates a brand-new page even if a
     *                     valid one already exists (used by the manual
     *                     "بازسازی" button, never by the automatic
     *                     self-healing check above).
     */
    private static function create_page($key, $def, $force = false) {
        if (!$force) {
            $existing_id = (int) get_option($def['option'], 0);
            if ($existing_id && get_post($existing_id)) {
                return $existing_id;
            }
        }

        // If a page with this exact title already exists (e.g. from a
        // previous activation before the option was saved, or restored
        // from trash), reuse it instead of creating a duplicate.
        $existing = get_page_by_title($def['title'], OBJECT, 'page');
        if ($existing && !$force) {
            update_option($def['option'], $existing->ID);
            return $existing->ID;
        }

        $page_id = wp_insert_post(array(
            'post_title'   => $def['title'],
            'post_content' => $def['shortcode'],
            'post_type'    => 'page',
            'post_status'  => 'publish',
        ));

        if (is_wp_error($page_id) || !$page_id) {
            return 0;
        }

        update_option($def['option'], $page_id);
        return $page_id;
    }

    /**
     * Public helper: URL of the "ثبت آگهی" page, for every internal
     * link that previously hardcoded home_url('/submit-exchange/').
     * Falls back to the CPT archive link if, for some unexpected
     * reason, no page is configured yet.
     */
    public static function get_submit_url() {
        $page_id = (int) get_option('moaveze_page_submit', 0);
        $url = $page_id ? get_permalink($page_id) : '';
        return $url ?: get_post_type_archive_link('moaveze_exchange');
    }

    /**
     * Public helper: URL of the "لیست آگهی‌ها" page. Admins can point
     * this at either the auto-created page OR the raw CPT archive
     * (/exchange/) from Settings > صفحات, in case they prefer keeping
     * the existing archive URL for SEO continuity.
     */
    public static function get_listings_url() {
        $page_id = (int) get_option('moaveze_page_listings', 0);
        $url = $page_id ? get_permalink($page_id) : '';
        return $url ?: get_post_type_archive_link('moaveze_exchange');
    }
}

Moaveze_Pages::init();
