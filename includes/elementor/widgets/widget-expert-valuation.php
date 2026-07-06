<?php
/**
 * Elementor Widget: Expert Valuation
 * Displays expert/AI valuation for the current post
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Expert_Valuation extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_expert_valuation';
    }

    public function get_title() {
        return 'قیمت کارشناسی ملک';
    }

    public function get_icon() {
        return 'eicon-price-table';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('show_range', array(
            'label'   => 'نمایش بازه قیمت',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('show_type_badge', array(
            'label'   => 'نمایش نشان نوع ارزیابی',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('card_style', array(
            'label'   => 'استایل کارت',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'modern',
            'options' => array(
                'modern'  => 'مدرن',
                'minimal' => 'مینیمال',
            ),
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $post_id = get_the_ID();
        if (!$post_id) return;

        $post_type = get_post_type($post_id);
        if (!in_array($post_type, array('moaveze_exchange', 'property'), true)) return;

        $expert_value = get_post_meta($post_id, '_moaveze_expert_value', true);
        if (empty($expert_value)) return;

        $expert_min   = get_post_meta($post_id, '_moaveze_expert_min', true);
        $expert_max   = get_post_meta($post_id, '_moaveze_expert_max', true);
        $value_type   = get_post_meta($post_id, '_moaveze_expert_value_type', true);
        $value_date   = get_post_meta($post_id, '_moaveze_expert_value_date', true);

        $settings     = $this->get_settings_for_display();
        $show_range   = $settings['show_range'] === 'yes';
        $show_badge   = $settings['show_type_badge'] === 'yes';
        $card_style   = $settings['card_style'];

        $formatted_value = Moaveze_Helpers::short_price($expert_value);
        $formatted_min   = $expert_min ? Moaveze_Helpers::short_price($expert_min) : '';
        $formatted_max   = $expert_max ? Moaveze_Helpers::short_price($expert_max) : '';
        $formatted_date  = $value_date ? Moaveze_Helpers::jalali_date($value_date) : '';

        $is_modern = $card_style === 'modern';

        $this->render_wrapper_start();

        $card_bg     = $is_modern ? '#f8fafc' : '#ffffff';
        $card_border = $is_modern ? '1px solid #e2e8f0' : '1px solid #f1f5f9';
        $card_radius = $is_modern ? '12px' : '6px';
        $card_shadow = $is_modern ? '0 4px 12px rgba(0,0,0,0.06)' : 'none';
        $padding     = $is_modern ? '20px' : '14px';

        echo '<div style="background:' . $card_bg . ';border:' . $card_border . ';border-radius:' . $card_radius . ';box-shadow:' . $card_shadow . ';padding:' . $padding . ';direction:rtl;font-family:inherit;">';

        // Title
        echo '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">';
        echo '<h4 style="margin:0;font-size:14px;font-weight:600;color:#374151;">قیمت کارشناسی تبریز هوم</h4>';

        // Badge
        if ($show_badge && $value_type) {
            $badge_label = ($value_type === 'ai') ? 'هوش مصنوعی' : 'کارشناس';
            $badge_color = ($value_type === 'ai') ? '#8b5cf6' : '#059669';
            echo '<span style="display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;color:#ffffff;background:' . esc_attr($badge_color) . ';">' . esc_html($badge_label) . '</span>';
        }
        echo '</div>';

        // Main value
        echo '<div style="font-size:20px;font-weight:700;color:#1f2937;margin-bottom:8px;">' . esc_html($formatted_value) . '</div>';

        // Range
        if ($show_range && $formatted_min && $formatted_max) {
            echo '<div style="font-size:12px;color:#6b7280;margin-bottom:8px;">';
            echo 'از ' . esc_html($formatted_min) . ' تا ' . esc_html($formatted_max);
            echo '</div>';
        }

        // Date
        if ($formatted_date) {
            echo '<div style="font-size:11px;color:#9ca3af;margin-top:4px;">تاریخ ارزیابی: ' . esc_html($formatted_date) . '</div>';
        }

        echo '</div>';
        $this->render_wrapper_end();
    }
}
