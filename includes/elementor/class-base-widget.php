<?php
/**
 * Base Widget Class for Moaveze Plus Elementor Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Moaveze_Elementor_Base_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget category
     */
    public function get_categories() {
        return array('moaveze-plus');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-exchange';
    }

    /**
     * Common style controls for all widgets
     */
    protected function register_style_section() {
        $this->start_controls_section('style_section', array(
            'label' => 'استایل',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ));

        $this->add_control('primary_color', array(
            'label'   => 'رنگ اصلی',
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#6366f1',
            'selectors' => array(
                '{{WRAPPER}} .moaveze-wrapper' => '--moaveze-primary: {{VALUE}};',
            ),
        ));

        $this->add_control('border_radius', array(
            'label'   => 'گردی گوشه‌ها',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 0, 'max' => 30)),
            'default' => array('size' => 12, 'unit' => 'px'),
            'selectors' => array(
                '{{WRAPPER}} .moaveze-wrapper' => '--moaveze-radius: {{SIZE}}{{UNIT}};',
            ),
        ));

        $this->add_control('dark_mode', array(
            'label'   => 'حالت تاریک',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => array(
                'auto'  => 'خودکار',
                'light' => 'روشن',
                'dark'  => 'تاریک',
            ),
        ));

        $this->end_controls_section();
    }

    /**
     * Render wrapper with dark mode class
     */
    protected function render_wrapper_start() {
        $settings = $this->get_settings_for_display();
        $dark_class = '';
        if ($settings['dark_mode'] === 'dark') $dark_class = ' moaveze-force-dark';
        echo '<div class="moaveze-elementor-widget' . $dark_class . '">';
    }

    protected function render_wrapper_end() {
        echo '</div>';
    }
}
