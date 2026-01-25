(function ($, Drupal, smpl, Swal) {
	//"use strict";

	var formw = 0;
	var formh = 0;

	var que = 0;       // size of queue
	var idx = 0;       // index of queue
	var pos = 0;       // position in the queue
	var lensx = 0;     // Center of Lens
	var lensy = 0;

	//https://projects.calebevans.me/jcanvas/
	smpl.canvas = $("#SmplCanvas");
	if (smpl.canvas.length < 1) {
		return;
	}

	smpl.ctx = smpl.canvas[0].getContext("2d");
	smpl.ctx.imageSmoothingEnabled = false;

	smpl.imgurl = "";  // Image path

	smpl.width = 0;	// width & height of canvas
	smpl.height = 0;
	smpl.ratio = 0;	// ratio of image
	smpl.delta = 5;	// A kép szélétől ennyivel kezdőik beljebb egy layer
	smpl.image = new Image();
	var canvasx = 0;	// size of container of canvas
	var canvasy = 0;

	//Handlers
	smpl.stroke = 'black';
	smpl.strokew = 1;
	smpl.fillStyle = "#fff";
	smpl.hradius = 4;   // radius of little circle ( of point )
	smpl.rgbcolors = "";

	// ---------- SmplImgEditForm --------------------
	var SmplImgEditForm = $("div#SmplImgEditForm");

	//Editform draggable on display

	if (SmplImgEditForm.length > 0) {
		SmplImgEditForm.resizable({
			minWidth: 400,
			minHeight: 400,
		})
			.draggable({
				cancel: "canvas,input,textarea,button,select,option",
				//containment: "window",
				cursor: "crosshair",
			});
	}

	// max size of editform
	formw = SmplImgEditForm.width();
	formh = SmplImgEditForm.height();

	//Autoload
	if (smpl.imgeditform) {
		SmplImgEditForm.show();
		LoadEditForm();
	}
	if (smpl.id < 0) {
		smpl.id = 0;
	}

	//--------- Graphic driver  --------
	if (smpl.graphicdrv == "gd") {
		$(".smpl_gd").show();
		$(".smpl_imagick").hide();
	} else {
		$(".smpl_gd").hide();
		$(".smpl_imagick").show();
	}

	$("select#smpl_gdriver").change(function () {
		smpl.graphicdrv = $("select#smpl_gdriver option:selected").val();
		if (smpl.graphicdrv == "gd") {
			$(".smpl_gd").show();
			$(".smpl_imagick").hide();
			$("#smpl_glogo").attr("src", smpl.modulepath + "/image/gdlogo.png");
		} else {
			$(".smpl_gd").hide();
			$(".smpl_imagick").show();
			$("#smpl_glogo").attr("src", smpl.modulepath + "/image/imagicklogo.png");
		}
	});

	//---------- Canvas Container
	var CanvasContainer = $("div#SmplCanvasContainer");
	canvasx = CanvasContainer.width();
	canvasy = CanvasContainer.height();

	var canvasOff = smpl.canvas.offset();
	smpl.offsetX = canvasOff.left;
	smpl.offsetY = canvasOff.top;
	SaveButtons(true);
	AddImageLayer();

	//-------- Add Image Layer
	function AddImageLayer() {
		smpl.canvas.addLayer({
			type: 'image',
			name: 'Image',
			index: 0,
			cursors: "move",
			x: 0,
			y: 0,
			width: smpl.offsetX,
			height: smpl.offsetY,
			fromCenter: false,
			//draggable: true,
		}).drawLayers();
	};

	//----------- SmplStep ----------------
	function ImgUrl(idx) {
		var lastIndex = (smpl.name).lastIndexOf('.');
		return smpl.tempurl + (smpl.name).substr(0, lastIndex) + "_temp_" + idx + (smpl.name).substr(lastIndex);
	}

	/**
	 * Save buttons enable/disable
	 * @param enable
	 * @returns
	 */
	function SaveButtons(enable) {
		var sb = $("#SmplSubmit");
		var sa = $("#SmplSaveAs");
		var sbkp = $("#SmplInit");
		var divsaveas = $('div#smpl_saveas');
		if (enable) {
			sb.prop("disabled", false);
			sb.removeClass("smpl_img_edit_form_darkenbuttons");
			sa.prop("disabled", false);
			sa.removeClass("smpl_img_edit_form_darkenbuttons");
			sbkp.prop("disabled", false);
			sbkp.removeClass("smpl_img_edit_form_darkenbuttons");
		} else {
			sb.prop('disabled', true);
			sb.addClass("smpl_img_edit_form_darkenbuttons");
			sa.prop('disabled', true);
			sa.addClass("smpl_img_edit_form_darkenbuttons");
			sbkp.prop("disabled", true);
			sbkp.addClass("smpl_img_edit_form_darkenbuttons");
			divsaveas.hide();
		}
		smpl.EyeDropOff();
	}

	/**
	 * Cancel the actual long process
	 */
	function Cancel() {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/cancel";
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				UndoRedo(data.idx, data.que);
				smpl.progress(false);
			},
			error: function (response) {
				smpl.progress(false);
				smpl.ErrorC(smpl.id);
			},
		});
	}

	/**
	 * Original state of image
	 * @param {*} idx
	 * @param {*} que
	 */
	function Init(idx, que,) {

	}

	/**
	 * Undo button disabled
	 * @param idx - index of queue (0....que-1)
	 * @param que - length of queue
	 * @param prev - previous number
	 * @param next - next number
	 */
	function UndoRedo(idx, que, prev, next) {
		let ud = $("#SmplUndo");
		let rd = $("#SmplRedo");
		ud.prop("title", smpl.words.Undo + ": " + prev);
		rd.prop("title", smpl.words.Redo + ": " + next);
		if (idx > 0) {
			ud.prop("disabled", false);
			ud.removeClass("smpl_img_edit_form_darkenbuttons");
		} else {
			ud.prop("disabled", true);
			ud.addClass("smpl_img_edit_form_darkenbuttons");
		}

		if (idx < que) {
			rd.prop("disabled", false);
			rd.removeClass("smpl_img_edit_form_darkenbuttons");
		} else {
			rd.prop("disabled", true);
			rd.addClass("smpl_img_edit_form_darkenbuttons");
		}

		$("#smpl_step").val(idx);
		$('#smpl_que').val(que)
		if (idx > 0) SaveButtons(true);
		else SaveButtons(false);
	}

	/**
	 * Load an image
	 * @param object data
	 * @returns
	 */
	function load(data) {
		$('div#smpl_imgedit_filename').html(data.name);
		$("input#smpl_imgedit_id").val(data.id);

		smpl.name = data.name;
		smpl.tempurl = data.url;
		smpl.idx = data.idx;
		smpl.bkp = data.bkp;
		smpl.signurl = data.signurl;

		smpl.width = data.width;
		smpl.height = data.height;
		smpl.ratio = parseInt(smpl.height) / parseInt(smpl.width);

		if (!smpl.isUndefined(data.avgcolor)) {
			smpl.stroke = smpl.EyeDropInvertColor(data.avgcolor);
		}

		if (smpl.idx == 0) {
			smpl.owidth = smpl.width;
			smpl.oheight = smpl.height;
		}
		smpl.filesize = data.filesize;
		$("#smpl_imgedit_filesize").html(smpl.filesize);

		smpl.modified = data.modified;
		$("#smpl_imgedit_modified").html(smpl.modified);
		$("#smpl_imgedit_size").html(smpl.width + "x" + smpl.height);

		smpl.hideHistogram();
		load_img(ImgUrl(smpl.idx), smpl.width, smpl.height);

		// size of iriginal image
		imageprop();
		$("#smpl_rgbcolor").val(data.avgcolor);

		//clear or autoget the histogram
		if (smpl.autohist == "") {
			smpl.autohist = smpl.getLocalStorage("smpl_autohist");
		}

		if (smpl.autohist) smpl.getHistogram();
		else smpl.hideHistogram();

		// jpeg or png data of copyright && author
		if (data.ext == "jpeg" || data.ext == "jpg" || data.ext == "png") {
			$(".smpl_wmspan").show();
			$("#smpl_watermark_copyright").val(data.copyright);
			$("#smpl_watermark_author").val(data.author);
		} else {
			$(".smpl_wmspan").hide();
			$("#smpl_watermark_copyright").val('');
			$("#smpl_watermark_author").val('');
		}
		smpl.imgeditsaved = false;
		UndoRedo(data.idx, data.que, data.prev, data.next);
		SmplImgEditForm.show();
		smpl.progress(false);
	};

	/**
	 * size of image
	 */
	function imageprop() {
		$("input#smpl_width").val(smpl.width);
		$("input#smpl_height").val(smpl.height);

		$("input#smpl_dwidth").val(smpl.width);
		$("input#smpl_dheight").val(smpl.height);

		$("#smpl_resizew").val(smpl.width);
		$("#smpl_resizeh").val(smpl.height);

		$("#smpl_blursize").prop("max", Math.min(smpl.width, smpl.height) / 2);
	}
	/**
	 * Load Image to image tag && Canvas
	 * @param src - source link of image
	 * @param dx  - width of image
	 * @param dy  - height of image
	 * @returns
	 */
	function load_img(src, dx, dy) {
		smpl.image.src = src;
		smpl.canvas.attr("width", dx);
		smpl.canvas.attr("height", dy);
		smpl.canvas.removeLayers();
		smpl.canvas.addLayer({
			type: 'image',
			name: 'Image',
			source: src + "?t=" + (new Date().getTime()),
			index: 0,
			fromCenter: false,
			x: 0,
			y: 0,
			width: dx,
			height: dy,
			click: function (layer) {
				smpl.layer.x = layer._eventX;
				smpl.layer.y = layer._eventY;
				$("#smpl_xp").val(smpl.layer.x);
				$("#smpl_yp").val(smpl.layer.y);
				smpl.EyeDropColor(smpl.layer.x, smpl.layer.y);
			},
		}).drawLayers();

		if (smpl.zoomenable) {
			smpl.ZoomDefault();
		} else {
			smpl.canvas.css("cursor", "default");
		}

		DefaultLayers(dx, dy);	// Set default parameters of layers

		var canvasOff = smpl.canvas.offset();
		smpl.offsetX = canvasOff.left;
		smpl.offsetY = canvasOff.top;
		Onlyjpeg(src);
	}

	/**
	 * Only jpeg can write metadatas into file
	 * @param src
	 * @return
	 */
	function Onlyjpeg(src) {
		src = src.toLowerCase();
		$("#smpl_watermark_author, #smpl_watermark_copyright").prop("disabled", (src.search(".jpg") > 0 ? false : true));
	}

	/**
	 * Set Layers default parameters
	 */
	function DefaultLayers(dx, dy) {
		var onepercent = 0.01;
		smpl.circ.setCirc(dx / 2, dy / 2, Math.round((Math.min(dx, dy) - 10) / 2));
		smpl.circ.radius = Math.round(dx * onepercent);

		smpl.rect.setRect(dx / 2, dy / 2, dx - 20, dy - 20);
		smpl.rect.radius = Math.round(dx * onepercent * (1 / smpl.zoom));

		smpl.dist.setDist(10, 10, dx - 10, 10, dx - 10, dy - 10, 10, dy - 10);
		smpl.dist.radius = Math.round(dx * onepercent);

		smpl.point.radius = Math.round(dx * onepercent);
		smpl.point.setPoint(dx / 2, dy / 2);
	}

	/**
	 * Call ajax when change something
	 * @param cmd - ajax command
	 * @param del - switch off the values, checkboxes, etc.
	 */
	smpl.edit = function (cmd) {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/edit?" + cmd + "&graphicdrv=" + $("select#smpl_gdriver option:selected").val();  //
		smpl.progress(true, true);
		let d = new Date();
		let ti0 = d.getTime();
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				try {
					let data = JSON.parse(response[0].data);
					smpl.progress(false);

					if (data.ok == "-1") {
						smpl.AlertC("Another process works on this file!", "warning");
						smpl.progress(false);
						return false;
					} else if (data.ok == "-2") {
						smpl.AlertC(data.msg, "warning");
						smpl.progress(false);
						return false;
					} else if (data.ok == "-3") {
						smpl.AlertC(data.msg, "warning");
						smpl.progress(false);
						return false;
					}

					if (!smpl.isUndefined(data.width) && !smpl.isUndefined(data.height)) {
						smpl.width = parseInt(data.width);
						smpl.height = parseInt(data.height);
					}

					smpl.imgeditsaved = false;

					smpl.idx = data.idx;
					smpl.tempname = data.tempname;
					load_img(ImgUrl(smpl.idx), smpl.width, smpl.height);

					let d1 = new Date();
					let ti1 = d1.getTime();
					$("#smpl_time").val(Math.floor((ti1 - ti0) / 1000));

					let step = parseInt($("#smpl_step").val()) + 1;
					$("#smpl_step").val(step);
					let que = parseInt($("#smpl_que").val());
					if (step > que) {
						$(".smpl_que").val(que + 1);
					}

					UndoRedo(smpl.idx, data.que, data.prev, data.next);
					SaveButtons(true);
					smpl.progress(false);
				} catch (e) {
					smpl.ErrorC(e.message);
				}

				smpl.getHistogram();
				smpl.progress(false);
				return false;
			},
			error: function (response) {
				smpl.progress(false);
				smpl.ErrorC(response);
				return false;
			},
		});
	}

	/**
	 * Open the ImgEditForm and load an image
	 * Load the Smpl Image Edit form
	 */
	$("button[class*='smpl_imgedit']").click(function (e) {
		smpl.id = $(this).attr('id').substring(6);    //which button => id
		smpl.imgeditsaved = true;
		LoadEditForm();
	});

	function LoadEditForm() {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/load/";
		SmplImgEditForm.hide();
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				if (data.ok == "-2") {
					smpl.progress(false);
					smpl.ErrorC(data.msg);
					return false;
				}
				//Load image
				load(data);

				let pos = $('#ImgBtn' + smpl.id).offset();
				let dy = parseFloat($("html").css("font-size")) * 5;
				SmplImgEditForm.show();
				let w = SmplImgEditForm.width();
				let ww = window.innerWidth;
				pos.left = (ww - w) * 0.5;
				if (pos.left < 0) {
					pos.left = 0
				} else if (pos.left + w > ww) {
					pos.left = ww - w - 2 * dy;
				}
				SmplImgEditForm.offset({ top: pos.top + dy, left: pos.left });
				return false;
			},
			error: function (response) {
				smpl.progress(false);
				smpl.ErrorC(response.responseText);
				return false;
			},
		});
	}

	/**
	 * If End and Save the editing of picture
	 */
	$("#SmplSubmit").click(function () {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/save?idx=" + smpl.idx;
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				$("img#smpltn" + smpl.id).attr("src", data.link);
				smpl.imgeditsaved = true;
				smpl.progress(false);
				SmplImgEditForm.hide();
				return false;
			},
			error: function (response) {
				let data = JSON.parse(response[0].data);
				smpl.progress(false);
				smpl.ErrorC(smpl.id);
				return false;
			},
		});
		return false;
	});

	//----------------  Save As -----------------
	$("#SmplSaveAs").click(function () {
		$("input#smpl_saveas_input").val(smpl.name);
		$("div#smpl_saveas").show();
	});

	$("#SmplSaveAsCancel").click(function () {
		$("input#smpl_saveas_input").val("");
		$("div#smpl_saveas").hide();
	});

	/**
	 * If End and Save the editing of picture
	 */
	$("#SmplSaveAsOK").click(function () {
		var newname = $("input#smpl_saveas_input").val();
		if (smpl.name == newname) {
			smpl.ErrorC('The new name is the same as the original');
			return false;
		}
		let url = smpl.ajax + "/imgedit/saveas/" + smpl.tempname + "/" + newname;
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				smpl.progress(false);
				$("img#smpltn" + smpl.id).attr("src", data.link)
				SmplImgEditForm.hide();
				return false;
			},
			error: function (response) {
				let data = JSON.parse(response[0].data);
				smpl.progress(false);
				smpl.ErrorC(smpl.id);
				return false;
			},
		});
		return false;
	});

	// ---- Cancel Button -----------
	$("#SmplCancel").click(function () {
		Cancel();
	});

	$(document).keydown(function (e) {
		if (e.key == "Escape" && SmplImgEditForm.is(":visible")) {
			Cancel();
		}
	})

	/**
	 * Cancel the modified image and close the window
	 */
	$("#SmplClose").click(function () {
		if (!smpl.imgeditsaved) {
			Swal.fire({
				html: smpl.words.Edit_not_saved,
				title: "The changed image not saved. Do you want to close?",
				className: "smpl-message-warning",
				closeOnClickOutside: true,
				closeOnEsc: true,
				dangerMode: true,
				showCloseButton: true,
				showCancelButton: true,
				cancelButtonText: smpl.words.Cancel,
				confirmButtonText: smpl.words.Confirm,
				icon: "warning",
			})
				.then((ok) => {
					if (ok.isConfirmed) {
						CloseAjax();
						SaveButtons($, false);
						SmplImgEditForm.hide();
					}
					return false;
				});
		}
		return false;
	});

	/**
	 * ImgeEdit close
	 */
	function CloseAjax() {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/close";
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				UndoRedo(0, 0, false);
				smpl.progress(false);
				smpl.imgeditsaved = true
			},
			error: function (response) {
				smpl.progress(false);
				smpl.ErrorC(smpl.id);
			},
		});
	}

	/**
	 * Show Original image
	 **/
	$("button#SmplInit").on("mousedown", function () {
		if (smpl.idx > 0) {
			smpl.bkp = smpl.idx;
			smpl.bwidth = smpl.width;
			smpl.bheight = smpl.height;
			load_img(ImgUrl(0), smpl.owidth, smpl.oheight);
			imageprop();
		};
	});

	/**
	 * Last changed image
	 */
	$("#SmplInit").on("mouseup", function () {
		if (smpl.bkp > 0) {
			smpl.idx = smpl.bkp;
			smpl.width = smpl.bwidth;
			smpl.height = smpl.bheight;
			load_img(ImgUrl(smpl.idx), smpl.width, smpl.height);
			imageprop();
		}
	});

	/**
	 * Undo the modified image
	 * back values: image src, width, height, undo number, max undo stack
	 */
	$("#SmplUndo").click(function () {
		Undo();
	});

	function Undo() {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/undo";
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				let data = JSON.parse(response[0].data);
				load(data);
				return false;
			},
			error: function (response) {
				smpl.progress(false);
				smpl.ErrorC(smpl.id);
				return false;
			},
		});
	}

	/**
	 *  Redo the images
	 */
	$("#SmplRedo").click(function () {
		Redo();
	});

	function Redo() {
		let url = smpl.ajax + "/imgedit/" + smpl.id + "/redo";
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function (response) {
				var data = JSON.parse(response[0].data);
				load(data);
				return false;
			},
			error: function (response) {
				var data = JSON.parse(response[0].data);
				smpl.progress(false);
				smpl.ErrorC(smpl.id);
				return false;
			},
		});
	}
	// --------- lib methods --------------------
	/**
	 * Change the main menu
	 * @param string t - wich button
	 * @returns
	 */
	smpl.button_actual = function (t) {
		smpl.ChangeLayer('');
		$("div.smpl_imgedit_border").hide();
		$("button.smpl_label").removeClass('smpl_label_actual');
		$("div#" + t).show();
		$("button#" + t).addClass('smpl_label_actual');
	}

	/**
	 * Reset The selection
	 * @returns
	 */
	smpl.resetSelection = function () {
		$("#smpl_x1").val('');
		$("#smpl_y1").val('');
		$("#smpl_x2").val('');
		$("#smpl_y2").val('');
		$("#smpl_wh").val("0 x 0px");
		$("#smpl_whp").val("0 x 0%");
	}
})(jQuery, Drupal, smpl, Swal);