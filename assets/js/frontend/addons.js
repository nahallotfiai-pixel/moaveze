/**
 * Moaveze Plus - Add-on Features JS
 * Report Listing modal, Compare Listings bar/table, QR Code modal.
 * Each block only activates if its corresponding server-side flag
 * (moavezeAddons.*) is true - i.e. enabled in
 * Settings > امکانات تکمیلی.
 */

(function ($) {
    'use strict';

    const MoavezeAddons = {
        compareIds: [],

        init() {
            if (typeof moavezeAddons === 'undefined') return;
            if (moavezeAddons.reportEnabled) this.initReport();
            if (moavezeAddons.compareEnabled) this.initCompare();
            if (moavezeAddons.qrEnabled) this.initQrCode();
        },

        /* ===================== Report Listing ===================== */
        initReport() {
            $(document).on('click', '.moaveze-report-btn', (e) => {
                const id = $(e.currentTarget).data('id');
                this.openReportModal(id);
            });

            $(document).on('submit', '#moaveze-report-form', (e) => {
                e.preventDefault();
                const $form = $(e.target);
                const $btn = $form.find('button[type="submit"]');
                $btn.prop('disabled', true);

                $.post(moavezePlus.ajaxUrl, {
                    action: 'moaveze_report_listing',
                    nonce: moavezePlus.nonce,
                    post_id: $form.data('post-id'),
                    reason: $form.find('[name="reason"]').val(),
                    details: $form.find('[name="details"]').val(),
                }, (response) => {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        MoavezePlus.showToast(response.data.message, 'success');
                        $('.moaveze-modal-overlay').remove();
                        $('body').css('overflow', '');
                    } else {
                        MoavezePlus.showToast(response.data.message || response.data || 'خطا', 'error');
                    }
                });
            });
        },

        openReportModal(postId) {
            $('.moaveze-modal-overlay').remove();
            const html = `
                <div class="moaveze-modal-overlay active">
                    <div class="moaveze-modal" style="max-width:440px;">
                        <div class="moaveze-modal-header">
                            <h3>گزارش تخلف آگهی</h3>
                            <button class="moaveze-modal-close">&times;</button>
                        </div>
                        <form id="moaveze-report-form" class="moaveze-modal-body" data-post-id="${postId}">
                            <div class="offer-form-section">
                                <h4>دلیل گزارش</h4>
                                <select name="reason" required style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;">
                                    <option value="اطلاعات نادرست">اطلاعات نادرست ملک</option>
                                    <option value="آگهی تکراری">آگهی تکراری</option>
                                    <option value="کلاهبرداری مشکوک">کلاهبرداری مشکوک</option>
                                    <option value="عدم پاسخگویی مالک">عدم پاسخگویی مالک</option>
                                    <option value="سایر موارد">سایر موارد</option>
                                </select>
                            </div>
                            <div class="offer-form-section">
                                <h4>توضیحات (اختیاری)</h4>
                                <textarea name="details" rows="3" style="width:100%;padding:10px;border:1.5px solid #e2e8f0;border-radius:8px;"></textarea>
                            </div>
                            <div class="moaveze-modal-footer" style="padding:0;border:none;margin-top:16px;">
                                <button type="button" class="moaveze-btn moaveze-btn-secondary moaveze-modal-close">انصراف</button>
                                <button type="submit" class="moaveze-btn moaveze-btn-primary">ارسال گزارش</button>
                            </div>
                        </form>
                    </div>
                </div>`;
            $('body').append(html);
            $('body').css('overflow', 'hidden');
        },

        /* ===================== Compare Listings ===================== */
        initCompare() {
            $(document).on('change', '.moaveze-compare-input', (e) => {
                const id = Number($(e.currentTarget).data('id'));
                if ($(e.currentTarget).is(':checked')) {
                    if (this.compareIds.length >= 3) {
                        $(e.currentTarget).prop('checked', false);
                        MoavezePlus.showToast('حداکثر ۳ آگهی قابل مقایسه است', 'error');
                        return;
                    }
                    this.compareIds.push(id);
                } else {
                    this.compareIds = this.compareIds.filter((i) => i !== id);
                }
                this.renderCompareBar();
            });

            $(document).on('click', '#moaveze-compare-view-btn', () => this.openCompareModal());
            $(document).on('click', '#moaveze-compare-clear-btn', () => {
                this.compareIds = [];
                $('.moaveze-compare-input').prop('checked', false);
                this.renderCompareBar();
            });
        },

        renderCompareBar() {
            $('#moaveze-compare-bar').remove();
            if (!this.compareIds.length) return;

            const bar = $(`
                <div id="moaveze-compare-bar" class="moaveze-compare-bar">
                    <span>${this.compareIds.length} آگهی برای مقایسه انتخاب شد</span>
                    <button type="button" id="moaveze-compare-view-btn" class="moaveze-btn moaveze-btn-primary moaveze-btn-sm" ${this.compareIds.length < 2 ? 'disabled' : ''}>مقایسه کن</button>
                    <button type="button" id="moaveze-compare-clear-btn" class="moaveze-btn moaveze-btn-ghost moaveze-btn-sm">پاک کردن</button>
                </div>
            `);
            $('body').append(bar);
        },

        openCompareModal() {
            $.post(moavezePlus.ajaxUrl, {
                action: 'moaveze_get_compare_data',
                nonce: moavezePlus.nonce,
                ids: this.compareIds,
            }, (response) => {
                if (!response.success || !response.data.items.length) return;
                this.renderCompareModal(response.data.items);
            });
        },

        renderCompareModal(items) {
            $('.moaveze-modal-overlay').remove();

            const rows = [
                ['تصویر', (i) => i.image ? `<img src="${i.image}" style="width:100%;border-radius:8px;">` : '—'],
                ['عنوان', (i) => `<a href="${i.url}" target="_blank">${i.title}</a>`],
                ['ارزش', (i) => `<strong>${i.value_short}</strong>`],
                ['قیمت هر متر', (i) => i.price_per_sqm],
                ['متراژ', (i) => i.area + ' متر'],
                ['نوع ملک', (i) => i.type],
                ['منطقه', (i) => i.district],
                ['اتاق', (i) => i.rooms],
                ['طبقه', (i) => i.floor],
                ['سال ساخت', (i) => i.year_built],
                ['امکانات', (i) => i.features.join('، ') || '—'],
                ['تأیید شده', (i) => i.verified ? '✅' : '—'],
            ];

            let tableHtml = '<table class="moaveze-compare-table"><tbody>';
            rows.forEach(([label, getter]) => {
                tableHtml += `<tr><th>${label}</th>${items.map((i) => `<td>${getter(i)}</td>`).join('')}</tr>`;
            });
            tableHtml += '</tbody></table>';

            const html = `
                <div class="moaveze-modal-overlay active">
                    <div class="moaveze-modal" style="max-width:900px;">
                        <div class="moaveze-modal-header">
                            <h3>مقایسه آگهی‌ها</h3>
                            <button class="moaveze-modal-close">&times;</button>
                        </div>
                        <div class="moaveze-modal-body">${tableHtml}</div>
                    </div>
                </div>`;
            $('body').append(html);
            $('body').css('overflow', 'hidden');
        },

        /* ===================== QR Code ===================== */
        initQrCode() {
            $(document).on('click', '.moaveze-qr-btn', (e) => {
                const url = $(e.currentTarget).data('url');
                const title = $(e.currentTarget).data('title');
                this.openQrModal(url, title);
            });
        },

        openQrModal(url, title) {
            $('.moaveze-modal-overlay').remove();
            // Uses the free, no-API-key QR image generation service so
            // no extra library/dependency is required in the plugin.
            const qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' + encodeURIComponent(url);

            const html = `
                <div class="moaveze-modal-overlay active">
                    <div class="moaveze-modal" style="max-width:360px;text-align:center;">
                        <div class="moaveze-modal-header">
                            <h3>QR Code آگهی</h3>
                            <button class="moaveze-modal-close">&times;</button>
                        </div>
                        <div class="moaveze-modal-body">
                            <img src="${qrImgUrl}" alt="QR Code" style="width:100%;max-width:260px;border-radius:8px;border:1px solid #e2e8f0;">
                            <p style="font-size:13px;color:#64748b;margin-top:12px;">${title}</p>
                            <a href="${qrImgUrl}" download="qr-${title}.png" class="moaveze-btn moaveze-btn-primary" style="margin-top:10px;">دانلود تصویر</a>
                        </div>
                    </div>
                </div>`;
            $('body').append(html);
            $('body').css('overflow', 'hidden');
        }
    };

    $(document).ready(() => MoavezeAddons.init());

})(jQuery);
