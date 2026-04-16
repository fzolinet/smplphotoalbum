<?php
/**
 * Common libraries
 * @package 
 */
namespace Drupal\smplphotoalbum\Controller;

define( "KB", 1024 );
define( "MB", 1048576 );
define( "GB", 1073741824 );
define( "TB", 1099511627776 );
class Lib{
  
/**
   * Show file size in B, KB, MB or GB
   * @param mixed $size in bytes
   * @return string 
   */

  public static function ShowFileSize($size) {
		if( $size > TB){
      $s = ( int ) ( $size / TB ) . '&nbsp;TB';
    } else if ($size > GB ) {
			$s = ( int ) ( $size / GB ) . '&nbsp;GB';
		} else if ( $size > MB ) {
			$s = ( int ) ( $size / MB ) . '&nbsp;MB';
		} else if ( $size > KB)  {
			$s = ( int ) ( $size / KB ) . '&nbsp;KB';
		} else {
			$s = $size . '&nbsp;B';
		}
		return $s;
	}

 /**
	 * Give back the extension of image
	 */
  public static function getExt( $str ) {
    $ext = pathinfo( $str, PATHINFO_EXTENSION );
    return strtolower( $ext );
  }

  /**
   * give back the filename from path
   * @param string $str
   * @return string filename without extension
   */
  public static function getFilename( $str){
    return pathinfo( $str, PATHINFO_FILENAME );
  }

  /**
   * Get root
   * @param string $root
   *
   * @return string
   */
  public static function getRoot(string $root){
    $root = str_replace ( "public://", \Drupal::service( 'file_system' )->realpath( "public://" )."\\", $root);
    $root .= substr( $root, -1 ) != '/' ? "/" : '';
    $root = str_replace("\\","/", $root);
    return $root;
  }

  public static function getTempUrl( ){
    $t = self::getConfig( 'temp' );
    $t = \Drupal::service( 'file_url_generator' )->generateAbsoluteString ( $t );
    $t = self::slash($t);
    return $t;
  }

  public static function getTempPath(){
    $t = self::getConfig( 'temp' );
    return self::slash(\Drupal::service ( 'file_system' )->realpath ( $t ) );
  } 

  public static function getTempVideoUrl($path){
    $t = self::getConfig( 'temp' );
    $t = \Drupal::service( 'file_url_generator' )->generateAbsoluteString ( $t );
    $t = self::slash($t);
    return $t . $path;
  }

  /**
   * It makes slash from double slash or backslash
   * @param mixed $p 
   * @return string|string[] 
   */
  public static function slash( $p, $type="folder" ){
    if( $type == "folder" || empty(self::getExt($p) ) ) {
      $p .= substr( $p, - 1 ) != '/' ? "/" : '';          
    }  
    $p = str_replace( "://",":__", $p );
    $p = str_replace([ "\\", "//" ],[ "/", "/" ], $p);
    $p = str_replace( ":__", "://", $p );
    return $p;
  }  

  /**
   * get cfg 
   * @param mixed $str 
   * @return mixed 
   */
  public static function getConfig( $str = "" ) {
    static $cfg = null, $ts = [];
    if ( $cfg == null ) {
      $cfg = \Drupal::config('smplphotoalbum.settings');
    } 

    if( empty( $str ) ) {
      return $cfg;
    } 

     $val = $cfg->get( $str );
     if ( !isset($ts[ $str ])){
       $ts[ $str ] = $val;
     }
     return $ts[$str];
  }
  
  /**
   * Give back to the path of smplphotoalbum module
   * @return mixed 
   */
  public static function getModulepath(){
    static $modulepath = "";
    if( empty( $modulepath ) ){
      $modulepath = \Drupal::service ( 'module_handler' )->getModule ( 'smplphotoalbum' )->getPath ();
    }
    return $modulepath;    
  }

  /**
   * Reset Edit Session
   * @return void 
   */
  public static function ResetEditsession( $type = "image", &$ts = [] ){
    unset(
      $ts['tempname'],
      $ts['path'],
      $ts['name'],
      $ts['que'],
      $ts['idx'],
      $ts["filesize"],
      $ts["width"],
      $ts["height"]
    );
    if($type != "image"){        
      unset (     
        $ts["framerate"],
        $ts["gop"],
        $ts["clipstart"],
        $ts["clipend"],
        $ts["duration"]
      );    
    }
    self::setSession( "smpl", $ts );
  }

  /**
   * Get the session 
   * @param mixed $str 
   * @return mixed 
   */
  public static function getSession( $str ){
    static $session = null;
    if ( $session == null ) {
      $session = \Drupal::request()->getSession();
    } 
    return $session->get( $str );
  }

  /**
   * Set Session variables
   * @param mixed $str 
   * @param mixed $val 
   * @return void 
   */
  public static function setSession( $str, $val ){
    static $session = null;
    if ( $session == null ) {
      $session = \Drupal::request()->getSession();
    }
    $session->set( $str, $val );
  }

  /**
   * Delete the session variable
   * @param mixed $str 
   * @return void 
   */
  public static function deleteSession( $str ){
    static $session = null;
    if ( $session == null ) {
      $session = \Drupal::request()->getSession();
    }
    $session->remove( $str );
  }

  /**
   * Make a new name of temporary file
   *
   * @param string $name
   * @param number $i
   * @return string
   */
  public static function NewName( $name = "", $i = 0 ) {
    $p = pathinfo ( $name );
    $p[ "filename"] = preg_replace( '/_temp_\d+/',"", $p[ "filename"] );
    $name = $p ["filename"] . "_temp_" . $i;
    $ext  = $p ["extension"];
    return $name . "." . $ext;
  }

   /**
   * Make a sign file into the temporary folder
   * @return string
   */
  public static function Sign( $str = "" ){
    if( empty( $str ) ){
      $str = "_sign.txt";
    } else{
      $str = pathinfo ( $str, PATHINFO_FILENAME ) ."_sign.txt";
    }    
    return $str;
  }
  
  public static function DeleteSignFile($temppath, $SignFile){
    
    if ( file_exists($temppath . $SignFile ) ) {      
      return unlink($temppath . $SignFile);      
    }
    return false;
}

  public static function SignUrl( &$ts ){    
    return  $ts["tempurl"] . self::Sign($ts["tempname"] );
  }

  /**
   * get the GET and POST request
   *
   * @param string $cmd
   * @param string $default
   * @param string $method - GET | POST | BOTH
   * @return string|int|float|bool|array|null
   */
  public static function Request( $cmd, $default = '' , $method = "BOTH" ){ 
    static $rq = null;
    $method = strtoupper($method);

    if ( $rq == null ) {
      $rq = \Drupal::request();
    }
    // GET request
    if( $method == "GET" || $method == "BOTH" ) {
      $g = $rq->query->get( $cmd );
      if ($g == "undefined") {
        $g = $default;
      }

      if ( !empty ( $g ) ) {
        return $g;
      }
    }

    //POST request
    $r = $rq->request->get( $cmd );
    if (isset ( $r )) {
      return $r;
    }
    return $default;
  }

   /**
   * Can you acces the smplphotoalbum
   * @return bool 
   * @throws ContainerNotInitializedException 
   * @throws ServiceCircularReferenceException 
   * @throws ServiceNotFoundException 
   */
  public static function smplphotoalbum_access() {
		$roles = \Drupal::currentUser()->getroles();
		return in_array( 'administrator', $roles ) ? true : false;
	}
}

