/**
 * Point layer
 */
(function($, Drupal, smpl ){	
	
  // definition of a point
  smpl.point={
	  x: parseInt(smpl.width / 2),
	  y: parseInt(smpl.height / 2),    
	  radius: smpl.hradius + 10,
	  strokew:1,
	  setPoint: function(x,y){
	    this.x = x;
	    this.y = y;
	  }
	};

	/**
   * Change coordinates of point
   */
	$("#smpl_xp, #smpl_yp").change( function(){
	    var x = parseInt( $("#smpl_xp").val() );
	    var y = parseInt( $("#smpl_yp").val() );
      if( isNaN( x ) ) x = 1;
      if( isNaN( y ) ) y = 1;
           
	    smpl.point.setPoint(x, y);
	    smpl.EyeDropColor(x, y);
	    smpl.canvas.setLayer( "Point", { x: smpl.point.x, y: smpl.point.y } ).drawLayers();
	});

	/**
   * Set The coordinates of point
   * @param float x coordinata
   * @param float y coordinata
   */
  smpl.PointChange = function(x, y){
    $("#smpl_xp").val( x );
    $("#smpl_yp").val( y );
  };
  
  //Add point layer
  smpl.addPointLayer = function(p){
    $("#smpl_xp").val( Math.round(p.x) );
    $("#smpl_yp").val( Math.round(p.y) );

    smpl.canvas.addLayer({
      type: 'arc',
      name: "Point",
      layer: true,
      //strokeStyle: smpl.stroke,
      //strokeWidth: p.strokew,
      strokeDash:[3,3],
      x: p.x, 
      y: p.y, 
      //scale: smpl.zoom,
      radius: smpl.hradius-1,
      bringToFront: true,
      draggable: true,
      click: function(layer){
				smpl.PointChangeImageClick(layer);
			},
    }).drawLayers();  
  }
  /**
   * 
   * @param { Object } layer 
   */
  smpl.PointLayerChange = function(layer){
    smpl.point.setPoint( layer.x, layer.y);
    let hex = smpl.EyeDropColor( layer.x, layer.y );
    hex = smpl.EyeDropInvertColor(hex, true);
    smpl.canvas.setLayer("Point",{ strokeStyle: hex }).drawLayers();
    smpl.PointChange( layer.x, layer.y );
  }
  
  /**
   * 
   * @param {*} layer - amelyik layeren kattant az egér
   */
  smpl.PointChangeImageClick = function(layer){
    let rect = smpl.canvas[0].getBoundingClientRect();
    let rel  = smpl.zoom / 100;
    let x    = Math.round( rel * layer.eventX * smpl.width / rect.width );
    let y    = Math.round( rel * layer.eventY * smpl.height / rect.height );
    //x *= rel;
    smpl.point.setPoint( x, y );
    smpl.EyeDropColor( x, y );
    smpl.canvas.setLayer("Point",{ x: smpl.point.x, y: smpl.point.y }).drawLayers();
    smpl.PointChange( x, y );
  }
})(jQuery, Drupal, smpl);