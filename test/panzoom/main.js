(function ($) {
$(document).ready(function () {

var $canvas = $('#canvas');
var $cursorCoords = $('#cursor-coords');

var canvasWidth = $canvas.width();
var canvasHeight = $canvas.height();

var translateX = 0;
var translateY = 0;
var scale = 1;

var startX;
var startY;
var isMousedown;
var isPanning;
var shapeSize = 100;
var nearestAmount = shapeSize / 4;

function applyTransformations() {

	// Create a layer for translatening the canvas
	$canvas.scaleCanvas({
		layer: true,
		name: 'scale',
		scale: scale
	});
	$canvas.translateCanvas({
		layer: true,
		name: 'translate',
		translateX: translateX,
		translateY: translateY
	});

}

function updateGridBackground() {
	$canvas.css({
		backgroundSize: (scale * nearestAmount * 2) + 'px ' + (scale * nearestAmount * 2) + 'px',
		backgroundPosition: (scale * translateX) + 'px ' + (scale * translateY) + 'px'
	});
}

function drawShapes() {

	$canvas.drawRect({
		layer: true,
		opacity: 0.5,
		fillStyle: '#36c',
		x: 0, y: 200,
		width: shapeSize, height: shapeSize
	});
	$canvas.drawRect({
		layer: true,
		opacity: 0.5,
		fillStyle: '#6c3',
		x: 200, y: 200,
		width: shapeSize, height: shapeSize
	});
	$canvas.drawRect({
		layer: true,
		opacity: 0.5,
		fillStyle: '#c33',
		x: 400, y: 200,
		width: shapeSize, height: shapeSize
	});

}

function restoreTransformations() {

	// This is essential for restoring translate on each draw
	$canvas.restoreCanvas({
		layer: true,
		name: 'restore',
		count: 2
	});

}

function draw() {

	applyTransformations();
	drawShapes();
	restoreTransformations();
	updateGridBackground();
}

function setScale(event, newScale, oldScale) {

	scale = newScale;
	translateX += (event.offsetX / newScale) - (event.offsetX / oldScale);
	translateY += (event.offsetY / newScale) - (event.offsetY / oldScale);

	$canvas.setLayer('scale', {
		scale: scale
	});
	$canvas.setLayer('translate', {
		translateX: translateX,
		translateY: translateY
	});
	$canvas.drawLayers();
	updateGridBackground();

}

function addEventBindings() {

	// Add translatening behavior to canvas
	$canvas.on('mousedown', function (event) {
		startX = (event.offsetX / scale) - translateX;
		startY = (event.offsetY / scale) - translateY;
		isPanning = false;
		isMousedown = true;
	});
	$canvas.on('mousemove', function (event) {
		if (isMousedown) {
			isPanning = true;
			translateX = (event.offsetX / scale) - startX;
			translateY = (event.offsetY / scale) - startY;
			$canvas.setLayer('translate', {
				translateX: translateX,
				translateY: translateY
			});
			$canvas.drawLayers();
			updateGridBackground();
		}
	});

	var translateIndex = 0;
	$canvas.on('mouseup', function (event) {
		isMousedown = false;
		if (!isPanning) {
			var oldScale = scale;
			var newScale = oldScale + 1;
			setScale(event, newScale, oldScale);
		}
	});

	$canvas.on('wheel', function (event) {
		var originalEvent = event.originalEvent;
		event.preventDefault();
		var deltaY = 0;
		if (originalEvent.deltaY) { // FireFox 17+ (IE9+, Chrome 31+?)
			deltaY = originalEvent.deltaY;
		} else if (originalEvent.wheelDelta) {
			deltaY = -originalEvent.wheelDelta;
		}
		var oldScale = scale;
		var newScale;
		if (deltaY < 0) {
			newScale = oldScale * (1 + 0.05);
		} else {
			newScale = oldScale * (1 - 0.05);
		}
		setScale(event, newScale, oldScale);
	});

}

addEventBindings();
draw();

});
}(jQuery));
