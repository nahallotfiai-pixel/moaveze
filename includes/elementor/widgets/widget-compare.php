<?php
/**
 * Elementor Widget: Compare Listings
 * Renders the compare listings feature via shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Compare extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_compare';
    }

    public function get_title() {
        return 'مقایسه آگهی‌ها';
    }

    public function get_icon() {
        return 'eicon-column';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('max_items', array(
            'label'   => 'حداکثر موارد مقایسه',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 3,
            'min'     => 2,
            'max'     => 5,
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $max_items = intval($settings['max_items']);

        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_compare max_items="' . $max_items . '"]');
        $this->render_wrapper_end();
    }
}
