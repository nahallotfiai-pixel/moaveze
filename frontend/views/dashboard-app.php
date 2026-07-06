<?php
/**
 * Moaveze Plus - Frontend Dashboard App View
 *
 * Self-contained HTML/CSS/JS for the consultant dashboard.
 * This file outputs a complete standalone HTML page.
 *
 * @var array $dashboard_data Passed from class-dashboard.php
 * @package Moaveze_Plus
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مشاوران - معاوضه پلاس</title>
    <style>

/* ═══════════ RESET & BASE ═══════════ */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --sidebar-bg: #1e1b4b;
    --sidebar-hover: #312e81;
    --sidebar-active: #4338ca;
    --sidebar-text: #c7d2fe;
    --sidebar-text-active: #ffffff;
    --sidebar-width: 260px;
    --header-height: 64px;
    --primary: #6366f1;
    --primary-hover: #4f46e5;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --bg: #f1f5f9;
    --card-bg: #ffffff;
    --text: #1e293b;
    --text-muted: #64748b;
    --border: #e2e8f0;
    --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.08);
    --radius: 12px;
    --radius-sm: 8px;
    --transition: 0.2s ease;
}
html { font-size: 14px; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, 'Vazirmatn', sans-serif;
    background: var(--bg);
    color: var(--text);
    direction: rtl;
    line-height: 1.6;
    min-height: 100vh;
    display: flex;
}
a { text-decoration: none; color: inherit; }
button { cursor: pointer; border: none; outline: none; font-family: inherit; }
input, select, textarea { font-family: inherit; font-size: inherit; }


/* ═══════════ SIDEBAR ═══════════ */
.mv-sidebar {
    position: fixed;
    top: 0; right: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    transition: transform var(--transition);
}
.mv-sidebar-brand {
    padding: 24px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.mv-sidebar-brand .logo {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.mv-sidebar-brand .brand-text {
    color: #fff; font-size: 16px; font-weight: 700;
}
.mv-sidebar-brand .brand-sub {
    color: var(--sidebar-text); font-size: 11px; margin-top: 2px;
}
.mv-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
.mv-nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; border-radius: var(--radius-sm);
    color: var(--sidebar-text); font-size: 14px; font-weight: 500;
    cursor: pointer; transition: all var(--transition);
    margin-bottom: 4px;
}
.mv-nav-item:hover { background: var(--sidebar-hover); color: #fff; }
.mv-nav-item.active { background: var(--sidebar-active); color: var(--sidebar-text-active); }
.mv-nav-item .nav-icon { font-size: 18px; width: 24px; text-align: center; }
.mv-nav-item .nav-badge {
    margin-right: auto; background: var(--danger);
    color: #fff; font-size: 11px; padding: 2px 7px;
    border-radius: 10px; font-weight: 600;
}


.mv-sidebar-user {
    padding: 16px 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; gap: 12px;
}
.mv-sidebar-user img {
    width: 36px; height: 36px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.2);
}
.mv-sidebar-user .user-name { color: #fff; font-size: 13px; font-weight: 600; }
.mv-sidebar-user .user-role { color: var(--sidebar-text); font-size: 11px; }
.mv-sidebar-user .logout-btn {
    margin-right: auto; color: var(--sidebar-text);
    font-size: 18px; padding: 4px; border-radius: 6px;
    transition: all var(--transition); background: none;
}
.mv-sidebar-user .logout-btn:hover { color: var(--danger); background: rgba(255,255,255,0.05); }

/* ═══════════ MAIN CONTENT ═══════════ */
.mv-main {
    margin-right: var(--sidebar-width);
    flex: 1; min-height: 100vh;
    display: flex; flex-direction: column;
}
.mv-header {
    position: sticky; top: 0;
    height: var(--header-height);
    background: var(--card-bg);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center;
    padding: 0 28px; gap: 16px;
    z-index: 100;
}
.mv-header .page-title { font-size: 18px; font-weight: 700; color: var(--text); }
.mv-header .header-spacer { flex: 1; }
.mv-header .notif-btn {
    position: relative; background: none;
    font-size: 22px; color: var(--text-muted); padding: 8px;
    border-radius: 8px; transition: all var(--transition);
}
.mv-header .notif-btn:hover { background: var(--bg); color: var(--text); }
.mv-header .notif-badge {
    position: absolute; top: 4px; left: 4px;
    background: var(--danger); color: #fff;
    font-size: 10px; min-width: 18px; height: 18px;
    border-radius: 9px; display: flex; align-items: center;
    justify-content: center; font-weight: 700;
    border: 2px solid var(--card-bg);
}
.mv-hamburger {
    display: none; background: none; font-size: 24px;
    color: var(--text); padding: 4px;
}


/* ═══════════ CONTENT AREA ═══════════ */
.mv-content { flex: 1; padding: 28px; }
.mv-page { display: none; animation: fadeIn 0.3s ease; }
.mv-page.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* ═══════════ CARDS & STATS ═══════════ */
.mv-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px; margin-bottom: 28px;
}
.mv-stat-card {
    background: var(--card-bg); border-radius: var(--radius);
    padding: 24px; box-shadow: var(--shadow);
    display: flex; align-items: center; gap: 16px;
    transition: box-shadow var(--transition);
}
.mv-stat-card:hover { box-shadow: var(--shadow-lg); }
.mv-stat-card .stat-icon {
    width: 52px; height: 52px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
}
.mv-stat-card .stat-icon.blue { background: #eff6ff; color: var(--info); }
.mv-stat-card .stat-icon.green { background: #ecfdf5; color: var(--success); }
.mv-stat-card .stat-icon.amber { background: #fffbeb; color: var(--warning); }
.mv-stat-card .stat-icon.purple { background: #f5f3ff; color: #7c3aed; }
.mv-stat-card .stat-value { font-size: 26px; font-weight: 800; color: var(--text); }
.mv-stat-card .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

/* ═══════════ TABLE CARD ═══════════ */
.mv-card {
    background: var(--card-bg); border-radius: var(--radius);
    box-shadow: var(--shadow); margin-bottom: 24px; overflow: hidden;
}
.mv-card-header {
    padding: 20px 24px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.mv-card-header h3 { font-size: 16px; font-weight: 700; }
.mv-card-body { padding: 0; }
.mv-card-body.padded { padding: 24px; }


/* ═══════════ DATA TABLE ═══════════ */
.mv-table { width: 100%; border-collapse: collapse; }
.mv-table th {
    text-align: right; padding: 12px 20px;
    font-size: 12px; font-weight: 600;
    color: var(--text-muted); background: #f8fafc;
    border-bottom: 1px solid var(--border);
    text-transform: uppercase; letter-spacing: 0.5px;
}
.mv-table td {
    padding: 14px 20px; border-bottom: 1px solid var(--border);
    font-size: 13px; vertical-align: middle;
}
.mv-table tr:last-child td { border-bottom: none; }
.mv-table tr:hover td { background: #fafbfc; }

/* ═══════════ BADGES ═══════════ */
.mv-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 6px;
    font-size: 12px; font-weight: 600;
}
.mv-badge.publish, .mv-badge.success, .mv-badge.accepted { background: #ecfdf5; color: #065f46; }
.mv-badge.pending, .mv-badge.warning { background: #fffbeb; color: #92400e; }
.mv-badge.draft, .mv-badge.muted { background: #f1f5f9; color: #475569; }
.mv-badge.rejected, .mv-badge.danger { background: #fef2f2; color: #991b1b; }
.mv-badge.negotiating, .mv-badge.info { background: #eff6ff; color: #1e40af; }

/* ═══════════ BUTTONS ═══════════ */
.mv-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 600;
    transition: all var(--transition); border: none;
}
.mv-btn-primary { background: var(--primary); color: #fff; }
.mv-btn-primary:hover { background: var(--primary-hover); }
.mv-btn-success { background: var(--success); color: #fff; }
.mv-btn-success:hover { background: #059669; }
.mv-btn-danger { background: var(--danger); color: #fff; }
.mv-btn-danger:hover { background: #dc2626; }
.mv-btn-outline {
    background: transparent; border: 1px solid var(--border);
    color: var(--text-muted);
}
.mv-btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.mv-btn-sm { padding: 5px 10px; font-size: 12px; }
.mv-btn-group { display: flex; gap: 8px; }


/* ═══════════ LOADING & EMPTY STATES ═══════════ */
.mv-skeleton {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 6px; height: 16px; margin-bottom: 8px;
}
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.mv-spinner {
    display: inline-block; width: 32px; height: 32px;
    border: 3px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.mv-loading-wrap {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 60px 20px; gap: 12px; color: var(--text-muted);
}
.mv-empty {
    text-align: center; padding: 48px 20px;
    color: var(--text-muted);
}
.mv-empty .empty-icon { font-size: 48px; margin-bottom: 12px; }
.mv-empty p { font-size: 14px; }

/* ═══════════ TOAST NOTIFICATIONS ═══════════ */
.mv-toast-container {
    position: fixed; top: 20px; left: 20px;
    z-index: 9999; display: flex; flex-direction: column; gap: 8px;
}
.mv-toast {
    background: var(--card-bg); border-radius: var(--radius-sm);
    box-shadow: var(--shadow-lg); padding: 14px 20px;
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 500; min-width: 280px;
    animation: slideIn 0.3s ease; border-right: 4px solid var(--primary);
}
.mv-toast.success { border-right-color: var(--success); }
.mv-toast.error { border-right-color: var(--danger); }
.mv-toast.warning { border-right-color: var(--warning); }
.mv-toast .toast-icon { font-size: 18px; }
@keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

/* ═══════════ FORM ELEMENTS ═══════════ */
.mv-form-group { margin-bottom: 16px; }
.mv-form-group label {
    display: block; font-size: 13px; font-weight: 600;
    color: var(--text); margin-bottom: 6px;
}
.mv-input, .mv-select {
    width: 100%; padding: 10px 14px;
    border: 1px solid var(--border); border-radius: var(--radius-sm);
    font-size: 14px; transition: border-color var(--transition);
    background: #fff;
}
.mv-input:focus, .mv-select:focus {
    border-color: var(--primary); outline: none;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}


/* ═══════════ VALUATION SECTION ═══════════ */
.mv-valuation-form {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 16px; max-width: 600px;
}
.mv-valuation-result {
    margin-top: 24px; padding: 24px;
    background: linear-gradient(135deg, #f5f3ff, #eff6ff);
    border-radius: var(--radius); border: 1px solid #e0e7ff;
}
.mv-valuation-result .val-price {
    font-size: 28px; font-weight: 800; color: var(--primary);
    margin-bottom: 8px;
}
.mv-valuation-result .val-range {
    font-size: 13px; color: var(--text-muted);
}

/* ═══════════ SETTINGS ═══════════ */
.mv-settings-section { max-width: 600px; }
.mv-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 0; border-bottom: 1px solid var(--border);
}
.mv-toggle-row:last-child { border-bottom: none; }
.mv-toggle-label .label-title { font-size: 14px; font-weight: 600; }
.mv-toggle-label .label-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.mv-toggle {
    position: relative; width: 44px; height: 24px;
    background: #cbd5e1; border-radius: 12px;
    cursor: pointer; transition: background var(--transition);
}
.mv-toggle.active { background: var(--primary); }
.mv-toggle::after {
    content: ''; position: absolute;
    width: 20px; height: 20px; background: #fff;
    border-radius: 50%; top: 2px; right: 2px;
    transition: transform var(--transition);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.mv-toggle.active::after { transform: translateX(-20px); }

/* ═══════════ OVERLAY (mobile sidebar) ═══════════ */
.mv-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.4); z-index: 999;
}
.mv-overlay.show { display: block; }


/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 768px) {
    .mv-sidebar { transform: translateX(100%); }
    .mv-sidebar.open { transform: translateX(0); }
    .mv-main { margin-right: 0; }
    .mv-hamburger { display: block; }
    .mv-overlay.show { display: block; }
    .mv-stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
    .mv-content { padding: 16px; }
    .mv-header { padding: 0 16px; }
    .mv-table { font-size: 12px; }
    .mv-table th, .mv-table td { padding: 10px 12px; }
    .mv-valuation-form { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .mv-stats-grid { grid-template-columns: 1fr; }
    .mv-stat-card { padding: 16px; }
}
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="mv-toast-container" id="toastContainer"></div>

    <!-- Mobile Overlay -->
    <div class="mv-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="mv-sidebar" id="sidebar">
        <div class="mv-sidebar-brand">
            <div class="logo">&#8644;</div>
            <div>
                <div class="brand-text">معاوضه پلاس</div>
                <div class="brand-sub">پنل مدیریت مشاوران</div>
            </div>
        </div>
        <nav class="mv-nav" id="navMenu">
            <div class="mv-nav-item active" data-page="home">
                <span class="nav-icon">&#127968;</span>
                <span>خانه</span>
            </div>
            <div class="mv-nav-item" data-page="listings">
                <span class="nav-icon">&#128203;</span>
                <span>آگهی‌ها</span>
            </div>
            <div class="mv-nav-item" data-page="offers">
                <span class="nav-icon">&#128233;</span>
                <span>پیشنهادات</span>
                <span class="nav-badge" id="offersBadge" style="display:none;">0</span>
            </div>

            <div class="mv-nav-item" data-page="matches">
                <span class="nav-icon">&#127919;</span>
                <span>تطبیق‌ها</span>
            </div>
            <div class="mv-nav-item" data-page="valuation">
                <span class="nav-icon">&#128200;</span>
                <span>ارزش‌گذاری</span>
            </div>
            <div class="mv-nav-item" data-page="settings">
                <span class="nav-icon">&#9881;</span>
                <span>تنظیمات</span>
            </div>
        </nav>
        <div class="mv-sidebar-user">
            <img src="<?php echo esc_url($dashboard_data['user']['avatar']); ?>" alt="avatar">
            <div>
                <div class="user-name"><?php echo esc_html($dashboard_data['user']['display_name']); ?></div>
                <div class="user-role"><?php echo $dashboard_data['user']['is_admin'] ? 'مدیر سیستم' : 'مشاور'; ?></div>
            </div>
            <a href="<?php echo esc_url($dashboard_data['logout_url']); ?>" class="logout-btn" title="خروج">&#9211;</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="mv-main">
        <header class="mv-header">
            <button class="mv-hamburger" id="hamburgerBtn">&#9776;</button>
            <h1 class="page-title" id="pageTitle">خانه</h1>
            <div class="header-spacer"></div>
            <button class="notif-btn" id="notifBtn" title="اعلان‌ها">
                &#128276;
                <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
            </button>
        </header>

        <div class="mv-content">

            <!-- ═══════ HOME PAGE ═══════ -->
            <section class="mv-page active" id="page-home">
                <div class="mv-stats-grid" id="statsGrid">
                    <div class="mv-stat-card">
                        <div class="stat-icon blue">&#128203;</div>
                        <div><div class="stat-value" id="statListings">-</div><div class="stat-label">کل آگهی‌ها</div></div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="stat-icon amber">&#9203;</div>
                        <div><div class="stat-value" id="statPending">-</div><div class="stat-label">در انتظار بررسی</div></div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="stat-icon green">&#128233;</div>
                        <div><div class="stat-value" id="statOffers">-</div><div class="stat-label">پیشنهادات فعال</div></div>
                    </div>
                    <div class="mv-stat-card">
                        <div class="stat-icon purple">&#127919;</div>
                        <div><div class="stat-value" id="statMatches">-</div><div class="stat-label">تطبیق‌های اخیر</div></div>
                    </div>
                </div>
                <div class="mv-card">
                    <div class="mv-card-header"><h3>آخرین آگهی‌ها</h3></div>
                    <div class="mv-card-body" id="recentListingsBody">
                        <div class="mv-loading-wrap"><div class="mv-spinner"></div><span>در حال بارگذاری...</span></div>
                    </div>
                </div>
            </section>


            <!-- ═══════ LISTINGS PAGE ═══════ -->
            <section class="mv-page" id="page-listings">
                <div class="mv-card">
                    <div class="mv-card-header">
                        <h3>آگهی‌های معاوضه</h3>
                        <div class="mv-btn-group">
                            <select class="mv-select" id="listingsStatusFilter" style="width:auto;padding:6px 12px;font-size:12px;">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="publish">منتشر شده</option>
                                <option value="pending">در انتظار</option>
                                <option value="draft">پیش‌نویس</option>
                            </select>
                        </div>
                    </div>
                    <div class="mv-card-body" id="listingsBody">
                        <div class="mv-loading-wrap"><div class="mv-spinner"></div><span>در حال بارگذاری...</span></div>
                    </div>
                </div>
            </section>

            <!-- ═══════ OFFERS PAGE ═══════ -->
            <section class="mv-page" id="page-offers">
                <div class="mv-card">
                    <div class="mv-card-header">
                        <h3>پیشنهادات دریافتی</h3>
                        <select class="mv-select" id="offersStatusFilter" style="width:auto;padding:6px 12px;font-size:12px;">
                            <option value="">همه</option>
                            <option value="pending">در انتظار</option>
                            <option value="accepted">قبول شده</option>
                            <option value="rejected">رد شده</option>
                            <option value="negotiating">مذاکره</option>
                        </select>
                    </div>
                    <div class="mv-card-body" id="offersBody">
                        <div class="mv-loading-wrap"><div class="mv-spinner"></div><span>در حال بارگذاری...</span></div>
                    </div>
                </div>
            </section>


            <!-- ═══════ MATCHES PAGE ═══════ -->
            <section class="mv-page" id="page-matches">
                <div class="mv-card">
                    <div class="mv-card-header"><h3>تطبیق‌های هوشمند</h3></div>
                    <div class="mv-card-body" id="matchesBody">
                        <div class="mv-loading-wrap"><div class="mv-spinner"></div><span>در حال بارگذاری...</span></div>
                    </div>
                </div>
            </section>

            <!-- ═══════ VALUATION PAGE ═══════ -->
            <section class="mv-page" id="page-valuation">
                <div class="mv-card">
                    <div class="mv-card-header"><h3>ارزش‌گذاری هوشمند</h3></div>
                    <div class="mv-card-body padded">
                        <p style="color:var(--text-muted);margin-bottom:20px;font-size:13px;">مشخصات ملک را وارد کنید تا ارزش تقریبی آن محاسبه شود.</p>
                        <div class="mv-valuation-form" id="valuationForm">
                            <div class="mv-form-group">
                                <label>متراژ (مترمربع)</label>
                                <input type="number" class="mv-input" id="valArea" placeholder="مثلاً ۱۲۰" min="10">
                            </div>
                            <div class="mv-form-group">
                                <label>نوع ملک</label>
                                <select class="mv-select" id="valType">
                                    <option value="">انتخاب کنید</option>
                                </select>
                            </div>
                            <div class="mv-form-group">
                                <label>منطقه</label>
                                <select class="mv-select" id="valDistrict">
                                    <option value="">انتخاب کنید</option>
                                </select>
                            </div>
                            <div class="mv-form-group">
                                <label>تعداد اتاق</label>
                                <input type="number" class="mv-input" id="valRooms" placeholder="۲" min="0" max="20">
                            </div>
                        </div>
                        <button class="mv-btn mv-btn-primary" id="runValuationBtn" style="margin-top:16px;">&#128200; محاسبه ارزش</button>
                        <div id="valuationResult" style="display:none;"></div>
                    </div>
                </div>
            </section>


            <!-- ═══════ SETTINGS PAGE ═══════ -->
            <section class="mv-page" id="page-settings">
                <div class="mv-card">
                    <div class="mv-card-header"><h3>تنظیمات اعلان‌ها</h3></div>
                    <div class="mv-card-body padded">
                        <div class="mv-settings-section">
                            <div class="mv-toggle-row">
                                <div class="mv-toggle-label">
                                    <div class="label-title">اعلان پیشنهاد جدید</div>
                                    <div class="label-desc">زمانی که پیشنهاد جدیدی دریافت می‌کنید</div>
                                </div>
                                <div class="mv-toggle active" data-setting="notify_new_offer"></div>
                            </div>
                            <div class="mv-toggle-row">
                                <div class="mv-toggle-label">
                                    <div class="label-title">اعلان تطبیق جدید</div>
                                    <div class="label-desc">زمانی که تطبیق هوشمند جدیدی یافت شود</div>
                                </div>
                                <div class="mv-toggle active" data-setting="notify_new_match"></div>
                            </div>
                            <div class="mv-toggle-row">
                                <div class="mv-toggle-label">
                                    <div class="label-title">اعلان ایمیلی</div>
                                    <div class="label-desc">دریافت اعلان‌ها از طریق ایمیل</div>
                                </div>
                                <div class="mv-toggle" data-setting="notify_email"></div>
                            </div>
                            <div class="mv-toggle-row">
                                <div class="mv-toggle-label">
                                    <div class="label-title">اعلان پیامکی</div>
                                    <div class="label-desc">دریافت اعلان‌ها از طریق پیامک</div>
                                </div>
                                <div class="mv-toggle" data-setting="notify_sms"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>


    <script>
    (function() {
        'use strict';

        // ═══════════ CONFIG ═══════════
        const CONFIG = {
            restUrl: <?php echo wp_json_encode($dashboard_data['rest_url']); ?>,
            nonce: <?php echo wp_json_encode($dashboard_data['nonce']); ?>,
            user: <?php echo wp_json_encode($dashboard_data['user']); ?>,
            adminUrl: <?php echo wp_json_encode($dashboard_data['admin_url']); ?>,
        };

        // ═══════════ HELPERS ═══════════
        function toPersianDigits(str) {
            const persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return String(str).replace(/[0-9]/g, d => persianDigits[parseInt(d)]);
        }

        async function api(endpoint, options = {}) {
            const url = CONFIG.restUrl + endpoint;
            const headers = {
                'X-WP-Nonce': CONFIG.nonce,
                'Content-Type': 'application/json',
            };
            const resp = await fetch(url, { ...options, headers: { ...headers, ...(options.headers || {}) }, credentials: 'same-origin' });
            const data = await resp.json();
            if (!resp.ok || data.success === false) {
                throw new Error(data.error?.message || data.message || 'خطا در دریافت اطلاعات');
            }
            return data;
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const icons = { success: '&#10004;', error: '&#10006;', warning: '&#9888;' };
            const toast = document.createElement('div');
            toast.className = 'mv-toast ' + type;
            toast.innerHTML = '<span class="toast-icon">' + (icons[type] || '') + '</span><span>' + message + '</span>';
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(-20px)'; setTimeout(() => toast.remove(), 300); }, 3500);
        }


        function statusLabel(status) {
            const labels = {
                publish: 'منتشر شده', pending: 'در انتظار', draft: 'پیش‌نویس',
                accepted: 'قبول شده', rejected: 'رد شده', negotiating: 'مذاکره',
                active: 'فعال', completed: 'تکمیل شده'
            };
            return labels[status] || status;
        }

        function statusClass(status) {
            const map = { publish: 'success', active: 'success', accepted: 'success', pending: 'warning', draft: 'muted', rejected: 'danger', negotiating: 'info' };
            return map[status] || 'muted';
        }

        function renderLoading() {
            return '<div class="mv-loading-wrap"><div class="mv-spinner"></div><span>در حال بارگذاری...</span></div>';
        }

        function renderEmpty(msg) {
            return '<div class="mv-empty"><div class="empty-icon">&#128466;</div><p>' + msg + '</p></div>';
        }

        // ═══════════ NAVIGATION ═══════════
        const pages = ['home', 'listings', 'offers', 'matches', 'valuation', 'settings'];
        const pageTitles = { home: 'خانه', listings: 'آگهی‌ها', offers: 'پیشنهادات', matches: 'تطبیق‌ها', valuation: 'ارزش‌گذاری', settings: 'تنظیمات' };

        function navigateTo(page) {
            pages.forEach(p => {
                const el = document.getElementById('page-' + p);
                if (el) el.classList.toggle('active', p === page);
            });
            document.querySelectorAll('.mv-nav-item').forEach(item => {
                item.classList.toggle('active', item.dataset.page === page);
            });
            document.getElementById('pageTitle').textContent = pageTitles[page] || '';
            // Close sidebar on mobile
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
            // Load page data
            if (page === 'home') loadHome();
            else if (page === 'listings') loadListings();
            else if (page === 'offers') loadOffers();
            else if (page === 'matches') loadMatches();
            else if (page === 'valuation') loadValuationConfig();
        }

        document.getElementById('navMenu').addEventListener('click', function(e) {
            const item = e.target.closest('.mv-nav-item');
            if (item && item.dataset.page) navigateTo(item.dataset.page);
        });


        // Mobile sidebar
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebarOverlay').classList.add('show');
        });
        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
        });

        // ═══════════ HOME PAGE ═══════════
        async function loadHome() {
            try {
                const statsResp = await api('stats');
                const stats = statsResp.data || statsResp;
                document.getElementById('statListings').textContent = toPersianDigits(stats.total_listings || 0);
                document.getElementById('statOffers').textContent = toPersianDigits(stats.total_offers || 0);
                document.getElementById('statMatches').textContent = toPersianDigits(stats.total_completed || 0);

                // Pending count
                try {
                    const pendingResp = await api('exchanges?status=pending&per_page=1');
                    const pendingCount = pendingResp.meta?.total || 0;
                    document.getElementById('statPending').textContent = toPersianDigits(pendingCount);
                } catch(e) {
                    document.getElementById('statPending').textContent = toPersianDigits(0);
                }
            } catch(e) {
                console.error('Stats load error:', e);
            }

            // Recent listings
            try {
                const recent = await api('exchanges/recent?limit=5');
                const items = recent.data?.items || [];
                if (items.length === 0) {
                    document.getElementById('recentListingsBody').innerHTML = renderEmpty('هنوز آگهی ثبت نشده است.');
                    return;
                }
                let html = '<table class="mv-table"><thead><tr><th>عنوان</th><th>نوع</th><th>ارزش</th><th>تاریخ</th></tr></thead><tbody>';
                items.forEach(item => {
                    html += '<tr>';
                    html += '<td><strong>' + (item.title || '-') + '</strong></td>';
                    html += '<td>' + (item.property_type?.name || '-') + '</td>';
                    html += '<td>' + (item.value_formatted || '-') + '</td>';
                    html += '<td>' + (item.created_at_jalali || '-') + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                document.getElementById('recentListingsBody').innerHTML = html;
            } catch(e) {
                document.getElementById('recentListingsBody').innerHTML = renderEmpty('خطا در بارگذاری.');
            }
        }


        // ═══════════ LISTINGS PAGE ═══════════
        async function loadListings(status) {
            const body = document.getElementById('listingsBody');
            body.innerHTML = renderLoading();
            try {
                let endpoint = 'exchanges?per_page=20';
                if (status) endpoint += '&status=' + status;
                const resp = await api(endpoint);
                const items = resp.data?.items || [];
                if (items.length === 0) {
                    body.innerHTML = renderEmpty('آگهی‌ای یافت نشد.');
                    return;
                }
                let html = '<table class="mv-table"><thead><tr><th>عنوان</th><th>نوع</th><th>منطقه</th><th>ارزش</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
                items.forEach(item => {
                    const st = item.post_status || 'publish';
                    html += '<tr>';
                    html += '<td><strong>' + (item.title || '-') + '</strong></td>';
                    html += '<td>' + (item.property_type?.name || '-') + '</td>';
                    html += '<td>' + (item.district?.name || '-') + '</td>';
                    html += '<td>' + (item.value_formatted || '-') + '</td>';
                    html += '<td><span class="mv-badge ' + statusClass(st) + '">' + statusLabel(st) + '</span></td>';
                    html += '<td><div class="mv-btn-group">';
                    if (st === 'pending' && CONFIG.user.is_admin) {
                        html += '<button class="mv-btn mv-btn-success mv-btn-sm" onclick="approvePost(' + item.id + ')">تأیید</button>';
                        html += '<button class="mv-btn mv-btn-danger mv-btn-sm" onclick="rejectPost(' + item.id + ')">رد</button>';
                    }
                    html += '<a href="' + (item.url || '#') + '" target="_blank" class="mv-btn mv-btn-outline mv-btn-sm">مشاهده</a>';
                    html += '</div></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                body.innerHTML = html;
            } catch(e) {
                body.innerHTML = renderEmpty('خطا: ' + e.message);
            }
        }

        document.getElementById('listingsStatusFilter').addEventListener('change', function() {
            loadListings(this.value);
        });

        // Approve/reject posts (admin only)
        window.approvePost = async function(postId) {
            try {
                await api('exchanges/' + postId, {
                    method: 'PUT',
                    body: JSON.stringify({ status: 'publish' })
                });
                showToast('آگهی تأیید شد.');
                loadListings();
            } catch(e) { showToast('خطا: ' + e.message, 'error'); }
        };
        window.rejectPost = async function(postId) {
            try {
                await api('exchanges/' + postId, {
                    method: 'PUT',
                    body: JSON.stringify({ status: 'draft' })
                });
                showToast('آگهی رد شد.');
                loadListings();
            } catch(e) { showToast('خطا: ' + e.message, 'error'); }
        };


        // ═══════════ OFFERS PAGE ═══════════
        async function loadOffers(status) {
            const body = document.getElementById('offersBody');
            body.innerHTML = renderLoading();
            try {
                let endpoint = 'offers/received?per_page=20';
                if (status) endpoint += '&status=' + status;
                const resp = await api(endpoint);
                const items = resp.data?.items || [];
                if (items.length === 0) {
                    body.innerHTML = renderEmpty('پیشنهادی دریافت نشده است.');
                    return;
                }
                let html = '<table class="mv-table"><thead><tr><th>فرستنده</th><th>آگهی</th><th>مبلغ نقدی</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead><tbody>';
                items.forEach(item => {
                    html += '<tr>';
                    html += '<td>' + (item.sender_name || 'کاربر') + '</td>';
                    html += '<td>' + (item.listing_title || '-') + '</td>';
                    html += '<td>' + (item.cash_offered_formatted || '-') + '</td>';
                    html += '<td><span class="mv-badge ' + statusClass(item.status) + '">' + (item.status_label || statusLabel(item.status)) + '</span></td>';
                    html += '<td>' + (item.created_at_jalali || '-') + '</td>';
                    html += '<td><div class="mv-btn-group">';
                    if (item.status === 'pending') {
                        html += '<button class="mv-btn mv-btn-success mv-btn-sm" onclick="updateOffer(' + item.id + ',\'accepted\')">قبول</button>';
                        html += '<button class="mv-btn mv-btn-danger mv-btn-sm" onclick="updateOffer(' + item.id + ',\'rejected\')">رد</button>';
                    }
                    html += '</div></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                body.innerHTML = html;
            } catch(e) {
                body.innerHTML = renderEmpty('خطا: ' + e.message);
            }
        }

        document.getElementById('offersStatusFilter').addEventListener('change', function() {
            loadOffers(this.value);
        });

        window.updateOffer = async function(offerId, status) {
            try {
                await api('offers/' + offerId + '/status', {
                    method: 'PUT',
                    body: JSON.stringify({ status: status })
                });
                const msg = status === 'accepted' ? 'پیشنهاد قبول شد.' : 'پیشنهاد رد شد.';
                showToast(msg);
                loadOffers();
            } catch(e) { showToast('خطا: ' + e.message, 'error'); }
        };


        // ═══════════ MATCHES PAGE ═══════════
        async function loadMatches() {
            const body = document.getElementById('matchesBody');
            body.innerHTML = renderLoading();
            try {
                // Try to load recent exchanges that might have matches
                const resp = await api('exchanges/recent?limit=10');
                const items = resp.data?.items || [];
                if (items.length === 0) {
                    body.innerHTML = renderEmpty('هنوز تطبیقی یافت نشده است.');
                    return;
                }
                // Display as potential matches (the matching algorithm runs server-side)
                let html = '<table class="mv-table"><thead><tr><th>آگهی</th><th>نوع</th><th>منطقه</th><th>ارزش</th><th>نوع معاوضه</th></tr></thead><tbody>';
                items.forEach(item => {
                    html += '<tr>';
                    html += '<td><strong>' + (item.title || '-') + '</strong></td>';
                    html += '<td>' + (item.property_type?.name || '-') + '</td>';
                    html += '<td>' + (item.district?.name || '-') + '</td>';
                    html += '<td>' + (item.value_formatted || '-') + '</td>';
                    const etLabels = { exact: 'دقیق', flexible: 'انعطاف‌پذیر', with_cash: 'با مابه‌التفاوت' };
                    html += '<td><span class="mv-badge info">' + (etLabels[item.exchange_type] || item.exchange_type || '-') + '</span></td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                html += '<div style="padding:16px 20px;text-align:center;color:var(--text-muted);font-size:13px;">الگوریتم تطبیق به‌صورت خودکار اجرا می‌شود و نتایج جدید از طریق اعلان به شما اطلاع داده می‌شود.</div>';
                body.innerHTML = html;
            } catch(e) {
                body.innerHTML = renderEmpty('خطا: ' + e.message);
            }
        }


        // ═══════════ VALUATION PAGE ═══════════
        let valuationConfigLoaded = false;
        async function loadValuationConfig() {
            if (valuationConfigLoaded) return;
            try {
                const resp = await api('config');
                const config = resp.data || resp;
                const typeSelect = document.getElementById('valType');
                const distSelect = document.getElementById('valDistrict');
                (config.property_types || []).forEach(t => {
                    typeSelect.innerHTML += '<option value="' + t.slug + '">' + t.name + '</option>';
                });
                (config.districts || []).forEach(d => {
                    distSelect.innerHTML += '<option value="' + d.slug + '">' + d.name + '</option>';
                });
                valuationConfigLoaded = true;
            } catch(e) { console.error(e); }
        }

        document.getElementById('runValuationBtn').addEventListener('click', async function() {
            const area = document.getElementById('valArea').value;
            const type = document.getElementById('valType').value;
            const district = document.getElementById('valDistrict').value;
            const rooms = document.getElementById('valRooms').value;

            if (!area || parseInt(area) < 10) {
                showToast('لطفاً متراژ معتبر وارد کنید (حداقل ۱۰ متر).', 'warning');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<div class="mv-spinner" style="width:18px;height:18px;border-width:2px;"></div> در حال محاسبه...';

            try {
                const resp = await api('valuation/estimate', {
                    method: 'POST',
                    body: JSON.stringify({ area: parseInt(area), type, district, rooms: parseInt(rooms) || 0 })
                });
                const est = resp.data?.estimate || {};
                const resultEl = document.getElementById('valuationResult');
                resultEl.style.display = 'block';
                resultEl.innerHTML = '<div class="mv-valuation-result">' +
                    '<div class="val-price">' + (est.value_formatted || '-') + '</div>' +
                    '<div class="val-range">بازه قیمت: ' + (est.min_formatted || '-') + ' تا ' + (est.max_formatted || '-') + '</div>' +
                    '<div style="margin-top:12px;font-size:13px;color:var(--text-muted);">' +
                    'قیمت هر متر: ' + (est.price_per_sqm_formatted || '-') +
                    ' &bull; سطح اطمینان: ' + (est.confidence === 'high' ? 'بالا' : est.confidence === 'medium' ? 'متوسط' : 'پایین') +
                    ' &bull; بر اساس ' + toPersianDigits(est.comparables_count || 0) + ' آگهی مشابه' +
                    '</div></div>';
                showToast('ارزش‌گذاری با موفقیت انجام شد.');
            } catch(e) {
                showToast('خطا: ' + e.message, 'error');
            }

            this.disabled = false;
            this.innerHTML = '&#128200; محاسبه ارزش';
        });


        // ═══════════ SETTINGS PAGE ═══════════
        document.querySelectorAll('.mv-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
                const setting = this.dataset.setting;
                const enabled = this.classList.contains('active');
                // Save preference (could be stored in user meta via a custom endpoint)
                showToast(enabled ? 'فعال شد.' : 'غیرفعال شد.');
            });
        });

        // ═══════════ NOTIFICATIONS ═══════════
        async function loadNotifCount() {
            try {
                const resp = await api('notifications/unread-count');
                const count = resp.data?.unread_count || 0;
                const badge = document.getElementById('notifBadge');
                const offersBadge = document.getElementById('offersBadge');
                if (count > 0) {
                    badge.style.display = 'flex';
                    badge.textContent = toPersianDigits(count);
                } else {
                    badge.style.display = 'none';
                }
                // Also try pending offers count
                try {
                    const offersResp = await api('offers/received?status=pending&per_page=1');
                    const pendingOffers = offersResp.meta?.total || 0;
                    if (pendingOffers > 0) {
                        offersBadge.style.display = 'inline-flex';
                        offersBadge.textContent = toPersianDigits(pendingOffers);
                    }
                } catch(e) {}
            } catch(e) { console.error(e); }
        }

        document.getElementById('notifBtn').addEventListener('click', function() {
            // Mark all as read and show toast
            api('notifications/read-all', { method: 'PUT' }).then(() => {
                document.getElementById('notifBadge').style.display = 'none';
                showToast('همه اعلان‌ها خوانده شد.');
            }).catch(() => {});
        });

        // ═══════════ INIT ═══════════
        loadHome();
        loadNotifCount();
        // Refresh notification count every 60 seconds
        setInterval(loadNotifCount, 60000);

    })();
    </script>
</body>
</html>
