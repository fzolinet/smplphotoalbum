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

  $("#SmplFolderName").change(function () {
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
    $("input#SmplFolderId").val('');
    $("input#SmplFolderName").val('');
    $("input#SmplFolderSubtitle").val('');
    $("input#SmplFolderLink").val('');
    e.preventDefault();
    return false;
  });

  /**
   * Folder form send to server
   * @return false
   */
  $("#SmplFolderSubmit").click(function () {
    let id = $("input#SmplFolderId").val();
    var name = $("input#SmplFolderName").val();

    // client side validation
    // - It can't is empty
    // - Disabled character '.'
    // - Can not in space
    // - script
    if (!smpl.Validation(name)) {
      smpl.AlertC(smpl.words.ClientSide + "This is no enabled foldername: '" + name + "' !", 'warning');
      return false;
    }

    let formData = {
      name: $("input#SmplFolderName").val(),
      subtitle: $("input#SmplFolderSubtitle").val(),
      link: $("input#SmplFolderLink").val(),
    };
    return true;
  });
})(jQuery, Drupal, smpl);