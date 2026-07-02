<?php
/**
 * Property Price Estimator
 * Estimates value based on area, district, type using existing data
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Price_Estimator {

    public function __construct() {
        add_action('wp_ajax_moaveze_estimate_price', array($this, 'estimate'));
        add_action('wp_ajax_nopriv_moaveze_estimate_price', array($this, 'estimate'));
        add_shortcode('moaveze_price_estimator', array($this, 'render_widget'));
    }

    /**
     * Estimate price via AJAX
     */
    public function estimate() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        $area = absint($_POST['area'] ?? 0);
        $district = sanitize_text_field($_POST['district'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $rooms = absint($_POST['rooms'] ?? 0);
        $year_built = absint($_POST['year_built'] ?? 0);

        if (!$area || $area < 10) {
            wp_send_json_error(array('message' => 'متراژ را وارد کنید'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'moaveze_exchanges';

        // Get comparable listings
        $where = "WHERE status = 'active' AND area_sqm > 0 AND property_value > 0";
        $params = array();

        if ($type) {
            $where .= " AND property_type = %s";
            $params[] = $type;
        }

        if ($district) {
            $where .= " AND district = %s";
            $params[] = $district;
        }

        // Get per-sqm prices from similar listings
        $query = "SELECT property_value, area_sqm, property_type, district, rooms 
                  FROM $table $where ORDER BY created_at DESC LIMIT 50";

        if (!empty($params)) {
            $comparables = $wpdb->get_results($wpdb->prepare($query, ...$params));
        } else {
            $comparables = $wpdb->get_results($query);
        }

        if (empty($comparables)) {
            // Fallback: use all listings from same district or type
            $comparables = $wpdb->get_results(
                "SELECT property_value, area_sqm, property_type, district, rooms 
                 FROM $table WHERE status = 'active' AND area_sqm > 0 AND property_value > 0
                 ORDER BY created_at DESC LIMIT 100"
            );
        }

        if (empty($comparables)) {
            wp_send_json_error(array('message' => 'داده کافی برای تخمین موجود نیست'));
        }

        // Calculate weighted average price per sqm
        $total_weight = 0;
        $weighted_price_per_sqm = 0;

        foreach ($comparables as $comp) {
            $price_per_sqm = $comp->property_value / $comp->area_sqm;
            $weight = 1.0;

            // Higher weight for same district
            if ($district && $comp->district === $district) {
                $weight += 2.0;
            }

            // Higher weight for same type
            if ($type && $comp->property_type === $type) {
                $weight += 1.5;
            }

            // Higher weight for similar area (within 30%)
            $area_ratio = min($area, $comp->area_sqm) / max($area, $comp->area_sqm);
            if ($area_ratio > 0.7) {
                $weight += 1.0;
            }

            // Similar rooms
            if ($rooms && $comp->rooms && abs($rooms - $comp->rooms) <= 1) {
                $weight += 0.5;
            }

            $weighted_price_per_sqm += $price_per_sqm * $weight;
            $total_weight += $weight;
        }

        $avg_price_per_sqm = $weighted_price_per_sqm / $total_weight;

        // Apply adjustments
        $adjustment = 1.0;

        // Year built adjustment (newer = higher)
        if ($year_built) {
            $current_year = 1403; // Shamsi
            $age = $current_year - $year_built;
            if ($age <= 2) $adjustment *= 1.1;
            elseif ($age <= 5) $adjustment *= 1.05;
            elseif ($age >= 20) $adjustment *= 0.85;
            elseif ($age >= 10) $adjustment *= 0.92;
        }

        // Room count effect
        if ($rooms >= 4) $adjustment *= 1.03;

        $estimated_value = round($avg_price_per_sqm * $area * $adjustment);
        $price_per_sqm_final = round($avg_price_per_sqm * $adjustment);

        // Range (±15%)
        $min_estimate = round($estimated_value * 0.85);
        $max_estimate = round($estimated_value * 1.15);

        wp_send_json_success(array(
            'estimated_value'  => $estimated_value,
            'min_estimate'     => $min_estimate,
            'max_estimate'     => $max_estimate,
            'price_per_sqm'    => $price_per_sqm_final,
            'comparables_count' => count($comparables),
            'confidence'       => min(95, count($comparables) * 3 + ($district ? 20 : 0) + ($type ? 15 : 0)),
            'formatted'        => array(
                'value'     => Moaveze_Helpers::short_price($estimated_value),
                'min'       => Moaveze_Helpers::short_price($min_estimate),
                'max'       => Moaveze_Helpers::short_price($max_estimate),
                'per_sqm'   => Moaveze_Helpers::short_price($price_per_sqm_final, false) . '/متر',
            ),
        ));
    }


    /**
     * Render price estimator shortcode
     */
    public function render_widget() {
        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false));
        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false));

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-estimator-card">
                <div class="estimator-header">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                    </svg>
                    <div>
                        <h3>تخمین‌گر ارزش ملک</h3>
                        <p>بر اساس آگهی‌های مشابه در منطقه شما</p>
                    </div>
                </div>

                <form id="estimator-form" class="estimator-form">
                    <div class="moaveze-field-group moaveze-field-grid-2">
                        <div class="moaveze-field">
                            <label>نوع ملک</label>
                            <select name="type" id="est-type">
                                <option value="">انتخاب کنید</option>
                                <?php if (!is_wp_error($types)) : foreach ($types as $t) : ?>
                                    <option value="<?php echo esc_attr($t->name); ?>"><?php echo esc_html($t->name); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="moaveze-field">
                            <label>منطقه</label>
                            <select name="district" id="est-district">
                                <option value="">انتخاب کنید</option>
                                <?php if (!is_wp_error($districts)) : foreach ($districts as $d) : ?>
                                    <option value="<?php echo esc_attr($d->name); ?>"><?php echo esc_html($d->name); ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="moaveze-field-group moaveze-field-grid-3">
                        <div class="moaveze-field">
                            <label>متراژ (متر مربع) *</label>
                            <input type="number" name="area" id="est-area" min="10" max="10000" required placeholder="مثال: 150">
                        </div>
                        <div class="moaveze-field">
                            <label>تعداد اتاق</label>
                            <select name="rooms" id="est-rooms">
                                <option value="">—</option>
                                <?php for ($i = 1; $i <= 6; $i++) : ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php endfor; ?>
                            </select>
                        </div>
                        <div class="moaveze-field">
                            <label>سال ساخت</label>
                            <input type="number" name="year_built" id="est-year" min="1350" max="1410" placeholder="مثال: 1400">
                        </div>
                    </div>

                    <button type="submit" class="moaveze-btn moaveze-btn-primary moaveze-btn-full" id="estimate-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/>
                        </svg>
                        تخمین ارزش
                    </button>
                </form>

                <!-- Result -->
                <div class="estimator-result" id="estimator-result" style="display:none;">
                    <div class="result-main">
                        <span class="result-label">ارزش تخمینی ملک شما</span>
                        <span class="result-value" id="result-value">—</span>
                        <span class="result-range" id="result-range">—</span>
                    </div>
                    <div class="result-details">
                        <div class="detail-item">
                            <span>قیمت هر متر مربع</span>
                            <strong id="result-per-sqm">—</strong>
                        </div>
                        <div class="detail-item">
                            <span>آگهی‌های مشابه</span>
                            <strong id="result-comparables">—</strong>
                        </div>
                        <div class="detail-item">
                            <span>اطمینان</span>
                            <strong id="result-confidence">—</strong>
                        </div>
                    </div>
                    <p class="result-disclaimer">
                        * این تخمین بر اساس آگهی‌های موجود در سیستم محاسبه شده و ممکن است با قیمت واقعی بازار تفاوت داشته باشد.
                    </p>
                </div>
            </div>
        </div>
        <script>
        jQuery(function($){
            $('#estimator-form').on('submit', function(e){
                e.preventDefault();
                var $btn = $('#estimate-btn').prop('disabled',true).html('در حال محاسبه...');
                $.post(moavezePlus.ajaxUrl, {
                    action: 'moaveze_estimate_price',
                    nonce: moavezePlus.nonce,
                    area: $('#est-area').val(),
                    district: $('#est-district').val(),
                    type: $('#est-type').val(),
                    rooms: $('#est-rooms').val(),
                    year_built: $('#est-year').val()
                }, function(r){
                    $btn.prop('disabled',false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg> تخمین ارزش');
                    if(r.success){
                        var d = r.data;
                        $('#result-value').text(d.formatted.value);
                        $('#result-range').text('از ' + d.formatted.min + ' تا ' + d.formatted.max);
                        $('#result-per-sqm').text(d.formatted.per_sqm);
                        $('#result-comparables').text(d.comparables_count + ' مورد');
                        $('#result-confidence').text(d.confidence + '%');
                        $('#estimator-result').slideDown(300);
                    } else {
                        MoavezePlus.showToast(r.data.message || 'خطا','error');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

new Moaveze_Price_Estimator();
