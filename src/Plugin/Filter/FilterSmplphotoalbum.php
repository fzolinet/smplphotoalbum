<?php

namespace Drupal\smplphotoalbum\Plugin\Filter;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\smplphotoalbum\ImageList;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * @Filter(
 * id = "filter_smplphotoalbum",
 * title = @Translation("Simple Photoalbum Filter"),
 * description = @Translation("This filter makes a photoalbum from files of directory!"),
 * type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterSmplphotoalbum extends FilterBase {
  private $mp;
  private $params = [];  
  private $cfg;
  private $sess;
  private $ts;
  private $rq;
  
  /**
   * filter process
   * @param string $text 
   * @param string $langcode 
   * @return FilterProcessResult - the result of filter
   * @throws ContainerNotInitializedException 
   * @throws ServiceCircularReferenceException 
   * @throws ServiceNotFoundException 
   * @throws SessionNotFoundException 
   * @throws BadRequestException 
   * @throws InvalidArgumentException 
   */
  public function process($text, $langcode) {
    // global $base_root;    

    $this->cfg  = \Drupal::config ( 'smplphotoalbum.settings' );
    $this->rq   = \Drupal::request();
    $this->sess = $this->rq->getSession ();
    $this->ts   = $this->sess->get("smpl");

    // Is there on the page photoalbum? I give the other part of the module
    $GLOBALS["smplphotoalbum"] = False;

    // Old style
    $ml01 = [];
    $ml02 = []; 
    $ml03 = [];
    $minta01 = "/\[smpl\|[^]]*\]/simx"; 
    $minta02 = "/\{smpl\|[^]]*\}/simx";
    $minta03 ="/[\[\{]smplphotoalbum[\]\}].*[\[\{]\/smplphotoalbum[\]\}]/simx";
    $is_img01 = preg_match( $minta01, $text, $ml01 );
    $is_img02 = preg_match( $minta02, $text, $ml02 );
    $is_img03 = preg_match( $minta03, $text, $ml03 ); // New Style
         
    if( !($is_img01 > 0 || $is_img02 > 0 || $is_img03 > 0 ) ){ 
      return new FilterProcessResult ( $text );
    }

    //------------------   
    if( $is_img01 > 0 ){      
      $match = str_ireplace( [ '[smpl|' , ']' ] ,"", $ml01[0] );              
      $this->ParamsInit();
      $match = str_replace ( "\\", "/", $match );        
      $match = (substr ( $match, 0, 1 ) != "/" ? "/" : "") . $match . (substr ( $match, 0, - 1 ) != "/" ? "/" : "");
      $this->params ['path'] = $match;

    } else if( $is_img02 > 0 ){      
      $match = str_ireplace( [ '{smpl|' , '}' ] ,"", $ml02[0] );              
      $this->ParamsInit();
      $match = str_replace ( "\\", "/", $match );        
      $match = (substr ( $match, 0, 1 ) != "/" ? "/" : "") . $match . (substr ( $match, 0, - 1 ) != "/" ? "/" : "");
      $this->params ['path'] = $match;
    
    } else if ( $is_img03 > 0 ){
      $match = $ml03 [0];
      $match = strip_tags( $match );
      $this->ParamsInit();      
      $this->ParamsChange( $match ); 
    } 
    
    $GLOBALS["smplphotoalbum"] = True;

    // is error in root checking
    $msg = $this->RootCheck();
    if (strlen ( $msg ) > 0) {
      return new FilterProcessResult ( $text );
    }
    
    // get order parameters from form
    $smpl_sortorder = $this->Request('smpl_sortorder');
    $smpl_ascdesc = $this->Request('smpl_ascdesc');
    if (!empty( $smpl_sortorder )  && !empty( $smpl_ascdesc ) ) {
      $this->params ['sortorder'] = $smpl_sortorder;
      $this->params ['ascdesc']   = $smpl_ascdesc;
    }

    $smplpage = $this->Request('smpl_ascdesc');
    if( !empty( $smplpage ) ){
      $this->params['smplpage'] = (int) $smplpage;
    }

    //Graphic driver handling    
    if(isset($this->params["graphicdrv"]) ){

      if( $this->params["graphicdrv"]== "imagick" && extension_loaded ( "imagick" )) {
        $this->params["graphicdrv"] = "imagick";
      }else{
        $this->params["graphicdrv"] = "gd";
      }
      //
      $this->sess->set('graphicdrv', $this->params["graphicdrv"]);      
    }

    //
    if($this->params['test'] == true){      
      $this->sess->set('params', $this->params);
    }

    //alpha handling
    if( isset( $this->params['wm'] ) && $this->params['wm'] ){
      $this->ts["wm"]       = $this->params["wm"];
      $this->ts["wmpath"]   = $this->params["wmpath"];
      $this->ts["wmalpha"]  = $this->params["wmalpha"];
      $this->ts["copyright"]= $this->params["copyright"];
      $this->ts["author"]   = $this->params["author"];
    }else{
      $this->ts["wm"]       = false;
      $this->ts["wmpath"]   = '';
      $this->ts["wmalpha"]  = 10;
      $this->ts["copyright"]= '';
      $this->ts["author"]   = '';
    }

    $this->sess->set('smpl', $this->ts);

    $ImgList = new ImageList( $this->params );
    
    if ( $ImgList->getSlide()) {
      $out = $ImgList->SlideShow();
    } else {
      $out = $ImgList->Render();
    }

    if( $is_img01 > 0 ){
      $text = preg_replace( $minta01, $out, $text, 1 );
    }
    
    if($is_img02 > 0){
      $text = preg_replace( $minta02, $out, $text, 1 );
    }

    if ($is_img03 > 0) {
      $text = preg_replace( $minta03, $out, $text, 1 );
    }

    $result = new FilterProcessResult ( $text );

    $lib = [ 'smplphotoalbum/smplphotoalbum' ];
    
    if( $ImgList->getSlide() ){
      
      $lib[] = "smplphotoalbum/smplphotoalbum-slide";

    }else if( $this->smplphotoalbum_access() ) {
      
      $lib[] = 'smplphotoalbum/smplphotoalbum-edit';

    }

    // AI using
    if( $this->params["aiclarifai"] || $this->params["aigemini"] ){
      $lib[] = "smplphotoalbum/smplphotoalbum-ai";
    }

    $result->setAttachments ( ['library' => $lib ]);
    $result->setCacheMaxAge(0); // do not use cache for this
    return $result;
  }

  /**
   * The root checking
   *
   * @return string $msg
   */
  function RootCheck() { 
    $p = \Drupal::service( 'file_system' )->realpath( "public://" );
    $root = str_replace( "public://", $p."/", $this->params["root"] );
    $root = $this->slash( $root );
    
    $msg = ( strlen( $this->params["root"] ) < 1) ? $this->t ( 'The main folder of Simple Photoalbum has to set! Please fix it in /admin/config/fz/smplphotoalbum!' ) : '';
    $msg .= ( $this->params["root"] == "/" || substr ( $root, 2 ) == ":/") ? $this->t ( 'Are you sure, the main folder of Simple Photoalbum is equal the server root?' ) : '';
    $msg .= substr( $this->params["root"], - 1, 1 ) == "/" ? $this->t ( "Simple Photoalbum Main folder must not end with '/'" ) : '';
    $msg .= ! is_dir( $this->params["root"] ) ? 'There is not the smplphotoalbum root folder' : '';

    if (strlen ( $msg ) > 0) {
      $msg = $msg . $this->t ( ' Please fix it in /admin/config/fz/smplphotoalbum' );
      \Drupal::messenger()->addMessage( $msg, 'error' );
      \Drupal::logger( 'smplphotoalbum' )->error( $msg );
    }
    return $msg;
  }

	/**
	 * it makes slash from backslash or double slash
	 * @param mixed $p 
	 * @return string|string[] 
	 */
	public function slash($p){
		return str_replace(["\\","//"],'/',$p);
	}
  /**
   * Set default parameters to use in module
   *   
   * @return 
   */
  function ParamsInit() {    
    $this->params = $this->getConfig();
    $this->params['modulepath'] = \Drupal::service ( 'module_handler' )->getModule ( 'smplphotoalbum' )->getPath ();
    $this->params['title']    = '';
    $this->params['notes']    = '';
    $this->params['slide']    = false;
    $this->params['interval'] = 10;  //sec
    $this->params['style']      = 'none';
    $this->params['slidestyle'] = 'none';
    $this->params['translate']  = false;
    $this->params['lang']       = 'en';
    $this->params['methods']    = 'POST';
    $this->params['folders']    = ($this->params['folders'] === 1 ? true: false);
    
    if (! isset ( $this->params['icon'] )) {
      $this->params['icon'] = '_col';
    }
    $this->params['app_extensions']        = " " . $this->params ['app_extensions']." ";
    $this->params['audio_extensions']      = " " . $this->params ['audio_extensions']." ";
    $this->params['audiohtml5_extensions'] = " " . $this->params ['audiohtml5_extensions']." ";
    $this->params['cmp_extensions']        = " " . $this->params ['cmp_extensions']." ";
    $this->params['dis_extensions']        = " " . $this->params ['dis_extensions']." ";
    $this->params['doc_extensions']        = " " . $this->params ['doc_extensions']." ";
    $this->params['image_extensions']      = " " . $this->params ['image_extensions']." ";
    $this->params['oth_extensions']        = " " . $this->params ['oth_extensions'] ." ";
    $this->params['video_extensions']      = " " . $this->params ['video_extensions']." ";
    $this->params['videohtml5_extensions'] = " " . $this->params ['videohtml5_extensions']." ";
    $this->params["aiclarifai"]            = $this->params["aiclarifai"] && extension_loaded("curl") && extension_loaded("grpc");    
    $this->params["aigemini"]              = $this->params["aigemini"];    
  }

  /**
   * Parameter is change from page
   * @param mixed $t - Property
   * @return void 
   */
  function ParamsChange($t) {
    $a = [        
        'aiclarifai',
        'aigemini',
        'app',
        'ascdesc',
        'audio',
        'author',
        'autoclose',
        'capt',
        'cmp',
        'copyright',
        'doc',
        'edit',
        'exif',
        'filter',
        'folders',
        'graphicdrv',
        'graphic',  
        'html5',
        'icon',
        'important',       
        'interval',
        'lang',
        'method',
        'newfolder',
        'notes',
        'number',
        'order',
        'path',
        'slide',
        'slide_checking',
        'slide_extensions',        
        'slidestyle',
        'sortorder',
        'stat',
        'style',
        'sub',
        'target',
        'test',
        'title',
        'translate',
        'upload',
        'url',
        'video',
        'viewed',
        'width',
        'wm',
        'wmalpha',
        'wmpath',
    ];

    $m = [];
    foreach ( $a as $e ) {
      $e = strtolower ( $e );
      $minta = '/{' . $e . ':[^}]*}/simx';

      $is = preg_match ( $minta, $t, $m );
      if ($is > 0) {

        $v = trim ( str_ireplace ( ["{" . $e . ":","}" ], ["",""], $m [0] ) );
        switch ($e) {
          case 'app'      : $this->params ['app']       = $v; break;
          case 'ascdesc'  : $this->params ['ascdesc']   = $v; break;
          case 'audio'    : $this->params ['audio']     = $v; break;          
          case 'author'   : $this->params ['author ']   = trim($v); break;
          case 'autoclose': $this->params ['autoclose'] = $this->truefalse($v); break;          
          case 'capt'     : $this->params ['capt']      = $v; break;
          case 'cmp'      : $this->params ['cmp']       = $v; break;                    
          case 'copyright': $this->params ['copyright'] = trim($v); break;          
          case 'doc'      : $this->params ['doc']       = $v; break;
          case 'edit'     : $this->params ['edit']      = $v; break;          
          case 'exif'     : $this->params ['exif']      = $v; break;
          case 'filter'   : $this->params ['filter']    = $this->truefalse($v); break;
          case 'folders'  : $this->params ['folders']   = $this->truefalse($v); break;
          case 'graphicdrv': //GD or Imagick
          case "graphic":
          case "grdrv":
            $v = trim(strtolower($v));
            $this->params['graphicdrv'] = "gd";
            if(!empty($v) && strpos(" ".$v, "imagick") >0 ){
              if(extension_loaded("imagick")){
                $this->params['graphicdrv'] = "imagick";
              }
            }            
            break;
          case 'height'   : $this->params['height'] = $v; break;
          case 'html5'    : $this->params ['html5'] = $this->truefalse($v); break;
          // icon color or black & white
          case 'icon' :
            $v = strtolower( trim( $v ) );
            if(in_array($v,['bw','_bw','blackandwhite','black&white','b&w'])){
              $this->params ['icon'] ='_bw';
            }else{
              $this->params ['icon'] ='_col';
            }
            break;
          case 'important': $this->params ['important']= $this->truefalse($v); break;
          case 'interval' : $this->params ['interval'] = $v; break;
          case 'keywords' : $this->params ['keywords'] = $v; break;
          case 'lang'     : $this->params ['lang']     = $v; break;
          case 'lazy'     : $this->params ['lazy']     = $this->truefalse($v); break;
          case "method"   : $this->params ['method']   = ( strtolower($v) == "get")? "GET" : "POST"; break;
          case 'notes'    : $this->params ['notes']    = $v; break;
          case 'number'   : $this->params ['number']   = (int)($v); break;
          case 'order'    : $this->params ['order']    = $v; break;
          // path of folder from photoalbum folder
          case 'path' :
            $v = str_replace ( "\\", "/", $v );
            $v = (substr ( $v, 0, 1 ) != "/" ? "/" : "") . $v . (substr ( $v, 0, - 1 ) != "/" ? "/" : "");
            $this->params ['path'] = $v;
            break;
          case 'private'   : $this->params ['private'] = $v; break;    // private store          
          // is this slideshow
          case 'slide'     : $this->params ['slide']     = $this->truefalse($v); break;
          // style of slide
          case 'slidestyle': $this->params ['slidestyle']= $v; break;
          // style of smplbox
          case 'smplbox'   : $this->params ['smplbox']   = $v; break;
          // default sortorder
          case 'sortorder' : $this->params ['sortorder'] = $v; break;
          // is there statistic
          case 'stat'      : $this->params ['stat']      = $v; break;
          // style of album
          case 'style'     : $this->params ['style']     = $v; break;
          // is there subtitles
          case 'sub'       : $this->params ['sub']       = $v; break;
          // target of url
          case 'target'    : $this->params ['target']    = $v; break;
          case 'test'      : $this->params ['test']      = $this->truefalse($v); break;
          // Title of album
          case 'title'     : $this->params ['title']     = !empty(trim ($v)) ? trim($v):""; break;
          // Is there translation
          case 'translate' : $this->params ['translate'] = $this->truefalse($v); break; 
          // Is there file upload
          case 'upload'    : $this->params ['upload']    = $this->params['upload'] && $this->truefalse( $v ); break;
          // Is there url
          case 'url '      : $this->params ['url']       = $this->truefalse($v); break;
          case 'video'     : $this->params ['video']     = $v; break;
          // show the vieved number of items
          case 'viewed'    : $this->params ['viewed']    = $v; break;
          // width of container of items
          case 'width'     : $this->params ['width']     = (int)($v); break;
          // is there watermark
          case 'wm'        : $this->params ['wm']        = ($this->truefalse($v))? 1:0; break;
          // path of default watermark image
          case 'wmpath'    : $this->params ['wmpath']    = $v; break;
          // alpha of watermark image
          case 'wmalpha'   : $this->params ['wmalpha']   = ($v >0 && $v < 100 ) ? (int) $v : 10; break;    
        }
      }
    }
  }

  /**
   * Give back True/false
   * @param string $v
   * @return boolean
   */
  function truefalse($v){
    $v = strtolower( trim( $v ) );
    $out = in_array ( $v, [ true, 'true', 'TRUE', 'True' , 1, '1', 'on'] ) ? true : false;
    return $out;
  }

  /** 
   * Read config variables
   * @return array
   */
  private function getConfig() {
    $index = [
      // ai - this is in the config
      'aiclarifai',
      'aigemini',
      'number', // How many item are in a page
      'width',  // default width of images in px
      'sub',    // subtitles: enable / disable
      'root',   // root folder of smplphotoalbum 
      'viewed', // viewed counter. enable / disable
      'exif',   // Exif informations: enable / disable
      'stat',   // Statistics: enable / disable
      'smplbox',// It helps to view the image in a lightbox,
      'method', // The method of smpl form

      // sorting
      'order',  // viewing order
      'sortorder',
      'ascdesc', // Ascending / Descending order
      'important', // Important items always on the top
      'filter', // filter of items
      'folders', // folder view enable / disable

      'private',
      'lazy', // lazy loading of images

      // Check database
      'check',
      'number_of_checking',
      'from',
      'keywords',

      // Subtitle auto change
      'subtitle_change',
      'subtitle_change_text',
      
      // Document extensions
      'image_extensions', // it is a list of extensions for images
      'image_checking',

      'audio_checking',
      'audio_extensions',
      'audiohtml5_extensions',

      'video_checking',
      'video_extensions',
      'videohtml5_extensions',
      'html5_checking',

      'doc_checking',
      'doc_extensions',

      'cmp_checking',
      'cmp_extensions',

      'app_checking',
      'app_extensions',

      'oth_checking',
      'oth_extensions',

      'dis_checking',
      'dis_extensions',
      'url_checking',

      'edit',        // Edit description of files
      'upload',      // Uploadable files
      'delete',      // Deletable files
      'imgedit',     // Editable images
      'temp',        // Temp. save edited files
      
      // Service
      'menu_rebuild_needed',
      'menu_rebuild_directaccess',

      // URL for images
      'url',
      'url_target',
      
      // Slideshow
      'slide_checking',
      'slide_extensions',
      'interval',
      'style',
      'slstyle',
      'graphicdrv',
      'autoclose',
      
      //Watermark
      'wm',
      'wmpath',
      'wmalpha',
      'copyright',
      'author',
      'icon',

      //testing
      'test',
      
      //translation
      'translate',
      'lang',
    ];
    $config = \Drupal::config ( 'smplphotoalbum.settings' );
    $cfg = [];

    foreach( $index as $i ) {
      $cfg[$i] = $config->get( $i );
    }
    return $cfg;
  }
  
  //getparameters
  public function getParams(){
    return $this->params;
  }

  // Get config
  public function getCfg(){
    return $this->cfg;
  }

  /**
   * Drupal Request
   * @param string $cmd
   * @param string $default
   * @return string
   */
  private function Request($cmd, $default = '') {
    $g = $this->rq->query->get( $cmd );
    if ($g == "undefined")
      $g = $default;
    if (isset ( $g ))
      return $g;

    $r = $this->rq->request->get( $cmd );
    if (isset ( $r ))
      return $r;
    return $default;
  }

  /**
   * Can you acces the smplphotoalbum
   * @return bool 
   * @throws ContainerNotInitializedException 
   * @throws ServiceCircularReferenceException 
   * @throws ServiceNotFoundException 
   */
  function smplphotoalbum_access() {
		$roles = \Drupal::currentUser()->getroles();
		return in_array( 'administrator', $roles ) ? true : false;
	}
}
