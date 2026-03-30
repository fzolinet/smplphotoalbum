<?php
namespace Drupal\smplphotoalbum\Controller;

require (__DIR__."/../../")."/vendor/autoload.php";

use Drupal\smplphotoalbum\Controller\GDDriver;
use Drupal\smplphotoalbum\Controller\ImagickDriver;
use Drupal\smplphotoalbum\Controller\BreakTime;
use Holiday\Metadata;
use PNGMetadata\PNGMetadata;
use Drupal\smplphotoalbum\Controller\Lib;

/**
 * Img Edit class functions
 * @author fz
 */
class ImageEdit {
  const TN = '_tn_/';
  private $cfg; //configuration
  private $con; //database connection
  private $gd; // Graphic driver class
  private $graphicdrv; // graphic driver
  private $id = -1;  // actual id
  private $img; // ImgManipulate class
  private $mp = ''; // Module path
  private $path =''; // original path from database
  private $root;  // root os smplphotoalbum
  public $rq;     // request of Drupal
  public $sess; // local session storage
  private $sign = ''; // It signs the long process
  private $temppath; // temporary directory: tipically /DRUPAL_ROOT/sites/default/files/smplphotoalbum
  public $ts; // Session, as array
  
  public $bt; //BreakTime
  private $Metadata; // object for jpeg or png exif information

  public function __construct( $id ) {
    $this->id   = $id;
    $this->rq   = \Drupal::request();    
    $this->ts   = LIB::getSession( "smpl" );
    $this->sign = LIB::Sign($this->ts["tempname"]);

    $this->cfg = \Drupal::config( 'smplphotoalbum.settings' );
    $this->mp  = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->con = \Drupal::database();

    $root       = LIB::getConfig( 'root' );
    $this->root = LIB::getRoot( $root );

    // temppath for copy    
    $this->temppath = LIB::getTemppath();    

    // tempurl to edit    
    $this->ts['tempurl'] = LIB::getTempUrl();

    // Graphic driver
    $this->graphicdrv = LIB::getConfig( 'graphicdrv' );
    if( isset( $this->ts["graphicdrv"] ) ){
      $this->graphicdrv  = $this->ts["graphicdrv"];
    }

    $graphicdrv = LIB::Request( 'graphicdrv', '');
    if( $graphicdrv != "" ){
      $this->graphicdrv = $graphicdrv;
    }

    // Choose graphic driver
    if ( !extension_loaded("imagick")) $this->graphicdrv ="gd";

    switch( $this->graphicdrv ){
      case "imagick": $this->gd = new ImagickDriver( $this->temppath, $this->bt, [0,0], $this->mp ); break;
      default: $this->gd = new GDDriver( $this->temppath, $this->bt, [0,0], $this->mp ); break;
    }
    
    // Long process
    $this->bt = new BreakTime( $this->temppath, $this->sign );
  }

  /**
   * Read Metadata class of jpeg or png
   */
  public function MetaRead( $path = '' ){
    if( 
      in_array ( strtolower( $this->ts['ext'] ), ['jpg', 'jpeg'] ) &&
      version_compare( PHP_VERSION, "8.0.0" ) >=0
    ){
      $this->Metadata = new Metadata();
    } else if( 
      strtolower($this->ts[ 'ext'] == 'png') && 
      version_compare( PHP_VERSION, "7.4") >= 0 && 
      !empty($path) 
    ){
      $this->Metadata = PNGMetadata::extract($path);
    }
  }

  /**
   * Loads the IMG Edit Layer
   *
   * @return array
   */
  public function load() {
    $json = [];
    $rs = $this->con
            ->select( "smplphotoalbum", "s" )
            ->fields("s", array('path','name'))
            ->condition( 'id', $this->id, '=' )
            ->execute();
    $record = $rs->fetchAssoc();
    $this->path = $record ["path"];
    $this->path = str_replace( "\\", "/", $this->path );
    //
    LIB::ResetEditSession("image", $this->ts);

    $this->ts['tempname'] = LIB::NewName( $record ["name"], 0 );
    $this->ts['id'] = $this->id;
    $this->ts["idx"] = 0;
    $this->ts['bkp'] = 0;
    $this->ts["name"] = $record["name"];
    $this->ts["path"] = $record['path'];
    $this->ts["cmd"][ $this->ts["idx"] ] = "";

    // sign url;
    $this->ts["signurl"] = LIB::SignUrl($this->ts);

    // Exif read from jpeg file
    $ext = LIB::getExt( $record ["name"] );

    if( in_array($ext, ['avif', 'xbm', 'xpm' ]) ){
      $json['ok'] = "-2";
      $json['msg'] = "Can not edit this type ( .".$ext." ) of file image";
      return $json;
    }

    if( in_array( strtolower( $ext ) , ['jpeg','jpg'] ) ){
      $this->ts['ext']  = 'jpeg';
    } else if( strtolower( $ext ) == "png" ){
      $this->ts['ext'] = "png";
    } else{
      $this->ts['ext']  = 'other';
    }

    //
    $this->ts["que"] = 0;
       
    array_map ( "unlink", glob ( $this->temppath . "*" ) );
    //
    $source   = realpath ( $this->root . $this->path . $this->ts["name"] );
    $dest     = $this->temppath . $this->ts["tempname"];
    copy ( $source, $dest );

    //Read metadata
    $this->ts['copyright'] = '';
    $this->ts['author']    = '';
    $this->MetaRead( $source );

    // jpeg file
    if($this->ts['ext']=="jpeg" && is_object( $this->Metadata) ){
      try{
        $this->Metadata->read($source);
        $cpr = $this->Metadata->get( Metadata::COPYRIGHT ) ;
        if ($cpr == false ){
          $this->ts['copyright'] = '';
        }else{
          $this->ts['copyright'] = $cpr;
        }

        $auth = $this->Metadata->get (Metadata::AUTHOR );
        if($auth == false){
          $this->ts['author'] = '';
        }else{
          $this->ts['author'] = $auth;
        }
      }catch(\Exception $e){
        $this->ts['copyright'] = '';
        $this->ts['author'] = '';
      }
    }

    //png file
    if( $this->ts['ext'] == "png" && !empty( $this->Metadata ) ){
      $this->ts["author"] = $this->Metadata["xmp"]["creator"];
      $this->ts["copyright"] = $this->Metadata["xmp"]["rights"];
    }

    // driver GD / Imagick
    $size = getimagesize ( $this->temppath . $this->ts["newname"] );
    $this->ts["width"]    = $size [0];
    $this->ts["height"]   = $size [1];
    $this->ts["filesize"] = Lib::ShowFileSize( filesize( $this->temppath . $this->ts["newname"] ) );
    $this->ts["modified"] = date ( "Y.m.d H:i:s",filemtime($this->temppath . $this->ts["newname"] ) );
    $this->ts["avgcolor"] = $this->AverageColor( $this->temppath . $this->ts["newname"] , $size [0], $size [1] );
    //
    LIB::setSession( "smpl", $this->ts );
    return $this->MakeJson();
    //return $json;
  }

  /**
   * Average Color
   * @param string $source
   * @param string $w - width
   * @param string $h - height
   */
  function Averagecolor($source, $w, $h){
    if($w == 0 || $h == 0){
      return "#888888";
    }
    
    $r = $g = $b = 0.0;
    if($this->graphicdrv =="imagick" )
      $this->getImagickAVGColor( $source, $w, $h, $r, $g, $b );
    else 
      $this->getGDAVGColor( $source, $w, $h, $r, $g, $b );
 
    $r = substr("0".dechex((int)$r), -2);
    $g = substr("0".dechex((int)$g), -2);
    $b = substr("0".dechex((int)$b), -2);
    $color = "#".$r.$g.$b;
    return $color;
  }
  /**
   * get AVG color with GD
  */
  function getGDAVGColor( $source, $w, $h, &$r, &$g, &$b){
	 $di = $dj=1;
    $szorzat = ($w/$di)*($h/$dj);
    $img = imagecreatetruecolor((int)$w, (int)$h);
    $ext = LIB::getExt( $source );
    switch ($ext) {
      case 'avif' :
        $img = @imagecreatefromavif( $source );
        break;
      case 'jpg' :
      case 'jpeg' :
        $img = @imagecreatefromjpeg( $source );
        break;
      case 'png' :
        $img = @imagecreatefrompng( $source );
        break;
      case 'wbmp' :
        $img = @imagecreatefromwbmp( $source );
        break;
      case 'gif' :
        $img = @imagecreatefromgif( $source );
        break;
      case 'bmp' :
        $img = @imagecreatefrombmp( $source );
        break;
      case 'xbm' :
        $img = @imagecreatefromxbm( $source );
        break;
      case 'xpm' :
        $img = @imagecreatefromxpm( $source );
        break;
      case 'webp' :
        $img = @imagecreatefromwebp( $source );
        break;
    }
    $scaled = imagescale($img, 1, 1, IMG_BICUBIC); 
    $index  = imagecolorat($scaled, 0, 0);
    $rgb    = imagecolorsforindex($scaled, $index); 
    $r = round(round(($rgb['red'] / 0x33)) * 0x33); 
    $g = round(round(($rgb['green'] / 0x33)) * 0x33); 
    $b = round(round(($rgb['blue'] / 0x33)) * 0x33); 
  }

  /**
   * get AVG color with Imagick
  */
  function getImagickAVGColor( $source, $w, $h, &$r, &$g, &$b ){    
    $img = new \Imagick( $source );
    $img->scaleImage(1,1, false);
    $pixel = $img->getImagePixelColor(0,0);
    $rgb = $pixel->getcolor();

    $r = $rgb['r'];
    $g = $rgb['g'];
    $b = $rgb['b'];
  }

  /**
   * Edit the image
   */
  public function edit() {
    $json = [];
    $this->ts["idx"]++;
    if ($this->ts["idx"] > $this->ts["que"]) {
      $this->ts["que"] = $this->ts["idx"];
    }

    //    
    $newname   = LIB::NewName( $this->ts["name"], $this->ts['idx'] );

    $type      = LIB::getExt( $this->ts["name"] );
    $size      = GetImagesize( $this->temppath . $this->ts["tempname"] );
    $this->img = new ImgManipulate(
      $this->graphicdrv,
      $this->temppath,
      $this->ts["tempname"],
      $newname,
      $type,
      $size,
      $this->cfg,
      $this->sign,
      $this->bt
    );
    //
    $cmd = LIB::Request( 'cmd' );

    //Itt jön létre a sign file
    file_put_contents( $this->temppath . $this->sign, "0%" );

    $this->ts["cmd"] [ $this->ts["idx"] ] = $cmd;

    switch ( $cmd ) {
      //Enhance Menu
      case "enhance" :
        break;
      case "redeye" :
        $x1 = LIB::Request( 'x1', 0 );
        $y1 = LIB::Request( 'y1', 0 );
        $x2 = LIB::Request( 'x2', 0 );
        $y2 = LIB::Request( 'y2', 0 );
        $this->img->redeye( $x1, $y1, $x2, $y2 );
        break;

      //Add menu
      case "watermark" :
        $x1 = Lib::Request( 'x1', 0 );
        $y1 = Lib::Request( 'y1', 0 );
        $x2 = Lib::Request( 'x2', $size [0] );
        $y2 = Lib::Request( 'y2', $size [1] );
        $wmalpha   = (int) Lib::Request( 'wmalpha', 50 );
        $wmpath    = Lib::Request( 'wmpath', '');
        $copyright = Lib::Request( 'copyright', '');
        $author    = Lib::Request( 'author' , '');
        $color     = Lib::Request( 'color' , '');

        if(empty($wmpath)){
          $wmpath = isset( $this->ts['wmpath'] )? $this->ts['wmpath'] : '';
        }
        if(empty($wmpath )){
          $wmpath = $this->cfg->get( 'wmpath' );
        }

        if( empty( $copyright ) ){
          $copyright = isset( $this->ts['copyright'] ) ? $this->ts['copyright'] : '';
        }
        if( empty( $copyright ) ){
          $copyright = $this->cfg->get('copyright');
        }
        $this->ts['copyright'] = $copyright;

        if(empty($author)){
          $author = isset( $this->ts['author'] ) ? $this->ts['author'] : '';
        }
        if(empty($author)){
          $author = $this->cfg->get('author');
        }
        $this->ts['author'] = $author;

        if(strpos($color, "#" ) === false ){
          $color = "#".$color;
        }
        $this->img->Watermark( $x1, $y1, $x2, $y2, $copyright, $author, $wmalpha, $wmpath, $color);
        break;

      case "vignette" :
        $x1       = LIB::Request( 'x1', (int) 0 );
        $y1       = LIB::Request( 'y1', (int) 0 );
        $x2       = LIB::Request( 'x2', (int) $size [0] );
        $y2       = LIB::Request( 'y2', (int) $size [1] );
        $kind     = LIB::Request( "kind", 'dark' );
        $blursize = LIB::Request( "blursize", (int) 10 );
        if($blursize < 1) $blursize = 2;
        $deep     = LIB::Request( "deep", (int) 10 );
        $geom     = LIB::Request( "geom", 'circle' );
        $sigma    = LIB::Request( "sigma",1 );
        $radius   = LIB::Request( "radius",10 );

        $this->img->Vignette( $this->graphicdrv, $geom, $x1, $y1, $x2, $y2, $kind, (int) $blursize, (int) $deep, $sigma, $radius);
        break;

      case "border" :
        $bordercolor = LIB::Request( "bordercolor", 'ff0000' );
        $top         = LIB::Request( "top"        , 3 );
        $innerbevel  = LIB::Request( "innerbevel" , 3 );
        $outerbevel  = LIB::Request( "outerbevel" , 3 );
        $height      = LIB::Request( "height" , 3 );
        $this->img->Border( $bordercolor, $top, $innerbevel, $outerbevel, $height);
        break;
      case "bevel" :
        $bevel     = LIB::Request("width",10);
        $depht     = LIB::Request("depht",3);
        $direction = LIB::Request("direction",0);
        $this->img->Bevel($bevel, $depht, $direction);
        break;

      //Rotate menu
      case "autoorient":
        $this->img->AutoOrient();
        break;

      case "rotate" :
        $this->img->Rotate ( LIB::Request( 'rotate', 180 ) );
        break;

      case "flip_vertical" :
        $this->img->Imageflip ( IMG_FLIP_VERTICAL );
        break;

      case "flip_horizontal" :
        $this->img->Imageflip ( IMG_FLIP_HORIZONTAL );
        break;

      case "crop" :
        $x1 = LIB::Request( 'x1', 0 );
        $y1 = LIB::Request( 'y1', 0 );
        $x2 = LIB::Request( 'x2', $size [0] );
        $y2 = LIB::Request( 'y2', $size [1] );
        if ($x1 >= 0 && $x2 >= 0 && $y1 >= 0 && $y2 >= 0) {
          $this->img->Crop( $x1, $y1, $x2, $y2 );
        }
        break;

      case "resize" :
        $wp = LIB::Request( 'wp', - 1 );
        $hp = LIB::Request( 'hp', - 1 );
        if ($wp >= 0 && $hp >= 0) {
          $this->img->Resize ( $wp, $hp );
        }
        break;

      case "shave" :
        $cols = ( int ) LIB::Request( "cols", 1 );
        $rows = ( int ) LIB::Request( "rows", 1 );
        $this->img->Shave($cols, $rows);
        break;

      case "perspective" :
        $points     = [];
        $points[0]  = ( int ) LIB::Request( "xs1", 0 );  //top left
        $points[1]  = ( int ) LIB::Request( "ys1", 0 );
        $points[2]  = ( int ) LIB::Request( "xt1", 10 );
        $points[3]  = ( int ) LIB::Request( "yt1", 10 );

        $points[4]  = ( int ) LIB::Request( "xs2", $size[0]-1 );  //top right
        $points[5]  = ( int ) LIB::Request( "ys2", 0 );
        $points[6]  = ( int ) LIB::Request( "xt2", $size[0]-12 );
        $points[7]  = ( int ) LIB::Request( "yt2", 30 );

        $points[8]  = ( int ) LIB::Request( "xs3", $size[0]-1 ); //bottom right
        $points[9]  = ( int ) LIB::Request( "ys3", $size[1]-1);
        $points[10] = ( int ) LIB::Request( "xt3", $size[0]-30 );
        $points[11] = ( int ) LIB::Request( "yt3", $size[1]-50 );

        $points[12] = ( int ) LIB::Request( "xs4", 0 );   //bottom left
        $points[13] = ( int ) LIB::Request( "ys4", $size[1]-1 );
        $points[14] = ( int ) LIB::Request( "xt4", 10 );
        $points[15] = ( int ) LIB::Request( "yt4", $size[0]-20 );

        $this->img->Perspective ( $points);
        break;

      case "distortion"  :
      case "lens" :
        $a = (float) LIB::Request( 'a', 0.2 );
        $b = (float) LIB::Request( 'b', 0.2 );
        $c = (float) LIB::Request( 'c', 0.2 );
        $d = (float) LIB::Request( 'd', 10 );
        $centerx  = (int) LIB::Request( 'centerx', 0 );
        $centery  = (int) LIB::Request( 'centery', 0 );
        $color    = LIB::Request( 'color', "#808080" );
        $bestfit  = LIB::Request( 'bestfit', "true");
        $bestfit  = ($bestfit == "true");
        $this->img->lens ( $a, $b, $c, $d, $centerx, $centery, $color, $bestfit);
        break;

      // Effect menu
      case "grayscale" :
        $this->img->Grayscale ();
        break;

      case "bw":
        $level  = (int) LIB::Request("level", 50);
        $this->img->BW($level);
        break;

      case "white":
        $r = ( int ) LIB::Request( "red"      , 1 );
        $g = ( int ) LIB::Request( "green"    , 1 );
        $b = ( int ) LIB::Request( "blue"     , 1 );
        $alpha = ( int ) LIB::Request( "alpha", 1 );
        $this->img->White($r, $g, $b, $alpha);
        break;

      case "charchoal":
        $radius = (int) LIB::Request("radius", 5);
        $sigma  = (int) LIB::Request("sigma", 2);
        $this->img->Charchoal( $radius, $sigma );
        break;

      case "oil":
        $radius = (int) LIB::Request("radius", 1);
        $level  = (int) LIB::Request("intlevel", 240);
        $this->img->Oil( $radius, $level );
        break;

      case "sepia":
        $level = LIB::Request("level",80);
        $this->img->Sepia($level);
        break;

      case "blueshift":
        $level = LIB::Request("level",15);
        $this->img->BlueShift($level);
        break;
      
        case "solarize":
          $treshold = LIB::Request("treshold",1);
          $this->img->Solarize($treshold);
          break;

      case "clahe":
        $width  = (int) LIB::Request("width",10);
        $height = (int) LIB::Request("height",10);
        $bins   = (int) LIB::Request("bins",29);
        $clip   = (float) LIB::Request("clip",100);
        $this->img->Clahe($width, $height, $bins, $clip);
        break;

      // Color menu
      case "whitebalance":        
        $mode = LIB::Request("mode","white");
        $exclude = (int) LIB::Request("exclude", 0);
        $this->img->Whitebalance($mode, $exclude);
        break;

      case "normalize":
        $channel = LIB::Request( "channel", "all" );
        $mode = LIB::Request("mode", 1);
        $dynamic = LIB::Request("dynamic", 254);
        $this->img->Normalize($this->graphicdrv, $channel, $mode, $dynamic );
        break;

      case "gamma" :
        $gammain   = LIB::Request( "gammain",  0 );
        $gammaout  = LIB::Request( "gammaout", 0 );
        $autogamma = LIB::Request( "autogamma", "false" );
        $autogamma = ( $autogamma == "true");
        $this->img->Gamma( $gammain, $gammaout, $autogamma );
        break;

      case "contrast" :
        $contrast = LIB::Request( 'contrast', 0 );
        $this->img->Contrast ( $contrast );
        break;

      case "brightness" :
      case "saturation" :
      case "hue" :
        $brightness = LIB::Request( 'brightness', 0 );
        $saturation = LIB::Request( 'saturation', 0 );
        $hue        = LIB::Request( 'hue', 0 );
        $this->img->Brightness ( $brightness, $saturation, $hue );
        break;

      case "rgb" : // colorize
        $r = ( int ) LIB::Request( "red"      , 1 );
        $g = ( int ) LIB::Request( "green"    , 1 );
        $b = ( int ) LIB::Request( "blue"     , 1 );
        $alpha = ( int ) LIB::Request( "alpha", 1 );
        $this->img->RGB( $r, $g, $b, $alpha );
        break;

      case "histogram":
        $equalize = LIB::Request("equalize", "false");
        $json = $this->img->Histogram($equalize);
        return $json;

      // Special Effects
      case "equalize":
        $this->img->Equalize();
        break;

      case "noise" : // Add Noise
        $level   = LIB::Request( "level", 1 );
        $type    = LIB::Request( "type", "uniform" );
        $channel = LIB::Request( "channel", "all" );
        $this->img->AddNoise( $level, $type, $channel );
        break;

      case "denoise" : // Smooth == Denoise???
        $type  = LIB::Request( "type", "denoise" );
        $level = LIB::Request( "level", 1 );
        $softness = LIB::Request( "softness", 0 );
        $this->img->DeNoise( $level, $type, $softness );
        break;

      case "sharp" :
        $radius = LIB::Request( "radius", 3 );
        $sigma  = LIB::Request( "sigma", 3 );
        $level  = LIB::Request( "level", 3 );
        $this->img->Sharp( (int) $radius, (int) $sigma, (int) $level );
        break;

      case "emboss" :
        $radius = LIB::Request( 'radius', 1 );
        $sigma  = LIB::Request( 'sigma', 1 );
        $level  = LIB::Request( 'level', 1 );
        $this->img->Emboss ($radius, $sigma, $level);
        break;

      case "edge" :
        $radius = LIB::Request( 'radius', 0 );
        $this->img->Edge ($radius);
        break;

      case "trim" :
        $fuzz = (float) LIB::Request( "fuzz"  , 0.1 );
        $r = ( int ) LIB::Request( "red"  , 1 );
        $g = ( int ) LIB::Request( "green", 1 );
        $b = ( int ) LIB::Request( "blue" , 1 );
        $a = ( int ) LIB::Request( "alpha", 1 );
        $this->img->Trim ($fuzz, $r, $g, $b, $a);
        break;

      case "blur":
        $radius = LIB::Request( 'radius' , 0 );
        $sigma  = LIB::Request( 'sigma'  , 0 );
        $type   = LIB::Request( 'type'   , 'blur' );
        $channel= LIB::Request( 'channel', "default" );
        $level  = LIB::Request( 'level'  , 1 );
        $angle  = LIB::Request( 'angle'  , 0 );
        $this->img->Blur($radius, $sigma, $type, $channel, $angle, $level);
        break;

      case "convolution" :
        $c00 = ( int ) LIB::Request( 'c00', 0 );
        $c01 = ( int ) LIB::Request( 'c01', 0 );
        $c02 = ( int ) LIB::Request( 'c02', 0 );
        $c10 = ( int ) LIB::Request( 'c10', 0 );
        $c11 = ( int ) LIB::Request( 'c11', 0 );
        $c12 = ( int ) LIB::Request( 'c12', 0 );
        $c20 = ( int ) LIB::Request( 'c20', 0 );
        $c21 = ( int ) LIB::Request( 'c21', 0 );
        $c22 = ( int ) LIB::Request( 'c22', 0 );
        $div = ( int ) LIB::Request( 'div', 0 );
        $off = ( int ) LIB::Request( 'offs', 0 );
        $this->img->Convolution( $c00, $c01, $c02, $c10, $c11, $c12, $c20, $c21, $c22, $div, $off );
        break;

      case "wave":
        $amplitude  = LIB::Request( 'amplitude' , 5 );
        $wavelength = LIB::Request( 'wavelength', 20 );
        $this->img->Wave($amplitude, $wavelength);
        break;
      
        case "swirl":
          $angle = LIB::Request( 'angle' , 90 );
          $this->img->Swirl( $angle);
          break;
                
      case "repair" :
      case "skintune" :        
      case "exposure":
      case "levels":
      case "autolevels":
      case "lighteq":
      case "dehaze":
      case "dodge":
      case "burn":
      case "liquify":

      default:
        $json = [];
        $json["ok"] ="-3";        
        $json["msg"] = ucfirst( $cmd ) . ": Under Construction!";
        return $json;
    } // end of switch

    if ( file_exists( $this->temppath . $this->sign )) {
      $this->img->save();

      //Read and write metadata of jpeg image
      if( $cmd == "watermark" && ( !empty( $copyright ) || !empty( $author ) ) ){
        // Exif of jpeg Read && Write Jpeg
        if ( in_array( strtolower($this->ts['ext'] ), ['jpg','jpeg']) && !empty($this->Metadata)){
          $this->Metadata->read( $this->temppath . $newname );
          $this->Metadata->set( Metadata::COPYRIGHT   , $copyright );
          $this->Metadata->set( Metadata::AUTHOR      , $author );
          $this->Metadata->set( Metadata::PHOTOGRAPHER, $author );
          $this->Metadata->write( $this->temppath . $newname );
        } else if( strtolower( $this->ts['ext']) == "png" && !empty( $this->Metadata )){
          $this->Metadata = PNGMetadata::extract($this->temppath . $newname );
        }
        $this->ts['copyright'] = $copyright;
        $this->ts['author']    = $author;
      }

      $this->ts['tempname'] = $newname;
      LIB::setSession('smpl', $this->ts);
    }

    unlink ( $this->temppath . $this->sign );
    return $this->MakeJson();
  }
  /**
   * Save AS edited image
   * @param mixed $src - old name
   * @param mixed $dst - new name
   * @return string
   */
  public function saveas($src = "", $name = ""){
    global $base_url;
    $json = $this->MakeJson();
    $srcp = str_replace(['\\','//'], '/', $this->temppath.$src );
    $dst = $this->root.$this->ts["path"].$name;
    $dstp = str_replace("//", "/", $dst );

    $ok = copy($srcp, $dstp);
    $json["msg"] = ($ok ? "":"Can not save the image\n");

    //Make thumbnail
    $okt = $this->MakeThumbnail($dstp, $name);
    $json ["ok"]   = empty($okt) && $ok ? "1" : "-1";
    $json ["link"] = $base_url . "/" . $this->mp . "/v.php?i=" . $this->ts["path"] . $name;
    if( !empty($okt) ){
      $json ["msg"] .= "Can not make new thumbnail\n";
    }
    return $json;
  }
  /**
   * Save the modified image
   *
   * @param string $cmd
   * @return string
   */
  public function save($cmd = "") {
    global $base_url;
    $json = $this->MakeJson();

    $src = $this->temppath . $this->ts['tempname'];
    $dst = str_replace("\\","/",realpath ( $this->root . $this->ts['path'] . $this->ts['name'] ));
    $ok  = copy( $src, $dst );
    $json["msg"] = ($ok ? "":"Can not save the image\n");

    // ********** Makes new thumbnail after save the file ***************
    $okt =  $this->MakeThumbnail($dst, $this->ts["name"] );
    $json ["ok"] = empty($okt) && $ok ? "1" : "-1";
    $json ["link"] = $base_url . "/" . $this->mp . "/v.php?i=" . $this->ts["path"] . $this->ts["name"];
    if(!empty($okt) ){
      $json ["msg"] .= "Can not make new thumbnail\n";
    }
    return $json;
  }

  public function MakeThumbnail( $dst, $name ){
    $size = $this->gd->GetImageSize( $dst );
    $dx = $size[ 0 ];
    $dy = $size[ 1 ];
    $width  = ( int ) ( $this->ts[ "width" ] );
    $height = $width * $dy / $dx;

    // Target image
    // driver GD / Imagick
    $dst_img = $this->gd->ImageCreateTrueColor( $width, $height );
    //
    $thumbnail = $this->root . $this->ts["path"] . self::TN . $name;
    $type = LIB::getExt( $name );

    // driver GD / Imagick
    $p = pathinfo($name);
    $src_img = $this->gd->ImageCreateFrom( $dst, strtolower($p["extension"]) );
    $dst_img = $this->gd->ImageCopyResized( $src_img, 0, 0, 0, 0, $width, $height, $dx, $dy );
    $ok = $this->gd->Image( $dst_img, $thumbnail );
    $msg = $ok ? "" : "can not make new thumbnail\n";
    return $msg;
  }

  /**
   * Undo
   * @return string
   */
  public function undo() {
    if ( $this->ts["idx"] > 0) {
      $this->ts["idx"]--;
      $this->ts["tempname"] = LIB::NewName( $this->ts["name"], $this->ts["idx"] );      
    }
    $this->ts["signurl"] = LIB::SignUrl($this->ts);
    if(file_exists($this->temppath . $this->sign)){
      @unlink ( $this->temppath . $this->sign );
    }
    return $this->MakeJson();
  }

  /**
   * Redo
   * @return string
   */
  public function redo() {
    if ($this->ts["idx"] < $this->ts["que"]) {
      $this->ts["idx"]++;
      $this->ts["tempname"] = LIB::NewName( $this->ts['name'], $this->ts["idx"] );      
    }
    $this->ts["signurl"] = LIB::SignUrl($this->ts);
    @unlink ( $this->temppath . $this->sign );
    return $this->MakeJson();
  }

  /**
   * Delete the edited image from hdd and close the image edit window
   *
   * @return array
   */
  public function close() {
    @unlink ( $this->temppath . $this->sign );
    array_map ( 'unlink', glob ( $this->temppath . '*_temp_*' ) );
    Lib::ResetEditSession("image", $this->ts );
    return [ "ok" => 'closed' ];
  }

  /**
   * Cancel the actual process with deleting the sign file
   *
   * @return string[]
   */
  public function cancel() {
    // delete the sign file
    @unlink ( $this->temppath . $this->sign );

    // delete the temporay files
    $filename = LIB::getFilename( $this->ts["tempname"] );     
    array_map ( 'unlink', glob ( $this->temppath . $filename . "_#*" ) );
    return $this->MakeJson();
  }

  /**
   * Make and return json file to ajax calling
   *
   * @param string $i
   * @param string $e
   * @return string[]|mixed[]
   */
  function MakeJson($i = '', $e = '') {
    $json = [];
    if (empty ( $i )) {
      // driver GD / Imagick
      $size = $this->gd->GetImagesize ( $this->temppath . $this->ts["tempname"] );
      //
      $json['id']   = $this->ts['id'];
    
      $json["modified"] = $this->ts["modified"];
      $json["copyright"] = $this->ts['copyright'];
      $json["author"]    = $this->ts['author'];               
      $json['avgcolor']  = $this->ts["avgcolor"];

      $json['ok']     = $this->ts['id'];
      $json["width"]  = $size [0];
      $json["height"] = $size [1];
      $json["filesize"] = $this->ts["filesize"];
      $json['id']     = $this->ts['id'];
      $json["idx"]    = $this->ts["idx"];
      $json["name"]   = $this->ts["name"];
      $json["que"]    = $this->ts["que"];

      $json["tempname"] = $this->ts["tempname"];
      $json["url"]      = $this->ts['tempurl'];      
      $json["signurl"]  = LIB::SignUrl($this->ts);
      $json["bkp"]    = $this->ts["bkp"];      
      $json['ext']    = $this->ts['ext'];

      if($this->ts["idx"]>0){
        $json["prev"] = $this->ts["cmd"][ $this->ts["idx"] - 1 ];
      }else{
        $json["prev"] = '';
      }
      
      if($this->ts["idx"] < $this->ts["que"]){
        $json["next"] = $this->ts["cmd"][ $this->ts["idx"] + 1 ];
      }else{
        $json["next"] = "";
      }
    } else {
      $json [$i] = $e;
    }
    LIB::setSession( "smpl", $this->ts );
    return $json;
  }  
}