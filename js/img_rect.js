/**
 * Rectangle Layer functions
 */
(function($, smpl ){	
	smpl.rect = {
  	x: 0,
  	y: 0,
  	width: smpl.width-smpl.delta*2,
  	height: smpl.height-smpl.delta*2,
  	radius: smpl.hradius,
  	setRect: function( x, y, w, h ){      	
    	this.x = Math.round( x );
    	this.y = Math.round( y );
    	this.width  = Math.round( w );
    	this.height = Math.round( h ) ;
    },
    setPoints(x1,y1,x2,y2){
    	this.x = Math.round( ( x1 + x2 ) / 2 );
    	this.y = Math.round( ( y1 + y2 ) / 2 );
    	this.width  = Math.round( x2 - x1 );
    	this.height = Math.round( y2 - y1 );
    },
    setCirc(x,y,r){
    	this.x = x;
    	this.y = y;
    	this.width  = 2*r;
    	this.height = 2*r;
    }
	};

	/**
	 * Change the coordinates of rectangle
	 */
	$("#smpl_x1, #smpl_x2, #smpl_y1, #smpl_y2").change( function(){
	  let x1 = parseInt( $("#smpl_x1").val() );
	  let y1 = parseInt( $("#smpl_y1").val() );
	  let x2 = parseInt( $("#smpl_x2").val() );
	  let y2 = parseInt( $("#smpl_y2").val() );
	  smpl.rect.setPoints(x1, y1, x2, y2);
	  let name="x";
	  if(smpl.canvas.getLayer("Rectangle")){
	  	name = "Rectangle";
	  } else if(smpl.canvas.getLayer("Ellipse")){
	   	name = "Ellipse";
	  }
	  
		if(name != "x"){
		  smpl.canvas.setLayer( name,{ 
	      x: smpl.rect.x, 
	      y: smpl.rect.y, 
	      width: smpl.rect.width, 
	      height: smpl.rect.height,
		  }).drawLayers();
	  }
	  smpl.rectChange(x1,y1, x2, y2);
	});
	
	/**
	 * rect position and size reset
	 */
	$("#smpl_rect_reset").click( function(){
		smpl.rect.setRect( smpl.width / 2, smpl.height / 2, smpl.width-20, smpl.height-20 );
    var name="x";
		if(smpl.canvas.getLayer("Rectangle")){
			name ="Rectangle";
    }else if(smpl.canvas.getLayer("Ellipse")){
			name ="Ellipse";
    }
		
		if(name != "x"){
    	smpl.canvas.setLayer(name,{
        x: smpl.rect.x, 
        y: smpl.rect.y, 
        width: smpl.rect.width, 
        height: smpl.rect.height,
      }).drawLayers();			
		}
	});

 	/**
 	 * Setting Aspect Ratio
 	 * @returns
 	 */
 	$("#smpl_aspect").change( function(){            
    let prop = ($("#smpl_aspect").is(":checked"))? true:false;
    if( smpl.canvas.getLayer("Rectangle") ){
			smpl.canvas.setLayer("Rectangle",{ constrainProportions: prop }).drawLayers();
    }else if( smpl.canvas.getLayer("Ellipse") ) {
     	smpl.canvas.setLayer("Ellipse",{ constrainProportions: prop }).drawLayers();
    }else if(smpl.canvas.getLayer("Circle") ) {
     	smpl.canvas.setLayer("Circle",{ constrainProportions: true }).drawLayers();
    }
 	});
	/**
	 * Add rectangle layer
	 * @param {*} p 
	 */
 	smpl.addRectangleLayer= function(p){
 		$("#smpl_x1").val( Math.round( p.x - (p.width / 2 - smpl.delta  ) ) );
    $("#smpl_x2").val( Math.round( p.x + (p.width / 2 - smpl.delta  ) ) );
    $("#smpl_y1").val( Math.round( p.y - (p.height / 2 - smpl.delta ) ) );
    $("#smpl_y2").val( Math.round( p.y + (p.height / 2 - smpl.delta ) ) );
   
  	smpl.canvas.addLayer( {
      type: "rectangle",
      name: "Rectangle",
      strokeStyle: smpl.stroke,
      strokeWidth: smpl.strokew,
      strokeDash:[ 5, 5 ],
      x: p.x,
      y: p.y,
      width:  p.width - 5,
      height: p.height - 5,
      draggable: true,
      drag: function(e){
				if( smpl.ctrlDown ) return;      	
    	  smpl.rect.setRect( e.x, e.y, e.width, e.height);
      	smpl.rectChange( e.x - e.width/2, e.y - e.height/2, e.x + e.width/2, e.y + e.height/2);
      },
			handlemove: function(e){   
				if( smpl.ctrlDown ) return;   	
      	smpl.rect.setRect( e.x, e.y, e.width, e.height);
      	smpl.rectChange( e.x - e.width /2, e.y - e.height/2, e.x + e.width/2, e.y + e.height/2 );
      },
			//Itt az x a canvas (kép) széléhez képesti eltolást jelenti
			//updateDragX: function ( layer, x ) { 
			//let vx = Math.round( x / smpl.zoom);*/
			//x = Math.floor(x);
			//console.log('updateDragX: ' + x);
			//return x;
			//},
			//updateDragY: function ( layer, y ) {
			//*let vy = Math.round( y / smpl.zoom);*/
			//	y = Math.floor(y);
			//	console.log('updateDragY: ' + y);
			//	return y;
			//},
      resizeFromCenter: false,
      constrainProportions: false,
      handlePlacement: 'both',
      handle:{
      	type: "arc",
      	fillStyle: smpl.fillStyle,
      	strokeStyle: smpl.stroke,
      	strokeWidth: smpl.strokew,
      	radius: smpl.hradius,  
      }
    }).drawLayers();    
  };

	/**
	 * Change thecoordinates of rectangle
	 * @param img - image
	 * @param x1
	 * @param y1
	 * @param x2
	 * @param y2
	 */
	 smpl.rectChange = function( x1, y1, x2, y2 ){
	    x1 = Math.round( x1 );
	    y1 = Math.round( y1 );
	    x2 = Math.round( x2 );
	    y2 = Math.round( y2 );
	    $("#smpl_x1").val( x1 );
	    $("#smpl_y1").val( y1 );
	    $("#smpl_x2").val( x2 );
	    $("#smpl_y2").val( y2 );

	    //image size
	    $("#smpl_w").val( smpl.width );
	    $("#smpl_h").val( smpl.height );

	    // width & height in px and %
	    let dx = x2 - x1;
	    let dy = y2 - y1;
	    $("#smpl_wh").val( dx + " x " + dy + "px ");
	    $("#smpl_whp").val( Math.floor( 100*dx / smpl.width) + " x " + Math.floor( 100 * dy / smpl.height) + "% ");
	 };
})(jQuery, smpl);