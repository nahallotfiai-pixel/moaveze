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
            this.bindViewHistory();
            this.bindDeleteHistory();
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
                MoavezeValuation.toggleLiteModelWarning($card, provider, model);
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
                MoavezeValuation.toggleLiteModelWarning($card, provider, model);

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

        /**
         * Show/hide the "Lite model not recommended for valuation
         * accuracy" warning based on the currently-selected model name
         * - per explicit user report that switching to a Lite model
         * (to work around a temporary Google rate-limit) produced
         * noticeably less accurate/reasonable price numbers. Lite
         * models trade off analytical depth for speed/cost, which
         * matters for a multi-step reasoning task like property
         * valuation even though it's fine for simpler tasks.
         */
        toggleLiteModelWarning($card, provider, model) {
            let $warning = $card.find('.moaveze-lite-model-warning');
            const isLite = provider === 'gemini' && /-lite/i.test(model || '');

            if (isLite && !$warning.length) {
                $warning = $(`
                    <p class="description moaveze-lite-model-warning" style="color:#92400e;background:#fef3c7;padding:8px 12px;border-radius:8px;">
                        ⚠ مدل‌های «Lite» (مثل Flash-Lite) برای سرعت و مصرف کم طراحی شده‌اند، نه برای دقت تحلیلی بالا - برای <strong>ارزش‌گذاری ملک</strong> (که به استدلال چندمرحله‌ای دقیق نیاز دارد) توصیه نمی‌شود و ممکن است اعداد نامنطقی یا کمتر دقیق ارائه دهد. برای بهترین نتیجه، مدل استاندارد (بدون Lite) مثل <code>gemini-2.5-flash</code> یا <code>gemini-2.5-pro</code> را انتخاب کنید. اگر فقط به دلیل محدودیت نرخ (rate limit) از Lite استفاده می‌کنید، پس از رفع ازدحام به مدل استاندارد برگردید.
                    </p>
                `);
                $card.find('.moaveze-ai-models-status').after($warning);
            } else if (!isLite) {
                $warning.remove();
            }
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
                    let methodologyHtml = '';
                    if (d.methodology) {
                        methodologyHtml = `<div class="var-methodology"><strong>روش محاسبه:</strong> ${d.methodology}</div>`;
                    }

                    $result.html(`
                        ${MoavezeValuation.renderValuationDetails(d)}
                        ${methodologyHtml}
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

        /**
         * Build the same rich result markup used right after running a
         * fresh AI valuation (methodology box + comparables table), for
         * both the "just ran AI" case and the "viewing a past history
         * entry" case - shared so the two never visually drift apart.
         */
        renderValuationDetails(d) {
            let rangeHtml = '';
            if (d.min_short && d.max_short) {
                rangeHtml = `<div class="var-range">محدوده منطقی: ${d.min_short} تا ${d.max_short}</div>`;
            }
            let perSqmHtml = '';
            if (d.price_per_sqm_short) {
                perSqmHtml = `<div class="var-per-sqm">قیمت پیشنهادی هر متر: <strong>${d.price_per_sqm_short}</strong></div>`;
            }
            let comparablesHtml = '';
            if (d.comparables && d.comparables.length) {
                const rows = d.comparables.map((c) => {
                    let linkCell = '<span class="var-no-link">بدون لینک (صرفاً مثال احتمالی)</span>';
                    if (c.source_url) {
                        if (c.url_status === 'verified') {
                            linkCell = `<a href="${c.source_url}" target="_blank" rel="noopener" class="var-source-link var-verified">
                                ✓ مشاهده آگهی منبع (لینک واقعی - تأییدشده توسط جست‌وجوی گوگل)
                            </a>`;
                        } else if (c.url_status === 'verified_dead') {
                            // Real search DID happen and DID find this
                            // exact page (independently confirmed by
                            // Google) - the link itself has since gone
                            // dead (404/410), most likely because the
                            // Divar listing was removed after being sold
                            // between when the search ran and now. Shown
                            // distinctly so the consultant doesn't
                            // mistake this for a fabricated citation.
                            linkCell = `<a href="${c.source_url}" target="_blank" rel="noopener" class="var-source-link var-dead">
                                ⛔ آگهی منبع دیگر در دسترس نیست (احتمالاً حذف/فروخته شده - اما جست‌وجو واقعی بود)
                            </a>`;
                        } else if (c.url_status === 'verified_unresolved') {
                            linkCell = `<a href="${c.source_url}" target="_blank" rel="noopener" class="var-source-link var-verified">
                                ✓ مشاهده آگهی منبع (تأییدشده - از طریق لینک واسط گوگل)
                            </a>`;
                        } else {
                            linkCell = `<a href="${c.source_url}" target="_blank" rel="noopener" class="var-source-link var-unverified">
                                ⚠ لینک ادعاشده توسط هوش مصنوعی (تأیید مستقل نشده - قبل از اتکا بررسی کنید)
                            </a>`;
                        }
                    }
                    return `
                        <tr>
                            <td>${c.description || '—'}</td>
                            <td>${c.price_total_short || '—'}</td>
                            <td>${c.price_per_sqm_short || '—'}</td>
                            <td>${linkCell}</td>
                        </tr>
                    `;
                }).join('');
                comparablesHtml = `
                    <div class="var-comparables-table">
                        <strong>موارد مشابه بررسی‌شده:</strong>
                        <table>
                            <thead><tr><th>توضیح</th><th>ارزش کل</th><th>قیمت هر متر</th><th>منبع</th></tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
            }

            const groundingBadge = d.grounded
                ? '<span class="var-grounding-badge var-grounding-on">🔍 با جست‌وجوی واقعی وب (Google Search Grounding)</span>'
                : '<span class="var-grounding-badge var-grounding-off">📚 فقط بر اساس دانش قبلی مدل (بدون جست‌وجوی زنده)</span>';

            // Independent proof a real search happened: the FULL raw
            // list of every page Google's search actually retrieved for
            // this answer, regardless of whether the model matched it
            // to one of the comparables above. If this list is empty
            // while "grounded" is true, that itself is meaningful (the
            // search ran but found nothing usable).
            let allSourcesHtml = '';
            if (d.grounded && d.all_grounded_sources && d.all_grounded_sources.length) {
                const items = d.all_grounded_sources.map((s) => `
                    <li>
                        <a href="${s.url}" target="_blank" rel="noopener">${s.title || s.url}</a>
                        ${s.is_dead ? '<span class="var-dead-tag">⛔ دیگر در دسترس نیست</span>' : ''}
                    </li>
                `).join('');
                allSourcesHtml = `
                    <details class="var-all-sources">
                        <summary>📋 مشاهده همه ${d.all_grounded_sources.length} منبعی که واقعاً در این جست‌وجو بازیابی شد (اثبات مستقل انجام جست‌وجو)</summary>
                        <ul>${items}</ul>
                    </details>
                `;
            } else if (d.grounded) {
                allSourcesHtml = `<p class="var-no-sources">جست‌وجوی وب انجام شد اما هیچ صفحه قابل استنادی بازیابی نشد.</p>`;
            }

            return `
                <div class="var-confidence">میزان اطمینان: ${d.confidence || 'نامشخص'}</div>
                ${groundingBadge}
                <div class="var-value">${d.value_short}</div>
                ${rangeHtml}
                ${perSqmHtml}
                <div class="var-reasoning">${d.reasoning || ''}</div>
                ${comparablesHtml}
                ${allSourcesHtml}
            `;
        },

        /**
         * "مشاهده" (view) button in the valuation history list - fetches
         * and toggles an inline detail panel showing the full
         * methodology/reasoning/comparables for that past entry,
         * without needing to re-run the AI (per explicit user request:
         * "بتوان نتایج قبلی تحلیل ها رو هم ... مشاهده کرد").
         */
        bindViewHistory() {
            $(document).on('click', '.valuation-view-btn', function () {
                const valuationId = $(this).data('valuation-id');
                const $details = $(`.valuation-history-details[data-valuation-id="${valuationId}"]`);

                if ($details.is(':visible')) {
                    $details.slideUp(150);
                    return;
                }

                if ($details.data('loaded')) {
                    $details.slideDown(150);
                    return;
                }

                $details.html('<span class="spinner is-active" style="float:none;"></span> در حال بارگذاری...').show();

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_get_valuation_details',
                    nonce: $('#moaveze_valuation_nonce').val(),
                    valuation_id: valuationId,
                }, function (response) {
                    if (response.success) {
                        $details.html(MoavezeValuation.renderValuationDetails(response.data)).data('loaded', true);
                    } else {
                        $details.html('<p style="color:#dc2626;">' + (response.data || 'خطا در بارگذاری جزئیات') + '</p>');
                    }
                });
            });
        },

        /**
         * "حذف" (delete) button in the valuation history list - per
         * explicit user request ("بتوان نتایج قبلی تحلیل ها رو هم پاک
         * کرد"). If the entry is currently published on the listing,
         * the server refuses on the first attempt and asks for
         * confirmation (requires_confirm), which we then re-send with
         * force=1 after the admin explicitly confirms losing the
         * published قیمت کارشناسی card too.
         */
        bindDeleteHistory() {
            $(document).on('click', '.valuation-delete-btn', function () {
                const valuationId = $(this).data('valuation-id');
                const $item = $(this).closest('.valuation-history-item');

                if (!confirm('آیا این ارزش‌گذاری برای همیشه حذف شود؟')) return;

                MoavezeValuation.doDeleteValuation(valuationId, false, $item);
            });
        },

        doDeleteValuation(valuationId, force, $item) {
            $.post(moavezeAdmin.ajaxUrl, {
                action: 'moaveze_delete_valuation',
                nonce: $('#moaveze_valuation_nonce').val(),
                valuation_id: valuationId,
                force: force ? '1' : '',
            }, function (response) {
                if (response.success) {
                    $item.next('.valuation-history-details').remove();
                    $item.slideUp(150, function () { $(this).remove(); });
                } else if (response.data && response.data.requires_confirm) {
                    if (confirm(response.data.message + '\n\nآیا مطمئنید و می‌خواهید همین حالا حذف کنید؟')) {
                        MoavezeValuation.doDeleteValuation(valuationId, true, $item);
                    }
                } else {
                    alert((response.data && response.data.message) || response.data || 'خطا در حذف');
                }
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
