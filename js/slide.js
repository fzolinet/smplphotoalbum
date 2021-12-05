/**
 * Slideshow js
 */
(function($, Drupal)
{
  var cont = $("img.smplslide_item_container");
  var img  = $("img.smplslidebox");
  if(typeof smplslide !== 'undefined') {
	    // the variable is defined
	
	// previous image
	$("#smplslideprev").click(function(){		
		var url = smplslide.ajax + "/slideget/prev?path="+ smplslide.path +"&i=" + smplslide.i;		
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				var data = JSON.parse(response[0].data);
				if(data.id != -1){					
					smplslidewrite(data);
				}else{
					alert(data.error);
				}
				$(".ajax-progress-fullscreen").remove();
			},
			error: function(response){
				$(".ajax-progress-fullscreen").remove();
				alert("Error on server side: " + response[0]);
			},
		});		
	});

	//Stop / restart the setinterval
	$("img.smplslidebox").hover(function(){
		clearInterval(smpltimer)
	}, function(){
		smpltimer = setInterval(smplnext, smplslide.interval);	
	});

	//Next image
	$("#smplslidenext").click(function(){
		smplnext();
	});	
    
	function smplnext(){
		var url = smplslide.ajax + "/slideget/next?path="+ smplslide.path +"&i=" + smplslide.i;		
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				var data = JSON.parse(response[0].data);
				if(data.id != -1){					
					smplslidewrite(data);
				}else{
					alert(data.error);
				}
				$(".ajax-progress-fullscreen").remove();
			},
			error: function(response){
				$(".ajax-progress-fullscreen").remove();
				alert("Error on server side: " + response[0]);
			},
		});			
	}

	//Load the next or previous image 
	function smplslidewrite(data){		
		var w    = img.width();
		var h    = img.height();
		smplslide.id       = data.id;
		smplslide.title    = data.title;
		smplslide.subtitle = data.subtitle;		
		smplslide.path     = data.path;
		smplslide.i        = data.i;
		smplslide.ths      = data.ths;
		
		cont.prop("id","smplslide" + smplslide.id);
		img.prop("id", "smplslide" + smplslide.id);
		if(smplslide.style =="random"){
			var st = smplslide.styles[Math.floor( Math.random() * smplslide.styles.length ) ];
		}else{
			var st = smplslide.style;
		}
		smplimageout(img, st,w,h);
				
		setTimeout(function(){
			//
			// Itt kel csinálni valamit, hogy az új kép ne torzítsa el a keretet
			img.hide();
			img.prop("src", smplslide.linksrc + smplslide.id);
			//
			console.log("magasság vált: " + $(".smplslide_all").height());
			img.prop("data-link", smplslide.linksrc + smplslide.id);
			img.prop("title", smplslide.title);
			smplimagein(img,st);
			smpltnwrite(smplslide.ths);
		},smplslide.eftime);
	}
	
	function smpltnwrite(ths){				
		var i = 0;
		$(".smplslidetnimg").each(function(){
			if( i < ths.length ){
				$(this).attr("title", ths[i].title);
				$(this).attr("src"  , smplslide.linksrc + ths[i].id + "&tn=1" );
			}
			i++;
		})
	}
	// slide out from original image
	function smplimageout(img, st,w,h){		
		switch (st){
			case 'fade' : img.fadeTo(smplslide.eftime, 0); break;
			case 'slide': break;
			case 'zero' :
				img.hide();
				break;
			case 'zero1' : 				
				img.animate({ 
					paddingTop:    h/2 + 'px',
					paddingBottom: h/2 + 'px',
					paddingLeft:   w/2 + 'px',
					paddingRight:  w/2 + 'px'
				},smplslide.eftime,"swing");
				console.log("magasság vált: " + $(".smplslide_all").height());
				break;
			default: break;
		}
	}

	//Slide in the new image
	function smplimagein(img, st) 
	{
		console.log("magasság vált: " + $(".smplslide_all").height());
		switch (st){
			case 'fade' : img.fadeTo(smplslide.eftime, 1); break;
			case 'slide': break;
			case "zero":
				img.show();
				break;
			case 'zero1' : 
				img.animate({ 
					paddingTop:    0 +'px',
					paddingBottom: 0 +'px' ,
					paddingLeft:   0 +'px',
					paddingRight:  0 +'px'
				},smplslide.eftime, "swing");
				break;
			default: break;
		}			
	}	
	smpltimer = setInterval(smplnext, smplslide.interval);
  }
})(jQuery, Drupal);
