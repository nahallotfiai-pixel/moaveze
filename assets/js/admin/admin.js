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
            this.initConnectPartiesModal();
        },

        /**
         * Update (or hide) the pending-count badge next to the dashboard
         * "در انتظار تأیید" heading, and show the empty state once the
         * last card has been approved/rejected.
         */
        updatePendingCountBadge() {
            const remaining = $('#pending-listings-grid .pending-listing-card').length;
            const $badge = $('.pending-count-badge');
            if (remaining > 0) {
                $badge.text(remaining);
            } else {
                $badge.remove();
                $('#pending-listings-grid').replaceWith(
                    '<div class="moaveze-empty-inline"><span class="dashicons dashicons-yes-alt"></span><p>هیچ آگهی در انتظار تأیید نیست. همه چیز به‌روز است!</p></div>'
                );
            }
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

            // ===== Dashboard Pending Review Panel (NEW) =====
            // Approve a pending listing directly from the dashboard card,
            // removing the card with a fade-out instead of a full reload.
            $(document).on('click', '.approve-listing-btn', function() {
                const $btn = $(this);
                const $card = $btn.closest('.pending-listing-card');
                const postId = $btn.data('post-id');

                $btn.prop('disabled', true);
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
                            $card.css('background', '#f0fdf4').fadeOut(400, function() {
                                $(this).remove();
                                MoavezeAdmin.updatePendingCountBadge();
                            });
                        } else {
                            alert(response.data || 'خطا در تأیید آگهی');
                            $btn.prop('disabled', false);
                        }
                    },
                    error() {
                        alert('خطا در ارتباط با سرور');
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Reject a pending listing directly from the dashboard card.
            $(document).on('click', '.reject-listing-btn', function() {
                const $btn = $(this);
                const $card = $btn.closest('.pending-listing-card');
                const postId = $btn.data('post-id');
                const reason = prompt('دلیل رد آگهی (برای اطلاع‌رسانی به کاربر):', '');
                if (reason === null) return;

                $btn.prop('disabled', true);
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
                            $card.css('background', '#fef2f2').fadeOut(400, function() {
                                $(this).remove();
                                MoavezeAdmin.updatePendingCountBadge();
                            });
                        } else {
                            alert(response.data || 'خطا در رد آگهی');
                            $btn.prop('disabled', false);
                        }
                    },
                    error() {
                        alert('خطا در ارتباط با سرور');
                        $btn.prop('disabled', false);
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

            // Add single property to exchange (list column button)
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

            // NEW: "ارسال به معاوضه" button inside the Houzez property
            // edit-screen metabox (see class-houzez-integration.php).
            $(document).on('click', '.moaveze-send-to-exchange-btn', function() {
                const $btn = $(this);
                const propertyId = $btn.data('property-id');

                $btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px;"></span> در حال ارسال...');

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
                            $btn.closest('.moaveze-houzez-metabox').html(
                                '<p class="status-linked"><span class="dashicons dashicons-yes-alt"></span> این ملک به معاوضه متصل شد</p>'
                            );
                        } else {
                            alert((response.data && response.data.message) || 'خطا در ارسال به معاوضه');
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-randomize"></span> ارسال به معاوضه');
                        }
                    },
                    error() {
                        alert('خطا در ارتباط با سرور');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-randomize"></span> ارسال به معاوضه');
                    }
                });
            });
        },

        /**
         * "ارتباط طرفین" (Connect Parties) modal.
         * FIX: previously .connect-parties-btn had NO click handler at all
         * (dead markup) - clicking it did nothing. This wires it up to
         * fetch full match details (contact info for both sides + a
         * human-readable breakdown of why the algorithm matched them) and
         * renders it in an in-page modal so the consultant/admin can
         * actually call/connect the two owners.
         */
        initConnectPartiesModal() {
            $(document).on('click', '.connect-parties-btn', function() {
                const matchId = $(this).data('match-id');
                MoavezeAdmin.openConnectModal(matchId);
            });

            // Close modal (overlay click or close button)
            $(document).on('click', '.moaveze-admin-modal-overlay, .moaveze-admin-modal-close', function(e) {
                if (e.target === e.currentTarget || $(e.target).hasClass('moaveze-admin-modal-close')) {
                    $('.moaveze-admin-modal-overlay').remove();
                }
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') $('.moaveze-admin-modal-overlay').remove();
            });

            // Mark as connected / in-progress / completed from inside the modal
            $(document).on('click', '.match-status-action', function() {
                const $btn = $(this);
                const matchId = $btn.data('match-id');
                const status = $btn.data('status');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_update_match_status',
                    nonce: moavezeAdmin.nonce,
                    match_id: matchId,
                    status: status,
                }, function(response) {
                    if (response.success) {
                        $('.moaveze-admin-modal-overlay').remove();
                        location.reload();
                    } else {
                        alert(response.data || 'خطا در بروزرسانی وضعیت');
                    }
                });
            });
        },

        /**
         * Fetch match details via AJAX and render the modal.
         */
        openConnectModal(matchId) {
            $('.moaveze-admin-modal-overlay').remove();
            $('body').append(
                '<div class="moaveze-admin-modal-overlay"><div class="moaveze-admin-modal">' +
                '<div class="moaveze-admin-modal-loading"><span class="spinner is-active"></span> در حال بارگذاری اطلاعات...</div>' +
                '</div></div>'
            );

            $.post(moavezeAdmin.ajaxUrl, {
                action: 'moaveze_get_match_full_details',
                nonce: moavezeAdmin.nonce,
                match_id: matchId,
            }, function(response) {
                if (!response.success) {
                    $('.moaveze-admin-modal').html(
                        '<div class="moaveze-admin-modal-header"><h2>خطا</h2><button class="moaveze-admin-modal-close">&times;</button></div>' +
                        '<div class="moaveze-admin-modal-body"><p>' + (response.data || 'خطا در بارگذاری اطلاعات') + '</p></div>'
                    );
                    return;
                }
                MoavezeAdmin.renderConnectModal(response.data);
            }).fail(function() {
                $('.moaveze-admin-modal').html(
                    '<div class="moaveze-admin-modal-header"><h2>خطا</h2><button class="moaveze-admin-modal-close">&times;</button></div>' +
                    '<div class="moaveze-admin-modal-body"><p>خطا در ارتباط با سرور</p></div>'
                );
            });
        },

        /**
         * Build the modal markup: two side-by-side property + contact
         * cards, plus a clear "why they matched" breakdown with per-factor
         * scores so the reasoning is transparent to the admin/consultant.
         */
        renderConnectModal(data) {
            const m = data.match;
            const a = data.side_a;
            const b = data.side_b;

            const fmt = (n) => Number(n || 0).toLocaleString('en-US');

            const sideCard = (side, label) => `
                <div class="connect-side-card">
                    <span class="connect-side-label">${label}</span>
                    <h3><a href="${side.edit_link}" target="_blank">${side.title}</a></h3>
                    <div class="connect-side-specs">
                        <span>${side.property_type || '—'}</span>
                        <span>${side.district || '—'}</span>
                        <span>${side.area ? side.area + ' متر' : '—'}</span>
                    </div>
                    <div class="connect-side-price">${fmt(side.value)} تومان</div>
                    <div class="connect-contact-box">
                        <div class="cc-row"><span class="cc-label">نام:</span> <strong>${side.contact_name || '—'}</strong></div>
                        <div class="cc-row"><span class="cc-label">تماس:</span>
                            <a class="cc-phone" href="tel:${side.contact_phone || ''}">${side.contact_phone || '—'}</a>
                        </div>
                        ${side.contact_email ? `<div class="cc-row"><span class="cc-label">ایمیل:</span> ${side.contact_email}</div>` : ''}
                    </div>
                </div>`;

            // Persian labels for the scoring breakdown factors
            const factorLabels = {
                value_range: 'محدوده ارزش',
                property_type: 'نوع ملک',
                district: 'منطقه',
                exchange_type: 'نوع معاوضه',
                cash_balance: 'تعادل نقدی',
                area: 'متراژ',
                mutual_interest: 'علاقه‌مندی دوطرفه',
            };

            let breakdownHtml = '';
            Object.keys(m.breakdown || {}).forEach((key) => {
                const f = m.breakdown[key];
                const pct = Math.round((f.score || 0) * 100);
                breakdownHtml += `
                    <div class="breakdown-row">
                        <div class="breakdown-top">
                            <span class="breakdown-label">${factorLabels[key] || key}</span>
                            <span class="breakdown-pct">${pct}%</span>
                        </div>
                        <div class="breakdown-bar"><div class="breakdown-fill" style="width:${pct}%"></div></div>
                        <div class="breakdown-detail">${f.detail || ''}</div>
                    </div>`;
            });

            let reasonsHtml = '';
            if (m.reasons && m.reasons.length) {
                reasonsHtml = '<ul class="match-reasons-list">' +
                    m.reasons.map(r => `<li><span class="dashicons dashicons-yes-alt"></span> ${r}</li>`).join('') +
                    '</ul>';
            }

            const html = `
                <div class="moaveze-admin-modal-header">
                    <h2><span class="dashicons dashicons-phone"></span> ارتباط طرفین معاوضه</h2>
                    <button class="moaveze-admin-modal-close">&times;</button>
                </div>
                <div class="moaveze-admin-modal-body">
                    <div class="connect-score-banner">
                        <span class="connect-score-value">${Math.round(m.score)}%</span>
                        <span class="connect-score-label">میزان تطابق</span>
                    </div>

                    <div class="connect-sides-grid">
                        ${sideCard(a, 'ملک A')}
                        <div class="connect-arrow"><span class="dashicons dashicons-leftright"></span></div>
                        ${sideCard(b, 'ملک B')}
                    </div>

                    <div class="connect-why-section">
                        <h4><span class="dashicons dashicons-info"></span> چرا این دو ملک با هم تطابق دارند؟</h4>
                        ${reasonsHtml || '<p class="description">دلیل خاصی ثبت نشده است.</p>'}
                        <div class="breakdown-list">${breakdownHtml}</div>
                        ${m.suggestion ? `<div class="connect-suggestion"><span class="dashicons dashicons-lightbulb"></span> ${m.suggestion}</div>` : ''}
                    </div>

                    ${m.consultant ? `<p class="connect-consultant-note">مشاور مسئول: <strong>${m.consultant}</strong></p>` : ''}
                </div>
                <div class="moaveze-admin-modal-footer">
                    <button class="button match-status-action" data-match-id="${m.id}" data-status="in_progress">
                        <span class="dashicons dashicons-phone"></span> علامت‌گذاری «در حال پیگیری»
                    </button>
                    <button class="button button-primary match-status-action" data-match-id="${m.id}" data-status="completed">
                        <span class="dashicons dashicons-yes"></span> معامله انجام شد
                    </button>
                </div>`;

            $('.moaveze-admin-modal').html(html);
        }
    };

    $(document).ready(() => MoavezeAdmin.init());

})(jQuery);
