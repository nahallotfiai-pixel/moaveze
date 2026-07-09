<?php
/**
 * Matches Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_matches = $wpdb->prefix . 'moaveze_matches';
$matches = $wpdb->get_results("SELECT * FROM $table_matches ORDER BY match_score DESC LIMIT 50");
?>

<div class="wrap moaveze-matches-page">
    <h1><span class="dashicons dashicons-update"></span> تطابق‌های یافته شده</h1>

    <div class="moaveze-filters">
        <select id="match-status-filter">
            <option value="all">همه</option>
            <option value="new">جدید</option>
            <option value="assigned">اختصاص یافته</option>
            <option value="in_progress">در حال بررسی</option>
            <option value="completed">تکمیل شده</option>
        </select>
        <button id="run-matching-algo" class="button button-primary">
            <span class="dashicons dashicons-search"></span> اجرای الگوریتم تطبیق
        </button>
    </div>

    <?php if (empty($matches)) : ?>
        <div class="moaveze-empty-state">
            <span class="dashicons dashicons-search" style="font-size:48px;color:#ddd;"></span>
            <p>هنوز تطابقی یافت نشده. الگوریتم تطبیق را اجرا کنید.</p>
        </div>
    <?php else : ?>
        <div class="moaveze-matches-grid">
            <?php foreach ($matches as $match) : ?>
                <?php
                $exchange_a = get_post($wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $match->exchange_id_a
                )));
                $exchange_b = get_post($wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $match->exchange_id_b
                )));
                ?>
                <?php
                    $match_details = json_decode($match->match_details, true) ?: array();
                    $match_reasons = $match_details['reasons'] ?? array();
                ?>
                <div class="moaveze-match-card" data-status="<?php echo esc_attr($match->status); ?>">
                    <div class="match-header">
                        <span class="match-score"><?php echo number_format($match->match_score, 0); ?>%</span>
                        <span class="match-status match-status-<?php echo esc_attr($match->status); ?>">
                            <?php
                            $statuses = array('new' => 'جدید', 'assigned' => 'اختصاص یافته', 'in_progress' => 'در حال بررسی', 'completed' => 'تکمیل');
                            echo esc_html($statuses[$match->status] ?? $match->status);
                            ?>
                        </span>
                    </div>
                    <div class="match-body">
                        <div class="match-side match-side-a">
                            <h4><?php echo esc_html($exchange_a->post_title ?? 'ملک A'); ?></h4>
                            <p class="match-value"><?php echo esc_html(Moaveze_Helpers::short_price(get_post_meta($exchange_a->ID ?? 0, '_moaveze_property_value', true))); ?></p>
                            <?php if ($exchange_a) : ?>
                                <a href="<?php echo esc_url(get_permalink($exchange_a->ID)); ?>" target="_blank" style="font-size:11px;color:#6366f1;">مشاهده آگهی</a>
                            <?php endif; ?>
                        </div>
                        <div class="match-arrow">
                            <span class="dashicons dashicons-leftright"></span>
                        </div>
                        <div class="match-side match-side-b">
                            <h4><?php echo esc_html($exchange_b->post_title ?? 'ملک B'); ?></h4>
                            <p class="match-value"><?php echo esc_html(Moaveze_Helpers::short_price(get_post_meta($exchange_b->ID ?? 0, '_moaveze_property_value', true))); ?></p>
                            <?php if ($exchange_b) : ?>
                                <a href="<?php echo esc_url(get_permalink($exchange_b->ID)); ?>" target="_blank" style="font-size:11px;color:#6366f1;">مشاهده آگهی</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- NEW: quick "why matched" summary directly on the
                         card so admins/consultants don't have to open the
                         modal just to see the top reasons. -->
                    <?php if (!empty($match_reasons)) : ?>
                        <div class="match-reasons-summary">
                            <?php foreach (array_slice($match_reasons, 0, 3) as $reason) : ?>
                                <span class="reason-chip"><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html($reason); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="match-reasons-summary match-reasons-empty">
                            <span>برای جزئیات کامل دلیل تطابق، روی «ارتباط طرفین» کلیک کنید</span>
                        </div>
                    <?php endif; ?>

                    <div class="match-actions">
                        <button class="button assign-consultant-btn" data-match-id="<?php echo esc_attr($match->id); ?>">
                            <span class="dashicons dashicons-businessman"></span> اختصاص مشاور
                        </button>
                        <button class="button button-primary connect-parties-btn" data-match-id="<?php echo esc_attr($match->id); ?>">
                            <span class="dashicons dashicons-phone"></span> ارتباط طرفین
                        </button>
                        <button class="button ai-suggest-btn" data-type="match" data-id="<?php echo esc_attr($match->id); ?>" data-has-suggestion="<?php echo $match->ai_suggestion ? '1' : '0'; ?>">
                            <span class="dashicons dashicons-superhero-alt"></span>
                            <?php echo $match->ai_suggestion ? 'مشاهده پیشنهاد هوش مصنوعی' : 'پیشنهاد هوش مصنوعی برای معامله'; ?>
                            <?php if ($match->ai_suggestion_status === 'approved') : ?>
                                <span class="ai-suggest-badge">✓</span>
                            <?php endif; ?>
                        </button>
                        <button class="button button-link-delete dismiss-match-btn" data-match-id="<?php echo esc_attr($match->id); ?>" style="color:#dc2626;">
                            <span class="dashicons dashicons-dismiss"></span> رد تطبیق
                        </button>
                    </div>
                    <!-- AI suggestion panel: filled in on demand via AJAX -->
                    <div class="ai-suggestion-panel" data-type="match" data-id="<?php echo esc_attr($match->id); ?>" style="display:none;"></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
