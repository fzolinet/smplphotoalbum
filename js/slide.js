/**
 * Slideshow js
 */
(function ($, Drupal, smpl, smplslide) {
	var container = $("img#smplslideimgcont");	// Container
	var img = $("img.smplslideimg");						// Image  	    
	smplslide.lastcmd = "next";

	// previous image
	$("#smplup, #smplprev").click(function () {
		smplslideload("up", "-1");
	});

	//Next image
	$("#smplnext, #smpldown").click(function () {
		smplslideload("next", "-1");
	});

	/**
	 * Click on the thumbnail
	 */
	$(".smplslidetnimg").click(function () {
		smplslide.id = $(this).attr("data-id");
		smplslideload("click", smplslide.id);
	});

	/**
	 * Stop / restart the setinterval
	 */
	$("img.smplslideimg").on("mouseenter", function () {
		clearInterval(smplslide.timer);
		smplslide.timer = false;
	}).on("mouseleave", function () {
		smplslide.timer = setInterval( smplslideload, smplslide.interval, smplslide.lastcmd, -1 );		
	});

	/**
   * Load an image and the thumbnails
   * @parameter string cmd
   * @parameter string|int id
   */
	function smplslideload(cmd, id) {
		// If the image loaded by click, the timer stop
		console.log(smplslide.timer);
		clearInterval(smplslide.timer);
		smplslide.timer = false;
		smplslide.lastcmd = cmd;
		let url = smplslide.ajax + "/slideget/" + cmd;

		if (id != "-1") {
			url += "?id=" + id;
		}

		smpl.progress(true);
		
		fetch(url)
			.then(response => response.json())
			.then(data => {
				data = JSON.parse(data[0].data);
				smpl.progress(false);
				if (data.id != -1) {
					smplslidewrite(data, img, cmd);
					smplslide.timer = setInterval(smplslideload, smplslide.interval, smplslide.lastcmd, -1);
				} else {
					smpl.ErrorC(data.error);
				}
			}).catch(error => function (error) {
				smpl.progress(false);
				ShowButtons();
				smpl.ErrorC(error.responseText);
			});
	}

	/**
	 * Write the images to the web page
	 * @param {*} data 
	 * @param {*} img 
	 * @param {*} cmd 
	 */
	function smplslidewrite(data, img, cmd) {
		smplslide.width = data.width;
		smplslide.height = data.height;
		smplslide.id = data.id;
		smplslide.name = data.name;
		smplslide.subtitle = data.subtitle;
		smplslide.alt = data.alt;
		smplslide.path = data.path;
		smplslide.ths = data.ths;

		container.attr("id", "smplslide" + smplslide.id);
		img.prop("id", "smplslide" + smplslide.id);

		// alter mode comes the next image 
		let st = "";
		if (smplslide.slidestyle == "random") {
			st = smplslide.styles[Math.floor(Math.random() * smplslide.styles.length)];
			if (st == "slide") {
				var cmds = ['up', 'down', 'next', 'prev'];
				cmd = cmds[Math.floor(Math.random() * cmds.length)];
			}
		} else {
			st = smplslide.slidestyle;
		}

		// TODO - optimize the code, there is a lot of repetition
		// TODO - more type of animation
		switch (st) {
			case 'fade': img.fadeTo(smplslide.eftime, 0); imgAttrChange(); img.fadeTo(smplslide.eftime, 1); break;
			case 'animate':
			case 'slide':
				switch (cmd) {
					case "next": var t = ['translateX(0)', 'translateX(-100%)', 'translateX(100%)', 'translateX(0)']; break;
					case "prev": var t = ['translateX(0)', 'translateX(100%)', 'translateX(-100%)', 'translateX(0)']; break;
					case "up":   var t = ['translateY(0)', 'translateY(100%)', 'translateY(-100%)', 'translateY(0)']; break;
					case "down": var t = ['translateY(0)', 'translateY(-100%)', 'translateY(100%)', 'translateY(0)']; break;
				}

				const img1 = document.getElementsByClassName("smplslideimg")[0];
				const animation1 = img1.animate(
					[{ transform: t[0] }, { transform: t[1] }],
					{ duration: 2 * smplslide.eftime, fill: 'forwards' }
				);

				animation1.finished.then(() => {
					imgAttrChange();	
					const animation2 = img1.animate(
						[{ transform: t[2] }, { transform: t[3] }],
						{ duration: 2 * smplslide.eftime, fill: 'forwards' }
					);
				});
				break;
			case 'zero': img.hide(); imgAttrChange(); img.show(); break;
			default: case 'zero': img.hide(); imgAttrChange(); img.show(); break;
		}

		SmplTnWrite();
	}

	// Change the image attributes
	function imgAttrChange() {
		img.attr("src", smplslide.linksrc + smplslide.id);
		img.attr("data-link", smplslide.linksrc + smplslide.id);
		img.attr("title", smplslide.subtitle);
	}

	// Write the thumbnails
	function SmplTnWrite() {
		for (let i = 0; i < 9; i++) {
			$("#smplslidetnid" + i)
				.attr("title", smplslide.ths[i].name)
				.attr("alt", smplslide.ths[i].subtitle)
				.attr("data-id", smplslide.ths[i].id)
				.attr("src", smplslide.linksrc + smplslide.ths[i].id + "?tn=1")
				.attr("data-link", smplslide.linksrc + smplslide.ths[i].id + "?tn=1");
		}
	}
	// Start the slideshow
	smplslide.timer = setInterval(smplslideload, smplslide.interval, smplslide.lastcmd, -1);

})(jQuery, Drupal, smpl, smplslide);
