<?php
/**
 * Elementor Widget: Submission Form
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Submit_Form extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_submit_form';
    }

    public function get_title() {
        return 'فرم ثبت آگهی معاوضه';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('form_title', array(
            'label'   => 'عنوان فرم',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'ثبت آگهی معاوضه',
        ));

        $this->add_control('show_progress', array(
            'label'   => 'نمایش نوار پیشرفت',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('require_login', array(
            'label'       => 'نیاز به ورود',
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'description' => 'از تنظیمات عمومی افزونه پیروی می‌کند',
        ));

        $this->add_control('success_message', array(
            'label'   => 'پیام موفقیت',
            'type'    => \Elementor\Controls_Manager::TEXTAREA,
            'default' => 'آگهی شما با موفقیت ثبت شد! پس از بررسی منتشر خواهد شد.',
        ));

        $this->end_controls_section();

        // Style
        $this->start_controls_section('form_style', array(
            'label' => 'استایل فرم',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ));

        $this->add_control('form_bg', array(
            'label'     => 'رنگ پس‌زمینه',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => array('{{WRAPPER}} .step-content' => 'background-color: {{VALUE}};'),
        ));

        $this->add_control('input_border_radius', array(
            'label'   => 'گردی فیلدها',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 0, 'max' => 20)),
            'default' => array('size' => 10, 'unit' => 'px'),
            'selectors' => array(
                '{{WRAPPER}} .moaveze-field input, {{WRAPPER}} .moaveze-field select, {{WRAPPER}} .moaveze-field textarea' => 'border-radius: {{SIZE}}{{UNIT}};',
            ),
        ));

        $this->add_control('button_bg', array(
            'label'     => 'رنگ دکمه‌ها',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#6366f1',
            'selectors' => array(
                '{{WRAPPER}} .moaveze-btn-primary' => 'background: {{VALUE}};',
            ),
        ));

        $this->end_controls_section();

        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_submit_form]');
        $this->render_wrapper_end();
    }
}
