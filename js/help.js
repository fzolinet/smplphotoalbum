(function($,Drupal){	
	var smpl_help = $("div#smpl_help");	
	$("#smpl_help_link").click(function(e){
		progress(true);
		var url   = smpl.ajax + "/help/";		
		$.ajax({
			url: url,
			type: "get",
			success: function(response){				
				$("div#smpl_help_content").html(response[0].data);				
				progress(false);
				smpl_help.show();
			}	
		});
		
	});
	//close smpl content window
	$("#smpl_help_close").click(function(){             
        smpl_help.hide();
	});
	
	// the help window is draggable
	if(smpl_help.Length>0){
		smpl_help.draggable();		
	}
	
	function progress(on){	
		$(".ajax-progress-fullscreen").remove();
		if(typeof on !== 'undefined' && on){
			$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
		}
	}
})(jQuery, Drupal);
