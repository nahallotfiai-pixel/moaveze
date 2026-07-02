<?php
/**
 * REST API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_REST_API {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST routes
     */
    public function register_routes() {
        $namespace = 'moaveze/v1';

        // Get exchanges
        register_rest_route($namespace, '/exchanges', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_exchanges'),
            'permission_callback' => '__return_true',
        ));

        // Get single exchange
        register_rest_route($namespace, '/exchanges/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_single_exchange'),
            'permission_callback' => '__return_true',
        ));

        // Get matches for exchange
        register_rest_route($namespace, '/exchanges/(?P<id>\d+)/matches', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_exchange_matches'),
            'permission_callback' => array($this, 'check_authenticated'),
        ));

        // Map data
        register_rest_route($namespace, '/map-data', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_map_data'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get exchanges list
     */
    public function get_exchanges($request) {
        $per_page = $request->get_param('per_page') ?: 12;
        $page = $request->get_param('page') ?: 1;
        $type = $request->get_param('type');
        $district = $request->get_param('district');

        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        if ($type) {
            $args['tax_query'][] = array('taxonomy' => 'moaveze_property_type', 'field' => 'slug', 'terms' => $type);
        }
        if ($district) {
            $args['tax_query'][] = array('taxonomy' => 'moaveze_district', 'field' => 'slug', 'terms' => $district);
        }

        $query = new WP_Query($args);
        $data = array();

        while ($query->have_posts()) {
            $query->the_post();
            $data[] = $this->format_exchange(get_the_ID());
        }
        wp_reset_postdata();

        return new WP_REST_Response(array(
            'data'       => $data,
            'total'      => $query->found_posts,
            'pages'      => $query->max_num_pages,
            'current'    => $page,
        ), 200);
    }

    /**
     * Get single exchange
     */
    public function get_single_exchange($request) {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== 'moaveze_exchange') {
            return new WP_REST_Response(array('error' => 'Not found'), 404);
        }

        return new WP_REST_Response($this->format_exchange($id, true), 200);
    }

    /**
     * Get matches
     */
    public function get_exchange_matches($request) {
        global $wpdb;
        $id = $request->get_param('id');

        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_matches 
             WHERE exchange_id_a = %d OR exchange_id_b = %d
             ORDER BY match_score DESC",
            $id, $id
        ));

        return new WP_REST_Response(array('matches' => $matches), 200);
    }

    /**
     * Get map data
     */
    public function get_map_data() {
        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'meta_query'     => array(
                array('key' => '_moaveze_latitude', 'value' => '', 'compare' => '!='),
            ),
        );

        $query = new WP_Query($args);
        $markers = array();

        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $markers[] = array(
                'id'    => $id,
                'title' => get_the_title(),
                'lat'   => floatval(get_post_meta($id, '_moaveze_latitude', true)),
                'lng'   => floatval(get_post_meta($id, '_moaveze_longitude', true)),
                'value' => absint(get_post_meta($id, '_moaveze_property_value', true)),
                'url'   => get_permalink(),
            );
        }
        wp_reset_postdata();

        return new WP_REST_Response(array('markers' => $markers), 200);
    }

    /**
     * Format exchange data for API
     */
    private function format_exchange($post_id, $detailed = false) {
        $data = array(
            'id'             => $post_id,
            'title'          => get_the_title($post_id),
            'url'            => get_permalink($post_id),
            'value'          => absint(get_post_meta($post_id, '_moaveze_property_value', true)),
            'area'           => absint(get_post_meta($post_id, '_moaveze_area_sqm', true)),
            'exchange_type'  => get_post_meta($post_id, '_moaveze_exchange_type', true),
            'image'          => get_the_post_thumbnail_url($post_id, 'medium'),
            'verified'       => (bool) get_post_meta($post_id, '_moaveze_verified', true),
        );

        if ($detailed) {
            $data['rooms'] = get_post_meta($post_id, '_moaveze_rooms', true);
            $data['floor'] = get_post_meta($post_id, '_moaveze_floor', true);
            $data['year_built'] = get_post_meta($post_id, '_moaveze_year_built', true);
            $data['description'] = get_the_content(null, false, $post_id);
            $data['lat'] = get_post_meta($post_id, '_moaveze_latitude', true);
            $data['lng'] = get_post_meta($post_id, '_moaveze_longitude', true);
            $data['cash_difference'] = get_post_meta($post_id, '_moaveze_cash_difference', true);
            $data['cash_direction'] = get_post_meta($post_id, '_moaveze_cash_direction', true);
        }

        return $data;
    }

    /**
     * Check authentication
     */
    public function check_authenticated($request) {
        return is_user_logged_in();
    }
}

new Moaveze_REST_API();
