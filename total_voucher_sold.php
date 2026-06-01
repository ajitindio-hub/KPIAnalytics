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
		<link href="<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/plugins/select2/select2.css" rel="stylesheet" />
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

		/* ── Period filter + daterangepicker ── */
		.du-filter-wrap {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}
		.du-period-select {
			height: 32px;
            margin-top:10px;
			padding: 0 26px 0 10px;
			font-size: 12px;
			font-weight: 600;
			color: #3d5166;
			background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%237a8da0'/%3E%3C/svg%3E") no-repeat right 8px center;
			border: 1px solid #dde3ec;
			border-radius: 6px;
			appearance: none; -webkit-appearance: none;
			cursor: pointer; min-width: 145px;
			transition: border-color .15s;
		}
		.du-period-select:focus { outline: none; border-color: #e87722; }
		.du-custom-date-wrap { display: flex; align-items: center; }
		.du-btn-daterange {
			display: inline-flex; align-items: center; gap: 6px;
			height: 32px; padding: 0 12px;
			font-size: 12px; font-weight: 600;
			color: #3d5166; background: #fff;
			border: 1px solid #dde3ec; border-radius: 6px;
			cursor: pointer; white-space: nowrap;
			transition: border-color .15s;
		}
		.du-btn-daterange:hover { border-color: #e87722; background: #fff8f3; }
		.du-btn-daterange i.icon-calendar { color: #e87722; font-size: 13px; }

		.du-voucher-section {
			margin-top: 20px;
		}
		.du-voucher-section-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 10px;
			margin-bottom: 14px;
			min-height: 44px;
		}
		.du-voucher-section-header h4 {
			margin: 0;
			font-size: 14px;
			font-weight: 700;
			color: #2c3e50;
			flex-shrink: 0;
		}
		/* ── Customer filter box: bordered card ── */
		.du-customer-filter-wrap {
			display: inline-flex;
			align-items: center;
			gap: 10px;
			flex-wrap: nowrap;
		}
		.du-customer-label {
			font-size: 12px;
			font-weight: 600;
			color: #5a6a80;
			white-space: nowrap;
			flex-shrink: 0;
		}

		/* ── Customer Multiselect (CMS) ── */
		.cms-outer-wrap {
			position: relative;
			min-width: 280px;
			width: 280px;
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
		.cms-trigger:hover { border-color: #e87722; }
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
			left: 0;
			right: 0;
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
		.cms-dd-list { max-height: 240px; overflow-y: auto; flex: 1; }
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
		.cms-dd-item.sel { background: #eef2ff; }
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

		/* ── Voucher-by-plan table card ── */
		.du-plan-table-card {
			background: #fff;
			border: 1px solid #dde3ec;
			border-radius: 8px;
			box-shadow: 0 1px 4px rgba(0,0,0,.06);
			overflow: hidden;
		}
		.du-plan-table-card-header {
			display: flex; align-items: center;
			justify-content: space-between;
			padding: 12px 16px;
			border-bottom: 1px solid #e4e9f0;
			background: #f8fafc;
		}
		.du-plan-table-card-header h5 {
			margin: 0; font-size: 13px;
			font-weight: 700; color: #2c3e50;
		}
		/* DataTables wrapper padding */
		#voucherPlanTable_wrapper {
			padding: 12px 16px 16px;
		}
		#voucherPlanTable_wrapper .dataTables_filter input {
			border: 1px solid #dde3ec;
			border-radius: 6px; padding: 4px 8px;
			font-size: 12px;
		}
		#voucherPlanTable_wrapper .dataTables_length select {
			border: 1px solid #dde3ec;
			border-radius: 6px; padding: 2px 6px;
			font-size: 12px;
		}
		#voucherPlanTable {
			width: 100% !important;
			font-size: 12px;
			border: 1px solid #e4e9f0 !important;
			border-collapse: collapse !important;
		}
		#voucherPlanTable thead th {
			background: #f0f4fa;
			color: #5a6a80;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .4px;
			border: 1px solid #e0e6ef !important;
			padding: 9px 12px !important;
			text-align: left;
		}
		/* Sr. No. column centered */
		#voucherPlanTable thead th:first-child,
		#voucherPlanTable tbody td:first-child {
			text-align: center !important;
			width: 52px !important;
			color: #8a97a8;
			font-weight: 600;
		}
		/* Vouchers Used column — right-aligned */
		#voucherPlanTable thead th:last-child,
		#voucherPlanTable tbody td:last-child {
			text-align: right !important;
		}
		#voucherPlanTable tbody td {
			vertical-align: middle;
			color: #2c3e50;
			border: 1px solid #edf1f7 !important;
			padding: 8px 12px !important;
		}
		#voucherPlanTable tbody tr:hover { background: #f5f8ff; }
		#voucherPlanTable tbody tr:nth-child(even) { background: #fafbfd; }
		#voucherPlanTable tbody tr:nth-child(even):hover { background: #f0f5ff; }
		.du-plan-count-badge {
			display: inline-block;
			background: #eef2ff; color: #3b7ef8;
			font-size: 11px; font-weight: 700;
			padding: 2px 10px; border-radius: 20px;
		}
		/* DataTables pagination alignment */
		#voucherPlanTable_wrapper .dataTables_paginate {
			text-align: right;
			margin-top: 10px;
		}
		#voucherPlanTable_wrapper .dataTables_info {
			font-size: 11px;
			color: #8a97a8;
			padding-top: 10px;
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
.kpi-text {
    font-size: 11px;
    color: #8a97a8;
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
				$_SESSION['submenulevel1'] = "VoucherSold";
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
									<span>Total Voucher Sold</span>
								</li>
							</ul>
						</div>
					</div>

					<!-- Page Header -->

					<div class="breadcrumb ais-report-header">
			<div class="du-page-header">
		<h3>
			<i class="icon-signal" style="color:#e87722;"></i>
			Voucher Volume Distribution 
		</h3>
		<div class="du-filter-wrap">
			<div class="du-custom-date-wrap">
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
					<!-- ══ KPI TILES — 3 separate boxes with gap ══ -->

					<!-- ══ PAGE HEADER: Title LEFT — Buttons RIGHT ══ -->


	<!-- ══ KPI TILES ══ -->
	<!--div class="du-kpi-tiles">
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
	</div-->
<!--div class="du-kpi-tiles">
    <div class="du-kpi-tile dl">
        <div class="tile-label">
            <i class="icon-ticket"></i> Total Vouchers Sold
        </div>

        <span>
            <?= $period == 7 ? 'Last 7 Days' : 'Last 30 Days' ?>
        </span>

        <div class="tile-value" id="kpiTotalVouchers">–</div>
    </div>
</div-->
<div class="du-kpi-tiles">
    <div class="du-kpi-tile du-kpi-split">

        <!-- Left: Label -->
        <div class="kpi-left">
            <i class="icon-ticket"></i>
            <div>
				<div class="kpi-title">Total Vouchers Sold</div>
				<div class="kpi-sub" id="kpiPeriodText">
    Last 7 Days
</div>			
			</div>
        </div>

        <!-- Middle: Total -->
        <div class="kpi-mid">
            <div class="kpi-value" id="kpiTotalVouchers">–</div>
            <div class="kpi-text">Vouchers</div>
        </div>

        <!-- Right: Secondary (example: Others / Remaining) -->
        <!--div class="kpi-right">
            <div class="kpi-value" id="kpiOtherVouchers">–</div>
			<div class="kpi-text" id="kpiRightPeriodText">
    Last 7 Days
</div>		   
		   </div-->

    </div>
</div>
<!-- ══ Donut Box + Table Box ══ -->
<div class="du-outer">

    <!-- Left: Donut Box -->
    <div class="du-donut-box">
        <div class="du-donut-box-title">Voucher Distribution</div>
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
                Voucher Sales Breakdown
            </h4>
        </div>
        <table class="du-table">
            <thead>
                <tr>
                    <th>Partner</th>
                    <th>Vouchers</th>
                    <th>Mix %</th>
                </tr>
            </thead>
            <!--tbody id="packLegendBody"-->
            <tbody id="partnerBody">
                <tr>
                    <td colspan="3" style="text-align:center;padding:30px;color:#8a97a8;">
                        <i class="icon-spinner icon-spin"></i> Loading…
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div><!-- /.du-outer -->

				<!-- ══ Customer Voucher by Plan Table ══ -->
				<div class="du-voucher-section">

					<!-- Section header with customer multi-select -->
					<div class="du-voucher-section-header">
						<h4>
							<i class="icon-list-ul" style="color:#e87722;margin-right:6px;"></i>
							Voucher Usage by Plan &amp; Customer
						</h4>
						<div class="du-customer-filter-wrap">
							<span class="du-customer-label">Filter by Customer:</span>
							<!-- Customer Multiselect -->
							<div class="cms-outer-wrap" id="cmsWrap">
								<div class="cms-trigger" id="cmsTrigger">
									<div class="cms-tags-row" id="cmsTagsRow">
										<span class="cms-placeholder" id="cmsPlaceholder">All Customers</span>
									</div>
									<span class="cms-count-badge" id="cmsCountBadge" style="display:none;"></span>
									<span class="cms-arrow" id="cmsArrow">▼</span>
								</div>
								<div class="cms-dropdown" id="cmsDropdown">
									<div class="cms-dd-search">
										<input type="text" id="cmsSearch" placeholder="Search customers…" autocomplete="off">
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
						</div>
					</div>

					<!-- Table card -->
					<div class="du-plan-table-card">
						<div class="du-plan-table-card-header">
							<h5>
								<i class="icon-ticket" style="color:#3b7ef8;margin-right:5px;"></i>
								Plan-wise Voucher Count
							</h5>
							<span id="voucherPlanBadge" style="font-size:11px;color:#8a97a8;"></span>
						</div>
						<div style="padding:12px 16px;">
							<table id="voucherPlanTable" class="display nowrap" style="width:100%">
								<thead>
									<tr>
										<th>#</th>
										<th>Customer</th>
										<th>Plan Name</th>
										<th>Vouchers Used</th>
									</tr>
								</thead>
								<tbody id="voucherPlanBody">
									<tr>
										<td colspan="4" style="text-align:center;padding:30px;color:#8a97a8;">
											<i class="icon-spinner icon-spin"></i> Loading…
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

				</div><!-- /.du-voucher-section -->
                

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

    /* ── State ── */
    var currentPeriod = 'custom';
    var currentFrom   = moment().subtract(6, 'days').format('YYYY-MM-DD');
    var currentTo     = moment().format('YYYY-MM-DD');
    var voucherDT     = null;

    /* ══ Daterangepicker — always visible, always custom ══ */
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
           // 'Today'       : [moment(), moment()],
            'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           // 'This Month'  : [moment().startOf('month'), moment().endOf('month')],
           // 'Last Month'  : [moment().subtract(1, 'month').startOf('month'),
		   //                  moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end) {
        currentFrom = start.format('YYYY-MM-DD');
        currentTo   = end.format('YYYY-MM-DD');
        $('#reportrange span').html(start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY'));
        var label = start.format('DD MMM YYYY') + ' – ' + end.format('DD MMM YYYY');
        $('#kpiPeriodText').text(label);
        $('#kpiRightPeriodText').text(label);
        loadReportData('custom', currentFrom, currentTo);
        loadVoucherPlanTable(selectedCustomerIds, 'custom', currentFrom, currentTo);
    });
    $('#reportrange span').html(
        moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY')
    );
    $('#kpiPeriodText').text(moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY'));
    $('#kpiRightPeriodText').text(moment().subtract(6, 'days').format('DD MMM YYYY') + ' – ' + moment().format('DD MMM YYYY'));

    /* ══ loadReportData ══ */
    function loadReportData(period, fromDate, toDate) {
        loadVoucherChart(period, fromDate, toDate);
    }

    function loadVoucherChart(period, fromDate, toDate) {
        var params = { period: period };
        if (period === 'custom' && fromDate && toDate) {
            params.from_date = fromDate;
            params.to_date   = toDate;
        }
        $.ajax({
            url: '../reports/datatables-scripts/get_total_voucher_sold.php',
            type: 'POST', data: params, dataType: 'json',
            success: function(resp) {
                if (!resp || resp.status !== 'success') return;
                $('#kpiTotalVouchers').text(resp.kpi.total_vouchers.toLocaleString());
                renderEchartVoucherSaleDonut(
                    'dataUsageDonut', resp.donut,
                    { nodataImg: "<?=$baseurl?>/<?=$appname?>/<?=$assetsDir?>/img/nodata.jpg" }
                );
                var html = '';
                $.each(resp.partners, function(i, p) {
                    html += '<tr>'
                          + '<td><span style="display:inline-block;width:10px;height:10px;'
                          + 'border-radius:50%;background:' + p.color + ';margin-right:8px;"></span>'
                          + p.name + '</td>'
                          + '<td style="font-weight:600;">' + p.value.toLocaleString() + '</td>'
                          + '<td><span class="du-share-badge">' + p.mix_pct + '%</span></td>'
                          + '</tr>';
                });
                $('#partnerBody').html(html);
            }
        });
    }

    /* ══ Customer Multiselect (CMS) ══ */
    var allCustomers       = [];
    var selectedCustomerIds = [];

    function cmsEsc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function updateCmsTrigger() {
        var n = selectedCustomerIds.length;
        if (n === 0) {
            $('#cmsTagsRow').html('<span class="cms-placeholder" id="cmsPlaceholder">All Customers</span>');
            $('#cmsCountBadge').hide();
        } else if (n <= 3) {
            var tags = '';
            selectedCustomerIds.forEach(function(id) {
                var c = allCustomers.find(function(x) { return x.id == id; });
                if (c) {
                    tags += '<span class="cms-tag">' + cmsEsc(c.name)
                          + '<span class="cms-tag-x" data-id="' + c.id + '">×</span></span>';
                }
            });
            $('#cmsTagsRow').html(tags);
            $('#cmsCountBadge').hide();
        } else {
            $('#cmsTagsRow').html('<span class="cms-placeholder">' + n + ' customers selected</span>');
            $('#cmsCountBadge').text(n).show();
        }
    }

    function initCms() {
        $('#cmsTrigger').on('click', function(e) {
            e.stopPropagation();
            $('#cmsDropdown').hasClass('open') ? closeCms() : openCms();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#cmsWrap').length) closeCms();
        });

        $('#cmsSearch').on('input', function() {
            var q = $(this).val().toLowerCase();
            renderCmsList(allCustomers.filter(function(c) {
                return c.name.toLowerCase().indexOf(q) !== -1;
            }));
        });

        $('#cmsSelectAll').on('click', function() {
            var q       = $('#cmsSearch').val().toLowerCase();
            var visible = q
                ? allCustomers.filter(function(c) { return c.name.toLowerCase().indexOf(q) !== -1; })
                : allCustomers;
            visible.forEach(function(c) {
                if (selectedCustomerIds.indexOf(c.id) === -1) selectedCustomerIds.push(c.id);
            });
            renderCmsList(visible);
            updateCmsTrigger();
        });

        $('#cmsClearAll').on('click', function() {
            selectedCustomerIds = [];
            var q = $('#cmsSearch').val().toLowerCase();
            renderCmsList(q
                ? allCustomers.filter(function(c) { return c.name.toLowerCase().indexOf(q) !== -1; })
                : allCustomers
            );
            updateCmsTrigger();
        });
		$('#cmsTagsRow').on('click', '.cms-tag-x', function() {
    var id = String($(this).data('id'));

    // 1. Remove from selectedCustomerIds
    selectedCustomerIds = selectedCustomerIds.filter(function(cid) {
        return String(cid) !== id;
    });

    // 2. Re-render list
    var q = $('#cmsSearch').val().toLowerCase();
    renderCmsList(
        q
        ? allCustomers.filter(function(c) { return c.name.toLowerCase().indexOf(q) !== -1; })
        : allCustomers
    );

    // 3. Update trigger UI
    updateCmsTrigger();

    // 4. Auto-apply filter immediately ✅
    $('#cmsApply').trigger('click');
		});
		
        $('#cmsApply').on('click', function() {
            closeCms();
            loadVoucherPlanTable(selectedCustomerIds, currentPeriod, currentFrom, currentTo);
        });

        $(document).on('click', '.cms-tag-x', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            selectedCustomerIds = selectedCustomerIds.filter(function(x) { return x != id; });
            updateCmsTrigger();
        });

        $(document).on('click', '.cms-dd-item', function() {
            var id = $(this).data('id');
            var idx = selectedCustomerIds.indexOf(id);
            if (idx === -1) {
                selectedCustomerIds.push(id);
                $(this).addClass('sel').find('input[type=checkbox]').prop('checked', true);
            } else {
                selectedCustomerIds.splice(idx, 1);
                $(this).removeClass('sel').find('input[type=checkbox]').prop('checked', false);
            }
            updateCmsTrigger();
        });
    }

    function openCms() {
        $('#cmsDropdown').addClass('open');
        $('#cmsTrigger').addClass('active');
        $('#cmsArrow').addClass('open');
        $('#cmsSearch').val('');
        renderCmsList(allCustomers);
        setTimeout(function() { $('#cmsSearch').focus(); }, 50);
    }

    function closeCms() {
        $('#cmsDropdown').removeClass('open');
        $('#cmsTrigger').removeClass('active');
        $('#cmsArrow').removeClass('open');
    }

    function renderCmsList(customers) {
        if (!customers || !customers.length) {
            $('#cmsList').html('<div class="cms-dd-empty">No customers found.</div>');
            return;
        }
        var html = '';
        customers.forEach(function(c) {
            var sel = selectedCustomerIds.indexOf(c.id) !== -1;
            html += '<div class="cms-dd-item' + (sel ? ' sel' : '') + '" data-id="' + c.id + '">'
                  + '<input type="checkbox"' + (sel ? ' checked' : '') + '>'
                  + '<span>' + cmsEsc(c.name) + '</span>'
                  + '</div>';
        });
        $('#cmsList').html(html);
    }

    function loadCustomerOptions() {
        $.ajax({
            url: '../reports/datatables-scripts/get_total_voucher_sold.php',
            type: 'POST', data: { action: 'getCustomers' }, dataType: 'json',
            success: function(resp) {
                if (!resp || !resp.customers) return;
                allCustomers = resp.customers.map(function(c) {
                    return { id: c.id, name: c.Name };
                });
                renderCmsList(allCustomers);
                loadVoucherPlanTable([], currentPeriod, currentFrom, currentTo);
            }
        });
    }

    initCms();

    function loadVoucherPlanTable(customerIds, period, fromDate, toDate) {
        period   = period   || currentPeriod;
        fromDate = fromDate || currentFrom;
        toDate   = toDate   || currentTo;
        if (voucherDT) { voucherDT.destroy(); voucherDT = null; }
        $('#voucherPlanBody').html(
            '<tr><td colspan="4" style="text-align:center;padding:24px;color:#8a97a8;">'
            + '<i class="icon-spinner icon-spin"></i> Loading…</td></tr>'
        );
        var postData = { action: 'getVoucherPlanData', period: period, customer_ids: customerIds };
        if (period === 'custom' && fromDate && toDate) {
            postData.from_date = fromDate;
            postData.to_date   = toDate;
        }
        $.ajax({
            url: '../reports/datatables-scripts/get_total_voucher_sold.php',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(resp) {
                if (!resp || !resp.plan_data) {
                    $('#voucherPlanBody').html('<tr><td colspan="4" style="text-align:center;padding:24px;color:#e74c3c;"><i class="icon-warning-sign"></i> Failed to load.</td></tr>');
                    return;
                }
                if (resp.plan_data.length === 0) {
                    $('#voucherPlanBody').html('<tr><td colspan="4" style="text-align:center;padding:30px;color:#8a97a8;">No data found.</td></tr>');
                    return;
                }
                var html = '';
                $.each(resp.plan_data, function(i, row) {
                    html += '<tr>'
                          + '<td style="text-align:center;color:#8a97a8;font-weight:600;">' + (i + 1) + '</td>'
                          + '<td>' + row.customer_name + '</td>'
                          + '<td>' + row.plan_name + '</td>'
                          + '<td style="text-align:right;"><span class="du-plan-count-badge">' + parseInt(row.voucher_count).toLocaleString() + '</span></td>'
                          + '</tr>';
                });
                $('#voucherPlanBody').html(html);
                $('#voucherPlanBadge').text(resp.plan_data.length + ' record(s)');
                voucherDT = $('#voucherPlanTable').DataTable({
                    pageLength : 10,
                    lengthMenu : [10, 25, 50, 100],
                    order      : [[3, 'desc']],
                    columnDefs : [
                        { targets: 0, orderable: false, searchable: false, width: '52px', className: 'dt-center' },
                        { targets: 3, className: 'dt-right' }
                    ],
                    dom        : '<"row"<"col-sm-6"l><"col-sm-6"f>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
                    language   : { search: 'Search:', lengthMenu: 'Show _MENU_' },
                   drawCallback: function() {
    var api   = this.api();
    var start = api.page.info().start;
    api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
        cell.innerHTML = start + i + 1;
    });
} 
                });
            },
            error: function() {
                $('#voucherPlanBody').html('<tr><td colspan="4" style="text-align:center;padding:24px;color:#e74c3c;"><i class="icon-warning-sign"></i> Request failed.</td></tr>');
            }
        });
    }

    /* ══ Initial load ══ */
    loadReportData('custom', currentFrom, currentTo);
    loadCustomerOptions();

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
