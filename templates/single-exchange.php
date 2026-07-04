<?php
/**
 * Single Exchange Listing Template - Redesigned (v2)
 * Premium, clear, and transparent presentation of a single listing.
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

// Expert/AI valuation ("قیمت کارشناسی") - ALWAYS a separate figure from
// the owner's own declared price above; never overwrites $value. Only
// rendered if a consultant has explicitly "applied" a valuation via the
// staff-only AI Valuation metabox (see class-ai-valuation.php).
$expert_value = get_post_meta($post_id, '_moaveze_expert_value', true);
$expert_min = get_post_meta($post_id, '_moaveze_expert_min', true);
$expert_max = get_post_meta($post_id, '_moaveze_expert_max', true);
$expert_value_type = get_post_meta($post_id, '_moaveze_expert_value_type', true);

// DEFENSIVE FIX: get_the_terms() returns a WP_Error object (not false)
// if the taxonomy itself is misconfigured/missing, and previously
// every "if ($type_terms)" check below would have evaluated a WP_Error
// object as truthy (it's a non-empty object), so $type_terms[0] could
// fatal with "cannot use object of type WP_Error as array". Explicitly
// normalize to null/empty-array here so every downstream check
// ("if ($type_terms) ...", "$type_terms[0]->name", etc.) is safe.
$type_terms = get_the_terms($post_id, 'moaveze_property_type');
if (is_wp_error($type_terms) || empty($type_terms)) $type_terms = null;

$district_terms = get_the_terms($post_id, 'moaveze_district');
if (is_wp_error($district_terms) || empty($district_terms)) $district_terms = null;

$feature_terms = get_the_terms($post_id, 'moaveze_feature');
if (is_wp_error($feature_terms) || empty($feature_terms)) $feature_terms = null;

$exchange_labels = array(
    'property_only'  => 'ملک با ملک',
    'property_cash'  => 'ملک + مابه‌التفاوت نقدی',
    'property_car'   => 'ملک + خودرو',
    'property_mixed' => 'ترکیبی',
    'flexible'       => 'انعطاف‌پذیر (بررسی پیشنهادات)',
);

$exchange_icons = array(
    'property_only'  => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    'property_cash'  => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',
    'property_car'   => '<path d="M5 17h14M5 17a2 2 0 01-2-2V9l2-4h10l2 4v6a2 2 0 01-2 2M5 17a2 2 0 100 4 2 2 0 000-4zM15 17a2 2 0 100 4 2 2 0 000-4z"/>',
    'property_mixed' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'flexible'       => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>',
);

// Increment views (once per page load is acceptable here)
$views = get_post_meta($post_id, '_moaveze_views_count', true) ?: 0;
update_post_meta($post_id, '_moaveze_views_count', $views + 1);

// Feature icon map (matches submission-form checkboxes)
$feature_icons = array(
    'پارکینگ' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
    'آسانسور' => '<rect x="2" y="2" width="20" height="20" rx="2"/><path d="M12 6v12M8 10l4-4 4 4M8 14l4 4 4-4"/>',
    'انباری'  => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>',
    'بالکن'   => '<path d="M3 12h18M3 12v8M21 12v8M5 12V7a7 7 0 0114 0v5"/>',
    'استخر'   => '<path d="M2 12h20M2 16c2 2 4 2 6 0s4-2 6 0 4 2 6 0"/>',
    'نگهبانی' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
);
?>

<div class="moaveze-wrapper moaveze-single-v2">
    <div class="moaveze-single-container">

        <!-- Breadcrumb -->
        <nav class="moaveze-breadcrumb">
            <a href="<?php echo home_url(); ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                خانه
            </a>
            <span class="sep">/</span>
            <a href="<?php echo esc_url(Moaveze_Pages::get_listings_url()); ?>">معاوضه ملک</a>
            <span class="sep">/</span>
            <span class="current"><?php the_title(); ?></span>
        </nav>


        <!-- ============ HERO ============ -->
        <div class="single-hero">
            <div class="single-hero-badges">
                <?php if ($verified) : ?>
                    <span class="hero-badge badge-verified">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        تأیید شده توسط مشاور
                    </span>
                <?php endif; ?>
                <?php if ($featured) : ?>
                    <span class="hero-badge badge-featured">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        ویژه
                    </span>
                <?php endif; ?>
                <span class="hero-badge badge-exchange-type">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $exchange_icons[$exchange_type] ?? ''; ?></svg>
                    <?php echo esc_html($exchange_labels[$exchange_type] ?? 'معاوضه'); ?>
                </span>
            </div>

            <div class="single-hero-row">
                <div class="single-hero-info">
                    <h1 class="single-title"><?php the_title(); ?></h1>
                    <div class="single-location">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php if ($district_terms) echo esc_html($district_terms[0]->name); ?>
                        <?php if ($address) echo ' · ' . esc_html($address); ?>
                    </div>
                </div>
                <div class="single-hero-price">
                    <span class="price-label">ارزش ملک</span>
                    <span class="price-value" title="<?php echo esc_attr(number_format($value) . ' تومان'); ?>">
                        <?php echo esc_html(Moaveze_Helpers::short_price($value)); ?>
                    </span>
                    <?php if ($area) : ?>
                        <span class="price-per-sqm"><?php echo esc_html(Moaveze_Helpers::short_price(round($value / $area))); ?> / متر</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- ============ GALLERY ============ -->
        <div class="moaveze-single-gallery" id="single-gallery">
            <?php
            $all_images = array();
            if (has_post_thumbnail()) {
                $all_images[] = array(
                    'full'  => get_the_post_thumbnail_url($post_id, 'full'),
                    'large' => get_the_post_thumbnail_url($post_id, 'large'),
                );
            }
            if (!empty($gallery) && is_array($gallery)) {
                foreach ($gallery as $img_id) {
                    $full = wp_get_attachment_image_url($img_id, 'full');
                    if ($full && !in_array($full, wp_list_pluck($all_images, 'full'), true)) {
                        $all_images[] = array(
                            'full'  => $full,
                            'large' => wp_get_attachment_image_url($img_id, 'large'),
                        );
                    }
                }
            }
            ?>
            <?php if (!empty($all_images)) : ?>
                <div class="gallery-main" data-full="<?php echo esc_url($all_images[0]['full']); ?>">
                    <img src="<?php echo esc_url($all_images[0]['large']); ?>" alt="<?php the_title_attribute(); ?>">
                    <button type="button" class="gallery-expand-btn" aria-label="نمایش تمام‌صفحه">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
                    </button>
                    <?php if (count($all_images) > 1) : ?>
                        <span class="gallery-count-badge"><?php echo count($all_images); ?> تصویر</span>
                    <?php endif; ?>
                </div>
                <?php if (count($all_images) > 1) : ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($all_images as $i => $img) : ?>
                            <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>" data-full="<?php echo esc_url($img['full']); ?>" data-large="<?php echo esc_url($img['large']); ?>">
                                <img src="<?php echo esc_url($img['large']); ?>" alt="">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="gallery-main gallery-placeholder">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>بدون تصویر</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lightbox (hidden by default, toggled via JS) -->
        <div class="moaveze-lightbox" id="moaveze-lightbox">
            <button class="lightbox-close" aria-label="بستن">&times;</button>
            <button class="lightbox-nav lightbox-prev" aria-label="قبلی">&#8249;</button>
            <img src="" alt="" id="lightbox-image">
            <button class="lightbox-nav lightbox-next" aria-label="بعدی">&#8250;</button>
            <span class="lightbox-counter" id="lightbox-counter"></span>
        </div>


        <div class="moaveze-single-body">
            <!-- ============ MAIN CONTENT ============ -->
            <div class="single-main">

                <!-- Property Specs -->
                <div class="moaveze-section-card">
                    <h3 class="section-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        مشخصات ملک
                    </h3>
                    <div class="specs-grid-v2">
                        <?php if ($type_terms) : ?>
                            <div class="spec-item-v2">
                                <span class="spec-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                                <span class="spec-value"><?php echo esc_html($type_terms[0]->name); ?></span>
                                <span class="spec-label">نوع ملک</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($area) : ?>
                            <div class="spec-item-v2">
                                <span class="spec-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg></span>
                                <span class="spec-value"><?php echo number_format($area); ?> متر</span>
                                <span class="spec-label">متراژ</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($rooms) : ?>
                            <div class="spec-item-v2">
                                <span class="spec-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg></span>
                                <span class="spec-value"><?php echo esc_html($rooms); ?></span>
                                <span class="spec-label">اتاق خواب</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($floor) : ?>
                            <div class="spec-item-v2">
                                <span class="spec-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></span>
                                <span class="spec-value"><?php echo esc_html($floor); ?> از <?php echo esc_html($total_floors); ?></span>
                                <span class="spec-label">طبقه</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($year_built) : ?>
                            <div class="spec-item-v2">
                                <span class="spec-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                                <span class="spec-value"><?php echo esc_html($year_built); ?></span>
                                <span class="spec-label">سال ساخت</span>
                            </div>
                        <?php endif; ?>
                        <div class="spec-item-v2 spec-item-highlight">
                            <span class="spec-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></span>
                            <span class="spec-value"><?php echo $area ? esc_html(Moaveze_Helpers::short_price(round($value / $area), false)) : '—'; ?></span>
                            <span class="spec-label">تومان هر متر</span>
                        </div>
                    </div>
                </div>


                <!-- Expert/AI Valuation ("قیمت کارشناسی") - always a SEPARATE
                     card from the owner's own price above, never a
                     replacement for it. -->
                <?php if ($expert_value) : ?>
                    <div class="moaveze-section-card moaveze-expert-valuation-card">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l9 4.5v9L12 20l-9-4.5v-9z"/><path d="M12 8v8M8 10l4-2 4 2"/></svg>
                            قیمت کارشناسی تبریز هوم
                        </h3>
                        <div class="expert-valuation-box">
                            <div class="expert-valuation-value">
                                <?php echo esc_html(Moaveze_Helpers::short_price($expert_value)); ?>
                                <span class="expert-valuation-badge">
                                    <?php echo $expert_value_type === 'ai' ? '🤖 پیشنهاد هوش مصنوعی، تأییدشده توسط مشاور' : '👤 نظر کارشناسی مشاور'; ?>
                                </span>
                            </div>
                            <?php if ($expert_min && $expert_max) : ?>
                                <div class="expert-valuation-range">
                                    محدوده منطقی: <?php echo esc_html(Moaveze_Helpers::short_price($expert_min)); ?> تا <?php echo esc_html(Moaveze_Helpers::short_price($expert_max)); ?>
                                </div>
                            <?php endif; ?>
                            <p class="expert-valuation-note">
                                این مقدار نظر کارشناسی تیم تبریز هوم است و جایگزین «ارزش ملک» ثبت‌شده توسط مالک (بالای صفحه) نمی‌شود؛ صرفاً برای مقایسه و کمک به تصمیم‌گیری شما ارائه شده است.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Features -->
                <?php if ($feature_terms && !is_wp_error($feature_terms) && !empty($feature_terms)) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            امکانات و ویژگی‌ها
                        </h3>
                        <div class="features-grid-v2">
                            <?php foreach ($feature_terms as $feature) : ?>
                                <span class="feature-tag-v2">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $feature_icons[$feature->name] ?? '<polyline points="20 6 9 17 4 12"/>'; ?></svg>
                                    <?php echo esc_html($feature->name); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if (get_the_content()) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h7"/></svg>
                            توضیحات مالک
                        </h3>
                        <div class="single-description">
                            <?php the_content(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Video (optional) -->
                <?php $video_url = get_post_meta($post_id, '_moaveze_video_url', true); ?>
                <?php if ($video_url) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            ویدیوی معرفی ملک
                        </h3>
                        <div class="single-video-embed">
                            <a href="<?php echo esc_url($video_url); ?>" target="_blank" rel="noopener" class="video-link-card">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                <span>مشاهده ویدیوی ملک</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Map -->
                <?php if ($lat && $lng) : ?>
                    <div class="moaveze-section-card">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            موقعیت روی نقشه
                        </h3>
                        <div class="single-map" id="single-map" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
                    </div>
                <?php endif; ?>

                <!-- Share -->
                <div class="moaveze-section-card single-share-card">
                    <h3 class="section-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        اشتراک‌گذاری این آگهی
                    </h3>
                    <div class="share-buttons">
                        <button class="share-btn share-copy" id="share-copy-link" data-url="<?php echo esc_url(get_permalink()); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            کپی لینک
                        </button>
                        <a class="share-btn share-telegram" href="https://t.me/share/url?url=<?php echo rawurlencode(get_permalink()); ?>&text=<?php echo rawurlencode(get_the_title()); ?>" target="_blank" rel="noopener">
                            تلگرام
                        </a>
                        <a class="share-btn share-whatsapp" href="https://wa.me/?text=<?php echo rawurlencode(get_the_title() . ' ' . get_permalink()); ?>" target="_blank" rel="noopener">
                            واتساپ
                        </a>
                    </div>
                </div>
            </div>


            <!-- ============ SIDEBAR ============ -->
            <div class="single-sidebar">
                <div class="single-sidebar-sticky">

                    <!-- Exchange Conditions Card -->
                    <div class="moaveze-section-card moaveze-exchange-card-v2">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/>
                                <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/>
                            </svg>
                            شرایط معاوضه
                        </h3>

                        <div class="exchange-type-highlight">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><?php echo $exchange_icons[$exchange_type] ?? ''; ?></svg>
                            <span><?php echo esc_html($exchange_labels[$exchange_type] ?? '—'); ?></span>
                        </div>

                        <div class="exchange-conditions-v2">
                            <?php if ($desired_type) : ?>
                                <div class="condition-row">
                                    <span class="cond-icon">🏠</span>
                                    <div class="cond-text">
                                        <span class="cond-label">ملک مورد نظر</span>
                                        <span class="cond-value"><?php echo esc_html($desired_type); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($desired_min || $desired_max) : ?>
                                <div class="condition-row">
                                    <span class="cond-icon">💰</span>
                                    <div class="cond-text">
                                        <span class="cond-label">محدوده ارزش مورد نظر</span>
                                        <span class="cond-value">
                                            <?php if ($desired_min) echo esc_html(Moaveze_Helpers::short_price($desired_min, false)); ?>
                                            <?php if ($desired_min && $desired_max) echo ' تا '; ?>
                                            <?php if ($desired_max) echo esc_html(Moaveze_Helpers::short_price($desired_max, false)); ?>
                                            تومان
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($cash_diff) : ?>
                                <div class="condition-row condition-row-highlight">
                                    <span class="cond-icon"><?php echo $cash_dir === 'give' ? '⬅️' : '➡️'; ?></span>
                                    <div class="cond-text">
                                        <span class="cond-label">مابه‌التفاوت نقدی</span>
                                        <span class="cond-value">
                                            <?php echo esc_html(Moaveze_Helpers::short_price($cash_diff)); ?>
                                            <em>(<?php echo $cash_dir === 'give' ? 'مالک می‌دهد' : 'مالک می‌گیرد'; ?>)</em>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($additional) : ?>
                                <div class="condition-row">
                                    <span class="cond-icon">🚗</span>
                                    <div class="cond-text">
                                        <span class="cond-label">دارایی اضافی پیشنهادی</span>
                                        <span class="cond-value"><?php echo esc_html($additional); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($alt_conditions) && is_array($alt_conditions)) : ?>
                            <div class="alt-conditions-v2">
                                <h4>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                    شرایط جایگزین پیشنهادی
                                </h4>
                                <?php foreach ($alt_conditions as $i => $cond) : ?>
                                    <div class="alt-condition-item-v2">
                                        <span class="alt-number-v2"><?php echo $i + 1; ?></span>
                                        <div>
                                            <p><?php echo esc_html($cond['description']); ?></p>
                                            <?php if (!empty($cond['value'])) : ?>
                                                <strong><?php echo number_format($cond['value']); ?> تومان</strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>


                    <!-- Offer CTA -->
                    <div class="moaveze-section-card offer-cta-card">
                        <button class="moaveze-btn moaveze-btn-primary moaveze-btn-full moaveze-btn-lg" id="send-offer-btn" data-exchange-id="<?php echo esc_attr($post_id); ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            ارسال پیشنهاد معاوضه
                        </button>
                        <div class="offer-note">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            پیشنهاد شما بدون نمایش اطلاعات تماس شما ارسال می‌شود
                        </div>
                        <?php if (get_option('moaveze_addon_favorites', 'yes') === 'yes') : ?>
                            <button type="button" class="moaveze-btn moaveze-btn-secondary moaveze-btn-full moaveze-favorite-btn moaveze-favorite-btn-inline" data-id="<?php echo esc_attr($post_id); ?>" style="margin-top:10px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                                </svg>
                                <span class="fav-btn-label">افزودن به علاقه‌مندی‌ها</span>
                            </button>
                        <?php endif; ?>

                        <?php if (get_option('moaveze_addon_report_listing') === 'yes') : ?>
                            <button type="button" class="moaveze-btn moaveze-btn-ghost moaveze-btn-full moaveze-report-btn" data-id="<?php echo esc_attr($post_id); ?>" style="margin-top:8px;color:#ef4444;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                                گزارش این آگهی
                            </button>
                        <?php endif; ?>

                        <?php if (get_option('moaveze_addon_qr_code') === 'yes') : ?>
                            <button type="button" class="moaveze-btn moaveze-btn-ghost moaveze-btn-full moaveze-qr-btn" data-url="<?php echo esc_url(get_permalink()); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>" style="margin-top:8px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                دریافت QR Code آگهی
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Meta info -->
                    <div class="single-meta-footer">
                        <span class="meta-footer-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <?php echo number_format($views + 1); ?> بازدید
                        </span>
                        <span class="meta-footer-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            ثبت شده در <?php echo esc_html(Moaveze_Helpers::jalali_date(get_the_time('U'), 'j F Y')); ?>
                        </span>
                        <span class="meta-footer-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                            کد آگهی: #<?php echo esc_html($post_id); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
