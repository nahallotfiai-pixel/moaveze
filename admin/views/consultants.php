<?php
/**
 * Consultants Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

$consultants = get_users(array('role' => 'moaveze_consultant'));
?>

<div class="wrap moaveze-consultants-page">
    <h1><span class="dashicons dashicons-businessman"></span> مدیریت مشاوران</h1>

    <div class="moaveze-section">
        <h2>افزودن مشاور جدید</h2>
        <div class="moaveze-add-consultant">
            <select id="user-to-consultant" class="regular-text">
                <option value="">انتخاب کاربر...</option>
                <?php
                $users = get_users(array('role__not_in' => array('moaveze_consultant', 'administrator')));
                foreach ($users as $user) :
                ?>
                    <option value="<?php echo esc_attr($user->ID); ?>"><?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?></option>
                <?php endforeach; ?>
            </select>
            <button id="add-consultant-btn" class="button button-primary">افزودن به مشاوران</button>
        </div>
    </div>

    <div class="moaveze-section">
        <h2>لیست مشاوران (<?php echo count($consultants); ?> نفر)</h2>

        <?php if (empty($consultants)) : ?>
            <p>هنوز مشاوری ثبت نشده.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>ایمیل</th>
                        <th>تاریخ عضویت</th>
                        <th>موارد اختصاص‌یافته</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultants as $consultant) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($consultant->display_name); ?></strong></td>
                            <td><?php echo esc_html($consultant->user_email); ?></td>
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
