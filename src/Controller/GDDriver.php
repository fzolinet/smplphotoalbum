<?php
/**
 * GD2 driver for Simple photoalbum
 * https://www.phpied.com/image-fun-with-php-part-2/
 */
namespace Drupal\smplphotoalbum\Controller;
use GDImage;
/**
 * GD2 driver for this module
 */
class GDDriver {
  private $mp =""; // Module path
  private $path;
  private $ext;
  private $width = 0;
  private $height = 0;
  private $bt;    // Breaktime variable

  function __construct($path, &$bt, $size, $mp) {
    $this->path   = $path;
    $this->bt     = $bt;
    $this->mp     = $mp;
    $this->width  = @$size[0];
    $this->height = @$size[1];
  }

  /**
   * Get the size of image
   * @param string $src
   * @return array
   */
  function GetImageSize($src) {
    return GetImagesize ( $src );
  }

  /**
   * Make a GD image from image on the disk
   * @param string $src
   * @param string $extension
   * @return GDImage|resource
   */
  function ImageCreateFrom($src, $extension) {
    switch ($extension) {
      case 'avif' :
        $img = @imagecreatefromavif( $src );
        break;

      case 'jpg' :
      case 'jpeg' :
        $img = @ImageCreateFromJPEG( $src );
        break;

      case 'png' :
        $img = @ImageCreateFromPNG( $src );
        break;

      case 'wbmp' :
        $img = @ImageCreateFromwbmp( $src );
        break;

      case 'gif' :
        $img = @ImageCreateFromGif( $src );
        break;

      case "bmp" :
        $img = @imagecreatefrombmp( $src );
        break;

      case 'xbm' :
        $img = @imagecreatefromxbm( $src );
        break;

      case 'xpm' :
        $img = @imagecreatefromxpm( $src );
        break;

      case 'webp' :
        $img = @imagecreatefromwebp( $src );
        break;
    }
    return $img;
  }

  /**
   * Resize the Image
   * @param GDImage $src_im
   * @param int $dst_x
   * @param int $dst_y0
   * @param int $src_x
   * @param int $src_y
   * @param int $width
   * @param int $height
   * @param int $dx
   * @param int $dy
   * @return GDImage
   */
  function ImageCopyResized( $src_im, $dst_x, $dst_y0, $src_x, $src_y, int $width, int $height, $dx, $dy) {
    $dst_img = imagecreatetruecolor ( $width, $height );
    @imagecopyresized ( $dst_img, $src_im, $dst_x, $dst_y0, $src_x, $src_y, $width, $height, $dx, $dy );
    return $dst_img;
  }
  /**
   * Save Image to the HDD
   *
   * @param GDImage $img
   * @param string $path
   * @param string $type
   * @return boolean $ok
   */
  function Image($img, $path, $type = "jpg") {
    switch ($type) {
      case 'avif' :
        $ok = @imageavif ( $img, $path);
        break;

      case 'jpg' :
      case 'jpeg' :
        $ok = @imagejpeg ( $img, $path, 70 );
        break;

      case 'png' :
        $ok = @imagepng ( $img, $path, 9 );
        break;

      case 'wbmp' :
        $ok = @imagewbmp ( $img, $path );
        break;

      case 'gif' :
        $ok = @imagegif ( $img, $path );
        break;

      case 'bmp' :
        $ok = @imagebmp ( $img, $path );
        break;

      case 'xbm' :
        $ok = @imagexbm ( $img, $path);
        break;

      // case 'xpm' :
      // $ok = @imagexpm ( $img, $path, 100);
      // break;

      case 'webp' :
        $ok = @imagewebp ( $img, $path);
        break;

      default :
        $ok = False;
    }
    return $ok;
  }
  /************************ Enhance menu *********************************/
  function FaceEdit(){
  }
  /**
   * Red Eye
   * @param GDImage $img
   * @param number $x1
   * @param number $y1
   * @param number $x2
   * @param number $y2*
   * @return GDImage
   */
  function RedEye( $img, $x1, $y1, $x2, $y2 ){
    return $img;
  }

  /************************ Add menu *************************************/
  /**
   * WaterMark
   * @param GDImage $img
   * @param int $x1
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @param string $copyright
   * @param string $author
   * @param int $wmalpha
   * @param string $wmpath
   * @param string $color
   * @return GDImage
   */
  function Watermark($img, $x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0, $copyright="(C)", $author="PiQasso",
           $wmalpha = 10, $wmpath = '', $color ='grey')
  {
    $dxw = $this->width;
    $dyw = $this->height;

    $wmimg = @ImageCreateFromPNG ( $wmpath );
    if ($wmimg == null) {
      return $img;
    }
    // real size of original image
    $dx = ( int ) ($x2 - $x1);
    $dy = ( int ) ($y2 - $y1);
    $wmimg = imagescale ( $wmimg, $dx, $dy );
    imagefilter( $wmimg, IMG_FILTER_GRAYSCALE );
    imagealphablending( $wmimg, false );
    imagesavealpha( $wmimg, true );
    imagefilter( $wmimg, IMG_FILTER_BRIGHTNESS, ( int ) $wmalpha );
    imagecopymerge( $img, $wmimg, $x1, $y1, 0, 0, $dx, $dy, $wmalpha );

    if (PHP_MAJOR_VERSION < 8) {
      imagedestroy ( $wmimg );
    }
    return $img;
  }

  /**
   * Vignette oval
   * @param GDImage img
   * @param int $x1 - coordinates corners of border
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @param int $kind - The dark / blur
   * @param int $blursize
   * @param int $deep
   * @param int $sigma - if blur
   * @param int $radius - if blur
   * @param int $shape - ellipse or circle
   * @return GDImage
   */
  public function Vignette_oval($img, $x1, $y1, $x2, $y2, $kind="dark", $blursize=10, $deep=10, $sigma=1, $radius=10, $shape="circle") {
    $x1 = (int) $x1;
    $y1 = (int) $y1;
    $x2 = (int) $x2;
    $y2 = (int) $y2;

    $maxx = imagesx($img);
    $maxy = imagesy($img);

    if($kind == "dark"){
      $bri  = $this->bri(0, $blursize, $deep);
    } else{
      $sigma = (float) (20/$blursize);
      //$radius = $sigma * 0.5;
    }

    $cx = (int)(0.5*($x2+$x1));
    $cy = (int)(0.5*($y2+$y1));
    if($shape=="circle"){
      $r1 = (int) (0.5*min($x2-$x1, $y2-$y1));
      $r2 = $r1;
    }else{
      $r1= (int) (0.5*($x2-$x1));
      $r2= (int) (0.5*($y2-$y1));
    }

    $this->bt->setMax($blursize);

    for($i=0; $i <= $blursize; $i++ ){

      if($i % 10 == 0){
       if($this->bt->Break( $i )){
        break 1;
       }
      }
      $x1 = $cx-$r1;
      $x2 = $cx+$r1;
      $y1 = $cy-$r2;
      $y2 = $cy+$r2;
      $width  = 2*$r1;
      $height = 2*$r2;

      //Az eredetiről készítek egy másolatot
      $img_temp = $this->ImageCopy($img, 0, 0, 0, 0 , $maxx, $maxy);
      $img_temp = imagecrop($img_temp, ['x'=>$x1, 'y'=>$y1, 'width'=>$width, 'height'=>$height]);
      imagealphablending($img_temp, true);
      // Mask
      $red = 255;
      $green= 0;
      $blue = 0;
      $mask = imagecreatetruecolor($width, $height);
      $transparent = imagecolorallocate($mask, $red, $green, $blue);
      imagecolortransparent($mask, $transparent);
      imagefilledellipse($mask, $width/2, $height/2, $width-1, $height-1, $transparent);
      $red = imagecolorallocate($mask, 0,0,0);

      // $img_temp masked
      imagecopymerge($img_temp, $mask, 0,0,0,0, $width, $height,100);
      imagecolortransparent($img_temp, $transparent);
      imagefill($img_temp, 0,0,$transparent);

      //modify the outer image
      if($kind=="dark") {
        $bri = $this->bri($i);
        imagefilter( $img, IMG_FILTER_BRIGHTNESS, -$bri);
      }else{
        imagefilter( $img, IMG_FILTER_SMOOTH, $sigma);
      }

      //copy back to unmodified and masked $img_temp
      imagecopymerge($img, $img_temp, $x1, $y1,0,0, $width, $height, 100);
      imagecolortransparent($img, $transparent);

      if($x1>0) $x1--;
      if($y1>0) $y1--;
      if($x2<$maxx-1) $x2++;
      if($y2<$maxy-1) $y2++;
      $r1++;
      $r2++;
    }
    return $img;
  }

  /**
   * Helper for Vignette
   * @param int $x
   * @param float|int $size
   * @param int $deep
   * @return int
   */
  function Bri($x, $size = 0, $deep = 0){
    static $a, $b, $max;
    if($size !=0 ){
      $a = (float) $size;
    }
    if($deep != 0){
      $b = (float) $deep/15;
    }

    $bri = $a * $b / ( $x + $a );
    return (int) $bri;
  }
  /**
   * Vignette Rectangle
   *
   * @param GdImage $img - GdImage
   * @param int $deep  - darkest place
   * @param int $mode  - linar / reciprok / log
   * @param int $sizex - x width
   * @param int $sizey - y width
   * @return GdImage
   */
  public function Vignette_rect( GdImage $img, $x1, $y1, $x2, $y2, $kind = "dark", $blursize =10, $deep = 10, $sigma=1, $radius = 1) {
    $x1 = (int) $x1;
    $y1 = (int) $y1;
    $x2 = (int) $x2;
    $y2 = (int) $y2;

    $maxx = imagesx($img);
    $maxy = imagesy($img);

    $dx1  = $x1 / $blursize;
    $dy1  = $y1 / $blursize;
    $dx2  = ($maxx-$x2) / $blursize;
    $dy2  = ($maxy-$y2) / $blursize;

    if($kind == "dark"){
      $bri  = $this->bri(0, $blursize, $deep);
    }else{
      $sigma = (float)(20/$blursize);
      $radius = $sigma * 0.5; //$sigma;
    }

    $this->bt->setMax( $blursize );
    //
    for($i=0; $i < $blursize; $i++){
      if($i % 10 == 0){
        if($this->bt->Break($i )){
          break 1;
        }
      }
      $img_temp = $this->ImageCopy($img, 0, 0, 0, 0 , $maxx, $maxy);

      $width  = (int) ($x2-$x1);
      $height = (int) ($y2-$y1);
      $img_temp = imagecrop($img_temp, ['x'=>$x1, 'y'=>$y1, 'width'=>$width, 'height'=>$height]);

      if($kind=="dark") {
        $bri = $this->bri($i);
        imagefilter ( $img, IMG_FILTER_BRIGHTNESS, -$bri);
      }else{
        imagefilter($img, IMG_FILTER_SMOOTH, $sigma);
      }

      imagecopymerge( $img, $img_temp, $x1, $y1, 0, 0, $width, $height, 100 );
      //$this->tempjpeg($img, "tmp_$i.jpg");
      if($x1>0) $x1--;
      if($y1>0) $y1--;
      if($x2<$maxx-1) $x2++;
      if($y2<$maxy-1) $y2++;
    }
    if (PHP_MAJOR_VERSION < 8) {
      imagedestroy( $img_temp);
    }
    return $img;
  }

    /**
   * Make a border
   * @param GdImage $img
   * @param string $bordercolor
   * @param int $top - top width
   * @param int $ib
   * @param int $ob
   * @return GdImage
   */
  function Border( $img, $bordercolor,  $top, $ib=0, $ob=0, $height = 0){
    $w = imagesx( $img );
    $h = imagesy( $img );

    $br     = hexdec( substr( $bordercolor, 0,2 ) );
    $bg     = hexdec( substr( $bordercolor, 2,2 ) );
    $bb     = hexdec( substr( $bordercolor, 4,2 ) );

    $a2     = 0.9;
    $a3     = 0.2;
    $a4     = 0.4;
    $at     = 0.6;
    $col1 = imagecolorallocate($img, $br, $bg, $bb);
    $col2 = imagecolorallocate($img, (int)($br*$a2), (int)($bg*$a2), (int)($bb*$a2));
    $col3 = imagecolorallocate($img, (int)($br*$a3), (int)($bg*$a3), (int)($bb*$a3));
    $col4 = imagecolorallocate($img, (int)($br*$a4), (int)($bg*$a4), (int)($bb*$a4));
    $colt = imagecolorallocate($img, (int)($br*$at), (int)($bg*$at), (int)($bb*$at));

    // ------ outer bevel ------------
    $this->Frame($img, 0,0, $w, $h, $ob, $col1, $col2, $col3, $col4 );

    // ------------ top of border --------
    $this->Frame($img, $ob, $ob, $w-$ob, $h-$ob, $top, $colt, $colt, $colt, $colt );

    //--------- inner bevel --------------
    $this->Frame($img, $ob + $top, $ob + $top, $w-$ob-$top, $h-$ob-$top, $ib, $col3, $col4, $col1, $col2 );
    return $img;
  }

  /**
   * Helper function for Border
   *
   * @param int $x1
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @param int $d
   * @param int $c1
   * @param int $c2
   * @param int $c3
   * @param int $c4
   * @return
   */
  function Frame(&$img, $x1, $y1, $x2, $y2, $d, $c1, $c2, $c3, $c4){
    // left vertical
    $points = [$x1, $y1,   $x1, $y2,  $x1 + $d, $y2-$d,    $x1+$d, $y1+$d ];
    imagefilledpolygon($img, $points, $c1);

    // top horizontal
    $points = [ $x1, $y1,  $x1 + $d,  $y1 + $d,   $x2-$d, $y1+$d,    $x2, $y1 ];
    imagefilledpolygon($img, $points, $c2);

    // bottom horizontal
    $points = [$x1, $y2,   $x2, $y2,  $x2-$d, $y2-$d,    $x1+$d, $y2-$d ];
    imagefilledpolygon($img, $points, $c3);

    // right vertical
    $points = [ $x2, $y1,  $x2-$d, $y1+$d,  $x2-$d, $y2-$d,  $x2, $y2 ];
    imagefilledpolygon($img, $points, $c4);
  }

  /******************************* Geometry menu ***********************/

  /**
   * Rotate
   *
   * @param GDImage $img
   * @param int $degrees
   * @return GDImage
   */
  function Rotate($img, $degrees) {
    return imagerotate ( $img, - $degrees, 0 );
  }

  /**
   * Flip
   *
   * @param GDImage $img
   * @param int $flip
   * return GDImage
   */
  function Flip($img, $flip = IMG_FLIP_HORIZONTAL) {
    imageflip ( $img, $flip );
    return $img;
  }

  /**
   * Crop
   *
   * @param GDImage $img
   * @param int $x1
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @return GDImage
   */
  function Crop($img, $x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0 ) {
    return imagecrop (
      $img,
      [
        'x' => $x1,
        'y' => $y1,
        'width' => ( $x2 - $x1 ),
        'height' => ( $y2 - $y1 )
      ]
    );
  }

  /**
   * Resize the image
   * @param GDImage $img
   * @param number $wp - width
   * @param number $hp - height
   * @return GDImage
   */
  function Resize( $img, $wp, $hp ) {
    $wp = ( int ) $wp;
    $hp = ( int ) $hp;
    return imagescale ( $img, $wp, $hp );
  }

  /**
   * Shave the edge of images
   * @param GDImage $img
   * @param int $cols
   * @param int $rows
   * @return GDImage
   */
  function Shave( $img, $cols, $rows ){
    $w = imagesx($img);
    $h = imagesy($img);
    $x1 = $cols;
    $y1 = $rows;
    $x2 = $w - $cols;
    $y2 = $h - $rows;
    $img = $this->Crop($img, $x1, $y1, $x2, $y2);
    return $img;
  }

  /**
   * Perspective distortion
   * @param GDImage $img
   * @param mixed $points
   * @return GDImage
   */
  function Perspective($img, $points){
    return $img;
  }

  function Lens( $img, $a, $b, $c, $d, $centerx, $centery, $bkgcolor ="#ffffff", $bestfit = true ){
    return $img;
  }
  /**
   * Image Copy
   *
   * @param GDImage $img
   * @param int $destx - Destination coordinates
   * @param int $desty
   * @param int $x - source coordinates
   * @param int $y
   * @param int $w - width, height
   * @param int $h
   * @return GDImage
   */
  function ImageCopy($img, $destx, $desty, $x, $y, $w, $h) {
    if (PHP_MAJOR_VERSION >= 8) {
      $dst = imagecreatetruecolor( $w, $h );
    } else {
      $dst = imagecreate ( $w, $h );
    }
    @imagecopy ( $dst, $img, $destx, $desty, $x, $y, $w, $h );
    return $dst;
  }

  /**
   * Resample
   *
   * @param GDImage $src - Source image
   * @param int $dst_x - Destination x,y
   * @param int $dst_y
   * @param int $src_x - Source x,y
   * @param int $src_y
   * @param int $dst_w - Destination width, height
   * @param int $dst_h
   * @param int $src_w - Source width, height
   * @param int $src_h
   * @return GDImage
   */
  function Resample($imgsrc, $dst_x = 0, $dst_y = 0, $src_x = 0, $src_y = 0, $dst_w = 1, $dst_h = 1, $src_w = 1, $src_h = 1) {
    $imgdst = imagecreatetruecolor ( $dst_w, $dst_h );
    imagecopyresampled ( $imgdst, $imgsrc, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );
    return $imgdst;
  }
  /************************** Lighting menu **********************************/

  /************************** Correction menu ********************************/
  
  /************************** Color menu *************************************/
  /**
   * A képek fehér egyensúlyának (white balance, WB) beállítására több algoritmus létezik, 
   * amelyek különböző megközelítésekkel próbálják eltávolítani a színhőmérsékleti eltéréseket. 
   * Az alábbiakban néhány gyakran alkalmazott algoritmust ismertetek:
   * https://www.mathworks.com/help/images/comparison-of-auto-white-balance-algorithms.html
   * https://mattmaulion.medium.com/white-balancing-an-enhancement-technique-in-image-processing-8dd773c69f6
   * 
   * 1. Szürke világ (Gray World Assumption)
   * 2. Fehér-pont módszer (White Patch Retinex)
   * 
   * Empty - whitebalance
   * @param GDImage $img
   * @param string $mode - whitebalance mode
   * @return GDImage
   */
  function Whitebalance( $img, $mode = "gray", $exclude = 0 ){
    $width  = imagesx($img);
    $height = imagesy($img);
    if($mode == "gray")  
      $this->bt->SetMax( 3 * $width * $height );
    else
      $this->bt->SetMax( 2 * $width * $height );
   
    $this->bt->cnt = 0;
    $imgr = imagecreatetruecolor($width, $height);
    // max colors & avg color

    $max = $this->MaxBright( $img, $width, $height );
    if( !$max ) return $img; 

    if( $mode == "gray" ){
      $avg = $this->AvgBright($img, $width, $height );
      if( !$avg ) return $img;   
    }            
    
    for( $x=0; $x < $width; $x++ ){
      for( $y = 0; $y < $height; $y++ ){

        if( $this->bt->Break($this->bt->cnt++) ){
          break 2;
        };
        
        $rgba = $this->getColor($img, $x, $y);
        $bright = $rgba["red"] + $rgba["green"] + $rgba["blue"];
        switch($mode){
          case "white": 
            if( $bright <= $max["pwmax"] * ( 1 - $exclude/100 ) ){
              $r = (int) ( $rgba["red"]   * 255 / $max["r"] ) ;
              $g = (int) ( $rgba["green"] * 255 / $max["g"] ) ;
              $b = (int) ( $rgba["blue"]  * 255 / $max["b"] ) ;
              $r = ( $r > 255) ? 255: $r;
              $g = ( $g > 255) ? 255: $g;
              $b = ( $b > 255) ? 255: $b;    
            }else{ 
              $r = $rgba["red"];
              $g = $rgba["green"];
              $b = $rgba["blue"];
            }
            break;
          case "gray":
            if( $bright <= $max["pwmax"] * ( 1 - $exclude/100 ) &&
                $bright >= $max["pwmax"] * ( 1 - $exclude/100 )
            ){
              $r = (int) ( $avg["r"] * $rgba["red"] );
              $g = (int) ( $avg["g"] * $rgba["green"] );
              $b = (int) ( $avg["b"] * $rgba["blue"] );  
            }else{
              $r = $rgba["red"];
              $g = $rgba["green"];
              $b = $rgba["blue"];  
            }
            break;
        }
        
        $color = imagecolorallocate( $imgr,$r,$g,$b );
        imagesetpixel($imgr, $x, $y, $color);
      }
    }
    return $imgr;
  }
  /**
   * Max bright of pixels
   * @param GDImage $img
   * @param int $width
   * @param int $height   
   * @return array|bool
   */
  function MaxBright(&$img, $width, $height){
    $pw = 0;
    $max = [];
    $max["pwmin"] = 3*255;
    $max["pwmax"] = 0;
    
    for( $x=0; $x < $width; $x++ ){
      for( $y = 0; $y < $height; $y++ ){

        if( $this->bt->Break($this->bt->cnt++ ) ){          
          return false;
        }

        $rgba = $this->getColor($img, $x, $y);
        $pw = $rgba["red"] + $rgba["green"] + $rgba["blue"];
        if( $pw > $max["pwmax"] ){          
          $max["r"] = $rgba["red"];
          $max["g"] = $rgba["green"];
          $max["b"] = $rgba["blue"];
          $max["pwmax"] = $pw; // Max bright
        }

        if($pw < $max["pwmin"]){          
          $max["pwmin"] = $pw;
        }
      }
    }
    return $max;
  }
  /**
   * Summary of AvgBright
   * @param GDImage $img - 
   * @param int $width
   * @param int $height
   * @return bool|float[] - average pixels
   */
  function AvgBright(&$img, $width, $height){
    $r = $g = $b = 0;
    $max = [];
    $rgbavg = 0;
    for( $x=0; $x < $width; $x++ ){
      for( $y = 0; $y < $height; $y++ ){

        if($this->bt->Break($this->bt->cnt++) ){
          return false;
        }

        $rgba = $this->getColor($img, $x, $y);
        $r += $rgba["red"];
        $g += $rgba["green"];
        $b += $rgba["blue"];  
        $rgbavg  = ($r + $g + $b) / 3;
      }
    }
    $rgbavg /= ($width* $height ); // átlagos fényesség
    $r /= ( $width * $height );
    $g /= ( $width * $height );
    $b /= ( $width * $height );
    $avg["r"] = $rgbavg / $r;
    $avg["g"] = $rgbavg / $g;
    $avg["b"] = $rgbavg / $b;
    return $avg;
  }

  /**
   * Summary of Normalize - empty
   * http://www.giacobbe85.altervista.org/down_en/info/Software_and_scripts/Automatic_color_correction_in_images.php?lang=eng
   * @param mixed $img
   */
  function Normalize( $img, $channel, $mode = 1, $dynamic = 254 ) {
    if( $channel == "default") $channel = "all";
    $rmin = $gmin = $bmin = 255;
    $rmax = $gmax = $bmax = 0;
    $width  = imagesx($img);
    $height = imagesy($img);

    $this->bt->setMax( 2 * $width * $height );

    $cnt = 0;
    for( $x=0; $x < $width; $x++ ){
      for( $y=0; $y < $height; $y++ ){
        $rgba = $this->getColor( $img, $x, $y );

        //searches min & max
        if( in_array($channel, ["all","r"] ) && $rgba['red']   < $rmin ) $rmin = $rgba["red"];
        if( in_array($channel, ["all","g"] ) && $rgba['green'] < $gmin ) $gmin = $rgba["green"];
        if( in_array($channel, ["all","b"] ) && $rgba['blue']  < $bmin ) $bmin = $rgba["blue"];

        if(  in_array($channel, ["all","r"] ) && $rgba['red']   > $rmax ) $rmax = $rgba["red"];
        if(  in_array($channel, ["all","g"] ) && $rgba['green'] > $gmax ) $gmax = $rgba["green"];
        if(  in_array($channel, ["all","b"] ) && $rgba['blue']  > $bmax ) $bmax = $rgba["blue"];

        if( $this->bt->Break($cnt++ ) ) {
          return $img;
        }
      }
    }
    // ------- Normalization mode 2 -------
    $rp = $rmin;
    $gp = $gmin;
    $bp = $bmin;
    if( $rmax != $rp) $scalar = $dynamic / ( $rmax - $rp ); else $scalar = 1;
    if( $gmax != $gp) $scalag = $dynamic / ( $gmax - $gp ); else $scalag = 1;
    if( $bmax != $bp) $scalab = $dynamic / ( $bmax - $bp ); else $scalab = 1;

    // Normalization mode 1.
    if ($mode == 1){
      $sp = min( $rp, $gp, $bp );
      if( max( $rmax, $gmax, $bmax ) != $sp) $scala = $dynamic / (max( $rmax, $gmax, $bmax ) - $sp ); else $scala = 1;
      $rp = $gp = $bp = $sp;
      $scalar = $scalag = $scalab = $scala;
    }

    // Normalized image
    $imgr = imagecreatetruecolor($width, $height);
    for( $x = 0; $x < $width; $x++ ){
      for( $y=0; $y < $height; $y++ ){
        $rgba = $this->getColor($img, $x, $y);
        $red   = in_array( $channel, ["all","r"] ) ? ( $rgba["red"] - $rp )   * $scalar : $rgba["red"];
        $green = in_array( $channel, ["all","g"] ) ? ( $rgba["green"] - $rp ) * $scalar : $rgba["green"];
        $blue  = in_array( $channel, ["all","b"] ) ? ( $rgba["blue"] - $rp )  * $scalar : $rgba["blue"];
        $color = imagecolorallocate(
          $imgr,
           $red,
           $green,
           $blue
        );
        imagesetpixel($imgr, $x, $y, $color);

        if( $this->bt->Break($cnt++) ) {
          return $img;
        }
      }
    }
    return $imgr;
  }

  /**
   * Set the gamma of image
   * @param GDImage $img
   * @param float $gammain
   * @param float $gammaout
   * @param boolean $autogamma
   *
   * @return GDImage
   */
  function Gamma($img, $gammain, $gammaout, $autogamma) {
    imagegammacorrect ( $img, $gammain, $gammaout );
    return $img;
  }

  /**
   * Brightness
   * https://stackoverflow.com/questions/33001508/change-saturation-of-an-image-with-php-gd-library
   *
   * @param GDImage $img
   * @param int $brightness
   * @return GDImage
   */
  function Brightness($img, $bri = 0, $sat = 0, $hue = 0) {
    $bri = (int) $bri;
    $sat = (int) $sat;
    $hue = (int) $hue;
    if($bri != 0){
      imagefilter ( $img, IMG_FILTER_BRIGHTNESS, $bri );
    }

    if($sat != 0){
      $img = $this->Saturation($img, $sat);
    }

    if($hue != 0){
      imagealphablending($img, false);
      imagesavealpha($img, false);
      $img = $this->hue($img, $hue);
    }
    return $img;
  }

  /**
   * Hue
   * @param GdImage $img
   * @param int $hue
   * @return GdImage $img
   */
  function Hue($img, $hue){
    if( ( $hue % 360 ) == 0) return $img;

    $width  = imagesx( $img );
    $height = imagesy( $img );

    $i = 0;
    $step = (int) ($width / 10);
    $this->bt->setMax($step * $width * $height);

    for( $j = 0; $j<$step; $j++) {
      for( $x = $j; $x < $width; $x+=$step) {
        for( $y = 0; $y < $height ; $y++ ){
          if( $this->bt->Break($i++) ){
              break 3;
          }
          $rgba = $this->getColor($img, $x, $y);

          $h=0; // 0<=...<=360
          $s=0; // 0<=...<=1
          $l=0; // 0<=...<=1
          $this->rgb2hsl($rgba['red'], $rgba["green"], $rgba["blue"], $h, $s, $l);

          //Change hue
          $h += $hue ;
          $h = $h % 360;

          $this->hsl2rgb($h, $s, $l, $rgba['red'], $rgba["green"], $rgba["blue"] );
          imagesetpixel(
            $img,
            $x,
            $y,
            imagecolorallocatealpha(
              $img,
              $rgba['red'],
              $rgba["green"],
              $rgba["blue"],
              $rgba["alpha"]
            )
          );
        }
      }
    }
    return $img;
  }

  /**
   * Saturation
   * @param GdImage $img
   * @param int $sat
   * @return GdImage $img
   */
  function Saturation($img, $sat){
    $width  = imagesx($img);
    $height = imagesy($img);

    $satp   = $sat / 255;
    $cnt = 0;
    $step = (int) ($width / 10);
    $this->bt->setMax( $step * $width * $height );
    for($j = 0; $j<$step; $j++){
      for($x = $j; $x < $width; $x+=$step) {
        for($y = 0; $y < $height; $y++) {
          if($this->bt->Break($cnt++)){
            break 3;
          }
          $rgba = $this->getColor($img, $x, $y);

          $h=0; // 0<=...<=360
          $s=0; // 0<=...<=1
          $l=0; // 0<=...<=1
          $this->rgb2hsl($rgba["red"], $rgba["green"], $rgba["blue"], $h, $s, $l);

          //change saturation
          $s += $satp;
          if( $s < 0) $s = 0;
          elseif( $s > 1) $s = 1;

          $this->hsl2rgb($h, $s, $l, $rgba["red"], $rgba["green"], $rgba["blue"]);
          ///////////***************************  Hiba *******************//////////////
          $color = imagecolorallocatealpha(
            $img,
            $rgba["red"],
            $rgba["green"],
            $rgba["blue"],
            $rgba["alpha"]
          );
          imagesetpixel( $img, $x, $y, $color);
        }
      }
    }
    return $img;
  }

  /**
   * HSL color to RGB
   * @param int $h
   * @param float $s 0<= ... <=1
   * @param float $l 0<= ... <=1
   * @return
   */
  function hsl2rgb (&$h, &$s, &$l, &$r, &$g, &$b) {

    $c = ( 1 - abs(2 * $l - 1) ) * $s;
    $x = $c * (1 - abs( fmod( $h / 60, 2) - 1 ) );
    $m = $l - $c*0.5;

    if ($h < 60) {
      $r = $c;
      $g = $x;
      $b = 0;
    } elseif ($h < 120) {
      $r = $x;
      $g = $c;
      $b = 0;
    } elseif ($h < 180) {
      $r = 0;
      $g = $c;
      $b = $x;
    } elseif ($h < 240) {
      $r = 0;
      $g = $x;
      $b = $c;
    } elseif ($h < 300) {
      $r = $x;
      $g = 0;
      $b = $c;
    } else {
      $r = $c;
      $g = 0;
      $b = $x;
    }
    $r = (int) ( ($r + $m) * 255);
    $g = (int) ( ($g + $m) * 255);
    $b = (int) ( ($b + $m) * 255);
  }

  /**
   * Helper - rgb color to hsl
   * @param int $r
   * @param int $g
   * @param int $b
   * @return
   */
  function rgb2hsl (&$r, &$g, &$b, &$h, &$s, &$l) {
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $mm = $max + $min;
    $l = $mm * 0.5;
    //Hue
    if ($max == $min) {
      $h = $s = 0;
    } else {
      $d =  $max - $min;
      //$s = $l > 0.5*255 ? $d / (2*255 - $max - $min) : $d / ($max + $min);
      $s = $l > 127 ? $d / (511 - $mm) : $d / $mm ;
      switch($max){
        case $r: $h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 ); break;
        case $g: $h = ( $b - $r ) / $d + 2 ; break;
        case $b: $h = ( $r - $g ) / $d + 4; break;
      }
      $h *=60;
      $h = (int) $h;
    }
    $l /=255;
  }

  /**
   * RGB conversion
   * @param GdImage $img
   * @param int $r - red
   * @param int $g - green
   * @param int $b - blue
   * @param float $alpha - alpha channel
   * @return GdImage
   */
  function RGB($img, $r, $g, $b, $a) {
    $a = (int)(127 * $a/100);
    imagealphablending($img, true);
    imagefilter ( $img, IMG_FILTER_COLORIZE, $r, $g, $b, $a );
    return $img;
  }

  /**
   * set the contrast of image
   * @param GdImage $img
   * @param int $cont
   * @return GdImage
   */
  function Contrast($img, $cont) {
    imagefilter ( $img, IMG_FILTER_CONTRAST, - $cont*10 );
    return $img;
  }

  /**
   * Change the image to grayscale
   * @param GdImage $img
   * @return GdImage
   */
  function GrayScale($img) {
    imagefilter ( $img, IMG_FILTER_GRAYSCALE );
    return $img;
  }
  /**
   * White the image above the colors
   * @param mixed $img
   * @param mixed $r
   * @param mixed $g
   * @param mixed $b
   * @param mixed $a
   */
  function White($img, $r, $g, $b, $a){
    imagefilter($img,IMG_FILTER_COLORIZE, $r, $g, $b );
    return $img;
  }

  /**
   * Black and White
   * @param GdImage $img
   * @param mixed $level
   * @return GdImage
   */
  function BW($img, $level = 50){
    imagefilter($img, IMG_FILTER_GRAYSCALE);
    imagefilter($img, IMG_FILTER_CONTRAST, -$level*10);
    return $img;
  }

  function Charchoal($img, $radius = 5, $sigma=2 ){
    return $img;
  }

  /**
   * Oil painting
   *
   * @param GDImage object $img
   * @param mixed $radius
   * @return GDImage object
   */
  function Oil($img, $radius = 5, $intensity_level = 240){
    $width  = imagesx($img);
    $height = imagesy($img);

    $cur_int = 0;
    for ($i = 0;$i<255;$i++){
      $intensity_count[$i] = $sumR[$i] = $sumG[$i] = $sumB[$i] = 0;
    }

    $current_intensity = 0;
    $curMax = 0;
    $maxIndex = 0;
    //
    $img1   = imagecreatetruecolor($width, $height);

    //BrekTime settings    
    $this->bt->setMax( ( $width - 2*$radius ) * ( $height - 2 * $radius ) );
    $this->bt->setLog(true);

    $cnt = 0;
    for( $col = $radius; $col < $width - $radius; $col++ ){
      for( $row = $radius; $row < $height - $radius; $row++){
        
        if( $this->bt->Break( $cnt++ ) ){
            break 2;
        }

        for ( $i = 0; $i < 256; $i++ ){
          $intensity_count[$i] = $sumR[$i] = $sumG[$i] = $sumB[$i] = 0;
        }

        /* Calculates the highest intensity Neighbouring Pixels. */
        for( $y = - $radius ; $y < $radius; $y++ ){          
          for( $x = - $radius; $x < $radius; $x++ ){
            $rgba = $this->getColor( $img, $col + $x, $row + $y );

            $current_intensity  = (int)( ( ( $rgba["red"] + $rgba["green"] + $rgba["blue"] ) * $intensity_level / 3) / 255);
            $intensity_count[ $current_intensity ]++;

            $sumR[ $current_intensity ] += $rgba["red"];
            $sumG[ $current_intensity ] += $rgba["green"];
            $sumB[ $current_intensity ] += $rgba["blue"];
          }
        }

        $maxIndex = 0;
        $curMax = $intensity_count[$maxIndex];

        for($i = 0; $i < $intensity_level; $i++){
          if( $intensity_count[ $i ] > $curMax ){
            $curMax = $intensity_count[$i];
            $maxIndex = $i;
          }
        }

        if($curMax > 0){
          $r = $sumR[ $maxIndex ] / $curMax;
          $g = $sumG[ $maxIndex ] / $curMax;
          $b = $sumB[ $maxIndex ] / $curMax;
          $color = imagecolorallocate($img1, (int) $r, (int) $g, (int) $b);          
          imagerectangle($img1, $col - $radius, $row - $radius, $col + $radius-1, $row + $radius-1, $color );
        }
      }
    }
    return $img1;
  }

  /**
   * get the color of a point of image
   *
   * @param GDImage $img
   * @param int $x - coordinates
   * @param int $y
   * @return array() - [ 'r', 'g', 'b', 'alpha']
   */
  function getColor( &$img, $x, $y ){
    return imagecolorsforindex(
      $img,
      imagecolorat($img, (int) $x, (int) $y )
    );
  }

  /**
   * Change the image to sepia
   * @param GDImage $img
   * @param mixed $level - experimental value
   * @return GDImage
   **/
  function Sepia($img, $level = 80){
    $level = (int) (0.6*$level);
    imagefilter($img,IMG_FILTER_GRAYSCALE);
    imagefilter($img,IMG_FILTER_BRIGHTNESS,-10);
    imagefilter($img,IMG_FILTER_COLORIZE, $level, 0, -$level);
    return $img;
  }

  /**
   * Change the image to sepia
   * @param GDImage $img
   * @param mixed $level - experimental value
   * @return GDImage
   **/
  function BlueShift($img, $level = 15){
    $level *= 14;
    $img = $this->Saturation($img, -$level);
    return $img;
  }

  /**
   * Change the image to sepia
   * @param GDImage $img
   * @param mixed $level - experimental value
   * @return GDImage
   **/
  function Solarize($img, $treshold = 1){
    return $img;
  }

  function Clahe($img,$width=10, $height=10, $bins=29, $clip=100){
    return $img;
  }

  /**
   * Denoise
   * @param GDImage $img
   * @param int $smoothlevel
   * @param string $type
   * @param number $softness
   * @return GDImage
   */
  function Denoise($img, $level, $type="denoise", $softness = 0.0) {
    imagefilter ( $img, IMG_FILTER_SMOOTH, $level );
    return $img;
  }

  /**
   * Add Noise to image
   * @param GDImage $img
   * @param int $level
   * @param int $type - has no effect
   * @param string $channel - r, g, b, alpha, all
   * @return GDImage
   */
  function AddNoise($img, $level = 1, $type = "uniform", $channel = "all") {
    if($channel == "default")
      $channel = "all";

    $width  = imagesx ( $img );
    $height = imagesy ( $img );

    $random = new Random($type);
    $cnt=0;
    $step = (int) ($width / 10);
    $this->bt->setMax( $step * $width * $height);

    for( $j = 0; $j< $step; $j++ ){
      for( $x = $j; $x < $width; $x += $step ) {
        for( $y = 0; $y < $height; $y++) {
          
          if( $this->bt->Break($cnt++) ) break 3;

          if (mt_rand(0,100) > $level) {
            continue;
          }

          $rgba = $this->getColor( $img, $x, $y);
          if(in_array( $channel, ["all","r"] ) ){
            $rgba["red"] += $random->get( -$level, $level );
          }
          if(in_array( $channel, ["all","g"] ) ){
            $rgba["green"] += $random->get( -$level, $level );
          }
          if(in_array( $channel, ["all","b"] ) ){
            $rgba["blue"] += $random->get( -$level, $level );
          }
          if(in_array( $channel, ["all","alpha"] )){
            $rgba["alpha"] += (int) ($random->get( -$level, $level) * 0.5 );
          }

          $rgba["red"]   = ( $rgba["red"]   > 255 ) ? 255 : ( ( $rgba["red"]   < 0 ) ? 0 : (int) ( $rgba["red"]   ) );
          $rgba["green"] = ( $rgba["green"] > 255 ) ? 255 : ( ( $rgba["green"] < 0 ) ? 0 : (int) ( $rgba["green"] ) );
          $rgba["blue"]  = ( $rgba["blue"]  > 255 ) ? 255 : ( ( $rgba["blue"]  < 0 ) ? 0 : (int) ( $rgba["blue"]  ) );
          $rgba["alpha"] = ( $rgba["alpha"] > 127 ) ? 127 : ( ( $rgba["alpha"] < 0 ) ? 0 : (int) ( $rgba["alpha"] ) );

          $color = Imagecolorallocatealpha ( $img, $rgba["red"], $rgba["green"] , $rgba["blue"], $rgba["alpha"] );
          ImageSetPixel ( $img, $x, $y, $color );
        }
      }
    }
    return $img;
  }

  /**
   * Emboss
   * @param GDImage $img
   * @param number $radius
   * @param number $sigma
   * @return GDImage
   */
  function Emboss($img, $radius = 1, $sigma = 1.0, $level=1) {
    $this->bt->setMax($level);
    $this->bt->setFreq( 1 );    
    for($i=0; $i < $level; $i++){
      if( $this->bt->Break($i) ) break 1;      
      imagefilter ( $img, IMG_FILTER_EMBOSS );
    }
    return $img;
  }

  /**
   * Edge filter
   * https://code.tutsplus.com/tutorials/php-gd-image-manipulation-beyond-the-basics--cms-31766
   * @param GDImage $img
   * @param number $radius
   * @return GDImage
   */
  function Edge( $img, $level = 1.0){
    $this->bt->setMax(5*$level );
    $this->bt->setFreq(2 );
    for($i=0; $i < 5*$level; $i++){
      if( $this->bt->Break($i) )   break 1;

      imagefilter($img, IMG_FILTER_EDGEDETECT);
    }
    return $img;
  }

  /**
   * Blur the image
   * @param GDImage $img
   * @param string $type
   * @param int $radius
   * @param int $sigma
   * @param string $channel
   * @param float $angle
   * @param int $level
   * @return GDImage image
   */
   function Blur($img,  $radius = 2, $sigma = 1,  $type="blur", $channel="default", $angle=0, $level=1) {
    $this->bt->setMax(5*$level);
    $this->bt->setFreq( 2 );
    for($i=0; $i < 5*$level; $i++){
      if( $this->bt->Break($i)) break 1;
      
      imagefilter ( $img, $type == "gaussianblur" ? IMG_FILTER_GAUSSIAN_BLUR : IMG_FILTER_SELECTIVE_BLUR );
    }
    return $img;
  }
  /**
   *
   * @param GDImage $img
   * @param float|int $radius
   * @param float|int $sigma
   * @param float |int $level
   * @return GDImage
   */
  function Sharp($img, $radius, $sigma, $level) {
    $level *= 0.2;
    $middle = 4 * $level+1;
    $matrix = array (
      array (0, -1*$level, 0 ),
      array (-1*$level, $middle, -1*$level),
      array (0, -1*$level,0 )
    );
    $div = $this->_div ( $matrix );
    imageconvolution ( $img, $matrix, $div, 0 );
    return $img;
  }

  /**
   * Helper - Matrix
   * @param array $m
   * @return float
   */
  function _div($m) {
    return array_sum ( $m [0] ) + array_sum ( $m [1] ) + array_sum ( $m [2] );
  }

  /**
   * Convolution function
   * @param GDImage $img
   * @param int $c00
   * @param int $c01
   * @param int $c02
   * @param int $c10
   * @param int $c11
   * @param int $c12
   * @param int $c20
   * @param int $c21
   * @param int $c22
   * @param int $div
   * @param int $off
   * @return GDImage
   */
  function Convolution($img, $matrix, $div, $off) {
    $div = $this->_div($matrix);
    imageconvolution ( $img, $matrix, $div, $off );
    return $img;
  }

  /**
   * Wave effect
   * @param mixed $img 
   * @param int $amplitude 
   * @param int $wavelength 
   * @return resource|GDImage|false 
   */
  function Wave( $img, $amplitude=5, $wavelength=20 ){    
    $width  = imagesx( $img );
    $height = imagesy( $img );
    $this->bt->SetMax( $width * $height );
    $imgr  = imageCreateTrueColor( $width, $height + 2*$amplitude);
    define("PI2", 2 * M_PI );
    $h1 = $height - $amplitude;
    for( $x = 0; $x < $width; $x++ ){
      for($y = 0; $y < $height; $y++ ){
        if($this->bt->Break($this->bt->cnt++)) break 2;

        $color0 = $this->getColor($img, $x, $y);        
        $color1 = imagecolorallocate($imgr, $color0["red"], $color0["green"], $color0["blue"]);
        $y1 = $amplitude + $y + $amplitude * sin( PI2 * $x / $wavelength );         
        imagesetpixel( $imgr, $x, (int) $y1, $color1 );
      }
    }
    return $imgr;
  }


  /**
   * Summary of Swirl
   * @param GDImage $img
   * @param int $angle
   * @return bool|GDImage|resource
   */
  function Swirl($img, $angle){
    $width  = imagesx( $img );
    $height = imagesy( $img );  

    // change into radian
    $rad_d = $this->DegtoRad( $angle );
    
    // Radius
    $w2 = (int) ( $width / 2 );
    $h2 = (int) ( $height / 2 );    
    if($h2 < $w2){
      $rmin = $h2;
      $xs = $w2 - $rmin;
      $xe = $w2 + $rmin;
      $ys = 0;
      $ye = $height;
    } else{
      $rmin = $w2;
      $xs = 0;
      $xe = $width;
      $ys = $h2 - $rmin;
      $ye = $h2 + $rmin;      
    }
    
    // BreakTime
    $setmax = ( $xe-$xs )*( $ye-$ys );
    $this->bt->SetMax( $setmax);
    $this->bt->SetFreq( (int) ( $setmax / 100 ) );

    $imgr = imagecreatetruecolor($width, $height);
    imagecopy($imgr, $img, 0, 0, 0, 0, $width, $height);

    for($x = $xs; $x < $xe; $x++ ){ 
      for($y = $ys; $y < $ye; $y++ ){           
        if( $this->bt->Break( $this->bt->cnt++ ) ) {
          break 2;        
        }

        $crd = $this->getPolar( $w2, $h2, $x, $y );
        if( $crd["r"] <= $rmin ){
          $color = $this->getColor( $img, $x, $y );
          $color1 = imagecolorallocate($imgr, $color["red"], $color["green"], $color["blue"] );
          
          $rad = $crd["rad"] - $rad_d * (1-  $crd["r"] / $rmin);
          $t = $this->getXY($w2, $h2, $crd["r"], $rad );
          imageSetPixel( $imgr, (int) $t["x"], (int) $t["y"] , $color1);                   
        }
      }
    }  
    return $imgr;
  }

  /**
   * get x and y coord fron polar coords
   * @param mixed $cx
   * @param mixed $cy
   * @param mixed $r
   * @param mixed $rad
   * @return array<float|int>
   */
  function getXY($cx, $cy, $r, $rad){
    $k["x"] = $cx + (int) ( $r * cos( $rad ) );
    $k["y"] = $cy + (int) ( $r * sin( $rad ) );
    return $k;
  }

  /**
   * Convert radian to degree
   * @param float $deg
   * @return float
   */
  private function DegToRad($deg){
    return  $deg * M_PI / 180;
  }
  /**
   * Get polar coordinates from descartes
   * @param mixed $cx
   * @param mixed $cy
   * @param mixed $x
   * @param mixed $y
   * @return array{r: float, rad: float}
   */
  private function getPolar($cx, $cy, $x, $y){
    if($x > $cx){
      if($y == $cy){    // x tengely
        $r = abs($y-$cy);
        $a = 0;
      }else if( $y > $cy){
        $a = atan( ( $y - $cy ) / ( $x - $cx ) );
        $r = sqrt( ( $x - $cx )*( $x-$cx ) + ( $y-$cy )*( $y-$cy ) );  
      }else {
        $a = atan( ( $y - $cy) / ($x-$cx) );
        $r = sqrt( ($x-$cx)*($x-$cx) + ($y-$cy)*($y-$cy) );  
      }
    }else if ($x < $cx){      
      if($y == $cy){    // x tengely
        $r = abs($y-$cy);
        $a = M_PI;
      }else if( $y > $cy){
        $a = atan( ( $y - $cy ) / ( $x - $cx ) ) + M_PI;
        $r = sqrt( ( $x - $cx )*( $x-$cx ) + ( $y-$cy )*( $y-$cy ) );  
      }else {
        $a = atan( ( $y - $cy) / ($x-$cx) )+M_PI;
        $r = sqrt( ($x-$cx)*($x-$cx) + ($y-$cy)*($y-$cy) );  
      }
    
    }else{
      $r = abs($y-$cy);
      if( $y > $cy )       $a = M_PI_2;
      else if ( $y < $cy ) $a = 3*M_PI_2;
      else                 $a = 0;
    }
    return ["rad" => $a, "r" => $r];
  }
  
  /**
   * Make a histogram
   * @param GDImage &$img 
   * @return array {red: string|int, green: string|int, blue: string|int, avgr: int, avgg: int, avgb: int, maxrval: unset|mixed, maxgval: unset|mixed, maxbval: unset|mixed} 
   */
  function Histogram( &$img, $equalize = false ){
    $width  = imagesx ( $img );
    $height = imagesy ( $img );
    $this->bt->SetMax( 2 * $width * $height + 3 * 256 );
    return $this->getHistogram( $img , $width, $height, false, 0, $equalize);
  }

  /**

  * get the histogram
   * @param GDImage &$img 
   * @param int $width 
   * @param int $height 
   * @param bool $onlymax 
   * @param int $cnt - How many ppercent leaves out 
   * @return array{red: string|int, green: string|int, blue: string|int, avgr: int, avgg: int, avgb: int, maxrval: unset|mixed, maxgval: unset|mixed, maxbval: unset|mixed}
   */
  function getHistogram( &$img, $width, $height, $onlymax = false, $cnt = 0 ){
    $w = 256;
    $h = 120;

    for ($i = 0; $i < 256; $i++ ){
      $red[$i] = $green[$i] = $blue[$i] = 0;
    }
        
    $maxr = $maxg = $maxb = 0;
    $avgr = $avgg = $avgb = 0;
    for( $x = 0; $x < $width; $x++ ){
      for( $y = 0; $y < $height; $y++ ){
        
        $rgba = $this->getColor($img, $x, $y);

        $avgr += $rgba["red"];
        $avgg += $rgba["green"];
        $avgb += $rgba["blue"];

        $red[ $rgba["red"] ]++;
        if( $red[ $rgba["red"] ] > $maxr ) {
          $maxr = $red[ $rgba["red"] ]; //Leggyakoribb vörös pixeé
          $maxrval = $rgba["red"];
        }

        $green[ $rgba["green"] ]++;
        if( $green[ $rgba["green"] ] > $maxg ) {
          $maxg = $green[ $rgba["green"] ];
          $maxgval = $rgba["green"];
        }

        $blue[ $rgba["blue"] ]++;
        if( $blue[ $rgba["blue"] ] > $maxb ){
          $maxb = $blue[ $rgba["blue"] ];
          $maxbval = $rgba["blue"];
        } 

        if( $this->bt->Break($this->bt->cnt++) ){
          break 2;
        };
      }
    }

    $avgr /= ( $width* $height );
    $avgg /= ( $width* $height );
    $avgb /= ( $width* $height );
    
    // Return only max | avg values 
    if( $onlymax ){
      return [
        "red"   => (int) $maxr, 
        "green" => (int) $maxg, 
        "blue"  => (int) $maxb, 
        "avgr"  => (int) $avgr, 
        "avgg"  => (int) $avgg, 
        "avgb"  => (int) $avgb,
        "maxrval" => $maxrval, 
        "maxgval" => $maxgval,
        "maxbval" => $maxbval
      ];
    }

    // Red
    $c = 100 / $maxr ;
    $imgr  = imageCreateTrueColor($w, $h);
    $r     = imagecolorallocate($imgr, 255,0,0);
    $black = imagecolorallocate($imgr, 0,0,0);
    imagefill($imgr, 1,1,$black);
    for( $x=0; $x < 256; $x++ ){
      $color = imagecolorallocate($imgr, $x, 0, 0);
      imagefilledrectangle( $imgr, $x, 100, $x, $h, $color );
      imageline($imgr, $x, 100 - (int)($c * $red[ $x ]), $x, 100, $r);
      if( $this->bt->Break($this->bt->cnt++) ){
        break 1;
      };
    }

    ob_start();
      imagejpeg($imgr);
      $imgrdata = ob_get_contents();
    ob_end_clean();
    $imgred = base64_encode($imgrdata);

    // Green
    $c = 100 / $maxg;
    $imgg  = imageCreateTrueColor($w, $h);
    $g     = imagecolorallocate($imgg, 0,255,0);
    $black = imagecolorallocate($imgg, 0,0,0);
    imagefill($imgg, 1,1,$black);
    for( $x=0; $x < 256; $x++ ){
      $color = imagecolorallocate($imgg, 0,$x, 0);
      imagefilledrectangle( $imgg, $x, 100, $x, $h, $color );
      imageline($imgg, $x, 100 - (int)($c * $green[ $x ]), $x, 100, $g);
      if( $this->bt->Break($this->bt->cnt++) ){
        break 1;
      };
    }

    ob_start();
      imagejpeg($imgg);
      $imggdata = ob_get_contents();
    ob_end_clean();
    $imggreen = base64_encode($imggdata);

    // Blue
    $c = 100 / $maxb;
    $imgb  = imageCreateTrueColor($w, $h);
    $b     = imagecolorallocate($imgb, 0,0,255);
    $black = imagecolorallocate($imgb, 0,0,0);
    imagefill($imgb, 1,1,$black);
    for( $x=0; $x < 256; $x++ ){
      $color = imagecolorallocate($imgb, 0,0, $x);
      imagefilledrectangle( $imgb, $x, 100, $x, $h, $color );
      imageline($imgb, $x, 100 - (int)($c * $blue[ $x ]), $x, 100, $b);
      if( $this->bt->Break($this->bt->cnt++) ){
        break 1;
      };
    }

    ob_start();
      imagejpeg($imgb);
      $imgbdata = ob_get_contents();
    ob_end_clean();
    $imgblue = base64_encode($imgbdata);

    // JSON datas
    return [ "red"=> $imgred, "green" => $imggreen, "blue" => $imgblue ];
  }


  /**
   * Save image
   * @param GDImage $img
   * @param object $cfg
   * @param string $name
   * @param string $type

   * @return boolean
   */
  function Save($img, &$cfg, $name = "filename", $type = "jpeg") {
    $ok = False;
    switch ($type) {

      case 'avif' :
        $quality = $cfg->get ( 'avif');
        if (empty ( $quality ))
          $quality = - 1;
        $ok = Imagejpeg ( $img, $name, $quality );
        break;

      case 'jpg' :
      case 'jpeg' :
        $quality = $cfg->get ( 'jpeg' );
        if (empty ( $quality ))
          $quality = - 1;
        $ok = Imagejpeg ( $img, $name, $quality );
        break;

      case 'png' :
        $quality = $cfg->get ( 'png' );
        if (empty ( $quality ))
          $quality = - 1;
        $ok = @Imagepng ( $img, $name, $quality, PNG_ALL_FILTERS );
        break;

      case 'wbmp' :
        $ok = @Imagewbmp ( $img, $name );
        break;

      case 'gif' :
        $ok = @imagegif ( $img, $name );
        break;

      case 'bmp' :
        $ok = @imagebmp ( $img, $name );
        break;

      case 'xbm' :
        $quality = $cfg->get ( 'xbm');
        if (empty ( $quality ))
          $quality = - 1;
        $ok = Imagexbm( $img, $name, $quality );
        break;

      case 'xpm' :
        $quality = $cfg->get ( 'xpm');
        if (empty ( $quality ))
          $quality = - 1;
          $ok = Imagejpeg( $img, $name, 100);
          break;

      case 'webp' :
        $quality = $cfg->get ( 'webp');
        if (empty ( $quality ))
          $quality = - 1;
          $ok = Imagewebp( $img, $name, $quality );
          break;

      default :
        $ok = true;
    }
    return $ok;
  }

  function ImageCreateTrueColor($width, $height) {
    return @ImageCreateTrueColor ( $width, $height );
  }

  function getWidth($img) {
    return imagesx ( $img );
  }

  function getHeight($img) {
    return imagesy ( $img );
  }

  function tempjpeg(&$img, $name){
    imagejpeg($img, $this->path.$name);
  }
  function setType( $ext ){
    if($ext == "jpg") $ext = "jpeg";
    $this->ext = $ext;
  }
}