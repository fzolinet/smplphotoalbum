/**
 * Delete an image from server
 */
(function ($, Drupal, smpl) {
	/**
	 * Send kill command to server
	 */
	$("button[class*='smpl_delete']").click(function () {
		let id = $(this).attr('id').substring(6);
		let name = $("div#smpl_sub" + id).html().trim();

		swal({
			text: smpl.words.Delete + ": '" + name + "'?",
			title: "Are you sure?",
			className: "smpl-message-warning",
			buttons: true,
			closeOnClickOutside: true,
			closeOnEsc: true,
			dangerMode: true,
		})
			.then((ok) => {
				if (ok) {
					smpl.progress(true);
					let url = smpl.ajax + "/delete/" + id;
					$.ajax({
						url: url,
						type: "get",
						success: function (response) {
							let suc = response[0].data
							smpl.progress(false);
							if (suc > 0) {
								$("div#smpl" + id).remove();
							} else {
								smpl.ErrorC(smpl.words.Del_not + ": " + name);
							}
						}
					});
				}
			});
	});
})(jQuery, Drupal, smpl);