<?php
/**
 * every kind of functions
 */
// use \Drupal\user;
use Twig\Node\Expression\Binary\AndBinary;
define("TN", '_tn_/');
/**
 * Variables and default values
 * 
 * @return array
 */
 if(!defined("SMPLADMIN")){
	define ( "SMPLTEST", false );
	define ( 'SMPLADMIN', 'smpllist' ); // smpladmin
 }
function _smplphotoalbum_variables(){
  $index = [
  'number', // From D8 version
  'width' , // default width of images in px
  'sub'   ,   // subtitles: enable / disable 
  'root'  ,
  'viewed', // viewed counter. enable / disable
  'exif'  , // Exif informations: enable / disable
  'stat'  , // Statistics: enable / disable
  'smplbox',  // It helps to view the image in a lightbox,  
  
  'order'    , // viewing order
  'sortorder',
  'ascdesc'  ,
  'private'  ,
  'lazy'     ,
  // Check database
  'check'    ,
  'number_of_checking',
  'from',
  
  'keywords' ,
  // Subtitle auto change
  'subtitle_change' ,
  'subtitle_change_text',
  // Document extensions
  'image_extensions',
  'image_checking',
      
  'audio_checking',
  'audio_extensions',
  'audiohtml5_extensions',
  
  'video_checking',
  'video_extensions',
  'videohtml5_extensions',
  'html5_checking',
  
  'doc_checking' ,
  'doc_extensions',
  
  'cmp_checking' ,
  'cmp_extensions',
  
  'app_checking' ,
  'app_extensions',
  
  'oth_checking',
  'oth_extensions',
  
  'dis_checking' ,
  'dis_extensions',
  'url_checking',
  
  'button_pre' ,
  'button_post', // Buttons default characters
  'edit'       , // Edit description of files
  'delete'     , // Deletable files
  'imgedit'    , // Editable images
  'temp'       , // Temp. save edited files
  // Service
  'menu_rebuild_needed'      ,
  'menu_rebuild_directaccess',
  
  // URL for images
  'url'       ,
  'url_target',
  //For slideshow
  'slide_checking',
  'slide_extensions'
  ];
  $config = \Drupal::config('smplphotoalbum.settings');
  $a =[];
  foreach($index AS $i){
    $a[$i] = $config->get($i);
  }
  return $a;
}

/**
 *
 * @param $var -
 *          If it is an array then array (variable name => value)
 *          or it is variable name AndBinary
 *          $
 */
function smpl_set($var, $val = "") {
  $config = \Drupal::service('config.factory')->getEditable('smplphotoalbum.settings');  
  if (is_array ( $var )) {
    foreach ( $var as $i => $e ) {
      $config->set ( $i, $e )->save();
    }
  } else {
   $config->set ( $var, $val )->save();
  }
}
/**
 *
 * @param
 *          array / unknown $var - what we got
 * @param string $default
 * @return mixed[]|mixed - one value or array of values
 */
function smpl_get($var, $default = "") {
  // static $smpl = array();
  $config = \Drupal::config('smplphotoalbum.settings');

  $val = "";
  if (is_array ( $var )) {
    foreach ( $var as $name ) {
      $val [$name] = $config->get ( $var );
    }
  } else {
    $val = $config ->get ( $var ); 
    if($var =='subtitle_change_text'){
      $var = smpl_extensions_text();
    }
  }  
  return $val;
}

/*
 * entry, $image typ, $types...
 * return
 */
function _smpl_icon($entry, $type) {
  $path_parts = pathinfo ( $entry );
  $ext = (isset ( $path_parts ['extension'] )) ? strtolower ( $path_parts ['extension'] ) : "";
  $str = 'unknown';
  
  switch ($type) {
    case 'doc' :
      $str = smpl_is_doc ( $entry ) ? 'doc_' . $ext . ".png" : 'doc.png';
      break;
    case 'audiohtml5' :
      $str = smpl_is_audiohtml5 ( $entry ) ? 'audio_' . $ext . ".png" : 'audio.png';
      break;
    case 'audio' :
      $str = smpl_is_audio ( $entry ) ? 'audio_' . $ext . ".png" : 'audio.png';
      break;
    case 'videohtml5' :
      $str = smpl_is_videohtml5 ( $entry ) ? 'video_' . $ext . ".png" : 'video.png';
      break;
    case 'video' :
      $str = smpl_is_video ( $entry ) ? 'video_' . $ext . ".png" : 'video.png';
      break;
    case 'cmp' :
      $str = smpl_is_cmp ( $entry ) ? 'cmp_' . $ext . ".png" : 'cmp.png';
      break;
    case 'app' :
      $str = smpl_is_app ( $entry ) ? 'app_' . $ext . ".png" : 'app.png';
      break;
    case 'other' :
      $str = smpl_is_oth ( $entry ) ? 'oth_' . $ext . ".png" : 'other.png';
      break;
  }
  return $str;
}
/**
 * Tokens
 * 
 * @param string $p
 *          - path
 * @return mixed
 */
function smpl_root($p = '') {
  if (empty ( $p ) || strlen ( $p ) == 0) {
    $p = smpl_get ( 'root' );
  }
  if (! is_dir ( $p )) {
    $p = '__DRUPALROOT__';
  }
  if (substr ( $p, - 1 ) == "/") {
    $p = substr ( $p, 0, - 1 );
    smpl_set ( 'root', $p );
  }
  $p = str_replace ( "__DRUPALROOT__", DRUPAL_ROOT, $p );
  $p = str_replace ( "__DOCUMENT_ROOT__", $_SERVER ["DOCUMENT_ROOT"], $p );
  $p = str_replace ( '\\', '/', $p );
  return $p;
}

function _smpl_check_writeable($dir) {
  return is_writable ( $dir );
}

/**
 * Delete new lines from templates
 */
function nldel($s) {
  return $s;
  $s = str_replace ( chr ( 10 ), "", $s );
  $s = str_replace ( chr ( 13 ), "", $s );
  $s = str_replace ( "<br/>", "", $s );
  $s = str_replace ( "<br>", "", $s );
  $s = str_ireplace ( array (
      "\n",
      "\r" 
  ), array (
      "",
      "" 
  ), $s );
  return $s;
}
/**
 * check current user is administrator;
 */
function smplphotoalbum_access() {
  $roles = \Drupal::currentUser ()->getroles ();
  return in_array ( 'administrator', $roles ) ? true : false;
}

/**
 * Media EXIF adatok olvas�sa
 * 
 * @param unknown $p          
 * @param string $id          
 * @param number $verbose          
 * @return string
 */
function smpl_media_read($p, $id = '', $verbose = 0) {
  require_once drupal_get_path ( 'module', 'smplphotoalbum' ) . '/lib/getid3/getid3/getid3.php';
  
  // Initialize getID3 engine
  $getID3 = new getID3 ();
  
  // Analyze file and store returned data in $finfo
  $finfo = $getID3->analyze ( $p );
  
  getid3_lib::CopyTagsToComments ( $finfo );
  
  $out = "";
  $out .= '<table class="smpl_exif_table">';
  $out .= smpl_fx ( t ( 'Artist' ), @$finfo ['comments_html'] ['artist'] [0] ); // artist
  if (isset ( $finfo ['tags'] ['id3v2'] ['title'] [0] )) {
    $out .= smpl_fx ( t ( 'Title' ), $finfo ['tags'] ['id3v2'] ['title'] [0] );
  }
  
  if (isset ( $finfo ['video'] )) {
    $a = $finfo ['video'];
    $out .= smpl_fx ( '<b>' . t ( 'Video' ) . '</b>', ' ' );
    $out .= smpl_fx ( t ( 'Data format' ), @$a ['dataformat'] );
    $out .= smpl_fx ( t ( 'Bitrate mode' ), @$a ['bitrate_mode'] );
    $out .= smpl_fx ( t ( 'total frames' ), @$a ['total_frames'] );
    $out .= smpl_fx ( t ( 'Frame rate' ), @( int ) ($a ['frame_rate']) . "fps" );
    $out .= smpl_fx ( t ( 'Length' ), ( int ) (@($a ['total_frames'] / $a ['frame_rate'])), 'sec' );
    $out .= smpl_fx ( t ( 'Lossless' ), @$a ['lossless'] ); // true = lossless compression; false = lossy compression
    $out .= smpl_fx ( t ( 'Resolution X' ), @$a ['resolution_x'] ); // horizontal dimension of video/image in pixels
    $out .= smpl_fx ( t ( 'Resolution Y' ), @$a ['resolution_y'] ); // vertical dimension of video/image in pixels
    $out .= smpl_fx ( t ( 'Codec' ), @$a ['codec'] );
    $out .= smpl_fx ( t ( 'Pixel Aspect Ratio' ), @$a ['pixel_aspect_ratio'] ); // pixel display aspect ratio
  }
  
  if (isset ( $finfo ['audio'] )) {
    $a = $finfo ['audio'];
    $out .= smpl_fx ( '<b>' . t ( 'Audio' ) . '</b>', ' ' );
    $out .= smpl_fx ( t ( 'Playtime' ), $finfo ['playtime_string'], 'sec' ); // playtime in minutes:seconds, formatted string
    $out .= smpl_fx ( t ( 'Sample Rate' ), isset ( $a ['sample_rate'] ) ? number_format ( $a ['sample_rate'], 0, ".", " " ) . " Hz" : 'unknown' );
    $out .= smpl_fx ( t ( 'Bitrate' ), isset ( $a ['bitrate'] ) ? number_format ( $a ['bitrate'], 0, ".", " " ) . " bit/s" : 'unknown' );
    $out .= smpl_fx ( t ( 'Bitrate mode' ), isset ( $a ['bitrate_mode'] ) ? (($a ['bitrate_mode'] == "vbr") ? "variable bitrate" : "constant bitrate") : 'unknown' );
    $out .= smpl_fx ( t ( 'Bits per sample' ), isset ( $a ['bits_per_sample'] ) ? $a ['bits_per_sample'] : 'unknown' );
    $out .= smpl_fx ( t ( 'Channelmode' ), isset ( $a ['schannelmode'] ) ? $a ['schannelmode'] : 'unknown' );
    $out .= smpl_fx ( t ( 'Channels' ), isset ( $a ['channels'] ) ? $a ['channels'] : 'unknown' );
    $out .= smpl_fx ( t ( 'Codec' ), isset ( $a ['codec'] ) ? $a ['codec'] : 'unknown' );
    $out .= smpl_fx ( t ( 'Compression ratio' ), isset ( $a ['compression_ratio'] ) ? sprintf ( "%02.3f", $a ['compression_ratio'] ) : 'unknown' );
    $out .= smpl_fx ( t ( 'Compression mode' ), isset ( $a ['lossless'] ) ? $a ['lossless'] : 'unknown' );
    $out .= smpl_fx ( t ( 'Dataformat' ), isset ( $a ['dataformat'] ) ? $a ['dataformat'] : 'unknown' );
    $out .= smpl_fx ( t ( 'Encoder' ), isset ( $a ['encoder'] ) ? $a ['encoder'] : 'unknown' );
  }
  $out .= '</table>';
  return $out;
}

/**
 * doc, docx, txt, ppt, pptx file properties reading
 * 
 * @param unknown $p          
 * @param string $id          
 * @param number $verbose          
 * @return string
 */
function smpl_doc_read($p, $id = '', $verbose = 0) {
  $out = '';
  return $out;
}

/**
 * xls and xlsx file properties reading
 * 
 * @param unknown_type $p          
 * @param unknown_type $id          
 * @param unknown_type $verbose          
 */
function smpl_excel_read($p, $id = '', $verbose = 0) {
  require_once drupal_get_path ( 'module', 'smplphotoalbum' ) . '/lib/PHPExcel/Classes/PHPExcel.php';
  $a = pathinfo ( $p );
  if (strtolower ( $a ['extension'] ) == 'xls') {
    $oR = new PHPExcel_Reader_Excel5 ();
  } elseif (strtolower ( $a ['extension'] ) == 'xlsx') {
    $oR = new PHPExcel_Reader_Excel2007 ();
  }
  try {
    $o = $oR->load ( $p );
    $creator = $o->getProperties ()->getCreator ();
    $crDatestamp = $o->getProperties ()->getCreated ();
    $crdatetime = date ( 'Y:m:d H:i:s', $crDatestamp );
    
    $modifiedBy = $o->getProperties ()->getLastModifiedBy ();
    $modDatestamp = $o->getProperties ()->getModified ();
    $moddatetime = date ( 'Y:m:d H:i:s', $modDatestamp );
    $title = $o->getProperties ()->getTitle ();
    $desc = $o->getProperties ()->getDescription ();
    $subj = $o->getProperties ()->getSubject ();
    $comp = $o->getProperties ()->getCompany ();
    $manager = $o->getProperties ()->getManager ();
  } catch ( Exception $e ) {
    $creator = 'unknown';
    $crDatestamp = filemtime ( $p );
    // smpl_test($p);
    $crdatetime = date ( 'Y.m.d H:i:s', filemtime ( $p ) );
    
    $modifiedBy = $crdatetime;
    $modDatestamp = $crdatetime;
    $moddatetime = $crdatetime;
    $title = basename ( $p );
    $desc = 'unknown';
    $subj = 'unknown';
    $comp = 'unknown';
    $manager = 'unknown';
  }
  
  $out = "\n";
  $out .= "    <table class='smpl_exif_table'>\n";
  $out .= smpl_fx ( t ( 'Creator' ), $creator );
  $out .= smpl_fx ( t ( 'Created on' ), $crdatetime );
  $out .= smpl_fx ( t ( 'Last Modified by' ), $modifiedBy );
  $out .= smpl_fx ( t ( 'Modified on' ), $moddatetime );
  $out .= smpl_fx ( t ( 'Title' ), $title );
  $out .= smpl_fx ( t ( 'Title' ), $desc );
  $out .= smpl_fx ( t ( 'Subject' ), $subj );
  $out .= smpl_fx ( t ( 'Company' ), $comp );
  $out .= smpl_fx ( t ( 'Manager' ), $manager );
  $out .= "\n";
  $out .= "    </table>\n";
  return $out;
}

function smpl_file_get_mimetype($filename) {
  return mime_content_type ( $filename );
}

/**
 * Checktypes:image, Video, Audio, Doc, app, compressed, disabled, docs, other
 * 
 * @param unknown $entry          
 */
function smpl_is_image($entry) {
  $ext  = pathinfo ( $entry, PATHINFO_EXTENSION );
  $type = smpl_get('image_extensions'); 
  
  $p = stripos (" ".$type, $ext );  
  return $p !== false;  
}
// audio extensions
function smpl_is_audio($entry) {
  return smpl_in_type ( $entry, 'audio_extensions');
}

// audio extensions
function smpl_is_audiohtml5($entry) {
  return smpl_in_type ( $entry, 'audiohtml5_extensions' );
}

// video extensions
function smpl_is_video($entry) {
  return smpl_in_type ( $entry, 'video_extensions');
}
// videohtml5 extensions
function smpl_is_videohtml5($entry) {
  return smpl_in_type ( $entry, 'videohtml5_extensions' );
}

// documents extensions
function smpl_is_doc($entry) {
  return smpl_in_type ( $entry, 'doc_extensions');
}

// compressed files
function smpl_is_cmp($entry) {
  return smpl_in_type ( $entry, 'cmp_extensions');
}
// application extensions
function smpl_is_app($entry) {
  return smpl_in_type ( $entry, 'app_extensions');
}

// other but enabled extensions
function smpl_is_oth($entry) {  
  return smpl_in_type ( $entry, 'oth_extensions' );
}

// disabled extensions
function smpl_is_dis($entry) {  
  return smpl_in_type ( $entry, 'dis_extensions');
}

/**
 * Is in the types
 * 
 * @param string $entry          
 * @param unknown $types          
 * @return boolean
 */
function smpl_in_type($entry, $type) {
  $ext = pathinfo ( $entry, PATHINFO_EXTENSION );
  $type = smpl_get($type);  
  return stripos ( $type, $ext ) !== false;
}
/**
 * All of the extensions of smplphotoalbum
 * 
 * @return array
 */
function smpl_extensions() {
  $a =[];
  $a = array_merge($a, explode(" ",smpl_get('image_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('audio_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('audiohtml5_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('video_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('videohtml5_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('doc_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('cmp_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('app_extensions')));
  $a = array_merge($a, explode(" ",smpl_get('oth_extensions')));
  return array_unique($a);
}

function smpl_extensions_text(){
  $b = smpl_extensions();
  return implode(" ", $b);
}
function smpl_ext_replace($str, $r = "") {
  return str_ireplace ( smpl_extensions (), $r, $str );
}

/**
 * type of entry
 * 
 * @param unknown $entry          
 * @return string|boolean
 */

function smpl_type($entry) {
  if (smpl_is_image ( $entry ))
    $type = "image";
  elseif (smpl_is_audiohtml5 ( $entry ))
    $type = "audiohtml5";
  elseif (smpl_is_audio ( $entry ))
    $type = "audio";
  elseif (smpl_is_videohtml5 ( $entry ))
    $type = "videohtml5";
  elseif (smpl_is_video ( $entry ))
    $type = "video";
  elseif (smpl_is_doc ( $entry ))
    $type = "doc";
  elseif (smpl_is_cmp ( $entry ))
    $type = "cmp";
  elseif (smpl_is_app ( $entry ))
    $type = "app";
  elseif (smpl_is_oth ( $entry ))
    $type = "oth";
  elseif (smpl_is_dis ( $entry ))
    $type = "dis";
  else
    $type = "dis";
  return $type;
}
function smpl_inittypes() {
  $this->OthArray = explode ( ' ', str_replace ( ',', ' ', str_replace ( '.', '', \Drupal::state ()->get ( 'smplphotoalbum.oth_checking', 0 ) ) ) );
}