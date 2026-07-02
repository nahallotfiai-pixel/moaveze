/**
 * Moaveze Plus - Multi-Step Form JS
 */

(function($) {
    'use strict';

    const MoavezeForm = {
        currentStep: 1,
        totalSteps: 5,
        uploadedImages: [],
        map: null,
        marker: null,

        init() {
            this.bindEvents();
            this.initMap();
            this.initImageUpload();
            this.updateProgress();
        },

        /**
         * Bind all events
         */
        bindEvents() {
            // Navigation
            $('#next-step').on('click', () => this.nextStep());
            $('#prev-step').on('click', () => this.prevStep());
            $('#moaveze-exchange-form').on('submit', (e) => this.submitForm(e));

            // Exchange type conditional fields
            $('input[name="exchange_type"]').on('change', (e) => this.toggleConditionals(e.target.value));

            // Toggle buttons
            $('.moaveze-toggle-btn').on('click', function() {
                $(this).siblings().removeClass('active');
                $(this).addClass('active');
            });

            // Add alternative condition
            $('#add-condition-btn').on('click', () => this.addAlternativeCondition());

            // Price formatting in real-time
            $(document).on('input', '#property_value', function() {
                const raw = $(this).val().replace(/[^\d]/g, '');
                if (raw) {
                    $('#value-hint').text(MoavezePlus.priceToText(parseInt(raw)));
                }
            });
        },

        /**
         * Go to next step
         */
        nextStep() {
            if (!this.validateStep(this.currentStep)) return;

            if (this.currentStep < this.totalSteps) {
                this.goToStep(this.currentStep + 1);
            }
        },

        /**
         * Go to previous step
         */
        prevStep() {
            if (this.currentStep > 1) {
                this.goToStep(this.currentStep - 1);
            }
        },

        /**
         * Navigate to specific step
         */
        goToStep(step) {
            // Mark current as completed
            $(`.moaveze-step[data-step="${this.currentStep}"]`).addClass('completed').removeClass('active');

            // Hide current step content
            $(`.moaveze-form-step[data-step="${this.currentStep}"]`).removeClass('active');

            // Update step
            this.currentStep = step;

            // Show new step content
            $(`.moaveze-form-step[data-step="${step}"]`).addClass('active');
            $(`.moaveze-step[data-step="${step}"]`).addClass('active').removeClass('completed');

            // Update navigation buttons
            this.updateNavigation();
            this.updateProgress();

            // Initialize map if step 3
            if (step === 3 && this.map) {
                setTimeout(() => this.map.invalidateSize(), 100);
            }

            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $('.moaveze-form-header').offset().top - 20
            }, 300);
        },

        /**
         * Update navigation buttons
         */
        updateNavigation() {
            if (this.currentStep === 1) {
                $('#prev-step').hide();
            } else {
                $('#prev-step').show();
            }

            if (this.currentStep === this.totalSteps) {
                $('#next-step').hide();
                $('#submit-form').show();
            } else {
                $('#next-step').show();
                $('#submit-form').hide();
            }
        },

        /**
         * Update progress bar
         */
        updateProgress() {
            const percent = (this.currentStep / this.totalSteps) * 100;
            $('#progress-fill').css('width', percent + '%');
        },

        /**
         * Validate current step
         */
        validateStep(step) {
            const $step = $(`.moaveze-form-step[data-step="${step}"]`);
            let valid = true;

            // Remove previous errors
            $step.find('.moaveze-field-error').remove();
            $step.find('.field-error').removeClass('field-error');

            // Check required fields
            $step.find('[required]').each(function() {
                if (!$(this).val() || $(this).val().trim() === '') {
                    valid = false;
                    $(this).addClass('field-error');
                    $(this).after('<span class="moaveze-field-error">این فیلد الزامی است</span>');
                }
            });

            // Step-specific validations
            if (step === 1) {
                const value = $('#property_value').val().replace(/[^\d]/g, '');
                if (value && parseInt(value) < 100000000) {
                    valid = false;
                    $('#property_value').addClass('field-error');
                    $('#property_value').after('<span class="moaveze-field-error">ارزش ملک باید حداقل ۱۰۰ میلیون تومان باشد</span>');
                }
            }

            if (step === 2) {
                // Images are optional but recommended
                if (this.uploadedImages.length === 0) {
                    // Just warn, don't block
                    if (!confirm('آیا بدون تصویر ادامه می‌دهید؟ تصاویر شانس معاوضه موفق را افزایش می‌دهد.')) {
                        valid = false;
                    }
                }
            }

            if (step === 5) {
                // Phone validation
                const phone = $('#contact_phone').val();
                if (phone && !/^09[0-9]{9}$/.test(phone)) {
                    valid = false;
                    $('#contact_phone').addClass('field-error');
                    $('#contact_phone').after('<span class="moaveze-field-error">شماره تماس معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷)</span>');
                }

                // Terms
                if (!$('#accept_terms').is(':checked')) {
                    valid = false;
                    MoavezePlus.showToast('لطفاً قوانین و مقررات را بپذیرید', 'error');
                }
            }

            if (!valid) {
                // Shake animation
                $step.find('.step-content').css('animation', 'none');
                setTimeout(() => {
                    $step.find('.step-content').css('animation', 'shake 0.5s');
                }, 10);
            }

            return valid;
        },

        /**
         * Toggle conditional fields based on exchange type
         */
        toggleConditionals(type) {
            // Hide all conditional sections
            $('.moaveze-conditional').removeClass('visible');

            // Always show desired property section
            $('#cond-desired-property').addClass('visible');

            switch(type) {
                case 'property_only':
                    break;
                case 'property_cash':
                    $('#cond-cash').addClass('visible');
                    break;
                case 'property_car':
                    $('#cond-assets').addClass('visible');
                    break;
                case 'property_mixed':
                    $('#cond-cash').addClass('visible');
                    $('#cond-assets').addClass('visible');
                    break;
                case 'flexible':
                    $('#cond-cash').addClass('visible');
                    $('#cond-assets').addClass('visible');
                    break;
            }
        },

        /**
         * Initialize Leaflet map
         */
        initMap() {
            if (!$('#submission-map').length) return;

            const centerLat = parseFloat(moavezePlus.mapCenter.lat) || 38.0962;
            const centerLng = parseFloat(moavezePlus.mapCenter.lng) || 46.2738;
            const zoom = parseInt(moavezePlus.mapZoom) || 12;

            this.map = L.map('submission-map').setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(this.map);

            // Click to place marker
            this.map.on('click', (e) => {
                const { lat, lng } = e.latlng;

                if (this.marker) {
                    this.marker.setLatLng([lat, lng]);
                } else {
                    this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

                    this.marker.on('dragend', () => {
                        const pos = this.marker.getLatLng();
                        $('#latitude').val(pos.lat.toFixed(6));
                        $('#longitude').val(pos.lng.toFixed(6));
                    });
                }

                $('#latitude').val(lat.toFixed(6));
                $('#longitude').val(lng.toFixed(6));
            });
        },

        /**
         * Initialize image upload with drag & drop
         */
        initImageUpload() {
            const $area = $('#upload-area');
            const $input = $('#property_images');
            const $preview = $('#image-preview');

            // Drag & Drop
            $area.on('dragover dragenter', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });

            $area.on('dragleave drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            $area.on('drop', (e) => {
                const files = e.originalEvent.dataTransfer.files;
                this.handleFiles(files);
            });

            // Click to upload
            $input.on('change', (e) => {
                this.handleFiles(e.target.files);
            });
        },

        /**
         * Handle file uploads
         */
        handleFiles(files) {
            Array.from(files).forEach(file => {
                if (!file.type.match('image.*')) {
                    MoavezePlus.showToast('فقط فایل‌های تصویری مجاز هستند', 'error');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    MoavezePlus.showToast('حجم فایل نباید بیشتر از ۵ مگابایت باشد', 'error');
                    return;
                }

                this.uploadImage(file);
            });
        },

        /**
         * Upload single image via AJAX
         */
        uploadImage(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'moaveze_upload_image');
            formData.append('nonce', moavezePlus.nonce);

            // Show preview immediately
            const reader = new FileReader();
            const tempId = 'temp-' + Date.now();

            reader.onload = (e) => {
                $('#image-preview').append(`
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
                            const percent = (e.loaded / e.total) * 100;
                            $(`#${tempId} .progress-bar`).css('width', percent + '%');
                        }
                    });
                    return xhr;
                },
                success: (response) => {
                    if (response.success) {
                        const item = $(`#${tempId}`);
                        item.find('.upload-progress').remove();
                        item.append(`<button type="button" class="remove-image" data-id="${response.data.id}">&times;</button>`);
                        item.attr('data-attachment-id', response.data.id);
                        this.uploadedImages.push(response.data.id);

                        // Bind remove
                        item.find('.remove-image').on('click', function() {
                            const id = $(this).data('id');
                            MoavezeForm.uploadedImages = MoavezeForm.uploadedImages.filter(i => i !== id);
                            $(this).closest('.image-item').remove();
                        });
                    } else {
                        $(`#${tempId}`).remove();
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
         * Add alternative condition
         */
        addAlternativeCondition() {
            const index = $('#alternative-conditions .alt-condition').length;
            const html = `
                <div class="alt-condition" data-index="${index}">
                    <button type="button" class="remove-condition" onclick="this.closest('.alt-condition').remove()">&times;</button>
                    <div class="moaveze-field-group moaveze-field-grid-2">
                        <div class="moaveze-field">
                            <label>نوع شرط</label>
                            <select name="alt_conditions[${index}][type]">
                                <option value="property_cash">ملک + نقد</option>
                                <option value="property_car">ملک + خودرو</option>
                                <option value="property_mixed">ترکیبی</option>
                                <option value="custom">سفارشی</option>
                            </select>
                        </div>
                        <div class="moaveze-field">
                            <label>ارزش پیشنهادی (تومان)</label>
                            <input type="text" name="alt_conditions[${index}][value]" class="moaveze-price-input" placeholder="مبلغ">
                        </div>
                    </div>
                    <div class="moaveze-field" style="margin-top:10px;">
                        <label>توضیحات شرط</label>
                        <textarea name="alt_conditions[${index}][description]" rows="2" placeholder="مثال: آپارتمان + ۵ میلیارد نقد + یک دستگاه خودرو"></textarea>
                    </div>
                </div>
            `;
            $('#alternative-conditions').append(html);
        },

        /**
         * Submit form
         */
        submitForm(e) {
            e.preventDefault();

            if (!this.validateStep(this.currentStep)) return;

            const $form = $('#moaveze-exchange-form');
            const $submitBtn = $('#submit-form');

            // Disable button
            $submitBtn.prop('disabled', true).html(`
                <svg class="moaveze-spinner" width="16" height="16" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" values="0 12 12;360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg>
                در حال ارسال...
            `);

            // Prepare form data
            const formData = $form.serializeArray();

            // Add uploaded images
            this.uploadedImages.forEach(id => {
                formData.push({ name: 'uploaded_images[]', value: id });
            });

            // Clean price values
            formData.forEach(item => {
                if (item.name === 'property_value' || item.name === 'desired_min_value' ||
                    item.name === 'desired_max_value' || item.name === 'cash_difference') {
                    item.value = item.value.replace(/[^\d]/g, '');
                }
            });

            $.ajax({
                url: moavezePlus.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        // Show success state
                        $form.hide();
                        $('.moaveze-form-header').hide();
                        $('#success-state').show();
                        MoavezePlus.showToast(response.data.message, 'success');
                    } else {
                        MoavezePlus.showToast(response.data.message || 'خطایی رخ داد', 'error');
                        $submitBtn.prop('disabled', false).html(`
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            ثبت آگهی معاوضه
                        `);
                    }
                },
                error: () => {
                    MoavezePlus.showToast('خطا در ارتباط با سرور', 'error');
                    $submitBtn.prop('disabled', false).html(`
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        ثبت آگهی معاوضه
                    `);
                }
            });
        }
    };

    // Initialize
    $(document).ready(() => MoavezeForm.init());
    window.MoavezeForm = MoavezeForm;

})(jQuery);
