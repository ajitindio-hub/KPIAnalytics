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
    <title><?= $WIFILANTITLE ?> – Total Activations vs Reactivations</title>
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

        /* ── Activation widget card ── */
        .activation-widget {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 8px;
            padding: 20px 24px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }

        /* ── Widget top bar ── */
        .activation-widget .widget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .activation-widget .widget-title {
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        /* ── Period filter ── */
        .period-filter-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
		.period-select {
            height:38px;
            margin-top:8px;
            padding: 5px 26px 5px 10px;
            font-size: 12px; font-weight: 500;
            border: 1px solid #d0d7e2; border-radius: 4px;
            background: #f7f9fc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23999'/%3E%3C/svg%3E") no-repeat right 8px center;
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
            color: #444; cursor: pointer; transition: border-color 0.15s; min-width: 135px;
        }
        .period-select:focus { outline: none; border-color: #e87722; }
        .custom-date-wrap { display: flex; align-items: center; }
        .btn-daterange {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 5px 12px; font-size: 12px; font-weight: 500;
            color: #444; background: #f7f9fc; border: 1px solid #d0d7e2;
            border-radius: 4px; cursor: pointer;
            transition: border-color 0.15s, background 0.15s; white-space: nowrap;
        }
        .btn-daterange:hover { border-color: #e87722; background: #fff8f3; }
        .btn-daterange i.icon-calendar { color: #e87722; font-size: 13px; }

        /* ── KPI cards ── */
        .kpi-row {
            display: flex;
            gap: 14px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .kpi-card {
            flex: 1 1 140px;
            border: 1px solid #e4e9f0;
            border-radius: 6px;
            padding: 14px 18px;
            text-align: center;
            background: #fafbfc;
            position: relative;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 6px 6px 0 0;
        }
        .kpi-card.kpi-total::before         { background: #5b6af0; }
        .kpi-card.kpi-activations::before   { background: #4a90d9; }
        .kpi-card.kpi-reactivations::before { background: #e87722; }

        .kpi-card .kpi-label {
            font-size: 11px;
            color: #7a8a9a;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .kpi-card .kpi-value {
            font-size: 26px;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1;
        }
        .kpi-card.kpi-activations   .kpi-value { color: #4a90d9; }
        .kpi-card.kpi-reactivations .kpi-value { color: #e87722; }

        /* skeleton shimmer */
        .kpi-skeleton {
            display: inline-block;
            background: linear-gradient(90deg,#eee 25%,#f5f5f5 50%,#eee 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
            border-radius: 4px;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ── Chart section ── */
        .chart-section-label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
        }
        #activationPieChart {
            width: 100%;
            height: 360px;
        }
        .chart-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 360px;
            color: #8a97a8;
            font-size: 13px;
            gap: 8px;
        }

        /* ── Legend ── */
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: #444;
        }
        .legend-dot {
            width: 12px; height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .legend-dot.blue   { background: #4a90d9; }
        .legend-dot.orange { background: #e87722; }

        /* ── Page header ── */
        .ais-report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }
        .ais-report-header h3 { margin: 0; font-size: 16px; }

        @media (max-width: 600px) {
            .kpi-card .kpi-value { font-size: 20px; }
            #activationPieChart  { height: 280px; }
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
            $_SESSION['submenulevel1'] = "ActivationReactivationSites";
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
                                <a href="#">Users</a>
                                <i class="icon-angle-right"></i>
                                <span>Total Activations vs Reactivations</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="breadcrumb ais-report-header">
                    <h3>
                        <i class="icon-signal" style="color:#e87722;margin-right:8px;"></i>
                        Total Activations vs Reactivations
                    </h3>
                </div>

                <!-- ═══════════════════════════════════════
                     ACTIVATION WIDGET
                ═══════════════════════════════════════ -->
                <div class="row-fluid">
                    <div class="span12">
                        <div class="activation-widget">

                            <!-- Widget header -->
                            <div class="widget-header">
                                <h4 class="widget-title">User Activations Breakdown</h4>
                                <div class="period-filter-wrap">
                                    <button type="button" id="reportrange" class="btn-daterange">
                                        <i class="icon-calendar"></i>
                                        <span>Select date range</span>
                                        <i class="icon-angle-down" style="margin-left:4px;font-size:10px;"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- KPI Cards -->
                            <div class="kpi-row">
                                <div class="kpi-card kpi-total">
                                    <div class="kpi-label">Total Activations</div>
                                    <div class="kpi-value" id="kpiTotal">
                                        <span class="kpi-skeleton" style="width:60px;height:28px;">&nbsp;</span>
                                    </div>
                                </div>
                                <div class="kpi-card kpi-activations">
                                    <div class="kpi-label">First-Time Activations</div>
                                    <div class="kpi-value" id="kpiActivations">
                                        <span class="kpi-skeleton" style="width:60px;height:28px;">&nbsp;</span>
                                    </div>
                                </div>
                                <div class="kpi-card kpi-reactivations">
                                    <div class="kpi-label">Reactivations</div>
                                    <div class="kpi-value" id="kpiReactivations">
                                        <span class="kpi-skeleton" style="width:60px;height:28px;">&nbsp;</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pie Chart -->
                            <div id="activationPieChart">
                                <div class="chart-loader">
                                    <i class="icon-spinner icon-spin"></i> Loading chart…
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <span class="legend-dot blue"></span>
                                    First-Time Activations
                                </div>
                                <div class="legend-item">
                                    <span class="legend-dot orange"></span>
                                    Reactivations
                                </div>
                            </div>

                        </div><!-- /activation-widget -->
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

<script>
jQuery(document).ready(function () {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    var chartInstance = null;

    /* ── Number formatter ── */
    function numFmt(n) {
        return parseInt(n, 10).toLocaleString();
    }

    /* ══════════════════════════════════════════════
       renderActivationChart(activations, reactivations)
       Destroys old instance and re-draws ECharts donut
    ══════════════════════════════════════════════ */
    function renderActivationChart(activations, reactivations) {
        var dom = document.getElementById('activationPieChart');

        if (chartInstance) {
            chartInstance.dispose();
            chartInstance = null;
        }

        chartInstance = echarts.init(dom);

        var option = {
            title: {
                text:      'Activation Source Split',
                subtext:   'Sites Data',
                left:      'center',
                top:       10,
                textStyle: { fontSize: 15, fontWeight: '600', color: '#2c3e50' },
                subtextStyle: { fontSize: 12, color: '#8a97a8' }
            },
            toolbox: {
                show:        true,
                orient:      'horizontal',
                right:       16,
                top:         10,
                itemSize:    18,
                itemGap:     10,
                showTitle:   true,
                feature: {
                    saveAsImage: {
                        show:              true,
                        type:              'png',
                        name:              'Activation_Source_Split',
                        title:             'Download PNG',
                        pixelRatio:        2,       /* retina quality */
                        backgroundColor:   '#fff',
                        iconStyle: {
                            borderColor: '#e87722',
                            borderWidth: 1.5
                        },
                        emphasis: {
                            iconStyle: {
                                borderColor:     '#e87722',
                                backgroundColor: '#fff7f0',
                                textFill:        '#e87722'
                            }
                        }
                    }
                }
            },
            tooltip: {
                trigger: 'item',
                formatter: function(params) {
                    return '<b>' + params.name + '</b><br/>'
                        + numFmt(params.value) + ' sites (' + params.percent + '%)';
                }
            },
            legend: { show: false },
            series: [{
                name:              'Activations',
                type:              'pie',
                radius:            '58%',       /* solid pie, no hole */
                center:            ['50%', '55%'],
                avoidLabelOverlap: true,
                label: {
                    show:           true,
                    position:       'outside',  /* labels outside like image 2 */
                    formatter:      function(params) {
                        return params.name + '\n' + params.percent + '%';
                    },
                    fontSize:       13,
                    color:          '#333',
                    lineHeight:     18
                },
                labelLine: {
                    show:         true,
                    length:       15,           /* first segment from pie edge */
                    length2:      20,           /* horizontal segment */
                    smooth:       0.5,
                    lineStyle: {
                        width: 1.5,
                        type:  'solid'
                    }
                },
                emphasis: {
                    itemStyle: {
                        shadowBlur:    12,
                        shadowOffsetX: 0,
                        shadowColor:   'rgba(0,0,0,.25)'
                    },
                    label: {
                        show:       true,
                        fontSize:   14,
                        fontWeight: 'bold'
                    }
                },
                data: [
                    {
                        value:     activations,
                        name:      'First-Time Activations',
                        itemStyle: { color: '#4a90d9' }
                    },
                    {
                        value:     reactivations,
                        name:      'Reactivations',
                        itemStyle: { color: '#e87722' }
                    }
                ]
            }]
        };

        chartInstance.setOption(option);
    }

    /* ══════════════════════════════════════════════
       loadActivationData(period, fromDate, toDate)
       POST → datatables-scripts/get_activations_reactivations_data.php
    ══════════════════════════════════════════════ */
    function loadActivationData(period, fromDate, toDate) {

        /* show skeleton loaders */
        $('#kpiTotal,#kpiActivations,#kpiReactivations').html(
            '<span class="kpi-skeleton" style="width:60px;height:28px;">&nbsp;</span>'
        );
        $('#activationPieChart').html(
            '<div class="chart-loader">' +
            '<i class="icon-spinner icon-spin"></i> Loading chart…</div>'
        );

        var params = { action: 'getActivationData', period: period };
        if (period === 'custom' && fromDate && toDate) {
            params.from_date = fromDate;
            params.to_date   = toDate;
        }

        $.ajax({
            url:      './datatables-scripts/get_activations_reactivations_data.php',
            type:     'POST',
            data:     params,
            dataType: 'json',

            success: function(resp) {
                if (!resp || resp.status !== 'success') {
                    console.error('API error:', resp);
                    showChartError();
                    return;
                }

                var kpi = resp.kpi;

                /* populate KPI cards */
                $('#kpiTotal').text(numFmt(kpi.total));
                $('#kpiActivations').text(numFmt(kpi.activations));
                $('#kpiReactivations').text(numFmt(kpi.reactivations));

                /* render donut chart */
                $('#activationPieChart').html('');   /* clear loader div */
                renderActivationChart(kpi.activations, kpi.reactivations);
            },

            error: function(xhr, status, err) {
                console.error('AJAX error:', status, err);
                $('#kpiTotal,#kpiActivations,#kpiReactivations').text('—');
                showChartError();
            }
        });
    }

    function showChartError() {
        $('#activationPieChart').html(
            '<div class="chart-loader" style="color:#e74c3c;">' +
            '<i class="icon-warning-sign"></i> Failed to load data. Please refresh.</div>'
        );
    }

    /* ══════════════════════════════════════════════
       Daterangepicker — initialise once, reuse
    ══════════════════════════════════════════════ */
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
            /*'This Month'  : [moment().startOf('month'), moment().endOf('month')],
            'Last Month'  : [moment().subtract(1, 'month').startOf('month'),
			moment().subtract(1, 'month').endOf('month')]*/
        }
    }, function(start, end) {
        $('#reportrange span').html(start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY'));
        loadActivationData('custom', start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
    });

    /* Set initial picker label */
    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ══════════════════════════════════════════════
       Initial data load — custom date range (last 7 days)
    ══════════════════════════════════════════════ */
    loadActivationData('custom', moment().subtract(6, 'days').format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));

    /* responsive resize */
    window.addEventListener('resize', function() {
        if (chartInstance) chartInstance.resize();
    });

});
</script>

</body>
</html>
