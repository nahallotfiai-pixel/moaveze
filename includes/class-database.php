<?php
/**
 * Database Handler
 * Creates and manages custom database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Database {

    /**
     * Create all custom tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Exchange requests table
        $table_exchanges = $wpdb->prefix . 'moaveze_exchanges';
        $sql_exchanges = "CREATE TABLE $table_exchanges (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            property_type varchar(50) NOT NULL,
            property_value bigint(20) NOT NULL DEFAULT 0,
            area_sqm int(11) NOT NULL DEFAULT 0,
            rooms int(5) DEFAULT NULL,
            district varchar(100) DEFAULT NULL,
            address text DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            exchange_type varchar(50) NOT NULL DEFAULT 'property',
            exchange_conditions longtext DEFAULT NULL,
            desired_property_type varchar(50) DEFAULT NULL,
            desired_min_value bigint(20) DEFAULT NULL,
            desired_max_value bigint(20) DEFAULT NULL,
            desired_districts text DEFAULT NULL,
            cash_difference bigint(20) DEFAULT 0,
            cash_direction varchar(10) DEFAULT 'give',
            additional_assets text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            visibility varchar(20) NOT NULL DEFAULT 'public',
            reciprocal_of_offer_id bigint(20) unsigned DEFAULT NULL,
            verified tinyint(1) NOT NULL DEFAULT 0,
            verified_by bigint(20) unsigned DEFAULT NULL,
            verified_at datetime DEFAULT NULL,
            featured tinyint(1) NOT NULL DEFAULT 0,
            boost_until datetime DEFAULT NULL,
            views_count int(11) NOT NULL DEFAULT 0,
            contact_name varchar(100) DEFAULT NULL,
            contact_phone varchar(20) DEFAULT NULL,
            contact_email varchar(100) DEFAULT NULL,
            monetization_mode varchar(30) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY property_type (property_type),
            KEY property_value (property_value),
            KEY district (district),
            KEY verified (verified),
            KEY featured (featured),
            KEY visibility (visibility)
        ) $charset_collate;";

        dbDelta($sql_exchanges);

        // Offers table
        $table_offers = $wpdb->prefix . 'moaveze_offers';
        $sql_offers = "CREATE TABLE $table_offers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) unsigned NOT NULL,
            from_user_id bigint(20) unsigned NOT NULL,
            from_exchange_id bigint(20) unsigned DEFAULT NULL,
            offer_type varchar(30) NOT NULL DEFAULT 'direct',
            offer_details longtext DEFAULT NULL,
            cash_offered bigint(20) DEFAULT 0,
            assets_offered text DEFAULT NULL,
            message text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            admin_notes text DEFAULT NULL,
            responded_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY from_user_id (from_user_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_offers);

        // Matches table
        $table_matches = $wpdb->prefix . 'moaveze_matches';
        $sql_matches = "CREATE TABLE $table_matches (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            exchange_id_a bigint(20) unsigned NOT NULL,
            exchange_id_b bigint(20) unsigned NOT NULL,
            match_score decimal(5,2) NOT NULL DEFAULT 0.00,
            match_type varchar(30) NOT NULL DEFAULT 'direct',
            match_details longtext DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'new',
            notified tinyint(1) NOT NULL DEFAULT 0,
            consultant_id bigint(20) unsigned DEFAULT NULL,
            consultant_notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_match (exchange_id_a, exchange_id_b),
            KEY match_score (match_score),
            KEY status (status),
            KEY consultant_id (consultant_id)
        ) $charset_collate;";

        dbDelta($sql_matches);

        // Chain swaps table
        $table_chains = $wpdb->prefix . 'moaveze_chains';
        $sql_chains = "CREATE TABLE $table_chains (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            chain_hash varchar(64) NOT NULL,
            exchange_ids longtext NOT NULL,
            chain_length int(5) NOT NULL DEFAULT 0,
            total_value bigint(20) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'detected',
            confirmed_by_all tinyint(1) NOT NULL DEFAULT 0,
            consultant_id bigint(20) unsigned DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY chain_hash (chain_hash),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_chains);

        // Contact views (monetization tracking)
        $table_contact_views = $wpdb->prefix . 'moaveze_contact_views';
        $sql_contact_views = "CREATE TABLE $table_contact_views (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            view_type varchar(20) NOT NULL DEFAULT 'contact',
            payment_amount bigint(20) DEFAULT 0,
            payment_method varchar(30) DEFAULT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        dbDelta($sql_contact_views);

        // Subscriptions table
        $table_subscriptions = $wpdb->prefix . 'moaveze_subscriptions';
        $sql_subscriptions = "CREATE TABLE $table_subscriptions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            plan_type varchar(30) NOT NULL DEFAULT 'basic',
            starts_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            payment_amount bigint(20) DEFAULT 0,
            payment_method varchar(30) DEFAULT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            auto_renew tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        dbDelta($sql_subscriptions);

        // Wishlist table
        $table_wishlist = $wpdb->prefix . 'moaveze_wishlist';
        $sql_wishlist = "CREATE TABLE $table_wishlist (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            desired_property_type varchar(50) DEFAULT NULL,
            desired_min_value bigint(20) DEFAULT NULL,
            desired_max_value bigint(20) DEFAULT NULL,
            desired_districts text DEFAULT NULL,
            desired_min_area int(11) DEFAULT NULL,
            desired_max_area int(11) DEFAULT NULL,
            desired_rooms int(5) DEFAULT NULL,
            additional_criteria longtext DEFAULT NULL,
            notify_on_match tinyint(1) NOT NULL DEFAULT 1,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_wishlist);

        // Notifications log
        $table_notifications = $wpdb->prefix . 'moaveze_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            type varchar(30) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            data longtext DEFAULT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read)
        ) $charset_collate;";

        dbDelta($sql_notifications);

        // Valuations table (manual + AI-assisted property valuations,
        // restricted to admin/consultant use only - see
        // class-ai-valuation.php)
        $table_valuations = $wpdb->prefix . 'moaveze_valuations';
        $sql_valuations = "CREATE TABLE $table_valuations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'manual',
            ai_provider varchar(30) DEFAULT NULL,
            suggested_value bigint(20) DEFAULT NULL,
            suggested_min bigint(20) DEFAULT NULL,
            suggested_max bigint(20) DEFAULT NULL,
            confidence varchar(20) DEFAULT NULL,
            reasoning longtext DEFAULT NULL,
            comparables longtext DEFAULT NULL,
            raw_response longtext DEFAULT NULL,
            manual_value bigint(20) DEFAULT NULL,
            manual_notes text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";

        dbDelta($sql_valuations);
    }

    /**
     * Run any pending schema upgrades on already-active installs.
     *
     * dbDelta() only ADDS new columns/tables defined in create_tables();
     * it does not run automatically after the initial activation. This
     * hook is fired on 'plugins_loaded' (see moaveze-plus.php) and checks
     * the stored 'moaveze_plus_db_version' option, re-running
     * create_tables() (which is safe/idempotent via dbDelta) whenever the
     * plugin has been updated with new columns - e.g. the 'visibility'
     * and 'reciprocal_of_offer_id' columns added for the "register your
     * own property while sending an offer" feature.
     */
    public static function maybe_upgrade() {
        $installed_version = get_option('moaveze_plus_db_version', '');

        if ($installed_version === MOAVEZE_PLUS_DB_VERSION) {
            return;
        }

        self::create_tables();
        update_option('moaveze_plus_db_version', MOAVEZE_PLUS_DB_VERSION);
    }

    /**
     * Drop all tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            'moaveze_exchanges',
            'moaveze_offers',
            'moaveze_matches',
            'moaveze_chains',
            'moaveze_contact_views',
            'moaveze_subscriptions',
            'moaveze_wishlist',
            'moaveze_notifications',
            'moaveze_valuations',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
    }
}
