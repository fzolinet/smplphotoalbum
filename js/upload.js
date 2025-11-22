/**
 * Upload properties of image
 */

(function ($, Drupal, smpl) {
  //Edit button

  /**
   * Upload command to show / hide the Upload form of properties   
   */
  let SmplUploadForm = $("div#SmplUploadForm");

  // Draggable window
  if (SmplUploadForm.length > 0) {
    SmplUploadForm.draggable();
  }

  // Upload Window open
  $("#SmplUpload").click(function (e) {
    let pos = $("#SmplUpload").offset();
    let dy = parseFloat($("html").css("font-size")) * 5;
    SmplUploadForm.show();
    let w = SmplUploadForm.width();
    let ww = window.innerWidth;
    pos.left = (ww - w) * 0.5;
    if (pos.left < 0) {
      pos.left = 0
    } else if (pos.left + w > ww) {
      pos.left = ww - w - 2 * dy;
    }
    SmplUploadForm.offset({ top: pos.top + dy, left: pos.left });
  });

  /**
   * click on cancel button of windows of properties
   * @return false
     */
  $("#SmplUploadClose").click(function (e) {
    SmplUploadForm.hide();
    $("input#smpl_upload_id").val('');
    $("input#smpl_uname").val('');
    $("input#smpl_usub").val('');
    $("select#smpl_utype option").attr("selected", false).change();
    $("select#smpl_utype option[value='image']").attr("selected", "selected").change();
    $("input#smpl_ulink").val('');
    $("input#smpl_uimportance").val(0);
    e.preventDefault();
    return false;
  });

  /**
   * Upload form send to server
   * @return false
   */
  $("#SmplUploadSubmit").click(function () {
    let id = $("input#smpl_uid").val();
    if ($("#smpl_uname").val().length < 1) {
      smpl.AlertC('Client side validation: There is no filename!', 'warning');
      return false;
    }
    let formData = {
      name: $("input#smpl_uname").val(),
      subtitle: $("input#smpl_usub").val(),
      type: $("select#smpl_utype option:selected").val(),
      link: $("input#smpl_ulink").val(),
      importance: $("input#smpl_uimportance").val(),
    };
    smpl.progress(true);

    // client side validation
    var fname = $("#smpl_uname").val();
    if (smpl.Validation(fname)) {
      return true;
    }
    smpl.progress(false);
    smpl.AlertC('Client side validation: Not allowed File type!', 'warning');
    return false;
  });

  /**
   * Validating the uploaded file extension && size
   */
  $("#smpl_uname").change(function () {
    let fname = $(this).val();
    if (fname.length < 1) {
      smpl.AlertC('Client side validation: There is no filename!', 'warning');
      return;
    }

    //Max size
    var size = document.getElementById("smpl_uname").files[0].size;
    if (size > smpl.maxsize) {
      smpl.AlertC("The file '" + fname + "' is too big! Max size of file is: " + smpl.maxsize + " bytes", "warning");
      return;
    }
    $("input#smpl_usize").val(size);

    var tim = document.getElementById('smpl_uname').files[0].lastModified;
    $("input#smpl_utime").val(new Date(tim).toLocaleDateString());
    var oktype = smpl.Validation(fname);
    if (!oktype) {
      smpl.AlertC('Client side validation: Not allowed File type!', 'warning');
    } else {
      let ar = fname.split(".");
      let len = ar.length;
      let extension = (ar[len - 1]).toLowerCase();
      if (smpl.images.includes(extension)) {
        selvalue = "image";
      } else if (smpl.audio.includes(extension)) {
        selvalue = "audio";
      } else if (smpl.audiohtml5.includes(extension)) {
        selvalue = "audiohtml5";
      } else if (smpl.video.includes(extension)) {
        selvalue = "video";
      } else if (smpl.videohtml5.includes(extension)) {
        selvalue = "videohtml5";
      } else if (smpl.application.includes(extension)) {
        selvalue = "app";
      } else if (smpl.compressed.includes(extension)) {
        selvalue = "cmp";
      } else if (smpl.document.includes(extension)) {
        selvalue = "doc";
      } else if (smpl.other.includes(extension)) {
        selvalue = "other";
      }
      $("#smpl_utype option[value=" + selvalue + "]").attr("selected", true);
    }
  })

  smpl.Validation = function (fname) {
    let ar = fname.split(".");
    let len = ar.length;
    let extension = (ar[len - 1]).toLowerCase();
    return smpl.extensions.includes(extension);
  }
})(jQuery, Drupal, smpl);