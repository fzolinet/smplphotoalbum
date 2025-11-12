/**
 * Edit properties of image
 */

(function ($, Drupal, smpl) {
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

	$("button[id*='SubBtn']").click(function (e) {
		let id = $(this).attr('id').substring(6);
		smpl.progress(true);
		let url = smpl.ajax + "/edit/" + id;
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
				if (data.typ == "image") {
					$("#smplairecognition").show();
				} else {
					$("#smplairecognition").hide();
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

				//setDivInWindow( SmplEditForm, pos );
				smpl.progress(false);
			},
			error: function (response) {
				smpl.progress(false);
			},
		});
	});

	/**
	 * click on cancel button of windows of properties
	 * @return false
		 */
	$("#SmplEClose").click(function (e) {
		SmplEditForm.hide();
		$("input#smpl_edit_id").val('');
		$("input#smpl_name").val('');
		$("textarea#smpl_sub").val('');
		$("input#smpl_importance").val(0);
		$("select#smpl_type option").attr("selected", false).change();
		$("select#smpl_type option[value='image']").attr("selected", "selected").change();
		$("input#smpl_link").val('');
		e.preventDefault();
		return false;
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
				SmplEditForm.hide();
			},
			error: function (response) {
				smpl.progress(false);
				SmplEditForm.hide();
				fz_t('JSON error: ' + response.toString());
			},
		});
		return false;
	});

	/**
	 * AI image recognition with Clarifai grpc client
	 **/
	$("#smplairecognition").click(function () {
		let id = $("input#smpl_edit_id").val();
		let url = smpl.ajax + "/ai/" + id;
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "POST",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				smpl.progress(false);
				if (data.id == '-1') {
					smpl.AlertC(data.msg);
				} else {
					var t = $("textarea#smpl_sub").val() + " !!! " + data.msg;
					$("textarea#smpl_sub").val(t);
				}
			},
			error: function (response) {
				smpl.ErrorC(response.responseText);
				smpl.progress(false);
			}
		});
	});
})(jQuery, Drupal, smpl);