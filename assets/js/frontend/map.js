/**
 * Moaveze Plus - Map JS
 * Interactive map with markers, clustering, and sidebar
 */

(function($) {
    'use strict';

    const MoavezeMap = {
        maps: {},
        markers: [],
        markerLayer: null,

        init() {
            this.initListingsMap();
            this.initFullMap();
            this.initSingleMap();
        },

        /**
         * Initialize listings page map
         */
        initListingsMap() {
            const $map = $('#listings-map');
            if (!$map.length) return;

            const centerLat = parseFloat(moavezePlus.mapCenter.lat) || 38.0962;
            const centerLng = parseFloat(moavezePlus.mapCenter.lng) || 46.2738;
            const zoom = parseInt(moavezePlus.mapZoom) || 12;

            this.maps.listings = L.map('listings-map').setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(this.maps.listings);

            this.markerLayer = L.layerGroup().addTo(this.maps.listings);

            // Load markers from cards
            this.loadMarkersFromCards();
        },

        /**
         * Load markers from listing cards on page
         */
        loadMarkersFromCards() {
            if (!this.maps.listings) return;

            this.markerLayer.clearLayers();

            $('.moaveze-card[data-lat][data-lng]').each((_, el) => {
                const $card = $(el);
                const lat = parseFloat($card.data('lat'));
                const lng = parseFloat($card.data('lng'));

                if (!lat || !lng) return;

                const title = $card.find('.moaveze-card-title a').text();
                const price = $card.find('.moaveze-card-price').text();
                const image = $card.find('.moaveze-card-image img').attr('src');
                const url = $card.find('.moaveze-card-title a').attr('href');

                const marker = L.circleMarker([lat, lng], {
                    radius: 8,
                    fillColor: '#6366f1',
                    fillOpacity: 0.9,
                    color: '#fff',
                    weight: 2,
                }).addTo(this.markerLayer);

                // Popup
                const popupContent = `
                    <div class="moaveze-map-popup">
                        ${image ? `<div class="popup-image"><img src="${image}" alt="${title}"></div>` : ''}
                        <div class="popup-title">${title}</div>
                        <div class="popup-price">${price}</div>
                        <a href="${url}" class="popup-link">مشاهده جزئیات</a>
                    </div>
                `;

                marker.bindPopup(popupContent, { maxWidth: 250 });

                // Hover effect
                marker.on('mouseover', function() {
                    this.setRadius(12);
                    $card.css('box-shadow', '0 0 0 3px #6366f1');
                });

                marker.on('mouseout', function() {
                    this.setRadius(8);
                    $card.css('box-shadow', '');
                });

                // Card hover highlights marker
                $card.on('mouseenter', () => marker.setRadius(12));
                $card.on('mouseleave', () => marker.setRadius(8));
            });
        },

        /**
         * Initialize full-page map
         */
        initFullMap() {
            const $map = $('#moaveze-fullmap');
            if (!$map.length) return;

            const centerLat = parseFloat(moavezePlus.mapCenter.lat) || 38.0962;
            const centerLng = parseFloat(moavezePlus.mapCenter.lng) || 46.2738;
            const zoom = parseInt(moavezePlus.mapZoom) || 12;

            this.maps.full = L.map('moaveze-fullmap').setView([centerLat, centerLng], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(this.maps.full);

            this.loadFullMapData();
        },

        /**
         * Load data for full map via AJAX
         */
        loadFullMapData() {
            const type = $('#moaveze-fullmap').data('type') || '';
            const district = $('#moaveze-fullmap').data('district') || '';

            $.ajax({
                url: moavezePlus.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'moaveze_get_map_data',
                    type: type,
                    district: district,
                },
                success: (response) => {
                    if (response.success) {
                        this.renderFullMapMarkers(response.data.markers);
                        $('#map-results-count').text(response.data.count + ' مورد');
                    }
                }
            });
        },

        /**
         * Render markers on full map
         */
        renderFullMapMarkers(markers) {
            if (!this.maps.full) return;

            const sidebarList = $('#map-sidebar-list');
            sidebarList.empty();

            markers.forEach(item => {
                // Map marker
                const markerColor = item.verified ? '#10b981' : '#6366f1';

                const marker = L.circleMarker([item.lat, item.lng], {
                    radius: 10,
                    fillColor: markerColor,
                    fillOpacity: 0.9,
                    color: '#fff',
                    weight: 3,
                }).addTo(this.maps.full);

                const exchangeLabels = {
                    'property_only': 'ملک با ملک',
                    'property_cash': 'ملک + نقد',
                    'property_car': 'ملک + خودرو',
                    'property_mixed': 'ترکیبی',
                    'flexible': 'انعطاف‌پذیر',
                };

                const popup = `
                    <div class="moaveze-map-popup">
                        ${item.image ? `<div class="popup-image"><img src="${item.image}" alt=""></div>` : ''}
                        <div class="popup-title">${item.title}</div>
                        <div class="popup-meta">${item.district} | ${item.type} | ${item.area} متر</div>
                        <div class="popup-price">${Number(item.value).toLocaleString('fa-IR')} تومان</div>
                        <a href="${item.url}" class="popup-link">مشاهده و ارسال پیشنهاد</a>
                    </div>
                `;
                marker.bindPopup(popup, { maxWidth: 260 });

                // Sidebar card
                const card = $(`
                    <div class="map-sidebar-card" data-id="${item.id}">
                        <div class="card-thumb">
                            ${item.image ? `<img src="${item.image}" alt="">` : '<div style="background:#f1f5f9;width:100%;height:100%;"></div>'}
                        </div>
                        <div class="card-info">
                            <h4>${item.title}</h4>
                            <span class="card-district">${item.district} | ${exchangeLabels[item.exchange] || 'معاوضه'}</span>
                            <span class="card-price">${Number(item.value).toLocaleString('fa-IR')} تومان</span>
                        </div>
                    </div>
                `);

                card.on('click', () => {
                    this.maps.full.setView([item.lat, item.lng], 15);
                    marker.openPopup();
                    sidebarList.find('.map-sidebar-card').removeClass('active');
                    card.addClass('active');
                });

                marker.on('click', () => {
                    sidebarList.find('.map-sidebar-card').removeClass('active');
                    card.addClass('active');
                    // Scroll sidebar to card
                    sidebarList.animate({
                        scrollTop: card.position().top + sidebarList.scrollTop() - 12
                    }, 300);
                });

                sidebarList.append(card);
            });

            // Fit bounds if markers exist
            if (markers.length > 0) {
                const bounds = L.latLngBounds(markers.map(m => [m.lat, m.lng]));
                this.maps.full.fitBounds(bounds, { padding: [30, 30] });
            }
        },

        /**
         * Initialize single listing map
         */
        initSingleMap() {
            const $map = $('#single-map');
            if (!$map.length) return;

            const lat = parseFloat($map.data('lat'));
            const lng = parseFloat($map.data('lng'));

            if (!lat || !lng) return;

            const map = L.map('single-map').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);

            L.marker([lat, lng]).addTo(map);
        }
    };

    // Initialize
    $(document).ready(() => MoavezeMap.init());
    window.MoavezeMap = MoavezeMap;

})(jQuery);
