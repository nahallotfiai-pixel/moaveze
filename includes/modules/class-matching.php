<?php
/**
 * Smart Matching Algorithm
 * Finds compatible exchange listings and calculates match scores
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Matching {

    public function __construct() {
        add_action('wp_ajax_moaveze_run_matching', array($this, 'run_matching'));
        add_action('save_post_moaveze_exchange', array($this, 'auto_match_on_save'), 20, 2);
    }

    /**
     * Run matching algorithm for all active exchanges
     */
    public function run_matching() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_exchanges';
        $matches_table = $wpdb->prefix . 'moaveze_matches';

        $exchanges = $wpdb->get_results("SELECT * FROM $table WHERE status = 'active'");
        $new_matches = 0;

        for ($i = 0; $i < count($exchanges); $i++) {
            for ($j = $i + 1; $j < count($exchanges); $j++) {
                $score = $this->calculate_match_score($exchanges[$i], $exchanges[$j]);

                if ($score >= 30) { // Minimum 30% match
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $matches_table WHERE 
                         (exchange_id_a = %d AND exchange_id_b = %d) OR 
                         (exchange_id_a = %d AND exchange_id_b = %d)",
                        $exchanges[$i]->id, $exchanges[$j]->id,
                        $exchanges[$j]->id, $exchanges[$i]->id
                    ));

                    if (!$existing) {
                        $wpdb->insert($matches_table, array(
                            'exchange_id_a' => $exchanges[$i]->id,
                            'exchange_id_b' => $exchanges[$j]->id,
                            'match_score'   => $score,
                            'match_type'    => 'direct',
                            'match_details' => wp_json_encode($this->get_match_details($exchanges[$i], $exchanges[$j])),
                            'status'        => 'new',
                        ));
                        $new_matches++;
                    }
                }
            }
        }

        wp_send_json_success(array(
            'message' => sprintf('%d تطابق جدید یافت شد', $new_matches),
            'count'   => $new_matches,
        ));
    }

    /**
     * Auto-match when a new exchange is published
     */
    public function auto_match_on_save($post_id, $post) {
        if ($post->post_status !== 'publish') return;

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_exchanges';
        $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", $post_id));

        if (!$current) return;

        $others = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'active' AND id != %d",
            $current->id
        ));

        $matches_table = $wpdb->prefix . 'moaveze_matches';

        foreach ($others as $other) {
            $score = $this->calculate_match_score($current, $other);
            if ($score >= 30) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $matches_table WHERE 
                     (exchange_id_a = %d AND exchange_id_b = %d) OR 
                     (exchange_id_a = %d AND exchange_id_b = %d)",
                    $current->id, $other->id, $other->id, $current->id
                ));

                if (!$existing) {
                    $wpdb->insert($matches_table, array(
                        'exchange_id_a' => $current->id,
                        'exchange_id_b' => $other->id,
                        'match_score'   => $score,
                        'match_type'    => 'direct',
                        'match_details' => wp_json_encode($this->get_match_details($current, $other)),
                        'status'        => 'new',
                    ));
                }
            }
        }
    }

    /**
     * Calculate match score between two exchanges
     */
    private function calculate_match_score($a, $b) {
        $score = 0;
        $factors = 0;

        // Value compatibility (Does A want B's value range and vice versa?)
        $value_score_ab = $this->value_compatibility($a, $b);
        $value_score_ba = $this->value_compatibility($b, $a);
        $score += ($value_score_ab + $value_score_ba) / 2 * 40; // 40% weight
        $factors += 40;

        // Property type match
        if ($a->desired_property_type && $b->property_type) {
            if ($a->desired_property_type === $b->property_type) {
                $score += 20;
            }
        } else {
            $score += 10; // Flexible
        }
        $factors += 20;

        // District compatibility
        $district_score = $this->district_compatibility($a, $b);
        $score += $district_score * 20; // 20% weight
        $factors += 20;

        // Exchange type compatibility
        $type_score = $this->exchange_type_compatibility($a, $b);
        $score += $type_score * 20; // 20% weight
        $factors += 20;

        return min(100, max(0, round(($score / $factors) * 100)));
    }

    /**
     * Value compatibility score
     */
    private function value_compatibility($seeker, $target) {
        if (!$seeker->desired_min_value && !$seeker->desired_max_value) {
            return 0.7; // No preference = somewhat compatible
        }

        $target_value = $target->property_value;
        $min = $seeker->desired_min_value ?: 0;
        $max = $seeker->desired_max_value ?: PHP_INT_MAX;

        if ($target_value >= $min && $target_value <= $max) {
            return 1.0; // Perfect match
        }

        // Calculate how close it is
        if ($target_value < $min) {
            $diff_percent = ($min - $target_value) / $min;
        } else {
            $diff_percent = ($target_value - $max) / $max;
        }

        return max(0, 1 - $diff_percent);
    }

    /**
     * District compatibility
     */
    private function district_compatibility($a, $b) {
        $a_desired = json_decode($a->desired_districts ?? '[]', true) ?: array();
        $b_desired = json_decode($b->desired_districts ?? '[]', true) ?: array();

        if (empty($a_desired) && empty($b_desired)) return 0.5;

        $score = 0;
        if (empty($a_desired) || in_array($b->district, $a_desired)) {
            $score += 0.5;
        }
        if (empty($b_desired) || in_array($a->district, $b_desired)) {
            $score += 0.5;
        }

        return $score;
    }

    /**
     * Exchange type compatibility
     */
    private function exchange_type_compatibility($a, $b) {
        if ($a->exchange_type === 'flexible' || $b->exchange_type === 'flexible') {
            return 0.8;
        }
        if ($a->exchange_type === $b->exchange_type) {
            return 1.0;
        }
        // Check if cash differences complement each other
        if ($a->cash_direction !== $b->cash_direction && $a->cash_difference && $b->cash_difference) {
            $diff = abs($a->cash_difference - $b->cash_difference);
            $max_cash = max($a->cash_difference, $b->cash_difference);
            return max(0, 1 - ($diff / $max_cash));
        }
        return 0.3;
    }

    /**
     * Get detailed match explanation
     */
    private function get_match_details($a, $b) {
        return array(
            'value_diff'    => abs($a->property_value - $b->property_value),
            'a_value'       => $a->property_value,
            'b_value'       => $b->property_value,
            'a_type'        => $a->property_type,
            'b_type'        => $b->property_type,
            'a_district'    => $a->district,
            'b_district'    => $b->district,
            'cash_compatible' => ($a->cash_direction !== $b->cash_direction),
        );
    }
}

new Moaveze_Matching();
