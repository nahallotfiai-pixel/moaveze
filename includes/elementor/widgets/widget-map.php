<?php
/**
 * Elementor Widget: Interactive Map
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Map extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_map';
    }

    public function get_title() {
        return 'نقشه تعاملی معاوضه';
    }

    public function get_icon() {
        return 'eicon-google-maps';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('height', array(
            'label'   => 'ارتفاع نقشه',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 300, 'max' => 900)),
            'default' => array('size' => 600, 'unit' => 'px'),
        ));

        $this->add_control('property_type', array(
            'label'       => 'فیلتر نوع ملک',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'خالی = همه',
        ));

        $this->add_control('district', array(
            'label'       => 'فیلتر منطقه',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'خالی = همه',
        ));

        $this->add_control('show_sidebar', array(
            'label'   => 'نمایش سایدبار',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $this->render_wrapper_start();
        $height = $s['height']['size'] . $s['height']['unit'];
        echo do_shortcode("[moaveze_map height=\"{$height}\" type=\"{$s['property_type']}\" district=\"{$s['district']}\"]");
        $this->render_wrapper_end();
    }
}
