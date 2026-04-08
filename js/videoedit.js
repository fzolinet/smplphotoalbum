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
    SmplVideoEditForm.resizable({ minWidth: 400, minHeight: 400, }).draggable({ cursor: "crosshair", });
  }

  if ( SmplVideoEditForm.is(":visible")) {
    SmplVideoEditForm.hide();
  }

  smpl.video_aspect_ratio = 1.0;
  smpl.video_size_changed = false;
  smpl.video_framerate_changed = false;
 
  /**
   * Undo the modified video
   * back values: image src, width, height, undo number, max undo stack
   */
  $("#SmplVidPrev").click(function (e) {
    VidPrevNext("prev");
    PrevNext(smpl.idx, smpl.que, smpl.prev, smpl.next); 
    e.preventDefault();
    return false;
  });

  /**
   *  Redo the images
   */
  $("#SmplVidNext").click(function (e) {
    VidPrevNext("next");
    PrevNext(smpl.idx, smpl.que, smpl.prev, smpl.next); 
    e.preventDefault();
    return false;
  });


  function VidPrevNext( cmd ) {
    let url = smpl.ajax + "/videoedit/" + smpl.id + "/" + cmd;
    smpl.progress(true);
    HideButtons();
    fetch(url)
      .then(response => {
        if (!response.ok) {
          smpl.ErrorC("Network response was not ok");
          smpl.progress(false);
        }
        var x = response.json();
        return x;
      })
      .then(d => {
        data = JSON.parse(d[0].data);        
        load(data);
        smpl.progress(false);
        ShowButtons();
        return false;
      }).catch(error => function (error) {
        smpl.progress(false);
        smpl.ErrorC(smpl.id);
        ShowButtons();
      });
  }

  /**
   * Copy the video from original place to edit place
   * load the datas of original video
   */
  $("button[id*='VidBtn']").on("click", function(e) {
    let id = $(this).attr('id').substring(6);
    smpl.videosaved = false;
    smpl.progress(true, true);
    var url = smpl.ajax + "/videoedit/" + id + "/load";
    HideButtons();    
    ShowButton("#SmplVidClose");
    
    fetch(url)
      .then(response => {
        if (!response.ok) {
          smpl.ErrorC("Network response was not ok");
          smpl.progress(false);
        }
        var x = response.json();
        return x;
      })
      .then(d => {
        data = JSON.parse(d[0].data);        
        //
        load(data);
        smpl.progress(false);
        clearInterval(smpl.counter);        
        //
        smpl.video_aspect_ratio = parseFloat( smpl.height / smpl.width );
        smpl.video_size_changed = false;
        smpl.video_framerate_changed = false;
        PrevNext(smpl.idx, smpl.que, smpl.prev, smpl.next); 
        
        //Show VideoEdit form 
        let pos = $("#SubBtn" + id).offset();
        let dy = parseFloat($("html").css("font-size")) * 3;
        let w = SmplVideoEditForm.width();
        let ww = window.innerWidth;
        pos.left = (ww - w) * 0.5;
        if (pos.left < 0) {
          pos.left = 0
        } else if (pos.left + w > ww) {
          pos.left = ww - w - 2 * dy;
        }
        SmplVideoEditForm.show();
        SmplVideoEditForm.offset({ top: pos.top + dy, left: pos.left });        
        //
        HideButtons();
        ShowButton("#SmplVidConvert, #SmplVidClose");        
        smpl.progress(false);                
      })
      .catch(error => {        
        ShowButtons();
        smpl.progress(false);
        smpl.ErrorC(error);        
      });  
  });
  
  /**
   * Undo button disabled
   * @param idx - index of queue (0....que-1)
   * @param que - length of queue
   * @param prev - previous number
   * @param next - next number
  */
  function PrevNext(idx, que, prev, next) {
    let pr = $("#SmplVidPrev");
    let nx = $("#SmplVidNext");
    pr.prop("title", smpl.words.Undo + ": " + prev);
    nx.prop("title", smpl.words.Redo + ": " + next);
    if (idx > 0) {
      pr.prop("disabled", false).removeClass("smpl_darkenbuttons");
    } else {
      pr.prop("disabled", true).addClass("smpl_darkenbuttons");
    }
    if (idx < que) {
      nx.prop("disabled", false).removeClass("smpl_darkenbuttons");
    } else {
      nx.prop("disabled", true).addClass("smpl_darkenbuttons");
    }
  }

  /**
   * Change width or height
   */
  $("#SmplVidWidth").on("change", function() {
    if ($("#SmplVidAspect").is(":checked")) {
      var h = Math.round( parseFloat( $(this).val() ) * smpl.video_aspect_ratio);
      $("#SmplVidHeight").val(h);
    }
    smpl.video_size_changed = true;
  })

  $("#SmplVidHeight").on("change", function() {
    if ($("#SmplVidAspect").is(":checked")) {
      var w = Math.round(parseFloat( $(this).val() ) / smpl.video_aspect_ratio);
      $("#SmplVidWidth").val(w);
    }
    smpl.video_size_changed = true;
  })

  /**
   * Clip start or end changed  
   */
  $("#SmplVidStart, #SmplVidEnd").on("change", function () {    
    $("#SmplPlayer").get(0).currentTime = parseFloat($(this).val());
  });
  
  /**
   * Framerate changed
   */
  $("#SmplVidFramerate").on("change", function () {
    smpl.video_framerate_changed = true;
  })

  /**
 * GOP changed
 */
  $("#SmplVidGOP").on("change", function () {
    smpl.video_gop_changed = true;
  })

  /**
   * Cancel the conversion
   */
  $("#SmplVidCancel").click( function (e) {
    smpl.videosaved = false;
    smpl.progress(false);
    clearInterval(smpl.counter);
    smpl.conversion_started = false;
    $("#SmplVidConvert").prop("disabled", false);
    var url = smpl.ajax + "/videoedit/" + smpl.id + "/cancel/?idx=" + smpl.idx; 
    smpl.ok = "cancel";
    ShowButtons();    
    
    fetch(url)
      .then(response => response.json())
      .then(data => function (data) {
        smpl.AlertC("{{ The conversion canceled }}", 'status');
        ShowButtons();
      }).catch(error => function (error) {
        smpl.AlertC("{{ Error canceling conversion }}", 'error');
        ShowButtons();
      });    
    e.preventDefault();
    return false;
  })

  /**
   * click on Close button of windows of properties
   * @return false
   */
  $("#SmplVidClose").click(function (e) {
    if ( smpl.videosaved === false) {
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
        background: "#fadb9a", 
        icon: "warning",
        animation: false
      })
        .then((ok) => {
          if (ok.isConfirmed) {
            HideButtons();
            CloseForm();
          }
          e.preventDefault();
          return false;
        });
    } else {
      HideButtons();
      CloseForm();
    }
    e.preventDefault();
    return false;
  });
  
  /**
   * Close the video edit form, unset all the session variables and delete the temp files
   */
  function CloseForm() {
    smpl.videosaved = true;
    SmplVideoEditForm.hide();
    $("#SmplVidId").val('');
    $("#SmplVidFilename").html('');    
    smpl.conversion_started = false;
    var url = smpl.ajax + "/videoedit/" + smpl.id + "/close";
    fetch(url)
      .then(response => response.json())      
      .then(data => function (data) {
        smpl.AlertC(data.msg, 'status');
        PrevNext(0, 0, false);       
        ShowButtons();
      }).catch(error => function (error) {        
        smpl.AlertC("{{ Error closing video edit }}", 'error');
        PrevNext(0, 0, false);       
        ShowButtons();
      });    
  }

  /** Save the actual video (and close the window ?) */
  $("#SmplVidSave").on("click", function ( e ) {
    Swal.fire({
      title: smpl.words.Save,
      html: '',
      classname: 'smpl-message-warning',
      closeOnClickOutside: true,
      closeOnEsc: true,
      dangerMode: true,
      showCloseButton: true,
      showCancelButton: true,
      cancelButtonText: smpl.words.Cancel,
      confirmButtonText: smpl.words.Confirm,
      icon: "warning",
      background: "#fadb9a",      
      animation: false
    })
      .then( (ok) => {
        if (smpl.progressvisible()) {
          smpl.AlertC(smpl.words.Conversion_running, 'warning');
          return false;
        }

        if (ok.isConfirmed) {
          var url = smpl.ajax + "/videoedit/" + smpl.id + "/save?idx=" + smpl.idx;
          smpl.progress(true, true);

          // Ajax hívás
          fetch(url)
            .then(response => response.json())
            .then(data => {
              data = JSON.parse(data[0].data);
              smpl.progress(false);
              if ( data.ok != 1 ) {
                smpl.ErrorC( data.msg );
                ShowButtons();
              } else {
                smpl.AlertC(data.msg, "status");                
                smpl.videosaved = true;
                ShowButtons();
                HideButton("#SmplVidSave");
              }              
              
            }).catch(error => function (error) {
              smpl.progress(false);
              ShowButtons();
              smpl.ErrorC(error.responseText);
            });
        }

        e.preventDefault();
        return false;
      })
  })

  /**
   * SaveAS
   */
  $("#SmplVidSaveAs").on("click", function (e) {    
      $("#SmplVidSaveAsInput").val(smpl.name);
      $("#smpl_vidsaveas").show();    
  });

  $("#SmplVidSaveAsCancel").on("click", function (e) {    
    $("#SmplVidSaveAsInput").val("");
    $("#smpl_vidsaveas").hide();
  });

  $("#SmplVidSaveAsOK").click(function (e) {
    var newname = $("#SmplVidSaveAsInput").val();
    if (smpl.name == newname) {
      smpl.ErrorC('The new name is the same as the original name!');
      return false;
    }
    
    let url = smpl.ajax + "/videoedit/" + smpl.id + "/saveas/?idx=" + smpl.idx + "&newname=" + newname;
    
    smpl.progress(true);
    fetch(url)
      .then( response => response.json())
      .then(data => {
        data = JSON.parse(data[0].data);
        smpl.progress(false);
        $("#smpl_vidsaveas").hide();
        if (data.ok == -1) {
          smpl.ErrorC(data.msg);          
        } else {
          smpl.AlertC(data.msg, 'status'); 
        }
        smpl.videosaved = true;
        
      })
      .catch(error => function (error) {
        smpl.progress(false);
        ShowButtons();
        smpl.ErrorC(error.responseText);
      });
      e.preventDefault();
    return false;
  });

  /** 
   * video conversion 
   * 
   */
  $("#SmplVidConvert").click(function(e) {
    Swal.fire({
      title: smpl.words.converting_long + ".",
      html: '',
      className: "smpl-message-warning",
      closeOnClickOutside: true,
      closeOnEsc: true,
      dangerMode: true,
      showCloseButton: true,
      showCancelButton: true,
      cancelButtonText: smpl.words.Cancel,
      confirmButtonText: smpl.words.Confirm,
      icon: "warning",
      background: "#fadb9a",
      animation: false
    })
      .then((ok) =>
      {
        if ( smpl.progressvisible() ) {
          smpl.AlertC(smpl.words.Conversion_running, 'warning');
          return false;
        }

        if (ok.isConfirmed) {
          var id = $("#SmplVidId").val();
          var url = smpl.ajax + "/videoedit/" + id + "/convert?x=1";
          
          // extension change
          if( $('input[name="SmplVidExt"]:checked').val() == "mp4") {
            url += '&newext=mp4';
          } else {
            url += '&newext=original';
          }
          
          //video size changed
          url += (smpl.video_size_changed) ? '&width=' + $("#SmplVidWidth").val() + '&height=' + $("#SmplVidHeight").val() : '';          

          //framerate changed
          url += (smpl.video_framerate_changed) ? '&framerate=' + $("#SmplVidFramerate").val() : '';         
          
          //GOP changed
          url += (smpl.video_gop_changed) ? '&gop=' + $("#SmplVidGOP").val():'';
         
          //clip changed
          var clipstart = $("#SmplVidStart").val();
          var clipend = $("#SmplVidEnd").val();
          var clipduration = $("#SmplVidDuration").val();

          if ( clipstart > 0 || clipend < clipduration ) {
            url += '&clipstart=' + clipstart;
            url += '&clipend=' + clipend;
          }

          //rotate
          if ($("#SmplVidRotate").val() != '0') {
              url += '&rotate=' + $("#SmplVidRotate").val();
          }
           
          var params = $("#SmplVidParams").val();
          if (params.length > 0) {            
            url += '&params=' + params.replaceAll(" ","%20");
          }

          //hosszú folyamat
          smpl.progress(true, true); 
          HideButtons();
          ShowButton("#SmplVidCancel");
          
          // Ajax hívás
          fetch(url)
            .then( response => response.json() )
            .then(data => {              
              data = JSON.parse(data[0].data);              
              smpl.progress(false);
              if ( data.ok == -1 ||data.id == '-1' || data.id == '-2') {
                smpl.ErrorC( data.msg );
              } else if(smpl.ok == "cancel"){
                smpl.AlertC("Conversion cancelled", 'warning');
              } else{                
                load(data);
                smpl.AlertC(data.msg, 'status');                
              }
              ShowButtons();
              HideButton("#SmplVidCancel");
              smpl.conversion_started = false;              
              smpl.progress(false);                         
            }).catch(error => function (error) {
              ShowButtons();
              smpl.progress(false);
              smpl.ErrorC(error.responseText);
            });
        }
        e.preventDefault();
        return false;
      });
  });

  /**
   * Load the datas of video and fill the form
   * with actual datas
   * @param {} data - from server json format
   */
  function load(data) {            
    smpl.tempname = data.tempname;
    smpl.name = data.name;    
    $("#SmplVidFilename").html(data.name);
    
    for (var prop of Object.keys( data )) {
      if (prop in data ) {
        if ( !smpl.isUndefined(data[prop])) {
          smpl[prop] = data[prop];  
        }             
      }
    }

    $("#SmplVidId").val(smpl.id);
    $("#SmplVidQue").val(smpl.que);   
    $("#SmplVidParams").val(smpl.params);
    $("#SmplVidIdx").val(smpl.idx); 
    $("#SmplVidPrev").val(smpl.prev);
    $("#SmplVidNext").val(smpl.next);
    $("#SmplVidModified").html(smpl.modified);    
    $("#SmplVidWidth").val(smpl.width);    
    $("#SmplVidHeight").val(smpl.height);    
    $("#SmplVidSize").html(smpl.width + "x" + smpl.height);
    $("#SmplVidFilesize").html(smpl.filesize);
    $("#SmplVidFramerate").val(smpl.framerate);
    $("#SmplVidGOP").val(smpl.gop);
    $("#SmplVidStart").val(0);
    $("#SmplVidEnd").val(Math.round(smpl.clipend * 100) / 100);    
    $("#SmplVidDuration").val(Math.round(data.duration * 100) / 100);    
    Player( smpl.url);
    
    smpl.video_framerate_changed = false;
    smpl.videosaved = false;
    if (smpl.idx == 0) {
      smpl.ourl = smpl.url;
      smpl.owidth = smpl.width;
      smpl.oheight = smpl.height;
      smpl.ofilesize = smpl.filesize;
      smpl.ostart = 0;
      smpl.oend = smpl.vi
      smpl.oframerate = smpl.framerate;
      smpl.ogop = 0;
      smpl.oclipstart = 0;
      smpl.oclipend = smpl.clipend;
      smpl.oduration = smpl.duration;
      smpl.omodified = smpl.modified;
    }
  }

  /**
   * Show Original video
   **/
  $("#SmplVidInit").on("mousedown", function () {
    if (smpl.idx > 0) {
      smpl.bkp = smpl.idx;
      smpl.bwidth = smpl.width;
      smpl.bheight = smpl.height;      
      LoadVidInitProp();
      Player(smpl.ourl);      
    };
  });


  /**
   * Last changed video
   */
  $("#SmplVidInit").on("mouseup", function () {
    if (smpl.bkp > 0) {
      smpl.idx = smpl.bkp;
      smpl.width = smpl.bwidth;
      smpl.height = smpl.bheight;      
      LoadVidProp();
      Player(smpl.url);
    }
  });

  function LoadVidInitProp() {     
    $("#SmplVidModified").html(smpl.omodified);
    $("#SmplVidWidth").val(smpl.owidth);
    $("#SmplVidHeight").val(smpl.oheight);
    $("#SmplVidSize").html(smpl.owidth + "x" + smpl.oheight);
    $("#SmplVidFilesize").html(smpl.ofilesize);
    $("#SmplVidFramerate").val(smpl.oframerate);
    $("#SmplVidGOP").val(smpl.ogop);
    $("#SmplVidStart").val(0);
    $("#SmplVidEnd").val(Math.round(smpl.oclipend * 100) / 100);
    $("#SmplVidDuration").val(Math.round(smpl.oduration * 100) / 100);      
  }
    
  function LoadVidProp() {
    $("#SmplVidModified").html(smpl.modified);
    $("#SmplVidWidth").val(smpl.width);
    $("#SmplVidHeight").val(smpl.height);
    $("#SmplVidSize").html(smpl.width + "x" + smpl.height);
    $("#SmplVidFilesize").html(smpl.filesize);
    $("#SmplVidFramerate").val(smpl.framerate);
    $("#SmplVidGOP").val(smpl.gop);
    $("#SmplVidStart").val(0);
    $("#SmplVidEnd").val(Math.round(smpl.clipend * 100) / 100);
    $("#SmplVidDuration").val(Math.round(smpl.duration * 100) / 100);      
  }

  /**
   * Video file url from index
   * @param {*} idx 
   * @returns 
   */
  function VideoUrl( idx ) {
    var lastIndex = (smpl.name).lastIndexOf('.');
    return smpl.tempurl + (smpl.name).substr(0, lastIndex) + "_temp_" + idx + (smpl.name).substr(lastIndex);
  }

  /**
   * Get th duration of video
   * @param {*} url 
   */  
  function Player(url) {
    $("#SmplPlayer").attr("src", url);
    var video = document.getElementById("SmplPlayer");
    var i = setInterval(function () {
      if (video.readyState > 0) {
        smpl.duration = video.duration;
        $("#SmplVidEnd").val(smpl.duration);
        $("#SmplVidDuration").val(smpl.duration);
        clearInterval(i);
      }      
    }, 200);     
  }

  /**
   * Video events
   * https://github.com/faktorvier/jquery-video
   */
  $('#SmplPlayer').video();
  $('#SmplPlayer').addVideoEvent('play', function(e) {
    HideButtons();
    ShowButton("#SmplVidClose");
  });

  $('#SmplPlayer').addVideoEvent('pause', function(e) {
    if (smpl.conversion_started) {
      ShowButtons();  
    } 
    ShowButton("#SmplVidConvert")
  });

  $('#SmplPlayer').addVideoEvent('finish', function(e) {
    if (smpl.conversion_started) {
      ShowButtons();
    }
    ShowButton("#SmplVidConvert")
  });

  /**
  * Buttons enabled
  */
  function ShowButtons(ok = true) {
    if (ok) {
      ShowButton("#SmplVidConvert, #SmplVidClose, #SmplVidSave, #SmplVidSaveAs, #SmplVidCancel");
      if (smpl.idx > 0) {
        ShowButton("#SmplVidPrev, #SmplVidInit");
      } else {
        HideButton("#SmplVidPrev, #SmplVidInit");
      }

      if (smpl.idx < smpl.que) {
        ShowButton("#SmplVidNext");
      } else {
        HideButton("#SmplVidNext");
      }

    } else {
      HideButton("#SmplVidConvert, #SmplVidPrev, #SmplVidNext, #SmplVidClose, #SmplVidSave, #SmplVidSaveAs, #SmplVidCancel, #SmplVidInit");
    }
  }

  function HideButtons() {
    ShowButtons(false);
  }

  function ShowButton(btn) {
    $(btn).prop("disabled", false).removeClass("smpl_darkenbuttons");
  }

  function HideButton(btn) {
    $(btn).prop("disabled", true).addClass("smpl_darkenbuttons");
  }

})(jQuery, Drupal, smpl, Swal);