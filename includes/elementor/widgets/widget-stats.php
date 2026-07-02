<?php
/**
 * Elementor Widget: Public Stats Counter
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Stats extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_stats';
    }

    public function get_title() {
        return 'آمار معاوضه';
    }

    public function get_icon() {
        return 'eicon-counter';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('show_listings', array(
            'label'   => 'نمایش تعداد آگهی',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('show_matches', array(
            'label'   => 'نمایش معاوضه موفق',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('show_users', array(
            'label'   => 'نمایش کاربران',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('animation_duration', array(
            'label'   => 'مدت انیمیشن (ثانیه)',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 500, 'max' => 5000)),
            'default' => array('size' => 2000),
        ));

        $this->end_controls_section();

        // Style
        $this->start_controls_section('stat_style', array(
            'label' => 'استایل اعداد',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ));

        $this->add_control('number_color', array(
            'label'     => 'رنگ اعداد',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => array('{{WRAPPER}} .stat-number' => 'color: {{VALUE}};'),
        ));

        $this->add_control('number_size', array(
            'label'   => 'اندازه اعداد',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 20, 'max' => 80)),
            'default' => array('size' => 36, 'unit' => 'px'),
            'selectors' => array('{{WRAPPER}} .stat-number' => 'font-size: {{SIZE}}{{UNIT}};'),
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_stats]');
        $this->render_wrapper_end();
    }
}
