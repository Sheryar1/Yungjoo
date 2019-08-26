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
		var start_datet =  new Date(start_datet);
		
		var month = ('0' + (start_datet.getMonth()+1)).slice(-2);
		var year = start_datet.getFullYear();
		var day = ('0'+start_datet.getDate()).slice(-2);
		
		var hour = start_datet.getHours()+1;
		var min = ('0'+start_datet.getMinutes()).slice(-2);
		var sec = ('0'+start_datet.getMilliseconds()).slice(-2);
		
		newdate = year+'/'+month+'/'+day+' '+hour+':'+min;

		
		jQuery('#free_date_timepicker_end').val(newdate);
	});   
	  
	jQuery('#datetimepicker2').datetimepicker();
	jQuery('#datetimepicker3').datetimepicker();
	jQuery('#datetimepicker4').datetimepicker();
	jQuery('#datetimepicker5').datetimepicker();
	jQuery('#datetimepicker6').datetimepicker();
	jQuery('#datetimepicker7').datetimepicker();
});


  