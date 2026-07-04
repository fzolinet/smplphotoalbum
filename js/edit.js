/**
 * Edit properties of image
 */
(function ($, Drupal, smpl, Swal) {
	//Edit button
	$('.smpl_edit').mouseover(function () {
		$(this).css('cursor', 'pointer');
	});

	$('.smpl_edit').mouseout(function () {
		$(this).css('cursor', 'default');
	});

	/**
	 * Edit command to show / hide the Edit form of properties
	 * with parameters of actual item
	 */
	let SmplEditForm = $("div#SmplEditForm");

	// Draggable window
	if (SmplEditForm.length > 0) {
		SmplEditForm.draggable();
	}

	var video_aspect_ratio = 1.0;
	var video_size_changed = false;

	$("button[id*='SubBtn']").click(function (e) {
		let id = $(this).attr('id').substring(6);
		smpl.editsaved = false;
		smpl.progress(true);
		var url = smpl.ajax + "/edit/" + id;
		$.ajax({
			url: url,
			type: "POST",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				$("input#smpl_edit_id").val(id);
				$("input#smpl_name").val(data.name);
				$("textarea#smpl_sub").val(data.subtitle);
				$("input#smpl_importance").val(data.importance);
				$("select#smpl_type option").attr("selected", false).change();
				$("select#smpl_type option[value='" + data.typ + "']").attr("selected", "selected").change();
				$("input#smpl_link").val(data.link);
				switch (data.typ) {
					case "image":
						$("#smplairecognition").show();
						$("#smplairecognition-info").show();
						$("#smpl_ai_check").val(data.ai);
						$(".smpl_ai_check").show();
						break;
					case "video":
						video_aspect_ratio = parseFloat( data.height / data.width);
						video_size_changed = false;
						$("tr#smpl_video2mp4").show();
						$("input#smpl_video_size").val((data.filesize).toLocaleString());
						$("input#smpl_video_width").val(data.width);
						$("input#smpl_video_height").val(data.height);
						$("input#smpl_video_framerate").val(data.framerate);
						$("input#smpl_video_clip_start").val(0);
						$("input#smpl_video_clip_duration").val(data.length);
						$("input#smpl_video_clip_end").val(data.length);
						video_aspect_ratio = parseFloat(data.height / data.width);
						break;
					case "folder":
						$("tr#smpl_link").hide();
						$("input#smpl_type").hide();
						$("tr#smpl_type").hide();
						$("tr#smpl_importance").hide();
						break;
					default:
						$("#smplairecognition").hide();
						$("#smplairecognition-info").hide();
						$(".smpl_ai_check").hide();
						$("tr#smpl_video2mp4").hide();
				}


				let pos = $("#SubBtn" + id).offset();
				let dy = parseFloat($("html").css("font-size")) * 5;
				SmplEditForm.show();
				let w = SmplEditForm.width();
				let ww = window.innerWidth;
				pos.left = (ww - w) * 0.5;
				if (pos.left < 0) {
					pos.left = 0
				} else if (pos.left + w > ww) {
					pos.left = ww - w - 2 * dy;
				}
				SmplEditForm.offset({ top: pos.top + dy, left: pos.left });
				smpl.progress(false);
			},
			error: function (response) {
				smpl.progress(false);
			},
		});
	});

	$("#smpl_video_width").on("change", function () {		
		if ($("#smpl_video_aspect").is(":checked")) {			
			var h = Math.round(parseFloat($(this).val()) * video_aspect_ratio);
			$("input#smpl_video_height").val( h );
		}
		video_size_changed = true;
	})

	$("#smpl_video_height").on("change", function () {		
		if ($("#smpl_video_aspect").is(":checked")) {			
			var w = Math.round(parseFloat( $(this).val()) / video_aspect_ratio);
			$("input#smpl_video_width").val( w );
		}
		video_size_changed = true;
	})
	/**
	 * click on cancel button of windows of properties
	 * @return false
		 */
	$("#SmplEClose").click(function (e) {
		if (smpl.editsaved === false) {
			Swal.fire({
				title: smpl.words.Properties_not_saved + " " +smpl.words.Close_the_window,
				html: smpl.words.Edit_not_saved,
				className: "smpl-message-warning",
				closeOnClickOutside: true,
				closeOnEsc: true,
				dangerMode: true,
				showCloseButton: true,
				showCancelButton: true,
				cancelButtonText: smpl.words.Cancel,
				confirmButtonText: smpl.words.Confirm,
				icon: "warning",
				animation: false
			})
				.then((ok) => {
					if (ok.isConfirmed) {
						smpl.editsaved = true;
						SmplEditForm.hide();
						$("input#smpl_edit_id").val('');
						$("input#smpl_name").val('');
						$("textarea#smpl_sub").val('');
						$("input#smpl_importance").val(0);
						$("select#smpl_type option").attr("selected", false).change();
						$("select#smpl_type option[value='image']").attr("selected", "selected").change();
						$("input#smpl_link").val('');
					}
					e.preventDefault();
					return false;
				});
		}
		e.preventDefault();
		return false;
	});

	$("#smpl_type").on("change", function () {
		if ($(this).val() == "image") {
			$("#smplairecognition").show();
			$("#smplairecognition-info").show();
			$(".smpl_ai_check").show();
		} else {
			$("#smplairecognition").hide();
			$("#smplairecognition-info").hide();
			$(".smpl_ai_check").hide();
		}
	});

	/**
	 * Edit form send to server
	 * @return false
	 */
	$("#SmplESubmit").click(function () {
		let id = $("input#smpl_edit_id").val();
		let formData = {
			name: $("input#smpl_name").val(),
			subtitle: $("textarea#smpl_sub").val(),
			type: $("select#smpl_type option:selected").val(),
			importance: $("input#smpl_importance").val(),
			link: $("input#smpl_link").val(),
		};
		let sendData = JSON.stringify(formData);
		let url = smpl.ajax + "/update/" + id + "/?json=" + sendData;
		smpl.progress(true);

		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				if (data.db == 1 && (typeof data.subtitle !== 'undefined') && (data.subtitle !== null)) {
					$("div#smpl_sub" + id).html(data.subtitle);
				}
				$("input#smpl_edit_id").val('');
				$("input#smpl_sub").val('');
				$("input#smpl_link").val('');
				$("input#smpl_importance").val('0');
				smpl.progress(false);
				smpl.editsaved = true;
				SmplEditForm.hide();
			},
			error: function (response) {
				smpl.progress(false);
				SmplEditForm.hide();
				smpl.ErrorC('JSON error: ' + response.toString());
			},
		});
		return false;
	});

	/**
	 * AI image recognition with gemini client
	 **/
	$("#smplairecognition").click(function () {
		let id = $("input#smpl_edit_id").val();
		let url = smpl.ajax + "/ai/" + id + '/recognition';
		smpl.progress(true);
		fetch(url)
			.then(response => response.json() )
			.then(data => {
				data = JSON.parse(data[0].data);
				smpl.progress(false);
				if (data.id == -1 || data.id == -2) {
					smpl.ErrorC(data.msg);
				} else {					
					var t = $("textarea#smpl_sub").val() + " \n!!! " + data.msg;
					$("textarea#smpl_sub").val(t);
				}
			})
			.catch(error => function (error) {
				smpl.ErrorC(error.responseText);
				smpl.progress(false);
			});		
	});

	$("button#smpl_ai_check").click(function () {
		let id = $("input#smpl_edit_id").val();
		let url = smpl.ajax + "/ai/" + id + '/check';
		smpl.progress(true);

		fetch(url)
			.then(response => response.json())
			.then(data => {
				data = JSON.parse(data[0].data);
				smpl.progress(false);
				
				if (data.ok == -1 || data.ok == -2) {
					smpl.ErrorC(data.msg);
				} else {					
					$("textarea#smpl_ai_check").val(data.msg);
				}
			})
			.catch(error => function (error) {
				smpl.ErrorC(error.responseText);
				smpl.progress(false);
			})		
	});
	
})(jQuery, Drupal, smpl, Swal);