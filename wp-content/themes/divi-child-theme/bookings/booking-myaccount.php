<h3>Bookings</h3>
<?php
	
	$current_user_id = $current_user->ID;
	var_dump($current_user);

	require_once('../../../../wp-config.php');
	
	
	 if (function_exists('ch_get_bookings_data')) { 
		$bookings_data = ch_get_bookings_data();
		
		foreach($bookings_data as $booking_id => $booking_value){
			
			$user_id = $booking_value->host_id;
			if($current_user_id == $user_id){
				echo'<pre>';var_dump($user_id);echo'</pre>'; 
			}
			
		}
	}
 ?>
<table id="example" class="table table-striped table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr>
							<th>Name</th>
							<th>Position</th>
							<th>Office</th>
							<th>Age</th>
							<th>Start date</th>
							<th>Salary</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>Name</th>
							<th>Position</th>
							<th>Office</th>
							<th>Age</th>
							<th>Start date</th>
							<th>Salary</th>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td>Tiger Nixon</td>
							<td>System Architect</td>
							<td>Edinburgh</td>
							<td>61</td>
							<td>2011/04/25</td>
							<td>$320,800</td>
						</tr>
						
					</tbody>
				</table>