/**
 * Ellipse layer
 */
(function($, Drupal, smpl ){	
  smpl.addEllipseLayer = function(p)
  {
  	$("#smpl_x1").val( Math.round( p.x - (p.width-5 ) / 2 ) );
    $("#smpl_x2").val( Math.round( p.x + (p.width-5 ) / 2 ) );
    $("#smpl_y1").val( Math.round( p.y - p.height / 2 + 5 ) );
    $("#smpl_y2").val( Math.round( p.y + p.height / 2 - 5 ) );
      
    smpl.canvas.addLayer({
        type: 'ellipse',
        name: "Ellipse",
        strokeStyle: smpl.stroke,
        strokeWidth: smpl.strokew,
        strokeDash:[5,5],
        x: p.x, 
        y: p.y,
        width:  p.width - 10, 
        height: p.height - 10,
        resizeFromCenter: false,
        constrainProportions: false,
        draggable: true,
        drag: function(layer){
          smpl.rect.setRect( layer.x, layer.y, layer.width, layer.height);
          smpl.rectChange(layer.x-layer.width/2, layer.y-layer.height/2, layer.x + layer.width/2, layer.y + layer.height /2);
        },
        handlePlacement: 'both',
        handle:{
          type: "arc",
          fillStyle: smpl.fillStyle,
          strokeStyle: smpl.stroke,
          strokeWidth: smpl.strokew,
          radius: smpl.hradius
        }, 
        handlemove: function(layer){
        	smpl.rect.setRect( layer.x, layer.y, layer.width, layer.height);
        	smpl.rectChange(layer.x-layer.width/2, layer.y-layer.height/2, layer.x + layer.width/2, layer.y + layer.height /2);
        },
      })
      .drawLayers();
  }
})(jQuery, Drupal, smpl);