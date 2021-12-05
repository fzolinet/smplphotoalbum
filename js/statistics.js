/**
 * Javascript code for statistics. interface
 */

(function($,Drupal){
	$("input[id^='edit-table']").click(function(){
		id = $(this).val();	
		var url  = smpl_ajax + "/statload/" + id;
		$.ajax({ 
			type: "GET",
			url: url,
			success:function(response){
				var data = JSON.parse(response[0].data)
				$('input#edit-smplid')        .val(data.id);
				$('input#edit-smplpath')      .val(data.path);
				$('input#edit-smplname')      .val(data.name);
				$('input#edit-smplsubtitle')  .val(data.subtitle);
				$('input#edit-smpltype')      .val(data.typ);
				$('input#edit-smplviewnumber').val(data.viewnumber);
				$('input#edit-smpllink')      .val(data.link);
				$('div#smpl_msg').hide();
				$('div#smpl_msg').css("color","none");
			},
			error: function(){
				alert("Ajax error");
			}
		});
	});
	
	$("#edit-modifyrecord").click(function(){
		var id = $('input#edit-smplid').val();
		var formData ={
			subtitle: $('input#edit-smplsubtitle').val(),
			link:     $('input#edit-smpllink').val(),
			viewnumber:$('input#edit-smplviewnumber').val(),				
		}
		var sendData = JSON.stringify(formData);
		var url = smpl_ajax + "/statupdate/" + id+"/?json="+sendData;
		$(".ajax-progress-fullscreen").remove();
		$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
		$.ajax({
			url: url,
			type: "GET",
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			data: sendData,
			success: function(response){				
				var data = JSON.parse(response[0].data);				
				if(data.db == 1){
					$("div#smpl_msg").html("Record saved");
					$("div#smpl_msg").css("color","green");
					$("div#smpl_msg").show();
				}
				$(".ajax-progress-fullscreen").remove();
			},
			error: function (response){
				var data = response.toString();
				$(".ajax-progress-fullscreen").remove();
				$("div#smpl_msg").html("Problem with save. Maybe there is not right id?");
				$("div#smpl_msg").css("color","red");
				$("div#smpl_msg").show();
			}
		});
	});
	$("#edit-modifyrecord").attr("onclick",'return false;');
	$('div#smpl_msg').hide();
})(jQuery, Drupal);