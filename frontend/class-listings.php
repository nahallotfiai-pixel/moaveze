<?php
/**
 * Frontend Listings Display
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Listings {

    public function __construct() {
        add_shortcode('moaveze_listings', array($this, 'render_listings'));
        add_shortcode('moaveze_single', array($this, 'render_single'));
        add_filter('single_template', array($this, 'single_template'));
        add_filter('archive_template', array($this, 'archive_template'));
    }

    /**
     * Single template override
     */
    public function single_template($template) {
        if (get_post_type() === 'moaveze_exchange') {
            $custom = MOAVEZE_PLUS_PATH . 'templates/single-exchange.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        return $template;
    }

    /**
     * Archive template override
     */
    public function archive_template($template) {
        if (is_post_type_archive('moaveze_exchange')) {
            $custom = MOAVEZE_PLUS_PATH . 'templates/archive-exchange.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        return $template;
    }

    /**
     * Render listings grid shortcode
     */
    public function render_listings($atts) {
        $atts = shortcode_atts(array(
            'per_page'      => get_option('moaveze_listings_per_page', 12),
            'type'          => '',
            'district'      => '',
            'min_value'     => '',
            'max_value'     => '',
            'style'         => get_option('moaveze_cards_style', 'modern'),
            'show_map'      => 'yes',
            'show_filters'  => 'yes',
        ), $atts);

        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        $args = array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $atts['per_page'],
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Tax queries
        $tax_query = array();
        if (!empty($atts['type'])) {
            $tax_query[] = array(
                'taxonomy' => 'moaveze_property_type',
                'field'    => 'slug',
                'terms'    => $atts['type'],
            );
        }
        if (!empty($atts['district'])) {
            $tax_query[] = array(
                'taxonomy' => 'moaveze_district',
                'field'    => 'slug',
                'terms'    => $atts['district'],
            );
        }
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        // Meta queries for value range
        $meta_query = array();
        if (!empty($atts['min_value'])) {
            $meta_query[] = array(
                'key'     => '_moaveze_property_value',
                'value'   => absint($atts['min_value']),
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }
        if (!empty($atts['max_value'])) {
            $meta_query[] = array(
                'key'     => '_moaveze_property_value',
                'value'   => absint($atts['max_value']),
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query($args);

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <?php if ($atts['show_filters'] === 'yes') : ?>
                <?php $this->render_filters(); ?>
            <?php endif; ?>

            <div class="moaveze-listings-layout">
                <?php if ($atts['show_map'] === 'yes') : ?>
                    <div class="moaveze-listings-map" id="listings-map"></div>
                <?php endif; ?>

                <div class="moaveze-listings-grid moaveze-style-<?php echo esc_attr($atts['style']); ?>">
                    <?php if ($query->have_posts()) : ?>
                        <?php while ($query->have_posts()) : $query->the_post(); ?>
                            <?php $this->render_card(get_the_ID(), $atts['style']); ?>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="moaveze-empty-state">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <h3>آگهی معاوضه‌ای یافت نشد</h3>
                            <p>با تغییر فیلترها یا ثبت آگهی جدید شروع کنید.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($query->max_num_pages > 1) : ?>
                <div class="moaveze-pagination">
                    <?php
                    echo paginate_links(array(
                        'total'   => $query->max_num_pages,
                        'current' => $paged,
                        'prev_text' => '&rarr;',
                        'next_text' => '&larr;',
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }


    /**
     * Render filters section
     */
    private function render_filters() {
        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false));
        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false));
        ?>
        <div class="moaveze-filters-bar">
            <div class="moaveze-filter-group">
                <select id="filter-type" class="moaveze-filter-select">
                    <option value="">نوع ملک</option>
                    <?php if (!is_wp_error($types)) : foreach ($types as $type) : ?>
                        <option value="<?php echo esc_attr($type->slug); ?>"><?php echo esc_html($type->name); ?></option>
                    <?php endforeach; endif; ?>
                </select>

                <select id="filter-district" class="moaveze-filter-select">
                    <option value="">منطقه</option>
                    <?php if (!is_wp_error($districts)) : foreach ($districts as $d) : ?>
                        <option value="<?php echo esc_attr($d->slug); ?>"><?php echo esc_html($d->name); ?></option>
                    <?php endforeach; endif; ?>
                </select>

                <select id="filter-exchange-type" class="moaveze-filter-select">
                    <option value="">نوع معاوضه</option>
                    <option value="property_only">ملک با ملک</option>
                    <option value="property_cash">ملک + نقد</option>
                    <option value="property_car">ملک + خودرو</option>
                    <option value="property_mixed">ترکیبی</option>
                    <option value="flexible">انعطاف‌پذیر</option>
                </select>

                <div class="moaveze-price-range">
                    <input type="text" id="filter-min-price" placeholder="حداقل قیمت" class="moaveze-price-input">
                    <span class="range-separator">تا</span>
                    <input type="text" id="filter-max-price" placeholder="حداکثر قیمت" class="moaveze-price-input">
                </div>
            </div>

            <div class="moaveze-filter-actions">
                <button id="apply-filters" class="moaveze-btn moaveze-btn-primary moaveze-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    جستجو
                </button>
                <button id="reset-filters" class="moaveze-btn moaveze-btn-ghost moaveze-btn-sm">پاک کردن</button>
                <button id="toggle-map-view" class="moaveze-btn moaveze-btn-outline moaveze-btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/></svg>
                    نقشه
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render a single listing card
     */
    private function render_card($post_id, $style = 'modern') {
        $value = get_post_meta($post_id, '_moaveze_property_value', true);
        $area = get_post_meta($post_id, '_moaveze_area_sqm', true);
        $rooms = get_post_meta($post_id, '_moaveze_rooms', true);
        $exchange_type = get_post_meta($post_id, '_moaveze_exchange_type', true);
        $verified = get_post_meta($post_id, '_moaveze_verified', true);
        $featured = get_post_meta($post_id, '_moaveze_featured', true);
        $lat = get_post_meta($post_id, '_moaveze_latitude', true);
        $lng = get_post_meta($post_id, '_moaveze_longitude', true);

        $type_terms = get_the_terms($post_id, 'moaveze_property_type');
        $district_terms = get_the_terms($post_id, 'moaveze_district');

        $exchange_labels = array(
            'property_only'  => 'ملک با ملک',
            'property_cash'  => 'ملک + نقد',
            'property_car'   => 'ملک + خودرو',
            'property_mixed' => 'ترکیبی',
            'flexible'       => 'انعطاف‌پذیر',
        );
        ?>
        <div class="moaveze-card moaveze-card-<?php echo esc_attr($style); ?>"
             data-lat="<?php echo esc_attr($lat); ?>"
             data-lng="<?php echo esc_attr($lng); ?>"
             data-id="<?php echo esc_attr($post_id); ?>">

            <?php if ($featured) : ?>
                <span class="moaveze-badge-featured">ویژه</span>
            <?php endif; ?>

            <?php if ($verified) : ?>
                <span class="moaveze-badge-verified">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    تأیید شده
                </span>
            <?php endif; ?>

            <button type="button" class="moaveze-favorite-btn" data-id="<?php echo esc_attr($post_id); ?>" aria-label="افزودن به علاقه‌مندی‌ها">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                </svg>
            </button>

            <div class="moaveze-card-image">
                <?php if (has_post_thumbnail($post_id)) : ?>
                    <?php echo get_the_post_thumbnail($post_id, 'medium_large', array('loading' => 'lazy')); ?>
                <?php else : ?>
                    <div class="moaveze-card-placeholder">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <div class="moaveze-card-overlay">
                    <span class="exchange-type-badge">
                        <?php echo esc_html($exchange_labels[$exchange_type] ?? 'معاوضه'); ?>
                    </span>
                </div>
            </div>

            <div class="moaveze-card-body">
                <h3 class="moaveze-card-title">
                    <a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?></a>
                </h3>

                <div class="moaveze-card-meta">
                    <?php if ($district_terms) : ?>
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html($district_terms[0]->name); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($type_terms) : ?>
                        <span class="meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                            <?php echo esc_html($type_terms[0]->name); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="moaveze-card-specs">
                    <?php if ($area) : ?>
                        <span class="spec-item">
                            <strong><?php echo esc_html(Moaveze_Helpers::to_persian_digits(number_format($area))); ?></strong>
                            <small>متر</small>
                        </span>
                    <?php endif; ?>
                    <?php if ($rooms) : ?>
                        <span class="spec-item">
                            <strong><?php echo esc_html(Moaveze_Helpers::to_persian_digits($rooms)); ?></strong>
                            <small>اتاق</small>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="moaveze-card-date">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo esc_html(Moaveze_Helpers::jalali_date(get_the_date('U', $post_id), 'Y/m/d')); ?>
                </div>

                <div class="moaveze-card-footer">
                    <span class="moaveze-card-price" title="<?php echo esc_attr(number_format($value) . ' تومان'); ?>">
                        <?php echo esc_html(Moaveze_Helpers::short_price($value)); ?>
                    </span>
                    <a href="<?php echo get_permalink($post_id); ?>" class="moaveze-btn moaveze-btn-sm moaveze-btn-outline">
                        جزئیات
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render single listing shortcode
     */
    public function render_single($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $post_id = $atts['id'] ?: get_the_ID();

        if (!$post_id || get_post_type($post_id) !== 'moaveze_exchange') {
            return '<p>آگهی یافت نشد.</p>';
        }

        ob_start();
        include MOAVEZE_PLUS_PATH . 'templates/single-exchange.php';
        return ob_get_clean();
    }
}

new Moaveze_Listings();
