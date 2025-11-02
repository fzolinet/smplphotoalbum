/**
 * Initialize/change/drop the layers
 */
(function($, Drupal, smpl ){	

/**
 * Change the type of layer
 * @param string type - type of layer
 * @param boolean on - switch on/off 
 */
	smpl.ChangeLayer= function(type, on){
		if(smpl.isUndefined( on ) ){
			on = true;
		}

		switch(type){
			case "Rectangle" : 				
				smpl.canvas.removeLayer("Distortion");
				smpl.canvas.removeLayer("Ellipse");
				smpl.canvas.removeLayer("Circle");	
				smpl.addRectangleLayer(smpl.rect); 
				break;
			
			case "Distortion":
				smpl.canvas.removeLayer("Ellipse");
				smpl.canvas.removeLayer("Circle");
				smpl.canvas.removeLayer("Rectangle");
				smpl.addDistortionLayer(smpl.dist); 
				break;
			
			case "Ellipse": 
				smpl.canvas.removeLayer("Distortion");
				smpl.canvas.removeLayer("Circle");
				smpl.canvas.removeLayer("Rectangle");
				smpl.addEllipseLayer(smpl.rect); 
				break;

			case "Circle": 
				smpl.canvas.removeLayer("Distortion");
				smpl.canvas.removeLayer("Ellipse");
				smpl.canvas.removeLayer("Rectangle");
				smpl.addCircleLayer(smpl.circ); 
				break;  
				
			/*case "Eyedrop":
			case "Point":
				if(on){
					smpl.addPointLayer(smpl.point);
				}else{
					smpl.canvas.removeLayer("Point").drawLayers();
				}
				break;
				*/
			case "Grid":
				if(on){
					smpl.addGridLayer();
				}else{
					smpl.canvas.removeLayer("Grid").drawLayers();
				}
				break;
			
			default : 
				smpl.canvas.removeLayer("Distortion");
				smpl.canvas.removeLayer("Rectangle");	
				smpl.canvas.removeLayer("Ellipse");
				smpl.canvas.removeLayer("Circle");
				smpl.canvas.removeLayer("Point");
				smpl.canvas.removeLayer("Grid");
				smpl.canvas.drawLayers(); 
				type = ""; 
				break
			}
			//
			if( type != ""){
				smpl.layer_area(type, on );
			}		
	}

	/**
	 * div#smpl_xxx_area change show/hide
	 * @param string h - layer type
	 * @param boolean add - only add not hide the others
	 * @returns
	 */
	smpl.layer_area = function(h, add){
		if(smpl.isUndefined(add)){
			//$("#smpl_rect_area, #smpl_point_area, #smpl_circle_area, #smpl_dist_area").hide();	
			$("#smpl_rect_area, #smpl_circle_area, #smpl_dist_area").hide();	
		}
		
		switch(h){
			case "Rectangle":
			case "Ellipse": 
				$("div#smpl_rect_area").show(); 
				break;

/*			case "Eyedrop":
				$("div#smpl_eyedrop_area").show();
			case "Point": 
				$("div#smpl_point_area").show(); 
				break;
*/
			case "Circle": 
				$("div#smpl_circle_area").show(); 
				$("div#smpl_rect_area").show();
				$("#smpl_aspect").prop("checked", true);
				break;

			case "Distortion": 
				$("div#smpl_dist_area").show(); 
				break;

			case "Grid": 
				$("div#smpl_grid_area").show(); 
				break;

			default : 
				break;
		}
	}

	// color methods
	smpl.ChangeRed = function( red, rgbcolor ){
		let r = parseInt( red ).toString(16);    
		return "#" + ("00"+r).slice(-2) + rgbcolor.substr(3);		
	}
		
	smpl.ChangeGreen = function ( green, rgbcolor){
		let g = ( parseInt(green) ).toString(16);
		return rgbcolor.substr(0,3) + ("00" + g).slice(-2) + rgbcolor.substr(5);
	}
	
	smpl.ChangeBlue = function (blue, rgbcolor){
		let b = ( parseInt( blue ) ).toString(16); 
		return rgbcolor.substr(0,5) + ("00" + b).slice(-2);
	} 

	smpl.DecToHex = function(red,green,blue) {		
		let r = parseInt( red ).toString(16);    
		let g = parseInt( green ).toString(16);    
		let b = parseInt( blue ).toString(16);		
		return "#" + r + g + b;		
	}
	
})(jQuery, Drupal, smpl);	