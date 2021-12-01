<?php
/* $Id: image.php,v 1.15.2.3.2.2.2.11 2009/10/29 00:09:17 tjfulopp Exp $ */

/* Check for bad URL inputs */
include "inc/functions.inc";
$url = isset($_GET['i']) ? $_GET['i'] : "/";

if( strpos($url, "://") !== false || strpos($url, "..") !== false ) {
 	header("HTTP/1.0 404 Not Found");
 	exit();
}

while (!@stat('includes/bootstrap.inc')) {
  chdir('..');
}
define('DRUPAL_ROOT',getcwd());

require './includes/bootstrap.inc';
//require './includes/file.inc';

drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);

$GLOBALS['devel_shutdown'] = FALSE;
if($url == '/'){
  $root= __DIR__."/image";
}else{
  $root = smpl_root();
}
$tmp = realpath($root.$url);

if(!file_exists($tmp)){
  $tmp = realpath(utf8_decode($root.$url));
  if(!file_exists($tmp )){
    if(is_dir($tmp ) || !file_exists($tmp)){
      $root= __DIR__."/image";
      $url       ="/404.png";
    }
  }
}

$root = str_replace("\\","/",$root);
$path      = pathinfo($url, PATHINFO_DIRNAME);
$name      = pathinfo($url, PATHINFO_BASENAME);
$name      = utf8_decode($name);
$ext       = pathinfo($url, PATHINFO_EXTENSION);

if( !isset($_GET['tn']) && $url !="/404.png" && !isset($_GET['imgedit']) ) {
  smpl_increment_view($path, $name);
}
//headers
$head     = _smpl_get_image_head($ext);
$size     = filesize($root.$url);
$filename = basename($url);
header("Cache-Control:max-age");
header("Content-disposition: inline; filename=".$filename );
header("Content-length: ".$size);
header($head);

$bufferlength = 4000000;
if($size<$bufferlength){
  echo file_get_contents($root.$url);
}else{
  $buf ='';
  $f = @fopen($root.$url,'rb');

  if($f){
	 $content ='';
	 while ( $content = @fread($f,$bufferlength) ) {
		  echo $content;
	 }
	 @fclose($f);
  }
die();
}
/**
 * Read data of a picture file
 */
function _smpl_get_image_head($ext){
	$ext = strtolower($ext);
  switch ($ext){
  	//Image mime types
  	case 'jpg':
    case 'jpeg':
    case 'tmp':
    	$type= 'image/jpeg';
    	break;
   	case 'gif':
   		$type= 'image/gif';
   		break;
   	case 'png':
   		$type= 'image/png';
   		break;
   	case 'bmp':
   		$type= 'image/bmp';
   		break;
   	case 'wbmp':
   		$type= 'image/wbmp';
   		break;
   	case 'wbmp':
   		$type= 'image/wbmp';
   		break;
   		//Audio mime types
   	case 'mp3':
   		$type= 'audio/mpeg';
   		break;
    case 'flac':
   			$type= 'audio/flac';
   			break;
   	case 'ogg':
   			$type= 'audio/ogg';
   			break;
		case 'kar':
   	case 'rmi':
   	case 'mid':
   		$type= 'audio/mid';
   		break;
   	case 'wav':
   		$type= 'audio/x-wav';
   		break;
   	case 'ra':
   		$type= 'audio/x-pn-realaudio';
   		break;
    //Video mime types
    case 'avi':
   		$type= 'video/x-msvideo';
   		break;
   		case 'wmv':
   			$type= 'video/x-ms-wmv';
   			break;
    case 'flv' :
   		$type= 'video/x-flv';
   		break;
    case 'mov' :		//QiuickTime
   		$type = 'video/quicktime';
   		break;
    case '3gp' :		//Mobil
   			$type = 'video/3gpp';
   			break;
    case 'mpg' :
    case 'mp2' :
    case 'mpe' :
    case 'mpeg':
    case 'mpv2':
   		$type = 'video/mpeg';
   		break;
   	case 'mp4':
   		$type = 'video/mp4';
   		break;

   	case 'm3u8': 		//iPhone
   			$type= 'application/x-mpegURL';
   			break;
   	case 'ts';		//iPhone
   		$type= 'video/MP2T';
   		break;
   	//Documents mime types
		case 'doc':
   		$type= 'application/msword';
   		break;
   	case 'docx':
   		$type= 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
   		break;
   	case 'xlsx':
   			$type= 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
   			break;
  	case 'xls':
   			$type= 'application/vnd.ms-excel';
   			break;
		case 'pps':
		case 'ppt' :
				$type = 'application/vnd.ms-powerpoint';
			break;
		case 'ppsx':
			$type= 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
			break;
		case 'pptx':
   		$type= 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
   		break;
   	case 'pdf':
   		$type='application/pdf';
   		break;
   	default:
   		$type = 'application/data';
  }
	return "Content-type: ".$type;
}

/**
 *
 * @param int $id
 * @return string $viewnumber - number of opened the view
 */
function smpl_increment_view( $path, $name ){
  $path .= substr($path,-1) !="/"? "/":"";
  $sql = "SELECT id, viewnumber FROM {smplphotoalbum} WHERE path = :path AND name =:name";
  $rs = db_query($sql, array(':path'=> $path, ':name' => $name));
  $db = $rs->rowCount();

  if($db<1){
    return '';
  }
  $record = $rs->fetchAssoc();

  $id = $record['id'];
  $db = (int) $record['viewnumber'];
  $db++;

  db_update('smplphotoalbum')
  ->fields(array('viewnumber' => $db))
  ->condition('id',$id,'=')
  ->execute();
  _smplphotoalbum_event($id, "view");
  return $db;
}