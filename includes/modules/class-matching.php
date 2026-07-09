<?php
/**
 * Smart Matching Algorithm - Phase 2
 * Advanced weighted scoring, chain detection, and auto-notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Matching {

    /**
     * Scoring weights (configurable)
     */
    private $weights = array(
        'value_range'     => 30,  // Does target value fall in desired range?
        'property_type'   => 20,  // Does target type match desired type?
        'district'        => 15,  // Is the district compatible?
        'exchange_type'   => 10,  // Are exchange types compatible?
        'cash_balance'    => 10,  // Do cash directions complement?
        'area'            => 10,  // Is the area comparable?
        'mutual_interest' => 5,   // Does B also want what A offers?
    );

    public function __construct() {
        add_action('wp_ajax_moaveze_run_matching', array($this, 'run_matching'));
        add_action('wp_ajax_moaveze_list_matches', array($this, 'ajax_list_matches'));
        add_action('wp_ajax_moaveze_detect_chains', array($this, 'detect_chains'));
        add_action('save_post_moaveze_exchange', array($this, 'auto_match_on_save'), 20, 2);
        add_action('wp_ajax_moaveze_get_match_explanation', array($this, 'get_match_explanation'));
        add_action('wp_ajax_moaveze_get_match_full_details', array($this, 'get_match_full_details'));
        add_action('wp_ajax_moaveze_update_match_status', array($this, 'update_match_status'));
    }

    /**
     * AJAX: Full details for the "connect parties" admin modal.
     * Returns both sides' contact info (admin/consultant only), the
     * property specs, and a human-readable breakdown of WHY they matched.
     * This is what powers the previously-broken "ارتباط طرفین" button.
     */
    public function get_match_full_details() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('moaveze_view_contacts')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $match_id = absint($_POST['match_id'] ?? 0);
        if (!$match_id) wp_send_json_error('شناسه تطابق نامعتبر');

        global $wpdb;
        $match = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_matches WHERE id = %d", $match_id
        ));
        if (!$match) wp_send_json_error('تطابق یافت نشد');

        $a = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $match->exchange_id_a
        ));
        $b = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $match->exchange_id_b
        ));
        if (!$a || !$b) wp_send_json_error('یکی از آگهی‌های این تطابق حذف شده است');

        $details = json_decode($match->match_details, true) ?: array();
        $consultant = $match->consultant_id ? get_userdata($match->consultant_id) : null;

        wp_send_json_success(array(
            'match' => array(
                'id'         => $match->id,
                'score'      => (float) $match->match_score,
                'type'       => $match->match_type,
                'status'     => $match->status,
                'reasons'    => $details['reasons'] ?? array(),
                'breakdown'  => $details['breakdown'] ?? array(),
                'suggestion' => $details['suggestion'] ?? '',
                'consultant' => $consultant ? $consultant->display_name : null,
                'notes'      => $match->consultant_notes,
            ),
            'side_a' => $this->format_side_for_modal($a),
            'side_b' => $this->format_side_for_modal($b),
        ));
    }

    /**
     * Format one side (exchange) of a match for the connect-parties modal.
     */
    private function format_side_for_modal($ex) {
        return array(
            'id'            => $ex->id,
            'post_id'       => $ex->post_id,
            'title'         => get_the_title($ex->post_id),
            'edit_link'     => get_edit_post_link($ex->post_id, 'raw'),
            'view_link'     => get_permalink($ex->post_id),
            'property_type' => $ex->property_type,
            'district'      => $ex->district,
            'value'         => (int) $ex->property_value,
            'area'          => (int) $ex->area_sqm,
            'exchange_type' => $ex->exchange_type,
            // Contact info is ONLY ever exposed here, behind the
            // manage_options / moaveze_view_contacts capability check
            // performed in get_match_full_details() above - never on
            // any public-facing endpoint.
            'contact_name'  => $ex->contact_name,
            'contact_phone' => $ex->contact_phone,
            'contact_email' => $ex->contact_email,
        );
    }

    /**
     * AJAX: Update a match's status (e.g. mark as "in_progress" or
     * "completed" after the consultant has connected both parties).
     */
    public function update_match_status() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('moaveze_view_matches')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $match_id = absint($_POST['match_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $allowed = array('new', 'assigned', 'in_progress', 'completed', 'rejected');
        if (!$match_id || !in_array($status, $allowed, true)) {
            wp_send_json_error('ورودی نامعتبر');
        }

        global $wpdb;
        $data = array('status' => $status);
        $format = array('%s');

        if ($notes !== '') {
            $data['consultant_notes'] = $notes;
            $format[] = '%s';
        }

        $wpdb->update(
            $wpdb->prefix . 'moaveze_matches',
            $data,
            array('id' => $match_id),
            $format,
            array('%d')
        );

        wp_send_json_success(array('message' => 'وضعیت تطابق بروزرسانی شد'));
    }

    /**
     * AJAX: List existing matches from the DB (for the frontend dashboard)
     */
    public function ajax_list_matches() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options') && !current_user_can('moaveze_view_matches')) {
            wp_send_json_error('دسترسی ندارید');
        }

        global $wpdb;
        $matches_table = $wpdb->prefix . 'moaveze_matches';
        $exchanges_table = $wpdb->prefix . 'moaveze_exchanges';

        $status_filter = sanitize_text_field($_POST['status'] ?? '');
        $where_status = '';
        if ($status_filter && in_array($status_filter, array('new', 'assigned', 'in_progress', 'completed', 'rejected'))) {
            $where_status = $wpdb->prepare(" AND m.status = %s", $status_filter);
        } else {
            // By default, hide rejected matches
            $where_status = " AND m.status != 'rejected'";
        }

        $matches = $wpdb->get_results(
            "SELECT m.*, 
                    a.post_id as post_id_a, a.property_type as type_a, a.district as district_a, 
                    a.property_value as value_a, a.area_sqm as area_a,
                    b.post_id as post_id_b, b.property_type as type_b, b.district as district_b, 
                    b.property_value as value_b, b.area_sqm as area_b
             FROM $matches_table m
             LEFT JOIN $exchanges_table a ON m.exchange_id_a = a.id
             LEFT JOIN $exchanges_table b ON m.exchange_id_b = b.id
             WHERE 1=1 $where_status
             ORDER BY m.match_score DESC
             LIMIT 50"
        );

        $result = array();
        foreach ($matches as $m) {
            $title_a = $m->post_id_a ? get_the_title($m->post_id_a) : 'ملک A';
            $title_b = $m->post_id_b ? get_the_title($m->post_id_b) : 'ملک B';
            $details = json_decode($m->match_details, true) ?: array();

            $result[] = array(
                'id'          => (int) $m->id,
                'score'       => (float) $m->match_score,
                'status'      => $m->status,
                'match_type'  => $m->match_type ?? '',
                'suggestion'  => $details['suggestion'] ?? '',
                'title_a'     => $title_a,
                'type_a'      => $m->type_a,
                'district_a'  => $m->district_a,
                'value_a'     => (int) $m->value_a,
                'area_a'      => (int) $m->area_a,
                'title_b'     => $title_b,
                'type_b'      => $m->type_b,
                'district_b'  => $m->district_b,
                'value_b'     => (int) $m->value_b,
                'area_b'      => (int) $m->area_b,
                'post_id_a'   => (int) $m->post_id_a,
                'post_id_b'   => (int) $m->post_id_b,
            );
        }

        wp_send_json_success(array('matches' => $result, 'total' => count($result)));
    }

    /**
     * Run matching algorithm for all active exchanges
     */
    public function run_matching() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_exchanges';
        $matches_table = $wpdb->prefix . 'moaveze_matches';


        // NOTE: visibility = 'private' excludes "reciprocal" listings that
        // a user chose to register ONLY for a specific offer (see
        // Moaveze_Offers::create_reciprocal_listing()) - those should
        // never surface in the general matching pool or chain detection,
        // only be linked to that one specific offer.
        $exchanges = $wpdb->get_results("SELECT * FROM $table WHERE status = 'active' AND visibility != 'private'");
        $new_matches = 0;
        $updated_matches = 0;

        for ($i = 0; $i < count($exchanges); $i++) {
            for ($j = $i + 1; $j < count($exchanges); $j++) {
                $result = $this->calculate_match_score($exchanges[$i], $exchanges[$j]);
                $score = $result['score'];
                $breakdown = $result['breakdown'];

                if ($score >= 25) { // Lower threshold for Phase 2
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $matches_table WHERE 
                         (exchange_id_a = %d AND exchange_id_b = %d) OR 
                         (exchange_id_a = %d AND exchange_id_b = %d)",
                        $exchanges[$i]->id, $exchanges[$j]->id,
                        $exchanges[$j]->id, $exchanges[$i]->id
                    ));

                    $details = $this->get_match_details($exchanges[$i], $exchanges[$j], $breakdown);

                    if (!$existing) {
                        $wpdb->insert($matches_table, array(
                            'exchange_id_a' => $exchanges[$i]->id,
                            'exchange_id_b' => $exchanges[$j]->id,
                            'match_score'   => $score,
                            'match_type'    => $this->determine_match_type($exchanges[$i], $exchanges[$j], $score),
                            'match_details' => wp_json_encode($details),
                            'status'        => 'new',
                        ));
                        $new_matches++;

                        // Notify admin of high-score matches
                        if ($score >= 70) {
                            do_action('moaveze_new_match', $wpdb->insert_id, $details);
                        }
                    } else {
                        // Update score if changed
                        $wpdb->update($matches_table,
                            array('match_score' => $score, 'match_details' => wp_json_encode($details)),
                            array('id' => $existing),
                            array('%f', '%s'),
                            array('%d')
                        );
                        $updated_matches++;
                    }
                }
            }
        }

        // Auto-detect chain swaps
        $chains_found = $this->detect_chain_swaps($exchanges, $matches_table);

        wp_send_json_success(array(
            'message' => sprintf('%d تطابق جدید یافت شد، %d بروزرسانی شد، %d زنجیره شناسایی شد',
                $new_matches, $updated_matches, $chains_found),
            'new_matches'     => $new_matches,
            'updated_matches' => $updated_matches,
            'chains_found'    => $chains_found,
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

        // Skip matching entirely for private (offer-only) reciprocal listings.
        if (isset($current->visibility) && $current->visibility === 'private') {
            return;
        }

        $others = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'active' AND visibility != 'private' AND id != %d", $current->id
        ));

        $matches_table = $wpdb->prefix . 'moaveze_matches';
        $top_matches = array();

        foreach ($others as $other) {
            $result = $this->calculate_match_score($current, $other);
            $score = $result['score'];

            if ($score >= 25) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $matches_table WHERE 
                     (exchange_id_a = %d AND exchange_id_b = %d) OR 
                     (exchange_id_a = %d AND exchange_id_b = %d)",
                    $current->id, $other->id, $other->id, $current->id
                ));

                if (!$existing) {
                    $details = $this->get_match_details($current, $other, $result['breakdown']);
                    $match_type = $this->determine_match_type($current, $other, $score);

                    $wpdb->insert($matches_table, array(
                        'exchange_id_a' => $current->id,
                        'exchange_id_b' => $other->id,
                        'match_score'   => $score,
                        'match_type'    => $match_type,
                        'match_details' => wp_json_encode($details),
                        'status'        => 'new',
                    ));

                    $top_matches[] = array('score' => $score, 'other' => $other);
                }
            }
        }

        // Notify admin about top matches for this new listing
        if (!empty($top_matches)) {
            usort($top_matches, function($a, $b) { return $b['score'] - $a['score']; });
            $best = $top_matches[0];

            if ($best['score'] >= 60) {
                do_action('moaveze_new_match', 0, array(
                    'new_listing'    => $current->id,
                    'best_match'     => $best['other']->id,
                    'score'          => $best['score'],
                    'total_matches'  => count($top_matches),
                ));
            }
        }
    }


    /**
     * Calculate match score with detailed breakdown
     */
    private function calculate_match_score($a, $b) {
        $breakdown = array();
        $total_score = 0;

        // 1. Value Range Compatibility (bidirectional)
        $val_ab = $this->value_compatibility($a, $b);
        $val_ba = $this->value_compatibility($b, $a);
        $value_score = ($val_ab + $val_ba) / 2;
        $breakdown['value_range'] = array(
            'score'  => $value_score,
            'weight' => $this->weights['value_range'],
            'detail' => sprintf('A→B: %.0f%% | B→A: %.0f%%', $val_ab * 100, $val_ba * 100),
        );
        $total_score += $value_score * $this->weights['value_range'];

        // 2. Property Type Match (bidirectional)
        $type_ab = $this->type_compatibility($a, $b);
        $type_ba = $this->type_compatibility($b, $a);
        $type_score = ($type_ab + $type_ba) / 2;
        $breakdown['property_type'] = array(
            'score'  => $type_score,
            'weight' => $this->weights['property_type'],
            'detail' => sprintf('A wants %s, B is %s | B wants %s, A is %s',
                $a->desired_property_type ?: 'any', $b->property_type,
                $b->desired_property_type ?: 'any', $a->property_type),
        );
        $total_score += $type_score * $this->weights['property_type'];

        // 3. District Compatibility
        $district_score = $this->district_compatibility($a, $b);
        $breakdown['district'] = array(
            'score'  => $district_score,
            'weight' => $this->weights['district'],
            'detail' => sprintf('A: %s, B: %s', $a->district, $b->district),
        );
        $total_score += $district_score * $this->weights['district'];

        // 4. Exchange Type Compatibility
        $extype_score = $this->exchange_type_compatibility($a, $b);
        $breakdown['exchange_type'] = array(
            'score'  => $extype_score,
            'weight' => $this->weights['exchange_type'],
            'detail' => sprintf('A: %s, B: %s', $a->exchange_type, $b->exchange_type),
        );
        $total_score += $extype_score * $this->weights['exchange_type'];

        // 5. Cash Balance (do the cash amounts complement?)
        $cash_score = $this->cash_balance_score($a, $b);
        $breakdown['cash_balance'] = array(
            'score'  => $cash_score,
            'weight' => $this->weights['cash_balance'],
            'detail' => sprintf('A %s %s | B %s %s',
                $a->cash_direction, Moaveze_Helpers::short_price($a->cash_difference, false),
                $b->cash_direction, Moaveze_Helpers::short_price($b->cash_difference, false)),
        );
        $total_score += $cash_score * $this->weights['cash_balance'];

        // 6. Area Compatibility
        $area_score = $this->area_compatibility($a, $b);
        $breakdown['area'] = array(
            'score'  => $area_score,
            'weight' => $this->weights['area'],
            'detail' => sprintf('A: %dm², B: %dm²', $a->area_sqm, $b->area_sqm),
        );
        $total_score += $area_score * $this->weights['area'];

        // 7. Mutual Interest Bonus
        $mutual_score = $this->mutual_interest($a, $b);
        $breakdown['mutual_interest'] = array(
            'score'  => $mutual_score,
            'weight' => $this->weights['mutual_interest'],
            'detail' => $mutual_score > 0.7 ? 'هر دو طرف به ملک دیگری علاقه‌مند' : 'یکطرفه',
        );
        $total_score += $mutual_score * $this->weights['mutual_interest'];

        $max_possible = array_sum($this->weights);
        $final_score = min(100, max(0, round(($total_score / $max_possible) * 100)));

        return array(
            'score'     => $final_score,
            'breakdown' => $breakdown,
        );
    }


    /**
     * Value compatibility score
     */
    private function value_compatibility($seeker, $target) {
        if (!$seeker->desired_min_value && !$seeker->desired_max_value) {
            return 0.6; // No preference
        }

        $target_value = $target->property_value;
        $min = $seeker->desired_min_value ?: 0;
        $max = $seeker->desired_max_value ?: PHP_INT_MAX;

        if ($target_value >= $min && $target_value <= $max) {
            return 1.0;
        }

        // Calculate proximity (how close is it?)
        if ($target_value < $min) {
            $diff_percent = ($min - $target_value) / $min;
        } else {
            $diff_percent = ($target_value - $max) / $max;
        }

        // Graceful degradation up to 40% diff
        return max(0, 1 - ($diff_percent / 0.4));
    }

    /**
     * Type compatibility (one direction)
     */
    private function type_compatibility($seeker, $target) {
        if (!$seeker->desired_property_type || $seeker->desired_property_type === '') {
            return 0.6; // Flexible
        }
        if ($seeker->desired_property_type === $target->property_type) {
            return 1.0; // Perfect
        }
        // Partial: similar categories
        $similar_groups = array(
            array('آپارتمان', 'پنت‌هاوس', 'دوبلکس'),
            array('ویلایی', 'دوبلکس', 'باغ'),
            array('تجاری', 'مغازه', 'دفتر کار'),
            array('زمین', 'باغ', 'سوله'),
        );
        foreach ($similar_groups as $group) {
            if (in_array($seeker->desired_property_type, $group) && in_array($target->property_type, $group)) {
                return 0.5;
            }
        }
        return 0.1;
    }

    /**
     * District compatibility (bidirectional)
     */
    private function district_compatibility($a, $b) {
        $a_desired = json_decode($a->desired_districts ?? '[]', true) ?: array();
        $b_desired = json_decode($b->desired_districts ?? '[]', true) ?: array();

        $score = 0;
        $checks = 0;

        if (!empty($a_desired)) {
            $checks++;
            if (in_array($b->district, $a_desired)) $score += 1.0;
        } else {
            $checks++;
            $score += 0.5; // No preference = neutral
        }

        if (!empty($b_desired)) {
            $checks++;
            if (in_array($a->district, $b_desired)) $score += 1.0;
        } else {
            $checks++;
            $score += 0.5;
        }

        // Bonus if same district
        if ($a->district === $b->district) {
            $score += 0.3;
        }

        return min(1.0, $score / $checks);
    }

    /**
     * Exchange type compatibility
     */
    private function exchange_type_compatibility($a, $b) {
        if ($a->exchange_type === 'flexible' || $b->exchange_type === 'flexible') {
            return 0.85;
        }
        if ($a->exchange_type === $b->exchange_type) {
            return 1.0;
        }
        // Compatible combos
        $compatible = array(
            'property_cash' => array('property_cash', 'property_mixed'),
            'property_car'  => array('property_car', 'property_mixed'),
            'property_mixed' => array('property_cash', 'property_car', 'property_mixed'),
        );
        if (isset($compatible[$a->exchange_type]) && in_array($b->exchange_type, $compatible[$a->exchange_type])) {
            return 0.7;
        }
        return 0.2;
    }


    /**
     * Cash balance score - do the cash offers complement each other?
     */
    private function cash_balance_score($a, $b) {
        // If neither has cash, neutral
        if (!$a->cash_difference && !$b->cash_difference) return 0.5;

        // If one gives and the other receives
        if ($a->cash_direction !== $b->cash_direction && $a->cash_direction && $b->cash_direction) {
            // Perfect if amounts are close
            $giver = ($a->cash_direction === 'give') ? $a : $b;
            $receiver = ($a->cash_direction === 'receive') ? $a : $b;

            if ($giver->cash_difference >= $receiver->cash_difference) {
                return 1.0; // Giver offers enough
            }
            $ratio = $giver->cash_difference / max(1, $receiver->cash_difference);
            return max(0.3, $ratio);
        }

        // Both give or both receive = problematic
        if ($a->cash_direction === $b->cash_direction && $a->cash_direction) {
            return 0.1;
        }

        return 0.4;
    }

    /**
     * Area compatibility
     */
    private function area_compatibility($a, $b) {
        if (!$a->area_sqm || !$b->area_sqm) return 0.5;

        $ratio = min($a->area_sqm, $b->area_sqm) / max($a->area_sqm, $b->area_sqm);

        // Higher value property usually has larger area, adjust
        $value_ratio = min($a->property_value, $b->property_value) / max(1, max($a->property_value, $b->property_value));

        // If the areas are proportional to value, good
        if (abs($ratio - $value_ratio) < 0.2) {
            return 0.8;
        }

        return max(0.2, $ratio);
    }

    /**
     * Mutual interest - does B also want what A has?
     */
    private function mutual_interest($a, $b) {
        $a_wants_b = 0;
        $b_wants_a = 0;

        // Does A want B's type?
        if (!$a->desired_property_type || $a->desired_property_type === $b->property_type) {
            $a_wants_b++;
        }
        // Does A's desired value range include B?
        if ($b->property_value >= ($a->desired_min_value ?: 0) && $b->property_value <= ($a->desired_max_value ?: PHP_INT_MAX)) {
            $a_wants_b++;
        }

        // Does B want A's type?
        if (!$b->desired_property_type || $b->desired_property_type === $a->property_type) {
            $b_wants_a++;
        }
        // Does B's desired value range include A?
        if ($a->property_value >= ($b->desired_min_value ?: 0) && $a->property_value <= ($b->desired_max_value ?: PHP_INT_MAX)) {
            $b_wants_a++;
        }

        $total = ($a_wants_b + $b_wants_a) / 4;
        return min(1.0, $total);
    }


    /**
     * Determine match type based on analysis
     */
    private function determine_match_type($a, $b, $score) {
        if ($score >= 80) return 'perfect';
        if ($score >= 60) return 'strong';
        if ($score >= 40) return 'moderate';
        return 'weak';
    }

    /**
     * Get detailed match explanation (Persian)
     */
    private function get_match_details($a, $b, $breakdown) {
        $reasons = array();

        // Generate human-readable explanations
        if ($breakdown['property_type']['score'] >= 0.8) {
            $reasons[] = sprintf('نوع ملک سازگار: %s ↔ %s', $a->property_type, $b->property_type);
        }
        if ($breakdown['value_range']['score'] >= 0.7) {
            $reasons[] = 'محدوده قیمت سازگار';
        }
        if ($breakdown['cash_balance']['score'] >= 0.7) {
            $reasons[] = 'شرایط مابه‌التفاوت نقدی مکمل';
        }
        if ($breakdown['district']['score'] >= 0.7) {
            $reasons[] = 'مناطق مورد نظر سازگار';
        }
        if ($breakdown['mutual_interest']['score'] >= 0.7) {
            $reasons[] = 'علاقه‌مندی دوطرفه';
        }

        return array(
            'reasons'        => $reasons,
            'breakdown'      => $breakdown,
            'value_diff'     => abs($a->property_value - $b->property_value),
            'a_value'        => $a->property_value,
            'b_value'        => $b->property_value,
            'a_type'         => $a->property_type,
            'b_type'         => $b->property_type,
            'a_district'     => $a->district,
            'b_district'     => $b->district,
            'a_area'         => $a->area_sqm,
            'b_area'         => $b->area_sqm,
            'cash_compatible' => ($a->cash_direction !== $b->cash_direction),
            'suggestion'     => $this->generate_suggestion($a, $b, $breakdown),
        );
    }

    /**
     * Generate AI-like suggestion for the match
     */
    private function generate_suggestion($a, $b, $breakdown) {
        $value_diff = abs($a->property_value - $b->property_value);

        if ($value_diff < 2000000000) {
            return 'اختلاف ارزش بسیار کم است. معاوضه مستقیم با مابه‌التفاوت جزئی ممکن است.';
        }

        $higher = ($a->property_value > $b->property_value) ? $a : $b;
        $lower = ($a->property_value > $b->property_value) ? $b : $a;

        if ($value_diff <= 10000000000) {
            $short = Moaveze_Helpers::short_price($value_diff);
            return sprintf('اختلاف %s. پیشنهاد: ملک کم‌ارزش‌تر + %s نقد.', $short, $short);
        }

        return sprintf('اختلاف ارزش %s. نیاز به مذاکره برای جبران اختلاف با نقد یا دارایی اضافی.',
            Moaveze_Helpers::short_price($value_diff));
    }


    /**
     * Detect chain swaps (A→B→C→A cycles)
     */
    public function detect_chains() {
        if (wp_doing_ajax()) {
            check_ajax_referer('moaveze_admin_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error('دسترسی ندارید');
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_exchanges';
        $exchanges = $wpdb->get_results("SELECT * FROM $table WHERE status = 'active' AND visibility != 'private'");

        $count = $this->detect_chain_swaps($exchanges, $wpdb->prefix . 'moaveze_matches');

        if (wp_doing_ajax()) {
            wp_send_json_success(array(
                'message' => sprintf('%d زنجیره معاوضه جدید شناسایی شد', $count),
                'count'   => $count,
            ));
        }

        return $count;
    }

    /**
     * Detect chain swaps among active exchanges
     * Uses graph-based cycle detection
     */
    private function detect_chain_swaps($exchanges, $matches_table) {
        global $wpdb;
        $chains_table = $wpdb->prefix . 'moaveze_chains';

        // Build directed graph: edge from A to B means "A wants what B has"
        $graph = array();
        $id_map = array();

        foreach ($exchanges as $ex) {
            $id_map[$ex->id] = $ex;
            $graph[$ex->id] = array();
        }

        // For each pair, if A wants B's type and value is in range, add edge
        foreach ($exchanges as $a) {
            foreach ($exchanges as $b) {
                if ($a->id === $b->id) continue;

                $wants_type = (!$a->desired_property_type || $a->desired_property_type === $b->property_type);
                $in_range = true;
                if ($a->desired_min_value && $b->property_value < $a->desired_min_value * 0.7) $in_range = false;
                if ($a->desired_max_value && $b->property_value > $a->desired_max_value * 1.3) $in_range = false;

                if ($wants_type && $in_range) {
                    $graph[$a->id][] = $b->id;
                }
            }
        }

        // Find cycles of length 3 and 4
        $found_chains = array();
        $new_chains = 0;

        foreach (array_keys($graph) as $start) {
            // DFS for cycles of length 3
            foreach ($graph[$start] as $mid) {
                if ($mid === $start) continue;
                foreach ($graph[$mid] as $end) {
                    if ($end === $start || $end === $mid) continue;
                    // Check if end connects back to start
                    if (in_array($start, $graph[$end])) {
                        $chain = array($start, $mid, $end);
                        sort($chain);
                        $hash = md5(implode('-', $chain));

                        if (!isset($found_chains[$hash])) {
                            $found_chains[$hash] = array($start, $mid, $end);
                        }
                    }
                }
            }
        }

        // Insert new chains
        foreach ($found_chains as $hash => $chain_ids) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $chains_table WHERE chain_hash = %s", $hash
            ));

            if (!$existing) {
                $total_value = 0;
                foreach ($chain_ids as $cid) {
                    if (isset($id_map[$cid])) {
                        $total_value += $id_map[$cid]->property_value;
                    }
                }

                $wpdb->insert($chains_table, array(
                    'chain_hash'   => $hash,
                    'exchange_ids' => wp_json_encode($chain_ids),
                    'chain_length' => count($chain_ids),
                    'total_value'  => $total_value,
                    'status'       => 'detected',
                ));
                $new_chains++;
            }
        }

        return $new_chains;
    }


    /**
     * AJAX: Get match explanation for frontend
     */
    public function get_match_explanation() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        $match_id = absint($_POST['match_id'] ?? 0);
        if (!$match_id) wp_send_json_error('شناسه نامعتبر');

        global $wpdb;
        $match = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_matches WHERE id = %d", $match_id
        ));

        if (!$match) wp_send_json_error('تطابق یافت نشد');

        $details = json_decode($match->match_details, true);

        wp_send_json_success(array(
            'score'      => $match->match_score,
            'type'       => $match->match_type,
            'reasons'    => $details['reasons'] ?? array(),
            'suggestion' => $details['suggestion'] ?? '',
            'breakdown'  => $details['breakdown'] ?? array(),
        ));
    }
}

new Moaveze_Matching();
