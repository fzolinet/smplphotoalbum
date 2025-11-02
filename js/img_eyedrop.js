/**
 * Eyedrop function
 */
( function($, smpl ){	
  
  smpl.eyedropIsActive = false;  

  /**
   * Change Eyedrop Checkbox
   */
  $("#smpl_eyedrop").change( function( e ){
	    if($(this).is(":checked")){
	    	smpl.eyedropIsActive = smpl.EyeDropOn();
	    }else{
	    	smpl.eyedropIsActive = smpl.EyeDropOff();
	    }
	});
	
	/*
   * Switch on the EyeDrop
   */
	smpl.EyeDropOn = function(){
		//smpl.ChangeLayer("Eyedrop", true);
    //$("#smpl_eyedrop").prop("checked", true);
    smpl.layer_area("Eyedrop", true);
    return true;
  };  

  /*
   * Switch off the EyeDrop
   */
  smpl.EyeDropOff = function(){
    smpl.ChangeLayer("Eyedrop", false);
    $("#smpl_eyedrop").prop("checked", false);
    return false;
  }

  smpl.EyeDropTransparency = function( is ){
  	let drop = false;
    if(is){
      drop = smpl.EyeDropOff();
      $("#smpl_eyedrop").attr("disabled","disabled");  
    }else{
      drop = smpl.EyeDropOn();
      $("#smpl_eyedrop").attr("disabled",false).attr("checked", true);
    }
    return drop;
  }

	/**
   * EyeDrop Color - get color from the point of canvas
   * @params int lx - coordinates
   * @params int ly
   * return hexa rgb color
   */
  smpl.EyeDropColor = function( lx, ly ){
      let px = smpl.ctx.getImageData( lx, ly, 1, 1 );
      $("#smpl_r").val( px.data[0] );
      $("#smpl_g").val( px.data[1] );
      $("#smpl_b").val( px.data[2] );
      $("#smpl_alpha").val(px.data[3]);
      let hex = "#";
      hex += ( '00' + px.data[0].toString(16).toUpperCase() ).slice(-2);
      hex += ( '00' + px.data[1].toString(16).toUpperCase() ).slice(-2);
      hex += ( '00' + px.data[2].toString(16).toUpperCase() ).slice(-2);
      $("#smpl_rgbcolor").val(hex);
      smpl.rgbcolor = hex;
      return hex;
  }
    
  /**
   * Count average of colors of domain
   * @params int lx - coordinates
   * @params int ly
   * return hexa rgb color
   */
  smpl.EyeDropAvgColor = function(lx, ly){
    let dx = 2;
    let dy = 2;
    let count = 0;
    let r = 0, g = 0, b = 0
    var minx = (lx - dx > 0) ? lx - dx : 0;
    var maxx = (lx + dx < smpl.width) ? lx + dx : smpl.width; 
    var miny = (ly - dy > 0) ? ly - dy : 0;
    var maxy = (ly + dy < smpl.height) ? ly + dy : smpl.height; 
    for(let x = minx; x < maxx; x++ ){      
      for(let y =-dy; y<dy; y++ ){          
        px = smpl.ctx.getImageData(x+lx,y+ly,1,1);
        r += px.data[0];
        g += px.data[1];
        b += px.data[2];
        count++;
      }
    }      
    r = Math.floor(r/count);
    g = Math.floor(g/count);
    b = Math.floor(b/count);
    return ( padZero(r) + padZero(g) + padZero(b) ).toUppercase();
  }

  /**
   * Invert a color
   * @param string hex - original color
   * @param boolean bw - black and white 
   * @returns hex color
   */
  smpl.EyeDropInvertColor = function(hex, bw) {
    if (hex.indexOf('#') === 0) {
      hex = hex.slice(1);
    }

    // convert 3-digit hex to 6-digits.
    if (hex.length === 3) {
      hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    if (hex.length !== 6) {
      throw new Error('Invalid HEX color.');
    }
    var r = parseInt(hex.slice(0, 2), 16),
        g = parseInt(hex.slice(2, 4), 16),
        b = parseInt(hex.slice(4, 6), 16);
    if (bw) {
      // https://stackoverflow.com/a/3943023/112731
      return (r * 0.299 + g * 0.587 + b * 0.114) > 186 ? '#000000' : '#FFFFFF';
    }

    // invert color components
    r = (255 - r).toString(16);
    g = (255 - g).toString(16);
    b = (255 - b).toString(16);
    // pad each with zeros and return
    return "#" + smpl.padZero(r) + smpl.padZero(g) + smpl.padZero(b);
  }
  /**
   * padding string with 0 on left side  
   * @param str
   * @param len
   * @returns
   */
  smpl.padZero = function(str, len) {
    len = len || 2;
    var zeros = new Array(len).join('0');
    return (zeros + str).slice(-len);
  }
}) (jQuery, smpl);