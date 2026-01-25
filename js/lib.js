/**
 * Show information window
 * @param string data
 * @param boolean show
 * @returns
 */
function fz_t(data, show = -1) {
  let d;

  if (show == false) {
    smpl.setLocalStorage("fz_test", false);
  } else if (smpl.getLocalStorage() == "true") {
    show = true;
  } else {
    show = false;
  }

  if (typeof data === "object") {
    d = JSON.stringify(data);
  } else {
    d = data;
  }

  let e = document.querySelector('#smpl_error');
  let ec = document.querySelector('#smpl_error_content');
  d = d.replace('<em class="placeholder">', '<b>');
  d = d.replace('</em>', '</b>')
  ec.innerHTML += d;
  if (show) {
    e.style.display = 'block';
  } else {
    e.style.display = 'none';
  }
}

/**
  * Filename and extension validation
  * @param {string} fname 
  * @param {string} ext 
  * @return
  */
smpl.Validation = function (fname, ext) {
  // Too short
  if (fname.length < 1) return false;
  if (ext) {
    let ar = fname.split(".");
    fname = ar[0];
  }
  //
  const disabled = " .<>|([]{},\/áéíóöőúüűÁÉÍÓÖŐÚŰ ";
  let i = 0;
  while (i < disabled.length && !fname.includes(disabled.substring(i, i + 1))) {
    i++;
  }
  if (i < disabled.length) return false;

  // Dont check the extension
  if (typeof ext === undefined || !ext) return true;

  //extension checking    
  let extension = (ar[ar.length - 1]).toLowerCase();
  return smpl.extensions.includes(extension);
}

/**
 * Get cookie
 * @returns false
 */
smpl.getcookie = function (name) {
  if (smpl.isUndefined(name)) {
    name = "fz_test";
  }
  let nameEQ = name + "=";
  let ca = document.cookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
  }
  return false;
}


/**
 * Setcookie
 * @param {*} value - cookie
 */
smpl.setcookie = function (key, value) {
  if (smpl.isUndefined(key)) {
    key = "fz_test";
  }
  let expires = "";
  document.cookie = key + "=" + value;
}

/**
 * Using of local Storage
 * @param string key
 * @param mixed value
 * @return void
 */
smpl.setLocalStorage = function (key, value) {
  if (!smpl.isUndefined(value)) {
    localStorage.setItem(key, value);
  } else {
    localStorage.removeItem(key, value);
  }

}

smpl.getLocalStorage = function (key) {
  return localStorage.getItem(key);
}

/**
  * Determine if an operand undefined
  * @param {Undefined} op 
  * @return 
  */
smpl.isUndefined = function (h) {
  return typeof h === "undefined";
}

/**
 * Setting the window on the display
 * @param object w - windows
 * @param obj pos - position of window
 * @return
 */
function setDivInWindow(w, pos) {
  w.css("display", "flex");
  w.css("position", "absolut");
}

/**
 * Innen a jQuery lib-ek
 */
(function ($, Drupal, smpl, Swal) {
  /**
   * Progress indicator on/off
   * @param Boolean on 
   * @return
   */
  smpl.progress = function (on, timer) {
    $(".ajax-progress-fullscreen").remove();

    if (!smpl.isUndefined(on) && on) {
      if (!smpl.isUndefined(timer) && timer) {
        smpl.timer = "0%";
        $("#smpl_progress").text(smpl.timer);
        $("#smpl_progress").show();
      }

      $('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
      $('body').css("cursor", "progress");

      if (smpl.timer === "0%" && !smpl.isUndefined(timer) && timer) {
        //
        // https://stackoverflow.com/questions/29246444/fetch-how-do-you-make-a-non-cached-request
        //
        smpl.timer = setInterval(function () {
          fetch(smpl.signurl, { cache: "no-cache" })
            .then(response => response.text())
            .then(data => {
              $("#smpl_progress").text(data);
            })
            .catch(error => console.error("error:", error));
        }, 2000);
      }
      return true;
    } else {
      $('body').css("cursor", "default");
      $("#smpl_progress").hide();
      clearInterval(smpl.timer);
      smpl.timer = "0%";
    }
    return false;
  };

  //1977_temp_0_sign.txt
  /**
   * 
   * @param {*} color 
   * @param {*} rr 
   * @param {*} rg 
   * @param {*} rb 
   * @returns string
   */
  smpl.colorBrightness = function (color, rr, rg, rb) {
    var r, g, b;
    if (color.match(/^rgb/)) {
      color = color.match(/rgba?\(([^)]+)\)/)[1];
      color = color.split(/ *, */).map(Number);
      r = color[0];
      g = color[1];
      b = color[2];
    } else if ('#' == color[0] && 7 == color.length) {
      r = parseInt(color.slice(1, 3), 16);
      g = parseInt(color.slice(3, 5), 16);
      b = parseInt(color.slice(5, 7), 16);
    } else if ('#' == color[0] && 4 == colour.length) {
      r = parseInt(color[1] + color[1], 16);
      g = parseInt(color[2] + color[2], 16);
      b = parseInt(color[3] + color[3], 16);
    }
    brightness = (r * 299 + g * 587 + b * 114) / 1000;
    if (brightness < 125) {
      r = Math.floor(r + rr);
      g = Math.floor(g + rg);
      b = Math.floor(b + rb);
    } else {
      r = Math.floor(r - rr);
      g = Math.floor(g - rg);
      b = Math.floor(b - rb);
    }
    return "rgb(" + r + "," + g + "," + b + ")";
  };

  /**
   * AlerC - Javascript messages
   * @param {*} cmd - message
   * @param {*} status - status code
   */
  smpl.AlertC = function (cmd, status) {
    var cl;
    switch (status) {
      case 'error': cl = "smpl-message-error"; title = "Error message"; break;
      case 'warning': cl = "smpl-message-warning"; title = "Warning message"; break;
      case 'status': cl = "smpl-message-status"; title = "Status message"; break;
    }

    // Sweetalert2 lib
    Swal.fire({
      title: title,
      text: cmd,
      showConfirmButton: true,
      confirmButtonText: smpl.words.Confirm,
      closeOnClickOutside: true,
      closeOnEsc: true,
      dangerMode: true,
    });
    $("div.swal-modal").removeClass("smpl-message-error");
    $("div.swal-modal").removeClass("smpl-message-warning");
    $("div.swal-modal").removeClass("smpl-message-notes");
    $("div.swal-modal").addClass(cl);
  }
  /* 
   * Warning popup box
   */
  smpl.ConfirmC = function (cmd) {
    smpl.AlertC("Warning: " + cmd, 'warning');
  }

  /**
    * ErrorC - Error alert
    * @param {*} cmd
    */
  smpl.ErrorC = function (cmd) {
    smpl.AlertC("Error on server side: " + cmd, 'error');
  }

  // Draggable window
  $('#smpl-message').draggable();

  // ImageBox
  smpl.ImageBox = function (link, alias) {
    Swal.fire({
      imageUrl: link,
      imageAlt: alias,
      showConfirmButton: true,
      showDenyButton: false,
      showCancelButton: true,
      confirmButtonText: smpl.words.OpenInNewTab,
      cancelButtonText: smpl.words.Close,
      draggable: true
    }).then((result) => {
      if (result.isConfirmed) {
        window.open(link, "_blank");
      } else {

      }
    });
  }
})(jQuery, Drupal, smpl, Swal);