<?php
/**
 * Valuations Admin Page
 *
 * Dedicated, discoverable home for the staff-only property valuation
 * feature (manual + AI-assisted) - see includes/modules/class-ai-valuation.php.
 * Previously this feature only existed as a metabox buried at the
 * bottom of each individual listing's edit screen with no link
 * anywhere else, which is why the site owner could not find it. This
 * page lists every listing with its valuation status at a glance and
 * links straight into the metabox (or lets you apply a pending
 * suggestion with one click, without leaving this page).
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$ai_enabled = get_option('moaveze_ai_valuation_enabled') === 'yes';
$active_provider = get_option('moaveze_ai_active_provider', 'gemini');
$providers = class_exists('Moaveze_AI_Valuation') ? Moaveze_AI_Valuation::get_providers() : array();

// One row per listing, with its most recent valuation (if any) joined in.
$rows = $wpdb->get_results("
    SELECT p.ID as post_id, p.post_title, p.post_status,
           v.id as valuation_id, v.type as v_type, v.status as v_status,
           v.manual_value, v.suggested_value, v.ai_provider, v.created_at as v_created_at
    FROM {$wpdb->posts} p
    LEFT JOIN (
        SELECT v1.* FROM {$wpdb->prefix}moaveze_valuations v1
        INNER JOIN (
            SELECT post_id, MAX(id) as max_id FROM {$wpdb->prefix}moaveze_valuations GROUP BY post_id
        ) v2 ON v1.post_id = v2.post_id AND v1.id = v2.max_id
    ) v ON v.post_id = p.ID
    WHERE p.post_type = 'moaveze_exchange' AND p.post_status IN ('publish','pending','draft')
    ORDER BY p.post_date DESC
");

$pending_count = 0;
foreach ($rows as $r) {
    if ($r->valuation_id && $r->v_status === 'pending') $pending_count++;
}
?>

<div class="wrap moaveze-valuations-page">
    <h1><span class="dashicons dashicons-chart-line"></span> ارزش‌گذاری ملک (مدیران و مشاوران)</h1>

    <div class="moaveze-notice moaveze-notice-warning">
        <span class="dashicons dashicons-lock"></span>
        <p>این بخش فقط برای مدیران و مشاوران تبریز هوم قابل مشاهده است و هرگز به کاربران عادی سایت نمایش داده نمی‌شود. برای هر آگهی می‌توانید ارزش‌گذاری دستی ثبت کنید یا (در صورت فعال بودن) از هوش مصنوعی پیشنهاد بگیرید.</p>
    </div>

    <?php if (!$ai_enabled) : ?>
        <div class="moaveze-notice" style="background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;">
            <span class="dashicons dashicons-info"></span>
            <p>
                ارزش‌گذاری با هوش مصنوعی در حال حاضر غیرفعال است (فقط ارزش‌گذاری دستی در دسترس است).
                برای فعال‌سازی به <a href="<?php echo esc_url(admin_url('admin.php?page=moaveze-settings&tab=ai')); ?>">تنظیمات &gt; هوش مصنوعی</a> بروید.
            </p>
        </div>
    <?php else : ?>
        <div class="moaveze-notice" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;">
            <span class="dashicons dashicons-yes-alt"></span>
            <p>
                ارزش‌گذاری با هوش مصنوعی فعال است (ارائه‌دهنده فعلی: <strong><?php echo esc_html($providers[$active_provider]['label'] ?? $active_provider); ?></strong>).
                <a href="<?php echo esc_url(admin_url('admin.php?page=moaveze-settings&tab=ai')); ?>">تغییر تنظیمات</a>
            </p>
        </div>
    <?php endif; ?>

    <div class="moaveze-stats-grid" style="margin-top:16px;">
        <div class="moaveze-stat-card moaveze-stat-warning">
            <div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($pending_count); ?></h3>
                <p>ارزش‌گذاری در انتظار بررسی</p>
            </div>
        </div>
        <div class="moaveze-stat-card moaveze-stat-primary">
            <div class="stat-icon"><span class="dashicons dashicons-building"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format(count($rows)); ?></h3>
                <p>کل آگهی‌ها</p>
            </div>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
        <thead>
            <tr>
                <th>عنوان آگهی</th>
                <th>ارزش ثبت‌شده مالک</th>
                <th>آخرین ارزش‌گذاری کارشناسی</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)) : ?>
                <tr><td colspan="5">هنوز هیچ آگهی‌ای ثبت نشده است.</td></tr>
            <?php else : ?>
                <?php foreach ($rows as $row) :
                    $owner_value = get_post_meta($row->post_id, '_moaveze_property_value', true);
                    $edit_link = get_edit_post_link($row->post_id, 'raw') . '#moaveze_ai_valuation';
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($row->post_title); ?></strong></td>
                        <td><?php echo esc_html(Moaveze_Helpers::short_price($owner_value)); ?></td>
                        <td>
                            <?php if (!$row->valuation_id) : ?>
                                <span style="color:#94a3b8;">— هنوز ارزش‌گذاری نشده —</span>
                            <?php else : ?>
                                <?php
                                $v_value = $row->manual_value ?: $row->suggested_value;
                                echo esc_html(Moaveze_Helpers::short_price($v_value));
                                echo $row->v_type === 'ai'
                                    ? ' <span class="vh-type ai">🤖 (' . esc_html($providers[$row->ai_provider]['label'] ?? $row->ai_provider) . ')</span>'
                                    : ' <span class="vh-type manual">👤 دستی</span>';
                                ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$row->valuation_id) : ?>
                                <span class="moaveze-badge" style="background:#f1f5f9;color:#64748b;">بدون ارزش‌گذاری</span>
                            <?php elseif ($row->v_status === 'applied') : ?>
                                <span class="moaveze-badge moaveze-badge-success" style="background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:20px;font-size:11px;">✓ اعمال شده</span>
                            <?php else : ?>
                                <span class="moaveze-badge moaveze-badge-warning" style="background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:20px;font-size:11px;">در انتظار تأیید</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($edit_link); ?>" class="button button-primary button-small">
                                <span class="dashicons dashicons-chart-line"></span> ارزش‌گذاری
                            </a>
                            <a href="<?php echo esc_url(get_permalink($row->post_id)); ?>" target="_blank" class="button button-small">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
