<?php
/**
 * Offers Admin View
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_offers = $wpdb->prefix . 'moaveze_offers';
$offers = $wpdb->get_results("SELECT * FROM $table_offers ORDER BY created_at DESC LIMIT 50");
?>

<div class="wrap moaveze-offers-page">
    <h1><span class="dashicons dashicons-email-alt"></span> پیشنهادات معاوضه</h1>

    <?php if (empty($offers)) : ?>
        <div class="moaveze-empty-state">
            <span class="dashicons dashicons-email-alt" style="font-size:48px;color:#ddd;"></span>
            <p>هنوز پیشنهادی ثبت نشده است.</p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped moaveze-table">
            <thead>
                <tr>
                    <th>ارسال‌کننده</th>
                    <th>آگهی مقصد</th>
                    <th>نوع پیشنهاد</th>
                    <th>مبلغ پیشنهادی</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offers as $offer) : ?>
                    <tr>
                        <td><?php echo esc_html(get_userdata($offer->from_user_id)->display_name ?? '—'); ?></td>
                        <td>
                            <?php
                            $exchange = $wpdb->get_row($wpdb->prepare(
                                "SELECT post_id FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $offer->exchange_id
                            ));
                            echo $exchange ? esc_html(get_the_title($exchange->post_id)) : '—';
                            ?>
                        </td>
                        <td><?php echo esc_html($offer->offer_type); ?></td>
                        <td><?php echo number_format($offer->cash_offered); ?> تومان</td>
                        <td>
                            <?php
                            $statuses = array(
                                'pending'  => '<span class="moaveze-badge moaveze-badge-warning">در انتظار</span>',
                                'accepted' => '<span class="moaveze-badge moaveze-badge-success">قبول شده</span>',
                                'rejected' => '<span class="moaveze-badge moaveze-badge-danger">رد شده</span>',
                            );
                            echo $statuses[$offer->status] ?? $offer->status;
                            ?>
                        </td>
                        <td><?php echo esc_html(mysql2date('Y/m/d', $offer->created_at)); ?></td>
                        <td>
                            <button class="button button-small view-offer-btn" data-offer-id="<?php echo esc_attr($offer->id); ?>">جزئیات</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
