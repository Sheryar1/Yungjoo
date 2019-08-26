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
		
		//start_datet.setHours(start_datet.getHours()+1);
		
		jQuery('#free_date_timepicker_end').val(start_datet);
	});   
	  
	jQuery('#datetimepicker2').datetimepicker();
	jQuery('#datetimepicker3').datetimepicker();
	jQuery('#datetimepicker4').datetimepicker();
	jQuery('#datetimepicker5').datetimepicker();
	jQuery('#datetimepicker6').datetimepicker();
	jQuery('#datetimepicker7').datetimepicker();
});


  