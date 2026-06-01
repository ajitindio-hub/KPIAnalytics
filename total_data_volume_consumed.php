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

$period = 'custom';

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
	<title><?= $WIFILANTITLE ?></title>
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
    <script src="<?= $baseurl ?>/<?= $appname ?>/<?= $assetsDir ?>/echarts-6.0.0/package/asset/echart-render.js"></script>

 <style>
	/* ── Fix daterangepicker Sunday column being cut off ── */
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

    /* ── Page header row: title LEFT, filter RIGHT ── */
    .du-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0;
    }
    .du-page-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ── Period filter (dropdown + daterangepicker) ── */
    .period-filter-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .period-select {
        height: 32px;
        padding: 0 10px;
        font-size: 12px;
        font-weight: 600;
        color: #5a6a80;
        background: #fff;
        border: 1px solid #dde3ec;
        border-radius: 6px;
        cursor: pointer;
        outline: none;
        transition: border-color .2s;
    }
    .period-select:focus { border-color: #e87722; }
    .btn-daterange {
        display: flex;
        align-items: center;
        gap: 6px;
        height: 32px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 600;
        color: #5a6a80;
        background: #fff;
        border: 1px solid #dde3ec;
        border-radius: 6px;
        cursor: pointer;
        white-space: nowrap;
        transition: all .2s;
    }
    .btn-daterange:hover, .btn-daterange.active {
        background: #e87722;
        color: #fff;
        border-color: #e87722;
    }

    /* ── Pagination controls ── */
    .du-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #f0f4fa;
        font-size: 12px;
        color: #8a97a8;
    }
    .du-pagination .du-page-info { font-weight: 500; }
    .du-pagination .du-page-btns {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .du-pagination .du-page-btns button {
        min-width: 28px;
        height: 28px;
        padding: 0 7px;
        font-size: 12px;
        font-weight: 600;
        color: #5a6a80;
        background: #fff;
        border: 1px solid #dde3ec;
        border-radius: 5px;
        cursor: pointer;
        transition: all .15s;
    }
    .du-pagination .du-page-btns button:hover:not(:disabled) {
        background: #e87722;
        color: #fff;
        border-color: #e87722;
    }
    .du-pagination .du-page-btns button.active {
        background: #e87722;
        color: #fff;
        border-color: #e87722;
    }
    .du-pagination .du-page-btns button:disabled {
        opacity: .4;
        cursor: not-allowed;
    }
    .du-rows-select {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #8a97a8;
    }
    .du-rows-select select {
        height: 26px;
        padding: 0 6px;
        font-size: 12px;
        border: 1px solid #dde3ec;
        border-radius: 4px;
        color: #5a6a80;
        background: #fff;
        outline: none;
    }

    /* ── KPI Tiles ── */
    .du-kpi-tiles {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        width: 100%;              /* full width */
    }
    .du-kpi-tile {
        flex: 1;
        background: #fff;
        border: 1px solid #e4e9f0;
        border-radius: 8px;
        padding: 16px 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }
    .du-kpi-tile::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 8px 8px 0 0;
    }
    .du-kpi-tile.dl::before  { background: #4A90D9; }
    .du-kpi-tile.ul::before  { background: #00C9A7; }
    .du-kpi-tile.avg::before { background: #9B59B6; }
    .du-kpi-tile.sold::before { background: #F39C12; }
    .du-kpi-tile.rev::before  { background: #E74C3C; }
    .du-kpi-tile .tile-label {
        font-size: 11px;
        font-weight: 600;
        color: #8a97a8;
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .du-kpi-tile.dl  .tile-label i { color: #4A90D9; }
    .du-kpi-tile.ul  .tile-label i { color: #00C9A7; }
    .du-kpi-tile.avg .tile-label i { color: #9B59B6; }
    .du-kpi-tile.sold .tile-label i { color: #F39C12; }
    .du-kpi-tile.rev  .tile-label i { color: #E74C3C; }
    .du-kpi-tile .tile-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e2b3c;
        line-height: 1;
    }

    /* ── Outer wrapper matches tiles width exactly ── */
    .du-outer {
        display: flex;
        gap: 16px;
        align-items: stretch;
        width: 100%;              /* same as tiles row */
    }

    /* ── Donut Box — wider ── */
    .du-donut-box {
        background: #fff;
        border: 1px solid #e4e9f0;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 16px;
        flex: 0 0 320px;          /* wider box */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .du-donut-box-title {
        font-size: 11px;
        font-weight: 600;
        color: #8a97a8;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 10px;
        align-self: flex-start;
    }
    #dataUsageDonut {
        width: 280px;             /* bigger donut */
        height: 280px;
    }
    .du-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 12px;
        margin-top: 12px;
    }
    .du-legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: #4a5568;
        font-weight: 500;
    }
    .du-legend-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── Table Box — fills remaining width ── */
    .du-table-box {
        flex: 1;
        background: #fff;
        border: 1px solid #e4e9f0;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .du-table-box-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f4fa;
    }
    .du-table-box-header h4 {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #2c3e50;
    }
    .du-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .du-table thead tr { border-bottom: 1px solid #e4e9f0; }
    .du-table thead th {
        padding: 7px 10px;
        color: #8a97a8;
        font-weight: 600;
        font-size: 11px;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .du-table tbody tr { border-bottom: 1px solid #f5f7fa; }
    .du-table tbody tr:last-child { border-bottom: none; }
    .du-table tbody tr:hover { background: #f9fbff; }
    .du-table tbody td {
        padding: 9px 10px;
        color: #2c3e50;
        vertical-align: middle;
    }
    .du-table tbody td:nth-child(2) { font-weight: 600; }
    .du-share-badge {
        background: #f0f4fa;
        color: #5a6a80;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 20px;
        display: inline-block;
	}
.ais-report-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
#duRowsPerPage {
    width: 50px;
}
</style> 
 
 

</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "analytics";
            $_SESSION['submenu']       = "bandwidthMenu";
            $_SESSION['submenulevel1'] = "dataConsumption";
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
                                <a href="#">Analytics</a>
                                <i class="icon-angle-right"></i>
                                <a href="#">Bandwidth</a>
                                <i class="icon-angle-right"></i>
                                <span>Data Consumption</span>
                            </li>
                        </ul>
                    </div>
                </div>

				<!-- Page Header -->
                <div class="breadcrumb ais-report-header">
                    <div class="du-page-header">
                        <h3>
                            <i class="icon-signal" style="color:#e87722;"></i>
                            Total data volume consumed
                        </h3>
                        <div class="period-filter-wrap">
                            <button type="button" id="reportrange" class="btn-daterange">
                                <i class="icon-calendar"></i>
                                <span>Select date range</span>
                                <i class="icon-angle-down" style="margin-left:4px;font-size:11px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <!-- ══ KPI TILES — 3 separate boxes with gap ══ -->

				<!-- ══ PAGE HEADER: Title LEFT — Buttons RIGHT ══ -->


<!-- ══ KPI TILES ══ -->
<div class="du-kpi-tiles">
    <div class="du-kpi-tile dl">
        <div class="tile-label"><i class="icon-download-alt"></i> Download</div>
        <div class="tile-value" id="kpiDownload">–</div>
    </div>
    <div class="du-kpi-tile ul">
        <div class="tile-label"><i class="icon-upload-alt"></i> Upload</div>
        <div class="tile-value" id="kpiUpload">–</div>
    </div>
    <div class="du-kpi-tile avg">
        <div class="tile-label"><i class="icon-dashboard"></i> Avg Usage per Pack</div>
        <div class="tile-value" id="kpiAvgPack">–</div>
    </div>
    <div class="du-kpi-tile sold">
        <div class="tile-label"><i class="icon-shopping-cart"></i> Packs Sold</div>
        <div class="tile-value" id="kpiPacksSold">–</div>
    </div>
    <div class="du-kpi-tile rev">
        <div class="tile-label"><i class="icon-money"></i> Total Revenue</div>
        <div class="tile-value" id="kpiRevenue">–</div>
    </div>
</div>

<!-- ══ Donut Box + Table Box ══ -->
<div class="du-outer">

    <!-- Left: Donut Box -->
    <div class="du-donut-box">
        <div class="du-donut-box-title">Data Usage by Pack</div>
        <div id="dataUsageDonut">
            <div class="chart-loader">
                <i class="icon-spinner icon-spin"></i> Loading…
            </div>
        </div>
        <div class="du-legend" id="donutLegend"></div>
    </div>

    <!-- Right: Table Box -->
    <div class="du-table-box">
        <div class="du-table-box-header">
            <h4>
                <i class="icon-signal" style="color:#e87722;margin-right:5px;"></i>
                Data Pack Breakdown
            </h4>
            <div class="du-rows-select">
                Rows per page:
                <select id="duRowsPerPage">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
        <table class="du-table">
            <thead>
                <tr>
                    <th>Data Pack</th>
                    <th>Total Usage</th>
                    <th>Packs Sold</th>
                    <th>Avg Usage / Sold Pack</th>
                    <th>Revenue</th>
                    <th>Sold Share %</th>
                </tr>
            </thead>
            <tbody id="packLegendBody">
                <tr>
                    <td colspan="6" style="text-align:center;padding:30px;color:#8a97a8;">
                        <i class="icon-spinner icon-spin"></i> Loading…
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- Pagination -->
        <div class="du-pagination" id="duPagination" style="display:none;">
            <div class="du-page-info" id="duPageInfo"></div>
            <div class="du-page-btns" id="duPageBtns"></div>
        </div>
    </div>

</div><!-- /.du-outer -->
                <!-- Site Detail Table -->
                

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

			<script>

jQuery(document).ready(function () {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ══════════════════════════════════════════════════════════════════
       CURRENCY FORMATTER  — formats large numbers as K / M / B
       ══════════════════════════════════════════════════════════════════ */
    function formatCurrency(currency, amount) {
        var n = parseFloat(amount) || 0;
        var formatted;
        if (Math.abs(n) >= 1e9) {
            formatted = (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + 'B';
        } else if (Math.abs(n) >= 1e6) {
            formatted = (n / 1e6).toFixed(2).replace(/\.?0+$/, '') + 'M';
        } else if (Math.abs(n) >= 1e3) {
            formatted = (n / 1e3).toFixed(2).replace(/\.?0+$/, '') + 'K';
        } else {
            formatted = n.toFixed(2);
        }
        return currency + ' ' + formatted;
    }

    /* ══════════════════════════════════════════════════════════════════
       PAGINATION STATE
       ══════════════════════════════════════════════════════════════════ */
    var allPackRows  = [];   /* full dataset from last AJAX call */
    var currentPage  = 1;
    var rowsPerPage  = parseInt($('#duRowsPerPage').val(), 10) || 5;
    var lastCurrency = '';

    $('#duRowsPerPage').on('change', function () {
        rowsPerPage  = parseInt($(this).val(), 10);
        currentPage  = 1;
        renderTable();
    });

    function renderTable() {
        var total   = allPackRows.length;
        var start   = (currentPage - 1) * rowsPerPage;
        var end     = Math.min(start + rowsPerPage, total);
        var visible = allPackRows.slice(start, end);

        var html = '';
        $.each(visible, function(i, p) {
            var revenueStr = formatCurrency(lastCurrency, p.revenue_total);
            html += '<tr>'
                  + '<td><span style="display:inline-block;width:10px;height:10px;'
                  + 'border-radius:50%;background:' + p.color + ';margin-right:8px;"></span>'
                  + p.pack_name + '</td>'
                  + '<td style="font-weight:600;">' + p.total_label + '</td>'
                  + '<td>' + parseInt(p.sold_count, 10).toLocaleString() + '</td>'
                  + '<td>' + p.avg_usage_label + '</td>'
                  + '<td>' + revenueStr + '</td>'
                  + '<td><span class="du-share-badge">' + p.sold_share_pct + '%</span></td>'
                  + '</tr>';
        });
        if (!html) {
            html = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#8a97a8;">No data available.</td></tr>';
        }
        $('#packLegendBody').html(html);

        /* ── Pagination info & buttons ── */
        if (total > 0) {
            $('#duPageInfo').text('Showing ' + (start + 1) + '–' + end + ' of ' + total + ' packs');
            renderPageButtons(total);
            $('#duPagination').show();
        } else {
            $('#duPagination').hide();
        }
    }

    function renderPageButtons(total) {
        var totalPages = Math.ceil(total / rowsPerPage);
        var btns = '';

        btns += '<button id="duPrevBtn"' + (currentPage <= 1 ? ' disabled' : '') + '>&#8592;</button>';

        /* show at most 5 page numbers */
        var start = Math.max(1, currentPage - 2);
        var end   = Math.min(totalPages, start + 4);
        start     = Math.max(1, end - 4);

        if (start > 1) btns += '<button disabled>…</button>';
        for (var p = start; p <= end; p++) {
            btns += '<button class="' + (p === currentPage ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
        }
        if (end < totalPages) btns += '<button disabled>…</button>';

        btns += '<button id="duNextBtn"' + (currentPage >= totalPages ? ' disabled' : '') + '>&#8594;</button>';

        var $btns = $('#duPageBtns').html(btns);

        $btns.find('button[data-page]').on('click', function () {
            currentPage = parseInt($(this).data('page'), 10);
            renderTable();
        });
        $btns.find('#duPrevBtn').on('click', function () {
            if (currentPage > 1) { currentPage--; renderTable(); }
        });
        $btns.find('#duNextBtn').on('click', function () {
            if (currentPage < Math.ceil(allPackRows.length / rowsPerPage)) { currentPage++; renderTable(); }
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       PERIOD FILTER — dropdown + daterangepicker
       ══════════════════════════════════════════════════════════════════ */
    var currentFrom = moment().subtract(6, 'days').format('YYYY-MM-DD');
    var currentTo   = moment().format('YYYY-MM-DD');

    /* ── Daterangepicker ── */
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
            'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           /* 'This Month'  : [moment().startOf('month'), moment().endOf('month')],
            'Last Month'  : [moment().subtract(1, 'month').startOf('month'),
			moment().subtract(1, 'month').endOf('month')]*/
        }
    }, function(start, end) {
        $('#reportrange span').html(start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY'));
        currentFrom = start.format('YYYY-MM-DD');
        currentTo   = end.format('YYYY-MM-DD');
        loadDataUsageChart('custom', currentFrom, currentTo);
    });
    /* Initial label */
    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ── Initial load using custom date range (last 7 days) ── */
    loadDataUsageChart('custom', currentFrom, currentTo);

    /* ══════════════════════════════════════════════════════════════════
       NEW PIE/DONUT RENDERER
       renderEchartPieSiteStatus(graphId, data, assets)
       ---------------------------------------------------------
       Mirrors the format of renderEchartPieFromLabels() already
       present in echart-render.js.

       Expected data shape (returned by the API under key "pie"):
       {
         TitleDonut:      "72.5%",        // centre label
         ratioDonutChart: 0.52,           // inner ring ratio (0–1)
         donutLabelType:  "percent",      // "percent" | "value"
         activePct:       72.5,           // used for rich-text centre label
         dataChart: [
           { label: "Active Sites",   value: 42, color: "#27ae60" },
           { label: "Inactive Sites", value: 18, color: "#e74c3c" }
         ]
       }
       ══════════════════════════════════════════════════════════════════ */
    function renderEchartPieSiteStatus(graphId, data, assets) {
        assets = assets || {};
        var nodataImg = assets.nodataImg || '<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg';

        // ── Normalize selector ────────────────────────────────────────
        var selector  = (graphId.charAt(0) === '#' || graphId.charAt(0) === '.')
                        ? graphId : '#' + graphId;
        var container = document.querySelector(selector);
        if (!container) {
            console.error('renderEchartPieSiteStatus: container not found for "' + selector + '"');
            return;
        }

        // ── Guard: dataChart ──────────────────────────────────────────
        if (!data || !Array.isArray(data.dataChart) || data.dataChart.length === 0) {
            $(selector).html('<img class="nodata_image" src="' + nodataImg + '" alt="No Data">');
            return;
        }

        var hasData = data.dataChart.some(function(item) { return item.value > 0; });
        if (!hasData) {
            $(selector).html('<img class="nodata_image" src="' + nodataImg + '" alt="No Data">');
            return;
        }

        // ── Donut radii ───────────────────────────────────────────────
        var innerRatio  = parseFloat(data.ratioDonutChart) || 0.52;
        var innerRadius = Math.round(innerRatio * 100) + '%';
        var outerRadius = '78%';
        var activePct   = data.activePct || 0;

        // ── Series data ───────────────────────────────────────────────
        var seriesData = data.dataChart.map(function(item, idx) {
            var fallbacks = ['#5470c6','#91cc75','#fac858','#ee6666','#73c0de'];
            return {
                name:      item.label,
                value:     item.value,
                itemStyle: { color: item.color || fallbacks[idx % fallbacks.length] }
            };
        });

        // ── Dispose & re-init ─────────────────────────────────────────
        var existingChart = echarts.getInstanceByDom(container);
        if (existingChart) existingChart.dispose();
        $(selector).html('');
        var myChart = echarts.init(container);

        // ── Option ────────────────────────────────────────────────────
        var option = {
            backgroundColor: 'transparent',

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
                         + ' <span style="margin-left:4px;">Sites: <b>' + params.value + '</b></span>'
                         + ' <span style="color:#999;margin-left:6px;">(' + params.percent + '%)</span>'
                         + '</div>';
                }
            },

            /* ── Download button (saveAsImage) ── */
            toolbox: {
                show: true,
                top: 4,
                right: 6,
                feature: {
                    saveAsImage: {
                        show: true,
                        pixelRatio: 2,
                        backgroundColor: '#ffffff',
                        name: 'site_distribution'
                    }
                }
            },

            series: [{
                name:              data.TitleDonut || 'Sites',
                type:              'pie',
                radius:            [innerRadius, outerRadius],
                center:            ['50%', '50%'],
                avoidLabelOverlap: false,
                itemStyle:         { borderRadius: 4, borderColor: '#fff', borderWidth: 2 },

                /* Rich-text centre label showing active% */
                label: {
                    show: true,
                    position: 'center',
                    formatter: function() {
                        return '{pct|' + activePct + '%}\n{sub|Active}';
                    },
                    rich: {
                        pct: { fontSize: 26, fontWeight: '700', color: '#1e2b3c', lineHeight: 32 },
                        sub: { fontSize: 12, color: '#8a97a8', lineHeight: 20 }
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

        // ── Responsive resize ─────────────────────────────────────────
        $(window).off('resize.pieSiteStatus_' + selector)
                 .on( 'resize.pieSiteStatus_' + selector, function() {
                     myChart.resize();
                 });
    }

    /* ══════════════════════════════════════════════════════════════════
       DataTable instance reference (destroyed & rebuilt on period change)
       ══════════════════════════════════════════════════════════════════ */
    var dtInstance = null;

    /* ══════════════════════════════════════════════════════════════════
       HELPERS
       ══════════════════════════════════════════════════════════════════ */
    function numberFormat(n) {
        return parseInt(n, 10).toLocaleString();
    }

	function wowBadge(wow, invertPositive) {
		if (wow === null || wow === undefined) return '';

		var isGood = invertPositive ? (wow <= 0) : (wow >= 0);

		var cls   = isGood ? 'up' : 'down';
		var arrow = isGood ? '▲' : '▼';   
		var sign  = wow > 0 ? '+' : '';

		return '<span class="kpi-wow ' + cls + '">' + arrow + ' ' + sign + wow + '% WoW</span>';
	}

	function statusBadge(status) {
        return status === 'Active'
            ? '<span class="badge-active">Active</span>'
            : '<span class="badge-inactive">Inactive</span>';
    }

    /* ══════════════════════════════════════════════════════════════════
       MAIN: loadReportData(period)
       ══════════════════════════════════════════════════════════════════ */




    /* ══════════════════════════════════════════════════════════════════
       MAIN: loadReportData(period)
       ══════════════════════════════════════════════════════════════════ */
function loadReportData(period) {
    loadDataUsageChart(period);
}

function loadDataUsageChart(period, dateFrom, dateTo) {
    var postData = { period: period };
    if (period === 'custom' && dateFrom && dateTo) {
        postData.date_from = dateFrom;
        postData.date_to   = dateTo;
    }

    $.ajax({
        url:      './datatables-scripts/get_data_usage_pack_data.php',
        type:     'POST',
        data:     postData,
        dataType: 'json',

        success: function(resp) {
            if (!resp || resp.status !== 'success') return;

            var kpi = resp.kpi;
            lastCurrency = kpi.currency;

            /* ── KPI values ── */
            $('#kpiTotalData').text(kpi.total_label);
            $('#kpiDownload').text(kpi.download_label);
            $('#kpiUpload').text(kpi.upload_label);
            $('#kpiAvgPack').text(kpi.avg_per_pack_label);
            $('#kpiPacksSold').text(parseInt(kpi.total_sold, 10).toLocaleString());
            $('#kpiRevenue').text(formatCurrency(kpi.currency, kpi.total_revenue));

            var ASSETS = {
                nodataImg: "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg"
            };

            /* ── Donut chart ── */
            renderEchartDataUsageDonut('dataUsageDonut', resp.donut, ASSETS);

            /* ── Store rows & render first page ── */
            allPackRows = resp.packs || [];
            currentPage = 1;
            renderTable();
        },

        error: function() {
            $('#dataUsageDonut').html(
                '<div class="chart-loader" style="color:#e74c3c;">' +
                '<i class="icon-warning-sign"></i> Failed to load data.</div>'
            );
        }
    });
}

    /* ══════════════════════════════════════════════════════════════════
       Initial load
       ══════════════════════════════════════════════════════════════════ */
    /* ── Responsive resize for charts ── */
    window.addEventListener('resize', function () {
        var donutInst = echarts.getInstanceByDom(document.getElementById('donutChart'));
        var trendInst = echarts.getInstanceByDom(document.getElementById('trendChart'));
        if (donutInst) donutInst.resize();
		if (trendInst) trendInst.resize();
		var partnerInst = echarts.getInstanceByDom(document.getElementById('partnerRevenueChart'));
		if (partnerInst) partnerInst.resize();
	});

	

});
</script>

</body>
</html>
