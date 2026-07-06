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
        add_action('wp_ajax_moaveze_add_consultant_role', array($this, 'ajax_add_consultant_role'));
        add_action('wp_ajax_moaveze_remove_consultant_role', array($this, 'ajax_remove_consultant_role'));
        add_action('wp_ajax_moaveze_search_users', array($this, 'ajax_search_users'));
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

    /**
     * AJAX: Add consultant role to a user
     */
    public function ajax_add_consultant_role() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $user_id = absint($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error('کاربر انتخاب نشده');
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('کاربر یافت نشد');
        }

        $user->add_role('moaveze_consultant');
        wp_send_json_success(array('message' => 'کاربر به مشاوران اضافه شد'));
    }

    /**
     * AJAX: Remove consultant role from a user
     */
    public function ajax_remove_consultant_role() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $user_id = absint($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error('کاربر انتخاب نشده');
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error('کاربر یافت نشد');
        }

        $user->remove_role('moaveze_consultant');
        wp_send_json_success(array('message' => 'نقش مشاور حذف شد'));
    }

    /**
     * AJAX: Search users for the consultant add form
     * Returns users grouped by their WordPress role
     */
    public function ajax_search_users() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی ندارید');
        }

        $search = sanitize_text_field($_POST['search'] ?? '');

        $args = array(
            'role__not_in' => array('moaveze_consultant'),
            'number'       => 30,
            'orderby'      => 'display_name',
            'order'        => 'ASC',
        );

        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name', 'user_nicename');
        }

        $users = get_users($args);

        $role_labels = array(
            'administrator' => 'مدیر',
            'editor'        => 'ویرایشگر',
            'author'        => 'نویسنده',
            'subscriber'    => 'مشترک',
            'contributor'   => 'مشارکت‌کننده',
            'houzez_agent'  => 'مشاور Houzez',
            'houzez_agency' => 'آژانس Houzez',
            'houzez_owner'  => 'مالک Houzez',
            'customer'      => 'مشتری',
        );

        $grouped = array();
        foreach ($users as $user) {
            $roles = (array) $user->roles;
            $primary_role = !empty($roles) ? $roles[0] : 'subscriber';
            $role_label = $role_labels[$primary_role] ?? $primary_role;

            $grouped[] = array(
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
                'phone'        => get_user_meta($user->ID, 'phone', true) ?: '',
                'role'         => $primary_role,
                'role_label'   => $role_label,
                'avatar'       => get_avatar_url($user->ID, array('size' => 40)),
            );
        }

        wp_send_json_success(array('users' => $grouped));
    }
}

new Moaveze_Consultant_Panel();
