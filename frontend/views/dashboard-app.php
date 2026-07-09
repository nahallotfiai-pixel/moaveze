<?php if (!defined('ABSPATH')) exit; ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>پنل مدیریت - معاوضه پلاس</title>
<link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
<style>

*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Vazirmatn',-apple-system,sans-serif;background:#f1f5f9;color:#1e293b;line-height:1.6;direction:rtl;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}
button{cursor:pointer;font-family:inherit;border:none;outline:none;}
input,select,textarea{font-family:inherit;outline:none;}
::-webkit-scrollbar{width:6px;height:6px;}
::-webkit-scrollbar-track{background:#f1f5f9;}
::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px;}
::-webkit-scrollbar-thumb:hover{background:#94a3b8;}

/* Layout */
.dashboard-wrap{display:flex;min-height:100vh;}
.sidebar{width:280px;background:#1e1b4b;color:#fff;position:fixed;top:0;right:0;height:100vh;overflow-y:auto;transition:transform .3s ease;z-index:1000;}
.sidebar-header{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:12px;}
.sidebar-header img{width:40px;height:40px;border-radius:50%;border:2px solid rgba(255,255,255,.2);}
.sidebar-header .user-info h3{font-size:14px;font-weight:600;color:#fff;}
.sidebar-header .user-info span{font-size:12px;color:#a5b4fc;display:block;margin-top:2px;}
.sidebar-nav{padding:16px 0;}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 24px;color:#c7d2fe;font-size:14px;font-weight:500;transition:all .2s;cursor:pointer;border-right:3px solid transparent;}
.nav-item:hover{background:rgba(255,255,255,.05);color:#fff;}
.nav-item.active{background:rgba(99,102,241,.2);color:#fff;border-right-color:#6366f1;}
.nav-item .icon{font-size:18px;width:24px;text-align:center;}
.nav-item .badge{margin-right:auto;background:#ef4444;color:#fff;font-size:11px;padding:2px 7px;border-radius:10px;font-weight:600;}

.sidebar-footer{position:absolute;bottom:0;right:0;left:0;padding:16px 20px;border-top:1px solid rgba(255,255,255,.1);background:#1e1b4b;}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:#a5b4fc;font-size:13px;transition:color .2s;}
.sidebar-footer a:hover{color:#fff;}
.main-content{margin-right:280px;flex:1;min-height:100vh;padding:32px;}
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;}
.top-bar h1{font-size:22px;font-weight:700;color:#1e1b4b;}
.top-bar-actions{display:flex;align-items:center;gap:12px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:500;transition:all .2s;}
.btn-primary{background:#6366f1;color:#fff;}
.btn-primary:hover{background:#4f46e5;transform:translateY(-1px);box-shadow:0 4px 12px rgba(99,102,241,.3);}
.btn-secondary{background:#fff;color:#475569;border:1px solid #e2e8f0;}
.btn-secondary:hover{background:#f8fafc;border-color:#cbd5e1;}
.btn-success{background:#10b981;color:#fff;}
.btn-success:hover{background:#059669;}
.btn-danger{background:#ef4444;color:#fff;}
.btn-danger:hover{background:#dc2626;}
.btn-warning{background:#f59e0b;color:#fff;}
.btn-warning:hover{background:#d97706;}
.btn-sm{padding:6px 14px;font-size:13px;border-radius:8px;}
.btn-xs{padding:4px 10px;font-size:12px;border-radius:6px;}

/* Cards */
.stat-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin-bottom:32px;}
.stat-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);transition:all .2s;}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08);}
.stat-card .card-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:16px;}
.stat-card .card-icon.indigo{background:#eef2ff;color:#6366f1;}
.stat-card .card-icon.green{background:#ecfdf5;color:#10b981;}
.stat-card .card-icon.amber{background:#fffbeb;color:#f59e0b;}
.stat-card .card-icon.rose{background:#fff1f2;color:#f43f5e;}
.stat-card .card-icon.purple{background:#faf5ff;color:#a855f7;}
.stat-card .card-icon.cyan{background:#ecfeff;color:#06b6d4;}
.stat-card .card-value{font-size:28px;font-weight:700;color:#1e1b4b;margin-bottom:4px;}
.stat-card .card-label{font-size:13px;color:#64748b;}
/* Panel / Card container */
.panel{background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:24px;}
.panel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;}
.panel-header h2{font-size:16px;font-weight:600;color:#1e1b4b;}

/* Table */
.data-table{width:100%;border-collapse:collapse;}
.data-table th{text-align:right;padding:12px 16px;font-size:12px;font-weight:600;color:#64748b;background:#f8fafc;border-bottom:1px solid #e2e8f0;}
.data-table td{padding:12px 16px;font-size:13px;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.data-table tr:hover td{background:#f8fafc;}
.data-table .status-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.status-published{background:#ecfdf5;color:#059669;}
.status-pending{background:#fffbeb;color:#d97706;}
.status-draft{background:#f1f5f9;color:#64748b;}
.status-accepted{background:#ecfdf5;color:#059669;}
.status-rejected{background:#fff1f2;color:#e11d48;}
.status-negotiating{background:#eef2ff;color:#6366f1;}
/* Tabs */
.tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #f1f5f9;padding-bottom:0;}
.tab-btn{padding:10px 20px;font-size:14px;font-weight:500;color:#64748b;background:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;}
.tab-btn:hover{color:#6366f1;}
.tab-btn.active{color:#6366f1;border-bottom-color:#6366f1;font-weight:600;}
/* Search & Filter */
.search-bar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.search-input{flex:1;min-width:200px;padding:10px 16px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fff;transition:border-color .2s;}
.search-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.filter-select{padding:10px 16px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;background:#fff;color:#475569;min-width:140px;}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center;padding:20px;animation:fadeIn .2s;}
.modal-overlay.active{display:flex;}
.modal{background:#fff;border-radius:20px;width:100%;max-width:600px;max-height:85vh;overflow-y:auto;padding:32px;position:relative;animation:slideUp .3s;}
.modal-close{position:absolute;top:16px;left:16px;width:32px;height:32px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:18px;color:#64748b;transition:all .2s;}
.modal-close:hover{background:#e2e8f0;color:#1e293b;}
.modal h3{font-size:18px;font-weight:600;color:#1e1b4b;margin-bottom:20px;}
/* Toast */
.toast-container{position:fixed;top:24px;left:24px;z-index:99999;display:flex;flex-direction:column;gap:8px;}
.toast{display:flex;align-items:center;gap:12px;padding:14px 20px;border-radius:12px;font-size:14px;font-weight:500;color:#fff;animation:slideIn .3s;box-shadow:0 8px 24px rgba(0,0,0,.15);}
.toast-success{background:#10b981;}
.toast-error{background:#ef4444;}
.toast-warning{background:#f59e0b;}
.toast .toast-icon{font-size:18px;}
/* Skeleton */
.skeleton{background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:8px;}
.skeleton-text{height:14px;margin-bottom:8px;width:80%;}
.skeleton-card{height:120px;border-radius:16px;}

/* Empty state */
.empty-state{text-align:center;padding:60px 20px;}
.empty-state .empty-icon{font-size:64px;margin-bottom:16px;opacity:.7;}
.empty-state h3{font-size:18px;font-weight:600;color:#1e1b4b;margin-bottom:8px;}
.empty-state p{font-size:14px;color:#64748b;max-width:320px;margin:0 auto 20px;}
/* Form */
.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
.form-control{width:100%;padding:10px 16px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;transition:border-color .2s;}
.form-control:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
/* Match card */
.match-card{border:1px solid #e2e8f0;border-radius:16px;padding:20px;margin-bottom:16px;transition:all .2s;}
.match-card:hover{border-color:#6366f1;box-shadow:0 4px 16px rgba(99,102,241,.1);}
.match-score{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700;}
.match-score.high{background:#ecfdf5;color:#059669;}
.match-score.medium{background:#fffbeb;color:#d97706;}
.match-score.low{background:#fff1f2;color:#e11d48;}
.match-sides{display:grid;grid-template-columns:1fr auto 1fr;gap:16px;align-items:center;margin-top:16px;}
.match-sides .arrow{font-size:24px;color:#6366f1;}
.match-side{padding:12px;background:#f8fafc;border-radius:12px;}
.match-side h4{font-size:13px;font-weight:600;margin-bottom:4px;}
.match-side p{font-size:12px;color:#64748b;}

/* Valuation */
.val-result{background:linear-gradient(135deg,#eef2ff,#faf5ff);border:1px solid #e0e7ff;border-radius:16px;padding:24px;margin-top:20px;}
.val-result .val-price{font-size:32px;font-weight:800;color:#4f46e5;margin-bottom:8px;}
.val-result .val-range{font-size:14px;color:#6366f1;}
.val-confidence{display:flex;align-items:center;gap:8px;margin-top:12px;}
.val-confidence .conf-bar{flex:1;height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;}
.val-confidence .conf-fill{height:100%;border-radius:4px;transition:width .5s;}
.conf-high{background:#10b981;width:90%;}
.conf-medium{background:#f59e0b;width:60%;}
.conf-low{background:#ef4444;width:30%;}
/* Activity feed */
.activity-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid #f1f5f9;}
.activity-item:last-child{border-bottom:none;}
.activity-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.activity-content{flex:1;}
.activity-content p{font-size:13px;color:#374151;}
.activity-content .time{font-size:11px;color:#94a3b8;margin-top:2px;}
/* Quick actions */
.quick-actions{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;}
.quick-action-btn{display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;transition:all .2s;font-size:13px;font-weight:500;color:#475569;}
.quick-action-btn:hover{background:#eef2ff;border-color:#c7d2fe;color:#4f46e5;transform:translateY(-2px);}
.quick-action-btn .qa-icon{font-size:24px;}

/* Toggle / Switch */
.toggle-wrap{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f1f5f9;}
.toggle-wrap:last-child{border-bottom:none;}
.toggle-label{font-size:14px;color:#374151;}
.toggle{position:relative;width:44px;height:24px;background:#cbd5e1;border-radius:12px;transition:background .2s;cursor:pointer;}
.toggle.on{background:#6366f1;}
.toggle::after{content:'';position:absolute;top:3px;right:3px;width:18px;height:18px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.1);}
.toggle.on::after{transform:translateX(-20px);}
/* Animations */
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideIn{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
/* Page transitions */
.page{display:none;animation:fadeIn .3s ease;}
.page.active{display:block;}
/* Hamburger */
.hamburger{display:none;position:fixed;top:16px;right:16px;z-index:1001;width:40px;height:40px;background:#1e1b4b;border-radius:10px;align-items:center;justify-content:center;font-size:20px;color:#fff;}

/* Responsive */
@media(max-width:768px){
.hamburger{display:flex;}
.sidebar{transform:translateX(100%);width:260px;}
.sidebar.open{transform:translateX(0);}
.main-content{margin-right:0;padding:16px;padding-top:64px;}
.stat-cards{grid-template-columns:repeat(2,1fr);gap:12px;}
.stat-card{padding:16px;}
.stat-card .card-value{font-size:22px;}
.form-row{grid-template-columns:1fr;}
.match-sides{grid-template-columns:1fr;text-align:center;}
.search-bar{flex-direction:column;align-items:stretch;}
.data-table{display:block;overflow-x:auto;}
.top-bar{flex-direction:column;align-items:flex-start;gap:12px;}
.top-bar-actions{width:100%;justify-content:flex-end;}
.quick-actions{grid-template-columns:repeat(2,1fr);}
}
@media(min-width:769px) and (max-width:1024px){
.stat-cards{grid-template-columns:repeat(3,1fr);}
.sidebar{width:240px;}
.main-content{margin-right:240px;}
}
/* Pagination */
.pagination{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:20px;}
.pagination button{width:36px;height:36px;border-radius:8px;background:#fff;border:1px solid #e2e8f0;font-size:13px;color:#475569;transition:all .2s;}
.pagination button:hover{background:#eef2ff;border-color:#c7d2fe;color:#6366f1;}
.pagination button.active{background:#6366f1;color:#fff;border-color:#6366f1;}
.pagination button:disabled{opacity:.4;cursor:not-allowed;}
</style>

</head>
<body>
<div class="toast-container" id="toastContainer"></div>
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)closeModal()">
<div class="modal" id="modalContent"></div>
</div>
<button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">☰</button>
<div class="dashboard-wrap">
<aside class="sidebar" id="sidebar">
<div class="sidebar-header">
<img src="<?php echo esc_url($dashboard_data['user']['avatar']); ?>" alt="avatar">
<div class="user-info">
<h3><?php echo esc_html($dashboard_data['user']['display_name']); ?></h3>
<span><?php echo $dashboard_data['user']['is_admin'] ? 'مدیر سایت' : 'مشاور'; ?></span>
</div>
</div>
<nav class="sidebar-nav">
<div class="nav-item active" data-page="home" onclick="navigateTo('home')"><span class="icon">🏠</span>خانه</div>
<div class="nav-item" data-page="listings" onclick="navigateTo('listings')"><span class="icon">📋</span>آگهی‌ها</div>
<div class="nav-item" data-page="offers" onclick="navigateTo('offers')"><span class="icon">💰</span>پیشنهادات<span class="badge" id="offersCount" style="display:none"></span></div>
<div class="nav-item" data-page="matches" onclick="navigateTo('matches')"><span class="icon">🎯</span>تطبیق‌ها</div>
<div class="nav-item" data-page="chains" onclick="navigateTo('chains')"><span class="icon">🔗</span>زنجیره‌ای</div>
<div class="nav-item" data-page="valuation" onclick="navigateTo('valuation')"><span class="icon">📊</span>ارزش‌گذاری</div>
<div class="nav-item" data-page="houzez" onclick="navigateTo('houzez')"><span class="icon">🏗️</span>آگهی‌های فروش</div>
<div class="nav-item" data-page="consultants" onclick="navigateTo('consultants')"><span class="icon">👥</span>مشاوران</div>
<?php if ($dashboard_data['user']['is_admin']): ?>
<div class="nav-item" data-page="settings" onclick="navigateTo('settings')"><span class="icon">⚙️</span>تنظیمات</div>
<?php endif; ?>
</nav>
<div class="sidebar-footer">
<a href="<?php echo esc_url($dashboard_data['home_url']); ?>">🌐 بازگشت به سایت</a>
<a href="<?php echo esc_url($dashboard_data['logout_url']); ?>" style="margin-top:8px;">🚪 خروج</a>
</div>
</aside>

<main class="main-content">
<!-- HOME PAGE -->
<div class="page active" id="page-home">
<div class="top-bar"><h1>داشبورد</h1><div class="top-bar-actions"><button class="btn btn-primary" onclick="navigateTo('listings')">+ آگهی جدید</button></div></div>
<div class="stat-cards" id="homeStats">
<div class="stat-card"><div class="card-icon indigo">📋</div><div class="card-value skeleton skeleton-text" id="statTotal">-</div><div class="card-label">کل آگهی‌ها</div></div>
<div class="stat-card"><div class="card-icon amber">⏳</div><div class="card-value skeleton skeleton-text" id="statPending">-</div><div class="card-label">در انتظار تأیید</div></div>
<div class="stat-card"><div class="card-icon green">💰</div><div class="card-value skeleton skeleton-text" id="statOffers">-</div><div class="card-label">پیشنهادات فعال</div></div>
<div class="stat-card"><div class="card-icon purple">🎯</div><div class="card-value skeleton skeleton-text" id="statMatches">-</div><div class="card-label">تطبیق‌ها</div></div>
<div class="stat-card"><div class="card-icon cyan">📊</div><div class="card-value skeleton skeleton-text" id="statValuations">-</div><div class="card-label">ارزش‌گذاری</div></div>
<div class="stat-card"><div class="card-icon rose">👁️</div><div class="card-value skeleton skeleton-text" id="statViews">-</div><div class="card-label">بازدید کل</div></div>
</div>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
<div class="panel"><div class="panel-header"><h2>فعالیت‌های اخیر</h2></div><div id="activityFeed"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:60%"></div><div class="skeleton skeleton-text" style="width:70%"></div></div></div>
<div class="panel"><div class="panel-header"><h2>دسترسی سریع</h2></div><div class="quick-actions"><button class="quick-action-btn" onclick="navigateTo('listings')"><span class="qa-icon">📝</span>آگهی جدید</button><button class="quick-action-btn" onclick="navigateTo('valuation')"><span class="qa-icon">📊</span>ارزش‌گذاری</button><button class="quick-action-btn" onclick="navigateTo('matches')"><span class="qa-icon">🎯</span>مشاهده تطبیق‌ها</button><button class="quick-action-btn" onclick="navigateTo('offers')"><span class="qa-icon">💬</span>پیشنهادات</button></div></div>
</div>
<div class="panel"><div class="panel-header"><h2>روند آگهی‌ها</h2></div><div id="chartPlaceholder" style="height:200px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;">نمودار روند ثبت آگهی‌ها (۳۰ روز اخیر)</div></div>
</div>

<!-- LISTINGS PAGE -->
<div class="page" id="page-listings">
<div class="top-bar"><h1>مدیریت آگهی‌ها</h1><div class="top-bar-actions"><button class="btn btn-success btn-sm" id="bulkApproveBtn" onclick="bulkApprove()" style="display:none">✓ تأیید همه در انتظار</button></div></div>
<div class="search-bar">
<input type="text" class="search-input" id="listingsSearch" placeholder="جستجو عنوان، منطقه..." oninput="debounce(loadListings,300)()">
<select class="filter-select" id="listingsStatus" onchange="loadListings()"><option value="">همه وضعیت‌ها</option><option value="publish">منتشر شده</option><option value="pending">در انتظار</option><option value="draft">پیش‌نویس</option></select>
</div>
<div class="panel" style="padding:0;overflow:hidden;">
<div id="listingsTable"><div style="padding:24px;"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:60%"></div><div class="skeleton skeleton-text" style="width:80%"></div><div class="skeleton skeleton-text" style="width:50%"></div></div></div>
</div>
<div class="pagination" id="listingsPagination"></div>
</div>

<!-- OFFERS PAGE -->
<div class="page" id="page-offers">
<div class="top-bar"><h1>مدیریت پیشنهادات</h1></div>
<div class="tabs">
<button class="tab-btn active" data-tab="received" onclick="switchOfferTab('received')">دریافت شده</button>
<button class="tab-btn" data-tab="sent" onclick="switchOfferTab('sent')">ارسال شده</button>
</div>
<div id="offersContent"><div class="panel"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:60%"></div></div></div>
<div class="pagination" id="offersPagination"></div>
</div>

<!-- MATCHES PAGE -->
<div class="page" id="page-matches">
<div class="top-bar"><h1>تطبیق‌های هوشمند</h1><div class="top-bar-actions"><button class="btn btn-primary btn-sm" onclick="runMatching()">🔄 اجرای تطبیق جدید</button></div></div>
<div class="search-bar">
<select class="filter-select" id="matchStatusFilter" onchange="loadMatches()">
<option value="all">همه</option>
<option value="new">جدید</option>
<option value="assigned">اختصاص‌یافته</option>
<option value="in_progress">در حال بررسی</option>
<option value="completed">تکمیل‌شده</option>
</select>
<select class="filter-select" id="matchScoreFilter" onchange="loadMatches()">
<option value="0">همه امتیازها</option>
<option value="70">بالای ۷۰٪</option>
<option value="50">بالای ۵۰٪</option>
<option value="30">بالای ۳۰٪</option>
</select>
</div>
<div id="matchesContent"><div class="skeleton skeleton-card" style="margin-bottom:16px;"></div><div class="skeleton skeleton-card"></div></div>
</div>

<!-- CHAINS PAGE -->
<div class="page" id="page-chains">
<div class="top-bar"><h1>معاوضه‌های زنجیره‌ای</h1><div class="top-bar-actions"><button class="btn btn-primary btn-sm" onclick="detectChains()">🔗 شناسایی زنجیره‌های جدید</button></div></div>
<p style="color:#64748b;font-size:13px;margin-bottom:20px;">معاوضه زنجیره‌ای زمانی رخ می‌دهد که چند ملک به‌صورت حلقه‌ای قابل معاوضه باشند. مثلاً: A → B → C → A</p>
<div id="chainsContent"><div class="skeleton skeleton-card" style="margin-bottom:16px;"></div></div>
</div>

<!-- VALUATION PAGE -->
<div class="page" id="page-valuation">
<div class="top-bar"><h1>ارزش‌گذاری ملک</h1></div>
<div class="tabs">
<button class="tab-btn active" data-tab="quick" onclick="switchValTab('quick')">تخمین سریع</button>
<button class="tab-btn" data-tab="full" onclick="switchValTab('full')">ارزش‌گذاری هوش مصنوعی</button>
<button class="tab-btn" data-tab="history" onclick="switchValTab('history')">تاریخچه</button>
</div>
<div id="valQuick" class="panel">
<div class="form-row">
<div class="form-group"><label>متراژ (متر مربع) *</label><input type="number" class="form-control" id="valArea" placeholder="مثلاً ۱۲۰" min="10"></div>
<div class="form-group"><label>نوع ملک</label><select class="form-control" id="valType"><option value="">انتخاب کنید</option></select></div>
</div>
<div class="form-row">
<div class="form-group"><label>منطقه</label><select class="form-control" id="valDistrict"><option value="">انتخاب کنید</option></select></div>
<div class="form-group"><label>تعداد اتاق</label><input type="number" class="form-control" id="valRooms" placeholder="مثلاً ۳" min="0" max="10"></div>
</div>
<div class="form-row">
<div class="form-group"><label>سال ساخت</label><input type="number" class="form-control" id="valYear" placeholder="مثلاً ۱۴۰۰" min="1350" max="1410"></div>
<div class="form-group"><label>طبقه</label><input type="number" class="form-control" id="valFloor" placeholder="مثلاً ۳" min="0" max="50"></div>
</div>
<div class="form-row">
<div class="form-group"><label>امکانات</label>
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px;">
<label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;background:#f8fafc;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><input type="checkbox" class="val-feature" value="parking"> پارکینگ</label>
<label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;background:#f8fafc;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><input type="checkbox" class="val-feature" value="elevator"> آسانسور</label>
<label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;background:#f8fafc;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><input type="checkbox" class="val-feature" value="storage"> انباری</label>
<label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;background:#f8fafc;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><input type="checkbox" class="val-feature" value="balcony"> بالکن</label>
<label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;background:#f8fafc;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><input type="checkbox" class="val-feature" value="pool"> استخر</label>
</div>
</div>
<div class="form-group"><label>توضیحات تکمیلی</label><textarea class="form-control" id="valNotes" rows="2" placeholder="اطلاعات اضافی مثل نوع نما، دسترسی‌ها..."></textarea></div>
</div>
<button class="btn btn-primary" onclick="runQuickEstimate()" style="margin-top:12px;">💡 تخمین قیمت</button>
<div id="quickEstimateResult"></div>
</div>
<div id="valFull" class="panel" style="display:none;">
<div class="form-group"><label>نوع آگهی</label>
<select class="form-control" id="valListingType" onchange="loadListingsForValuation()">
<option value="exchange">آگهی معاوضه</option>
<option value="property">آگهی فروش (Houzez)</option>
</select>
</div>
<div class="form-group"><label>انتخاب آگهی</label><select class="form-control" id="valListingSelect"><option value="">در حال بارگذاری...</option></select></div>
<button class="btn btn-primary" onclick="runAIValuation()">🤖 اجرای ارزش‌گذاری هوش مصنوعی</button>
<div id="aiValuationResult"></div>
</div>
<div id="valHistory" class="panel" style="display:none;">
<div id="valuationHistoryTable"><div class="skeleton skeleton-text"></div></div>
</div>
</div>

<!-- HOUZEZ PAGE -->
<div class="page" id="page-houzez">
<div class="top-bar"><h1>آگهی‌های فروش (Houzez)</h1></div>
<div class="search-bar">
<input type="text" class="search-input" id="houzezSearch" placeholder="جستجو شناسه یا عنوان..." oninput="debounce(loadHouzezProperties,300)()">
</div>
<div class="panel" style="padding:0;overflow:hidden;">
<div id="houzezTable"><div style="padding:24px;"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:60%"></div></div></div>
</div>
<div class="pagination" id="houzezPagination"></div>
</div>

<!-- CONSULTANTS PAGE -->
<div class="page" id="page-consultants">
<div class="top-bar"><h1>مدیریت مشاوران</h1></div>
<div class="panel">
<div class="panel-header"><h2>افزودن مشاور جدید</h2></div>
<div class="search-bar">
<input type="text" class="search-input" id="consultantSearch" placeholder="نام کاربری یا ایمیل..." oninput="debounce(searchUsers,300)()">
<button class="btn btn-primary btn-sm" onclick="searchUsers()">جستجو</button>
</div>
<div id="userSearchResults"></div>
</div>
<div class="panel">
<div class="panel-header"><h2>مشاوران فعلی</h2></div>
<div id="consultantsList"><div class="skeleton skeleton-text"></div></div>
</div>
</div>

<!-- SETTINGS PAGE (Admin only) -->
<?php if ($dashboard_data['user']['is_admin']): ?>
<div class="page" id="page-settings">
<div class="top-bar"><h1>تنظیمات</h1></div>
<div class="panel">
<div class="panel-header"><h2>اعلان‌ها</h2></div>
<div class="toggle-wrap"><span class="toggle-label">اعلان پیشنهاد جدید</span><div class="toggle on" onclick="toggleSetting(this,'notif_new_offer')"></div></div>
<div class="toggle-wrap"><span class="toggle-label">اعلان تطبیق جدید</span><div class="toggle on" onclick="toggleSetting(this,'notif_new_match')"></div></div>
<div class="toggle-wrap"><span class="toggle-label">اعلان تأیید آگهی</span><div class="toggle on" onclick="toggleSetting(this,'notif_listing_approved')"></div></div>
<div class="toggle-wrap"><span class="toggle-label">اعلان SMS</span><div class="toggle" onclick="toggleSetting(this,'notif_sms')"></div></div>
</div>
<div class="panel">
<div class="panel-header"><h2>هوش مصنوعی</h2></div>
<div class="form-group"><label>ارائه‌دهنده AI</label><select class="form-control" id="settingAiProvider"><option value="gemini">Google Gemini</option><option value="chatgpt">OpenAI ChatGPT</option><option value="hermes">Hermes</option><option value="custom">سفارشی</option></select></div>
<div class="form-group"><label>وضعیت SMS</label><p style="font-size:13px;color:#64748b;" id="smsStatus">در حال بارگذاری...</p></div>
</div>
<div class="panel">
<div class="panel-header"><h2>لینک‌های مدیریت</h2></div>
<div style="display:flex;flex-wrap:wrap;gap:12px;">
<a href="<?php echo esc_url($dashboard_data['admin_url'] . 'admin.php?page=moaveze-plus'); ?>" class="btn btn-secondary" target="_blank">⚙️ تنظیمات افزونه</a>
<a href="<?php echo esc_url($dashboard_data['admin_url'] . 'admin.php?page=moaveze-analytics'); ?>" class="btn btn-secondary" target="_blank">📈 آمار و تحلیل</a>
<a href="<?php echo esc_url($dashboard_data['admin_url'] . 'edit.php?post_type=moaveze_exchange'); ?>" class="btn btn-secondary" target="_blank">📋 همه آگهی‌ها (wp-admin)</a>
<a href="<?php echo esc_url($dashboard_data['admin_url'] . 'admin.php?page=moaveze-monetization'); ?>" class="btn btn-secondary" target="_blank">💳 درآمدزایی</a>
</div>
</div>
</div>
<?php endif; ?>

</main>
</div>

<script>
// Global error handler: prevent any unhandled JS error from
// killing the entire page (navigation must ALWAYS work)
window.addEventListener('error', function(e) {
    console.error('Moaveze Dashboard Error:', e.message, e.filename, e.lineno);
});
window.addEventListener('unhandledrejection', function(e) {
    console.error('Moaveze Dashboard Unhandled Promise:', e.reason);
    e.preventDefault(); // Prevent the error from propagating
});
// ═══════════════════════════════════════════════════════════════
// CONFIGURATION
// ═══════════════════════════════════════════════════════════════
const CONFIG = {
    restUrl: <?php echo json_encode($dashboard_data['rest_url']); ?>,
    nonce: <?php echo json_encode($dashboard_data['nonce']); ?>,
    adminNonce: <?php echo json_encode($dashboard_data['admin_nonce']); ?>,
    valuationNonce: <?php echo json_encode($dashboard_data['valuation_nonce']); ?>,
    userId: <?php echo (int) $dashboard_data['user']['id']; ?>,
    isAdmin: <?php echo $dashboard_data['user']['is_admin'] ? 'true' : 'false'; ?>,
    adminUrl: <?php echo json_encode($dashboard_data['admin_url']); ?>,
    homeUrl: <?php echo json_encode($dashboard_data['home_url']); ?>
};

let currentPage = 'home';
let listingsPage = 1;
let offersPage = 1;
let houzezPage = 1;
let currentOfferTab = 'received';
let debounceTimers = {};

// ═══════════════════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ═══════════════════════════════════════════════════════════════
function toPersianDigits(str) {
    if (str === null || str === undefined) return '';
    const persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return String(str).replace(/[0-9]/g, d => persianDigits[parseInt(d)]);
}

function shortPrice(value) {
    if (!value || value <= 0) return '—';
    value = parseInt(value);
    if (value >= 1000000000) return toPersianDigits((value / 1000000000).toFixed(1).replace('.0','')) + ' میلیارد';
    if (value >= 1000000) return toPersianDigits((value / 1000000).toFixed(0)) + ' میلیون';
    if (value >= 1000) return toPersianDigits((value / 1000).toFixed(0)) + ' هزار';
    return toPersianDigits(value) + ' تومان';
}

function debounce(fn, delay) {
    return function(...args) {
        const key = fn.name || 'default';
        clearTimeout(debounceTimers[key]);
        debounceTimers[key] = setTimeout(() => fn.apply(this, args), delay);
    };
}

async function apiCall(endpoint, method = 'GET', body = null, silent = false) {
    const opts = {
        method,
        headers: {
            'X-WP-Nonce': CONFIG.nonce,
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    };
    if (body && method !== 'GET') opts.body = JSON.stringify(body);
    try {
        const url = endpoint.startsWith('http') ? endpoint : CONFIG.restUrl + endpoint;
        const resp = await fetch(url, opts);
        const data = await resp.json();
        if (!resp.ok) throw new Error(data?.error?.message || data?.message || 'خطای سرور');
        return data;
    } catch(err) {
        if (!silent) showToast(err.message || 'خطا در ارتباط با سرور', 'error');
        throw err;
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const icons = { success: '✓', error: '✗', warning: '⚠' };
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<span class="toast-icon">' + (icons[type]||'ℹ') + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(-20px)'; setTimeout(() => toast.remove(), 300); }, 4000);
}

function showModal(title, content) {
    document.getElementById('modalContent').innerHTML = '<button class="modal-close" onclick="closeModal()">✕</button><h3>' + title + '</h3>' + content;
    document.getElementById('modalOverlay').classList.add('active');
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
}

function getStatusLabel(status) {
    const labels = { publish: 'منتشر شده', pending: 'در انتظار', draft: 'پیش‌نویس', accepted: 'قبول شده', rejected: 'رد شده', negotiating: 'در حال مذاکره', withdrawn: 'لغو شده' };
    return labels[status] || status;
}

function getStatusClass(status) {
    const classes = { publish: 'status-published', pending: 'status-pending', draft: 'status-draft', accepted: 'status-accepted', rejected: 'status-rejected', negotiating: 'status-negotiating' };
    return classes[status] || 'status-draft';
}

function renderPagination(containerId, currentPg, totalPages, callback) {
    const c = document.getElementById(containerId);
    if (totalPages <= 1) { c.innerHTML = ''; return; }
    let html = '<button ' + (currentPg <= 1 ? 'disabled' : '') + ' onclick="' + callback + '(' + (currentPg-1) + ')">‹</button>';
    for (let i = 1; i <= Math.min(totalPages, 7); i++) {
        html += '<button class="' + (i === currentPg ? 'active' : '') + '" onclick="' + callback + '(' + i + ')">' + toPersianDigits(i) + '</button>';
    }
    html += '<button ' + (currentPg >= totalPages ? 'disabled' : '') + ' onclick="' + callback + '(' + (currentPg+1) + ')">›</button>';
    c.innerHTML = html;
}

// ═══════════════════════════════════════════════════════════════
// NAVIGATION
// ═══════════════════════════════════════════════════════════════
function navigateTo(page) {
    currentPage = page;
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const pageEl = document.getElementById('page-' + page);
    if (pageEl) pageEl.classList.add('active');
    const navEl = document.querySelector('.nav-item[data-page="' + page + '"]');
    if (navEl) navEl.classList.add('active');
    // Load page data
    switch(page) {
        case 'home': loadDashboard(); break;
        case 'listings': loadListings(); break;
        case 'offers': loadOffers(); break;
        case 'matches': loadMatches(); break;
        case 'chains': loadChains(); break;
        case 'valuation': loadValuationFilters(); break;
        case 'houzez': loadHouzezProperties(); break;
        case 'consultants': loadConsultants(); break;
        case 'settings': loadSettings(); break;
    }
    // Close mobile sidebar
    document.getElementById('sidebar').classList.remove('open');
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// ═══════════════════════════════════════════════════════════════
// HOME / DASHBOARD
// ═══════════════════════════════════════════════════════════════
async function loadDashboard() {
    // Load stats independently (don't let one failure block everything)
    try {
        const statsResp = await apiCall('stats', 'GET', null, true);
        const stats = statsResp.data || statsResp;
        document.getElementById('statTotal').textContent = toPersianDigits(stats.total_listings || 0);
        document.getElementById('statTotal').classList.remove('skeleton','skeleton-text');
        document.getElementById('statOffers').textContent = toPersianDigits(stats.total_offers || 0);
        document.getElementById('statOffers').classList.remove('skeleton','skeleton-text');
        document.getElementById('statMatches').textContent = toPersianDigits(stats.total_completed || 0);
        document.getElementById('statMatches').classList.remove('skeleton','skeleton-text');
        document.getElementById('statValuations').textContent = toPersianDigits(stats.total_completed || 0);
        document.getElementById('statValuations').classList.remove('skeleton','skeleton-text');
        // Views: sum of all _moaveze_views_count meta values
        document.getElementById('statViews').textContent = toPersianDigits(stats.total_views || 0);
        document.getElementById('statViews').classList.remove('skeleton','skeleton-text');
    } catch(e) {
        document.querySelectorAll('.stat-card .card-value').forEach(el => {
            el.textContent = '—';
            el.classList.remove('skeleton','skeleton-text');
        });
    }

    // Pending count (separate try/catch so it doesn't block)
    try {
        const pendResp = await apiCall('exchanges?per_page=1&status=pending', 'GET', null, true);
        document.getElementById('statPending').textContent = toPersianDigits(pendResp.meta?.total || 0);
    } catch(e) {
        document.getElementById('statPending').textContent = '—';
    }
    document.getElementById('statPending').classList.remove('skeleton','skeleton-text');

    // Offers badge
    try {
        const offResp = await apiCall('offers/received?per_page=1&status=pending', 'GET', null, true);
        if (offResp.meta && offResp.meta.total > 0) {
            const badge = document.getElementById('offersCount');
            badge.textContent = toPersianDigits(offResp.meta.total);
            badge.style.display = 'inline';
        }
    } catch(e) { /* silent */ }

    // Activity feed
    loadActivityFeed();
}

async function loadActivityFeed() {
    try {
        const resp = await apiCall('notifications?per_page=10', 'GET', null, true);
        const items = resp.data?.items || [];
        const container = document.getElementById('activityFeed');
        if (items.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><h3>فعالیتی ثبت نشده</h3><p>هنوز فعالیتی در سیستم ثبت نشده است.</p></div>';
            return;
        }
        container.innerHTML = items.map(item => `
            <div class="activity-item">
                <div class="activity-icon" style="background:#eef2ff;">${item.icon || 'ℹ️'}</div>
                <div class="activity-content">
                    <p>${item.title || item.message}</p>
                    <span class="time">${item.time_ago || item.created_at_jalali || ''}</span>
                </div>
            </div>
        `).join('');
    } catch(e) {
        document.getElementById('activityFeed').innerHTML = '<p style="color:#94a3b8;font-size:13px;">خطا در بارگذاری فعالیت‌ها</p>';
    }
}

// ═══════════════════════════════════════════════════════════════
// LISTINGS MANAGEMENT
// ═══════════════════════════════════════════════════════════════
async function loadListings(page) {
    if (page) listingsPage = page;
    const search = document.getElementById('listingsSearch').value;
    const status = document.getElementById('listingsStatus').value;
    let url = 'exchanges/my?page=' + listingsPage + '&per_page=12';
    if (status) url += '&status=' + status;
    if (search) url += '&search=' + encodeURIComponent(search);
    try {
        const resp = await apiCall(url);
        const items = resp.data?.items || [];
        const container = document.getElementById('listingsTable');
        if (items.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><h3>آگهی‌ای یافت نشد</h3><p>هنوز آگهی ثبت نشده یا فیلتر فعلی نتیجه‌ای ندارد.</p></div>';
            document.getElementById('listingsPagination').innerHTML = '';
            return;
        }
        // Show bulk approve if pending exists
        const hasPending = items.some(i => i.post_status === 'pending');
        document.getElementById('bulkApproveBtn').style.display = (hasPending && CONFIG.isAdmin) ? 'inline-flex' : 'none';

        container.innerHTML = '<table class="data-table"><thead><tr><th>عنوان</th><th>نوع</th><th>منطقه</th><th>ارزش</th><th>متراژ</th><th>بازدید</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead><tbody>' +
            items.map(item => `<tr>
                <td><strong>${item.title}</strong></td>
                <td>${item.property_type?.name || '—'}</td>
                <td>${item.district?.name || '—'}</td>
                <td>${item.value_formatted || shortPrice(item.value)}</td>
                <td>${toPersianDigits(item.area)} م²</td>
                <td>${toPersianDigits(item.views || 0)}</td>
                <td><span class="status-badge ${getStatusClass(item.post_status || 'publish')}">${getStatusLabel(item.post_status || 'publish')}</span></td>
                <td>${item.created_at_jalali || toPersianDigits(item.time_ago || '')}</td>
                <td>
                    <button class="btn btn-secondary btn-xs" onclick="viewListingDetail(${item.id})">جزئیات</button>
                    <button class="btn btn-secondary btn-xs" onclick="editExchange(${item.id})">ویرایش</button>
                    <a href="${item.url || '#'}" target="_blank" class="btn btn-secondary btn-xs">صفحه آگهی</a>
                    ${item.post_status === 'pending' && CONFIG.isAdmin ? '<button class="btn btn-success btn-xs" onclick="approveListing('+item.id+')">تأیید</button>' : ''}
                </td>
            </tr>`).join('') + '</tbody></table>';
        renderPagination('listingsPagination', listingsPage, resp.meta?.pages || 1, 'loadListings');
    } catch(e) { console.error(e); }
}

async function viewListingDetail(id) {
    try {
        const resp = await apiCall('exchanges/' + id);
        const item = resp.data?.item || resp.item || {};
        const content = `
            <div style="margin-bottom:16px;">
                ${item.thumbnail?.medium?.url ? '<img src="'+item.thumbnail.medium.url+'" style="width:100%;border-radius:12px;margin-bottom:16px;max-height:250px;object-fit:cover;">' : ''}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div><strong>نوع:</strong> ${item.property_type?.name || '—'}</div>
                    <div><strong>منطقه:</strong> ${item.district?.name || '—'}</div>
                    <div><strong>متراژ:</strong> ${toPersianDigits(item.area)} متر</div>
                    <div><strong>اتاق:</strong> ${toPersianDigits(item.rooms || '—')}</div>
                    <div><strong>ارزش:</strong> ${item.value_formatted || shortPrice(item.value)}</div>
                    <div><strong>نوع معاوضه:</strong> ${item.exchange_type || '—'}</div>
                    <div><strong>سال ساخت:</strong> ${toPersianDigits(item.year_built || '—')}</div>
                    <div><strong>طبقه:</strong> ${toPersianDigits(item.floor || '—')}</div>
                </div>
                ${item.description_raw ? '<div style="margin-top:16px;padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;line-height:2;">'+item.description_raw+'</div>' : ''}
            </div>`;
        showModal(item.title || 'جزئیات آگهی', content);
    } catch(e) { console.error(e); }
}

async function approveListing(id) {
    if (!confirm('آیا از تأیید این آگهی مطمئن هستید؟')) return;
    try {
        await apiCall('exchanges/' + id, 'PUT', { status: 'publish' });
        showToast('آگهی با موفقیت تأیید شد');
        loadListings();
    } catch(e) { console.error(e); }
}

async function bulkApprove() {
    if (!confirm('آیا از تأیید تمام آگهی‌های در انتظار مطمئن هستید؟')) return;
    try {
        const resp = await apiCall('exchanges/my?status=pending&per_page=50');
        const items = resp.data?.items || [];
        let approved = 0;
        for (const item of items) {
            try {
                await apiCall('exchanges/' + item.id, 'PUT', { status: 'publish' });
                approved++;
            } catch(e) {}
        }
        showToast(toPersianDigits(approved) + ' آگهی تأیید شد');
        loadListings();
    } catch(e) { console.error(e); }
}

// ═══════════════════════════════════════════════════════════════
// OFFERS MANAGEMENT
// ═══════════════════════════════════════════════════════════════
function switchOfferTab(tab) {
    currentOfferTab = tab;
    offersPage = 1;
    document.querySelectorAll('#page-offers .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('#page-offers .tab-btn[data-tab="'+tab+'"]').classList.add('active');
    loadOffers();
}

async function loadOffers(page) {
    if (page) offersPage = page;
    const endpoint = currentOfferTab === 'received' ? 'offers/received' : 'offers/my';
    try {
        const resp = await apiCall(endpoint + '?page=' + offersPage + '&per_page=10');
        const items = resp.data?.items || [];
        const container = document.getElementById('offersContent');
        if (items.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">💬</div><h3>پیشنهادی وجود ندارد</h3><p>هنوز پیشنهادی ' + (currentOfferTab === 'received' ? 'دریافت' : 'ارسال') + ' نشده است.</p></div>';
            document.getElementById('offersPagination').innerHTML = '';
            return;
        }
        container.innerHTML = items.map(item => `
            <div class="panel" style="margin-bottom:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            ${item.sender_name ? '<strong>'+item.sender_name+'</strong>' : ''}
                            <span class="status-badge ${getStatusClass(item.status)}">${item.status_label || getStatusLabel(item.status)}</span>
                        </div>
                        <p style="font-size:13px;color:#475569;">آگهی: <strong>${item.listing_title}</strong></p>
                        ${item.cash_offered ? '<p style="font-size:13px;color:#059669;margin-top:4px;">💰 مبلغ نقدی: ' + (item.cash_offered_formatted || shortPrice(item.cash_offered)) + '</p>' : ''}
                        ${item.message ? '<p style="font-size:12px;color:#64748b;margin-top:4px;background:#f8fafc;padding:8px;border-radius:6px;">'+item.message+'</p>' : ''}
                        <p style="font-size:11px;color:#94a3b8;margin-top:6px;">${item.time_ago || item.created_at_jalali || ''}</p>
                    </div>
                    ${currentOfferTab === 'received' && item.status === 'pending' ? `
                    <div style="display:flex;gap:8px;">
                        <button class="btn btn-success btn-sm" onclick="updateOfferStatus(${item.id},'accepted')">قبول</button>
                        <button class="btn btn-danger btn-sm" onclick="updateOfferStatus(${item.id},'rejected')">رد</button>
                        <button class="btn btn-warning btn-sm" onclick="updateOfferStatus(${item.id},'negotiating')">مذاکره</button>
                    </div>` : ''}
                </div>
            </div>
        `).join('');
        renderPagination('offersPagination', offersPage, resp.meta?.pages || 1, 'loadOffers');
    } catch(e) { console.error(e); }
}

async function updateOfferStatus(offerId, status) {
    const labels = { accepted: 'قبول', rejected: 'رد', negotiating: 'مذاکره' };
    if (!confirm('آیا از ' + labels[status] + ' این پیشنهاد مطمئن هستید؟')) return;
    try {
        await apiCall('offers/' + offerId + '/status', 'PUT', { status });
        showToast('وضعیت پیشنهاد بروزرسانی شد');
        loadOffers();
    } catch(e) { console.error(e); }
}

// ═══════════════════════════════════════════════════════════════
// MATCHES
// ═══════════════════════════════════════════════════════════════
function getMatchStatusLabel(status) {
    const labels = { 'new': 'جدید', 'assigned': 'اختصاص‌یافته', 'in_progress': 'در حال بررسی', 'completed': 'تکمیل‌شده' };
    return labels[status] || status || '—';
}

function getMatchStatusClass(status) {
    const classes = { 'new': 'status-pending', 'assigned': 'status-negotiating', 'in_progress': 'status-negotiating', 'completed': 'status-published' };
    return classes[status] || 'status-draft';
}

async function loadMatches() {
    const container = document.getElementById('matchesContent');
    container.innerHTML = '<div style="padding:24px;text-align:center;"><div class="skeleton" style="height:80px;margin-bottom:12px;"></div><div class="skeleton" style="height:80px;"></div></div>';
    const statusFilter = document.getElementById('matchStatusFilter').value;
    const scoreFilter = parseInt(document.getElementById('matchScoreFilter').value) || 0;
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_list_matches');
        formData.append('nonce', CONFIG.adminNonce);
        if (statusFilter && statusFilter !== 'all') formData.append('status', statusFilter);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data && data.data.matches && data.data.matches.length > 0) {
            let matches = data.data.matches;
            // Client-side score filter
            if (scoreFilter > 0) matches = matches.filter(m => parseFloat(m.score) >= scoreFilter);
            // Filter out rejected/dismissed matches
            matches = matches.filter(m => m.status !== 'rejected');
            if (matches.length === 0) {
                container.innerHTML = '<div class="empty-state"><div class="empty-icon">🎯</div><h3>تطبیقی با این فیلتر یافت نشد</h3></div>';
                return;
            }
            container.innerHTML = matches.map(m => {
                const score = parseFloat(m.score || 0);
                const scoreClass = score >= 70 ? 'high' : (score >= 40 ? 'medium' : 'low');
                const details = m.match_details || {};
                const reasons = details.reasons || [];
                const reasonsHtml = reasons.length > 0
                    ? '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;">' + reasons.map(r => '<span style="display:inline-block;padding:3px 10px;background:#eef2ff;color:#4f46e5;border-radius:20px;font-size:11px;font-weight:500;">✓ ' + r + '</span>').join('') + '</div>'
                    : '';
                // Listing links
                const linkA = m.post_id_a ? '<a href="' + CONFIG.homeUrl + '?p=' + m.post_id_a + '" target="_blank" style="font-size:11px;color:#6366f1;text-decoration:underline;">مشاهده آگهی</a>' : '';
                const linkB = m.post_id_b ? '<a href="' + CONFIG.homeUrl + '?p=' + m.post_id_b + '" target="_blank" style="font-size:11px;color:#6366f1;text-decoration:underline;">مشاهده آگهی</a>' : '';
                return `<div class="match-card">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
                        <span class="match-score ${scoreClass}">🎯 ${toPersianDigits(score.toFixed(0))}٪ تطابق</span>
                        <span class="status-badge ${getMatchStatusClass(m.status)}">${getMatchStatusLabel(m.status)}</span>
                    </div>
                    <div class="match-sides">
                        <div class="match-side">
                            <h4>${m.title_a || 'ملک A'}</h4>
                            <p>${m.district_a || '—'} • ${m.type_a || '—'}</p>
                            <p style="font-size:12px;color:#475569;margin-top:4px;">ارزش: ${shortPrice(m.value_a)}</p>
                            <p style="font-size:11px;color:#94a3b8;">${toPersianDigits(m.area_a || 0)} م²</p>
                            ${linkA}
                        </div>
                        <div class="arrow">⇄</div>
                        <div class="match-side">
                            <h4>${m.title_b || 'ملک B'}</h4>
                            <p>${m.district_b || '—'} • ${m.type_b || '—'}</p>
                            <p style="font-size:12px;color:#475569;margin-top:4px;">ارزش: ${shortPrice(m.value_b)}</p>
                            <p style="font-size:11px;color:#94a3b8;">${toPersianDigits(m.area_b || 0)} م²</p>
                            ${linkB}
                        </div>
                    </div>
                    ${reasonsHtml}
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;padding-top:12px;border-top:1px solid #f1f5f9;">
                        <button class="btn btn-secondary btn-xs" onclick="assignConsultant(${m.id})">👤 اختصاص مشاور</button>
                        <button class="btn btn-primary btn-xs" onclick="connectParties(${m.id})">📞 ارتباط طرفین</button>
                        <button class="btn btn-secondary btn-xs" onclick="getAISuggestion(${m.id})">🤖 پیشنهاد AI</button>
                        <button class="btn btn-danger btn-xs" onclick="dismissMatch(${m.id})">✗ رد تطبیق</button>
                        <select class="filter-select" style="padding:4px 10px;font-size:12px;min-width:auto;border-radius:6px;" onchange="changeMatchStatus(${m.id}, this.value)">
                            <option value="" disabled selected>تغییر وضعیت</option>
                            <option value="new">جدید</option>
                            <option value="assigned">اختصاص‌یافته</option>
                            <option value="in_progress">در حال بررسی</option>
                            <option value="completed">تکمیل‌شده</option>
                        </select>
                    </div>
                    <div id="ai-suggestion-panel-${m.id}" style="display:none;margin-top:12px;"></div>
                </div>`;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">🎯</div><h3>تطبیقی یافت نشد</h3><p>دکمه «اجرای تطبیق جدید» را بزنید تا الگوریتم تطبیق اجرا شود.</p></div>';
        }
    } catch(e) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">🎯</div><h3>خطا در بارگذاری</h3><p>' + (e.message || 'خطا') + '</p></div>';
    }
}

async function dismissMatch(matchId) {
    if (!confirm('آیا از رد این تطبیق مطمئن هستید؟ این تطبیق دیگر نمایش داده نخواهد شد.')) return;
    await changeMatchStatus(matchId, 'rejected');
}

async function runMatching() {
    showToast('در حال اجرای الگوریتم تطبیق...', 'warning');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_run_matching');
        formData.append('nonce', CONFIG.adminNonce);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('تطبیق جدید اجرا شد');
            loadMatches();
        } else {
            showToast(data.data || 'خطا در اجرای تطبیق', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط', 'error'); }
}

async function assignConsultant(matchId) {
    const content = `
        <div class="form-group">
            <label>جستجوی مشاور</label>
            <input type="text" class="form-control" id="consultantAssignSearch" placeholder="نام یا ایمیل مشاور..." oninput="debounce(searchConsultantsForAssign,300)()">
        </div>
        <div id="consultantAssignResults" style="max-height:300px;overflow-y:auto;"></div>
    `;
    showModal('اختصاص مشاور به تطبیق', content);
    window._currentAssignMatchId = matchId;
    searchConsultantsForAssign();
}

async function searchConsultantsForAssign() {
    const query = document.getElementById('consultantAssignSearch')?.value || '';
    const container = document.getElementById('consultantAssignResults');
    if (!container) return;
    container.innerHTML = '<div class="skeleton skeleton-text"></div>';
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_get_consultants');
        formData.append('nonce', CONFIG.adminNonce);
        if (query) formData.append('search', query);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(c => `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:#f8fafc;border-radius:8px;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="${c.avatar || ''}" style="width:32px;height:32px;border-radius:50%;background:#e2e8f0;">
                        <span style="font-size:13px;"><strong>${c.display_name || c.name || ''}</strong></span>
                    </div>
                    <button class="btn btn-success btn-xs" onclick="doAssignConsultant(${window._currentAssignMatchId}, ${c.id})">انتخاب</button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="font-size:13px;color:#64748b;padding:8px;">مشاوری یافت نشد</p>';
        }
    } catch(e) {
        container.innerHTML = '<p style="color:#ef4444;font-size:13px;">خطا در جستجو</p>';
    }
}

async function doAssignConsultant(matchId, consultantId) {
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_assign_consultant');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('match_id', matchId);
        formData.append('consultant_id', consultantId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('مشاور با موفقیت اختصاص یافت');
            closeModal();
            loadMatches();
        } else {
            showToast(data.data || 'خطا در اختصاص مشاور', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط با سرور', 'error'); }
}

async function getAISuggestion(matchId) {
    const panel = document.getElementById('ai-suggestion-panel-' + matchId);
    if (!panel) return;
    panel.style.display = 'block';
    panel.innerHTML = '<div class="skeleton" style="height:60px;"></div>';
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_get_ai_suggestion');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('type', 'match');
        formData.append('id', matchId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            renderAISuggestionPanel(panel, data.data, matchId);
        } else {
            // No existing suggestion, offer to generate one
            panel.innerHTML = `
                <div style="background:#fffbeb;padding:12px;border-radius:8px;font-size:13px;color:#92400e;">
                    <p>هنوز پیشنهادی برای این تطبیق ثبت نشده است.</p>
                    <button class="btn btn-primary btn-sm" style="margin-top:8px;" onclick="generateAISuggestion(${matchId})">🤖 تولید پیشنهاد جدید</button>
                </div>`;
        }
    } catch(e) {
        panel.innerHTML = '<p style="color:#ef4444;font-size:13px;padding:8px;">خطا در دریافت پیشنهاد</p>';
    }
}

async function generateAISuggestion(matchId) {
    const panel = document.getElementById('ai-suggestion-panel-' + matchId);
    if (!panel) return;
    panel.innerHTML = '<div class="skeleton" style="height:60px;"></div>';
    showToast('در حال تولید پیشنهاد هوش مصنوعی...', 'warning');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_suggest_match_deal');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('match_id', matchId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('پیشنهاد هوش مصنوعی تولید شد');
            renderAISuggestionPanel(panel, data.data, matchId);
        } else {
            panel.innerHTML = '<p style="color:#ef4444;font-size:13px;padding:8px;">' + (data.data || 'خطا در تولید پیشنهاد') + '</p>';
        }
    } catch(e) {
        panel.innerHTML = '<p style="color:#ef4444;font-size:13px;padding:8px;">خطا در ارتباط با سرور</p>';
    }
}

function renderAISuggestionPanel(panel, suggestion, matchId) {
    const confColors = { 'بالا': '#10b981', 'متوسط': '#f59e0b', 'پایین': '#ef4444' };
    const confColor = confColors[suggestion.confidence] || '#64748b';
    const statusLabel = suggestion.status === 'approved' ? '<span style="color:#10b981;font-weight:600;">✓ تأیید شده</span>' : '<span style="color:#f59e0b;">در انتظار تأیید</span>';
    let html = `<div style="background:linear-gradient(135deg,#eef2ff,#faf5ff);border:1px solid #e0e7ff;border-radius:12px;padding:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <strong style="font-size:14px;color:#4f46e5;">💡 پیشنهاد هوش مصنوعی</strong>
            <div style="display:flex;align-items:center;gap:8px;">
                ${statusLabel}
                <span style="font-size:11px;color:#64748b;">${suggestion.provider || ''}</span>
            </div>
        </div>
        <p style="font-size:13px;color:#374151;margin-bottom:12px;">${suggestion.summary || ''}</p>`;
    if (suggestion.structures && suggestion.structures.length > 0) {
        html += '<div style="margin-bottom:12px;">';
        suggestion.structures.forEach(s => {
            html += `<div style="background:#fff;border-radius:8px;padding:10px;margin-bottom:8px;border:1px solid #e2e8f0;">
                <strong style="font-size:12px;color:#1e1b4b;">${s.title || ''}</strong>
                <p style="font-size:12px;color:#475569;margin-top:4px;">${s.description || ''}</p>
                ${s.cash_short ? '<p style="font-size:12px;color:#059669;margin-top:4px;">💰 ' + s.cash_short + (s.cash_direction === 'a_to_b' ? ' (طرف الف به ب)' : s.cash_direction === 'b_to_a' ? ' (طرف ب به الف)' : '') + '</p>' : ''}
            </div>`;
        });
        html += '</div>';
    }
    if (suggestion.risks && suggestion.risks.length > 0) {
        html += '<div style="margin-bottom:12px;"><strong style="font-size:12px;color:#dc2626;">⚠ نکات و ریسک‌ها:</strong><ul style="margin-top:4px;padding-right:16px;">';
        suggestion.risks.forEach(r => { html += '<li style="font-size:12px;color:#64748b;margin-bottom:2px;">' + r + '</li>'; });
        html += '</ul></div>';
    }
    html += `<div style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:11px;color:${confColor};">اطمینان: ${suggestion.confidence || '—'}</span>
        ${suggestion.status !== 'approved' ? '<button class="btn btn-success btn-xs" onclick="approveAISuggestion('+matchId+')">✓ تأیید پیشنهاد</button>' : ''}
        <button class="btn btn-secondary btn-xs" onclick="generateAISuggestion('+matchId+')">🔄 تولید مجدد</button>
    </div></div>`;
    panel.innerHTML = html;
}

async function approveAISuggestion(matchId) {
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_approve_ai_suggestion');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('type', 'match');
        formData.append('id', matchId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('پیشنهاد هوش مصنوعی تأیید شد');
            getAISuggestion(matchId);
        } else {
            showToast(data.data || 'خطا در تأیید', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط', 'error'); }
}

async function changeMatchStatus(matchId, newStatus) {
    if (!newStatus) return;
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_update_match_status');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('match_id', matchId);
        formData.append('status', newStatus);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('وضعیت تطبیق بروزرسانی شد');
            loadMatches();
        } else {
            showToast(data.data || 'خطا در تغییر وضعیت', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط', 'error'); }
}

async function connectParties(matchId) {
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_get_match_full_details');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('match_id', matchId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            const d = data.data;
            const content = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                    <div style="background:#f8fafc;padding:16px;border-radius:12px;">
                        <h4 style="margin-bottom:8px;color:#6366f1;">طرف الف</h4>
                        <p><strong>${d.side_a?.title || ''}</strong></p>
                        <p style="font-size:13px;">📞 ${d.side_a?.contact_phone || 'نامشخص'}</p>
                        <p style="font-size:13px;">👤 ${d.side_a?.contact_name || 'نامشخص'}</p>
                    </div>
                    <div style="background:#f8fafc;padding:16px;border-radius:12px;">
                        <h4 style="margin-bottom:8px;color:#10b981;">طرف ب</h4>
                        <p><strong>${d.side_b?.title || ''}</strong></p>
                        <p style="font-size:13px;">📞 ${d.side_b?.contact_phone || 'نامشخص'}</p>
                        <p style="font-size:13px;">👤 ${d.side_b?.contact_name || 'نامشخص'}</p>
                    </div>
                </div>
                <div style="background:#eef2ff;padding:12px;border-radius:8px;font-size:13px;">
                    <strong>امتیاز تطبیق:</strong> ${toPersianDigits(d.match?.score || 0)}٪
                    ${d.match?.suggestion ? '<br><strong>پیشنهاد:</strong> ' + d.match.suggestion : ''}
                </div>`;
            showModal('اطلاعات ارتباط طرفین', content);
        } else {
            showToast(data.data || 'خطا', 'error');
        }
    } catch(e) { showToast('خطا در دریافت اطلاعات', 'error'); }
}

// ═══════════════════════════════════════════════════════════════
// CHAIN SWAPS
// ═══════════════════════════════════════════════════════════════
async function loadChains() {
    const container = document.getElementById('chainsContent');
    container.innerHTML = '<div style="padding:24px;text-align:center;"><div class="skeleton" style="height:100px;margin-bottom:12px;"></div></div>';
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_list_chains');
        formData.append('nonce', CONFIG.adminNonce);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data && data.data.chains && data.data.chains.length > 0) {
            const chains = data.data.chains;
            container.innerHTML = chains.map(chain => {
                const statusLabels = { detected: 'شناسایی‌شده', in_progress: 'در حال پیگیری', completed: 'تکمیل‌شده' };
                const statusClasses = { detected: 'status-pending', in_progress: 'status-negotiating', completed: 'status-published' };
                const nodesHtml = chain.nodes.map((node, i) => {
                    const arrow = i < chain.nodes.length - 1 ? ' <span style="color:#6366f1;font-size:18px;margin:0 4px;">→</span> ' : '';
                    return `<div style="display:inline-flex;align-items:center;gap:4px;background:#f8fafc;padding:8px 12px;border-radius:8px;border:1px solid #e2e8f0;">
                        <div>
                            <strong style="font-size:12px;display:block;">${node.title || 'ملک ' + (i+1)}</strong>
                            <span style="font-size:11px;color:#64748b;">${node.district || ''} • ${shortPrice(node.value)}</span>
                            ${node.url ? '<br><a href="' + node.url + '" target="_blank" style="font-size:10px;color:#6366f1;">مشاهده آگهی</a>' : ''}
                        </div>
                    </div>${arrow}`;
                }).join('');
                const loopArrow = ' <span style="color:#10b981;font-size:18px;font-weight:bold;">↺</span>';
                return `<div class="match-card" style="border-color:#a78bfa;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="background:#f5f3ff;color:#7c3aed;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;">🔗 ${toPersianDigits(chain.chain_length)} ملک</span>
                            <span style="font-size:13px;color:#475569;">ارزش کل: ${shortPrice(chain.total_value)}</span>
                        </div>
                        <span class="status-badge ${statusClasses[chain.status] || 'status-draft'}">${statusLabels[chain.status] || chain.status}</span>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:16px;">
                        ${nodesHtml}${loopArrow}
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;padding-top:12px;border-top:1px solid #f1f5f9;">
                        <button class="btn btn-primary btn-xs" onclick="getChainAISuggestion(${chain.id})">🤖 پیشنهاد AI برای تسویه</button>
                        <button class="btn btn-secondary btn-xs" onclick="changeChainStatus(${chain.id}, 'in_progress')">📋 پیگیری</button>
                        <button class="btn btn-success btn-xs" onclick="changeChainStatus(${chain.id}, 'completed')">✓ تکمیل</button>
                    </div>
                    <div id="chain-ai-panel-${chain.id}" style="display:none;margin-top:12px;"></div>
                </div>`;
            }).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">🔗</div><h3>زنجیره‌ای یافت نشد</h3><p>دکمه «شناسایی زنجیره‌های جدید» را بزنید.</p></div>';
        }
    } catch(e) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">🔗</div><h3>خطا</h3><p>' + (e.message || '') + '</p></div>';
    }
}

async function detectChains() {
    showToast('در حال شناسایی زنجیره‌ها...', 'warning');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_detect_chains');
        formData.append('nonce', CONFIG.adminNonce);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast(data.data?.message || 'زنجیره‌ها شناسایی شدند');
            loadChains();
        } else {
            showToast(data.data || 'خطا', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط', 'error'); }
}

async function getChainAISuggestion(chainId) {
    const panel = document.getElementById('chain-ai-panel-' + chainId);
    if (!panel) return;
    panel.style.display = 'block';
    panel.innerHTML = '<div class="skeleton" style="height:60px;"></div>';
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_get_ai_suggestion');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('type', 'chain');
        formData.append('id', chainId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data) {
            const s = data.data;
            panel.innerHTML = `<div style="background:linear-gradient(135deg,#f5f3ff,#eef2ff);border:1px solid #e0e7ff;border-radius:12px;padding:16px;">
                <strong style="color:#6d28d9;">💡 پیشنهاد تسویه زنجیره‌ای</strong>
                <p style="font-size:13px;color:#374151;margin-top:8px;">${s.summary || s.suggestion || ''}</p>
                ${s.structures ? '<div style="margin-top:8px;">' + s.structures.map(st => '<div style="background:#fff;padding:8px;border-radius:8px;margin-top:6px;border:1px solid #e2e8f0;font-size:12px;"><strong>' + (st.title||'') + '</strong><br>' + (st.description||'') + '</div>').join('') + '</div>' : ''}
                ${s.status !== 'approved' ? '<button class="btn btn-success btn-xs" style="margin-top:10px;" onclick="approveChainSuggestion('+chainId+')">✓ تأیید</button>' : '<span style="color:#10b981;font-size:12px;margin-top:8px;display:block;">✓ تأیید شده</span>'}
            </div>`;
        } else {
            panel.innerHTML = `<div style="background:#fffbeb;padding:12px;border-radius:8px;font-size:13px;color:#92400e;">
                <p>هنوز پیشنهادی ثبت نشده.</p>
                <button class="btn btn-primary btn-sm" style="margin-top:8px;" onclick="generateChainSuggestion(${chainId})">🤖 تولید پیشنهاد</button>
            </div>`;
        }
    } catch(e) { panel.innerHTML = '<p style="color:#ef4444;font-size:13px;">خطا</p>'; }
}

async function generateChainSuggestion(chainId) {
    const panel = document.getElementById('chain-ai-panel-' + chainId);
    if (panel) panel.innerHTML = '<div class="skeleton" style="height:60px;"></div>';
    showToast('در حال تولید پیشنهاد...', 'warning');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_suggest_chain_deal');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('chain_id', chainId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('پیشنهاد تولید شد');
            getChainAISuggestion(chainId);
        } else {
            showToast(data.data || 'خطا', 'error');
            if (panel) panel.innerHTML = '<p style="color:#ef4444;font-size:13px;">' + (data.data || 'خطا') + '</p>';
        }
    } catch(e) { showToast('خطا', 'error'); }
}

async function approveChainSuggestion(chainId) {
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_approve_ai_suggestion');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('type', 'chain');
        formData.append('id', chainId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('پیشنهاد تأیید شد');
            getChainAISuggestion(chainId);
        } else { showToast(data.data || 'خطا', 'error'); }
    } catch(e) { showToast('خطا', 'error'); }
}

async function changeChainStatus(chainId, newStatus) {
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_update_match_status');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('match_id', chainId);
        formData.append('status', newStatus);
        formData.append('table', 'chains');
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('وضعیت بروزرسانی شد');
            loadChains();
        } else { showToast(data.data || 'خطا', 'error'); }
    } catch(e) { showToast('خطا', 'error'); }
}

// ═══════════════════════════════════════════════════════════════
// INLINE EXCHANGE EDITOR
// ═══════════════════════════════════════════════════════════════
async function editExchange(postId) {
    showModal('در حال بارگذاری...', '<div class="skeleton" style="height:200px;"></div>');
    try {
        const resp = await apiCall('exchanges/' + postId);
        const item = resp.data?.item || resp.item || resp.data || resp;
        const exchangeTypes = [
            {value: 'similar', label: 'مشابه'},
            {value: 'any', label: 'هر نوع'},
            {value: 'specific', label: 'خاص'}
        ];
        const exchangeTypeOptions = exchangeTypes.map(t =>
            '<option value="' + t.value + '"' + (item.exchange_type === t.value ? ' selected' : '') + '>' + t.label + '</option>'
        ).join('');
        const cashDirections = [
            {value: 'give', label: 'می‌دهم'},
            {value: 'receive', label: 'می‌گیرم'}
        ];
        const cashDirOptions = cashDirections.map(d =>
            '<option value="' + d.value + '"' + (item.cash_direction === d.value ? ' selected' : '') + '>' + d.label + '</option>'
        ).join('');

        const content = `
            <div class="form-row">
                <div class="form-group">
                    <label>عنوان</label>
                    <input type="text" class="form-control" id="editTitle" value="${(item.title || '').replace(/"/g, '&quot;')}">
                </div>
                <div class="form-group">
                    <label>ارزش (تومان)</label>
                    <input type="number" class="form-control" id="editValue" value="${item.property_value || item.value || ''}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>متراژ (متر مربع)</label>
                    <input type="number" class="form-control" id="editArea" value="${item.area_sqm || item.area || ''}">
                </div>
                <div class="form-group">
                    <label>تعداد اتاق</label>
                    <input type="number" class="form-control" id="editRooms" value="${item.rooms || ''}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>طبقه</label>
                    <input type="number" class="form-control" id="editFloor" value="${item.floor || ''}">
                </div>
                <div class="form-group">
                    <label>سال ساخت</label>
                    <input type="number" class="form-control" id="editYearBuilt" value="${item.year_built || ''}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>نوع معاوضه</label>
                    <select class="form-control" id="editExchangeType">
                        <option value="">انتخاب کنید</option>
                        ${exchangeTypeOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label>مابه‌التفاوت (تومان)</label>
                    <input type="number" class="form-control" id="editCashDiff" value="${item.cash_difference || ''}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>جهت مابه‌التفاوت</label>
                    <select class="form-control" id="editCashDirection">
                        <option value="">انتخاب کنید</option>
                        ${cashDirOptions}
                    </select>
                </div>
                <div class="form-group"></div>
            </div>
            <div class="form-group">
                <label>توضیحات</label>
                <textarea class="form-control" id="editDescription" rows="3">${(item.description_raw || item.description || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
            </div>
            <button class="btn btn-primary" onclick="saveExchange(${postId})" style="width:100%;margin-top:8px;">ذخیره تغییرات</button>
        `;
        showModal('ویرایش آگهی: ' + (item.title || ''), content);
    } catch(e) {
        showModal('خطا', '<p style="color:#ef4444;">خطا در بارگذاری اطلاعات آگهی: ' + (e.message || 'خطا') + '</p>');
    }
}

async function saveExchange(postId) {
    const body = {};
    const title = document.getElementById('editTitle')?.value;
    const value = document.getElementById('editValue')?.value;
    const area = document.getElementById('editArea')?.value;
    const rooms = document.getElementById('editRooms')?.value;
    const floor = document.getElementById('editFloor')?.value;
    const yearBuilt = document.getElementById('editYearBuilt')?.value;
    const exchangeType = document.getElementById('editExchangeType')?.value;
    const cashDiff = document.getElementById('editCashDiff')?.value;
    const cashDirection = document.getElementById('editCashDirection')?.value;
    const description = document.getElementById('editDescription')?.value;

    if (title) body.title = title;
    if (value) body.property_value = parseInt(value);
    if (area) body.area_sqm = parseInt(area);
    if (rooms) body.rooms = parseInt(rooms);
    if (floor) body.floor = parseInt(floor);
    if (yearBuilt) body.year_built = parseInt(yearBuilt);
    if (exchangeType) body.exchange_type = exchangeType;
    if (cashDiff) body.cash_difference = parseInt(cashDiff);
    if (cashDirection) body.cash_direction = cashDirection;
    if (description !== undefined) body.description = description;

    try {
        await apiCall('exchanges/' + postId, 'PUT', body);
        showToast('تغییرات با موفقیت ذخیره شد');
        closeModal();
        loadListings();
    } catch(e) {
        showToast('خطا در ذخیره تغییرات: ' + (e.message || 'خطا'), 'error');
    }
}

// ═══════════════════════════════════════════════════════════════
// VALUATION
// ═══════════════════════════════════════════════════════════════
let valFiltersLoaded = false;

function switchValTab(tab) {
    document.querySelectorAll('#page-valuation .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('#page-valuation .tab-btn[data-tab="'+tab+'"]').classList.add('active');
    document.getElementById('valQuick').style.display = tab === 'quick' ? 'block' : 'none';
    document.getElementById('valFull').style.display = tab === 'full' ? 'block' : 'none';
    document.getElementById('valHistory').style.display = tab === 'history' ? 'block' : 'none';
    if (tab === 'full') loadListingsForValuation();
    if (tab === 'history') loadValuationHistory();
}

async function loadValuationFilters() {
    if (valFiltersLoaded) return;
    try {
        const resp = await apiCall('config');
        const config = resp.data || resp;
        const typeSelect = document.getElementById('valType');
        const distSelect = document.getElementById('valDistrict');
        (config.property_types || []).forEach(t => {
            typeSelect.innerHTML += '<option value="'+t.slug+'">'+t.name+'</option>';
        });
        (config.districts || []).forEach(d => {
            distSelect.innerHTML += '<option value="'+d.slug+'">'+d.name+'</option>';
        });
        valFiltersLoaded = true;
    } catch(e) { console.error(e); }
}

async function runQuickEstimate() {
    const area = parseInt(document.getElementById('valArea').value);
    const type = document.getElementById('valType').value;
    const district = document.getElementById('valDistrict').value;
    const rooms = parseInt(document.getElementById('valRooms').value) || 0;
    const yearBuilt = parseInt(document.getElementById('valYear').value) || 0;
    const floor = parseInt(document.getElementById('valFloor').value) || 0;
    const features = [];
    document.querySelectorAll('.val-feature:checked').forEach(cb => features.push(cb.value));
    const notes = document.getElementById('valNotes').value;
    if (!area || area < 10) { showToast('لطفاً متراژ معتبر وارد کنید (حداقل ۱۰ متر)', 'warning'); return; }
    const resultDiv = document.getElementById('quickEstimateResult');
    resultDiv.innerHTML = '<div class="skeleton" style="height:120px;margin-top:16px;"></div>';
    try {
        const body = { area };
        if (type) body.type = type;
        if (district) body.district = district;
        if (rooms) body.rooms = rooms;
        if (yearBuilt) body.year_built = yearBuilt;
        if (floor) body.floor = floor;
        if (features.length) body.features = features;
        if (notes) body.notes = notes;
        const resp = await apiCall('valuation/estimate', 'POST', body);
        const est = resp.data?.estimate || {};
        const confClass = est.confidence === 'high' ? 'conf-high' : (est.confidence === 'medium' ? 'conf-medium' : 'conf-low');
        const confLabel = est.confidence === 'high' ? 'بالا' : (est.confidence === 'medium' ? 'متوسط' : 'پایین');
        resultDiv.innerHTML = `
            <div class="val-result">
                <div class="val-price">${est.value_formatted || shortPrice(est.value)}</div>
                <div class="val-range">محدوده: ${est.min_formatted || shortPrice(est.min_value)} تا ${est.max_formatted || shortPrice(est.max_value)}</div>
                <p style="margin-top:8px;font-size:13px;color:#475569;">قیمت هر متر مربع: ${est.price_per_sqm_formatted || shortPrice(est.price_per_sqm)}</p>
                <p style="font-size:12px;color:#64748b;">بر اساس ${toPersianDigits(est.comparables_count || 0)} آگهی مشابه</p>
                <div class="val-confidence">
                    <span style="font-size:12px;color:#64748b;">اطمینان: ${confLabel}</span>
                    <div class="conf-bar"><div class="conf-fill ${confClass}"></div></div>
                </div>
            </div>`;
    } catch(e) { resultDiv.innerHTML = '<p style="color:#ef4444;margin-top:12px;">خطا در تخمین قیمت</p>'; }
}

async function loadListingsForValuation() {
    const select = document.getElementById('valListingSelect');
    select.innerHTML = '<option value="">در حال بارگذاری...</option>';
    const type = document.getElementById('valListingType').value;
    try {
        if (type === 'exchange') {
            const resp = await apiCall('exchanges?per_page=50', 'GET', null, true);
            const items = resp.data?.items || [];
            select.innerHTML = '<option value="">انتخاب آگهی معاوضه...</option>';
            items.forEach(item => {
                select.innerHTML += '<option value="' + item.id + '" data-type="exchange">' + item.title + ' (' + toPersianDigits(item.area) + ' م²)</option>';
            });
        } else {
            // Load Houzez properties
            const resp = await fetch(CONFIG.homeUrl + 'wp-json/wp/v2/properties?per_page=50&property_status=133&_fields=id,title,property_meta', { credentials: 'same-origin', headers: { 'X-WP-Nonce': CONFIG.nonce } });
            const items = await resp.json();
            select.innerHTML = '<option value="">انتخاب آگهی فروش...</option>';
            if (Array.isArray(items)) {
                items.forEach(item => {
                    const title = item.title?.rendered || item.title || '';
                    const area = item.property_meta?.fave_property_size?.[0] || item.property_meta?.fave_property_size || '';
                    select.innerHTML += '<option value="' + item.id + '" data-type="property">' + title + (area ? ' (' + toPersianDigits(area) + ' م²)' : '') + '</option>';
                });
            }
        }
    } catch(e) {
        select.innerHTML = '<option value="">خطا در بارگذاری</option>';
    }
}

async function runAIValuation() {
    const select = document.getElementById('valListingSelect');
    const postId = select.value;
    if (!postId) { showToast('لطفاً یک آگهی انتخاب کنید', 'warning'); return; }
    const resultDiv = document.getElementById('aiValuationResult');
    resultDiv.innerHTML = '<div class="skeleton" style="height:150px;margin-top:16px;"></div>';
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_run_ai_valuation');
        formData.append('nonce', CONFIG.valuationNonce);
        formData.append('post_id', postId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            const v = data.data;
            resultDiv.innerHTML = `
                <div class="val-result">
                    <div class="val-price">${v.value_formatted || shortPrice(v.estimated_value || v.value)}</div>
                    ${v.range ? '<div class="val-range">محدوده: ' + (v.range.min_formatted || shortPrice(v.range.min)) + ' تا ' + (v.range.max_formatted || shortPrice(v.range.max)) + '</div>' : ''}
                    ${v.provider ? '<p style="font-size:12px;color:#6366f1;margin-top:8px;">ارائه‌دهنده: ' + v.provider + '</p>' : ''}
                    ${v.confidence ? '<p style="font-size:12px;margin-top:4px;">سطح اطمینان: ' + v.confidence + '</p>' : ''}
                    ${v.methodology ? '<div style="margin-top:12px;padding:12px;background:#fff;border-radius:8px;font-size:12px;color:#475569;"><strong>روش‌شناسی:</strong> ' + v.methodology + '</div>' : ''}
                    ${v.comparables && v.comparables.length > 0 ? '<div style="margin-top:12px;"><strong style="font-size:13px;">املاک مشابه:</strong><table class="data-table" style="margin-top:8px;"><thead><tr><th>عنوان</th><th>متراژ</th><th>ارزش</th></tr></thead><tbody>' + v.comparables.map(c => '<tr><td>'+c.title+'</td><td>'+toPersianDigits(c.area)+' م²</td><td>'+shortPrice(c.value)+'</td></tr>').join('') + '</tbody></table></div>' : ''}
                    <button class="btn btn-success btn-sm" style="margin-top:16px;" onclick="applyValuation(${postId},'${v.estimated_value || v.value}')">اعمال به آگهی</button>
                </div>`;
        } else {
            resultDiv.innerHTML = '<p style="color:#ef4444;margin-top:12px;">' + (data.data || 'خطا در ارزش‌گذاری') + '</p>';
        }
    } catch(e) { resultDiv.innerHTML = '<p style="color:#ef4444;margin-top:12px;">خطا در ارتباط با سرور</p>'; }
}

async function applyValuation(postId, value) {
    try {
        await apiCall('exchanges/' + postId, 'PUT', { property_value: parseInt(value) });
        showToast('ارزش‌گذاری با موفقیت به آگهی اعمال شد');
    } catch(e) { showToast('خطا در اعمال ارزش', 'error'); }
}

async function loadValuationHistory() {
    const container = document.getElementById('valuationHistoryTable');
    try {
        const resp = await apiCall('exchanges/my?per_page=20');
        const items = resp.data?.items || [];
        if (items.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">📊</div><h3>تاریخچه‌ای موجود نیست</h3></div>';
            return;
        }
        container.innerHTML = '<table class="data-table"><thead><tr><th>آگهی</th><th>ارزش فعلی</th><th>متراژ</th><th>تاریخ ثبت</th></tr></thead><tbody>' +
            items.map(item => `<tr><td>${item.title}</td><td>${item.value_formatted || shortPrice(item.value)}</td><td>${toPersianDigits(item.area)} م²</td><td>${item.created_at_jalali || ''}</td></tr>`).join('') +
            '</tbody></table>';
    } catch(e) { container.innerHTML = '<p style="color:#ef4444;">خطا</p>'; }
}

// ═══════════════════════════════════════════════════════════════
// HOUZEZ PROPERTIES
// ═══════════════════════════════════════════════════════════════
async function loadHouzezProperties(page) {
    const search = document.getElementById('houzezSearch')?.value || '';
    const container = document.getElementById('houzezTable');
    if (page) houzezPage = page;
    container.innerHTML = '<div style="padding:24px;"><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text" style="width:60%"></div></div>';
    try {
        // Use WordPress core REST API for the 'property' post type
        // Filter: only 'sell' status (exclude rent/رهن/اجاره)
        let url = CONFIG.homeUrl + 'wp-json/wp/v2/properties?per_page=20&page=' + houzezPage + '&_fields=id,title,property_meta,status,link&property_status=133';
        if (search) url += '&search=' + encodeURIComponent(search);
        const resp = await fetch(url, { credentials: 'same-origin', headers: { 'X-WP-Nonce': CONFIG.nonce } });
        const totalPages = parseInt(resp.headers.get('X-WP-TotalPages')) || 1;
        const items = await resp.json();
        if (!Array.isArray(items) || items.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">🏗️</div><h3>ملکی یافت نشد</h3></div>';
            return;
        }
        container.innerHTML = '<table class="data-table"><thead><tr><th>عنوان</th><th>کد آگهی</th><th>قیمت</th><th>وضعیت معاوضه</th><th>عملیات</th></tr></thead><tbody>' +
            items.map(item => {
                const meta = item.property_meta || {};
                const price = meta.fave_property_price ? meta.fave_property_price[0] || meta.fave_property_price : '';
                const listingId = meta.fave_property_id ? meta.fave_property_id[0] || meta.fave_property_id : '';
                const hasExchange = meta._moaveze_exchange_linked ? true : false;
                const title = item.title?.rendered || item.title || '';
                return `<tr>
                    <td><strong>${title}</strong></td>
                    <td>${toPersianDigits(listingId)}</td>
                    <td>${shortPrice(price)}</td>
                    <td>${hasExchange ? '<span style="color:#10b981;">✓ تبدیل شده</span>' : '<span style="color:#94a3b8;">—</span>'}</td>
                    <td>${!hasExchange ? '<button class="btn btn-primary btn-xs" onclick="convertToExchange('+item.id+')">تبدیل به معاوضه</button>' : '<span style="color:#10b981;font-size:12px;">متصل</span>'} <button class="btn btn-secondary btn-xs" onclick="runAIValuationForProperty('+item.id+')">📊 ارزش‌گذاری</button></td>
                </tr>`;
            }).join('') + '</tbody></table>';
        renderPagination('houzezPagination', houzezPage, totalPages, 'loadHouzezProperties');
    } catch(e) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">🏗️</div><h3>خطا در بارگذاری</h3><p>'+e.message+'</p></div>';
    }
}

async function runAIValuationForProperty(propertyId) {
    showToast('در حال اجرای ارزش‌گذاری...', 'warning');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_run_ai_valuation');
        formData.append('nonce', CONFIG.valuationNonce);
        formData.append('post_id', propertyId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            const v = data.data;
            showModal('نتیجه ارزش‌گذاری', `
                <div class="val-result">
                    <div class="val-price">${v.value_formatted || shortPrice(v.estimated_value || v.suggested_value || v.value)}</div>
                    ${v.range ? '<div class="val-range">محدوده: ' + shortPrice(v.range.min) + ' تا ' + shortPrice(v.range.max) + '</div>' : ''}
                    ${v.confidence ? '<p style="font-size:13px;margin-top:8px;">سطح اطمینان: ' + v.confidence + '</p>' : ''}
                    ${v.methodology ? '<p style="font-size:12px;color:#64748b;margin-top:8px;">' + v.methodology + '</p>' : ''}
                </div>
            `);
        } else {
            showToast(data.data || 'خطا در ارزش‌گذاری', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط با سرور', 'error'); }
}

async function convertToExchange(propertyId) {
    if (!confirm('آیا این ملک به آگهی معاوضه تبدیل شود؟')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_convert_to_exchange');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('property_id', propertyId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('ملک با موفقیت به آگهی معاوضه تبدیل شد');
            loadHouzezProperties();
        } else {
            showToast(data.data || 'خطا در تبدیل', 'error');
        }
    } catch(e) { showToast('خطا در ارتباط با سرور', 'error'); }
}

// ═══════════════════════════════════════════════════════════════
// CONSULTANTS
// ═══════════════════════════════════════════════════════════════
async function loadConsultants() {
    const container = document.getElementById('consultantsList');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_get_consultants');
        formData.append('nonce', CONFIG.adminNonce);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(c => `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="${c.avatar || ''}" style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;">
                        <div>
                            <strong style="font-size:14px;">${c.display_name || c.name || ''}</strong>
                            <p style="font-size:12px;color:#64748b;">${c.email || ''}</p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        ${c.stats ? '<span style="font-size:12px;color:#64748b;">'+toPersianDigits(c.stats.matches || 0)+' تطبیق</span>' : ''}
                        <button class="btn btn-danger btn-xs" onclick="removeConsultant(${c.id})">حذف</button>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">👥</div><h3>مشاوری ثبت نشده</h3><p>از بخش بالا می‌توانید مشاور جدید اضافه کنید.</p></div>';
        }
    } catch(e) {
        container.innerHTML = '<p style="color:#94a3b8;font-size:13px;">خطا در بارگذاری مشاوران</p>';
    }
}

async function searchUsers() {
    const query = document.getElementById('consultantSearch').value;
    if (!query || query.length < 2) return;
    const container = document.getElementById('userSearchResults');
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_search_users');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('search', query);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(u => `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;background:#f8fafc;border-radius:8px;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="${u.avatar || ''}" style="width:32px;height:32px;border-radius:50%;background:#e2e8f0;">
                        <span style="font-size:13px;"><strong>${u.display_name || ''}</strong> (${u.email || u.login || ''})</span>
                    </div>
                    <button class="btn btn-success btn-xs" onclick="addConsultant(${u.id})">افزودن</button>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="font-size:13px;color:#64748b;padding:8px;">کاربری یافت نشد</p>';
        }
    } catch(e) { container.innerHTML = ''; }
}

async function addConsultant(userId) {
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_add_consultant');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('user_id', userId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('مشاور با موفقیت اضافه شد');
            document.getElementById('userSearchResults').innerHTML = '';
            document.getElementById('consultantSearch').value = '';
            loadConsultants();
        } else {
            showToast(data.data || 'خطا', 'error');
        }
    } catch(e) { showToast('خطا', 'error'); }
}

async function removeConsultant(userId) {
    if (!confirm('آیا از حذف این مشاور مطمئن هستید؟')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_remove_consultant');
        formData.append('nonce', CONFIG.adminNonce);
        formData.append('user_id', userId);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success) {
            showToast('مشاور حذف شد');
            loadConsultants();
        } else {
            showToast(data.data || 'خطا', 'error');
        }
    } catch(e) { showToast('خطا', 'error'); }
}

// ═══════════════════════════════════════════════════════════════
// SETTINGS
// ═══════════════════════════════════════════════════════════════
async function loadSettings() {
    // Load current AI provider settings
    try {
        const formData = new FormData();
        formData.append('action', 'moaveze_get_settings');
        formData.append('nonce', CONFIG.adminNonce);
        const resp = await fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.data) {
            if (data.data.ai_provider) {
                const sel = document.getElementById('settingAiProvider');
                if (sel) sel.value = data.data.ai_provider;
            }
            if (data.data.sms_status) {
                document.getElementById('smsStatus').textContent = data.data.sms_status;
            }
        }
    } catch(e) { /* silent */ }
}

function toggleSetting(el, key) {
    el.classList.toggle('on');
    const value = el.classList.contains('on') ? '1' : '0';
    const formData = new FormData();
    formData.append('action', 'moaveze_save_setting');
    formData.append('nonce', CONFIG.adminNonce);
    formData.append('key', key);
    formData.append('value', value);
    fetch(CONFIG.adminUrl + 'admin-ajax.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => { if (d.success) showToast('تنظیم ذخیره شد'); })
        .catch(() => {});
}

// ═══════════════════════════════════════════════════════════════
// INITIALIZATION
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard data in background - NEVER block navigation
    setTimeout(function() { loadDashboard(); }, 100);
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.getElementById('hamburgerBtn');
        if (window.innerWidth <= 768 && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });
    // Keyboard shortcut: Escape closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
});
</script>
</body>
</html>
