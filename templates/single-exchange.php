<?php
/**
 * Single Exchange Listing Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$post_id = get_the_ID();
$value = get_post_meta($post_id, '_moaveze_property_value', true);
$area = get_post_meta($post_id, '_moaveze_area_sqm', true);
$rooms = get_post_meta($post_id, '_moaveze_rooms', true);
$floor = get_post_meta($post_id, '_moaveze_floor', true);
$total_floors = get_post_meta($post_id, '_moaveze_total_floors', true);
$year_built = get_post_meta($post_id, '_moaveze_year_built', true);
$lat = get_post_meta($post_id, '_moaveze_latitude', true);
$lng = get_post_meta($post_id, '_moaveze_longitude', true);
$address = get_post_meta($post_id, '_moaveze_address', true);
$exchange_type = get_post_meta($post_id, '_moaveze_exchange_type', true);
$desired_type = get_post_meta($post_id, '_moaveze_desired_property_type', true);
$desired_min = get_post_meta($post_id, '_moaveze_desired_min_value', true);
$desired_max = get_post_meta($post_id, '_moaveze_desired_max_value', true);
$cash_diff = get_post_meta($post_id, '_moaveze_cash_difference', true);
$cash_dir = get_post_meta($post_id, '_moaveze_cash_direction', true);
$additional = get_post_meta($post_id, '_moaveze_additional_assets', true);
$verified = get_post_meta($post_id, '_moaveze_verified', true);
$featured = get_post_meta($post_id, '_moaveze_featured', true);
$gallery = get_post_meta($post_id, '_moaveze_gallery', true);
$alt_conditions = get_post_meta($post_id, '_moaveze_alt_conditions', true);

$type_terms = get_the_terms($post_id, 'moaveze_property_type');
$district_terms = get_the_terms($post_id, 'moaveze_district');
$feature_terms = get_the_terms($post_id, 'moaveze_feature');

$exchange_labels = array(
    'property_only'  => 'ملک با ملک',
    'property_cash'  => 'ملک + مابه‌التفاوت نقدی',
    'property_car'   => 'ملک + خودرو',
    'property_mixed' => 'ترکیبی',
    'flexible'       => 'انعطاف‌پذیر',
);

// Increment views
$views = get_post_meta($post_id, '_moaveze_views_count', true) ?: 0;
update_post_meta($post_id, '_moaveze_views_count', $views + 1);
?>

<div class="moaveze-wrapper">
    <div class="moaveze-single-container">
        <!-- Breadcrumb -->
        <nav class="moaveze-breadcrumb">
            <a href="<?php echo home_url(); ?>">خانه</a>
            <span class="sep">/</span>
            <a href="<?php echo get_post_type_archive_link('moaveze_exchange'); ?>">معاوضه ملک</a>
            <span class="sep">/</span>
            <span class="current"><?php the_title(); ?></span>
        </nav>

        <!-- Header -->
        <div class="moaveze-single-header">
            <div class="single-header-content">
                <div class="single-badges">
                    <?php if ($verified) : ?>
                        <span class="badge badge-verified">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            تأیید شده
                        </span>
                    <?php endif; ?>
                    <?php if ($featured) : ?>
                        <span class="badge badge-featured">ویژه</span>
                    <?php endif; ?>
                    <span class="badge badge-exchange-type"><?php echo esc_html($exchange_labels[$exchange_type] ?? 'معاوضه'); ?></span>
                </div>
                <h1 class="single-title"><?php the_title(); ?></h1>
                <div class="single-location">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php if ($district_terms) echo esc_html($district_terms[0]->name); ?>
                    <?php if ($address) echo ' - ' . esc_html($address); ?>
                </div>
            </div>
            <div class="single-header-price">
                <span class="price-value"><?php echo number_format($value); ?></span>
                <span class="price-unit">تومان</span>
            </div>
        </div>

        <!-- Gallery -->
        <div class="moaveze-single-gallery">
            <?php if (has_post_thumbnail()) : ?>
                <div class="gallery-main">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($gallery) && is_array($gallery)) : ?>
                <div class="gallery-thumbs">
                    <?php foreach ($gallery as $img_id) : ?>
                        <div class="gallery-thumb" data-full="<?php echo esc_url(wp_get_attachment_image_url($img_id, 'large')); ?>">
                            <?php echo wp_get_attachment_image($img_id, 'thumbnail'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="moaveze-single-body">
            <!-- Main Content -->
            <div class="single-main">
                <!-- Property Specs -->
                <div class="moaveze-section-card">
                    <h3 class="section-title">مشخصات ملک</h3>
                    <div class="specs-grid">
                        <?php if ($type_terms) : ?>
                            <div class="spec-item">
                                <span class="spec-label">نوع ملک</span>
                                <span class="spec-value"><?php echo esc_html($type_terms[0]->name); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($area) : ?>
                            <div class="spec-item">
                                <span class="spec-label">متراژ</span>
                                <span class="spec-value"><?php echo number_format($area); ?> متر مربع</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($rooms) : ?>
                            <div class="spec-item">
                                <span class="spec-label">تعداد اتاق</span>
                                <span class="spec-value"><?php echo esc_html($rooms); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($floor) : ?>
                            <div class="spec-item">
                                <span class="spec-label">طبقه</span>
                                <span class="spec-value"><?php echo esc_html($floor); ?> از <?php echo esc_html($total_floors); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($year_built) : ?>
                            <div class="spec-item">
                                <span class="spec-label">سال ساخت</span>
                                <span class="spec-value"><?php echo esc_html($year_built); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="spec-item">
                            <span class="spec-label">قیمت هر متر</span>
                            <span class="spec-value"><?php echo $area ? number_format(round($value / $area)) : '—'; ?> تومان</span>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <?php if ($feature_terms && !is_wp_error($feature_terms)) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">امکانات و ویژگی‌ها</h3>
                        <div class="features-grid">
                            <?php foreach ($feature_terms as $feature) : ?>
                                <span class="feature-tag">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php echo esc_html($feature->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if (get_the_content()) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">توضیحات</h3>
                        <div class="single-description">
                            <?php the_content(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Map -->
                <?php if ($lat && $lng) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">موقعیت روی نقشه</h3>
                        <div class="single-map" id="single-map" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="single-sidebar">
                <!-- Exchange Conditions Card -->
                <div class="moaveze-section-card moaveze-exchange-card">
                    <h3 class="section-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/>
                            <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/>
                        </svg>
                        شرایط معاوضه
                    </h3>
                    <div class="exchange-conditions">
                        <div class="condition-item">
                            <span class="cond-label">نوع معاوضه:</span>
                            <span class="cond-value"><?php echo esc_html($exchange_labels[$exchange_type] ?? '—'); ?></span>
                        </div>
                        <?php if ($desired_type) : ?>
                            <div class="condition-item">
                                <span class="cond-label">ملک مورد نظر:</span>
                                <span class="cond-value"><?php echo esc_html($desired_type); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($desired_min || $desired_max) : ?>
                            <div class="condition-item">
                                <span class="cond-label">محدوده ارزش:</span>
                                <span class="cond-value">
                                    <?php if ($desired_min) echo number_format($desired_min); ?>
                                    <?php if ($desired_min && $desired_max) echo ' تا '; ?>
                                    <?php if ($desired_max) echo number_format($desired_max); ?>
                                    تومان
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($cash_diff) : ?>
                            <div class="condition-item condition-highlight">
                                <span class="cond-label">مابه‌التفاوت:</span>
                                <span class="cond-value">
                                    <?php echo number_format($cash_diff); ?> تومان
                                    (<?php echo $cash_dir === 'give' ? 'می‌دهم' : 'می‌گیرم'; ?>)
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($additional) : ?>
                            <div class="condition-item">
                                <span class="cond-label">دارایی اضافی:</span>
                                <span class="cond-value"><?php echo esc_html($additional); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($alt_conditions) && is_array($alt_conditions)) : ?>
                        <div class="alt-conditions">
                            <h4>شرایط جایگزین:</h4>
                            <?php foreach ($alt_conditions as $i => $cond) : ?>
                                <div class="alt-condition-item">
                                    <span class="alt-number"><?php echo $i + 1; ?></span>
                                    <p><?php echo esc_html($cond['description']); ?></p>
                                    <?php if (!empty($cond['value'])) : ?>
                                        <small>ارزش: <?php echo number_format($cond['value']); ?> تومان</small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Offer Button -->
                <div class="moaveze-section-card">
                    <button class="moaveze-btn moaveze-btn-primary moaveze-btn-full" id="send-offer-btn" data-exchange-id="<?php echo esc_attr($post_id); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        ارسال پیشنهاد معاوضه
                    </button>
                    <p class="offer-note">پیشنهاد شما بدون نمایش اطلاعات تماس ارسال می‌شود</p>
                </div>

                <!-- Views Counter -->
                <div class="moaveze-views-counter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <span><?php echo number_format($views + 1); ?> بازدید</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
