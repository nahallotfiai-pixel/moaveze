<?php
/**
 * Sample Data Seeder
 * Creates 6 realistic test exchange listings for Tabriz
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Sample_Data {

    /**
     * Initialize
     */
    public static function init() {
        add_action('wp_ajax_moaveze_seed_sample_data', array(__CLASS__, 'seed'));
        add_action('wp_ajax_moaveze_clear_sample_data', array(__CLASS__, 'clear'));
    }

    /**
     * Get sample listings data
     */
    private static function get_samples() {
        return array(

            // 1. آپارتمان لوکس در ولیعصر
            array(
                'title'          => 'آپارتمان ۱۸۰ متری لوکس در ولیعصر تبریز',
                'description'    => 'آپارتمان تمام بازسازی شده با متریال درجه یک، نورگیر عالی، ویو شهر، دسترسی به مترو و اتوبان. سند تک‌برگ آماده انتقال.',
                'property_type'  => 'آپارتمان',
                'district'       => 'ولیعصر',
                'value'          => 18000000000,
                'area'           => 180,
                'rooms'          => 3,
                'floor'          => 7,
                'total_floors'   => 10,
                'year_built'     => 1399,
                'lat'            => '38.0795',
                'lng'            => '46.2919',
                'address'        => 'تبریز، خیابان ولیعصر، برج آسمان، طبقه هفتم',
                'exchange_type'  => 'property_cash',
                'desired_type'   => 'ویلایی',
                'desired_min'    => 25000000000,
                'desired_max'    => 35000000000,
                'cash_diff'      => 8000000000,
                'cash_dir'       => 'give',
                'additional'     => '',
                'features'       => array('parking', 'elevator', 'storage', 'balcony', 'security'),
                'contact_name'   => 'علی محمدی',
                'contact_phone'  => '09141234567',
                'contact_email'  => 'ali.m@example.com',
                'verified'       => true,
                'featured'       => true,
            ),

            // 2. ویلای دوبلکس در باغمیشه
            array(
                'title'          => 'ویلای دوبلکس ۳۵۰ متری در باغمیشه',
                'description'    => 'ویلای دوبلکس فول امکانات با حیاط ۲۰۰ متری، استخر سرپوشیده، ۲ پارکینگ. مناسب خانواده بزرگ. محله آرام و سرسبز.',
                'property_type'  => 'ویلایی',
                'district'       => 'باغمیشه',
                'value'          => 45000000000,
                'area'           => 350,
                'rooms'          => 5,
                'floor'          => 0,
                'total_floors'   => 2,
                'year_built'     => 1396,
                'lat'            => '38.0451',
                'lng'            => '46.3412',
                'address'        => 'تبریز، باغمیشه، فاز ۳، کوچه گلستان',
                'exchange_type'  => 'property_mixed',
                'desired_type'   => 'آپارتمان',
                'desired_min'    => 15000000000,
                'desired_max'    => 25000000000,
                'cash_diff'      => 15000000000,
                'cash_dir'       => 'receive',
                'additional'     => 'یک دستگاه خودرو لکسوس یا BMW به ارزش حداقل ۱۰ میلیارد هم قبول می‌شود',
                'features'       => array('parking', 'storage', 'balcony', 'pool', 'security'),
                'contact_name'   => 'رضا احمدی',
                'contact_phone'  => '09142345678',
                'contact_email'  => 'reza.a@example.com',
                'verified'       => true,
                'featured'       => true,
            ),

            // 3. مغازه تجاری در آبرسان
            array(
                'title'          => 'مغازه تجاری ۷۵ متری در آبرسان با موقعیت عالی',
                'description'    => 'مغازه سر نبش با ویترین ۶ متری، بر خیابان اصلی، مناسب هر نوع کسب‌وکار. اجاره فعلی ماهیانه ۱۵۰ میلیون تومان. سند ششدانگ.',
                'property_type'  => 'مغازه',
                'district'       => 'آبرسان',
                'value'          => 22000000000,
                'area'           => 75,
                'rooms'          => 0,
                'floor'          => 0,
                'total_floors'   => 1,
                'year_built'     => 1385,
                'lat'            => '38.0678',
                'lng'            => '46.3051',
                'address'        => 'تبریز، میدان آبرسان، خیابان شریعتی',
                'exchange_type'  => 'flexible',
                'desired_type'   => '',
                'desired_min'    => 20000000000,
                'desired_max'    => 50000000000,
                'cash_diff'      => 0,
                'cash_dir'       => 'give',
                'additional'     => 'حاضرم با ۲ واحد آپارتمان نوساز یا ویلا معاوضه کنم. مابه‌التفاوت قابل مذاکره.',
                'features'       => array('parking', 'storage'),
                'contact_name'   => 'حسین کریمی',
                'contact_phone'  => '09143456789',
                'contact_email'  => '',
                'verified'       => true,
                'featured'       => false,
            ),

            // 4. آپارتمان نوساز در رشدیه
            array(
                'title'          => 'آپارتمان نوساز ۱۲۰ متری در رشدیه',
                'description'    => 'آپارتمان صفر، کلید نخورده، تحویل فوری. طراحی مدرن اروپایی، کابینت MDF، کف سرامیک اسپانیایی. نزدیک پارک و مدرسه.',
                'property_type'  => 'آپارتمان',
                'district'       => 'رشدیه',
                'value'          => 14000000000,
                'area'           => 120,
                'rooms'          => 2,
                'floor'          => 4,
                'total_floors'   => 6,
                'year_built'     => 1403,
                'lat'            => '38.1012',
                'lng'            => '46.2654',
                'address'        => 'تبریز، رشدیه، خیابان استاد شهریار، نبش کوچه ۱۵',
                'exchange_type'  => 'property_cash',
                'desired_type'   => 'آپارتمان',
                'desired_min'    => 18000000000,
                'desired_max'    => 25000000000,
                'cash_diff'      => 6000000000,
                'cash_dir'       => 'give',
                'additional'     => 'یک دستگاه پژو پارس مدل ۱۴۰۱ به ارزش ۳ میلیارد هم در ازای مابه‌التفاوت قابل ارائه است',
                'features'       => array('parking', 'elevator', 'storage'),
                'contact_name'   => 'مهدی حسینی',
                'contact_phone'  => '09144567890',
                'contact_email'  => 'mehdi.h@example.com',
                'verified'       => false,
                'featured'       => false,
            ),

            // 5. زمین در شهرک سهند
            array(
                'title'          => 'زمین مسکونی ۵۰۰ متری در شهرک سهند',
                'description'    => 'زمین مسکونی با پروانه ساخت ۵ طبقه، بر ۱۲ متری، امتیازات آب و برق و گاز متصل. موقعیت سرمایه‌گذاری عالی.',
                'property_type'  => 'زمین',
                'district'       => 'شهرک سهند',
                'value'          => 30000000000,
                'area'           => 500,
                'rooms'          => 0,
                'floor'          => 0,
                'total_floors'   => 0,
                'year_built'     => 0,
                'lat'            => '37.9856',
                'lng'            => '46.1789',
                'address'        => 'شهرک سهند، فاز ۲، بلوار آزادی',
                'exchange_type'  => 'property_car',
                'desired_type'   => 'آپارتمان',
                'desired_min'    => 20000000000,
                'desired_max'    => 30000000000,
                'cash_diff'      => 5000000000,
                'cash_dir'       => 'receive',
                'additional'     => 'آپارتمان ۱۵۰+ متری در مناطق مرکزی تبریز + یک دستگاه خودروی بالای ۵ میلیارد',
                'features'       => array(),
                'contact_name'   => 'ناصر قاسمی',
                'contact_phone'  => '09145678901',
                'contact_email'  => 'naser.g@example.com',
                'verified'       => true,
                'featured'       => false,
            ),

            // 6. پنت‌هاوس در ائل‌گلی
            array(
                'title'          => 'پنت‌هاوس ۲۵۰ متری در ائل‌گلی با ویوی دریاچه',
                'description'    => 'پنت‌هاوس فوق لوکس با تراس ۸۰ متری و ویوی مستقیم دریاچه ائل‌گلی. فول فرنیش ایتالیایی، جکوزی، سونا خشک و بخار. سیستم هوشمند خانه.',
                'property_type'  => 'پنت‌هاوس',
                'district'       => 'ائل‌گلی',
                'value'          => 65000000000,
                'area'           => 250,
                'rooms'          => 4,
                'floor'          => 12,
                'total_floors'   => 12,
                'year_built'     => 1401,
                'lat'            => '38.0234',
                'lng'            => '46.3598',
                'address'        => 'تبریز، بلوار ائل‌گلی، برج پارسیان، طبقه ۱۲',
                'exchange_type'  => 'property_only',
                'desired_type'   => 'ویلایی',
                'desired_min'    => 55000000000,
                'desired_max'    => 75000000000,
                'cash_diff'      => 0,
                'cash_dir'       => '',
                'additional'     => '',
                'features'       => array('parking', 'elevator', 'storage', 'balcony', 'pool', 'security'),
                'contact_name'   => 'فرهاد نوروزی',
                'contact_phone'  => '09146789012',
                'contact_email'  => 'farhad.n@example.com',
                'verified'       => true,
                'featured'       => true,
            ),
        );
    }


    /**
     * Seed sample data
     */
    public static function seed() {
        if (is_admin() && current_user_can('manage_options')) {
            // Check nonce if AJAX
            if (wp_doing_ajax()) {
                check_ajax_referer('moaveze_admin_nonce', 'nonce');
            }
        }

        global $wpdb;
        $samples = self::get_samples();
        $created = 0;

        // Ensure taxonomies exist
        Moaveze_Taxonomies::insert_default_terms();

        foreach ($samples as $sample) {
            // Check if already exists
            $existing = get_posts(array(
                'post_type'  => 'moaveze_exchange',
                'title'      => $sample['title'],
                'post_status' => 'any',
                'numberposts' => 1,
            ));
            if (!empty($existing)) continue;

            // Create post
            $post_id = wp_insert_post(array(
                'post_title'   => $sample['title'],
                'post_content' => $sample['description'],
                'post_type'    => 'moaveze_exchange',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ));

            if (is_wp_error($post_id)) continue;


            // Set taxonomies
            wp_set_object_terms($post_id, $sample['property_type'], 'moaveze_property_type');
            wp_set_object_terms($post_id, $sample['district'], 'moaveze_district');
            if (!empty($sample['features'])) {
                $feature_names = array(
                    'parking' => 'پارکینگ', 'elevator' => 'آسانسور',
                    'storage' => 'انباری', 'balcony' => 'بالکن',
                    'pool' => 'استخر', 'security' => 'نگهبانی',
                );
                $terms = array_map(function($f) use ($feature_names) {
                    return $feature_names[$f] ?? $f;
                }, $sample['features']);
                wp_set_object_terms($post_id, $terms, 'moaveze_feature');
            }

            // Save meta
            $meta = array(
                'property_value'        => $sample['value'],
                'area_sqm'              => $sample['area'],
                'rooms'                 => $sample['rooms'],
                'floor'                 => $sample['floor'],
                'total_floors'          => $sample['total_floors'],
                'year_built'            => $sample['year_built'],
                'latitude'              => $sample['lat'],
                'longitude'             => $sample['lng'],
                'address'               => $sample['address'],
                'exchange_type'         => $sample['exchange_type'],
                'desired_property_type' => $sample['desired_type'],
                'desired_min_value'     => $sample['desired_min'],
                'desired_max_value'     => $sample['desired_max'],
                'cash_difference'       => $sample['cash_diff'],
                'cash_direction'        => $sample['cash_dir'],
                'additional_assets'     => $sample['additional'],
                'contact_name'          => $sample['contact_name'],
                'contact_phone'         => $sample['contact_phone'],
                'contact_email'         => $sample['contact_email'],
                'verified'              => $sample['verified'] ? '1' : '0',
                'featured'              => $sample['featured'] ? '1' : '0',
            );

            foreach ($meta as $key => $value) {
                update_post_meta($post_id, '_moaveze_' . $key, $value);
            }


            // Insert into exchanges table
            $wpdb->insert(
                $wpdb->prefix . 'moaveze_exchanges',
                array(
                    'post_id'               => $post_id,
                    'user_id'               => get_current_user_id(),
                    'property_type'         => $sample['property_type'],
                    'property_value'        => $sample['value'],
                    'area_sqm'              => $sample['area'],
                    'rooms'                 => $sample['rooms'],
                    'district'              => $sample['district'],
                    'address'               => $sample['address'],
                    'latitude'              => $sample['lat'],
                    'longitude'             => $sample['lng'],
                    'exchange_type'         => $sample['exchange_type'],
                    'desired_property_type' => $sample['desired_type'],
                    'desired_min_value'     => $sample['desired_min'],
                    'desired_max_value'     => $sample['desired_max'],
                    'cash_difference'       => $sample['cash_diff'],
                    'cash_direction'        => $sample['cash_dir'],
                    'additional_assets'     => $sample['additional'],
                    'status'                => 'active',
                    'verified'              => $sample['verified'] ? 1 : 0,
                    'featured'              => $sample['featured'] ? 1 : 0,
                    'contact_name'          => $sample['contact_name'],
                    'contact_phone'         => $sample['contact_phone'],
                    'contact_email'         => $sample['contact_email'],
                    'views_count'           => rand(5, 120),
                ),
                array('%d','%d','%s','%d','%d','%d','%s','%s','%s','%s','%s','%s','%d','%d','%d','%s','%s','%s','%d','%d','%s','%s','%s','%d')
            );

            $created++;
        }


        // Create sample offers between listings
        self::create_sample_offers();

        // Create sample matches
        self::create_sample_matches();

        if (wp_doing_ajax()) {
            wp_send_json_success(array(
                'message' => sprintf('%d آگهی نمونه با موفقیت ایجاد شد', $created),
                'count'   => $created,
            ));
        }

        return $created;
    }

    /**
     * Create sample offers
     */
    private static function create_sample_offers() {
        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_offers';
        $exchanges = $wpdb->prefix . 'moaveze_exchanges';

        // Check if offers already exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($existing > 0) return;

        $all_exchanges = $wpdb->get_results("SELECT id, post_id, property_value, district FROM $exchanges ORDER BY id LIMIT 6");
        if (count($all_exchanges) < 4) return;

        // Offer 1: Listing 4 (apartment Roshdieh) offers on Listing 1 (apartment Valiasr)
        $wpdb->insert($table, array(
            'exchange_id'      => $all_exchanges[0]->id,
            'from_user_id'     => get_current_user_id(),
            'from_exchange_id' => $all_exchanges[3]->id,
            'offer_type'       => 'exchange_with_cash',
            'offer_details'    => wp_json_encode(array(
                'message' => 'آپارتمان نوساز ۱۲۰ متری رشدیه + ۶ میلیارد نقد در ازای آپارتمان شما',
                'assets'  => 'خودرو پژو پارس ۱۴۰۱ هم در صورت نیاز قابل ارائه',
            )),
            'cash_offered'     => 6000000000,
            'assets_offered'   => 'آپارتمان ۱۲۰ متری رشدیه + امکان ارائه خودرو',
            'message'          => 'سلام. آپارتمان نوساز من در رشدیه + ۶ میلیارد نقد. آیا علاقه‌مند هستید؟',
            'status'           => 'pending',
        ), array('%d','%d','%d','%s','%s','%d','%s','%s','%s'));

        // Offer 2: Someone offers on the Penthouse
        $wpdb->insert($table, array(
            'exchange_id'      => $all_exchanges[5]->id,
            'from_user_id'     => get_current_user_id(),
            'from_exchange_id' => $all_exchanges[1]->id,
            'offer_type'       => 'exchange_with_cash',
            'offer_details'    => wp_json_encode(array(
                'message' => 'ویلای دوبلکس باغمیشه + ۲۰ میلیارد نقد',
            )),
            'cash_offered'     => 20000000000,
            'assets_offered'   => 'ویلای ۳۵۰ متری باغمیشه',
            'message'          => 'ویلای من در باغمیشه ۴۵ میلیارد ارزش داره + ۲۰ میلیارد نقد هم می‌دم',
            'status'           => 'pending',
        ), array('%d','%d','%d','%s','%s','%d','%s','%s','%s'));

        // Offer 3: Land owner offers on apartment
        $wpdb->insert($table, array(
            'exchange_id'      => $all_exchanges[3]->id,
            'from_user_id'     => get_current_user_id(),
            'from_exchange_id' => $all_exchanges[4]->id,
            'offer_type'       => 'exchange_with_assets',
            'offer_details'    => wp_json_encode(array(
                'message' => 'زمین ۵۰۰ متری سهند با پروانه ساخت ۵ طبقه',
            )),
            'cash_offered'     => 0,
            'assets_offered'   => 'زمین مسکونی ۵۰۰ متری شهرک سهند + پروانه ساخت',
            'message'          => 'زمین من ۳۰ میلیارد ارزش داره و با پروانه ساخت ۵ طبقه‌ای خیلی مناسب سرمایه‌گذاری است.',
            'status'           => 'accepted',
            'responded_at'     => current_time('mysql'),
        ), array('%d','%d','%d','%s','%s','%d','%s','%s','%s','%s'));
    }


    /**
     * Create sample matches between compatible listings
     */
    private static function create_sample_matches() {
        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_matches';
        $exchanges = $wpdb->prefix . 'moaveze_exchanges';

        // Check if matches exist
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($existing > 0) return;

        $all = $wpdb->get_results("SELECT id, property_value, property_type, district, exchange_type, desired_property_type, desired_min_value, desired_max_value FROM $exchanges ORDER BY id LIMIT 6");
        if (count($all) < 6) return;

        // Match 1: Apartment Valiasr (wants villa) ↔ Villa Baghmishe (wants apartment)
        // Score: High - A wants villa and B IS villa, B wants apartment and A IS apartment
        $wpdb->insert($table, array(
            'exchange_id_a' => $all[0]->id,
            'exchange_id_b' => $all[1]->id,
            'match_score'   => 82.50,
            'match_type'    => 'direct',
            'match_details' => wp_json_encode(array(
                'reason' => 'آپارتمان ولیعصر ویلا می‌خواهد و ویلای باغمیشه آپارتمان می‌خواهد',
                'value_diff' => abs($all[0]->property_value - $all[1]->property_value),
                'compatible_types' => true,
                'cash_compatible' => true,
            )),
            'status' => 'new',
        ), array('%d','%d','%f','%s','%s','%s'));

        // Match 2: Apartment Roshdieh (wants apartment 18-25B) ↔ Apartment Valiasr (18B)
        $wpdb->insert($table, array(
            'exchange_id_a' => $all[3]->id,
            'exchange_id_b' => $all[0]->id,
            'match_score'   => 75.00,
            'match_type'    => 'direct',
            'match_details' => wp_json_encode(array(
                'reason' => 'آپارتمان رشدیه خواهان آپارتمان ۱۸-۲۵ میلیاردی است و آپارتمان ولیعصر ۱۸ میلیارد ارزش دارد',
                'value_diff' => 4000000000,
                'compatible_types' => true,
                'cash_compatible' => true,
            )),
            'status' => 'new',
        ), array('%d','%d','%f','%s','%s','%s'));

        // Match 3: Land Sahand (wants apt) ↔ Apartment Roshdieh (14B in range 20-30B? lower but close)
        $wpdb->insert($table, array(
            'exchange_id_a' => $all[4]->id,
            'exchange_id_b' => $all[3]->id,
            'match_score'   => 45.00,
            'match_type'    => 'direct',
            'match_details' => wp_json_encode(array(
                'reason' => 'زمین سهند آپارتمان می‌خواهد، آپارتمان رشدیه کمی از محدوده ارزش پایین‌تر است',
                'value_diff' => 16000000000,
                'partial_match' => true,
            )),
            'status' => 'new',
        ), array('%d','%d','%f','%s','%s','%s'));

        // Match 4: Penthouse (wants villa 55-75B) ↔ Villa Baghmishe (45B - close!)
        $wpdb->insert($table, array(
            'exchange_id_a' => $all[5]->id,
            'exchange_id_b' => $all[1]->id,
            'match_score'   => 62.50,
            'match_type'    => 'direct',
            'match_details' => wp_json_encode(array(
                'reason' => 'پنت‌هاوس ویلا می‌خواهد و ویلای باغمیشه ارزشی نزدیک به محدوده مورد نظر دارد',
                'value_diff' => 20000000000,
                'type_match' => true,
            )),
            'status' => 'assigned',
            'consultant_id' => get_current_user_id(),
            'consultant_notes' => 'در حال بررسی. اختلاف قیمت ۲۰ میلیارد قابل مذاکره است.',
        ), array('%d','%d','%f','%s','%s','%s','%d','%s'));

        // Chain swap: Apt Roshdieh → Apt Valiasr → Villa Baghmishe → needs something Roshdieh has
        $chain_ids = array($all[3]->id, $all[0]->id, $all[1]->id);
        $wpdb->insert($wpdb->prefix . 'moaveze_chains', array(
            'chain_hash'    => md5(implode('-', $chain_ids)),
            'exchange_ids'  => wp_json_encode($chain_ids),
            'chain_length'  => 3,
            'total_value'   => $all[3]->property_value + $all[0]->property_value + $all[1]->property_value,
            'status'        => 'detected',
        ), array('%s','%s','%d','%d','%s'));
    }


    /**
     * Clear all sample data
     */
    public static function clear() {
        if (wp_doing_ajax()) {
            check_ajax_referer('moaveze_admin_nonce', 'nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        global $wpdb;

        // Delete all exchange posts
        $posts = get_posts(array(
            'post_type'      => 'moaveze_exchange',
            'numberposts'    => -1,
            'post_status'    => 'any',
        ));

        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }

        // Clear custom tables
        $tables = array(
            'moaveze_exchanges',
            'moaveze_offers',
            'moaveze_matches',
            'moaveze_chains',
            'moaveze_contact_views',
            'moaveze_notifications',
        );

        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");
        }

        if (wp_doing_ajax()) {
            wp_send_json_success(array('message' => 'تمام داده‌های نمونه پاک شدند'));
        }
    }
}

Moaveze_Sample_Data::init();
