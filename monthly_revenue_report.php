<?php
if (session_id() == '') session_start();
header("Cache_control:private");

$accessarray = $_SESSION['accessarray'];
require('../include/checkdata.php');
checkExpiredSession($accessarray);
if($_SESSION['customerid'] != 1) {

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
    /* ═══════════════════════════════════════════════
       PAGE HEADER
    ═══════════════════════════════════════════════ */
    .mr-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 0;
    }
    .mr-page-header-left h3 {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
        color: #1e2b3c;
    }
    .mr-page-header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* ═══════════════════════════════════════════════
       OPERATOR MULTISELECT — exact .cms-* from reference
    ═══════════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════════
       KPI CARDS
    ═══════════════════════════════════════════════ */
    .mr-kpi-row {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }
    .mr-kpi-card {
        flex: 1;
        background: #fff;
        border: 1px solid #e4e9f0;
        border-radius: 8px;
        padding: 18px 20px 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        position: relative;
        overflow: hidden;
    }
    .mr-kpi-card-label {
        font-size: 11px;
        font-weight: 600;
        color: #8a97a8;
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 10px;
    }
    .mr-kpi-card-value {
        font-size: 26px;
        font-weight: 700;
        color: #1e2b3c;
        line-height: 1;
    }
    .mr-growth-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 14px;
        font-weight: 700;
    }
    .mr-growth-badge.up   { color: #16a34a; }
    .mr-growth-badge.down { color: #dc2626; }
    .mr-growth-badge .arrow { font-size: 12px; }

    /* ═══════════════════════════════════════════════
       TREND CHART BOX
    ═══════════════════════════════════════════════ */
    .mr-chart-box {
        background: #fff;
        border: 1px solid #e4e9f0;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .mr-chart-box-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .mr-chart-box-header h4 {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #2c3e50;
    }
    .mr-chart-legend {
        display: flex;
        gap: 20px;
        font-size: 11px;
        color: #5a6a80;
        font-weight: 500;
    }
    .mr-chart-legend-dot {
        display: inline-block;
        width: 28px;
        height: 3px;
        border-radius: 2px;
        margin-right: 5px;
        vertical-align: middle;
    }
    #revenueLineChart { width: 100%; height: 270px; }

    /* ═══════════════════════════════════════════════
       COMPARISON TABLE
    ═══════════════════════════════════════════════ */
    .mr-table-box {
        background: #fff;
        border: 1px solid #e4e9f0;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .mr-table-box-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f0f4fa;
    }
    .mr-table-box-header h4 { margin: 0; font-size: 13px; font-weight: 600; color: #2c3e50; }
    .mr-export-btns { display: flex; gap: 8px; }
    .mr-export-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: opacity .2s;
    }
    .mr-export-btn:hover { opacity: .85; }
    .mr-export-btn.excel { background: #16a34a; color: #fff; }

    .mr-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .mr-table thead tr { border-bottom: 2px solid #e4e9f0; }
    .mr-table thead th {
        padding: 8px 12px;
        color: #8a97a8;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .4px;
        text-align: left;
    }
    .mr-table tbody tr { border-bottom: 1px solid #f5f7fa; transition: background .15s; }
    .mr-table tbody tr:last-child { border-bottom: none; }
    .mr-table tbody tr:hover { background: #f9fbff; }
    .mr-table tbody tr.mtd-row { background: #f0f7ff; font-weight: 700; }
    .mr-table tbody tr.mtd-row:hover { background: #e6f2ff; }
    .mr-table tbody td { padding: 10px 12px; color: #2c3e50; vertical-align: middle; }

    .mr-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .mr-badge.final { background: #f0f4fa; color: #5a6a80; }
    .mr-badge.mtd   { background: #dbeafe; color: #1d4ed8; }

    .mr-tbl-up   { color: #16a34a; font-weight: 600; }
    .mr-tbl-down { color: #dc2626; font-weight: 600; }
    .mr-tbl-na   { color: #b0bec5; }

    .mr-table-note {
        font-size: 11px;
        color: #8a97a8;
        font-style: italic;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #f0f4fa;
    }

    /* ═══════════════════════════════════════════════
       LOADING SKELETON
    ═══════════════════════════════════════════════ */
    .mr-skeleton {
        background: linear-gradient(90deg, #f0f4fa 25%, #e4e9f2 50%, #f0f4fa 75%);
        background-size: 200% 100%;
        animation: skeletonPulse 1.4s infinite;
        border-radius: 4px;
        display: inline-block;
        min-width: 80px;
        height: 1em;
    }
    @keyframes skeletonPulse {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .ais-report-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
    }
</style>
</head>

<body class="page-header-fixed">
    <?php include('../include/header.php'); ?>
    <div class="page-container row-fluid">
        <?php include('../include/core-plugins.php'); ?>
        <?php
            $_SESSION['mainmenu']      = "analytics";
            $_SESSION['submenu']       = "revenueMenu";
            $_SESSION['submenulevel1'] = "MonthlyRevenue";
            include('../include/sidebar_temp.php');
        ?>

        <div class="page-content">
            <div class="container-fluid">

                <!-- ── Breadcrumb ── -->
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
                                <span>Monthly Revenue Analysis</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ── Page Header ── -->
                <div class="breadcrumb ais-report-header" style="margin-bottom:16px;">
                    <div class="mr-page-header">

                        <!-- Left: title -->
                        <div class="mr-page-header-left">
                            <h3>
                                <i class="icon-bar-chart" style="color:#e87722;margin-right:6px;"></i>
                                Monthly Revenue Analysis Report
                            </h3>
                        </div>

                        <!-- Right: operator multiselect -->
                        <div class="mr-page-header-right">
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
                                        <div class="cms-dd-empty"><i class="icon-spinner icon-spin"></i> Loading…</div>
                                    </div>
                                    <div class="cms-dd-footer">
                                        <button class="cms-dd-apply" id="cmsApply">Apply Filter</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ══ KPI CARDS ══ -->
                <div class="mr-kpi-row">
                    <div class="mr-kpi-card">
                        <div class="mr-kpi-card-label">Total Revenue MTD</div>
                        <div class="mr-kpi-card-value" id="mrKpiMtd"><span class="mr-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                    </div>
                    <div class="mr-kpi-card">
                        <div class="mr-kpi-card-label" id="mrKpiPrevLabel">Prev Month Revenue</div>
                        <div class="mr-kpi-card-value" id="mrKpiPrev"><span class="mr-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                    </div>
                    <div class="mr-kpi-card">
                        <div class="mr-kpi-card-label">MoM Growth (MTD)</div>
                        <div class="mr-kpi-card-value" id="mrKpiMom"><span class="mr-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                    </div>
                    <div class="mr-kpi-card">
                        <div class="mr-kpi-card-label">YoY Growth (MTD)</div>
                        <div class="mr-kpi-card-value" id="mrKpiYoy"><span class="mr-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                    </div>
                </div>

                <!-- ══ REVENUE TREND CHART ══ -->
                <div class="mr-chart-box">
                    <div class="mr-chart-box-header">
                        <h4><i class="icon-signal" style="color:#e87722;margin-right:5px;"></i> Revenue Trend (Last 12 Months)</h4>
                        <div class="mr-chart-legend">
                            <span><span class="mr-chart-legend-dot" style="background:#2563eb;"></span>Current Year</span>
                            <span><span class="mr-chart-legend-dot" style="background:#b0bec5; border-top:2px dashed #b0bec5; height:0; width:28px; display:inline-block; vertical-align:middle; margin-right:5px;"></span>Previous Year</span>
                        </div>
                    </div>
                    <div id="revenueLineChart">
                        <div style="text-align:center;padding:60px 0;color:#8a97a8;">
                            <i class="icon-spinner icon-spin"></i> Loading chart…
                        </div>
                    </div>
                </div>

                <!-- ══ MONTHLY COMPARISON TABLE ══ -->
                <div class="mr-table-box">
                    <div class="mr-table-box-header">
                        <h4><i class="icon-table" style="color:#e87722;margin-right:5px;"></i> Monthly Revenue Comparison</h4>
                        <div class="mr-export-btns">
                            <button class="mr-export-btn excel" onclick="exportRevenueExcel()">
                                <i class="icon-file-excel-alt"></i> Export CSV
                            </button>
                        </div>
                    </div>

                    <table class="mr-table" id="mrRevenueTable">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Revenue</th>
                                <th>Prev Month Revenue</th>
                                <th>MoM Growth</th>
                                <th>YoY Growth</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="mrRevenueTableBody">
                            <tr>
                                <td colspan="6" style="text-align:center;padding:30px;color:#8a97a8;">
                                    <i class="icon-spinner icon-spin"></i> Loading…
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mr-table-note" id="mrTableNote" style="display:none;">
                        <i class="icon-info-sign"></i>
                        <em>Note: The current month reflects Month-To-Date performance and is compared against the same period of the previous month to ensure accurate trend analysis.</em>
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
jQuery(document).ready(function ($) {
    App.init();
    UIJQueryUI.init();
    FormSamples.init();

    /* ══════════════════════════════════════════════════════════════════
       STATE
    ══════════════════════════════════════════════════════════════════ */
    var allOperators        = [];   // [{ id, name }, …]
    var selectedOperatorIds = [];   // array of numeric IDs

    /* ══════════════════════════════════════════════════════════════════
       HELPERS
    ══════════════════════════════════════════════════════════════════ */
    function cmsEsc(s) {
        return String(s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function formatCurrency(val) {
        var num = parseFloat(val) || 0;
        return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function growthBadge(pct, forTable) {
        if (pct === null || pct === undefined) {
            return forTable
                ? '<span class="mr-tbl-na">—</span>'
                : '<span class="mr-growth-badge" style="color:#b0bec5;">—</span>';
        }
        var n     = parseFloat(pct);
        var dir   = n >= 0 ? 'up' : 'down';
        var arrow = n >= 0 ? '▲' : '▼';
        var sign  = n > 0 ? '+' : '';
        if (forTable) {
            return '<span class="mr-tbl-' + dir + '">' + arrow + ' ' + sign + n.toFixed(1) + '%</span>';
        }
        return '<span class="mr-growth-badge ' + dir + '">'
             + '<span class="arrow">' + arrow + '</span>'
             + sign + n.toFixed(1) + '%'
             + '</span>';
    }

    /* ══════════════════════════════════════════════════════════════════
       CMS MULTISELECT — exact pattern from reference file
    ══════════════════════════════════════════════════════════════════ */

    /* Toggle open/close */
    $('#cmsTrigger').on('click', function (e) {
        e.stopPropagation();
        $('#cmsDropdown').hasClass('open') ? closeCms() : openCms();
    });

    /* Close on outside click */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#cmsWrap').length) closeCms();
    });

    /* Live search filter */
    $('#cmsSearch').on('input', function () {
        var q = $(this).val().toLowerCase();
        renderCmsList(allOperators.filter(function (o) {
            return o.name.toLowerCase().indexOf(q) !== -1;
        }));
    });

    /* Select All (respects current search filter) */
    $('#cmsSelectAll').on('click', function () {
        var q       = $('#cmsSearch').val().toLowerCase();
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
    $('#cmsClearAll').on('click', function () {
        selectedOperatorIds = [];
        var q = $('#cmsSearch').val().toLowerCase();
        renderCmsList(q
            ? allOperators.filter(function (o) { return o.name.toLowerCase().indexOf(q) !== -1; })
            : allOperators
        );
        updateCmsTrigger();
    });

    /* Apply → close panel and reload data */
    $('#cmsApply').on('click', function () {
        closeCms();
        loadMonthlyRevenue();
    });

    /* ── Open / Close helpers ── */
    function openCms() {
        $('#cmsDropdown').addClass('open');
        $('#cmsTrigger').addClass('active');
        $('#cmsArrow').addClass('open');
        $('#cmsSearch').val('');
        renderCmsList(allOperators);
        setTimeout(function () { $('#cmsSearch').focus(); }, 50);
    }

    function closeCms() {
        $('#cmsDropdown').removeClass('open');
        $('#cmsTrigger').removeClass('active');
        $('#cmsArrow').removeClass('open');
    }

    /* ── Render list rows ── */
    function renderCmsList(operators) {
        if (!operators || !operators.length) {
            $('#cmsList').html('<div class="cms-dd-empty">No operators found.</div>');
            return;
        }
        var html = '';
        operators.forEach(function (o) {
            var sel = selectedOperatorIds.indexOf(o.id) !== -1;
            html += '<div class="cms-dd-item' + (sel ? ' sel' : '') + '" data-id="' + o.id + '">'
                  + '<input type="checkbox"' + (sel ? ' checked' : '') + '>'
                  + '<span>' + cmsEsc(o.name) + '</span>'
                  + '</div>';
        });
        $('#cmsList').html(html);

        /* Row click toggles selection */
        $('#cmsList .cms-dd-item').on('click', function () {
            var id  = $(this).data('id');
            var idx = selectedOperatorIds.indexOf(id);
            if (idx === -1) {
                selectedOperatorIds.push(id);
                $(this).addClass('sel').find('input').prop('checked', true);
            } else {
                selectedOperatorIds.splice(idx, 1);
                $(this).removeClass('sel').find('input').prop('checked', false);
            }
            updateCmsTrigger();
        });
    }

    /* ── Update trigger button label + inline tags ── */
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

        /* Show up to 2 name pills */
        selectedOperatorIds.slice(0, 2).forEach(function (id) {
            var op = allOperators.find(function (o) { return o.id == id; });
            if (!op) return;
            var $tag = $(
                '<span class="cms-tag">' + cmsEsc(op.name)
                + ' <span class="cms-tag-x" data-id="' + id + '">×</span></span>'
            );
            $row.append($tag);
        });

        if (selectedOperatorIds.length > 2) {
            $row.append('<span class="cms-tag">+' + (selectedOperatorIds.length - 2) + ' more</span>');
        }

        /* × on individual pills → remove + reload */
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
            loadMonthlyRevenue();
        });
    }

    /* ── Fetch operator list from server ── */
    function loadOperators() {
        $.ajax({
            url     : './datatables-scripts/get_operators.php',
            type    : 'POST',
            dataType: 'json',
            success : function (resp) {
                if (resp && resp.status === 'success' && resp.operators && resp.operators.length) {
                    allOperators = resp.operators.map(function (o) {
                        return { id: o.operator_id, name: o.account_name };
                    });
                    renderCmsList(allOperators);
                } else {
                    $('#cmsList').html('<div class="cms-dd-empty">No operators found.</div>');
                }
                /* Fire first data load AFTER operators are ready */
                loadMonthlyRevenue();
            },
            error: function () {
                $('#cmsList').html('<div class="cms-dd-empty">Error loading operators.</div>');
                loadMonthlyRevenue();
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       BUILD REVENUE TABLE
    ══════════════════════════════════════════════════════════════════ */
    function buildRevenueTable(rows, currency) {
        if (!rows || rows.length === 0) {
            $('#mrRevenueTableBody').html(
                '<tr><td colspan="6" style="text-align:center;padding:30px;color:#8a97a8;">No data available.</td></tr>'
            );
            return;
        }
        var html = '';
        rows.forEach(function (r) {
            var rowClass     = r.is_mtd ? ' class="mtd-row"' : '';
            var revenueLabel = formatCurrency(r.current_revenue);
            var prevLabel    = formatCurrency(r.previous_revenue);
            var momHtml      = growthBadge(r.change_vs_prev_pct, true);
            var yoyHtml      = growthBadge(r.change_vs_y1_pct,   true);
            var statusHtml   = r.status === 'MTD'
                ? '<span class="mr-badge mtd">MTD (Data till ' + new Date().getDate() + ' ' + r.month_label.split(' ')[0] + ')</span>'
                : '<span class="mr-badge final">Final</span>';
            var monthDisplay = r.is_mtd
                ? '<strong>' + r.month_label + ' (MTD)</strong>'
                : r.month_label;

            html += '<tr' + rowClass + '>'
                  + '<td>' + monthDisplay + '</td>'
                  + '<td style="font-weight:600;">' + revenueLabel + '</td>'
                  + '<td>' + prevLabel + '</td>'
                  + '<td>' + momHtml + '</td>'
                  + '<td>' + yoyHtml + '</td>'
                  + '<td>' + statusHtml + '</td>'
                  + '</tr>';
        });
        $('#mrRevenueTableBody').html(html);
        $('#mrTableNote').show();
    }

    /* ══════════════════════════════════════════════════════════════════
       MAIN LOADER — passes selected operator IDs to backend
    ══════════════════════════════════════════════════════════════════ */
    window.loadMonthlyRevenue = function () {
        /* Reset to skeletons */
        ['mrKpiMtd','mrKpiPrev','mrKpiMom','mrKpiYoy'].forEach(function (id) {
            $('#' + id).html('<span class="mr-skeleton">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>');
        });
        $('#revenueLineChart').html(
            '<div style="text-align:center;padding:60px 0;color:#8a97a8;">'
            + '<i class="icon-spinner icon-spin"></i> Loading chart…</div>'
        );
        $('#mrRevenueTableBody').html(
            '<tr><td colspan="6" style="text-align:center;padding:30px;color:#8a97a8;">'
            + '<i class="icon-spinner icon-spin"></i> Loading…</td></tr>'
        );
        $('#mrTableNote').hide();

        $.ajax({
            url     : './datatables-scripts/get_monthly_revenue_data.php',
            type    : 'POST',
            dataType: 'json',
            data    : {
                operator_ids: selectedOperatorIds.join(',')   // empty string = no filter = all data
            },
            success: function (resp) {
                if (!resp || resp.status !== 'success') {
                    $('#mrRevenueTableBody').html(
                        '<tr><td colspan="6" style="text-align:center;padding:30px;color:#e74c3c;">Failed to load data.</td></tr>'
                    );
                    return;
                }

                var kpi = resp.kpi;

                /* KPI cards */
                $('#mrKpiMtd').text(formatCurrency(kpi.mtd_revenue));
                $('#mrKpiPrevLabel').text(kpi.prev_month_label ? kpi.prev_month_label + ' Revenue' : 'Prev Month Revenue');
                $('#mrKpiPrev').text(formatCurrency(kpi.prev_revenue));
                $('#mrKpiMom').html(growthBadge(kpi.mom_pct, false));
                $('#mrKpiYoy').html(growthBadge(kpi.yoy_pct, false));

                /* Chart */
                renderEchartRevenueTrend('revenueLineChart', resp.chart);

                /* Table */
                buildRevenueTable(resp.table, resp.currency);
            },
            error: function () {
                $('#mrRevenueTableBody').html(
                    '<tr><td colspan="6" style="text-align:center;padding:30px;color:#e74c3c;">'
                    + '<i class="icon-warning-sign"></i> Failed to load data.</td></tr>'
                );
            }
        });
    };

    /* ══════════════════════════════════════════════════════════════════
       EXPORT CSV
    ══════════════════════════════════════════════════════════════════ */
    window.exportRevenueExcel = function () {
        var table = document.getElementById('mrRevenueTable');
        var rows  = table.querySelectorAll('tr');
        var csv   = [];
        rows.forEach(function (r) {
            var cols = r.querySelectorAll('th,td');
            var row  = [];
            cols.forEach(function (c) { row.push('"' + c.innerText.replace(/"/g,'""') + '"'); });
            csv.push(row.join(','));
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        var a    = document.createElement('a');
        a.href   = URL.createObjectURL(blob);
        a.download = 'monthly_revenue.csv';
        a.click();
    };

    /* ══════════════════════════════════════════════════════════════════
       INIT — load operators first, then data
    ══════════════════════════════════════════════════════════════════ */
    loadOperators();

});
</script>

</body>
</html>
<?
}
else {
    header('Content-Type: text/html');
    $url = ($accessarray['aattr']) ? "/$appname/$accessdenied" : "/$appname/$featuredenied";
    header('Location: '.$url);
}
?>
