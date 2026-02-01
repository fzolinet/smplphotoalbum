/**
 * Video convert properties of video
 */

(function ($, Drupal, smpl, Swal) {
  //Edit button
  $('.smpl_edit').mouseover(function () {
    $(this).css('cursor', 'pointer');
  });

  $('.smpl_edit').mouseout(function () {
    $(this).css('cursor', 'default');
  });

  /**
   * Edit command to show / hide the Edit form of properties
   * with parameters of actual item
   */
  let SmplVideoEditForm = $("div#SmplVideoEditForm");

  // Draggable window
  if (SmplVideoEditForm.length > 0) {
    SmplVideoEditForm
      .resizable({
        minWidth: 400,
        minHeight: 400,
      })
      .draggable({
        cursor: "crosshair",
      });
  }

  if ( SmplVideoEditForm.is(":visible")) {
    SmplVideoEditForm.hide();
  }

  var video_aspect_ratio = 1.0;
  var video_size_changed = false;


  $("button[id*='VidBtn']").click(function (e) {
    let id = $(this).attr('id').substring(6);
    smpl.vidsaved = false;
    smpl.progress(true);
    var url = smpl.ajax + "/videoedit/" + id + "/load";
    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        let data = JSON.parse(response[0].data);
        $("input#smpl_vidid").val(id);
        $("div#smpl_vidfilename").html(data.name);        
        $("tr#smpl_video2mp4").show();
        $("input#smpl_vidsize").val((data.filesize).toLocaleString());
        $("input#smpl_vidwidth").val(data.width);
        $("input#smpl_vidheight").val(data.height);
        $("input#smpl_videframerate").val(data.framerate);
        $("input#smpl_clipstart").val(0);        
        $("input#smpl_clipend").val(Math.round(data.clipend * 100) / 100);
        $("input#smpl_clipend").attr({ max: Math.round(data.clipend * 100) / 100 });
        $("input#smpl_clipduration").val(Math.round(data.duration * 100) /100);
        video_aspect_ratio = parseFloat(data.height / data.width);
        video_size_changed = false;
        let pos = $("#SubBtn" + id).offset();
        let dy = parseFloat($("html").css("font-size")) * 5;
        SmplVideoEditForm.show();
        let w = SmplVideoEditForm.width();
        let ww = window.innerWidth;
        pos.left = (ww - w) * 0.5;
        if (pos.left < 0) {
          pos.left = 0
        } else if (pos.left + w > ww) {
          pos.left = ww - w - 2 * dy;
        }
        SmplVideoEditForm.offset({ top: pos.top + dy, left: pos.left });
        smpl.progress(false);
        CopyVideo2Edit(id);
      },
      error: function (response) {
        smpl.progress(false);
      },
    });
  });

  /**
   * Copy video from original place to _edit_ place and
   * rename to temporary name
   * @param {*} id 
   */
  function CopyVideo2Edit(id) {
    var url = smpl.ajax + "/videoedit/" + id + "/copy2edit";
    $.ajax({
      url: url,
      type: "GET",
      success: {
        
      },
      error: function (response) {
        smpl.progress(false);
      },
    }); 
  }
  
  /**
   * Change width or height
   */
  $("#smpl_vidwidth").on("change", function () {
    if ($("#smpl_vidaspect").is(":checked")) {
      var h = Math.round(parseFloat($(this).val()) * video_aspect_ratio);
      $("input#smpl_vidheight").val(h);
    }
    video_size_changed = true;
  })

  $("#smpl_vidheight").on("change", function () {
    if ($("#smpl_vidaspect").is(":checked")) {
      var w = Math.round(parseFloat($(this).val()) / video_aspect_ratio);
      $("input#smpl_vidwidth").val(w);
    }
    video_size_changed = true;
  })
  /**
   * click on cancel button of windows of properties
   * @return false
     */
  $("#SmplVidClose").click(function (e) {
    if ( smpl.vidsaved === false) {
      Swal.fire({
        title: "The Edited Videoclip not saved. Do you want to close?",
        html: smpl.words.Edit_not_saved,
        className: "smpl-message-warning",
        closeOnClickOutside: true,
        closeOnEsc: true,
        dangerMode: true,
        showCloseButton: true,
        showCancelButton: true,
        cancelButtonText: smpl.words.Cancel,
        confirmButtonText: smpl.words.Confirm,
        icon: "warning",
        animation: false
      })
        .then((ok) => {
          if (ok.isConfirmed) {
            smpl.vidsaved = true;
            SmplVideoEditForm.hide();
            $("input#smpl_videdit_id").val('');            
            $("div#smpl_videdit_filename").html(''); 
          }
          e.preventDefault();
          return false;
        });
    }
    e.preventDefault();
    return false;
  });

  $("#smpl_type").on("change", function () {
    if ($(this).val() == "image") {
      $("#smplairecognition").show();
      $("#smplairecognition-info").show();
      $(".smpl_ai_check").show();
    } else {
      $("#smplairecognition").hide();
      $("#smplairecognition-info").hide();
      $(".smpl_ai_check").hide();
    }
  });

  /**
   * Edit form send to server
   * @return false
   */
  $("#SmplESubmit").click(function () {
    let id = $("input#smpl_edit_id").val();
    let formData = {
      name: $("input#smpl_name").val(),
      subtitle: $("textarea#smpl_sub").val(),
      type: $("select#smpl_type option:selected").val(),
      importance: $("input#smpl_importance").val(),
      link: $("input#smpl_link").val(),
    };
    let sendData = JSON.stringify(formData);
    let url = smpl.ajax + "/update/" + id + "/?json=" + sendData;
    smpl.progress(true);

    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        let data = JSON.parse(response[0].data);
        if (data.db == 1 && (typeof data.subtitle !== 'undefined') && (data.subtitle !== null)) {
          $("div#smpl_sub" + id).html(data.subtitle);
        }
        $("input#smpl_edit_id").val('');
        $("input#smpl_sub").val('');
        $("input#smpl_link").val('');
        $("input#smpl_importance").val('0');
        smpl.progress(false);
        smpl.editsaved = true;
        SmplEditForm.hide();
      },
      error: function (response) {
        smpl.progress(false);
        SmplEditForm.hide();
        smpl.ErrorC('JSON error: ' + response.toString());
      },
    });
    return false;
  });

  /**
   * AI image recognition with gemini client
   **/
  $("#smplairecognition").click(function () {
    let id = $("input#smpl_edit_id").val();
    let url = smpl.ajax + "/ai/" + id + '/recognition';
    smpl.progress(true);
    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        let data = JSON.parse(response[0].data);
        smpl.progress(false);
        if (data.id == '-1' || data.id == '-2') {
          smpl.ErrorC(data.msg);
        } else {
          var t = $("textarea#smpl_sub").val() + " \n!!! " + data.msg;
          $("textarea#smpl_sub").val(t);
        }
      },
      error: function (response) {
        smpl.ErrorC(response.responseText);
        smpl.progress(false);
      }
    });
  });

  $("button#smpl_ai_check").click(function () {
    let id = $("input#smpl_edit_id").val();
    let url = smpl.ajax + "/ai/" + id + '/check';
    smpl.progress(true);
    $.ajax({
      url: url,
      type: "GET",
      success: function (response) {
        let data = JSON.parse(response[0].data);
        smpl.progress(false);
        if (data.id == '-1' || data.id == '-2') {
          smpl.ErrorC(data.msg);
        } else {
          $("textarea#smpl_ai_check").val(data.msg);
        }
      },
      error: function (response) {
        smpl.ErrorC(response.responseText);
        smpl.progress(false);
      }
    });
  });

  /** video conversion */
  $("button#smpl_video2mp4").click(function (e) {
    Swal.fire({
      title: smpl.words.converting_long + ".",
      html: smpl.words.Video_conversion_confirm_msg,
      className: "smpl-message-warning",
      closeOnClickOutside: true,
      closeOnEsc: true,
      dangerMode: true,
      showCloseButton: true,
      showCancelButton: true,
      cancelButtonText: smpl.words.Cancel,
      confirmButtonText: smpl.words.Confirm,
      icon: "warning",
      animation: false
    })
      .then((ok) => {
        if (ok.isConfirmed) {
          var id = $("input#smpl_edit_id").val();
          var url = smpl.ajax + "/video2mp4/" + id;
          var width = $("input#smpl_video_width").val();
          var height = $("input#smpl_video_height").val();
          var framerate = $("input#smpl_video_framerate").val();

          if (video_size_changed) {
            url += '?width=' + $("input#smpl_video_width").val();
            url += '&height=' + $("input#smpl_video_height").val();
            url += '&framerate=' + $("input#smpl_video_framerate").val();
          }

          var clipstart = $("#smpl_video_clip_start").val();
          var clipend = $("#smpl_video_clip_end").val();

          if (clipstart > 0 && clipend < video_length) {
            url += '&clipstart=' + $("#smpl_video_clip_start").val();
            url += '&clipend=' + $("#smpl_video_clip_end").val();
          }

          smpl.progress(true);
          $.ajax({
            url: url,
            type: "GET",
            success: function (response) {
              let data = JSON.parse(response[0].data);
              smpl.progress(false);
              if (data.id == '-1' || data.id == '-2') {
                smpl.ErrorC(data.msg);
              } else {
                smpl.AlertC(data.msg);
              }
            },
            error: function (response) {
              smpl.ErrorC(response.responseText);
              smpl.progress(false);
            }
          });
        }
        e.preventDefault();
        return false;
      });
  });

})(jQuery, Drupal, smpl, Swal);