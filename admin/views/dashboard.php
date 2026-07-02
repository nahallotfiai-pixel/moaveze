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

    <!-- Pending Review Panel (NEW) -->
    <?php $pending_listings = Moaveze_Dashboard::get_pending_listings(10); ?>
    <div class="moaveze-section moaveze-pending-panel">
        <div class="pending-panel-header">
            <h2>
                <span class="dashicons dashicons-clock"></span>
                در انتظار تأیید
                <?php if (!empty($pending_listings)) : ?>
                    <span class="pending-count-badge"><?php echo count($pending_listings); ?></span>
                <?php endif; ?>
            </h2>
            <?php if (!empty($pending_listings)) : ?>
                <a href="<?php echo admin_url('edit.php?post_status=pending&post_type=moaveze_exchange'); ?>" class="button">مشاهده همه</a>
            <?php endif; ?>
        </div>

        <?php if (empty($pending_listings)) : ?>
            <div class="moaveze-empty-inline">
                <span class="dashicons dashicons-yes-alt"></span>
                <p>هیچ آگهی در انتظار تأیید نیست. همه چیز به‌روز است!</p>
            </div>
        <?php else : ?>
            <div class="pending-listings-grid" id="pending-listings-grid">
                <?php foreach ($pending_listings as $item) : ?>
                    <div class="pending-listing-card" data-post-id="<?php echo esc_attr($item['id']); ?>">
                        <div class="plc-thumb">
                            <?php if ($item['thumbnail']) : ?>
                                <img src="<?php echo esc_url($item['thumbnail']); ?>" alt="">
                            <?php else : ?>
                                <span class="dashicons dashicons-camera"></span>
                            <?php endif; ?>
                        </div>
                        <div class="plc-body">
                            <strong class="plc-title"><?php echo esc_html($item['title']); ?></strong>
                            <div class="plc-meta">
                                <span><?php echo esc_html($item['type'][0] ?? '—'); ?></span>
                                <span><?php echo esc_html($item['district'][0] ?? '—'); ?></span>
                                <span><?php echo $item['value'] ? number_format($item['value']) . ' تومان' : '—'; ?></span>
                            </div>
                            <div class="plc-submeta">
                                توسط <?php echo esc_html($item['author']); ?> ·
                                <?php echo esc_html(Moaveze_Helpers::jalali_time_diff($item['date'])); ?>
                            </div>
                        </div>
                        <div class="plc-actions">
                            <a href="<?php echo esc_url($item['preview_link']); ?>" target="_blank" class="button button-small" title="پیش‌نمایش">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                            <a href="<?php echo esc_url($item['edit_link']); ?>" class="button button-small" title="ویرایش">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <button class="button button-primary button-small approve-listing-btn" data-post-id="<?php echo esc_attr($item['id']); ?>">
                                <span class="dashicons dashicons-yes"></span> تأیید
                            </button>
                            <button class="button button-small reject-listing-btn" data-post-id="<?php echo esc_attr($item['id']); ?>">
                                <span class="dashicons dashicons-no"></span> رد
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

    <!-- Sample Data Tools -->
    <div class="moaveze-section" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;margin-top:20px;">
        <h2 style="color:#166534;"><span class="dashicons dashicons-database"></span> داده‌های نمونه (تست)</h2>
        <p style="color:#15803d;">برای تست عملکرد افزونه، ۶ آگهی نمونه واقعی تبریز + تطابق‌ها و پیشنهادات ایجاد کنید:</p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
            <button id="seed-sample-data" class="button button-primary" style="background:#16a34a;border-color:#16a34a;">
                <span class="dashicons dashicons-database-add"></span> ایجاد داده‌های نمونه
            </button>
            <button id="clear-sample-data" class="button" style="color:#dc2626;border-color:#dc2626;">
                <span class="dashicons dashicons-trash"></span> پاک کردن همه داده‌ها
            </button>
        </div>
        <div id="sample-data-result" style="margin-top:10px;"></div>
    </div>
    <script>
    jQuery(function($) {
        $('#seed-sample-data').on('click', function() {
            var $btn = $(this).prop('disabled', true).text('در حال ایجاد...');
            $.post(ajaxurl, { action: 'moaveze_seed_sample_data', nonce: moavezeAdmin.nonce }, function(r) {
                if (r.success) {
                    $('#sample-data-result').html('<p style="color:#16a34a;">✓ ' + r.data.message + '</p>');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $('#sample-data-result').html('<p style="color:#dc2626;">✗ خطا</p>');
                }
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-database-add"></span> ایجاد داده‌های نمونه');
            });
        });
        $('#clear-sample-data').on('click', function() {
            if (!confirm('آیا مطمئنید؟ همه آگهی‌ها و داده‌ها پاک خواهند شد.')) return;
            var $btn = $(this).prop('disabled', true).text('در حال پاک‌سازی...');
            $.post(ajaxurl, { action: 'moaveze_clear_sample_data', nonce: moavezeAdmin.nonce }, function(r) {
                if (r.success) {
                    $('#sample-data-result').html('<p style="color:#16a34a;">✓ ' + r.data.message + '</p>');
                    setTimeout(function() { location.reload(); }, 1500);
                }
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> پاک کردن همه داده‌ها');
            });
        });
    });
    </script>

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
                            <td><?php echo esc_html(Moaveze_Helpers::jalali_date($activity['date'], 'Y/m/d H:i')); ?></td>
                            <td><a href="<?php echo esc_url($activity['link']); ?>" class="button button-small">مشاهده</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
