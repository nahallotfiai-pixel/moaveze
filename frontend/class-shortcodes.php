<?php
/**
 * Additional Shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Shortcodes {

    public function __construct() {
        add_shortcode('moaveze_featured', array($this, 'render_featured'));
        add_shortcode('moaveze_recent', array($this, 'render_recent'));
        add_shortcode('moaveze_stats', array($this, 'render_stats'));
        add_shortcode('moaveze_search', array($this, 'render_search_widget'));
    }

    /**
     * Featured exchanges slider
     */
    public function render_featured($atts) {
        $atts = shortcode_atts(array('count' => 6), $atts);

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $atts['count'],
            'meta_key'       => '_moaveze_featured',
            'meta_value'     => '1',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-featured-slider" id="featured-slider">
                <?php while ($query->have_posts()) : $query->the_post();
                    $id = get_the_ID();
                    $value = get_post_meta($id, '_moaveze_property_value', true);
                    $area = get_post_meta($id, '_moaveze_area_sqm', true);
                    $district_terms = get_the_terms($id, 'moaveze_district');
                ?>
                    <div class="moaveze-featured-card">
                        <div class="featured-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('large'); ?>
                            <?php endif; ?>
                            <div class="featured-overlay">
                                <span class="featured-badge">ویژه</span>
                            </div>
                        </div>
                        <div class="featured-content">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <div class="featured-meta">
                                <?php if ($district_terms) : ?>
                                    <span><?php echo esc_html($district_terms[0]->name); ?></span>
                                <?php endif; ?>
                                <span><?php echo esc_html(Moaveze_Helpers::to_persian_digits(number_format($area))); ?> متر</span>
                            </div>
                            <div class="featured-price"><?php echo esc_html(Moaveze_Helpers::short_price($value)); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Recent exchanges
     */
    public function render_recent($atts) {
        $atts = shortcode_atts(array('count' => 4), $atts);

        $query = new WP_Query(array(
            'post_type'      => 'moaveze_exchange',
            'post_status'    => 'publish',
            'posts_per_page' => $atts['count'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-recent-grid">
                <?php while ($query->have_posts()) : $query->the_post();
                    $id = get_the_ID();
                    $value = get_post_meta($id, '_moaveze_property_value', true);
                    $district_terms = get_the_terms($id, 'moaveze_district');
                ?>
                    <div class="moaveze-recent-item">
                        <div class="recent-thumb">
                            <?php if (has_post_thumbnail()) : the_post_thumbnail('thumbnail'); endif; ?>
                        </div>
                        <div class="recent-info">
                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                            <span class="recent-price"><?php echo esc_html(Moaveze_Helpers::short_price($value)); ?></span>
                            <?php if ($district_terms) : ?>
                                <span class="recent-district"><?php echo esc_html($district_terms[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Public stats counter
     */
    public function render_stats() {
        $total = wp_count_posts('moaveze_exchange')->publish;
        global $wpdb;
        $matches = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}moaveze_matches WHERE status = 'completed'") ?: 0;

        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-public-stats">
                <div class="stat-item">
                    <span class="stat-number" data-count="<?php echo esc_attr($total); ?>">0</span>
                    <span class="stat-label">آگهی فعال</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="<?php echo esc_attr($matches); ?>">0</span>
                    <span class="stat-label">معاوضه موفق</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="<?php echo esc_attr($total * 2); ?>">0</span>
                    <span class="stat-label">کاربر فعال</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Search widget ([moaveze_search] - a separate, standalone search
     * box, distinct from the filter bar on the listings page itself).
     *
     * BUG FIX (part of the full search overhaul): this form submitted
     * completely different field names (property_type, district,
     * min_value, max_value) than what Moaveze_Listings::render_listings()
     * actually reads ($_GET['moaveze_type'], ['moaveze_district'],
     * ['moaveze_min_value'], ['moaveze_max_value']) - so this widget's
     * search never had any effect on the listings page at all, no
     * matter what was selected. Field names now match exactly.
     *
     * Also switched <select> values from term SLUG to term ID, for the
     * same reason documented in detail in
     * Moaveze_Listings::build_listings_query(): even though a plain
     * HTML GET form submit (unlike the AJAX filter bar) doesn't hit
     * jQuery's double-encoding bug, using term IDs everywhere keeps
     * every entry point into the listings query consistent and equally
     * immune to any future encoding edge case.
     */
    public function render_search_widget() {
        ob_start();
        ?>
        <div class="moaveze-wrapper">
            <div class="moaveze-search-widget">
                <h3>جستجوی معاوضه</h3>
                <form class="moaveze-search-form" action="<?php echo esc_url(Moaveze_Pages::get_listings_url()); ?>" method="get">
                    <select name="moaveze_type">
                        <option value="">نوع ملک</option>
                        <?php
                        $types = get_terms(array('taxonomy' => 'moaveze_property_type', 'hide_empty' => false));
                        if (!is_wp_error($types)) : foreach ($types as $t) : ?>
                            <option value="<?php echo esc_attr($t->term_id); ?>"><?php echo esc_html($t->name); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <select name="moaveze_district">
                        <option value="">منطقه</option>
                        <?php
                        $districts = get_terms(array('taxonomy' => 'moaveze_district', 'hide_empty' => false));
                        if (!is_wp_error($districts)) : foreach ($districts as $d) : ?>
                            <option value="<?php echo esc_attr($d->term_id); ?>"><?php echo esc_html($d->name); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <input type="text" name="moaveze_min_value" placeholder="حداقل قیمت" class="moaveze-price-input">
                    <input type="text" name="moaveze_max_value" placeholder="حداکثر قیمت" class="moaveze-price-input">
                    <button type="submit" class="moaveze-btn moaveze-btn-primary">جستجو</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Moaveze_Shortcodes();
