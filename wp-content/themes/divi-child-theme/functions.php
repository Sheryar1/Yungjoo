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