/**
 * Edit properties of image
 */

(function($, Drupal ){
	//Edit button
	$('.smpl_edit').mouseover(function(){
		$(this).css('cursor','pointer');
	});

	$('.smpl_edit').mouseout(function(){
		$(this).css('cursor','default');
	});
	
 	/**
 	 * Edit command to show / hide the Edit form of properties
 	 * with parameters of actual item
 	 */
	var SmplEditForm = $("div#SmplEditForm");
	// Draggable window
	
	if(SmplEditForm.Length >0){
		SmplEditForm.draggable();	
	}
	
	$(".smpl_edit").click(function(e){
		var id  = $(this).attr('id').substring(6);
		if(SmplEditForm.css('display') == "none")
		{
			pos = $(this).position();		
			SmplEditForm.hide();
			$(".ajax-progress-fullscreen").remove();
			$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
			var url  = smpl.ajax + "/edit/" + id;
			$.ajax({
				url: url,
				type: "GET",
				success: function(response){
					var data = JSON.parse(response[0].data);					
					$("input#smpl_edit_id").val(id);
					$("input#smpl_name").val(data.name);
					$("input#smpl_sub").val(data.subtitle);
					$("input#smpl_link").val(data.link);
					setDivInWindow(SmplEditForm, pos , e.pageX, e.pageY);
					$(".ajax-progress-fullscreen").remove();
				},
				error: function(response){
					$(".ajax-progress-fullscreen").remove();
					alert("Error on server side: " + id);
				},
			});
		}
		else
		{
			/**
			 * Are you sure to cancel all modify
			 */
			var id = $('input#smpl_id').val();
			if(id.length >0 && window.confirm("Are you sure to cancel the modifies?")){				
				SmplEditForm.hide(smpl_eff);								
			}
		}
	});

	/**
	 * click on cancel button of windows of properties
 	*/
	$("button#SmplEditFormCancel").click(function(){
		$("div#SmplEditForm").hide();				
		$("input#smpl_edit_id").val('');
		$("input#smpl_name").val('');
		$("input#smpl_sub").val('');
		$("input#smpl_link").val('');
		return false;
	});
		
	/**
	 * Edit form send to server
	 */
	$("button#SmplEditFormSubmit").click(function(){
		//Save the datas of form to server
		var id = $("input#smpl_edit_id").val();
		var formData = {
			subtitle: $("input#smpl_sub").val(),
			link:     $("input#smpl_link").val(),	
		};
		var sendData = JSON.stringify(formData);

		var url = smpl.ajax + "/update/" + id;
		var method = "GET"
		if(method == "GET"){
			url += "/?json="+sendData;
		}
		$(".ajax-progress-fullscreen").remove();
		$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
		$.ajax({
			url: url,
			type: method,
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: sendData,
			success: function(response){				
				var data = JSON.parse(response[0].data);				
				if(data.db == 1){
					$("div#smpl_sub"+id).html(data.subtitle);
				}
				
				$("input#smpl_edit_id").val('');
				$("input#smpl_name").val('');
				$("input#smpl_sub").val('');
				$("input#smpl_link").val(''),
				$(".ajax-progress-fullscreen").remove();
				$("div#SmplEditForm").hide();
			},
			error :  function(response){
				$(".ajax-progress-fullscreen").remove();
				$("div#SmplEditForm").hide();
				alert('JSON error: ' + response.toString());
			},
		});
		return false;	
	});	
})(jQuery, Drupal);