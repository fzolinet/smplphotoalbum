<?php

namespace Drupal\smplphotoalbum\Controller;
use Drupal;
use Imagick;
use Drupal\smplphotoalbum\Controller\GDDriver;
use Drupal\smplphotoalbum\Controller\ImagickDriver;

/** @package Drupal\smplphotoalbum\Controller */
class ImgManipulate{
  private $sign; // $sign of thebreaking of the long process
  private \GDImage | Imagick $src_img; //source image
  private \GDImage | Imagick $dst_img; // destination image
  private int $dx = 0;
  private $dy = 0;
  private $source = ''; // source path
  //private $filename;
  private $path = ''; // original path
  private $newname = ''; // new filename
  private $cfg;
  private $graphicdrv;  // graphic driver
  private $gd;          // graphic driver in string
  private $mp;          // modulepath
  private $public;
  private $root; // actual folder of images of Simple photoalbum
  private $type; //type of image
  private $ext;
  private $size;
  private $bt;
  private $temppath ="";

  /**
   *
   * @param $graphicdrv - graphic driver
   * @param $path - temppath
   * @param $name - original filename
   * @param $newname - the filename after the modify
   * @param $type - type of image
   * @param $size - size of image
   * @param $cfg - configuration
   * @param $sign - configuration
   */
  public function __construct( $graphicdrv, $path, $name, $newname, $type, $size, $cfg, $sign, &$bt ) {
    $this->graphicdrv = $graphicdrv;
    $this->sign   = $sign;
    $this->path   = $path;
    $this->source = $path . $name;
    $this->bt     = $bt;

    $this->cfg    = Drupal::config( 'smplphotoalbum.settings' );
    $this->mp     = Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->public = Drupal::service( 'file_system' )->realpath( "public://" );
    $root = $this->cfg->get( 'root' );
    $root = str_replace( "public://", $this->public . "/", $root );
    $root .= substr( $root, - 1 ) != '/' ? "/" : '';

    $this->root = str_replace( ['\\',"//"], ['/',"/"], $root );

    // for breaktime
    $p = pathinfo( $name );
    //$this->filename = $path . $p["filename"];
    $this->ext = $p["extension"];

    $this->newname = $path . $newname;
    $this->type = $type;
    $this->cfg = $cfg;

    if(!$size){
      $size = getimagesize($path . $name );
    }
    $this->size = $size;
    $this->dx = @$size[0];
    $this->dy = @$size[1];

    // temppath for copy
    $t = $this->cfg->get( 'temp' );
    $this->temppath = str_replace( "\\", "/", Drupal::service ( 'file_system' )->realpath ( $t ) );
    $this->temppath .= substr( $this->temppath, - 1 ) != '/' ? "/" : '';

    if( $this->graphicdrv == "gd" ) {
      $this->gd = new GDDriver( $this->path, $this->bt, $size, $this->mp);
    } else if( $this->graphicdrv == "imagick" ) {
      $this->gd = new ImagickDriver( $this->path, $this->bt, $size, $this->mp );
    }

    $this->gd->setType($this->ext );

    // driver GD / Imagick
    $this->dst_img = $this->gd->ImageCreateTruecolor( $this->dx, $this->dy );
    $this->src_img = $this->gd->ImageCreateFrom( $this->source, $this->type);
    //
  }

  //-------- Enhance menu ---------------------
  /**
   * Summary of Redeye
   * @param mixed $x1
   * @param mixed $y1
   * @param mixed $x2
   * @param mixed $y2
   * @return void
   */
  function Redeye($x1, $y1, $x2, $y2 ){
    $this->dst_img = $this->gd->Redeye( $this->src_img, $x1, $y1, $x2, $y2 );
  }

  //----------- Geometry menu ----------
  /**
   * auto Orient
   * @return void
   */
  function AutoOrient() {
    $this->dst_img = $this->gd->autoOrient( $this->src_img );
  }

  /**
   * rotate the image
   *
   * @param number $degrees
   * @return void
   */
  public function Rotate( $degrees ) {
    $degrees = (float) $degrees;
    $this->dst_img = $this->gd->Rotate( $this->src_img, $degrees );
  }

  /**
   * Flip the image
   *
   * @param int $flip
   * @return void
   */
  public function Imageflip($flip) {
    $this->dst_img = $this->gd->Flip( $this->src_img, $flip );
  }

  /**
   * Crop the image
   *
   * @param int $x1
   * @param int $y1
   * @param int $x2
   * @param int $y2
   * @return void
   */
  public function Crop(int $x1 = 0, int $y1 = 0, int $x2 = 0, int $y2 = 0) {
    $this->dst_img = $this->gd->Crop( $this->src_img, $x1, $y1, $x2, $y2 );
  }

  /**
   * Resize the image
   *
   * @param float | int $wp
   * @param float | int $hp
   * @return void
   */
  public function Resize($wp, $hp) {
    if($wp > 0 && $hp > 0) {
      $this->makedst( $wp, $hp );
      $this->dst_img = $this->gd->Resize( $this->src_img, $wp, $hp );
    }
  }

  /**
   * Shave
   *
   * @param int $cols
   * @param int $rows
   * @return void
   */
  public function Shave( $cols, $rows ){
    $this->dst_img = $this->gd->Shave( $this->src_img, $cols, $rows );
  }

  /**
   * Perspective
   * @param array $points
   * @return void
   */
  function Perspective( $points ){
    $this->dst_img = $this->gd->Perspective( $this->src_img, $points);
  }

  /**
   * Distortion - under construction
   * @return void
   */
  function Distortion(){
  }

  /**
   * Lens
   * @param $a
   * @param $b
   * @param $c
   * @param $d
   * @param int $centerx
   * @param int $centery
   * @param number $color
   * @param boolean $bestfit
   * @return void
   */
  function lens($a, $b, $c, $d, $centerx, $centery, $color, $bestfit=true){
    $this->dst_img = $this->gd->Lens( 
      $this->src_img, 
      $a, 
      $b, 
      $c, 
      $d, 
      $centerx, 
      $centery, 
      $color, 
      $bestfit 
    );
  }

  //------------ Effect menu ----------------
  /**
   * Gray scale
   * @return void
   */
  public function Grayscale()
  {
    $this->dst_img = $this->gd->GrayScale( $this->src_img );
  }

  /**
   * Black and White
   * @param int $level
   * @return void
   */
  public function BW( $level=50 )
  {
    $this->dst_img = $this->gd->BW( $this->src_img, $level );
  }

  /**
   * White
   * @param int $r
   * @param int $g
   * @param int $b
   * @param int $a
   * @return void
   */
  public function White( $r, $g, $b, $a )
  {
    $this->dst_img = $this->gd->White( $this->src_img, $r, $g, $b, $a );
  }

  /**
   * Summary of Charchoal
   * @param float $radius
   * @param int $sigma
   * @return void
   */
  public function Charchoal( $radius=5, $sigma=2)
  {
    $this->dst_img = $this->gd->Charchoal( $this->src_img, $radius, $sigma );
  }

  /**
   * Summary of Oil
   * @param float $radius
   * @return void
   */
  public function Oil( $radius = 5, $level = 240 )
  {
    $this->dst_img = $this->gd->Oil( $this->src_img, $radius, $level );
  }

  /**
   * Sepia
   *
   * @param int $level
   * @return void
   */
  public function Sepia($level = 80){
    $this->dst_img = $this->gd->Sepia( $this->src_img, $level );
  }

   /**
   * BlueShift
   *
   * @param int $level
   * @return void
   */
  public function BlueShift($level = 15){
    $this->dst_img = $this->gd->BlueShift( $this->src_img, $level );
  }

   /**
   * Solarize
   *
   * @param int $treshold
   * @return void
   */
  public function Solarize($treshold = 1){
    $this->dst_img = $this->gd->Solarize( $this->src_img, $treshold );
  }

  /**
   * Clahe
   * @param int $width
   * @param int $height
   * @param int $bins
   * @param int $clip
   * @return void
   */
  public function Clahe($width=10, $height=10, $bins=29, $clip=100){
    $this->dst_img = $this->gd->Clahe( $this->src_img, $width, $height, $bins, $clip);
  }

  // ----------- Color menu ------------
  public function Histogram(){    
    return $this->gd->Histogram($this->src_img);
  }

  public function Equalize(){
    return $this->gd->Equalize($this->src_img);
  }

  /**
   * Whitebalance the image
   *
   * @param mixed $channel
   * @return void
   */
  public function Whitebalance( $mode, $exclude = 0 ){      
      $this->dst_img = $this->gd->Whitebalance($this->src_img , $mode, $exclude);
  }

  /**
   * Normalize the image
   *
   * @param mixed $channel
   * @return void
   */
  public function Normalize($graphicdrv, $channel, $mode=1, $dynamic = 254){
    if($graphicdrv == "gd"){
      $this->dst_img = $this->gd->Normalize( $this->src_img, $channel, $mode, $dynamic );
    }else{
      $this->dst_img = $this->gd->Normalize( $this->src_img, $channel );
    }
  }

  /**
   * Gamma change
   * @param float $gammain
   * @param float $gammaout
   * @param float $autogamma
   * @return void
   */
  public function Gamma($gammain, $gammaout, $autogamma) {
    $this->dst_img = $this->gd->Gamma( $this->src_img, $gammain, $gammaout, $autogamma );
  }
  /**
   * Make a contrast
   *
   * @param number $cont
   * @return void
   */
  public function Contrast($cont)
  {
    $this->dst_img = $this->gd->Contrast( $this->src_img, $cont );
  }

  /**
   * Change brightness, Saturation, hue
   * @param number $bri
   * @param number $sat
   * @param number $hue
   * @return void
   */
  public function Brightness($bri=0, $sat=0, $hue=0) {
    $this->dst_img = $this->gd->Brightness( $this->src_img, $bri, $sat, $hue );
  }

  /**
   * settings RGB
   * @param number $r
   * @param number $g
   * @param number $b
   * @param number $a
   * @return void
   */
  public function RGB($r, $g, $b, $a) {
    $this->dst_img = $this->gd->RGB( $this->src_img, $r, $g, $b, $a );
  }

  /**
   * Add Noise
   * @param number $level
   * @param string $type
   * @param string $channel
   * @return void
   */
  public function AddNoise($level = 1, $type = "uniform", $channel = "all") {
    $this->dst_img = $this->gd->AddNoise( $this->src_img, $level, $type, $channel );
  }

  /**
   * Noise reduction
   * @param number $level
   * @param string $type
   * @param number $softness
   * @return void
   */
  public function DeNoise($level = 3, $type="denoise", $softness=0.0) {
    $this->dst_img = $this->gd->Denoise( $this->src_img, $level, $type, $softness );
  }

  /**
   * Sharpener the image
   * @param int $radius
   * @param int $sigma
   * @param number $level
   * @return void
   */
  public function Sharp($radius, $sigma, $level) {
    $this->dst_img = $this->gd->Sharp( $this->src_img, $radius, $sigma, $level );
  }
  /**
   * Embossing
   *
   * @param float $radius
   * @param float $sigma
   * @return void
   */
  public function Emboss($radius = 1, $sigma = 1.0, $level = 1) {
    $this->dst_img = $this->gd->Emboss( $this->src_img, $radius, $sigma, $level );
  }

  /**
   * Trim
   *
   * @param float $fuzz
   * @param int $r
   * @param int $g
   * @param int $b
   * @param int $alpha
   * @return void
   */
  public function Trim($fuzz, $r, $g, $b, $alpha){
    $this->dst_img = $this->gd->Trim( $this->src_img, $fuzz, $r, $g, $b, $alpha );
  }

  /**
   * Edge
   *
   * @param float $radius
   * @return void
   */
  public function Edge($level = 0.0) {
    $this->dst_img = $this->gd->Edge( $this->src_img, $level);
  }

  /**
   * Blur
   *
   * @param string $type
   * @param float $radius
   * @param float $sigma
   * @param string $channel
   * @param float $angle
   * @return void
   */
  public function Blur($radius = 0, $sigma = 1.0, $type = "blur", $channel = "default",  $angle = 0, $level=0) {
    $this->dst_img = $this->gd->Blur( $this->src_img, $radius, $sigma, $type, $channel,  $angle, $level );
  }

  /**
   * Convolution
   */
  public function Convolution($c00, $c01, $c02, $c10, $c11, $c12, $c20, $c21, $c22, $div, $off) {
    $matrix = [ [$c00, $c01, $c02], [$c10, $c11, $c12], [$c20, $c21, $c22]];
    $this->dst_img = $this->gd->Convolution( $this->src_img, $matrix, $div, $off );
  }

  public function Wave( $amplitude = 5 , $wavelength = 20 ){
    $amplitude = (int) $amplitude;
    $wavelength = (int) $wavelength;
     $this->dst_img = $this->gd->Wave( $this->src_img, $amplitude, $wavelength );
  }

  public function Swirl( $angle = 0){    
     $this->dst_img = $this->gd->Swirl( $this->src_img, (int) $angle );
  }
  /**
   * Make a border
   *
   * @param string $bordercolor
   * @param string $top
   * @param string $innerbevel
   * @param string $outerbevel
   * @param string $height
   * @return void
   */
  public function Border($bordercolor, $top, $innerbevel, $outerbevel, $height) {
    $bordercolor = str_replace( "#", "", $bordercolor );
    $this->dst_img = $this->gd->Border( $this->src_img, $bordercolor, $top, $innerbevel, $outerbevel, $height );
  }

  public Function Bevel( $bevel, $depht ){
    $this->dst_img = $this->gd->Bevel($this->src_img, $bevel, $depht );
  }
  /**
   * Vignette
   *
   * @param string $grdrv - graphic driver
   * @param string $geom  - Geometry: rectangle, circle, ellipse
   * @param integer $x1   - coordinates of left top corner
   * @param integer $y1
   * @param integer $x2   - coordinates of right bottom corner
   * @param integer $y2
   * @param integer $kind - darkening or blurring
   * @param number $blursize
   * @param number $sigma
   * @param number $radius
   * @return void
   */
  public function Vignette($grdrv, $geom = "circle", $x1 = 0, $y1 = 0, $x2 = 0, $y2 = 0, $kind = "dark", $blursize = 10, $deep = 10, $sigma = 1, $radius = 10) {
    if($grdrv == "gd") {
      $geom = strtolower( $geom );

      switch( $geom ){
        case "rect" :
          $this->dst_img = $this->gd->Vignette_rect(
            $this->src_img, 
            $x1,
            $y1,
            $x2,
            $y2,
            $kind,
            $blursize,
            $deep,     
            $sigma, 
            $radius 
          );
          break;
        case "ellipse" :
          $this->dst_img = $this->gd->Vignette_oval( 
            $this->src_img, 
            $x1, 
            $y1, 
            $x2, 
            $y2, 
            $kind, 
            $blursize, 
            $deep, 
            $sigma, 
            $radius, 
            "ellipse" 
          );
          break;

        case "circle" :
        default :
          $this->dst_img = $this->gd->Vignette_oval( 
            $this->src_img, 
            $x1,   
            $y1, 
            $x2, 
            $y2, 
            $kind, 
            $blursize, 
            $deep, 
            $sigma, 
            $radius, 
            "circle"  
          );
          break;
      }

      $this->makedst();
      $this->dst_img = $this->src_img; //!!!!!!!!!!!!! kitörölni majd!!!!!!!!!!!!
    } else {

      switch( $geom ){
        case "rect" :
          $this->dst_img = $this->gd->Vignette_rect( 
            $this->src_img,  
            $x1, 
            $y1, 
            $x2, 
            $y2, 
            $kind, 
            $blursize, 
            $deep,  
            $sigma, 
            $radius 
          );
          break;
        case "ellipse" :
          $this->dst_img = $this->gd->Vignette_oval( 
            $this->src_img, 
            $x1, 
            $y1, 
            $x2, 
            $y2, 
            $kind, 
            $blursize, 
            $deep, 
            $sigma, 
            $radius, 
            "ellipse" 
          );
          break;
        
          case "circle" :
        default :
          $this->dst_img = $this->gd->Vignette_oval( 
            $this->src_img, 
            $x1, 
            $y1, 
            $x2, 
            $y2, 
            $kind, 
            $blursize, 
            $deep, 
            $sigma, 
            $radius , 
            "circle"
          );
          break;
      }
    }
  }

  /**
   * Watermark
   *
   * @param number $x1 - top left point in
   * @param number $y1
   * @param number $x2 - bottom right point
   * @param number $y2
   * @param number $wmalpha - alpha
   * @param string $wmpath - watermark file path
   */
  public function Watermark($x1 = 0, $y1 = 0, $x2 = 100, $y2 = 100, $copyright = "", $author = "" , $wmalpha = 10, $wmpath = "", $wmcolor = "grey" ) {
    $wmpath = trim( $wmpath );
    if(strpos( " " . $wmpath, 'public://' ) > 0) {
      
      $wmpath = str_replace( 'public://', $this->public, $wmpath );
    
    } elseif( strpos( " " . $wmpath, "smplphotoalbum://" ) > 0 ) {
      
      $wmpath = str_replace( 'smplphotoalbum://', Drupal::root() . "/" . $this->mp."/", $wmpath );
      $wmpath = str_replace(["\\","//"],"/", $wmpath);
    
    }

    $this->dst_img = $this->gd->Watermark( $this->src_img, $x1, $y1, $x2, $y2, $copyright, $author, $wmalpha, $wmpath, $wmcolor );
  }

  /**
   * Save the image with original name
   *
   * @param string $target
   * @return boolean
   */
  public function Save() {
    $ok = $this->gd->Save( $this->dst_img, $this->cfg, $this->newname, $this->type);

    if($this->graphicdrv != 'imagick') {
      imagedestroy( $this->dst_img );
      imagedestroy( $this->src_img );
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
  public function Transparency($r = 0, $g = 0, $b = 0) {
    if($this->type != "png") {
      return false;
    }
    // Make copy from original
  }

  /**
   * Make new destination image
   *
   * @param number $dx
   * @param number $y
   * @return
   */
  private function makedst($dx = 0, $dy = 0) {
    if($dx == 0)
      $dx = $this->dx;
    if($dy == 0)
      $dy = $this->dy;
    $this->dst_img = $this->gd->ImageCreateTrueColor( $dx, $dy );
  }

  /**
   * @param string $ext
   */
  public function setType($ext = ''){
    $this->ext = $ext;
  }
}