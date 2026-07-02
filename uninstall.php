<?php
/**
 * Moaveze Plus Uninstall
 * Removes all plugin data on uninstall
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include database class
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';

// Drop tables
Moaveze_Database::drop_tables();

// Remove options
$options = array(
    'moaveze_plus_version',
    'moaveze_plus_db_version',
    'moaveze_enable_exchange',
    'moaveze_require_login',
    'moaveze_auto_approve',
    'moaveze_listings_per_page',
    'moaveze_map_center_lat',
    'moaveze_map_center_lng',
    'moaveze_map_zoom',
    'moaveze_map_style',
    'moaveze_monetization_mode',
    'moaveze_subscription_price',
    'moaveze_per_contact_price',
    'moaveze_vip_enabled',
    'moaveze_boost_enabled',
    'moaveze_boost_price',
    'moaveze_chat_enabled',
    'moaveze_chat_require_approval',
    'moaveze_chat_auto_close_days',
    'moaveze_email_notifications',
    'moaveze_sms_notifications',
    'moaveze_sms_api_key',
    'moaveze_push_notifications',
    'moaveze_show_in_houzez',
    'moaveze_exchange_badge',
    'moaveze_dark_mode',
    'moaveze_primary_color',
    'moaveze_cards_style',
    'moaveze_hide_contact_info',
    'moaveze_contact_visible_to',
    'moaveze_houzez_sync',
);

foreach ($options as $option) {
    delete_option($option);
}

// Remove custom role
remove_role('moaveze_consultant');

// Remove all posts of our type
$posts = get_posts(array(
    'post_type'      => 'moaveze_exchange',
    'numberposts'    => -1,
    'post_status'    => 'any',
));

foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
}

// Flush rewrite rules
flush_rewrite_rules();
