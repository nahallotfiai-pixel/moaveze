/**
 * Moaveze Plus - Favorites/Bookmarks (localStorage-based)
 *
 * Works for guests AND logged-in users. Guests: pure localStorage.
 * Logged-in users: localStorage is merged with their server-saved list
 * on load (moavezeServerFavoriteIds, set by class-favorites.php on the
 * favorites page) and pushed back to the server after every change so
 * it follows them across devices.
 */

(function($) {
    'use strict';

    const STORAGE_KEY = 'moaveze_favorites';

    const MoavezeFavorites = {
        ids: [],

        init() {
            this.load();
            this.mergeServerIds();
            this.renderButtonStates();
            this.bindEvents();
            this.loadFavoritesPage();
        },

        load() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                this.ids = raw ? JSON.parse(raw) : [];
            } catch (e) {
                this.ids = [];
            }
        },

        save() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.ids));
            this.syncToServer();
        },

        /**
         * If the current page set window.moavezeServerFavoriteIds (only
         * the [moaveze_favorites] page does), merge those IDs in so a
         * logged-in user sees their favorites even on a fresh browser.
         */
        mergeServerIds() {
            if (Array.isArray(window.moavezeServerFavoriteIds) && window.moavezeServerFavoriteIds.length) {
                const merged = new Set([...this.ids, ...window.moavezeServerFavoriteIds]);
                this.ids = Array.from(merged);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.ids));
            }
        },

        syncToServer() {
            $.post(moavezePlus.ajaxUrl, {
                action: 'moaveze_sync_favorites',
                nonce: moavezePlus.nonce,
                ids: this.ids,
            });
        },

        isFavorite(id) {
            return this.ids.includes(Number(id));
        },

        toggle(id) {
            id = Number(id);
            if (this.isFavorite(id)) {
                this.ids = this.ids.filter((i) => i !== id);
            } else {
                this.ids.push(id);
            }
            this.save();
            return this.isFavorite(id);
        },

        bindEvents() {
            $(document).on('click', '.moaveze-favorite-btn', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(e.currentTarget);
                const id = $btn.data('id');
                const nowActive = this.toggle(id);

                $('.moaveze-favorite-btn[data-id="' + id + '"]').toggleClass('active', nowActive);
                $('.moaveze-favorite-btn-inline[data-id="' + id + '"] .fav-btn-label')
                    .text(nowActive ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها');

                if (window.MoavezePlus && MoavezePlus.showToast) {
                    MoavezePlus.showToast(nowActive ? 'به علاقه‌مندی‌ها اضافه شد' : 'از علاقه‌مندی‌ها حذف شد', 'success');
                }

                // Remove card immediately if we're on the favorites page itself
                if (!nowActive) {
                    $('#favorites-grid .moaveze-card[data-id="' + id + '"]').fadeOut(250, function() { $(this).remove(); });
                }
            });
        },

        /**
         * Paint the correct heart state (filled/outline) on every
         * favorite button present on the current page (cards + single).
         */
        renderButtonStates() {
            $('.moaveze-favorite-btn').each((_, el) => {
                const id = $(el).data('id');
                const active = this.isFavorite(id);
                $(el).toggleClass('active', active);
                $(el).closest('.moaveze-favorite-btn-inline').find('.fav-btn-label')
                    .text(active ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها');
            });
        },

        /**
         * On the dedicated [moaveze_favorites] page, fetch full card data
         * for the saved IDs and render real listing cards.
         */
        loadFavoritesPage() {
            const $grid = $('#favorites-grid');
            if (!$grid.length) return;

            if (!this.ids.length) {
                $grid.html(`<div class="moaveze-empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                    <h3>هنوز آگهی‌ای ذخیره نکرده‌اید</h3>
                    <p>روی آیکون قلب هر آگهی که می‌پسندید کلیک کنید تا اینجا ذخیره شود.</p>
                </div>`);
                return;
            }

            $.post(moavezePlus.ajaxUrl, {
                action: 'moaveze_get_favorites',
                nonce: moavezePlus.nonce,
                ids: this.ids,
            }, (response) => {
                if (!response.success || !response.data.items.length) {
                    $grid.html('<div class="moaveze-empty-state"><p>آگهی‌های ذخیره‌شده دیگر موجود نیستند.</p></div>');
                    return;
                }
                $grid.html(response.data.items.map((item) => this.renderFavoriteCard(item)).join(''));
                this.renderButtonStates();
            });
        },

        renderFavoriteCard(item) {
            return `
                <div class="moaveze-card" data-id="${item.id}">
                    <button type="button" class="moaveze-favorite-btn active" data-id="${item.id}" aria-label="حذف از علاقه‌مندی‌ها">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 10-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                    </button>
                    <div class="moaveze-card-image">
                        ${item.image ? `<img src="${item.image}" alt="${item.title}" loading="lazy">` : '<div class="moaveze-card-placeholder"></div>'}
                    </div>
                    <div class="moaveze-card-body">
                        <h3 class="moaveze-card-title"><a href="${item.url}">${item.title}</a></h3>
                        <div class="moaveze-card-meta">
                            <span class="meta-item">${item.district || ''}</span>
                            <span class="meta-item">${item.type || ''}</span>
                        </div>
                        <div class="moaveze-card-footer">
                            <span class="moaveze-card-price">${MoavezePlus.formatPriceShort(item.value)}</span>
                            <a href="${item.url}" class="moaveze-btn moaveze-btn-sm moaveze-btn-outline">جزئیات</a>
                        </div>
                    </div>
                </div>`;
        }
    };

    $(document).ready(() => MoavezeFavorites.init());
    window.MoavezeFavorites = MoavezeFavorites;

})(jQuery);
