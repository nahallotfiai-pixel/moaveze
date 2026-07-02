<?php
/**
 * Elementor Widget: Wishlist
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Wishlist extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_wishlist';
    }

    public function get_title() {
        return 'لیست آرزو';
    }

    public function get_icon() {
        return 'eicon-heart-o';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('max_items', array(
            'label'   => 'حداکثر موارد',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 3,
            'min'     => 1,
            'max'     => 10,
        ));

        $this->add_control('show_form', array(
            'label'   => 'نمایش فرم افزودن',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_wishlist]');
        $this->render_wrapper_end();
    }
}
