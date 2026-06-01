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

        /* ── Period select ── */
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

        /* ── Page header row: title LEFT, buttons RIGHT ── */
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

        /* ── KPI Tiles ── */
        .du-kpi-tiles {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            width: 100%;
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
        .du-kpi-tile .tile-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e2b3c;
            line-height: 1;
        }

        /* ── Outer wrapper ── */
        .du-outer {
            display: flex;
            gap: 16px;
            align-items: stretch;
            width: 100%;
        }

        /* ── Donut Box ── */
        .du-donut-box {
            background: #fff;
            border: 1px solid #e4e9f0;
            width:100%;
        }
        .du-donut-box-title {
            font-size: 11px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
            align-self: flex-start;
            padding: 10px;
        }

        /* ── Table Box ── */
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
        .du-kpi-split {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        /* Left */
        .kpi-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 180px;
        }
        .kpi-left i {
            color: #3b7ef8;
            font-size: 16px;
        }
        .kpi-title {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
        }
        .kpi-sub {
            font-size: 11px;
            color: #8a97a8;
        }

        /* Middle + Right */
        .kpi-mid,
        .kpi-right {
            text-align: center;
            flex: 1;
            position: relative;
        }

        /* Divider */
        .kpi-mid::after {
            content: '';
            position: absolute;
            right: -10px;
            top: 10%;
            height: 80%;
            width: 1px;
            background: #e4e9f0;
        }

        /* Values */
        .kpi-value {
            font-size: 20px;
            font-weight: 700;
            color: #1e2b3c;
        }
    </style>

    </head>

    <body class="page-header-fixed">
        <?php include('../include/header.php'); ?>
        <div class="page-container row-fluid">
            <?php include('../include/core-plugins.php'); ?>
            <?php
                $_SESSION['mainmenu']      = "report";
                $_SESSION['submenu']       = "revenueMenu";
                $_SESSION['submenulevel1'] = "AvgRevenuePerActiveSite";
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
                                    <span>Revenue Per Active sites</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Page Header -->
                    <div class="breadcrumb ais-report-header">
                        <div class="du-page-header">
                            <h3>
                                <i class="icon-signal" style="color:#e87722;"></i>
                                Average Revenue Per Active Site
                            </h3>
                            <div class="du-filter-wrap">
                                <div id="customDateWrap" class="du-custom-date-wrap" style="display:none;">
                                    <button type="button" id="reportrange" class="du-btn-daterange">
                                        <i class="icon-calendar"></i>
                                        <span>Select date range</span>
                                        <i class="icon-angle-down" style="margin-left:4px;font-size:10px;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="du-kpi-tiles">
                        <div class="du-kpi-tile du-kpi-split">

                            <!-- Left: Label -->
                            <div class="kpi-left">
                                <i class="icon-ticket"></i>
                                <div>
                                    <div class="kpi-title">Total Average Revenue Per Active sites</div>
                                </div>
                            </div>

                            <!-- Middle: Current RPS -->
                            <div class="kpi-mid">
                                <div class="kpi-value" id="kpiTotalVouchers">$0</div>
                                <div class="kpi-sub">
                                    <span id="kpiChangePct">▲ 0.00%</span>
                                    <span id="kpiChangeLabel"> Vs Last Week</span>
                                </div>
                            </div>

                            <!-- Right: Previous period avg -->
                            <div class="kpi-right">
                                <div id="kpiPrevLabel">Previous Week Avg</div>
                                <div class="kpi-value" id="kpiPreviousAvg">–</div>
                            </div>

                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="du-outer">
                        <div class="du-donut-box">
                            <div class="du-donut-box-title">Revenue per Active Site Trend</div>
                            <div id="chart" style="width: 100%; height: 300px;"></div>
                        </div>
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
        <script src="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/select2/select2.min.js"></script>

        <script>
jQuery(document).ready(function () {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ══ Helpers ══ */

	function formatDate(dateStr) {
    // Add 'T00:00:00' to avoid timezone shift on date-only strings
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-IN', { month: 'short', year: 'numeric' });
	}

	/* ══ Daterangepicker ══ */
	$('#reportrange').daterangepicker({
	startDate      : moment().startOf('year'),
		endDate        : moment().startOf('year').add(2, 'months').endOf('month'),
		maxDate        : moment(),
		maxSpan        : { days: 365 },
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

		'Jan - Mar': [
			moment().month(0).startOf('month'),
			moment().month(2).endOf('month')
		],

		'Apr - Jun': [
			moment().month(3).startOf('month'),
			moment().month(5).endOf('month')
		],

		'Jul - Sep': [
			moment().month(6).startOf('month'),
			moment().month(8).endOf('month')
		],

		'Oct - Dec': [
			moment().month(9).startOf('month'),
			moment().month(11).endOf('month')
		]
	}

	}, function(start, end) {

		$('#reportrange span').html(
			start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY')
	);

		currentFrom = start.format('YYYY-MM-DD');
		currentTo   = end.format('YYYY-MM-DD');

		updateComparisonLabels('custom', currentFrom, currentTo);
		loadReportData('custom', currentFrom, currentTo);

	});
	    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
	);

	/* ══ Comparison label helper ══ */
	function updateComparisonLabels(period, fromDate, toDate) {
		var changeLabel, prevLabel;

		if (period === 'today') {
			changeLabel = ' Vs Yesterday';
			prevLabel   = 'Yesterday Avg';
		} else if (period === '7') {
			changeLabel = ' Vs Previous 7 Days';
			prevLabel   = 'Previous 7 Days Avg';
		} else if (period === '30') {
			changeLabel = ' Vs Previous 30 Days';
			prevLabel   = 'Previous 30 Days Avg';
		} else if (period === 'custom' && fromDate && toDate) {
			var days = moment(toDate).diff(moment(fromDate), 'days') + 1;
			changeLabel = ' Vs Previous ' + days + ' Days';
			prevLabel   = 'Previous ' + days + ' Days Avg';
		} else {
			changeLabel = ' Vs Previous Period';
			prevLabel   = 'Previous Period Avg';
		}

		$('#kpiChangeLabel').text(changeLabel);
		$('#kpiPrevLabel').text(prevLabel);
	}

    /* ══ Period dropdown ══ */
    //$('#periodSelect').on('change', function() {
        var period = $(this).val();
        //currentPeriod = period;
        period = 'custom';
        if (period === 'custom') {
            $('#customDateWrap').show();
            var drp = $('#reportrange').data('daterangepicker');
            currentFrom = drp.startDate.format('YYYY-MM-DD');
            currentTo   = drp.endDate.format('YYYY-MM-DD');
            loadReportData('custom', currentFrom, currentTo);
        } else {
            $('#customDateWrap').hide();
            currentFrom = null; currentTo = null;
            loadReportData(period);
        }
 
        if (period == 7) {
            $('#kpiChangeLabel').text(' Vs Last Week');
            $('#kpiPrevLabel').text('Previous Week Avg');
        } else {
            $('#kpiChangeLabel').text(' Vs Previous 30 Days');
            $('#kpiPrevLabel').text('Previous 30 Days Avg');
        }
    //});

    /* ══ Main load ══ */
    function loadReportData(period, fromDate, toDate) {
        loadAvgRevenueByActiveSite(period, fromDate, toDate);
    }

    function loadAvgRevenueByActiveSite(period, fromDate, toDate) {
        var params = { period: period };
        if (period === 'custom' && fromDate && toDate) {
            params.from_date = fromDate;
            params.to_date   = toDate;
        }

        $.ajax({
            url     : '../reports/datatables-scripts/get_avg_revenue_by_active_sites.php',
            type    : 'POST',
            data    : params,
            dataType: 'json',
            success : function(resp) {
                if (!resp || resp.status !== 'success') return;

                var labels  = resp.trend.map(item => period == 7 ? item.label : item.display_date);
                var rpsData = resp.trend.map(item => item.rps);

                var option = {
                    tooltip: {
                        trigger        : 'axis',
                        backgroundColor: '#fff',
                        borderColor    : '#e4e9f0',
                        borderWidth    : 1,
                        textStyle      : { color: '#2c3e50' },
                        formatter      : function(params) {
                            var item = resp.trend[params[0].dataIndex];
                            return `<b>${(item.display_date)}</b><br/>
Revenue Per Site: $${item.rps.toFixed(2)}<br/>
Revenue: $${item.revenue.toLocaleString('en-IN')}<br/>
Active Users: ${item.active_users.toLocaleString('en-IN')}<br/>
Active Sites: ${item.active_sites.toLocaleString('en-IN')}`;
                        }
                    },
                    xAxis: {
                        type        : 'category',
                        data        : labels,
                        boundaryGap : false,
                        name        : 'Months',
                        nameLocation: 'middle',
                        nameGap     : 25,
                        axisLine    : { show: false },
                        axisTick    : { show: false },
                        axisLabel   : { color: '#6b7a90', fontSize: 11 }
                    },
                    yAxis: {
                        type        : 'value',
                        name        : 'Revenue Per Site($)',
                        nameLocation: 'middle',
                        nameGap     : 37,
                        axisLine    : { show: false },
                        axisTick    : { show: false },
                        axisLabel   : { color: '#6b7a90', fontSize: 11, formatter: value => value },
                        splitLine   : { lineStyle: { color: '#f0f3f8' } }
                    },
                    series: [{
                        name     : 'Revenue per Active Site',
                        type     : 'line',
                        data     : rpsData,
                        smooth   : true,
                        symbol   : 'circle',
                        symbolSize: 6,
                        lineStyle: { width: 2, color: '#3b7ef8' },
                        itemStyle: { color: '#3b7ef8', borderWidth: 2, borderColor: '#fff' },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: 'rgba(59,126,248,0.25)' },
                                { offset: 1, color: 'rgba(59,126,248,0.02)' }
                            ])
                        }
                    }],
                    grid: { left: '5%', right: '5%', bottom: '8%', top: '8%', containLabel: true }
                };

                var myChart = echarts.getInstanceByDom(document.getElementById('chart'))
                           || echarts.init(document.getElementById('chart'));
                myChart.setOption(option, true);

                /* KPI */
                $('#kpiTotalVouchers').text('$' + resp.kpi.value.toFixed(2));
                $('#kpiChangePct').html(
                    `<span style="color:${resp.kpi.change_pct >= 0 ? 'green' : 'red'}">
                        ${resp.kpi.change_pct >= 0 ? '▲' : '▼'} ${resp.kpi.change_pct.toFixed(2)}%
                    </span>`
                );
                $('#kpiChangeLabel').css('color', resp.kpi.change_pct >= 0 ? 'green' : 'red');
                $('#kpiPreviousAvg').text('$' + resp.kpi.previous_avg.toFixed(2));
            }
        });
    }

    /* ══ Initial load ══ */
var drp      = $('#reportrange').data('daterangepicker');
currentFrom  = drp.startDate.format('YYYY-MM-DD');
currentTo    = drp.endDate.format('YYYY-MM-DD');

$('#reportrange span').html(
    drp.startDate.format('DD MMM YYYY') + ' – ' + drp.endDate.format('DD MMM YYYY')
);

updateComparisonLabels('custom', currentFrom, currentTo);
loadReportData('custom', currentFrom, currentTo);
});
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
