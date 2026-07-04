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
            this.bindFetchModels();
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

        toLatinDigits(str) {
            const persianMap = { '۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9' };
            return (str || '').replace(/[۰-۹]/g, (ch) => persianMap[ch] || ch);
        },

        bindManualSave() {
            $(document).on('click', '.valuation-save-manual-btn', function () {
                const $box = $(this).closest('.moaveze-valuation-box');
                const $btn = $(this);
                // Normalize Persian/Arabic-Indic digits to Latin before
                // stripping non-digits (same fix as the frontend price
                // inputs - see MoavezePlus.toLatinDigits() in main.js).
                const value = MoavezeValuation.toLatinDigits($box.find('.valuation-manual-value').val()).replace(/[^\d]/g, '');
                const minValue = MoavezeValuation.toLatinDigits($box.find('.valuation-manual-min').val()).replace(/[^\d]/g, '');
                const maxValue = MoavezeValuation.toLatinDigits($box.find('.valuation-manual-max').val()).replace(/[^\d]/g, '');
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
                    min_value: minValue,
                    max_value: maxValue,
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

        /**
         * "دریافت لیست مدل‌های موجود" button next to each provider's
         * model field on Settings > هوش مصنوعی - fetches the real list
         * of models available to the entered API key/account right now
         * and shows each one's رایگان/پولی status so the admin never has
         * to guess or hardcode a model name.
         */
        bindFetchModels() {
            $(document).on('click', '.moaveze-ai-fetch-models-btn', function () {
                const $btn = $(this);
                const provider = $btn.data('provider');
                const $card = $btn.closest('.moaveze-ai-provider-card');
                const $status = $card.find('.moaveze-ai-models-status');
                const $select = $card.find('.moaveze-ai-model-select');
                const $input = $card.find('.moaveze-ai-model-input');
                const apiKey = $card.find('input[type="password"]').val();
                const accountId = $card.find('input[name="moaveze_ai_cloudflare_ai_account_id"]').val();
                const url = $card.find('.moaveze-ai-url-input').val();

                $btn.prop('disabled', true).find('.dashicons').addClass('moaveze-spin');
                $status.text('در حال دریافت لیست مدل‌ها...').css('color', '#64748b');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_fetch_ai_models',
                    nonce: moavezeAdmin.nonce,
                    provider: provider,
                    api_key: apiKey,
                    account_id: accountId,
                    url: url,
                }, function (response) {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('moaveze-spin');

                    if (!response.success) {
                        $status.text('✗ ' + (response.data || 'خطا در دریافت لیست مدل‌ها')).css('color', '#dc2626');
                        $select.hide();
                        return;
                    }

                    const models = response.data.models || [];
                    if (!models.length) {
                        $status.text('هیچ مدلی برای این حساب یافت نشد.').css('color', '#dc2626');
                        $select.hide();
                        return;
                    }

                    $select.empty();
                    models.forEach((m) => {
                        const badge = m.is_free ? '🟢 رایگان' : '🔶 ' + m.pricing_note;
                        const $opt = $('<option></option>').val(m.id).text(`${m.label} — ${badge}`);
                        if (m.id === $input.val()) $opt.prop('selected', true);
                        $select.append($opt);
                    });
                    $select.show();
                    $status.text(`✓ ${models.length} مدل یافت شد. یکی را انتخاب کنید یا نام مدل را در فیلد بالا دستی وارد کنید.`).css('color', '#16a34a');
                }).fail(() => {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('moaveze-spin');
                    $status.text('✗ خطا در ارتباط با سرور').css('color', '#dc2626');
                });
            });

            // Selecting a model from the dropdown fills the text input
            // AND saves it immediately via AJAX (see
            // Moaveze_AI_Valuation::ajax_save_ai_model) - this used to
            // rely purely on the big "ذخیره تنظیمات" form submit, which
            // the site owner reported resulted in the field going blank
            // again after saving. Saving instantly removes that entire
            // failure mode; the value is still written into the visible
            // text input too, so the normal settings-form save (if used)
            // carries the same value.
            $(document).on('change', '.moaveze-ai-model-select', function () {
                const $select = $(this);
                const $card = $select.closest('.moaveze-ai-provider-card');
                const $input = $card.find('.moaveze-ai-model-input');
                const $status = $card.find('.moaveze-ai-models-status');
                const provider = $input.data('provider');
                const model = $select.val();

                $input.val(model);
                $status.text('در حال ذخیره مدل انتخاب‌شده...').css('color', '#64748b');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_save_ai_model',
                    nonce: moavezeAdmin.nonce,
                    provider: provider,
                    model: model,
                }, function (response) {
                    if (response.success) {
                        $input.val(response.data.saved_value);
                        $status.text('✓ ' + response.data.message + ' (ذخیره شد و پس از رفرش صفحه هم باقی می‌ماند)').css('color', '#16a34a');
                    } else {
                        $status.text('✗ ' + (response.data || 'خطا در ذخیره مدل')).css('color', '#dc2626');
                    }
                }).fail(function () {
                    $status.text('✗ خطا در ارتباط با سرور هنگام ذخیره مدل').css('color', '#dc2626');
                });
            });

            // Typing a model name directly (without using the dropdown)
            // also saves instantly on blur, for the same reason.
            $(document).on('blur', '.moaveze-ai-model-input', function () {
                const $input = $(this);
                const provider = $input.data('provider');
                const model = $input.val().trim();
                if (!provider || !model) return;

                const $card = $input.closest('.moaveze-ai-provider-card');
                const $status = $card.find('.moaveze-ai-models-status');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_save_ai_model',
                    nonce: moavezeAdmin.nonce,
                    provider: provider,
                    model: model,
                }, function (response) {
                    if (response.success) {
                        $status.text('✓ مدل ذخیره شد').css('color', '#16a34a');
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
