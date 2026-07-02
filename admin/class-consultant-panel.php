<?php
/**
 * Consultant Panel
 * Manages consultant role and capabilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Consultant_Panel {

    public function __construct() {
        add_action('init', array($this, 'register_consultant_role'));
        add_action('wp_ajax_moaveze_verify_exchange', array($this, 'ajax_verify_exchange'));
        add_action('wp_ajax_moaveze_reject_exchange', array($this, 'ajax_reject_exchange'));
        add_action('wp_ajax_moaveze_assign_consultant', array($this, 'ajax_assign_consultant'));
    }

    /**
     * Register consultant role
     */
    public function register_consultant_role() {
        if (!get_role('moaveze_consultant')) {
            add_role('moaveze_consultant', 'مشاور معاوضه', array(
                'read'                    => true,
                'edit_posts'              => true,
                'edit_published_posts'    => true,
                'upload_files'            => true,
                'moaveze_view_contacts'   => true,
                'moaveze_verify_listings' => true,
                'moaveze_manage_offers'   => true,
                'moaveze_view_matches'    => true,
            ));
        }
    }

    /**
     * Verify an exchange listing
     */
    public function ajax_verify_exchange() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('moaveze_verify_listings') && !current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $post_id = intval($_POST['post_id']);
        $current_user = get_current_user_id();

        update_post_meta($post_id, '_moaveze_verified', '1');
        update_post_meta($post_id, '_moaveze_verified_by', $current_user);
        update_post_meta($post_id, '_moaveze_verified_at', current_time('mysql'));

        // Update status to published if pending
        if (get_post_status($post_id) === 'pending') {
            wp_update_post(array(
                'ID'          => $post_id,
                'post_status' => 'publish',
            ));
        }

        wp_send_json_success(array('message' => 'آگهی تأیید شد'));
    }

    /**
     * Reject an exchange listing
     */
    public function ajax_reject_exchange() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('moaveze_verify_listings') && !current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $post_id = intval($_POST['post_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');

        update_post_meta($post_id, '_moaveze_rejected', '1');
        update_post_meta($post_id, '_moaveze_reject_reason', $reason);

        wp_update_post(array(
            'ID'          => $post_id,
            'post_status' => 'draft',
        ));

        wp_send_json_success(array('message' => 'آگهی رد شد'));
    }

    /**
     * Assign consultant to a match
     */
    public function ajax_assign_consultant() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        global $wpdb;

        $match_id = intval($_POST['match_id']);
        $consultant_id = intval($_POST['consultant_id']);

        $wpdb->update(
            $wpdb->prefix . 'moaveze_matches',
            array(
                'consultant_id' => $consultant_id,
                'status'        => 'assigned',
            ),
            array('id' => $match_id),
            array('%d', '%s'),
            array('%d')
        );

        wp_send_json_success(array('message' => 'مشاور اختصاص یافت'));
    }
}

new Moaveze_Consultant_Panel();
