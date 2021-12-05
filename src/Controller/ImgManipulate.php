<?php

namespace Drupal\smplphotoalbum\Controller;

class ImgManipulate {
  private $src_img;
  private $dst_img;
  private $maxx;
  private $maxy;
  private $dx = 1;
  private $dy;
  private $height;
  private $source = ''; // source path
  private $size;
  private $path = ''; // original path
  private $name = ''; // original filename
  private $newname = ''; // new filename
                         //
  private $pngqualiti = 9; // compression level
  private $jpgcomp = 100;
  private $imagic = false;
  /**
   *
   * @param $path -
   *          temppath
   * @param $name -
   *          original filename
   * @param $tempname -
   *          the filename after the modify
   */
  public function __construct($path, $name, $newname, $type) {
    $this->path = $path;
    $this->source = $path . $name;
    $this->newname = $path . $newname;
    $this->type = $type;
    $size = GetImagesize ( $this->source );
    $this->size = $size;
    $this->dx = $size [0];
    $this->dy = $size [1];
    $this->dst_img = @ImageCreateTrueColor ( $this->dx, $this->dy );
    switch ($this->type) {
      case 'jpg' :
      case 'jpeg' :
        $this->src_img = ImageCreateFromJPEG ( $this->source );
        break;
      case 'png' :
        $this->src_img = @ImageCreateFromPNG ( $this->source );
        break;
      case 'wbmp' :
        $this->src_img = @ImageCreateFromwbmp ( $this->source );
        break;
      case 'gif' :
        $this->src_img = @ImageCreateFromGif ( $this->source );
        break;
      case "bmp" :
        $this->src_img = @imagecreatefrombmp ( $this->source );
        break;
    }
  }
  
  /**
   * rotate the image
   * 
   * @param unknown $rad          
   */
  public function rotate($degrees) {
    $this->dst_img = imagerotate ( $this->src_img, - $degrees, 0 );
  }
  
  /**
   * Flip the image
   * 
   * @param int $flip          
   */
  public function imageflip($flip) {
    imageflip ( $this->src_img, $flip );
    $this->dst_img = $this->src_img;
  }
  
  /**
   * Crop the image
   * 
   * @param number $x0          
   * @param number $y0          
   * @param number $x1          
   * @param number $y1          
   */
  public function crop($x1, $y1, $x2, $y2) {
    $this->dst_img = imagecrop ( $this->src_img, array (
        'x' => $x1,
        'y' => $y1,
        'width' => ($x2 - $x1),
        'height' => ($y2 - $y1) 
    ) );
  }
  /**
   * Resize the image
   */
  public function resize($wp, $hp) {
    $wp = ( int ) $wp;
    $hp = ( int ) $hp;
    if ($wp > 0 && $hp > 0) {
      $this->makedst ( $wp, $hp );
      $this->dst_img = imagescale ( $this->src_img, $wp, $hp );
    }
  }
  
  /**
   * Resample
   * 
   * @param int $dst_x
   *          - destination x,y
   * @param int $dst_y          
   * @param int $src_x
   *          - source x,y
   * @param int $src_y          
   * @param int $dst_w
   *          - destination w,h
   * @param int $dst_h          
   * @param int $src_w
   *          - source w,h
   * @param int $src_h          
   * @return bool
   */
  public function resample($dst_x, $dst_y, $src_x, $src_y, $dst_w, $ds_th, $src_w, $src_h) {
    // patch
    // imagegammacorrect($this->src_img, 2.2, 1.0);
    $ok = imagecopyresampled ( $this->dst_img, $this->src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );
    // imagegammacorrect($this->dst_img, 1.0, 2.2);
    return $ok;
  }
  /**
   * Make a contrast
   * 
   * @param unknown $cont          
   */
  public function contrast($cont) {
    imagefilter ( $this->src_img, IMG_FILTER_CONTRAST, - $cont );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  /**
   * Gray scale
   */
  public function grayscale() {
    imagefilter ( $this->src_img, IMG_FILTER_GRAYSCALE );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  /**
   * Change brightness
   */
  public function brightness($brightness) {
    imagefilter ( $this->src_img, IMG_FILTER_BRIGHTNESS, $brightness );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  /**
   * settings RGB
   */
  public function rgb($r, $g, $b) {
    imagefilter ( $this->src_img, IMG_FILTER_COLORIZE, $r, $g, $b );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  public function gamma($gammain, $gammaout) {
    imagegammacorrect ( $this->src_img, $gammain, $gammaout );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  /**
   * Sharpener the image
   * 
   * @param unknown $div          
   */
  public function sharp($div) {
    $matrix = array (
        array (
            - 1,
            - 1,
            - 1 
        ),
        array (
            - 1,
            16,
            - 1 
        ),
        array (
            - 1,
            - 1,
            - 1 
        ) 
    );
    $div = $this->_div ( $matrix );
    imageconvolution ( $this->src_img, $matrix, $div, 0 );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  private function _div($m) {
    return array_sum ( $m [0] ) + array_sum ( $m [1] ) + array_sum ( $m [2] );
  }
  
  /**
   * Noise reduction
   */
  public function smooth($smoothlevel) {
    imagefilter ( $this->src_img, IMG_FILTER_SMOOTH, $smoothlevel );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  
  /**
   * Embossing
   */
  public function emboss() {
    $matrix = array (
        array (
            2,
            0,
            0 
        ),
        array (
            0,
            - 1,
            0 
        ),
        array (
            0,
            0,
            - 1 
        ) 
    );
    $div = $this->_div ( $matrix );
    imageconvolution ( $this->src_img, $matrix, $div, 127 );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  /**
   * Gaussian Blur
   */
  public function gaussianblur() {
    $matrix = array (
        array (
            1,
            2,
            1 
        ),
        array (
            2,
            4,
            2 
        ),
        array (
            1,
            2,
            1 
        ) 
    );
    $div = $this->_div ( $matrix );
    imageconvolution ( $this->src_img, $matrix, $div, 0 );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  public function convolution() {
    $c00 = ( int ) $_REQUEST ['c00'];
    $c01 = ( int ) $_REQUEST ['c01'];
    $c02 = ( int ) $_REQUEST ['c02'];
    $c10 = ( int ) $_REQUEST ['c10'];
    $c11 = ( int ) $_REQUEST ['c11'];
    $c12 = ( int ) $_REQUEST ['c12'];
    $c20 = ( int ) $_REQUEST ['c20'];
    $c21 = ( int ) $_REQUEST ['c21'];
    $c22 = ( int ) $_REQUEST ['c22'];
    $div = ( int ) $_REQUEST ['div'];
    $off = ( int ) $_REQUEST ['offs'];
    $matrix = array (
        array (
            $c00,
            $c01,
            $c02 
        ),
        array (
            $c10,
            $c11,
            $c12 
        ),
        array (
            $c20,
            $c21,
            $c22 
        ) 
    );
    imageconvolution ( $this->src_img, $matrix, $div, $off );
    $this->makedst ();
    $this->dst_img = $this->src_img;
  }
  /**
   * Save the image with original name
   * 
   * @param string $target          
   */
  public function save() {
    $ok = False;
    switch ($this->type) {
      case 'jpg' :
      case 'jpeg' :
        $ok = Imagejpeg ( $this->dst_img, $this->newname, 80 );
        break;
      case 'png' :
        $ok = @Imagepng ( $this->dst_img, $this->newname, 9 );
        break;
      case 'wbmp' :
        $ok = @Imagewbmp ( $this->dst_img, $this->newname );
        break;
      case 'gif' :
        $ok = @imagegif ( $this->dst_img, $this->newname );
        break;
      case 'bmp' :
        $ok = @imagebmp ( $this->dst_img, $this->newname );
        break;
      default :
        $ok = true;
    }
    return $ok;
  }
  
  /**
   * Make the png transparency
   * 
   * @param number $r          
   * @param number $g          
   * @param number $b          
   */
  public function transparency($r = 0, $g = 0, $b = 0) {
    if ($this->type != "png") {
      return false;
    }
    // Make copy from original
  }
  /**
   * Make new destination image
   * 
   * @param number $dx          
   * @param number $y          
   */
  function makedst($dx = 0, $dy = 0) {
    if ($dx == 0)
      $dx = $this->dx;
    if ($dy == 0)
      $dy = $this->dy;
    $this->dst_img = imagecreatetruecolor ( $dx, $dy );
  }
  function getWidth() {
    return imagesx ( $this->dst_img );
  }
  function getHeight() {
    return imagesy ( $this->dst_img );
  }
}