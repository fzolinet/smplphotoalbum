<?php
namespace Drupal\smplphotoalbum\Controller;

require (__DIR__."/../../")."/vendor/autoload.php";

use Drupal\smplphotoalbum\Controller\GDDriver;
use Drupal\smplphotoalbum\Controller\ImagickDriver;
use Drupal\smplphotoalbum\Controller\BreakTime;
use Holiday\Metadata;
use PNGMetadata\PNGMetadata;

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
  //private $Metaarray;

  public function __construct( $id ) {
    $this->id   = $id;
    $this->rq   = \Drupal::request();
    $this->sess = $this->rq->getSession ();
    $this->ts   = $this->sess->get( "smpl" );
    $this->sign = $this->Sign();

    $this->cfg = \Drupal::config( 'smplphotoalbum.settings' );
    $this->mp  = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->con = \Drupal::database();

    $root       = $this->cfg->get( 'root' );
    $this->root = $this->getRoot( $root );

    // temppath for copy
    $t = $this->cfg->get( 'temp' );
    $this->temppath = str_replace( "\\", "/", \Drupal::service ( 'file_system' )->realpath ( $t ) );
    $this->temppath .= substr( $this->temppath, - 1 ) != '/' ? "/" : '';

    // tempurl to edit
    $this->ts ['tempurl'] = \Drupal::service( 'file_url_generator' )->generateAbsoluteString ( $t );
    $this->ts ['tempurl'] .= substr( $this->ts['tempurl'], - 1 ) != '/' ? "/" : '';

    // Graphic driver
    $this->graphicdrv = $this->cfg->get ( 'graphicdrv' );
    if( isset( $this->ts["graphicdrv"] ) ){
      $this->graphicdrv  = $this->ts["graphicdrv"];
    }

    $graphicdrv = $this->Request( 'graphicdrv', '');
    if( $graphicdrv != "" ){
      $this->graphicdrv = $graphicdrv;
    }

    // Choose graphic driver
    if ( !extension_loaded("imagick")) $this->graphicdrv ="gd";

    switch($this->graphicdrv){
      case "imagick":
        $this->gd = new ImagickDriver( $this->temppath, $this->bt, [0,0], $this->mp );
        break;
      default:
        $this->gd = new GDDriver( $this->temppath, $this->bt, [0,0], $this->mp );    
        break;
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
    $rs = $this->con->select( "smplphotoalbum", "s" )
               ->fields("s", array('path','name'))
               ->condition( 'id', $this->id, '=' )
               ->execute();
    $record = $rs->fetchAssoc();
    $this->path = $record ["path"];
    $this->path = str_replace( "\\", "/", $this->path );
    //
    $this->unset();
    $this->ts['tempname'] = $this->NewName ( $record ["name"], 0 );
    $this->ts['id'] = $this->id;
    $this->ts["idx"] = 0;
    $this->ts['bkp'] = 0;
    $this->ts["name"] = $record["name"];
    $this->ts["path"] = $record['path'];
    $this->ts["cmd"][ $this->ts["idx"] ] = "";

    // sign url;
    $this->ts["signurl"] = $this->SignUrl();

    // Exif read from jpeg file
    $ext = $this->getExt( $record ["name"] );

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
    $this->ts ["que"] = 0;
       
    array_map ( "unlink", glob ( $this->temppath . "*" ) );
    //
    $source   = realpath ( $this->root . $this->path . $this->ts["name"] );
    $dest     = $this->temppath . $this->ts ["tempname"];
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
    $size = getimagesize ( $this->temppath . $this->ts ["tempname"] );
    $this->ts["width"]    = $size [0];
    $this->ts["height"]   = $size [1];
    $this->ts["filesize"] = $this->ShowFileSize( filesize( $this->temppath . $this->ts ["tempname"] ) );
    $this->ts["modified"] = date ( "Y.m.d H:i:s",filemtime($this->temppath . $this->ts ["tempname"] ) );
    $this->ts["avgcolor"] = $this->AverageColor( $this->temppath . $this->ts ["tempname"] , $size [0], $size [1] );
    //
    $this->sess->set( "smpl", $this->ts );
    return $this->MakeJson();
    //return $json;
  }

  function ShowFileSize($size) {
		if ($size > 1073741824) {
			$s = ( int ) ( $size / 1073741824 ) . '&nbsp;GB';
		} else if ( $size > 1048576 ) {
			$s = ( int ) ( $size / 1048576 ) . '&nbsp;MB';
		} else if ( $size > 1024)  {
			$s = ( int ) ( $size / 1024 ) . '&nbsp;KB';
		} else {
			$s = $size . '&nbsp;B';
		}
		return $s;
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
    $szorzat = ($w/$di)*($h/$dj);
    $img = imagecreatetruecolor((int)$w, (int)$h);
    $ext = $this->getExt( $source );
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
    $tempname  = $this->ts["tempname"];
    $newname   = $this->NewName( $this->ts["name"], $this->ts['idx'] );

    $type      = $this->getExt( $this->ts ["name"] );
    $size      = GetImagesize( $this->temppath . $tempname );
    $this->img = new ImgManipulate(
      $this->graphicdrv,
      $this->temppath,
      $tempname,
      $newname,
      $type,
      $size,
      $this->cfg,
      $this->sign,
      $this->bt
    );
    //
    $cmd = $this->rq->query->get( 'cmd' );

    //Itt jön létre a sign file
    file_put_contents( $this->temppath . $this->sign, "0%" );

    $this->ts["cmd"][$this->ts["idx"]] = $cmd;

    switch ( $cmd ) {
      //Enhance Menu
      case "enhance" :
        break;
      case "redeye" :
        $x1 = $this->Request( 'x1', 0 );
        $y1 = $this->Request( 'y1', 0 );
        $x2 = $this->Request( 'x2', 0 );
        $y2 = $this->Request( 'y2', 0 );
        $this->img->redeye( $x1, $y1, $x2, $y2 );
        break;

      //Add menu
      case "watermark" :
        $x1 = $this->Request( 'x1', 0 );
        $y1 = $this->Request( 'y1', 0 );
        $x2 = $this->Request( 'x2', $size [0] );
        $y2 = $this->Request( 'y2', $size [1] );
        $wmalpha   = (int) $this->Request( 'wmalpha', 50 );
        $wmpath    = $this->Request( 'wmpath', '');
        $copyright = $this->Request( 'copyright', '');
        $author    = $this->Request( 'author' , '');
        $color     = $this->Request( 'color' , '');

        if(empty($wmpath)){
          $wmpath = isset( $this->ts['wmpath'] )? $this->ts['wmpath'] : '';
        }
        if(empty($wmpath )){
          $wmpath = $this->cfg->get( 'wmpath' );
        }

        if(empty($copyright)){
          $copyright = isset($this->ts['copyright'])? $this->ts['copyright']:'';
        }
        if(empty($copyright)){
          $copyright = $this->cfg->get('copyright');
        }
        $this->ts['copyright'] = $copyright;

        if(empty($author)){
          $author = isset($this->ts['author'])? $this->ts['author']:'';
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
        $x1       = $this->Request( 'x1', (int) 0 );
        $y1       = $this->Request( 'y1', (int) 0 );
        $x2       = $this->Request( 'x2', (int) $size [0] );
        $y2       = $this->Request( 'y2', (int) $size [1] );
        $kind     = $this->Request( "kind", 'dark' );
        $blursize = $this->Request( "blursize", (int) 10 );
        if($blursize < 1) $blursize = 2;
        $deep     = $this->Request( "deep", (int) 10 );
        $geom     = $this->Request( "geom", 'circle' );
        $sigma    = $this->Request( "sigma",1 );
        $radius   = $this->Request( "radius",10 );

        $this->img->Vignette( $this->graphicdrv, $geom, $x1, $y1, $x2, $y2, $kind, (int) $blursize, (int) $deep, $sigma, $radius);
        break;

      case "border" :
        $bordercolor = $this->Request( "bordercolor", 'ff0000' );
        $top         = $this->Request( "top"        , 3 );
        $innerbevel  = $this->Request( "innerbevel" , 3 );
        $outerbevel  = $this->Request( "outerbevel" , 3 );
        $height      = $this->Request( "height" , 3 );
        $this->img->Border( $bordercolor, $top, $innerbevel, $outerbevel, $height);
        break;
      case "bevel" :
        $bevel     = $this->Request("width",10);
        $depht     = $this->Request("depht",3);
        $direction = $this->Request("direction",0);
        $this->img->Bevel($bevel, $depht, $direction);
        break;

      //Rotate menu
      case "autoorient":
        $this->img->AutoOrient();
        break;

      case "rotate" :
        $this->img->Rotate ( $this->Request( 'rotate', 180 ) );
        break;

      case "flip_vertical" :
        $this->img->Imageflip ( IMG_FLIP_VERTICAL );
        break;

      case "flip_horizontal" :
        $this->img->Imageflip ( IMG_FLIP_HORIZONTAL );
        break;

      case "crop" :
        $x1 = $this->Request( 'x1', 0 );
        $y1 = $this->Request( 'y1', 0 );
        $x2 = $this->Request( 'x2', $size [0] );
        $y2 = $this->Request( 'y2', $size [1] );
        if ($x1 >= 0 && $x2 >= 0 && $y1 >= 0 && $y2 >= 0) {
          $this->img->Crop( $x1, $y1, $x2, $y2 );
        }
        break;

      case "resize" :
        $wp = $this->Request( 'wp', - 1 );
        $hp = $this->Request( 'hp', - 1 );
        if ($wp >= 0 && $hp >= 0) {
          $this->img->Resize ( $wp, $hp );
        }
        break;

      case "shave" :
        $cols = ( int ) $this->Request( "cols", 1 );
        $rows = ( int ) $this->Request( "rows", 1 );
        $this->img->Shave($cols, $rows);
        break;

      case "perspective" :
        $points     = [];
        $points[0]  = ( int ) $this->Request( "xs1", 0 );  //top left
        $points[1]  = ( int ) $this->Request( "ys1", 0 );
        $points[2]  = ( int ) $this->Request( "xt1", 10 );
        $points[3]  = ( int ) $this->Request( "yt1", 10 );

        $points[4]  = ( int ) $this->Request( "xs2", $size[0]-1 );  //top right
        $points[5]  = ( int ) $this->Request( "ys2", 0 );
        $points[6]  = ( int ) $this->Request( "xt2", $size[0]-12 );
        $points[7]  = ( int ) $this->Request( "yt2", 30 );

        $points[8]  = ( int ) $this->Request( "xs3", $size[0]-1 ); //bottom right
        $points[9]  = ( int ) $this->Request( "ys3", $size[1]-1);
        $points[10] = ( int ) $this->Request( "xt3", $size[0]-30 );
        $points[11] = ( int ) $this->Request( "yt3", $size[1]-50 );

        $points[12] = ( int ) $this->Request( "xs4", 0 );   //bottom left
        $points[13] = ( int ) $this->Request( "ys4", $size[1]-1 );
        $points[14] = ( int ) $this->Request( "xt4", 10 );
        $points[15] = ( int ) $this->Request( "yt4", $size[0]-20 );

        $this->img->Perspective ( $points);
        break;

      case "distortion"  :
      case "lens" :
        $a = (float) $this->Request( 'a', 0.2 );
        $b = (float) $this->Request( 'b', 0.2 );
        $c = (float) $this->Request( 'c', 0.2 );
        $d = (float) $this->Request( 'd', 10 );
        $centerx  = (int) $this->Request( 'centerx', 0 );
        $centery  = (int) $this->Request( 'centery', 0 );
        $color    = $this->Request( 'color', "#808080" );
        $bestfit  = $this->Request( 'bestfit', "true");
        $bestfit  = ($bestfit == "true");
        $this->img->lens ( $a, $b, $c, $d, $centerx, $centery, $color, $bestfit);
        break;

      // Effect menu
      case "grayscale" :
        $this->img->Grayscale ();
        break;

      case "bw":
        $level  = (int) $this->Request("level", 50);
        $this->img->BW($level);
        break;

      case "white":
        $r = ( int ) $this->Request( "red"      , 1 );
        $g = ( int ) $this->Request( "green"    , 1 );
        $b = ( int ) $this->Request( "blue"     , 1 );
        $alpha = ( int ) $this->Request( "alpha", 1 );
        $this->img->White($r, $g, $b, $alpha);
        break;

      case "charchoal":
        $radius = (int) $this->Request("radius", 5);
        $sigma  = (int) $this->Request("sigma", 2);
        $this->img->Charchoal( $radius, $sigma );
        break;

      case "oil":
        $radius = (int) $this->Request("radius", 1);
        $level  = (int) $this->Request("intlevel", 240);
        $this->img->Oil( $radius, $level );
        break;

      case "sepia":
        $level = $this->Request("level",80);
        $this->img->Sepia($level);
        break;

      case "blueshift":
        $level = $this->Request("level",15);
        $this->img->BlueShift($level);
        break;
      
        case "solarize":
          $treshold = $this->Request("treshold",1);
          $this->img->Solarize($treshold);
          break;

      case "clahe":
        $width  = (int) $this->Request("width",10);
        $height = (int) $this->Request("height",10);
        $bins   = (int) $this->Request("bins",29);
        $clip   = (float) $this->Request("clip",100);
        $this->img->Clahe($width, $height, $bins, $clip);
        break;

      // Color menu
      case "whitebalance":        
        $mode = $this->Request("mode","white");
        $exclude = (int) $this->Request("exclude", 0);
        $this->img->Whitebalance($mode, $exclude);
        break;

      case "normalize":
        $channel = $this->Request( "channel", "all" );
        $mode = $this->Request("mode", 1);
        $dynamic = $this->Request("dynamic", 254);
        $this->img->Normalize($this->graphicdrv, $channel, $mode, $dynamic );
        break;

      case "gamma" :
        $gammain   = $this->Request( "gammain",  0 );
        $gammaout  = $this->Request( "gammaout", 0 );
        $autogamma = $this->Request( "autogamma", "false" );
        $autogamma = ( $autogamma == "true");
        $this->img->Gamma( $gammain, $gammaout, $autogamma );
        break;

      case "contrast" :
        $contrast = $this->Request( 'contrast', 0 );
        $this->img->Contrast ( $contrast );
        break;

      case "brightness" :
      case "saturation" :
      case "hue" :
        $brightness = $this->Request( 'brightness', 0 );
        $saturation = $this->Request( 'saturation', 0 );
        $hue        = $this->Request( 'hue', 0 );
        $this->img->Brightness ( $brightness, $saturation, $hue );
        break;

      case "rgb" : // colorize
        $r = ( int ) $this->Request( "red"      , 1 );
        $g = ( int ) $this->Request( "green"    , 1 );
        $b = ( int ) $this->Request( "blue"     , 1 );
        $alpha = ( int ) $this->Request( "alpha", 1 );
        $this->img->RGB( $r, $g, $b, $alpha );
        break;

      case "histogram":
        $equalize = $this->Request("equalize", "false");
        $json = $this->img->Histogram($equalize);
        return $json;

      // Special Effects
      case "equalize":
        $this->img->Equalize();
        break;

      case "noise" : // Add Noise
        $level   = $this->Request( "level", 1 );
        $type    = $this->Request( "type", "uniform" );
        $channel = $this->Request( "channel", "all" );
        $this->img->AddNoise( $level, $type, $channel );
        break;

      case "denoise" : // Smooth == Denoise???
        $type  = $this->Request( "type", "denoise" );
        $level = $this->Request( "level", 1 );
        $softness = $this->Request( "softness", 0 );
        $this->img->DeNoise( $level, $type, $softness );
        break;

      case "sharp" :
        $radius = $this->Request( "radius", 3 );
        $sigma  = $this->Request( "sigma", 3 );
        $level  = $this->Request( "level", 3 );
        $this->img->Sharp( (int) $radius, (int) $sigma, (int) $level );
        break;

      case "emboss" :
        $radius = $this->Request( 'radius', 1 );
        $sigma  = $this->Request( 'sigma', 1 );
        $level  = $this->Request( 'level', 1 );
        $this->img->Emboss ($radius, $sigma, $level);
        break;

      case "edge" :
        $radius = $this->Request( 'radius', 0 );
        $this->img->Edge ($radius);
        break;

      case "trim" :
        $fuzz = (float) $this->Request( "fuzz"  , 0.1 );
        $r = ( int ) $this->Request( "red"  , 1 );
        $g = ( int ) $this->Request( "green", 1 );
        $b = ( int ) $this->Request( "blue" , 1 );
        $a = ( int ) $this->Request( "alpha", 1 );
        $this->img->Trim ($fuzz, $r, $g, $b, $a);
        break;

      case "blur":
        $radius = $this->Request( 'radius' , 0 );
        $sigma  = $this->Request( 'sigma'  , 0 );
        $type   = $this->Request( 'type'   , 'blur' );
        $channel= $this->Request( 'channel', "default" );
        $level  = $this->Request( 'level'  , 1 );
        $angle  = $this->Request( 'angle'  , 0 );
        $this->img->Blur($radius, $sigma, $type, $channel, $angle, $level);
        break;

      case "convolution" :
        $c00 = ( int ) $this->Request( 'c00', 0 );
        $c01 = ( int ) $this->Request( 'c01', 0 );
        $c02 = ( int ) $this->Request( 'c02', 0 );
        $c10 = ( int ) $this->Request( 'c10', 0 );
        $c11 = ( int ) $this->Request( 'c11', 0 );
        $c12 = ( int ) $this->Request( 'c12', 0 );
        $c20 = ( int ) $this->Request( 'c20', 0 );
        $c21 = ( int ) $this->Request( 'c21', 0 );
        $c22 = ( int ) $this->Request( 'c22', 0 );
        $div = ( int ) $this->Request( 'div', 0 );
        $off = ( int ) $this->Request( 'offs', 0 );
        $this->img->Convolution( $c00, $c01, $c02, $c10, $c11, $c12, $c20, $c21, $c22, $div, $off );
        break;

      case "wave":
        $amplitude  = $this->Request( 'amplitude' , 5 );
        $wavelength = $this->Request( 'wavelength', 20 );
        $this->img->Wave($amplitude, $wavelength);
        break;
      
        case "swirl":
          $angle = $this->Request( 'angle' , 90 );
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
          $this->ts['copyright'] = $copyright;
          $this->ts['author']    = $author;
        }

        if(strtolower( $this->ts['ext']) == "png" && !empty( $this->Metadata )){
          $this->Metadata = PNGMetadata::extract($this->temppath . $newname );

          $this->ts['copyright'] = $copyright;
          $this->ts['author']    = $author;
        }
      }

      $this->ts['tempname'] = $newname;
      $this->sess->set('smpl', $this->ts);
    }

    unlink ( $this->temppath . $this->sign );
    return $this->makejson();
  }
  /**
   * Save AS edited image
   * @param mixed $src - old name
   * @param mixed $dst - new name
   * @return string
   */
  public function saveas($src = "", $name = ""){
    global $base_url;
    $json = $this->makejson();
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
    $json = $this->makejson();

    $src = $this->temppath . $this->ts['tempname'];
    $dst = str_replace("\\","/",realpath ( $this->root . $this->ts['path'] . $this->ts ['name'] ));
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
    $type = $this->getExt( $name );

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
      $this->ts["tempname"] = $this->NewName ( $this->ts["name"], $this->ts["idx"] );      
    }
    $this->ts["signurl"] = $this->SignUrl();
    if(file_exists($this->temppath . $this->sign)){
      @unlink ( $this->temppath . $this->sign );
    }
    return $this->makejson();
  }

  /**
   * Redo
   * @return string
   */
  public function redo() {
    if ($this->ts["idx"] < $this->ts["que"]) {
      $this->ts ["idx"]++;
      $this->ts ["tempname"] = $this->NewName ( $this->ts ['name'], $this->ts["idx"] );      
    }
    $this->ts ["signurl"] = $this->SignUrl();
    @unlink ( $this->temppath . $this->sign );
    return $this->makejson ();
  }

  /**
   * Delete the edited image from hdd and close the image edit window
   *
   * @return array
   */
  public function close() {
    @unlink ( $this->temppath . $this->sign );
    array_map ( 'unlink', glob ( $this->temppath . '*_temp_*' ) );
    $this->unset ();
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
    $p = pathinfo ( $this->ts ["tempname"] );
    $filename = $p ["filename"];
    array_map ( 'unlink', glob ( $this->temppath . $filename . "_#*" ) );
    return $this->makejson ();
  }

  /**
   * Make and return json file to ajax calling
   *
   * @param string $i
   * @param string $e
   * @return string[]|mixed[]
   */
  function makejson($i = '', $e = '') {
    $json = [];
    if (empty ( $i )) {
      // driver GD / Imagick
      $size = $this->gd->GetImagesize ( $this->temppath . '/' . $this->ts ["tempname"] );
      //
      $json['id']   = $this->ts['id'];
    
      $json["modified"] = $this->ts["modified"];
      $json["copyright"] = $this->ts['copyright'];
      $json["author"]    = $this->ts['author'];
       
        
      $json['avgcolor']  = $this->ts["avgcolor"];

      $json ['ok']     = $this->ts ['id'];
      $json ["width"]  = $size [0];
      $json ["height"] = $size [1];
      $json ["filesize"] = $this->ts["filesize"];
      $json ['id']     = $this->ts ['id'];
      $json ["idx"]    = $this->ts ["idx"];
      $json ["name"]   = $this->ts ["name"];
      $json ["que"]    = $this->ts ["que"];

      $json ["tempname"] = $this->ts ["tempname"];
      $json ["url"]      = $this->ts ['tempurl'];      
      $json ["signurl"]  = $this->SignUrl();
      $json["bkp"]  = $this->ts["bkp"];      
      $json['ext']  = $this->ts['ext'];

      if($this->ts["idx"]>0){
        $json["prev"] = $this->ts["cmd"][ $this->ts ["idx"] - 1 ];
      }else{
        $json["prev"] = '';
      }

      
      if($this->ts["idx"] < $this->ts["que"]){
        $json["next"] = $this->ts["cmd"][ $this->ts ["idx"] + 1 ];
      }else{
        $json["next"] = "";
      }
    } else {
      $json [$i] = $e;
    }
    $this->sess->set( "smpl", $this->ts );
    return $json;
  }

  /**
   * * Unset data of imgedit functions
   */
  function unset() {
    unset (
      $this->ts ['tempname'],
      $this->ts ['path'],
      $this->ts ['name'],
      $this->ts ['que'],
      $this->ts ['idx'],
      $this->ts ['width']
    );
  }
  /**
   * get the GET request
   *
   * @param string $cmd
   * @return string
   */
  private function Request($cmd, $default = '') {
    $g = $this->rq->query->get ( $cmd );
    if ($g == "undefined")
      $g = $default;
    if (isset ( $g ))
      return $g;

    $r = $this->rq->request->get ( $cmd );
    if (isset ( $r ))
      return $r;
    return $default;
  }

  /**
   * Make a new name of temporary file
   *
   * @param string $name
   * @param number $i
   * @return string
   */
  private function NewName($name = "", $i = 0) {
    $p = pathinfo ( $name );
    $name = $p ["filename"] . "_temp_" . $i;
    $ext  = $p ["extension"];
    return $name . "." . $ext;
  }

  /**
   * Make a sign file into the temporary folder
   * @return string
   */
  function Sign() {
    $p = pathinfo ( $this->ts ["tempname"], PATHINFO_FILENAME ) ."_sign.txt";
    return $p;
  }

  function SignUrl(){    
    return $this->ts['tempurl'] . $this->sign();
  }

  /**
   * Get root
   * @param string $root
   *
   * @return string
   */
  function getRoot(string $root){
    $root = str_replace ( "public://", \Drupal::service( 'file_system' )->realpath( "public://" )."\\", $root);
    $root .= substr( $root, -1 ) != '/' ? "/" : '';
    $root = str_replace("\\","/", $root);
    return $root;
  }

  /**
	 * Give back the extension of image
	 */
	public function getExt($str){
		return strtolower(pathinfo($str,PATHINFO_EXTENSION));
	}
}