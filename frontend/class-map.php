<?php
/**
 * Interactive Map Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Map {

    public function __construct() {
        add_shortcode('moaveze_map', array($this, 'render_map'));
        add_action('wp_ajax_moaveze_get_map_data', array($this, 'get_map_data'));
        add_action('wp_ajax_nopriv_moaveze_get_map_data', array($this, 'get_map_data'));
    }

    /**
     * Render full-page map shortcode
     */
    public function render_map($atts) {
        $atts = shortcode_atts(array(
            'height'  => '600px',
            'type'    => '',
            'district' => '',
        ), $atts);

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-fullmap-container" style="height:<?php echo esc_attr($atts['height']); ?>">
                <div class="moaveze-fullmap" id="moaveze-fullmap"
                     data-type="<?php echo esc_attr($atts['type']); ?>"
                     data-district="<?php echo esc_attr($atts['district']); ?>">
                </div>
                <div class="moaveze-map-sidebar" id="map-sidebar">
                    <div class="map-sidebar-header">
                        <h3>ملک‌های معاوضه‌ای</h3>
                        <span id="map-results-count">0 مورد</span>
                    </div>
                    <div class="map-sidebar-list" id="map-sidebar-list">
                        <!-- Cards populated by JS -->
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get map markers data via AJAX
     */
    public function get_map_data() {
        $type = sanitize_text_field($_GET['type'] ?? '');
        $district = sanitize_text_field($_GET['district'] ?? '');
        $min_value = absint($_GET['min_value'] ?? 0);
        $max_value = absint($_GET['max_value'] ?? 0);

        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'meta_query'     => array(
                array(
                    'key'     => '_moaveze_latitude',
                    'value'   => '',
                    'compare' => '!=',
                ),
            ),
        );

        $tax_query = array();
        if ($type) {
            $tax_query[] = array('taxonomy' => 'moaveze_property_type', 'field' => 'slug', 'terms' => $type);
        }
        if ($district) {
            $tax_query[] = array('taxonomy' => 'moaveze_district', 'field' => 'slug', 'terms' => $district);
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        if ($min_value || $max_value) {
            if ($min_value) {
                $args['meta_query'][] = array('key' => '_moaveze_property_value', 'value' => $min_value, 'compare' => '>=', 'type' => 'NUMERIC');
            }
            if ($max_value) {
                $args['meta_query'][] = array('key' => '_moaveze_property_value', 'value' => $max_value, 'compare' => '<=', 'type' => 'NUMERIC');
            }
        }

        $query = new WP_Query($args);
        $markers = array();

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $lat = get_post_meta($id, '_moaveze_latitude', true);
            $lng = get_post_meta($id, '_moaveze_longitude', true);

            if (!$lat || !$lng) continue;

            $type_terms = get_the_terms($id, 'moaveze_property_type');
            $district_terms = get_the_terms($id, 'moaveze_district');

            $markers[] = array(
                'id'        => $id,
                'title'     => get_the_title(),
                'lat'       => floatval($lat),
                'lng'       => floatval($lng),
                'value'     => absint(get_post_meta($id, '_moaveze_property_value', true)),
                'area'      => absint(get_post_meta($id, '_moaveze_area_sqm', true)),
                'type'      => $type_terms ? $type_terms[0]->name : '',
                'district'  => $district_terms ? $district_terms[0]->name : '',
                'exchange'  => get_post_meta($id, '_moaveze_exchange_type', true),
                'image'     => get_the_post_thumbnail_url($id, 'thumbnail'),
                'url'       => get_permalink($id),
                'verified'  => (bool) get_post_meta($id, '_moaveze_verified', true),
            );
        }
        wp_reset_postdata();

        wp_send_json_success(array('markers' => $markers, 'count' => count($markers)));
    }
}

new Moaveze_Map();
