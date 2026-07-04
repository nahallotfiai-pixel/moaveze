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
            this.initInstantFeatureToggle();
            this.initAISuggestions();
            this.initConvertToExchange();
        },

        /**
         * "تبدیل به آگهی معاوضه" button on Houzez property edit
         * screens - calls ajax_convert_to_exchange to create a full
         * moaveze_exchange post from the property's data.
         */
        initConvertToExchange() {
            $(document).on('click', '.moaveze-send-to-exchange-btn', function() {
                const $btn = $(this);
                const propertyId = $btn.data('property-id');

                if (!confirm('آیا این ملک به آگهی معاوضه تبدیل شود؟ (تمام مشخصات، تصاویر و موقعیت ملک کپی خواهد شد)')) return;

                $btn.prop('disabled', true).text('در حال تبدیل...');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_convert_to_exchange',
                    nonce: moavezeAdmin.nonce,
                    property_id: propertyId,
                }, function(response) {
                    if (response.success) {
                        $btn.replaceWith(`
                            <p class="status-linked" style="color:#16a34a;font-weight:600;">
                                <span class="dashicons dashicons-yes-alt"></span> ${response.data.message}
                            </p>
                            <a href="${response.data.edit_url}" class="button button-small" target="_blank">ویرایش آگهی معاوضه</a>
                            <a href="${response.data.view_url}" class="button button-small" target="_blank">مشاهده</a>
                        `);
                    } else {
                        const msg = (response.data && response.data.message) || response.data || 'خطا';
                        alert(msg);
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-randomize"></span> ارسال به معاوضه');
                    }
                }).fail(function() {
                    alert('خطا در ارتباط با سرور');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-randomize"></span> ارسال به معاوضه');
                });
            });
        },

        /**
         * "پیشنهاد هوش مصنوعی برای معامله/تسویه" button on the Matches
         * and Chain Swaps admin pages - per explicit user request that
         * AI be able to suggest (and let the consultant save/approve)
         * deal structures there too, not just property valuations.
         * Reuses the same staff-only AI infrastructure as the
         * valuation metabox (see class-ai-suggestions.php).
         */
        initAISuggestions() {
            $(document).on('click', '.ai-suggest-btn', function() {
                const $btn = $(this);
                const type = $btn.data('type'); // 'match' or 'chain'
                const id = $btn.data('id');
                const hasSuggestion = $btn.data('has-suggestion') === 1 || $btn.data('has-suggestion') === '1';
                const $panel = $(`.ai-suggestion-panel[data-type="${type}"][data-id="${id}"]`);

                // Toggle closed if already open and loaded.
                if ($panel.is(':visible') && $panel.data('loaded')) {
                    $panel.slideUp(150);
                    return;
                }

                $panel.data('loaded', false).html(`
                    <div class="ai-suggestion-loading">
                        <span class="spinner is-active" style="float:none;"></span>
                        ${hasSuggestion ? 'در حال بارگذاری پیشنهاد ذخیره‌شده...' : 'در حال دریافت پیشنهاد از هوش مصنوعی...'}
                    </div>
                `).slideDown(150);

                // If a suggestion already exists, fetch the STORED one
                // instead of spending another AI request re-generating
                // it every time the button is clicked.
                if (hasSuggestion) {
                    $.post(moavezeAdmin.ajaxUrl, {
                        action: 'moaveze_get_ai_suggestion',
                        nonce: moavezeAdmin.nonce,
                        type: type,
                        id: id,
                    }, function(response) {
                        if (response.success) {
                            $panel.html(MoavezeAdmin.renderAISuggestion(response.data, type, id, true)).data('loaded', true);
                        } else {
                            $panel.html(`<p class="ai-suggestion-error">✗ ${response.data || 'خطا در بارگذاری پیشنهاد'}</p>`);
                        }
                    }).fail(function() {
                        $panel.html('<p class="ai-suggestion-error">✗ خطا در ارتباط با سرور</p>');
                    });
                    return;
                }

                MoavezeAdmin.generateAISuggestion(type, id, $panel, $btn);
            });

            // "بازتولید پیشنهاد" (regenerate) - explicitly re-run the AI
            // even though a stored suggestion already exists.
            $(document).on('click', '.ai-suggestion-regenerate-btn', function() {
                const $btn = $(this);
                const type = $btn.data('type');
                const id = $btn.data('id');
                const $panel = $(`.ai-suggestion-panel[data-type="${type}"][data-id="${id}"]`);
                $panel.html(`
                    <div class="ai-suggestion-loading">
                        <span class="spinner is-active" style="float:none;"></span>
                        در حال دریافت پیشنهاد جدید از هوش مصنوعی...
                    </div>
                `);
                MoavezeAdmin.generateAISuggestion(type, id, $panel);
            });

            // Approve a displayed AI suggestion.
            $(document).on('click', '.ai-suggestion-approve-btn', function() {
                const $btn = $(this);
                const type = $btn.data('type');
                const id = $btn.data('id');

                $btn.prop('disabled', true);
                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_approve_ai_suggestion',
                    nonce: moavezeAdmin.nonce,
                    type: type,
                    id: id,
                }, function(response) {
                    if (response.success) {
                        $btn.replaceWith('<span class="ai-suggestion-approved-label">✓ تأیید شده توسط مشاور</span>');
                    } else {
                        alert(response.data || 'خطا در تأیید پیشنهاد');
                        $btn.prop('disabled', false);
                    }
                });
            });
        },

        /**
         * Shared "generate a brand-new AI suggestion" AJAX call, used
         * both by the first-time click on .ai-suggest-btn and by the
         * explicit "بازتولید پیشنهاد" (regenerate) button.
         */
        generateAISuggestion(type, id, $panel, $btn) {
            const action = type === 'match' ? 'moaveze_suggest_match_deal' : 'moaveze_suggest_chain_deal';
            const idParam = type === 'match' ? 'match_id' : 'chain_id';

            $.post(moavezeAdmin.ajaxUrl, {
                action: action,
                nonce: moavezeAdmin.nonce,
                [idParam]: id,
            }, function(response) {
                if (response.success) {
                    $panel.html(MoavezeAdmin.renderAISuggestion(response.data, type, id, false)).data('loaded', true);
                    if ($btn) {
                        $btn.data('has-suggestion', '1');
                        $btn.html('<span class="dashicons dashicons-superhero-alt"></span> مشاهده پیشنهاد هوش مصنوعی');
                    }
                } else {
                    $panel.html(`<p class="ai-suggestion-error">✗ ${response.data || 'خطا در دریافت پیشنهاد'}</p>`);
                }
            }).fail(function() {
                $panel.html('<p class="ai-suggestion-error">✗ خطا در ارتباط با سرور</p>');
            });
        },

        /**
         * Render the AI deal-structure suggestion result (summary,
         * proposed structures with cash amounts, risks, confidence) -
         * shared markup for both the "just generated" and "viewing
         * existing" cases.
         */
        renderAISuggestion(data, type, id, isExisting) {
            const structuresHtml = (data.structures || []).map((s) => `
                <div class="ai-structure-card">
                    <strong>${s.title || 'ساختار پیشنهادی'}</strong>
                    <p>${s.description || ''}</p>
                    ${s.cash_short ? `<span class="ai-structure-cash">💰 ${s.cash_short}${s.cash_direction ? ' (' + s.cash_direction + ')' : ''}</span>` : ''}
                </div>
            `).join('');

            const risksHtml = (data.risks || []).length ? `
                <div class="ai-suggestion-risks">
                    <strong>نکات و ریسک‌ها:</strong>
                    <ul>${data.risks.map((r) => `<li>${r}</li>`).join('')}</ul>
                </div>
            ` : '';

            const approveBtn = data.status === 'approved'
                ? '<span class="ai-suggestion-approved-label">✓ تأیید شده توسط مشاور</span>'
                : `<button type="button" class="button button-primary ai-suggestion-approve-btn" data-type="${type}" data-id="${id}">
                     <span class="dashicons dashicons-yes"></span> تأیید این پیشنهاد
                   </button>`;

            const regenerateBtn = isExisting
                ? `<button type="button" class="button ai-suggestion-regenerate-btn" data-type="${type}" data-id="${id}">
                     <span class="dashicons dashicons-update"></span> بازتولید پیشنهاد
                   </button>`
                : '';

            return `
                <div class="ai-suggestion-result">
                    <div class="ai-suggestion-header">
                        <span class="ai-suggestion-provider">🤖 ${data.provider || 'هوش مصنوعی'}</span>
                        <span class="ai-suggestion-confidence">میزان اطمینان: ${data.confidence || 'نامشخص'}</span>
                    </div>
                    <p class="ai-suggestion-summary">${data.summary || ''}</p>
                    <div class="ai-structures-grid">${structuresHtml}</div>
                    ${risksHtml}
                    <div class="ai-suggestion-footer">${approveBtn}${regenerateBtn}</div>
                </div>
            `;
        },

        /**
         * Feature checkboxes (پارکینگ/آسانسور/انباری/...) on the exchange
         * listing edit screen save themselves INSTANTLY via AJAX the
         * moment they're clicked - no need to click "به‌روزرسانی" first.
         *
         * WHY: the most likely explanation for "I checked the box in
         * wp-admin but it still doesn't show on the site" reports that
         * persisted even after the taxonomy-sync bug was fixed is simply
         * that clicking a checkbox alone does nothing until the whole
         * post form is submitted - and it's easy to toggle a box, get
         * distracted, and never actually click Update. Saving instantly
         * removes that entire failure mode. It also updates the
         * "وضعیت فعلی امکانات ذخیره‌شده" live readout in the metabox so
         * the admin gets immediate, verifiable confirmation the value
         * really did reach the database - no guessing, no waiting for a
         * full page reload of the (possibly cached) public listing page.
         */
        initInstantFeatureToggle() {
            $(document).on('change', '.moaveze-instant-feature', function() {
                const $checkbox = $(this);
                const postId = $checkbox.data('post-id');
                const featureKey = $checkbox.data('feature-key');
                const checked = $checkbox.is(':checked');
                const $status = $(`.moaveze-instant-save-status[data-feature-key="${featureKey}"]`);

                $checkbox.prop('disabled', true);
                $status.html('<span class="spinner is-active" style="float:none;margin:0 4px;width:14px;height:14px;"></span>');

                $.post(moavezeAdmin.ajaxUrl, {
                    action: 'moaveze_toggle_feature',
                    nonce: moavezeAdmin.nonce,
                    post_id: postId,
                    feature_key: featureKey,
                    checked: checked ? '1' : '',
                }, function(response) {
                    $checkbox.prop('disabled', false);
                    if (response.success) {
                        $status.html('<span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span>').attr('title', response.data.message);
                        setTimeout(() => $status.fadeOut(600, function(){ $(this).show().empty(); }), 1500);
                        // Live-refresh the diagnostic readout with what
                        // was actually just verified in the database.
                        const $debugList = $('#moaveze-feature-debug-list');
                        if ($debugList.length) {
                            $debugList.text(response.data.active_terms.length ? response.data.active_terms.join('، ') : '');
                            if (!response.data.active_terms.length) $debugList.html('<em>هیچ ویژگی‌ای ثبت نشده</em>');
                        }
                    } else {
                        $status.html('<span class="dashicons dashicons-warning" style="color:#dc2626;"></span>');
                        alert('خطا در ذخیره: ' + (response.data || 'خطای نامشخص') + ' — لطفاً دوباره تلاش کنید یا با دکمه «به‌روزرسانی» کل فرم را ذخیره کنید.');
                        $checkbox.prop('checked', !checked); // revert on failure
                    }
                }).fail(function() {
                    $checkbox.prop('disabled', false).prop('checked', !checked);
                    $status.html('<span class="dashicons dashicons-warning" style="color:#dc2626;"></span>');
                    alert('خطا در ارتباط با سرور - تیک به حالت قبل بازگشت. لطفاً اتصال اینترنت را بررسی کنید یا از دکمه «به‌روزرسانی» استفاده کنید.');
                });
            });
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

            // Short price formatter (میلیون/میلیارد) local to admin.js,
            // since the frontend MoavezePlus helper object is not loaded
            // on wp-admin pages. Mirrors Moaveze_Helpers::short_price()
            // in PHP so admin and frontend agree on formatting.
            const fmt = (n) => {
                n = Number(n) || 0;
                if (n <= 0) return '0 تومان';
                const trim = (v) => {
                    const r = Math.round(v * 10) / 10;
                    return (r % 1 === 0) ? String(r) : String(r);
                };
                if (n >= 1000000000) return trim(n / 1000000000) + ' میلیارد تومان';
                if (n >= 1000000) return trim(n / 1000000) + ' میلیون تومان';
                return n.toLocaleString('en-US') + ' تومان';
            };

            const sideCard = (side, label) => `
                <div class="connect-side-card">
                    <span class="connect-side-label">${label}</span>
                    <h3><a href="${side.edit_link}" target="_blank">${side.title}</a></h3>
                    <div class="connect-side-specs">
                        <span>${side.property_type || '—'}</span>
                        <span>${side.district || '—'}</span>
                        <span>${side.area ? side.area + ' متر' : '—'}</span>
                    </div>
                    <div class="connect-side-price">${fmt(side.value)}</div>
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
