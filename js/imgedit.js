/* 
 * Edit command to show / hide the SmplImgEditForm form of properties
 * with parameters of actual item
 */	

$j(document).ready(function(){
	//ImgEdit button	
	$j('.smpl_imgedit_plus').mouseover(function(){
		$j(this).css('cursor','pointer');
	});

	$j('.smpl_imgedit_plus').mouseout(function(){
		$j(this).css('cursor','default');
	});
		
	SmplImgEditForm   = $j("div#SmplImgEditForm");
	
	// Draggable window
	$j(function(){
		SmplImgEditForm.draggable();	
	})
	
	var smpl_imgedit_max_width = $j(window).width() / 1.4;
	SmplImgEditForm.css("maxWidth",smpl_imgedit_max_width);
	SmplImgEditForm.css("minWidth",600);
	
	window.onresize = function(){
		smpl_imgedit_max_width = ($j(window).width() /2 );
		SmplImgEditForm.css("maxWidth",smpl_imgedit_max_width);
		SmplImgEditForm.css("minWidth",600);
	};

	//initial imgAreaSelect
	_smpl_init_area();
	
	_smpl_ThumbButton(true);
	/**
	 * Load the Smlp Image Edit form
	 */
	
	$j(".smpl_imgedit_plus").click(function(e){
		var id  = $j(this).attr('id').substring(6);		//which button => id 
		smpl_imgeditid = id;
		$j("img#SmplImgtmp").attr('src',smpl.ajax + "/images/loading.gif");
		var visimg = SmplImgEditForm.css('display');
		pos = $j(this).position();
		if(visimg != "none"){
			HideSmplImgEditForm(id);
		}		
		var url  = smpl.ajax + "/imgedit/" + id + "/form";
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){
				_i = data.split("|");				
				$j("input#imgid").val(_i[0]);
				$j('div#smpl_imgedit_filename').html(_i[1]);
				$j("img#SmplImgtmp").attr("src",_i[2]+"?v=" + Math.random());
				_smpl_natural( _i[3], _i[4]);
				_smpl_undo_redo(_i[5],_i[6], false);				
				_smpl_init_area();												
				setDivInWindow(SmplImgEditForm, pos, e.pageX, e.pageY)
			},
			error :  function(data){
				alert(data);
			},
		});
	});
	
	/**
	 * Undo the modified image
	 * back values: image src, width, height, undo number, max undo stack
	 */
	$j("input#SmplImgEditFormUndo").click(function(){
		var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/undo";
		$j("img.smpl_imgedit_img").attr('src',smpl.ajax + "/images/loading.gif");
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){
				//reload the image from source
				var _i = data.split("|");
				$j("img.smpl_imgedit_img").attr('src',_i[0]+"?v=" + Math.random() );
				_smpl_natural(_i[1],_i[2]);
				if (typeof del !== 'undefined' ){
					_smpl_turn_off(del);	
				}
				_smpl_init_area();
				_smpl_undo_redo(_i[3], _i[4], true);
				return false;
			},
			error :  function(data){
				alert(data);
				return false;
			},
		});	
		return false;
	});

	// Redo
	$j("input#SmplImgEditFormRedo").click(function(){
		var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/redo";
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){
				//reload the image from source
				var _i = data.split("|");
				$j("img.smpl_imgedit_img").attr('src',_i[0]+"?v=" + Math.random());
				_smpl_natural(_i[1],_i[2]);
				if (typeof del !== 'undefined' ){
					_smpl_turn_off(del);	
				}
				_smpl_init_area();
				_smpl_undo_redo(_i[3], _i[4], true);
				return false;
			},
			error :  function(data){
				alert(data);
				return false;
			},
		});	
		return false;
	});
	/**
	 * Cancel the modified image
	 */
	$j("input#SmplImgEditFormCancel").click(function(){
		HideSmplImgEditForm(smpl_imgeditid);
	});
	
	/**
	 * If End and Save the editing of picture 
	 */
	$j("input#SmplImgEditFormSubmit").click(function(){		
		var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/save";
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){
				//reload the image from source
				newsrc = data+"?v=" + Math.random();
				$j("img.smpl_imgedit_img").attr('src',newsrc );
				$j("img#img" + smpl_imgeditid).attr('src',newsrc );
				_smpl_natural(0,0);
				_smpl_turn_off(" ");
				_smpl_undo_redo(0,0, false);
				_smpl_SaveButton(false);
				SmplImgEditForm.hide(smpl_eff);
				return false;
			},
			error :  function(data){
				alert(data);
				return false;
			},
		});	
		return false;
	});
	
	/**
	 * Make thumbnail 
	 */
	$j("input#SmplImgEditFormThumb").click(function(){		
		var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/thumb";
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){
				//reload the image from source
				newsrc = data+"?v=" + Math.random();
				$j("img#img" + smpl_imgeditid).attr('src',newsrc );
				return false;
			},
			error :  function(data){
				alert(data);
				return false;
			},
		});	
		return false;
	});

	// Rotate -90,90,180 degree 
	$j("select#img_rotate").change(function(){				
		grad = $j("select#img_rotate").val();
		if(grad != "-"){
			_smpl_edit("rotate=" + grad, " ");	
		}
	});
	
	//rotate	
	$j("input#img_rotate_number_chk").change(function(){	
		grad = $j("input#img_rotate_number").val();		
		if(grad != 0){			
			_smpl_edit("rotate=" + grad, " ");
		}	
	});
	
	// flip vertical
	$j("input#smpl_flip_vertical").click(function(){				
		if($j(this).attr("checked")){			
			_smpl_edit("flip_vertical=1", " ");
		}
	});

	//flip horizontal
	$j("input#smpl_flip_horizontal").click(function(){		
		if($j(this).attr("checked")){	
			_smpl_edit("flip_horizontal=1", " ");
		}
	});
	
	//contrast
	$j("input#smpl_contrast_chk").click(function(){		
		if($j(this).attr("checked") && $j("input#smpl_contrast").val() != 0 ){
			cmd = "contrast=" + $j("input#smpl_contrast").val();
			_smpl_edit(cmd, " ");
		}
	});
	
	//brightness
	$j("input#smpl_brightness_chk").click(function(){		
		if($j(this).attr("checked") && $j("input#smpl_brightness").val() != 0 ){
			cmd = "brightness=" + $j("input#smpl_brightness").val();
			_smpl_edit(cmd, " ");
		}
	});
	
	//colorize / rgb
	$j("input#smpl_rgb_chk").click(function(){
		r = $j("input#smpl_red").val(); 
		g = $j("input#smpl_green").val(); 
		b = $j("input#smpl_blue").val();
		if($j(this).attr("checked") && (r != 0 || g != 0 || b != 0 ) ){
			cmd = "&rgb=1&red=" + r + "&green=" + g + "&blue=" + b; 
			_smpl_edit(cmd, " ");	
		}
	});
	
	$j("input#smpl_red").click(function(){		
		_smpl_turn_off("rgb");	
	});
	$j("input#smpl_green").click(function(){		
		_smpl_turn_off("rgb");	
	});
	$j("input#smpl_blue").click(function(){		
		_smpl_turn_off("rgb");	
	});

	// Gamma change
	$j("input#smpl_gamma").change(function(){	
		if( $j("input#smpl_gamma").attr("checked") ){
			cmd = "&gamma=1";
			cmd += "&gammain=" + $j("#smpl_gammain").val();
			cmd += "&gammaout=" + $j("#smpl_gammaout").val();
			_smpl_edit(cmd, " ");
			$j("input#smpl_gammain").val('2.2');
			$j("input#smpl_gammaout").val('1.0');
		}		
	});
	
	$j("input#smpl_gammain").change(function(){		
		$j("div#smpl_gammain_val").html($j(this).val());
	});
	
	$j("input#smpl_gammaout").change(function(){
		$j("div#smpl_gammaout_val").html($j(this).val());
	});
		
	//Denoise
	$j("input#smpl_denoise_chk").click(function(){		
		if($j(this).attr("checked")){
			maskWidth  = $j("input#smpl_maskWidth").val();
			maskHeight = $j("input#smpl_maskHeight").val(); 
			cmd = "denoise=1&maskWidth="+ maskWidth + "&maskHeight=" + maskHeight; 
			_smpl_edit(cmd, " ");	
		}
	});

	// Crop	 	
	$j("input#smpl_crop").change(function(){	
		x1= $j("#smpl_x1").val();
		y1= $j("#smpl_y1").val();
		x2= $j("#smpl_x2").val();
		y2= $j("#smpl_y2").val();
		if(x1  && y1 && x2 && y2 ) 
		{
			cmd = "crop=1";
			cmd += "&x1=" + x1;
			cmd += "&y1=" + y1;
			cmd += "&x2=" + x2;
			cmd += "&y2=" + y2;
			_smpl_edit(cmd, " ");
		}

	});	
	
	//Aspect ratio change
	$j("input#smpl_aspect").change(function(){
		if($j(this).attr("checked")){
			prop = $j("img#SmplImgtmp").width() +":"+ $j("img#SmplImgtmp").height();
			smpl_ias.setOptions({aspectRatio: prop});			
		}else{
			smpl_ias.setOptions({aspectRatio: ""});
		}
		smpl_ias.update();
	});

	
	// Resize	 	
	$j("input#smpl_resize").change(function(){		
		//resize
		wp = $j("#smpl_wp").val();
		hp = $j("#smpl_hp").val();
		if( wp && hp){
			cmd = "resize=1" + "&wp=" + wp + "&hp=" + hp + ( $j("input#smpl_aspect").attr("checked")? "&prop=1" : ""); 
			_smpl_edit(cmd, " ");
		}
	});	

	// If change the width or height in pixel or percent with hand 
	$j("#smpl_w").change(function (){
		$j("#smpl_wp").val( 100 * $j(this).val() / smpl_width |0);
		
		if( $j("input#smpl_aspect").attr("checked")){	// if aspect ratio is on
			$j("#smpl_h").val( $j(this).val() / _smpl_prop());
			$j("#smpl_hp").val($j("#smpl_wp").val());
		}				
	});
	
	$j("#smpl_h").change(function (){
		$j("#smpl_hp").val( 100 * $j(this).val() * smpl_height |0);
		
		if( $j("input#smpl_aspect").attr("checked")){	// if aspect ratio is on
			$j("#smpl_w").val( $j(this).val() * _smpl_prop());
			$j("#smpl_wp").val($j("#smpl_hp").val());
		}	
	});
	
	$j("#smpl_wp").change(function (){
		$j("#smpl_w").val( $j(this).val()* smpl_width / 100 |0);
		if( $j("input#smpl_aspect").attr("checked")){
			$j("#smpl_hp").val( $j(this).val() );
			$j("#smpl_h").val( $j(this).val()* smpl_height / 100 |0);
		}
	});
	
	$j("#smpl_hp").change(function (){
		$j("#smpl_h").val( $j(this).val()* smpl_height / 100 |0);
		if( $j("input#smpl_aspect").attr("checked")){
			$j("#smpl_wp").val( $j(this).val() );
			$j("#smpl_w").val( $j(this).val()* smpl_width / 100 |0);
		}
	});
});

//global smpl variables
var smpl_imgeditid = 0;
var smpl_width  = 0;
var smpl_height = 0;
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
		smpl_width  = document.querySelector("#SmplImgtmp").naturalWidth;
		smpl_height = document.querySelector("#SmplImgtmp").naturalHeight;
	}
	$j("div#smpl_width").html(smpl_width);
	$j("div#smpl_height").html(smpl_height);
}

/**
 * Turn off everything in the window
 */
function _smpl_turn_off(ok){
	if( ok != "rotate_number" ) {
		$j("input#img_rotate_number").val(0);
		$j("input#img_rotate_number_chk").attr("checked",false);
	}
	if( ok != "rotate" )   				$j("select#img_rotate").val("-");
	if( ok != "flip_vertical")			$j("input#smpl_flip_vertical").attr("checked",false);
	if( ok != "flip_horizontal")		$j("input#smpl_flip_horizontal").attr("checked", false);
	if( ok != "crop")					{
		$j("input#smpl_crop").attr("checked",false);		
		$j("#smpl_x1").val( "");
		$j("#smpl_y1").val( "");
		$j("#smpl_x2").val( "");
		$j("#smpl_y2").val( "");	
	}
	if( ok != "resize") {
		$j("input#smpl_resize").attr("checked",false);
		$j("#smpl_w").val( "");
		$j("#smpl_h").val( "");
		$j("#smpl_wp").val( "");
		$j("#smpl_hp").val( "");
	}		
	if( ok != "resize" && ok != "crop") $j("#SmplImgtmp").imgAreaSelect({remove: true});
	if( ok != "contrast") {
		$j("input#smpl_contrast").val(0);
		$j("input#smpl_contrast_chk").attr("checked",false);
	}
	if( ok != "brightness") {
		$j("input#smpl_brightness").val(0);
		$j("input#smpl_brightness_chk").attr("checked",false);
	}
	if( ok != "rgb") {
		$j("input#smpl_red").val(0);
		$j("input#smpl_green").val(0);
		$j("input#smpl_blue").val(0);
		$j("input#smpl_rgb_chk").attr("checked",false);
	}
	if( ok != "gamma"){
		$j("input#smpl_gammain").val(2.2);
		$j("input#smpl_gammaout").val(1.0);
		$j("div#smpl_gammain").html("2.2");
		$j("div#smpl_gammaout").html("1.0");		
	}
	if( ok != "denoise"){
		$j("input#smpl_maskWidth").val(3);
		$j("input#smpl_maskHeight").val(3);
		$j("input#smpl_denoise_chk").attr("checked",false);		
	}
}

/**
 * Call ajax when change something
 * @param cmd - ajax command
 * @param del - switch off the values, checkboxes, etc.
 */
function _smpl_edit(cmd, del){
	var url  = smpl.ajax + "/imgedit/" + smpl_imgeditid + "/edit?" + cmd;
	$j("body").addClass("smpl_waiting");
	$j.ajax({
		url: url,
		type: "GET",
		success: function(data){
			//reload the image from source
			var _i = data.split("|");			
			$j("img.smpl_imgedit_img" ).attr('src', _i[0] + "?v=" + Math.random() );
			_smpl_natural(_i[1],_i[2]);
			if (typeof del !== 'undefined' ){
				_smpl_turn_off(del);	
			}
			_smpl_ThumbButton(false);
			_smpl_init_area();
			_smpl_undo_redo(_i[3],_i[4], true);
			$j('body').removeClass('smpl_waiting');
			return false;
		},
		error :  function(data){
			alert(data);
			return false;
		},
	});	
}

function HideSmplImgEditForm(id){
	var url  = smpl.ajax + "/imgedit/" + id + "/cancel";
	$j.ajax({
		url: url,
		type: "GET",
		success: function(data){
			_smpl_turn_off(" ");
			_smpl_undo_redo(0,0, false);
		},
		error :  function(data){
			alert(data);
		},
	});
	_smpl_SaveButton(false);
	SmplImgEditForm.hide(smpl_eff);
	return false;		
}

/**
 * callback function when imageareaselection changes
 * @param img - image
 * @param selection - selection
 */
function _smpl_Area_Change(img, selection){
	w = $j("#SmplImgtmp").width();
	h = $j("#SmplImgtmp").height();
	$j("#smpl_x1").val( ( selection.x1 * smpl_width  / w) |0 );
	$j("#smpl_y1").val( ( selection.y1 * smpl_height / h) |0 );
	$j("#smpl_x2").val( ( selection.x2 * smpl_width  / w) |0 );
	$j("#smpl_y2").val( ( selection.y2 * smpl_height / h) |0 );

	$j("#smpl_w").val( (selection.x2 - selection.x1) * smpl_width  / w);
	$j("#smpl_h").val( (selection.y2 - selection.y1) * smpl_height / h);
	$j("#smpl_wp").val( (100 * (selection.x2 - selection.x1) / w ) |0 );
	$j("#smpl_hp").val( (100 * (selection.y2 - selection.y1) / h ) |0 );
}
			
/**
 * ImgAreaSelect initialize
 */
function _smpl_init_area(){
	//Selection object instance
	if($j("input#smpl_aspect").attr("checked")){
		prop = $j("#SmplImgtmp").width() +":"+ $j("#SmplImgtmp").height();
		smpl_ias = $j("#SmplImgtmp").imgAreaSelect({
			handles: true,
			instance: true,
			aspectRatio: prop,
			onSelectEnd: _smpl_Area_Change
		});
	}else{
		smpl_ias = $j("#SmplImgtmp").imgAreaSelect({
			handles: true,
			instance: true,
			onSelectEnd: _smpl_Area_Change
		});			
	}	
}
/**
 * Undo button disabled
 * @param i
 */
function _smpl_undo_redo(i,j, enable){
	var ud = $j("input#SmplImgEditFormUndo");
	var rd = $j("input#SmplImgEditFormRedo");
	
	t = ud.val();
	t = t.split(':');
	t1 = t[0]+":"+i;
	$j('#smpl_idx').html(i);
	$j('#smpl_queue').html(j);
	
	if(i>1){
		if($j.fn.jquery < '1.6')ud.removeAttr("disabled");
		else					ud.prop("disabled", false);
	}else{
		if($j.fn.jquery < '1.6')ud.attr("disabled", "disabled");
		else					ud.prop('disabled', true);
	}

	if(i<j){
		if($j.fn.jquery < '1.6')rd.removeAttr("disabled");
		else					rd.prop("disabled", false);
	}else{
		if($j.fn.jquery < '1.6')rd.attr("disabled", "disabled");
		else					rd.prop('disabled', true);
	}	
	_smpl_SaveButton(enable);
}

function _smpl_ThumbButton(enable){
	var sb = $j("input#SmplImgEditFormThumb");
	if( enable ){
		if($j.fn.jquery < '1.6')sb.removeAttr("disabled");
		else					sb.prop("disabled", false);
	}else{
		if($j.fn.jquery < '1.6')sb.attr("disabled", "disabled");
		else					sb.prop('disabled', true);
	}	
}
function _smpl_SaveButton(enable){
	var sb = $j("input#SmplImgEditFormSubmit");
	if( enable ){
		if($j.fn.jquery < '1.6')sb.removeAttr("disabled");
		else					sb.prop("disabled", false);
	}else{
		if($j.fn.jquery < '1.6')sb.attr("disabled", "disabled");
		else					sb.prop('disabled', true);
	}
}