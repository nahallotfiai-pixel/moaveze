<?php
/**
 * Chain Swaps Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_chains = $wpdb->prefix . 'moaveze_chains';
$chains = $wpdb->get_results("SELECT * FROM $table_chains ORDER BY created_at DESC");
?>

<div class="wrap moaveze-chains-page">
    <h1><span class="dashicons dashicons-networking"></span> معاوضه‌های زنجیره‌ای</h1>

    <p class="description">
        معاوضه زنجیره‌ای زمانی رخ می‌دهد که چند ملک به صورت حلقه‌ای قابل معاوضه باشند.
        مثلاً: A ملکش را به B می‌دهد، B به C، و C به A.
    </p>

    <button id="detect-chains-btn" class="button button-primary" style="margin:15px 0;">
        <span class="dashicons dashicons-search"></span> شناسایی زنجیره‌های جدید
    </button>

    <?php if (empty($chains)) : ?>
        <div class="moaveze-empty-state">
            <span class="dashicons dashicons-networking" style="font-size:48px;color:#ddd;"></span>
            <p>هنوز زنجیره معاوضه‌ای شناسایی نشده. دکمه بالا را بزنید.</p>
        </div>
    <?php else : ?>
        <div class="moaveze-chains-list">
            <?php foreach ($chains as $chain) : ?>
                <div class="moaveze-chain-card">
                    <div class="chain-header">
                        <span class="chain-length"><?php echo esc_html($chain->chain_length); ?> ملک</span>
                        <span class="chain-value">ارزش کل: <?php echo number_format($chain->total_value); ?> تومان</span>
                        <span class="chain-status chain-status-<?php echo esc_attr($chain->status); ?>">
                            <?php
                            $statuses = array('detected' => 'شناسایی شده', 'in_progress' => 'در حال پیگیری', 'completed' => 'تکمیل شده');
                            echo esc_html($statuses[$chain->status] ?? $chain->status);
                            ?>
                        </span>
                    </div>
                    <div class="chain-visual">
                        <?php
                        $exchange_ids = json_decode($chain->exchange_ids, true);
                        if ($exchange_ids) :
                            foreach ($exchange_ids as $i => $eid) :
                                $ex = $wpdb->get_row($wpdb->prepare(
                                    "SELECT post_id FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $eid
                                ));
                                ?>
                                <div class="chain-node">
                                    <span class="node-title"><?php echo $ex ? esc_html(get_the_title($ex->post_id)) : 'ملک ' . ($i+1); ?></span>
                                </div>
                                <?php if ($i < count($exchange_ids) - 1) : ?>
                                    <span class="chain-arrow dashicons dashicons-arrow-left-alt"></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <span class="chain-arrow dashicons dashicons-arrow-left-alt"></span>
                            <span class="chain-loop">↺</span>
                        <?php endif; ?>
                    </div>
                    <div class="chain-actions">
                        <button class="button button-primary">پیگیری توسط مشاور</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
