<?php
/**
 * @file
 * Contains \Drupal\mymodule\Controller\MyModuleController.
 */
namespace Drupal\smplphotoalbum\Controller;
require_once realpath(__DIR__."/../../")."/vendor/autoload.php";

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Database\InvalidQueryException;
use Drupal\Core\Extension\InfoParser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\smplphotoalbum\Controller\ImageEdit;
use Drupal\smplphotoalbum\Controller\VideoEdit;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\smplphotoalbum\Controller\Lib;

//Gemini
use Gemini;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;

use Drupal\smplphotoalbum\AI;
use Drupal\smplphotoalbum\SlideShow;

class SmplphotoalbumController extends ControllerBase{  
  private $cfg;
  private $mp;
  private $root;
  private $public;
  private $TN;
  private $sess;
  private $wm;  
  private $aigemini;
  private $lang;
  private $words = [];
  private $ffmpeg = false;   // true/false
  private $ffmpeg_path = ''; // on Linux: /usr/bin/ffmpeg and on Windows for example C:\\ffmpeg\\bin\\ffmpeg.exe
  
  /**
   * Class constructor.
   */
  public function __construct() {      
    $this->cfg    = \Drupal::config( 'smplphotoalbum.settings' );
    $this->lang   = $this->cfg->get( 'lang' );
    $this->mp     = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->public = \Drupal::service( 'file_system' )->realpath( "public://" );
    $this->sess   = \Drupal::request()->getSession();
    $this->wm     = $this->sess->get("wm", false);    
    $root = $this->cfg->get( 'root' );    
    $root = str_replace( "public://", $this->public . "/", $root );
    $root .= substr( $root, - 1 ) != '/' ? "/" : '';

    $this->root = Lib::slash( $root );
    $this->TN = $this->cfg->get( 'TN' );       
    $this->aigemini = $this->cfg->get("aigemini");    
    $this->ffmpeg = $this->cfg->get("ffmpeg");
    $this->ffmpeg_path = $this->cfg->get("ffmpeg_path");        
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
   * @param string $type
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function exif($id = 1, $type ='' ) {
    $response = new AjaxResponse();

    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'path',
        'name',
        'typ', 
        'viewnumber',
        'subtitle',
        'link',
        'size',
        'modified',
        'importance'       
    ] )->condition( 's.id', $id, '=' )->execute();
    $a = $record->fetchAssoc();
  
    //
    $ex = new Exif( $a, $this->cfg );
    
    //Video convert
    if ( !empty($type) && $a['typ'] == "video" ){
      $ex->Videoinfo( $a );
      return $a;
    }
    
    $exif = (string) $ex->Info();        
    $str = file_get_contents( $this->mp . "/templates/exif.html.twig" );
    $str = str_replace( "{{ exif }}", $exif, $str );
        //
    $this->Readwords();
    $s = [
      "{{ id }}",
      "{{ desc }}",
      "{{ FileSize }}",
      "{{ filesize }}",
      "{{ File type }}",
      "{{ Last modified }}",
      "{{ lastmodified }}",
      "{{ Number of views }}",
      "{{ viewnumber }}",
    ];
    $r = [
      $a["id"],
      $a["name"]." => ".$a["subtitle"],
      $this->words["File size"],
      Lib::ShowFileSize($a["size"]),
      $this->words["File type"],
      $this->words["Last modified"],
      date("Y.m.d",$a["modified"]),
      $this->words["Number of views"],
      $a["viewnumber"],
    ];
    $str =str_replace($s, $r, $str);
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
    if($a['typ'] == "video") {
      $x = $this->exif($id, 'video');  
      $a["width"]     = $x['width'];
      $a["height"]    = $x['height'];
      $a["framerate"] = $x['framerate']; 
      $a['filesize']  = $x['filesize'];
      $a['clipend']   = $x['clipend'];      
    }

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

    $json = json_decode( Lib::Request("json") );

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

      $fromtn = Lib::slash( $fromtn );
      $totn   = Lib::slash( $totn );

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
        $fromimg = Lib::slash( $fromimg );
        $toimg   = Lib::slash( $toimg );
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
    $rs = $con->select( 'smplphotoalbum', 's' )
              ->fields( 's', [ 'path' ] )
              ->condition( 's.id', $id, '=' )
              ->execute();                  
    $path = $rs->fetchAssoc()["path"];

    if(empty( $path )) {
      $response->addCommand( new InsertCommand( '', "There is not path to Invalid ID: " . $id, [] ) );
      return $response;
    }

    $qry = $con ->select( 'smplphotoalbum', 's' )
                ->fields( 's', [ 'id', 'subtitle', 'path' ] )
                ->condition( 'path', $path, "=" );

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
    $s = trim( str_replace( ["-","_"], [" "," "], $s ) );
    return $s;
  }

  /**
   * IMGEdit controller function
   *
   * @param int $id     - id of item
   * @param string $cmd - Command
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function imgedit($id = -1, $cmd = 'load', $oldname = "", $newname = "" ) {
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
    $pics = Lib::Pics();

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
      $record["name"]     = $_SESSION["slide"]["img"][$i]['name'];
      $record["subtitle"] = $_SESSION["slide"]["img"][$i]['subtitle'];

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

      $_SESSION["slide"] ['i']  = $i;
      $_SESSION["slide"] ["id"] = $id;
      $_SESSION["slide"] ["img"] [$i] ['path'] = $record["path"];
      $_SESSION["slide"] ["img"] [$i] ['name'] = $record["name"];
      $_SESSION["slide"] ["img"] [$i] ['subtitle'] = $record["subtitle"];      
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

    $Slide = new SlideShow($path = "", "", $this->mp);
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
  public function v( $id = -1 ) {    
    // search path
    $name = Lib::Request("n","");
    $path = Lib::Request("p","");    
    $tn   = Lib::Request("tn","");
    
    if(empty( $path ) || empty( $name )) {
      return new BinaryFileResponse( $this->mp . "/image/404.png" );
    }

    $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
    if( $name == "..") $p = $this->mp . "/image/folderup.png";
    else if( $tn == "folder" ) $p = $this->mp . "/image/folder.png";
    else if( $tn != "" ){      
      switch($ext){
        case "xbm": $p = $this->mp ."/image/other_xbm.png"; break;
        case "bak": 
        case "bkp": $p = $this->mp ."/image/other_bak.png"; break;
        case "xbm": $p = $this->mp ."/image/other_torrent.png"; break;
        default   : $p = Lib::slash( $this->root . $path . $this->TN . $name, "file"); break;
      }      
    }else{
      $con = \Drupal::database();
      $rs = $con->select( "smplphotoalbum", "s" )
                ->fields( "s", ['path','name'] )
                ->condition( 'id', $id, '=' )
                ->condition( 'name', $name, '=')
                ->execute();
      $record = $rs->fetchAssoc();
      if(empty( $record )) {
        return new BinaryFileResponse( $this->mp . "/image/404.png" );
      }
      $p = Lib::slash( $this->root . $path . $name, "file") ;
      $con->update('smplphotoalbum')
          ->expression( "viewnumber", "viewnumber + 1" )
          ->fields( [ "viewnumber" => 0 ] )
          ->condition( "id", $id, "=" )
          ->execute();

      // Watermark if you want depends of type of file
      if( $this->isimage( $p ) && Lib::getSession("wm", false)) {
        $p = $this->watermarkonfly( $id, $p );
      }
    }
    
    if( !file_exists( $p ) ) {
      return new BinaryFileResponse( $this->mp . "/image/404.png" );
    }
    $response = new BinaryFileResponse( $p );
    
    if( !$this->isimage( $p )) {    
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
      $wmpath = Lib::getSession("wmpath", false);
      if( !$wmpath ){
        $wmpath = Lib::getConfig( 'wmpath' );
      }
    }

    if(strpos( " " . $wmpath, "smplphotoalbum://" ) > 0) {
      
      $wmpath = str_replace( 
        "smplphotoalbum://", 
        Lib::slash( DRUPAL_ROOT . "/" . $this->mp . "/" ), 
        $wmpath 
      );

    } elseif(strpos( " " . $wmpath, "public://" ) > 0) {
      $public = Lib::slash($this->public );
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

    $wmalpha = Lib::getSession("wmalpha", 50);
    imagefilter( $wmimg, IMG_FILTER_BRIGHTNESS, ( int ) $wmalpha );
    imagecopymerge( $img, $wmimg, $x1, $y1, 0, 0, $wmdx, $wmdy, $wmalpha );
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
  function ImageCreateFrom( $src ) {
    $p = pathinfo( $src );
    switch(strtolower($p["extension"])){
      case "avif": $img = @imagecreatefromavif( $src ); break;        
      case "bmp" : $img = @ImageCreateFromBmp( $src );  break;        
      case 'gif' : $img = @ImageCreateFromGif( $src );  break;
      case 'jpg' :
      case 'jpeg': $img = ImageCreateFromJPEG( $src );  break;
      case 'png' : $img = @ImageCreateFromPNG( $src );  break;
      case 'wbmp': $img = @ImageCreateFromwbmp( $src ); break;
      case "webp": $img = @ImageCreateFromWebp( $src ); break;
      case "xbm" : $img = @ImageCreateFromXbm( $src );  break;
      case "xpm" : $img = @ImageCreateFromXpm( $src );  break;
    }
    return $img;
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
  public function ai( $id, $cmd =''){
    global $base_url;
    $response = new AjaxResponse();    
    // Check the access
    if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
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
    if( $a[ 'typ' ] != "image" ){
      $str = json_encode( ["id" => "-1", "msg" => "The type of file does not 'image'!" ] );     
      $response->addCommand( new InsertCommand( '', $str, [] ) );
      return $response;
    }

    $p =  LIB::slash($this->root. $a["path"]."/".$a["name"], "file");
    
    if( $this->aigemini ){
      $this->ReadWords();
      $AI = new AIGemini( $p, $cmd, $this->words );     
      $answer = $AI->process();
    } else{     
      $answer =["id" => "-1", "msg" => "There is no AI Gemini enabled!" ];     
    }
    
    $str = json_encode( $answer);     
    $response->addCommand( new InsertCommand( '', $str, [] ) );    
    return $response;
  }

  /**
   * Video edit window open
   * @param int $id 
   * @param string $cmd 
   * @return AjaxResponse 
   */
  public function videoedit( $id = -1, $cmd = 'load', $oldname = '', $newname = '') {
    
    $response = new AjaxResponse();

    if(! $this->access()) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }

    // Is there enabled the ffmpeg conversion 
    if( !$this->ffmpeg ){
      $response->addCommand( new InsertCommand( '', "FFMpeg conversion is disabled", [] ) );
      return $response;
    }

    // Is there ffmpeg installed on the server 
    if( ! file_exists( $this->ffmpeg_path ) ) {  
      $response->addCommand( new InsertCommand( '', "FFMpeg path is invalid", [] ) );
      return $response;
    }

    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id',
        'path',
        'name',
        'typ',                
    ] )->condition( 's.id', $id, '=' )->execute();
    
    // there is no record
    $a = $record->fetchAssoc();

    if( !isset( $a['id']) || $a['id'] != $id  ){      
      $response->addCommand( new InsertCommand( '', "There is no record" , [] ) );
      return $response;
    }

    // not video
    if( !in_array($a['typ'], ["video" , "videohtml5"]) ) {
      $response->addCommand( new InsertCommand( '',  "The item is no video", [] ) );
      return $response;
    } 
    
    // original extension of video
    $p = Lib::slash( $this->root . $a["path"]."/".$a["name"] );        
    $ext = strtolower(pathinfo( $p, PATHINFO_EXTENSION ));
    
    if($id != 0) {
      $oldname = Lib::Request('oldname', 0 );
      $newname = Lib::Request('newname', 0 );      
      $newext = Lib::Request('newext', $ext );       // extension of output video
      $width = Lib::Request('width', 0 );
      $height = Lib::Request('height', 0 );
      $framerate = Lib::Request('framerate', 30 );
      $gop = Lib::Request('gop', 2 );             // group of pictures
      $clipstart = Lib::Request('clipstart', 0 ); // clip start time >=0      
      $clipend = Lib::Request('clipend', 0 );     // clip last time <= duration
      $rotate = Lib::Request('rotate', 0 );       // rotate video 90,180,270      
      $duration = Lib::Request('duration', 0 );   // Length of video      
            
      $video = new VideoEdit( 
        $id, 
        $a['path'],
        $a['name'],
        $a['typ'],
        $ext,
        $newext,
        $width,
        $height,
        $framerate,
        $gop,
        $clipstart,
        $clipend,
        $rotate,
        $duration,
        $oldname,
        $newname        
      );

      switch($cmd){
        case 'load'  :  $json = $video->Load(); break;       
        case 'save'  :  $json = $video->Save(); break;
        case 'saveas':  $json = $video->SaveAs(); break;
        case 'convert': $json = $video->Convert(); break;
        case 'prev'  :  $json = $video->Prev(); break;
        case 'next'  :  $json = $video->Next(); break;
        case 'cancel':  $json = $video->Cancel(); break;
        case 'close' :
        default      :  $json = $video->Close();
      }
    } else {
      $json = ["cmd", $cmd ];
    }     
    $response->addCommand( new InsertCommand( '', json_encode( $json ), [] ) );
    return $response;
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

    $json = json_decode( Lib::Request( 'json' ) ) ;
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

  /**
   * 
   * @param mixed $p 
   * @return string 
   */
  function DblSlashToSmpl($p){
    if(substr( $p, 0, 1 ) == "/"){
      $p = substr($p,1);
    }
    $p .= (substr( $p, - 1 ) != "/") ? '/' : '';
    return $p;
  }

  function Pics(){
		$pics = $_SESSION["slide"]["img"];
		unset ($pics["id"], $pics["path"], $pics["i"]);
		return $pics;
	}

  /**
 	 * Reads the words of translating
	 * @return
 	 */
	function ReadWords(){		
		if($this->lang == ""){
			$this->lang = 'en';
		}
		if( $this->lang == 'en' ){
			$words = file( $this->mp ."/translate/translate.txt", FILE_IGNORE_NEW_LINES );
		} else {			
			$words = file( $this->mp ."/translate/translate_" . $this->lang . ".txt", FILE_IGNORE_NEW_LINES );
		}
		$words = str_replace( "_"," ", $words);
		
		foreach($words AS $e){
			$e = trim( $e );
			if( strpos( ' '.$e, ';' ) > 0 ){
				continue;
			}
			$a = explode( "=", $e );

			if( count( $a ) == 1 ) {
				$this->words[ $e ] = $e;
			}else{
				$this->words[ trim( $a[0] ) ] = trim( $a[1] );
			}
		}
	}	


}