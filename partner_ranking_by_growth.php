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
<!--[if IE 8]><html lang="en" class="ie8"><![endif]-->
<!--[if IE 9]><html lang="en" class="ie9"><![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title><?= $WIFILANTITLE ?> – Partner Ranking by Growth</title>
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
           REPORT HEADER  (matches reference rev-header pattern)
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
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Period select  (matches reference .du-period-select) */
        .du-period-select {
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #5a6a80;
            background: #fff;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            cursor: pointer;
            height: 34px;
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

        /* ═══════════════════════════════════════════
           OPERATOR MULTISELECT  (exact .cms-* mirror from reference)
        ═══════════════════════════════════════════ */
        .cms-outer-wrap {
            position: relative;
            min-width: 260px;
            width: 260px;
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
        .cms-trigger:hover  { border-color: #e87722; }
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
            left: 0; right: 0;
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
        .cms-dd-list { max-height: 220px; overflow-y: auto; flex: 1; }
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
        .cms-dd-item.sel   { background: #eef2ff; }
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

        /* ═══════════════════════════════════════════
           KPI BAR
        ═══════════════════════════════════════════ */
        .kpi-container {
            display: flex;
            background: #f7f8fb;
            border-radius: 10px;
            border: 1px solid #e6ebf2;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .kpi-item {
            flex: 1;
            text-align: center;
            padding: 18px 10px;
            position: relative;
        }
        .kpi-item:not(:last-child)::after {
            content: "";
            position: absolute;
            right: 0; top: 20%;
            height: 60%; width: 1px;
            background: #dfe5ec;
        }
        .kpi-title  { font-size: 14px; color: #6b7a90; margin-bottom: 6px; font-weight: 500; }
        .kpi-value  { font-size: 22px; font-weight: 600; color: #2c3e50; }
        .kpi-growth { color: #27ae60; font-weight: 600; }

        /* ═══════════════════════════════════════════
           TABLE CARD
        ═══════════════════════════════════════════ */
        .table-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 18px 20px;
            margin-bottom: 20px;
        }
        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .table-card-header h4 { margin: 0; font-size: 14px; font-weight: 600; color: #2c3e50; }
        .period-badge {
            font-size: 11px;
            font-weight: 500;
            background: #f0f4fa;
            color: #5a6a80;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* DataTable */
        #siteStatusTable {
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            table-layout: fixed;
            border: 1px solid #e2e8f0;
            width: 100% !important;
        }
        #siteStatusTable thead th {
            background: linear-gradient(to bottom, #f8fafc, #eef2f7);
            color: #5f6b7a;
            font-weight: 600;
            font-size: 13px;
            padding: 12px 10px;
            border-bottom: 1px solid #dfe5ec;
            border-right: 1px solid #e6ebf2;
        }
        #siteStatusTable thead th:last-child { border-right: none; }
        #siteStatusTable tbody td {
            border-top: 1px solid #edf1f5;
            border-right: 1px solid #e6ebf2;
            padding: 10px;
            font-size: 14px;
            color: #2c3e50;
        }
        #siteStatusTable tbody td:last-child { border-right: none; }
        #siteStatusTable tbody tr { height: 42px; }
        #siteStatusTable tbody tr:hover { background: #f9fbfd; }
        #siteStatusTable th:nth-child(1),
        #siteStatusTable td:nth-child(1) {
            width: 70px !important; max-width: 70px;
            text-align: center;
            padding-left: 4px; padding-right: 4px;
            font-weight: 600; font-size: 12px; color: #6b7a90;
        }
        #siteStatusTable td:nth-child(2) { font-weight: 600; }
        #siteStatusTable th:nth-child(3),
        #siteStatusTable th:nth-child(4),
        #siteStatusTable th:nth-child(5),
        #siteStatusTable th:nth-child(6) { text-align: right; }
        #siteStatusTable td:nth-child(3),
        #siteStatusTable td:nth-child(4),
        #siteStatusTable td:nth-child(5),
        #siteStatusTable td:nth-child(6) { text-align: right; padding-right: 15px; }

        div.dt-buttons { margin-left: 10px !important; }
        div.dataTables_length { margin-right: 10px !important; }
        .dt-buttons .dt-button { margin-right: 8px !important; border-radius: 6px; background: #f4f6f9; border: 1px solid #e0e6ed; }
        .dataTables_wrapper .dataTables_filter input { border-radius: 20px; border: 1px solid #e0e6ed; padding: 6px 12px; }
        .top-bar { display: flex; align-items: center; justify-content: space-between; }
        .dataTables_filter { order: 1; margin: 0 0 10px; }
        .dt-buttons { order: 2; margin-left: auto; }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "analytics";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "PartnerRankingByGrowth";
            include('../include/sidebar_temp.php');
        ?>

        <div class="page-content">
            <div class="container-fluid">

                <!-- Breadcrumb -->
                <div style="margin-top:12px;">
                    <?php include('../include/style-customizer.php'); ?>
                    <ul class="breadcrumb" style="margin:0;">
                        <li>
                            <i class="icon-home"></i>
                            <a href="<?=$DASHBOARDPATH?>"><?=$labelHome?></a>
                            <i class="icon-angle-right"></i>
                            <a href="#">Analytics</a>
                            <i class="icon-angle-right"></i>
                            <a href="#">Revenue</a>
                            <i class="icon-angle-right"></i>
                            <span>Partner Ranking by Growth</span>
                        </li>
                    </ul>
                </div>
                <br>

                <!-- ════ REPORT HEADER ════ -->
                <div class="rev-header breadcrumb">

                    <!-- Left: title + date subtitle -->
                    <div class="rev-header-left">
                        <h3>
                            <i class="icon-signal" style="color:#e87722;"></i>
                            Partner Ranking by Growth
                        </h3>
                        <div class="rev-subtitle">
                            <i class="icon-calendar"></i>
                            <span id="dateRangeText"><?= $formattedStart . ' – ' . $formattedEnd ?></span>
                        </div>
                    </div>

                    <!-- Right: operator multiselect + period select -->
                    <div class="rev-header-right">

                        <!-- Operator multiselect (mirrors reference .cms-* widget) -->
                        <div class="cms-outer-wrap" id="cmsWrap">
                            <div class="cms-trigger" id="cmsTrigger">
                                <div class="cms-tags-row" id="cmsTagsRow">
                                    <span class="cms-placeholder" id="cmsPlaceholder">All Operators</span>
                                </div>
                                <span class="cms-count-badge" id="cmsCountBadge" style="display:none;"></span>
                                <span class="cms-arrow" id="cmsArrow">▼</span>
                            </div>
                            <div class="cms-dropdown" id="cmsDropdown">
                                <div class="cms-dd-search">
                                    <input type="text" id="cmsSearch" placeholder="Search operators…" autocomplete="off">
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

                        <!-- Period select + optional daterangepicker (matches reference) -->
                        <div class="du-filter-wrap">
                            <!--select id="periodSelect" class="du-period-select">
                                <option value="today">Today</option>
                                <option value="7"  <?= $period == '7'  ? 'selected' : '' ?>>Last 7 Days</option>
                                <option value="30" <?= $period == '30' ? 'selected' : '' ?>>Last 30 Days</option>
                                <option value="custom">Custom Range</option>
                            </select-->
                            <div id="customDateWrap" style="display:none; margin-top:6px;">
                                <button type="button" id="reportrange" class="du-btn-daterange">
                                    <i class="icon-calendar"></i>
                                    <span>Select date range</span>
                                    <i class="icon-angle-down" style="margin-left:4px;font-size:10px;"></i>
                                </button>
                            </div>
                        </div>

                    </div><!-- /rev-header-right -->
                </div><!-- /rev-header -->

                <!-- Period info line -->
                <div id="reportPeriodContainer" style="font-size:12px;color:#6b7a90;margin-bottom:14px;"></div>

                <!-- ════ KPI ════ -->
                <div class="kpi-container">
                    <div class="kpi-item">
                        <div class="kpi-title">Total Partners</div>
                        <div class="kpi-value" id="kpiTotalPartners">--</div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-title">Highest Growth Partner</div>
                        <div class="kpi-value" id="kpiTopPartner">--</div>
                    </div>
                    <div class="kpi-item">
                        <div class="kpi-title">Highest Revenue Growth</div>
                        <div class="kpi-value" id="kpiRevenueGrowth">--</div>
                    </div>
                </div>

                <!-- ════ Table ════ -->
                <div class="table-card">
                    <div class="table-card-header">
                        <h4 id="tableTitle">
                            <i class="icon-table" style="margin-right:6px;color:#e87722;"></i>
                            Partner Growth Ranking (Week vs W-1)
                        </h4>
                        <span class="period-badge" id="periodBadge">Weekly</span>
                    </div>
                    <table id="siteStatusTable" class="table table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Partner</th>
                                <th>Revenue (Current Week)</th>
                                <th>Revenue (W-1)</th>
                                <th>Change</th>
                                <th>Growth %</th>
                            </tr>
                        </thead>
                        <tbody id="siteTableBody">
                            <tr>
                                <td colspan="6" class="text-center" style="padding:30px;color:#8a97a8;">
                                    <i class="icon-spinner icon-spin"></i> Loading data…
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

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
/* ════════════════════════════════════════════════
   GLOBALS
════════════════════════════════════════════════ */
var currentPeriod       = <?= json_encode($period) ?>;
var currentFrom         = null;
var currentTo           = null;
var selectedOperatorIds = [];
var allOperators        = [];

/* ════════════════════════════════════════════════
   DOCUMENT READY
════════════════════════════════════════════════ */
jQuery(document).ready(function ($) {

    UIJQueryUI.init();
    FormSamples.init();

    /* ── Daterangepicker (used when period = custom) ── */
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
                            // moment().subtract(1,'month').endOf('month')]
        }
    }, function (start, end) {
        $('#reportrange span').html(
            start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY')
        );
        currentFrom = start.format('YYYY-MM-DD');
        currentTo   = end.format('YYYY-MM-DD');
        updateDateSubtitle('custom', currentFrom, currentTo);
        loadReportData();
    });

    $('#reportrange span').html(
        moment().subtract(6,'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ── Period select ── */
  //  $('#periodSelect').on('change', function () {
        //currentPeriod = $(this).val();
        currentPeriod = 'custom';

        if (currentPeriod === 'custom') {
            $('#customDateWrap').show();
            var drp = $('#reportrange').data('daterangepicker');
            currentFrom = drp.startDate.format('YYYY-MM-DD');
            currentTo   = drp.endDate.format('YYYY-MM-DD');
        } else {
            $('#customDateWrap').hide();
            currentFrom = null;
            currentTo   = null;
        }

        var badgeMap = { today: 'Today', '7': 'Weekly', '30': 'Monthly', custom: 'Custom' };
        $('#periodBadge').text(badgeMap[currentPeriod] || currentPeriod);

        updateDateSubtitle(currentPeriod, currentFrom, currentTo);
        loadReportData();
   // });

    /* ── Initial load ── */
    initOperatorMultiselect();   /* loads operators then fires first loadReportData() */

}); // end ready

/* ════════════════════════════════════════════════
   DATE SUBTITLE  (mirrors reference updateComparisonLabels)
════════════════════════════════════════════════ */
function updateDateSubtitle(period, fromDate, toDate) {
    var subtitleText, periodLabel, col2, col3;

    if (period === 'today') {
        subtitleText = moment().format('DD MMM YYYY');
        periodLabel  = 'Today vs Yesterday';
        col2 = 'Revenue (Today)';
        col3 = 'Revenue (Yesterday)';
    } else if (period === 'custom' && fromDate && toDate) {
        subtitleText = moment(fromDate).format('DD MMM YYYY') + ' – ' + moment(toDate).format('DD MMM YYYY');
        periodLabel  = 'Custom Range';
        col2 = 'Revenue (Selected)';
        col3 = 'Revenue (Prior Period)';
    } else if (period === '30') {
        subtitleText = moment().subtract(29,'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY');
        periodLabel  = 'Month vs M-1';
        col2 = 'Revenue (Current Month)';
        col3 = 'Revenue (M-1)';
    } else {
        /* default: 7 days */
        subtitleText = moment().subtract(6,'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY');
        periodLabel  = 'Week vs W-1';
        col2 = 'Revenue (Current Week)';
        col3 = 'Revenue (W-1)';
    }

    jQuery('#dateRangeText').text(subtitleText);
    jQuery('#tableTitle').html(
        '<i class="icon-table" style="margin-right:6px;color:#e87722;"></i>' +
        'Partner Growth Ranking (' + periodLabel + ')'
    );
    jQuery('#siteStatusTable thead th').eq(2).text(col2);
    jQuery('#siteStatusTable thead th').eq(3).text(col3);
    jQuery('#reportPeriodContainer').html('<strong>Report Period:</strong> ' + subtitleText);
}

/* ════════════════════════════════════════════════
   BUILD AJAX PARAMS
════════════════════════════════════════════════ */
function buildParams(action) {
    var params = { action: action, period: currentPeriod };
    if (currentPeriod === 'custom' && currentFrom && currentTo) {
        params.from_date = currentFrom;
        params.to_date   = currentTo;
    }
    params.operatorIds = selectedOperatorIds;   /* jQuery serialises arrays */
    return params;
}

/* ════════════════════════════════════════════════
   LOAD REPORT DATA  (KPI + Table)
════════════════════════════════════════════════ */
function loadReportData() {

    jQuery('#kpiTotalPartners, #kpiTopPartner, #kpiRevenueGrowth').text('…');

    jQuery.ajax({
        url     : './datatables-scripts/partner_growth_report.php',
        type    : 'POST',
        data    : buildParams('getData'),
        dataType: 'json',

        success: function (resp) {
            if (!resp || resp.status !== 'success') {
                console.error('API error:', resp);
                return;
            }

            var kpi = resp.kpi;

            /* ── KPI Cards ── */
            jQuery('#kpiTotalPartners').text(numberFormat(kpi.total_partners));

            if (kpi.highest_growth_pct !== '') {
                jQuery('#kpiTopPartner').html(
                    cmsEsc(kpi.highest_growth_partner) +
                    ' <span class="kpi-growth">(+' + kpi.highest_growth_pct + '%)</span>'
                );
            } else {
                jQuery('#kpiTopPartner').text('N/A');
            }

            jQuery('#kpiRevenueGrowth').text('$' + numberFormat(kpi.highest_revenue_growth));

            /* ── DataTable ── */
            if (jQuery.fn.DataTable.isDataTable('#siteStatusTable')) {
                jQuery('#siteStatusTable').DataTable().destroy();
                jQuery('#siteTableBody').empty();
            }

            jQuery('#siteStatusTable').DataTable({
                bServerSide : false,
                autoWidth   : false,
                data        : resp.table,
                dom         : '<"top-bar"fB>rtip',
                lengthChange: false,
                order       : [[5, 'desc']],
                columnDefs  : [{ targets: 0, className: 'text-center', width: '50px' }],

                buttons: [{
                    text  : '<i class="icon-download-alt"></i> Export CSV',
                    action: function () { exportPartnerGrowthCsv(); }
                }],

                columns: [
                    { data: 'rank' },
                    { data: 'partner' },
                    {
                        data  : 'revenue_current',
                        render: function (d) { return '$' + parseFloat(d).toLocaleString(); }
                    },
                    {
                        data  : 'revenue_previous',
                        render: function (d) { return '$' + parseFloat(d).toLocaleString(); }
                    },
                    {
                        data  : 'change_value',
                        render: function (d) {
                            var v = parseFloat(d), pos = v >= 0;
                            return '<span style="color:' + (pos ? '#27ae60' : '#e74c3c') + ';font-weight:600;">'
                                + (pos ? '+' : '-') + '$' + Math.abs(v).toLocaleString()
                                + ' ' + (pos ? '▲' : '▼') + '</span>';
                        }
                    },
                    {
                        data  : 'growth_pct',
                        render: function (d) {
                            var v = parseFloat(d), pos = v >= 0;
                            return '<span style="color:' + (pos ? '#27ae60' : '#e74c3c') + ';font-weight:600;">'
                                + (pos ? '+' : '') + v + '%</span>';
                        }
                    }
                ]
            });
        },

        error: function (xhr, status, err) {
            console.error('AJAX error:', status, err);
            jQuery('#siteTableBody').html(
                '<tr><td colspan="6" class="text-center" style="padding:30px;color:#e74c3c;">' +
                '<i class="icon-warning-sign"></i> Failed to load data. Please refresh.</td></tr>'
            );
        }
    });
}

/* ════════════════════════════════════════════════
   CSV EXPORT
════════════════════════════════════════════════ */
function exportPartnerGrowthCsv() {
    var dt   = jQuery('#siteStatusTable').DataTable();
    var data = dt.rows({ search: 'applied', order: 'applied' }).data().toArray();

    var $form = jQuery('<form>', {
        method: 'POST',
        action: './datatables-scripts/partner_growth_report.php',
        target: '_self'
    });

    $form.append(jQuery('<input>', { type: 'hidden', name: 'action',    value: 'exportCsv' }));
    $form.append(jQuery('<input>', { type: 'hidden', name: 'period',    value: currentPeriod }));
    if (currentFrom) $form.append(jQuery('<input>', { type: 'hidden', name: 'from_date', value: currentFrom }));
    if (currentTo)   $form.append(jQuery('<input>', { type: 'hidden', name: 'to_date',   value: currentTo }));
    $form.append(jQuery('<input>', { type: 'hidden', name: 'tableData', value: JSON.stringify(data) }));
    jQuery.each(selectedOperatorIds, function (i, id) {
        $form.append(jQuery('<input>', { type: 'hidden', name: 'operatorIds[]', value: id }));
    });

    jQuery('body').append($form);
    $form.submit();
    $form.remove();
}

/* ════════════════════════════════════════════════
   HELPERS
════════════════════════════════════════════════ */
function numberFormat(n) {
    return parseFloat(n).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function cmsEsc(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ════════════════════════════════════════════════
   OPERATOR MULTISELECT
   Exact mirror of the reference .cms-* logic used
   for "Customer Multiselect" in revenue_distribution
════════════════════════════════════════════════ */
function initOperatorMultiselect() {

    /* Fetch operator list */
    jQuery.ajax({
        url     : './datatables-scripts/partner_growth_report.php',
        type    : 'POST',
        data    : { action: 'getOperators' },
        dataType: 'json',
        success : function (src) {
            var json;
            try { json = (typeof src === 'string') ? JSON.parse(src) : src; }
            catch (e) { json = { status: 'error' }; }

            if (json.status === 'success' && json.operators && json.operators.length) {
                allOperators = json.operators.map(function (o) {
                    return { id: o.OperatorId, name: o.AccountName };
                });
                renderCmsList(allOperators);
            } else {
                jQuery('#cmsList').html('<div class="cms-dd-empty">No operators found.</div>');
            }

            /* Fire first data load AFTER operators are ready */
            updateDateSubtitle(currentPeriod, null, null);
            loadReportData();
        }
    });

    /* Toggle dropdown */
    jQuery('#cmsTrigger').on('click', function (e) {
        e.stopPropagation();
        jQuery('#cmsDropdown').hasClass('open') ? closeCms() : openCms();
    });

    /* Close on outside click */
    jQuery(document).on('click', function (e) {
        if (!jQuery(e.target).closest('#cmsWrap').length) closeCms();
    });

    /* Search */
    jQuery('#cmsSearch').on('input', function () {
        var q = jQuery(this).val().toLowerCase();
        renderCmsList(allOperators.filter(function (o) {
            return o.name.toLowerCase().indexOf(q) !== -1;
        }));
    });

    /* Select All */
    jQuery('#cmsSelectAll').on('click', function () {
        var q       = jQuery('#cmsSearch').val().toLowerCase();
        var visible = q
            ? allOperators.filter(function (o) { return o.name.toLowerCase().indexOf(q) !== -1; })
            : allOperators;
        visible.forEach(function (o) {
            if (selectedOperatorIds.indexOf(o.id) === -1) selectedOperatorIds.push(o.id);
        });
        renderCmsList(visible);
        updateCmsTrigger();
    });

    /* Clear All */
    jQuery('#cmsClearAll').on('click', function () {
        selectedOperatorIds = [];
        var q = jQuery('#cmsSearch').val().toLowerCase();
        renderCmsList(q
            ? allOperators.filter(function (o) { return o.name.toLowerCase().indexOf(q) !== -1; })
            : allOperators
        );
        updateCmsTrigger();
    });

    /* Apply → refresh data */
    jQuery('#cmsApply').on('click', function () {
        closeCms();
        loadReportData();
    });
}

function openCms() {
    jQuery('#cmsDropdown').addClass('open');
    jQuery('#cmsTrigger').addClass('active');
    jQuery('#cmsArrow').addClass('open');
    jQuery('#cmsSearch').val('');
    renderCmsList(allOperators);
    setTimeout(function () { jQuery('#cmsSearch').focus(); }, 50);
}

function closeCms() {
    jQuery('#cmsDropdown').removeClass('open');
    jQuery('#cmsTrigger').removeClass('active');
    jQuery('#cmsArrow').removeClass('open');
}

function renderCmsList(operators) {
    if (!operators || !operators.length) {
        jQuery('#cmsList').html('<div class="cms-dd-empty">No operators found.</div>');
        return;
    }
    var html = '';
    operators.forEach(function (o) {
        var sel = selectedOperatorIds.indexOf(o.id) !== -1;
        html += '<div class="cms-dd-item' + (sel ? ' sel' : '') + '" data-id="' + o.id + '">' +
                '<input type="checkbox"' + (sel ? ' checked' : '') + '>' +
                '<span>' + cmsEsc(o.name) + '</span>' +
                '</div>';
    });
    jQuery('#cmsList').html(html);

    jQuery('#cmsList .cms-dd-item').on('click', function () {
        var id  = jQuery(this).data('id');
		var idx = selectedOperatorIds.indexOf(id);
        if (idx === -1) {
            selectedOperatorIds.push(id);
            jQuery(this).addClass('sel').find('input').prop('checked', true);
        } else {
            selectedOperatorIds.splice(idx, 1);
            jQuery(this).removeClass('sel').find('input').prop('checked', false);
        }
        updateCmsTrigger();
    });
}

function updateCmsTrigger() {
    var $row   = $('#cmsTagsRow');
    var $ph    = $('#cmsPlaceholder');
    var $badge = $('#cmsCountBadge');

    $row.find('.cms-tag').remove();

    if (selectedOperatorIds.length === 0) {
        $ph.show();
        $badge.hide().text('');
        return;
    }

    $ph.hide();
    $badge.text(selectedOperatorIds.length).show();

    selectedOperatorIds.slice(0, 2).forEach(function (id) {
        var op = allOperators.find(function (o) { return o.id == id; });
        if (!op) return;
        var $tag = jQuery(
            '<span class="cms-tag">' + cmsEsc(op.name) +
            ' <span class="cms-tag-x" data-id="' + id + '">×</span></span>'
        );
        $row.append($tag);
    });

    if (selectedOperatorIds.length > 2) {
        $row.append('<span class="cms-tag">+' + (selectedOperatorIds.length - 2) + ' more</span>');
    }

    /* × button on individual tags */
    $row.find('.cms-tag-x').on('click', function (e) {
        e.stopPropagation();
        var id  = parseInt($(this).data('id'), 10);
        var idx = selectedOperatorIds.indexOf(id);
        if (idx !== -1) selectedOperatorIds.splice(idx, 1);
        updateCmsTrigger();
        if ($('#cmsDropdown').hasClass('open')) {
            var q = $('#cmsSearch').val().toLowerCase();
            renderCmsList(q
                ? allOperators.filter(function (o) { return o.name.toLowerCase().indexOf(q) !== -1; })
                : allOperators
            );
        }
    });
}
</script>

</body>
</html>
