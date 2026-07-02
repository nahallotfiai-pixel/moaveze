<?php
/**
 * Analytics Admin View
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap moaveze-analytics-page">
    <h1><span class="dashicons dashicons-chart-area"></span> آنالیتیکس معاوضه پلاس</h1>

    <!-- Period Selector -->
    <div class="analytics-toolbar">
        <select id="analytics-period" class="analytics-period-select">
            <option value="7">۷ روز اخیر</option>
            <option value="30" selected>۳۰ روز اخیر</option>
            <option value="90">۹۰ روز اخیر</option>
            <option value="365">یک سال اخیر</option>
        </select>
        <button id="refresh-analytics" class="button">
            <span class="dashicons dashicons-update"></span> بروزرسانی
        </button>
    </div>

    <!-- Overview Cards -->
    <div class="analytics-overview" id="analytics-overview">
        <div class="analytics-card primary">
            <div class="ac-icon"><span class="dashicons dashicons-building"></span></div>
            <div class="ac-body"><h3 id="stat-new-listings">-</h3><p>آگهی جدید</p></div>
        </div>
        <div class="analytics-card success">
            <div class="ac-icon"><span class="dashicons dashicons-update"></span></div>
            <div class="ac-body"><h3 id="stat-matches">-</h3><p>تطابق یافته</p></div>
        </div>
        <div class="analytics-card warning">
            <div class="ac-icon"><span class="dashicons dashicons-email-alt"></span></div>
            <div class="ac-body"><h3 id="stat-offers">-</h3><p>پیشنهاد</p></div>
        </div>
        <div class="analytics-card info">
            <div class="ac-icon"><span class="dashicons dashicons-visibility"></span></div>
            <div class="ac-body"><h3 id="stat-views">-</h3><p>بازدید</p></div>
        </div>
        <div class="analytics-card purple">
            <div class="ac-icon"><span class="dashicons dashicons-chart-bar"></span></div>
            <div class="ac-body"><h3 id="stat-revenue">-</h3><p>درآمد (تومان)</p></div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="analytics-charts-row">
        <div class="analytics-chart-card wide">
            <div class="chart-header">
                <h3>نمودار فعالیت</h3>
                <select id="chart-metric">
                    <option value="listings">آگهی‌ها</option>
                    <option value="offers">پیشنهادات</option>
                    <option value="matches">تطابق‌ها</option>
                    <option value="revenue">درآمد</option>
                    <option value="views">بازدیدها</option>
                </select>
            </div>
            <canvas id="activity-chart" height="280"></canvas>
        </div>
    </div>

    <!-- Two Column Data -->
    <div class="analytics-two-col">
        <!-- Popular Districts -->
        <div class="analytics-chart-card">
            <h3>مناطق پرطرفدار</h3>
            <div id="districts-chart-container">
                <canvas id="districts-chart" height="250"></canvas>
            </div>
            <div id="districts-list" class="analytics-list"></div>
        </div>

        <!-- Trending Types -->
        <div class="analytics-chart-card">
            <h3>انواع ملک پرتقاضا</h3>
            <div id="types-chart-container">
                <canvas id="types-chart" height="250"></canvas>
            </div>
            <div id="types-list" class="analytics-list"></div>
        </div>
    </div>

    <!-- Conversion Rates -->
    <div class="analytics-chart-card">
        <h3>نرخ تبدیل</h3>
        <div class="conversion-grid" id="conversion-grid">
            <div class="conversion-item">
                <div class="conv-circle" id="conv-listing-offer"><span>-</span></div>
                <p>آگهی → پیشنهاد</p>
            </div>
            <div class="conversion-item">
                <div class="conv-circle" id="conv-offer-accept"><span>-</span></div>
                <p>پذیرش پیشنهاد</p>
            </div>
            <div class="conversion-item">
                <div class="conv-circle" id="conv-match-complete"><span>-</span></div>
                <p>تکمیل معامله</p>
            </div>
            <div class="conversion-item">
                <div class="conv-circle" id="conv-avg-time"><span>-</span></div>
                <p>میانگین زمان تطابق (روز)</p>
            </div>
        </div>
    </div>

    <!-- Revenue Breakdown -->
    <div class="analytics-chart-card">
        <h3>تفکیک درآمد</h3>
        <div class="revenue-breakdown" id="revenue-breakdown">
            <div class="rev-item">
                <span class="rev-label">اشتراک‌ها</span>
                <span class="rev-value" id="rev-subscription">-</span>
                <div class="rev-bar"><div class="rev-bar-fill sub-fill" id="rev-sub-bar"></div></div>
            </div>
            <div class="rev-item">
                <span class="rev-label">مشاهده تماس</span>
                <span class="rev-value" id="rev-contact">-</span>
                <div class="rev-bar"><div class="rev-bar-fill contact-fill" id="rev-contact-bar"></div></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
jQuery(function($){
    var activityChart = null, districtsChart = null, typesChart = null;

    function loadAnalytics(){
        var period = $('#analytics-period').val();
        $.post(ajaxurl, {action:'moaveze_get_analytics', nonce:moavezeAdmin.nonce, period:period}, function(r){
            if(!r.success) return;
            var d = r.data;

            // Overview
            $('#stat-new-listings').text(d.overview.new_listings.toLocaleString('fa-IR'));
            $('#stat-matches').text(d.overview.total_matches.toLocaleString('fa-IR'));
            $('#stat-offers').text(d.overview.total_offers.toLocaleString('fa-IR'));
            $('#stat-views').text(d.overview.total_views.toLocaleString('fa-IR'));
            $('#stat-revenue').text(d.overview.total_revenue.toLocaleString('fa-IR'));

            // Conversion rates
            $('#conv-listing-offer span').text(d.conversion_rates.listing_to_offer + '%');
            $('#conv-offer-accept span').text(d.conversion_rates.offer_acceptance + '%');
            $('#conv-match-complete span').text(d.conversion_rates.match_completion + '%');
            $('#conv-avg-time span').text(d.conversion_rates.avg_time_to_match);

            // Revenue
            $('#rev-subscription').text(d.revenue_data.formatted.subscription);
            $('#rev-contact').text(d.revenue_data.formatted.contact);
            var total = d.revenue_data.total_revenue || 1;
            $('#rev-sub-bar').css('width', (d.revenue_data.subscription_revenue/total*100)+'%');
            $('#rev-contact-bar').css('width', (d.revenue_data.contact_revenue/total*100)+'%');

            // Districts chart
            renderDistrictsChart(d.popular_districts);

            // Types chart
            renderTypesChart(d.trending_types);

            // Load default activity chart
            loadChart($('#chart-metric').val());
        });
    }

    function loadChart(metric){
        var period = $('#analytics-period').val();
        $.post(ajaxurl, {action:'moaveze_get_chart_data', nonce:moavezeAdmin.nonce, metric:metric, period:period}, function(r){
            if(!r.success) return;
            renderActivityChart(r.data, metric);
        });
    }

    function renderActivityChart(data, metric){
        var ctx = document.getElementById('activity-chart').getContext('2d');
        if(activityChart) activityChart.destroy();

        var colors = {listings:'#6366f1',offers:'#f59e0b',matches:'#10b981',revenue:'#3b82f6',views:'#8b5cf6'};
        var labels_fa = {listings:'آگهی',offers:'پیشنهاد',matches:'تطابق',revenue:'درآمد (تومان)',views:'بازدید'};

        activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: labels_fa[metric],
                    data: data.values,
                    borderColor: colors[metric],
                    backgroundColor: colors[metric] + '20',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function renderDistrictsChart(districts){
        var ctx = document.getElementById('districts-chart').getContext('2d');
        if(districtsChart) districtsChart.destroy();

        var colors = ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#06b6d4'];

        districtsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: districts.map(function(d){ return d.name; }),
                datasets: [{
                    data: districts.map(function(d){ return d.count; }),
                    backgroundColor: colors.slice(0, districts.length),
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { font: { family: 'Tahoma' } } } }
            }
        });

        // List
        var html = '';
        districts.forEach(function(d,i){
            html += '<div class="list-item"><span class="dot" style="background:'+colors[i]+'"></span><span>'+d.name+'</span><strong>'+d.count+' آگهی | '+d.avg_value_formatted+'</strong></div>';
        });
        $('#districts-list').html(html);
    }

    function renderTypesChart(types){
        var ctx = document.getElementById('types-chart').getContext('2d');
        if(typesChart) typesChart.destroy();

        var colors = ['#6366f1','#10b981','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#ec4899','#14b8a6'];

        typesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: types.map(function(t){ return t.name; }),
                datasets: [{
                    label: 'تعداد',
                    data: types.map(function(t){ return t.count; }),
                    backgroundColor: colors.slice(0, types.length),
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        var html = '';
        types.forEach(function(t,i){
            html += '<div class="list-item"><span class="dot" style="background:'+colors[i]+'"></span><span>'+t.name+'</span><strong>'+t.percentage+'% ('+t.count+')</strong></div>';
        });
        $('#types-list').html(html);
    }

    // Events
    $('#analytics-period, #refresh-analytics').on('change click', loadAnalytics);
    $('#chart-metric').on('change', function(){ loadChart($(this).val()); });

    // Initial load
    loadAnalytics();
});
</script>
