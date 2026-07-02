/**
 * Moaveze Plus - Single Listing Page JS
 * Gallery lightbox + thumbnail switching + share/copy-link
 */

(function($) {
    'use strict';

    const MoavezeSingle = {
        images: [],
        currentIndex: 0,

        init() {
            if (!$('#single-gallery').length) return;
            this.collectImages();
            this.bindThumbnails();
            this.bindLightbox();
            this.bindShare();
        },

        /**
         * Collect all gallery images (main + thumbs) into an ordered array
         * used for lightbox prev/next navigation.
         */
        collectImages() {
            this.images = [];
            $('.gallery-thumb').each((i, el) => {
                this.images.push($(el).data('full'));
            });
            // Fallback: no thumbs (single image) - use the main image only.
            if (this.images.length === 0 && $('.gallery-main').data('full')) {
                this.images.push($('.gallery-main').data('full'));
            }
        },

        /**
         * Clicking a thumbnail swaps the main image (no page reload) and
         * marks that thumbnail active.
         */
        bindThumbnails() {
            $(document).on('click', '.gallery-thumb', function() {
                const large = $(this).data('large') || $(this).data('full');
                const full = $(this).data('full');
                $('.gallery-main img').attr('src', large);
                $('.gallery-main').data('full', full);
                $('.gallery-thumb').removeClass('active');
                $(this).addClass('active');
            });
        },

        /**
         * Full-screen lightbox: opens on main-image click or the expand
         * button, supports prev/next, ESC to close, and click-outside.
         */
        bindLightbox() {
            const $lightbox = $('#moaveze-lightbox');
            const $img = $('#lightbox-image');

            const open = (index) => {
                if (!this.images.length) return;
                this.currentIndex = ((index % this.images.length) + this.images.length) % this.images.length;
                $img.attr('src', this.images[this.currentIndex]);
                $('#lightbox-counter').text((this.currentIndex + 1) + ' / ' + this.images.length);
                $lightbox.addClass('active');
                $('body').css('overflow', 'hidden');
            };

            const close = () => {
                $lightbox.removeClass('active');
                $('body').css('overflow', '');
            };

            $(document).on('click', '.gallery-main, .gallery-expand-btn', (e) => {
                e.preventDefault();
                const idx = this.images.indexOf($('.gallery-main').data('full'));
                open(idx === -1 ? 0 : idx);
            });

            $(document).on('click', '.lightbox-next', () => open(this.currentIndex + 1));
            $(document).on('click', '.lightbox-prev', () => open(this.currentIndex - 1));
            $(document).on('click', '.lightbox-close', close);

            // Click on the dark backdrop (but not the image itself) closes it.
            $lightbox.on('click', function(e) {
                if (e.target === this) close();
            });

            $(document).on('keydown', (e) => {
                if (!$lightbox.hasClass('active')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft') open(this.currentIndex + 1); // RTL: left = next
                if (e.key === 'ArrowRight') open(this.currentIndex - 1); // RTL: right = prev
            });
        },

        /**
         * "کپی لینک" button - copies the current listing URL to the
         * clipboard and shows a toast confirmation.
         */
        bindShare() {
            $(document).on('click', '#share-copy-link', function() {
                const url = $(this).data('url');
                const fallbackCopy = () => {
                    const $tmp = $('<input>').val(url).appendTo('body').select();
                    document.execCommand('copy');
                    $tmp.remove();
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).catch(fallbackCopy);
                } else {
                    fallbackCopy();
                }

                if (window.MoavezePlus && MoavezePlus.showToast) {
                    MoavezePlus.showToast('لینک آگهی کپی شد', 'success');
                }
            });
        }
    };

    $(document).ready(() => MoavezeSingle.init());

})(jQuery);
