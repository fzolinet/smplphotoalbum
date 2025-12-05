/**
 * Description of an item
 */
(function ($, Drupal, smpl) {
	/*
	 * Description & Exif info window show / hide
	*/
	$('a.smpl_desc_a').click(function () {
		let id = $(this).attr('id').substring(5);
		let pos = $(this).position();
		let obj = $("div#DescSub" + id);
		obj.css('left', pos.left + 'px');
		obj.css('top', pos.top + 'px');
		obj.draggable();
		smpl.progress(true);
		let url = smpl.ajax + "/exif/" + id;
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let s = response[0].data;
				$("div#Exif" + id).html(s);
				smpl.progress(false);
				obj.show();
				smpl.desc_info_show = true;
				smpl.exif = id;
			},
			error: function (response) {
				smpl.progress(false);
				smpl.ErrorC("Error in server side. Please come back later");
			}
		});
	});

	/*
	 * Desc info hide
	 */
	$('div.smpl_desc_info').click(function () {
		$(this).hide();
		smpl.desc_info_show = false;
	});

	//If click on some element of body the windows will close
	$('body').click(function (event) {
		$('div.smpl_desc_info').hide();
		if (typeof smpl !== 'undefined') {
			smpl.desc_info_show = false;
		}
	});
})(jQuery, Drupal, smpl);
