/**
 * Moaveze Plus - AI Valuation Admin JS
 * Handles the metabox (manual + AI valuation) on the listing edit
 * screen, and the "test connection" button on the settings > AI tab.
 * Both are strictly admin/consultant-only surfaces.
 */

(function ($) {
    'use strict';

    const MoavezeValuation = {
        init() {
            this.bindTabs();
            this.bindManualSave();
            this.bindRunAI();
            this.bindApply();
            this.bindTestConnection();
        },

        bindTabs() {
            $(document).on('click', '.valuation-tab-btn', function () {
                if ($(this).is(':disabled')) return;
                const tab = $(this).data('tab');
                $('.valuation-tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.valuation-tab-content').hide();
                $(`.valuation-tab-content[data-tab-content="${tab}"]`).show();
            });
        },

        bindManualSave() {
            $(document).on('click', '.valuation-save-manual-btn', function () {
                const $box = $(this).closest('.moaveze-valuation-box');
                const $btn = $(this);
                // Normalize Persian/Arabic-Indic digits to Latin before
                // stripping non-digits (same fix as the frontend price
                // inputs - see MoavezePlus.toLatinDigits() in main.js).
                const persianMap = { '۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9' };
                const rawInput = $box.find('.valuation-manual-value').val() || '';
                const latinized = rawInput.replace(/[۰-۹]/g, (ch) => persianMap[ch] || ch);
                const value = latinized.replace(/[^\d]/g, '');
                const notes = $box.find('.valuation-manual-notes').val();

                if (!value) {
                    alert('لطفاً مبلغ ارزش‌گذاری را وارد کنید');
                    return;
                }

                $btn.prop('disabled', true);
                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_save_manual_valuation',
                    nonce: $('#moaveze_valuation_nonce').val(),
                    exchange_id: $box.data('exchange-id'),
                    post_id: $box.data('post-id'),
                    value: value,
                    notes: notes,
                }, function (response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        alert(response.data.message + ' (' + response.data.value_short + ')');
                        location.reload();
                    } else {
                        alert(response.data.message || response.data || 'خطا در ذخیره');
                    }
                });
            });
        },

        bindRunAI() {
            $(document).on('click', '.valuation-run-ai-btn', function () {
                const $box = $(this).closest('.moaveze-valuation-box');
                const $btn = $(this);
                const $result = $box.find('.valuation-ai-result');

                $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px;"></span> در حال دریافت پیشنهاد...');
                $result.hide();

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_run_ai_valuation',
                    nonce: $('#moaveze_valuation_nonce').val(),
                    exchange_id: $box.data('exchange-id'),
                }, function (response) {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero-alt"></span> دریافت پیشنهاد هوش مصنوعی');

                    if (!response.success) {
                        alert((response.data && response.data.message) || 'خطا در دریافت پیشنهاد هوش مصنوعی');
                        return;
                    }

                    const d = response.data;
                    let rangeHtml = '';
                    if (d.min_short && d.max_short) {
                        rangeHtml = `<div class="var-range">محدوده منطقی: ${d.min_short} تا ${d.max_short}</div>`;
                    }

                    $result.html(`
                        <div class="var-confidence">میزان اطمینان: ${d.confidence}</div>
                        <div class="var-value">${d.value_short}</div>
                        ${rangeHtml}
                        <div class="var-reasoning">${d.reasoning}</div>
                        ${d.comparables ? `<div class="var-comparables">${d.comparables}</div>` : ''}
                        <p style="margin-top:10px;font-size:12px;color:#64748b;">پیشنهاد ${d.provider} ذخیره شد. برای اعمال روی آگهی، صفحه را رفرش کرده و از بخش «تاریخچه ارزش‌گذاری‌ها» دکمه «اعمال روی آگهی» را بزنید.</p>
                    `).show();
                }).fail(() => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero-alt"></span> دریافت پیشنهاد هوش مصنوعی');
                    alert('خطا در ارتباط با سرور');
                });
            });
        },

        bindApply() {
            $(document).on('click', '.valuation-apply-btn', function () {
                if (!confirm('آیا این ارزش به‌عنوان ارزش رسمی آگهی اعمال شود؟')) return;
                const valuationId = $(this).data('valuation-id');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_apply_valuation',
                    nonce: $('#moaveze_valuation_nonce').val(),
                    valuation_id: valuationId,
                }, function (response) {
                    if (response.success) {
                        alert(response.data.message + ' (' + response.data.value_short + ')');
                        location.reload();
                    } else {
                        alert(response.data.message || response.data || 'خطا');
                    }
                });
            });
        },

        bindTestConnection() {
            $(document).on('click', '.moaveze-ai-test-btn', function () {
                const $btn = $(this);
                const provider = $btn.data('provider');
                const $resultSpan = $btn.siblings('.ai-test-result');

                $btn.prop('disabled', true);
                $resultSpan.removeClass('success error').text('در حال تست...');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_test_ai_connection',
                    nonce: moavezeAdmin.nonce,
                    provider: provider,
                }, function (response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $resultSpan.addClass('success').text('✓ متصل شد: ' + response.data.reply);
                    } else {
                        $resultSpan.addClass('error').text('✗ ' + (response.data.message || 'خطا در اتصال'));
                    }
                }).fail(() => {
                    $btn.prop('disabled', false);
                    $resultSpan.addClass('error').text('✗ خطا در ارتباط با سرور');
                });
            });
        }
    };

    $(document).ready(() => MoavezeValuation.init());

})(jQuery);
