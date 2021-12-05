<?php

namespace Drupal\smplphotoalbum\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\smplphotoalbum\ImageList;

require_once drupal_get_path ( 'module', 'smplphotoalbum' ) . '/src/functions.php';

/**
 * @Filter(
 * id = "filter_smplphotoalbum",
 * title = @Translation("Simple Photoalbum Filter"),
 * description = @Translation("This filter makes a photoalbum from files of directory!"),
 * type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterSmplphotoalbum extends FilterBase {
  public $params = array();
  protected $renderer;
  public $cfg;
  
  public function process($text, $langcode) {
    // global $base_root;      
    static $r = 0;
    $r++;
    $this->cfg = \Drupal::config('smplphotoalbum.settings');     
    $ml = array();    
    $is_img = 0;
    $is_img_new = 0;
    $s_minta = "/\[smplphotoalbum\].*\[\/smplphotoalbum\]/simx";
    $is_img_new = preg_match ( $s_minta, $text, $ml ); // Gallery from folder

    if ($is_img_new > 0) {
      $match = $ml [0];
      $match = strip_tags( $match );
      $this->params_init();
      // Modify the params with the actual attributes
      $this->params_change( $match );
    } else {      
      $is_img = preg_match( "/\[smpl\|[^]]*\]/smx", $text, $ml ); // Gallery from folder
                                                                  // Initialize the actual parameters
      if ($is_img == 0) {
        return new FilterProcessResult( $text );
      }
      
      $match = $ml[0];
      
      // clean the match      
      $match = str_replace( [ "[","]" ], '', $match );
      $e = explode('|',$match);
      if(isset($e[1])){
        $match = $e[1];
      }
      $match = $match . (substr ( $match, 0, - 1 ) != "/" ? "/" : "");      
      $this->params_init();
      // Modify the params with the actual attributes
      $this->params["path"] = $match;      
    }
    
    // is error in root checking
    $msg = $this->root_checking();    
    if (strlen( $msg ) > 0) {
      return new FilterProcessResult( $text );
    }
    
    // get order parameters from form
    if (isset ( $_REQUEST ['smpl_sortorder'] ) && isset ( $_REQUEST ['smpl_ascdesc'] )) {
      $this->params ['sortorder'] = $_REQUEST ['smpl_sortorder'];
      $this->params ['ascdesc'] = $_REQUEST ['smpl_ascdesc'];
    }
    //
    $ImgList = new ImageList( $this->params );    
    if($this->params['slide']){
      $out = $ImgList->SlideShow();     
    }
    else{
      $out = $ImgList->Render();
    }
    if ($is_img > 0) {
      $text = preg_replace( "/\[smpl\|[^]]*\]/simx", $out, $text, 1 );
    } else if ($is_img_new > 0) {
      $text = preg_replace( "#\[smplphotoalbum\].*\[\/smplphotoalbum\]#simx", $out, $text, 1 );
    }
    $result = new FilterProcessResult( $text );
    
    $result->setAttachments ( array (
        'library' => array (
            'smplphotoalbum/smplphotoalbum' 
        ) 
    ) );
    
    return $result;
  }
  
  /**
   * The root checking
   * 
   * @param unknown $root          
   */
  function root_checking() {    
    $root = str_replace ( '\\', '/', $this->params["root"] );
    $root = str_replace("public://", \Drupal::service('file_system')->realpath("public://"), $this->params["root"]);
    $msg = '';
    $msg .= (strlen ( $this->params["root"] ) < 1) ? t ( 'The main folder of Simple Photoalbum has to set! Please fix it in /admin/config/fz/smplphotoalbum!' ) : '';
    $msg .= ($this->params["root"] == "/" || substr ( $root, 2 ) == ":/") ? t ( 'Are you sure, the main folder of Simple Photoalbum is equal the server root?' ) : '';
    $msg .= substr ( $this->params["root"], - 1, 1 ) == "/" ? t ( "Simple Photoalbum Main folder must not end with '/'" ) : '';
    $msg .= ! is_dir ( $this->params["root"] ) ? 'There is not the smplphotoalbum root folder' : '';
    
    if (strlen ( $msg ) > 0) {
      $msg = $msg . t ( ' Please fix it in /admin/config/fz/smplphotoalbum' );
      \Drupal::messenger()->addMessage($msg, 'error' );
      \Drupal::logger ( 'smplphotoalbum' )->error ( $msg );
    }
    return $msg;
  }
  
  /**
   * Set default parameters to use in module
   * 
   * @param unknown $params          
   */
  function params_init() {
    global $base_url;
    $this->params = $this->variables();      
    $mp = \Drupal::service ( 'module_handler' )->getModule ( 'smplphotoalbum' )->getPath ();
    $this->params ['modulepath']  = $mp;
    $this->params['title']        = '';
    $this->params['slide']        = false;
    $this->params['interval']     = 5000;
    $this->params['style']        = 'none';
  }
  
  function params_change($t) {
    $a = array (
        'path',
        'number',
        'width',
        'sub',
        'order',
        'sortorder',
        'ascdesc',
        'capt',
        'viewed',
        'edit',
        'exif',
        'stat',
        'audio',
        'video',
        'doc',
        'cmp',
        'app',
        'url',
        'target',
        'html5',
        'title',
        'slide',
        'slide_checking',
        'slide_extensions',
        'interval',
        'style'
    );
    
    $m = array ();    
    foreach ( $a as $e ) {
      $e = strtolower($e);
      $minta = '/{' . $e . ':[^}]*}/simx';
      
      $is = preg_match ( $minta, $t, $m );
      if ($is > 0) {
        $v = trim( str_ireplace( array( "{" . $e . ":", "}" ), array( "", "" ), $m [0]) );
        switch ($e) {
          case 'path' :
            $v = str_replace ( "\\", "/", $v );
            $v = (substr ( $v, 0, 1 ) != "/" ? "/" : "") . $v . (substr ( $v, 0, - 1 ) != "/" ? "/" : "");
            $this->params ['path'] = $v;
            break;          
          case 'number'   : $this->params['number']    = (int) ($v); break;
          case 'width'    : $this->params['width']     = (int) ($v); break;
          case 'sub'      : $this->params['sub']       = $v; break;
          case 'order'    : $this->params['order']     = $v; break;
          case 'sortorder': $this->params['sortorder'] = $v; break;
          case 'ascdesc'  : $this->params['ascdesc']   = $v; break;
          case 'capt'     : $this->params['capt']      = $v; break;
          case 'viewed'   : $this->params['viewed']    = $v; break;
          case 'smplbox'  : $this->params['smplbox']   = $v; break;
          case 'edit'     : $this->params['edit']      = $v; break;
          case 'exif'     : $this->params['exif']      = $v; break;
          case 'stat'     : $this->params['stat']      = $v; break;
          case 'private'  : $this->params['private']   = $v; break;
          case 'keywords' : $this->params['keywords']  = $v; break;
          case 'audio'    : $this->params['audio']     = $v; break;
          case 'video'    : $this->params['video']     = $v; break;
          case 'doc'      : $this->params['doc']       = $v; break;
          case 'cmp'      : $this->params['cmp']       = $v; break;
          case 'app'      : $this->params['app']       = $v; break;
          case 'url '     : $this->params['url']       = (in_array( $v, array (true,'true','True','TRUE',1,'1')) ? true : false); break;
          case 'target'   : $this->params['target']    = $v; break;
          case 'lazy'     : $this->params['lazy']      = (in_array( $v, array ( true, 'true', 'True', 'TRUE', 1, '1' )) ? true : false); break;
          case 'html5'    : 
            $h = array( true, 'true', 'True', 'TRUE', 1, '1', 'on' ); 
            $this->params ['html5'] = (in_array( $v, $h, True ) ? 1 : 0); 
            break;
          case 'title'    : $this->params['title']      = !empty(trim($v)) ? trim($v) : "" ; break;
          case 'slide'    : $this->params['slide']      = (in_array( $v, array (true,'true','True','TRUE',1,'1')) ? true : false); break;
          case 'interval' : $this->params['interval']  = $v; break;
          case 'style'    : $this->params['style']     = $v; break;
        }
      }
    }
  }
  
  private function variables(){
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
        // Slideshow
        'slide_checking',
        'slide_extensions',
        'interval',
        'style'
    ];
    $config = \Drupal::config('smplphotoalbum.settings');
    $a =[];
    foreach($index AS $i){
      $a[$i] = $config->get($i);
    }
    return $a;
  }
}