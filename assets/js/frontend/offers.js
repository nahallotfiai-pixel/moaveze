/**
 * Moaveze Plus - Offers Modal & My Offers JS
 */

(function($) {
    'use strict';

    const MoavezeOffers = {
        currentFilter: 'all',
        rpUploadedImages: [], // images uploaded via the reciprocal-listing mini-form

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

            // Toggle the inline "register your property" mini-form
            $(document).on('change', '#register-property-checkbox', function() {
                const $fields = $('#register-property-fields');
                if ($(this).is(':checked')) {
                    $fields.slideDown(200);
                    // Require the core fields only when the section is open
                    $fields.find('[name="reg_title"], [name="reg_phone"], [name="reg_property_type"], [name="reg_district"], [name="reg_value"], [name="reg_area"]').prop('required', true);
                } else {
                    $fields.slideUp(200);
                    $fields.find('[required]').prop('required', false);
                }
            });

            // Visual selected-state on the visibility choice cards
            $(document).on('change', 'input[name="reg_visibility"]', function() {
                $('.rp-visibility-option').removeClass('selected');
                $(this).closest('.rp-visibility-option').addClass('selected');
            });

            // ===== Reciprocal-listing image upload (drag & drop + click) =====
            $(document).on('dragover dragenter', '#rp-upload-area', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            $(document).on('dragleave drop', '#rp-upload-area', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            $(document).on('drop', '#rp-upload-area', (e) => {
                this.handleRpFiles(e.originalEvent.dataTransfer.files);
            });
            $(document).on('change', '#rp-property-images', (e) => {
                this.handleRpFiles(e.target.files);
            });
            // Remove an uploaded reciprocal-listing image
            $(document).on('click', '#rp-image-preview .remove-image', function() {
                const id = $(this).data('id');
                MoavezeOffers.rpUploadedImages = MoavezeOffers.rpUploadedImages.filter((i) => i !== id);
                $(this).closest('.image-item').remove();
            });
        },

        /**
         * Validate + upload each selected/dropped file for the reciprocal
         * listing mini-form, reusing the exact same
         * 'moaveze_upload_image' AJAX endpoint the main submission form
         * (form.js) already uses - same size/type limits, same response
         * shape ({id, url, thumb}).
         */
        handleRpFiles(files) {
            Array.from(files).forEach((file) => {
                if (!file.type.match('image.*')) {
                    MoavezePlus.showToast('فقط فایل‌های تصویری مجاز هستند', 'error');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    MoavezePlus.showToast('حجم فایل نباید بیشتر از ۵ مگابایت باشد', 'error');
                    return;
                }
                this.uploadRpImage(file);
            });
        },

        uploadRpImage(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'moaveze_upload_image');
            formData.append('nonce', moavezePlus.nonce);

            const tempId = 'rp-temp-' + Date.now() + '-' + Math.random().toString(36).slice(2);
            const reader = new FileReader();
            reader.onload = (e) => {
                $('#rp-image-preview').append(`
                    <div class="image-item" id="${tempId}">
                        <img src="${e.target.result}" alt="">
                        <div class="upload-progress"><div class="progress-bar"></div></div>
                    </div>
                `);
            };
            reader.readAsDataURL(file);

            $.ajax({
                url: moavezePlus.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            $(`#${tempId} .progress-bar`).css('width', (e.loaded / e.total * 100) + '%');
                        }
                    });
                    return xhr;
                },
                success: (response) => {
                    const $item = $(`#${tempId}`);
                    if (response.success) {
                        $item.find('.upload-progress').remove();
                        $item.append(`<button type="button" class="remove-image" data-id="${response.data.id}">&times;</button>`);
                        this.rpUploadedImages.push(response.data.id);
                    } else {
                        $item.remove();
                        MoavezePlus.showToast(response.data.message || 'خطا در آپلود', 'error');
                    }
                },
                error: () => {
                    $(`#${tempId}`).remove();
                    MoavezePlus.showToast('خطا در آپلود تصویر', 'error');
                }
            });
        },

        /**
         * Open offer modal
         */
        openOfferModal(exchangeId) {
            // Remove existing modal
            $('.moaveze-modal-overlay').remove();
            this.rpUploadedImages = [];

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

                            <!-- ===== NEW: Register-your-property-inline ===== -->
                            <div class="offer-form-section register-property-section">
                                <label class="register-property-toggle">
                                    <input type="checkbox" id="register-property-checkbox" name="register_property" value="1">
                                    <span>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                        ملک خودم را هم برای معاوضه ثبت کنم
                                    </span>
                                </label>

                                <div id="register-property-fields" style="display:none;">
                                    <p class="rp-hint">با ثبت مشخصات ملک خودتان، پیشنهاد شما معتبرتر دیده می‌شود و شانس موفقیت معاوضه بالاتر می‌رود.</p>

                                    <div class="rp-field-grid-2">
                                        <div class="rp-field">
                                            <label>عنوان ملک</label>
                                            <input type="text" name="reg_title" placeholder="مثال: آپارتمان ۱۲۰ متری در رشدیه">
                                        </div>
                                        <div class="rp-field">
                                            <label>شماره تماس</label>
                                            <input type="tel" name="reg_phone" placeholder="۰۹۱۲۱۲۳۴۵۶۷" dir="ltr">
                                        </div>
                                    </div>
                                    <div class="rp-field-grid-2">
                                        <div class="rp-field">
                                            <label>نوع ملک</label>
                                            <select name="reg_property_type" id="reg-property-type"><option value="">انتخاب کنید</option></select>
                                        </div>
                                        <div class="rp-field">
                                            <label>منطقه</label>
                                            <select name="reg_district" id="reg-district" class="moaveze-searchable-select" data-placeholder="جستجوی منطقه..."><option value="">انتخاب کنید</option></select>
                                        </div>
                                    </div>
                                    <div class="rp-field-grid-2">
                                        <div class="rp-field">
                                            <label>ارزش ملک (تومان)</label>
                                            <input type="text" name="reg_value" class="moaveze-price-input" placeholder="مثال: 14000000000">
                                        </div>
                                        <div class="rp-field">
                                            <label>متراژ (متر مربع)</label>
                                            <input type="number" name="reg_area" placeholder="مثال: 120">
                                        </div>
                                    </div>

                                    <!-- ===== NEW: Media upload (previously missing entirely) ===== -->
                                    <div class="rp-field rp-field-full">
                                        <label>تصاویر ملک (اختیاری)</label>
                                        <div class="moaveze-upload-area rp-upload-area" id="rp-upload-area">
                                            <div class="upload-placeholder">
                                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                    <polyline points="21 15 16 10 5 21"></polyline>
                                                </svg>
                                                <span>تصاویر را بکشید یا کلیک کنید</span>
                                                <small>حداکثر ۵ مگابایت هر فایل</small>
                                            </div>
                                            <input type="file" id="rp-property-images" multiple accept="image/jpeg,image/png,image/webp" class="upload-input">
                                        </div>
                                        <div class="moaveze-image-preview rp-image-preview" id="rp-image-preview"></div>
                                    </div>

                                    <div class="rp-field rp-field-full">
                                        <label>لینک ویدیوی ملک (اختیاری)</label>
                                        <input type="url" name="reg_video_url" placeholder="لینک آپارات، یوتیوب یا هر ویدیوی دیگر" dir="ltr">
                                        <small class="rp-hint" style="margin:6px 0 0;">می‌توانید لینک ویدیوی معرفی ملک خود را از آپارات، یوتیوب یا هر سرویس دیگر وارد کنید.</small>
                                    </div>

                                    <!-- The key question the user asked for -->
                                    <div class="rp-visibility-choice">
                                        <label class="rp-visibility-question">این ملک فقط برای همین پیشنهاد ثبت شود یا برای معاوضه‌های مشابه دیگر هم در نظر گرفته شود؟</label>
                                        <div class="rp-visibility-options">
                                            <label class="rp-visibility-option">
                                                <input type="radio" name="reg_visibility" value="private" checked>
                                                <div class="rvo-card">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                                    <strong>فقط همین پیشنهاد</strong>
                                                    <small>ملک شما جای دیگری نمایش داده یا پیشنهاد داده نمی‌شود</small>
                                                </div>
                                            </label>
                                            <label class="rp-visibility-option">
                                                <input type="radio" name="reg_visibility" value="public">
                                                <div class="rvo-card">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 010 20 15.3 15.3 0 010-20z"/></svg>
                                                    <strong>برای موارد مشابه هم ثبت شود</strong>
                                                    <small>در سیستم تطبیق هوشمند شرکت می‌کند و ممکن است پیشنهادهای دیگری هم دریافت کنید</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
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
            this.populateRegisterPropertySelects();
        },

        /**
         * Fill the "ثبت ملک من" mini-form's property-type/district
         * <select> options from the data already localized in
         * moavezePlus.propertyTypes / moavezePlus.districts (see
         * moaveze-plus.php enqueue_frontend_assets()) - avoids an extra
         * AJAX round trip just to populate two dropdowns.
         */
        populateRegisterPropertySelects() {
            const $type = $('#reg-property-type');
            const $district = $('#reg-district');

            (moavezePlus.propertyTypes || []).forEach((t) => {
                $type.append(`<option value="${t.slug}">${t.name}</option>`);
            });
            (moavezePlus.districts || []).forEach((d) => {
                $district.append(`<option value="${d.slug}">${d.name}</option>`);
            });

            // The district <select> is created dynamically (modal markup
            // is injected via JS), so it doesn't exist yet when
            // MoavezePlus.init() ran on page load - initialize the
            // searchable combobox for it now that its options exist.
            if (window.MoavezePlus && MoavezePlus.initSearchableSelects) {
                MoavezePlus.initSearchableSelects();
            }
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
            // Clean price fields (also normalizes stray Persian digits,
            // same fix applied to the main submission form - see form.js)
            formData.forEach(item => {
                if (item.name === 'cash_offered' || item.name === 'reg_value') {
                    item.value = MoavezePlus.toLatinDigits(item.value).replace(/[^\d]/g, '');
                }
            });

            // Attach any images uploaded via the reciprocal-listing
            // mini-form (gallery), so create_reciprocal_listing() on the
            // server can build the new listing's gallery + thumbnail.
            this.rpUploadedImages.forEach((id) => {
                formData.push({ name: 'reg_images[]', value: id });
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
