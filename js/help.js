(function($, Drupal, smpl){	
	let SmplHelp = $("#smpl_help");
	SmplHelp.resizable().draggable();
	$("#smpl_help_link").click(function(e){
		smpl.progress(true);
		let url   = smpl.ajax + "/help/";
		$.ajax({
			url: url,
			type: "get",
			success: function(response){
				$("div#smpl_help_content").html(response[0].data);
				smpl.progress(false);
				SmplHelp.show();
			}	
		});
	});
	
	//close smpl content window
	$("#smpl_help_close, form#SmplTableAll, main").click(function(){ 
			SmplHelp.hide();
	});
	
	$(document).keydown(function(e){
	  if( e.key == "Escape" && SmplHelp.is(":visible") ){
	    SmplHelp.hide();
	  }
	})
})(jQuery, Drupal, smpl);
