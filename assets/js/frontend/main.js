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
        },

        /**
         * Format price inputs with thousand separators
         */
        initPriceFormatting() {
            $(document).on('input', '.moaveze-price-input', function() {
                let value = $(this).val().replace(/[^\d]/g, '');
                if (value) {
                    $(this).val(Number(value).toLocaleString('fa-IR'));
                    // Show human-readable hint
                    const hint = $(this).siblings('.field-hint');
                    if (hint.length) {
                        hint.text(MoavezePlus.priceToText(parseInt(value)));
                    }
                }
            });
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
