<?php
/**
 * AI-Assisted Deal-Structure Suggestions for Matches & Chain Swaps
 *
 * Per explicit user request: "کاری کن ai بتونه در قسمت تطابق ها و
 * معاوضه زنجیره‌ای هم پیشنهاد بده و قابل ذخیره سازی و تایید باشه".
 *
 * SCOPE: exactly like the property-valuation AI module
 * (class-ai-valuation.php), this is STAFF-ONLY (admin + moaveze_consultant
 * role) - never exposed to regular site visitors. It reuses that same
 * module's configured provider/proxy/Cloudflare-Worker infrastructure
 * via Moaveze_AI_Valuation::ask() rather than duplicating any HTTP/
 * provider-dispatch logic.
 *
 * For a MATCH (two listings): asks the AI to propose a concrete,
 * fair deal structure to bridge the value gap between the two
 * properties (e.g. "ملک A + ۲ میلیارد تومان نقد" or "ملک A + خودروی
 * X"), grounded in the exact same match-score breakdown the matching
 * algorithm already calculated (class-matching.php).
 *
 * For a CHAIN (3+ listings in a cycle): asks the AI to propose the
 * order of hand-offs and how any residual value differences around
 * the loop should be settled.
 *
 * Every suggestion is:
 *   - stored (ai_suggestion / ai_suggestion_status / ai_suggestion_
 *     provider / ai_suggestion_at columns on moaveze_matches /
 *     moaveze_chains, added in class-database.php)
 *   - re-viewable at any time without re-running the AI
 *   - explicitly "approved" by a consultant (a deliberate action,
 *     never automatic) before it's considered final - approval simply
 *     marks ai_suggestion_status = 'approved' and adds a note to
 *     consultant_notes; it does NOT change match/chain status by
 *     itself, keeping that entirely under manual consultant control.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Moaveze_AI_Suggestions {

    public function __construct() {
        add_action('wp_ajax_moaveze_suggest_match_deal', array($this, 'ajax_suggest_match_deal'));
        add_action('wp_ajax_moaveze_suggest_chain_deal', array($this, 'ajax_suggest_chain_deal'));
        add_action('wp_ajax_moaveze_approve_ai_suggestion', array($this, 'ajax_approve_ai_suggestion'));
        add_action('wp_ajax_moaveze_get_ai_suggestion', array($this, 'ajax_get_ai_suggestion'));
    }

    /**
     * Same staff-only gate used throughout class-ai-valuation.php.
     */
    private function current_user_is_staff() {
        return current_user_can('manage_options') || current_user_can('moaveze_verify_listings');
    }

    /**
     * Get the shared AI valuation module instance so we can reuse its
     * ask()/is_ready()/get_active_provider_label() wrappers instead of
     * re-implementing provider dispatch, proxy, and Worker-relay logic.
     */
    private function ai() {
        global $moaveze_ai_valuation_instance;
        // Moaveze_AI_Valuation doesn't currently expose a singleton, so
        // build a light local instance purely to call its public
        // methods - its constructor only registers hooks, which is safe
        // to run more than once (WordPress dedupes identical callback
        // registrations), and calling ask()/is_ready() doesn't depend
        // on any instance state.
        static $instance = null;
        if ($instance === null) {
            $instance = new Moaveze_AI_Valuation();
        }
        return $instance;
    }

    /**
     * AJAX: fetch a previously-generated suggestion (for "مشاهده
     * پیشنهاد" without re-running the AI, and to redisplay after page
     * reload).
     */
    public function ajax_get_ai_suggestion() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $type = sanitize_key($_POST['type'] ?? ''); // 'match' or 'chain'
        $id = absint($_POST['id'] ?? 0);
        if (!in_array($type, array('match', 'chain'), true) || !$id) {
            wp_send_json_error('ورودی نامعتبر');
        }

        global $wpdb;
        $table = $type === 'match' ? $wpdb->prefix . 'moaveze_matches' : $wpdb->prefix . 'moaveze_chains';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$row || !$row->ai_suggestion) {
            wp_send_json_error('هنوز پیشنهادی برای این مورد ثبت نشده است');
        }

        wp_send_json_success($this->format_suggestion_for_display($row));
    }

    /**
     * AJAX: mark a stored suggestion as "approved" by the consultant -
     * a deliberate, explicit action (never automatic).
     */
    public function ajax_approve_ai_suggestion() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $type = sanitize_key($_POST['type'] ?? '');
        $id = absint($_POST['id'] ?? 0);
        if (!in_array($type, array('match', 'chain'), true) || !$id) {
            wp_send_json_error('ورودی نامعتبر');
        }

        global $wpdb;
        $table = $type === 'match' ? $wpdb->prefix . 'moaveze_matches' : $wpdb->prefix . 'moaveze_chains';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if (!$row || !$row->ai_suggestion) {
            wp_send_json_error('پیشنهادی برای تأیید یافت نشد');
        }

        $user = wp_get_current_user();
        $note_field = $type === 'match' ? 'consultant_notes' : 'notes';
        $existing_notes = $row->{$note_field} ?: '';
        $approval_note = sprintf(
            "[%s] پیشنهاد هوش مصنوعی (%s) توسط %s تأیید شد.",
            Moaveze_Helpers::jalali_date(current_time('mysql'), 'Y/m/d H:i'),
            $row->ai_suggestion_provider ?: '—',
            $user->display_name ?: $user->user_login
        );

        $wpdb->update(
            $table,
            array(
                'ai_suggestion_status' => 'approved',
                $note_field            => trim($existing_notes . "\n" . $approval_note),
            ),
            array('id' => $id)
        );

        wp_send_json_success(array('message' => 'پیشنهاد هوش مصنوعی تأیید شد و در یادداشت‌های مشاور ثبت شد'));
    }

    /**
     * AJAX: generate (or re-generate) an AI-suggested deal structure
     * for a specific MATCH between two listings.
     */
    public function ajax_suggest_match_deal() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $match_id = absint($_POST['match_id'] ?? 0);
        if (!$match_id) wp_send_json_error('شناسه تطابق نامعتبر');

        global $wpdb;
        $match = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_matches WHERE id = %d", $match_id
        ));
        if (!$match) wp_send_json_error('تطابق یافت نشد');

        $a = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $match->exchange_id_a
        ));
        $b = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $match->exchange_id_b
        ));
        if (!$a || !$b) wp_send_json_error('یکی از آگهی‌های این تطابق حذف شده است');

        $prompt = $this->build_match_prompt($a, $b, $match);
        $result = $this->ai()->ask($prompt, true);

        if (!$result['success']) {
            wp_send_json_error('خطا در ارتباط با هوش مصنوعی: ' . $result['error']);
        }

        $parsed = $this->parse_deal_response($result['text']);
        $provider_label = $this->ai()->get_active_provider_label();

        $wpdb->update(
            $wpdb->prefix . 'moaveze_matches',
            array(
                'ai_suggestion'          => wp_json_encode($parsed),
                'ai_suggestion_status'   => 'pending',
                'ai_suggestion_provider' => $provider_label,
                'ai_suggestion_at'       => current_time('mysql'),
            ),
            array('id' => $match_id)
        );

        wp_send_json_success($this->format_parsed_for_display($parsed, $provider_label, 'pending'));
    }

    /**
     * AJAX: generate (or re-generate) an AI-suggested settlement plan
     * for a CHAIN swap (3+ listings in a cycle).
     */
    public function ajax_suggest_chain_deal() {
        check_ajax_referer('moaveze_admin_nonce', 'nonce');
        if (!$this->current_user_is_staff()) wp_send_json_error('دسترسی ندارید');

        $chain_id = absint($_POST['chain_id'] ?? 0);
        if (!$chain_id) wp_send_json_error('شناسه زنجیره نامعتبر');

        global $wpdb;
        $chain = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moaveze_chains WHERE id = %d", $chain_id
        ));
        if (!$chain) wp_send_json_error('زنجیره یافت نشد');

        $exchange_ids = json_decode($chain->exchange_ids, true) ?: array();
        if (count($exchange_ids) < 3) wp_send_json_error('زنجیره نامعتبر است');

        $exchanges = array();
        foreach ($exchange_ids as $eid) {
            $ex = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}moaveze_exchanges WHERE id = %d", $eid
            ));
            if ($ex) $exchanges[] = $ex;
        }
        if (count($exchanges) < 3) wp_send_json_error('یکی از آگهی‌های این زنجیره حذف شده است');

        $prompt = $this->build_chain_prompt($exchanges);
        $result = $this->ai()->ask($prompt, true);

        if (!$result['success']) {
            wp_send_json_error('خطا در ارتباط با هوش مصنوعی: ' . $result['error']);
        }

        $parsed = $this->parse_deal_response($result['text']);
        $provider_label = $this->ai()->get_active_provider_label();

        $wpdb->update(
            $wpdb->prefix . 'moaveze_chains',
            array(
                'ai_suggestion'          => wp_json_encode($parsed),
                'ai_suggestion_status'   => 'pending',
                'ai_suggestion_provider' => $provider_label,
                'ai_suggestion_at'       => current_time('mysql'),
            ),
            array('id' => $chain_id)
        );

        wp_send_json_success($this->format_parsed_for_display($parsed, $provider_label, 'pending'));
    }

    /**
     * Build the Persian prompt for a two-party MATCH, grounded in the
     * exact same data the matching algorithm scored (value, area,
     * district, exchange type/cash direction), so the AI's suggestion
     * is consistent with why the system already thinks these two are a
     * good match rather than reasoning from scratch.
     */
    private function build_match_prompt($a, $b, $match) {
        $details = json_decode($match->match_details, true) ?: array();
        $suggestion_hint = $details['suggestion'] ?? '';

        $side = function ($ex, $label) {
            return sprintf(
                "ملک %s: عنوان «%s»، نوع %s، منطقه %s، متراژ %d متر، ارزش %s تومان، نوع معاوضه مدنظر: %s، مابه‌التفاوت نقدی: %s (%s)، ملک/منطقه مورد نظر: %s",
                $label,
                get_the_title($ex->post_id) ?: '—',
                $ex->property_type ?: 'نامشخص',
                $ex->district ?: 'نامشخص',
                (int) $ex->area_sqm,
                number_format($ex->property_value),
                $ex->exchange_type ?: 'نامشخص',
                $ex->cash_difference ? number_format($ex->cash_difference) : 'ندارد',
                $ex->cash_direction === 'give' ? 'می‌دهد' : 'می‌گیرد',
                $ex->desired_property_type ?: 'فرقی ندارد'
            );
        };

        $side_a = $side($a, 'A');
        $side_b = $side($b, 'B');
        $value_diff = abs($a->property_value - $b->property_value);

        return <<<PROMPT
تو یک مشاور معاوضه ملک باتجربه در تبریز هستی که وظیفه‌ات پیشنهاد یک ساختار معامله منصفانه و عملی بین دو طرف معاوضه ملک است.

{$side_a}
{$side_b}

اختلاف ارزش این دو ملک: {$value_diff} تومان.
تحلیل اولیه سیستم: {$suggestion_hint}

با توجه به این اطلاعات، یک یا دو ساختار معامله مشخص و عملی پیشنهاد بده که اختلاف ارزش را به شکل منصفانه جبران کند (مثلاً مابه‌التفاوت نقدی دقیق، یا افزودن دارایی دیگر). نکات ریسک یا مواردی که باید مشاور قبل از نهایی کردن معامله بررسی کند را هم ذکر کن.

پاسخ را دقیقاً و فقط به‌صورت JSON با این ساختار بده (بدون توضیح اضافه یا markdown):

{
  "summary": "<خلاصه یک‌خطی پیشنهاد اصلی>",
  "structures": [
    {"title": "<عنوان کوتاه ساختار پیشنهادی>", "description": "<توضیح کامل: چه کسی چه چیزی می‌دهد/می‌گیرد>", "cash_amount": <عدد تومان یا null>, "cash_direction": "<'a_to_b' یا 'b_to_a' یا null>"}
  ],
  "risks": ["<نکته یا ریسک اول>", "<نکته دوم>"],
  "confidence": "<بالا, متوسط, یا پایین>"
}
PROMPT;
    }

    /**
     * Build the Persian prompt for a chain swap (3+ properties in a
     * cycle), asking the AI to propose the hand-off order and how to
     * settle any residual value differences around the loop.
     */
    private function build_chain_prompt($exchanges) {
        $nodes = array();
        $total_value = 0;
        foreach ($exchanges as $i => $ex) {
            $nodes[] = sprintf(
                "ملک %d: عنوان «%s»، نوع %s، منطقه %s، متراژ %d متر، ارزش %s تومان",
                $i + 1,
                get_the_title($ex->post_id) ?: '—',
                $ex->property_type ?: 'نامشخص',
                $ex->district ?: 'نامشخص',
                (int) $ex->area_sqm,
                number_format($ex->property_value)
            );
            $total_value += (int) $ex->property_value;
        }
        $nodes_text = implode("\n", $nodes);
        $count = count($exchanges);

        return <<<PROMPT
تو یک مشاور معاوضه ملک باتجربه در تبریز هستی. یک معاوضه زنجیره‌ای شناسایی شده که در آن {$count} ملک به‌صورت حلقه‌ای قابل معاوضه هستند (هر نفر ملک بعدی در حلقه را می‌گیرد و ملک خودش را به نفر قبلی می‌دهد).

{$nodes_text}

ارزش کل حلقه: {$total_value} تومان.

با توجه به این اطلاعات:
۱. ترتیب منطقی انجام این معاوضه زنجیره‌ای را پیشنهاد بده (چه کسی اول باید ملکش را تحویل دهد).
۲. اگر اختلاف ارزش قابل توجهی بین ملک‌های متوالی در زنجیره وجود دارد، نحوه تسویه آن (مابه‌التفاوت نقدی بین کدام دو طرف) را دقیقاً مشخص کن.
۳. مهم‌ترین ریسک‌های اجرای یک معاوضه زنجیره‌ای (مثلاً نیاز به تعهد همزمان همه طرفین، مراحل قانونی) را ذکر کن.

پاسخ را دقیقاً و فقط به‌صورت JSON با این ساختار بده (بدون توضیح اضافه یا markdown):

{
  "summary": "<خلاصه یک‌خطی ترتیب پیشنهادی>",
  "structures": [
    {"title": "<مثلاً 'ترتیب تحویل'>", "description": "<توضیح کامل ترتیب و تسویه نقدی بین طرفین>", "cash_amount": <عدد تومان یا null>, "cash_direction": null}
  ],
  "risks": ["<ریسک اول>", "<ریسک دوم>"],
  "confidence": "<بالا, متوسط, یا پایین>"
}
PROMPT;
    }

    /**
     * Parse the AI's JSON deal-structure response defensively (same
     * markdown-fence-stripping approach as
     * Moaveze_AI_Valuation::parse_valuation_response()).
     */
    private function parse_deal_response($text) {
        $json_start = strpos($text, '{');
        $json_end = strrpos($text, '}');
        $json_str = ($json_start !== false && $json_end !== false)
            ? substr($text, $json_start, $json_end - $json_start + 1)
            : $text;

        $data = json_decode($json_str, true);

        $structures = array();
        if (!empty($data['structures']) && is_array($data['structures'])) {
            foreach ($data['structures'] as $s) {
                if (!is_array($s)) continue;
                $structures[] = array(
                    'title'          => $s['title'] ?? '',
                    'description'    => $s['description'] ?? '',
                    'cash_amount'    => isset($s['cash_amount']) && is_numeric($s['cash_amount']) ? absint($s['cash_amount']) : null,
                    'cash_direction' => $s['cash_direction'] ?? null,
                );
            }
        }

        return array(
            'summary'    => $data['summary'] ?? mb_substr($text, 0, 300),
            'structures' => $structures,
            'risks'      => !empty($data['risks']) && is_array($data['risks']) ? array_map('sanitize_text_field', $data['risks']) : array(),
            'confidence' => $data['confidence'] ?? 'نامشخص',
            'raw'        => $text,
        );
    }

    /**
     * Shape a freshly-parsed suggestion for the AJAX JSON response
     * (price fields formatted short for display).
     */
    private function format_parsed_for_display($parsed, $provider_label, $status) {
        return array(
            'summary'    => $parsed['summary'],
            'confidence' => $parsed['confidence'],
            'provider'   => $provider_label,
            'status'     => $status,
            'risks'      => $parsed['risks'],
            'structures' => array_map(function ($s) {
                return array(
                    'title'       => $s['title'],
                    'description' => $s['description'],
                    'cash_short'  => $s['cash_amount'] ? Moaveze_Helpers::short_price($s['cash_amount']) : null,
                    'cash_direction' => $s['cash_direction'],
                );
            }, $parsed['structures']),
        );
    }

    /**
     * Shape a stored (previously-generated) DB row for the "مشاهده"
     * re-display AJAX response.
     */
    private function format_suggestion_for_display($row) {
        $parsed = json_decode($row->ai_suggestion, true) ?: array();
        $parsed = wp_parse_args($parsed, array('summary' => '', 'structures' => array(), 'risks' => array(), 'confidence' => 'نامشخص'));
        return $this->format_parsed_for_display($parsed, $row->ai_suggestion_provider, $row->ai_suggestion_status);
    }
}

new Moaveze_AI_Suggestions();
