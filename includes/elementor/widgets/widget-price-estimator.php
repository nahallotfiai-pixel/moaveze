<?php
/**
 * Elementor Widget: Price Estimator
 * Renders the price estimator via shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Price_Estimator extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_price_estimator';
    }

    public function get_title() {
        return 'تخمین‌گر قیمت ملک';
    }

    public function get_icon() {
        return 'eicon-calculator';
    }

    protected function register_controls() {
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_price_estimator]');
        $this->render_wrapper_end();
    }
}
