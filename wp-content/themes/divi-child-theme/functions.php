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

	
	wp_register_script('datepicker_js', get_stylesheet_directory_uri() . '/datetimepicker/jquery.datetimepicker.full.js', array('jquery'), true);
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
	
	wp_localize_script('custom_js', 'custom_ajax', array('ajaxurl' =>admin_url('admin-ajax.php')));
	if(is_account_page() || is_page('my-account/booking-details/') || is_page('my-account/invitation_details/') ){
        wp_register_style('bootstrap4', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css');
        wp_enqueue_style('bootstrap4');
   }
	

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
		  hours varchar(500) NOT NULL,

		  UNIQUE KEY id (id)
		  
		) $charset_collate;";
		dbDelta( $sql );

	 }
	 $myCustomer = $wpdb->get_row("SELECT * FROM wp_bookings");
		//Add column if not present.
		if(!isset($myCustomer->hours)){
			$wpdb->query("ALTER TABLE wp_bookings ADD hours varchar(500) NOT NULL");
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
// Create Database Table for Hourly Limit			 
			 
	$hours_table = $wpdb->prefix . "hourslimit";  //get the database table prefix to create my new table
	if($wpdb->get_var( "show tables like '$hours_table'" ) != $hours_table) 
		{
		$sql = "CREATE TABLE $hours_table (
		
		  id int(10) unsigned NOT NULL AUTO_INCREMENT,
		  date varchar(255) NOT NULL,
		  total_hours varchar(255) NOT NULL,
		  purchased_hours varchar(255) NOT NULL,
		  remaining_hours varchar(255) NOT NULL,
		  
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
	$total_days = '';
	
	// Get the user ID
    $user_id = get_post_meta($order_id, '_customer_user', true);
	
		foreach($items as $item_id => $item){
			//var_dump($item);
				$meeting_start = wc_get_order_item_meta( $item_id, 'start_time', true );
				$meeting_end = wc_get_order_item_meta( $item_id, 'end_time', true );
				$total_days = wc_get_order_item_meta($item_id, '_qty', true);
		}
	
		$start_timestamp = strtotime($meeting_start);
		$end_timestamp = strtotime($meeting_end);
		
		// get start date
		$start_date_only = new DateTime($meeting_start);
		$start_date_only = $start_date_only->format('Y-m-d');
		
		// get end date
		$end_date_only = new DateTime($meeting_end);
		$end_date_only = $end_date_only->format('Y-m-d');
		
		// get total num of hours between 2 dates
		$date1 = new DateTime($meeting_start);
		$date2 = new DateTime($meeting_end);
		$diff = $date2->diff($date1);
		$hours = $diff->h;
		$hours = $hours + ($diff->days*24);
		
		// get next day date
		$tomorrow_date = new DateTime($meeting_start);
		$tomorrow_date->add(new DateInterval("P1D"));
		$tomorrow =  $tomorrow_date->format('Y/m/d');
		
		// get num of remaining hours in start date
		$datetime1 = new DateTime($meeting_start);
		$datetime2 = new DateTime($tomorrow);
		$interval = $datetime1->diff($datetime2);
		$start_hours = $interval->format('%h');
		
		$dt_end = new DateTime($tomorrow.' 00:00');
		$remain = $dt_end->diff(new DateTime($meeting_start));
		//echo $remain->h . ' hours';
		//echo '<br>';
		
		// get num of hour in end date
		$end_hours = 24 - $start_hours;
		
		
		
		//get all dates between 2 dates
		$period = new DatePeriod(
			 new DateTime($meeting_start),
			 new DateInterval('P1D'),
			 new DateTime($meeting_end)
		);
		
		if($start_date_only == $end_date_only ){
			$dates_array = array($start_date_only => $hours);
			//var_dump($dates_array);
		}
		else{
		
		// get all dates/hours and add into array in dates => hours pairs
		$dates_array = array();
		$counter = '0';
		
		//$len = count($period);
		//echo '<pre>';var_dump($period);echo '</pre>';
		
		foreach ($period as $key => $value) {
			
			//echo '<pre>';var_dump($value);echo '</pre>'; 
			if($counter == 0){
				$dates_array[$value->format('Y-m-d')] = $start_hours;
			}
			else{
				$dates_array[$value->format('Y-m-d')] = 24; 	
			}
			if ($value == end($period) ) {
				$dates_array[$end_date_only] = $end_hours;
			}
			
			
			$counter ++;			
		}
		//$end_date_array = array($end_date_only => $end_hours);
		//array_push($dates_array, $end_date_array);
		//var_dump($dates_array); 
		}
	
	//exit();
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
		$final_array = json_encode($dates_array);
		//var_dump($final_array); 
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
				'hours' => $final_array, 
			) 
		);
		if($success) {
			//echo ' Inserted successfully';
		} 
		else {
		   //echo 'not';
		}		
}

add_action( 'woocommerce_thankyou', 'bbloomer_redirectcustom');
  
function bbloomer_redirectcustom( $order_id ){
    $order = wc_get_order( $order_id );
    $url = '/my-account';
    if ( ! $order->has_status( 'failed' ) ) {
        wp_safe_redirect( $url );
        exit;
    }
}

// ------------------
// 4. Get bookings data from DB to show on my-account booking page

function ch_get_bookings_data($tbl_name){
	global $wpdb;
	$table_name = $wpdb->prefix . "$tbl_name";
	$bookings = $wpdb->get_results( "SELECT * FROM $table_name" );
	return $bookings;
}

// ------------------
// 4. Get single meta from DB

function ch_single_col_data($tbl_name, $field_name, $host_id){
	global $wpdb;
	$table_name = $wpdb->prefix . "$tbl_name";
	$field_data = $wpdb->get_results( "SELECT $field_name FROM $table_name WHERE host_id = $host_id" );
	return $field_data;
}

// ------------------
// 1. Register new endpoint to use for My Account page
// Note: Resave Permalinks or it will give 404 error
  
function ch_add_premium_support_endpoint() {
    add_rewrite_endpoint( 'booking-details', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'invitation_details', EP_ROOT | EP_PAGES );
}
  
add_action( 'init', 'ch_add_premium_support_endpoint' );
  
  
// ------------------
// 2. Add new query var
  
function ch_premium_support_query_vars( $vars ) {
    $vars[] = 'booking-details';
    $vars[] = 'invitation_details';
    return $vars;
}
  
add_filter( 'query_vars', 'ch_premium_support_query_vars', 0 );
  
  
// ------------------
// 3. Insert the new endpoint into the My Account menu
  
function ch_add_premium_support_link_my_account( $items ) {
    $items['booking-details'] = 'Bookings';
    $items['invitation_details'] = 'Invitations';
    return $items;
}
  
add_filter( 'woocommerce_account_menu_items', 'ch_add_premium_support_link_my_account' );
  
  
// ------------------
// 4. Add content to the new endpoint
  
function ch_booking_details_content() {
	echo '<h3>Bookings</h3>';
	
		$current_user = get_current_user_id();
	if (function_exists('ch_get_bookings_data')) { 
		$bookings_data = ch_get_bookings_data('bookings');
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
			$join_url = '';
			if($current_user == $user_id){
				//var_dump($host_url);
				
				$start_date = new DateTime();
				
				$since_start = $start_date->diff(new DateTime($meeting_start));
				
				
				if($since_start->h == 0 && $since_start->i == 10){
					$join_url = '<a href="'.$host_url.'&userId='.$user_id.'&nickName='.$user_id.'" target="_blank">Join</a>';
				}else{
					$join_url = '<a href="#">Join</a>';
				}
				
				?>
				<tr>
					<td><?php echo $event_name; ?></td>
					<td><?php echo $meeting_start; ?></td>
					<td><?php echo $meeting_end; ?></td>
					<td><?php echo $join_url; ?></td>
					<td><a id ="participant_popup" class="modal-toggle" >Invite</a></td>
				</tr>
				<?php
			}
			
		}
		?>
		
		  <div class="moda">
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
						<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" id="event_id" />
						<input type="submit" value="Send Invitation" name="submitted" id="invite_btn" />
				  </form>
				  <div class="response_div"></div>
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

function ch_invitation_details_content() {
	echo '<h3>Invitations</h3>';
	
	$current_user = get_current_user_id();
	if (function_exists('ch_get_bookings_data')) { 
		$invitation_data = ch_get_bookings_data('invitations');
	?>
	<table id="invitations" class="table table-striped table-bordered" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th>Name</th>
				<th>Email</th>
				
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($invitation_data as $invitation_id => $invitation_value){
			//echo'<pre>';var_dump($invitation_value);echo'</pre>';
			$user_id = $invitation_value->host_id;
			$part_name = $invitation_value->participant_name;
			$part_email = $invitation_value->participant_email;
			
			
			if($current_user == $user_id){
				//var_dump($host_url);
				?>
				<tr>
					<td><?php echo $part_name; ?></td>
					<td><?php echo $part_email; ?></td>
					
				</tr>
				<?php
			}
			
		}
		?>
		</tbody>
		</table>
		<?php
	}
}
add_action( 'woocommerce_account_invitation_details_endpoint', 'ch_invitation_details_content' );
// ------------------
// 4. Reorder My Acccount Tabs

function ch_my_account_order() {
	$myorder = array(
		'dashboard'          => __( 'Dashboard', 'woocommerce' ),
		'booking-details' => __( 'Bookings', 'woocommerce' ),
		'invitation_details' => __( 'Invitations', 'woocommerce' ),
		'edit-account'       => __( 'Account Details', 'woocommerce' ),
		'customer-logout'    => __( 'Logout', 'woocommerce' ),
	);
	return $myorder;
}
add_filter ( 'woocommerce_account_menu_items', 'ch_my_account_order' );


add_filter( 'woocommerce_add_cart_item_data', 'woo_custom_add_to_cart' );

function woo_custom_add_to_cart( $cart_item_data ) {

    global $woocommerce;
    $woocommerce->cart->empty_cart();

    // Do nothing with the data and return
    return $cart_item_data;
}

add_action("wp_ajax_save_invites_data", "save_invites_data");
add_action("wp_ajax_nopriv_save_invites_data", "save_invites_data");
function save_invites_data(){
	global $wpdb;
	global $current_user;
    wp_get_current_user();
    $host_email =  $current_user->user_email;
	$event_id = $_POST['event_id'];
	$host_id = $_POST['host_id'];
	$par_id = '';
	$par_name = $_POST['par_name'];
	$par_email = $_POST['par_email'];
	$user_data = array_combine($par_name, $par_email);
	
	$booking_table = $wpdb->prefix . "bookings";
	$par_url = $wpdb->get_results( "SELECT participant_url FROM $booking_table WHERE event_id = $event_id" );
	$participant_url = $par_url[0]->participant_url;
	
	foreach($user_data as $name => $email){	
		$par_id  = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
		
		$message = $participant_url.'&userId='.$par_id.'&nickName='.$par_id;
		
		 //Insert data into Database Table
		
		$table_name = $wpdb->prefix . "invitations";
		$success = $wpdb->insert( 
			$table_name, 
			array( 
				'host_id' => $host_id, 
				'participant_id' => $par_id, 
				'participant_name' => $name, 
				'participant_email' => $email, 
			) 
		);
		if($success) {
		  //php mailer variables
		  $to = $email;
		  $subject = "Meeting Invitation";
		  $headers = 'From: '. $host_email . "\r\n" .
			'Reply-To: ' . $host_email . "\r\n";
		 
			//Here put your Validation and send mail
			$sent = wp_mail($to, $subject, $message, $headers);
			  if($sent) {
				echo "Invitations Sent.";
			  }//message sent!
			  else  {

			  }//message w
		} 
		else {
			echo 'not';
		}
		
	}

	wp_die();
}
function ch_add_admin_page(){
	// Generate Valley Admin Page
	add_menu_page('Hours per day', 'Hours per day', 'manage_options', 'hours_per_day', 'ch_hours_per_day', '', 110 );
	
}
add_action('admin_menu', 'ch_add_admin_page');

function get_daily_hours(){
	global $wpdb;
	$table_name = $wpdb->prefix . "bookings";
	$daily_hours = $wpdb->get_results( "SELECT hours FROM $table_name" );
		
		$final_arr = array();
		$hours_array = '';
		
		foreach($daily_hours as $key => $value){
				$daily_hours = $value->hours;
				
				if(!empty($daily_hours )){
					
					$hours_array = json_decode($daily_hours, true);
					
					ksort($hours_array);
					
					$count = '';
					
					foreach($hours_array as $date_key => $hour_value){
						
						if(array_key_exists($date_key, $final_arr)){
							$final_arr[$date_key] += $hour_value;
						}else{
							$final_arr[$date_key] = $hour_value;
						}
					
					}
					
				}
			}
		return $final_arr;
}

function ch_hours_per_day(){
	if(isset($_POST['submitted'])){
		$hours_limit = $_POST['hours_limit'];
		$get_hours_limit = get_post_meta(1001, 'hours_limit', true);
		//var_dump($get_hours_limit);
		if(empty($get_hours_limit)){
			add_post_meta( 1001, 'hours_limit', $hours_limit );
		}else{
			update_post_meta( 1001, 'hours_limit', $hours_limit );
		}
		
	}
	$get_hours_limit = get_post_meta(1001, 'hours_limit', true);
	//echo '<h1>Hours per day</h1>';
	?>
	<form action="<?php echo get_permalink() ?>" method="post">
		<input type="text" id="hours_limit" name="hours_limit" value="<?php echo $get_hours_limit; ?>">
		<input type="submit" value="Save" name="submitted">
	</form>
	<?php
			$final_arr = get_daily_hours();
		?>
			<table id="invitations" class="table table-striped table-bordered" cellspacing="0" width="60%" border="1">
				<thead>
					<tr>
						<th style="text-align:left;">Date</th>
						<th style="text-align:left;">Hours</th>
						
					</tr>
				</thead>
			<tbody>
		<?php
		foreach($final_arr as $date_k => $hour_v){
		?>
			<tr>
				<td><?php echo $date_k; ?></td>
				<td><?php echo $hour_v; ?></td>
				
			</tr>
		<?php
		}
		?>
			</tbody>
			</table>
		<?php
			
}
//function get_dates_to_restrict(){
	
//}

function get_all_disabled_dates(){
	$get_hours_limit = get_post_meta(1001, 'hours_limit', true);
	$disable_dates = array();
		$all_dates = get_daily_hours();
		foreach($all_dates as $key => $value){
			if($value >= $get_hours_limit){
				$origDate = $key;
 
				$newDate = date("d.m.Y", strtotime($origDate));
				
				$disable_dates[] = $newDate;
			}
		}
	$disable_dates = json_encode($disable_dates);
	return $disable_dates;
}
	
add_action('wp_footer', 'add_this_script_footer'); 
function add_this_script_footer(){ 

		$disable_dates = get_all_disabled_dates();
		if ( !is_user_logged_in() ) {
			wp_dequeue_script('js-divi-popup');
			?>
			<script type="text/javascript">
				jQuery(document).ready(function( $ ) {
					jQuery('a.et_pb_button.et_pb_pricing_table_button').attr('href', '/wp-admin');
				});	
			</script>
			<?php
			
		}else{
			wp_enqueue_script( 'js-divi-popup' );
		}
?>
	
	<script type="text/javascript">
	jQuery(document).ready(function( $ ) {
		var disabledDates = '<?php echo $disable_dates ; ?>';
		//alert(disabledDates);
		jQuery('#free_date_timepicker_start, #hourly_datetimepicker_start, #hourly_datetimepicker_end, #daily_datetimepicker_start, #monthly_datetimepicker_start').datetimepicker({
			disabledDates: disabledDates, formatDate:'d.m.Y',
		});
	});	
	</script>
<?php }
	

add_action("wp_ajax_disable_dates", "disable_dates");
add_action("wp_ajax_nopriv_disable_dates", "disable_dates");
function disable_dates(){
	$meeting_start = $_POST['start_date'];
	$meeting_end = $_POST['end_date'];
	$get_hours_limit = get_post_meta(1001, 'hours_limit', true);
		// get start date
		$start_date_only = new DateTime($meeting_start);
		$start_date_only = $start_date_only->format('Y-m-d');
		
		// get end date
		$end_date_only = new DateTime($meeting_end);
		$end_date_only = $end_date_only->format('Y-m-d');
		
		// get next day date
		$tomorrow_date = new DateTime($meeting_start);
		$tomorrow_date->add(new DateInterval("P1D"));
		$tomorrow =  $tomorrow_date->format('Y/m/d');
		
		// get num of remaining hours in start date
		$datetime1 = new DateTime($meeting_start);
		$datetime2 = new DateTime($tomorrow);
		$interval = $datetime1->diff($datetime2);
		$start_hours = $interval->format('%h');
		
		// get num of hour in end date
		$end_hours = 24 - $start_hours;
		
		//get all dates between 2 dates
		$period = new DatePeriod(
			 new DateTime($meeting_start),
			 new DateInterval('P1D'),
			 new DateTime($meeting_end)
		);
		
		if($start_date_only == $end_date_only){
			// get total num of hours between 2 dates
			$date1 = new DateTime($meeting_start);
			$date2 = new DateTime($meeting_end);
			$diff = $date2->diff($date1);
			$hours = $diff->h;
			$hours = $hours + ($diff->days*24);
			
			$all_dates = get_daily_hours();
				
				foreach($all_dates as $key => $value){
					if($key == $start_date_only){
						$hourly_limit  = $hours + $value;
						if($hourly_limit >= $get_hours_limit){
							echo '0';
						}
					}
				}
		}
	else{
		
		$dates_array = array();
		
		foreach ($period as $key => $value) {
			//var_dump($value);
			$dates_array[] = $value->format('d.m.Y')	;	
			$counter ++;			
		}
		
		// get all dates/hours and add into array in dates => hours pairs
		$dates_array_compare = array();
		$counter = '0';
		
		foreach ($period as $key => $value) {
			
			if($counter == 0){
				$dates_array_compare[$value->format('Y-m-d')] = $start_hours;
			}
			else{
				$dates_array_compare[$value->format('Y-m-d')] = 24; 	
			}
			if ($value == end($period) ) {
				$dates_array_compare[$end_date_only] = $end_hours;
			}
			
			$counter ++;			
		}
		ksort($dates_array_compare);
		//var_dump($dates_array_compare);
		
		$disable_dates = get_all_disabled_dates();
		$new_disable_dates = json_decode($disable_dates, true);
		
		$result = array_diff($dates_array,$new_disable_dates);
		if(!empty($result)){
			if($dates_array == $result){
				$all_dates = get_daily_hours();
				//var_dump($all_dates);
				$empty_arr = array();
				foreach($all_dates as $key => $value){
					
					foreach($dates_array_compare as $key2 => $value2 ){
						if($key == $key2){
							$sum_value = $value + $value2;
							
							if($sum_value >= $get_hours_limit){
							$empty_arr[$key]= $sum_value;
							}
						}
					}
					
				}
				if(!empty($empty_arr)){
					echo '1';
				}
				
			}
			else{
				echo "false";
			}
		}
	}
	die();
}
function my_login_redirect( $redirect_to, $request, $user ) {
    //is there a user to check?
    global $user;
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {

        if ( in_array( 'customer', $user->roles ) ) {
            // redirect them to the default place
            return '/pricing';
        } else {
            return home_url();
        }
    } else {
        return $redirect_to;
    }
}
add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );

add_action( 'woocommerce_email_after_order_table', 'add_link_back_to_order', 10, 2 );
function add_link_back_to_order( $order, $is_admin ) {

	// Only for admin emails
	if ( ! $is_admin ) {
		return;
	}

	// Open the section with a paragraph so it is separated from the other content
	$link = '<p>';

	// Add the anchor link with the admin path to the order page
	$link .= '<a href="'. admin_url( 'post.php?post=' . absint( $order->id ) . '&action=edit' ) .'" >';

	// Clickable text
	$link .= __( 'Click here to go to the booking page', 'Divi' );

	// Close the link
	$link .= '</a>';

	// Close the paragraph
	$link .= '</p>';

	// Return the link into the email
	echo $link;

}
function sv_add_email_register_section( $order, $sent_to_admin, $plain_text ) {
	$my_account_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
	// bail if the account page endpoint isn't set or if this is an admin email
	if ( ! $my_account_url || $sent_to_admin ) {
		return;
	}
	// only add this section for guest users who will return false
	
		
		ob_start(); 
	  
		?>
		<h3 style="text-align: center; margin-bottom: 2em;">View Booking Details</h3>
		<div style="text-align: center; margin-bottom: 2em;">
			<!---<p>Want us to remember you for next time? You can easily register an account to set preferred shipping and billing addresses, securely save payment methods, and view your complete purchase history. It takes less than 20 seconds, and will make shopping fabulously easy!</p> --->
			<a href="<?php echo $my_account_url . 'booking-details/'; ?>" target="_blank" style="border: 2px solid #a46497; border-radius: 5px; max-width: 400px; margin: 0.5em auto; padding: 10px 30px; text-decoration: none;">Booking Details</a>
		</div>
		<?php
		
		echo ob_get_clean();
}
add_action( 'woocommerce_email_order_meta', 'sv_add_email_register_section', 50, 3 );