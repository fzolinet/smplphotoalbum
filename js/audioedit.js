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
  let SmplAudioEditForm = $("div#SmplAudioEditForm");

  // Draggable window
  if (SmplAudioEditForm.length > 0) {
    SmplAudioEditForm.resizable({ minWidth: 400, minHeight: 400, }).draggable({ cursor: "crosshair", });
  }

  if ( SmplAudioEditForm.is(":visible")) {
    SmplAudioEditForm.hide();
  }

  smpl.audio_size_changed = false;
  
  /**
   * Undo the modified audio
   * back values: image src, width, height, undo number, max undo stack
   */
  $("#SmplAudPrev").click(function (e) {
    AudPrevNext("prev");
    PrevNext(smpl.idx, smpl.que, smpl.prev, smpl.next);     
    return false;
  });

  /**
   *  Redo the images
   */
  $("#SmplAudNext").click(function (e) {
    AudPrevNext("next");
    PrevNext(smpl.idx, smpl.que, smpl.prev, smpl.next);     
    return false;
  });


  function AudPrevNext( cmd ) {
    let url = smpl.ajax + "/audioedit/" + smpl.id + "/" + cmd;
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
  $("button[id*='AudBtn']").on("click", function(e) {
    let id = $(this).attr('id').substring(6);
    smpl.audiosaved = false;
    smpl.progress(true, true);
    var url = smpl.ajax + "/audioedit/" + id + "/load";
    HideButtons();    
    ShowButton("#SmplAudClose");
    
    fetch(url)
      .then(response => {
        if (!response.ok) {
          smpl.ErrorC(smpl.words.Network_response_not_ok);
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
        smpl.audio_size_changed = false;        
        PrevNext(smpl.idx, smpl.que, smpl.prev, smpl.next); 
        
        //Show AudioEdit form 
        smpl.WindowPosition(SmplAudioEditForm, "#SubBtn" + id, "#smpl_audsaveas");     
        //
        HideButtons();
        ShowButton("#SmplAudConvert, #SmplAudClose");        
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
    let pr = $("#SmplAudPrev");
    let nx = $("#SmplAudNext");
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
   * Clip start or end changed  
   */
  $("#SmplAudStart, #SmplAudEnd").on("change", function () {    
    $("#SmplAudPlayer").get(0).currentTime = parseFloat($(this).val());
  });
    
  /**
   * Cancel the conversion
   */
  $("#SmplAudCancel").click( function (e) {
    smpl.audiosaved = false;
    smpl.progress(false);
    clearInterval(smpl.counter);
    smpl.conversion_started = false;
    $("#SmplAudConvert").prop("disabled", false);
    var url = smpl.ajax + "/audioedit/" + smpl.id + "/cancel/?idx=" + smpl.idx; 
    smpl.ok = "cancel";
    ShowButtons();    
    
    fetch(url)
      .then(response => response.json())
      .then(data => function (data) {
        smpl.AlertC(smpl.words.Conversion_canceled, 'status');
        ShowButtons();
      }).catch(error => function (error) {
        smpl.AlertC(smpl.words.Error_canceling_conversion, 'error');
        ShowButtons();
      });    
    e.preventDefault();
    return false;
  })

  /**
   * click on Close button of windows of properties
   * @return false
   */
  $("#SmplAudClose").click(function (e) {
    if ( smpl.audiosaved === false) {
      Swal.fire({
        title: smpl.words.not_saved,
        html: smpl.words.Audioclip_not_saved + " " + smpl.words.Close_the_window,
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
    smpl.audiosaved = true;
    SmplAudioEditForm.hide();
    $("#SmplAudId").val('');
    $("#SmplAudFilename").html('');    
    smpl.conversion_started = false;
    var url = smpl.ajax + "/audioedit/" + smpl.id + "/close";
    fetch(url)
      .then(response => response.json())      
      .then(data => function (data) {
        smpl.AlertC(data.msg, 'status');
        PrevNext(0, 0, false);       
        ShowButtons();
      }).catch(error => function (error) {        
        smpl.AlertC(smpl.words.Error_closing_audio_edit, 'error');
        PrevNext(0, 0, false);       
        ShowButtons();
      });    
  }

  /** Save the actual video (and close the window ?) */
  $("#SmplAudSave").on("click", function ( e ) {
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
          var url = smpl.ajax + "/audioedit/" + smpl.id + "/save?idx=" + smpl.idx;
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
                HideButton("#SmplAudSave");
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
  $("#SmplAudSaveAs").on("click", function (e) {    
      $("#SmplAudSaveAsInput").val(smpl.name);
      $("#smpl_audsaveas").show();    
  });

  $("#SmplAudSaveAsCancel").on("click", function (e) {    
    $("#SmplAudSaveAsInput").val("");
    $("#smpl_audsaveas").hide();
  });

  $("#SmplAudSaveAsOK").click(function (e) {
    var newname = $("#SmplAudSaveAsInput").val();
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
        $("#smpl_audsaveas").hide();
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
  $("#SmplAudConvert").click(function(e) {
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
          var id = $("#SmplAudId").val();
          var url = smpl.ajax + "/audioedit/" + id + "/convert?x=1";
          
          // extension change
          url += '&newext=' + smpl.ext;

          if ($("#SmplAudAudioBitrate").val() > 0) {
            url += '&audiokilobitrate=' + $("#SmplAudAudioBitrate").val();
          }
          
          //clip changed
          var clipstart = $("#SmplAudStart").val();
          var clipend = $("#SmplAudEnd").val();
          var clipduration = $("#SmplAudDuration").val();

          if ( clipstart > 0 || clipend < clipduration ) {
            url += '&clipstart=' + clipstart;
            url += '&clipend=' + clipend;
          }

          var params = $("#SmplAudParams").val();
          if (params.length > 0) {
            url += '&params=' + params.replaceAll(" ", "%20");
          }

          //hosszú folyamat
          smpl.progress(true, true); 
          HideButtons();
          ShowButton("#SmplAudCancel");
          
          // Ajax hívás
          fetch(url)
            .then( response => response.json() )
            .then(data => {              
              data = JSON.parse(data[0].data);              
              smpl.progress(false);
              if ( data.ok == -1 ||data.id == '-1' || data.id == '-2') {
                smpl.ErrorC( data.msg );
              } else if( smpl.ok == "cancel" ){
                smpl.AlertC(smpl.words.Conversion_canceled, 'warning' );
              } else{                
                load( data );
                smpl.AlertC( data.msg, 'status' );                
              }
              ShowButtons(); 
              HideButton("#SmplAudCancel");
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

  $("select#SmplAudFormat").on("change", function () {
    smpl.ext = $("select#SmplAudFormat option:selected").val();
  })

  /**
   * Load the datas of video and fill the form
   * with actual datas
   * @param {} data - from server json format
   */
  function load(data) {            
    smpl.tempname = data.tempname;
    smpl.name = data.name;
    smpl.ext = data.ext;
    $("#SmplAudFilename").html(data.name);
    
    for (var prop of Object.keys( data )) {
      if (prop in data ) {
        if ( !smpl.isUndefined(data[prop])) {
          smpl[prop] = data[prop];  
        }             
      }
    }

    $("#SmplAudId").val(smpl.id);
    $("#SmplAudQue").val(smpl.que); 
    $("#SmplAudParams").val(smpl.params);    
    $("#SmplAudIdx").val(smpl.idx); 
    $("#SmplAudPrev").val(smpl.prev);
    $("#SmplAudNext").val(smpl.next);
    $("#SmplAudModified").html(smpl.modified);            
    $("#SmplAudFilesize").html(smpl.filesize);    
    $("#SmplAudAudioBitrate").val(smpl.audiokilobitrate);
    $("#SmplAudFormat").val(smpl.ext);
    
    $("#SmplAudStart").val(0);
    $("#SmplAudEnd").val(Math.round(smpl.clipend * 100) / 100);    
    $("#SmplAudDuration").val(Math.round(data.duration * 100) / 100);    
    Player( smpl.url);
    
    smpl.audiosaved = false;
    if (smpl.idx == 0) {
      smpl.ourl = smpl.url;
      smpl.ofilesize = smpl.filesize;
      smpl.ostart = 0;
      smpl.oend = smpl.vi
      smpl.oclipstart = 0;
      smpl.oclipend = smpl.clipend;
      smpl.oduration = smpl.duration;
      smpl.omodified = smpl.modified;
    }
  }

  /**
   * Show Original video
   **/
  $("#SmplAudInit").on("mousedown", function () {
    if (smpl.idx > 0) {
      smpl.bkp = smpl.idx;
      smpl.bwidth = smpl.width;
      smpl.bheight = smpl.height;      
      LoadAudInitProp();
      Player(smpl.ourl);      
    };
  });

  /**
   * Last changed video
   */
  $("#SmplAudInit").on("mouseup", function () {
    if (smpl.bkp > 0) {
      smpl.idx = smpl.bkp;
      LoadAudProp();
      Player(smpl.url);
    }
  });

  function LoadAudInitProp() {     
    $("#SmplAudModified").html(smpl.omodified);
    $("#SmplAudSize").html(smpl.owidth + "x" + smpl.oheight);
    $("#SmplAudFilesize").html(smpl.ofilesize);
    $("#SmplAudStart").val(0);
    $("#SmplAudEnd").val(Math.round(smpl.oclipend * 100) / 100);
    $("#SmplAudDuration").val(Math.round(smpl.oduration * 100) / 100);      
  }
    
  function LoadAudProp() {
    $("#SmplAudModified").html(smpl.modified);
    $("#SmplAudFilesize").html(smpl.filesize);
    $("#SmplAudStart").val(0);
    $("#SmplAudEnd").val(Math.round(smpl.clipend * 100) / 100);
    $("#SmplAudDuration").val(Math.round(smpl.duration * 100) / 100);      
  }

  /**
   * Video file url from index
   * @param {*} idx 
   * @returns 
   */
  function AudioUrl( idx ) {
    var lastIndex = (smpl.name).lastIndexOf('.');
    return smpl.tempurl + (smpl.name).substr(0, lastIndex) + "_temp_" + idx + (smpl.name).substr(lastIndex);
  }

  var audio = document.getElementById("SmplAudPlayer");

  /**
   * Get th duration of video
   * @param {*} url 
   */  
  function Player(url) {
    $("#SmplAudPlayer").attr("src", url);
    var i = setInterval(function () {
      if (audio.readyState > 0) {
        smpl.duration = audio.duration;
        $("#SmplAudEnd").val(smpl.duration);
        $("#SmplAudDuration").val(smpl.duration);
        clearInterval(i);
      }      
    }, 200);     
  }

  /**
    * Audio events
    * https://github.com/faktorvier/jquery-video
    */
  $('#SmplAudPlayer').media("audio");

  $('#SmplAudPlayer').addMediaEvent('play', function (e) {
    HideButtons();
    ShowButton("#SmplAudClose");
  });

  $('#SmplAudPlayer').addMediaEvent('pause', function (e) {
    if (smpl.conversion_started) {
      ShowButtons();
    }
    ShowButton("#SmplAudConvert")
  });

  $('#SmplAudPlayer').addMediaEvent('finish', function (e) {
    if (smpl.conversion_started) {
      ShowButtons();
    }
    ShowButton("#SmplAudConvert")
  });


  /**
  * Buttons enabled
  */
  function ShowButtons(ok = true) {
    if (ok) {
      ShowButton("#SmplAudConvert, #SmplAudClose, #SmplAudSave, #SmplAudSaveAs, #SmplAudCancel");
      if (smpl.idx > 0) {
        ShowButton("#SmplAudPrev, #SmplAudInit");
      } else {
        HideButton("#SmplAudPrev, #SmplAudInit");
      }

      if (smpl.idx < smpl.que) {
        ShowButton("#SmplAudNext");
      } else {
        HideButton("#SmplAudNext");
      }

    } else {
      HideButton("#SmplAudConvert, #SmplAudPrev, #SmplAudNext, #SmplAudClose, #SmplAudSave, #SmplAudSaveAs, #SmplAudCancel, #SmplAudInit");
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