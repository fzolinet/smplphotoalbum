/**
 * Order an image from server
 */
(function($, Drupal){
	$("#smpl_sortorder, #smpl_ascdesc").change(function(){
		let v = $("button.smpl_pager_current").val();
		$("input#smplpagehidden").val(v);
		$("#SmplTableAll").submit();
	});
})(jQuery, Drupal);