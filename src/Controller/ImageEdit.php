<?php

namespace Drupal\smplphotoalbum\Controller;

/**
 * Img Edit class functions
 * 
 * @author fz
 *        
 */
class ImageEdit {
  private $name;
  private $mp;
  private $root;
  private $con;
  private $id;
  private $path; // original path from database
  private $temppath; // temporary directory: tipically /DRUPAL_ROOT/...files/smplphotoalbum
  private $tempurl;
  private $generator;
  private $url; // tempfile url
  private $img; // ImgManipulate class  
  public $rq;
  public $ts; // Session
  public $sess; // local session storage
  
  public function __construct($id) {
    global $base_url;
    $this->id   = $id;
    $this->rq   = \Drupal::request();
    $this->sess = $this->rq->getSession();
    $this->ts   = $this->sess->get( "smpl" );

    $this->cfg = \Drupal::config('smplphotoalbum.settings');
    $this->mp  = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->con = \Drupal::database();
    
    $root  = $this->cfg->get('root');
    $root  = str_replace("public://", \Drupal::service('file_system')->realpath("public://")."/", $root);
    $root .= substr($root,-1) !='/'?"/":'';
    $this->root = $root;

    //temppath for copy
    $t = $this->cfg->get('temp');
    $this->temppath  = str_replace("\\","/", \Drupal::service('file_system')->realpath($t));  
    $this->temppath .= substr($this->temppath,-1) != '/' ?"/":'';        
    //tempurl to to edit
    $this->ts['tempurl']  = file_create_url($t);
    $this->ts['tempurl'] .= substr(ts['tempurl'],-1) != '/' ?"/":'';
  }
  
  /**
   * Loads the IMG Edit Layer
   * 
   * @param string $id          
   * @param string $url          
   * @param string $temp          
   * @return string
   */
  public function load() {
    $rs = $this->con->select ( "smplphotoalbum", "s" )->fields ( "s", array (
        'path',
        'name' 
    ) )->condition ( 'id', $this->id, '=' )->execute ();
    $record = $rs->fetchAssoc ();
    $this->path = $record ["path"];
    $this->path = str_replace ( "\\", "/", $this->path );    
    //
    $this->unset();    
    $this->ts['tempname'] = $record["name"] . "_temp_0";
    $this->ts['id']    = $this->id;
    $this->ts["idx"]   = 0;
    $this->ts["name"]  = $record ["name"];
    $this->ts["path"]  = $record ['path'];        
    $this->ts["que"]   = 0;    
    $this->ts["width"] = isset ( $_REQUEST ["w"] ) ? $_REQUEST ["w"] : 150;
    //
    $pattern = $this->temppath . "*_temp_*";
    $a = glob ( $pattern );
    array_map ( 'unlink', $a );
    //
    
    $source = realpath ( $this->root . $this->path . $this->ts ["name"] );
    $dest = $this->temppath . $this->ts["tempname"];
    copy ( $source, $dest );
    
    return $this->makejson();
  }
  
  /**
   * Edit the image
   */
  public function edit() {
    $this->ts ["idx"] ++;
    if ($this->ts ["idx"] > $this->ts ["que"]) {
      $this->ts ["que"] = $this->ts ["idx"];
    }
    $idx = $this->ts ["idx"];
    //
    $tempname = $this->ts ["tempname"];
    $newname = $this->ts ["name"] . "_temp_" . $idx;
    $type = strtolower ( pathinfo ( $this->ts ["name"], PATHINFO_EXTENSION ) );
    $this->img = new ImgManipulate ( $this->temppath, $tempname, $newname, $type );
    // rotate
    if (isset ( $_REQUEST ["rotate"] ) && is_numeric ( $_REQUEST ["rotate"] )) {
      $this->img->rotate ( $_REQUEST ["rotate"] );
    }
    
    /**
     * Flip horizontal or flip vertical
     */
    if (isset ( $_REQUEST ["flip_vertical"] ) && $_REQUEST ["flip_vertical"] == 1) {
      $this->img->imageflip ( IMG_FLIP_VERTICAL );
    }
    
    if (isset ( $_REQUEST ["flip_horizontal"] ) && $_REQUEST ["flip_horizontal"] == 1) {
      $this->img->imageflip ( IMG_FLIP_HORIZONTAL );
    }
    
    if (isset ( $_REQUEST ['x1'] ) && isset ( $_REQUEST ['x2'] ) && isset ( $_REQUEST ['y1'] ) && isset ( $_REQUEST ['y2'] )) {
      $x1 = $_REQUEST ['x1'];
      $y1 = $_REQUEST ['y1'];
      $x2 = $_REQUEST ['x2'];
      $y2 = $_REQUEST ['y2'];
      $coord = true;
    } else {
      $coord = false;
    }
    /**
     * Crop
     */
    if ($coord && isset ( $_REQUEST ["crop"] )) {
      $this->img->crop ( $x1, $y1, $x2, $y2 );
    }
    /**
     * Resize
     */
    if (isset ( $_REQUEST ["resize"] )) {
      $wp = isset ( $_REQUEST ["wp"] ) ? $_REQUEST ["wp"] : 0;
      $hp = isset ( $_REQUEST ["hp"] ) ? $_REQUEST ["hp"] : 0;
      $this->img->resize ( $wp, $hp );
    }
    
    /**
     * Contrast
     */
    if (isset ( $_REQUEST ["contrast"] )) {
      $this->img->contrast ( $_REQUEST ["contrast"] );
    }
    /**
     * Brightness
     */
    if (isset ( $_REQUEST ["brightness"] )) {
      $this->img->brightness ( $_REQUEST ["brightness"] );
    }
    /**
     * Colorize
     */
    if (isset ( $_REQUEST ["rgb"] )) {
      $r = ( float ) ($_REQUEST ["red"]);
      $g = ( float ) ($_REQUEST ["green"]);
      $b = ( float ) ($_REQUEST ["blue"]);
      $this->img->rgb ( $r, $g, $b );
    }
    
    /**
     * Gray scale
     */
    if (isset ( $_REQUEST ["grayscale"] )) {
      $this->img->grayscale ();
    }
    
    /**
     * Gamma
     */
    if (isset ( $_REQUEST ['gamma'] )) {
      $gammain = isset ( $_REQUEST ["gammain"] ) ? $_REQUEST ["gammain"] : 0;
      $gammaout = isset ( $_REQUEST ["gammaout"] ) ? $_REQUEST ["gammaout"] : 0;
      $this->img->gamma ( $gammain, $gammaout );
    }
    /**
     * DeNoise
     */
    if (isset ( $_REQUEST ["sharp"] )) {
      
      $sharpw = (isset ( $_REQUEST ["sharpw"] )) ? $_REQUEST ["sharpw"] : 3;
      $sharph = (isset ( $_REQUEST ["sharph"] )) ? $_REQUEST ["sharph"] : 3;
      $this->img->sharp ( $sharpw, $sharph );
    }
    /**
     * Smooth => Denoise???
     */
    if (isset ( $_REQUEST ["smooth"] )) {
      $smoothlevel = (isset ( $_REQUEST ["smoothlevel"] )) ? $_REQUEST ["smoothlevel"] : 3;
      $this->img->smooth ( $smoothlevel );
    }
    
    if (isset ( $_REQUEST ["emboss"] )) {
      $this->img->emboss ();
    }
    
    if (isset ( $_REQUEST ["gaussianblur"] )) {
      $this->img->gaussianblur ();
    }
    if (isset ( $_REQUEST ["convolution"] )) {
      $this->img->convolution ();
    }
    
    $this->img->save();
    $this->ts['tempname'] = $newname;
    return $this->makejson();
  }
  
  /**
   * Save the modified image
   *
   * @param unknown $id          
   * @return string
   */
  public function save() {
    global $base_url;
    $json = $this->makejson ();
    
    $src = $this->temppath . "/" . $this->ts ['tempname'];
    $dst = realpath ( $this->root . $this->ts['path'] . $this->ts['name'] );
    copy ( $src, $dst );
    
    // Makes new thumbnail after save the file
    $source = $dst;
    $size   = GetImageSize ( $source );
    $dx     = $size [0];
    $dy     = $size [1];
    $width  = (int) ($this->ts ["width"]);
    $height = $width * $dy / $dx;
    // Target image
    $dst_im = @ImageCreateTrueColor ( $width, $height );
    $thumbnail = $this->root . $this->ts ["path"] . TN . $this->ts ["name"];
    $type      = strtolower ( pathinfo ( $this->ts ["name"], PATHINFO_EXTENSION ) );
    $ok = false;
    switch ($type) {
      case 'jpg' :
      case 'jpeg' :
        $src_im = imagecreatefromjpeg ( $source );
        @imagecopyresized ( $dst_im, $src_im, 0, 0, 0, 0, $width, $height, $dx, $dy );
        $ok = @imagejpeg ( $dst_im, $thumbnail, 70 );
        break;
      case 'png' :
        $src_im = @imagecreatefrompng ( $source );
        @imagecopyresized ( $dst_im, $src_im, 0, 0, 0, 0, $width, $height, $dx, $dy );
        $ok = @imagepng ( $dst_im, $thumbnail, 9 );
        break;
      case 'wbmp' :
        $src_im = @imagecreatefromwbmp ( $source );
        @imagecopyresized ( $dst_im, $src_im, 0, 0, 0, 0, $width, $$height, $dx, $dy );
        $ok = @imagewbmp ( $dst_im, $thumbnail );
        break;
      case 'gif' :
        $src_im = @imagecreatefromgif ( $source );
        @imagecopyresized ( $dst_im, $src_im, 0, 0, 0, 0, $width, $height, $dx, $dy );
        $ok = @imagegif ( $dst_im, $thumbnail );
        break;
      case 'bmp' :
        $src_im = @imagecreatefrombmp ( $source );
        @imagecopyresized ( $dst_im, $src_im, 0, 0, 0, 0, $width, $height, $dx, $dy );
        $ok = @imagebmp ( $dst_im, $thumbnail );
        break;
      default :
        $ok = false;
    }
    $json ["ok"] = $ok ? "1" : "-1";
    
    $mt = "&rnd=" . microtime ();
    if (file_exists ( $this->root . $this->ts ["path"] . TN . $this->ts ["name"] )) {
      $out = $base_url . "/" . $this->mp . "/v.php?i=" . $this->ts ["path"] . TN . $this->ts ["name"] . $mt . "&tn=1";
    } else {
      $out = $base_url . "/" . $this->mp . "/v.php?i=" . $this->ts ["path"] . $this->ts ["name"] . $mt;
    }
    $json ["link"] = $out;
    return $json;
  }
  
  /**
   *
   * @return number
   */
  public function undo() {
    $idx = $this->ts['idx'];
    if ($idx > 0) {
      $idx --;
      $this->ts["idx"] = $idx;
      $this->ts["tempname"] = $this->ts["name"] . "_temp_" . $idx;
    }
    return $this->makejson ();
  }
  
  /**
   *
   * @return string
   */
  public function redo() {
    $idx = $this->ts["idx"];
    $que = $this->ts["que"];
    if ($idx < $que) {
      $idx ++;
      $this->ts["idx"] = $idx;
      $this->ts["tempname"] = $this->ts['name'] . "_temp_" . $idx;
    }
    return $this->makejson ();
  }
  
  /**
   * Cancel the edited image from hdd
   * 
   * @return string
   */
  public function close() {
    array_map ( 'unlink', glob ( $this->ts ["temppath"] .'/'. '*_temp_*' ) );
    $this->unset ();
    return array (
        "ok" => 'closed' 
    );
  }
  function makejson($i = '', $e = '') {
    $json = array ();
    if (empty ( $i )) {
      $size = GetImagesize ( $this->ts ["temppath"] . '/' .$this->ts ["tempname"] );
      
      $json["dx"]       = $size[0];
      $json["dy"]       = $size[1];      
      $json['id']       = $this->ts['id'];
      $json["idx"]      = $this->ts["idx"];
      $json["name"]     = $this->ts["name"];
      $json["que"]      = $this->ts["que"];
      $json["tempname"] = $this->ts["tempname"];      
      $json["url"]      = $this->ts['tempurl'];
      $json["width"]    = $this->ts["width"];
    } else {
      $json[$i] = $e;
    }
    $this->sess->set ( "smpl", $this->ts );
    return $json;
  }
  
  /**
   * * Unset data of imgedit functions
   */
  function unset() {
    unset ( 
        $this->ts['tempname'], 
        $this->ts['path'], 
        $this->ts['name'], 
        $this->ts['que'], 
        $this->ts['idx'], 
        $this->ts['width'] );
  }
}
?>