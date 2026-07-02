<?php
/**
 * Elementor Widget: Search
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Search extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_search';
    }

    public function get_title() {
        return 'جستجوی معاوضه';
    }

    public function get_icon() {
        return 'eicon-search';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('title', array(
            'label'   => 'عنوان',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'جستجوی معاوضه',
        ));

        $this->add_control('layout', array(
            'label'   => 'چیدمان',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'horizontal',
            'options' => array(
                'horizontal' => 'افقی',
                'vertical'   => 'عمودی',
            ),
        ));

        $this->add_control('show_price_range', array(
            'label'   => 'نمایش فیلتر قیمت',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_search]');
        $this->render_wrapper_end();
    }
}
