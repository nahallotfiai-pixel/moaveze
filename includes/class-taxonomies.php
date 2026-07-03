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

        // Tabriz Districts - expanded to be comprehensive.
        // Combines: (a) the well-known modern/real-estate-market districts
        // that were already in the original short list, (b) additional
        // well-known modern neighborhoods commonly used in Tabriz real
        // estate listings (Divar, local agencies), and (c) the classic/
        // historic quarters of Tabriz as documented on Wikipedia
        // (https://en.wikipedia.org/wiki/Template:Districts_of_Tabriz),
        // transliterated to their standard Persian spelling. This was
        // requested to make the district list "more comprehensive" than
        // the original 20-item list.
        $districts = array(
            // --- Original + well-known modern districts ---
            'ولیعصر', 'رشدیه', 'باغمیشه', 'الهیه',
            'سعادت‌آباد', 'ائل‌گلی', 'آبرسان', 'منصور',
            'شهرک سهند', 'پاستور', 'ارم',
            'مارالان', 'قره‌آغاج', 'دروازه تهران', 'شمس تبریزی',
            'شهناز', 'بارنج', 'کوچه‌باغ', 'زعفرانیه',
            // --- Additional well-known modern neighborhoods ---
            'ولی‌عصر شمالی', 'ولی‌عصر جنوبی', 'یاغچیان', 'گلستان',
            'باغ‌شمال', 'خطیب', 'خیام', 'نصف راه',
            'راه آهن', 'شهرک باهنر', 'شهرک ارم', 'ونک آباد',
            'میدان ساعت', 'فلکه دانشسرا', 'آبباریک', 'آذربایجان',
            'ولیعصر مرکزی', 'انصاری', 'دانشسرا', 'حکم‌آباد',
            'کوی ولیعصر', 'شهرک شهید بهشتی', 'زعفرانیه جدید', 'ائل گلی جدید',
            // --- Historic / classic quarters of Tabriz ---
            'باغمشا', 'بازار تبریز', 'سرخاب', 'شتربان',
            'نوبر', 'لاله', 'ششگلان', 'چرنداب',
            'داواچی', 'گجیل', 'امامیه', 'حکم‌آور',
            'خیابان', 'لیلاوا', 'قره‌ملک', 'سیلاب',
            'شام‌قازان', 'شاه‌گلی', 'تپه‌لی‌باغ', 'ویجویه',
            'باغ‌شمالی', 'گازران', 'راسته‌کوچه', 'بیلانکوه',
            'اخماقیه', 'احرار',
        );

        foreach ($districts as $district) {
            if (!term_exists($district, 'moaveze_district')) {
                wp_insert_term($district, 'moaveze_district');
            }
        }
    }

    /**
     * Add any newly-introduced default districts (or property types) to
     * an already-active install without requiring plugin reactivation.
     * Mirrors the pattern used by Moaveze_Database::maybe_upgrade() -
     * insert_default_terms() is idempotent (term_exists() guard) so this
     * is always safe to re-run.
     */
    public static function maybe_add_new_terms() {
        $version = get_option('moaveze_taxonomies_version', '');
        if ($version === MOAVEZE_PLUS_VERSION) return;

        self::insert_default_terms();
        update_option('moaveze_taxonomies_version', MOAVEZE_PLUS_VERSION);
    }
}
