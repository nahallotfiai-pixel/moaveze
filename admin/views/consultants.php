<?php
/**
 * Consultants Admin View - with search + role grouping
 */

if (!defined('ABSPATH')) {
    exit;
}

$consultants = get_users(array('role' => 'moaveze_consultant'));
?>

<div class="wrap moaveze-consultants-page">
    <h1><span class="dashicons dashicons-businessman"></span> مدیریت مشاوران</h1>

    <div class="moaveze-section" style="background:#fff;padding:20px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:20px;">
        <h2 style="margin-top:0;">افزودن مشاور جدید</h2>
        <p class="description">نام، ایمیل یا شماره تلفن کاربر مورد نظر را جستجو کنید:</p>

        <div class="moaveze-add-consultant" style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap;">
            <div style="flex:1;min-width:300px;position:relative;">
                <input type="text" id="consultant-search-input" class="regular-text" style="width:100%;" placeholder="جستجوی کاربر (نام، ایمیل، تلفن)..." autocomplete="off">
                <input type="hidden" id="selected-consultant-id" value="">
                <div id="consultant-search-results" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:999;background:#fff;border:1px solid #ddd;border-radius:8px;max-height:300px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1);"></div>
            </div>
            <button id="add-consultant-btn" class="button button-primary" disabled>افزودن به مشاوران</button>
        </div>
    </div>

    <div class="moaveze-section" style="background:#fff;padding:20px;border-radius:10px;border:1px solid #e2e8f0;">
        <h2 style="margin-top:0;">لیست مشاوران (<?php echo count($consultants); ?> نفر)</h2>

        <?php if (empty($consultants)) : ?>
            <p style="color:#64748b;">هنوز مشاوری ثبت نشده.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:50px;">آواتار</th>
                        <th>نام</th>
                        <th>ایمیل</th>
                        <th>تلفن</th>
                        <th>تاریخ عضویت</th>
                        <th>موارد اختصاص‌یافته</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultants as $consultant) : ?>
                        <tr>
                            <td><?php echo get_avatar($consultant->ID, 36); ?></td>
                            <td><strong><?php echo esc_html($consultant->display_name); ?></strong></td>
                            <td><?php echo esc_html($consultant->user_email); ?></td>
                            <td><?php echo esc_html(get_user_meta($consultant->ID, 'phone', true) ?: '—'); ?></td>
                            <td><?php echo esc_html(Moaveze_Helpers::jalali_date($consultant->user_registered)); ?></td>
                            <td>
                                <?php
                                global $wpdb;
                                $assigned = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_matches WHERE consultant_id = %d",
                                    $consultant->ID
                                ));
                                echo esc_html($assigned);
                                ?>
                            </td>
                            <td>
                                <button class="button button-small remove-consultant-btn" data-user-id="<?php echo esc_attr($consultant->ID); ?>">حذف از مشاوران</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(function($) {
    let searchTimeout;
    const $input = $('#consultant-search-input');
    const $results = $('#consultant-search-results');
    const $hiddenId = $('#selected-consultant-id');
    const $addBtn = $('#add-consultant-btn');

    $input.on('input', function() {
        const query = $(this).val().trim();
        $hiddenId.val('');
        $addBtn.prop('disabled', true);

        clearTimeout(searchTimeout);
        if (query.length < 2) {
            $results.hide().empty();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.post(moavezeAdmin.ajaxUrl, {
                action: 'moaveze_search_users',
                nonce: moavezeAdmin.nonce,
                search: query
            }, function(response) {
                if (!response.success || !response.data.users.length) {
                    $results.html('<div style="padding:12px;color:#94a3b8;text-align:center;">کاربری یافت نشد</div>').show();
                    return;
                }

                let html = '';
                let currentRole = '';
                response.data.users.forEach(function(user) {
                    if (user.role_label !== currentRole) {
                        currentRole = user.role_label;
                        html += '<div style="padding:6px 12px;background:#f1f5f9;font-size:11px;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">' + currentRole + '</div>';
                    }
                    html += '<div class="consultant-search-item" data-user-id="' + user.id + '" style="padding:10px 12px;cursor:pointer;display:flex;align-items:center;gap:10px;border-bottom:1px solid #f1f5f9;transition:background 0.15s;">';
                    html += '<img src="' + user.avatar + '" style="width:32px;height:32px;border-radius:50%;" alt="">';
                    html += '<div style="flex:1;">';
                    html += '<strong style="font-size:13px;">' + user.display_name + '</strong>';
                    html += '<div style="font-size:11px;color:#6b7280;">' + user.email;
                    if (user.phone) html += ' | ' + user.phone;
                    html += '</div></div>';
                    html += '<span style="font-size:10px;background:#e0e7ff;color:#3730a3;padding:2px 6px;border-radius:4px;">' + user.role_label + '</span>';
                    html += '</div>';
                });

                $results.html(html).show();
            });
        }, 300);
    });

    // Select user from results
    $(document).on('click', '.consultant-search-item', function() {
        const userId = $(this).data('user-id');
        const name = $(this).find('strong').text();
        $input.val(name);
        $hiddenId.val(userId);
        $addBtn.prop('disabled', false);
        $results.hide();
    });

    // Hide results on click outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.moaveze-add-consultant').length) {
            $results.hide();
        }
    });

    // Hover effect
    $(document).on('mouseenter', '.consultant-search-item', function() {
        $(this).css('background', '#f8fafc');
    }).on('mouseleave', '.consultant-search-item', function() {
        $(this).css('background', '');
    });

    // Add consultant button
    $addBtn.on('click', function() {
        const userId = $hiddenId.val();
        if (!userId) return;

        $(this).prop('disabled', true).text('در حال افزودن...');

        $.post(moavezeAdmin.ajaxUrl, {
            action: 'moaveze_add_consultant_role',
            nonce: moavezeAdmin.nonce,
            user_id: userId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'خطا در افزودن مشاور');
                $addBtn.prop('disabled', false).text('افزودن به مشاوران');
            }
        }).fail(function() {
            alert('خطا در ارتباط با سرور');
            $addBtn.prop('disabled', false).text('افزودن به مشاوران');
        });
    });
});
</script>

<style>
    .moaveze-consultants-page .wp-list-table img { border-radius: 50%; }
    .moaveze-consultants-page .moaveze-section h2 { font-size: 16px; color: #1e293b; }
</style>
