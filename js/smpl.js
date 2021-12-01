if(typeof($j) === 'undefined'){
	var $j = jQuery;	
}
if( typeof(smpl) === 'undefined'){
	var smpl    = new Array();
}
/* Global variables */
var win = $j(window);
var smplclk_left=0;
var smplclk_top=0;
var smpl_eff=600;
$j(document).ready(function(){
	//view
	$j('.smpl_image_link').click( function(){
		var id    = $j(this).attr('id');
		var x = $j(this).parent().parent();
		smpl.view = x.find("div.smpl_view_text");
		var url   = smpl.ajax + "/view/" + id;
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){
				smpl.view.html(data);
			}
		});
	});
	
	/**
	 * Social network
	 */
	/*
	 * Set the image FB share acceptable og:
	 *
	 */
	$j(".smpl_fb_plus").click(function(){
		var id    = $j(this).attr('id').substring(5);
		var parnt = $j(this).parent().parent();
		var img = parnt.find('img.smpl_img');
		var col = rgb2hex(img.css("border-top-color"));
		var fb = "false";
		if(col == "#aaaaaa" || col == "#AAAAAA"){
			fb = "true";
		}
		var url  = smpl.ajax + "/fb/" + id + "/" + fb;

		$j.get(url,function(data){
			if(data.substring(0,2) == '11'){
				img.css("border-color","#0000ff");
			}else{
				img.css("border-color","#aaaaaa");
			}
		});
	});

	if(smpl.keywords != null){
		//<meta name="keywords" content="insert subtitles here">
		var cnt = "";
		var mta = $j("meta[name=keywords]");
		if( mta.length < 1){
			$j("head").prepend("<meta name='keywords' content='' />");
		};
		mta = $j("meta[name=keywords]");
		cnt = mta.attr('content');
		var smpl_sub_text = $j("div.smpl_sub_text");
		$j.each(smpl_sub_text, function(index, value){
			var str = value.innerHTML;
			cnt = cnt + str + ",";
		});
		if( cnt.substr(cnt.length-1,cnt.length-1) == "," ){
			cnt = cnt.substr(0,cnt.length-1);
		}
		mta.attr('content', cnt);
	}

	/**
	 * Order settings
	 */
	//smpl.order.selected
	$j("select#smpl_sortsource").change(function(){
		var str = "";
		$j("select#smpl_sortsource option:selected").each(function(){
			str = $j(this).text();
		});
		smpl.order = str;
		smplorder_set();
	});
	//smpl.order.selected
	$j("select#smpl_sort").change(function(){
		var str = "";
		$j("select#smpl_sort option:selected").each(function(){
			str = $j(this).text();
		});
		smpl.updown = str;
		smplorder_set();
	});

	function smplorder_set(){
		f = $j("form#smpl_order");
		f.submit();
		return true;
	}
	
	var divs = $j("div.smpl_mobile_div");
	var maxh = 0;
	 
	divs.each(function(index, el) {
	    if($j(el).height() > maxh){
	      maxh = $j(el).height();
	    }
	});
	 
	divs.each(function(index, el) {
	    $j(el).css("min-height", maxh+"px");
	});

	//Help shows / hides	
	$smpl_hlp = $j("div#smpl_help");
	$j("#smpl_help_link").click(function(e){		
		var url   = smpl.ajax + "/edithelp/";
		$j.ajax({
			url: url,
			type: "GET",
			success: function(data){				
				$j("div#smpl_help_content").html(data);
			}
		});
		$smpl_hlp.show();          
	});
	$j("#smpl_help_close").click(function(){             
        $smpl_hlp.hide();
	});
	// the help window is draggable
	if($smpl_hlp.length>0 ){
		$j(function(){
			$smpl_hlp.draggable();
		});
	}
});



/*End the code of taxonomy */
function rgb2hex(rgb)
{
    if (/^#[0-9A-F]{6}$/i.test(rgb)) return rgb;

    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}
/**
 * 
 * @param id
 * @returns
 */
function _q(id){
  return id.replace( /(:|\.|\[|\]|\_)/g, "\\$1" );
}

function setDivInWindow(w, pos, mouseX, mouseY) {
	w.show();		
	px = pos.left;	//A dokumentumhoz képest van
	py = pos.top;
	var wdy  = w.height();
	var wdx  = w.width();
	var xoff = window.pageXOffset;
	var yoff = window.pageYOffset;
	var dx   = window.innerWidth;
	var dy   = window.innerHeight;
		
	if( px + wdx+162 > dx+xoff ){
		px -= ((px+wdx+162) - (dx + xoff))+100;
	}	
	if ( py + wdy +544> dy+yoff ){
		py -= ((py+wdy+544) - (dy + yoff))+100;
	}
	w.css({position: "absolute", left: px, top: py });
}
