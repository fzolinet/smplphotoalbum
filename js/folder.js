/**
 * Folder properties of image
 */

(function ($, Drupal, smpl) {
  /**
   * Folder command command to show / hide the New folder form of properties   
   */
  let SmplFolderForm = $("div#SmplFolderForm");

  // Draggable window
  if (SmplFolderForm.length > 0) {
    SmplFolderForm.draggable();
  }

  // Upload Window open
  $("#SmplFolder").click(function (e) {
    let pos = $("#SmplFolder").offset();
    let dy = parseFloat($("html").css("font-size")) * 5;
    SmplFolderForm.show();
    let w = SmplFolderForm.width();
    let ww = window.innerWidth;
    pos.left = (ww - w) * 0.5;
    if (pos.left < 0) {
      pos.left = 0
    } else if (pos.left + w > ww) {
      pos.left = ww - w - 2 * dy;
    }
    SmplFolderForm.offset({ top: pos.top + dy, left: pos.left });
  });

  $("#smpl_fname").change(function () {
    let fname = $(this).val();
    if (!smpl.Validation(fname, false)) {
      smpl.AlertC("Client side validation: There is no enabled folder name: '" + fname + "' !", 'warning');
    }
  });
  /**
   * click on cancel button of windows of properties
   * @return false
     */
  $("#SmplFolderClose").click(function (e) {
    SmplFolderForm.hide();
    $("input#smpl_fid").val('');
    $("input#smpl_fname").val('');
    $("input#smpl_fsub").val('');
    $("input#smpl_flink").val('');
    e.preventDefault();
    return false;
  });

  /**
   * Folder form send to server
   * @return false
   */
  $("#SmplUploadSubmit").click(function () {
    let id = $("input#smpl_fid").val();
    var fname = $("#smpl_fname").val();

    // client side validation
    // - It can't is empty
    // - Disabled character '.'
    // - Can not in space
    // - script
    if (!smpl.Validation(fname)) {
      smpl.AlertC("Client side validation: There is no enabled folder name: '" + fname + "' !", 'warning');
      return false;
    }

    let formData = {
      name: $("input#smpl_fname").val(),
      subtitle: $("input#smpl_fsub").val(),
      link: $("input#smpl_flink").val(),
    };
    return true;
  });
})(jQuery, Drupal, smpl);