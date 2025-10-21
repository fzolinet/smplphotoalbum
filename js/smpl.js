/**
 * Load an Image / Cancel an image
 */
(function ($, Drupal, smpl) {
  /* Global variables */
  var win = $(window);


  //view
  $('.smpl_image_link').click(function () {
    var id = $(this).attr('id');
    var x = $(this).parent().parent();
    smpl.view = x.find("div.smpl_view_text");
    var url = smpl.ajax + "/view/" + id;
    $.ajax({
      url: url,
      type: "GET",
      success: function (data) {
        smpl.view.html(data);
      }
    });
  });

  //View image in box
  let SmplImgDiv = $("#smpl_img_div");
  SmplImgDiv.draggable({ cursor: "move" }).resizable({ aspectRatio: true });
  let SmplImgBack = $('#smpl_img_back');
  let SmplImgClose = $("#smpl_img_close");
  let SmplImgBoxLink = "";
  let SmplImgBox = $("img#smpl_img_box");

  // Esc key pushed
  $(document).keydown(function (e) {
    if (e.key == "Escape" && SmplImgDiv.is(":visible")) {
      SmplImgBox.hide();
      SmplImgDiv.hide();
      SmplImgBack.hide();
    }
  })

  // click an image from the list
  $("img.smpl_img").click(function (e) {
    smpl.progress();
    SmplImgBoxLink = $(this).attr("data-link");
    SmplImgBox.attr("src", SmplImgBoxLink);
  });

  /**
   * Load an image
   */
  SmplImgBox.on("load", function () {
    smpl.progress();
    SmplImgBack.show();
    SmplImgDiv.show();
    SmplImgBox.show();
    SmplImgClose.show();
    smpl.progress(false);
  });

  //Error window resizable draggable
  $("#smpl_error").resizable().draggable();

  //Close the image
  $("#smpl_div_close, #smpl_img_back").click(function () {
    SmplImgBox
      .hide()
      .attr("src", "")
      .css("z-index", 0);
    SmplImgBack.hide();
    SmplImgDiv.hide();
    SmplImgBox.hide();
    SmplImgClose.hide();
  });

  //click image open in new tab
  $("div#smpl_div_new").click(function () {
    window.open(SmplImgBoxLink, "_blank");
  });

  $("#smpl_stat_link").click(function () {
    smplstat = !smplstat;
    if (smplstat) {
      $("div#smpl_stat").show();
    } else {
      $("div#smpl_stat").hide();
    }
  });

  /**
   * Order on the page
   */
  $("#smpl_sortorder, #smpl_ascdesc").change(function () {
    let v = $("button.smpl_pager_current").val();
    $("input#smplpagehidden").val(v);
    $("#SmplTableAll").submit();
  });

  /**
   * Help
   */
  let SmplHelp = $("#smpl_help");
  SmplHelp.resizable().draggable();
  $("#smpl_help_link").click(function (e) {
    smpl.progress(true);
    let url = smpl.ajax + "/help/";
    $.ajax({
      url: url,
      type: "get",
      success: function (response) {
        $("div#smpl_help_content").html(response[0].data);
        smpl.progress(false);
        SmplHelp.show();
      }
    });
  });

  //close smpl content window
  $("#smpl_help_close, form#SmplTableAll, main").click(function () {
    SmplHelp.hide();
  });

  $(document).keydown(function (e) {
    if (e.key == "Escape" && SmplHelp.is(":visible")) {
      SmplHelp.hide();
    }
  })

})(jQuery, Drupal, smpl);
