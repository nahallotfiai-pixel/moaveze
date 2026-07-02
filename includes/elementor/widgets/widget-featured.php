<?php
/**
 * Elementor Widget: Featured Exchanges
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Featured extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_featured';
    }

    public function get_title() {
        return 'آگهی‌های ویژه معاوضه';
    }

    public function get_icon() {
        return 'eicon-star';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('count', array(
            'label'   => 'تعداد',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
            'min'     => 2,
            'max'     => 12,
        ));

        $this->add_control('layout', array(
            'label'   => 'چیدمان',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'slider',
            'options' => array(
                'slider' => 'اسلایدر',
                'grid'   => 'شبکه‌ای',
            ),
        ));

        $this->add_control('autoplay', array(
            'label'   => 'پخش خودکار اسلایدر',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'condition' => array('layout' => 'slider'),
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $this->render_wrapper_start();
        echo do_shortcode("[moaveze_featured count=\"{$s['count']}\"]");
        $this->render_wrapper_end();
    }
}
