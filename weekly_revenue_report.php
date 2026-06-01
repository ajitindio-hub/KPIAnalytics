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
    <link rel="shortcut icon" href="<?=$baseurl?>/<?=$appname?>/img/favicon.ico" />

    <!-- ECharts -->
    <script src="<?= $baseurl ?>/<?= $appname ?>/<?= $assetsDir ?>/echarts-6.0.0/package/dist/echarts.min.js"></script>
    <script src="<?= $baseurl ?>/<?= $appname ?>/<?= $assetsDir ?>/echarts-6.0.0/package/asset/echart-render.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════════
           PAGE HEADER
        ═══════════════════════════════════════════════════════ */
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

        /* ═══════════════════════════════════════════════════════
           SHARED CARD BASE
        ═══════════════════════════════════════════════════════ */
        .wpr-card {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .wpr-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f4fa;
        }
        .wpr-card-header h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
        }
        .wpr-badge-pill {
            font-size: 11px;
            font-weight: 500;
            background: #f0f4fa;
            color: #5a6a80;
            padding: 3px 10px;
            border-radius: 20px;
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

        /* ═══════════════════════════════════════════════════════
           SECTION 1 — PARTNER RANKING CHART
        ═══════════════════════════════════════════════════════ */
        #partnerRevenueChart { width: 100%; height: 340px; }

        /* ── Customer + Partner filter row ── */
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

        /* Partner count badge inside label */
        #partnerCountBadge {
            margin-left: 6px;
            background: #e87722;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 20px;
            letter-spacing: .3px;
        }

        /* Spinner on select while loading */
        .select-loading { position: relative; }
        .select-loading::after {
            content: '';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px; height: 14px;
            border: 2px solid #dde3ec;
            border-top-color: #e87722;
            border-radius: 50%;
            animation: selSpin .6s linear infinite;
        }
        @keyframes selSpin { to { transform: translateY(-50%) rotate(360deg); } }

        /* ═══════════════════════════════════════════════════════
           SECTION 2 — KPI HERO
        ═══════════════════════════════════════════════════════ */
        .wr-hero {
            background: #fff;
            border: 1px solid #e4e9f0;
            border-radius: 10px;
            padding: 22px 26px 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }
        .wr-hero-label {
            font-size: 11px;
            font-weight: 700;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 8px;
        }
        .wr-hero-value {
            font-size: 36px;
            font-weight: 800;
            color: #1e2b3c;
            line-height: 1;
            margin-bottom: 10px;
        }
        .wr-hero-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        .wr-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        .wr-badge.up      { background: #dcfce7; color: #16a34a; }
        .wr-badge.down    { background: #fee2e2; color: #dc2626; }
        .wr-badge.neutral { background: #f0f4fa; color: #5a6a80; }
        .wr-hero-period { font-size: 11px; color: #8a97a8; margin-top: 6px; }

        /* ═══════════════════════════════════════════════════════
           SECTION 3 — COUNTRY TABLE + BAR CHART
        ═══════════════════════════════════════════════════════ */
        .wr-body {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .wr-table-wrap {
            flex: 0 0 52%;
            max-width: 52%;
        }
        .wr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .wr-table thead tr { border-bottom: 2px solid #e4e9f0; }
        .wr-table thead th {
            padding: 7px 10px;
            color: #8a97a8;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            text-align: left;
        }
        .wr-table thead th.right { text-align: right; }
        .wr-table tbody tr { border-bottom: 1px solid #f5f7fa; transition: background .15s; }
        .wr-table tbody tr:last-child { border-bottom: none; }
        .wr-table tbody tr:hover { background: #f9fbff; }
        .wr-table tbody td { padding: 9px 10px; color: #2c3e50; vertical-align: middle; }
        .wr-table tbody td.right { text-align: right; }
        .wr-country-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .wr-flag {
            width: 22px; height: 16px;
            border-radius: 2px;
            object-fit: cover;
            flex-shrink: 0;
            background: #e4e9f0;
            display: inline-block;
        }
        .wr-country-code { font-weight: 600; color: #2c3e50; font-size: 12px; }
        .wr-revenue-cell {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            font-weight: 600;
        }
        .wr-arrow-up   { color: #16a34a; font-size: 10px; }
        .wr-arrow-down { color: #dc2626; font-size: 10px; }
        .wr-table-footer { margin-top: 12px; font-size: 11px; color: #8a97a8; }
        .wr-view-all {
            font-size: 12px;
            color: #2563eb;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .wr-view-all:hover { text-decoration: underline; }

        .wr-chart-wrap { width: 48%; flex-shrink: 0; }
        .wr-chart-label {
            font-size: 11px;
            font-weight: 600;
            color: #8a97a8;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
        }
        #weeklyRevenueBarChart { width: 100%; height: 340px; }

        /* ═══════════════════════════════════════════════════════
           SKELETON LOADER
        ═══════════════════════════════════════════════════════ */
        .wr-skeleton {
            background: linear-gradient(90deg, #f0f4fa 25%, #e4e9f2 50%, #f0f4fa 75%);
            background-size: 200% 100%;
            animation: wrSkelPulse 1.4s infinite;
            border-radius: 4px;
            display: inline-block;
            min-width: 80px;
            height: 1em;
        }
        @keyframes wrSkelPulse {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ═══════════════════════════════════════════════════════
           DIVIDER between sections
        ═══════════════════════════════════════════════════════ */
        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 8px 0 20px;
            color: #8a97a8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
        }
        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e4e9f0;
        }

        @media (max-width: 900px) {
            .wr-body { flex-direction: column; }
            .wr-chart-wrap { width: 100%; }
            .filter-row { flex-direction: column; }
            .filter-group { min-width: 100%; }
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
            $_SESSION['submenulevel1'] = "WeeklyPartnerRevenue";
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
                                <span>Weekly Partner Revenue</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="breadcrumb ais-report-header" style="margin-bottom:16px;">
                    <h3>
                        <i class="icon-bar-chart" style="color:#e87722;margin-right:6px;"></i>
                        Weekly Partner Revenue Report
                    </h3>
                    <span class="wpr-badge-pill" style="font-size:12px;padding:5px 14px;">
                        Last 7 Days
                    </span>
                </div>

                <!-- ═══════════════════════════════════════════════════
                     SECTION 1 — PARTNER RANKING BY REVENUE
                ═══════════════════════════════════════════════════ -->
                <div class="section-divider">Partner Ranking by Revenue</div>

                <div class="wpr-card">
                    <div class="wpr-card-header">
                        <h4>
                            <i class="icon-signal" style="color:#e87722;margin-right:6px;"></i>
                            Partner Ranking
                        </h4>
                        <span class="wpr-badge-pill" id="partnerPeriodBadge">Last 7 Days</span>
                    </div>

                    <!-- Customer + Partner filters -->
                    <div class="filter-row">
                        <div class="filter-group" id="customerFilterGroup">
                            <label for="customerFilter">
                                <i class="icon-building" style="margin-right:4px;"></i>Customer
                            </label>
                            <select id="customerFilter">
                                <option value="">— All Customers —</option>
                            </select>
                        </div>

                        <!--div class="filter-group" id="partnerFilterGroup">
                            <label for="partnerFilter">
                                <i class="icon-user" style="margin-right:4px;"></i>Partner
                                <span id="partnerCountBadge" style="display:none;"></span>
                            </label>
                            <select id="partnerFilter" disabled>
                                <option value="">— All Partners —</option>
                            </select>
                        </div>

                        <div style="display:flex;flex-direction:column;justify-content:flex-end;">
                            <button id="clearFilters" class="filter-clear-btn" title="Reset filters">
                                <i class="icon-remove"></i> Clear Filters
                            </button>
                        </div-->
                    </div>

                    <!-- Active filter tags -->
                    <div id="activeFilterTags" class="active-filters" style="display:none;"></div>

                    <!-- Chart -->
                    <div id="partnerRevenueChart">
                        <div class="chart-loader">
                            <i class="icon-spinner icon-spin"></i> Loading…
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════
                     SECTION 2 — WEEKLY REVENUE KPI HERO
                ═══════════════════════════════════════════════════ -->
                <div class="section-divider">Weekly Revenue Summary</div>

                <div class="wr-hero">
                    <div class="wr-hero-left">
                        <div class="wr-hero-label">Total Weekly Revenue</div>
                        <div class="wr-hero-value" id="wrHeroValue">
                            <span class="wr-skeleton" style="width:160px;height:36px;">&nbsp;</span>
                        </div>
                        <div class="wr-hero-meta">
                            <span id="wrHeroBadge">
                                <span class="wr-skeleton" style="width:80px;">&nbsp;</span>
                            </span>
                            <span id="wrHeroVs" style="color:#8a97a8;font-size:13px;font-weight:500;"></span>
                        </div>
                        <div class="wr-hero-period" id="wrHeroPeriod"></div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════
                     SECTION 3 — COUNTRY TABLE + BAR CHART
                ═══════════════════════════════════════════════════ -->
                <div class="wpr-card">
                    <div class="wpr-card-header">
                        <h4>Revenue by Country</h4>
                        <a class="wr-view-all" id="wrViewAll" style="display:none;"
                           onclick="toggleAllCountries()">View All →</a>
                    </div>

                    <div class="wr-body">
                        <!-- Table -->
                        <div class="wr-table-wrap">
                            <table class="wr-table">
                                <thead>
                                    <tr>
                                        <th>Country</th>
                                        <th class="right">Revenue ▼</th>
                                    </tr>
                                </thead>
                                <tbody id="wrTableBody">
                                    <tr>
                                        <td colspan="2" style="text-align:center;padding:40px;color:#8a97a8;">
                                            <i class="icon-spinner icon-spin"></i> Loading…
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="wr-table-footer" id="wrTableFooter"></div>
                        </div>

                        <!-- Bar Chart -->
                        <div class="wr-chart-wrap">
                            <div class="wr-chart-label">Revenue (USD)</div>
                            <div id="weeklyRevenueBarChart">
                                <div style="text-align:center;padding:60px 0;color:#8a97a8;">
                                    <i class="icon-spinner icon-spin"></i> Loading chart…
                                </div>
                            </div>
                        </div>
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

<script>
jQuery(document).ready(function () {
    App.init();

    /* ══════════════════════════════════════════════════════════════
       CONSTANTS
    ══════════════════════════════════════════════════════════════ */
    var FIXED_PERIOD = 7;   /* both sections always use last 7 days */
    var BASE         = '<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>';
    var ASSETS       = { nodataImg: BASE + '/img/nodata.jpg' };

    /* ══════════════════════════════════════════════════════════════
       STATE  (partner ranking filters)
    ══════════════════════════════════════════════════════════════ */
    var _selectedCustomer     = '';
    var _selectedCustomerName = '';
    var _selectedPartner      = '';
    var _selectedPartnerName  = '';

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 HELPERS — FILTER TAGS
    ══════════════════════════════════════════════════════════════ */
    function renderFilterTags() {
        var $wrap = $('#activeFilterTags');
        $wrap.empty();
        if (!_selectedCustomer && !_selectedPartner) { $wrap.hide(); return; }
        $wrap.show();
        if (_selectedCustomer) {
            $wrap.append(
                '<div class="filter-tag">'
              + '<i class="icon-building" style="font-size:11px;"></i>'
              + ' Customer: <b>' + _selectedCustomerName + '</b>'
              + '<span class="remove-tag" data-remove="customer" title="Remove">×</span>'
              + '</div>'
            );
        }
        if (_selectedPartner) {
            $wrap.append(
                '<div class="filter-tag">'
              + '<i class="icon-user" style="font-size:11px;"></i>'
              + ' Partner: <b>' + _selectedPartnerName + '</b>'
              + '<span class="remove-tag" data-remove="partner" title="Remove">×</span>'
              + '</div>'
            );
        }
    }

    $(document).on('click', '.remove-tag', function() {
        var target = $(this).data('remove');
        if (target === 'customer') {
            _selectedCustomer = _selectedCustomerName = '';
            _selectedPartner  = _selectedPartnerName  = '';
            $('#customerFilter').val('');
            resetPartnerDropdown();
        } else {
            _selectedPartner = _selectedPartnerName = '';
            $('#partnerFilter').val('');
        }
        renderFilterTags();
        loadPartnerRevenueChart(_selectedCustomer, _selectedPartner);
    });

    function resetPartnerDropdown() {
        $('#partnerFilter').find('option:not(:first)').remove()
                           .end().val('').prop('disabled', true);
        $('#partnerCountBadge').hide().text('');
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — LOAD CUSTOMERS  (once on page init)
    ══════════════════════════════════════════════════════════════ */
    function loadCustomers() {
        $('#customerFilterGroup').addClass('select-loading');
        $.ajax({
            url:      '../reports/datatables-scripts/get_customers_and_partners.php',
            type:     'POST',
            data:     { action: 'customers' },
            dataType: 'json',
            success: function(resp) {
                $('#customerFilterGroup').removeClass('select-loading');
                if (!resp || resp.status !== 'success') return;
                var $sel = $('#customerFilter');
                $sel.find('option:not(:first)').remove();
                $.each(resp.customers, function(i, c) {
                    $sel.append('<option value="' + c.id + '">' + c.name + '</option>');
                });
            },
            error: function() { $('#customerFilterGroup').removeClass('select-loading'); }
        });
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — LOAD PARTNERS for a customer
    ══════════════════════════════════════════════════════════════ */
    function loadPartnersForCustomer(customerId, keepOperatorId) {
        var $group = $('#partnerFilterGroup');
        var $sel   = $('#partnerFilter');
        resetPartnerDropdown();
        if (!customerId) return;

        $group.addClass('select-loading');
        $sel.prop('disabled', true);

        $.ajax({
            url:      '../reports/datatables-scripts/get_customers_and_partners.php',
            type:     'POST',
            data:     { action: 'partners_by_customer', selected_customer_id: customerId, period: FIXED_PERIOD },
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
                if (keepOperatorId && stillExists) {
                    $sel.val(keepOperatorId);
                } else if (keepOperatorId && !stillExists) {
                    _selectedPartner = _selectedPartnerName = '';
                    renderFilterTags();
                }
            },
            error: function() { $group.removeClass('select-loading'); }
        });
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — CUSTOMER change
    ══════════════════════════════════════════════════════════════ */
    $(document).on('change', '#customerFilter', function() {
        _selectedCustomer     = $(this).val();
        _selectedCustomerName = $(this).find('option:selected').text().trim();
        _selectedPartner      = _selectedPartnerName = '';
        loadPartnersForCustomer(_selectedCustomer, '');
        renderFilterTags();
        loadPartnerRevenueChart(_selectedCustomer, '');
    });

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — PARTNER change
    ══════════════════════════════════════════════════════════════ */
    $(document).on('change', '#partnerFilter', function() {
        _selectedPartner     = $(this).val();
        _selectedPartnerName = $(this).find('option:selected').text().trim();
        renderFilterTags();
        loadPartnerRevenueChart(_selectedCustomer, _selectedPartner);
    });

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — CLEAR filters
    ══════════════════════════════════════════════════════════════ */
    $(document).on('click', '#clearFilters', function() {
        _selectedCustomer = _selectedCustomerName = '';
        _selectedPartner  = _selectedPartnerName  = '';
        $('#customerFilter').val('');
        resetPartnerDropdown();
        renderFilterTags();
        loadPartnerRevenueChart('', '');
    });

    /* ══════════════════════════════════════════════════════════════
       SECTION 1 — LOAD PARTNER REVENUE CHART
    ══════════════════════════════════════════════════════════════ */
    function loadPartnerRevenueChart(customerId, operatorId) {
        $('#partnerRevenueChart').html(
            '<div class="chart-loader"><i class="icon-spinner icon-spin"></i> Loading…</div>'
        );
        var postData = { period: FIXED_PERIOD };
        if (customerId) postData.selected_customer_id = customerId;
        if (operatorId) postData.operator_id           = operatorId;

        $.ajax({
            url:      '../reports/datatables-scripts/get_partner_revenue_data.php',
            type:     'POST',
            data:     postData,
            dataType: 'json',
            success: function(resp) {
                if (!resp || resp.status !== 'success') {
                    $('#partnerRevenueChart').html(
                        '<div class="chart-loader" style="color:#e74c3c;">'
                      + '<i class="icon-warning-sign"></i> No data available.</div>'
                    );
                    return;
                }
                $('#partnerPeriodBadge').text('Last ' + FIXED_PERIOD + ' Days');
                renderEchartPartnerRevenue('partnerRevenueChart', resp, ASSETS);
            },
            error: function() {
                $('#partnerRevenueChart').html(
                    '<div class="chart-loader" style="color:#e74c3c;">'
                  + '<i class="icon-warning-sign"></i> Failed to load partner data.</div>'
                );
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION 2+3 HELPERS — WEEKLY REVENUE
    ══════════════════════════════════════════════════════════════ */
    var allRows    = [];
    var showingAll = false;
    var totalCount = 0;

    window.toggleAllCountries = function() {
        showingAll = !showingAll;
        var toShow = showingAll ? allRows : allRows.slice(0, 10);
        buildTable(toShow);
        renderEchartWeeklyRevenueBar('weeklyRevenueBarChart', toShow.slice(0, 10));
        $('#wrViewAll').text(showingAll ? '← Show Top 10' : 'View All →');
    };

    function fmtUSD(val) {
        var n = parseFloat(val) || 0;
        if (n >= 1000000) return '$' + (n / 1000000).toFixed(2) + 'M';
        if (n >= 1000)    return '$' + (n / 1000).toFixed(1) + 'k';
        return '$' + n.toFixed(2);
    }

    function badgeHtml(pct, trend) {
        if (pct === null || pct === undefined) {
            return '<span class="wr-badge neutral">—</span>';
        }
        var n     = parseFloat(pct);
        var sign  = n > 0 ? '+' : '';
        var cls   = trend === 'up' ? 'up' : 'down';
        var arrow = trend === 'up' ? '▲' : '▼';
        return '<span class="wr-badge ' + cls + '">' + arrow + ' ' + sign + n.toFixed(1) + '%</span>';
    }

    function trendArrow(trend) {
        if (trend === 'up')   return '<span class="wr-arrow-up">▲</span>';
        if (trend === 'down') return '<span class="wr-arrow-down">▼</span>';
        return '';
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION 3 — BUILD COUNTRY TABLE
    ══════════════════════════════════════════════════════════════ */
    function buildTable(rows) {
        if (!rows || rows.length === 0) {
            $('#wrTableBody').html(
                '<tr><td colspan="2" style="text-align:center;padding:40px;color:#8a97a8;">'
              + 'No data available.</td></tr>'
            );
            return;
        }
        var html = '';
        rows.forEach(function(r) {
            var countryCode = r.country_code || '—';
            var displayName = r.country_name || r.currency || '—';
            var revenueStr  = fmtUSD(r.current_usd);
            var arrow       = trendArrow(r.trend);
            html += '<tr>'
                  + '<td>'
                  +   '<div class="wr-country-cell">'
                  +     '<img class="wr-flag" src="' + BASE + '/img/flags/' + countryCode.toLowerCase() + '.png"'
                  +          ' onerror="this.style.display=\'none\'">'
                  +     '<span class="wr-country-code">' + displayName + '</span>'
                  +   '</div>'
                  + '</td>'
                  + '<td class="right">'
                  +   '<div class="wr-revenue-cell">' + revenueStr + ' ' + arrow + '</div>'
                  + '</td>'
                  + '</tr>';
        });
        $('#wrTableBody').html(html);
    }

    /* ══════════════════════════════════════════════════════════════
       SECTION 2+3 — LOAD WEEKLY REVENUE
    ══════════════════════════════════════════════════════════════ */
    function loadWeeklyRevenue() {
        /* Reset UI */
        $('#wrHeroValue').html('<span class="wr-skeleton" style="width:160px;height:36px;">&nbsp;</span>');
        $('#wrHeroBadge').html('<span class="wr-skeleton" style="width:80px;">&nbsp;</span>');
        $('#wrHeroVs').text('');
        $('#wrHeroPeriod').text('');
        $('#wrTableBody').html(
            '<tr><td colspan="2" style="text-align:center;padding:40px;color:#8a97a8;">'
          + '<i class="icon-spinner icon-spin"></i> Loading…</td></tr>'
        );
        $('#weeklyRevenueBarChart').html(
            '<div style="text-align:center;padding:60px 0;color:#8a97a8;">'
          + '<i class="icon-spinner icon-spin"></i> Loading chart…</div>'
        );
        $('#wrViewAll').hide();
        $('#wrTableFooter').text('');

        $.ajax({
            url:      './datatables-scripts/get_weekly_revenue_data.php',
            type:     'POST',
            dataType: 'json',
            success: function(resp) {
                if (!resp || resp.status !== 'success') {
                    $('#wrTableBody').html(
                        '<tr><td colspan="2" style="text-align:center;padding:40px;color:#e74c3c;">'
                      + 'Failed to load data.</td></tr>'
                    );
                    return;
                }

                var kpi = resp.kpi;

                /* KPI Hero */
                $('#wrHeroValue').text(fmtUSD(kpi.total_current_usd));
                $('#wrHeroBadge').html(badgeHtml(kpi.change_pct, kpi.trend));
                $('#wrHeroVs').html(
                    'vs ' + kpi.prev_week_label + ' &nbsp;'
                  + (kpi.trend === 'up'
                        ? '<span style="color:#16a34a;">↑</span>'
                        : '<span style="color:#dc2626;">↓</span>')
                );
                $('#wrHeroPeriod').text('Period: ' + kpi.period);

                /* Store rows + render */
                allRows    = resp.table || [];
                totalCount = resp.total_count || 0;

                buildTable(allRows.slice(0, 10));
                renderEchartWeeklyRevenueBar('weeklyRevenueBarChart', allRows.slice(0, 10));

                /* Footer + View All link */
                $('#wrTableFooter').text(
                    'Showing Top ' + Math.min(10, allRows.length) + ' of ' + totalCount + ' Countries'
                );
                if (totalCount > 10) $('#wrViewAll').show();
            },
            error: function() {
                $('#wrTableBody').html(
                    '<tr><td colspan="2" style="text-align:center;padding:40px;color:#e74c3c;">'
                  + '<i class="icon-warning-sign"></i> Failed to load data.</td></tr>'
                );
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════
       RESIZE  — keep both charts responsive
    ══════════════════════════════════════════════════════════════ */
    window.addEventListener('resize', function() {
        var partnerInst = echarts.getInstanceByDom(document.getElementById('partnerRevenueChart'));
        var weeklyInst  = echarts.getInstanceByDom(document.getElementById('weeklyRevenueBarChart'));
        if (partnerInst) partnerInst.resize();
        if (weeklyInst)  weeklyInst.resize();
    });

    /* ══════════════════════════════════════════════════════════════
       INIT — fire both loaders in parallel
    ══════════════════════════════════════════════════════════════ */
    loadCustomers();
    loadPartnerRevenueChart('', '');
    loadWeeklyRevenue();
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
