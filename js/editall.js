/**
 * Edit properties of a path
 */
(function($, Drupal ){
	//Edit button
	$('#EditAll').mouseover(function(){
		$(this).css('cursor','pointer');
	});
	
	$('#EditAll').mouseout(function(){
		$(this).css('cursor','default');
	});
	
	$('#EditAll').click(function(e){
		var id  = $('div.smpl_item_container:first').attr('id').substring(4);		
		$(".ajax-progress-fullscreen").remove();
		$('body').after( Drupal.theme.ajaxProgressIndicatorFullscreen() );
		var url  = smpl.ajax + "/updatepath/" + id;		
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				var data = JSON.parse(response[0].data);
				window.location.reload();
				$(".ajax-progress-fullscreen").remove();
			},
			error: function(response){
				$(".ajax-progress-fullscreen").remove();
				alert("Error on server side: " + response[0].data);
			},
		});
	})
	
})(jQuery, Drupal);
