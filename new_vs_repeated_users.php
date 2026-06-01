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

$labelHome    = getLabel($lang, $module, "home");
$labelReports = getLabel($lang, $module, "reports");

// ── Read period from URL for server-side default ──
$period = isset($_GET['period']) && in_array($_GET['period'], ['7', '30', 'custom'])
              ? $_GET['period']
              : '7';
$dateFrom   = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : '';
$dateTo     = isset($_GET['dateTo'])   ? $_GET['dateTo']   : '';
?>
<!DOCTYPE html>
<!--[if IE 8]><html lang="en" class="ie8"><![endif]-->
<!--[if IE 9]><html lang="en" class="ie9"><![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title><?= $WIFILANTITLE ?> – New vs Returning Users</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <?php include('../include/global-styles.php'); ?>
    <link rel="shortcut icon" href="<?=$baseurl?>/<?=$appname?>/img/favicon.ico" />
<link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" />


	<style>
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

		/* ── Page header ── */
		.nvr-report-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 12px;
			margin-bottom: 20px;
		}
		.nvr-report-header h3 {
			margin: 0;
			font-size: 18px;
			color: #2c3e50;
		}

		/* ── Filter bar ── */
		.filter-bar {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
        }

        /* ── KPI tiles ── */
        .kpi-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .kpi-card {
            flex: 1;
            min-width: 150px;
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
        .kpi-card.newu::before     { background: #27ae60; }
        .kpi-card.returning::before{ background: #e87722; }
        .kpi-card.newpct::before   { background: #2980b9; }
        .kpi-card.retpct::before   { background: #8e44ad; }

        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 6px;
        }
        .kpi-value {
            font-size: 30px;
            font-weight: 700;
            color: #1e2b3c;
            line-height: 1.1;
        }
        .kpi-sub {
            font-size: 12px;
            color: #8a97a8;
            margin-top: 5px;
        }

        /* ── Chart cards ── */
        .charts-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .chart-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            padding: 18px 20px;
        }
        .chart-card.pie-card   { flex: 0 0 340px; min-width: 300px; }
        .chart-card.trend-card { flex: 1; min-width: 320px; }

        .chart-title {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-title .badge-pill {
            font-size: 11px;
            font-weight: 500;
            background: #f0f4fa;
            color: #5a6a80;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ── Pie legend ── */
        .pie-legend {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 10px;
        }
        .pie-legend-item {
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
        .legend-dot.new       { background: #27ae60; }
        .legend-dot.returning { background: #e87722; }

        /* ── EChart containers ── */
        #pieChart   { width: 100%; height: 240px; }
        #trendChart { width: 100%; height: 260px; }

        /* ── Skeleton loader ── */
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
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
<?php
			if($_SESSION['customerid'] != 1){
				$_SESSION['mainmenu']      = "analytics";
			}else{
				$_SESSION['mainmenu']      = "report";
			}
            $_SESSION['submenu']       = "usersMenu";
            $_SESSION['submenulevel1'] = "NewVsRepeatedUser";
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
                                <span>New vs Returning Users</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Page Header + Filter Bar -->
                <div class="breadcrumb nvr-report-header">
                    <h3>
                        <i class="icon-group" style="color:#e87722;margin-right:8px;"></i>
                        New vs Returning Users
                    </h3>

                    <!-- ── Filter Bar ── -->
                    <div class="filter-bar">
                        <select id="periodSelect"
                                style="height:32px;padding:0 28px 0 10px;font-size:12px;font-weight:600;
                                       color:#3d5166;background:#fff url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'10\' height=\'6\'%3E%3Cpath d=\'M0 0l5 6 5-6z\' fill=\'%237a8da0\'/%3E%3C/svg%3E') no-repeat right 9px center;
                                       border:1px solid #dde3ec;border-radius:7px;appearance:none;-webkit-appearance:none;
                                       cursor:pointer;min-width:160px;">
                            <option value="7"      <?= $period=='7'      ? 'selected':'' ?>>Last 7 Days</option>
                            <option value="30"     <?= $period=='30'     ? 'selected':'' ?>>Last 30 Days</option>
                            <option value="custom" <?= $period==='custom' ? 'selected':'' ?>>Custom Range</option>
                        </select>

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

                <!-- KPI Tiles -->
                <div class="kpi-row">
                    <div class="kpi-card total">
                        <div class="kpi-label">Total Users</div>
                        <div class="kpi-value" id="kpiTotal">
                            <span class="kpi-skeleton" style="width:70px;height:30px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub" id="kpiTotalSub">Last 7 days</div>
                    </div>
                    <div class="kpi-card newu">
                        <div class="kpi-label">New Users</div>
                        <div class="kpi-value" id="kpiNew">
                            <span class="kpi-skeleton" style="width:70px;height:30px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub">First-time visitors</div>
                    </div>
                    <div class="kpi-card returning">
                        <div class="kpi-label">Returning Users</div>
                        <div class="kpi-value" id="kpiReturning">
                            <span class="kpi-skeleton" style="width:70px;height:30px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub">Repeat visitors</div>
                    </div>
                    <div class="kpi-card newpct">
                        <div class="kpi-label">New User %</div>
                        <div class="kpi-value" id="kpiNewPct">
                            <span class="kpi-skeleton" style="width:70px;height:30px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub">Of total sessions</div>
                    </div>
                    <div class="kpi-card retpct">
                        <div class="kpi-label">Returning User %</div>
                        <div class="kpi-value" id="kpiRetPct">
                            <span class="kpi-skeleton" style="width:70px;height:30px;">&nbsp;</span>
                        </div>
                        <div class="kpi-sub">Of total sessions</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="charts-row">

                    <!-- Pie Chart -->
                    <div class="chart-card pie-card">
                        <div class="chart-title">
                            New vs Returning Users
                            <span class="badge-pill" id="pieBadge">Last 7 Days</span>
                        </div>
                        <div id="pieChart">
                            <div class="chart-loader">
                                <i class="icon-spinner icon-spin"></i> Loading…
                            </div>
                        </div>
                        <div class="pie-legend">
                            <div class="pie-legend-item">
                                <span class="legend-dot new"></span> New Users
                            </div>
                            <div class="pie-legend-item">
                                <span class="legend-dot returning"></span> Returning Users
                            </div>
                        </div>
                    </div>

                    <!-- Trend Line Chart -->
                    <div class="chart-card trend-card">
                        <div class="chart-title">
                            New vs Returning User Trend
                            <span class="badge-pill" id="trendBadge">Last 7 Days</span>
                        </div>
                        <div id="trendChart">
                            <div class="chart-loader">
                                <i class="icon-spinner icon-spin"></i> Loading…
                            </div>
                        </div>
                    </div>

                </div><!-- /charts-row -->

            </div><!-- /container-fluid -->
        </div><!-- /page-content -->
    </div><!-- /page-container -->

    <?php include('../include/footer.php'); ?>
<script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/moment.min.js"></script>
<script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/daterangepicker/daterangepicker.min.js"></script>
<script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/echarts-6.0.0/package/dist/echarts.min.js"></script>
<script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/echarts-6.0.0/package/asset/echart-render.js"></script>

<script>
jQuery(document).ready(function () {
    App.init();

    var ASSETS = {
        loadingGif : "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/ajax-loading.gif",
        nodataImg  : "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg"
    };

    var customDateFrom = '<?= htmlspecialchars($dateFrom) ?>';
    var customDateTo   = '<?= htmlspecialchars($dateTo) ?>';

    function numberFormat(n) {
        return parseInt(n, 10).toLocaleString();
    }

    function getPeriodLabel(period) {
        var map = { '1': 'Today', '7': 'Last 7 Days', '30': 'Last 30 Days', 'custom': 'Custom Range' };
        return map[period] || 'Last 7 Days';
    }

    function showSkeletons() {
        var skel = '<span class="kpi-skeleton" style="width:70px;height:30px;">&nbsp;</span>';
        $('#kpiTotal, #kpiNew, #kpiReturning, #kpiNewPct, #kpiRetPct').html(skel);
        $('#pieChart, #trendChart').html(
            '<div class="chart-loader"><i class="icon-spinner icon-spin"></i> Loading…</div>'
        );
    }

    function loadReportData(period) {
        period = period || $('#periodSelect').val() || '7';
        showSkeletons();

        var postData = { action: 'getReportData', period: period };
        if (period === 'custom') {
            postData.dateFrom = customDateFrom;
            postData.dateTo   = customDateTo;
        }

        var label = getPeriodLabel(period);
        if (period === 'custom' && customDateFrom && customDateTo) {
            label = customDateFrom + ' → ' + customDateTo;
        }

        $('#pieBadge, #trendBadge').text(label);
        $('#kpiTotalSub').text(label);
		console.log("postData=>",postData)
        $.ajax({
            url      : './datatables-scripts/get_new_vs_repeated_user_data.php',
            type     : 'POST',
            data     : postData,
            dataType : 'json',
            success: function (resp) {
                if (!resp || resp.status !== 'success') {
                    console.error('API error:', resp);
                    return;
                }
                var kpi   = resp.kpi;
                var trend = resp.trend;

                $('#dataAsOf').text(kpi.date_from && kpi.date_to
                    ? kpi.date_from + ' → ' + kpi.date_to
                    : 'No data');

                $('#kpiTotal').text(numberFormat(kpi.total_users));
                $('#kpiNew').text(numberFormat(kpi.new_users));
                $('#kpiReturning').text(numberFormat(kpi.repeated_users));
                $('#kpiNewPct').text(kpi.new_pct + '%');
                $('#kpiRetPct').text(kpi.ret_pct + '%');

                renderEchartPieNewVsReturning('pieChart', kpi, ASSETS);
                renderEchartTrendNewVsReturning('trendChart', trend, ASSETS);
            },
            error: function (xhr, status, err) {
                console.error('AJAX error:', status, err);
                $('#pieChart, #trendChart').html(
                    '<div class="chart-loader" style="color:#e74c3c;">' +
                    '<i class="icon-warning-sign"></i> Failed to load data.</div>'
                );
            }
        });
    }

/* ══ Daterangepicker ══ */

var yesterday = moment().subtract(1, 'days');

$('#customRangeInput').daterangepicker({
    startDate      : customDateFrom ? moment(customDateFrom) : moment().subtract(7, 'days'),
    endDate        : customDateTo   ? moment(customDateTo)   : yesterday,
    maxDate        : yesterday,
    minDate        : moment().subtract(90, 'days'),
    maxSpan        : { days: 90 },
    showDropdowns  : true,
    linkedCalendars: false,
    autoApply      : false,
    opens          : 'left',
    /* ── NO ranges: {} here — removes the left panel completely ── */
    locale: {
        format     : 'YYYY-MM-DD',
        separator  : ' – ',
        applyLabel : 'Apply',
        cancelLabel: 'Cancel',
        firstDay   : 1
    }
}, function(start, end) {
    var startStr = start.format('YYYY-MM-DD');
    var endStr   = end.format('YYYY-MM-DD');
    var days     = end.diff(start, 'days');

    if (days > 90) {
        $('#dateRangeMsg').html('<span style="color:#c0392b;font-weight:700;">Max 90 days exceeded</span>');
        return;
    }

    $('#dateRangeMsg').text('Max 90 days');
    customDateFrom = startStr;
    customDateTo   = endStr;
    $('#customRangeInput').val(startStr + '  →  ' + endStr);

    if (history.replaceState) {
        history.replaceState(null, '', '?period=custom&dateFrom=' + startStr + '&dateTo=' + endStr);
    }
    loadReportData('custom');
});

/* ── Hide on init ── */
$('#customRangeInput').data('daterangepicker').hide();

/* ── Pre-fill if returning from URL ── */
if (customDateFrom && customDateTo) {
    $('#customRangeInput').val(customDateFrom + '  →  ' + customDateTo);
}

/* ── Cancel just hides picker ── */
$('#customRangeInput').on('cancel.daterangepicker', function() {
    $('#customRangeInput').data('daterangepicker').hide();
    /* Reset dropdown back to last valid period if no custom dates set */
    if (!customDateFrom || !customDateTo) {
        $('#periodSelect').val('7').trigger('change');
    }
});

/* ── Dropdown change ── */
$('#periodSelect').on('change', function() {
    var val = $(this).val();

    if (val === 'custom') {
        $('#customRangeWrap').css('display', 'flex');
        setTimeout(function() {
            $('#customRangeInput').data('daterangepicker').show();
        }, 50);
    } else {
        $('#customRangeWrap').css('display', 'none');
        $('#customRangeInput').data('daterangepicker').hide();
        customDateFrom = '';
        customDateTo   = '';
        if (history.replaceState) {
            history.replaceState(null, '', '?period=' + val);
        }
        loadReportData(val);
    }
});

/* ── On page load: show wrap if custom ── */
if ($('#periodSelect').val() === 'custom') {
    $('#customRangeWrap').css('display', 'flex');
}

    /* Dropdown change */
    $('#periodSelect').on('change', function() {
        var val = $(this).val();
        if (val === 'custom') {
            $('#customRangeWrap').css('display', 'flex');
            $('#customRangeInput').click();
        } else {
            $('#customRangeWrap').css('display', 'none');
            customDateFrom = '';
            customDateTo   = '';
            if (history.replaceState) {
                history.replaceState(null, '', '?period=' + val);
            }
            loadReportData(val);
        }
    });

    if ($('#periodSelect').val() === 'custom') {
        $('#customRangeWrap').css('display', 'flex');
    }

    window.addEventListener('resize', function () {
        var pieInst   = echarts.getInstanceByDom(document.getElementById('pieChart'));
        var trendInst = echarts.getInstanceByDom(document.getElementById('trendChart'));
        if (pieInst)   pieInst.resize();
        if (trendInst) trendInst.resize();
    });

    loadReportData('<?= htmlspecialchars($period) ?>');
});
</script>

</body>
</html>
