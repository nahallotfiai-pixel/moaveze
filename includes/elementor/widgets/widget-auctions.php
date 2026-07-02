<?php
/**
 * Elementor Widget: Auctions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Auctions extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_auctions';
    }

    public function get_title() {
        return 'مزایده‌های معاوضه';
    }

    public function get_icon() {
        return 'eicon-price-table';
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
            'max'     => 24,
        ));

        $this->add_control('show_ended', array(
            'label'   => 'نمایش پایان‌یافته‌ها',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
        ));

        $this->add_control('show_countdown', array(
            'label'   => 'نمایش شمارش معکوس',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_auctions]');
        $this->render_wrapper_end();
    }
}
