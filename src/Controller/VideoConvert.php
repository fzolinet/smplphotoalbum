<?php
namespace Drupal\smplphotoalbum\Controller;
require realpath(__DIR__."/../../")."/vendor/autoload.php";

use FFMpeg\FFMpeg;

class VideoConvert {
  private $path = '';
  private $type = '';
  private $width = 1280;
  private $height = 720;
  private $framerate = 30;

  // Video2MP4 class implementation
  function __construct($path, $type, $width=0, $height=0, $framerate=30) {
    $this->path = $path;
    $this->type = $type;
    $this->width = $width;
    $this->height = $height;
    $this->framerate = $framerate;       
  }

  public function convert() {
        
    $ok = "1";
    $msg = "Conversion successful from '$this->path' to MP4 format.";
fz_t($msg);
    $ffmpeg = FFMpeg::create();
    
fz_t($ffmpeg);
    $video = $ffmpeg->open($this->path);
    $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');
    $filename = pathinfo($this->path, PATHINFO_FILENAME) . '.mp4';
    $video ->save($format, $filename);

    return ["id" => $ok, "msg" => $msg];
  }
} 