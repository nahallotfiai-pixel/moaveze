<?php
/**
 * Custom Post Types Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Post_Types {

    /**
     * Register custom post types
     */
    public static function register() {
        // Exchange Listing Post Type
        register_post_type('moaveze_exchange', array(
            'labels' => array(
                'name'               => 'معاوضه‌ها',
                'singular_name'      => 'معاوضه',
                'menu_name'          => 'معاوضه پلاس',
                'add_new'            => 'افزودن آگهی معاوضه',
                'add_new_item'       => 'افزودن آگهی معاوضه جدید',
                'edit_item'          => 'ویرایش آگهی معاوضه',
                'new_item'           => 'آگهی معاوضه جدید',
                'view_item'          => 'مشاهده آگهی معاوضه',
                'search_items'       => 'جستجوی آگهی‌های معاوضه',
                'not_found'          => 'آگهی معاوضه‌ای یافت نشد',
                'not_found_in_trash' => 'آگهی معاوضه‌ای در سطل زباله یافت نشد',
                'all_items'          => 'همه آگهی‌ها',
            ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            // Attach this CPT's edit screens under our own custom top-level
            // menu (registered in Moaveze_Admin) instead of creating its own
            // top-level menu item. This was the cause of the duplicate
            // "معاوضه پلاس" entry in the admin sidebar.
            'show_in_menu'       => 'moaveze-plus',
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'exchange', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => array(
                'title',
                'editor',
                'thumbnail',
                'custom-fields',
                'author',
            ),
            'taxonomies'         => array('moaveze_property_type', 'moaveze_district', 'moaveze_feature'),
        ));
    }
}
