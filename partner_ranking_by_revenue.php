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
            font-weight: 600;
            color: #2c3e50;
        }

        /* Date range picker button */
        .btn-daterange {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 34px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 500;
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
        .btn-daterange i.icon-calendar { color: inherit; font-size: 13px; }

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
        #donutChart  { width: 100%; height: 220px; }
        #trendChart  { width: 100%; height: 240px; }
        #partnerRevenueChart { width: 100%; height: 320px; }

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

        /* ── Filter row ── */
        .filter-row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 18px;
            padding: 14px 16px;
            background: #f7f9fc;
            border: 1px solid #e4e9f0;
            border-radius: 8px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 200px;
        }
        .filter-group label {
            font-size: 11px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 0;
        }
        .filter-group select {
            padding: 7px 12px;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            font-size: 13px;
            color: #2c3e50;
            background: #fff;
            cursor: pointer;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
            width: 100%;
        }
        .filter-group select:focus {
            border-color: #e87722;
            box-shadow: 0 0 0 3px rgba(232,119,34,.12);
        }
        .filter-group select:disabled {
            background: #f0f3f8;
            color: #aab4c0;
            cursor: not-allowed;
        }
        .filter-clear-btn {
            padding: 7px 16px;
            border: 1px solid #dde3ec;
            border-radius: 6px;
            font-size: 13px;
            color: #5a6a80;
            background: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: background .18s, color .18s;
            white-space: nowrap;
        }
        .filter-clear-btn:hover {
            background: #fdecea;
            color: #c0392b;
            border-color: #f5b7b1;
        }

        /* Active filter tags */
        .active-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 12px;
        }
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff4ec;
            border: 1px solid #f5c199;
            color: #b45309;
            font-size: 12px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .filter-tag .remove-tag {
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            color: #e87722;
            font-weight: 700;
        }
        .filter-tag .remove-tag:hover { color: #c0392b; }

        /* Loading spinner on select */
        .select-loading {
            position: relative;
        }
        .select-loading::after {
            content: '';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            border: 2px solid #dde3ec;
            border-top-color: #e87722;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }
    </style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "report";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "PartnerRanking";
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
                                <span>Partner ranking by revenue</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="breadcrumb ais-report-header">
                    <h3><i class="icon-signal" style="color:#e87722;margin-right:8px;"></i>Partner ranking by revenue</h3>
                    <button type="button" id="reportrange" class="btn-daterange">
                        <i class="icon-calendar"></i>
                        <span>Select date range</span>
                        <i class="icon-angle-down" style="margin-left:4px;font-size:11px;"></i>
                    </button>
                </div>

                <!-- Partner Ranking by Revenue Chart -->
                <div class="chart-card" style="margin-bottom:20px;">
                    <div class="chart-title">
                        Partner Ranking by Revenue
                        <span class="badge-pill" id="partnerPeriodBadge">Last <?= $period ?> Days</span>
                    </div>

                    <!-- ══════════════════════════════════════════
                         FILTER ROW: Customer → Partner
                         ══════════════════════════════════════════ -->
                    <div class="filter-row">

                        <!-- Customer dropdown -->
                        <div class="filter-group" id="customerFilterGroup">
                            <label for="customerFilter">
                                <i class="icon-building" style="margin-right:4px;"></i>Customer
                            </label>
                            <select id="customerFilter">
                                <option value="">— All Customers —</option>
                                <!-- populated via AJAX -->
                            </select>
                        </div>

                        <!-- Partner dropdown (disabled until customer chosen) -->
                        <!--div class="filter-group" id="partnerFilterGroup">
                            <label for="partnerFilter">
                                <i class="icon-user" style="margin-right:4px;"></i>Partner
                                <span id="partnerCountBadge"
                                      style="display:none; margin-left:6px; background:#e87722;
                                             color:#fff; font-size:10px; font-weight:700;
                                             padding:1px 7px; border-radius:20px; letter-spacing:.3px;">
                                </span>
                            </label>
                            <select id="partnerFilter" disabled>
                                <option value="">— All Partners —</option>
                                <!-- populated after customer is selected -->
                            </select>
                        </div-->

                        <!-- Clear button -->
                        <!--div style="display:flex; flex-direction:column; justify-content:flex-end;">
                            <button id="clearFilters" class="filter-clear-btn" title="Reset all filters">
                                <i class="icon-remove"></i> Clear Filters
                            </button>
                        </div-->

                    </div>
                    <!-- /filter-row -->

                    <!-- Active filter tags (shown when filters are applied) -->
                    <div id="activeFilterTags" class="active-filters" style="display:none;"></div>

                    <!-- Chart area -->
                    <div id="partnerRevenueChart" style="width:100%;height:320px;">
                        <div class="chart-loader">
                            <i class="icon-spinner icon-spin"></i> Loading…
                        </div>
                    </div>

                </div>
                <!-- /chart-card -->

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
       STATE
       ══════════════════════════════════════════════════════════════════ */
    var _currentFrom      = moment().subtract(6, 'days').format('YYYY-MM-DD');
    var _currentTo        = moment().format('YYYY-MM-DD');
    var _selectedCustomer = '';   /* adm_customer.id */
    var _selectedCustomerName = '';
    var _selectedPartner  = '';   /* operator_id */
    var _selectedPartnerName  = '';

    var ASSETS = {
        nodataImg: "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg"
    };

    /* ══════════════════════════════════════════════════════════════════
       ACTIVE FILTER TAGS
       ══════════════════════════════════════════════════════════════════ */
    function renderFilterTags() {
        var $wrap = $('#activeFilterTags');
        $wrap.empty();

        if (!_selectedCustomer && !_selectedPartner) {
            $wrap.hide();
            return;
        }

        $wrap.show();

        if (_selectedCustomer) {
            $wrap.append(
                '<div class="filter-tag">' +
                '<i class="icon-building" style="font-size:11px;"></i>' +
                ' Customer: <b>' + _selectedCustomerName + '</b>' +
                '<span class="remove-tag" data-remove="customer" title="Remove">×</span>' +
                '</div>'
            );
        }
        if (_selectedPartner) {
            $wrap.append(
                '<div class="filter-tag">' +
                '<i class="icon-user" style="font-size:11px;"></i>' +
                ' Partner: <b>' + _selectedPartnerName + '</b>' +
                '<span class="remove-tag" data-remove="partner" title="Remove">×</span>' +
                '</div>'
            );
        }
    }

    /* Remove individual tag */
    $(document).on('click', '.remove-tag', function() {
        var target = $(this).data('remove');
        if (target === 'customer') {
            /* Clearing customer also clears partner */
            _selectedCustomer     = '';
            _selectedCustomerName = '';
            _selectedPartner      = '';
            _selectedPartnerName  = '';
            $('#customerFilter').val('');
            resetPartnerDropdown();
        } else if (target === 'partner') {
            _selectedPartner     = '';
            _selectedPartnerName = '';
            $('#partnerFilter').val('');
        }
        renderFilterTags();
        loadPartnerRevenueChart(_currentFrom, _currentTo, _selectedCustomer, _selectedPartner);
    });

    /* ══════════════════════════════════════════════════════════════════
       RESET PARTNER DROPDOWN
       ══════════════════════════════════════════════════════════════════ */
    function resetPartnerDropdown() {
        var $sel = $('#partnerFilter');
        $sel.find('option:not(:first)').remove();
        $sel.val('').prop('disabled', true);
        $('#partnerCountBadge').hide().text('');
    }

    /* ══════════════════════════════════════════════════════════════════
       LOAD CUSTOMERS  (page init — called once)
       ══════════════════════════════════════════════════════════════════ */
    function loadCustomers() {
        var $group = $('#customerFilterGroup');
        $group.addClass('select-loading');

        $.ajax({
            url:      '../reports/datatables-scripts/get_customers_and_partners.php',
            type:     'POST',
            data:     { action: 'customers' },
            dataType: 'json',
            success: function(resp) {
                $group.removeClass('select-loading');
                if (!resp || resp.status !== 'success') return;
                var $sel = $('#customerFilter');
                $sel.find('option:not(:first)').remove();
                $.each(resp.customers, function(i, c) {
                    $sel.append('<option value="' + c.id + '">' + c.name + '</option>');
                });
            },
            error: function() {
                $group.removeClass('select-loading');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       LOAD PARTNERS for a given customer  (called on customer change)
       ══════════════════════════════════════════════════════════════════ */
    function loadPartnersForCustomer(customerId, dateFrom, dateTo) {
        var $group = $('#partnerFilterGroup');
        var $sel   = $('#partnerFilter');

        resetPartnerDropdown();

        if (!customerId) return;

        $group.addClass('select-loading');
        $sel.prop('disabled', true);

        $.ajax({
            url:      '../reports/datatables-scripts/get_customers_and_partners.php',
            type:     'POST',
            data:     { action: 'partners_by_customer', selected_customer_id: customerId, period: 'custom', date_from: dateFrom, date_to: dateTo },
            dataType: 'json',
            success: function(resp) {
                $group.removeClass('select-loading');
                if (!resp || resp.status !== 'success') return;

                var partners = resp.partners || [];

                if (partners.length === 0) {
                    $sel.append('<option value="" disabled>No partners found</option>');
                    $sel.prop('disabled', true);
                    $('#partnerCountBadge').hide();
                    return;
                }

                $.each(partners, function(i, p) {
                    $sel.append('<option value="' + p.operator_id + '">' + p.partner_name + '</option>');
                });

                $sel.prop('disabled', false);

                /* Show count badge */
                $('#partnerCountBadge').text(partners.length).show();
            },
            error: function() {
                $group.removeClass('select-loading');
                $sel.prop('disabled', true);
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       CUSTOMER FILTER  change handler
       ══════════════════════════════════════════════════════════════════ */
    $(document).on('change', '#customerFilter', function() {
        _selectedCustomer     = $(this).val();
        _selectedCustomerName = $(this).find('option:selected').text().trim();
        _selectedPartner      = '';
        _selectedPartnerName  = '';

        /* Reset partner, then load fresh list for this customer */
        loadPartnersForCustomer(_selectedCustomer, _currentFrom, _currentTo);

        renderFilterTags();
        loadPartnerRevenueChart(_currentFrom, _currentTo, _selectedCustomer, '');
    });

    /* ══════════════════════════════════════════════════════════════════
       PARTNER FILTER  change handler
       ══════════════════════════════════════════════════════════════════ */
    $(document).on('change', '#partnerFilter', function() {
        _selectedPartner     = $(this).val();
        _selectedPartnerName = $(this).find('option:selected').text().trim();
        renderFilterTags();
        loadPartnerRevenueChart(_currentFrom, _currentTo, _selectedCustomer, _selectedPartner);
    });

    /* ══════════════════════════════════════════════════════════════════
       CLEAR FILTERS button
       ══════════════════════════════════════════════════════════════════ */
    $(document).on('click', '#clearFilters', function() {
        _selectedCustomer     = '';
        _selectedCustomerName = '';
        _selectedPartner      = '';
        _selectedPartnerName  = '';
        $('#customerFilter').val('');
        resetPartnerDropdown();
        renderFilterTags();
        loadPartnerRevenueChart(_currentFrom, _currentTo, '', '');
    });

    /* ══════════════════════════════════════════════════════════════════
       LOAD PARTNER REVENUE CHART
       ══════════════════════════════════════════════════════════════════ */
    function loadPartnerRevenueChart(dateFrom, dateTo, customerId, operatorId) {
        $('#partnerRevenueChart').html(
            '<div class="chart-loader"><i class="icon-spinner icon-spin"></i> Loading…</div>'
        );

        var postData = { period: 'custom', date_from: dateFrom, date_to: dateTo };
        if (customerId)  postData.selected_customer_id = customerId;
        if (operatorId)  postData.operator_id           = operatorId;

        $.ajax({
            url:      '../reports/datatables-scripts/get_partner_revenue_data.php',
            type:     'POST',
            data:     postData,
            dataType: 'json',
            success: function(resp) {
                if (!resp || resp.status !== 'success') {
                    $('#partnerRevenueChart').html(
                        '<div class="chart-loader" style="color:#e74c3c;">' +
                        '<i class="icon-warning-sign"></i> No data available.</div>'
                    );
                    return;
                }
                $('#partnerPeriodBadge').text(
                    moment(dateFrom).format('DD MMM YYYY') + ' – ' + moment(dateTo).format('DD MMM YYYY')
                );
                renderEchartPartnerRevenue('partnerRevenueChart', resp, ASSETS);
            },
            error: function() {
                $('#partnerRevenueChart').html(
                    '<div class="chart-loader" style="color:#e74c3c;">' +
                    '<i class="icon-warning-sign"></i> Failed to load partner data.</div>'
                );
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       MAIN  loadReportData(dateFrom, dateTo)
       ══════════════════════════════════════════════════════════════════ */
    function loadReportData(dateFrom, dateTo) {
        _currentFrom = dateFrom;
        _currentTo   = dateTo;

        if (_selectedCustomer) {
            loadPartnersForCustomerKeepSelection(_selectedCustomer, dateFrom, dateTo, _selectedPartner);
        }

        loadPartnerRevenueChart(dateFrom, dateTo, _selectedCustomer, _selectedPartner);
    }

    /*
     * Like loadPartnersForCustomer() but re-selects the previously chosen
     * partner if it is still present in the new list.
     */
    function loadPartnersForCustomerKeepSelection(customerId, dateFrom, dateTo, keepOperatorId) {
        var $group = $('#partnerFilterGroup');
        var $sel   = $('#partnerFilter');

        resetPartnerDropdown();
        if (!customerId) return;

        $group.addClass('select-loading');

        $.ajax({
            url:      '../reports/datatables-scripts/get_customers_and_partners.php',
            type:     'POST',
            data:     { action: 'partners_by_customer', selected_customer_id: customerId, period: 'custom', date_from: dateFrom, date_to: dateTo },
            dataType: 'json',
            success: function(resp) {
                $group.removeClass('select-loading');
                if (!resp || resp.status !== 'success') return;

                var partners = resp.partners || [];
                if (partners.length === 0) {
                    $sel.append('<option value="" disabled>No partners found</option>');
                    return;
                }

                var stillExists = false;
                $.each(partners, function(i, p) {
                    $sel.append('<option value="' + p.operator_id + '">' + p.partner_name + '</option>');
                    if (String(p.operator_id) === String(keepOperatorId)) stillExists = true;
                });

                $sel.prop('disabled', false);
                $('#partnerCountBadge').text(partners.length).show();

                /* Re-select previous partner if still valid */
                if (keepOperatorId && stillExists) {
                    $sel.val(keepOperatorId);
                } else if (keepOperatorId && !stillExists) {
                    /* Partner no longer has data in this range — clear it */
                    _selectedPartner     = '';
                    _selectedPartnerName = '';
                    renderFilterTags();
                }
            },
            error: function() {
                $group.removeClass('select-loading');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       DATERANGEPICKER
       ══════════════════════════════════════════════════════════════════ */
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
        }
    }, function(start, end) {
        $('#reportrange span').html(start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY'));
        loadReportData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
    });

    /* Set initial picker label */
    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );

    /* ══════════════════════════════════════════════════════════════════
       RESPONSIVE RESIZE
       ══════════════════════════════════════════════════════════════════ */
    window.addEventListener('resize', function () {
        var partnerInst = echarts.getInstanceByDom(document.getElementById('partnerRevenueChart'));
        if (partnerInst) partnerInst.resize();
    });

    /* ══════════════════════════════════════════════════════════════════
       INIT
       ══════════════════════════════════════════════════════════════════ */
    loadCustomers();                                              /* fill customer dropdown */
    loadReportData(_currentFrom, _currentTo);                    /* draw chart unfiltered  */
});
</script>

</body>
</html>
<?php
}
else {
    header('Content-Type: text/html');
    $url = ($accessarray['aattr']) ? "/$appname/$accessdenied" : "/$appname/$featuredenied";
    header('Location: '.$url);
}
?>
