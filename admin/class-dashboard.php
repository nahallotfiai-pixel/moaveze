<?php
/**
 * Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Dashboard {

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
