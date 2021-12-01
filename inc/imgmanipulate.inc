<?php

class ImgManipulate {
  private $src_img;
  private $dst_img;
  private $maxx;
  private $maxy;
  private $dx=1;
  private $dy;
  private $height;
  private $source ="";  //source path
  private $size;
  private $path;
  private $tempname;
  //
  private $pngqualiti = 9; //compression level
  private $jpgcomp = 100;
  private $imagic = false;
  
  function __construct(  $path ="" , $tempname ="" ){
    $this->imagic   = extension_loaded('imagick');
    $this->path     = $path;
    $this->tempname = $tempname;
    $this->source   = str_replace("\\","/",$path.$tempname);
    if(!empty($this->source)){
      if ($this->imagic){
        try{
          $this->src_img = new Imagick($this->source);
          $this->src_img->readImage($this->source);
          $this->dx     = $this->src_img->getImageWidth();
          $this->dy     = $this->src_img->getImageHeight();
          $size         = array($this->dx,$this->dy );
          $this->type   = strtolower(pathinfo($this->source,PATHINFO_EXTENSION));
        }catch(Exception $e){
          $this->imagic = false;
          $size         = GetImagesize($this->source);
          $this->size   = $size;
          $this->dx     = $size[0];
          $this->dy     = $size[1];
          $this->type   = strtolower(pathinfo($this->source,PATHINFO_EXTENSION));
          $this->load();
        }
      }else{
        $size         = GetImagesize($this->source);
        $this->size   = $size;
        $this->dx     = $size[0];
        $this->dy     = $size[1];
        $this->type   = strtolower(pathinfo($this->source,PATHINFO_EXTENSION));
        $this->load();
      }

      if(!isset($_SESSION["smpl_queue"])){
        $_SESSION["smpl_queue"] = array();
      }
      if(!isset($_SESSION["smpl_idx"])){
        $_SESSION["smpl_idx"]   = 0;
      }
    }
  }

 
  /**
   * load the actual image file from filesystem
   * @param string $src
   */
  function load($src =''){
    if(!empty($src)){
      $this->source = $src;
    }
    $this->dst_img = @ImageCreateTrueColor( $this->width, $this->height);
    switch($this->type){
      case 'jpg':
      case 'jpeg':
        $this->src_img = ImageCreateFromJPEG($this->source );
        break;
      case 'png':
        $this->src_img = @ImageCreateFromPNG($this->source );
        break;
      case 'wbmp':
        $this->src_img = @ImageCreateFromwbmp($this->source );
        break;
      case 'gif':
        $this->src_img = @ImageCreateFromGif($this->source );
        break;
      default:
        $ok = true;
    }
  }
  
  /**
   * rotate the image
   * @param unknown $rad
   */
  function rotate(){
    $degrees = $_GET["rotate"];
    if($this->imagic){
      try{
        $this->src_img->rotateImage("#00000000", $degrees);
      }catch(Exception $e){
        error_log($e);
      }
    }else{
      $this->dst_img = imagerotate($this->src_img, -$degrees, $background=0);
    }
  }
  
  /**
   * Flip the image
   * @param int $flip
   */
  function imageflip($flip){
    if($this->imagic){
      if($flip == IMG_FLIP_VERTICAL){
        $this->src_img->flipImage();  //vertical mirror
      }else{
        $this->src_img->flopImage();  //Horizontal mirror
      }
    }else{
      if(version_compare(PHP_VERSION, "5.5.0") >= 0){
        imageflip($this->src_img , $flip);
        $this->dst_img = $this->src_img;
      }else{
        $this->_imageflip($this->src_img,$flip);
        $this->dst_img = $this->src_img;
      }
    }
  }
  
  function _imageflip(&$image, $flip){
    $width  = imagesx($image);
    $height = imagesy($image);
    // Truecolor provides better results, if possible.
    if (function_exists('imageistruecolor') && imageistruecolor($image))
    {
      $tmp = imagecreatetruecolor(1, $height);
    }
    else
    {
      $tmp = imagecreate(1, $height);
    }
    
    $x = $width - 1;
    for ($i = (int) floor(($width - 1) / 2); $i >= 0; $i--)
    {
    // Backup right stripe.         Dest   Source
      imagecopy($tmp,   $image,     0, 0,  $x-$i, 0, $x-$i, $height);
      // Copy left stripe to the right.
      imagecopy($image, $image, $x-$i, 0,  $i   , 0,    $i, $height);
      // Copy backuped right stripe to the left.
      imagecopy($image, $tmp,      $i, 0,  0    , 0,     1, $height);
    }
    imagedestroy($tmp);
    return true;
  }
  
  /**
   * Crop the image
   * @param number $x0
   * @param number $y0
   * @param number $x1
   * @param number $y1
   */
  function crop(){
    $x1 = $_GET['x1'];
    $y1 = $_GET['y1'];
    $x2 = $_GET['x2'];
    $y2 = $_GET['y2'];
    if($this->imagic){
      $this->src_img->cropImage( $x2-$x1,  $y2-$y1,  $x1,  $y1 );
      $this->src_img->setImagePage(0,0,0,0);
    }else{
      $this->makedst($x2-$x1, $y2-$y1);
      $ok = $this->resample(0,0,   $x1,$y1  ,$x2-$x1,$y2-$y1  ,$x2-$x1,$y2-$y1);
    }
  }
  /**
   * Resize the image
   */
  function resize(){
    $wp = isset($_GET['wp']) ? $_GET['wp']: 0;
    $hp = isset($_GET['hp']) ? $_GET['hp']: 0;

    if($wp >0 && $hp > 0 ){
      if(isset($_GET["prop"]) ){
        $hp = $wp;
      }
      $w = (int)( $this->dx * $wp / 100);
      $h = (int)( $this->dy * $hp / 100);
    }
    if($this->imagic){
      $this->src_img->resizeImage($w,$h,FILTER_LANCZOS , 1);
    }else{
      $this->makedst($w,$h );
      $ok = $this->resample(0,0, 0,0  , $w,$h ,  $this->dx,$this->dy);
    }
  }

  /**
   * Resample
   * @param int $dstx - destination x,y
   * @param int $dsty
   * @param int $srcx - source x,y
   * @param int $srcy
   * @param int $dstw - destination w,h
   * @param int $dsth
   * @param int $srcw - source w,h
   * @param int $srch
   * @return bool
   */
  function resample($dstx, $dsty, $srcx, $srcy, $dstw, $dsth, $srcw, $srch){
    //patch
    imagegammacorrect($this->src_img, 2.2, 1.0);
    $ok = imagecopyresampled($this->dst_img, $this->src_img, $dstx, $dsty, $srcx, $srcy, $dstw, $dsth, $srcw, $srch );
    imagegammacorrect($this->dst_img, 1.0, 2.2);
    return $ok;
  }
  /**
   * Make a contrast
   * @param unknown $cont
   */
  function contrast(){
    $cont = $_GET["contrast"];
    if($this->imagic){
      $c = 100*$cont/255;
      $this->src_img->brightnessContrastImage(0,$c);
    }else{
      $ok = imagefilter($this->src_img,IMG_FILTER_CONTRAST, -$cont);
      $this->makedst();
      $this->dst_img = $this->src_img;
    }
  }
  
  /**
   * Change brightness
   */
  function brightness(){
    $brightness = $_GET["brightness"];
    if($this->imagic){
      $br = 100*$brightness/255;
      $this->src_img->brightnessContrastImage($br,0);
    }else{
      $ok = imagefilter($this->src_img,IMG_FILTER_BRIGHTNESS, $brightness);
      $this->makedst();
      $this->dst_img = $this->src_img;
    }
  }
  /**
   * settings RGB
   */
  function rgb(){
    $red   = (float) ($_GET["red"]);
    $green = (float)($_GET["green"]);
    $blue  = (float)($_GET["blue"]);
    if($this->imagic){
      $colormatrix = array(
        1.0, 0.0, 0.0, 0.0, 0.0, $red/255,
        0.0, 1.0, 0.0, 0.0, 0.0, $green/255,
        0.0, 0.0, 1.0, 0.0, 0.0, $blue/255 ,
        0.0, 0.0, 0.0, 1.0, 0.0, 0.0,
        0.0, 0.0, 0.0, 0.0, 1.0, 0.0,
        0.0, 0.0, 0.0, 0.0, 0.0, 1.0
        
      );
      try{
        $this->src_img->colorMatrixImage($colormatrix);
      }catch(Exception $e){
        echo $e;
      }
    }else{
      $ok = imagefilter($this->src_img,IMG_FILTER_COLORIZE, $red, $green, $blue);
      $this->makedst();
      $this->dst_img = $this->src_img;
    }
  }
    
  function gamma(){
    $gammain  = $_GET['gammain'];
    $gammaout = $_GET['gammaout'];
    if($this->imagic){
      $this->src_img->gammaImage($gammain);
    }else{
      $ok = imagegammacorrect($this->src_img,$gammain, $gammaout);
      $this->makedst();
      $this->dst_img = $this->src_img;
    }
  }
  
  /**
   * Noise reduction
   */
  function denoise($maskWidth = 3, $maskHeight = 3){
    if($this->imagic){
      $this->src_img->despeckleImage();
    }else{
      $maskWidth  = (isset($_GET["maskWidth"]))  ? $_GET["maskWidth"]  : $maskWidth;
      $maskHeight = (isset($_GET["maskHeight"])) ? $_GET["maskHeight"] : $maskHeight;
      
      $scanLines = array();
      
      // precalculate some variables for better performance
      $maskWidth2 = (int)($maskWidth / 2);
      $maskHeight2 = (int)($maskHeight / 2);
      $maskMiddle = (int)(($maskWidth * $maskHeight) / 2);
      $maxY = $this->dy - $maskHeight2;
      $maxX = $this->dx - $maskWidth2;
     
      // scan every line of the image
      for($y = $maskHeight2; $y < $maxY; ++$y) {
        $medianY = $y + $maskHeight2;
        $maxI = $y + $maskHeight2 + 1;
        $minI = $y <= $maskHeight2 ? 0 : $y + 1;
        // cache few image lines in advance, to speed up further access.
        for($i = $minI; $i < $maxI; ++$i) {
          for($j = 0; $j < $this->dx; ++$j) {
            $scanLines[$i][$j] = imagecolorat($this->src_img, $j, $i);
          }
        }
        // unset old image lines from the cache.
        unset($scanLines[$medianY - $maskHeight]);
        for($x = $maskWidth2; $x < $maxX; ++$x) {
          $medianX = $x + $maskWidth2;
          $median = array();
          for($n = 0; $n < $maskHeight; ++$n) {
            for($m = 0; $m < $maskWidth; ++$m) {
              $median[] = $scanLines[$medianY - $n][$medianX - $m];
            }
          }
          sort($median, SORT_NUMERIC);
          imagesetpixel($this->src_img, $x, $y, $median[$maskMiddle]);
        }
      }
      $this->makedst();
      $this->dst_img = $this->src_img;
    }
  }
  /**
   * Save the image with original name
   * @param string $target
   */
  function save($target =''){
    if($this->imagic){
      $ok = $this->src_img->writeImage($this->source);
    }else{
      switch($this->type){
        case 'jpg':
        case 'jpeg':
          $ok = @Imagejpeg ($this->dst_img, $this->source,80);
          break;
        case 'png':
          $ok = @Imagepng ($this->dst_img,  $this->source,9);
          break;
        case 'wbmp':
          $ok = @Imagewbmp($this->dst_img,  $this->source);
          break;
        case 'gif':
          $ok = @imagegif($this->dst_img,   $this->source);
          break;
        default:
          $ok = true;
      }
    }

    return $ok;
  }
 
  /**
   * Output the image to browser
   */
  function display(){
    if($this->imagic){
      $this->src_img->getImage();
    }else{
      switch($this->type){
        case 'jpg':
        case 'jpeg':
          header("Content-type: image/jpeg");
          $ok = @Imagejpeg ($this->dst_img, null,80);
          break;
        case 'png':
          header("Content-type: image/png");
          $ok = @Imagepng ($this->$dst_img, null,9);
          break;
        case 'wbmp':
          header("Content-type: image/wbmp");
          $ok = @Imagewbmp($this->dst_img, null);
          break;
        case 'gif':
          header("Content-type: image/gif");
          $ok = @imagegif($this->dst_img, null);
          break;
        default:
          $ok = true;
      }
    }
  }
  
  function output(){
    $this->display();
  }
  /**
   * Make the png transparency
   * @param number $r
   * @param number $g
   * @param number $b
   */
  function transparency($r=0, $g=0, $b=0){
    if($this->type != "png"){
      return false;
    }
    //Make copy from original
  }
  /**
   * Make new destination image
   * @param number $dx
   * @param number $y
   */
  function makedst($dx = 0,$dy = 0){
    if($dx == 0 ) $dx = $this->dx;
    if($dy == 0 ) $dy = $this->dy;
    $this->dst_img = imagecreatetruecolor($dx,$dy);
  }
  
  function getWidth(){
    return $this->imagic ? $this->src_img->getImageWidth():imagesx($this->dst_img);
  }
  
  function getHeight(){
    return $this->imagic ? $this->src_img->getImageHeight():imagesy($this->dst_img);
  }
  
  /**
   * Backup the actual image into an undo queue
   * $_SESSION['smpl_idx'] shows the last saved element
   * $_SESSION['smpl_que'] shows the last index of the storage
   */
  function backup(){
    $from = $this->path.$this->tempname;
    $to   = $this->path.$this->tempname.( $_SESSION['smpl_idx'] + 1);
  
    $ok   = copy($from, $to);
    if($ok){
      $_SESSION['smpl_idx']++;
      for($i = $_SESSION['smpl_idx']; $i <= $_SESSION['smpl_que'] ;$i++){
        unlink($this->path.$this->tempname.$i);
      }
      $_SESSION['smpl_que'] = $_SESSION['smpl_idx'];
    }
  }
  
  /**
   * reload tha last image from queue
   */
  function undo(){
    if( $_SESSION["smpl_idx"] > 1 ){
      $from = $this->path.$this->tempname.($_SESSION["smpl_idx"]);
      $to   = $this->path.$this->tempname;
      $ok   = copy($from, $to);
      if($ok) {
        $_SESSION["smpl_idx"]--;
      }
    }
  }
  /**
   * Reload the next saved image from queue
   */
  function redo(){
    if( $_SESSION["smpl_idx"] < $_SESSION['smpl_que']){
      $from = $this->path.$this->tempname.($_SESSION["smpl_idx"]+1);
      $to   = $this->path.$this->tempname;
      
      $ok   = copy($from, $to);
      if($ok){
        $_SESSION["smpl_idx"]++;
      }
    }
  }
}