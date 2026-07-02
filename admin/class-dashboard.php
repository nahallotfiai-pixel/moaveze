<?php
/**
 * Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Dashboard {

    /**
     * Get listings pending review (status = pending), newest first.
     * Used by the new "در انتظار تأیید" dashboard panel.
     */
    public static function get_pending_listings($limit = 10) {
        $posts = get_posts(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'pending',
            'numberposts'    => $limit,
            'orderby'        => 'date',
            'order'          => 'ASC', // oldest pending first
        ));

        $items = array();
        foreach ($posts as $post) {
            $items[] = array(
                'id'            => $post->ID,
                'title'         => $post->post_title,
                'author'        => get_the_author_meta('display_name', $post->post_author),
                'date'          => $post->post_date,
                'value'         => get_post_meta($post->ID, '_moaveze_property_value', true),
                'district'      => wp_get_post_terms($post->ID, 'moaveze_district', array('fields' => 'names')),
                'type'          => wp_get_post_terms($post->ID, 'moaveze_property_type', array('fields' => 'names')),
                'thumbnail'     => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
                'edit_link'     => get_edit_post_link($post->ID, 'raw'),
                'preview_link'  => get_preview_post_link($post->ID),
            );
        }
        return $items;
    }

    /**
     * Get dashboard stats
     */
    public static function get_stats() {
        global $wpdb;

        $stats = array();

        // Total exchanges
        $stats['total_exchanges'] = wp_count_posts('moaveze_exchange')->publish;
        $stats['pending_exchanges'] = wp_count_posts('moaveze_exchange')->pending;

        // Matches
        $table_matches = $wpdb->prefix . 'moaveze_matches';
        $stats['total_matches'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_matches");
        $stats['new_matches'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_matches WHERE status = 'new'");

        // Offers
        $table_offers = $wpdb->prefix . 'moaveze_offers';
        $stats['total_offers'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_offers");
        $stats['pending_offers'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_offers WHERE status = 'pending'");

        // Revenue
        $table_views = $wpdb->prefix . 'moaveze_contact_views';
        $stats['total_revenue'] = $wpdb->get_var("SELECT SUM(payment_amount) FROM $table_views") ?: 0;
        $stats['monthly_revenue'] = $wpdb->get_var(
            "SELECT SUM(payment_amount) FROM $table_views WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
        ) ?: 0;

        // Chain Swaps
        $table_chains = $wpdb->prefix . 'moaveze_chains';
        $stats['active_chains'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_chains WHERE status = 'detected'");

        return $stats;
    }

    /**
     * Get recent activity
     */
    public static function get_recent_activity($limit = 10) {
        global $wpdb;

        $activities = array();

        // Recent exchanges
        $recent_posts = get_posts(array(
            'post_type'   => 'moaveze_exchange',
            'numberposts' => $limit,
            'post_status' => array('publish', 'pending'),
            'orderby'     => 'date',
            'order'       => 'DESC',
        ));

        foreach ($recent_posts as $post) {
            $activities[] = array(
                'type'    => 'exchange',
                'title'   => $post->post_title,
                'status'  => $post->post_status,
                'date'    => $post->post_date,
                'user_id' => $post->post_author,
                'link'    => get_edit_post_link($post->ID),
            );
        }

        return $activities;
    }
}
