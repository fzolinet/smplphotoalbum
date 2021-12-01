<?php
/**
 * Edit an image
 * @param string $id - id of image
 * @param string $form -
 */

function smplphotoalbum_imgedit($id='', $par=''){
  global $base_url;
  if(!smplphotoalbum_access() || empty( $id )){
    die('-1');
  }
  $t = variable_get("smpl_temp");
  $t = (substr($t,0,1) =="/"?"":"/").$t;
  $url      = $base_url . $t;
  $smpltemp = DRUPAL_ROOT . $t;
  $smpltemp = str_replace("\\","/",$smpltemp);
  
  $out ='';
 
  // Empty form
  if(!empty($par)){
    switch($par){
      case "form" :
        $out = _smpl_imgedit_form_load($id, $url, $smpltemp);
        break;
      case "save" :
        $out =_smpl_imgedit_save($id, $url, $smpltemp);  // overwrite the original image with the changed image
        break;
      case "edit" :
        $out = _smpl_imgedit_edit($id, $url, $smpltemp);  //return the link of edited image
        break;
      case "undo" :
        $out =_smpl_imgedit_undo($id, $url, $smpltemp);
        break;
        case "redo" :
          $out =_smpl_imgedit_redo($id, $url, $smpltemp);
          break;
        case "thumb" :
          $out =_smpl_imgedit_thumb($id, $url, $smpltemp);
          break;
      case "cancel" :
        $out = _smpl_imgedit_cancel($id, $url, $smpltemp); //return the link of original image
    }
    die($out);
  }
  return $out;
}
/**
 * Save the modified image
 *
 * @param unknown $id
 * @return string
 */
function _smpl_imgedit_save($id, $url, $smpltemp){
  global $base_url;
  
  $root     = smpl_root();
  $mp       = drupal_get_path('module','smplphotoalbum');
  $path     = $_SESSION['smpl_path'];
  $tempname = $_SESSION['smpl_tempname'];
  $name     = $_SESSION['smpl_name'];
  
  $src = $smpltemp.$tempname;
  $dst = realpath($root.$path.$name);
 
  $ok  = copy($src, $dst);
  
  //Makes new thumbnail after save the file
  _smpl_new_thumbnail($root,$path,$name);
  $mt = "&rnd=".microtime();
  if(file_exists($root . $path . TN . $name)){
    $out = $base_url."/".$mp."/v.php?i=".$path . TN . $name . $mt."&tn=1";
  }else{
    $out = $base_url."/".$mp."/v.php?i=".$path . $name . $mt;
  }
  smpl_unset();
  return $out;
}

function _smpl_imgedit_thumb($id){
  global $base_url;
  $out = "";
   //Data of image
  $record = db_select('smplphotoalbum','s')
  ->fields('s', array('path','name'))
  ->condition('id',$id,'=')
  ->execute()
  ->fetchAssoc();
  
  $root     = smpl_root();
  $mp       = drupal_get_path('module','smplphotoalbum');
  $path = $record['path'];
  $name = $record["name"];
  _smpl_new_thumbnail($root,$path, $name);
  
  $mt = "&rnd=".microtime();
  if(file_exists($root . $path . TN . $name)){
    $out = $base_url."/".$mp."/v.php?i=".$path . TN . $name . $mt."&tn=1";
  }else{
    $out = $base_url."/".$mp."/v.php?i=".$path . $name . $mt;
  }
  return $out;
}
/**
 * Make Thumbnail
 * @param string $root
 * @param string $path
 * @param string $entry
 */
function _smpl_new_thumbnail($root,$path,$entry){
  //size of original big picture
  $rpath = realpath($root.$path.$entry);
  $size = GetImageSize($rpath);
  $dx   = $size[0];
  $dy   = $size[1];
  
  $width = $_SESSION['smpl_w'];
  $type = strtolower(pathinfo($entry,PATHINFO_EXTENSION));

  if($dx > $width ) {
    //Target image
    $dst_im = @ImageCreateTrueColor( $width, $width * $dy / $dx);
    $source = $rpath;
    $target = realpath($root.$path.TN.$entry);
  
    switch($type){
      case 'jpg':
      case 'jpeg':
        $im = ImageCreateFromJPEG ($source );
        $a  = Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $width, $width*$dy/$dx, $dx, $dy);
        $ok = @Imagejpeg ($dst_im, $target,80);
        break;
      case 'png':
        $im = @ImageCreateFromPNG ($source );
        $a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $width, $width*$dy/$dx, $dx, $dy);
        $ok = @Imagepng ($dst_im, $target,9);
        break;
      case 'wbmp':
        $im = @ImageCreateFromwbmp ($source );
        $a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $width, $width*$dy/$dx, $dx, $dy);
        $ok = @Imagewbmp($dst_im, $target);
        break;
      case 'gif':
        $im = @ImageCreateFromGif ($source );
        $a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $width, $width*$dy/$dx, $dx, $dy);
        $ok = @imagegif($dst_im, $target);
        break;
      default:
        $ok = true;
    }
  }
}
/**
 *
 * @param int $id          - Image id
 * @param string $url      - img url
 * @param string $smpltemp - img filepath
 * @return number
 */
function _smpl_imgedit_undo($id, $url, $smpltemp){
  $tempname = $_SESSION['smpl_tempname'];
  fz_die();
  $img      = new ImgManipulate($smpltemp,$tempname);
  
  $img->undo();

  $size     = Getimagesize($smpltemp.$tempname);
  
  $str      = $url.$tempname."|";
  $str     .= $size[0] ."|";
  $str     .= $size[1] ."|";
  $str     .= $_SESSION["smpl_idx"]."|";
  $str     .= $_SESSION["smpl_que"];
  return $str;
}

function _smpl_imgedit_redo($id, $url, $smpltemp){
  $tempname = $_SESSION['smpl_tempname'];
  fz_die();
  $img      = new ImgManipulate($smpltemp,$tempname);
  $img->redo();

  $size     = Getimagesize($smpltemp.$tempname);
  $str      = $url.$tempname."|";
  $str     .= $size[0] ."|";
  $str     .= $size[1] ."|";
  $str     .= $_SESSION["smpl_idx"]."|";
  $str     .= $_SESSION["smpl_que"];
  return $str;
}

function _smpl_imgedit_edit($id, $url, $smpltemp){
  $tempname = $_SESSION['smpl_tempname'];
  
  $img      = new ImgManipulate($smpltemp,$tempname);
  /**
   * rotate
   */
  
  if((isset($_GET["rotate"]) && is_numeric($_GET["rotate"]) ) ){
    $img->rotate();
  }
  
  /**
   * Flip horizontal or flip vertical
   */
  if( isset($_GET["flip_vertical"]) && $_GET["flip_vertical"] == 1){
    $img->imageflip(IMG_FLIP_VERTICAL);
  }
  
  if( isset($_GET["flip_horizontal"]) && $_GET["flip_horizontal"] == 1){
    $img->imageflip(IMG_FLIP_HORIZONTAL);
  }
  
  /**
   * Crop
   */
  if(isset($_GET["crop"])){
    $img->crop();
  }
  /**
   * Resize
   */
  if(isset($_GET["resize"])){
    $img->resize();
  }
  
  /**
   * Contrast
   */
  if(isset($_GET["contrast"])){
    $img->contrast();
  }
  /**
   * Brightness
   */
  if(isset($_GET["brightness"])){
    $img->brightness();
  }
  /**
   * Colorize
   */
  if(isset($_GET["rgb"])){
    $img->rgb();
  }
  
  /**
   * Gamma
   */
  if(isset($_GET['gamma'])){
    $img->gamma();
  }
  /**
   * DeNoise
   */
  if(isset($_GET["denoise"])){
    $img->denoise();
  }
  
  $img->backup();
  
  $img->save();
  $str  = $url.$tempname."|";
  $str .= $img->getWidth() ."|";
  $str .= $img->getHeight()."|";
  $str .= $_SESSION['smpl_idx']."|";                    //actual index of undo
  $str .= $_SESSION['smpl_que']; //size of undo storage
  return $str;
}

/**
 * delete the temporary file and return the original src of image
 * @param unknown $id
 */
function _smpl_imgedit_cancel($id, $url, $smpltemp){
  if(!isset($_SESSION['smpl_tempname'])) return "-1";
  array_map('unlink', glob($smpltemp.'_tm*') );
  smpl_unset();
  return "1";
}

function smpl_unset(){
  unset($_SESSION['smpl_tempname']);
  unset($_SESSION['smpl_path']);
  unset($_SESSION['smpl_name']);
  unset($_SESSION["smpl_que"]);
  unset($_SESSION["smpl_idx"]);
}
/**
 * Loads the IMG Edit Layer
 * @param string $id
 */
function _smpl_imgedit_form_load($id='', $url, $smpltemp){
  global $base_url;
  //
  //Data of image
  $record = db_select('smplphotoalbum','s')
  ->fields('s', array('path','name'))
  ->condition('id',$id,'=')
  ->execute()
  ->fetchAssoc();
  
  $root     = smpl_root();
  $_SESSION["smpl_path"]     = $record['path'];
  $_SESSION["smpl_tempname"] = _smpl_imgedit_make_temp($id, $root, $record["path"] , $record["name"], $smpltemp);
  $_SESSION["smpl_name"]     = $record["name"];
  $_SESSION["smpl_que"]      = 0;
  $_SESSION["smpl_idx"]      = 0;
  
  $size = GetImageSize($smpltemp.$_SESSION["smpl_tempname"] );
  $dx   = $size[0];
  $dy   = $size[1];
  $str  = $id."|";              // image id
  $str .= $record["name"]."|";  // image name
  $str .= $url. $_SESSION["smpl_tempname"]."|"; // temporary name for editing
  $str .= $dx."|";                              // width
  $str .= $dy ."|";                             // height
  $str .= $_SESSION["smpl_idx"] ."|";           // queue index
  $str .= $_SESSION["smpl_que"];                // max queue length
  return $str;
}
/**
 * Make temp file and the editing will be on it.
 * @param int  $id - The place of original picture
 * @param string $root - The root of pictures
 * @param string $path - The path of original picture
 * @param string $name - The name of original picture
 * @return string $out  - the path of temp file
 *
 */
function _smpl_imgedit_make_temp($id, $root, $path, $name, $smpltemp){
  $source   = $root.$path.$name;
  $ext      = strtolower(pathinfo($name,PATHINFO_EXTENSION));
  array_map('unlink', glob($smpltemp.'_tm*.tmp.*') );
  $temp0    = drupal_tempnam($smpltemp,'_tm');
  $tempname = $temp0.".".$ext;
  unlink($temp0);
  $source = realpath($source);  //The link ot good. It needs the ralpath for copy!!!
  $ok = copy($source, $tempname);
  return basename( $tempname);
}

function _smpl_imgedit_destroy(){
  
}
