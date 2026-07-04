<?php
/**
 * "آگهی‌های فروش (املاک)" Admin Page
 *
 * ROOT-CAUSE FIX for "دکمه تبدیل به معاوضه هیچ‌جا نیست":
 *
 * The Houzez theme renders its own "Properties" list table
 * (edit.php?post_type=property) with a heavily customized layout -
 * a bundled "Info" column, custom SVG action icons, its own JS, etc.
 * It does NOT build that table using WordPress's standard
 * manage_{post_type}_posts_columns / post_row_actions filters the
 * normal way core does - it overrides/replaces large parts of the
 * list table rendering itself. That is why every previous fix
 * (a new column via manage_property_posts_columns, a row-action via
 * post_row_actions/page_row_actions) never visibly appeared on that
 * specific screen: those filters were correctly registered on our
 * side (confirmed - the plugin version bump WAS live), but Houzez's
 * own list-table code path never calls the WordPress core rendering
 * functions that fire them.
 *
 * Rather than continuing to fight an unreliable, undocumented,
 * theme-controlled screen, this page is a completely independent,
 * plugin-owned list of every Houzez "property" post - built with our
 * own <table>, our own query, our own button - that does not depend
 * on a single Houzez hook/filter/template firing correctly. This is
 * now the permanent, guaranteed-to-work home for "تبدیل به معاوضه".
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!post_type_exists('property')) {
    echo '<div class="wrap"><h1>آگهی‌های فروش</h1><p>قالب Houzez (یا نوع پست "property") روی این سایت شناسایی نشد.</p></div>';
    return;
}

$paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$per_page = 30;

$query = new WP_Query(array(
    'post_type'      => 'property',
    'post_status'    => array('publish', 'pending', 'draft'),
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
));
?>

<div class="wrap moaveze-properties-page">
    <h1>
        <span class="dashicons dashicons-admin-multisite"></span>
        آگهی‌های فروش (املاک Houzez) - تبدیل به معاوضه
    </h1>

    <div class="moaveze-notice" style="background:#eef2ff;border:1px solid #c7d2fe;color:#3730a3;padding:12px 16px;border-radius:8px;margin:16px 0;">
        <span class="dashicons dashicons-info"></span>
        <p style="margin:6px 0 0;">
            این صفحه مستقل از جدول «Properties» قالب است تا دکمه‌ها همیشه قابل مشاهده و کارکردن باشند.
            برای هر ملک می‌توانید آن را به یک آگهی معاوضه تبدیل کنید (کپی کامل قیمت، متراژ، امکانات، تصاویر و موقعیت)
            یا وارد صفحه ویرایش شوید تا <strong>ارزش‌گذاری هوش مصنوعی</strong> را روی آن اجرا کنید.
        </p>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:35%;">عنوان</th>
                <th>قیمت ثبت‌شده</th>
                <th>وضعیت انتشار</th>
                <th>وضعیت معاوضه</th>
                <th style="width:260px;">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$query->have_posts()) : ?>
                <tr><td colspan="5">هیچ ملکی یافت نشد.</td></tr>
            <?php else : ?>
                <?php while ($query->have_posts()) : $query->the_post();
                    $property_id = get_the_ID();
                    $price = get_post_meta($property_id, 'fave_property_price', true);
                    $exchange_id = get_post_meta($property_id, '_moaveze_exchange_linked', true);
                    $is_linked = $exchange_id && get_post($exchange_id);
                    $status = get_post_status($property_id);
                    $status_labels = array(
                        'publish' => '<span style="color:#00a32a;">منتشرشده</span>',
                        'pending' => '<span style="color:#dba617;">در انتظار</span>',
                        'draft'   => '<span style="color:#787c82;">پیش‌نویس</span>',
                    );
                    ?>
                    <tr data-property-row="<?php echo esc_attr($property_id); ?>">
                        <td>
                            <strong><a href="<?php echo esc_url(get_edit_post_link($property_id)); ?>"><?php the_title(); ?></a></strong>
                            <div class="row-actions">
                                <span><a href="<?php echo esc_url(get_edit_post_link($property_id)); ?>">ویرایش</a> | </span>
                                <span><a href="<?php echo esc_url(get_permalink($property_id)); ?>" target="_blank">مشاهده</a></span>
                            </div>
                        </td>
                        <td><?php echo $price ? esc_html(Moaveze_Helpers::short_price($price)) : '—'; ?></td>
                        <td><?php echo $status_labels[$status] ?? esc_html($status); ?></td>
                        <td class="moaveze-conversion-status-cell">
                            <?php if ($is_linked) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color:#10b981;"></span> متصل به معاوضه
                            <?php else : ?>
                                <span style="color:#94a3b8;">هنوز تبدیل نشده</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_linked) : ?>
                                <a href="<?php echo esc_url(get_edit_post_link($exchange_id)); ?>" class="button button-small">
                                    مشاهده آگهی معاوضه
                                </a>
                            <?php else : ?>
                                <button type="button" class="button button-primary button-small moaveze-convert-to-exchange-btn" data-property-id="<?php echo esc_attr($property_id); ?>">
                                    <span class="dashicons dashicons-randomize"></span> تبدیل به معاوضه
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(get_edit_post_link($property_id, 'raw') . '#moaveze_ai_valuation'); ?>" class="button button-small">
                                <span class="dashicons dashicons-chart-line"></span> ارزش‌گذاری
                            </a>
                        </td>
                    </tr>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    $total_pages = $query->max_num_pages;
    if ($total_pages > 1) :
        echo '<div class="tablenav"><div class="tablenav-pages">';
        echo paginate_links(array(
            'base'    => add_query_arg('paged', '%#%'),
            'format'  => '',
            'current' => $paged,
            'total'   => $total_pages,
        ));
        echo '</div></div>';
    endif;
    ?>
</div>
