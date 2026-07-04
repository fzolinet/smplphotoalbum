(function ($) {
  $.fn.MediaPlayer = function (id, options) {
    
    var settings = $.extend({
      url: "",
      type: "video",
      language: "en",      
    }, options);

    if (settings.type === "audio") {
      return this.each(function () {
        var audio = document.getelementById( id);
        audio.setAttribute("controls", "controls");
        audio.setAttribute("src", settings.url);
        $(this).append(audio);
      });
    } else if (settings.type === "video") {
      return this.each(function () {
        var video = document.getelementById( id );
        video.setAttribute("controls", "controls");
        video.setAttribute("src", settings.url);
        $(this).append(video);
      });
    }
  };
})(jQuery);



