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
    <title><?= $WIFILANTITLE ?> – Active / Inactive Sites</title>
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
.daterangepicker {
    min-width: 600px !important;
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
/* Divider between months */
.daterangepicker .drp-calendar.left {
    border-right: 1px solid #dcdcdc;
    margin-right: 20px;
    padding-right: 32px !important;
}

.daterangepicker .drp-calendar.right {
    padding-left: 12px !important;
}

        /* ── Report-level overrides (inherits Indio theme) ── */
        .ais-report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .ais-report-header h3 {
            margin: 0;
            font-size: 18px;
            color: #2c3e50;
        }

        /* Period toggle */
        .period-toggle {
            display: flex;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            overflow: hidden;
        }
        .period-toggle a {
            padding: 7px 18px;
            font-size: 13px;
            font-weight: 500;
            color: #5a6a80;
            text-decoration: none;
            background: #fff;
            transition: background .18s, color .18s;
        }
        .period-toggle a.active,
        .period-toggle a:hover {
            background: #e87722;
            color: #fff;
        }

        /* KPI cards row */
        .kpi-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .kpi-card {
            flex: 1;
            min-width: 160px;
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            padding: 18px 22px 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .kpi-card.total::before    { background: #5b6af0; }
        .kpi-card.active::before   { background: #27ae60; }
        .kpi-card.inactive::before { background: #e74c3c; }

        .kpi-card .kpi-label {
            font-size: 12px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 6px;
        }
        .kpi-card .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e2b3c;
            line-height: 1.1;
        }
        .kpi-card .kpi-wow {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 6px;
            padding: 2px 8px;
            border-radius: 20px;
        }
        .kpi-wow.up      { color: #1a9e4c; background: #e6f9ee; }
        .kpi-wow.down    { color: #c0392b; background: #fdecea; }
        .kpi-wow.neutral { color: #7f8c8d; background: #f0f0f0; }

        /* Charts grid */
        .charts-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .chart-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 18px 20px;
        }
        .chart-card.donut-card { flex: 0 0 340px; min-width: 300px; }
        .chart-card.trend-card { flex: 1; min-width: 300px; }
        .chart-card .chart-title {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-card .chart-title span.badge-pill {
            font-size: 11px;
            font-weight: 500;
            background: #f0f4fa;
            color: #5a6a80;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* Donut legend */
        .donut-legend {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 10px;
        }
        .donut-legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #4a5568;
            font-weight: 500;
        }
        .legend-dot {
            width: 11px; height: 11px;
            border-radius: 50%;
            display: inline-block;
        }
        .legend-dot.active   { background: #27ae60; }
        .legend-dot.inactive { background: #e74c3c; }

        /* Alert banner */
        .site-alert-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fffbec;
            border: 1px solid #f5c842;
            border-radius: 8px;
            padding: 11px 16px;
            font-size: 13px;
            color: #7a5c00;
            margin-bottom: 20px;
        }
        .site-alert-banner i { color: #f0ad4e; font-size: 16px; }

        /* DataTable card */
        .table-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 18px 20px;
            margin-bottom: 20px;
        }
        .table-card .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .table-card .table-card-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Status badges */
        .badge-active   { background: #e6f9ee; color: #1a9e4c; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-inactive { background: #fdecea; color: #c0392b; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        /* Revenue col */
        td.revenue-col { font-weight: 600; color: #2c3e50; }

        /* ECharts containers */
        #donutChart { width: 100%; height: 220px; }
        #trendChart { width: 100%; height: 240px; }

        /* Skeleton / loader */
        .kpi-skeleton {
            background: linear-gradient(90deg, #f0f3f8 25%, #e4e9f0 50%, #f0f3f8 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 4px;
            display: inline-block;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .chart-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #8a97a8;
            font-size: 13px;
            gap: 8px;
		}
/* Status filter toggle */
.status-filter-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
.status-filter-wrap .filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #8a97a8;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}
.status-filter {
    display: flex;
    border: 1px solid #dde3ec;
    border-radius: 6px;
    overflow: hidden;
}
.status-filter .sf-btn {
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #5a6a80;
    background: #fff;
    border: none;
    cursor: pointer;
    transition: background .15s, color .15s;
    line-height: 1.6;
}
.status-filter .sf-btn + .sf-btn { border-left: 1px solid #dde3ec; }
.status-filter .sf-btn.sf-active     { background: #e87722; color: #fff; }
.status-filter .sf-btn.sf-active-green { background: #27ae60; color: #fff; }
.status-filter .sf-btn.sf-active-red   { background: #e74c3c; color: #fff; }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "analytics";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "ActiveInactiveSites";
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
                                <a href="#">Revenue</a>
                                <i class="icon-angle-right"></i>
                                <span>Active / Inactive Sites</span>
                            </li>
                        </ul>
                    </div>
                </div>

				<!-- Page Header -->
                <div class="breadcrumb ais-report-header">
					<h3><i class="icon-signal" style="color:#e87722;margin-right:8px;"></i>Total Active vs Inactive Sites</h3>
					<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
						<select id="periodSelect"
						        style="height:32px;padding:0 28px 0 10px;font-size:12px;font-weight:600;
						               color:#3d5166;background:#fff url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\'%3E%3Cpath d=\'M0 0l5 6 5-6z\' fill=\'%237a8da0\'/%3E%3C/svg%3E') no-repeat right 9px center;
						               border:1px solid #dde3ec;border-radius:7px;appearance:none;-webkit-appearance:none;
						               cursor:pointer;min-width:160px;">
							<option value="7"  <?= $period=='7'  ? 'selected':'' ?>>Last 7 Days</option>
							<option value="30" <?= $period=='30' ? 'selected':'' ?>>Last 30 Days</option>
							<option value="custom" <?= $period==='custom' ? 'selected':'' ?>>Custom Range</option>
						</select>
						<!-- Custom daterangepicker — shown only when Custom Range is selected -->
						<div id="customRangeWrap" style="display:none;align-items:center;gap:6px;">
							<input type="text" id="customRangeInput" readonly
							       style="height:32px;padding:0 10px;font-size:12px;font-weight:600;
							              color:#3d5166;border:1px solid #dde3ec;border-radius:7px;
							              background:#fff;cursor:pointer;min-width:210px;"
							       placeholder="Select date range…" />
							<span id="dateRangeMsg" style="font-size:11px;color:#9aabb8;">Max 90 days</span>
						</div>
					</div>
				</div>

                <!-- KPI Cards -->
                <div class="kpi-row">
                    <div class="kpi-card total">
                        <div class="kpi-label">Total Sites</div>
                        <div class="kpi-value" id="kpiTotal">
                            <span class="kpi-skeleton" style="width:60px;height:32px;">&nbsp;</span>
                        </div>
                    </div>
                    <div class="kpi-card active">
                        <div class="kpi-label">Active Sites</div>
                        <div class="kpi-value" id="kpiActive">
                            <span class="kpi-skeleton" style="width:60px;height:32px;">&nbsp;</span>
                        </div>
                        <div id="kpiActiveWow"></div>
                    </div>
                    <div class="kpi-card inactive">
                        <div class="kpi-label">Inactive Sites</div>
                        <div class="kpi-value" id="kpiInactive">
                            <span class="kpi-skeleton" style="width:60px;height:32px;">&nbsp;</span>
                        </div>
                        <div id="kpiInactiveWow"></div>
                    </div>
                </div>

                <!-- Alert Banner (hidden until data loads) -->
                <div id="alertBanner" style="display:none;" class="site-alert-banner">
                    <i class="icon-warning-sign"></i>
                    <strong>Inactive Site Alert:</strong>&nbsp;
                    <span id="alertBannerText"></span>
                </div>

                <!-- Charts Row -->
                <div class="charts-row">

                    <!-- Donut Chart -->
                    <div class="chart-card donut-card">
                        <div class="chart-title">
                            Site Distribution
                            <span class="badge-pill" id="periodBadge"><?= $period == 7 ? 'Weekly' : 'Monthly' ?></span>
                        </div>
                        <div id="donutChart">
                            <div class="chart-loader">
                                <i class="icon-spinner icon-spin"></i> Loading…
                            </div>
                        </div>
                        <div class="donut-legend">
                            <div class="donut-legend-item">
                                <span class="legend-dot active"></span> Active Sites
                            </div>
                            <div class="donut-legend-item">
                                <span class="legend-dot inactive"></span> Inactive Sites
                            </div>
                        </div>
                    </div>

                    <!-- Trend Line Chart -->
                    <div class="chart-card trend-card">
                        <div class="chart-title">
                            Active vs Inactive – Daily Trend
                            <span class="badge-pill" id="trendBadge">Last <?= $period ?> Days</span>
                        </div>
                        <div id="trendChart">
                            <div class="chart-loader">
                                <i class="icon-spinner icon-spin"></i> Loading…
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Site Detail Table -->
                <div class="table-card">
<div class="table-card-header">
    <h4><i class="icon-table" style="margin-right:6px;color:#e87722;"></i>Site-Level Commercial Status</h4>
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <!-- Status Filter -->
        <div class="status-filter-wrap">
            <span class="filter-label">Status:</span>
            <div class="status-filter" id="statusFilterBtns">
                <button class="sf-btn sf-active" data-status="">All</button>
                <button class="sf-btn" data-status="Active">&#10003; Active</button>
                <button class="sf-btn" data-status="Inactive">&#10007; Inactive</button>
            </div>
        </div>
        <!-- Count badges -->
        <div>
            <span class="badge-active"  id="badgeActive"  style="margin-right:6px;">– Active</span>
            <span class="badge-inactive" id="badgeInactive">– Inactive</span>
        </div>
    </div>
</div>					
					<table id="siteStatusTable" class="table table-striped table-hover table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Site Name</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Country</th>
                                <th>Revenue (<span id="currencySymbol"><?= htmlspecialchars($_SESSION['currency']) ?></span>)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="siteTableBody">
                            <tr>
                                <td colspan="7" class="text-center" style="padding:30px;color:#8a97a8;">
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
    <!-- moment + daterangepicker must load BEFORE the inline report script -->
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/moment.min.js"></script>
    <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.min.js"></script>

<script>
jQuery(document).ready(function () {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();
    var currentStatusFilter = '';
    var customDateFrom      = '';   // YYYY-MM-DD
    var customDateTo        = '';   // YYYY-MM-DD
    var currentPeriod       = <?= json_encode($period) ?>;

/* ── Status filter buttons ── */
$(document).on('click', '#statusFilterBtns .sf-btn', function () {
    var status = $(this).data('status');

    $('#statusFilterBtns .sf-btn').removeClass('sf-active sf-active-green sf-active-red');
    if      (status === 'Active')   $(this).addClass('sf-active-green');
    else if (status === 'Inactive') $(this).addClass('sf-active-red');
    else                            $(this).addClass('sf-active');

    currentStatusFilter = status;

    if ($.fn.DataTable.isDataTable('#siteStatusTable')) {
        $('#siteStatusTable').DataTable().ajax.reload();
    }
});


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
function exportSiteStatusCsv(period) {
    var dt       = $('#siteStatusTable').DataTable();
    var settings = dt.settings()[0];
 
    /* Grab current search + sort state from DataTables internals */
    var search   = settings.oPreviousSearch ? settings.oPreviousSearch.sSearch : '';
    var sortCol  = settings.aaSorting && settings.aaSorting[0] ? settings.aaSorting[0][0] : 5;
    var sortDir  = settings.aaSorting && settings.aaSorting[0] ? settings.aaSorting[0][1] : 'desc';
 
    /* Build and submit a hidden form — PHP streams CSV back as download */
    var formFields = [
        $('<input>', { type: 'hidden', name: 'action',       value: 'exportCsvData' }),
        $('<input>', { type: 'hidden', name: 'sSearch',      value: search }),
        $('<input>', { type: 'hidden', name: 'iSortCol_0',   value: sortCol }),
        $('<input>', { type: 'hidden', name: 'sSortDir_0',   value: sortDir }),
        $('<input>', { type: 'hidden', name: 'statusFilter', value: currentStatusFilter })
    ];

    if (period === 'custom' && customDateFrom && customDateTo) {
        formFields.push($('<input>', { type: 'hidden', name: 'dateFrom', value: customDateFrom }));
        formFields.push($('<input>', { type: 'hidden', name: 'dateTo',   value: customDateTo }));
    } else {
        formFields.push($('<input>', { type: 'hidden', name: 'period', value: period }));
    }

    var $form = $('<form>', {
        method : 'POST',
        action : './datatables-scripts/get_active_inactive_site_data.php',
        target : '_self'
    }).append(formFields);
 
    $('body').append($form);
    $form.submit();
    $form.remove();
}
 
function loadReportData(period) {
    currentPeriod = period;

    var ajaxData = { action: 'getReportData' };
    if (period === 'custom' && customDateFrom && customDateTo) {
        ajaxData.dateFrom = customDateFrom;
        ajaxData.dateTo   = customDateTo;
    } else {
        ajaxData.period = period;
    }
 
    $.ajax({
        url:      './datatables-scripts/get_active_inactive_site_data.php',
        type:     'POST',
        data:     ajaxData,
        dataType: 'json',
 
        success: function(resp) {
            if (!resp || resp.status !== 'success') {
                console.error('API error:', resp);
                return;
            }
 
            var kpi = resp.kpi;
 
            /* ── KPI Cards ── */
            $('#kpiTotal').text(numberFormat(kpi.total_sites));
            $('#kpiActive').text(numberFormat(kpi.active_sites));
            $('#kpiInactive').text(numberFormat(kpi.inactive_sites));
            $('#kpiActiveWow').html(wowBadge(kpi.active_wow,   false));
            $('#kpiInactiveWow').html(wowBadge(kpi.inactive_wow, true));
 
            /* ── Period badges ── */
            if (kpi.is_custom_range) {
                $('#periodBadge').text(kpi.date_from + ' → ' + kpi.date_to);
                $('#trendBadge').text(kpi.date_from + ' → ' + kpi.date_to);
            } else {
                $('#periodBadge').text(kpi.period == 1 ? 'Today' : kpi.period == 7 ? 'Weekly' : 'Monthly');
                $('#trendBadge').text(kpi.period == 1 ? 'Today' : 'Last ' + kpi.period + ' Days');
            }
 
            /* ── Table header badges ── */
            $('#badgeActive').text(kpi.active_sites   + ' Active');
            $('#badgeInactive').text(kpi.inactive_sites + ' Inactive');
 
            /* ── Alert Banner ── */
            if (kpi.inactive_sites > 0) {
                var plural = kpi.inactive_sites > 1 ? 's' : '';
                var rangeLabel = kpi.is_custom_range
                    ? kpi.date_from + ' to ' + kpi.date_to
                    : 'the last ' + kpi.period + ' days';
                $('#alertBannerText').text(
                    kpi.inactive_sites + ' site' + plural +
                    ' with zero revenue in ' + rangeLabel +
                    ' • Field intervention recommended'
                );
                $('#alertBanner').show();
            } else {
                $('#alertBanner').hide();
            }
 
            var ASSETS = {
                loadingGif : "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/ajax-loading.gif",
                nodataImg  : "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg"
            };
 
            /* ── Donut chart ── */
            renderEchartPieSiteStatus('donutChart', resp.pie, ASSETS);
 
            /* ── Line chart ── */
            var lineData = resp.line;
            lineData.chartConfig = lineData.chartConfig || {};
            lineData.chartConfig.xAxis = {
                type         : 'category',
                boundaryGap  : false,
                axisLabel    : {
                    interval : (kpi.period <= 7) ? 0 : 2,
                    rotate   : (kpi.period <= 7) ? 0 : 45,
                    fontSize : 11,
                    color    : '#8a97a8'
                }
            };
            renderEchart('trendChart', lineData, ASSETS);
 
            /* ── Server-side DataTable ── */
            if ($.fn.DataTable.isDataTable('#siteStatusTable')) {
                $('#siteStatusTable').DataTable().destroy();
                $('#siteTableBody').empty();
            }
 
            $('#siteStatusTable').dataTable({
                "scrollX"       : true,
                "sDom"          : 'lrBtip',
                "buttons"       : [
                    {
                        text: '<i class="icon-download-alt"></i> Export CSV',
                        action: function() {
                            exportSiteStatusCsv(currentPeriod);
                        }
                    }
                ],
                "aoColumnDefs"  : [
                    { "bSortable": false, "aTargets": [0, 6] }
                ],
                "aaSorting"     : [[5, 'desc']],
                "aLengthMenu"   : [
                    [<?= $recordsperpage[0] ?>, <?= $recordsperpage[1] ?>, <?= $recordsperpage[2] ?>, <?= $recordsperpage[3] ?>],
                    [<?= $recordsperpage[0] ?>, <?= $recordsperpage[1] ?>, <?= $recordsperpage[2] ?>, <?= $recordsperpage[3] ?>]
                ],
                "iDisplayLength": <?= $maxpagesize ?>,
                "pagingType"    : "full_numbers",
                "bProcessing"   : true,
                "bServerSide"   : true,
                "sAjaxSource"   : "datatables-scripts/get_active_inactive_site_data.php",
 
                "fnServerParams": function(aoData) {
                    aoData.push({ "name": "action",       "value": "getTableData" });
                    aoData.push({ "name": "statusFilter", "value": currentStatusFilter });
                    if (currentPeriod === 'custom' && customDateFrom && customDateTo) {
                        aoData.push({ "name": "dateFrom", "value": customDateFrom });
                        aoData.push({ "name": "dateTo",   "value": customDateTo });
                    } else {
                        aoData.push({ "name": "period",   "value": currentPeriod });
                    }
                },
 
                "fnServerData"  : fnDataTablesPipeline,
 
                "fnDrawCallback": function(oSettings) {
                    var that = this;
                    if (oSettings.bSorted || oSettings.bFiltered) {
                        this.$('td:first-child', { "filter": "applied" }).each(function(i) {
                            that.fnUpdate(
                                oSettings._iDisplayStart + i + 1,
                                this.parentNode, 0, false, false
                            );
                        });
                    }
                },
 
                "language": {
                    "search"            : '',
                    "searchPlaceholder" : 'Search sites...',
                    "lengthMenu"        : 'Show _MENU_ sites',
                    "info"              : 'Showing _START_ to _END_ of _TOTAL_ sites',
                    "paginate"          : { "previous": "&laquo;", "next": "&raquo;" }
                }
            });
        },
 
        error: function(xhr, status, err) {
            console.error('AJAX error:', status, err);
            $('#donutChart, #trendChart').html(
                '<div class="chart-loader" style="color:#e74c3c;">' +
                '<i class="icon-warning-sign"></i> Failed to load data.</div>'
            );
            $('#siteTableBody').html(
                '<tr><td colspan="7" class="text-center" style="padding:30px;color:#e74c3c;">' +
                '<i class="icon-warning-sign"></i> Failed to load data. Please refresh.</td></tr>'
            );
        }
 
    });
}


    /* ══════════════════════════════════════════════════════════════════
       Period dropdown + custom daterangepicker
       ══════════════════════════════════════════════════════════════════ */
    (function initDateRangePicker() {
        var $select  = $('#periodSelect');
        var $wrap    = $('#customRangeWrap');
        var $input   = $('#customRangeInput');
        var $msg     = $('#dateRangeMsg');
        var MAX_DAYS = 90;

        /* Init daterangepicker */
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
            loadReportData('custom');
        });

        $input.on('cancel.daterangepicker', function() {
            $select.val('7').trigger('change');
        });

        /* Dropdown change */
        $select.on('change', function() {
            var val = $(this).val();
            if (val === 'custom') {
                $wrap.css('display', 'flex');
                $input.click();
            } else {
                $wrap.css('display', 'none');
                customDateFrom = '';
                customDateTo   = '';
                if (history.replaceState) {
                    history.replaceState(null, '', '?period=' + val);
                }
                loadReportData(val);
            }
        });
    })();

    /* ══════════════════════════════════════════════════════════════════
       Initial load
       ══════════════════════════════════════════════════════════════════ */
    loadReportData(<?= json_encode($period) ?>);

    /* ── Responsive resize for charts ── */
    window.addEventListener('resize', function () {
        var donutInst = echarts.getInstanceByDom(document.getElementById('donutChart'));
        var trendInst = echarts.getInstanceByDom(document.getElementById('trendChart'));
        if (donutInst) donutInst.resize();
        if (trendInst) trendInst.resize();
    });
});
</script>

</body>
</html>
