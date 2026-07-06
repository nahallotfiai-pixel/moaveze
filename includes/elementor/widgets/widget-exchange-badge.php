<?php
/**
 * Elementor Widget: Exchange Badge
 * Shows exchange availability box with CTA button on Houzez property pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Exchange_Badge extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_exchange_badge';
    }

    public function get_title() {
        return 'باکس قابلیت معاوضه';
    }

    public function get_icon() {
        return 'eicon-info-circle';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('box_style', array(
            'label'   => 'استایل باکس',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'prominent',
            'options' => array(
                'prominent' => 'برجسته',
                'minimal'   => 'مینیمال',
                'inline'    => 'خطی',
            ),
        ));

        $this->add_control('button_text', array(
            'label'   => 'متن دکمه',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'مشاهده شرایط معاوضه',
        ));

        $this->add_control('show_icon', array(
            'label'   => 'نمایش آیکون',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $post_id = get_the_ID();
        if (!$post_id) return;

        $exchange_linked = get_post_meta($post_id, '_moaveze_exchange_linked', true);
        if (empty($exchange_linked)) return;

        $settings    = $this->get_settings_for_display();
        $box_style   = $settings['box_style'];
        $button_text = $settings['button_text'];
        $show_icon   = $settings['show_icon'] === 'yes';

        $exchange_url = get_permalink($exchange_linked);
        if (!$exchange_url) return;

        $this->render_wrapper_start();

        // Style variations
        if ($box_style === 'prominent') {
            $box_css = 'background:linear-gradient(135deg,#ede9fe,#f0fdf4);border:2px solid #a78bfa;border-radius:12px;padding:20px;direction:rtl;text-align:center;';
            $text_css = 'font-size:15px;font-weight:600;color:#374151;margin:0 0 12px 0;';
            $btn_css = 'display:inline-block;padding:10px 24px;background:#6366f1;color:#ffffff;border-radius:8px;text-decoration:none;font-size:14px;font-weight:500;transition:background 0.2s;';
        } elseif ($box_style === 'minimal') {
            $box_css = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;direction:rtl;text-align:center;';
            $text_css = 'font-size:13px;font-weight:500;color:#4b5563;margin:0 0 10px 0;';
            $btn_css = 'display:inline-block;padding:8px 18px;background:#4f46e5;color:#ffffff;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;';
        } else { // inline
            $box_css = 'display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;direction:rtl;';
            $text_css = 'font-size:13px;font-weight:500;color:#374151;margin:0;flex:1;';
            $btn_css = 'display:inline-block;padding:6px 14px;background:#6366f1;color:#ffffff;border-radius:6px;text-decoration:none;font-size:12px;font-weight:500;white-space:nowrap;';
        }

        echo '<div style="' . $box_css . '">';

        // Icon + text
        $icon_html = '';
        if ($show_icon) {
            $icon_html = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-left:6px;"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path></svg>';
        }

        if ($box_style === 'inline') {
            echo '<p style="' . $text_css . '">' . $icon_html . 'این ملک قابل معاوضه است</p>';
            echo '<a href="' . esc_url($exchange_url) . '" style="' . $btn_css . '">' . esc_html($button_text) . '</a>';
        } else {
            if ($show_icon) {
                echo '<div style="margin-bottom:8px;">' . $icon_html . '</div>';
            }
            echo '<p style="' . $text_css . '">این ملک قابل معاوضه است</p>';
            echo '<a href="' . esc_url($exchange_url) . '" style="' . $btn_css . '">' . esc_html($button_text) . '</a>';
        }

        echo '</div>';
        $this->render_wrapper_end();
    }
}
