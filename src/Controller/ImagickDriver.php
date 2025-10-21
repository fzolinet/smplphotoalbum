<?php
/**
 * Imagick Driver for Simple photoalbum
 */
namespace Drupal\smplphotoalbum\Controller;
use Imagick;
use ImagickPixel;
use ImagickDraw;

/**
 * Imagick Driver
 *
 * @author fz
 */
class ImagickDriver{
  private $bt;    // BreakTime
  public $img;
  private $mp = ''; // Module path
  private $path;
  private string $ext;
  private $sign;
  private $sess;
  public $src = '';
  private $width = 0;
  private $height = 0;
  private $ver;

  /**
   *
   * @param string $src
   */
  function __construct( $path, &$bt, $size, $mp) {
    $this->path   = $path;    
    $this->bt     = $bt;
    $this->width  = @$size[0];
    $this->height = @$size[1];
    $this->mp     = $mp;        
    $v            = Imagick::getVersion();
    preg_match('/ImageMagick ([0-9]+\.[0-9]+\.[0-9]+)/', $v['versionString'], $v);
    $this->ver = $v[1];
  }

  /**
   * Read an Imagick obj
   * @param string $src
   */
  function make($src = '') {
    if(! empty( $src ) && empty( $this->img )) {
      $this->src = $src;
      $this->img = new Imagick( $src );
    }
  }

  /**
   * Get the size of Imagick obj
   * @param string $src
   * @return []
   */
  function GetImageSize($src = '') {
    $this->make( $src );
    $size = $this->img -> getImageGeometry();
    $size[0] = $size['width'];
    $size[1] = $size['height'];
    return $size;
  }

  /**
   * Create Imagick Image From src
   * @param string $src
   * @param string $ext
   * @return Imagick obj
   */
  function ImageCreateFrom($src="", $ext = '') {
    $this->make( $src );
    return $this->img;
  }

  /************************ Enhance menu *********************************/
  function FaceEdit($img){
    return $img;
  }
  /**
   * Red Eye
   */
  function RedEye($img, $x1, $y1, $x2, $y2){
    $img2 = $img->clone();
    $img3 = $img->clone();

    $img2->modulateImage(15,0,100);
    $img3->colorfloodfillImage( new ImagickPixel('white'), 18, new ImagickPixel('white') );

    $draw = new ImagickDraw();
    $draw->setStrokeWidth(0);
    $draw->setstrokeColor( new ImagickPixel('black') );
    $draw->setFillColor( new ImagickPixel('black') );
    //$draw->setFillAlpha(1);
    $draw->ellipse(0.5*($x1+$x2), 0.5*($y1+$y2), 0.5*($x1-$x1), 0.5*($y2-$y1), 0, 360);
    $img3->drawImage($draw);
    return $img;
  }
  /************************ Add menu *************************************/

  /**
   * Watermark on the image
   *
   * @param Imagick object  $img
   * @param int $x1
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @param string $watermark
   * @param string $author
   *
   * @param number $wmalpha
   * @param string $wmpath
   * @param string $wmtext
   * @param string $wmtext
   * @param float $wmalpha
   * @param string $wmpath
   * @param string $wmcolor
   * @return Imagick object
   */
  function Watermark( $img, $x1=0, $y1=0, $x2=0, $y2=0, $copyright="", $author="", $alpha=50, $wmpath = "", $color = "grey") {
    $wmimg = new Imagick();
    $wmimg->readImage( $wmpath );

    // real size of original image
    $dx = ( int ) ($x2 - $x1);
    $dy = ( int ) ($y2 - $y1);

    // $wmimg->scaleImage( $dx, $dy);
    $filter = Imagick::FILTER_GAUSSIAN;
    $blur = true;
    $wmimg->resizeImage( $dx, $dy, $filter, $blur );
    $wmimg->modulateImage($alpha, 10.0, 0.0);
    $wmimg->setImageType( Imagick::IMGTYPE_GRAYSCALEMATTE );

    // from here: https://www.sitepoint.com/adding-text-watermarks-with-imagick/
    $watermark = new Imagick();

    // Watermark text
    $text = $copyright.PHP_EOL.$author;

    // Create a new drawing palette
    $draw = new ImagickDraw();
    $watermark->newImage( $dx, $dy, new Imagickpixel( 'none' ) );

    // Set font properties
    $draw->setFont($this->mp . "/image/arial.ttf");
    $draw->setFillColor( new ImagickPixel( $color ) );
    $draw->setFontSize( (int) ($dy / 20) );
    $draw->setFillOpacity( (float) $alpha / 100 );

    // Position text at the bottom right of the image
    $draw->setGravity( Imagick::GRAVITY_NORTHWEST );

    // Draw text on the watermark palette
    $px = 10;
    $py = 10;
    $watermark->annotateImage( $draw, $px, $py, 0, $text );

    // Position text at the bottom right of the watermark
    $draw->setGravity( Imagick::GRAVITY_SOUTHEAST );

    // Draw text on the watermark
    $watermark->annotateImage($draw, $px+5, $py+5, 0, $text);
    // Repeatedly overlay watermark on image
    for ($w = 0; $w < $dx; $w += $dx/3) {
      for ($h = 0; $h < $dy; $h += $dy/4) {
        $img->compositeImage( $watermark, Imagick::COMPOSITE_OVER, (int) $w, (int) $h );
      }
    }

    // Overlay watermark on image
    $img->compositeImage( $watermark, Imagick::COMPOSITE_DISSOLVE, 0, 0 );

    //Exif information
    if($this->ext == "png" ){
      $img->setImageProperty("Exif:copyright", $copyright);
      $img->setImageProperty("Exif:author", $author);
    } else if($this->ext == "png") {
      // Does not works
      //$img->setImageProperty("Exif:copyright", $copyright);
      //$img->setImageProperty("Exif:author", $author);

    }
    return $img;
  }

  /**
   * https://stackoverflow.com/questions/17846665/black-color-of-imagemagick-vignette
   * @param Imagick Image $img
   * @param number $x1
   * @param number $y1
   * @param number $x2
   * @param number $y2
   * @param string $kind
   * @param number $blursize
   * @param number $deep - darken
   * @param number $sigma - blur
   * @param number $radius
   * @return Imagick Image
   */
    function Vignette_oval( $img, $x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0, $kind="dark", $blursize = 10,
                            $deep = 10, $sigma = 1, $radius = 1, $shape = "circle" ){
    $x1 = (int) $x1;
    $y1 = (int) $y1;
    $x2 = (int) $x2;
    $y2 = (int) $y2;

    $width  = $img->getImageWidth();
    $height = $img->getImageHeight();

    if($kind == "dark"){
      if(version_compare($this->ver,'7.0.0')>=0){
        $bri = $this->bri($deep);
      }else{
        $bri = $this->bri($deep);
      }
    } else{
      $sigma = (float) (20 / (float) $blursize);
      $bri = 0;
    }

    $cx = (int)( 0.5*( $x2 + $x1 ) );
    $cy = (int)( 0.5*( $y2 + $y1 ) );
    if($shape=="circle"){
      $rx = (int) (0.5*min($x2-$x1, $y2-$y1));
      $ry = $rx;
    }else{
      $rx= (int) ($x2-$cx);
      $ry= (int) ($y2-$cy);
    }

    $this->bt->setMax($blursize);
    for( $i = (int) $blursize; $i >= 0; $i--){
      if($i % 2 == 0 && $this->bt->Break( (int) $blursize - $i) ){
        break 1;
      }
      $r1 = $rx + $i;
      $r2 = $ry + $i;
      if($r1 < 1 || $r2<2){
        break 1;
      }
      $mask = new Imagick();
      $mask->newPseudoImage( $width, $height, 'canvas:transparent');

      $draw = new ImagickDraw();
      $fillcolor = new Imagickpixel();
      $fillcolor->setColor("white");
     // $draw->setImageBorderColor( $fillcolor );
      $draw->setFillColor( $fillcolor );
      $draw->ellipse($cx, $cy, $r1, $r2, 0,360);
      $mask->drawImage($draw);

      if(version_compare($this->ver,'6.3.6') >=0 ){
        $img->setImageClipMask( $mask) ;
      }else{
        $img->setImageMask($mask, 2 );  // Imagick::PIXELMASK_WRITE
      }

      if($kind=="dark") {
        //$img->modulateImage( $bri, 100.0, 100.0 );
        if(version_compare($this->ver, "7.0.0")>=0){
          $img->brightnessContrastImage( -$bri * 0.01, 0.0, Imagick::CHANNEL_ALL );
        }else{
          $img->brightnessContrastImage( -$bri * 0.005, 0.0, Imagick::CHANNEL_ALL );
        }

      }else{
        //$img->blurImage($radius, $sigma, Imagick::CHANNEL_ALL);
        $img->adaptiveblurImage($radius, $sigma, Imagick::CHANNEL_ALL);
      }
    }
    return $img;
  }
  function colorhex($r,$g,$b){
    return "#".str_pad(dechex($r),2,"0",STR_PAD_LEFT).str_pad(dechex($g),2,"0",STR_PAD_LEFT).str_pad(dechex($b),2,"0",STR_PAD_LEFT);
  }
  function tempjpeg($img, $name, $i){
    $img->setImageFormat("jpg");
    $img->writeImage($this->path.$name."_".sprintf("%03s", $i).".jpg");
  }

  /**
   * Vignette rectangle
   * @param Imagick Image $img
   * @param number $x1
   * @param number $y1
   * @param number $x2
   * @param number $y2
   * @param int $blursize
   * @param int $deep - darken
   * @param number $sigma - blur
   * @param number $radius
   * @return Imagick Image
   */
  function Vignette_rect($img, $x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0, $kind = "dark", $blursize = 10, $deep = 10, $sigma=1, $radius = 1) {
    $x1 = (int) $x1;
    $y1 = (int) $y1;
    $x2 = (int) $x2;
    $y2 = (int) $y2;
    $sizew = $img->GetImageGeometry();
    $maxx = $sizew["width"];
    $maxy = $sizew["height"];
    if($kind =="dark"){
      $bri = $this->bri($deep/100);
    }

    $this->bt->setMax($blursize);
    for($i = 0; $i< $blursize; $i++){
      if($i % 2 == 0 && $this->bt->Break($i)){
        break 1;
      }

      $img_temp = $img->clone();
      $width  = (int) ($x2-$x1);
      $height = (int) ($y2-$y1);

      $img_temp->cropImage($width, $height, $x1, $y1);
      if($kind == "dark"){
        $img->brightnessContrastImage( -$bri, 0.0, Imagick::CHANNEL_ALL );
      }else{
        $img->blurImage($radius, $sigma, Imagick::CHANNEL_ALL);
      }
      $img->compositeImage($img_temp, Imagick::COMPOSITE_ATOP,$x1, $y1, Imagick::CHANNEL_ALL);
      if($x1>0) $x1--;
      if($y1>0) $y1--;
      if($x2<$maxx-1) $x2++;
      if($y2<$maxy-1) $y2++;
    }
    return $img;
  }

  /**
   * Helper for Vignette
   * https://www.geogebra.org/graphing
   * @param number  $deep
   * @return number
   */
  function bri($deep = 0.0){
    static $b;
    if($deep != 0){
      $b = (float) $deep*1.8;
    }
    $bri = (float)($b);
    return $bri;
  }

  /**
   * Border
   * @param Imagick $img
   * @param string $bordercolor - border color
   * @param int  $t  -  width of the top
   * @param int  $ib - width of inner bevel
   * @param int  $ob - width of outer bevel
   * @param int  $h  - height of border
   * @return Imagick
   */
  function Border( $img, $bordercolor, $t, $ib, $ob, $h ) {
    $width  = $t + $ib + $ob;
    $height = $h + $ib + $ob;    
    $img->frameImage( "#" . $bordercolor, $width, $height, $ib, $ob );
    return $img;
  }

  //-------------  Geometry menu -------------------

  /**
   * AutoOrient
   */
  function autoOrient($img){
    $orientation = $img->getImageOrientation();
    switch($orientation) {
        case Imagick::ORIENTATION_BOTTOMRIGHT:
          $img->rotateimage("#000", 180); // rotate 180 degrees
          break;

        case Imagick::ORIENTATION_RIGHTTOP:
          $img->rotateimage("#000", 90); // rotate 90 degrees CW
          break;

        case Imagick::ORIENTATION_LEFTBOTTOM:
          $img->rotateimage("#000", -90); // rotate 90 degrees CCW
          break;
      }

      // Now that it's auto-rotated, make sure the EXIF data is correct in case the EXIF gets saved with the image!

      $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
   return $img;
  }

  /**
   * Rotate Image
   * @param Imagick $img
   * @param float | int $degrees
   * @return Imagick
   */
  function Rotate( $img, $degrees ) {
    $degrees = fmod( $degrees, 360.00 );
    $color = "rgb(0,0,0)";
    $img->rotateImage( $color, $degrees );
    return $img;
  }

  /**
   * Flip / Flop
   * @param Imagick $img
   * @param int $flip
   *          IMG_FLIP_VERTICAL
   *          IMG_FLIP_HORIZONTAL
   */
  function Flip($img, $flip) {
    if($flip == IMG_FLIP_VERTICAL) {
      $img->flipimage();
    } elseif($flip == IMG_FLIP_HORIZONTAL) {
      $img->flopimage();
    }
    return $img;
  }

  /**
   * Crop Imagick Image
   * @param Imagick $img
   * @param int $x1
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @return Imagick
   */
  function Crop( $img, int $x1, int $y1, int $x2, int $y2) {
    $img->cropImage( $x2 - $x1, $y2 - $y1, $x1, $y1 );
    return $img;
  }

  /**
   * Resize - Resample
   *
   * @param Imagick $img
   * @param float | int $wp
   * @param float | int $hp
   * @return Imagick
   */
  function Resize($img, int $wp, int $hp) {
    $img->scaleImage( $wp, $hp, true);
    return $img;
  }

  /**
   * Shave
   *
   * @param Imagick object $img
   * @param int $cols
   * @param int $rows
   * @return Imagick
   */
  function Shave($img, $cols, $rows){
    $img->shaveImage($cols, $rows);
    return $img;
  }


  function Perspective($img, $points){
    $img->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
    $img->setIMageMatte(true);
    $img->distortImage( Imagick::DISTORTION_PERSPECTIVE, $points, true);
    return $img;
  }

  /**
   * https://legacy.imagemagick.org/Usage/distorts/#barrel
   * @param Imagick object $img
   * @param float $a
   * @param float $b
   * @param float $c
   * @param int $strength
   * @param int $centerx
   * @param int $centery
   * @param string $bkgcolor
   * @return Imagick
   */
  function Lens( $img, $a, $b, $c, $d, $centerx, $centery, $bkgcolor ="#ffffff", $bestfit = true){
    $img->setImageBackgroundColor($bkgcolor);
    $img->setImageVirtualPixelMethod( Imagick::VIRTUALPIXELMETHOD_EDGE);
    $bestfit = ($bestfit == "true") ? true: false;
    if($d >= 0){
      $type = Imagick::DISTORTION_BARREL;
      $points = [ $a, $b, $c, $d, $centerx, $centery ];
    }else{
      $type = Imagick::DISTORTION_BARRELINVERSE;
      $points = [ $a, $b, $c, -$d, $centerx, $centery ];
    }
    $img->distortImage($type, $points, false);
    return $img;
  }
  /**
   * Resample
   *
   * @param Imagick $src - Source image
   * @param int $dst_x - Destination x,y
   * @param int $dst_y
   * @param int $src_x - Source x,y
   * @param int $src_y
   * @param int $dst_w - Destination width, height
   * @param int $dst_h
   * @param int $src_w - Source width, height
   * @param int $src_h
   * @return Imagick
   */
  function Resample(Imagick $src_img, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h) {
    return $src_img;
  }
  // -------------- Lighting menu -----------------

  //--------------- Correction menu ---------------
  //--------------- Color menu --------------------

  /**
   * Set the whitebalance of image
   *
   * @param mixed $img
   * @return Imagick
   */
  function Whitebalance( $img, $mode = "", $exclude = 0 ){     
    if($this->ver < '7'){
      $img->autoLevelImage();
      $img->negateImage(false);      
    } else{
      $img->whiteBalanceImage();
    }
    return $img;
  }
  /**
   * Normalize the Image
   * @param Imagick object $img
   * @param mixed $channel
   * @return Imagick
   */
  function Normalize($img, $channel ){
    $channel = $this->Channel( $channel );
    $img->normalizeImage( $channel );
    return $img;
  }

  /**
   * Set the gamma of image
   *
   * @param Imagick object $img
   * @param float $gammain
   * @param float $gammaout
   * @param boolean $autogamma
   * @return Imagick
   */
  function Gamma($img, $gammain, $gammaout, $autogamma) {
    if( $autogamma ){
      $img->autoGammaImage();
    }else{
      $gamma = ( float ) $gammain / ( float ) $gammaout;
      $img->gammaImage( $gamma, Imagick::CHANNEL_DEFAULT );
    }
    return $img;
  }

  /**
   * Brightness
   *
   * @param Imagick object $img
   * @param float $brightness - 0 - dark, 100 - the same, 200 - white
   * @param float $sat - Saturation - 0: gray, 100 - no change, 200 - max color
   * @param float $hue - Hue - 0= -180°, 100 = 0°, 200 = +180°
   * @return Imagick object
   */
  function Brightness($img, $bri=0.0, $sat = 0.0, $hue = 0.0) {
    $bri = $bri * 100 / 255 + 100;
    $sat = $sat * 100 / 255 + 100;
    $hue = $hue * 100 / 255 + 100;
    $img->modulateImage( ( float ) $bri, (float) $sat, (float) $hue );
    return $img;
  }

  /**
   * RGB conversion
   *
   * @param Imagick object $img
   * @param int $r - red
   * @param int $g - green
   * @param int $b - blue
   * @param int $alpha - alpha channel
   * @return Imagick object
   */
  function RGB($img, $r, $g, $b, $a) {
    $r = substr( "0" . dechex( $r ), - 2 );
    $g = substr( "0" . dechex( $g ), - 2 );
    $b = substr( "0" . dechex( $b ), - 2 );
    $color = "#" . $r . $g . $b;
    //
    $a = ( float ) ($a * 0.01);
    $a = 1 - $a;
    $img->colorizeImage( $color, $a, TRUE );
    return $img;
  }

  /**
   * set the contrast of image
   *
   * @param Imagick object $img
   * @param int $cont
   * @return Imagick object
   */
  function Contrast($img, $cont) {
    if($cont > 0) {
      for($i = 0; $i < $cont; $i ++) {
        $img->contrastImage( 1 );
      }
    } else if($cont < 0) {
      for($i = 0; $i > $cont; $i --) {
        $img->contrastImage( 0 );
      }
    }
    return $img;
  }

  /**
   * Change the image to grayscale
   *
   * @param Imagick object $img
   * @return Imagick object
   */
  function GrayScale( $img ) {
    $img->setImageType( Imagick::IMGTYPE_GRAYSCALEMATTE );
    return $img;
  }
  /**
   * Black and White
   *
   * @param Imagick $img
   * @param int $level
   * @return Imagick
   */
  function BW( $img, $level = 50){
    $level /= (float) 100;
    $max = Imagick::getQuantum();
    $level *= $max;
    $img->thresholdImage( $level, Imagick::CHANNEL_ALL);
    return $img;
  }

  /**
   * White above the color
   * @param Imagick object $img
   * @param int $r
   * @param int $g
   * @param int $b
   * @param int $a
   *
   * @return Imagick object
   */
  function White($img, $r, $g, $b, $a){
    $r = substr( "0" . dechex( $r ), - 2 );
    $g = substr( "0" . dechex( $g ), - 2 );
    $b = substr( "0" . dechex( $b ), - 2 );
    $color = "#" . $r . $g . $b;
    //
    $a = ( float ) ($a * 0.01);
    $a = 1 - $a;
    $img->whiteThresholdImage( $color);
    return $img;
  }

  /**
   * Summary of Charchoal
   * @param Imagick object $img
   * @param mixed $radius
   * @param mixed $sigma
   * @return Imagick object
   */
  function Charchoal($img, $radius = 5, $sigma = 2){
    $img->charcoalImage($radius, $sigma);
    return $img;
  }

  /**
   * Oil painting
   *
   * @param Imagick object $img
   * @param mixed $radius
   * @param mixed $level
   * @return Imagick object
   */
  function Oil($img, $radius = 5, $level = 50){
    $img->oilPaintImage( $radius );
    return $img;
  }

  /**
   * Sepia color of image
   *
   * @param Imagick object $img
   * @param mixed $level
   * @return Imagick object
   */
  function Sepia($img, $level = 80){
    $img->sepiaToneImage($level);
    return $img;
  }

    /**
   * Sepia color of image
   *
   * @param Imagick object $img
   * @param mixed $level
   * @return Imagick object
   */
  function BlueShift($img, $level = 15){
    $level = 0.1*(float) ($level);
    $img->blueShiftImage($level);
    return $img;
  }

  /**
   * change the color like in Sunshine
   *
   * @param Imagick object $img
   * @param mixed $treshold
   * @return Imagick object
   */
  function Solarize($img, $treshold = 1){    
    $treshold = (int) (Imagick::getquantum() * $treshold / 100 );
    $img->solarizeImage($treshold);
    return $img;
  }
  
  /**
   * Summary of Clahe
   * @param Imagick object$img
   * @param mixed $width
   * @param mixed $height
   * @param mixed $bins
   * @param mixed $clip
   * @return Imagick object
   */
  function Clahe( $img, $width=10, $height=10, $bins=29, $clip=100 ){
    if(version_compare($this->ver,'7.0.7') >0 ){
      $q = Imagick::getQuantum();
      $img->claheImage($width, $height, $bins, $clip * $q ); //* $q
    }
    return $img;
  }
  /**
   * Summary of Histogram
   * @param mixed $img
   * @return array{blue: string, green: string, red: string}
   *
   *
   * @param Imagick $img
   * @return Array -
   */
  public function Histogram( $img, $equalize = false ){
    $width  = $this->width;
    $height = $this->height;
    $this->bt->SetMax( 2 * $width * $height + 3 * 256 );
    return $this->getHistogram( $img , $width, $height, false, 0, $equalize);
  }

  /**
   * 
   */
  function getHistogram($img, $width, $height, $onlymax = false, $cnt = 0, $equalize = false ){
    $w = 256;
    $h = 120;
    $hh = $h-20;
    if ( $width > $height ){
      if($width >200){
        $dx = 200;
        $dy = intval( $height * $dx / $width );
        $img->adaptiveResizeImage($dx, $dy, true);
      }
      
    } else{
      if( $height > $width ){
        $dy = 200;
        $dx = intval($width * $dy/$height);
        $img->adaptiveResizeImage($dx, $dy, true);
      }      
    }
       
    $histogramElements = $img->getImageHistogram();    
    $this->bt->SetMax( 3*count( $histogramElements ) );
    $this->bt->SetFreq(200);

    $getMax = function($carry, $item){
      if($item > $carry){
        return $item;
      }
      return $carry;
    };

    $colorValues = [
      "red"   => $this->getColorStatistics( $histogramElements, \Imagick::COLOR_RED ),
      "green" => $this->getColorStatistics( $histogramElements, \Imagick::COLOR_GREEN ),
      "blue"  => $this->getColorStatistics( $histogramElements, \Imagick::COLOR_BLUE ),
    ];      
    if( $colorValues["red"] == false || $colorValues["green"] == false || $colorValues["blue"] == false){
      return [ "red"=> "0", "green" => "0", "blue" => "0"];
    }
    $maxr = array_reduce( $colorValues["red"]  , $getMax, 0 );
    $maxg = array_reduce( $colorValues["green"], $getMax, 0 );
    $maxb = array_reduce( $colorValues["blue"] , $getMax, 0 );    
    $max = max( $maxr, $maxg, $maxb );
    
    $scale = ($h-20) / $max;
    for( $i = 0; $i<256; $i++){
      $colorValues["red"][$i]   *= $hh / $maxr;
      $colorValues["green"][$i] *= $hh / $maxg;
      $colorValues["blue"][$i]  *= $hh / $maxb;
    }
    
    // ---------- Red histogram ------------
    $hred = new Imagick();
    $hred->newpseudoimage($w, $h, "xc:black");
    $hred->setImageFormat('jpg');

    $draw = new ImagickDraw();
    $draw->setStrokeWidth(0); //make the lines be as thin as possible

    $color0 = new ImagickPixel("rgb(255,0,0)");
    for( $i=0; $i<256; $i++ ){
      $v = $colorValues["red"][$i];      
      $draw->setStrokeColor($color0);
      $draw->line($i, $hh-$v, $i, $hh);

      $color = new ImagickPixel("rgb($i,0,0)");
      $draw->setStrokeColor($color);
      $draw->line($i,100, $i, $h);
    }
        
    ob_start();
      $hred->drawImage($draw);  
      header("Content-Type: image/jpeg");
      print $hred;
      $imgrdata = ob_get_contents();
    ob_end_clean();
    $imgred = base64_encode($imgrdata);

    // ------------ Green histogram ---------------
    $hgreen = new Imagick();
    $hgreen->newpseudoimage($w, $h, "xc:black");
    $hgreen->setImageFormat('jpg');
    
    $draw = new ImagickDraw();
    $draw->setStrokeWidth(0); //make the lines be as thin as possible    
        
    for( $i=0; $i<256; $i++ ){
      $v = $colorValues["green"][$i];
      $color0 = new ImagickPixel("rgb(0,255,0)");
      $draw->setStrokeColor($color0);
      $draw->line($i, $hh-$v, $i, $hh);
      $color = new ImagickPixel("rgb(0,$i,0)");
      $draw->setStrokeColor($color);
      $draw->line($i,100, $i, $h);
    }
    
    ob_start();      
      $hgreen->drawImage($draw);
      header("Content-Type: image/jpeg");
      print $hgreen;
      $imggdata = ob_get_contents();
    ob_end_clean();
    $imggreen = base64_encode($imggdata);

    // ------------- Blue histogram ---------------
    $hblue = new Imagick();
    $hblue->newpseudoimage($w, $h, "xc:black");
    $hblue->setImageFormat('jpg');
       
    $draw = new ImagickDraw();
    $draw->setStrokeWidth(0); //make the lines be as thin as possible    
    $color0 = new ImagickPixel("rgb(0,0,255)");

    for($i=0; $i<256; $i++ ){
      $v = $colorValues["blue"][$i];      
      $draw->setStrokeColor($color0);
      $draw->line($i, $hh-$v, $i, $hh);
      $color = new ImagickPixel("rgb(0,0,$i)");
      $draw->setStrokeColor($color);
      $draw->line($i,100, $i, $h);
    }
       
    ob_start();
      $hblue->drawImage($draw);  
      header("Content-Type: image/jpeg");
      print $hblue;
      $imgbdata = ob_get_contents();
    ob_end_clean();
    $imgblue = base64_encode($imgbdata);
   
    return [ "red"=> $imgred, "green" => $imggreen, "blue" => $imgblue ];
  }

  function getColorStatistics( $histogramElements, $colorChannel ){
    $colorStatistics = [];   
    foreach( $histogramElements AS $histogramElement ){      
      $color = $histogramElement->getColorValue( $colorChannel );      
      $color = intval( $color * 256 );
      $count = $histogramElement->getColorCount();
      
      if ( array_key_exists( $color, $colorStatistics ) ) {
          $colorStatistics[$color] += $count;
      } else {
          $colorStatistics[$color] = $count;
      }
      if($this->bt->Break($this->bt->cnt++)){
        return false;
      }
    }

    ksort( $colorStatistics );
    return $colorStatistics;
  }

  /**
   * 
   * @param mixed $img 
   * @return mixed 
   */
  function Equalize($img){
    $img->equalizeImage();
    return $img;
  }
  /**
   * Sharpener the image
   *
   * @param Imagick object $img
   * @param int $level
   * @return Imagick object
   */
  Function Sharp($img, $radius, $sigma, $level) {
    $channel = Imagick::CHANNEL_ALL;
    $img->adaptiveSharpenImage( $radius, $sigma, $channel );   
    return $img;     
  }

  /**
   *
   * @param Imagick object $img
   * @param int $level - smoothlevel
   * @param string $type
   * @param string $softness
   * @return Imagick object
   */
  function Denoise($img, $level, $type, $softness) {
    switch($type){
      case "denoise":
        for($i = 0; $i < $level; $i++) {
          $img->despeckleImage();
        }
        break;

      case "enhance":
        if(version_compare( $this->ver,'7.0.0' ) <= 0 ){
          for($i = 0; $i < $level; $i++) {
            $img->reduceNoiseImage($level);
          }
        } else{
          //  $treshold = (float) $treshold / 100; // Below this is noise
          //  $softness = (float) $level / 100;
          //  $img->WaveletDenoiseImage( $img, (float) $treshold /100, (float) $softness / 100);
        }
        break;
    }
    return $img;
  }

  /**
   * Add Noise to image
   *
   * @param Imagick object $img
   * @param int $level
   * @param int $type - has no effect
   * @param string $channel - r, g, b, alpha, all
   *
   * @return Imagick object
   */
  function AddNoise($img, $level = 1, $typeidx = "uniform", $channel = 'all') {
    $t = [
        "uniform"   => Imagick::NOISE_UNIFORM,
        "gaussian"  => Imagick::NOISE_GAUSSIAN,
        "multigauss"=> Imagick::NOISE_MULTIPLICATIVEGAUSSIAN,
        "impulse"   => Imagick::NOISE_IMPULSE,
        "laplacian" => Imagick::NOISE_LAPLACIAN,
        "poisson"   => Imagick::NOISE_POISSON,
        "random"    => Imagick::NOISE_RANDOM
    ];
    if(! array_key_exists( $typeidx, $t )) {
      return $img;
    }

    $type = $t[$typeidx];
    $level = ( int ) $level;
    $channel = $this->Channel( $channel );
    for($i = 0; $i < $level; $i++){
      $img->addNoiseImage( $type, $channel );
    }
    return $img;
  }

  /**
   * Emboss
   *
   * @param
   * Imagick object $img
   * @param number $radius
   * @param number $sigma
   * @return Imagick object
   */
  function Emboss($img, $radius = 0.0, $sigma = 1.0, $level = 1) {
    $img->embossImage( ( float ) $radius, ( float ) $sigma );
    return $img;
  }

  /**
   * Trim
   *
   * @param Imagick object $img
   * @param float $fuzz
   * @param int $r
   * @param int $g
   * @param int $b
   * @param int $alpha
   * @return Imagick
   */
  function Trim( $img, $fuzz, $r, $g, $b, $a ){
    $a = (float) $a / 255;
    $color = new ImagickPixel("rgba($r,$g,$b,$a)");
    $img->borderImage($color, 10, 10);
    $q = Imagick::getQuantum();
    $img->trimImage($fuzz * $q);
    return $img;
  }

  /**
   * Edge
   *
   * @param Imagick object $img
   * @param number $radius
   * @return Imagick object
   */
  function Edge( $img, $radius = 0.0 ) {
    $img->edgeImage( ( float ) $radius );
    return $img;
  }

  /**
   * Compile string channel to int channel
   *
   * @param string $channel
   * @return number
   */
  function Channel($channel = "all") {
    $channel = strtolower( $channel );
    switch($channel){
      case "r" :
        $channel = Imagick::CHANNEL_RED;
        break;
      case "g" :
        $channel = Imagick::CHANNEL_GREEN;
        break;
      case "b" :
        $channel = Imagick::CHANNEL_BLUE;
        break;
      case "alpha" :
        $channel = Imagick::CHANNEL_ALPHA;
        break;
      case "opacity" :
        $channel = Imagick::CHANNEL_OPACITY;
        break;
      case "all" :
        $channel = Imagick::CHANNEL_ALL;
        break;
      case "default" :
      default :
        $channel = Imagick::CHANNEL_DEFAULT;
        break;
    }
    return $channel;
  }

  /**
   * Emboss
   *
   * @param Imagick object $img
   * @param number $radius
   * @param number $sigma
   * @return Imagick object
   */
  function Blur($img, $radius = 4, $sigma = 2, $type = "blur", $chan = "default", $angle = 0, $level = 1) {
    $channel = $this->Channel( $chan );
    switch($type){
      case "gaussianblur" :
        $img->gaussianBlurImage( $radius, $sigma, $channel );
        break;
      case "adaptiveblur" :
        $img->adaptiveBlurImage( $radius, $sigma, $channel );
        break;
      case "motionblur" :
        $img->motionBlurImage( $radius, $sigma, $angle, $channel );
        break;
      case "blur" :
      default :
        $img->blurImage( $radius, $sigma, $channel );
    }
    return $img;
  }

  /**
   * Convolution
   * @param Imagick $img
   * @param array $matrix
   * @param number $div
   * @param number $off
   * @return Imagick
   */
  function Convolution( $img, $matrix, $div, $off ) {
    //$kernel = ImagickKernel::fromMatrix( $matrix );
    $img->convolveImage( $matrix, Imagick::CHANNEL_ALL );
    return $img;
  }

  /**
   * Wave the image
   * @param mixed $img 
   * @param int $amplitude 
   * @param int $wavelength 
   * @return mixed 
   */
  function Wave($img, $amplitude=5, $wavelength=20){
    $img->waveImage( $amplitude, $wavelength );
    return $img;
  }

  /**
   * Swirl
   * @param mixed $img 
   * @param int $angle 
   * @param int $xp - center of swirl
   * @param int $yp 
   * @return mixed 
   */
  function Swirl($img, $angle=0, $xp=0, $yp=0){    
    $img->swirlImage($angle);
    return $img;
  }


  /**
   * Resize the image
   * @param Imagick $src_im
   * @param int $dst_x
   * @param int $dst_y0
   * @param int $src_x
   * @param int $src_y
   * @param int $width
   * @param int $height
   * @param int $dx
   * @param int $dy
   * @return Imagick
   */
  function ImageCopyResized(Imagick $src_img, int $dst_x, int $dst_y0, int $src_x, int $src_y, int $width, int $height, int $dx, int $dy) {
    $src_img->scaleImage($width, $height);
    return $src_img;
  }

  /**
   * Write an image to the disk
   * @param Imagick $img
   * @param string $path
   * @param string $type
   * @return boolean
   */
  function Image( $img, $path = ".", $type = "jpeg") {
    if($type == "jpg") {
      $type = "jpeg";
    }
    $img->setImageFormat( $type );
    return $img->WriteImage( $path );
  }

  /**
   * Save the modified Image
   *
   * @param Imagick image $img
   * @param object $cfg - Smplphotoalbum config
   * @param string $name - file path
   * @param string $type - image type

   * @return boolean
   */
  function Save($img, $cfg, $name = "filename", $type = "jpg") {

    $ok = False;
    switch ($type) {
      case 'avif':
        $img->setImageFormat("avif");
        $ok = $img->writeImage( $name );
        break;
      case 'jpg' :
      case 'jpeg' :
        $quality = $cfg->get( 'jpeg' );
        if($quality != -1){
          $img->setImageCompressionQuality( ( int ) ( 10*$quality ) );
        }
        $img->setImageCompression(Imagick::COMPRESSION_JPEG);
        $img->setImageFormat( "jpeg" );
        $ok = $img->writeImage( $name );
        break;

      case 'png' :
        $quality = $cfg->get( 'png' );
        if($quality != -1){
          $img->setImageCompressionQuality( $quality );
          $img->setOption( 'png:compression-level' , $quality );
        }
        $img->setImageFormat( "png" );
        $ok = $img->writeImage( $name );
        break;

      case 'wbmp' :
        $img->setImageFormat("wbmp");
        $ok = $img->writeImage($name);
        break;

      case 'gif' :
        $img->setImageFormat("gif");
        $ok = $img->writeImage($name);
        break;

      case 'bmp' :
        $img->setImageFormat("bmp");
        $ok = $img->writeImage($name);
        break;

      case 'xbm' :
        $img->setImageFormat("xbm");
        $ok = $img->writeImage($name);
        break;

      case 'xpm' :
        $img->setImageFormat("xpm");
        $ok = $img->writeImage($name);
        break;

      case 'webp' :
        $img->setImageFormat("webp");
        $ok = $img->writeImage($name);
        break;

      default :
        $ok = true;
    }
    return $ok;
  }

  /**
   * * Make a Truecolor Image
   * @param number $width
   * @param number $height
   * @return Imagick
   */
  function ImageCreateTrueColor($width = 0, $height = 0 ) {
    $img = new Imagick();
    $img->newImage( $width, $height, new Imagickpixel( 'rgb(255,255,255)' ) );
    return $img;
  }

  function getWidth() {
  }

  function getHeight() {
  }
/**
 * Set the type of image
 * @param string $ext
 */
  function setType( $ext ){
    if($ext == "jpg") $ext = "jpeg";
    $this->ext = $ext;
  }
}