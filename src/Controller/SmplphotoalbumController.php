<?php
/**
 * @file
 * Contains \Drupal\mymodule\Controller\MyModuleController.
 */
namespace Drupal\smplphotoalbum\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Database\InvalidQueryException;
use Drupal\Core\Extension\InfoParser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\smplphotoalbum\Controller\ImageEdit;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;

use Clarifai\ClarifaiClient;

use Clarifai\Api\Data;
use Clarifai\Api\Image;
use Clarifai\Api\Input;
use Clarifai\Api\PostModelOutputsRequest;
use Clarifai\Api\Status\StatusCode;
use Drupal\smplphotoalbum\SlideShow;

require_once realpath(__DIR__."/../../")."/vendor/autoload.php";

class SmplphotoalbumController extends ControllerBase{
  private $cfg;
  private $mp;
  private $root;
  private $public;
  private $TN;
  private $sess;
  private $wm;
  
  /**
   * Class constructor.
   */
  public function __construct() {
    $this->cfg    = \Drupal::config( 'smplphotoalbum.settings' );
    $this->mp     = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->public = \Drupal::service( 'file_system' )->realpath( "public://" );
    $this->sess   = \Drupal::request()->getSession();
    $this->wm     = $this->sess->get("wm", false);    
    $root = $this->cfg->get( 'root' );
    $root = str_replace( "public://", $this->public . "/", $root );
    $root .= substr( $root, - 1 ) != '/' ? "/" : '';

    $this->root = $this->slash( $root );
    $this->TN = $this->cfg->get( 'TN' );
  }
  
  // ...
  // AJAX Callback to read a message.
  public function help() {
    $output = '<h3>' . $this->t( 'About' ) . '</h3>';
    $path = $this->mp . '/smplphotoalbum.info.yml';
    $InfoParsed = new InfoParser( \Drupal::root() );
    $info = $InfoParsed->parse( $path );
    $output .= "<h4>Simple Photoalbum</h4>";
    $output .= 'Author: <a href="http://www.fzolee.hu">Zoltan Fabian</a><br/>';
    $output .= "Drupal ".$this->t("version") .": ".\Drupal::VERSION."<br/>";
    $output .= $this->t("Module")." ".$this->t( 'version' ) . ': ' . $info['version'] . '<br/>';
    $output .= $this->t( 'compiled' ) . ': ' . date( 'Y.m.d', $info['datestamp'] );
    $output .= "<p>" . $this->t( "Simple Photoalbum shows a set of picture and other filetypes ." ) . "</p>";
    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if($lang == "hu") {
      $output .= file_get_contents( $this->mp . "/Olvassel.html" );
    } else {
      $output .= file_get_contents( $this->mp . "/Readme.html" );
    }
    $selector = "div#smpl_help_content";
    $content = $output;
    $settings = [];
    $response = new AjaxResponse();
    $response->addCommand( new InsertCommand( $selector, $content, $settings ) );
    return $response;
  }

  /**
   * Delete an item from server
   * 
   * @param  int $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function delete($id = -1) {
    $id = ( int ) $id;
    $ok = false;
    $response = new AjaxResponse();
    if(! $this->access() || $id == - 1) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }
    //
    $con = \Drupal::database();
    $qry = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'path',
        'name',
        'typ',
        'importance'
    ] )->condition( 's.id', $id, '=' );
    
    $rs = $qry->execute ();    
    
    $db = 0;
    $a=[];
    foreach ( $rs as $record ) {
      $a['id']   = $record->id;
      $a['name'] = $record->name;
      $a['path'] = $record->path;
      $a['typ']  = $record->typ;
      $a['importance']  = $record->importance;
      $db++;
    }

    if($db == 1) {
      $path = $this->DblSlashToSmpl($a["path"]);
      //
      $tn = $this->root . $path . $this->TN . $a["name"];
      if( $a['typ'] !="image" ){
        $tn .= ".png";
      }

      $ok = true;
      if( file_exists($tn) ){
        $ok = unlink( $tn );
      }
      //
      if($ok) {
        $img = $this->root . $path . $a["name"];        
        if( file_exists( $img ) ) 
          $ok = unlink( $img );
        else           
          $ok = false;
        
        // Delete from database
        if($ok) {
          $db = $con->delete( "smplphotoalbum" )->condition( "id", $id, "=" )->execute();
          $ok = ($db == 1);
        }
      }
    }
    $content = ($ok) ? $id : '-1';
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }

  /**
   * Exif information of image
   * @param string $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function exif($id = 1) {
    $response = new AjaxResponse();
    /*if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "", [] ) );
      return $response;
    }*/

    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'path',
        'name',
        'typ', 
        'link',
        'importance'       
    ] )->condition( 's.id', $id, '=' )->execute();
    $a = $record->fetchAssoc();

    //
    $ex = new Exif( $a, $this->cfg );
    $exif = (string) $ex->Info();
        //
    $str = file_get_contents( $this->mp . "/templates/exif.html.twig" );
    $str = str_replace( "{{ exif }}", $exif, $str );
    $response->addCommand( new InsertCommand( '', $str, [] ) );
    return $response;
  }

  /**
   * Load data of an item
   *
   * @param string $id
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   */
  public function edit($id = -1) {
    $id = ( int ) $id;
    $response = new AjaxResponse();

    if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }

    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'name',
        'subtitle',
        'typ',        
        'link',
        'importance',
    ] )->condition( 's.id', $id, '=' )->execute();

    $a = $record->fetchAssoc();
    $content = json_encode( $a );
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }

  /**
   * modify the properties of an item
   *
   * @param string $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function update( $id = -1) {
    $response = new AjaxResponse();
    if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }

    $json = json_decode( $this->Request("json") );

    $con = \Drupal::database();
    $qry = $con -> select( 'smplphotoalbum', 's' )
                -> fields( 's', [ 'id', 'path', 'name', 'typ', 'subtitle', 'link', 'importance' ] )
                -> condition( 's.id', $id, '=' );
    
    $record = $qry->execute();
    $a = $record->fetchAssoc();
    $f = [];
    $ok = false;

    // rename the file
    $path = $this->DblSlashToSmpl($a["path"]);
    
    // Only one item
    if( isset( $json->name ) && !empty($json->name) && $a['name'] != $json->name ) {
      
      // Rename thumbnail
      $fromtn = $this->root . $path . $this->TN . $a["name"];
      $totn   = $this->root . $path . $this->TN . $json->name;

      $fromtn = $this->slash( $fromtn );
      $totn   = $this->slash( $totn );

      if( $a['typ'] != "image" ){
        $fromtn .= ".png";
        $totn   .= ".png";
      }

      $ok = true;
      if( file_exists($fromtn) ){
        $ok = rename( $fromtn, $totn );
      }

      // rename image
      if( $ok ) {
        $fromimg = $this->root . $path . $a["name"];
        $toimg   = $this->root . $path . $json->name;
        $fromimg = $this->slash( $fromimg );
        $toimg   = $this->slash( $toimg );
        if( file_exists( $fromimg ) ) {
          $ok = rename( $fromimg, $toimg );
        } else {
          $ok = false;
        }
      }
      if($ok){
        $f["name"] = $json->name;
      }
    }
    
    if( isset( $json->subtitle ) && !empty( $json->subtitle ) ) {
      $f['subtitle'] = $this->cleantext($json->subtitle);
    }
      
    // change link
    if( isset( $json->link ) && !empty( $json->link ) && $a['link'] != $json->link ){
      $f['link'] =  $json->link;
    }
    // change type
    if( isset( $json->type ) && !empty( $json->type ) && $a['typ'] != $json->type ){
      $f['typ'] =  $json->type;
    }

    // change importance
    if( isset( $json->importance ) && $a['importance'] != $json->importance ){
      $f['importance'] =  $json->importance;
    }
    // Change subtitle
    $db = 0;
    if(count($f) >0 ){
      $db = $con->update( "smplphotoalbum" )
            ->fields( $f )
            ->condition( "id", $id, "=" )->execute();
  
      \Drupal::messenger()->addStatus("ID: $id: '".$a['name']."'");
      foreach($f AS $i => $e){
        \Drupal::messenger()->addStatus("=> '$i' : updated in the database");
      }   
    }else{
      \Drupal::messenger()->addStatus( $this->t("There was nothing changed in the database") ); 
    }  

    \Drupal::messenger()->addStatus($msg);

    $f['db'] = $db;
    $content = json_encode( $f );      
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }

  /**
   * Edit the properties of this path
   *
   * @param string $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function updatepath($id = -1) {
    $id = ( int ) $id;
    $response = new AjaxResponse();
    if(! $this->access() || $id == - 1) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }

    $con = \Drupal::database();
    $record = $con-> select( 'smplphotoalbum', 's' )
                  -> fields( 's', [ 'path' ] )
                  -> condition( 's.id', $id, '=' )
                  -> execute();                  
    $path = $record->FetchAssoc()["path"];
    
    if(empty( $path )) {
      $response->addCommand( new InsertCommand( '', "There is not path to Invalid ID: " . $id, [] ) );
      return $response;
    }

    $qry = $con -> select( 'smplphotoalbum', 's' )
                -> fields( 's', [ 'id', 'subtitle', 'path' ] )
                -> condition( 'path', $path, "=" );

    $recs = $qry->execute();
    $a = $recs->FetchAll();
    
    $chg = array();
    foreach( $a as $i => $e ) {
      $id = $e->id;
      $bkp = $e->subtitle;
      $e->subtitle = $this->cleantext( $e->subtitle );
      $chg[$i] = ($bkp == $e->subtitle) ? true : false;
    }

    $sum = 0;
    foreach( $a as $i => $e ) {
      if(! $chg[$i]) {
        $db = $con->update( "smplphotoalbum" )
                  ->fields( ["subtitle" => $e->subtitle ] )
                  ->condition( "id", $e->id, "=" )
                  ->execute();
        $sum += $db;
      }
    }
    \Drupal::messenger()->addMessage( $this->t( $sum . ' of records updated on the path "' . $path . '" ' ), 'status' );

    $content = json_encode(  [ 'db' => $sum, 'path' => $path ] );
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }

  /**
   * Clean subtitle txt of item
   * @param string $s
   * @return string
   */
  function cleantext( $s = '' ) {
    $a = $this->smpl_extensions();
    foreach($a AS $i =>$e){
        $a[$i] = ".".$e;
    }
    $s = str_ireplace( $a, '', $s );
    $s = trim( str_replace( ["-","_",'.'], [" "," ",""], $s ) );
    return $s;
  }

  /**
   * IMGEdit controller function
   *
   * @param int $id     - id of item
   * @param string $cmd - Command
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function imgedit($id = -1, $cmd = 'load', $oldname="", $newname="") {
    $response = new AjaxResponse();

    if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }
    
    if($id != 0) {
      $img = new ImageEdit( $id );
      switch($cmd){
        case 'load'  : $json = $img->load(); break;
        case 'save'  : $json = $img->save(); break;
        case 'saveas': $json = $img->saveas( $oldname, $newname); break;
        case 'edit'  : $json = $img->edit(); break;
        case 'undo'  : $json = $img->undo(); break;
        case 'redo'  : $json = $img->redo(); break;
        case 'cancel': $json = $img->cancel(); break;
        case 'close' :
        default      : $json = $img->close();
      }
    } else {
      $json = ["cmd", $cmd ];
    }
    $response->addCommand( new InsertCommand( '', json_encode( $json ), [] ) );
    return $response;
  }

  /**
   * send an image
   *
   * @param int $id
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function slide( $id = -1)  {
    if($id == - 1) {
      return new BinaryFileResponse( $this->mp . "/image/404.png" );
    }
    $tn = strpos($id, "&tn=");
    $id = (int) (str_replace("&tn=1","",$id));
    $pics = $this->Pics();

    if( $_SESSION["slide"]["i"] >-1 ) {

      $i = 0;
      while($i < count($pics) && $id != $pics[$i]["id"]){
        $i++;
      }
      if( !( $i < count($pics) ) ){
        $i = 0;
      }
      $record["i"]        = $i;
      $record["path"]     = $_SESSION["slide"]['path'];
      $record["name"]     = $_SESSION["slide"][$i]['name'];
      $record["subtitle"] = $_SESSION["slide"][$i]['subtitle'];

    }else{

      $con = \Drupal::database();
      $rs = $con->select( "smplphotoalbum", "s" )->fields( "s", array(
          'path',
          'name',
          'subtitle'
      ) )->condition( 'id', $id, '=' )->execute();
      $record = $rs->fetchAssoc(); 
      
      $i = 0;
      while($i < count($pics) && $id != $pics[$i]["id"]){
        $i++;
      }

      if( !($i < count($pics) ) ) {
        $i = 0;
      }

      $_SESSION["slide"] ['id'] = $i;
      $_SESSION["slide"] ["id"] = $id;
      $_SESSION["slide"] [$i] ['path'] = $record["path"];
      $_SESSION["slide"] [$i] ['name'] = $record["name"];
      $_SESSION["slide"] [$i] ['subtitle'] = $record["subtitle"];      
    }

    if( empty( $record ) ) {
      return new BinaryFileResponse( $this->mp . "/image/404.png" );
    }
      
    if($tn) {
      $p = $this->root . $record['path'] . $this->TN . $record['name'];
    } else {
      $p = $this->root . $record['path'] . $record['name'];
    }

    $p = str_replace( '//', '/', $p );

    // Watermark if you want depends of type of file    
    $isimage = $this->isimage($p);    
    if($isimage && $this->wm ) {
      $p = $this->watermarkonfly( $id, $p );
    }
    
    $_SESSION["slide"]["actual"] = $id;  // Actual image
    
    $response = new BinaryFileResponse( $p );
    if(! $isimage ) {
      $response->headers->set( 'Pragma', 'no-cache' );
      $response->headers->set( 'Content-Disposition', 'attachment; filename="' . basename( $p ) . '"' );
    }
    return $response;
  }

  /**
   * paging of slide
   *
   * @param string $cmd
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function slideget($cmd = "next") {
    $response = new AjaxResponse();
  
    // there is no 'slide' session
    if( !isset( $_SESSION['slide'] )) {
      $json["error"] = "There is no 'slide' session. Maybe clear the cache!";
      $response->addCommand( new InsertCommand( '', json_encode( $json ), [] ) );
      return $response;
    }

    $Slide = new SlideShow();
    $json = $Slide->SlideGet($cmd);
    $content = json_encode( $json );    
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }

  /**
   * Command
   * @param mixed $p - filename of image
   * @param mixed $i - number in the row, not id!!!!
   * @return array 
   */
  function slidetnget($p, $i = 0) {
    $a = [];
    $k = 0;
    $db = count( $p );
    if($db % 2 == 0){
      $begin = (int) ($db / 2) - 1;
      $end = (int) ($db / 2) + 1;
    }else{
      $begin = (int) ($db / 2);
      $end = (int) ($db / 2) + 1;
    }
    for($j = $begin; $j < $end; $j ++) {
      $idx = ($db + $i + $j) % $db;
      $a[ $k ]['id']    = $p[$idx]["id"];
      $a[ $k ]['tnid']  = $i + $j;
      $a[ $k ]['title'] = $p[$idx]["subtitle"];
      $a[ $k ]['alt']   = $p[$idx]["subtitle"];
      $k ++;
    }
    return $a;
  }
  /**
   * Shows an image
   *
   * @param string $id
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function v($id = -1) {
    
    // search path
    $name = $this->Request("n","");
    $path = $this->Request("p","");
    $tn   = $this->Request("tn","");
    
    if(empty( $path ) || empty( $name )) {
      return new BinaryFileResponse( $this->mp . "/image/404.png" );
    }

    if(!empty( $tn )) {
      $p = $this->tn( $path, $name );
    } else {
      $con = \Drupal::database();
      $rs = $con->select( "smplphotoalbum", "s" )
                ->fields( "s", ['path','name'] )
                ->condition( 'id', $id, '=' )
                ->execute();
      $record = $rs->fetchAssoc();

      if(empty( $record )) {
        return new BinaryFileResponse( $this->mp . "/image/404.png" );
      }
      $p = $this->root . $path . $name;
      
      $con->update('smplphotoalbum')
          ->expression( "viewnumber", "viewnumber + 1" )
          ->fields( [ "viewnumber" => 0 ] )
          ->condition( "id", $id, "=" )
          ->execute();

      // Watermark if you want depends of type of file
      if($this->isimage( $p ) && $this->sess->get("wm", false)) {
        $p = $this->watermarkonfly( $id, $p );
      }
    }

    $response = new BinaryFileResponse( $p );
    if(! $this->isimage( $p )) {
      header("Content-type: " . mime_content_type($p));
      $response->headers->set( 'Pragma', 'no-cache' );
      $response->headers->set( 'Content-Disposition', 'attachment; filename="' . basename( $p ) . '"' );
    }
    return $response;
  }
  
  /**
   * Watermark on fly
   *
   * @param number $id
   * @param string $p
   * @return string
   */
  function watermarkonfly($id = 1, $p ="") {
    $sizew = GetImagesize( $p );
    $dx = $sizew[0];
    $dy = $sizew[1];

    $img = $this->ImageCreateFrom( $p );

    // load watermark file
    if(empty( $wmpath )){
      $wmpath = $this->sess->get("wmpath", false);
      if( !$wmpath ){
        $wmpath = $this->cfg->get( 'wmpath' );
      }
    }

    if(strpos( " " . $wmpath, "smplphotoalbum://" ) > 0) {
      
      $wmpath = str_replace( 
        "smplphotoalbum://", 
        $this->slash( DRUPAL_ROOT . "/" . $this->mp . "/" ), 
        $wmpath 
      );

    } elseif(strpos( " " . $wmpath, "public://" ) > 0) {
      $public = $this->slash($this->public );
      $wmpath = str_replace( "public://", $public . "/", $wmpath );
    }

    $wmimg = $this->ImageCreateFrom( $wmpath );

    // size of watermark
    $wmdx  = imagesx( $wmimg );
    $wmdy  = imagesy( $wmimg );

    $d     = 0.3;
    $wmdx  = (int) ( $wmdx * $d );
    $wmdy  = (int) ( $wmdy * $d );
    $x1    = (int) ( ($dx - $wmdx) * $d );
    $y1    = (int) ( ($dy - $wmdy) * $d );

    // real size of original image
    $wmimg = imagescale( $wmimg, $wmdx, $wmdy, IMG_NEAREST_NEIGHBOUR );

    imagefilter( $wmimg, IMG_FILTER_GRAYSCALE );
    imagealphablending( $wmimg, false );
    imagesavealpha( $wmimg, true );

    $wmalpha = $_SESSION["wmalpha"];
    imagefilter( $wmimg, IMG_FILTER_BRIGHTNESS, ( int ) $wmalpha );
    imagecopymerge( $img, $wmimg, $x1, $y1, 0, 0, $wmdx, $wmdy, $wmalpha );
    imagedestroy( $wmimg );
    $this->OutputImage( $img, $p );
    return $p;
  }

  function OutputImage($img, $src) {
    $p = pathinfo( $src );
    switch(strtolower($p["extension"])){
      case "avif":
        header('Content-Type: image/avif');
        $ok = imageavif($img, null, -1,-1);
        break;
        
      case 'bmp' :
        header('Content-Type: image/bmp');
        $ok = @imagebmp( $img, null );
        break;

      case 'gif' :
        header( 'Content-Type: image/gif' );
        $ok = @imagegif( $img, null );
        break;
        
      case 'jpg' :
      case 'jpeg' :
        header( 'Content-Type: image/jpeg' );
        $ok = @imagejpeg( $img, null, 70 );
        break;
        
      case 'png' :
        header( 'Content-Type: image/png' );
        $ok = @imagepng( $img, null, 9 );
        break;
        
      case 'wbmp' :
        header( 'Content-Type: image/vnd.wap.wbmp' );
        $ok = @imagewbmp( $img, null );
        break;
        
      case 'xbm' :
        header('Content-Type: image/x-xbitmap');
        $ok = @imagexbm( $img, null );
        break;
        
      case 'xpm' :
        header('Content-Type: image/x-pixmap');
        $ok = @imagejpeg( $img, null,100 );
        break;
        
      case 'webp' :
        header('Content-Type: image/webp');
        $ok = @imagewebp( $img, null );
        break;
      default :
        $ok = False;
    }
    return $ok;
  }

  /**
   * Make a GD image from image on the disk
   *
   * @param string $src
   * @param string $ext
   * @return \GdImage|resource
   */
  function ImageCreateFrom($src) {
    $p = pathinfo( $src );
    switch(strtolower($p["extension"])){
      case "avif":
        $img = @imagecreatefromavif( $src );
        break;
        
      case "bmp":
        $img = @ImageCreateFromBmp( $src );
        break;
        
      case 'gif':
        $img = @ImageCreateFromGif( $src );
        break;
        
      case 'jpg':
      case 'jpeg':
        $img = ImageCreateFromJPEG( $src );
        break;
        
      case 'png':
        $img = @ImageCreateFromPNG( $src );
        break;
        
      case 'wbmp':
        $img = @ImageCreateFromwbmp( $src );
        break;
        
      case "webp":
        $img = @ImageCreateFromWebp( $src );
        break;
      
      case "xbm":
        $img = @ImageCreateFromXbm( $src );
        break;
        
      case "xpm":
        $img = @ImageCreateFromXpm( $src );
        break;
    }
    return $img;
  }

  /**
   * search the thumbnail image
   *
   * @param string $id
   * @return string
   */
  private function tn($path = "", $name = "") {
    $p = $this->root . $path . $this->TN . $name;
    $p = $this->slash( $p );
        
    if($this->isimage( $name )) {
      if(! file_exists( $p )) {
        $p = $this->root . $path . $name;
      }
    } else {
      if(! file_exists( $p )) {
        $p = $this->root . $path . $this->TN . $name . ".png";
      }
    }
    if(! file_exists( $p )) {
      return "-1";
    }
    return $p;
  }

  /**
   * Artifical Intelligence for image recognition
   * @param mixed $id 
   * @return AjaxResponse 
   * @throws ContainerNotInitializedException 
   * @throws ServiceCircularReferenceException 
   * @throws ServiceNotFoundException 
   * @throws InvalidQueryException 
   */
  function ai( $id ){
    global $base_url;
    $response = new AjaxResponse();
    
    // Error handling
    $curl = extension_loaded("curl");
    $grpc = extension_loaded("grpc");

    if(!($curl && $grpc)){
      $str = json_encode( ["id" => "-1", "msg" => "'cUrl or GRPC PHP extension is not installed!" ] );
      $response->addCommand( new InsertCommand( '', $str, [] ) );
      return $response;
    }

    $AI = $this->cfg->get( 'ai' );
    if(!$AI){
      $str = json_encode( ["id" => "-1", "msg" => "AI setting is disabled!" ] );     
      $response->addCommand( new InsertCommand( '', $str, [] ) );
      return $response;
    }  

    // Database calling
    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'path',
        'name',
        'subtitle',
        'typ',
        'link',
        'importance'
    ] )->condition( 's.id', $id, '=' )->execute();

    $a = $record->fetchAssoc();

    //no Image
    if($a['typ'] != "image"){
      $str = json_encode( ["id" => "-1", "msg" => "The type of file does not 'image'!" ] );     
      $response->addCommand( new InsertCommand( '', $str, [] ) );
      return $response;
    }

    $p = $this->slash( $this->root. $a["path"]."/".$a["name"] );
    $content = file_get_contents($p);      
    $str = $this->ai_recognition( $content );  
    
    if(!isset($str) || $str === null ){
      $res = ["id" => "-1", "msg" => "There is no answer from AI!" ];
    } else{
      $res = ["id" => "1", "msg" => $str ];
    }    
    
    $content = json_encode( $res );     
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }

  function ai_recognition( $bytes ){
    $curl = extension_loaded("curl");
    $grpc = extension_loaded("grpc");
    $msg = "";
    $msg .=  !$curl ? "Curl PHP extension not installed!" : "";
    $msg .=  !$grpc ? "GRPC PHP extension not installed! (https://pecl.php.net/package/gRPC )" : "";
    if(!empty($msg)){
        return $msg;
    }

    //$MODEL_ID = 'aaa03c23b3724a16a56b629203edc62c';
    $MODEL_ID = 'general-image-recognition';
    $MODEL_VERSION_ID = 'aa7f35c01e0642fda5cf400f543e7c40';
    $client = ClarifaiClient::grpc();
    
    $metadata = ['Authorization' => ['Key f80633969cc54ae9890398ea09901496']];

    [$response, $status] = $client->PostModelOutputs(
        new PostModelOutputsRequest([
            'model_id' => $MODEL_ID,  // This is the ID of the publicly available General model.
            'version_id' => $MODEL_VERSION_ID,
            'inputs' => [
                new Input([
                    'data' => new Data([
                        'image' => new Image( [ 'base64' => $bytes ] )

                        /*'image' => new Image([
                            //'https://www.fzolee.hu/fw2/smplphotoalbum/v/3168?p=/szamitogepek/&n=junosty.jpg'
                            'url' => $url
                        ])*/
                    ])
                ])
            ]
        ]),
        $metadata
    )->wait(); 
    
    $status      = $response->getStatus();
    $code        = $status->getCode();
    $description = $status->getDescription();
    $details     = $status->getDetails();
  
    if ($code != StatusCode::SUCCESS) {
        throw new \Exception("Failure response: " . $description . " " . $details);
    }
  
    $msg = " ";
    foreach ($response->getOutputs()[0]->getData()->getConcepts() as $concept) {
        $msg .= $concept->getName() . ": (" . number_format($concept->getValue(), 2) . "), ";
    }
    return $msg; 
  }

  /**
   * Load data of an item
   *
   * @param string $id
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   */
  public function statload($id = -1) {
    $response = new AjaxResponse();
    $a["id"] = "-1";
    if(! $this->access()) {
      $a["error"] = "There is not enough permission";
      $response->addCommand( new InsertCommand( '', json_encode( $a ), [] ) );
      return $response;
    }

    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'path',
        'name',
        'subtitle',
        'typ',
        'viewnumber',
        'link',
        'importance'
    ] )->condition( 's.id', $id, '=' )->execute();

    $a = $record->fetchAssoc();
    $content = json_encode( $a );
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }
  /**
   *
   * @param string $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function statupdate($id = -1) {
    $response = new AjaxResponse();
    if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }

    $json = json_decode( $this->Request( 'json' ) ) ;
    $json->subtitle = $this->smpl_ext_replace( $json->subtitle );
    $json->subtitle = str_replace( ["-","_"], " ", $json->subtitle );
    $json->subtitle = trim( $json->subtitle );

    $con = \Drupal::database();
    $db = $con->update( "smplphotoalbum" )
          ->fields( [      
              "subtitle" => $json->subtitle,
              "link" => $json->link,
              "viewnumber" => $json->viewnumber,
              "importance" => $json->importance
            ])
          ->condition( "id", $id, "=" )->execute();
    
    $content = json_encode( [
        "db"         => $db,
        "subtitle"   => $json->subtitle,
        "link"       => $json->link,
        "viewnumber" => $json->viewnumber,
        "importance" => $json->importance
    ]);
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }
  /**
   * Entry is an image?
   * @param string $entry
   * @return boolean
   */
  public function isimage( $entry ) {
    $exts = $this->cfg->get( 'image_extensions' );
    if( version_compare(PHP_VERSION , "8.1.0" >= 0 ) ){
      return stripos( " " . $exts, pathinfo ( $entry, PATHINFO_EXTENSION ) ) > 0;
    }
    $exts = str_ireplace("avif","",$exts);
    return stripos( " " . $exts, pathinfo ( $entry, PATHINFO_EXTENSION ) ) > 0;
  }
  /*
   * Access of current user 
   */
  private function access() {
    $roles = \Drupal::currentUser()->getroles();
    return in_array( 'administrator', $roles ) ? true : false;
  }

  /**
   * All of the extensions of smplphotoalbum
   *
   * @return array
   */
  private function smpl_extensions() {
    $a = [];
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'image_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'audio_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'audiohtml5_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'video_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'videohtml5_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'doc_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'cmp_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'app_extensions' ) ) );
    $a = array_merge( $a, explode( " ", $this->smpl_get( 'oth_extensions' ) ) );
    return array_unique( $a );
  }

  /**
   * read from the config a setting
   * @param array | string  $var - what we got
   * @param string $default
   * @return mixed[]|mixed - one value or array of values
   */
  function smpl_get($var = '' , $default = "") {
    // static $smpl = array();
    $config = \Drupal::config( 'smplphotoalbum.settings' );

    $val = "";
    if(is_array( $var )) {
      foreach( $var as $name ) {
        $val[$name] = $config->get( $name );
      }
    } else {
      $val = $config->get( $var );
      if($var == 'subtitle_change_text') {
        $var = $this->smpl_extensions_text();
      }
    }
    return $val;
  }

  /**
   * split the list of extensions
   * 
   * @return string
   */
  function smpl_extensions_text() {
    $b = $this->smpl_extensions();
    return implode( " ", $b );
  }

  function smpl_ext_replace($str, $r = "") {
    return str_ireplace( $this->smpl_extensions(), $r, $str );
  }
  
  function DblSlashToSmpl($p){
    if(substr( $p, 0, 1 ) == "/"){
      $p = substr($p,1);
    }
    $p .= (substr( $p, - 1 ) != "/") ? '/' : '';
    return $p;
  }

  /**
   * Request GET / POST Parameters from Browser
   * 
   * @param mixed $key
   * @param string $method
   * @return string
   */
  function Request($key, $default = '' ){    
    return  \Drupal::request()->get($key, $default );  // $_GET / $_POST    
  }

  /**
   * It makes slash from double slash or backslash
   * @param mixed $p 
   * @return string|string[] 
   */
  public function slash($p){
    return str_replace(['\\',"//"],"/", $p);
  }
  
  function Pics(){
		$pics = $_SESSION["slide"];
		unset ($pics["id"], $pics["path"], $pics["i"]);
		return $pics;
	}
}