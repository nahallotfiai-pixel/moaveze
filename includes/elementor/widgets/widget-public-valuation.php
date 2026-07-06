<?php
/**
 * Elementor Widget: Public Valuation
 * Renders the public property valuation form via shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Public_Valuation extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_public_valuation';
    }

    public function get_title() {
        return 'ارزش‌گذاری ملک (عمومی)';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    protected function register_controls() {
        $this->register_style_section();
    }

    protected function render() {
        $this->render_wrapper_start();
        echo do_shortcode('[moaveze_public_valuation]');
        $this->render_wrapper_end();
    }
}
