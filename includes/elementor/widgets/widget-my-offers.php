<?php
/**
 * Elementor Widget: My Offers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_My_Offers extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_my_offers';
    }

    public function get_title() {
        return 'پیشنهادات من';
    }

    public function get_icon() {
        return 'eicon-mail';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('show_stats', array(
            'label'   => 'نمایش آمار وضعیت',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('show_filters', array(
            'label'   => 'نمایش فیلتر وضعیت',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_my_offers]');
        $this->render_wrapper_end();
    }
}
