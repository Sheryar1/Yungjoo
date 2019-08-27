jQuery(document).ready(function( $ ) {
	var minDateTime =  new Date();
	minDateTime.setHours(minDateTime.getHours()-1);
	
	// Free Package
	
	jQuery('#free_date_timepicker_start').datetimepicker({
		minDate: 0,
		minDateTime: minDateTime,
		todayButton: true,
		
	});
	
	jQuery("#free_date_timepicker_start").change(function(){
		var free_start_date =  jQuery('#free_date_timepicker_start').val();
		var free_start_date =  new Date(free_start_date);
		
		var month = ('0' + (free_start_date.getMonth()+1)).slice(-2);
		var year = free_start_date.getFullYear();
		var day = ('0'+free_start_date.getDate()).slice(-2);
		
		var hour = free_start_date.getHours()+1;
		var min = ('0'+free_start_date.getMinutes()).slice(-2);
		var sec = ('0'+free_start_date.getMilliseconds()).slice(-2);
		
		newdate = year+'/'+month+'/'+day+' 0'+hour+':'+min;

		
		jQuery('#free_date_timepicker_end').val(newdate);
	});
	
	jQuery('#free_pkg #free_package_btn').click(function() {
		var f_start_date = jQuery( '#free_date_timepicker_start' ).val();
		var f_end_date = jQuery( '#free_date_timepicker_end' ).val();
		if(f_start_date != "" || f_end_date != ""){  
			var free_url = jQuery(this).attr('href');
			var free_new_url = free_url.replace("f_s_date", f_start_date);
			var free_neww_url = free_new_url.replace("f_e_date", f_end_date);  
			var free_final_url = jQuery('#free_package_btn').attr("href", free_neww_url);
		}
		else{
			jQuery('.free_error_msg').show();
			return false;
		}
	});
	
	// Hourly Package
	
	jQuery('#hourly_datetimepicker_start').datetimepicker({
		minDate: 0,
		minDateTime: minDateTime,
		todayButton: true,
    });	
	
	jQuery("#hourly_datetimepicker_start").change(function(){
		var hour_start_date =  jQuery('#hourly_datetimepicker_start').val();
		var hour_start_date =  new Date(hour_start_date);
		hour_start_date.setHours(hour_start_date.getHours()+1);
		
		jQuery('#hourly_datetimepicker_end').datetimepicker({
			minDate: 0,
			minDateTime: hour_start_date,
			todayButton: true,
		});	
	});
	
	jQuery('#hourly_pkg #hourly_package_btn').click(function() {
		var h_start_date = jQuery( '#hourly_datetimepicker_start' ).val();
		var h_startdate =  new Date(h_start_date);
		var start_hour = h_startdate.getHours();
		var h_end_date = jQuery( '#hourly_datetimepicker_end' ).val();
		var h_enddate =  new Date(h_end_date);
		var end_hour = h_enddate.getHours();
		var hour_diff = end_hour - start_hour;
		
		if(h_start_date != "" || h_end_date != ""){  
			var hourly_url = jQuery(this).attr('href');
			var hourly_start_date = hourly_url.replace("f_s_date", h_start_date);
			var hourly_end_date = hourly_start_date.replace("f_e_date", h_end_date);
			var hourly_quantity = hourly_end_date.replace("quantity=1", "quantity="+hour_diff);			
			var hourly_final_url = jQuery('#free_package_btn').attr("href", hourly_quantity);
			
		}
		else{
			jQuery('.free_error_msg').show();
			return false;
		}
	});
	
	// Daily Package
	jQuery('#daily_datetimepicker_start').datetimepicker({
		minDate: 0,
		minDateTime: minDateTime,
		todayButton: true,
    });
	
	jQuery("#daily_datetimepicker_start").change(function(){
		var num_of_days = jQuery('#no_of_days').val();
		var daily_start_date =  jQuery('#daily_datetimepicker_start').val();
		var daily_start_date =  new Date(daily_start_date);
		
		var month = ('0' + (daily_start_date.getMonth()+1)).slice(-2);
		var year = daily_start_date.getFullYear();
		var day = ('0'+daily_start_date.getDate()).slice(-2);
		
		var day_end = parseInt(day) + parseInt(num_of_days);
		console.log(day_end);
		
		var hour = daily_start_date.getHours();
		var min = ('0'+daily_start_date.getMinutes()).slice(-2);
		var sec = ('0'+daily_start_date.getMilliseconds()).slice(-2);
		
		new_start_date = year+'/'+month+'/'+day_end+' '+hour+':'+min;
		jQuery('#daily_datetimepicker_end').val(new_start_date);
	});
	
	jQuery('#daily_pkg #daily_package_btn').click(function() {
		var d_start_date = jQuery( '#daily_datetimepicker_start' ).val();
		var d_end_date = jQuery( '#daily_datetimepicker_end' ).val();
		var days_num = jQuery('#no_of_days').val();
		
		
		if(d_start_date != "" || d_end_date != ""){  
			var daily_url = jQuery(this).attr('href');
			var daily_start_date = daily_url.replace("f_s_date", d_start_date);
			var daily_end_date = daily_start_date.replace("f_e_date", d_end_date);
			var daily_quantity = daily_end_date.replace("quantity=1", "quantity="+days_num);			
			var daily_final_url = jQuery('#free_package_btn').attr("href", daily_quantity);
			
		}
		else{
			jQuery('.free_error_msg').show();
			return false;
		}
	});
  
 }); 


  