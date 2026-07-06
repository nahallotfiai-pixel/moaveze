<?php
/**
 * Elementor Widget: Property Gallery
 * Displays gallery images for the current exchange listing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Widget_Property_Gallery extends Moaveze_Elementor_Base_Widget {

    public function get_name() {
        return 'moaveze_property_gallery';
    }

    public function get_title() {
        return 'گالری تصاویر ملک';
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    protected function register_controls() {
        $this->start_controls_section('content_section', array(
            'label' => 'محتوا',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ));

        $this->add_control('columns', array(
            'label'   => 'تعداد ستون',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'options' => array(
                '2' => '۲ ستون',
                '3' => '۳ ستون',
                '4' => '۴ ستون',
            ),
        ));

        $this->add_control('image_height', array(
            'label'   => 'ارتفاع تصاویر',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'range'   => array('px' => array('min' => 100, 'max' => 400)),
            'default' => array('size' => 200, 'unit' => 'px'),
        ));

        $this->add_control('lightbox', array(
            'label'   => 'لایت‌باکس',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->add_control('show_count', array(
            'label'   => 'نمایش تعداد تصاویر',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));

        $this->end_controls_section();
        $this->register_style_section();
    }

    protected function render() {
        $post_id = get_the_ID();
        if (!$post_id) return;

        $settings     = $this->get_settings_for_display();
        $columns      = intval($settings['columns']);
        $image_height = isset($settings['image_height']['size']) ? intval($settings['image_height']['size']) : 200;
        $lightbox     = $settings['lightbox'] === 'yes';
        $show_count   = $settings['show_count'] === 'yes';

        // Gather images: featured image + gallery meta
        $images = array();

        $featured_id = get_post_thumbnail_id($post_id);
        if ($featured_id) {
            $images[] = $featured_id;
        }

        $gallery = get_post_meta($post_id, '_moaveze_gallery', true);
        if (!empty($gallery)) {
            if (is_string($gallery)) {
                $gallery_ids = array_filter(array_map('intval', explode(',', $gallery)));
            } elseif (is_array($gallery)) {
                $gallery_ids = array_filter(array_map('intval', $gallery));
            } else {
                $gallery_ids = array();
            }
            foreach ($gallery_ids as $gid) {
                if (!in_array($gid, $images, true)) {
                    $images[] = $gid;
                }
            }
        }

        $this->render_wrapper_start();

        // No images placeholder
        if (empty($images)) {
            echo '<div style="background:#f3f4f6;border:2px dashed #d1d5db;border-radius:12px;padding:40px;text-align:center;direction:rtl;">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
            echo '<p style="margin:0;color:#6b7280;font-size:14px;">تصویری برای نمایش وجود ندارد</p>';
            echo '</div>';
            $this->render_wrapper_end();
            return;
        }

        $total_images = count($images);

        // Count badge
        if ($show_count) {
            echo '<div style="margin-bottom:10px;direction:rtl;font-size:13px;color:#6b7280;">';
            echo Moaveze_Helpers::to_persian_digits($total_images) . ' تصویر';
            echo '</div>';
        }

        // Gallery grid
        echo '<div style="display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:8px;direction:rtl;">';

        $gallery_id = 'moaveze-gallery-' . $post_id;

        foreach ($images as $attachment_id) {
            $full_url  = wp_get_attachment_image_url($attachment_id, 'full');
            $thumb_url = wp_get_attachment_image_url($attachment_id, 'medium_large');
            $alt       = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

            if (!$thumb_url) continue;

            $img_style = 'width:100%;height:' . $image_height . 'px;object-fit:cover;border-radius:8px;display:block;';

            if ($lightbox && $full_url) {
                echo '<a href="' . esc_url($full_url) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="' . esc_attr($gallery_id) . '" style="display:block;overflow:hidden;border-radius:8px;">';
                echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt) . '" style="' . $img_style . 'cursor:pointer;transition:transform 0.3s;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'" />';
                echo '</a>';
            } else {
                echo '<div style="overflow:hidden;border-radius:8px;">';
                echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt) . '" style="' . $img_style . '" />';
                echo '</div>';
            }
        }

        echo '</div>';
        $this->render_wrapper_end();
    }
}
