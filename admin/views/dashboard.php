<?php
/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = Moaveze_Dashboard::get_stats();
$activities = Moaveze_Dashboard::get_recent_activity();
?>

<div class="wrap moaveze-dashboard">
    <h1><span class="dashicons dashicons-randomize"></span> داشبورد معاوضه پلاس</h1>

    <!-- Stats Cards -->
    <div class="moaveze-stats-grid">
        <div class="moaveze-stat-card moaveze-stat-primary">
            <div class="stat-icon"><span class="dashicons dashicons-building"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_exchanges']); ?></h3>
                <p>آگهی فعال</p>
            </div>
            <?php if ($stats['pending_exchanges'] > 0) : ?>
                <span class="stat-badge"><?php echo $stats['pending_exchanges']; ?> در انتظار</span>
            <?php endif; ?>
        </div>

        <div class="moaveze-stat-card moaveze-stat-success">
            <div class="stat-icon"><span class="dashicons dashicons-update"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_matches']); ?></h3>
                <p>تطابق یافته</p>
            </div>
            <?php if ($stats['new_matches'] > 0) : ?>
                <span class="stat-badge"><?php echo $stats['new_matches']; ?> جدید</span>
            <?php endif; ?>
        </div>

        <div class="moaveze-stat-card moaveze-stat-warning">
            <div class="stat-icon"><span class="dashicons dashicons-email-alt"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_offers']); ?></h3>
                <p>پیشنهاد</p>
            </div>
            <?php if ($stats['pending_offers'] > 0) : ?>
                <span class="stat-badge"><?php echo $stats['pending_offers']; ?> در انتظار</span>
            <?php endif; ?>
        </div>

        <div class="moaveze-stat-card moaveze-stat-info">
            <div class="stat-icon"><span class="dashicons dashicons-chart-area"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['monthly_revenue']); ?></h3>
                <p>درآمد این ماه (تومان)</p>
            </div>
        </div>

        <div class="moaveze-stat-card moaveze-stat-purple">
            <div class="stat-icon"><span class="dashicons dashicons-networking"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['active_chains']); ?></h3>
                <p>زنجیره فعال</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="moaveze-section">
        <h2>دسترسی سریع</h2>
        <div class="moaveze-quick-actions">
            <a href="<?php echo admin_url('post-new.php?post_type=moaveze_exchange'); ?>" class="moaveze-action-btn">
                <span class="dashicons dashicons-plus-alt"></span>
                <span>افزودن آگهی</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=moaveze-matches'); ?>" class="moaveze-action-btn">
                <span class="dashicons dashicons-update"></span>
                <span>تطابق‌ها</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=moaveze-offers'); ?>" class="moaveze-action-btn">
                <span class="dashicons dashicons-email-alt"></span>
                <span>پیشنهادات</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=moaveze-settings'); ?>" class="moaveze-action-btn">
                <span class="dashicons dashicons-admin-generic"></span>
                <span>تنظیمات</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="moaveze-section">
        <h2>فعالیت‌های اخیر</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>عنوان</th>
                    <th>وضعیت</th>
                    <th>کاربر</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activities)) : ?>
                    <tr><td colspan="5">هنوز فعالیتی ثبت نشده</td></tr>
                <?php else : ?>
                    <?php foreach ($activities as $activity) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($activity['title']); ?></strong></td>
                            <td>
                                <?php if ($activity['status'] === 'publish') : ?>
                                    <span class="moaveze-badge moaveze-badge-success">فعال</span>
                                <?php else : ?>
                                    <span class="moaveze-badge moaveze-badge-warning">در انتظار</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(get_userdata($activity['user_id'])->display_name ?? '—'); ?></td>
                            <td><?php echo esc_html(mysql2date('Y/m/d H:i', $activity['date'])); ?></td>
                            <td><a href="<?php echo esc_url($activity['link']); ?>" class="button button-small">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
