<?php
/**
 * Divi Child Theme
 * Functions.php
 *
 * ===== NOTES ==================================================================
 * 
 * Unlike style.css, the functions.php of a child theme does not override its 
 * counterpart from the parent. Instead, it is loaded in addition to the parent's 
 * functions.php. (Specifically, it is loaded right before the parent's file.)
 * 
 * In that way, the functions.php of a child theme provides a smart, trouble-free 
 * method of modifying the functionality of a parent theme. 
 * 
 * =============================================================================== */
 
function divichild_enqueue_scripts() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	wp_register_style('datepicker_css', get_stylesheet_directory_uri() . '/datetimepicker/jquery.datetimepicker.css' );
	wp_enqueue_style('datepicker_css');

	
	wp_register_script('datepicker_js', get_stylesheet_directory_uri() . '/datetimepicker/jquery.datetimepicker.full.min.js', array('jquery'), true);
	wp_enqueue_script('datepicker_js');
	
	wp_register_script('custom_js', get_stylesheet_directory_uri() . '/custom.js', array('jquery'), true);
	wp_enqueue_script('custom_js');
}
add_action( 'wp_enqueue_scripts', 'divichild_enqueue_scripts' );

// Add custom note as custom cart item data
add_filter( 'woocommerce_add_cart_item_data', 'get_custom_product_data', 30, 2 );
function get_custom_product_data( $cart_item_data, $product_id ){
    if ( isset($_GET['f_start_date']) && ! empty($_GET['f_start_date']) || isset($_GET['f_end_date']) && ! empty($_GET['f_end_date'])) {
        $cart_item_data['free_pkg_start'] = sanitize_text_field( $_GET['f_start_date'] );
    
        $cart_item_data['free_pkg_end'] = sanitize_text_field( $_GET['f_end_date'] );
		
        //$cart_item_data['unique_key'] = md5( microtime().rand() );
    }
    return $cart_item_data;
}


// Display note in cart and checkout pages as cart item data - Optional
add_filter( 'woocommerce_get_item_data', 'display_custom_item_data', 10, 2 );
function display_custom_item_data( $cart_item_data, $cart_item ) {
    if ( isset( $cart_item['free_pkg_start'] ) ||  isset( $cart_item['free_pkg_end'] )){
        $cart_item_data[] = array(
            'name' => "Start Time",
            'value' =>   $cart_item['free_pkg_start'],

        );
		$cart_item_data[] = array(
            'name' => "End Time",
            'value' =>   $cart_item['free_pkg_end'],
        );
    }
	
    return $cart_item_data;
}

// Save and display product note in orders and email notifications (everywhere)
add_action( 'woocommerce_checkout_create_order_line_item', 'add_custom_data_order_item_meta', 20, 4 );
function add_custom_data_order_item_meta( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['free_pkg_start'] ) ||  isset( $values['free_pkg_end'] ) ){
        $item->update_meta_data( 'Start Time',  $values['free_pkg_start'] );
		$item->update_meta_data( 'End Time',  $values['free_pkg_end'] );
	}
}