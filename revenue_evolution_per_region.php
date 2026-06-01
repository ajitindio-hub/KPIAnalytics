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

$labelHome    = getLabel($lang, $module, "home");
$labelReports = getLabel($lang, $module, "reports");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?= $WIFILANTITLE ?> – Revenue Evolution Per Region</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <?php include('../include/global-styles.php'); ?>
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/css/pages/search.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/DT_bootstrap.css" rel="stylesheet" />
    <link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/css/jquery.dataTables.min.css" rel="stylesheet" />
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

        /* ── Page header ── */
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
        .rpr-page-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #333; }
        .rpr-page-header h3 i { color: #e87722; margin-right: 7px; }

        /* ── Period Filter Dropdown + Daterangepicker ── */
        .period-filter-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
		.period-select {
            height: 38px;
            margin-top: 8px;
            padding: 6px 28px 6px 10px; font-size: 13px; font-weight: 500;
            border: 1px solid #ddd; border-radius: 4px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23999'/%3E%3C/svg%3E") no-repeat right 9px center;
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
            color: #444; cursor: pointer; transition: border-color 0.15s; min-width: 140px;
        }
        .period-select:focus { outline: none; border-color: #e87722; }
        .custom-date-wrap { display: flex; align-items: center; }
        .btn-daterange {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 6px 14px; font-size: 13px; font-weight: 500;
            color: #444; background: #fff; border: 1px solid #ddd;
            border-radius: 4px; cursor: pointer;
            transition: border-color 0.15s, background 0.15s; white-space: nowrap;
        }
        .btn-daterange:hover { border-color: #e87722; background: #fff8f3; }
        .btn-daterange i.icon-calendar { color: #e87722; font-size: 14px; }

        /* ── Stat Cards ── */
        .stat-card {
            background: #fff; border: 1px solid #e8e8e8; border-radius: 6px;
            padding: 18px 20px 16px; margin-bottom: 14px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s;
        }
        .stat-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.09); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 20px;
        }
        .stat-card.blue   .stat-icon { background: rgba(78,159,245,0.12);  color: #4e9ff5; }
        .stat-card.green  .stat-icon { background: rgba(46,204,113,0.12);  color: #27ae60; }
        .stat-card.amber  .stat-icon { background: rgba(232,119,34,0.12);  color: #e87722; }
        .stat-card.purple .stat-icon { background: rgba(155,89,182,0.12);  color: #9b59b6; }

        .stat-body { flex: 1; min-width: 0; }
        .sc-label {
            font-size: 11px; font-weight: 700; letter-spacing: 0.08em;
            color: #aaa; text-transform: uppercase; margin-bottom: 4px;
        }
        .sc-value { font-size: 24px; font-weight: 700; color: #2c3e50; line-height: 1.15; }
        .sc-sub   { font-size: 11px; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Evolution badge in stat card */
        .evo-badge {
            display: inline-flex; align-items: center; gap: 3px;
            font-size: 12px; font-weight: 700;
            padding: 2px 8px; border-radius: 99px; white-space: nowrap;
        }
        .evo-badge.up   { background: rgba(46,204,113,0.15); color: #27ae60; }
        .evo-badge.down { background: rgba(231,76,60,0.12);  color: #e74c3c; }
        .evo-badge.flat { background: rgba(149,165,166,0.15);color: #7f8c8d; }

        /* ── Content cards ── */
        .content-card {
            background: #fff; border: 1px solid #e5e5e5; border-radius: 4px;
            overflow: hidden; margin-bottom: 14px;
        }
        .content-card-header {
            padding: 11px 16px; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .content-card-header h4 { margin: 0; font-size: 13px; font-weight: 700; color: #444; }
        .content-card-header h4 i { color: #e87722; margin-right: 6px; }
        .content-card-body { padding: 16px; }

        /* ── Chart container ── */
        #evoBarChart { width: 100%; height: 360px; display: block; }
        #evoSparkLine { width: 100%; display: block; }
        #evoSparkLineScroll::-webkit-scrollbar { width: 5px; }
        #evoSparkLineScroll::-webkit-scrollbar-track { background: #f5f5f5; border-radius: 4px; }
        #evoSparkLineScroll::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        #evoSparkLineScroll::-webkit-scrollbar-thumb:hover { background: #bbb; }

        /* ── Evolution Table ── */
        .evo-table { width: 100%; border-collapse: collapse; }
        .evo-table thead th {
            font-size: 11px; font-weight: 700; color: #aaa;
            text-transform: uppercase; letter-spacing: 0.06em;
            padding: 0 0 8px; border-bottom: 1px solid #eee; text-align: left;
        }
        .evo-table thead th.right { text-align: right; }
        .evo-table tbody tr { border-bottom: 1px solid #f5f5f5; transition: background 0.1s; }
        .evo-table tbody tr:hover { background: #fafafa; }
        .evo-table tbody tr:last-child { border-bottom: none; }
        .evo-table tbody td { padding: 10px 0; font-size: 13px; color: #333; vertical-align: middle; }
        .evo-table tbody td.right { text-align: right; }

        .td-region { display: flex; align-items: center; gap: 8px; }
        .region-dot { width: 11px; height: 11px; border-radius: 2px; flex-shrink: 0; }

        .td-rev  { font-weight: 700; color: #2c3e50; white-space: nowrap; }
        .td-prev { color: #999; font-size: 12px; white-space: nowrap; }

        /* Evolution pill */
        .evo-pill {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 12px; font-weight: 700;
            padding: 3px 10px; border-radius: 99px; white-space: nowrap;
        }
        .evo-pill.up   { background: rgba(39,174,96,0.12);  color: #27ae60; }
        .evo-pill.down { background: rgba(231,76,60,0.10);  color: #e74c3c; }
        .evo-pill.flat { background: rgba(127,140,141,0.12);color: #7f8c8d; }
        .evo-pill i { font-size: 10px; }

        /* Mini sparkline bar (inline progress) */
        .spark-wrap { display: flex; align-items: center; gap: 6px; }
        .spark-bar  { flex: 1; height: 6px; background: #eee; border-radius: 99px; overflow: hidden; }
        .spark-fill { height: 100%; border-radius: 99px; transition: width 0.5s ease; }

        /* Share % progress bar in table */
        .share-bar-wrap  { display: flex; align-items: center; gap: 8px; min-width: 120px; }
        .share-bar-track { flex: 1; height: 8px; background: #eee; border-radius: 99px; overflow: hidden; }
        .share-bar-fill  { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
        .share-bar-label { font-size: 12px; font-weight: 700; color: #555; white-space: nowrap; min-width: 34px; }

        /* Tfoot */
        .evo-table tfoot td {
            font-size: 13px; font-weight: 700; color: #333;
            padding: 9px 0 0; border-top: 2px solid #eee;
        }
        .evo-table tfoot td.right { text-align: right; color: #2c3e50; }

        /* ── Legend strip ── */
        .chart-legend { display: flex; gap: 18px; flex-wrap: wrap; padding: 0 0 10px; }
        .legend-item  { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #555; }
        .legend-swatch { width: 12px; height: 12px; border-radius: 3px; }

        /* ── Date comparison strip ── */
        .date-strip {
            background: #f8f9fa; border: 1px solid #eee; border-radius: 4px;
            padding: 7px 14px; font-size: 12px; color: #666;
            display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 14px;
        }
        .date-strip span { display: flex; align-items: center; gap: 5px; }
        .date-strip b { color: #333; }
        .ds-dot { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

        /* ── Loading / Error ── */
        .loading-spinner { text-align: center; padding: 60px 0; color: #aaa; font-size: 13px; }
        .loading-spinner i { font-size: 28px; display: block; margin-bottom: 10px; color: #e87722; animation: spin 0.9s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .alert-error { background: #fdf2f2; border-left: 4px solid #e74c3c; padding: 12px 16px; font-size: 13px; color: #922b21; border-radius: 0 4px 4px 0; }

        /* ── Pagination ── */
        .pg-bar { display: flex; align-items: center; justify-content: flex-end; gap: 3px; padding-top: 10px; border-top: 1px solid #f0f0f0; margin-top: 6px; }
        .pg-info { font-size: 11px; color: #bbb; margin-right: 4px; }
        .pg-btn { padding: 3px 8px; font-size: 12px; border: 1px solid #ddd; background: #fff; border-radius: 3px; cursor: pointer; color: #555; line-height: 1.4; }
        .pg-btn:hover:not([disabled]) { background: #f5f5f5; }
        .pg-btn[disabled] { opacity: 0.35; cursor: default; }
        .pg-btn.active { background: #e87722; color: #fff; border-color: #e87722; }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "report";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "Revenueevolution";
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
                                <span>Revenue Evolution Per Region</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="rpr-page-header">
                    <h3><i class="icon-signal"></i>Revenue Per Region &amp; Evolution vs Previous Month</h3>
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

                <!-- Summary Stat Cards -->
                <div class="row-fluid" style="margin-bottom:14px;">
                    <div class="span3">
                        <div class="stat-card blue">
                            <div class="stat-icon"><i class="icon-money"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Current Revenue</div>
                                <div class="sc-value" id="card-cur-rev">–</div>
                                <div class="sc-sub"   id="card-cur-range">Loading…</div>
                            </div>
                        </div>
                    </div>
                    <div class="span3">
                        <div class="stat-card purple">
                            <div class="stat-icon"><i class="icon-calendar"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Previous Revenue</div>
                                <div class="sc-value" id="card-prev-rev">–</div>
                                <div class="sc-sub"   id="card-prev-range"></div>
                            </div>
                        </div>
                    </div>
                    <div class="span3">
                        <div class="stat-card green">
                            <div class="stat-icon"><i class="icon-arrow-up"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Total Evolution</div>
                                <div class="sc-value" id="card-evo-val">–</div>
                                <div class="sc-sub"><span id="card-evo-badge"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="span3">
                        <div class="stat-card amber">
                            <div class="stat-icon"><i class="icon-trophy"></i></div>
                            <div class="stat-body">
                                <div class="sc-label">Fastest Growing</div>
                                <div class="sc-value" id="card-top-region">–</div>
                                <div class="sc-sub"   id="card-top-evo"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div id="loadingState" class="content-card">
                    <div class="content-card-body loading-spinner">
                        <i class="icon-refresh"></i>Fetching revenue evolution data…
                    </div>
                </div>

                <!-- Error -->
                <div id="errorState" class="alert-error" style="display:none;"></div>

                <!-- Main content -->
                <div id="dataContent" style="display:none;">

                    <!-- Date range comparison strip -->
                    <div class="date-strip">
                        <span><span class="ds-dot" style="background:#4e9ff5;"></span> <b>Current period:</b> <span id="strip-cur">–</span></span>
                        <span><span class="ds-dot" style="background:#c8a0e8;"></span> <b>Previous period:</b> <span id="strip-prev">–</span></span>
                    </div>

                    <!-- TOP ROW: Grouped bar chart -->
                    <div class="row-fluid">
                        <div class="span12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><i class="icon-bar-chart"></i>Revenue Comparison: Current vs Previous Period</h4>
                                    <div class="chart-legend">
                                        <div class="legend-item"><span class="legend-swatch" style="background:#4e9ff5;"></span>Current Period</div>
                                        <div class="legend-item"><span class="legend-swatch" style="background:#c8a0e8;"></span>Previous Period</div>
                                    </div>
                                </div>
                                <div class="content-card-body" style="padding:12px 16px 8px;">
                                    <div id="evoBarChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLE ROW: Full-width Region Breakdown table -->
                    <div class="row-fluid">
                        <div class="span12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><i class="icon-list"></i>Region Breakdown &amp; Evolution</h4>
                                </div>
                                <div class="content-card-body">
                                    <table class="evo-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Region</th>
                                                <th style="width:28%;">Share (Current Period)</th>
                                                <th class="right" style="width:18%;">Current</th>
                                                <th class="right" style="width:18%;">Previous</th>
                                                <th class="right" style="width:16%;">Evolution</th>
                                            </tr>
                                        </thead>
                                        <tbody id="evoTbody"></tbody>
                                        <tfoot>
                                            <tr>
                                                <td><strong>TOTAL</strong></td>
                                                <td></td>
                                                <td class="right" id="ft-cur">–</td>
                                                <td class="right" id="ft-prev">–</td>
                                                <td class="right" id="ft-evo">–</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <div class="pg-bar" id="evoPagination" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /table row -->

                    <!-- CHART ROW: Full-width Evolution % chart -->
                    <div class="row-fluid">
                        <div class="span12">
                            <div class="content-card">
                                <div class="content-card-header">
                                    <h4><i class="icon-signal"></i>Evolution % by Region</h4>
                                </div>
                                <div class="content-card-body" style="padding:12px 16px 12px 0;">
                                    <div id="evoSparkLineScroll" style="overflow-y:auto; max-height:420px;">
                                        <div id="evoSparkLine"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /chart row -->
                </div><!-- /dataContent -->

            </div><!-- /container-fluid -->
        </div><!-- /page-content -->
    </div><!-- /page-container -->

    <?php include('../include/footer.php'); ?>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/jquery.dataTables.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/data-tables/dataTables.buttons.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/scripts/form-samples.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/moment.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.min.js"></script>

<script>
jQuery(document).ready(function () {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ── Config ── */
    var API_URL       = '<?= $baseurl ?>/<?= $appname ?>/reports/datatables-scripts/get_revenue_evolution.php';
    var PALETTE       = ['#4e9ff5','#2ecc71','#9b59b6','#f39c12','#e74c3c','#1abc9c','#e67e22','#3498db','#16a085','#8e44ad'];
    var PREV_ALPHA    = 'c8a0e8';  // light purple for previous bars
    var currentPeriod = 'custom';
    var currentFrom   = moment().subtract(6, 'days').format('YYYY-MM-DD');
    var currentTo     = moment().format('YYYY-MM-DD');

    /* ── Helpers ── */
    function fmtRev(v) {
        v = parseFloat(v) || 0;
        if (v >= 1000000) return '$' + (v / 1000000).toFixed(2) + 'M';
        if (v >= 1000)    return '$' + (v / 1000).toFixed(1) + 'K';
        return '$' + v.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function fmtDate(str) {
        var d = new Date(str);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }
    function fmtEvo(pct) {
        var sign = pct > 0 ? '+' : '';
        return sign + parseFloat(pct).toFixed(2) + '%';
    }
    function trendClass(trend) {
        return trend === 'up' ? 'up' : (trend === 'down' ? 'down' : 'flat');
    }
    function trendIcon(trend) {
        return trend === 'up' ? 'icon-arrow-up' : (trend === 'down' ? 'icon-arrow-down' : 'icon-minus');
    }

    /* ── UI States ── */
    function showLoading() { $('#loadingState').show(); $('#errorState').hide(); $('#dataContent').hide(); }
    function showError(msg) { $('#loadingState').hide(); $('#errorState').text('Error: ' + msg).show(); $('#dataContent').hide(); }
    function showData()  { $('#loadingState').hide(); $('#errorState').hide(); $('#dataContent').show(); }

    /* ── Render stat cards ── */
    function renderCards(resp) {
        $('#card-cur-rev').text(fmtRev(resp.current.total_revenue));
        $('#card-cur-range').text(fmtDate(resp.current.from_date) + ' – ' + fmtDate(resp.current.to_date));
        $('#card-prev-rev').text(fmtRev(resp.previous.total_revenue));
        $('#card-prev-range').text(fmtDate(resp.previous.from_date) + ' – ' + fmtDate(resp.previous.to_date));

        var evoPct = parseFloat(resp.total_evolution_pct) || 0;
        var tc = trendClass(evoPct > 0.5 ? 'up' : (evoPct < -0.5 ? 'down' : 'flat'));
        $('#card-evo-val').text(fmtEvo(evoPct));
        $('#card-evo-badge').html('<span class="evo-badge ' + tc + '"><i class="' + trendIcon(tc) + '"></i> vs previous period</span>');

        /* Date strip */
        $('#strip-cur').text(fmtDate(resp.current.from_date) + ' – ' + fmtDate(resp.current.to_date));
        $('#strip-prev').text(fmtDate(resp.previous.from_date) + ' – ' + fmtDate(resp.previous.to_date));

        /* Fastest growing region (highest positive evo) */
        var topUp = null;
        (resp.regions || []).forEach(function(r) {
            if (!topUp || r.evolution_pct > topUp.evolution_pct) topUp = r;
        });
        if (topUp) {
            $('#card-top-region').text(topUp.region);
            $('#card-top-evo').html('<span class="evo-badge ' + trendClass(topUp.trend) + '">' + fmtEvo(topUp.evolution_pct) + ' vs prev period</span>');
        }
    }

    /* ── Render grouped bar chart (ECharts) ── */
    function renderBarChart(regions) {
        var container = document.querySelector('#evoBarChart');
        if (!container) return;

        var labels    = regions.map(function(r) { return r.region; });
        var curData   = regions.map(function(r) { return r.current_revenue; });
        var prevData  = regions.map(function(r) { return r.previous_revenue; });

        var existingChart = echarts.getInstanceByDom(container);
        if (existingChart) existingChart.dispose();
        var chart = echarts.init(container);

        var option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                backgroundColor: 'rgba(255,255,255,0.97)',
                borderColor: '#e4e9f0',
                borderWidth: 1,
                textStyle: { color: '#2c3e50', fontSize: 13 },
                extraCssText: 'box-shadow:0 3px 12px rgba(0,0,0,.12);border-radius:8px;',
                formatter: function(params) {
                    var html = '<div style="font-weight:700;margin-bottom:6px;color:#333;">' + params[0].name + '</div>';
                    params.forEach(function(p) {
                        html += '<div style="margin:3px 0;">' + p.marker + ' ' + p.seriesName
                              + ': <b>' + fmtRev(p.value) + '</b></div>';
                    });
                    /* evolution delta */
                    if (params.length === 2) {
                        var cur  = params[0].value, prev = params[1].value;
                        var pct  = prev > 0 ? (((cur - prev) / prev) * 100).toFixed(2) : 'N/A';
                        var clr  = cur >= prev ? '#27ae60' : '#e74c3c';
                        var sign = cur >= prev ? '▲' : '▼';
                        html += '<div style="margin-top:5px;padding-top:5px;border-top:1px solid #eee;color:' + clr + ';font-weight:700;">'
                              + sign + ' Evolution: ' + (pct !== 'N/A' ? (cur>=prev?'+':'') + pct + '%' : 'New') + '</div>';
                    }
                    return html;
                }
            },
            toolbox: {
                show: true, top: 4, right: 6,
                feature: { saveAsImage: { show: true, pixelRatio: 2, backgroundColor: '#ffffff', name: 'revenue_evolution' } }
            },
            legend: { show: false },
            grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
            xAxis: {
                type: 'category',
                data: labels,
                axisLine: { lineStyle: { color: '#ddd' } },
                axisLabel: { color: '#666', fontSize: 12, fontWeight: 600 }
            },
            yAxis: {
                type: 'value',
                axisLabel: {
                    color: '#999', fontSize: 11,
                    formatter: function(v) {
                        if (v >= 1000000) return '$' + (v/1000000).toFixed(1) + 'M';
                        if (v >= 1000)    return '$' + (v/1000).toFixed(0) + 'K';
                        return '$' + v;
                    }
                },
                splitLine: { lineStyle: { color: '#f0f0f0' } }
            },
            series: [
                {
                    name: 'Current Period',
                    type: 'bar',
                    barMaxWidth: 40,
                    data: curData.map(function(v, i) {
                        return { value: v, itemStyle: { color: PALETTE[i % PALETTE.length], borderRadius: [4,4,0,0] } };
                    }),
                    label: {
                        show: true, position: 'top', fontSize: 10, fontWeight: 700, color: '#555',
                        formatter: function(p) {
                            return p.value >= 1000 ? '$' + (p.value/1000).toFixed(1) + 'K' : ('$' + p.value);
                        }
                    }
                },
                {
                    name: 'Previous Period',
                    type: 'bar',
                    barMaxWidth: 40,
                    data: prevData.map(function(v) {
                        return { value: v, itemStyle: { color: '#c8a0e8', borderRadius: [4,4,0,0] } };
                    }),
                    label: {
                        show: true, position: 'top', fontSize: 10, color: '#999',
                        formatter: function(p) {
                            return p.value >= 1000 ? '$' + (p.value/1000).toFixed(1) + 'K' : ('$' + p.value);
                        }
                    }
                }
            ]
        };

        chart.setOption(option);
        setTimeout(function() { chart.resize(); }, 50);
        $(window).off('resize.evoBar').on('resize.evoBar', function() { chart.resize(); });
    }

    /* ── Render Evolution % lollipop chart (ECharts) ── */
    function renderEvoChart(regions) {
        var container = document.querySelector('#evoSparkLine');
        if (!container) return;

        /* Sort descending — best performer at top */
        var sorted = regions.slice().sort(function(a, b) { return b.evolution_pct - a.evolution_pct; });
        var labels = sorted.map(function(r) { return r.region; });
        var values = sorted.map(function(r) { return parseFloat(r.evolution_pct) || 0; });

        /* Dynamic height: 56px per row, min 300px */
        var rowHeight  = 56;
        var chartHeight = Math.max(300, labels.length * rowHeight);
        container.style.height = chartHeight + 'px';

        var existingChart = echarts.getInstanceByDom(container);
        if (existingChart) existingChart.dispose();
        var chart = echarts.init(container);

        /* Colours per value */
        var GREEN = '#1a8a4a', RED = '#c0392b', FLAT = '#666';
        function dotColor(v) { return v > 0.5 ? GREEN : (v < -0.5 ? RED : FLAT); }

        /* Lollipop stem: custom series using markLine per-item workaround.
           We draw stems as a scatter series with error-bar style via a
           custom series that draws a line from 0 to value for each bar. */
        var stemData = values.map(function(v, i) {
            return { value: [0, i], toValue: [v, i], color: dotColor(v) };
        });

        var option = {
            backgroundColor: 'transparent',
            tooltip: {
                trigger: 'item',
                backgroundColor: 'rgba(255,255,255,0.97)',
                borderColor: '#e4e9f0', borderWidth: 1,
                textStyle: { color: '#2c3e50', fontSize: 12 },
                extraCssText: 'box-shadow:0 4px 14px rgba(0,0,0,.13);border-radius:8px;padding:10px 14px;',
                formatter: function(params) {
                    if (params.seriesIndex !== 1) return null; /* only dot series */
                    var v = params.value[0];
                    var clr = dotColor(v);
                    var icon = v > 0.5 ? '▲' : (v < -0.5 ? '▼' : '—');
                    return '<b style="font-size:13px;">' + params.name + '</b>'
                         + '<br/><span style="color:' + clr + ';font-size:15px;font-weight:700;">'
                         + icon + ' ' + (v > 0 ? '+' : '') + v.toFixed(2) + '%</span>';
                }
            },
            grid: { left: 20, right: 40, top: 10, bottom: 10, containLabel: true },
            xAxis: {
                type: 'value',
                axisLabel: {
                    fontSize: 10, color: '#888',
                    formatter: function(v) { return v + '%'; }
                },
                splitLine: { lineStyle: { color: '#ddd', type: 'dashed' } },
                axisLine: { show: false },
                axisTick: { show: false }
            },
            yAxis: {
                type: 'category',
                data: labels,
                inverse: true,
                axisLabel: { fontSize: 11, color: '#444', fontWeight: 600, margin: 10 },
                axisLine: { lineStyle: { color: '#eee' } },
                axisTick: { show: false },
                splitLine: { show: false }
            },
            series: [
                /* ── Series 0: stems (thin lines from 0 → value) ── */
                {
                    type: 'custom',
                    silent: true,
                    renderItem: function(params, api) {
                        var start  = api.coord([0,           api.value(1)]);
                        var end    = api.coord([api.value(0), api.value(1)]);
                        var color  = api.value(2);
                        return {
                            type: 'line',
                            shape: { x1: start[0], y1: start[1], x2: end[0], y2: end[1] },
                            style: { stroke: color, lineWidth: 3, opacity: 0.85 }
                        };
                    },
                    /* encode value[0]=x, value[1]=yIndex, value[2]=colorIndex */
                    data: values.map(function(v, i) {
                        return [v, i, dotColor(v)];
                    }),
                    z: 2
                },
                /* ── Series 1: dots ── */
                {
                    type: 'scatter',
                    symbolSize: 14,
                    data: values.map(function(v, i) {
                        return {
                            value: [v, i],
                            name: labels[i],
                            itemStyle: {
                                color: dotColor(v),
                                borderColor: '#fff',
                                borderWidth: 2,
                                shadowBlur: 6,
                                shadowColor: 'rgba(0,0,0,0.18)'
                            },
                            label: {
                                show: true,
                                position: v >= 0 ? 'right' : 'left',
                                distance: 6,
                                fontSize: 11,
                                fontWeight: 700,
                                color: dotColor(v),
                                formatter: (v > 0 ? '+' : '') + v.toFixed(1) + '%',
                                overflow: 'break'
                            }
                        };
                    }),
                    label: { show: false },
                    z: 3
                },
                /* ── Series 2: zero reference line ── */
                {
                    type: 'line',
                    silent: true,
                    data: [],
                    markLine: {
                        silent: true,
                        symbol: 'none',
                        lineStyle: { color: '#999', width: 2, type: 'solid' },
                        label: { show: false },
                        data: [{ xAxis: 0 }]
                    }
                }
            ]
        };

        chart.setOption(option);
        setTimeout(function() { chart.resize(); }, 50);
        $(window).off('resize.evoSpark').on('resize.evoSpark', function() { chart.resize(); });
    }

    /* ── Render evolution table with pagination ── */
    var PAGE_SIZE    = 6;
    var currentPage  = 1;
    var allRegions   = [];

    function renderPage(page) {
        var start = (page - 1) * PAGE_SIZE;
        var slice = allRegions.slice(start, start + PAGE_SIZE);

        /* Compute total current revenue for share % */
        var totalCur = 0;
        allRegions.forEach(function(r) { totalCur += parseFloat(r.current_revenue) || 0; });

        var html  = '';
        slice.forEach(function(r, idx) {
            var i     = allRegions.indexOf(r);
            var color = PALETTE[i % PALETTE.length];
            var pct   = parseFloat(r.current_pct) || 0;
            var tc    = trendClass(r.trend);
            var ti    = trendIcon(r.trend);

            /* Share % bar */
            var sharePct = totalCur > 0 ? ((parseFloat(r.current_revenue) || 0) / totalCur * 100) : 0;
            var shareBar = '<div class="share-bar-wrap">'
                         + '<div class="share-bar-track"><div class="share-bar-fill" style="width:' + sharePct.toFixed(1) + '%;background:' + color + ';"></div></div>'
                         + '<span class="share-bar-label">' + sharePct.toFixed(0) + '%</span>'
                         + '</div>';

            html += '<tr>'
                  + '<td><span class="td-region"><span class="region-dot" style="background:' + color + ';"></span>' + r.region + '</span></td>'
                  + '<td>' + shareBar + '</td>'
                  + '<td class="right td-rev">' + fmtRev(r.current_revenue) + '</td>'
                  + '<td class="right td-prev">' + fmtRev(r.previous_revenue) + '</td>'
                  + '<td class="right"><span class="evo-pill ' + tc + '"><i class="' + ti + '"></i>' + fmtEvo(r.evolution_pct) + '</span></td>'
                  + '</tr>';
        });
        $('#evoTbody').html(html);
    }

    function renderPagination(total) {
        var pages = Math.ceil(total / PAGE_SIZE);
        if (pages <= 1) { $('#evoPagination').hide().html(''); return; }
        var html = '<span class="pg-info">Page ' + currentPage + ' of ' + pages + '</span>';
        html += '<button class="pg-btn" id="pgPrev"' + (currentPage === 1 ? ' disabled' : '') + '>&#8249;</button>';
        for (var p = 1; p <= pages; p++) {
            html += '<button class="pg-btn' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
        }
        html += '<button class="pg-btn" id="pgNext"' + (currentPage === pages ? ' disabled' : '') + '>&#8250;</button>';
        $('#evoPagination').html(html).show();

        $('#evoPagination').off('click').on('click', '.pg-btn', function() {
            var $b = $(this);
            if ($b.attr('disabled') !== undefined) return;
            if ($b.attr('id') === 'pgPrev')      currentPage--;
            else if ($b.attr('id') === 'pgNext') currentPage++;
            else currentPage = parseInt($b.data('page'), 10);
            currentPage = Math.max(1, Math.min(pages, currentPage));
            renderPage(currentPage);
            renderPagination(total);
        });
    }

    function renderTable(resp) {
        allRegions  = resp.regions || [];
        currentPage = 1;
        renderPage(currentPage);
        renderPagination(allRegions.length);

        /* Footer totals */
        $('#ft-cur').text(fmtRev(resp.current.total_revenue));
        $('#ft-prev').text(fmtRev(resp.previous.total_revenue));
        var tc = trendClass(resp.total_evolution_pct > 0.5 ? 'up' : (resp.total_evolution_pct < -0.5 ? 'down' : 'flat'));
        $('#ft-evo').html('<span class="evo-pill ' + tc + '">' + fmtEvo(resp.total_evolution_pct) + '</span>');
    }

    /* ── Fetch ── */
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
            success: function(resp) {
                if (!resp.success) { showError(resp.error || 'Unknown API error'); return; }
                renderCards(resp);
                renderTable(resp);
                showData();
                renderBarChart(resp.regions);
                renderEvoChart(resp.regions);
            },
            error: function(xhr) {
                var msg = 'Request failed (HTTP ' + xhr.status + ')';
                try { var j = JSON.parse(xhr.responseText); if (j.error) msg = j.error; } catch(e) {}
                showError(msg);
            }
        });
    }

    /* ── Daterangepicker — always visible, always custom ── */
    $('#reportrange').daterangepicker({
        startDate      : moment().subtract(6, 'days'),
        endDate        : moment(),
        maxDate        : moment(),
        maxSpan        : { days: 90 },
        showDropdowns  : true,
        linkedCalendars: false,
        locale: {
            format      : 'DD MMM YYYY',
            separator   : ' – ',
            applyLabel  : 'Apply',
            cancelLabel : 'Cancel',
            firstDay    : 1
        },
        ranges: {
           // 'Today'       : [moment(), moment()],
            'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          //  'This Month'  : [moment().startOf('month'), moment().endOf('month')],
          //  'Last Month'  : [moment().subtract(1, 'month').startOf('month'),
          //                   moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end) {
        currentFrom = start.format('YYYY-MM-DD');
        currentTo   = end.format('YYYY-MM-DD');
        $('#reportrange span').html(start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY'));
        loadData('custom', currentFrom, currentTo);
    });

    /* Set initial picker label */
    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ── Boot ── */
    loadData('custom', currentFrom, currentTo);
});
</script>

</body>
</html>
