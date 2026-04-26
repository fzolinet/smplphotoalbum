(function ($, Drupal, smpl) {
	//Menus
	Menu();
	EnhanceMenu();
	AddMenu();
	GeomMenu();
	LightingMenu();
	EffectMenu();
	ColorMenu();
	DetailMenu();

	/**
	 * Change the submenu
	 *
	 * @param t - the menu
	 * @param handletype - Which handler
	 * @return bool
	 */
	function label_actual(t, type) {
		if (smpl.isUndefined(type)) {
			type = '';
		}
		smpl.ChangeLayer(type);
		$("div.smpl_imgedit_border").hide();
		smpl.layer_area(type);
		$("button.smpl_label").removeClass('smpl_label_actual');
		$("div#smpl_" + t).show();
		$("#" + t).addClass('smpl_label_actual');
		return false;
	}

	/************* Main Menu **************/
	function Menu() {
		$("nav#smpl_imgedit_menu > button.smpl_label").click(function () {
			var menuid = $(this).attr('id');
			$("div.smpl_menu").hide();
			$("div.smpl_imgedit_border").hide();
			$("div#" + menuid + "_menu").show();
			smpl.button_actual(menuid);
		});
	}

	// ------------ Enhance Menu --------
	function EnhanceMenu() {
		FaceEdit();
		RedEye();
		Repair();
		Skin();
	}

	//------------- Face edit -----------
	function FaceEdit() {
		$("#smpl_faceedit").click(function () {
			label_actual("faceedit");
			return false;
		});

		$("#smpl_faceedit_done").click(function () {
			let cmd = "cmd=faceedit";
			smpl.edit(cmd);
			label_actual("faceedit");
		});
	}

	//--------- Red eye --------------
	function RedEye() {
		$("#smpl_redeye").click(function () {
			label_actual("redeye", 'Circle');
			return false;
		});

		//signed place
		$("#smpl_redeye_done").click(function () {
			let cmd = "cmd=redeye";
			let x1 = parseInt($("#smpl_x1").val());
			let y1 = parseInt($("#smpl_y1").val());
			let x2 = parseInt($("#smpl_x2").val());
			let y2 = parseInt($("#smpl_y2").val());
			if (!(x1 == 0 && y1 == 0 && x2 == 0 && y2 == 0)) {
				let cmd = "cmd=crop";
				cmd += "&x1=" + x1 + "&y1=" + y1 + "&x2=" + x2 + "&y2=" + y2;
				smpl.edit(cmd);
			}
			label_actual("redeye");
		});
	}

	// ---------- Repair ----------
	function Repair() {
		$("#smpl_repair").click(function () {
			return label_actual("repair");
		});

		$("#smpl_repair_done").click(function () {
			let cmd = "cmd=repair";
			smpl.edit(cmd);
			label_actual("repair");
		});
	}

	// --------- Skin tune ---------
	function Skin() {
		$("#smpl_skintune").click(function () {
			return label_actual("skintune");
		});

		$("#smpl_skintune_done").click(function () {
			let cmd = "cmd=skintune";
			smpl.edit(cmd);
			label_actual("skintune");
		});
	}

	/*************** Add Menu ***********/
	function AddMenu() {
		Watermark();
		Vignette();
		Border();
		Bevel();
	}

	smpl.EyeDropOn();

	//----------- Watermark -----------------
	function Watermark() {
		$("#smpl_watermark").click(function () {
			smpl.ChangeLayer("Rectangle");
			smpl.layer_area("Rectangle", true);
			return label_actual("watermark", "Rectangle");
		});

		$("#smpl_watermark_done").click(function () {
			let x1 = $("#smpl_x1").val();
			let y1 = $("#smpl_y1").val();
			let x2 = $("#smpl_x2").val();
			let y2 = $("#smpl_y2").val();
			if (isNaN(x1) || isNaN(y1) || isNaN(x2) || isNaN(y2) || (x1 == x2 || y1 == y2)) {
				smpl.AlertC(Drupal.t("Select an area!"));
				return;
			}

			let cmd = "cmd=watermark";
			cmd += "&x1=" + x1 + "&y1=" + y1;
			cmd += "&x2=" + x2 + "&y2=" + y2;
			//cmd += "&wmpath="  + $("#smpl_watermark_path").val();
			cmd += "&wmalpha=" + $("#smpl_watermark_alpha").val();
			cmd += "&copyright=" + $("#smpl_watermark_copyright").val();
			cmd += "&author=" + $("#smpl_watermark_author").val();
			cmd += "&color=" + (($("#smpl_rgbcolor").val()).replace("#", ""));
			smpl.edit(cmd);
			label_actual("watermark", "Rectangle");
		});
	}

	//------------  Vignette -----------
	function Vignette() {
		$("#smpl_vignette").click(function () {
			let sel = $("select#smpl_geom option:selected").val();
			switch (sel) {
				case "rect":
					label_actual("vignette", "Rectangle");
					break;
				case "ellipse":
					label_actual("vignette", "Ellipse");
					break;
				case "circle":
					label_actual("vignette", "Circle");
					break;
			}
			span_vignette_blur();
			return false;
		});

		$("select#smpl_kind").change(function () {
			span_vignette_blur();
		});

		$("select#smpl_geom").change(function () {
			let geom = $("select#smpl_geom option:selected").val();
			switch (geom) {
				case "rect":
					label_actual("vignette", "Rectangle");
					break;
				case "ellipse":
					label_actual("vignette", "Ellipse");
					break;
				case "circle":
					label_actual("vignette", "Circle");
					break;
			}
			span_vignette_blur();
		});

		$("#smpl_vignette_done").click(function () {
			let x1 = $("#smpl_x1").val();
			let y1 = $("#smpl_y1").val();
			let x2 = $("#smpl_x2").val();
			let y2 = $("#smpl_y2").val();
			if (isNaN(x1) || isNaN(y1) && isNaN(x2) || isNaN(y2) || (x1 == x2 || y1 == y2)) {
				smpl.AlertC(Drupal.t("Select an area!"));
				return;
			}

			let kind = $("select#smpl_kind option:selected").val(); // Darken / Blur
			let mode = $("select#smpl_mode option:selected").val(); // linear, exponent, log,
			let geom = $("select#smpl_geom option:selected").val(); // Rect, Circle, Ellipse
			let blursize = $("input#smpl_blursize").val();
			let deep = $("input#smpl_deep").val();
			let radius = $("#smpl_blur_radiusv").val();
			let sigma = $("#smpl_blur_sigmav").val();
			let cmd = "cmd=vignette";
			cmd += "&kind=" + kind;
			cmd += "&blursize=" + blursize;
			cmd += "&geom=" + geom;
			cmd += "&x1=" + x1 + "&y1=" + y1;
			cmd += "&x2=" + x2 + "&y2=" + y2;
			if (kind == "blur") {
				cmd += "&radius=" + radius;
				cmd += "&sigma=" + sigma;
			} else {
				cmd += "&deep=" + deep;
			}
			smpl.edit(cmd);
			label_actual("vignette");
		});
	}

	function span_vignette_blur() {
		if ($("select#smpl_kind option:selected").val() == "blur") {
			$("#smpl_vignette_blur").show();
			$(".smpl_deep").hide();
		} else {
			$("#smpl_vignette_blur").hide();
			$(".smpl_deep").show();
		};
	}

	//------------ Border -------------
	function Border() {
		$("#smpl_border").click(function () {
			return label_actual("border");
		});

		$("#smpl_border_done").click(function () {
			let cmd = "cmd=border";
			cmd += "&bordercolor=" + ($("#smpl_rgbcolor").val()).replace("#", "");
			cmd += "&top=" + $("#smpl_border_top").val();
			cmd += "&innerbevel=" + $("#smpl_border_innerbevel").val();
			cmd += "&outerbevel=" + $("#smpl_border_outerbevel").val();
			cmd += "&height=" + $("#smpl_border_height").val();
			smpl.edit(cmd);
			label_actual("border");
		});
	}

	//---------- Bevel ------------------
	function Bevel() {
		$("#smpl_bevel").click(function () {
			return label_actual("bevel");
		});

		$("#smpl_bevel_done").click(function () {
			let cmd = "cmd=bevel";
			cmd += "&width=" + $("#smpl_bevel_width").val();
			cmd += "&depht=" + $("#smpl_bevel_depht").val();
			cmd += "&direction=" + $("#smpl_bevel_direction").val();
			smpl.edit(cmd);
			label_actual("bevel");
		});
	}

	/*********** Geom menü **************/
	function GeomMenu() {
		Rotate();
		Flip();
		Crop();
		Resize();
		Shave();
		Perspective();
		Distortion();
		Lens();
		Liquify();
	}

	//--------- Rotate -------------
	function Rotate() {
		$("#smpl_rotate").click(function () {
			return label_actual("rotate");
		});

		$("#smpl_autoorient_done").click(function () {
			let cmd = "cmd=autoorient";
			smpl.edit(cmd);
			label_actual("rotate");
		});

		// Rotate -90,90,180 degree and the value
		$("#smpl_rotate_deg").change(function () {
			$("#smpl_rotate_val").val(parseInt($(this).val()));
		});

		$("#smpl_rotate_done").click(function () {
			let grad = parseInt($("#smpl_rotate_val").val());
			$("div#smpl_rotate").hide();
			if (grad != 0) {
				smpl.edit("cmd=rotate&rotate=" + grad);
				$("#smpl_rotate").val(0).change();
			}
			label_actual("rotate");
		});
	}

	//--------- FLip ---------------
	function Flip() {
		// flip vertical
		$("#smpl_flip_vertical_done").click(function () {
			$("div#smpl_rotate").hide();
			smpl.edit("cmd=flip_vertical");
			label_actual("rotate", "");
		});

		//flip horizontal
		$("#smpl_flip_horizontal_done").click(function () {
			$("div#smpl_rotate").hide();
			smpl.edit("cmd=flip_horizontal");
			label_actual("rotate", "");
		});
	}

	//--------------- Crop ---------------
	function Crop() {
		$("#smpl_crop").click(function () {
			return label_actual("crop", "Rectangle");
		});

		$("#smpl_crop_done").click(function () {
			let x1 = parseInt($("#smpl_x1").val());
			let y1 = parseInt($("#smpl_y1").val());
			let x2 = parseInt($("#smpl_x2").val());
			let y2 = parseInt($("#smpl_y2").val());
			if (!(x1 == 0 && y1 == 0 && x2 == 0 && y2 == 0)) {
				let cmd = "cmd=crop";
				cmd += "&x1=" + x1 + "&y1=" + y1 + "&x2=" + x2 + "&y2=" + y2;
				smpl.edit(cmd);
			}
			label_actual("crop");
		});
	}

	//-------------- Resize -----------
	function Resize() {
		$("#smpl_resize").click(function () {
			return label_actual("resize");			
		});

		$("#smpl_resize_percent").val(100)
		$("#smpl_resize_percent_val").val(100);

		$("#smpl_resize_done").click(function () {
			let wp = $("#smpl_resizew").val();
			let hp = $("#smpl_resizeh").val();
			if (wp && hp) {
				let cmd = "cmd=resize&wp=" + wp + "&hp=" + hp;
				smpl.edit(cmd);
				$("#smpl_resize_percent").val(100)
				$("#smpl_resize_percent_val").val(100);
			}
			label_actual("resize");
		});

		$("#smpl_resize_percent").change(function () {
			let perc = $(this).val();
			$("#smpl_resize_percent_val").val(perc);
			$('#smpl_resizew').val(smpl.width * perc / 100 | 0);
			$('#smpl_resizeh').val(smpl.height * perc / 100 | 0);
			resize(smpl.width * perc / 100, smpl.height * perc / 100, perc);
		});

		$("#smpl_resize_percent_val").change(function () {
			let perc = parseInt($(this).val());
			$("#smpl_resize_percent").val(perc);
			$('#smpl_resizew').val(smpl.width * perc / 100 | 0);
			$('#smpl_resizeh').val(smpl.height * perc / 100 | 0);
			resize(smpl.width * perc / 100, smpl.height * perc / 100, perc);
		});

		$("#smpl_resizew").change(function () {
			let w = parseInt($(this).val());
			let perc = 100 * w / smpl.width;
			let h = smpl.height * perc / 100;
			$("#smpl_resize_percent_val").val(perc | 0);
			$("#smpl_resize_percent").val(perc | 0);
			$('#smpl_resizeh').val(h | 0);
			resize(w, h, perc);
		});

		$("#smpl_resizeh").change(function () {
			let h = parseInt($(this).val());
			let perc = 100 * h / smpl.height;
			let w = smpl.width * perc / 100;
			$("#smpl_resize_percent").val(perc | 0);
			$("#smpl_resize_percent_val").val(perc | 0);
			$('#smpl_resizew').val(w | 0);
			resize(w, h, perc);
		});
	}

	function resize(w, h, perc) {
		//left top
		$("#smpl_x1").val(0 | 0);
		$("#smpl_y1").val(0 | 0);
		//Right bottom
		$("#smpl_x2").val(w | 0);
		$("#smpl_y2").val(h | 0);
		// width & height in pixel
		$("#smpl_w").val(w | 0);
		$("#smpl_h").val(h | 0);
		// width & height in %
		$("#smpl_wp").val(perc | 0);
		$("#smpl_hp").val(perc | 0);
	}

	//--------------- Shave --------------------
	function Shave() {
		$("#smpl_shave").click(function () {
			label_actual("shave");
			$("#smpl_grid_area").show();
			return false;
		});

		$("#smpl_shave_done").click(function () {
			let cmd = "cmd=shave";
			cmd += "&cols=" + $("#smpl_shave_cols").val();
			cmd += "&rows=" + $("#smpl_shave_rows").val();
			smpl.edit(cmd);
			label_actual("shave");
		});
	}

	//---------------- Perspective ------
	function Perspective() {
		$("#smpl_perspective").click(function () {
			$("#smpl_xd1").val(smpl.dist.x1);
			$("#smpl_yd1").val(smpl.dist.x1);
			$("#smpl_xd2").val(smpl.dist.x2);
			$("#smpl_yd2").val(smpl.dist.y2);
			$("#smpl_xd3").val(smpl.dist.x3);
			$("#smpl_yd3").val(smpl.dist.y3);
			$("#smpl_xd4").val(smpl.dist.x4);
			$("#smpl_yd4").val(smpl.dist.y4);

			label_actual("perspective", "Distortion");
			$("div#smpl_grid_area").show();
			return false;
		});

		$("#smpl_perspective_done").click(function () {
			let cmd = "cmd=perspective";
			for (let i = 1; i < 5; i++) {
				cmd += "&xd" + i + "=" + $("#smpl_xd" + i).val();
				cmd += "&yd" + i + "=" + $("#smpl_yd" + i).val();
			}
			cmd += "&color=" + $("#smpl_rgbcolor").val();
			smpl.edit(cmd);
			label_actual("perspective", "Distortion");
		});

		function getPercpectiveCmd() {
			let cmd = "";
			return cmd;
		}

		$("#smpl_transparent_perspective").change(function (e) {
			if ($(this).is(":checked")) {
				smpl.EyeDropTransparency("perspective", true);
			} else {
				smpl.EyeDropTransparency("perspective", false);
			}
		});
	}

	//---------- Distortion -------------
	function Distortion() {
		$("#smpl_distortion").click(function () {
			label_actual("distortion", "Distortion");
			$("div#smpl_grid_area").show();
			return false;
		});

		$("#smpl_distortion_horizontal_center").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_horizontal_center_n").val(n)
		});

		$("#smpl_distortion_horizontal_center_n").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_horizontal_center").val(n)
		});


		$("#smpl_distortion_vertical_center").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_vertical_center_n").val(n);
		});
		$("#smpl_distortion_vertical_center_n").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_vertical_center").val(n);
		});

		$("#smpl_distortion_strength").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_strength_n").val(n);
		});
		$("#smpl_distortion_strength_n").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_strength").val(n);
		});

		$("#smpl_distortion_scale").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_scale_n").val(n);
		});
		$("#smpl_distortion_scale_n").change(function () {
			var n = $(this).val();
			$("#smpl_distortion_scale").val(n);
		});

		$("#smpl_distortion_done").click(function () {
			let cmd = "cmd=distortion";
			cmd += "&horizontal=" + $("#smpl_distortion_horizontal_center").val();
			cmd += "&vertical=" + $("#smpl_distortion_vertical_center").val();
			cmd += "&strength=" + $("#smpl_distortion_strength").val();
			cmd += "&scale=" + $("#smpl_distortion_scale").val();
			cmd += "&type=" + $("select#smpl_distortion_type option:selected").val();
			cmd += "&color=" + $("#smpl_rgbcolor").val();
			cmd += "&transparency" + ($("#smpl_transparent_lens").is(":checked") ? "true" : "false");
			smpl.edit(cmd);
			label_actual("distortion", "Distortion");
		});

		$("#smpl_transparent_distortion").change(function (e) {
			if ($(this).is(":checked")) {
				smpl.EyeDropTransparency("distortion", true);
			} else {
				smpl.EyeDropTransparency("distortion", false);
			}
		});
	}

	// ---------- Lens -------------------
	function Lens() {
		$("#smpl_lens").click(function () {
			$("#smpl_x").val(parseInt(smpl.width / 2));
			$("#smpl_y").val(parseInt(smpl.height / 2));
			$("#smpl_radius").val(parseInt(Math.min(smpl.height, smpl.width) / 2));
			$("#smpl_lensstrength").val(parseInt(0));
			label_actual("lens", "Circle");
			$("div#smpl_grid_area").show();
			return false;
		});

		$("#smpl_lens_done").click(function () {
			let cmd = "cmd=lens";
			cmd += "&a=" + $("#smpl_lensA").val();
			cmd += "&b=" + $("#smpl_lensB").val();
			cmd += "&c=" + $("#smpl_lensC").val();
			cmd += "&d=" + $("#smpl_lensD").val();
			cmd += "&centerx=" + $("#smpl_x").val();
			cmd += "&centery=" + $("#smpl_y").val();
			cmd += "&radius=" + $("#smpl_radius").val();
			cmd += "&color=" + $("#smpl_rgbcolor").val();
			cmd += "&transparency" + ($("#smpl_transparent_lens").is(":checked") ? "true" : "false");
			cmd += "&bestfit=" + ($("#smpl_lensbestfit").is(":checked") ? "true" : "false");
			smpl.edit(cmd);
			label_actual("lens");
		});

		$("#smpl_transparent_lens").change(function (e) {
			if ($(this).is(":checked")) {
				smpl.EyeDropTransparency("lens", true);
			} else {
				smpl.EyeDropTransparency("lens", true);
			}
		});
	}

	//---------- Liquify ---------------
	function Liquify() {
		$("#smpl_liquify").click(function () {
			return label_actual("liquify");
		});

		$("#smpl_liquify_done").click(function () {
			let cmd = "cmd=liquify";
			smpl.edit(cmd);
		});

	}

	/***************vLighting Menu ************/
	function LightingMenu() {
		Exposure();
		Levels();
		Autolevels();
		LightEQ();
		Dehaze();
		DodgeBurn();
	}

	// ---------------- Exposure ----------------
	function Exposure() {
		$("#smpl_exposure").click(function () {
			return label_actual("exposure");
		});

		$("#smpl_exposure_done").click(function () {
			let cmd = "cmd=exposure";
			smpl.edit(cmd);
			label_actual("exposure");
		});
	}

	//------------- Levels ------------------------
	function Levels() {
		$("#smpl_levels").click(function () {
			return label_actual("levels");
		});

		$("#smpl_levels_done").click(function () {
			let cmd = "cmd=levels";
			smpl.edit(cmd);
			label_actual("levels");
		});
	}

	//------------- Auto levels ---------------------
	function Autolevels() {
		$("#smpl_autolevels").click(function () {
			return label_actual("autolevels");
		});

		$("#smpl_autolevels_done").click(function () {
			let cmd = "cmd=autolevels";
			smpl.edit(cmd);
			label_actual("autolevels");
		});
	}

	//------------ Light EQ -----------------------
	function LightEQ() {
		$("#smpl_lighteq").click(function () {
			return label_actual("lighteq");
		});

		$("#smpl_lighteq_done").click(function () {
			let cmd = "cmd=lighteq";
			smpl.edit(cmd);
			label_actual("lighteq");
		});
	}

	//------------ Dehaze -------------------------
	function Dehaze() {
		$("#smpl_dehaze").click(function () {
			return label_actual("dehaze");
		});

		$("#smpl_dehaze_done").click(function () {
			let cmd = "cmd=dehaze";
			smpl.edit(cmd);
			label_actual("dehaze");
		});
	}

	//------------- Dodge / Burn ------------------
	function DodgeBurn() {
		$("#smpl_dodgeburn").click(function () {
			return label_actual("dodgeburn", "");
		});

		$("#smpl_dodge_done").click(function () {
			let cmd = "cmd=dodge";

			smpl.edit(cmd);
			label_actual("dodgeburn");
		});

		$("#smpl_burn_done").click(function () {
			let cmd = "cmd=burn";
			smpl.edit(cmd);
			label_actual("dodgeburn");
		});
	}

	/*************** Effect Menu *****************/
	function EffectMenu() {
		GrayScale();
		BW();
		White();
		Charchoal();
		Oil();
		Sepia();
		BlueShift();
		Solarize();
		Clahe();
	}

	//---------------- GrayScale ------------- */
	function GrayScale() {
		$("#smpl_grayscale").click(function () {
			return label_actual("grayscale");
		});

		$("#smpl_grayscale_done").click(function () {
			let cmd = "cmd=grayscale";
			smpl.edit(cmd);
			label_actual("grayscale");
		});
	}

	//-------------- B/W ---------------- */
	function BW() {
		$("#smpl_bw").click(function () {
			return label_actual("bw");
		});

		$("#smpl_bw_done").click(function () {
			let cmd = "cmd=bw";
			cmd += "&level=" + $("#smpl_bwlevel").val();
			smpl.edit(cmd);
			label_actual("bw");
		});
	}

	//-------------- White -------------- */
	function White() {
		$("#smpl_white").click(function () {
			return label_actual("white");
		});

		$("#smpl_white_done").click(function () {
			let cmd = "cmd=white";
			cmd += "&red=" + parseInt(smpl.rgbcolor.substring(1, 3), 16);
			cmd += "&green=" + parseInt(smpl.rgbcolor.substring(3, 5), 16);
			cmd += "&blue=" + parseInt(smpl.rgbcolor.substring(5, 7), 16);
			cmd += "&alpha=" + parseFloat($("#smpl_alpha").val());
			smpl.edit(cmd);
			label_actual("white");
		});
	}

	//--------------- Charchoal ------------- */
	function Charchoal() {
		$("#smpl_charchoal").click(function () {
			return label_actual("charchoal");
		});

		$("#smpl_charchoal_done").click(function () {
			let cmd = "cmd=charchoal";
			cmd += "&radius=" + $("#smpl_charchoal_radius").val();
			cmd += "&sigma=" + $("#smpl_charchoal_sigma").val();
			smpl.edit(cmd);
			label_actual("charchoal");
		});
	}

	//--------------- Oil ------------- */
	function Oil() {
		$("#smpl_oil").click(function () {
			return label_actual("oil");
		});

		$("#smpl_oil_done").click(function () {
			let cmd = "cmd=oil";
			cmd += "&radius=" + $("#smpl_oilradius").val();
			cmd += "&intlevel=" + $("#smpl_oilintlevel").val();
			smpl.edit(cmd);
			label_actual("oil");
		});
	}

	// --------------- Sepia -------------
	function Sepia() {
		$("#smpl_sepia").click(function () {
			return label_actual("sepia");
		});

		$("#smpl_sepia_done").click(function () {
			let cmd = "cmd=sepia";
			cmd += "&level=" + $("#smpl_sepialevel").val();
			smpl.edit(cmd);
			label_actual("sepia");
		});
	}

	// --------------- BlueShift -------------
	function BlueShift() {
		$("#smpl_blueshift").click(function () {
			return label_actual("blueshift");
		});

		$("#smpl_blueshift_done").click(function () {
			let cmd = "cmd=blueshift";
			cmd += "&level=" + $("#smpl_blueshiftlevel").val();
			smpl.edit(cmd);
			label_actual("blueshift");
		});
	}
	//--------------- Solarize -----------------
	function Solarize() {
		$("#smpl_solarize").click(function () {
			return label_actual("solarize");
		});

		$("#smpl_solarize_done").click(function () {
			let cmd = "cmd=solarize";
			cmd += "&treshold=" + $("#smpl_solarizetreshold").val();
			smpl.edit(cmd);
			label_actual("solarize");
		});
	}
	//--------------- CLAHE -------------------- 
	function Clahe() {
		if (smpl.imagick > "7.0.7") {
			$("#smpl_clahe").click(function () {
				return label_actual("clahe", "");
			});

			$("#smpl_clahe_done").click(function () {
				let cmd = "cmd=clahe";
				cmd += "&width=" + $("#smpl_clahewidth").val();
				cmd += "&height=" + $("#smpl_claheheight").val();
				cmd += "&bins=" + $("#smpl_clahebins").val();
				cmd += "&clip=" + $("#smpl_claheclip").val();
				smpl.edit(cmd);
				label_actual("clahe");
			});
		} else {
			$("#smpl_clahe").hide();
		}
	}

	/**********************************
	 * -------- Color Menu ------------
	 **********************************/
	function ColorMenu() {
		Histogram();
		Equalize();
		Whitebalance();
		Normalize();
		Gamma();
		Brightness();
		RGB();
		Contrast();
	}

	//-------- Histogram --------------------------
	function Histogram() {
		$("#smpl_histogram").click(function () {
			return label_actual("histogram");
		});

		smpl.autohistogram = function () {
			if ($("#smpl_autohistogram").is(":checked"))
				smpl.autohist = true;
			else
				smpl.autohist = false;
			smpl.setLocalStorage("smpl_autohist", smpl.autohist);
		}

		// default autohistogram 
		smpl.autohistogram();

		$("#smpl_autohistogram").change(function (e) {
			smpl.autohistogram();
		});

		// Histogram
		$("#smpl_histogram_done").click(function () {
			smpl.getHistogram();
		});

		smpl.hideHistogram = function () {
			$("#smpl_histogram_area").hide();
			$("img#smpl_hist_r").attr("src", "");
			$("img#smpl_hist_g").attr("src", "");
			$("img#smpl_hist_b").attr("src", "");
		};

		smpl.showHistogram = function () {
			$("#smpl_histogram_area").show();
		}

		smpl.getHistogram = function () {
			let url = smpl.ajax + "/imgedit/" + smpl.id + "/edit?cmd=histogram"
			url += ($("#smpl_equalize").is(":checked") ? "&equalize=true" : "");
			url += "&graphicdrv=" + $("select#smpl_gdriver option:selected").val();

			smpl.progress(true, true);
			$.ajax({
				url: url,
				type: "GET",
				success: function (response) {
					let data = JSON.parse(response[0].data);
					$("img#smpl_hist_r").attr("src", "data:image/jpeg;base64," + data.red);
					$("img#smpl_hist_g").attr("src", "data:image/jpeg;base64," + data.green);
					$("img#smpl_hist_b").attr("src", "data:image/jpeg;base64," + data.blue);
					$("#smpl_histogram_area").show();
					smpl.progress(false);
					smpl.showHistogram();
				},
				error: function (response) {
					fz_t("Error on server side: histogram: " + smpl.id);
					smpl.progress(false);
					return false;
				},
			});
		}
	}
	//----------Equalize ---------------------------
	function Equalize() {
		$("#smpl_equalize").click(function () {
			return label_actual("equalize");
		});

		$("#smpl_equalize_done").click(function () {
			let cmd = "cmd=equalize";
			smpl.edit(cmd);
			label_actual("color");
		});
	}
	//-------- Whitebalance -----------------------
	function Whitebalance() {
		$("#smpl_whitebalance").click(function () {
			return label_actual("whitebalance");
		});

		$("#smpl_whitebalance_done").click(function () {
			let cmd = "cmd=whitebalance";
			cmd += "&mode=" + $("#smpl_whitebalance_mode option:selected").val();
			cmd += "&exclude=" + $("#smpl_whitebalance_exclude").val();
			smpl.edit(cmd);
			label_actual("color");
		});

	}

	//-------- Normalize -----------------------
	function Normalize() {
		$("#smpl_normalize").click(function () {
			return label_actual("normalize");
		});

		$("#smpl_normalize_done").click(function () {
			let cmd = "cmd=normalize";
			cmd += "&channel=" + $("select#smpl_normalize_channel option:selected").val();
			smpl.edit(cmd);
			label_actual("color");
		});

	}

	//-------- Gamma -----------------------
	function Gamma() {
		$("#smpl_gamma").click(function () {
			return label_actual("gamma");
		});

		$("#smpl_gamma_done").click(function () {
			let cmd = "cmd=gamma";
			cmd += "&gammain=" + $("#smpl_gammain").val();
			cmd += "&gammaout=" + $("#smpl_gammaout").val();
			cmd += "&autogamma=" + ($("#smpl_autogamma").is(":checked") ? "true" : "false");
			smpl.edit(cmd);
			label_actual("color");
		});

		$("#smpl_gammain").change(function () {
			$("div#smpl_gammain_val").html($(this).val());
		});

		$("#smpl_gammaout").change(function () {
			$("div#smpl_gammaout_val").html($(this).val());
		});

		$("#smpl_autogamma").change(function () {
			if ($(this).is(":checked")) {
				$("#smpl_gammain, #smpl_gammaout").prop("disabled", true);
			} else {
				$("#smpl_gammain_val, #smpl_gammaout").prop("disabled", false);
			}
		})
	}

	//------------------ Brightness ---------------
	function Brightness() {
		$("#smpl_brightness").click(function () {
			return label_actual("brightness");
		});

		$("#smpl_brightness_done, #smpl_saturation_done, #smpl_hue_done").click(function () {
			let brightness = $("input#smpl_brightness").val();
			let saturation = $("input#smpl_saturation").val();
			let hue = $("input#smpl_hue").val();
			let cmd = "cmd=brightness&brightness=" + brightness;
			cmd += "&saturation=" + saturation;
			cmd += "&hue=" + hue;
			smpl.edit(cmd);
			label_actual("color");
		});
	}

	//------------------- RGB ----------------------*/
	function RGB() {
		$("#smpl_rgb").click(function () {
			return label_actual("rgb");
		});

		$("#smpl_rgb_done").click(function () {
			let cmd = "cmd=rgb";
			cmd += "&red=" + parseInt(smpl.rgbcolor.substring(1, 3), 16);
			cmd += "&green=" + parseInt(smpl.rgbcolor.substring(3, 5), 16);
			cmd += "&blue=" + parseInt(smpl.rgbcolor.substring(5, 7), 16);
			cmd += "&alpha=" + parseFloat($("#smpl_alpha").val());
			smpl.edit(cmd);
			label_actual("color");
		});
	}

	//------------------- Contrast -------------- */
	function Contrast() {
		$("#smpl_contrast").click(function () {
			return label_actual("contrast");
		});

		$("#smpl_contrast_done").click(function () {
			let contrast = $("input#smpl_contrast").val();
			if (contrast != 0) {
				let cmd = "cmd=contrast&contrast=" + contrast;
				smpl.edit(cmd);
			}
			label_actual("color");
		});
	}

	/*********** Special Effects menu **********/
	function DetailMenu() {
		Addnoise();
		Denoise();
		Sharpen();
		Emboss();
		Trim();
		Edge();
		Blur();
		Convolution();
		Wave();
		Swirl();
	}

	//------------------ Noise -----------------
	function Addnoise() {
		$("#smpl_noise").click(function () {
			return label_actual("noise");
		});

		//Add noise
		$("#smpl_addnoise_done").click(function () {
			let cmd = "cmd=noise";
			cmd += "&level=" + $("#smpl_noiselevel").val();
			cmd += "&type=" + $("select#smpl_noise_type option:selected").val();
			cmd += "&channel=" + $("select#smpl_addnoise_channel option:selected").val();
			smpl.edit(cmd);
			label_actual("noise");
		});
	}

	//---------- Denoise -------------------------
	function Denoise() {
		$("#smpl_denoise_done").click(function () {
			let cmd = "cmd=denoise";
			cmd += "&type=" + $("select#smpl_denoise_type option:selected").val();
			cmd += "&level=" + $("#smpl_denoiselevel").val();
			cmd += "&softness=" + $("#smpl_softness").val();
			smpl.edit(cmd);
			$("#smpl_denoisew").val(3);
			label_actual("noise");
		});
	}

	//--------------- Sharpen -------------------
	function Sharpen() {
		$("#smpl_sharp").click(function () {
			return label_actual("sharp");
		});

		$("#smpl_sharp_done").click(function () {
			let cmd = "cmd=sharp";
			cmd += "&radius=" + $("#smpl_sharp_radius").val();
			cmd += "&sigma=" + $("#smpl_sharp_sigma").val();
			cmd += "&level=" + $("#smpl_sharplevel").val();
			smpl.edit(cmd);
			label_actual("sharpen");
		});
	}

	//--------------- Emboss --------------------
	function Emboss() {
		$("#smpl_emboss").click(function () {
			return label_actual("emboss");
		});

		$("#smpl_emboss_done").click(function () {
			let cmd = "cmd=emboss";
			cmd += "&radius=" + $("#smpl_emboss_radius").val();
			cmd += "&sigma=" + $("#smpl_emboss_sigma").val();
			cmd += "&level=" + $("#smpl_embosslevel").val();
			smpl.edit(cmd);
			label_actual("emboss");
		});
	}

	// --------------- Trim ---------------------
	function Trim() {
		$("#smpl_trim").click(function () {
			return label_actual("trim");
		});

		$("#smpl_trim_done").click(function () {
			let cmd = "cmd=trim";
			cmd += "&fuzz=" + parseFloat($("#smpl_trimfuzz").val());
			cmd += "&red=" + parseInt(smpl.rgbcolor.substring(1, 3), 16);
			cmd += "&green=" + parseInt(smpl.rgbcolor.substring(3, 5), 16);
			cmd += "&blue=" + parseInt(smpl.rgbcolor.substring(5, 7), 16);
			cmd += "&alpha=" + parseFloat($("#smpl_alpha").val());
			smpl.edit(cmd);
			label_actual("trim");
		});
	}

	//--------------- Edge ---------------------
	function Edge() {
		$("#smpl_edge").click(function () {
			return label_actual("edge");
		});

		$("#smpl_edge_done").click(function () {
			let cmd = "cmd=edge";
			cmd += "&radius=" + $("input#smpl_edge_radius").val();
			smpl.edit(cmd);
			label_actual("edge");
		});
	}

	//--------------- Blur ---------------------
	function Blur() {
		$("#smpl_blur").click(function () {
			return label_actual("blur");
		});

		$("#smpl_blur_done").click(function () {
			let cmd = "cmd=blur";
			cmd += "&radius=" + $("#smpl_blur_radius").val();
			cmd += "&sigma=" + $("#smpl_blur_sigma").val();
			cmd += "&type=" + $("select#smpl_blurtype option:selected").val();
			cmd += "&channel=" + $("select#smpl_blur_channel option:selected").val();
			cmd += "&level=" + $("#smpl_blurlevel").val();
			cmd += "&angle=" + $("#smpl_blur_angle").val();
			smpl.edit(cmd);
			label_actual("blur");
		});

		$("#smpl_blurtype").change(function () {
			if ($("select#smpl_blurtype option:selected").val() == "motionblur") {
				$("span#smpl_blur_angle_visibility").show();
				$("input#smpl_blur_angle").show();
			} else {
				$("span#smpl_blur_angle_visibility").hide();
				$("input#smpl_blur_angle").hide();
			}
		});
	}

	//--------------- Convolution --------------
	function Convolution() {
		$("#smpl_convolution").click(function () {
			conv_filter();
			return label_actual("convolution");
		});

		$("#smpl_convolution_done").click(function () {
			let c00 = $("#smpl_conva00").val();
			let c01 = $("#smpl_conva01").val();
			let c02 = $("#smpl_conva02").val();
			let c10 = $("#smpl_conva10").val();
			let c11 = $("#smpl_conva11").val();
			let c12 = $("#smpl_conva12").val();
			let c20 = $("#smpl_conva20").val();
			let c21 = $("#smpl_conva21").val();
			let c22 = $("#smpl_conva22").val();
			let div = $("#smpl_convdiv").val();
			let off = $("#smpl_convoff").val();
			let cmd = "cmd=convolution&";
			cmd += "&c00=" + c00;
			cmd += "&c01=" + c01;
			cmd += "&c02=" + c02;
			cmd += "&c10=" + c10;
			cmd += "&c11=" + c11;
			cmd += "&c12=" + c12;
			cmd += "&c20=" + c20;
			cmd += "&c21=" + c21;
			cmd += "&c22=" + c22;
			cmd += "&div=" + div;
			cmd += "&off=" + off;
			smpl.edit(cmd);
			label_actual("convolution");
		});

		// SMPL filters: sharpen / blur / find edge ...
		$('#smpl_filters').change(function () {
			conv_filter();
		});

		//Divisor counting
		$("input[id*='smpl_conva']").change(function () {
			convdiv();
		});
	}

	function conv_filter() {
		let filter = $("#smpl_filters").val();
		let a;
		switch (filter) {
			case 'sharpen':
				a = [0, -1, 0, -1, 5, -1, 0, -1, 0];
				break;
			case 'edge':
				a = [0, 1, 0, 1, -4, 1, 0, 1, 0];
				break;
			case "findedge":
				a = [-1, -1, -1, -2, 8, -1, -1, -1, -1];
				break;
			case "boxblur":
				a = [1, 1, 1, 1, 1, 1, 1, 1, 1];
				break;
			case "gaussianblur":
				a = [1, 2, 1, 2, 3, 2, 1, 2, 1];
				break;
			case "unsharp":
				a = [1, 4, 1, 4, 16, 4, 1, 4, 1];
				break;
			case "emboss":
				a = [-2, -1, 0, -1, 1, 1, 0, 1, 2];
				break;
		}

		$('#smpl_conva00').val(a[0]);
		$('#smpl_conva01').val(a[1]);
		$('#smpl_conva02').val(a[2]);
		$('#smpl_conva10').val(a[3]);
		$('#smpl_conva11').val(a[4]);
		$('#smpl_conva12').val(a[5]);
		$('#smpl_conva20').val(a[6]);
		$('#smpl_conva21').val(a[7]);
		$('#smpl_conva22').val(a[8]);
		convdiv();
	}

	function convdiv() {
		let sum = 0;
		sum += parseInt($('#smpl_conva00').val());
		sum += parseInt($('#smpl_conva01').val());
		sum += parseInt($('#smpl_conva02').val());
		sum += parseInt($('#smpl_conva10').val());
		sum += parseInt($('#smpl_conva11').val());
		sum += parseInt($('#smpl_conva12').val());
		sum += parseInt($('#smpl_conva20').val());
		sum += parseInt($('#smpl_conva21').val());
		sum += parseInt($('#smpl_conva22').val());
		$("#smpl_convdiv").val(sum);
	}
	//------------ Wave effect ----------------------

	function Wave() {
		$("#smpl_wave").click(function () {
			return label_actual("wave");
		});

		$("#smpl_wave_done").click(function () {
			let cmd = "cmd=wave";
			cmd += "&amplitude=" + $("#smpl_amplitude").val();
			cmd += "&wavelength=" + $("#smpl_wavelength").val();
			smpl.edit(cmd);
			label_actual("wave");
		});
	}
	//------------ Swirl effect ----------------------
	function Swirl() {
		$("#smpl_swirl").click(function () {
			return label_actual("swirl");
		});

		$("#smpl_swirl_done").click(function () {
			let cmd = "cmd=swirl";
			cmd += "&angle=" + $("#smpl_angle").val();
			smpl.edit(cmd);
			label_actual("swirl");
		});

		$("#smpl_xp, #smpl_yp").change(function () {
			$("#smpl_xpsw").val(smpl.layer.x);
			$("#smpl_ypsw").val(smpl.layer.y);
		})
	}

	//-------------- Events -----------------------------------------------------------------

	/**
	 * Change RGB color
	 */
	$("input#smpl_rgbcolor").on("change", function () {
		smpl.rgbcolor = $(this).val();
		let r = parseInt(smpl.rgbcolor.substring(1, 3), 16);
		let g = parseInt(smpl.rgbcolor.substring(3, 5), 16);
		let b = parseInt(smpl.rgbcolor.substring(5, 7), 16);
		$("input#smpl_r").val(r);
		$("input#smpl_g").val(g);
		$("input#smpl_b").val(b);
	});

	/**
	 * Change Red
	 */
	$("input#smpl_r").change(function () {
		$("input#smpl_rgbcolor").val(smpl.ChangeRed($(this).val(), smpl.rgbcolor));
	});

	/**
	 * Change Green
	 */
	$("input#smpl_g").change(function () {
		$("input#smpl_rgbcolor").val(smpl.ChangeGreen($(this).val(), smpl.rgbcolor));
	});

	/**
	 * Change Blue
	 */
	$("input#smpl_b").change(function () {
		$("input#smpl_rgbcolor").val(smpl.ChangeBlue($(this).val(), smpl.rgbcolor));
	});

	/**
	* Reset Colors
	*/
	$("button#smpl_color_reset").click(function () {
		$("input#smpl_gammain").val('2.2');
		$("input#smpl_gammaout").val('1.0');
		$("div#smpl_gammain_val").html('2.2');
		$("div#smpl_gammaout_val").html('1.0');

		$("input#smpl_contrast").val(0);
		$("input#smpl_brightness").val(0);
		$("input#smpl_r").val(128);
		$("input#smpl_g").val(128);
		$("input#smpl_b").val(128);
		$("input#smpl_alpha").val(0);
		var hex = smpl.DecToHex(128, 128, 128);
		$("input#smpl_rgbcolor").val(hex);
	});

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

})(jQuery, Drupal, smpl);