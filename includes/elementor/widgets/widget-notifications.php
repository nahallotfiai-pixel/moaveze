<?php
/**
 * Elementor Widget: Notifications Center
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Notifications extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_notifications';
    }

    public function get_title() {
        return 'مرکز اعلان‌ها';
    }

    public function get_icon() {
        return 'eicon-bell';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('per_page', array(
            'label'   => 'تعداد در هر صفحه',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 15,
            'min'     => 5,
            'max'     => 50,
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_notifications]');
        $this->render_wrapper_end();
    }
}
