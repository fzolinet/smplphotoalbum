(function($, Drupal ){	
	if( typeof(smpl) === 'undefined'){
		var smpl    = new Array();
	}	
	/* Global variables */
	var win = $(window);
	var smplclk_left=0;
	var smplclk_top=0;	
	//view
	$('.smpl_image_link').click( function(){
		var id    = $(this).attr('id');
		var x     = $(this).parent().parent();
		smpl.view = x.find("div.smpl_view_text");
		var url   = smpl.ajax + "/view/" + id;
		$.ajax({
			url: url,
			type: "GET",
			success: function(data){
				smpl.view.html(data);
			}
		});
	});
	
	//View image in box
	var smpldiv    = $("div#smpldiv");
	var smplbox    = $("img#smplbox");	
	var smplback   = $('div#smplback');
	var smplclose  = $("div#smpldiv_close");
    $("img.smplbox").click(function(e){
        progress(true);
        var link = $(this).attr("data-link"); 
        smplbox.attr("src", link);
        var h = $(window).height();
        var w = $(window).width();
    	smplbox.css("max-width" ,w*0.8);
    	smplbox.css("max-height",h*0.8);
    	
    });
    
    smplbox.on("load", function(){
    	smplback.show();
    	smpldiv.show();
    	smplbox.show();
    	smplclose.show();
    	progress(false);
    });
    if(smplbox.Length>0){
    	smplbox.draggable();	
    }
    
    smplbox.click(function(){
    	smplbox.hide();
    	smplbox.attr("src", "");
    	smplbox.css("z-index",0);
    	smplback.hide();
        smpldiv.hide();
        smplbox.hide();
        smplclose.hide();
    })

	function progress(on){	
		$(".ajax-progress-fullscreen").remove();
		if(typeof on !== 'undefined' && on){
			$('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
		}
	}
})(jQuery, Drupal);

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

function setDivInWindow(w, pos) {
	w.show();
	var wx = w.width();
	var wy = w.height();
	var left = 0;
	var top  = 0;
	//
	var winx = window.innerWidth;
	var winy = window.innerHeight;
	
	if(wx <  winx) left = (winx - wx) / 2	
	if(wy < winy) top = (winy - wy) / 2;
	
	w.css('position','fixed');
	w.position({top: top + 'px', left: left + 'px'});	
}
/**
 * Bug logging function
 * @param a - string
 * @returns
 */
function fz_t(a){
	console.log(a);
    console.trace();	
}
