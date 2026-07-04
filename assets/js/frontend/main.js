/**
 * Moaveze Plus - Main Frontend JS
 */

(function($) {
    'use strict';

    const MoavezePlus = {
        init() {
            this.initPriceFormatting();
            this.initAnimations();
            this.initCounters();
            this.initDarkMode();
            this.initSearchableSelects();
            this.initChipSearch();
            this.initListingsFilters();
        },

        /**
         * Listings page filter bar ("جستجو"/"پاک کردن"/"نقشه" buttons -
         * see Moaveze_Listings::render_filters() in class-listings.php).
         *
         * BUG FIX: these buttons previously had NO JavaScript binding
         * anywhere in the codebase at all - clicking "جستجو" did
         * nothing because nothing ever read the filter values. Neither
         * the results grid nor the map ever changed, exactly as
         * reported.
         *
         * Fix approach: reload the page with the selected filter values
         * as URL query parameters (?moaveze_type=...&moaveze_district=...
         * etc), which Moaveze_Listings::render_listings() now reads (see
         * the corresponding PHP fix) to build the WP_Query - this
         * automatically updates BOTH the results grid and the map,
         * since the map is populated from the same server-rendered
         * cards (see MoavezeMap.loadMarkersFromCards() in map.js).
         * A full page reload is deliberately used instead of an AJAX
         * partial-refresh here to guarantee 100% consistent behavior
         * with pagination, browser back/forward, and shareable/
         * bookmarkable filtered-search URLs - all of which an AJAX-only
         * approach would need significant extra plumbing to support.
         */
        initListingsFilters() {
            const $filterBar = $('.moaveze-filters-bar');
            if (!$filterBar.length) return;

            $(document).on('click', '#apply-filters', function() {
                const params = new URLSearchParams(window.location.search);
                const setOrDelete = (key, value) => {
                    if (value) params.set(key, value); else params.delete(key);
                };

                setOrDelete('moaveze_type', $('#filter-type').val());
                setOrDelete('moaveze_district', $('#filter-district').val());
                setOrDelete('moaveze_exchange_type', $('#filter-exchange-type').val());
                setOrDelete('moaveze_min_value', MoavezePlus.toLatinDigits($('#filter-min-price').val()).replace(/[^\d]/g, ''));
                setOrDelete('moaveze_max_value', MoavezePlus.toLatinDigits($('#filter-max-price').val()).replace(/[^\d]/g, ''));
                // Always go back to page 1 of results when the filter
                // criteria changes - an old page number could otherwise
                // point past the end of a now-smaller filtered result set.
                params.delete('paged');

                const query = params.toString();
                window.location.href = window.location.pathname + (query ? '?' + query : '');
            });

            $(document).on('click', '#reset-filters', function() {
                window.location.href = window.location.pathname;
            });

            $(document).on('click', '#toggle-map-view', function() {
                const $map = $('#listings-map');
                $map.slideToggle(200, function() {
                    // Leaflet needs an explicit size recalculation after
                    // its container goes from display:none to visible,
                    // otherwise it renders as a blank/broken grey box.
                    if ($map.is(':visible') && window.MoavezeMap && window.MoavezeMap.maps.listings) {
                        window.MoavezeMap.maps.listings.invalidateSize();
                    }
                });
            });
        },

        /**
         * Turn any <select class="moaveze-searchable-select"> into a
         * lightweight searchable combobox (type to filter options),
         * with no external library dependency. Used for the district
         * dropdowns (now ~60 items after the district-list expansion),
         * where scrolling a huge native <select> is painful - typing
         * "رشد" instantly narrows it down to "رشدیه".
         *
         * The original <select> is kept in the DOM (just visually
         * hidden) so normal form submission / validation / required
         * attributes keep working unchanged - this widget only
         * controls its .value from the outside.
         */
        initSearchableSelects() {
            $('select.moaveze-searchable-select').each(function () {
                const $select = $(this);
                if ($select.data('moaveze-searchable-init')) return; // avoid double-init
                $select.data('moaveze-searchable-init', true);

                const options = $select.find('option').map(function () {
                    return { value: $(this).val(), label: $(this).text() };
                }).get();

                const placeholder = $select.data('placeholder') || 'جستجو یا انتخاب کنید...';
                const currentLabel = $select.find('option:selected').text() || '';

                const $wrapper = $(`
                    <div class="moaveze-combobox">
                        <input type="text" class="moaveze-combobox-input" placeholder="${placeholder}" autocomplete="off" value="${currentLabel !== options[0]?.label ? currentLabel : ''}">
                        <svg class="combobox-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        <div class="moaveze-combobox-list" style="display:none;"></div>
                    </div>
                `);

                $select.hide().after($wrapper);
                const $input = $wrapper.find('.moaveze-combobox-input');
                const $list = $wrapper.find('.moaveze-combobox-list');

                const renderList = (filter = '') => {
                    const term = filter.trim().toLowerCase();
                    const filtered = options.filter((o) => o.value && o.label.toLowerCase().includes(term));
                    $list.empty();
                    if (!filtered.length) {
                        $list.append('<div class="combobox-empty">موردی یافت نشد</div>');
                        return;
                    }
                    filtered.forEach((o) => {
                        const $item = $(`<div class="combobox-item" data-value="${o.value}">${o.label}</div>`);
                        if (o.value === $select.val()) $item.addClass('selected');
                        $list.append($item);
                    });
                };

                $input.on('focus click', () => {
                    renderList($input.val());
                    $list.show();
                    $wrapper.addClass('open');
                });

                $input.on('input', () => renderList($input.val()));

                $list.on('click', '.combobox-item', function () {
                    const value = $(this).data('value');
                    const label = $(this).text();
                    $select.val(value).trigger('change');
                    $input.val(label);
                    $list.hide();
                    $wrapper.removeClass('open');
                });

                $(document).on('click', (e) => {
                    if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                        $list.hide();
                        $wrapper.removeClass('open');
                    }
                });
            });
        },

        /**
         * Adds a search box above any ".moaveze-chip-picker" (used for
         * the multi-select district chips in the submission form) that
         * filters the visible chips as the user types - needed now that
         * the district list has grown to ~60 items.
         */
        initChipSearch() {
            $('.moaveze-chip-picker').each(function () {
                const $picker = $(this);
                if ($picker.data('moaveze-chip-search-init')) return;
                if ($picker.find('.moaveze-chip').length < 8) return; // not worth it for short lists
                $picker.data('moaveze-chip-search-init', true);

                const $search = $('<input type="text" class="moaveze-chip-search" placeholder="جستجوی منطقه...">');
                $picker.before($search);

                $search.on('input', function () {
                    const term = $(this).val().trim().toLowerCase();
                    $picker.find('.moaveze-chip').each(function () {
                        const label = $(this).text().toLowerCase();
                        $(this).toggle(label.includes(term));
                    });
                });
            });
        },

        /**
         * Format price inputs with thousand separators
         *
         * IMPORTANT: We must strip BOTH Latin (0-9) and Persian/Arabic-Indic
         * (۰-۹ / ٠-٩) digits, and always render the separators using Latin
         * digits (toLocaleString('en-US')). If we used 'fa-IR' the field
         * would display Persian digits, which the \d regex below does not
         * match on the next keystroke - causing every digit except the
         * newest one to be wiped out (the exact bug reported).
         */
        initPriceFormatting() {
            $(document).on('input', '.moaveze-price-input', function() {
                const cursorWasAtEnd = this.selectionEnd === this.value.length;
                let value = MoavezePlus.toLatinDigits($(this).val()).replace(/[^\d]/g, '');
                if (value) {
                    // Avoid leading zeros causing weird formatting
                    value = String(parseInt(value, 10));
                    $(this).val(Number(value).toLocaleString('en-US'));
                    // Show human-readable hint
                    const hint = $(this).siblings('.field-hint');
                    if (hint.length) {
                        hint.text(MoavezePlus.priceToText(parseInt(value, 10)));
                    }
                } else {
                    $(this).val('');
                }
                // Keep the caret at the end - simplest reliable behavior
                // for a formatted numeric field.
                if (cursorWasAtEnd) {
                    const len = this.value.length;
                    this.setSelectionRange(len, len);
                }
            });
        },

        /**
         * Convert Persian/Arabic-Indic digits to Latin digits so numeric
         * regexes (\d) and parseInt/Number() work correctly.
         */
        toLatinDigits(str) {
            if (!str) return str;
            const persian = '۰۱۲۳۴۵۶۷۸۹';
            const arabic = '٠١٢٣٤٥٦٧٨٩';
            return String(str).replace(/[۰-۹٠-٩]/g, (ch) => {
                let idx = persian.indexOf(ch);
                if (idx === -1) idx = arabic.indexOf(ch);
                return idx === -1 ? ch : idx;
            });
        },

        /**
         * Convert a string of Latin digits to Persian digits for display
         * (JS counterpart of Moaveze_Helpers::to_persian_digits() in PHP).
         */
        toPersianDigits(str) {
            const map = { '0':'۰','1':'۱','2':'۲','3':'۳','4':'۴','5':'۵','6':'۶','7':'۷','8':'۸','9':'۹' };
            return String(str).replace(/[0-9]/g, (d) => map[d]);
        },

        /**
         * Short price formatter for client-side rendered content (map
         * popups, offers list, notifications) - mirrors
         * Moaveze_Helpers::short_price() in PHP so both sides always
         * agree on formatting, e.g. 45500000000 -> "۴۵.۵ میلیارد تومان".
         */
        formatPriceShort(amount, withUnit = true) {
            amount = Number(amount) || 0;
            const suffix = withUnit ? ' تومان' : '';
            const trim = (n) => {
                const r = Math.round(n * 10) / 10;
                return (r % 1 === 0) ? String(r) : String(r).replace(/0$/, '').replace(/\.$/, '');
            };

            let out;
            if (amount <= 0) {
                out = '0' + suffix;
            } else if (amount >= 1000000000000) {
                out = trim(amount / 1000000000000) + ' هزار میلیارد' + suffix;
            } else if (amount >= 1000000000) {
                out = trim(amount / 1000000000) + ' میلیارد' + suffix;
            } else if (amount >= 1000000) {
                out = trim(amount / 1000000) + ' میلیون' + suffix;
            } else {
                out = amount.toLocaleString('en-US') + suffix;
            }
            return MoavezePlus.toPersianDigits(out);
        },

        /**
         * Gregorian -> Jalali date conversion (JS counterpart of
         * Moaveze_Helpers::jalali_date() in PHP), for content rendered
         * purely client-side (e.g. offers/notifications fetched via AJAX).
         * Accepts a MySQL datetime string ("2026-07-02 10:30:00") or any
         * value new Date() can parse.
         */
        toJalali(dateStr, withTime = false) {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return '';

            const gy = d.getFullYear(), gm = d.getMonth() + 1, gd = d.getDate();
            const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            let gy2 = (gm > 2) ? (gy + 1) : gy;
            let days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
                + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];

            let jy = -1595 + (33 * Math.floor(days / 12053));
            days %= 12053;
            jy += 4 * Math.floor(days / 1461);
            days %= 1461;

            if (days > 365) {
                jy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }

            let jm, jd;
            if (days < 186) {
                jm = 1 + Math.floor(days / 31);
                jd = 1 + (days % 31);
            } else {
                jm = 7 + Math.floor((days - 186) / 30);
                jd = 1 + ((days - 186) % 30);
            }

            const pad = (n) => String(n).padStart(2, '0');
            let out = `${jy}/${pad(jm)}/${pad(jd)}`;
            if (withTime) {
                out += ` ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }
            return MoavezePlus.toPersianDigits(out);
        },

        /**
         * Convert price to Persian text
         */
        priceToText(num) {
            if (!num || num === 0) return '';
            if (num >= 1000000000000) {
                return (num / 1000000000000).toFixed(1) + ' هزار میلیارد تومان';
            }
            if (num >= 1000000000) {
                return (num / 1000000000).toFixed(1) + ' میلیارد تومان';
            }
            if (num >= 1000000) {
                return (num / 1000000).toFixed(0) + ' میلیون تومان';
            }
            return num.toLocaleString('fa-IR') + ' تومان';
        },

        /**
         * Initialize scroll animations
         */
        initAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.moaveze-card, .moaveze-section-card').forEach(el => {
                observer.observe(el);
            });
        },

        /**
         * Animated counters
         */
        initCounters() {
            document.querySelectorAll('.stat-number[data-count]').forEach(el => {
                const target = parseInt(el.dataset.count);
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;

                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    el.textContent = Math.round(current).toLocaleString('fa-IR');
                }, 16);
            });
        },

        /**
         * Auto dark mode detection
         */
        initDarkMode() {
            if (document.body.classList.contains('moaveze-auto-dark')) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                const updateDarkMode = (e) => {
                    document.querySelectorAll('.moaveze-wrapper').forEach(wrapper => {
                        if (e.matches) {
                            wrapper.style.setProperty('--moaveze-bg', '#0f172a');
                            wrapper.style.setProperty('--moaveze-surface', '#1e293b');
                            wrapper.style.setProperty('--moaveze-text', '#e2e8f0');
                            wrapper.style.setProperty('--moaveze-text-muted', '#94a3b8');
                            wrapper.style.setProperty('--moaveze-border', '#334155');
                        }
                    });
                };
                mediaQuery.addEventListener('change', updateDarkMode);
            }
        },

        /**
         * Show notification toast
         */
        showToast(message, type = 'success') {
            const toast = $(`
                <div class="moaveze-toast moaveze-toast-${type}">
                    <span class="toast-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
                    <span class="toast-message">${message}</span>
                </div>
            `);

            $('body').append(toast);
            setTimeout(() => toast.addClass('show'), 10);
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        },

        /**
         * AJAX helper
         */
        ajax(action, data = {}) {
            return $.ajax({
                url: moavezePlus.ajaxUrl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: moavezePlus.nonce,
                    ...data
                }
            });
        }
    };

    // Initialize when DOM ready
    $(document).ready(() => MoavezePlus.init());

    // Expose globally
    window.MoavezePlus = MoavezePlus;

})(jQuery);
