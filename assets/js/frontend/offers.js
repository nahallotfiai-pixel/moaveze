/**
 * Moaveze Plus - Offers Modal & My Offers JS
 */

(function($) {
    'use strict';

    const MoavezeOffers = {
        currentFilter: 'all',

        init() {
            this.bindEvents();
            this.loadMyOffers();
        },

        bindEvents() {
            // Open offer modal from single page
            $(document).on('click', '#send-offer-btn', (e) => {
                const exchangeId = $(e.currentTarget).data('exchange-id');
                this.openOfferModal(exchangeId);
            });

            // Close modal
            $(document).on('click', '.moaveze-modal-close, .moaveze-modal-overlay', (e) => {
                if (e.target === e.currentTarget) this.closeModal();
            });

            // ESC key closes modal
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') this.closeModal();
            });

            // Submit offer
            $(document).on('submit', '#offer-form', (e) => {
                e.preventDefault();
                this.submitOffer(e.target);
            });

            // Filter offers by status
            $(document).on('click', '.offer-stat-card', (e) => {
                const filter = $(e.currentTarget).data('filter');
                this.filterOffers(filter);
                $('.offer-stat-card').removeClass('active');
                $(e.currentTarget).addClass('active');
            });

            // Withdraw offer
            $(document).on('click', '.withdraw-offer-btn', (e) => {
                const offerId = $(e.currentTarget).data('offer-id');
                if (confirm('آیا مطمئنید که می‌خواهید پیشنهاد خود را لغو کنید؟')) {
                    this.withdrawOffer(offerId);
                }
            });
        },

        /**
         * Open offer modal
         */
        openOfferModal(exchangeId) {
            // Remove existing modal
            $('.moaveze-modal-overlay').remove();

            const modalHtml = `
                <div class="moaveze-modal-overlay active">
                    <div class="moaveze-modal">
                        <div class="moaveze-modal-header">
                            <h3>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                                ارسال پیشنهاد معاوضه
                            </h3>
                            <button class="moaveze-modal-close">&times;</button>
                        </div>
                        <form id="offer-form" class="moaveze-modal-body">
                            <input type="hidden" name="exchange_id" value="${exchangeId}">

                            <div class="offer-form-section">
                                <h4>نوع پیشنهاد شما</h4>
                                <div class="offer-type-selector">
                                    <label class="offer-type-option">
                                        <input type="radio" name="offer_type" value="exchange_with_cash" checked>
                                        <div class="option-card">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                            <strong>ملک + نقد</strong>
                                            <small>ملک خود + مابه‌التفاوت</small>
                                        </div>
                                    </label>
                                    <label class="offer-type-option">
                                        <input type="radio" name="offer_type" value="exchange_with_assets">
                                        <div class="option-card">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                                            <strong>ملک + دارایی</strong>
                                            <small>ملک + خودرو یا دارایی</small>
                                        </div>
                                    </label>
                                    <label class="offer-type-option">
                                        <input type="radio" name="offer_type" value="direct">
                                        <div class="option-card">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                                            <strong>معاوضه مستقیم</strong>
                                            <small>فقط ملک با ملک</small>
                                        </div>
                                    </label>
                                    <label class="offer-type-option">
                                        <input type="radio" name="offer_type" value="custom">
                                        <div class="option-card">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/></svg>
                                            <strong>پیشنهاد سفارشی</strong>
                                            <small>شرایط خودتان</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="offer-form-section">
                                <h4>مبلغ نقدی پیشنهادی (تومان)</h4>
                                <input type="text" name="cash_offered" class="moaveze-price-input" 
                                       placeholder="مبلغی که حاضرید پرداخت کنید" style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;">
                            </div>

                            <div class="offer-form-section">
                                <h4>دارایی‌های دیگر (خودرو، زمین و...)</h4>
                                <textarea name="assets_offered" rows="2" placeholder="مثال: یک دستگاه BMW X3 مدل ۲۰۲۲" 
                                          style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;resize:vertical;"></textarea>
                            </div>

                            <div class="offer-form-section">
                                <h4>توضیحات پیشنهاد</h4>
                                <textarea name="message" rows="3" placeholder="توضیحات تکمیلی درباره پیشنهاد شما..." required
                                          style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;resize:vertical;"></textarea>
                            </div>

                            <div class="moaveze-modal-footer" style="padding:0;border:none;margin-top:20px;">
                                <button type="button" class="moaveze-btn moaveze-btn-secondary moaveze-modal-close">انصراف</button>
                                <button type="submit" class="moaveze-btn moaveze-btn-primary" id="submit-offer-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                    </svg>
                                    ارسال پیشنهاد
                                </button>
                            </div>
                        </form>
                    </div>
                </div>`;

            $('body').append(modalHtml);
            $('body').css('overflow', 'hidden');
        },

        closeModal() {
            $('.moaveze-modal-overlay').removeClass('active');
            setTimeout(() => $('.moaveze-modal-overlay').remove(), 300);
            $('body').css('overflow', '');
        },

        /**
         * Submit offer via AJAX
         */
        submitOffer(form) {
            const $btn = $('#submit-offer-btn');
            $btn.prop('disabled', true).html('<div class="loading-spinner" style="width:16px;height:16px;border-width:2px;margin:0;display:inline-block;vertical-align:middle;"></div> در حال ارسال...');

            const formData = $(form).serializeArray();
            // Clean price
            formData.forEach(item => {
                if (item.name === 'cash_offered') {
                    item.value = item.value.replace(/[^\d]/g, '');
                }
            });

            formData.push({ name: 'action', value: 'moaveze_send_offer' });
            formData.push({ name: 'nonce', value: moavezePlus.nonce });

            $.post(moavezePlus.ajaxUrl, $.param(formData), (response) => {
                if (response.success) {
                    this.closeModal();
                    MoavezePlus.showToast(response.data.message, 'success');
                } else {
                    MoavezePlus.showToast(response.data.message || 'خطایی رخ داد', 'error');
                    $btn.prop('disabled', false).html('ارسال پیشنهاد');
                }
            }).fail(() => {
                MoavezePlus.showToast('خطا در ارتباط با سرور', 'error');
                $btn.prop('disabled', false).html('ارسال پیشنهاد');
            });
        },

        /**
         * Load my offers
         */
        loadMyOffers() {
            if (!$('#offers-list').length) return;

            $.post(moavezePlus.ajaxUrl, {
                action: 'moaveze_get_my_offers',
                nonce: moavezePlus.nonce,
            }, (response) => {
                if (response.success) {
                    this.renderOffers(response.data.offers);
                    this.updateStats(response.data.stats);
                }
            });
        },

        renderOffers(offers) {
            const $list = $('#offers-list');
            if (!offers.length) {
                $list.html(`<div class="moaveze-empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    <h3>هنوز پیشنهادی ارسال نکرده‌اید</h3>
                    <p>با مشاهده آگهی‌های معاوضه و ارسال پیشنهاد شروع کنید.</p>
                </div>`);
                return;
            }

            let html = '';
            offers.forEach(offer => {
                if (this.currentFilter !== 'all' && offer.status !== this.currentFilter) return;
                html += this.renderOfferItem(offer);
            });

            $list.html(html || '<div class="moaveze-empty-state"><p>موردی با این فیلتر یافت نشد.</p></div>');
        },

        renderOfferItem(offer) {
            return `
                <div class="moaveze-offer-item" data-status="${offer.status}">
                    <div class="offer-item-image">
                        ${offer.image ? `<img src="${offer.image}" alt="">` : '<div style="background:#f1f5f9;width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;">🏠</div>'}
                    </div>
                    <div class="offer-item-body">
                        <div class="offer-item-title">
                            <a href="${offer.url}">${offer.title}</a>
                        </div>
                        <div class="offer-item-meta">${offer.property_type} | ${offer.district} | ${MoavezePlus.formatPriceShort(offer.property_value)}</div>
                        <div class="offer-item-details">
                            ${offer.cash_offered ? `<span class="offer-detail-item">💰 ${MoavezePlus.formatPriceShort(offer.cash_offered)}</span>` : ''}
                            ${offer.assets_offered ? `<span class="offer-detail-item">🚗 ${offer.assets_offered.substring(0, 40)}</span>` : ''}
                        </div>
                        ${offer.admin_notes ? `<div style="margin-top:8px;padding:8px 12px;background:#f0fdf4;border-radius:8px;font-size:12px;color:#166534;">💬 ${offer.admin_notes}</div>` : ''}
                    </div>
                    <div class="offer-item-actions">
                        <span class="offer-status-badge badge-${offer.status_color}">${offer.status_label}</span>
                        <span class="offer-date" title="${MoavezePlus.toJalali(offer.date, true)}">${MoavezePlus.toJalali(offer.date)}</span>
                        ${offer.status === 'pending' ? `<button class="moaveze-btn moaveze-btn-ghost moaveze-btn-sm withdraw-offer-btn" data-offer-id="${offer.id}" style="margin-top:6px;">لغو</button>` : ''}
                    </div>
                </div>`;
        },

        updateStats(stats) {
            $('#stat-total').text(stats.total);
            $('#stat-pending').text(stats.pending);
            $('#stat-accepted').text(stats.accepted);
            $('#stat-negotiating').text(stats.negotiating);
            $('#stat-rejected').text(stats.rejected);
        },

        filterOffers(status) {
            this.currentFilter = status;
            this.loadMyOffers();
        },

        withdrawOffer(offerId) {
            $.post(moavezePlus.ajaxUrl, {
                action: 'moaveze_withdraw_offer',
                nonce: moavezePlus.nonce,
                offer_id: offerId,
            }, (response) => {
                if (response.success) {
                    MoavezePlus.showToast(response.data.message, 'success');
                    this.loadMyOffers();
                } else {
                    MoavezePlus.showToast(response.data.message || 'خطا', 'error');
                }
            });
        }
    };

    $(document).ready(() => MoavezeOffers.init());
    window.MoavezeOffers = MoavezeOffers;

})(jQuery);
