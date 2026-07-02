<?php
/**
 * Elementor Widget: Exchange Listings Grid
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Listings extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_listings';
    }

    public function get_title() {
        return 'لیست آگهی‌های معاوضه';
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('per_page', array(
            'label'   => 'تعداد در هر صفحه',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 12,
            'min'     => 3,
            'max'     => 48,
            'step'    => 3,
        ));

        $this->add_control('columns', array(
            'label'   => 'تعداد ستون',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'options' => array(
                '2' => '۲ ستون',
                '3' => '۳ ستون',
                '4' => '۴ ستون',
            ),
        ));

        $this->add_control('card_style', array(
            'label'   => 'استایل کارت',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'modern',
            'options' => array(
                'modern'  => 'مدرن',
                'classic' => 'کلاسیک',
                'minimal' => 'مینیمال',
            ),
        ));

        $this->add_control('show_filters', array(
            'label'   => 'نمایش فیلترها',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('show_map', array(
            'label'   => 'نمایش نقشه',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('property_type', array(
            'label'       => 'فیلتر نوع ملک',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'مثال: آپارتمان',
            'description' => 'خالی = همه انواع',
        ));

        $this->add_control('district', array(
            'label'       => 'فیلتر منطقه',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'مثال: ولیعصر',
            'description' => 'خالی = همه مناطق',
        ));

        $this->add_control('orderby', array(
            'label'   => 'مرتب‌سازی',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => array(
                'date'   => 'جدیدترین',
                'value'  => 'ارزش (بیشترین)',
                'random' => 'تصادفی',
            ),
        ));

        $this->end_controls_section();

        // Layout section
        $this->start_controls_section('layout_section', array(
            'label' => 'چیدمان',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('map_height', array(
            'label'   => 'ارتفاع نقشه',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 200, 'max' => 700)),
            'default' => array('size' => 400, 'unit' => 'px'),
            'condition' => array('show_map' => 'yes'),
        ));

        $this->add_control('card_image_height', array(
            'label'   => 'ارتفاع تصویر کارت',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 120, 'max' => 350)),
            'default' => array('size' => 200, 'unit' => 'px'),
            'selectors' => array(
                '{{WRAPPER}} .moaveze-card-image' => 'height: {{SIZE}}{{UNIT}};',
            ),
        ));

        $this->add_control('gap', array(
            'label'   => 'فاصله بین کارت‌ها',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 8, 'max' => 40)),
            'default' => array('size' => 24, 'unit' => 'px'),
            'selectors' => array(
                '{{WRAPPER}} .moaveze-listings-grid' => 'gap: {{SIZE}}{{UNIT}};',
            ),
        ));

        $this->end_controls_section();

        // Style section
        $this->register_style_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $this->render_wrapper_start();

        $atts = array(
            'per_page'     => $s['per_page'],
            'style'        => $s['card_style'],
            'show_filters' => $s['show_filters'],
            'show_map'     => $s['show_map'],
            'type'         => $s['property_type'],
            'district'     => $s['district'],
        );

        $shortcode = '[moaveze_listings';
        foreach ($atts as $key => $val) {
            if ($val) $shortcode .= " {$key}=\"{$val}\"";
        }
        $shortcode .= ']';

        echo '<style>';
        echo '{{WRAPPER}} .moaveze-listings-grid { grid-template-columns: repeat(' . $s['columns'] . ', 1fr); }';
        if ($s['show_map'] === 'yes' && !empty($s['map_height']['size'])) {
            echo '{{WRAPPER}} .moaveze-listings-map { height: ' . $s['map_height']['size'] . 'px; }';
        }
        echo '</style>';

        echo do_shortcode($shortcode);
        $this->render_wrapper_end();
    }
}
