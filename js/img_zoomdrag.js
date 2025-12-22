/**
 * Zoom
*  click, drag, zoom on Image 
* ******************** Roblouie **************************************
* https://roblouie.com/article/617/transforming-mouse-coordinates-to-canvas-coordinates/
* jsFiddle: https://jsfiddle.net/rlouie/6q94pyau/2/
*/
(function ($, smpl) {

	//Zoom
	var zoomMin = 0.09;	    	// zoom min in %
	var zoomMax = 1001;	    	// zoom max in % 
	var zoomDefault = 1.25;		// default ratio of changing zoom   

	smpl.zoomenable = true;  	// can i use the zoom
	smpl.rel = 1.25;	  // Zoom in / out relative value;
	smpl.zoomBak = 1;		  // last zoom value (%)
	smpl.zoom = 1;		  // new zoom value (%)
	smpl.startX = 0;			// position of click on image
	smpl.startY = 0;
	smpl.isMousedown = false; // mouse button is down
	smpl.isPanning = false;   // panning

	smpl.dragstart = { x: 0, y: 0, z: 0, w: 1 }; // position of start dragging 
	smpl.isdragging = false;        // is dragging now
	smpl.tp = { x: 0, y: 0, z: 0, w: 1 }; // Transformed cursor position, top left position
	smpl.layer = { x: 0, y: 0 };

	/**
	 * Transform the coordinates of event to canvas coordinates
	 * a,d - scale
	 * e,f - translated coordinates
	 * @parameter number x
	 * @parameter number y
	 * 	
	 * return object (number x, number y) 
	 */

	smpl.getTransformedPoint = function (x, y) {
		return getTransformedPoint(x, y);
	};

	function getTransformedPoint(x, y) {
		//Original coordinates
		const p = new DOMPoint(x, y);
		const q = smpl.ctx.getTransform().invertSelf().transformPoint(p);
		//Transformed coordinates
		q.x = Math.round(q.x);
		q.y = Math.round(q.y);
		return q;
	}

	/**
	 * Zoom enable / disable
	 */
	$("#smpl_zoom_enable").change(function () {
		zoomenable = $(this).is(":checked");
		$("input#smpl_zoom").prop("disabled", !zoomenable);
		$("button#smpl_orig").prop("disabled", !zoomenable);
	});

	/**
		* Keyboard events
		*/
	// body - nem jó	
	if ($("#SmplImgEditForm").length > 0) {

		$("body").keydown(function (e) {
			smpl.ctrlDown = e.ctrlKey ? true : false;

			if (!(smpl.zoomenable || smpl.ctrlDown)) return;
			var imgeditform = $("div#SmplImgEditForm").is(":visible");

			if (!imgeditform) return;
			switch (e.keyCode) {
				case 37: 	// left arrow
					Translate(-1, false);
					e.preventDefault();
					break;
				case 39:  // right arrow
					Translate(1, false);
					e.preventDefault();
					break;
				case 38: 	// Up arrow
					Translate(false, -1);
					e.preventDefault();
					break;
				case 40:	// down' arrow
					Translate(false, 1);
					e.preventDefault();
					break;
				case 107: 	// +jel
					if (smpl.zoom < zoomMax) {
						smpl.zoom *= zoomDefault;
						$("input#smpl_zoom").val(Math.floor(100 * smpl.zoom));
						ZoomCanvas(smpl.zoom, e.type);
					}
					break;
				case 109:   // -jel
					if (smpl.zoom > zoomMin) {
						smpl.zoom /= zoomDefault;
						$("input#smpl_zoom").val(Math.floor(100 * smpl.zoom));
						ZoomCanvas(smpl.zoom, e.type);
					}
					break;
			}
		});

		$("body").keyup(function (e) {
			smpl.ctrlDown = e.ctrlKey ? true : false;
		});
	}

	/**
	 * Translate
	 * @param {*} x - relative translate not absolute
	 * @param {*} y 
	 */
	function Translate(x, y) {
		if (!x && !y) return;
		if (x) smpl.tp.x += x;
		if (y) smpl.tp.y += y;
		//console.log("x: "+ x.toString() + ", y: " + y.toString());
		smpl.canvas.translateCanvas({	//relative translate
			translateX: (x ? x : 0),
			translateY: (y ? y : 0),
		});
		smpl.canvas.drawLayers();
		smpl.canvas.restoreCanvas();
	}

	/**
	 * Wheel zoom
	 * https://github.com/jquery/jquery-mousewheel
	 * 
	 * @return boolean
	 */
	smpl.canvas.mousewheel(function (e) {
		if (!(smpl.zoomenable || smpl.ctrlDown)) return false;

		// mouse nagyít
		if ((e.deltaY < 0) && (smpl.zoom > zoomMin)) {
			smpl.zoom /= zoomDefault;
			smpl.canvas.css("cursor", "zoom-out");
		}

		// mouse kicsinyít
		if ((e.deltaY > 0) && (smpl.zoom < zoomMax)) {
			smpl.zoom *= zoomDefault;
			smpl.canvas.css("cursor", "zoom-in");
		}

		$("input#smpl_zoom").val(Math.floor(100 * smpl.zoom));

		ZoomCanvas(smpl.zoom, e);
		e.preventDefault();
		clearTimeout($.data(this, "smpl_timer"));
		$.data(this, "smpl_timer", setTimeout(function () {
			smpl.canvas.css("cursor", "default");
		}, 250));
	});

	/**
	 * Mousewheel is over
	 */
	smpl.canvas.unmousewheel(function (e) {
		smpl.canvas.css("cursor", "default");
	});

	/**
	 * CLick on image
	 * .offsetX / Y - távolság a canvas szélétől
	 */
	smpl.canvas.on("mousedown", function (e) {
		// distance of mouse position and top / left of border of canvas 		
		smpl.dragstart.x = e.offsetX;
		smpl.dragstart.y = e.offsetY;
		smpl.startX = (e.offsetX) - smpl.tp.x;
		smpl.startY = (e.offsetY) - smpl.tp.y;
		smpl.isMousedown = true;
		smpl.isPanning = false;
	});

	/**
	 * Dragging The mouse
	 * e.pageX / e.pageY - távolság a dokumentum szélétől
	 * e.offsetX / e.offsetY - távolság a canvas szélétől
	 * screenX / screenY - A viewport távolság koordinátái
	 */
	smpl.canvas.on("mousemove", function (e) {
		if (!smpl.isMousedown || !smpl.ctrlDown) return;

		dx = (e.offsetX - smpl.dragstart.x) / smpl.zoom;
		dy = (e.offsetY - smpl.dragstart.y) / smpl.zoom;
		Translate(dx, dy);
		smpl.dragstart.x = e.offsetX;
		smpl.dragstart.y = e.offsetY;
	});

	function log(e) {
		var ki = "pageX/Y: " + e.pageX + ", " + e.pageY + "\n";
		ki += "screenX/Y: " + e.screenX + ", " + e.screenY + "\n";
		ki += "offsetX/Y: " + e.offsetX + ", " + e.offsetY + "\n";
		console.log(ki);
	}

	/**
		 * click over on image
	 */
	smpl.canvas.on("mouseup", function () {
		smpl.isdragging = false;
		smpl.isMousedown = false;
		smpl.canvas.css("cursor", "default");
	});

	/**
	 * Change The Zoom with input field
	 */
	$("input#smpl_zoom").change(function () {
		smpl.zoom = (parseInt($(this).val()) / 100);
		if ((smpl.zoom > zoomMin) && (smpl.zoom < zoomMax)) {
			ZoomCanvas(smpl.zoom, false);
		}
		smpl.canvas.css("cursor", "default");
	});

	// Origin size & place
	$("#smpl_orig").click(function () {
		smpl.ZoomDefault();
	});

	/**
	 * Default Zoom size && position
	 */
	smpl.ZoomDefault = function () {
		smpl.zoom = 1;
		$("#smpl_zoom").val(Math.floor(100 * smpl.zoom));

		//scale reset
		smpl.rel = smpl.zoom / smpl.zoomBak;
		smpl.canvas.scaleCanvas({ scale: smpl.rel });
		smpl.zoomBak = smpl.zoom;
		smpl.canvas.drawLayers();

		// transform reset
		smpl.ctx.resetTransform();
		smpl.tp = { x: 0, y: 0, z: 0, w: 1 };
		smpl.dragstart = smpl.tp;
		smpl.canvas.css("cursor", "default");
		drawImageToCanvas();
	}

	/**
	 * Change the zoom
	 * @param float z - zoom
	 * @param boolean | event e 
	 * @returns
	 */
	function ZoomCanvas(z, e) {
		smpl.rel = z / smpl.zoomBak;

		//Called by mouse wheel		

		if (e.type == "mousewheel" && e.type) {
			dx = (e.offsetX / z) - (e.offsetX / smpl.zoomBak);
			dy = (e.offsetY / z) - (e.offsetY / smpl.zoomBak);
			Translate(dx, dy);
		} else if (e == "keydown") {
			dx = (smpl.tp.x / z) - (smpl.tp.x / smpl.zoomBak);
			dy = (smpl.tp.y / z) - (smpl.tp.y / smpl.zoomBak);
			Translate(dx, dy);
		}
		smpl.canvas.scaleCanvas({ scale: smpl.rel });
		smpl.zoomBak = z;
		smpl.canvas.drawLayers();
		smpl.canvas.restoreCanvas();
	}

	// Ezt lehet, hogy nem így kell, mint az eredetiben!!!!!!!!!!!!!!!
	/**
	 * vizSkala    = 1; // horizontal scale - vízszintes skála
	 * vizFerde    = 0; // horizontal skewing - vízszintes ferdeség
	 * fuggFerde   = 0; // vertical skeving - függőleges ferdeség
	 * fuggSkala   = 1; // vertical scale - függőleges skála
	 * vizEltolas  = 0; // horizontal translation - eltolás
	 * fuggEltolas = 0; // vertical translation - függőleges eltolás
	 * @returns
	 */
	function drawImageToCanvas() {
		smpl.canvas.moveLayer("Image", 100);
		// 
		let vizSkala = 1; // horizontal scale - vízszintes skála
		let vizFerde = 0; // horizontal skewing - vízszintes ferdeség
		let fuggFerde = 0; // vertical skeving - függőleges ferdeség
		let fuggSkala = 1; // vertical scale - függőleges skála
		let vizEltolas = 0; // horizontal translation - eltolás
		let fuggEltolas = 0; // vertical translation - függőleges eltolás
		smpl.ctx.setTransform(vizSkala, vizFerde, fuggFerde, fuggSkala, vizEltolas, fuggEltolas);
		smpl.ctx.clearRect(0, 0, smpl.width, smpl.height); 	// Törli a területet és átlátszóvá teszi																	
		smpl.ctx.drawImage(smpl.image, 0, 0, smpl.width, smpl.height);
		smpl.canvas.moveLayer("Image", 0);
	}

})(jQuery, smpl);