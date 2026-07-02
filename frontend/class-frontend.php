<?php
/**
 * Frontend Main Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Frontend {

    public function __construct() {
        add_action('wp_head', array($this, 'add_custom_css_vars'));
        add_filter('body_class', array($this, 'add_body_classes'));
    }

    /**
     * Add CSS custom properties based on settings
     */
    public function add_custom_css_vars() {
        $primary_color = get_option('moaveze_primary_color', '#6366f1');
        $dark_mode = get_option('moaveze_dark_mode', 'auto');
        ?>
        <style>
            :root {
                --moaveze-primary: <?php echo esc_attr($primary_color); ?>;
                --moaveze-primary-light: <?php echo esc_attr($primary_color); ?>20;
                --moaveze-primary-dark: <?php echo esc_attr($this->darken_color($primary_color, 20)); ?>;
                --moaveze-success: #10b981;
                --moaveze-warning: #f59e0b;
                --moaveze-danger: #ef4444;
                --moaveze-info: #3b82f6;
                --moaveze-radius: 12px;
                --moaveze-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
                --moaveze-shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
            }
            <?php if ($dark_mode === 'dark') : ?>
            .moaveze-wrapper {
                --moaveze-bg: #1a1a2e;
                --moaveze-surface: #16213e;
                --moaveze-text: #e2e8f0;
                --moaveze-text-muted: #94a3b8;
                --moaveze-border: #334155;
            }
            <?php else : ?>
            .moaveze-wrapper {
                --moaveze-bg: #f8fafc;
                --moaveze-surface: #ffffff;
                --moaveze-text: #1e293b;
                --moaveze-text-muted: #64748b;
                --moaveze-border: #e2e8f0;
            }
            <?php endif; ?>
        </style>
        <?php
    }

    /**
     * Add body classes
     */
    public function add_body_classes($classes) {
        $dark_mode = get_option('moaveze_dark_mode', 'auto');
        if ($dark_mode === 'dark') {
            $classes[] = 'moaveze-dark';
        } elseif ($dark_mode === 'auto') {
            $classes[] = 'moaveze-auto-dark';
        }
        return $classes;
    }

    /**
     * Darken a hex color
     */
    private function darken_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - $percent);
        $g = max(0, hexdec(substr($hex, 2, 2)) - $percent);
        $b = max(0, hexdec(substr($hex, 4, 2)) - $percent);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}

new Moaveze_Frontend();
