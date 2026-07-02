<?php
/**
 * Elementor Widget: Recent Exchanges
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Recent extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_recent';
    }

    public function get_title() {
        return 'آخرین آگهی‌های معاوضه';
    }

    public function get_icon() {
        return 'eicon-time-line';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('count', array(
            'label'   => 'تعداد',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 4,
            'min'     => 2,
            'max'     => 12,
        ));

        $this->add_control('layout', array(
            'label'   => 'چیدمان',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'list',
            'options' => array(
                'list' => 'لیستی',
                'grid' => 'شبکه‌ای',
            ),
        ));

        $this->add_control('show_image', array(
            'label'   => 'نمایش تصویر',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $this->render_wrapper_start();
        echo do_shortcode("[moaveze_recent count=\"{$s['count']}\"]");
        $this->render_wrapper_end();
    }
}
