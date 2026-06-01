<?php
if (session_id() == '') session_start();
header("Cache_control:private");

$accessarray = $_SESSION['accessarray'];
require('../include/checkdata.php');
checkExpiredSession($accessarray);
$accessarray = $_SESSION['accessarray'];

require('../include/constants.php');
checkAccessControls("AuthUsers", 1);
cleanRequest($_REQUEST);

require('../include/dbinfo.php');
require('../include/lang.php');
require('../include/utils.php');
require('../include/config.php');
require('../include/cache.php');

$lang   = ($_SESSION['language']) ? ($_SESSION['language']) : "en";
$module = "radius";

$period = isset($_REQUEST['period']) && in_array($_REQUEST['period'], ['1', '7', '30', 'custom'])
          ? $_REQUEST['period']
          : '7';

/* ── Labels ── */
$labelHome    = getLabel($lang, $module, "home");
$labelReports = getLabel($lang, $module, "reports");
$labelBilling = getLabel($lang, $module, "billing");
?>
<!DOCTYPE html>
<!--[if IE 8]><html lang="en" class="ie8"><![endif]-->
<!--[if IE 9]><html lang="en" class="ie9"><![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title><?= $WIFILANTITLE ?> – Active Users & New Users VS ARPU</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <?php include('../include/global-styles.php'); ?>
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/css/pages/search.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/DT_bootstrap.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/buttons.dataTables.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/responsive.dataTables.min.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" />
    <link rel="shortcut icon" href="<?=$baseurl?>/<?=$appname?>/img/favicon.ico" />

    <!-- ECharts -->
    <script src="<?= $baseurl ?>/<?= $appname ?>/<?= $assetsDir ?>/echarts-6.0.0/package/dist/echarts.min.js"></script>

	<style>
.drp-calendar.right {
min-width: 300px !important;
padding-left: 12px !important;
}
.drp-calendar.left {
min-width: 300px !important;
border-right: 1px solid #dcdcdc;
    margin-right: 10px;
    padding-right: 12px !important;
}

        /* ═══════════════════════════════════════════════════
           ARPU REPORT — Component Styles
           All classes prefixed with .arpu- to avoid conflicts
           ═══════════════════════════════════════════════════ */

        /* ── Page wrapper ── */
        .arpu-page-wrap {
            padding: 0 10px 30px;
        }

        /* ── Filter bar (period toggle + dropdowns) ── */
        .arpu-filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }
        .arpu-filter-bar .arpu-title {
            font-size: 17px;
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: -.2px;
        }
        .arpu-filter-bar .arpu-title span {
            color: #e87722;
        }
        .arpu-filter-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .arpu-period-toggle {
            display: flex;
            border: 1px solid #dde3ec;
            border-radius: 7px;
            overflow: hidden;
			background: #fff;
            margin-bottom: 10px;
        }
        .arpu-period-toggle .pt-btn {
            padding: 6px 20px;
            font-size: 12px;
            font-weight: 700;
            color: #7a8da0;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: background .15s, color .15s;
            letter-spacing: .3px;
        }
        .arpu-period-toggle .pt-btn + .pt-btn {
            border-left: 1px solid #dde3ec;
        }
        .arpu-period-toggle .pt-btn.pt-active,
        .arpu-period-toggle .pt-btn:hover {
            background: #e87722;
            color: #fff;
        }
        .arpu-filter-select {
            height: 32px;
            padding: 0 28px 0 10px;
            font-size: 12px;
            font-weight: 600;
            color: #3d5166;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%237a8da0'/%3E%3C/svg%3E") no-repeat right 9px center;
            border: 1px solid #dde3ec;
            border-radius: 7px;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            min-width: 130px;
            transition: border-color .15s, box-shadow .15s;
        }
        .arpu-filter-select:focus {
            outline: none;
            border-color: #e87722;
            box-shadow: 0 0 0 2px rgba(232,119,34,.15);
        }

        /* ── KPI cards row ── */
        .arpu-kpi-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .arpu-kpi-card {
            flex: 1;
            min-width: 200px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #edf0f7;
            padding: 18px 20px 16px;
            box-shadow: 0 2px 8px rgba(44,62,80,.06);
            position: relative;
            overflow: hidden;
        }
        .arpu-kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 10px 10px 0 0;
        }
        .arpu-kpi-card.kpi-users::before  { background: #3498db; }
        .arpu-kpi-card.kpi-prev::before   { background: #1a5276; }
        .arpu-kpi-card.kpi-revenue::before{ background: #27ae60; }
        .arpu-kpi-card.kpi-arpu::before   { background: #7C3AED; }

        .arpu-kpi-card .kpi-label {
            font-size: 11px;
            font-weight: 700;
            color: #9aabb8;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 6px;
        }
        .arpu-kpi-card .kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: #1e2d3d;
            line-height: 1;
            margin-bottom: 8px;
            font-variant-numeric: tabular-nums;
        }
        .arpu-kpi-card .kpi-value.loading {
            color: #cdd5dd;
        }
        .arpu-kpi-card .kpi-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
        }
        .arpu-kpi-card .kpi-badge.up   { background: #eafaf1; color: #1e8449; }
        .arpu-kpi-card .kpi-badge.down { background: #fdedec; color: #c0392b; }
        .arpu-kpi-card .kpi-badge.flat { background: #f4f6f7; color: #7f8c8d; }
        .arpu-kpi-card .kpi-sub {
            font-size: 11px;
            color: #b0bec5;
            margin-top: 4px;
        }
        /* icon circle */
        .arpu-kpi-card .kpi-icon {
            position: absolute;
            top: 16px; right: 16px;
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }
        .arpu-kpi-card.kpi-users   .kpi-icon { background: #ebf5fb; color: #3498db; }
        .arpu-kpi-card.kpi-prev    .kpi-icon { background: #eaf0fb; color: #1a5276; }
        .arpu-kpi-card.kpi-revenue .kpi-icon { background: #eafaf1; color: #27ae60; }
        .arpu-kpi-card.kpi-arpu    .kpi-icon { background: #ede9fe; color: #7C3AED; }

        /* ── Chart card ── */
        .arpu-chart-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #edf0f7;
            box-shadow: 0 2px 8px rgba(44,62,80,.06);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .arpu-chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 14px 20px 12px;
            border-bottom: 1px solid #f0f3f8;
        }
        .arpu-chart-header .ch-title {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
        }
        .arpu-chart-header .ch-legend {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .arpu-chart-header .ch-legend .leg-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #5a6a80;
            font-weight: 600;
        }
        .arpu-chart-header .ch-legend .leg-dot {
            width: 12px; height: 12px;
            border-radius: 3px;
        }
        .arpu-chart-header .ch-legend .leg-dot.arpu-line {
            border-radius: 50%;
            background: #7C3AED;
        }
        #arpuTrendChart {
            width: 100%;
            height: 320px;
        }

        /* ── Loading skeleton shimmer ── */
        @keyframes shimmer {
            0%   { background-position: -600px 0; }
            100% { background-position: 600px 0; }
        }
        .arpu-skeleton {
            border-radius: 6px;
            background: linear-gradient(90deg, #f0f3f8 25%, #e4e9f0 50%, #f0f3f8 75%);
            background-size: 600px 100%;
            animation: shimmer 1.4s infinite linear;
        }

        /* ── Spinner overlay ── */
        .arpu-chart-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 320px;
        }
        .arpu-spinner {
            width: 36px; height: 36px;
            border: 3px solid #f0f3f8;
            border-top-color: #e87722;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── KPI Group Columns ── */
        .arpu-kpi-groups {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .arpu-kpi-group {
            flex: 1;
            min-width: 300px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #edf0f7;
            box-shadow: 0 2px 8px rgba(44,62,80,.06);
            overflow: hidden;
        }
        .arpu-kpi-group-header {
            padding: 12px 18px 10px;
            border-bottom: 2px solid #edf0f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .arpu-kpi-group-header.current {
            border-bottom-color: #e87722;
            background: linear-gradient(135deg, #fff8f3 0%, #fff 100%);
        }
        .arpu-kpi-group-header.previous {
            border-bottom-color: #1a5276;
            background: linear-gradient(135deg, #f0f4fa 0%, #fff 100%);
        }
        .arpu-kpi-group-header .gh-label {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .7px;
        }
        .arpu-kpi-group-header.current .gh-label { color: #e87722; }
        .arpu-kpi-group-header.previous .gh-label { color: #1a5276; }
        .arpu-kpi-group-header .gh-period {
            font-size: 11px;
            color: #9aabb8;
            font-weight: 600;
            margin-left: auto;
        }
        .arpu-kpi-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .arpu-kpi-inner .arpu-kpi-card {
            border-radius: 0;
            border: none;
            border-right: 1px solid #f0f3f8;
            border-bottom: 1px solid #f0f3f8;
            box-shadow: none;
            padding: 14px 16px 12px;
        }
        /* Remove right border on every 2nd card (right column) */
        .arpu-kpi-inner .arpu-kpi-card:nth-child(2n) {
            border-right: none;
        }
        /* Remove bottom border on last row (cards 3 & 4) */
        .arpu-kpi-inner .arpu-kpi-card:nth-child(3),
        .arpu-kpi-inner .arpu-kpi-card:nth-child(4) {
            border-bottom: none;
        }
        .arpu-kpi-inner .arpu-kpi-card::before {
            border-radius: 0;
        }
        /* new-user accent */
        .arpu-kpi-card.kpi-new::before   { background: #e67e22; }
        .arpu-kpi-card.kpi-new .kpi-icon { background: #fef3e2; color: #e67e22; }
        /* prev-new accent */
        .arpu-kpi-card.kpi-prev-new::before   { background: #8e44ad; }
        .arpu-kpi-card.kpi-prev-new .kpi-icon { background: #f5eef8; color: #8e44ad; }
        /* prev-revenue */
        .arpu-kpi-card.kpi-prev-revenue::before   { background: #16a085; }
        .arpu-kpi-card.kpi-prev-revenue .kpi-icon { background: #e8f8f5; color: #16a085; }
        /* prev-arpu */
        .arpu-kpi-card.kpi-prev-arpu::before   { background: #2980b9; }
        .arpu-kpi-card.kpi-prev-arpu .kpi-icon { background: #ebf5fb; color: #2980b9; }

        /* ── Combined summary table ── */
        .arpu-summary-table-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #edf0f7;
            box-shadow: 0 2px 8px rgba(44,62,80,.06);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .arpu-summary-table-card .ast-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px 10px;
            border-bottom: 1px solid #f0f3f8;
        }
        .arpu-summary-table-card .ast-header .ast-title {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
        }
        .arpu-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .arpu-summary-table thead tr th {
            background: #f7f9fc;
            color: #7a8da0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 10px 14px;
            border-bottom: 1px solid #edf0f7;
            white-space: nowrap;
        }
        .arpu-summary-table thead tr.th-group th {
            background: #edf0f7;
            color: #2c3e50;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
            text-align: center;
            padding: 8px 14px;
            border-bottom: 1px solid #dde3ec;
        }
        .arpu-summary-table thead tr.th-group th.grp-current {
            background: #fff3eb;
            color: #e87722;
            border-right: 2px solid #e8ddd3;
        }
        .arpu-summary-table thead tr.th-group th.grp-previous {
            background: #eaf0fb;
            color: #1a5276;
        }
        .arpu-summary-table tbody tr td {
            padding: 9px 14px;
            border-bottom: 1px solid #f5f7fa;
            color: #2c3e50;
            font-weight: 600;
            vertical-align: middle;
        }
        .arpu-summary-table tbody tr:last-child td { border-bottom: none; }
        .arpu-summary-table tbody tr:hover td { background: #fafbfe; }
        .arpu-summary-table td.col-sep {
            border-right: 2px solid #edf0f7;
        }
        .arpu-summary-table td.col-label {
            font-weight: 700;
            color: #5a6a80;
            font-size: 12px;
            white-space: nowrap;
        }
        .arpu-summary-table .badge-up   { color: #1e8449; font-weight: 700; }
        .arpu-summary-table .badge-down { color: #c0392b; font-weight: 700; }
        .arpu-summary-table .badge-flat { color: #7f8c8d; font-weight: 700; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .arpu-kpi-groups { gap: 10px; }
            .arpu-kpi-group  { min-width: 100%; }
            .arpu-kpi-card .kpi-value { font-size: 22px; }
        }
        @media (max-width: 480px) {
            .arpu-kpi-inner { grid-template-columns: 1fr; }
            .arpu-kpi-inner .arpu-kpi-card:nth-child(2n) { border-right: none; }
            .arpu-kpi-inner .arpu-kpi-card:nth-child(3)  { border-bottom: 1px solid #f0f3f8; }
            .arpu-kpi-inner .arpu-kpi-card:nth-child(4)  { border-bottom: none; }
        }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "analytics";
            $_SESSION['submenu']       = "usersMenu";
            $_SESSION['submenulevel1'] = "ActiveNewUsersARPU";
            include('../include/sidebar_temp.php');
        ?>

        <div class="page-content">
            <div class="container-fluid">

                <!-- Breadcrumb -->
                <div class="row-fluid" style="margin-bottom:10px;">
                    <div class="span12">
                        <?php include('../include/style-customizer.php'); ?>
                        <ul class="breadcrumb" style="margin-top:12px;">
                            <li>
                                <i class="icon-home"></i>
                                <a href="<?=$DASHBOARDPATH?>"><?=$labelHome?></a>
                                <i class="icon-angle-right"></i>
                                <a href="#"><?=$labelReports?></a>
                                <i class="icon-angle-right"></i>
                                <a href="#">NMS</a>
                                <i class="icon-angle-right"></i>
                                <span>Active Users &amp; New Users VS ARPU</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════
                     MAIN REPORT CONTENT
                     ══════════════════════════════════════════ -->
                <div class=" arpu-page-wrap">

					<!-- Filter bar: period + partner + region -->
                    <div class="breadcrumb arpu-filter-bar">
						<div class="arpu-title">
                          <i class="icon-user" style="color:#e87722;margin-right:8px;"></i>
                            Active Users &amp; New Users <span>VS ARPU</span>
                        </div>
                        <div class="arpu-filter-controls">
                            <!-- Date range dropdown -->
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <select class="arpu-filter-select" id="arpuPeriodSelect" style="min-width:160px;">
                                    <option value="7" <?= $period == 7 ? 'selected' : '' ?>>Last 7 Days</option>
                                    <option value="30" <?= $period == 30 ? 'selected' : '' ?>>Last 30 Days</option>
                                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                                </select>
                                <!-- Custom daterangepicker — shown only when "Custom Range" selected -->
                                <div id="arpuCustomRangeWrap" style="display:none;align-items:center;gap:6px;">
                                    <input type="text" id="arpuCustomRange" readonly
                                           style="height:32px;padding:0 10px;font-size:12px;font-weight:600;
                                                  color:#3d5166;border:1px solid #dde3ec;border-radius:7px;
                                                  background:#fff;cursor:pointer;min-width:210px;"
                                           placeholder="Select date range…" />
                                    <span id="arpuDateRangeMsg" style="font-size:11px;color:#9aabb8;">Max 90 days</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Two-Column KPI Groups ── -->
                    <div class="arpu-kpi-groups">

                        <!-- ── COLUMN 1: Current Period ── -->
                        <div class="arpu-kpi-group">
                            <div class="arpu-kpi-group-header current">
                                <i class="icon-calendar" style="color:#e87722;font-size:13px;"></i>
                                <span class="gh-label">Current Period</span>
                                <span class="gh-period" id="ghPeriodCurrent">–</span>
                            </div>
                            <div class="arpu-kpi-inner">

                                <!-- Total Active Users -->
                                <div class="arpu-kpi-card kpi-users">
                                    <div class="kpi-icon"><i class="icon-user"></i></div>
                                    <div class="kpi-label">Total Users</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiActiveUsers" style="height:28px;width:100px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiUsersChg">–</span>
                                    <div class="kpi-sub">Active users</div>
                                </div>

                                <!-- New Users (Current) -->
                                <div class="arpu-kpi-card kpi-new">
                                    <div class="kpi-icon"><i class="icon-user"></i></div>
                                    <div class="kpi-label">New Users</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiNewUsers" style="height:28px;width:100px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiNewUsersChg">–</span>
                                    <div class="kpi-sub">New signups</div>
                                </div>

                                <!-- Total Revenue (Current) -->
                                <div class="arpu-kpi-card kpi-revenue">
                                    <div class="kpi-icon"><i class="icon-money"></i></div>
                                    <div class="kpi-label">Total Revenue</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiRevenue" style="height:28px;width:110px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiRevenueChg">–</span>
                                    <div class="kpi-sub">Datapack + Voucher</div>
                                </div>

                                <!-- ARPU (Total Users) -->
                                <div class="arpu-kpi-card kpi-arpu">
                                    <div class="kpi-icon"><i class="icon-signal"></i></div>
                                    <div class="kpi-label">Avg ARPU / User</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiArpu" style="height:28px;width:80px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiArpuChg">–</span>
                                    <div class="kpi-sub">Revenue ÷ Total Users</div>
                                </div>

                            </div><!-- /arpu-kpi-inner -->
                        </div><!-- /current group -->

                        <!-- ── COLUMN 2: Previous Period (M-1) ── -->
                        <div class="arpu-kpi-group">
                            <div class="arpu-kpi-group-header previous">
                                <i class="icon-calendar" style="color:#1a5276;font-size:13px;"></i>
                                <span class="gh-label">Previous Period (<span id="periodLabel">M&#8209;1</span>)</span>
                                <span class="gh-period" id="ghPeriodPrevious">–</span>
                            </div>
                            <div class="arpu-kpi-inner">

                                <!-- Total Active Users M-1 -->
                                <div class="arpu-kpi-card kpi-prev">
                                    <div class="kpi-icon"><i class="icon-user"></i></div>
                                    <div class="kpi-label">Total Users M&#8209;1</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiPrevUsers" style="height:28px;width:100px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiPrevUsersBadge">Prev Period</span>
                                    <div class="kpi-sub">Active users</div>
                                </div>

                                <!-- New Users M-1 -->
                                <div class="arpu-kpi-card kpi-prev-new">
                                    <div class="kpi-icon"><i class="icon-user"></i></div>
                                    <div class="kpi-label">New Users M&#8209;1</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiPrevNewUsers" style="height:28px;width:100px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiPrevNewUsersBadge">Prev Period</span>
                                    <div class="kpi-sub">New signups</div>
                                </div>

                                <!-- Total Revenue M-1 -->
                                <div class="arpu-kpi-card kpi-prev-revenue">
                                    <div class="kpi-icon"><i class="icon-money"></i></div>
                                    <div class="kpi-label">Total Revenue M&#8209;1</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiPrevRevenue" style="height:28px;width:110px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiPrevRevenueBadge">Prev Period</span>
                                    <div class="kpi-sub">Datapack + Voucher</div>
                                </div>

                                <!-- ARPU M-1 (Total Users M-1) -->
                                <div class="arpu-kpi-card kpi-prev-arpu">
                                    <div class="kpi-icon"><i class="icon-signal"></i></div>
                                    <div class="kpi-label">Avg ARPU M&#8209;1</div>
                                    <div class="kpi-value loading arpu-skeleton" id="kpiPrevArpu" style="height:28px;width:80px;margin-bottom:6px;">&nbsp;</div>
                                    <span class="kpi-badge flat" id="kpiPrevArpuBadge">Prev Period</span>
                                    <div class="kpi-sub">Revenue ÷ Total Users M&#8209;1</div>
                                </div>

                            </div><!-- /arpu-kpi-inner -->
                        </div><!-- /previous group -->

                    </div><!-- /arpu-kpi-groups -->

                    <!-- ── Combined Summary Table ── (hidden per requirement) -->
                    <div class="arpu-summary-table-card" style="display:none;">
                        <div class="ast-header">
                            <div class="ast-title">
                                <i class="icon-table" style="color:#e87722;margin-right:6px;"></i>
                                Active Users &amp; New Users VS ARPU — Summary Comparison
                            </div>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="arpu-summary-table">
                                <thead>
                                    <tr class="th-group">
                                        <th style="text-align:left;background:#f7f9fc;color:#7a8da0;border-right:1px solid #edf0f7;">Metric</th>
                                        <th colspan="4" class="grp-current">Current Period</th>
                                        <th colspan="4" class="grp-previous">Previous Period (M&#8209;1)</th>
                                    </tr>
                                    <tr>
                                        <th style="text-align:left;">—</th>
                                        <th>Total Users</th>
                                        <th>New Users</th>
                                        <th>Total Revenue</th>
                                        <th class="col-sep">ARPU (Total Users)</th>
                                        <th>Total Users M&#8209;1</th>
                                        <th>New Users M&#8209;1</th>
                                        <th>Total Revenue M&#8209;1</th>
                                        <th>ARPU M&#8209;1 (Total Users)</th>
                                    </tr>
                                </thead>
                                <tbody id="arpuSummaryTableBody">
                                    <tr>
                                        <td colspan="9" style="text-align:center;color:#b0bec5;padding:22px;">
                                            <div class="arpu-spinner" style="margin:0 auto;"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div><!-- /summary table -->

                    <!-- Trend Chart Card -->
                    <div class="arpu-chart-card">
                        <div class="arpu-chart-header">
                            <div class="ch-title">
                                <i class="icon-bar-chart" style="color:#e87722;margin-right:6px;"></i>
                                Active Users &amp; New Users VS ARPU Trend
                            </div>
                            <div class="ch-legend">
                                <div class="leg-item">
                                    <div class="leg-dot" style="background:#3498db;"></div>
                                    <span>Active Users (Current)</span>
                                </div>
                                <div class="leg-item">
                                    <div class="leg-dot" style="background:#e67e22;"></div>
                                    <span>New Users (Current)</span>
                                </div>
                                <div class="leg-item">
                                    <div class="leg-dot" style="background:#1a5276;"></div>
                                    <span>Active Users (M&#8209;1)</span>
                                </div>
                                <div class="leg-item">
                                    <div class="leg-dot" style="background:#8e44ad;"></div>
                                    <span>New Users (M&#8209;1)</span>
                                </div>
                                <div class="leg-item">
                                    <div class="leg-dot arpu-line"></div>
                                    <span>ARPU (Active Users)</span>
                                </div>
                            </div>
                        </div>
                        <!-- Chart or spinner -->
                        <div id="arpuChartWrap" style="position:relative;">
                            <div class="arpu-chart-spinner" id="arpuChartSpinner"
                                 style="position:absolute;top:0;left:0;right:0;bottom:0;z-index:10;background:#fff;">
                                <div class="arpu-spinner"></div>
                            </div>
                            <div id="arpuTrendChart"></div>
                        </div>
                    </div><!-- /arpu-chart-card -->

                </div><!-- /arpu-page-wrap -->

            </div><!-- /container-fluid -->
        </div><!-- /page-content -->
    </div><!-- /page-container -->

    <?php include('../include/footer.php'); ?>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/jquery.dataTables.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/dataTables.buttons.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/buttons.html5.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/buttons.print.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/scripts/form-samples.js"></script>
    <!-- moment + daterangepicker must load BEFORE the inline report script -->
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/moment.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.min.js"></script>

<script>
jQuery(document).ready(function ($) {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ══════════════════════════════════════════════════════
       ACTIVE USERS & NEW USERS VS ARPU — Combined Report JS
       Calls both:
         get_active_user_arpu_data.php  → active users + ARPU
         get_new_user_arpu_data.php     → new users + ARPU
       and merges into a unified KPI / table / chart view.
       ══════════════════════════════════════════════════════ */

    var currentPeriod  = <?= json_encode($period) ?>;
    var currentPartner = 'all';
    var currentRegion  = 'all';
    var arpuChart      = null;
    var customDateFrom = '';
    var customDateTo   = '';

    /* ── Update M-1 / W-1 labels based on selected period ── */
    function updatePeriodLabels(period) {
        var lbl = (period == 7 || period === '7') ? 'W&#8209;1' : 'M&#8209;1';
        // Group header span
        $('#periodLabel').html(lbl);
        // KPI card labels
        $('#kpiPrevUsers').closest('.arpu-kpi-card').find('.kpi-label').html('Total Users ' + lbl);
        $('#kpiPrevNewUsers').closest('.arpu-kpi-card').find('.kpi-label').html('New Users ' + lbl);
        $('#kpiPrevRevenue').closest('.arpu-kpi-card').find('.kpi-label').html('Total Revenue ' + lbl);
        $('#kpiPrevArpu').closest('.arpu-kpi-card').find('.kpi-label').html('Avg ARPU ' + lbl);
        $('#kpiPrevArpu').closest('.arpu-kpi-card').find('.kpi-sub').html('Revenue &divide; Total Users ' + lbl);
    }

    /* ── Helpers ── */
    function fmtNumber(n) {
        if (n === null || n === undefined || isNaN(n)) return '–';
        return parseFloat(n).toLocaleString('en-US');
    }

    var currencySymbols = {
        'USD': '$', 'INR': '₹', 'NGN': '₦', 'XOF': 'CFA', 'XAF': 'FCFA',
        'CDF': 'FC', 'TZS': 'TSh', 'MGA': 'Ar', 'EUR': '€', 'GBP': '£'
    };
    var activeCurrency = 'USD';

    function getCurrencySymbol(code) {
        return currencySymbols[code] || (code || '$');
    }
    function fmtCurrency(n) {
        if (n === null || n === undefined || isNaN(n)) return '–';
        var sym = getCurrencySymbol(activeCurrency);
        return sym + ' ' + parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtArpu(n) {
        if (n === null || n === undefined || isNaN(n)) return '–';
        var sym = getCurrencySymbol(activeCurrency);
        if (n === 0)   return sym + ' 0.00';
        if (n < 0.001) return sym + ' ' + parseFloat(n).toFixed(8);
        if (n < 1)     return sym + ' ' + parseFloat(n).toFixed(4);
        return sym + ' ' + parseFloat(n).toFixed(2);
    }
    function fmtRev(v) {
        v = parseFloat(v) || 0;
        var sym = getCurrencySymbol(activeCurrency);
        if (v >= 1000000) return sym + (v / 1000000).toFixed(2) + 'M';
        if (v >= 1000)    return sym + (v / 1000).toFixed(1) + 'K';
        return sym + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function makeBadge(pct) {
        if (pct === null || pct === undefined || isNaN(pct)) return { cls: 'flat', txt: '–' };
        pct = parseFloat(pct);
        if (pct > 0)  return { cls: 'up',   txt: '▲ ' + pct.toFixed(1) + '%' };
        if (pct < 0)  return { cls: 'down', txt: '▼ ' + Math.abs(pct).toFixed(1) + '%' };
        return { cls: 'flat', txt: '0.0%' };
    }
    function fmtDateRange(from, to) {
        if (!from || !to) return '–';
        var f = moment(from), t = moment(to);
        if (f.isSame(t, 'day')) return f.format('D MMM YYYY');
        return f.format('D MMM') + ' – ' + t.format('D MMM YYYY');
    }

    /* ── Skeleton loading state ── */
    function setKpiLoading() {
        var ids = [
            '#kpiActiveUsers','#kpiNewUsers','#kpiRevenue','#kpiArpu',
            '#kpiPrevUsers','#kpiPrevNewUsers','#kpiPrevRevenue','#kpiPrevArpu'
        ];
        ids.forEach(function(id) {
            $(id).addClass('loading arpu-skeleton').css({ height: '28px', width: '100px' }).html('&nbsp;');
        });
        ['#kpiUsersChg','#kpiNewUsersChg','#kpiRevenueChg','#kpiArpuChg'].forEach(function(id) {
            $(id).attr('class','kpi-badge flat').text('–');
        });
        ['#kpiPrevUsersBadge','#kpiPrevNewUsersBadge','#kpiPrevRevenueBadge','#kpiPrevArpuBadge'].forEach(function(id) {
            $(id).attr('class','kpi-badge flat').text('Prev Period');
        });
        $('#arpuChartSpinner').show();
        /* Reset summary table */
        $('#arpuSummaryTableBody').html(
            '<tr><td colspan="9" style="text-align:center;color:#b0bec5;padding:22px;">' +
            '<div class="arpu-spinner" style="margin:0 auto;"></div></td></tr>'
        );
    }

    /* ── Populate KPI cards from both API responses ── */
    function renderKpis(active, newUser, meta) {
        /* Period header labels */
        $('#ghPeriodCurrent').text(fmtDateRange(meta.current_from, meta.current_to));
        $('#ghPeriodPrevious').text(fmtDateRange(meta.previous_from, meta.previous_to));

        var usersChg   = makeBadge(active.active_users_change_pct);
        var newChg     = makeBadge(newUser.new_users_change_pct);
        var revChg     = makeBadge(
            active.total_revenue > 0
                ? ((active.total_revenue - active.previous_total_revenue) / active.previous_total_revenue * 100)
                : null
        );
        var arpuChg    = makeBadge(active.average_arpu_change_pct);

        /* Current period */
        $('#kpiActiveUsers').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtNumber(active.active_users));
        $('#kpiUsersChg').attr('class','kpi-badge ' + usersChg.cls).text(usersChg.txt);

        $('#kpiNewUsers').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtNumber(newUser.new_users));
        $('#kpiNewUsersChg').attr('class','kpi-badge ' + newChg.cls).text(newChg.txt);

        $('#kpiRevenue').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtRev(active.total_revenue));
        $('#kpiRevenueChg').attr('class','kpi-badge ' + revChg.cls).text(revChg.txt);

        $('#kpiArpu').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtArpu(active.average_arpu));
        $('#kpiArpuChg').attr('class','kpi-badge ' + arpuChg.cls).text(arpuChg.txt);

        /* Previous period (M-1) */
        $('#kpiPrevUsers').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtNumber(active.previous_active_users));
        $('#kpiPrevNewUsers').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtNumber(newUser.previous_new_users));
        $('#kpiPrevRevenue').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtRev(active.previous_total_revenue));

        /* ARPU M-1 = previous_total_revenue ÷ previous_active_users */
        var prevArpu = (active.previous_active_users > 0)
            ? (active.previous_total_revenue / active.previous_active_users)
            : 0;
        $('#kpiPrevArpu').removeClass('loading arpu-skeleton').css({height:'',width:''}).text(fmtArpu(prevArpu));
    }

    /* ── Populate summary comparison table ── */
    function renderSummaryTable(active, newUser) {
        /* ARPU M-1 */
        var prevArpu = (active.previous_active_users > 0)
            ? (active.previous_total_revenue / active.previous_active_users) : 0;

        /* Change badges (text-only for table) */
        function chgSpan(pct) {
            if (pct === null || isNaN(pct)) return '<span class="badge-flat">–</span>';
            pct = parseFloat(pct);
            if (pct > 0)  return '<span class="badge-up">▲ ' + pct.toFixed(1) + '%</span>';
            if (pct < 0)  return '<span class="badge-down">▼ ' + Math.abs(pct).toFixed(1) + '%</span>';
            return '<span class="badge-flat">0.0%</span>';
        }
        var revChangePct = (active.previous_total_revenue > 0)
            ? ((active.total_revenue - active.previous_total_revenue) / active.previous_total_revenue * 100) : null;

        var html = '<tr>' +
            '<td class="col-label">Totals</td>' +
            /* current */
            '<td>' + fmtNumber(active.active_users) + ' ' + chgSpan(active.active_users_change_pct) + '</td>' +
            '<td>' + fmtNumber(newUser.new_users) + ' ' + chgSpan(newUser.new_users_change_pct) + '</td>' +
            '<td>' + fmtRev(active.total_revenue) + ' ' + chgSpan(revChangePct) + '</td>' +
            '<td class="col-sep">' + fmtArpu(active.average_arpu) + ' ' + chgSpan(active.average_arpu_change_pct) + '</td>' +
            /* previous */
            '<td>' + fmtNumber(active.previous_active_users) + '</td>' +
            '<td>' + fmtNumber(newUser.previous_new_users) + '</td>' +
            '<td>' + fmtRev(active.previous_total_revenue) + '</td>' +
            '<td>' + fmtArpu(prevArpu) + '</td>' +
            '</tr>';

        $('#arpuSummaryTableBody').html(html);
    }

    /* ── Render combined ECharts trend chart ── */
    function renderArpuChart(activeChart, newChart, period) {
        var dom = document.getElementById('arpuTrendChart');
        if (!dom) return;
        if (arpuChart) { arpuChart.dispose(); }
        arpuChart = echarts.init(dom);

        /* Merge by index — both APIs return same date slots */
        var categories    = activeChart.map(function(r) { return r.label; });
        var curActive     = activeChart.map(function(r) { return r.active_users; });
        var prevActive    = activeChart.map(function(r) { return r.active_users_previous; });
        var curNew        = newChart.map(function(r)    { return r.new_users; });
        var prevNew       = newChart.map(function(r)    { return r.new_users_previous; });
        var arpuVals      = activeChart.map(function(r) { return r.arpu_current; });

        var maxUsers  = Math.max.apply(null, curActive.concat(prevActive).concat(curNew).concat(prevNew).concat([1]));
        var maxArpu   = Math.max.apply(null, arpuVals.concat([1]));
        var yUsersMax = Math.ceil(maxUsers * 1.3);
        var yArpuMax  = parseFloat((maxArpu  * 1.35).toFixed(2));

        var option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'cross', label: { backgroundColor: '#283b56' } },
                backgroundColor: 'rgba(255,255,255,0.97)',
                borderColor: '#e0e7ef',
                borderWidth: 1,
                padding: [10, 14],
                textStyle: { color: '#2c3e50', fontSize: 12 },
                formatter: function(params) {
                    var idx       = params.length ? params[0].dataIndex : 0;
                    var dateLabel = (activeChart[idx] && activeChart[idx].label)
                                   ? activeChart[idx].label
                                   : (params[0] ? params[0].axisValue : '');
                    var html = '<div style="font-weight:700;margin-bottom:6px;color:#2c3e50;">' + dateLabel + '</div>';
                    params.forEach(function(p) {
                        var colorMap = {
                            'Active Users (Current)' : '#3498db',
                            'New Users (Current)'    : '#e67e22',
                            'Active Users (M-1)'     : '#1a5276',
                            'New Users (M-1)'        : '#8e44ad',
                            'ARPU (Current)'         : '#7C3AED'
                        };
                        var c = colorMap[p.seriesName] || '#999';
                        if (p.seriesName === 'ARPU (Current)') {
                            html += '<div style="margin-top:4px;padding-top:4px;border-top:1px solid #f0f3f8;">';
                            html += '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + c + ';margin-right:6px;vertical-align:middle;"></span>';
                            html += '<span style="color:#5a6a80;">ARPU: </span><b>' + fmtRev(p.value) + '</b>';
                            html += '<div style="font-size:11px;color:#9aabb8;margin-top:2px;">Revenue ÷ Active Users</div>';
                            html += '</div>';
                        } else {
                            html += '<div style="margin-top:3px;">';
                            html += '<span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + c + ';margin-right:6px;vertical-align:middle;"></span>';
                            html += '<span style="color:#5a6a80;">' + p.seriesName + ': </span><b>' + fmtNumber(p.value) + '</b> users';
                            html += '</div>';
                        }
                    });
                    return html;
                }
            },
            legend: { show: false },
            toolbox: {
                show: true, orient: 'horizontal', left: 'right', top: 'bottom',
                itemSize: 14, itemGap: 8,
                feature: {
                    dataView:    { readOnly: true, title: 'Data View' },
                    restore:     { title: 'Restore' },
                    saveAsImage: { title: 'Save Image' }
                }
            },
            dataZoom: { show: false, start: 0, end: 100 },
            grid: { left: 60, right: 100, top: 36, bottom: 50, containLabel: true },
            xAxis: [{
                type: 'category',
                boundaryGap: true,
                data: categories,
                axisLabel: {
                    interval: period == 7 ? 0 : 'auto',
                    rotate:   period == 7 ? 0 : 40,
                    fontSize: 11, fontWeight: 600, color: '#4a5a6a'
                },
                axisLine: { lineStyle: { color: '#e4e9f0' } },
                axisTick: { show: false }
            }],
            yAxis: [
                {
                    type: 'value', name: 'Users', nameLocation: 'end', nameGap: 10,
                    nameTextStyle: { color: '#4a5a6a', fontSize: 12, fontWeight: 600 },
                    min: 0, max: yUsersMax, minInterval: 1, boundaryGap: [0, 0.2],
                    axisLabel: {
                        color: '#4a5a6a', fontSize: 12, fontWeight: 600,
                        formatter: function(v) { return v >= 1000 ? (v/1000).toFixed(1)+'k' : v; }
                    },
                    axisLine: { show: true, lineStyle: { color: '#dde3ec' } },
                    splitLine: { lineStyle: { color: '#f0f3f8' } }
                },
                {
                    type: 'value',
                    name: 'ARPU (' + getCurrencySymbol(activeCurrency) + ')',
                    nameLocation: 'end', nameGap: 8,
                    nameTextStyle: { color: '#4a5a6a', fontSize: 12, fontWeight: 600 },
                    min: 0, max: yArpuMax, boundaryGap: [0, 0.2],
                    axisLabel: {
                        color: '#4a5a6a', fontSize: 12, fontWeight: 600,
                        formatter: function(v) { return getCurrencySymbol(activeCurrency) + ' ' + v.toFixed(2); }
                    },
                    axisLine: { show: true, lineStyle: { color: '#dde3ec' } },
                    splitLine: { show: false }
                }
            ],
            series: [
                {
                    name: 'Active Users (Current)', type: 'bar',
                    xAxisIndex: 0, yAxisIndex: 0,
                    barMaxWidth: 16, barGap: '8%',
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#5dade2' }, { offset: 1, color: '#2e86c1' }
                        ]),
                        borderRadius: [4, 4, 0, 0]
                    },
                    emphasis: { itemStyle: { color: '#1a78c2' } },
                    data: curActive
                },
                {
                    name: 'New Users (Current)', type: 'bar',
                    xAxisIndex: 0, yAxisIndex: 0,
                    barMaxWidth: 16,
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#f39c12' }, { offset: 1, color: '#d35400' }
                        ]),
                        borderRadius: [4, 4, 0, 0]
                    },
                    emphasis: { itemStyle: { color: '#c0392b' } },
                    data: curNew
                },
                {
                    name: 'Active Users (M-1)', type: 'bar',
                    xAxisIndex: 0, yAxisIndex: 0,
                    barMaxWidth: 16,
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#5d7a9e' }, { offset: 1, color: '#1a3a5c' }
                        ]),
                        borderRadius: [4, 4, 0, 0]
                    },
                    emphasis: { itemStyle: { color: '#1a3a5c' } },
                    data: prevActive
                },
                {
                    name: 'New Users (M-1)', type: 'bar',
                    xAxisIndex: 0, yAxisIndex: 0,
                    barMaxWidth: 16,
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#bb8fce' }, { offset: 1, color: '#7d3c98' }
                        ]),
                        borderRadius: [4, 4, 0, 0]
                    },
                    emphasis: { itemStyle: { color: '#7d3c98' } },
                    data: prevNew
                },
                {
                    name: 'ARPU (Current)', type: 'line',
                    xAxisIndex: 0, yAxisIndex: 1,
                    smooth: true, symbol: 'circle', symbolSize: 8,
                    lineStyle: { color: '#7C3AED', width: 2.5 },
                    itemStyle: { color: '#fff', borderColor: '#7C3AED', borderWidth: 2.5 },
                    emphasis: {
                        itemStyle: {
                            color: '#7C3AED', borderColor: '#fff',
                            borderWidth: 2, shadowBlur: 8, shadowColor: 'rgba(124,58,237,.35)'
                        }
                    },
                    areaStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: 'rgba(124,58,237,.12)' },
                            { offset: 1, color: 'rgba(124,58,237,.01)' }
                        ])
                    },
                    z: 10,
                    data: arpuVals
                }
            ]
        };

        arpuChart.setOption(option);
        arpuChart.resize();
        $('#arpuChartSpinner').hide();
    }

    /* ── Main data loader — fires two parallel AJAX calls ── */
    function loadArpuData(period) {
        currentPeriod = period;
        updatePeriodLabels(period);
        setKpiLoading();

        var baseParams = {
            action    : 'getArpuReport',
            partnerid : currentPartner,
            region    : currentRegion
        };
        if (period === 'custom' && customDateFrom && customDateTo) {
            baseParams.dateFrom = customDateFrom;
            baseParams.dateTo   = customDateTo;
        } else {
            baseParams.period = period;
        }

        var reqActive = $.ajax({
            url     : 'datatables-scripts/get_active_user_arpu_data.php',
            type    : 'GET',
            data    : $.extend({}, baseParams),
            dataType: 'json'
        });

        var reqNew = $.ajax({
            url     : 'datatables-scripts/get_new_user_arpu_data.php',
            type    : 'GET',
            data    : $.extend({}, baseParams),
            dataType: 'json'
        });

        $.when(reqActive, reqNew).done(function(activeRes, newRes) {
            /* $.when passes [data, status, xhr] per request */
            var active  = activeRes[0];
            var newUser = newRes[0];

            if (!active || active.error || !newUser || newUser.error) {
                console.error('Combined ARPU API error', active, newUser);
                return;
            }

            if (active.currency) activeCurrency = active.currency;

            renderKpis(active.summary, newUser.summary, active.meta);
            renderSummaryTable(active.summary, newUser.summary);
            renderArpuChart(active.chart, newUser.chart, period);

        }).fail(function(xhr, status, err) {
            console.error('Combined ARPU AJAX error', status, err);
            $('#arpuChartSpinner').html(
                '<div style="text-align:center;color:#c0392b;padding:40px;">' +
                '<i class="icon-warning-sign" style="font-size:24px;"></i>' +
                '<p style="margin-top:8px;">Failed to load data. Please try again.</p>' +
                '</div>'
            );
        });
    }

    /* ── Load region + partner dropdowns ── */
    function loadFilterOptions() {
        $.ajax({
            url     : 'datatables-scripts/get_active_user_arpu_data.php',
            type    : 'GET',
            data    : { action: 'getFilterOptions', region: 'all' },
            dataType: 'json',
            success : function(resp) {
                if (!resp) return;
                var $region = $('#arpuRegionFilter');
                $region.find('option:not(:first)').remove();
                if (resp.regions && resp.regions.length) {
                    $.each(resp.regions, function(i, r) {
                        $region.append($('<option>').val(r.Country).text(r.Country));
                    });
                }
                populatePartners(resp.partners);
            }
        });
    }

    function populatePartners(partners) {
        var $partner = $('#arpuPartnerFilter');
        var prevVal  = $partner.val();
        $partner.find('option:not(:first)').remove();
        if (partners && partners.length) {
            $.each(partners, function(i, p) {
                $partner.append($('<option>').val(p.customerid).text(p.partner));
            });
        }
        if (prevVal && prevVal !== 'all') {
            $partner.val(prevVal);
            if ($partner.val() === null) $partner.val('all');
        }
    }

    function reloadPartnersForRegion(region) {
        $.ajax({
            url     : 'datatables-scripts/get_active_user_arpu_data.php',
            type    : 'GET',
            data    : { action: 'getFilterOptions', region: region },
            dataType: 'json',
            success : function(resp) {
                if (resp && resp.partners) populatePartners(resp.partners);
            }
        });
    }

    /* ── Period dropdown + custom daterangepicker ── */
    (function initDateRangePicker() {
        var $select  = $('#arpuPeriodSelect');
        var $wrap    = $('#arpuCustomRangeWrap');
        var $input   = $('#arpuCustomRange');
        var $msg     = $('#arpuDateRangeMsg');
        var MAX_DAYS = 90;

        $input.daterangepicker({
            opens        : 'left',
            autoApply    : false,
            showDropdowns: true,
            maxSpan      : { days: MAX_DAYS },
            locale       : { format: 'YYYY-MM-DD', cancelLabel: 'Clear' },
            startDate    : moment().subtract(6, 'days'),
            endDate      : moment()
        });

        $input.on('apply.daterangepicker', function(ev, picker) {
            var startStr = picker.startDate.format('YYYY-MM-DD');
            var endStr   = picker.endDate.format('YYYY-MM-DD');
            var days     = picker.endDate.diff(picker.startDate, 'days');
            if (days > MAX_DAYS) {
                $msg.html('<span style="color:#c0392b;font-weight:700;">Max 90 days exceeded</span>');
                return;
            }
            $msg.text('Max 90 days');
            customDateFrom = startStr;
            customDateTo   = endStr;
            $input.val(startStr + '  →  ' + endStr);
            if (history.replaceState) {
                history.replaceState(null, '', '?period=custom&dateFrom=' + startStr + '&dateTo=' + endStr);
            }
            loadArpuData('custom');
        });

        $input.on('cancel.daterangepicker', function() {
            $select.val('7').trigger('change');
        });

        $select.on('change', function() {
            var val = $(this).val();
            if (val === 'custom') {
                $wrap.css('display', 'flex');
                $input.click();
            } else {
                $wrap.css('display', 'none');
                customDateFrom = '';
                customDateTo   = '';
                if (history.replaceState) history.replaceState(null, '', '?period=' + val);
                loadArpuData(val);
            }
        });
    })();

    /* ── Partner filter ── */
    $('#arpuPartnerFilter').on('change', function() {
        currentPartner = $(this).val();
        loadArpuData(currentPeriod);
    });

    /* ── Region filter ── */
    $('#arpuRegionFilter').on('change', function() {
        currentRegion  = $(this).val();
        currentPartner = 'all';
        $('#arpuPartnerFilter').val('all');
        reloadPartnersForRegion(currentRegion);
        loadArpuData(currentPeriod);
    });

    /* ── Resize chart on window resize ── */
    $(window).on('resize', function() {
        if (arpuChart) arpuChart.resize();
    });

    /* ── Initial load ── */
    loadFilterOptions();
    updatePeriodLabels(currentPeriod);
    loadArpuData(currentPeriod);

});
</script>


</body>
</html>
