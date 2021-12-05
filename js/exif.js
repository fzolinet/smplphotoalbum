/**
 * Description of an image
 */

(function($, Drupal){
  /*
   * Description & Exif info window show / hide
  */

  if(typeof smpl !== 'undefined'){	
	  $('a.smpl_desc_a').click(function(){
		var id  = $(this).attr('id').substring(5);
		
		var pos = $(this).position(); 
		obj = $("div#DescSub"+id);
		obj.css('left',pos.left + 'px');
		obj.css('top',pos.top+ 'px');
		obj.draggable();
		$(".ajax-progress-fullscreen").remove();
		$("div.smpl_desc_info").hide();
		var url = smpl.ajax + "/exif/" + id;
		$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				var s = response[0].data;
				$("div#Exif" + id).html(s);
				$(".ajax-progress-fullscreen").remove();
				obj.show();
				
				smpl.desc_info_show = true;
			}
		});		
	  });

	/*
	 * Desc info hide
	 */
	$('div.smpl_desc_info').click( function (){
		$(this).hide();
		smpl.desc_info_show = false;
	});

	//If click on some element of body the windows will close
	$('body').click( function (event){
		var x = event.target.className;
		if(x != "smpl_desc_a" ){
			$('div.smpl_desc_info').hide();
			if(typeof smpl !== 'undefined'){
				smpl.desc_info_show = false;
			}
		}
	});
  }	
})(jQuery, Drupal);
