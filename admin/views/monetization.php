<?php
/**
 * Monetization Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_views = $wpdb->prefix . 'moaveze_contact_views';
$table_subs = $wpdb->prefix . 'moaveze_subscriptions';

$total_revenue = $wpdb->get_var("SELECT SUM(payment_amount) FROM $table_views") ?: 0;
$monthly_revenue = $wpdb->get_var(
    "SELECT SUM(payment_amount) FROM $table_views WHERE MONTH(created_at) = MONTH(NOW())"
) ?: 0;
$active_subs = $wpdb->get_var("SELECT COUNT(*) FROM $table_subs WHERE status = 'active'") ?: 0;
?>

<div class="wrap moaveze-monetization-page">
    <h1><span class="dashicons dashicons-chart-bar"></span> گزارش درآمدزایی</h1>

    <div class="moaveze-stats-grid">
        <div class="moaveze-stat-card moaveze-stat-success">
            <div class="stat-content">
                <h3><?php echo number_format($total_revenue); ?> تومان</h3>
                <p>درآمد کل</p>
            </div>
        </div>
        <div class="moaveze-stat-card moaveze-stat-primary">
            <div class="stat-content">
                <h3><?php echo number_format($monthly_revenue); ?> تومان</h3>
                <p>درآمد این ماه</p>
            </div>
        </div>
        <div class="moaveze-stat-card moaveze-stat-info">
            <div class="stat-content">
                <h3><?php echo number_format($active_subs); ?></h3>
                <p>اشتراک فعال</p>
            </div>
        </div>
    </div>

    <div class="moaveze-section">
        <h2>تراکنش‌های اخیر</h2>
        <?php
        $transactions = $wpdb->get_results("SELECT * FROM $table_views ORDER BY created_at DESC LIMIT 20");
        ?>
        <?php if (empty($transactions)) : ?>
            <p>هنوز تراکنشی ثبت نشده.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>آگهی</th>
                        <th>نوع</th>
                        <th>مبلغ</th>
                        <th>تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx) : ?>
                        <tr>
                            <td><?php echo esc_html(get_userdata($tx->user_id)->display_name ?? '—'); ?></td>
                            <td><?php echo esc_html($tx->exchange_id); ?></td>
                            <td><?php echo esc_html($tx->view_type); ?></td>
                            <td><?php echo number_format($tx->payment_amount); ?> تومان</td>
                            <td><?php echo esc_html(mysql2date('Y/m/d H:i', $tx->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
