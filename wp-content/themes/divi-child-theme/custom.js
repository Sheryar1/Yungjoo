jQuery(document).ready(function( $ ) {
	var minDateTime =  new Date();
	minDateTime.setHours(minDateTime.getHours()-1);
	jQuery('#free_date_timepicker_start').datetimepicker({
		minDate: 0,
		minDateTime: minDateTime,
		todayButton: true,
		
	   });
	jQuery("#free_date_timepicker_start").change(function(){
		var start_datet =  jQuery('#free_date_timepicker_start').val();
		//var hours = start_datet.getHours();
		//alert(hours);
		jQuery('#free_date_timepicker_end').val(start_datet);
	});   
	  
	jQuery('#datetimepicker2').datetimepicker();
	jQuery('#datetimepicker3').datetimepicker();
	jQuery('#datetimepicker4').datetimepicker();
	jQuery('#datetimepicker5').datetimepicker();
	jQuery('#datetimepicker6').datetimepicker();
	jQuery('#datetimepicker7').datetimepicker();
});

jQuery('#free_package_btn').click(function() {
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
  