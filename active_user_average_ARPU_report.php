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
    <title><?= $WIFILANTITLE ?> – Active Users & ARPU</title>
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
        /* ═══════════════════════════════════════════════════
           ARPU REPORT — Component Styles
           All classes prefixed with .arpu- to avoid conflicts
           ═══════════════════════════════════════════════════ */
/* ── Fix daterangepicker Sunday column being cut off ── */
.daterangepicker {
    min-width: 660px !important;
}
.daterangepicker .calendar-table {
    padding: 4px !important;
}
.daterangepicker td,
.daterangepicker th {
    min-width: 28px !important;
    width: 28px !important;
}
.daterangepicker .drp-calendar {
    max-width: 260px !important;
    padding: 8px !important;
}
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

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .arpu-kpi-row { gap: 10px; }
            .arpu-kpi-card { min-width: calc(50% - 5px); }
            .arpu-kpi-card .kpi-value { font-size: 22px; }
        }
        @media (max-width: 480px) {
            .arpu-kpi-card { min-width: 100%; }
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
            $_SESSION['submenulevel1'] = "ActiveUsersARPU";
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
                                <span>Active Users &amp; ARPU</span>
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
                            Total Active Users &amp; <span>Average ARPU</span> per User
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

                    <!-- KPI Cards -->
                    <div class="arpu-kpi-row">

                        <!-- Active Users (Current) -->
                        <div class="arpu-kpi-card kpi-users">
                            <div class="kpi-icon"><i class="icon-user"></i></div>
                            <div class="kpi-label">Active Users (Current)</div>
                            <div class="kpi-value loading arpu-skeleton" id="kpiActiveUsers" style="height:32px;width:120px;margin-bottom:8px;">&nbsp;</div>
                            <span class="kpi-badge flat" id="kpiUsersChg">–</span>
                            <div class="kpi-sub" id="kpiActiveUsersSub">vs previous period</div>
                        </div>

                        <!-- Active Users (Previous) -->
                        <div class="arpu-kpi-card kpi-prev">
                            <div class="kpi-icon"><i class="icon-user"></i></div>
                            <div class="kpi-label">Active Users (Previous)</div>
                            <div class="kpi-value loading arpu-skeleton" id="kpiPrevUsers" style="height:32px;width:100px;margin-bottom:8px;">&nbsp;</div>
                            <span class="kpi-badge flat" id="kpiPrevUsersBadge">Comparison period</span>
                            <div class="kpi-sub" id="kpiPrevUsersSub">&nbsp;</div>
                        </div>

                        <!-- Total Revenue -->
                        <div class="arpu-kpi-card kpi-revenue">
                            <div class="kpi-icon"><i class="icon-money"></i></div>
                            <div class="kpi-label">Total Revenue (Current)</div>
                            <div class="kpi-value loading arpu-skeleton" id="kpiRevenue" style="height:32px;width:130px;margin-bottom:8px;">&nbsp;</div>
                            <span class="kpi-badge flat" id="kpiRevenueChg">–</span>
                            <div class="kpi-sub">Datapack + Voucher</div>
                        </div>

                        <!-- ARPU -->
                        <div class="arpu-kpi-card kpi-arpu">
                            <div class="kpi-icon"><i class="icon-signal"></i></div>
                            <div class="kpi-label">Average ARPU / User</div>
                            <div class="kpi-value loading arpu-skeleton" id="kpiArpu" style="height:32px;width:90px;margin-bottom:8px;">&nbsp;</div>
                            <span class="kpi-badge flat" id="kpiArpuChg">–</span>
                            <div class="kpi-sub">Revenue ÷ Active Users</div>
                        </div>

                    </div><!-- /arpu-kpi-row -->

                    <!-- Trend Chart Card -->
                    <div class="arpu-chart-card">
                        <div class="arpu-chart-header">
                            <div class="ch-title">
                                <i class="icon-bar-chart" style="color:#e87722;margin-right:6px;"></i>
                                Active Users vs ARPU Trend
                            </div>
                            <div class="ch-legend">
                                <div class="leg-item">
                                    <div class="leg-dot" style="background:#3498db;"></div>
                                    <span id="legendCurrent">Active Users (Current)</span>
                                </div>
                                <div class="leg-item">
                                    <div class="leg-dot" style="background:#1a5276;"></div>
                                    <span id="legendPrevious">Active Users (Previous)</span>
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
       ARPU REPORT — Main JS
       ══════════════════════════════════════════════════════ */

    var currentPeriod  = <?= json_encode($period) ?>;
    var currentPartner = 'all';
    var currentRegion  = 'all';
    var arpuChart      = null;
    var customDateFrom = '';   // YYYY-MM-DD, set when custom range is chosen
    var customDateTo   = '';   // YYYY-MM-DD

    /* ── Helpers ── */
    function fmtNumber(n) {
        if (n === null || n === undefined || isNaN(n)) return '–';
        return parseFloat(n).toLocaleString('en-US');
    }

    /* Currency symbol map for common codes */
    var currencySymbols = {
        'USD': '$', 'INR': '₹', 'NGN': '₦', 'XOF': 'CFA', 'XAF': 'FCFA',
        'CDF': 'FC', 'TZS': 'TSh', 'MGA': 'Ar', 'EUR': '€', 'GBP': '£'
    };

    /* Active currency — updated whenever a report response arrives */
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
        if (n === 0)      return sym + ' 0.00';
        if (n < 0.001)    return sym + ' ' + parseFloat(n).toFixed(8);
        if (n < 1)        return sym + ' ' + parseFloat(n).toFixed(4);
        return sym + ' ' + parseFloat(n).toFixed(2);
	}

	 function fmtRev(v) {
        v = parseFloat(v) || 0;
        if (v >= 1000000) return '$' + (v / 1000000).toFixed(2) + 'M';
        if (v >= 1000)    return '$' + (v / 1000).toFixed(1) + 'K';
        return '$' + v.toLocaleString();
    }


    /* Build a percentage-change badge */
    function makeBadge(pct) {
        if (pct === null || pct === undefined || isNaN(pct)) {
            return { cls: 'flat', txt: '–' };
        }
        pct = parseFloat(pct);
        if (pct > 0)  return { cls: 'up',   txt: '▲ ' + pct.toFixed(1) + '%' };
        if (pct < 0)  return { cls: 'down', txt: '▼ ' + Math.abs(pct).toFixed(1) + '%' };
        return { cls: 'flat', txt: '0.0%' };
	}

    /* ── Set skeleton loading state on KPI cards ── */
    function setKpiLoading() {
        ['#kpiActiveUsers','#kpiPrevUsers','#kpiRevenue','#kpiArpu'].forEach(function(id) {
            $(id).addClass('loading arpu-skeleton').css({ height:'32px', width: id==='#kpiArpu'?'90px':'120px' }).html('&nbsp;');
        });
        ['#kpiUsersChg','#kpiRevenueChg','#kpiArpuChg'].forEach(function(id) {
            $(id).attr('class','kpi-badge flat').text('–');
        });
        /* Show spinner overlay */
        $('#arpuChartSpinner').show();
    }

    /* ── Populate KPI cards ── */
    function renderKpis(summary) {
        var usersChg   = makeBadge(summary.active_users_change_pct);
        var arpu_chg   = makeBadge(summary.average_arpu_change_pct);

        /* Active users current */
        $('#kpiActiveUsers')
            .removeClass('loading arpu-skeleton')
            .css({ height:'', width:'' })
            .text(fmtNumber(summary.active_users));
        $('#kpiUsersChg').attr('class','kpi-badge ' + usersChg.cls).text(usersChg.txt);

        /* Previous users */
        $('#kpiPrevUsers')
            .removeClass('loading arpu-skeleton')
            .css({ height:'', width:'' })
            .text(fmtNumber(summary.previous_active_users));
        $('#kpiPrevUsersBadge').attr('class','kpi-badge flat').text('Prev Period');

        /* Revenue */
        $('#kpiRevenue')
            .removeClass('loading arpu-skeleton')
            .css({ height:'', width:'' })
            .text(fmtRev(summary.total_revenue));

        /* ARPU */
        $('#kpiArpu')
            .removeClass('loading arpu-skeleton')
            .css({ height:'', width:'' })
            .text(fmtRev(summary.average_arpu));
        $('#kpiArpuChg').attr('class','kpi-badge ' + arpu_chg.cls).text(arpu_chg.txt);
    }

    /* ── Render ECharts chart — single xAxis for perfect bar+line alignment ── */
    function renderArpuChart(chartData, period) {
        var dom = document.getElementById('arpuTrendChart');
        if (!dom) return;

        if (arpuChart) { arpuChart.dispose(); }
        arpuChart = echarts.init(dom);

        /*
         * Mirror the ECharts "Dynamic Data" dual-axis pattern exactly:
         *   xAxis[0]  — date labels  (boundaryGap: true) → used by ARPU line
         *   xAxis[1]  — numeric idx  (boundaryGap: true) → used by bars (hidden)
         * Both arrays have the SAME length so bars and line points share the same
         * slot positions and therefore align perfectly.
         */
        var categories = chartData.map(function(r) { return r.label; });
        var curUsers   = chartData.map(function(r) { return r.active_users; });
        var prevUsers  = chartData.map(function(r) { return r.active_users_previous; });
        var arpuVals   = chartData.map(function(r) { return r.arpu_current; });

        /* Dynamic y-axis max with headroom */
        var maxUsers  = Math.max.apply(null, curUsers.concat(prevUsers).concat([1]));
        var maxArpu   = Math.max.apply(null, arpuVals.concat([1]));
        var yUsersMax = Math.ceil(maxUsers * 1.25);
        var yArpuMax  = parseFloat((maxArpu  * 1.35).toFixed(2));

        var option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    label: { backgroundColor: '#283b56' }
                },
                backgroundColor: 'rgba(255,255,255,0.97)',
                borderColor: '#e0e7ef',
                borderWidth: 1,
                padding: [10, 14],
                textStyle: { color: '#2c3e50', fontSize: 12 },
                formatter: function(params) {
                    /* Use xAxis[0] value (date label) as header */
                    /*  Always resolve date from dataIndex — axisValue is unreliable
                       because it returns a numeric string from xAxis[1] bars AND a date
                       string from xAxis[0] line, causing parseInt("15 Apr")=15 wrong index */
                    var idx       = params.length ? params[0].dataIndex : 0;
                    var dateLabel = (chartData[idx] && chartData[idx].label)
                                   ? chartData[idx].label
                                   : (params[0] ? params[0].axisValue : '');
                    var html = '<div style="font-weight:700;margin-bottom:6px;color:#2c3e50;">' + dateLabel + '</div>';
                    params.forEach(function(p) {
                        if (p.seriesName === 'ARPU (Current)') {
                            html += '<div style="margin-top:4px;padding-top:4px;border-top:1px solid #f0f3f8;">';
                            html += '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#7C3AED;margin-right:6px;vertical-align:middle;"></span>';
                            html += '<span style="color:#5a6a80;">ARPU (Current): </span><b>' + fmtRev(p.value) + '</b>';
                            html += '<div style="font-size:11px;color:#9aabb8;margin-top:2px;">Revenue ÷ Active Users</div>';
                            html += '</div>';
                        } else {
                            var dotColor = p.seriesName.indexOf('Previous') >= 0 ? '#1a5276' : '#3498db';
                            html += '<div style="margin-top:3px;">';
                            html += '<span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + dotColor + ';margin-right:6px;vertical-align:middle;"></span>';
                            html += '<span style="color:#5a6a80;">' + p.seriesName + ': </span><b>' + fmtNumber(p.value) + '</b> users';
                            html += '</div>';
                        }
                    });
                    return html;
                }
            },
            legend: { show: false },
            toolbox: {
                show: true,
                orient: 'horizontal',
                left: 'right',
                top: 'bottom',
                itemSize: 14,
                itemGap: 8,
                feature: {
                    dataView:    { readOnly: true, title: 'Data View' },
                    restore:     { title: 'Restore' },
                    saveAsImage: { title: 'Save Image' }
                }
            },
            dataZoom: { show: false, start: 0, end: 100 },
            grid: {
                left: 60, right: 100, top: 36, bottom: 50,
                containLabel: true
            },
            /* ✅ Single xAxis — bars and line all share the same date slots */
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: true,
                    data: categories,
                    axisLabel: {
                        interval: period == 7 ? 0 : 'auto',
                        rotate:   period == 7 ? 0 : 40,
                        fontSize: 11,
                        fontWeight: 600,
                        color: '#4a5a6a'
                    },
                    axisLine: { lineStyle: { color: '#e4e9f0' } },
                    axisTick: { show: false }
                }
            ],
            /* ── Two y-axes ── */
            yAxis: [
                /* yAxis[0] — Active Users (left) */
                {
                    type: 'value',
                    name: 'Active Users',
                    nameLocation: 'end',
                    nameGap: 10,
                    nameTextStyle: { color: '#4a5a6a', fontSize: 12, fontWeight: 600 },
                    min: 0,
                    max: yUsersMax,
                    minInterval: 1,
                    boundaryGap: [0, 0.2],
                    axisLabel: {
                        color: '#4a5a6a',
                        fontSize: 12,
                        fontWeight: 600,
                        formatter: function(v) { return v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v; }
                    },
                    axisLine: { show: true, lineStyle: { color: '#dde3ec' } },
                    splitLine: { lineStyle: { color: '#f0f3f8' } }
                },
                /* yAxis[1] — ARPU (right) — dark neutral labels */
                {
                    type: 'value',
                    name: 'ARPU (' + getCurrencySymbol(activeCurrency) + ')',
                    nameLocation: 'end',
                    nameGap: 8,
                    nameTextStyle: { color: '#4a5a6a', fontSize: 12, fontWeight: 600 },
                    min: 0,
                    max: yArpuMax,
                    boundaryGap: [0, 0.2],
                    axisLabel: {
                        color: '#4a5a6a',
                        fontSize: 12,
                        fontWeight: 600,
                        formatter: function(v) { return getCurrencySymbol(activeCurrency) + ' ' + v.toFixed(2); }
                    },
                    axisLine: { show: true, lineStyle: { color: '#dde3ec' } },
                    splitLine: { show: false }
                }
            ],
            series: [
                /* Current period bars — xAxis[0] / yAxis[0] */
                {
                    name: 'Current Period',
                    type: 'bar',
                    xAxisIndex: 0,
                    yAxisIndex: 0,
                    barMaxWidth: 22,
                    barGap: '10%',
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#5dade2' },
                            { offset: 1, color: '#2e86c1' }
                        ]),
                        borderRadius: [4, 4, 0, 0]
                    },
                    emphasis: { itemStyle: { color: '#1a78c2' } },
                    data: curUsers
                },
                /* Previous period bars — xAxis[0] / yAxis[0] */
                {
                    name: 'Previous Period',
                    type: 'bar',
                    xAxisIndex: 0,
                    yAxisIndex: 0,
                    barMaxWidth: 22,
                    itemStyle: {
                        color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                            { offset: 0, color: '#5d7a9e' },
                            { offset: 1, color: '#1a3a5c' }
                        ]),
                        borderRadius: [4, 4, 0, 0]
                    },
                    emphasis: { itemStyle: { color: '#1a3a5c' } },
                    data: prevUsers
                },
                /* ARPU line — xAxis[0] (dates) / yAxis[1] */
                {
                    name: 'ARPU (Current)',
                    type: 'line',
                    xAxisIndex: 0,
                    yAxisIndex: 1,
                    smooth: true,
                    symbol: 'circle',
                    symbolSize: 8,
                    lineStyle: { color: '#7C3AED', width: 2.5 },
                    itemStyle: {
                        color: '#fff',
                        borderColor: '#7C3AED',
                        borderWidth: 2.5
                    },
                    emphasis: {
                        itemStyle: {
                            color: '#7C3AED',
                            borderColor: '#fff',
                            borderWidth: 2,
                            shadowBlur: 8,
                            shadowColor: 'rgba(124,58,237,.35)'
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

        /* Force correct sizing (fixes init-while-hidden bug) then hide spinner */
        arpuChart.resize();
        $('#arpuChartSpinner').hide();
    }

    /* ── Main data loader ── */
    function loadArpuData(period) {
        currentPeriod = period;
        setKpiLoading();

        var ajaxData = {
            action    : 'getArpuReport',
            partnerid : currentPartner,
            region    : currentRegion
        };

        if (period === 'custom' && customDateFrom && customDateTo) {
            ajaxData.dateFrom = customDateFrom;
            ajaxData.dateTo   = customDateTo;
        } else {
            ajaxData.period = period;
        }

        $.ajax({
            url     : 'datatables-scripts/get_active_user_arpu_data.php',
            type    : 'GET',
            data    : ajaxData,
            dataType: 'json',
            success : function(resp) {
                if (!resp || resp.error) {
                    console.error('ARPU API error', resp);
                    return;
                }
                /* Update active currency from server response */
                if (resp.currency) {
                    activeCurrency = resp.currency;
                }
                renderKpis(resp.summary);
                renderArpuChart(resp.chart, period);
            },
            error: function(xhr, status, err) {
                console.error('ARPU AJAX error', status, err);
                $('#arpuChartSpinner').html(
                    '<div style="text-align:center;color:#c0392b;padding:40px;">' +
                    '<i class="icon-warning-sign" style="font-size:24px;"></i>' +
                    '<p style="margin-top:8px;">Failed to load data. Please try again.</p>' +
                    '</div>'
                );
            }
        });
    }

    /* ── Load region dropdown on page init ── */
    function loadFilterOptions() {
        $.ajax({
            url     : 'datatables-scripts/get_active_user_arpu_data.php',
            type    : 'GET',
            data    : { action: 'getFilterOptions', region: 'all' },
            dataType: 'json',
            success : function(resp) {
                if (!resp) return;

                /* Populate Regions */
                var $region = $('#arpuRegionFilter');
                $region.find('option:not(:first)').remove();
                if (resp.regions && resp.regions.length) {
                    $.each(resp.regions, function(i, r) {
                        $region.append(
                            $('<option>').val(r.Country).text(r.Country)
                        );
                    });
                }

                /* Populate all Partners (no region filter yet) */
                populatePartners(resp.partners);
            }
        });
    }

    /* ── Populate the partner dropdown from a given list ── */
    function populatePartners(partners) {
        var $partner = $('#arpuPartnerFilter');
        var prevVal  = $partner.val();
        $partner.find('option:not(:first)').remove();
        if (partners && partners.length) {
            $.each(partners, function(i, p) {
                $partner.append(
                    $('<option>').val(p.customerid).text(p.partner)
                );
            });
        }
        /* Restore prior selection if it still exists in the new list */
        if (prevVal && prevVal !== 'all') {
            $partner.val(prevVal);
            if ($partner.val() === null) $partner.val('all');
        }
    }

    /* ── Reload partner list filtered by selected region ── */
    function reloadPartnersForRegion(region) {
        $.ajax({
            url     : 'datatables-scripts/get_active_user_arpu_data.php',
            type    : 'GET',
            data    : { action: 'getFilterOptions', region: region },
            dataType: 'json',
            success : function(resp) {
                if (resp && resp.partners) {
                    populatePartners(resp.partners);
                }
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

        /* Init daterangepicker on the custom input */
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
            /* revert to last preset if user cancels */
            $select.val('7').trigger('change');
        });

        /* Dropdown change handler */
        $select.on('change', function() {
            var val = $(this).val();
            if (val === 'custom') {
                $wrap.css('display', 'flex');
                $input.click(); // open picker immediately
            } else {
                $wrap.css('display', 'none');
                customDateFrom = '';
                customDateTo   = '';
                if (history.replaceState) {
                    history.replaceState(null, '', '?period=' + val);
                }
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
        /* Reset partner dropdown then reload filtered partner list */
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
    loadArpuData(currentPeriod);

});
</script>

</body>
</html>
