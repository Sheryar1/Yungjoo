<?php
wp_enqueue_style( 'amchart-export', PAFW()->plugin_url() . '/assets/vendor/amcharts/plugins/export/export.css', array (), PAFW_VERSION );
wp_enqueue_style( 'semantic-ui-daterangepicker', PAFW()->plugin_url() . '/assets/vendor/semantic-ui-daterangepicker/daterangepicker.css', array (), PAFW_VERSION );
wp_enqueue_style( 'bootstrap', PAFW()->plugin_url() . '/assets/vendor/bootstrap/bootstrap.css', array (), PAFW_VERSION );
wp_enqueue_style( 'pafw-payment-health-status', PAFW()->plugin_url() . '/assets/css/payment-health-status.css', array (), PAFW_VERSION );

wp_enqueue_script( 'moment', PAFW()->plugin_url() . '/assets/vendor/moment/moment.min.js' );
wp_enqueue_script( 'semantic-ui-daterangepicker', PAFW()->plugin_url() . '/assets/vendor/semantic-ui-daterangepicker/daterangepicker.js', array (
	'jquery',
	'jquery-ui-core',
	'moment',
	'underscore'
), PAFW_VERSION );

wp_enqueue_script( 'amchart', PAFW()->plugin_url() . '/assets/vendor/amcharts/amcharts.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-serial', PAFW()->plugin_url() . '/assets/vendor/amcharts/serial.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-pie', PAFW()->plugin_url() . '/assets/vendor/amcharts/pie.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-gauge', PAFW()->plugin_url() . '/assets/vendor/amcharts/gauge.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-light', PAFW()->plugin_url() . '/assets/vendor/amcharts/themes/light.js', array (), PAFW_VERSION );
wp_enqueue_script( 'jquery-block-ui', PAFW()->plugin_url() . '/assets/js/jquery.blockUI.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-export', PAFW()->plugin_url() . '/assets/vendor/amcharts/plugins/export/export.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-theme-chalk', PAFW()->plugin_url() . '/assets/vendor/amcharts/themes/chalk.js', array (), PAFW_VERSION );

wp_enqueue_script( 'payment-health-status', PAFW()->plugin_url() . '/assets/js/admin/payment-health-status.js', array (), PAFW_VERSION );
wp_localize_script( 'payment-health-status', '_pafw_payment_health_status', array (
	'dashboard_action'         => PAFW()->slug() . '-pafw_payment_health_status_action',
	'start_date'               => date( 'Y-m-d', strtotime( "-30 days" ) ),
	'end_date'                 => date( "Y-m-d" ),
	'sales_by_date'            => $sales_by_date,
	'sales_by_date2'           => $sales_by_date,
	'sales_by_paymethod'       => $sales_by_paymethod,
	'sales_by_paymethod_title' => $sales_by_paymethod_title,
	'currency'                 => get_woocommerce_currency_symbol()
) );

add_action( 'admin_footer', 'pafw_dashboard_footer' );

function pafw_dashboard_footer() {
	?>
    <div id="balloon" style="display: none;"></div>
	<?php
}

?>
<h3>결제 요청 및 처리 현황</h3>

<div id="pafw-dashboard-wrapper">

    <div class="pafw-dashboard-search">
        <div id="reportrange" class="clear" style="">
            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
            <span><?php echo date( 'Y-m-d', strtotime( "-30 days" ) ); ?> - <?php echo date( "Y-m-d" ); ?></span> <b
                    class="caret"></b>
        </div>
    </div>

    <div class="pafw-dashboard stat invert">
        <div class="pafw-dashboard-stat-wrapper">
            <div class="pafw-dashboard-stat">
                <div class="display completed">
                    <div class="number">
                        <h3 class="font-green-sharp">
                            <span class="amount">0</span>
                            <small class="font-green-sharp">원</small>
                        </h3>
                        <small>COMPLETED</small>
                        <h3 class="font-green-sharp small" style="float: right">
                            <span class="count">0</span>
                            <span>건</span>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="icon-pie-chart"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="pafw-dashboard-stat-wrapper">
            <div class="pafw-dashboard-stat">
                <div class="display request">
                    <div class="number">
                        <h3 class="font-red-haze">
                            <span class="amount">0</span>
                            <small class="font-red-haze">원</small>
                        </h3>
                        <small>REQUEST</small>
                        <h3 class="font-red-haze small" style="float: right">
                            <span class="count">0</span>
                            <span>건</span>
                        </h3></div>
                    <div class="icon">
                        <i class="icon-pie-chart"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="pafw-dashboard-stat-wrapper">
            <div class="pafw-dashboard-stat">
                <div class="display cancelled">
                    <div class="number">
                        <h3 class="font-blue-sharp">
                            <span class="amount">0</span>
                            <small class="font-blue-sharp">원</small>
                        </h3>
                        <small>CANCELLED</small>
                        <h3 class="font-blue-sharp small" style="float: right">
                            <span class="count">0</span>
                            <span>건</span>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="icon-pie-chart"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="pafw-dashboard-stat-wrapper">
            <div class="pafw-dashboard-stat">
                <div class="display failed">
                    <div class="number">
                        <h3 class="font-purple-soft">
                            <span class="amount">0</span>
                            <small class="font-purple-soft">원</small>
                        </h3>
                        <small>FAILED</small>
                        <h3 class="font-purple-soft small" style="float: right">
                            <span class="count">0</span>
                            <span>건</span>
                        </h3>
                    </div>
                    <div class="icon">
                        <i class="icon-pie-chart"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="pafw-dashboard timeline">
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>결제 요청 처리 현황</span>
                    <span class="search-interval" data-interval="1M" data-gap_value="1" data-amount_label="월별매출" data-count_label="월별구매건수">월</span>
                    <span class="search-interval" data-interval="1w" data-gap_value="7" data-amount_label="주별매출" data-count_label="주별구매건수">주</span>
                    <span class="search-interval selected" data-interval="1d" data-gap_value="1" data-amount_label="일별매출" data-count_label="일별구매건수">일</span>
                </p>
                <div class="pafw_serialchart_panel">
                    <div id="top_sales_by_date_chart"></div>
                </div>
            </div>
        </div>

    </div>
    <div class="pafw-dashboard timeline" style="height: 300px;">
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>결제성공율 (Negative)</span>
                </p>
                <div class="pafw_piechart_panel">
                    <div id="negative_rate_gauge"></div>
                </div>
            </div>
        </div>
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>결제성공율 (Positive)</span>
                </p>
                <div class="pafw_piechart_panel">
                    <div id="positive_rate_gauge"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="pafw-dashboard timeline">
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>결제 실패 분석 ( 결제수단별 )</span>
                </p>
                <div class="pafw_piechart_panel">
                    <div id="failed_count_by_payment_method"></div>
                </div>
            </div>
        </div>
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>결제 실패 분석 ( 오류코드별 )</span>
                </p>
                <div class="pafw_piechart_panel">
                    <div id="failed_count_by_result_code"></div>
                </div>
            </div>
        </div>
    </div>
</div>
