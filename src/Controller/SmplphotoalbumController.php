<?php

/**
 * @file
 * Contains \Drupal\mymodule\Controller\MyModuleController.
 */
namespace Drupal\smplphotoalbum\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\InfoParser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
//use Drupal\Core\Config\ConfigFactoryInterface;
//use Drupal\Core\Extension\ModuleHandlerInterface;

require_once drupal_get_path ( 'module', 'smplphotoalbum' ) . "/src/functions.php";

class SmplphotoalbumController extends ControllerBase {
  private $cfg;
  private $mp;
  private $root;
  /**
   * Class constructor.
   */
  public function __construct() {
    $this->cfg = \Drupal::config('smplphotoalbum.settings');    
    $this->mp  = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();    
    $root = $this->cfg->get('root');           
    $root = str_replace("public://", \Drupal::service('file_system')->realpath("public://")."/", $root);    
    $root .= substr($root,-1) !='/'?"/":'';
    
    $this->root = str_replace(['\\',"//"],['/',"/"],$root);    
  }
  
  // ...
  // AJAX Callback to read a message.
  public function help() {
    $output = '<h3>' . t ( 'About' ) . '</h3>';    
    $path = $this->mp . '/smplphotoalbum.info.yml';
    $InfoParsed = new InfoParser ();
    $info = $InfoParsed->parse ( $path );
    $output .= 'Author: <a href="http://www.fzolee.hu">Zoltan Fabian</a><br/>';
    $output .= t ( 'version' ) . ': ' . $info ['version'] . '<br/>';
    $output .= t ( 'compiled' ) . ': ' . date ( 'Y.m.d', $info ['datestamp'] );
    $output .= "<p>" . t ( "Simple Photoalbum shows a set of picture and other filetypes ." ) . "</p>";
    $lang = \Drupal::languageManager ()->getCurrentLanguage ()->getId ();
    if ($lang == "hu") {
      $output .= file_get_contents ( $this->mp  . "/Olvassel.html" );
    } else {
      $output .= file_get_contents ( $this->mp  . "/Readme.html" );
    }
    
    $selector = "div#smpl_help_content";
    $content = $output;
    $settings = [ ];
    $response = new AjaxResponse ();
    $response->addCommand ( new InsertCommand ( $selector, $content, $settings ) );
    return $response;
  }
  
  public function delete($id = -1) {
    $id = ( int ) $id;
    $ok = false;
    $response = new AjaxResponse ();
    if (! smplphotoalbum_access () || $id == -1) {
      $response->addCommand ( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }
    //
    $con = \Drupal::database();
    $qry = $con->select ( 'smplphotoalbum', 's' )->fields ( 's', [ 
        'id',
        'path',
        'name' 
    ] )->condition ( 's.id', $id, '=' );
    $res = $qry->execute ();
    $res->allowRowCount = true;
    $db = $res->rowCount ();
    if ($db == 1) {
      // get simplephotoalbum root folder
      // get path of image
      $a = $res->fetchAssoc ();
      
      $path = $a["path"];
      $path .= (substr ( $path, 0, 1 ) != "/") ? '/' : '';
      $path .= (substr ( $path, - 1 ) != "/") ? '/' : '';
      //
      $tn = $this->root . $path . TN . $a ["name"];
      $tn = str_replace("//","/",$tn);
      $ok = unlink ( $tn );
      //
      if ($ok) {
        $img = $this->root . $path . $a ["name"];
        if (file_exists( $img )) {
          $ok = (unlink( $img ) ? true : false);
        } else {
          $ok = false;
        }
        // Delete from database
        if ($ok) {
          $db = $con->delete ( "smplphotoalbum" )->condition ( "id", $id, "=" )->execute ();
          $ok = ($db == 1);
        }
      }
    }
    $content = ($ok) ? $id : '-1';
    $response->addCommand ( new InsertCommand ( '', $content, [ ] ) );
    return $response;
  }
  
  // exif
  public function exif($id = 1) {
    $response = new AjaxResponse ();
    if (! $this->access ()) {
      $response->addCommand ( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }
    
    $con = \Drupal::database ();
    $record = $con->select ( 'smplphotoalbum', 's' )->fields ( 's', [ 
        'id',
        'path',
        'name',
        'typ' 
    ] )->condition ( 's.id', $id, '=' )->execute ();
    $a     = $record->fetchAssoc ();    
    
    //
    $ex   = new Exif($a, $this->cfg);
    $exif = $ex->Info();
      //
    $str = file_get_contents( $this->mp . "/templates/exif.html.twig" );
    $str = str_replace( "{{ exif }}", $exif, $str );
    $response->addCommand ( new InsertCommand ( '', $str, [ ] ) );
    return $response;
  }
  
  /**
   * Load data of an item
   * 
   * @param unknown $id          
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   */
  public function edit($id = -1) {
    $id = (int) $id;
    $response = new AjaxResponse();
    
    if (! $this->access() ) {
      $response->addCommand( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }
    
    $con = \Drupal::database ();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [ 
        'id',
        'name',
        'subtitle',
        "link" 
    ] )->condition( 's.id', $id, '=' )->execute ();
    
    $a = $record->fetchAssoc ();
    $content = json_encode ( $a );
    $response->addCommand ( new InsertCommand ( '', $content, [ ] ) );
    return $response;
  }
  
  /**
   * modify the properties of an item
   * @param unknown $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function update($id = -1) {
    $response = new AjaxResponse();
    if (! $this->access()) {
      $response->addCommand ( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }
    
    $json = json_decode ( $_REQUEST ['json'] );
    
    $json->subtitle = $this->cleansubtitle($json->subtitle );
    
    $con = \Drupal::database ();
    $db = $con->update ( "smplphotoalbum" )->fields ( array (
        "subtitle" => $json->subtitle,
        "link    " => $json->link 
    ) )->condition ( "id", $id, "=" )->execute ();
    
    $content = json_encode ( array (
        "db"       => $db,
        "subtitle" => $json->subtitle,
        "link"     => $json->link 
    ) );
    $response->addCommand ( new InsertCommand ( '', $content, [ ] ) );
    return $response;
  }
  
  function cleansubtitle($s){
    $s = str_ireplace ( smpl_extensions(), '', $s);
    $s = trim(str_replace ( ["-","_", '.'], [" "," ",""], $s ));  
    return $s;
  }
  /**
   * Edit the properties of this path
   * @param unknown $id
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function updatepath($id = -1){
    $id = (int)$id;
    $response = new AjaxResponse();
    if (! $this->access() || $id ==-1) {
      $response->addCommand( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }

    $con = \Drupal::database ();
    $record = $con->select( 'smplphotoalbum', 's' )
            ->fields( 's', ['path', ] )
            ->condition( 's.id', $id, '=' )
            ->execute ();
    $path = $record->FetchAssoc()["path"];    
    if(empty($path)){
      $response->addCommand( new InsertCommand ( '', "There is not path to Invalid ID: ".$id, [ ] ) );
      return $response;
    }
    $recs = $con->select('smplphotoalbum','s')
            ->fields ('s', ["id", "subtitle"] )
            ->condition ( "path", $path, "=" )
            ->execute ();    
    $a = $recs->FetchAll();    
    $ext = smpl_extensions();
    $chg = array();
    foreach($a AS $i => $e){
      $id  = $e->id;
      $bkp = $e->subtitle;
      $e->subtitle = $this->cleansubtitle($e->subtitle );
      $chg[$i] = ($bkp == $e->subtitle)? true : false;
    }
    $sum = 0;
    foreach($a AS $i => $e){
      if(!$chg[$i]){
        $db = $con->update ( "smplphotoalbum" )->fields ( array (
          "subtitle" => $e->subtitle,      
        ) )->condition ( "id", $e->id, "=" )->execute ();
        $sum += $db;
      }
    }
    \Drupal::messenger ()->addMessage ( t ( $sum.' of records updated on the path "'.$path .'" '), 'status' );    
    
    $content = json_encode( 
      array(
        'db'   => $sum,
        'path' => $path
      )
    );
   
    $response->addCommand ( new InsertCommand ( '', $content, [ ] ) );
    return $response;
  }
  
  /**
   * IMGEdit controller function
   * 
   * @param $id -
   *          id of item
   * @param string $cmd
   *          - Command
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function imgedit($id = -1, $cmd = 'load') {
    $response = new AjaxResponse ();
    
    if (! $this->access()) {
      $response->addCommand ( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }
    
    if ($id != 0) {
      $img = new ImageEdit( $id );
      switch ($cmd) {
        case 'load' : $json = $img->load(); break;
        case 'save' : $json = $img->save(); break;
        case 'edit' : $json = $img->edit(); break;
        case 'undo' : $json = $img->undo(); break;
        case 'redo' : $json = $img->redo(); break;
        case 'thumbnail' : $json = $img->thumb(); break;
        case 'close': 
        default     : $json = $img->close();
      }
    } else {
      $json = array("cmd",$cmd);
    }
    $response->addCommand( new InsertCommand ( '', json_encode ( $json ), [ ] ) );
    return $response;
  }
  /**
   * send an image
   * @param int $id
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function slide($id = -1){
    if ($id == -1 ) {
      return new BinaryFileResponse ( $this->mp . "/image/404.png" );
    }    
    $con = \Drupal::database();
    $rs = $con->select( "smplphotoalbum", "s" )
      ->fields( "s", array('path','name') )
      ->condition( 'id', $id, '=' )
      ->execute();
    $record = $rs->fetchAssoc();
    
    if (empty ( $record )) {
      return new BinaryFileResponse ( $this->mp . "/image/404.png" );
    }
    $tn = isset($_REQUEST['tn'])? $_REQUEST["tn"]:0;
    if($tn >0){
      $p = $this->root . $record['path'] . TN. $record['name'];
    }else{
      $p = $this->root . $record['path'] . $record['name'];
    }
    
    $p = str_replace('//','/',$p);    
    // Watermark if you want depends of type of file
    $response =  new BinaryFileResponse ( $p );
    if(! $this->isimage($p)){
      $response->headers->set('Pragma','no-cache');
      $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($p) . '"');
    }
    return $response;
  }
  /**
   * paging of slide
   * @param unknown $id
   * @param string $cmd
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function slideget($cmd ="next"){
    $path = isset ( $_REQUEST ['path']  ) ? $_REQUEST ['path']  : "";
    $i    = isset ( $_REQUEST ["i"] )     ? $_REQUEST ['i'] : -1;    
    $response = new AjaxResponse ();
    $json = array("id" => "-1");  
    
    if( empty($path) || $path == "/" ){
      $json["error"] = "Empty path or root path";
      $response->addCommand ( new InsertCommand ( '', json_encode($json), [ ] ) );
      return $response;
    }

    if(!isset($_SESSION['slide'][$path])){
      $json["error"] = "There is no 'slide' session. Maybe clear the cache!";
      $response->addCommand ( new InsertCommand ( '', json_encode($json), [ ] ) );
      return $response;
    }

    $pics = $_SESSION['slide'][$path];    
    $db = count($pics['img']);
  
    if($cmd == "next"){
      $i++;
      if( $i >= $db){
        $i = 0;
      }
    }
    if($cmd == "prev"){
     $i--;
      if( $i<0 ){
        $i = $db-1;
      }      
    }    
//    fz_t($i);
    $json["id"]       = $pics['img'][$i]["id"];
    $json["error"]    = "";
    $json["i"]        = $i;
    $json["path"]     = $path;
    $json['title']    = $pics['img'][$i]["title"];
    $json['subtitle'] = $pics['img'][$i]["subtitle"];
    $json['ths']      = $this->slidetnget($pics['img'], $i);    
    $content = json_encode($json);        
    $response->addCommand( new InsertCommand ( '', $content, [$i ] ) );
    return $response;
  }
  
  function slidetnget($p, $i){
    $a = array();
    $k = 0;
    $db = count($p);     
    for($j =-3; $j < 4; $j++ ){
      $idx = ($db + $i + $j) % $db;      
      $a[$k]['id']    = $p[$idx]["id"];
      $a[$k]['title'] = $p[$idx]["title"];
      $k++;
    }    
    return $a;
  }
  /**
   * Shows an image
   * 
   * @param unknown $id          
   * @return Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  public function v($id = -1) {
    // search path
    $name = isset ( $_REQUEST ['n']  ) ? $_REQUEST ['n']  : "";
    $path = isset ( $_REQUEST ['p']  ) ? $_REQUEST ['p']  : "";
    $tn   = isset ( $_REQUEST ['tn'] ) ? $_REQUEST ['tn'] : "";    
        
    if (empty ( $path ) || empty ( $name )) {
      return new BinaryFileResponse ( $this->mp . "/image/404.png" );
    }    
    
    if (!empty ( $tn )) {
      $p = $this->tn( $path, $name );
    } else {
      $con = \Drupal::database();
      $rs = $con->select( "smplphotoalbum", "s" )
                ->fields( "s", array('path','name') )
                ->condition( 'id', $id, '=' )
                ->execute();
      $record = $rs->fetchAssoc();
      
      if (empty ( $record )) {
        return new BinaryFileResponse ( $this->mp . "/image/404.png" );
      }
      $p = $this->root . $path . $name;
      
      $con->update('smplphotoalbum')
          ->expression("viewnumber","viewnumber + 1")
          ->fields(array("viewnumber" => 0))
          ->condition ( "id", $id, "=" )
          ->execute();
    }
    // Watermark if you want depends of type of file
    $response =  new BinaryFileResponse ( $p );
    if(! $this->isimage($p)){
      $response->headers->set('Pragma','no-cache');
      $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($p) . '"');
    }
    return $response;
  }
  
  /**
   * search the thumbnail image
   * 
   * @param unknown $id          
   * @return Symfony\Component\HttpFoundation\BinaryFileResponse
   */
  private function tn( $path = "", $name = "") {
    $TN = $this->cfg->get('TN');
    $p = $this->root . $path . $TN . $name;
    if ($this->isimage ( $name )) {
      if (!file_exists( $p )) {
        $p = $this->root . $path . $name;
      }
    } else {
      if (!file_exists( $p )) {
        $p = $this->root . $path . $TN . $name . ".png";
      }
    }
    if (!file_exists( $p )) {
      return "-1";
    }
    // Watermark if you want depends of type of file
    return $p;
  }
  
  /**
   * Load data of an item
   * 
   * @param unknown $id          
   * @return \Drupal\Core\Ajax\AjaxResponse|string
   */
  public function statload($id = -1) {
    $response = new AjaxResponse ();
    $a["id"] = "-1";
    if (! smplphotoalbum_access ()) {
      $a["error"] ="There is not enough permission";
      $response->addCommand ( new InsertCommand ( '', json_encode($a), [ ] ) );
      return $response;
    }
    
    $con = \Drupal::database ();
    $record = $con->select ( 'smplphotoalbum', 's' )->fields ( 's', [ 
        'id',
        'path',
        'name',
        'subtitle',
        'typ',
        'viewnumber',
        'link' 
    ] )->condition ( 's.id', $id, '=' )->execute ();
    
    $a = $record->fetchAssoc ();
    $content = json_encode ( $a );
    $response->addCommand ( new InsertCommand ( '', $content, [ ] ) );
    return $response;
  }
  /**
   *
   * @param unknown $id          
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function statupdate($id = -1) {
    $response = new AjaxResponse ();
    if (! smplphotoalbum_access ()) {
      $response->addCommand ( new InsertCommand ( '', "-1", [ ] ) );
      return $response;
    }
    
    $json = json_decode ( $_REQUEST ['json'] );
    $json->subtitle = smpl_ext_replace ( $json->subtitle );
    $json->subtitle = str_replace ( array (
        "-",
        "_" 
    ), " ", $json->subtitle );
    $json->subtitle = trim($json->subtitle);
    $con = \Drupal::database ();
    $db = $con->update ( "smplphotoalbum" )->fields ( array (
        "subtitle" => $json->subtitle,
        "link" => $json->link,
        "viewnumber" => $json->viewnumber 
    ) )->condition ( "id", $id, "=" )->execute ();
    $content = json_encode ( array (
        "db" => $db,
        "subtitle" => $json->subtitle,
        "link" => $json->link,
        "viewnumber" => $json->viewnumber 
    ) );
    $response->addCommand ( new InsertCommand ( '', $content, [ ] ) );
    return $response;
  }
  
  private function isimage( $entry){
    return stripos(" ".$this->cfg->get('image_extensions'), pathinfo($entry, PATHINFO_EXTENSION)) > 0;
  }
  
  private function access(){
    $roles = \Drupal::currentUser ()->getroles ();
    return in_array ( 'administrator', $roles ) ? true : false;
  }
}
?>