/**
 * ------------ Grid Layer ------------------
 */
(function($, smpl ){	
	smpl.grid = {
  		strokew: 1,
  		dx:10,
  		dy:10
	};

	$("#smpl_grid_check").change(function(){
		if($(this).is(":checked")){
			smpl.addGridLayer();
	  }else{
	  	smpl.canvas.removeLayer("Grid").drawLayers();
	  }
	});	

	smpl.addGridLayer = function(){
    canvas.drawPath({
      type: 'drawpath',
      name: "Grid",
      layer: true,
      strokeStyle: smpl.stroke,
      strokeWidth: smpl.grid.strokew,
      strokeDash:[3,3],
    }).drawLayers();
      
    //Change the order of layers
    let layers = smpl.canvas.getLayers();
    let imax = layers.length-1;
    for(let i = imax; i>0; i--){
    	smpl.canvas.moveLayer(i,i+1);
    }
    smpl.canvas.moveLayer("Grid",1);

    let grid = {};
    let i=1;
    for(let x= 0 ; x < smpl.width; x+= 2 * smpl.grid.dx ){
      grid["p"+i] = { type: "line", 
          x1: x,    y1: 0, 
          x2: x,    y2: smpl.height-1,
          x3: x + smpl.grid.dx, y3: smpl.height-1,
          x4: x + smpl.grid.dx, y4: 0,
          x5: x + 2 * smpl.grid.dx, y5: 0,
      }
      i++;
    }
      
    for( let y = 0 ; y< smpl.height; y += smpl.grid.dy ){
      grid["p"+i] = { type: "line", x1: 0, y1: y, x2: smpl.width-1, y2: y }
      i++;
    }
    smpl.canvas.setLayer("Grid",grid).drawLayers();
	};
})(jQuery, smpl);