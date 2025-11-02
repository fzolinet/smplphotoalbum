/**
 * Slideshow js
 */
(function($, Drupal, smpl){  
	var container = $("img#smplslideimgcont");	// Container
  var img  = $("img.smplslideimg");						// Image  	    
	smplslide.lastcmd = "next";
	// previous image
	$("#smplup, #smplprev").click(function(){		
		smplslideload( "up", "-1" );
	});


	//Next image
	$("#smplnext, #smpldown").click(function(){		
		smplslideload( "next", "-1" );
	});


	/**
	 * Click on the thumbnail
	 */
	$(".smplslidetnimg").click(function(){
		smplslide.id = $(this).attr("data-id");
		smplslideload("click", smplslide.id );
	});

	/**
	 * Load an image and the thumbnails
	 * @parameter string cmd
	 * @parameter string|int id
	 */
	function smplslideload( cmd, id ){		
		// If the image loaded by click, the timer stop
		clearInterval( smpltimer );
		smpltimer = false;
		smplslide.lastcmd = cmd;
		let url = smplslide.ajax + "/slideget/"+ cmd;

		if(id != "-1"){
			url += "?id=" + id;		
		}
		
		smpl.progress(true);
		$.ajax({
			url: url,
			type: "GET",
			success: function(response){
				let data = JSON.parse( response[0].data );
				if(data.id != -1){
					smplslidewrite( data, img, cmd );
				}else{
					alert( data.error );
				}
				smpl.progress(false);
				
				//if image loaded the timer restarted
				if( !smpltimer ){
					//smpltimer = setInterval( smplslideload, smplslide.interval, smplslide.lastcmd, -1 );						 
				}
			},
			error: function(response){
				smpl.progress(false);				
				alert("Error on server side: " + response[0]);
			},
		});
	}		

	/**
	 * Stop / restart the setinterval
	 */
	$("img.smplslidebox").on("mouseenter", function(){
		clearInterval( smpltimer );
		smpltimer = false;
	}).on("mouseleave", function(){		
		//smpltimer = setInterval( smplslideload, smplslide.interval, smplslide.lastcmd, -1 );		
	});
	
	/**
	 * Write the images to the web page
	 * @param {*} data 
	 * @param {*} img 
	 * @param {*} cmd 
	 */
	function smplslidewrite(data, img, cmd){
		smplslide.width    = data.width;
		smplslide.height   = data.height;			
		smplslide.id       = data.id;		
		smplslide.name     = data.name;
		smplslide.subtitle = data.subtitle;
		smplslide.alt      = data.alt;
		smplslide.path     = data.path;		
		smplslide.ths      = data.ths;		

		container.attr("id","smplslide" + smplslide.id);
		img.prop("id", "smplslide" + smplslide.id);

		// alter mode comes the next image 
		let st = "";
		if( smplslide.slidestyle == "random" ){
			st = smplslide.styles[Math.floor( Math.random() * smplslide.styles.length ) ];
			if(st == "slide"){
				var cmds = ['up', 'down', 'next', 'prev'];
				cmd = cmds[ Math.floor(Math.random() * cmds.length)];
			}
		}else{
			st = smplslide.slidestyle;
		}

		switch(st){
			case 'fade'   : img.fadeTo(smplslide.eftime, 0); imgAttrChange(); img.fadeTo(smplslide.eftime, 1); break;
			case 'animate':
			case 'slide'  :	
				switch (cmd){
					case "next": var t = ['translateX(0)', 'translateX(-100%)', 'translateX(100%)','translateX(0)']; break;
					case "prev": var t = ['translateX(0)', 'translateX(100%)', 'translateX(-100%)','translateX(0)']; break;
					case "up":   var t = ['translateY(0)', 'translateY(100%)', 'translateY(-100%)','translateY(0)']; break;
					case "down": var t = ['translateY(0)', 'translateY(-100%)', 'translateY(100%)','translateY(0)']; break;
				}
				
				const img1 = document.getElementsByClassName("smplslideimg")[0];				
				const animation1 = img1.animate(
					[ { transform: t[0] }, { transform: t[1] } ],
					{ duration: 2 * smplslide.eftime, fill: 'forwards' }
				);
					
				animation1.finished.then(() => {
					//img.hide();
					imgAttrChange();					
					//img.show();
					const animation2 = img1.animate(
						[ { transform: t[2] }, { transform: t[3] } ],
						{ duration: 2 * smplslide.eftime, fill: 'forwards' }
					);
				});				
				break;
			case 'zero'   : img.hide(); imgAttrChange(); img.show(); break;		
			default: case 'zero'   : img.hide(); imgAttrChange(); img.show(); break;
		}

		SmplTnWrite();	
	}

	function imgAttrChange(){		
		img.attr("src", smplslide.linksrc + smplslide.id);
		img.attr("data-link", smplslide.linksrc + smplslide.id);
		img.attr("title", smplslide.subtitle );		
	}

	function SmplTnWrite(  ){	
		for(let i = 0 ; i < 9 ;i++){			
			$("#smplslidetnid" + i)
				.attr("title"    , smplslide.ths[i].name)
				.attr("alt"      , smplslide.ths[i].subtitle)
				.attr("data-id"  , smplslide.ths[i].id)
				.attr("src"      , smplslide.linksrc + smplslide.ths[i].id + "?tn=1" )
				.attr("data-link", smplslide.linksrc + smplslide.ths[i].id + "?tn=1" );			
		}	
	}	
})(jQuery, Drupal, smpl);
