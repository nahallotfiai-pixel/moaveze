<?php
/**
 * Archive Exchange Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="moaveze-wrapper">
    <div class="moaveze-archive-container">
        <div class="moaveze-archive-header">
            <h1 class="moaveze-archive-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/>
                    <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/>
                </svg>
                معاوضه ملک در تبریز
            </h1>
            <p class="moaveze-archive-subtitle">ملک خود را معاوضه کنید و بدون واسطه به خانه رویایی‌تان برسید</p>
            <a href="<?php echo esc_url(Moaveze_Pages::get_submit_url()); ?>" class="moaveze-btn moaveze-btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                ثبت آگهی معاوضه
            </a>
        </div>

        <?php echo do_shortcode('[moaveze_listings show_map="yes" show_filters="yes"]'); ?>
    </div>
</div>

<?php get_footer(); ?>
