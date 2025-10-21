/**
 * Test Framework for smplphotoalbum
 */
//import {  fz_t } from './lib.js';

(function($, Drupal) {
  let msg = "";
  let t = 2;
  let speed = 1000;
  if( (typeof smpl !== "undefined" && smpl.test ) || (typeof smplslide !== "undefined" && smplslide.test )){
    $(document).ajaxComplete(function(event, request, settings){
      let data = request.responseJSON[0].data;
      let suc = JSON.parse(data).ok
      let a = event.target.activeElement.id;
      let msg ='';
      if(a == "smpl_test_start" ){
        msg = data;
      }else{
        msg = "<br><br>Event: "+event.type+",<br>- success:"+suc +"<br>- ID: "+a+"<br>- URL: "+settings.url;
      }
      fz_t(msg);
    });
  }

  let filename = "";
  $("button#smpl_test_start").click(function(){
      var url = smpl.ajax + "/test/";
      smpl.test = true;
      $("#smpl_error_content").html("");
      progress(true);
      msg = "";
      $.ajax( {
          url: url,
          type: "get",
          success: function(response){
              var data = JSON.parse(response[0].data);
              smpl.id   = data.id;
              progress(false);
              return false;
           },
           error: function(response){
               progress(false);
               msg += "Error in the test:" + response.responseText;
               return false;
           },
      } );
      SmplSetCookie('smpl_test_id',smpl.id);
      location.reload(true);
  } );

  $("#smpl_fz_test").click( function(){
    if($(this).is(":checked") ){
      SmplSetCookie("fz_test", "true");
      fz_t("",true);
    }else{
      SmplDeleteCookie("fz_test");
      fz_t("",false);
    }
  });
  
  /**
   * Progress Indicator on/off
   * @param on
   * @returns
   */
  function progress(on){
      $(".ajax-progress-fullscreen").remove();
      if(typeof on !== 'undefined' && on){
          $('body').after(Drupal.theme.ajaxProgressIndicatorFullscreen());
          $('body').css("cursor","progress");
      }else{
          $('body').css("cursor","default");
      }
  }
})(jQuery, Drupal);

/**
 * Set a cookie
 * @param string name
 * @param string value
 * @param number days
 */
function SmplSetCookie( name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

/**
 * get a cookie
 * param string name - name of cookie
 */
function SmplGetCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
/**
 * Delete Cookie
 * @param string name
 */
function SmplDeleteCookie(name) {
    document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}