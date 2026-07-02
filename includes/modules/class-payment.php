<?php
/**
 * Payment Gateway Module
 * Supports ZarinPal and IDPay for Iranian Rial payments
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_Payment {

    private $gateways = array();

    public function __construct() {
        $this->register_gateways();

        // AJAX endpoints
        add_action('wp_ajax_moaveze_create_payment', array($this, 'create_payment'));
        add_action('wp_ajax_moaveze_verify_payment', array($this, 'verify_payment'));

        // Callback URLs
        add_action('init', array($this, 'register_callback_routes'));
        add_action('template_redirect', array($this, 'handle_callback'));

        // Settings registration
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register available gateways
     */
    private function register_gateways() {
        $this->gateways = array(
            'zarinpal' => array(
                'name'       => 'زرین‌پال',
                'class'      => 'Moaveze_Gateway_ZarinPal',
                'icon'       => 'zarinpal.svg',
                'enabled'    => get_option('moaveze_gateway_zarinpal_enabled', 'no'),
                'merchant'   => get_option('moaveze_gateway_zarinpal_merchant', ''),
                'sandbox'    => get_option('moaveze_gateway_zarinpal_sandbox', 'yes'),
            ),
            'idpay' => array(
                'name'       => 'آیدی‌پی',
                'class'      => 'Moaveze_Gateway_IDPay',
                'icon'       => 'idpay.svg',
                'enabled'    => get_option('moaveze_gateway_idpay_enabled', 'no'),
                'api_key'    => get_option('moaveze_gateway_idpay_api_key', ''),
                'sandbox'    => get_option('moaveze_gateway_idpay_sandbox', 'yes'),
            ),
        );
    }

    /**
     * Register payment settings
     */
    public function register_settings() {
        // ZarinPal
        register_setting('moaveze_payment', 'moaveze_gateway_zarinpal_enabled');
        register_setting('moaveze_payment', 'moaveze_gateway_zarinpal_merchant');
        register_setting('moaveze_payment', 'moaveze_gateway_zarinpal_sandbox');

        // IDPay
        register_setting('moaveze_payment', 'moaveze_gateway_idpay_enabled');
        register_setting('moaveze_payment', 'moaveze_gateway_idpay_api_key');
        register_setting('moaveze_payment', 'moaveze_gateway_idpay_sandbox');

        // General
        register_setting('moaveze_payment', 'moaveze_payment_currency');
        register_setting('moaveze_payment', 'moaveze_default_gateway');
    }

    /**
     * Register callback route
     */
    public function register_callback_routes() {
        add_rewrite_rule(
            'moaveze-payment/callback/([^/]+)/?$',
            'index.php?moaveze_payment_callback=1&gateway=$matches[1]',
            'top'
        );
        add_rewrite_tag('%moaveze_payment_callback%', '1');
        add_rewrite_tag('%gateway%', '([^&]+)');
    }

    /**
     * Handle payment callback
     */
    public function handle_callback() {
        if (!get_query_var('moaveze_payment_callback')) return;

        $gateway_id = get_query_var('gateway', '');
        if (empty($gateway_id)) {
            // Fallback: check URL parameter
            if (isset($_GET['gateway'])) {
                $gateway_id = sanitize_text_field($_GET['gateway']);
            }
        }

        // Also check for direct callback URL format
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, 'moaveze-payment/callback/') !== false) {
            preg_match('/callback\/([^\/\?]+)/', $uri, $matches);
            if (!empty($matches[1])) {
                $gateway_id = $matches[1];
            }
        }

        if (!$gateway_id || !isset($this->gateways[$gateway_id])) {
            wp_die('درگاه نامعتبر', 'خطا', array('response' => 400));
        }

        $this->process_callback($gateway_id);
    }


    /**
     * Create a payment via AJAX
     */
    public function create_payment() {
        check_ajax_referer('moaveze_plus_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'برای پرداخت باید وارد شوید'));
        }

        $type = sanitize_text_field($_POST['payment_type'] ?? '');
        $amount = absint($_POST['amount'] ?? 0);
        $reference_id = absint($_POST['reference_id'] ?? 0);
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? get_option('moaveze_default_gateway', 'zarinpal'));

        // Validate payment type
        $valid_types = array('subscription', 'per_contact', 'vip', 'boost');
        if (!in_array($type, $valid_types)) {
            wp_send_json_error(array('message' => 'نوع پرداخت نامعتبر'));
        }

        // Calculate amount based on type
        if (!$amount) {
            switch ($type) {
                case 'subscription':
                    $plan = sanitize_text_field($_POST['plan'] ?? 'monthly');
                    $amount = $this->get_subscription_price($plan);
                    break;
                case 'per_contact':
                    $amount = absint(get_option('moaveze_per_contact_price', 0));
                    break;
                case 'vip':
                    $amount = absint(get_option('moaveze_vip_price', 500000));
                    break;
                case 'boost':
                    $days = absint($_POST['days'] ?? 7);
                    $amount = absint(get_option('moaveze_boost_price', 200000)) * $days;
                    break;
            }
        }

        if ($amount <= 0) {
            wp_send_json_error(array('message' => 'مبلغ پرداخت نامعتبر'));
        }

        // Create transaction record
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_transactions',
            array(
                'user_id'      => get_current_user_id(),
                'amount'       => $amount,
                'type'         => $type,
                'reference_id' => $reference_id,
                'gateway'      => $gateway_id,
                'status'       => 'pending',
                'meta_data'    => wp_json_encode($_POST),
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s')
        );
        $transaction_id = $wpdb->insert_id;

        // Get description
        $descriptions = array(
            'subscription' => 'خرید اشتراک معاوضه پلاس',
            'per_contact'  => 'مشاهده اطلاعات تماس',
            'vip'          => 'آگهی VIP معاوضه',
            'boost'        => 'بوست آگهی معاوضه',
        );
        $description = $descriptions[$type] ?? 'پرداخت معاوضه پلاس';

        // Build callback URL
        $callback_url = home_url("/moaveze-payment/callback/{$gateway_id}/?transaction_id={$transaction_id}");

        // Send to gateway
        $result = $this->send_to_gateway($gateway_id, array(
            'amount'         => $amount,
            'description'    => $description,
            'callback_url'   => $callback_url,
            'transaction_id' => $transaction_id,
            'user_email'     => wp_get_current_user()->user_email,
            'user_phone'     => get_user_meta(get_current_user_id(), 'phone', true),
        ));

        if ($result['success']) {
            wp_send_json_success(array(
                'redirect_url'   => $result['redirect_url'],
                'transaction_id' => $transaction_id,
            ));
        } else {
            // Mark as failed
            $wpdb->update(
                $wpdb->prefix . 'moaveze_transactions',
                array('status' => 'failed'),
                array('id' => $transaction_id)
            );
            wp_send_json_error(array('message' => $result['error'] ?? 'خطا در اتصال به درگاه'));
        }
    }


    /**
     * Send payment request to gateway
     */
    private function send_to_gateway($gateway_id, $data) {
        switch ($gateway_id) {
            case 'zarinpal':
                return $this->zarinpal_request($data);
            case 'idpay':
                return $this->idpay_request($data);
            default:
                return array('success' => false, 'error' => 'درگاه نامعتبر');
        }
    }

    /**
     * ZarinPal payment request
     */
    private function zarinpal_request($data) {
        $merchant = get_option('moaveze_gateway_zarinpal_merchant', '');
        $sandbox = get_option('moaveze_gateway_zarinpal_sandbox', 'yes');

        if (!$merchant) {
            return array('success' => false, 'error' => 'مرچنت کد زرین‌پال تنظیم نشده');
        }

        $base_url = ($sandbox === 'yes')
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
            : 'https://api.zarinpal.com/pg/v4/payment/request.json';

        $body = array(
            'merchant_id'  => $merchant,
            'amount'       => $data['amount'],
            'callback_url' => $data['callback_url'],
            'description'  => $data['description'],
            'metadata'     => array(
                'email' => $data['user_email'],
                'mobile' => $data['user_phone'],
            ),
        );

        $response = wp_remote_post($base_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode($body),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => 'خطا در اتصال به زرین‌پال');
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['data']['authority'])) {
            $authority = $result['data']['authority'];
            $redirect_base = ($sandbox === 'yes')
                ? 'https://sandbox.zarinpal.com/pg/StartPay/'
                : 'https://www.zarinpal.com/pg/StartPay/';

            // Save authority
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'moaveze_transactions',
                array('gateway_ref' => $authority),
                array('id' => $data['transaction_id'])
            );

            return array(
                'success'      => true,
                'redirect_url' => $redirect_base . $authority,
                'authority'    => $authority,
            );
        }

        return array('success' => false, 'error' => $result['errors']['message'] ?? 'خطای زرین‌پال');
    }

    /**
     * IDPay payment request
     */
    private function idpay_request($data) {
        $api_key = get_option('moaveze_gateway_idpay_api_key', '');
        $sandbox = get_option('moaveze_gateway_idpay_sandbox', 'yes');

        if (!$api_key) {
            return array('success' => false, 'error' => 'API Key آیدی‌پی تنظیم نشده');
        }

        $body = array(
            'order_id' => $data['transaction_id'],
            'amount'   => $data['amount'],
            'name'     => wp_get_current_user()->display_name,
            'phone'    => $data['user_phone'],
            'mail'     => $data['user_email'],
            'desc'     => $data['description'],
            'callback' => $data['callback_url'],
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'X-API-KEY'    => $api_key,
            'X-SANDBOX'    => ($sandbox === 'yes') ? '1' : '0',
        );

        $response = wp_remote_post('https://api.idpay.ir/v1.1/payment', array(
            'headers' => $headers,
            'body'    => wp_json_encode($body),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'error' => 'خطا در اتصال به آیدی‌پی');
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['link'])) {
            // Save ID
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'moaveze_transactions',
                array('gateway_ref' => $result['id']),
                array('id' => $data['transaction_id'])
            );

            return array(
                'success'      => true,
                'redirect_url' => $result['link'],
            );
        }

        return array('success' => false, 'error' => $result['error_message'] ?? 'خطای آیدی‌پی');
    }


    /**
     * Process payment callback
     */
    private function process_callback($gateway_id) {
        $transaction_id = absint($_GET['transaction_id'] ?? 0);

        if (!$transaction_id) {
            $this->redirect_with_status('failed', 'شناسه تراکنش نامعتبر');
            return;
        }

        global $wpdb;
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_transactions WHERE id = %d",
            $transaction_id
        ));

        if (!$transaction || $transaction->status !== 'pending') {
            $this->redirect_with_status('failed', 'تراکنش نامعتبر یا قبلاً پردازش شده');
            return;
        }

        // Verify with gateway
        $verified = false;
        switch ($gateway_id) {
            case 'zarinpal':
                $verified = $this->zarinpal_verify($transaction);
                break;
            case 'idpay':
                $verified = $this->idpay_verify($transaction);
                break;
        }

        if ($verified) {
            // Mark as successful
            $wpdb->update(
                $wpdb->prefix . 'moaveze_transactions',
                array('status' => 'completed', 'completed_at' => current_time('mysql')),
                array('id' => $transaction_id)
            );

            // Execute payment action
            $this->execute_payment_action($transaction);

            // Redirect to success
            $this->redirect_with_status('success', 'پرداخت با موفقیت انجام شد');
        } else {
            $wpdb->update(
                $wpdb->prefix . 'moaveze_transactions',
                array('status' => 'failed'),
                array('id' => $transaction_id)
            );
            $this->redirect_with_status('failed', 'پرداخت ناموفق بود');
        }
    }

    /**
     * ZarinPal verification
     */
    private function zarinpal_verify($transaction) {
        $authority = $_GET['Authority'] ?? '';
        $status = $_GET['Status'] ?? '';

        if ($status !== 'OK' || !$authority) return false;

        $merchant = get_option('moaveze_gateway_zarinpal_merchant', '');
        $sandbox = get_option('moaveze_gateway_zarinpal_sandbox', 'yes');

        $base_url = ($sandbox === 'yes')
            ? 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json'
            : 'https://api.zarinpal.com/pg/v4/payment/verify.json';

        $response = wp_remote_post($base_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode(array(
                'merchant_id' => $merchant,
                'amount'      => $transaction->amount,
                'authority'   => $authority,
            )),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) return false;

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return isset($result['data']['code']) && $result['data']['code'] == 100;
    }

    /**
     * IDPay verification
     */
    private function idpay_verify($transaction) {
        $id = $_POST['id'] ?? $_GET['id'] ?? '';
        $order_id = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
        $status_code = $_POST['status'] ?? $_GET['status'] ?? '';

        if ($status_code != 10) return false; // 10 = ready to verify

        $api_key = get_option('moaveze_gateway_idpay_api_key', '');
        $sandbox = get_option('moaveze_gateway_idpay_sandbox', 'yes');

        $response = wp_remote_post('https://api.idpay.ir/v1.1/payment/verify', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-KEY'    => $api_key,
                'X-SANDBOX'    => ($sandbox === 'yes') ? '1' : '0',
            ),
            'body' => wp_json_encode(array(
                'id'       => $id,
                'order_id' => $order_id,
            )),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) return false;

        $result = json_decode(wp_remote_retrieve_body($response), true);
        return isset($result['status']) && $result['status'] == 100;
    }


    /**
     * Execute the action after successful payment
     */
    private function execute_payment_action($transaction) {
        $meta = json_decode($transaction->meta_data, true) ?: array();

        switch ($transaction->type) {
            case 'subscription':
                $this->activate_subscription($transaction->user_id, $meta['plan'] ?? 'monthly', $transaction->amount);
                break;

            case 'per_contact':
                $this->grant_contact_access($transaction->user_id, $transaction->reference_id, $transaction->amount);
                break;

            case 'vip':
                $this->make_listing_vip($transaction->reference_id);
                break;

            case 'boost':
                $days = absint($meta['days'] ?? 7);
                $this->boost_listing($transaction->reference_id, $days);
                break;
        }

        // Send notification
        $notif = new Moaveze_Notifications();
        $notif->send(
            $transaction->user_id,
            'system',
            'پرداخت موفق ✅',
            sprintf('پرداخت شما به مبلغ %s تومان با موفقیت انجام شد.', number_format($transaction->amount)),
            array('transaction_id' => $transaction->id, 'type' => $transaction->type)
        );
    }

    /**
     * Activate subscription
     */
    private function activate_subscription($user_id, $plan, $amount) {
        $durations = array('monthly' => 30, 'quarterly' => 90, 'yearly' => 365);
        $days = $durations[$plan] ?? 30;

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_subscriptions',
            array(
                'user_id'        => $user_id,
                'plan_type'      => $plan,
                'starts_at'      => current_time('mysql'),
                'expires_at'     => date('Y-m-d H:i:s', strtotime("+{$days} days")),
                'status'         => 'active',
                'payment_amount' => $amount,
                'payment_method' => 'online',
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Grant contact access
     */
    private function grant_contact_access($user_id, $exchange_id, $amount) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'moaveze_contact_views',
            array(
                'exchange_id'    => $exchange_id,
                'user_id'        => $user_id,
                'view_type'      => 'paid',
                'payment_amount' => $amount,
                'payment_method' => 'online',
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
    }

    /**
     * Make listing VIP
     */
    private function make_listing_vip($post_id) {
        update_post_meta($post_id, '_moaveze_featured', '1');
        update_post_meta($post_id, '_moaveze_vip_until', date('Y-m-d H:i:s', strtotime('+30 days')));
    }

    /**
     * Boost listing
     */
    private function boost_listing($post_id, $days) {
        update_post_meta($post_id, '_moaveze_boost_until', date('Y-m-d H:i:s', strtotime("+{$days} days")));
        update_post_meta($post_id, '_moaveze_featured', '1');
    }

    /**
     * Get subscription price for plan
     */
    private function get_subscription_price($plan) {
        $base = absint(get_option('moaveze_subscription_price', 500000));
        $multipliers = array('monthly' => 1, 'quarterly' => 2.7, 'yearly' => 10);
        return round($base * ($multipliers[$plan] ?? 1));
    }

    /**
     * Redirect with payment status
     */
    private function redirect_with_status($status, $message) {
        $url = add_query_arg(array(
            'payment_status'  => $status,
            'payment_message' => urlencode($message),
        ), home_url('/'));
        wp_redirect($url);
        exit;
    }

    /**
     * Get active gateway for display
     */
    public static function get_active_gateways() {
        $gateways = array();
        if (get_option('moaveze_gateway_zarinpal_enabled') === 'yes') {
            $gateways['zarinpal'] = 'زرین‌پال';
        }
        if (get_option('moaveze_gateway_idpay_enabled') === 'yes') {
            $gateways['idpay'] = 'آیدی‌پی';
        }
        return $gateways;
    }
}

new Moaveze_Payment();
