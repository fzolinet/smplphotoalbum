//---------------------- Circle Layer -------------
(function($, Drupal, smpl ){	
	smpl.circ = {
			x: 1,
			y: 1,
			r: Math.round( ( Math.min(smpl.width, smpl.height) - 20 ) / 2 ),
			radius: smpl.hradius,
			setCirc: function (x, y, r){
				this.x = x;
				this.y = y;
				this.r = r;
				smpl.rect.setCirc(x, y, r);
			}, 
			width: function () {
				return 2*this.r;
			},
			height: function (){
				return 2*this.r;
			},
	};
	
	$("#smpl_x, #smpl_y, #smpl_radius").change(function(){
      let x = parseInt( $("#smpl_x").val() );
      let y = parseInt( $("#smpl_y").val() );
      let r = parseInt( $("#smpl_radius").val() );
      smpl.circ.setCirc(x, y, r);
      
      if(smpl.canvas.getLayer("Circle")){
      	smpl.canvas.setLayer("Circle", 
        {
          x: smpl.circ.x, 
          y: smpl.circ.y, 
          width:  smpl.circ.width(), 
          height: smpl.circ.height()
        }).drawLayers();
      }
      smpl.CircleChange(x, y, r);
    });  
  
	smpl.addCircleLayer = function( p ) {
      $("#smpl_x").val( Math.round( p.x ) );
      $("#smpl_y").val( Math.round( p.y ) );
      $("#smpl_radius").val( Math.round( p.radius ) );      
   
      smpl.canvas.addLayer({
          type: 'ellipse',
          name: "Circle",
          strokeStyle: smpl.stroke,
          strokeWidth: smpl.strokew,
          strokeDash:[5,5],
          x: p.x, 
          y: p.y,
          width:  2*(p.r-10), 
          height: 2*(p.r-10),
          resizeFromCenter: true,
          aspectRatio: 1,
          constrainProportions: true,
          draggable: true,
          drag: function(layer){
          	smpl.circ.setCirc( layer.x, layer.y, layer.width / 2 );                         
          	smpl.CircleChange(layer.x, layer.y, layer.width / 2);
          },
          handlePlacement: 'both', //'both', 'corners'
          handle: {
            type: "arc",
            fillStyle: smpl.fillStyle,
            strokeStyle: smpl.stroke,
            strokeWidth: smpl.strokew,
            radius: smpl.hradius
           }, 
           handlemove: function(layer) {
          	 smpl.circ.setCirc( layer.x, layer.y, layer.width / 2 );
          	 smpl.CircleChange(layer.x, layer.y, layer.width / 2);
           },
      }).drawLayers();
  };

  smpl.CircleChange = function(x, y, r){
    $("#smpl_x").val(x);
    $("#smpl_y").val(y);
    $("#smpl_radius").val(r);
    smpl.rectChange(x-r, y-r, x+r, y+r);
  }
  
	 smpl.rectChange = function(x1, y1, x2, y2)
	 {
	     x1 = Math.round(x1);
	     y1 = Math.round(y1);
	     x2 = Math.round(x2);
	     y2 = Math.round(y2);
	     $("#smpl_x1").val(x1);
	     $("#smpl_y1").val(y1);
	     $("#smpl_x2").val(x2);
	     $("#smpl_y2").val(y2);

	     //image size
	     $("#smpl_w").val( smpl.width );
	     $("#smpl_h").val( smpl.height );

	     // width & height in px and %
	     let dx = x2-x1;
	     let dy = y2-y1;      
	     $("#smpl_wh").val( dx + " x " + dy + "px ");
	     $("#smpl_whp").val( Math.floor( 100*dx / smpl.width) + " x " + Math.floor( 100 * dy / smpl.height) + "% ");
	 }

})(jQuery, Drupal, smpl);