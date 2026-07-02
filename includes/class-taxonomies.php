<?php
/**
 * Custom Taxonomies Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Taxonomies {

    /**
     * Register custom taxonomies
     */
    public static function register() {
        self::register_property_type();
        self::register_district();
        self::register_feature();
    }

    /**
     * Property Type Taxonomy
     */
    private static function register_property_type() {
        register_taxonomy('moaveze_property_type', 'moaveze_exchange', array(
            'labels' => array(
                'name'          => 'نوع ملک',
                'singular_name' => 'نوع ملک',
                'search_items'  => 'جستجوی نوع ملک',
                'all_items'     => 'همه انواع ملک',
                'edit_item'     => 'ویرایش نوع ملک',
                'add_new_item'  => 'افزودن نوع ملک جدید',
            ),
            'hierarchical' => true,
            'public'       => true,
            'show_in_rest' => true,
            'rewrite'      => array('slug' => 'exchange-type'),
        ));
    }


    /**
     * District Taxonomy
     */
    private static function register_district() {
        register_taxonomy('moaveze_district', 'moaveze_exchange', array(
            'labels' => array(
                'name'          => 'منطقه',
                'singular_name' => 'منطقه',
                'search_items'  => 'جستجوی منطقه',
                'all_items'     => 'همه مناطق',
                'edit_item'     => 'ویرایش منطقه',
                'add_new_item'  => 'افزودن منطقه جدید',
            ),
            'hierarchical' => true,
            'public'       => true,
            'show_in_rest' => true,
            'rewrite'      => array('slug' => 'exchange-district'),
        ));
    }

    /**
     * Feature Taxonomy
     */
    private static function register_feature() {
        register_taxonomy('moaveze_feature', 'moaveze_exchange', array(
            'labels' => array(
                'name'          => 'ویژگی‌ها',
                'singular_name' => 'ویژگی',
                'search_items'  => 'جستجوی ویژگی',
                'all_items'     => 'همه ویژگی‌ها',
                'edit_item'     => 'ویرایش ویژگی',
                'add_new_item'  => 'افزودن ویژگی جدید',
            ),
            'hierarchical' => false,
            'public'       => true,
            'show_in_rest' => true,
            'rewrite'      => array('slug' => 'exchange-feature'),
        ));
    }

    /**
     * Insert default terms on activation
     */
    public static function insert_default_terms() {
        // Property Types
        $property_types = array(
            'آپارتمان', 'ویلایی', 'تجاری', 'اداری',
            'زمین', 'باغ', 'سوله', 'مغازه',
            'دفتر کار', 'انبار', 'پنت‌هاوس', 'دوبلکس',
        );

        foreach ($property_types as $type) {
            if (!term_exists($type, 'moaveze_property_type')) {
                wp_insert_term($type, 'moaveze_property_type');
            }
        }

        // Tabriz Districts
        $districts = array(
            'ولیعصر', 'رشدیه', 'باغمیشه', 'الهیه',
            'سعادت‌آباد', 'ائل‌گلی', 'آبرسان', 'منصور',
            'شهرک سهند', 'ایل‌گلی', 'پاستور', 'ارم',
            'مارالان', 'قره‌آغاج', 'دروازه تهران', 'شمس تبریزی',
            'شهناز', 'بارنج', 'کوچه‌باغ', 'زعفرانیه',
        );

        foreach ($districts as $district) {
            if (!term_exists($district, 'moaveze_district')) {
                wp_insert_term($district, 'moaveze_district');
            }
        }
    }
}
