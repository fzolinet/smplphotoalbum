/**
 * Delete an image from server
 */

(function($, Drupal){
	/**
	 * Send kill commandto the server
	 */
	$(".smpl_delete").click(function(){
		var id     = $(this).attr('id').substring(6);
		var name   = $("div#smpl_sub" +id).html().trim();		
		var ok = window.confirm(smpl.del +": '" +name +"'?");
		
		if (ok) {
			var url = smpl.ajax + "/delete/" + id;
			$.ajax({
				url: url,
				type: "get",
				success: function(response){
					var suc = response[0].data
					if(suc > 0){
						$("div#smpl"+id).remove();	
					}else{
						alert(smpl.del_not + ": " + name);	
					}
				}
			});
		}
	});
})(jQuery, Drupal);