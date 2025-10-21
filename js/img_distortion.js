/**
 * Distortion layer
 */
(function($, Drupal, smpl ){	
		smpl.dist = {
		    x1: 10,            y1: 10,
		    x2: smpl.width-10, y2: 10,
		    x3: smpl.width-10, y3: smpl.height-10,
		    x4: 10           , y4: smpl.height-10,
		    radius: smpl.hradius,
		    setDist: function(x1, y1, x2, y2, x3, y3, x4, y4 ){
		      this.x1 = parseInt(x1);
		      this.y1 = parseInt(y1);
		      this.x2 = parseInt(x2);
		      this.y2 = parseInt(y2);
		      this.x3 = parseInt(x3);
		      this.y3 = parseInt(y3);
		      this.x4 = parseInt(x4);
		      this.y4 = parseInt(y4);
		    }, 
	};

 	$("#smpl_xd1, #smpl_yd1, #smpl_xd2, #smpl_yd2, #smpl_xd3, #smpl_yd3, #smpl_xd4, #smpl_yd4").change(function(){
    	let x1 = $("#smpl_xd1").val();
    	let y1 = $("#smpl_yd1").val();
    	let x2 = $("#smpl_xd2").val();
    	let y2 = $("#smpl_yd2").val();
    	let x3 = $("#smpl_xd3").val();
    	let y3 = $("#smpl_yd3").val();
    	let x4 = $("#smpl_xd4").val();
    	let y4 = $("#smpl_yd4").val();
    	smpl.dist.setDist( x1, y1, x2, y2, x3, y3, x4, y4 );
    	if(smpl.canvas.getLayer("Distortion")){
    		smpl.canvas.setLayer("Distortion", 
    			{
    				x1: x1, y1: y1,
    				x2: x2, y2: y2,
    				x3: x3, y3: y3,
    				x4: x4, y4: y4,
    			}
    		).drawLayers();
    	}
    	smpl.distChange(x1, y1, x2, y2, x3, y3, x4, y4 );
  });


  smpl.addDistortionLayer = function(p)
  {  	
  	smpl.canvas.addLayer({
          type: 'line',        
          name: 'Distortion',
          strokeStyle: smpl.stroke,
          strokeWidth: smpl.strokew,
          strokeDash:[5,5],
          rounded: false,
          cursor: "crosshair",
          x1: p.x1, y1: p.y1,
          x2: p.x2, y2: p.y2,
          x3: p.x3, y3: p.y3,
          x4: p.x4, y4: p.y4,
          closed: true,
          draggable: true,
          drag: function(layer){
            smpl.dist.setDist( layer.x1, layer.y1,layer.x2, layer.y2,layer.x3, layer.y3,layer.x4, layer.y4 );
            smpl.distChange( layer.x1, layer.y1,layer.x2, layer.y2,layer.x3, layer.y3,layer.x4, layer.y4  );
          },
          handle:{
            type: 'arc',
            fillStyle: smpl.fillStyle,
            strokeStyle: smpl.stroke,
            strokewidth: smpl.strokew,
            radius: smpl.hradius
          },
          handlemove: function(layer){
          	smpl.dist.setDist( layer.x1, layer.y1,layer.x2, layer.y2,layer.x3, layer.y3,layer.x4, layer.y4 );
          	smpl.distChange( layer.x1, layer.y1,layer.x2, layer.y2,layer.x3, layer.y3,layer.x4, layer.y4  );
          }
        }).drawLayers();
  };
  
  smpl.distChange = function(x1, y1, x2, y2, x3, y3, x4, y4){
      $("#smpl_xd1").val(x1); $("#smpl_yd1").val(y1);
      $("#smpl_xd2").val(x2); $("#smpl_yd2").val(y2);
      $("#smpl_xd3").val(x3); $("#smpl_yd3").val(y3);
      $("#smpl_xd4").val(x4); $("#smpl_yd4").val(y4);
  };

})(jQuery, Drupal, smpl);