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

require('../include/lang.php');
require('../include/utils.php');
require('../include/config.php');
require('../include/cache.php');

$lang   = ($_SESSION['language']) ? ($_SESSION['language']) : "en";
$module = "radius";

/* ── Labels only (no DB query here) ── */
$labelHome    = getLabel($lang, $module, "home");
$labelReports = getLabel($lang, $module, "reports");
?>
<!DOCTYPE html>
<!--[if IE 8]><html lang="en" class="ie8"><![endif]-->
<!--[if IE 9]><html lang="en" class="ie9"><![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title><?= $WIFILANTITLE ?> – Total Revenue Per Region</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <?php include('../include/global-styles.php'); ?>
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/css/pages/search.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/DT_bootstrap.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/jquery.dataTables.min.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/buttons.dataTables.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/responsive.dataTables.min.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/select2/select2.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="shortcut icon" href="<?=$baseurl?>/<?=$appname?>/img/favicon.ico" />

    <!-- ECharts -->
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
        /* ── Page header bar (title + toggle) ── */
        .rpr-page-header {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 11px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .rpr-page-header h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #333;
        }
        .rpr-page-header h3 i { color: #e87722; margin-right: 7px; }

        /* ── Period Filter Dropdown + Daterangepicker ── */
        .period-filter-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
		.period-select {
			height:38px;
            margin-top:8px;
            padding: 6px 28px 6px 10px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23999'/%3E%3C/svg%3E") no-repeat right 9px center;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            color: #444;
            cursor: pointer;
            transition: border-color 0.15s;
            min-width: 140px;
        }
        .period-select:focus { outline: none; border-color: #e87722; }

        .custom-date-wrap { display: flex; align-items: center; }

        .btn-daterange {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
            white-space: nowrap;
        }
        .btn-daterange:hover  { border-color: #e87722; background: #fff8f3; }
        .btn-daterange i.icon-calendar { color: #e87722; font-size: 14px; }

        /* ── Summary stat cards — icon badge style ── */
        .stat-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            padding: 18px 20px 16px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.09); }

        /* Circular icon badge */
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: 20px;
        }
        .stat-card.blue  .stat-icon { background: rgba(78,159,245,0.12); color: #4e9ff5; }
        .stat-card.green .stat-icon { background: rgba(46,204,113,0.12); color: #27ae60; }
        .stat-card.amber .stat-icon { background: rgba(243,156,18,0.12); color: #e87722; }

        /* Text block next to icon */
        .stat-body { flex: 1; min-width: 0; }
        .stat-card .sc-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .stat-card .sc-value {
            font-size: 26px;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.15;
        }
        .stat-card .sc-sub {
            font-size: 11px;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Two side-by-side content cards ── */
        .content-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .content-card-header {
            padding: 11px 16px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .content-card-header h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: #444;
        }
        .content-card-header h4 i { color: #e87722; margin-right: 6px; }
        .content-card-body { padding: 16px; }

        /* ── Chart ── */
        #revenueDonut {
            width: 100%;
            height: 320px;
            min-height: 320px;
            display: block;
        }

        /* ── Breakdown table ── */
        .breakdown-table { width: 100%; border-collapse: collapse; }
        .breakdown-table thead th {
            font-size: 11px;
            font-weight: 700;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0 0 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .breakdown-table thead th.right { text-align: right; }
        .breakdown-table tbody tr { border-bottom: 1px solid #f5f5f5; }
        .breakdown-table tbody tr:last-child { border-bottom: none; }
        .breakdown-table tbody td {
            padding: 9px 0;
            font-size: 13px;
            color: #333;
            vertical-align: middle;
        }
        .breakdown-table tbody td.right { text-align: right; }

        /* Region name cell */
        .td-region { display: flex; align-items: center; gap: 8px; }
        .region-dot {
            width: 11px; height: 11px;
            border-radius: 2px;
            flex-shrink: 0;
            display: inline-block;
        }
        .td-rev { font-weight: 700; color: #2c3e50; white-space: nowrap; }
        .td-share { white-space: nowrap; color: #888; font-size: 12px; }

        /* Status progress bar cell */
        .td-status { width: 42%; padding-right: 18px !important; }
        .bar-track {
            width: 100%;
            height: 10px;
            background: #e8eaed;
            border-radius: 99px;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.4s ease;
        }

        /* Total row */
        .breakdown-table tfoot td {
            font-size: 13px;
            font-weight: 700;
            color: #333;
            padding: 9px 0 0;
            border-top: 2px solid #eee;
        }
        .breakdown-table tfoot td.right { text-align: right; color: #2c3e50; }

        /* ── Pagination ── */
        .pg-bar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 3px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
            margin-top: 6px;
        }
        .pg-info { font-size: 11px; color: #bbb; margin-right: 4px; }
        .pg-btn {
            padding: 3px 8px;
            font-size: 12px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 3px;
            cursor: pointer;
            color: #555;
            line-height: 1.4;
        }
        .pg-btn:hover:not([disabled]) { background: #f5f5f5; }
        .pg-btn[disabled] { opacity: 0.35; cursor: default; }
        .pg-btn.active { background: #e87722; color: #fff; border-color: #e87722; }

        /* ── Download btn ── */
        .btn-dl {
            background: none; border: none; cursor: pointer;
            color: #aaa; font-size: 15px; padding: 2px 4px;
            border-radius: 3px; transition: color 0.15s;
        }
        .btn-dl:hover { color: #555; }

        /* ── Loading / Error ── */
        .loading-spinner {
            text-align: center; padding: 60px 0;
            color: #aaa; font-size: 13px;
        }
        .loading-spinner i {
            font-size: 28px; display: block;
            margin-bottom: 10px; color: #e87722;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .alert-error {
            background: #fdf2f2;
            border-left: 4px solid #e74c3c;
            padding: 12px 16px; font-size: 13px;
            color: #922b21; border-radius: 0 4px 4px 0;
        }

        /* ── Customer multi-select filter ── */
        .customer-filter-bar {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .customer-filter-bar .cust-label {
            font-size: 12px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        /* ── Customer Multiselect (CMS) ── */
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

        /* ── Map section ── */
        #noc-map-wrapper {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .map-header {
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            font-weight: 700;
            color: #444;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .map-header .fa { color: #e87722; font-size: 15px; }
        #activeFilterBadge {
            margin-left: 6px;
            display: inline-block;
            background: #e87722;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            border-radius: 10px;
            padding: 2px 9px;
            line-height: 1.5;
        }
        #activeFilterBadge:empty { display: none; }
        #dvMap {
            width: 100%;
            height: 480px;
        }
        .map-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 480px;
            color: #aaa;
            font-size: 13px;
            flex-direction: column;
            gap: 10px;
        }
        .map-loading i { font-size: 28px; color: #e87722; animation: spin 0.9s linear infinite; }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "report";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "Revenueperregion";
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
                                <a href="#">Revenue</a>
                                <i class="icon-angle-right"></i>
                                <span>Total Revenue Per Region</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Page Header bar — title left, toggle right -->
                <div class="rpr-page-header">
                    <h3>
                        <i class="icon-signal"></i>Total Revenue Per Region
                    </h3>
					<div class="period-filter-wrap">
                        <div class="custom-date-wrap">
                            <button type="button" id="reportrange" class="btn-daterange">
                                <i class="icon-calendar"></i>
                                <span>Select date range</span>
                                <i class="icon-angle-down" style="margin-left:4px;font-size:11px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Customer multi-select filter bar -->
                

                <!-- Summary Stat Cards — icon badge style -->
                <div class="row-fluid" style="margin-bottom:14px;">
                    <div class="span4">
                        <div class="stat-card blue">
                            <div class="stat-icon"><i class="icon-money"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Total Revenue</div>
                                <div class="sc-value" id="card-total-revenue">–</div>
                                <div class="sc-sub"   id="card-date-range">Loading…</div>
                            </div>
                        </div>
                    </div>
                    <div class="span4">
                        <div class="stat-card green">
                            <div class="stat-icon"><i class="icon-globe"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Regions Tracked</div>
                                <div class="sc-value" id="card-region-count">–</div>
                                <div class="sc-sub">Active operational regions</div>
                            </div>
                        </div>
                    </div>
                    <div class="span4">
                        <div class="stat-card amber">
                            <div class="stat-icon"><i class="icon-trophy"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Top Region</div>
                                <div class="sc-value" id="card-top-region">–</div>
                                <div class="sc-sub"   id="card-top-pct"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading state (full width, shown before data arrives) -->
                <div id="loadingState" class="content-card">
                    <div class="content-card-body loading-spinner">
                        <i class="icon-refresh"></i>Fetching revenue data…
                    </div>
                </div>

                <!-- Error state -->
                <div id="errorState" class="alert-error" style="display:none;"></div>

                <!-- Two side-by-side cards: Chart | Breakdown table -->
                <div id="dataContent" style="display:none;">
                    <div class="row-fluid">

                        <!-- LEFT: Donut chart card -->
                        <div class="span5">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4>REVENUE SHARE BY REGION</h4>
                                </div>
                                <div class="content-card-body" style="padding:12px;">
                                    <div id="revenueDonut"></div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: Breakdown table card -->
                        <div class="span7">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><i class="icon-bar-chart"></i>Data Pack Breakdown</h4>
                                </div>
                                <div class="content-card-body">
                                    <table class="breakdown-table">
                                        <thead>
                                            <tr>
                                                <th style="width:22%;">Region</th>
                                                <th style="width:42%;">Status percentage</th>
                                                <th class="right" style="width:20%;">Revenue</th>
                                                <th class="right" style="width:16%;">Share</th>
                                            </tr>
                                        </thead>
                                        <tbody id="regionTbody"></tbody>
                                        <tfoot>
                                            <tr>
                                                <td><strong>TOTAL</strong></td>
                                                <td></td>
                                                <td class="right" id="listTotalRevenue"></td>
                                                <td class="right">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <!-- Pagination (only when > 5 rows) -->
                                    <div class="pg-bar" id="regionPagination" style="display:none;"></div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /row-fluid -->
                </div><!-- /dataContent -->
<div class="customer-filter-bar">
                    <span class="cust-label"><i class="icon-filter" style="color:#e87722;margin-right:4px;"></i>Filter by Customer:</span>
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
                </div>
                <!-- ────────── SITE MAP ────────── -->
                <div id="noc-map-wrapper">
                    <div class="map-header">
                        <i class="fa fa-globe"></i>
                        Site Map – Revenue Locations
                        <span id="activeFilterBadge"></span>
                    </div>
                    <div id="dvMap">
                        <div class="map-loading" id="mapLoading">
                            <i class="icon-refresh"></i>
                            Loading map data…
                        </div>
                    </div>
                </div>
                <!-- END MAP -->

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
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
jQuery(document).ready(function () {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ────────────────────────────────────────────
       Config
    ──────────────────────────────────────────── */
    var API_URL       = '<?= $baseurl ?>/<?= $appname ?>/reports/datatables-scripts/get_revenue_per_region.php';
    var MAP_API_URL   = '<?= $baseurl ?>/<?= $appname ?>/reports/datatables-scripts/get_sites_map_data.php';
    var CUST_API_URL  = '<?= $baseurl ?>/<?= $appname ?>/reports/datatables-scripts/get_customers_list.php';
    var PALETTE       = ['#4e9ff5','#2ecc71','#9b59b6','#f39c12','#e74c3c','#1abc9c','#e67e22','#3498db'];
    var currentPeriod = 'custom';
    var currentFrom   = moment().subtract(6, 'days').format('YYYY-MM-DD');
    var currentTo     = moment().format('YYYY-MM-DD');
    var selectedCustomers = [];  /* array of customer IDs for the map filter */
    var leafletMap    = null;
    var markerCluster = null;

    /* ────────────────────────────────────────────
       Customer Multiselect (CMS)
    ──────────────────────────────────────────── */
    var allCustomers        = [];
    var selectedCustomerIds = [];  /* mirrors selectedCustomers for map filter */

    function cmsEsc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function updateCmsTrigger() {
        var n = selectedCustomerIds.length;
        if (n === 0) {
            $('#cmsTagsRow').html('<span class="cms-placeholder" id="cmsPlaceholder">All Customers</span>');
            $('#cmsCountBadge').hide();
        } else if (n <= 3) {
            var tags = '';
            selectedCustomerIds.forEach(function(id) {
                var c = allCustomers.find(function(x) { return x.id == id; });
                if (c) {
                    tags += '<span class="cms-tag">' + cmsEsc(c.name)
                          + '<span class="cms-tag-x" data-id="' + c.id + '">×</span></span>';
                }
            });
            $('#cmsTagsRow').html(tags);
            $('#cmsCountBadge').hide();
        } else {
            $('#cmsTagsRow').html('<span class="cms-placeholder">' + n + ' customers selected</span>');
            $('#cmsCountBadge').text(n).show();
        }
    }

    function renderCmsList(customers) {
        if (!customers || !customers.length) {
            $('#cmsList').html('<div class="cms-dd-empty">No customers found.</div>');
            return;
        }
        var html = '';
        customers.forEach(function(c) {
            var sel = selectedCustomerIds.indexOf(c.id) !== -1;
            html += '<div class="cms-dd-item' + (sel ? ' sel' : '') + '" data-id="' + c.id + '">'
                  + '<input type="checkbox"' + (sel ? ' checked' : '') + '>'
                  + '<span>' + cmsEsc(c.name) + '</span>'
                  + '</div>';
        });
        $('#cmsList').html(html);
    }

    function openCms() {
        $('#cmsDropdown').addClass('open');
        $('#cmsTrigger').addClass('active');
        $('#cmsArrow').addClass('open');
        $('#cmsSearch').val('');
        renderCmsList(allCustomers);
        setTimeout(function() { $('#cmsSearch').focus(); }, 50);
    }

    function closeCms() {
        $('#cmsDropdown').removeClass('open');
        $('#cmsTrigger').removeClass('active');
        $('#cmsArrow').removeClass('open');
    }

    function initCms() {
        $('#cmsTrigger').on('click', function(e) {
            e.stopPropagation();
            $('#cmsDropdown').hasClass('open') ? closeCms() : openCms();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#cmsWrap').length) closeCms();
        });

        $('#cmsSearch').on('input', function() {
            var q = $(this).val().toLowerCase();
            renderCmsList(allCustomers.filter(function(c) {
                return c.name.toLowerCase().indexOf(q) !== -1;
            }));
        });

        $('#cmsSelectAll').on('click', function() {
            var q       = $('#cmsSearch').val().toLowerCase();
            var visible = q
                ? allCustomers.filter(function(c) { return c.name.toLowerCase().indexOf(q) !== -1; })
                : allCustomers;
            visible.forEach(function(c) {
                if (selectedCustomerIds.indexOf(c.id) === -1) selectedCustomerIds.push(c.id);
            });
            renderCmsList(visible);
            updateCmsTrigger();
        });

        $('#cmsClearAll').on('click', function() {
            selectedCustomerIds = [];
            var q = $('#cmsSearch').val().toLowerCase();
            renderCmsList(q
                ? allCustomers.filter(function(c) { return c.name.toLowerCase().indexOf(q) !== -1; })
                : allCustomers
            );
            updateCmsTrigger();
        });

		$('#cmsTagsRow').on('click', '.cms-tag-x', function() {
    var id = String($(this).data('id'));

    // 1. Remove from selectedCustomerIds
    selectedCustomerIds = selectedCustomerIds.filter(function(cid) {
        return String(cid) !== id;
    });

    // 2. Re-render list
    var q = $('#cmsSearch').val().toLowerCase();
    renderCmsList(
        q
        ? allCustomers.filter(function(c) { return c.name.toLowerCase().indexOf(q) !== -1; })
        : allCustomers
    );

    // 3. Update trigger UI
    updateCmsTrigger();

    // 4. Auto-apply filter immediately ✅
    $('#cmsApply').trigger('click');
		});

        $('#cmsApply').on('click', function() {
            closeCms();
            selectedCustomers = selectedCustomerIds.slice();
            updateFilterBadge();
            loadMapData();
        });

        $(document).on('click', '.cms-tag-x', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            selectedCustomerIds = selectedCustomerIds.filter(function(x) { return x != id; });
            updateCmsTrigger();
        });

        $(document).on('click', '.cms-dd-item', function() {
            var id = $(this).data('id');
            var idx = selectedCustomerIds.indexOf(id);
            if (idx === -1) {
                selectedCustomerIds.push(id);
                $(this).addClass('sel').find('input[type=checkbox]').prop('checked', true);
            } else {
                selectedCustomerIds.splice(idx, 1);
                $(this).removeClass('sel').find('input[type=checkbox]').prop('checked', false);
            }
            updateCmsTrigger();
        });
    }

    /* Load customer options from API */
    $.ajax({
        url:      CUST_API_URL,
        method:   'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.success && resp.customers) {
                allCustomers = resp.customers.map(function(c) {
                    return { id: c.id, name: c.name };
                });
                renderCmsList(allCustomers);
            }
        }
    });

    function updateFilterBadge() {
        var n = selectedCustomers.length;
        $('#activeFilterBadge').text(n > 0 ? n + ' customer' + (n > 1 ? 's' : '') + ' selected' : '');
    }

    initCms();

    /* ────────────────────────────────────────────
       Daterangepicker — initialise once, reuse
    ──────────────────────────────────────────── */
    /* ────────────────────────────────────────────
       Daterangepicker — always visible, always custom
    ──────────────────────────────────────────── */
    $('#reportrange').daterangepicker({
        startDate : moment().subtract(6, 'days'),
        endDate   : moment(),
        maxDate   : moment(),
        maxSpan   : { days: 90 },
        showDropdowns : true,
        linkedCalendars: false,
        locale: {
            format      : 'DD MMM YYYY',
            separator   : ' – ',
            applyLabel  : 'Apply',
            cancelLabel : 'Cancel',
            firstDay    : 1
        },
        ranges: {
            //'Today'       : [moment(), moment()],
            'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            //'This Month'  : [moment().startOf('month'), moment().endOf('month')],
            //'Last Month'  : [moment().subtract(1, 'month').startOf('month'),
            //                 moment().subtract(1, 'month').endOf('month')]
        }
    }, function (start, end) {
        currentFrom = start.format('YYYY-MM-DD');
        currentTo   = end.format('YYYY-MM-DD');
        $('#reportrange span').html(start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY'));
        loadData('custom', currentFrom, currentTo);
        loadMapData();
    });

    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ────────────────────────────────────────────
       Helpers
    ──────────────────────────────────────────── */
    function fmtRev(v) {
        v = parseFloat(v) || 0;
        if (v >= 1000000) return '$' + (v / 1000000).toFixed(2) + 'M';
        if (v >= 1000)    return '$' + (v / 1000).toFixed(1) + 'K';
        return '$' + v.toLocaleString();
    }

    function fmtDate(str) {
        var d = new Date(str);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    /* ────────────────────────────────────────────
       UI state helpers
    ──────────────────────────────────────────── */
    function showLoading() {
        $('#loadingState').show();
        $('#errorState').hide();
        $('#dataContent').hide();
    }
    function showError(msg) {
        $('#loadingState').hide();
        $('#errorState').text('Error: ' + msg).show();
        $('#dataContent').hide();
    }
    function showData() {
        $('#loadingState').hide();
        $('#errorState').hide();
        $('#dataContent').show();
    }

    /* ────────────────────────────────────────────
       Render summary cards
    ──────────────────────────────────────────── */
    function renderCards(resp) {
        var dateLabel = fmtDate(resp.from_date) + ' – ' + fmtDate(resp.to_date);
        $('#card-total-revenue').text(fmtRev(resp.total_revenue));
        $('#card-date-range').text(dateLabel);
        $('#card-region-count').text(resp.region_count);

        if (resp.regions && resp.regions.length > 0) {
            var top = resp.regions[0];
            $('#card-top-region').text(top.region);
            $('#card-top-pct').text(top.percentage + '% of total revenue');
        } else {
            $('#card-top-region').text('–');
            $('#card-top-pct').text('No data');
        }
    }

    /* ────────────────────────────────────────────
       renderEchartPieSiteStatus  (shared render fn)
    ──────────────────────────────────────────── */
    function renderEchartPieSiteStatus(graphId, data, assets) {
        assets = assets || {};
        var nodataImg = assets.nodataImg || '<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg';

        var selector  = (graphId.charAt(0) === '#' || graphId.charAt(0) === '.')
                        ? graphId : '#' + graphId;
        var container = document.querySelector(selector);
        if (!container) {
            console.error('renderEchartPieSiteStatus: container not found for "' + selector + '"');
            return;
        }

        if (!data || !Array.isArray(data.dataChart) || data.dataChart.length === 0) {
            $(selector).html('<img class="nodata_image" src="' + nodataImg + '" alt="No Data">');
            return;
        }

        var hasData = data.dataChart.some(function(item) { return item.value > 0; });
        if (!hasData) {
            $(selector).html('<img class="nodata_image" src="' + nodataImg + '" alt="No Data">');
            return;
        }

        var innerRatio  = parseFloat(data.ratioDonutChart) || 0.52;
        var innerRadius = Math.round(innerRatio * 100) + '%';
        var outerRadius = '78%';
        var activePct   = data.activePct || 0;

        var seriesData = data.dataChart.map(function(item, idx) {
            var fallbacks = ['#5470c6','#91cc75','#fac858','#ee6666','#73c0de'];
            return {
                name:      item.label,
                value:     item.value,
                itemStyle: { color: item.color || fallbacks[idx % fallbacks.length] }
            };
        });

        var existingChart = echarts.getInstanceByDom(container);
        if (existingChart) existingChart.dispose();
        var myChart = echarts.init(container);

        var option = {
            backgroundColor: 'transparent',
            legend: { show: false },
            tooltip: {
                trigger: 'item',
                backgroundColor: 'rgba(255,255,255,0.95)',
                borderColor: '#e4e9f0',
                borderWidth: 1,
                textStyle: { color: '#2c3e50', fontSize: 13 },
                extraCssText: 'box-shadow:0 3px 12px rgba(0,0,0,.12);border-radius:8px;',
                formatter: function(params) {
                    return '<div style="font-weight:bold;margin-bottom:4px;color:#333;">'
                         + params.name + '</div>'
                         + '<div style="margin:3px 0;">'
                         + params.marker
                         + ' <span style="margin-left:4px;">Revenue: <b>' + fmtRev(params.value) + '</b></span>'
                         + ' <span style="color:#999;margin-left:6px;">(' + params.percent + '%)</span>'
                         + '</div>';
                }
            },
            toolbox: {
                show: true,
                top: 4,
                right: 6,
                feature: {
                    saveAsImage: {
                        show: true,
                        pixelRatio: 2,
                        backgroundColor: '#ffffff',
                        name: 'revenue_per_region'
                    }
                }
            },
            series: [{
                name:              data.TitleDonut || 'Revenue',
                type:              'pie',
                radius:            [innerRadius, outerRadius],
                center:            ['50%', '50%'],
                avoidLabelOverlap: false,
                itemStyle:         { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },
                label: {
                    show: true,
                    position: 'center',
                    formatter: function() {
                        return '{pct|' + activePct + '}\n{sub|Total Revenue}';
                    },
                    rich: {
                        pct: { fontSize: 22, fontWeight: '700', color: '#1e2b3c', lineHeight: 30 },
                        sub: { fontSize: 11, color: '#8a97a8', lineHeight: 20 }
                    }
                },
                emphasis: { label: { show: true } },
                labelLine: { show: false },
                data: seriesData,
                animationType:    'expansion',
                animationEasing:  'cubicOut',
                animationDuration: 900
            }]
        };

        myChart.setOption(option);
        setTimeout(function() { myChart.resize(); }, 50);

        $(window).off('resize.pieSiteStatus_' + selector)
                 .on( 'resize.pieSiteStatus_' + selector, function() {
                     myChart.resize();
                 });
    }

    /* ────────────────────────────────────────────
       renderChart — adapter
    ──────────────────────────────────────────── */
    function renderChart(regions, totalRevenue) {
        var dataChart = regions.map(function(r, i) {
            return {
                label: r.region,
                value: r.total_revenue,
                color: PALETTE[i % PALETTE.length]
            };
        });

        var data = {
            TitleDonut:     'Revenue',
            ratioDonutChart: 0.52,
            activePct:      fmtRev(totalRevenue),
            dataChart:      dataChart
        };

        renderEchartPieSiteStatus('revenueDonut', data);
    }

    /* ────────────────────────────────────────────
       Render region breakdown table (with pagination)
    ──────────────────────────────────────────── */
    var PAGE_SIZE   = 5;
    var currentPage = 1;
    var allRegions  = [];

    function renderPage(page) {
        var start = (page - 1) * PAGE_SIZE;
        var slice = allRegions.slice(start, start + PAGE_SIZE);
        var html  = '';
        slice.forEach(function (r, idx) {
            var i     = start + idx;
            var color = PALETTE[i % PALETTE.length];
            var pct = parseFloat(r.percentage) || 0;
            html += '<tr>'
                  +   '<td><span class="td-region"><span class="region-dot" style="background:' + color + ';"></span>' + r.region + '</span></td>'
                  +   '<td class="td-status"><div class="bar-track"><div class="bar-fill" style="width:' + pct + '%;background:' + color + ';"></div></div></td>'
                  +   '<td class="right td-rev">' + fmtRev(r.total_revenue) + '</td>'
                  +   '<td class="right td-share">' + r.percentage + '%</td>'
                  + '</tr>';
        });
        $('#regionTbody').html(html);
    }

    function renderPagination(total) {
        var pages = Math.ceil(total / PAGE_SIZE);
        if (pages <= 1) { $('#regionPagination').hide().html(''); return; }
        var html = '<span class="pg-info">Page ' + currentPage + ' of ' + pages + '</span>';
        html += '<button class="pg-btn" id="pgPrev"' + (currentPage === 1 ? ' disabled' : '') + '>&#8249;</button>';
        for (var p = 1; p <= pages; p++) {
            html += '<button class="pg-btn' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
        }
        html += '<button class="pg-btn" id="pgNext"' + (currentPage === pages ? ' disabled' : '') + '>&#8250;</button>';
        $('#regionPagination').html(html).show();

        $('#regionPagination').off('click').on('click', '.pg-btn', function () {
            var $b = $(this);
            if ($b.attr('disabled') !== undefined) return;
            if ($b.attr('id') === 'pgPrev')      { currentPage--; }
            else if ($b.attr('id') === 'pgNext') { currentPage++; }
            else { currentPage = parseInt($b.data('page'), 10); }
            currentPage = Math.max(1, Math.min(pages, currentPage));
            renderPage(currentPage);
            renderPagination(total);
        });
    }

    function renderList(regions, totalRevenue) {
        allRegions  = regions;
        currentPage = 1;
        renderPage(currentPage);
        renderPagination(regions.length);
        $('#listTotalRevenue').text(fmtRev(totalRevenue));
    }

    /* ────────────────────────────────────────────
       Fetch revenue data from API
    ──────────────────────────────────────────── */
    function loadData(period, fromDate, toDate) {
        showLoading();

        var params = { period: period };
        if (period === 'custom' && fromDate && toDate) {
            params.from_date = fromDate;
            params.to_date   = toDate;
        }

        $.ajax({
            url:      API_URL,
            method:   'GET',
            data:     params,
            dataType: 'json',
            success: function (resp) {
                if (!resp.success) {
                    showError(resp.error || 'Unknown API error');
                    return;
                }
                renderCards(resp);
                renderList(resp.regions, resp.total_revenue);
                showData();
                renderChart(resp.regions, resp.total_revenue);
            },
            error: function (xhr) {
                var msg = 'Request failed (HTTP ' + xhr.status + ')';
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json.error) msg = json.error;
                } catch (e) {}
                showError(msg);
            }
        });
    }

    /* ────────────────────────────────────────────
       Leaflet Map — initialise once, reload markers
    ──────────────────────────────────────────── */
    function initMap() {
        if (leafletMap) return; /* already initialised */

        /* Hide the loading placeholder, init the real map */
        $('#mapLoading').remove();

        leafletMap = L.map('dvMap', {
            center:    [0, 20],  /* Africa-centred default */
            zoom:      4,
            scrollWheelZoom: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 18
        }).addTo(leafletMap);

        markerCluster = L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius:    50
        });
        leafletMap.addLayer(markerCluster);
    }

    /* Custom orange marker icon */
    var orangeIcon = L.divIcon({
        className: '',
        html: '<div style="width:12px;height:12px;border-radius:50%;background:#e87722;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);"></div>',
        iconSize:   [12, 12],
        iconAnchor: [6, 6],
        popupAnchor:[0, -8]
    });

    function loadMapData() {
        initMap();
        markerCluster.clearLayers();

        var params = { period: currentPeriod };
        if (currentPeriod === 'custom' && currentFrom && currentTo) {
            params.from_date = currentFrom;
            params.to_date   = currentTo;
        }
        if (selectedCustomers.length > 0) {
            params.customer_ids = selectedCustomers.join(',');
        }

        $.ajax({
            url:      MAP_API_URL,
            method:   'GET',
            data:     params,
            dataType: 'json',
            success: function (resp) {
                if (!resp.success || !resp.sites || resp.sites.length === 0) return;

                var bounds = [];
                resp.sites.forEach(function (site) {
                    var lat = parseFloat(site.latitude);
                    var lng = parseFloat(site.longitude);
                    if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

                    var marker = L.marker([lat, lng], { icon: orangeIcon });

                    var revenueHtml = (site.total_revenue !== undefined && site.total_revenue !== null)
                        ? '<div><b>Revenue:</b> <span style="color:#e87722;font-weight:700;">' + fmtRev(site.total_revenue) + '</span></div>'
                        : '';

                    var popupHtml =
                        '<div style="min-width:190px;font-size:12px;line-height:1.7;">' +
                        '<div style="font-weight:700;font-size:13px;color:#2c3e50;margin-bottom:5px;border-bottom:1px solid #eee;padding-bottom:4px;">' + (site.location_name || 'Site') + '</div>' +
                        (site.customer_name ? '<div><b>Customer:</b> ' + site.customer_name + '</div>' : '') +
                        //(site.city    ? '<div><b>City:</b> '    + site.city    + '</div>' : '') +
                        //(site.country ? '<div><b>Country:</b> ' + site.country + '</div>' : '') +
                        revenueHtml +
                        '</div>';

                    marker.bindPopup(popupHtml);
                    markerCluster.addLayer(marker);
                    bounds.push([lat, lng]);
                });

                if (bounds.length > 0) {
                    leafletMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
                }
            }
        });
    }

    /* ────────────────────────────────────────────
       Boot
    ──────────────────────────────────────────── */
    loadData('custom', currentFrom, currentTo);
    loadMapData();
});
</script>

</body>
</html>
