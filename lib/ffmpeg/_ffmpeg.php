<?php
/**
 * Calling:
 * $f = new ffmpeg($root, $path, $videofilename, [$percent=10])
 * @author Administrator
 *
 */
class _ffmpeg{
  private $root;
  private $path;
  private $mov;
  private $maxframe;
  private $percent;
  private $length;
  private $th;         // Full thumbnail path
  private $width;      // Thumbnail width
  private $entry;
  function __construct($root, $path, $entry, $width=150, $percent = 1){
    $this->path    = $path;
    $this->root    = $root;
    $this->entry   = $entry;
    $this->percent = $percent;
    $this->width   = $width;
    $this->target  = $root."/".$path."/".TN. $this->entry.".png";
    $this->mov     = new ffmpeg_movie($root."/".$path."/".$entry);
    
    $this->maxframe= $this->mov->getFrameCount();
    $this->length  = (int)($this->mov->getDuration()*100)/100;
  }
  
  function getLength(){
    return $this->length;
  }
  
  function maxFrame(){
    return $this->maxframe;
  }
  
  function MakeThumbnail(){
    global $base_path;
    $ok = false;
    $i   = (int) (($this->maxframe*$this->percent)/100);
    $frame = $this->mov->getFrame($i);
    
    if($frame){
      $img = $frame->toGDImage();
      if($img){
        $dx = imagesx($img);
        $dy = imagesy($img);
        if ($this->width % 2 ) $this->width--;
        $height = (int)($this->width * $dy / $dx );
        
        $dst = @ImageCreateTrueColor( $this->width, $height);
        imagegammacorrect($img, 2.2, 1.0);
        $ok = @imagecopyresampled($dst, $img, 0,0, 0,0, $this->width, $height, $dx, $dy );
        @imagegammacorrect($dst, 1.0, 2.2);
        $ok = @ImagePNG($dst,$this->target);
        $this->size = filesize($this->target);
      }
    }
    
    if(!$ok){
      $ext    = strtolower(pathinfo($this->entry, PATHINFO_EXTENSION));
      $source = str_replace("\\","/",realpath(dirname(__FILE__) ."../../../image")."/video_".$ext.".png");
      $ok = copy($source,$this->target);
    }
    return $ok;
  }
  /**
   * Shows the thumbnail
   */
  function ShowThumbnail(){
    $i   = (int) (($this->maxframe*$this->percent)/100);
    $frame = $this->mov->getFrame($i);
    if($frame){
      $img = $frame->toGDImage();
      if($img){
        header('image/png');
        header("Cache-Control:max-age");
        header("Content-disposition: inline; filename=".$this->thfname );
        header("Content-length: ".$this->size);
        header("Content-type: image/png");
        imagepng($this->mov);
      }
    }
  }
  /**
   * Return the filename where the thumbnail is made
   */
  function getThumbnail(){
    return $this->thfname;
  }
  function destroyGD(){
    imagedestroy($this->mov);
  }
}