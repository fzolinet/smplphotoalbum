/* 
 * Edit command to show / hide the SmplImgEditForm form of properties
 * with parameters of actual item
 */	

(function($, Drupal ){		
	SmplImgEditForm   = $("div#SmplImgEditForm");
	if(SmplImgEditForm.Length >0){
		SmplImgEditForm.draggable();	
	}	

	var smpl_imgedit_maxw = $(window).width() / 1.4;
	SmplImgEditForm.css("maxWidth",smpl_imgedit_maxw);
	SmplImgEditForm.css("minWidth",300);
	
	window.onresize = function(){
		smpl_imgedit_maxw = ($(window).width() /2 );
		SmplImgEditForm.css("maxWidth",smpl_imgedit_maxw);
		SmplImgEditForm.css("minWidth",300);
	};	
	smplSaveButton(true);	//Save button on

	// Load the Smlp Image Edit form
	$(".smpl_imgedit").click(function(e){
		pos            = $(this).position();
		var id         = $(this).attr('id').substring(6);		//which button => id 		
		smpl_imgeditid = id;		
		
		var url        = smpl.ajax + "/imgedit/" + id + "/load/?w="+ $("img#smpl"+smpl_imgeditid).width();
		SmplImgEditForm.hide();
		progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				var data = JSON.parse(response[0].data);				
				smpl_load(data, SmplImgEditForm, pos);				
				progress(false);
			},
			error :  function(response){
				progress(false);
				alert("Error on server side: " + id);
				return false;
			},
		});
	});
	
	// Rotate -90,90,180 degree and the value
	$("#smpl_rotate").change(function(){				
		grad = parseInt($("select#smpl_rotate").val());
		$("#smpl_rotate_val").val(grad)
	});
	
	$("#smpl_rotate_done").click(function(){	
		var grad = parseInt($("#smpl_rotate_val").val());		
		if(grad != 0){			
			_smpl_edit("rotate=" + grad);
			$("#smpl_rotate").val(0).change();
		}	
	});
	
	// flip vertical
	$("#smpl_flip_vertical_done").click(function(){				
		_smpl_edit("flip_vertical=1");
	});

	//flip horizontal
	$("#smpl_flip_horizontal_done").click(function(){		
		_smpl_edit("flip_horizontal=1");
	});
	
	// ------------Crop--------------------	 	
	$("#smpl_crop_done").click(function(){	
		var x1=parseInt($("#smpl_x1").val());
		var y1=parseInt($("#smpl_y1").val());
		var x2=parseInt($("#smpl_x2").val());
		var y2=parseInt($("#smpl_y2").val());
		if(!(x1 ==0  && y1 ==0 && x2==0 && y2==0 )) 
		{
			cmd = "crop=1";
			cmd += "&x1=" + x1+ "&y1=" + y1 + "&x2=" + x2 + "&y2=" + y2;
			_smpl_edit(cmd);
		}
	});	
	
	//Aspect ratio change
	$("#smpl_aspect").change(function(){
		if($(this).is(":checked")){
			smpl_ias.setOptions({aspectRatio: smplimg.width() +":"+ smplimg.height()});
			smplasp = 1;
		}else{
			smpl_ias.setOptions({aspectRatio: ""});
			smplasp = 0;
		}
		smpl_ias.update();
	});

	/**
	 * ImgAreaSelect
	 */
	var smplimg = $("#SmplImgtmp");
	var smplasp = 0;
	smplInitArea();			//initial imgAreaSelect

	//initialize
	function smplInitArea(){
		//Selection object instance
		if($("input#smpl_aspect").attr("checked") ){
			smplasp=1;
			prop     = smplimg.width() +":"+ smplimg.height();
			smpl_ias = smplimg.imgAreaSelect({
				handles: true,
				instance: true,				
				aspectRatio: prop, 
				onSelectChange: _smpl_Area_Change,
			});
		}else{
			smplasp = 0;
			smpl_ias = smplimg.imgAreaSelect({
				handles: true,
				instance: true,
				onSelectChange: _smpl_Area_Change,
			});			
		}	
	}

	/**
	 * callback function when imageareaselection changes
	 * @param img - image
	 * @param selection - selection
	 */
	function _smpl_Area_Change(img, s){
		w = smplimg.width();
		h = smplimg.height();		
		$("#smpl_x1").val( ( s.x1 * smpl_width  / w) |0 );
		$("#smpl_y1").val( ( s.y1 * smpl_height / h) |0 );
		$("#smpl_x2").val( ( s.x2 * smpl_width  / w) |0 );
		$("#smpl_y2").val( ( s.y2 * smpl_height / h) |0 );

		$("#smpl_w").val( (s.x2 - s.x1) * smpl_width  / w  |0);
		$("#smpl_h").val( (s.y2 - s.y1) * smpl_height / h |0);
		$("#smpl_wp").val( (100 * (s.x2 - s.x1)  )    / w |0 );
		$("#smpl_hp").val( (100 * (s.y2 - s.y1)  )    / h |0 );
	}	

	// Resize	
	$("#smpl_resize_percent").val(100)
	$("#smpl_resize_percent_val").val(100);
	$("#smpl_resize_done").click(function(){		
		//resize
		wp = $("#smpl_resizew").val();
		hp = $("#smpl_resizeh").val();
		if( wp && hp){
			cmd = "resize=1" + "&wp=" + wp + "&hp=" + hp ; 
			_smpl_edit(cmd);
			$("#smpl_resize_percent").val(100)
			$("#smpl_resize_percent_val").val(100);
		}	
	});	

	$("#smpl_resize_percent").change(function(){
		var perc = $(this).val();
		$("#smpl_resize_percent_val").val(perc);
		$('#smpl_resizew').val( smpl_width * perc/100|0);
		$('#smpl_resizeh').val( smpl_height* perc/100|0);
	});

	$("#smpl_resize_percent_val").change(function(){
		var perc = parseInt($(this).val());
		$("#smpl_resize_percent").val(perc);
		$('#smpl_resizew').val( smpl_width * perc/100|0);
		$('#smpl_resizeh').val( smpl_height* perc/100|0);
	});

	$("#smpl_resizew").change(function(){
		var w = parseInt($(this).val());
		var perc = 100 * w / smpl_width;
		$("#smpl_resize_percent_val").val(perc|0);
		$("#smpl_resize_percent").val(perc|0);
		$('#smpl_resizeh').val( smpl_height* perc / 100|0);
	});

	$("#smpl_resizeh").change(function(){
		var h = parseInt($(this).val());
		var perc = 100 * h / smpl_height;
		$("#smpl_resize_percent").val(perc|0);
		$("#smpl_resize_percent_val").val(perc|0);
		$('#smpl_resizew').val( smpl_width * perc / 100|0);
	});
	
	// Color / Lightning 
	// Gamma change
	$("#smpl_gammain").change(function(){		
		$("div#smpl_gammain_val").html($(this).val());
	});
	
	$("#smpl_gammaout").change(function(){
		$("div#smpl_gammaout_val").html($(this).val());
	});
	
	$("#smpl_gamma_done").click(function(){	
		cmd = "&gamma=1";
		cmd += "&gammain=" + $("#smpl_gammain").val();
		cmd += "&gammaout=" + $("#smpl_gammaout").val();
		_smpl_edit(cmd);
		$("#smpl_gammain").val('2.2');
		$("#smpl_gammaout").val('1.0');	
		$("div#smpl_gammain_val").html('2.2');
		$("div#smpl_gammaout_val").html('1.0');
	});
	
	
	//contrast
	$("#smpl_contrast_done").click(function(){
		var contrast= parseInt($("#smpl_contrast").val());
		if(contrast != 0)
		cmd = "contrast=" + contrast;
		_smpl_edit(cmd);
		$("#smpl_contrast").val(0);
	});
	
	//brightness
	$("#smpl_brightness_done").click(function(){
		var brightness = parseInt($("#smpl_brightness").val());
		if(brightness != 0){
			cmd = "brightness=" + brightness;
			_smpl_edit(cmd);
			$("#smpl_brightness").val(0)	
		}
	});
	
	//colorize / rgb
	$("#smpl_rgb_done").click(function(){
		var r = parseInt($("#smpl_r").val()); 
		var g = parseInt($("#smpl_g").val()); 
		var b = parseInt($("#smpl_b").val());
		if(!( r == 0 && g == 0 & b == 0)){
			cmd = "&rgb=1&red=" + r + "&green=" + g + "&blue=" + b;
			_smpl_edit(cmd);
			$("#smpl_r").val(0); 
			$("#smpl_g").val(0); 
			$("#smpl_b").val(0);
		}
	});	
	//Gray Scale
	$("#smpl_sharp_done").click(function(){
		cmd = "sharp=1";
		_smpl_edit(cmd);
	});	
	//Sharping
	$("#smpl_grayscale_done").click(function(){
		
		cmd = "grayscale=1";
		_smpl_edit(cmd);
	});

	//Denoise
	$("#smpl_denoise_done").click(function(){		
		cmd = "smooth=1&smoothlevel="+ $("#smpl_smoothlevel").val() ;
		_smpl_edit(cmd);
		$("#smpl_denoisew").val(3);		
	});
	
	//Emboss
	$("#smpl_emboss_done").click(function(){		
		cmd = "emboss=1&";
		_smpl_edit(cmd);		
	});	

	//Gaussian blur
	$("#smpl_gaussianblur_done").click(function(){		
		cmd = "gaussianblur=1&";
		_smpl_edit(cmd);		
	});

	//Convolution
	$("#smpl_convolution_done").click(function(){		
		var c00 = $("#smpl_conv00").val();
		var c01 = $("#smpl_conv01").val();
		var c02 = $("#smpl_conv02").val();
		var c10 = $("#smpl_conv10").val();
		var c11 = $("#smpl_conv11").val();
		var c12 = $("#smpl_conv12").val();
		var c20 = $("#smpl_conv20").val();
		var c21 = $("#smpl_conv21").val();
		var c22 = $("#smpl_conv22").val();
		var div = $("#smpl_convdiv").val();
		var off = $("#smpl_convoff").val();
		cmd  ="convolution=1&";
		cmd += "&c00="+c00;
		cmd += "&c01="+c01;
		cmd += "&c02="+c02;
		cmd += "&c10="+c10;
		cmd += "&c11="+c11;
		cmd += "&c12="+c12;
		cmd += "&c20="+c20;
		cmd += "&c21="+c21;
		cmd += "&c22="+c22;
		cmd += "&div="+div;
		cmd += "&off="+off;
		_smpl_edit(cmd);		
	});	

	//Divisor counting
	$(".smpl_conv").change(function(){
		var sum = 0;
		$('.smpl_conv').each(function(){
			sum += parseInt($(this).val());
		});
		$("#smpl_convdiv").val(sum)
	});
	
	$('#smpl_filters').change(function(){
		var filter = $(this).val();
		var a;
		switch(filter){
		case 'sharpen':
			var a=[0,-1, 0, -1, 5, -1, 0, -1, 0];
			break;
		case 'edge':
			var a=[1,0,-1, 0,0,0, -1,0,1];
			break;
		case "findedge":
			var a=[-1,-1,-1, -1,8,-1, -1,-1,-1];
			break;
		case "boxblur":
			var a=[1,1,1, 1,1,1, 1,1,1];
			break;
		case "gaussianblur":
			var a=[1,2,1, 2,4,2, 1,2,1];
			break;
		case "unsharp":
			var a=[1,4,1, 4,16,4, 1,4,1];
			break;
		case "emboss":
			var a=[-2,-1,0, -1,1,1, 0,1,2];
			break;
		}
		var sum = 0;
		var i = 0;
		$('.smpl_conv').each(function(){
			$(this).val(a[i]);
			sum += parseInt(a[i]);
			i++;
		});
		$("#smpl_convdiv").val(sum)		
	});
	//arrays
	
//global smpl variables
var smpl_imgeditid = 0;
var smpl_width  = 0;
var smpl_height = 0;
var smpl_que   = 0
var smpl_idx   = 0;
var SmplImgEditForm;
var smpl_ias;
var smpl_changed = false;

/**
 * Library functions
 *
 */

/**
 * width / height proportion
 * @returns float 
 */
function _smpl_prop(){
	return smpl_width / smpl_height;
}

/**
 * Set the real width & height number to variables
 */
function _smpl_natural(dx, dy){				
	if(typeof dx !== 'undefined' && typeof dy !== 'undefined'){
		smpl_width  = dx;
		smpl_height = dy;
	}else{
		smpl_width  = smplimg.naturalWidth;
		smpl_height = smplimg.naturalHeight;
	}
	$("input#smpl_width").val(smpl_width);
	$("input#smpl_height").val(smpl_height);
	$("#smpl_resizew").val(smpl_width);
	$("#smpl_resizeh").val(smpl_height);
}

/**
 * Call ajax when change something
 * @param cmd - ajax command
 * @param del - switch off the values, checkboxes, etc.
 */
function _smpl_edit(cmd){
	var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/edit?" + cmd;
	fz_t(url);
	progress(true);
	$.ajax({
		url: url,
		type: "GET",
		success: function(response){
			try{
				var data = JSON.parse(response[0].data);
				fz_t(data);
				if(typeof data.dx !== 'undefined' && typeof data.dy !== 'undefined'){
					smpl_width  = parseInt(data.dx);
					smpl_height = parseInt(data.dy);
				}
				smplimg.attr('src', data.url + "/"+data.tempname)				
				smpl_ias.cancelSelection();
				var step = parseInt($("#smpl_step").val())+1;
				$("#smpl_step").val(step);
				var que = parseInt($("#smpl_que").val());
				if(step > que){
					$("#smpl_que").val(que+1);
				}
				smplSaveButton(true);
			}catch(e){
				progress(false);
			}
			progress(false);
			return false;
		},
		error:  function(response){
			progress(false);
			fz_t(response);
			alert("Error on server side: " + smpl_imgeditid);
			return false;
		},
	});			
}
/**
 * Load an image
 * @param data
 * @returns
 */
function smpl_load(data, obj, pos){		
	$('div#smpl_imgedit_filename').html(data.name);
	$("input#smpl_imgedit_id").val(data.id);	
	fz_t(data);
	smplimg.attr("src",data.url+data.tempname+"?v=" + Math.random() );
	
	_smpl_natural( data.dx, data.dy);
	smplUndoRedo(data.idx, data.que);
	smplInitArea();
	setDivInWindow(obj, pos)
}

function HideSmplImgEditForm(id){
	var url  = smpl.ajax + "/imgedit/" + id + "/cancel";
	$.ajax({
		url: url,
		type: "GET",
		success: function(response){
			smplUndoRedo(0,0, false);
		},
		error :  function(response){
			$(".ajax-progress-fullscreen").remove();
			alert("Error on server side: " + id);
		},
	});
	smplSaveButton(false);
	SmplImgEditForm.hide();
	return false;		
}

function SmplHideImageEdit() {
	smpl_ias = smplimg.imgAreaSelect({hide:true})
	SmplImgEditForm.hide();	
}

/**
 * Undo the modified image
 * back values: image src, width, height, undo number, max undo stack
 */
$("button#SmplImgEditFormUndo").click(function(){
	var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/undo";		
	progress(true);
	$.ajax({
		url: url,
		type: "GET",
		success: function(response){
			var data = JSON.parse(response[0].data);
			smpl_load(data, SmplImgEditForm, pos);
			progress(false);
			return false;
		},
		error :  function(response){
			progress(false);
			alert("Error on server side: " + smpl_imgeditid);
			return false;
		},
	});
});

// Redo
$("button#SmplImgEditFormRedo").click(function(){
	var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/redo";			
	progress(true);
	$.ajax({
		url: url,
		type: "GET",
		success: function(response){
			var data = JSON.parse(response[0].data);				
			smpl_load(data, SmplImgEditForm, pos);
			progress(false);
			return false;
		},
		error :  function(response){
			progress(false);
			alert("Error on server side: " + smpl_imgeditid);
			return false;
		},
	});
});

/**
 * If End and Save the editing of picture 
 */
$("button#SmplImgEditFormSubmit").click(function(){		
	var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/save";		
	progress(true);
	$.ajax({
		url: url,
		type: "GET",
		success: function(response){
			var data = JSON.parse(response[0].data);
			progress(false);				
			$("img#smpltn" +smpl_imgeditid ).attr("src",data.link)
			return false;
		},
		error:  function(response){
			var data = JSON.parse(response[0].data);
			progress(false);
			alert("Error on server side: " + smpl_imgeditid);
			return false;
		},
	});	
	return false;
});


/**
 * Cancel the modified image
 */
$("button#SmplImgEditFormClose").click(function(){				
	var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/close";		
	progress(true)
	$.ajax({
		url: url,
		type: "GET",
		success: function(response){
			var data = JSON.parse(response[0].data);
			smplUndoRedo(0,0, false);
			progress(false);
		},
		error :  function(response){
			progress(false);
			alert("Error on server side: " + smpl_imgeditid);
		},
	});
	smplSaveButton(false);
	SmplHideImageEdit();		
	return false;
});



/**
 * Undo button disabled
 * @param i
 */
function smplUndoRedo(idx , que){
	var ud = $("input#SmplImgEditFormUndo");
	if(idx > 0){
		ud.removeAttr("disabled");
	}
	else{		
		ud.prop("disabled", true);	
	}
	
	var rd = $("input#SmplImgEditFormRedo");
	if(idx < que) rd.removeAttr("disabled");
	else          rd.prop("disabled", true);
	
	$("#smpl_step").val(idx);
	$('#smpl_que').val(que)
	if(idx > 0)   smplSaveButton(true);	
	else          smplSaveButton(false);
}

function smplSaveButton(enable){
	var sb = $("button#SmplImgEditFormSubmit");
	if( enable ) sb.prop("disabled", false);
	else         sb.prop('disabled', true);
}
function progress(on){	
	$(".ajax-progress-fullscreen").remove();
	if(typeof on !== 'undefined' && on){
		$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
	}
}
})(jQuery, Drupal);