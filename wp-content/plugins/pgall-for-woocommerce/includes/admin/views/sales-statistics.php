<?php
wp_enqueue_style( 'amchart-export', PAFW()->plugin_url() . '/assets/vendor/amcharts/plugins/export/export.css', array (), PAFW_VERSION );
wp_enqueue_style( 'semantic-ui-daterangepicker', PAFW()->plugin_url() . '/assets/vendor/semantic-ui-daterangepicker/daterangepicker.css', array (), PAFW_VERSION );
wp_enqueue_style( 'bootstrap', PAFW()->plugin_url() . '/assets/vendor/bootstrap/bootstrap.css', array (), PAFW_VERSION );
wp_enqueue_style( 'pafw-sales', PAFW()->plugin_url() . '/assets/css/sales-statistics.css', array (), PAFW_VERSION );

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
wp_enqueue_script( 'amchart-light', PAFW()->plugin_url() . '/assets/vendor/amcharts/themes/light.js', array (), PAFW_VERSION );
wp_enqueue_script( 'jquery-block-ui', PAFW()->plugin_url() . '/assets/js/jquery.blockUI.js', array (), PAFW_VERSION );
wp_enqueue_script( 'amchart-export', PAFW()->plugin_url() . '/assets/vendor/amcharts/plugins/export/export.js', array (), PAFW_VERSION );

wp_enqueue_script( 'pafw-sales', PAFW()->plugin_url() . '/assets/js/admin/sales-statistics.js', array (), PAFW_VERSION );
wp_localize_script( 'pafw-sales', '_pafw_sales', array (
	'action'     => PAFW()->slug() . '-pafw_sales_action',
	'start_date' => date( 'Y-m-d', strtotime( "-30 days" ) ),
	'end_date'   => date( "Y-m-d" ),
	'currency'   => get_woocommerce_currency_symbol()
) );

add_action( 'admin_footer', 'pafw_dashboard_footer' );

function pafw_dashboard_footer() {
	?>
    <div id="balloon" style="display: none;"></div>
	<?php
}

$summary = PAFW_Admin_Sales::get_summary_data();

?>
<h3><?php _e( '매출현황', 'pgall-for-woocommerce' ); ?></h3>

<div id="pafw-dashboard-wrapper">
    <div class="pafw-dashboard stat invert">
        <div class="pafw-dashboard-stat-wrapper">
            <div class="pafw-dashboard-stat">
                <div class="display today">
                    <div class="number">
                        <h3 class="font-green-sharp">
                            <span class="amount"><?php echo number_format( $summary['today']['order_total'] ); ?></span>
                            <small class="font-green-sharp">원</small>
                        </h3>
                        <small><?php _e( '오늘', 'pgall-for-woocommerce' ); ?></small>
                        <h3 class="font-green-sharp small" style="float: right">
                            <span class="count"><?php echo number_format( $summary['today']['count'] ); ?></span>
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
                <div class="display week">
                    <div class="number">
                        <h3 class="font-red-haze">
                            <span class="amount"><?php echo number_format( $summary['week']['order_total'] ); ?></span>
                            <small class="font-red-haze">원</small>
                        </h3>
                        <small><?php _e( '이번주', 'pgall-for-woocommerce' ); ?></small>
                        <h3 class="font-red-haze small" style="float: right">
                            <span class="count"><?php echo number_format( $summary['week']['count'] ); ?></span>
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
                <div class="display month">
                    <div class="number">
                        <h3 class="font-blue-sharp">
                            <span class="amount"><?php echo number_format( $summary['month']['order_total'] ); ?></span>
                            <small class="font-blue-sharp">원</small>
                        </h3>
                        <small><?php _e( '이번달', 'pgall-for-woocommerce' ); ?></small>
                        <h3 class="font-blue-sharp small" style="float: right">
                            <span class="count"><?php echo number_format( $summary['month']['count'] ); ?></span>
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
                <div class="display year">
                    <div class="number">
                        <h3 class="font-purple-soft">
                            <span class="amount"><?php echo number_format( $summary['year']['order_total'] ); ?></span>
                            <small class="font-purple-soft">원</small>
                        </h3>
                        <small><?php _e( '올해', 'pgall-for-woocommerce' ); ?></small>
                        <h3 class="font-purple-soft small" style="float: right">
                            <span class="count"><?php echo number_format( $summary['year']['count'] ); ?></span>
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

    <div class="pafw-dashboard-search">
        <div id="reportrange" class="clear" style="">
            <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
            <span><?php echo date( 'Y-m-d', strtotime( "-30 days" ) ); ?> - <?php echo date( "Y-m-d" ); ?></span> <b
                    class="caret"></b>
        </div>
    </div>

    <div class="pafw-dashboard stat">
        <div class="pafw-dashboard-stat-wrapper-box">
            <div class="pafw-dashboard-stat-wrapper-progress-box">
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-on-hold">
                            <div class="pafw-order-status">
                                <small>입금대기</small>
                                <div class="font-grey small" style="float: right">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                                <div class="amount-wrapper">
                                    <span class="amount">0</span>
                                    <small class="font-greyt">원</small>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="icon-pie-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-processing">
                            <div class="pafw-order-status">
                                <small>임급완료</small>
                                <div class="font-grey small" style="float: right">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                                <div class="amount-wrapper">
                                    <span class="amount">0</span>
                                    <small class="font-greyt">원</small>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="icon-pie-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-shipping">
                            <div class="pafw-order-status">
                                <small>배송중</small>
                                <div class="font-grey small" style="float: right">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                                <div class="amount-wrapper">
                                    <span class="amount">0</span>
                                    <small class="font-greyt">원</small>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="icon-pie-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-shipped">
                            <div class="pafw-order-status">
                                <small>배송완료</small>
                                <div class="font-grey small" style="float: right">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                                <div class="amount-wrapper">
                                    <span class="amount">0</span>
                                    <small class="font-greyt">원</small>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="icon-pie-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pafw-dashboard-stat-wrapper-box">
            <div class="pafw-dashboard-stat">
                <div class="display wc-completed">
                    <div class="pafw-order-status">
                        <h3>주문처리완료</h3>
                        <div class="count-wrapper">
                            <span class="count">0</span>
                            <span>건</span>
                        </div>
                        <div class="amount-wrapper">
                            <span class="amount">0</span>
                            <small class="font-greyt">원</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="pafw-dashboard-stat-wrapper-box">
            <div class="pafw-dashboard-stat-wrapper-claim-box">
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-cancel-request">
                            <div class="pafw-order-status">
                                <small>취소요청</small>
                                <div class="amount-wrapper">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-exchange-request">
                            <div class="pafw-order-status">
                                <small>교환요청</small>
                                <div class="amount-wrapper">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-return-request">
                            <div class="pafw-order-status">
                                <small>반품요청</small>
                                <div class="amount-wrapper">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-cancelled">
                            <div class="pafw-order-status">
                                <small>취소완료</small>
                                <div class="amount-wrapper">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-accept-exchange">
                            <div class="pafw-order-status">
                                <small>교환접수</small>
                                <div class="amount-wrapper">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pafw-dashboard-progress-item">
                    <div class="pafw-dashboard-stat">
                        <div class="display wc-accept-return">
                            <div class="pafw-order-status">
                                <small>반품접수</small>
                                <div class="amount-wrapper">
                                    <span class="count">0</span>
                                    <span>건</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pafw-dashboard timeline">
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>매출현황</span>
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

    <div class="pafw-dashboard timeline">
        <div class="pafw_w12 pafw_dashboard_panel_wrapper pafw_pc_60">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>요일별 매출</span>
                </p>
                <div class="pafw_piechart_panel">
                    <div id="sales_by_day_of_week_chart"></div>
                </div>
            </div>
        </div>
        <div class="pafw_w12 pafw_dashboard_panel_wrapper">
            <div class="pafw_dashboard_panel">
                <p class="pafw_panel_title">
                    <span>시간대별 매출</span>
                </p>
                <div class="pafw_piechart_panel">
                    <div id="sales_by_hour_chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
