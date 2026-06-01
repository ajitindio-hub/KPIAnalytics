<?php
if (session_id() == '') session_start();
header("Cache_control:private");

$accessarray = $_SESSION['accessarray'];
require('../include/checkdata.php');
checkExpiredSession($accessarray);
if($_SESSION['customerid'] == 1) {

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

$period = isset($_REQUEST['period']) && in_array($_REQUEST['period'], ['7', '30', 'today', 'custom'])
          ? $_REQUEST['period']
          : '7';

/* ── Labels ── */
$labelHome    = getLabel($lang, $module, "home");
$labelReports = getLabel($lang, $module, "reports");
$labelBilling = getLabel($lang, $module, "billing");

/* ── Period date range for subtitle ── */
$endDate   = new DateTime();
$startDate = ($period == '30')
    ? (clone $endDate)->modify('-29 days')
    : (clone $endDate)->modify('-6 days');
$formattedStart = $startDate->format('d M Y');
$formattedEnd   = $endDate->format('d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?= $WIFILANTITLE ?> – Revenue Distribution</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <?php include('../include/global-styles.php'); ?>
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/css/pages/search.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/DT_bootstrap.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/buttons.dataTables.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/responsive.dataTables.min.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" />
    <link rel="shortcut icon" href="<?=$baseurl?>/<?=$appname?>/img/favicon.ico" />
    <script src="<?= $baseurl ?>/<?= $appname ?>/<?= $assetsDir ?>/echarts-6.0.0/package/dist/echarts.min.js"></script>
    <script src="<?= $baseurl ?>/<?= $appname ?>/<?= $assetsDir ?>/echarts-6.0.0/package/asset/echart-render.js"></script>

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

        /* ═══════════════════════════════════════════
           LAYOUT & PAGE WRAPPER
        ═══════════════════════════════════════════ */
        .rev-breadcrumb { margin-top: 12px; }

        /* ═══════════════════════════════════════════
           REPORT HEADER
        ═══════════════════════════════════════════ */
        .rev-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            margin-top: 12px;
        }
        .rev-header-left h3 {
            margin: 0 0 3px;
            font-size: 20px;
            font-weight: 700;
            color: #1e2b3c;
            letter-spacing: -0.3px;
        }
        .rev-header-left .rev-subtitle {
            font-size: 12px;
            color: #8a97a8;
            font-weight: 400;
        }
        .rev-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Period select */
        .du-period-select {
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #5a6a80;
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            cursor: pointer;
        }
        .du-btn-daterange {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            color: #5a6a80;
            cursor: pointer;
        }
        .du-btn-daterange:hover { border-color: #e87722; color: #e87722; }

        /* View toggle */
        .view-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .view-label {
            font-size: 13px;
            font-weight: 500;
            color: #8a97a8;
        }
        .view-toggle {
            display: flex;
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .view-toggle button {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 500;
            background: transparent;
            border: none;
            color: #5a6a80;
            cursor: pointer;
            font-family: inherit;
            transition: all .18s;
        }
        .view-toggle button.active {
            background: #1e2b3c;
            color: #fff;
        }
        .vt-checkbox {
            width: 14px; height: 14px;
            border: 2px solid currentColor;
            border-radius: 3px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .view-toggle button.active .vt-checkbox::after {
            content: '';
            width: 8px; height: 5px;
            border-left: 2px solid #fff;
            border-bottom: 2px solid #fff;
            transform: rotate(-45deg) translateY(-1px);
            display: block;
        }

        /* ═══════════════════════════════════════════
           KPI CARDS
        ═══════════════════════════════════════════ */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 18px;
        }
        @media (max-width: 1024px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 540px)  { .kpi-row { grid-template-columns: 1fr; } }

        .kpi-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 12px;
            padding: 16px 18px 14px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
            position: relative;
            overflow: hidden;
            transition: box-shadow .18s, transform .18s;
        }
        .kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.10); transform: translateY(-2px); }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 12px 12px 0 0;
        }
        .kpi-card.kpi-blue::before   { background: linear-gradient(90deg,#3b7ef8,#6aa3ff); }
        .kpi-card.kpi-green::before  { background: linear-gradient(90deg,#10b981,#34d399); }
        .kpi-card.kpi-red::before    { background: linear-gradient(90deg,#ef4444,#f87171); }
        .kpi-card.kpi-orange::before { background: linear-gradient(90deg,#f97316,#fb923c); }

        .kpi-icon {
            position: absolute;
            top: 14px; right: 16px;
            font-size: 22px;
            opacity: 0.12;
        }
        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 8px;
        }
        .kpi-value {
            font-size: 22px;
            font-weight: 700;
            color: #1e2b3c;
            line-height: 1.1;
            margin-bottom: 6px;
            word-break: break-word;
        }
        .kpi-sub {
            font-size: 12px;
            color: #8a97a8;
            margin-bottom: 4px;
        }
        .kpi-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .kpi-badge.up   { color: #10b981; background: #ecfdf5; }
        .kpi-badge.down { color: #ef4444; background: #fef2f2; }

        /* Skeleton loader */
        .skel {
            background: linear-gradient(90deg,#f0f4f8 25%,#e4e9f0 50%,#f0f4f8 75%);
            background-size: 200% 100%;
            animation: shimmer 1.3s infinite;
            border-radius: 4px;
            display: inline-block;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ═══════════════════════════════════════════
           MAIN CONTENT GRID
        ═══════════════════════════════════════════ */
        .rev-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 16px;
            margin-bottom: 18px;
            align-items: start;
        }
        @media (max-width: 900px) { .rev-grid { grid-template-columns: 1fr; } }

        .rev-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
            overflow: hidden;
        }
        .rev-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px 0;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .rev-card-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #1e2b3c;
        }
        .period-badge {
            font-size: 11px;
            font-weight: 500;
            background: #f0f4fa;
            color: #5a6a80;
            padding: 3px 10px;
            border-radius: 20px;
        }

        #donutChart {
            width: 100%;
            height: 260px;
        }
        .donut-summary {
            display: flex;
            justify-content: space-around;
            padding: 10px 16px 16px;
            border-top: 1px solid #f0f4fa;
            flex-wrap: wrap;
            gap: 8px;
        }
        .ds-item { text-align: center; }
        .ds-item .ds-val {
            font-size: 16px;
            font-weight: 700;
            color: #1e2b3c;
        }
        .ds-item .ds-label {
            font-size: 11px;
            color: #8a97a8;
            margin-top: 2px;
        }

        .table-card-body { padding: 0 16px 16px; }

        .rev-card .dataTables_wrapper { font-size: 13px; }
        .rev-card .dataTables_length select,
        .rev-card .dataTables_filter input {
            border: 1px solid #dde3ec;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 13px;
        }
        .rev-card .dataTables_filter input { margin-left: 6px; }
        .rev-card table.dataTable thead th {
            background: #f8faff;
            font-size: 11px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 2px solid #e4e9f0;
            white-space: nowrap;
        }
        .rev-card table.dataTable tbody tr:hover { background: #f8faff !important; }
        .rev-card .dt-buttons .btn {
            font-size: 12px;
            padding: 5px 12px;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #dde3ec;
            color: #5a6a80;
        }
        .rev-card .dt-buttons .btn:hover { background: #f0f4fa; }

        .badge-share {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #f0f4fa;
            color: #5a6a80;
        }
        .change-up   { color: #10b981; font-weight: 600; }
        .change-down { color: #ef4444; font-weight: 600; }

        .dot-color {
            display: inline-block;
            width: 9px; height: 9px;
            border-radius: 50%;
            margin-right: 6px;
            vertical-align: middle;
            flex-shrink: 0;
        }

        td.rev-col {
            font-weight: 600;
            color: #1e2b3c;
            font-family: 'Courier New', monospace;
        }

        .rev-progress {
            height: 4px;
            background: #f0f4fa;
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
            min-width: 60px;
        }
        .rev-progress-fill {
            height: 100%;
            border-radius: 2px;
            background: linear-gradient(90deg, #3b7ef8, #6aa3ff);
            transition: width .5s ease;
        }

        .chart-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 260px;
            color: #8a97a8;
            font-size: 13px;
            gap: 8px;
        }

        .nodata_image {
            display: block;
            margin: 0 auto;
            max-width: 180px;
            opacity: .6;
        }
        .nodata-chart-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 260px;
            gap: 10px;
        }
        .nodata-chart-wrap .nodata_image { margin: 0; }
        .nodata-chart-wrap span { font-size: 13px; color: #8a97a8; }
        .nodata-table-row td {
            padding: 40px 20px !important;
            text-align: center;
            color: #8a97a8;
            font-size: 13px;
        }
        .nodata-table-row .nodata-icon {
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
            opacity: 0.4;
        }

        @media (max-width: 600px) {
            .rev-header-left h3 { font-size: 16px; }
            .kpi-value { font-size: 18px; }
        }
        div.dt-buttons { float: right !important; }

        /* ── Customer multiselect ── */
        .cms-outer-wrap {
            position: relative;
            min-width: 280px;
            width: 280px;
        }
        .cms-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 11px;
            border: 1px solid #dde3ec;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            font-size: 13px;
            color: #1e2b3c;
            user-select: none;
            min-height: 34px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            transition: border-color .15s;
            width: 100%;
            box-sizing: border-box;
        }
        .cms-trigger:hover { border-color: #e87722; }
        .cms-trigger.active { border-color: #3b7ef8; }
        .cms-tags-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            flex: 1;
            align-items: center;
            overflow: hidden;
            min-width: 0;
        }
        .cms-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #eef2ff;
            color: #3b7ef8;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .cms-tag-x { cursor: pointer; font-size: 13px; line-height: 1; opacity: .7; }
        .cms-tag-x:hover { opacity: 1; }
        .cms-placeholder { color: #8a97a8; font-size: 13px; white-space: nowrap; }
        .cms-count-badge {
            background: #3b7ef8;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 10px;
            flex-shrink: 0;
        }
        .cms-arrow { font-size: 10px; color: #8a97a8; flex-shrink: 0; transition: transform .2s; }
        .cms-arrow.open { transform: rotate(180deg); }
        .cms-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            width: 100%;
            box-sizing: border-box;
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 10px;
            box-shadow: 0 6px 24px rgba(0,0,0,.13);
            z-index: 99999;
            flex-direction: column;
            overflow: hidden;
        }
        .cms-dropdown.open { display: flex; }
        .cms-dd-search { padding: 8px 10px; border-bottom: 1px solid #f0f4fa; }
        .cms-dd-search input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            outline: none;
        }
        .cms-dd-search input:focus { border-color: #3b7ef8; }
        .cms-dd-actions { display: flex; gap: 6px; padding: 5px 10px; border-bottom: 1px solid #f0f4fa; }
        .cms-dd-action {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 4px;
            border: 1px solid #dde3ec;
            background: #f8faff;
            color: #5a6a80;
            cursor: pointer;
            font-weight: 500;
            font-family: inherit;
        }
        .cms-dd-action:hover { background: #eef2ff; color: #3b7ef8; border-color: #3b7ef8; }
        .cms-dd-list { max-height: 240px; overflow-y: auto; flex: 1; }
        .cms-dd-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            font-size: 13px;
            color: #1e2b3c;
            cursor: pointer;
            transition: background .1s;
        }
        .cms-dd-item:hover { background: #f8faff; }
        .cms-dd-item.sel { background: #eef2ff; }
        .cms-dd-item input[type=checkbox] {
            accent-color: #3b7ef8;
            width: 14px; height: 14px;
            flex-shrink: 0;
            pointer-events: none;
        }
        .cms-dd-empty { padding: 18px; text-align: center; color: #8a97a8; font-size: 13px; }
        .cms-dd-footer { padding: 8px 10px; border-top: 1px solid #f0f4fa; }
        .cms-dd-apply {
            width: 100%;
            padding: 7px;
            background: #1e2b3c;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background .15s;
        }
        .cms-dd-apply:hover { background: #e87722; }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "report";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "RevenueDistribution";
            include('../include/sidebar_temp.php');
        ?>

        <div class="page-content">
            <div class="container-fluid rev-page">

                <!-- Breadcrumb -->
                <div class="rev-breadcrumb">
                    <?php include('../include/style-customizer.php'); ?>
                    <ul class="breadcrumb" style="margin:0;">
                        <li>
                            <i class="icon-home"></i>
                            <a href="<?=$DASHBOARDPATH?>"><?=$labelHome?></a>
                            <i class="icon-angle-right"></i>
                            <a href="#"><?=$labelReports?></a>
                            <i class="icon-angle-right"></i>
                            <a href="#">Revenue</a>
                            <i class="icon-angle-right"></i>
                            <span>Revenue Distribution</span>
                        </li>
                    </ul>
                </div>
                <br>

                <!-- Report Header -->
                <div class="rev-header breadcrumb">
                    <div class="rev-header-left">
                        <h3>Total Revenue Distribution</h3>
                        <div class="rev-subtitle" id="dateRangeLabel">
                            <i class="icon-calendar"></i>
                            <span id="dateRangeText"><?= $formattedStart . ' - ' . $formattedEnd ?></span>
                        </div>
                    </div>

                    <div class="rev-header-right">
                        <!-- Customer Multiselect -->
                        <div class="cms-outer-wrap" id="cmsWrap">
                            <div class="cms-trigger" id="cmsTrigger">
                                <div class="cms-tags-row" id="cmsTagsRow">
                                    <span class="cms-placeholder" id="cmsPlaceholder">All Customers</span>
                                </div>
                                <span class="cms-count-badge" id="cmsCountBadge" style="display:none;"></span>
                                <span class="cms-arrow" id="cmsArrow">▼</span>
                            </div>
                            <div class="cms-dropdown" id="cmsDropdown">
                                <div class="cms-dd-search">
                                    <input type="text" id="cmsSearch" placeholder="Search customers…" autocomplete="off">
                                </div>
                                <div class="cms-dd-actions">
                                    <button class="cms-dd-action" id="cmsSelectAll">Select All</button>
                                    <button class="cms-dd-action" id="cmsClearAll">Clear All</button>
                                </div>
                                <div class="cms-dd-list" id="cmsList">
                                    <div class="cms-dd-empty">Loading…</div>
                                </div>
                                <div class="cms-dd-footer">
                                    <button class="cms-dd-apply" id="cmsApply">Apply Filter</button>
                                </div>
                            </div>
                        </div>

                        <!-- Period Select -->
                        <div class="du-filter-wrap">
                            <!--select id="periodSelect" class="du-period-select">
                                <option value="today">Today</option>
                                <option value="7" <?= $period == '7' ? 'selected' : '' ?>>Last 7 Days</option>
                                <option value="30" <?= $period == '30' ? 'selected' : '' ?>>Last 30 Days</option>
                                <option value="custom">Custom Range</option>
                            </select-->
                            <div id="customDateWrap" style="display:none;">
                                <button type="button" id="reportrange" class="du-btn-daterange">
                                    <i class="icon-calendar"></i>
                                    <span>Select date range</span>
                                    <i class="icon-angle-down" style="margin-left:4px;font-size:10px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- View Toggle -->
                <div class="view-toggle-wrap">
                    <span class="view-label">View:</span>
                    <div class="view-toggle">
                        <button class="active" id="btnPartner" onclick="switchView('partner')">
                            <span class="vt-checkbox"></span> By Partner
                        </button>
                        <button id="btnDataPack" onclick="switchView('datapack')">
                            <span class="vt-checkbox"></span> By Data Pack
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-row">
                    <div class="kpi-card kpi-blue">
                        <i class="icon-bar-chart kpi-icon"></i>
                        <div class="kpi-label" id="kpiHighRevLabel">Highest Revenue</div>
                        <div class="kpi-value" id="kpiHighRev">
                            <span class="skel" style="width:100px;height:22px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub" id="kpiHighRevSub">&nbsp;</div>
                    </div>
                    <div class="kpi-card kpi-green">
                        <i class="icon-arrow-up kpi-icon"></i>
                        <div class="kpi-label" id="kpiHighGrowthLabel">Highest Growth</div>
                        <div class="kpi-value" id="kpiHighGrowth">
                            <span class="skel" style="width:100px;height:22px;">&nbsp;</span>
                        </div>
                        <div id="kpiHighGrowthBadge"></div>
                    </div>
                    <div class="kpi-card kpi-red">
                        <i class="icon-arrow-down kpi-icon"></i>
                        <div class="kpi-label" id="kpiLowRevLabel">Lowest Revenue</div>
                        <div class="kpi-value" id="kpiLowRev">
                            <span class="skel" style="width:100px;height:22px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub" id="kpiLowRevSub">&nbsp;</div>
                    </div>
                    <div class="kpi-card kpi-orange">
                        <i class="icon-warning-sign kpi-icon"></i>
                        <div class="kpi-label" id="kpiLowGrowthLabel">Lowest Growth</div>
                        <div class="kpi-value" id="kpiLowGrowth">
                            <span class="skel" style="width:100px;height:22px;">&nbsp;</span>
                        </div>
                        <div id="kpiLowGrowthBadge"></div>
                    </div>
                </div>

                <!-- Main Grid: Donut + Table -->
                <div class="rev-grid">

                    <!-- Donut Chart Card -->
                    <div class="rev-card">
                        <div class="rev-card-header">
                            <h4 id="donutTitle">Revenue by Partner</h4>
                            <!--span class="period-badge" id="periodBadge">
                                <?= $period == '7' ? 'Weekly' : 'Monthly' ?>
                            </span-->
                        </div>
                        <div id="donutChart">
                            <div class="chart-loading">
                                <i class="icon-spinner icon-spin"></i> Loading…
                            </div>
                        </div>
                        <div class="donut-summary" id="donutSummary" style="display:none;">
                            <div class="ds-item">
                                <div class="ds-val" id="dsTotalRev">–</div>
                                <div class="ds-label">Total Revenue</div>
                            </div>
                            <div class="ds-item">
                                <div class="ds-val" id="dsTotalPacks">–</div>
                                <div class="ds-label" id="dsTotalCountLabel">Partners</div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Card -->
                    <div class="rev-card">
                        <div class="rev-card-header">
                            <h4 id="tableTitle">Revenue by Partner</h4>
                        </div>
                        <div class="table-card-body">
                            <table id="siteStatusTable"
                                   class="table table-striped table-hover table-bordered"
                                   style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th id="colSecondHeader">Partner</th>
                                        <th>Revenue</th>
                                        <th>Share %</th>
                                    </tr>
                                </thead>
                                <tbody id="siteTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center" style="padding:30px;color:#8a97a8;">
                                            <i class="icon-spinner icon-spin"></i> Loading data…
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- /rev-grid -->

            </div><!-- /container-fluid -->
        </div><!-- /page-content -->
    </div><!-- /page-container -->

    <?php include('../include/footer.php'); ?>

    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/jquery.dataTables.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/dataTables.buttons.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/buttons.html5.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/buttons.print.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/scripts/form-samples.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/moment.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/select2/select2.min.js"></script>

<script>
/* ════════════════════════════════════════════════
   GLOBALS
════════════════════════════════════════════════ */
var currentPeriod       = <?= json_encode($period) ?>;
var currentView         = 'partner';
var currentFrom         = null;
var currentTo           = null;
var donutChart          = null;
var selectedCustomerIds = [];
var allCustomers        = [];

/* ECharts color palette */
var PALETTE = [
    '#3b7ef8','#10b981','#7c3aed',
    '#f97316','#ef4444','#94a3b8',
    '#f59e0b','#06b6d4','#ec4899'
];

/* ════════════════════════════════════════════════
   VIEW CONFIG
════════════════════════════════════════════════ */
var VIEW_CONFIG = {
    partner: {
        action            : 'getTotalRevenueDistributionByPartner',
        chartTitle        : 'Revenue by Partner',
        tableTitle        : 'Revenue by Partner',
        colHeader         : 'Partner',
        countLabel        : 'Partners',
        kpiHighRevLabel   : 'Highest Revenue',
        kpiLowRevLabel    : 'Lowest Revenue',
        kpiHighGrowthLabel: 'Highest Growth',
        kpiLowGrowthLabel : 'Lowest Growth'
    },
    datapack: {
        action            : 'getTotalRevenueDistributionByDataPack',
        chartTitle        : 'Revenue by Data Pack',
        tableTitle        : 'Revenue by Data Pack',
        colHeader         : 'Data Pack',
        countLabel        : 'Data Packs',
        kpiHighRevLabel   : 'Highest Revenue',
        kpiLowRevLabel    : 'Lowest Revenue',
        kpiHighGrowthLabel: 'Highest Growth',
        kpiLowGrowthLabel : 'Lowest Growth'
    }
};

/* ════════════════════════════════════════════════
   INIT
════════════════════════════════════════════════ */
jQuery(document).ready(function ($) {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ── Daterangepicker ── */
    $('#reportrange').daterangepicker({
        startDate      : moment().subtract(6, 'days'),
        endDate        : moment(),
        maxDate        : moment(),
        maxSpan        : { days: 90 },
        showDropdowns  : true,
        linkedCalendars: false,
        locale: {
            format     : 'DD MMM YYYY',
            separator  : ' – ',
            applyLabel : 'Apply',
            cancelLabel: 'Cancel',
            firstDay   : 1
        },
        ranges: {
            //'Today'       : [moment(), moment()],
            'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            //'This Month'  : [moment().startOf('month'), moment().endOf('month')],
            //'Last Month'  : [moment().subtract(1,'month').startOf('month'),
            //                 moment().subtract(1,'month').endOf('month')]
        }
    }, function (start, end) {
        $('#reportrange span').html(
            start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY')
        );
        currentFrom = start.format('YYYY-MM-DD');
        currentTo   = end.format('YYYY-MM-DD');
        updateComparisonLabels('custom', currentFrom, currentTo);
        refreshAllWidgets();
    });

    $('#reportrange span').html(
        moment().subtract(6,'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ── Period select ── */
   // $('#periodSelect').on('change', function () {
	currentPeriod = $(this).val();
	currentPeriod = "custom";

        if (currentPeriod === 'custom') {
            $('#customDateWrap').show();
            var drp    = $('#reportrange').data('daterangepicker');
            currentFrom = drp.startDate.format('YYYY-MM-DD');
            currentTo   = drp.endDate.format('YYYY-MM-DD');
        } else {
            $('#customDateWrap').hide();
            currentFrom = null;
            currentTo   = null;
        }

        /* Period badge in donut header */
       /* var badgeMap = { 'today': 'Today', '7': 'Weekly', '30': 'Monthly', 'custom': 'Custom' };
	$('#periodBadge').text(badgeMap[currentPeriod] || currentPeriod);*/

        updateComparisonLabels(currentPeriod, currentFrom, currentTo);
        refreshAllWidgets();
    //});

    /* ── Window resize ── */
    $(window).on('resize', function () {
        if (donutChart) donutChart.resize();
    });

    /* ── Initial load ── */
    initCustomerMultiselect();
    refreshAllWidgets();
});

/* ════════════════════════════════════════════════
   COMPARISON LABEL HELPER
════════════════════════════════════════════════ */
function updateComparisonLabels(period, fromDate, toDate) {
    /* Used by the subtitle under the page title */
    if (period === 'today') {
        $('#dateRangeText').text(moment().format('DD MMM YYYY'));
    } else if (period === 'custom' && fromDate && toDate) {
        $('#dateRangeText').text(
            moment(fromDate).format('DD MMM YYYY') + ' - ' + moment(toDate).format('DD MMM YYYY')
        );
    } else {
        var days = (period == '30') ? 29 : 6;
        $('#dateRangeText').text(
            moment().subtract(days, 'days').format('DD MMM YYYY') +
            ' - ' + moment().format('DD MMM YYYY')
        );
    }
}

/* ════════════════════════════════════════════════
   REFRESH ALL (chart + table + KPIs)
════════════════════════════════════════════════ */
function refreshAllWidgets() {
    applyViewLabels();
    loadDonutChart(currentPeriod, currentFrom, currentTo);
    loadTableData(currentPeriod, currentFrom, currentTo);
}

/* ════════════════════════════════════════════════
   BUILD AJAX PARAMS (shared helper)
════════════════════════════════════════════════ */
function buildParams(period, fromDate, toDate) {
    var params = { period: period };
    if (period === 'custom' && fromDate && toDate) {
        params.from_date = fromDate;
        params.to_date   = toDate;
    }
    return params;
}

/* ════════════════════════════════════════════════
   APPLY VIEW LABELS TO DOM
════════════════════════════════════════════════ */
function applyViewLabels() {
    var cfg = VIEW_CONFIG[currentView];
    $('#donutTitle').text(cfg.chartTitle);
    $('#tableTitle').text(cfg.tableTitle);
    $('#colSecondHeader').text(cfg.colHeader);
    $('#dsTotalCountLabel').text(cfg.countLabel);
    $('#kpiHighRevLabel').text(cfg.kpiHighRevLabel);
    $('#kpiLowRevLabel').text(cfg.kpiLowRevLabel);
    $('#kpiHighGrowthLabel').text(cfg.kpiHighGrowthLabel);
    $('#kpiLowGrowthLabel').text(cfg.kpiLowGrowthLabel);
}

/* ════════════════════════════════════════════════
   VIEW SWITCH
════════════════════════════════════════════════ */
function switchView(view) {
    if (currentView === view) return;
    currentView = view;

    $('#btnPartner').toggleClass('active', view === 'partner');
    $('#btnDataPack').toggleClass('active', view === 'datapack');
    $('#donutChart').css('height', view === 'datapack' ? '700px' : '260px');

    refreshAllWidgets();
}

/* ════════════════════════════════════════════════
   KPI CARD STATES
════════════════════════════════════════════════ */
function resetKpiCards() {
    $('#kpiHighRev').text('-');
    $('#kpiHighRevSub').text('$0');
    $('#kpiHighGrowth').text('-');
    $('#kpiHighGrowthBadge').html('');
    $('#kpiLowRev').text('-');
    $('#kpiLowRevSub').text('$0');
    $('#kpiLowGrowth').text('-');
    $('#kpiLowGrowthBadge').html('');
}

function skeletonKpiCards() {
    var skelHtml = '<span class="skel" style="width:100px;height:22px;">&nbsp;</span>';
    $('#kpiHighRev, #kpiHighGrowth, #kpiLowRev, #kpiLowGrowth').html(skelHtml);
    $('#kpiHighRevSub, #kpiLowRevSub').html('&nbsp;');
    $('#kpiHighGrowthBadge, #kpiLowGrowthBadge').html('');
}

/* ════════════════════════════════════════════════
   DONUT CHART
════════════════════════════════════════════════ */
function loadDonutChart(period, fromDate, toDate) {
    if (donutChart) { donutChart.dispose(); donutChart = null; }

    document.getElementById('donutChart').innerHTML =
        '<div class="chart-loading"><i class="icon-spinner icon-spin"></i> Loading…</div>';
    document.getElementById('donutSummary').style.display = 'none';

    skeletonKpiCards();

    var cfg    = VIEW_CONFIG[currentView];
    var params = buildParams(period, fromDate, toDate);
    params.action      = cfg.action;
    params.customerIds = selectedCustomerIds;

    $.ajax({
        url    : "<?=$baseurl?>/<?=$appname?>/network/controller/smartap_controller.php",
        async  : false,
        data   : params,
        success: function (source) {
            var jsonData;
            try { jsonData = (typeof source === 'string') ? JSON.parse(source) : source; }
            catch(e) { jsonData = { status: '0' }; }

            if (jsonData.status !== '1' || !jsonData.data || !jsonData.data.length) {
                document.getElementById('donutChart').innerHTML =
                    '<div class="nodata-chart-wrap">' +
                    '<img class="nodata_image" src="<?="$baseurl/$appname/$assetsDir"?>/img/nodata.jpg" alt="No Data">' +
                    '<span>No data available</span>' +
                    '</div>';
                document.getElementById('donutSummary').style.display = 'none';
                resetKpiCards();
                return;
            }

            var totalRev = jsonData.data.reduce(function (s, d) {
                return s + (parseFloat(d.value) || 0);
            }, 0);
            $('#dsTotalRev').text(formatRevenue(totalRev));
            $('#dsTotalPacks').text(jsonData.data.length);
            $('#donutSummary').show();

            jsonData.data.forEach(function (d, i) {
                d.itemStyle = { color: 'hsl(' + ((i * 40) % 360) + ', 70%, 60%)' };
            });

            var chartDom = document.getElementById('donutChart');
            donutChart   = echarts.init(chartDom);

            donutChart.setOption({
                backgroundColor: 'transparent',
                tooltip: {
                    trigger    : 'item',
                    formatter  : function (p) {
                        return '<b>' + p.name + '</b><br/>' +
                               formatRevenue(p.value) + ' &nbsp; <b>' + p.percent.toFixed(1) + '%</b>';
                    },
                    borderRadius: 8,
                    padding: [8, 12]
                },
                legend: {
                    top: '4%', left: 'center',
                    itemWidth: 10, itemHeight: 10,
                    textStyle: { fontSize: 11, color: '#5a6a80' }
                },
                series: [{
                    name            : 'Revenue',
                    type            : 'pie',
                    radius          : ['42%', '68%'],
                    center          : ['50%', '62%'],
                    avoidLabelOverlap: false,
                    itemStyle       : { borderRadius: 8, borderColor: '#fff', borderWidth: 2 },
                    label           : { show: false, position: 'center' },
                    emphasis        : {
                        label: {
                            show: true, fontSize: 16, fontWeight: 'bold',
                            formatter: function (p) {
                                return '{a|' + p.name + '}\n{b|' + formatRevenue(p.value) + '}';
                            },
                            rich: {
                                a: { fontSize: 11, color: '#8a97a8', lineHeight: 20 },
                                b: { fontSize: 16, fontWeight: 'bold', color: '#1e2b3c' }
                            }
                        }
                    },
                    labelLine: { show: false },
                    data     : jsonData.data
                }]
            });
        },
        error: function () {
            document.getElementById('donutChart').innerHTML =
                '<div class="chart-loading" style="color:#ef4444;">' +
                '<i class="icon-warning-sign"></i> Failed to load chart.</div>';
            resetKpiCards();
        }
    });
}

/* ════════════════════════════════════════════════
   TABLE DATA
════════════════════════════════════════════════ */
function loadTableData(period, fromDate, toDate) {
    if ($.fn.DataTable.isDataTable('#siteStatusTable')) {
        $('#siteStatusTable').DataTable().destroy();
    }
    $('#siteTableBody').empty();

    $('#siteStatusTable').dataTable({
        scrollX       : true,
        scrollCollapse: true,
        sDom          : 'rBtip',
        buttons       : [{
            text  : '<i class="icon-download-alt"></i> Export CSV',
            action: function () { exportRevenueCsv(period, fromDate, toDate, currentView); }
        }],
        columnDefs: [
            { targets: [0, 3], orderable: false },
            { width: '5%',  targets: [0], className: 'text-center' },
            { width: '55%', targets: [1] },
            {
                width    : '20%',
                targets  : [2],
                className: 'text-right',
                render   : function (data, type) {
                    var val = parseFloat(String(data).replace(/[^0-9.-]+/g, '')) || 0;
                    return type === 'display' ? formatRevenue(val) : val;
                }
            },
            { width: '20%', targets: [3], className: 'text-right' }
        ],
        autoWidth     : false,
        aaSorting     : [[2, 'desc']],
        aLengthMenu   : [
            [<?= $recordsperpage[0] ?>, <?= $recordsperpage[1] ?>, <?= $recordsperpage[2] ?>, <?= $recordsperpage[3] ?>],
            [<?= $recordsperpage[0] ?>, <?= $recordsperpage[1] ?>, <?= $recordsperpage[2] ?>, <?= $recordsperpage[3] ?>]
        ],
        iDisplayLength: <?= $maxpagesize ?>,
        pagingType    : 'full_numbers',
        bProcessing   : true,
        bServerSide   : true,
        sAjaxSource   : '../reports/datatables-scripts/get_datapackwise_revenue.php',
        fnServerParams: function (aoData) {
            aoData.push({ name: 'action', value: 'getTableData' });
            aoData.push({ name: 'period', value: period });
            aoData.push({ name: 'view',   value: currentView });
            if (period === 'custom' && fromDate && toDate) {
                aoData.push({ name: 'from_date', value: fromDate });
                aoData.push({ name: 'to_date',   value: toDate });
            }
            selectedCustomerIds.forEach(function (id) {
                aoData.push({ name: 'customerIds[]', value: id });
            });
        },
        fnServerData: function (sSource, aoData, fnCallback) {
            $.ajax({
                url     : sSource,
                data    : aoData,
                success : fnCallback,
                type    : 'POST',
                dataType: 'json',
                error   : function (xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                }
            });
        },
        fnInitComplete: function (oSettings, json) {
            updateKpiFromData(json.aaData);
            if (json && (json.iTotalRecords === 0 || !json.aaData || !json.aaData.length)) {
                $('#siteTableBody').html(
                    '<tr class="nodata-table-row"><td colspan="4">' +
                    '<i class="icon-inbox nodata-icon"></i>No data found' +
                    '</td></tr>'
                );
            }
        },
        fnDrawCallback: function (oSettings) {
            var totalRecs = oSettings.fnRecordsTotal ? oSettings.fnRecordsTotal() : 0;
            if (totalRecs === 0) {
                $('#siteTableBody').html(
                    '<tr class="nodata-table-row"><td colspan="4">' +
                    '<i class="icon-inbox nodata-icon"></i>No data found' +
                    '</td></tr>'
                );
                return;
            }
            var start = oSettings._iDisplayStart;
            this.$('td:first-child', { filter: 'applied' }).each(function (i) {
                $(this).html(start + i + 1);
            });
            this.$('td:nth-child(3)', { filter: 'applied' }).addClass('rev-col');
        },
        language: {
            search           : '',
            searchPlaceholder: 'Search…',
            lengthMenu       : 'Show _MENU_ entries',
            info             : 'Showing _START_–_END_ of _TOTAL_ entries',
            infoEmpty        : 'No entries to show',
            zeroRecords      : '',
            paginate         : { previous: '&laquo;', next: '&raquo;' }
        }
    });
}

/* ════════════════════════════════════════════════
   KPI UPDATE FROM TABLE DATA
════════════════════════════════════════════════ */
function updateKpiFromData(data) {
    if (!data || !data.length) {
        resetKpiCards();
        return;
    }

    var parsed = data.map(function (row) {
        var name      = row[1];
        var revenue   = parseFloat(row[2]) || 0;
        var shareHtml = row[3] || '';
        var growth    = null;
        var type      = 'none';
        var match     = shareHtml.match(/(↑|↓)\s*([\d.]+)%/);

        if (match) {
            var val = parseFloat(match[2]) || 0;
            if (match[1] === '↑') { growth = val;  type = 'up'; }
            else                  { growth = -val; type = 'down'; }
        }
        return { name: name, revenue: revenue, growth: growth, type: type, rawGrowth: shareHtml };
    });

    var revSorted = parsed.slice().sort(function (a, b) { return b.revenue - a.revenue; });
    var highest   = revSorted[0];
    var lowest    = revSorted[revSorted.length - 1];

    $('#kpiHighRev').text(highest.name);
    $('#kpiHighRevSub').text(formatRevenue(highest.revenue));
    $('#kpiLowRev').text(lowest.name);
    $('#kpiLowRevSub').text(formatRevenue(lowest.revenue));

    var posGrowth = parsed.filter(function (x) { return x.type === 'up'; });
    var negGrowth = parsed.filter(function (x) { return x.type === 'down'; });

    if (posGrowth.length) {
        var highGrow = posGrowth.sort(function (a, b) { return b.growth - a.growth; })[0];
        $('#kpiHighGrowth').text(highGrow.name);
        $('#kpiHighGrowthBadge').html(highGrow.rawGrowth);
    } else {
        $('#kpiHighGrowth').text('-');
        $('#kpiHighGrowthBadge').html('');
    }

    if (negGrowth.length) {
        var lowGrow = negGrowth.sort(function (a, b) { return a.growth - b.growth; })[0];
        $('#kpiLowGrowth').text(lowGrow.name);
        $('#kpiLowGrowthBadge').html(lowGrow.rawGrowth);
    } else {
        $('#kpiLowGrowth').text('-');
        $('#kpiLowGrowthBadge').html('');
    }
}

/* ════════════════════════════════════════════════
   EXPORT CSV
════════════════════════════════════════════════ */
function exportRevenueCsv(period, fromDate, toDate, view) {
    var dt       = $('#siteStatusTable').DataTable();
    var settings = dt.settings()[0];
    var search   = settings.oPreviousSearch ? settings.oPreviousSearch.sSearch : '';
    var sortCol  = settings.aaSorting && settings.aaSorting[0] ? settings.aaSorting[0][0] : 2;
    var sortDir  = settings.aaSorting && settings.aaSorting[0] ? settings.aaSorting[0][1] : 'desc';

    var $form = $('<form>', {
        method: 'POST',
        action: '../reports/datatables-scripts/get_datapackwise_revenue.php',
        target: '_self'
    });

    $form.append($('<input>', { type: 'hidden', name: 'action',     value: 'exportCsvData' }));
    $form.append($('<input>', { type: 'hidden', name: 'period',     value: period }));
    $form.append($('<input>', { type: 'hidden', name: 'view',       value: view }));
    $form.append($('<input>', { type: 'hidden', name: 'sSearch',    value: search }));
    $form.append($('<input>', { type: 'hidden', name: 'iSortCol_0', value: sortCol }));
    $form.append($('<input>', { type: 'hidden', name: 'sSortDir_0', value: sortDir }));

    if (period === 'custom' && fromDate && toDate) {
        $form.append($('<input>', { type: 'hidden', name: 'from_date', value: fromDate }));
        $form.append($('<input>', { type: 'hidden', name: 'to_date',   value: toDate }));
    }

    selectedCustomerIds.forEach(function (id) {
        $form.append($('<input>', { type: 'hidden', name: 'customerIds[]', value: id }));
    });

    $('body').append($form);
    $form.submit();
    $form.remove();
}

/* ════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════ */
function formatRevenue(val) {
    val = parseFloat(val) || 0;
    if (val >= 1000000) return '$' + (val / 1000000).toFixed(2) + 'M';
    if (val >= 1000)    return '$' + (val / 1000).toFixed(1) + 'K';
    return '$' + val.toFixed(2);
}

function wowBadge(wow, invertPositive) {
    if (wow === null || wow === undefined || wow === '') return '';
    var isGood = invertPositive ? (wow <= 0) : (wow >= 0);
    var cls    = isGood ? 'up'  : 'down';
    var arrow  = isGood ? '▲'   : '▼';
    var sign   = wow > 0 ? '+'  : '';
    return '<span class="kpi-badge ' + cls + '">' + arrow + ' ' + sign + wow + '%</span>';
}

/* ════════════════════════════════════════════════
   CUSTOMER MULTISELECT
════════════════════════════════════════════════ */
function initCustomerMultiselect() {
    $.ajax({
        url : "<?=$baseurl?>/<?=$appname?>/network/controller/smartap_controller.php",
        data: { action: 'getCustomerList' },
        success: function (src) {
            var json;
            try { json = (typeof src === 'string') ? JSON.parse(src) : src; }
            catch(e) { json = { status: '0' }; }

            if (json.status === '1' && json.data && json.data.length) {
                allCustomers = json.data;
                renderCmsList(allCustomers);
            } else {
                $('#cmsList').html('<div class="cms-dd-empty">No customers found.</div>');
            }
        }
    });

    $('#cmsTrigger').on('click', function (e) {
        e.stopPropagation();
        $('#cmsDropdown').hasClass('open') ? closeCms() : openCms();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#cmsWrap').length) closeCms();
    });

    $('#cmsSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        renderCmsList(allCustomers.filter(function (c) {
            return c.name.toLowerCase().indexOf(q) !== -1;
        }));
    });

    $('#cmsSelectAll').on('click', function () {
        var q       = $('#cmsSearch').val().toLowerCase();
        var visible = q
            ? allCustomers.filter(function (c) { return c.name.toLowerCase().indexOf(q) !== -1; })
            : allCustomers;
        visible.forEach(function (c) {
            if (selectedCustomerIds.indexOf(c.id) === -1) selectedCustomerIds.push(c.id);
        });
        renderCmsList(visible);
        updateCmsTrigger();
    });

    $('#cmsClearAll').on('click', function () {
        selectedCustomerIds = [];
        var q = $('#cmsSearch').val().toLowerCase();
        renderCmsList(q
            ? allCustomers.filter(function (c) { return c.name.toLowerCase().indexOf(q) !== -1; })
            : allCustomers
        );
        updateCmsTrigger();
    });

    $('#cmsApply').on('click', function () {
        closeCms();
        refreshAllWidgets();
    });
}

function openCms() {
    $('#cmsDropdown').addClass('open');
    $('#cmsTrigger').addClass('active');
    $('#cmsArrow').addClass('open');
    $('#cmsSearch').val('');
    renderCmsList(allCustomers);
    setTimeout(function () { $('#cmsSearch').focus(); }, 50);
}

function closeCms() {
    $('#cmsDropdown').removeClass('open');
    $('#cmsTrigger').removeClass('active');
    $('#cmsArrow').removeClass('open');
}

function renderCmsList(customers) {
    if (!customers || !customers.length) {
        $('#cmsList').html('<div class="cms-dd-empty">No customers found.</div>');
        return;
    }
    var html = '';
    customers.forEach(function (c) {
        var sel = selectedCustomerIds.indexOf(c.id) !== -1;
        html += '<div class="cms-dd-item' + (sel ? ' sel' : '') + '" data-id="' + c.id + '">' +
                '<input type="checkbox"' + (sel ? ' checked' : '') + '>' +
                '<span>' + cmsEsc(c.name) + '</span>' +
                '</div>';
    });
    $('#cmsList').html(html);

    $('#cmsList .cms-dd-item').on('click', function () {
        var id  = parseInt($(this).data('id'), 10);
        var idx = selectedCustomerIds.indexOf(id);
        if (idx === -1) {
            selectedCustomerIds.push(id);
            $(this).addClass('sel').find('input').prop('checked', true);
        } else {
            selectedCustomerIds.splice(idx, 1);
            $(this).removeClass('sel').find('input').prop('checked', false);
        }
        updateCmsTrigger();
    });
}

function updateCmsTrigger() {
    var $row   = $('#cmsTagsRow');
    var $ph    = $('#cmsPlaceholder');
    var $badge = $('#cmsCountBadge');

    $row.find('.cms-tag').remove();

    if (selectedCustomerIds.length === 0) {
        $ph.show();
        $badge.hide().text('');
        return;
    }

    $ph.hide();
    $badge.text(selectedCustomerIds.length).show();

    selectedCustomerIds.slice(0, 2).forEach(function (id) {
        var cust = allCustomers.find(function (c) { return c.id === id; });
        if (!cust) return;
        var $tag = $(
            '<span class="cms-tag">' + cmsEsc(cust.name) +
            ' <span class="cms-tag-x" data-id="' + id + '">×</span></span>'
        );
        $row.append($tag);
    });

    if (selectedCustomerIds.length > 2) {
        $row.append('<span class="cms-tag">+' + (selectedCustomerIds.length - 2) + ' more</span>');
    }

    $row.find('.cms-tag-x').on('click', function (e) {
        e.stopPropagation();
        var id  = parseInt($(this).data('id'), 10);
        var idx = selectedCustomerIds.indexOf(id);
        if (idx !== -1) selectedCustomerIds.splice(idx, 1);
        updateCmsTrigger();
        if ($('#cmsDropdown').hasClass('open')) {
            var q = $('#cmsSearch').val().toLowerCase();
            renderCmsList(q
                ? allCustomers.filter(function (c) { return c.name.toLowerCase().indexOf(q) !== -1; })
                : allCustomers
            );
        }
    });
}

function cmsEsc(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

</body>
</html>
<?php
} else {
    header('Content-Type: text/html');
    $url = ($accessarray['aattr']) ? "/$appname/$accessdenied" : "/$appname/$featuredenied";
    header('Location: ' . $url);
}
?>
