/**
 * Order an image from server
 */

(function($, Drupal){
	$("#smpl_sortorder").change(function(){
		$("#SmplTableAll").submit();
	});
	
	$("#smpl_ascdesc").change(function(){
		$("#SmplTableAll").submit();
	});
})(jQuery, Drupal);