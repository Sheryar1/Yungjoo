jQuery(document).ready(function( $ ) {
	var minDateTime =  new Date();
	minDateTime.setHours(minDateTime.getHours()-1);
	
	// Free Package
	
	jQuery('#free_date_timepicker_start').datetimepicker({
		minDate: 0,
		minDateTime: minDateTime,
		defaultSelect: false,
		todayButton: false,
		//timepicker: false,
		
		onSelectTime:function(ct,$i){
			console.log($i);
			  var daily_start_datee =  jQuery('#free_date_timepicker_start').val();
			  var daily_end_datee =  jQuery('#free_date_timepicker_end').val();
			  if(daily_end_datee != ""){
				  //alert(daily_start_datee);
				jQuery.ajax({
					 type : "post",
					 url : custom_ajax.ajaxurl,
					 data : {action: "disable_dates",  start_date: daily_start_datee, end_date: daily_end_datee },
					 success: function(response) {
						if(response == 'false'){
							console.log(response);
							jQuery(".error_msg").append("This date is not available please select any other date");
							jQuery('#free_date_timepicker_start').val(" ");
							jQuery('#free_date_timepicker_end').val(" ");					
						}else if(response == '1'){
							jQuery(".error_msg").append("This date is not available please select any other date");
							jQuery('#free_date_timepicker_start').val(" ");
							jQuery('#free_date_timepicker_end').val(" ");		
						}
						else if(response == '0'){
							jQuery(".error_msg").append("This date is not available please select any other date");
							jQuery('#free_date_timepicker_start').val(" ");
							jQuery('#free_date_timepicker_end').val(" ");		
						}
						else{
							console.log(response);
						}
									
					 }
				});
			  }
			 // e.preventDefault();
			}
		
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
		
		newdate = year+'/'+month+'/'+day+' '+hour+':'+min;

		
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
		defaultSelect: false,
		todayButton: false,
    });	
	
	jQuery("#hourly_datetimepicker_start").change(function(){
		var hour_start_date =  jQuery('#hourly_datetimepicker_start').val();
		var hour_start_date =  new Date(hour_start_date);
		hour_start_date.setHours(hour_start_date.getHours()+1);
		
		jQuery('#hourly_datetimepicker_end').datetimepicker({
			minDate: 0,
			minDateTime: hour_start_date,
			defaultSelect: false,
			todayButton: false,
			onSelectTime:function(ct,$i){
			console.log($i);
			  var daily_start_datee =  jQuery('#hourly_datetimepicker_start').val();
			  var daily_end_datee =  jQuery('#hourly_datetimepicker_end').val();
			  if(daily_end_datee != ""){
				  //alert(daily_start_datee);
				jQuery.ajax({
					 type : "post",
					 url : custom_ajax.ajaxurl,
					 data : {action: "disable_dates",  start_date: daily_start_datee, end_date: daily_end_datee },
					 success: function(response) {
						if(response == 'false'){
							console.log(response);
							alert("This date is not available please select any other date");
							jQuery('#hourly_datetimepicker_start').val(" ");
							jQuery('#hourly_datetimepicker_end').val(" ");					
						}else if(response == '1'){
							alert("This date is not available please select any other date");
							jQuery('#hourly_datetimepicker_start').val(" ");
							jQuery('#hourly_datetimepicker_end').val(" ");		
						}
						else if(response == '0'){
							alert("This date is not available please select any other date");
							jQuery('#hourly_datetimepicker_start').val(" ");
							jQuery('#hourly_datetimepicker_end').val(" ");		
						}
						else{
							console.log(response);
						}
									
					 }
				});
			  }
			 // e.preventDefault();
			}	
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
		
		if(h_start_date != "" && h_end_date != ""){  
			var hourly_url = jQuery(this).attr('href');
			var hourly_start_date = hourly_url.replace("f_s_date", h_start_date);
			var hourly_end_date = hourly_start_date.replace("f_e_date", h_end_date);
			var hourly_quantity = hourly_end_date.replace("quantity=1", "quantity="+hour_diff);			
			var hourly_final_url = jQuery('#hourly_package_btn').attr("href", hourly_quantity);
			
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
		defaultSelect: false,
		todayButton: false,
		//todayButton: true,
		onSelectTime:function(ct,$i){
			console.log($i);
		  var daily_start_datee =  jQuery('#daily_datetimepicker_start').val();
		  var daily_end_datee =  jQuery('#daily_datetimepicker_end').val();
		  if(daily_end_datee != ""){
			  //alert(daily_start_datee);
		    jQuery.ajax({
				 type : "post",
				 url : custom_ajax.ajaxurl,
				 data : {action: "disable_dates",  start_date: daily_start_datee, end_date: daily_end_datee },
				 success: function(response) {
					if(response == 'false'){
						console.log(response);
						alert("This date is not available please select any other date");
						jQuery('#daily_datetimepicker_start').val(" ");
						jQuery('#daily_datetimepicker_end').val(" ");					
					}else if(response == '1'){
						alert("This date is not available please select any other date");
						jQuery('#daily_datetimepicker_start').val(" ");
						jQuery('#daily_datetimepicker_end').val(" ");		
					}
					else{
						console.log(response);
					}
								
				 }
			});
		  }
		 // e.preventDefault();
		}	
    });
	
	jQuery("#daily_datetimepicker_start").on('change', function(){
		//alert(jQuery('#daily_datetimepicker_start').val());
		var num_of_days = jQuery('#no_of_days').val();
		var daily_start_datee =  jQuery('#daily_datetimepicker_start').val();
		//alert(daily_start_datee);
		
		
		var daily_start_date =  new Date(daily_start_datee);
		
		var month = ('0' + (daily_start_date.getMonth()+1)).slice(-2);
		var year = daily_start_date.getFullYear();
		var day = ('0'+daily_start_date.getDate()).slice(-2);
		
		var day_end = parseInt(day) + parseInt(num_of_days);
		console.log(day_end);
		
		var hour = daily_start_date.getHours();
		var min = ('0'+daily_start_date.getMinutes()).slice(-2);
		var sec = ('0'+daily_start_date.getMilliseconds()).slice(-2);
		
		new_start_date = year+'/'+month+'/'+day_end+' '+hour+':'+min;
		if(daily_start_datee != ""){
			var end_date = jQuery('#daily_datetimepicker_end').val(new_start_date);
			if(end_date != ""){
				
					
			}
		}
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
			var daily_final_url = jQuery('#daily_package_btn').attr("href", daily_quantity);
			
		}
		else{
			jQuery('.free_error_msg').show();
			return false;
		}
	});
	
	// Weekly Package
	jQuery('#monthly_datetimepicker_start').datetimepicker({
		minDate: 0,
		minDateTime: minDateTime,
		defaultSelect: false,
		todayButton: false,
		onSelectTime:function(ct,$i){
			console.log($i);
		  var daily_start_datee =  jQuery('#monthly_datetimepicker_start').val();
		  var daily_end_datee =  jQuery('#monthly_datetimepicker_end').val();
		  if(daily_end_datee != ""){
			  //alert(daily_start_datee);
		    jQuery.ajax({
				 type : "post",
				 url : custom_ajax.ajaxurl,
				 data : {action: "disable_dates",  start_date: daily_start_datee, end_date: daily_end_datee },
				 success: function(response) {
					if(response == 'false'){
						console.log(response);
						alert("This date is not available please select any other date");
						jQuery('#monthly_datetimepicker_start').val(" ");
						jQuery('#monthly_datetimepicker_end').val(" ");					
					}else if(response == '1'){
						alert("This date is not available please select any other date");
						jQuery('#monthly_datetimepicker_start').val(" ");
						jQuery('#monthly_datetimepicker_end').val(" ");		
					}
					else{
						console.log(response);
					}
								
				 }
			});
		  }
		 // e.preventDefault();
		}	
    });
	jQuery("#monthly_datetimepicker_start").change(function(){
		var monthly_start_date =  jQuery('#monthly_datetimepicker_start').val();
		var monthly_start_date =  new Date(monthly_start_date);
		
		var month = ('0' + (monthly_start_date.getMonth()+1)).slice(-2);
		var year = monthly_start_date.getFullYear();
		var day = ('0'+monthly_start_date.getDate()).slice(-2);
		
		var month_end = parseInt(month) + parseInt(1);
		console.log(month_end);
		
		var hour = monthly_start_date.getHours();
		var min = ('0'+monthly_start_date.getMinutes()).slice(-2);
		var sec = ('0'+monthly_start_date.getMilliseconds()).slice(-2);
		
		new_start_date = year+'/'+month_end+'/'+day+' '+hour+':'+min;
		jQuery('#monthly_datetimepicker_end').val(new_start_date);
	});
	
	jQuery('#monthly_pkg #monthly_package_btn').click(function() {
		var m_start_date = jQuery( '#monthly_datetimepicker_start' ).val();
		var m_end_date = jQuery( '#monthly_datetimepicker_end' ).val();
		if(m_start_date != "" || m_end_date != ""){  
			var free_url = jQuery(this).attr('href');
			var free_new_url = free_url.replace("f_s_date", m_start_date);
			var free_neww_url = free_new_url.replace("f_e_date", m_end_date);  
			var free_final_url = jQuery('#monthly_package_btn').attr("href", free_neww_url);
		}
		else{
			jQuery('.free_error_msg').show();
			return false;
		}
	});
	
	// DataTable
		jQuery('#example').DataTable();
		jQuery('#invitations').DataTable();
		
	// Invite Popup
	jQuery('.modal-toggle').on('click', function(e) {
	  e.preventDefault();
	  jQuery('.moda').toggleClass('is-visible');
	});	
	
	// Add more fields
	 var max_fields = 25;
	 
     var add_input_button = jQuery('.add_input_button');
	 
     var field_wrapper = jQuery('.invite_wrap_inner');
	 
     var new_field_html = '<div><input type="text" name="participant_name[]" id="" required /><input type="email" name="participant_email[]" id="" required /><input type="hidden" name="participant_id[]" value="" /><a href="javascript:void(0);" class="remove_input_button" title="Remove field"><i class="fa fa-times" aria-hidden="true"></i>Remove</a></div>';
	 
     var input_count = 1;
     // Add button dynamically
     jQuery(add_input_button).click(function(){
	   var $user_id = jQuery('#host_id').val();    	
       if(input_count < max_fields){
       	input_count++;
        jQuery(field_wrapper).append(new_field_html);
       }
     });
	
	// Remove dynamically added button
	jQuery(field_wrapper).on('click', '.remove_input_button', function(e){
		e.preventDefault();
		jQuery(this).parent('div').remove();
		input_count--;
	});

	// Invite Ajax
	jQuery('input[name="submitted"]').click( function(e) {
	e.preventDefault(); 	
		
      //var ajax_url = "<?= admin_url('admin-ajax.php'); ?>";
	  	  //alert(ajax_url);
	  var host_id = jQuery('input[name*="host_id"]').val();
	  var event_id = jQuery('input[name*="event_id"]').val();
      var par_name = jQuery('input[name*="participant_name[]"]').map(function(){return jQuery(this).val();}).get();
      var par_email = jQuery('input[name*="participant_email[]"]').map(function(){return jQuery(this).val();}).get();
      var par_id = jQuery('input[name*="participant_id[]"]').map(function(){return jQuery(this).val();}).get();

      jQuery.ajax({
         type : "post",
         url : custom_ajax.ajaxurl,
         data : {action: "save_invites_data",  host_id: host_id, event_id: event_id, par_name : par_name, par_email: par_email, par_id: par_id },
		 beforeSend: function() {
			jQuery('.response_div').html("Processing....");
		  },
         success: function(response) {
               jQuery('.response_div').html(response);    
         }
      });  

   });
  
  
  
 }); 


  