/**
 * Moaveze Plus - Admin JS
 */

(function($) {
    'use strict';

    const MoavezeAdmin = {
        init() {
            this.initColorPicker();
            this.initZoomSlider();
            this.initMatchingButton();
            this.initChainDetection();
            this.initConsultantActions();
            this.initImportHouzez();
        },

        /**
         * Color picker initialization
         */
        initColorPicker() {
            if ($.fn.wpColorPicker) {
                $('.moaveze-color-picker').wpColorPicker();
            }
        },

        /**
         * Map zoom slider value display
         */
        initZoomSlider() {
            $('#moaveze_map_zoom').on('input', function() {
                $('#zoom_value').text($(this).val());
            });
        },

        /**
         * Run matching algorithm
         */
        initMatchingButton() {
            $('#run-matching-algo').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('در حال اجرا...');

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_run_matching',
                        nonce: moavezeAdmin.nonce,
                    },
                    success(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('خطا: ' + (response.data || 'مشکلی پیش آمد'));
                        }
                    },
                    error() {
                        alert('خطا در ارتباط با سرور');
                    },
                    complete() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> اجرای الگوریتم تطبیق');
                    }
                });
            });
        },

        /**
         * Chain detection button
         */
        initChainDetection() {
            $('#detect-chains-btn').on('click', function() {
                const $btn = $(this);
                $btn.prop('disabled', true).text('در حال شناسایی...');

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_detect_chains',
                        nonce: moavezeAdmin.nonce,
                    },
                    success(response) {
                        if (response.success) {
                            alert(response.data.message || 'عملیات انجام شد');
                            location.reload();
                        }
                    },
                    complete() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> شناسایی زنجیره‌های جدید');
                    }
                });
            });
        },

        /**
         * Consultant actions (verify/reject)
         */
        initConsultantActions() {
            // Verify exchange
            $(document).on('click', '.verify-exchange-btn', function() {
                const postId = $(this).data('post-id');
                if (!confirm('آیا این آگهی را تأیید می‌کنید؟')) return;

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_verify_exchange',
                        nonce: moavezeAdmin.nonce,
                        post_id: postId,
                    },
                    success(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });

            // Reject exchange
            $(document).on('click', '.reject-exchange-btn', function() {
                const postId = $(this).data('post-id');
                const reason = prompt('دلیل رد آگهی:');
                if (reason === null) return;

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_reject_exchange',
                        nonce: moavezeAdmin.nonce,
                        post_id: postId,
                        reason: reason,
                    },
                    success(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });

            // Assign consultant
            $(document).on('click', '.assign-consultant-btn', function() {
                const matchId = $(this).data('match-id');
                // Simple prompt for now - can be enhanced with a modal
                const consultantId = prompt('شناسه مشاور:');
                if (!consultantId) return;

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_assign_consultant',
                        nonce: moavezeAdmin.nonce,
                        match_id: matchId,
                        consultant_id: consultantId,
                    },
                    success(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        }
                    }
                });
            });

            // Add consultant role
            $('#add-consultant-btn').on('click', function() {
                const userId = $('#user-to-consultant').val();
                if (!userId) {
                    alert('لطفاً کاربری را انتخاب کنید');
                    return;
                }

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_add_consultant_role',
                        nonce: moavezeAdmin.nonce,
                        user_id: userId,
                    },
                    success(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });

            // Remove consultant
            $(document).on('click', '.remove-consultant-btn', function() {
                if (!confirm('آیا مطمئن هستید؟')) return;
                const userId = $(this).data('user-id');

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_remove_consultant_role',
                        nonce: moavezeAdmin.nonce,
                        user_id: userId,
                    },
                    success(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });
        },

        /**
         * Import from Houzez
         */
        initImportHouzez() {
            $('#moaveze_import_houzez').on('click', function() {
                if (!confirm('آیا ملک‌های فعلی Houzez به سیستم معاوضه اضافه شوند؟')) return;

                const $btn = $(this);
                $btn.prop('disabled', true).text('در حال واردسازی...');

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_import_from_houzez',
                        nonce: moavezeAdmin.nonce,
                    },
                    success(response) {
                        if (response.success) {
                            alert(response.data.message);
                        } else {
                            alert('خطا: ' + (response.data.message || 'مشکلی پیش آمد'));
                        }
                    },
                    complete() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> وارد کردن ملک‌های موجود');
                    }
                });
            });

            // Add single property to exchange
            $(document).on('click', '.moaveze-add-to-exchange', function() {
                const $btn = $(this);
                const propertyId = $btn.data('property-id');

                $btn.prop('disabled', true).text('...');

                $.ajax({
                    url: moavezeAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'moaveze_import_from_houzez',
                        nonce: moavezeAdmin.nonce,
                        property_id: propertyId,
                    },
                    success(response) {
                        if (response.success) {
                            $btn.replaceWith('<span class="dashicons dashicons-yes-alt" style="color:#10b981;"></span>');
                        } else {
                            $btn.prop('disabled', false).text('افزودن');
                        }
                    }
                });
            });
        }
    };

    $(document).ready(() => MoavezeAdmin.init());

})(jQuery);
