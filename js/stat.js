var smplstat = false;
(function($, Drupal){
  $("#smpl_stat_link").click(function(){
    smplstat = !smplstat;
    if(smplstat){
     	$("div#smpl_stat").show();
    }else{
    	$("div#smpl_stat").hide();
    }
  });
})(jQuery, Drupal);
