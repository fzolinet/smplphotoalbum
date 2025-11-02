// import { progress, fz_t } from './lib.js';
(function($, Drupal, smpl ){
	$("input#smpl_filter").on("focus",function(){
			$(this).attr("placeholder","");
	});
	
	$("input#smpl_filter").change(function(){
		if( $(this).val().length<1 )
			 $("#SmplTableAll").submit();
		else{
			$(this).attr("placeholder","Filter");
			$("input#smpl_filter").css("border", "solid 1px grey");
		}
	});
	
	$("#SmplFilterBtn").on("click", function(e){
		if( $("input#smpl_filter").val().length > 0 )
			 $("#SmplTableAll").submit();
		else{
			$("input#smpl_filter").css("border", "solid 1px red");
			event.preventDefault();
		}
		return false;
	});
})(jQuery, Drupal, smpl);