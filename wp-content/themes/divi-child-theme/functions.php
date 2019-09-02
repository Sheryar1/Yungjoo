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
 
// ------------------
// Include scripts 
function ch_enqueue_scripts() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css');
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	
	// Datetimepicker
	wp_register_style('datepicker_css', get_stylesheet_directory_uri() . '/datetimepicker/jquery.datetimepicker.css' );
	wp_enqueue_style('datepicker_css');

	
	wp_register_script('datepicker_js', get_stylesheet_directory_uri() . '/datetimepicker/jquery.datetimepicker.full.min.js', array('jquery'), true);
	wp_enqueue_script('datepicker_js');
	
	// DataTables
	wp_register_style('datatables_css', get_stylesheet_directory_uri() . '/datatables/css/dataTables.bootstrap.min.css' );
	wp_enqueue_style('datatables_css');

	
	wp_register_script('datatables_js', get_stylesheet_directory_uri() . '/datatables/js/jquery.dataTables.min.js', array('jquery'), true);
	wp_enqueue_script('datatables_js');
	
	wp_register_script('datatables_bootstrap_js', get_stylesheet_directory_uri() . '/datatables/js/dataTables.bootstrap.min.js', array('jquery'), true);
	wp_enqueue_script('datatables_bootstrap_js');
	
	// Bookings Custom Js
	wp_register_script('custom_js', get_stylesheet_directory_uri() . '/custom.js', array('jquery'), true);
	wp_enqueue_script('custom_js');
}
add_action( 'wp_enqueue_scripts', 'ch_enqueue_scripts' );

// ------------------
// Create Database Table for bookings

	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "bookings";  //get the database table prefix to create my new table
if($wpdb->get_var( "show tables like '$table_name'" ) != $table_name) 
    {
    $sql = "CREATE TABLE $table_name (
	
      id int(10) unsigned NOT NULL AUTO_INCREMENT,
	  event_id varchar(100) NOT NULL,
	  host_id varchar(100) NOT NULL,
      host_url varchar(255) NOT NULL,
      participant_url varchar(255) NOT NULL,
      start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,

      UNIQUE KEY id (id)
      
    ) $charset_collate;";
    dbDelta( $sql );
 }

// ------------------
// Create Database Table for bookings

		$invite_table_name = $wpdb->prefix . "invitations";  //get the database table prefix to create my new table
		if($wpdb->get_var( "show tables like '$invite_table_name'" ) != $invite_table_name) 
			{
				$sql = "CREATE TABLE $invite_table_name (
				
				  id int(10) unsigned NOT NULL AUTO_INCREMENT,
				  host_id varchar(100) NOT NULL,
				  participant_id varchar(100) NOT NULL,
				  participant_name varchar(255) NOT NULL,
				  participant_email varchar(255) NOT NULL,

				  UNIQUE KEY id (id)
				  
				) $charset_collate;";
				dbDelta( $sql );
			 }

// ------------------
// Add custom note as custom cart item data
add_filter( 'woocommerce_add_cart_item_data', 'ch_get_custom_product_data', 30, 2 );
function ch_get_custom_product_data( $cart_item_data, $product_id ){
    if ( isset($_GET['f_start_date']) && ! empty($_GET['f_start_date']) || isset($_GET['f_end_date']) && ! empty($_GET['f_end_date'])) {
        $cart_item_data['free_pkg_start'] = sanitize_text_field( $_GET['f_start_date'] );
    
        $cart_item_data['free_pkg_end'] = sanitize_text_field( $_GET['f_end_date'] );
		
        //$cart_item_data['unique_key'] = md5( microtime().rand() );
    }
    return $cart_item_data;
}

// ------------------
// Display note in cart and checkout pages as cart item data - Optional
add_filter( 'woocommerce_get_item_data', 'ch_display_custom_item_data', 10, 2 );
function ch_display_custom_item_data( $cart_item_data, $cart_item ) {
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

// ------------------
// Save and display product note in orders and email notifications (everywhere)
add_action( 'woocommerce_checkout_create_order_line_item', 'ch_add_custom_data_order_item_meta', 20, 4 );
function ch_add_custom_data_order_item_meta( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['free_pkg_start'] ) ||  isset( $values['free_pkg_end'] ) ){
        $item->update_meta_data( 'start_time',  $values['free_pkg_start'] );
		$item->update_meta_data( 'end_time',  $values['free_pkg_end'] );
	}
}

// ------------------
// Save Bookings data
add_action('woocommerce_thankyou', 'ch_save_booking_data', 10, 1);
function ch_save_booking_data($order_id ){
    $event_id = $order_id;
	$order = wc_get_order( $order_id );
	// Get order item meta
	$items = $order->get_items();
	$meeting_start = '';
	$meeting_end = '';
	
	// Get the user ID
    $user_id = get_post_meta($order_id, '_customer_user', true);
	
	foreach($items as $item_id => $item){
			$meeting_start = wc_get_order_item_meta( $item_id, 'start_time', true );
			$meeting_end = wc_get_order_item_meta( $item_id, 'end_time', true );
	}
	$start_timestamp = strtotime($meeting_start);
	$end_timestamp = strtotime($meeting_end);
	
	// Api req
	 $data = array(
	     'event_id' => ''.$event_id.'',
	     'service_type' => 'meeting',
	     'start_time' => $start_timestamp,
	     'end_time' => $end_timestamp,
	     'inadvance' => '200',
	     'tenant_id' => 'camxf'
	 );
	 $req_body = json_encode($data);
	 $curl = curl_init();
	 	curl_setopt_array($curl, array(
	 	CURLOPT_URL => "http://rmsrtc.cmi.chinamobile.com/rms/v1/room/?tenant_id=camxf",
	 	CURLOPT_RETURNTRANSFER => true,
	 	CURLOPT_ENCODING => "",
	 	CURLOPT_MAXREDIRS => 10,
	 	CURLOPT_TIMEOUT => 30,
	 	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	 	CURLOPT_CUSTOMREQUEST => "POST",
	 	CURLOPT_POSTFIELDS => $req_body,
	 	CURLOPT_HTTPHEADER => array(
	 		"Accept: */*",
	 		"Cache-Control: no-cache",
	 		"Connection: keep-alive",
	 		"Host: rmsrtc.cmi.chinamobile.com",
	 		"Postman-Token: 37a627b0-4c1e-45d1-a7ba-48b152698222,3d930f0b-5499-4dc4-8f7d-31b85f96acef",
	 		"User-Agent: PostmanRuntime/7.15.2",
	 		"accept-encoding: gzip, deflate",
	 		"cache-control: no-cache",
	 		"Content-Type: application/json",
	 		"Authorization: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE1OTg0NDcwNzUsInRva2VuIjoiMTFlN2JkMjItM2U0Zi00NzliLTlhZjgtZWUyZGNiYmYxZjRmIn0.fGebX1oaFNOFs2suqQoDsfUIEYRNjtD60FsmWPK1moQ"
	 	),
	 	));

	 	$response = curl_exec($curl);
	 	$err = curl_error($curl);
	 	curl_close($curl);

	 	if ($err) {
	 	echo "cURL Error #:" . $err;
	 	}
	 	$response = json_decode($response, true);
		$meeting_host_url = '';
		$meeting_url_participant = '';
		
		foreach($response as $meeting_data){
			if (is_array($meeting_data)){
				$meeting_host_url = $meeting_data['url_host'];
				$meeting_url_participant = $meeting_data['url_participant'];
			}
		}
		// Insert data into Database Table
		global $wpdb;
		$table_name = $wpdb->prefix . "bookings";
		$success = $wpdb->insert( 
			$table_name, 
			array( 
				'event_id' => $event_id, 
				'host_id' => $user_id, 
				'host_url' => $meeting_host_url, 
				'participant_url' => $meeting_url_participant, 
				'start_time' => $meeting_start, 
				'end_time' => $meeting_end, 
			) 
		);
		if($success) {
		 //echo ' Inserted successfully';
		} 
		else {
		   //echo 'not';
		}
}

// ------------------
// 4. Get bookings data from DB to show on my-account booking page

function ch_get_bookings_data(){
	global $wpdb;
	$table_name = $wpdb->prefix . "bookings";
	$bookings = $wpdb->get_results( "SELECT * FROM $table_name" );
	return $bookings;
}
// ------------------
// 1. Register new endpoint to use for My Account page
// Note: Resave Permalinks or it will give 404 error
  
function ch_add_premium_support_endpoint() {
    add_rewrite_endpoint( 'booking-details', EP_ROOT | EP_PAGES );
}
  
add_action( 'init', 'ch_add_premium_support_endpoint' );
  
  
// ------------------
// 2. Add new query var
  
function ch_premium_support_query_vars( $vars ) {
    $vars[] = 'booking-details';
    return $vars;
}
  
add_filter( 'query_vars', 'ch_premium_support_query_vars', 0 );
  
  
// ------------------
// 3. Insert the new endpoint into the My Account menu
  
function ch_add_premium_support_link_my_account( $items ) {
    $items['booking-details'] = 'Bookings';
    return $items;
}
  
add_filter( 'woocommerce_account_menu_items', 'ch_add_premium_support_link_my_account' );
  
  
// ------------------
// 4. Add content to the new endpoint
  
function ch_booking_details_content() {
	echo '<h3>Bookings</h3>';
	
		$current_user = get_current_user_id();
	if (function_exists('ch_get_bookings_data')) { 
		$bookings_data = ch_get_bookings_data();
	?>
	<table id="example" class="table table-striped table-bordered" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th>Name</th>
				<th>Meeting Start DateTime</th>
				<th>Meeting End DateTime</th>
				<th>Join</th>
				<th>Invite</th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($bookings_data as $booking_id => $booking_value){
			//echo'<pre>';var_dump($booking_value);echo'</pre>';
			$user_id = $booking_value->host_id;
			$event_id = $booking_value->event_id;
			$meeting_start = $booking_value->start_time;
			$meeting_end = $booking_value->end_time;
			$host_url = $booking_value->host_url;
			$participant_url = $booking_value->participant_url;
			
			$event_name = '';
			$order = new WC_Order( $event_id );
			$items = $order->get_items();
			foreach($items as $k=>$val){
				$event_name = $val['name'];
			}
			$user = get_userdata($current_user);
			$user_name = $user->data->display_name;
			if($current_user == $user_id){
				//var_dump($host_url);
				?>
				<tr>
					<td><?php echo $event_name; ?></td>
					<td><?php echo $meeting_start; ?></td>
					<td><?php echo $meeting_end; ?></td>
					<td><a href="<?php echo $host_url; ?>&nickName=<?php echo $user_name; ?>" target="_blank">Join</a></td>
					<td><a id ="participant_popup" class="modal-toggle" >Invite</a></td>
				</tr>
				<?php
			}
			
		}
		?>
		<?php
		 // ------------------
		 // Invitations
		
		 if(isset($_POST['submitted'])) {
				$participant_id = $_POST['participant_id'];
				$participant_name = $_POST['participant_name'];
				$participant_email = $_POST['participant_email'];
				var_dump($participant_name);
				exit();
			 // Insert data into Database Table
				global $wpdb;
				$table_name = $wpdb->prefix . "invitations";
				$success = $wpdb->insert( 
					$table_name, 
					array( 
						'host_id' => $user_id, 
						'participant_id' => $participant_id, 
						'participant_name' => $participant_name, 
						'participant_email' => $participant_email, 
					) 
				);
				if($success) {
				 echo ' Inserted successfully';
				} 
				else {
				   echo 'not';
				}
		 }
		?>
		  <div class="modal">
			<div class="modal-overlay modal-toggle"></div>
			<div class="modal-wrapper modal-transition">
			  <div class="modal-header">
				<button class="modal-close modal-toggle"><svg class="icon-close icon" viewBox="0 0 32 32"><use xlink:href="#icon-close"></use></svg></button>
				<h2 class="modal-heading">Send Invitations</h2>
			  </div>
			  
			  <div class="modal-body">
				<div class="modal-content">
				  <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" class="invite_wrap">
						<div class="invite_wrap_inner">
						  <div>
							<input type="text" name="participant_name[]" id="" required />
							<input type="email" name="participant_email[]" id="" required />
							<input type="hidden" name="participant_id[]" value="" id="" />
							<a href="javascript:void(0);" class="add_input_button" title="Add field"><i class="fa fa-plus-circle" aria-hidden="true"></i>Add More</a>
						  </div>
						</div>
						<input type="hidden" name="host_id" value="<?php echo $current_user; ?>" id="host_id" />
						<input type="submit" value="Send Invitation" name="submitted" />
				  </form>
				</div>
			  </div>
			</div>
		  </div>			
		</tbody>
	</table>
	<?php
	}	
}
  
add_action( 'woocommerce_account_booking-details_endpoint', 'ch_booking_details_content' );

// ------------------
// 4. Reorder My Acccount Tabs

function ch_my_account_order() {
	$myorder = array(
		'dashboard'          => __( 'Dashboard', 'woocommerce' ),
		'booking-details' => __( 'Bookings', 'woocommerce' ),
		'edit-account'       => __( 'Account Details', 'woocommerce' ),
		'customer-logout'    => __( 'Logout', 'woocommerce' ),
	);
	return $myorder;
}
add_filter ( 'woocommerce_account_menu_items', 'ch_my_account_order' );

add_filter( 'woocommerce_add_to_cart_validation', 'bbloomer_only_one_in_cart', 10, 2 );
  
function bbloomer_only_one_in_cart( $passed, $added_product_id ) {
 
// empty cart first: new item will replace previous
wc_empty_cart();
 var_dump("<h1>Text</h1>");
return $passed;
}
