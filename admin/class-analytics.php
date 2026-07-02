<?php
/**
 * Advanced Analytics Dashboard
 * Charts, graphs, conversion rates, popular districts, trending types
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Analytics {

    public function __construct() {
        add_action('wp_ajax_moaveze_get_analytics', array($this, 'get_analytics_data'));
        add_action('wp_ajax_moaveze_get_chart_data', array($this, 'get_chart_data'));
    }

    /**
     * Get all analytics data
     */
    public function get_analytics_data() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $period = sanitize_text_field($_POST['period'] ?? '30');

        wp_send_json_success(array(
            'overview'          => $this->get_overview($period),
            'popular_districts' => $this->get_popular_districts($period),
            'trending_types'    => $this->get_trending_types($period),
            'conversion_rates'  => $this->get_conversion_rates($period),
            'revenue_data'      => $this->get_revenue_data($period),
            'activity_timeline' => $this->get_activity_timeline($period),
        ));
    }

    /**
     * Get chart data for specific metric
     */
    public function get_chart_data() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $metric = sanitize_text_field($_POST['metric'] ?? 'listings');
        $period = absint($_POST['period'] ?? 30);

        $data = array();
        switch ($metric) {
            case 'listings':
                $data = $this->get_listings_chart($period);
                break;
            case 'offers':
                $data = $this->get_offers_chart($period);
                break;
            case 'matches':
                $data = $this->get_matches_chart($period);
                break;
            case 'revenue':
                $data = $this->get_revenue_chart($period);
                break;
            case 'views':
                $data = $this->get_views_chart($period);
                break;
        }

        wp_send_json_success($data);
    }

    /**
     * Overview stats
     */
    private function get_overview($days) {
        global $wpdb;
        $ex = $wpdb->prefix . 'moaveze_exchanges';
        $of = $wpdb->prefix . 'moaveze_offers';
        $ma = $wpdb->prefix . 'moaveze_matches';
        $cv = $wpdb->prefix . 'moaveze_contact_views';
        $date_filter = "created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)";

        return array(
            'total_listings'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM $ex WHERE status='active'"),
            'new_listings'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM $ex WHERE $date_filter"),
            'total_offers'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM $of WHERE $date_filter"),
            'accepted_offers'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM $of WHERE status='accepted' AND $date_filter"),
            'total_matches'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM $ma WHERE $date_filter"),
            'high_matches'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM $ma WHERE match_score >= 70 AND $date_filter"),
            'total_views'       => (int) $wpdb->get_var("SELECT SUM(views_count) FROM $ex"),
            'total_revenue'     => (int) ($wpdb->get_var("SELECT SUM(payment_amount) FROM $cv WHERE $date_filter") ?: 0),
            'avg_listing_value' => (int) ($wpdb->get_var("SELECT AVG(property_value) FROM $ex WHERE status='active'") ?: 0),
            'avg_match_score'   => (float) ($wpdb->get_var("SELECT AVG(match_score) FROM $ma WHERE $date_filter") ?: 0),
        );
    }


    /**
     * Popular districts
     */
    private function get_popular_districts($days) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT district, COUNT(*) as count, AVG(property_value) as avg_value, SUM(views_count) as total_views
             FROM {$wpdb->prefix}moaveze_exchanges 
             WHERE status = 'active' AND district != ''
             GROUP BY district ORDER BY count DESC LIMIT 10"
        );

        $formatted = array();
        foreach ($results as $r) {
            $formatted[] = array(
                'name'       => $r->district,
                'count'      => (int) $r->count,
                'avg_value'  => round($r->avg_value),
                'views'      => (int) $r->total_views,
                'avg_value_formatted' => number_format(round($r->avg_value / 1000000000, 1), 1) . ' میلیارد',
            );
        }
        return $formatted;
    }

    /**
     * Trending property types
     */
    private function get_trending_types($days) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT property_type, COUNT(*) as count, AVG(property_value) as avg_value, AVG(area_sqm) as avg_area
             FROM {$wpdb->prefix}moaveze_exchanges 
             WHERE status = 'active' AND property_type != ''
             GROUP BY property_type ORDER BY count DESC LIMIT 8"
        );

        $formatted = array();
        $total = array_sum(array_column($results, 'count'));
        foreach ($results as $r) {
            $formatted[] = array(
                'name'       => $r->property_type,
                'count'      => (int) $r->count,
                'percentage' => $total > 0 ? round(($r->count / $total) * 100, 1) : 0,
                'avg_value'  => round($r->avg_value),
                'avg_area'   => round($r->avg_area),
            );
        }
        return $formatted;
    }

    /**
     * Conversion rates
     */
    private function get_conversion_rates($days) {
        global $wpdb;
        $ex = $wpdb->prefix . 'moaveze_exchanges';
        $of = $wpdb->prefix . 'moaveze_offers';
        $ma = $wpdb->prefix . 'moaveze_matches';

        $total_listings = max(1, $wpdb->get_var("SELECT COUNT(*) FROM $ex WHERE status='active'"));
        $total_offers = max(1, $wpdb->get_var("SELECT COUNT(*) FROM $of"));
        $accepted_offers = $wpdb->get_var("SELECT COUNT(*) FROM $of WHERE status='accepted'");
        $total_matches = $wpdb->get_var("SELECT COUNT(*) FROM $ma");
        $completed_matches = $wpdb->get_var("SELECT COUNT(*) FROM $ma WHERE status='completed'");
        $listings_with_offers = $wpdb->get_var("SELECT COUNT(DISTINCT exchange_id) FROM $of");

        return array(
            'listing_to_offer'    => round(($listings_with_offers / $total_listings) * 100, 1),
            'offer_acceptance'    => round(($accepted_offers / $total_offers) * 100, 1),
            'match_completion'    => $total_matches > 0 ? round(($completed_matches / $total_matches) * 100, 1) : 0,
            'avg_offers_per_listing' => round($total_offers / $total_listings, 1),
            'offers_with_cash'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM $of WHERE cash_offered > 0"),
            'avg_time_to_match'   => $this->get_avg_time_to_match(),
        );
    }

    /**
     * Average time from listing to first match (in days)
     */
    private function get_avg_time_to_match() {
        global $wpdb;
        $result = $wpdb->get_var(
            "SELECT AVG(DATEDIFF(m.created_at, e.created_at))
             FROM {$wpdb->prefix}moaveze_matches m
             JOIN {$wpdb->prefix}moaveze_exchanges e ON m.exchange_id_a = e.id
             WHERE m.created_at > e.created_at"
        );
        return round($result ?: 0, 1);
    }

    /**
     * Revenue data breakdown
     */
    private function get_revenue_data($days) {
        global $wpdb;
        $cv = $wpdb->prefix . 'moaveze_contact_views';
        $sb = $wpdb->prefix . 'moaveze_subscriptions';

        $contact_revenue = $wpdb->get_var("SELECT SUM(payment_amount) FROM $cv WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)") ?: 0;
        $subscription_revenue = $wpdb->get_var("SELECT SUM(payment_amount) FROM $sb WHERE starts_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)") ?: 0;

        return array(
            'contact_revenue'      => (int) $contact_revenue,
            'subscription_revenue' => (int) $subscription_revenue,
            'total_revenue'        => (int) ($contact_revenue + $subscription_revenue),
            'active_subscriptions' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $sb WHERE status='active' AND expires_at > NOW()"),
            'formatted'            => array(
                'contact'      => number_format($contact_revenue) . ' تومان',
                'subscription' => number_format($subscription_revenue) . ' تومان',
                'total'        => number_format($contact_revenue + $subscription_revenue) . ' تومان',
            ),
        );
    }


    /**
     * Activity timeline (last N days, grouped by day)
     */
    private function get_activity_timeline($days) {
        global $wpdb;
        $results = array();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $label = date('m/d', strtotime("-{$i} days"));

            $listings = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_exchanges WHERE DATE(created_at) = %s", $date
            ));
            $offers = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers WHERE DATE(created_at) = %s", $date
            ));
            $matches = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_matches WHERE DATE(created_at) = %s", $date
            ));

            $results[] = array(
                'date'     => $date,
                'label'    => $label,
                'listings' => (int) $listings,
                'offers'   => (int) $offers,
                'matches'  => (int) $matches,
            );
        }

        return $results;
    }

    /**
     * Listings chart data (daily new listings)
     */
    private function get_listings_chart($days) {
        global $wpdb;
        $data = array('labels' => array(), 'values' => array());

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('m/d', strtotime("-{$i} days"));
            $data['values'][] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_exchanges WHERE DATE(created_at) = %s", $date
            ));
        }
        return $data;
    }

    /**
     * Offers chart
     */
    private function get_offers_chart($days) {
        global $wpdb;
        $data = array('labels' => array(), 'values' => array());

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('m/d', strtotime("-{$i} days"));
            $data['values'][] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_offers WHERE DATE(created_at) = %s", $date
            ));
        }
        return $data;
    }

    /**
     * Matches chart
     */
    private function get_matches_chart($days) {
        global $wpdb;
        $data = array('labels' => array(), 'values' => array());

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('m/d', strtotime("-{$i} days"));
            $data['values'][] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_matches WHERE DATE(created_at) = %s", $date
            ));
        }
        return $data;
    }

    /**
     * Revenue chart
     */
    private function get_revenue_chart($days) {
        global $wpdb;
        $data = array('labels' => array(), 'values' => array());

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('m/d', strtotime("-{$i} days"));
            $data['values'][] = (int) ($wpdb->get_var($wpdb->prepare(
                "SELECT SUM(payment_amount) FROM {$wpdb->prefix}moaveze_contact_views WHERE DATE(created_at) = %s", $date
            )) ?: 0);
        }
        return $data;
    }

    /**
     * Views chart
     */
    private function get_views_chart($days) {
        global $wpdb;
        $data = array('labels' => array(), 'values' => array());

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data['labels'][] = date('m/d', strtotime("-{$i} days"));
            $data['values'][] = (int) ($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_contact_views WHERE DATE(created_at) = %s", $date
            )) ?: 0);
        }
        return $data;
    }
}

new Moaveze_Analytics();
