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
     * Render property details meta box
     */
    public static function render_property_details_box($post) {
        wp_nonce_field('moaveze_save_meta', 'moaveze_meta_nonce');
        $fields = self::get_fields();
        $property_fields = array(
            'property_value', 'area_sqm', 'rooms', 'floor',
            'total_floors', 'year_built', 'parking', 'elevator',
            'storage', 'balcony', 'latitude', 'longitude', 'address'
        );

        echo '<div class="moaveze-meta-box">';
        foreach ($property_fields as $key) {
            $value = get_post_meta($post->ID, '_moaveze_' . $key, true);
            $field = $fields[$key];
            self::render_field($key, $field, $value);
        }
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
    private static function render_field($key, $field, $value) {
        $name = 'moaveze_' . $key;
        echo '<div class="moaveze-field">';
        echo '<label for="' . esc_attr($name) . '">' . esc_html($field['label']) . '</label>';

        switch ($field['type']) {
            case 'textarea':
                echo '<textarea id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" rows="3">' . esc_textarea($value) . '</textarea>';
                break;
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="1" ' . checked($value, '1', false) . '>';
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
                $terms = get_terms(array(
                    'taxonomy'   => 'moaveze_property_type',
                    'hide_empty' => false,
                ));
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $options[$term->slug] = $term->name;
                    }
                }
                break;
        }

        return $options;
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
    }
}

// Initialize
Moaveze_Meta_Fields::init();
