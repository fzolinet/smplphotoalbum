<?php

/**
 * @file
 * Contains Drupal\smplphotoalbum\Form\MessagesForm.
 */
namespace Drupal\smplphotoalbum\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File;

require_once drupal_get_path('module', 'smplphotoalbum') . '/src/functions.php';

class SettingsForm extends ConfigFormBase {
   private $moduleHandler;  
  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'),
        $container->get('module_handler')
        );
  }
  
  /**
   *
   * {@inheritdoc}
   *
   */
  protected function getEditableConfigNames() {
    return [ 
        'smplphotoalbum.settings' 
    ];
  }
  
  /**
   * Return the unique form ID
   * 
   * {@inheritdoc}
   *
   */
  public function getFormId() {
    return 'smplphotoalbum_form';
  }
  
  /**
   * Make Admin form
   * returns the form array
   * 
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cfg = $this->configFactory->get('smplphotoalbum.settings');
    // If I want to config this server then I have to use state!
    $form = [ ];
    
    $form["default"] = [ 
        '#type' => 'fieldset',
        '#title' => t ( 'Default settings' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE 
    
    ];
    $form["default"] ['number'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'Maximum item on one page' ),
        '#default_value' => $cfg->get('number'),
        '#size' => 2,
        '#maxlength' => 2,
        '#description' => t ( "The maximum number of item on one page." ) 
    ];
    
    $form["default"] ['width'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'Maximum width of thumbnail (in pixels)' ),
        '#default_value' => $cfg->get( 'width'),
        '#size' => 11,
        '#maxlength' => 11,
        '#description' => t ( "Set the maximum width of thumbnails." ) 
    ];
    
    $form["default"] ['root'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'Root folder of galleries' ),
        '#required' => TRUE,
        '#default_value' => smpl_root ( $cfg->get( "root") ),
        '#description' => t ( 'The root of photogalleries, somewhere in the public filesystem.')
    ];
   /* 
    $form ['default'] ['private'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Private access of files' ),
        '#default_value' => $cfg->get( 'private' ),
        '#description' => t ( "The private access of files is little bit slower, but is is safe" ),
        '#attributes' => array (
            "readonly" => "readonly" 
        ) 
    ];
    */
    $form["default"] ['smplbox'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'Class of thumbnails' ),
        '#default_value' => ($cfg->get( 'smplbox') ) ?? "smplbox",
        '#description' => t ( "It is recommended to use the smplbox" ),
        '#attributes' => array (
            "readonly" => "readonly" 
        ) 
    ];

    $form["default"] ['button_pre'] = [
        '#type'          => 'textfield',
        '#title'         => t( 'Pager button - previous' ),
        '#default_value' => $cfg->get( 'button_pre') ,
        '#description'   => t ( "It is recommended to leave that alone" ),
        '#attributes' => array (
           // "readonly" => "readonly"
        )
    ];
    $form["default"] ['button_post'] = [
        '#type'          => 'textfield',
        '#title'         => t( 'Pager button - next' ),
        '#default_value' => $cfg->get( 'button_post') ,
        '#description'   => t ( "It is recommended to leave that alone" ),
        '#attributes' => array (
          //  "readonly" => "readonly"
        )
    ];
    /*
     * if(!isset($cfg['directaccess'])) $cfg['directaccess'] = false;
     * $form["default"]['directaccess'] = [
     * '#type' => 'checkbox',
     * '#title' => t('Direct access of files '),
     * '#required' => FALSE,
     * '#default_value' => $cfg['directaccess'],
     * '#description' => t("Direct access to files in the filesystem."),
     * '#attributes' => array('CHECKED'=>'checked', 'readonly' => 'readonly', 'onclick' => 'return false;', 'style'=>'background-color:#DDD;')
     * ];
     */
    
    $form['view'] = [ 
        '#type' => 'fieldset',
        '#title' => t ( 'Viewing options' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE 
    ];
    
    $form['view'] ['lazy'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lazy loading of images' ),
        '#default_value' => $cfg->get( 'lazy' ),
        '#description' => t ( "Enable or disable lazy loading" ) 
    ];
    $form ['view'] ['sub'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Show the subtitles of files' ),
        '#default_value' => $cfg->get( 'sub'),
        '#description' => t ( "If it is true output the subtitle of files if exists, othervise output the name of file" ) 
    ];
    
    $form['view'] ['viewed'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Show the number of views of files' ),
        '#default_value' => $cfg->get( 'viewed'),
        '#description' => t ( "Output the number of views of files" ) 
    ];
    
    $form['view'] ['exif'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Show the EXIF informations of items' ),
        '#default_value' => $cfg->get( 'exif'),
        '#description' => t ( "Output the width and height of the images and other properties of items" ) 
    ];
    
    $form['view'] ['url'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Show the URL of items' ),
        '#default_value' => $cfg->get( 'url'),
        '#description' => t ( "If exists is outputs the url of items and open it in new _blank window." ) 
    ];
    
    $form['view']['url_target']= [
        '#type' => 'textfield',
        '#title' => t('What is the default target of link'),
        '#default_value' => $cfg->get('url_target'),
        '#required' => false,
        '#description' => t("The content of link shows in this window."),
        '#attributes' => array( ($cfg->get('url_checking')?"'readonly' => 'readonly'":""), 'style'=>'background-color:#DDD;'),
    ];
    $form ['view'] ['stat'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Show the statistics of this folder' ),
        '#default_value' => $cfg->get( 'stat'),
        '#description' => t ( "Shows the statistics this folder." ) 
    ];
    
    $form ['button_pre'] = [
        '#type' => 'checkbox',
        '#title' => t ( 'Show the statistics of this folder' ),
        '#default_value' => $cfg->get( 'stat'),
        '#description' => t ( "Shows the statistics this folder." )
    ];

     $form['order'] = [ 
        '#type' => 'fieldset',
        '#title' => t ( 'Ordering options' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#description' => t ( "Experimental version!" ) 
    ];
    
    $form ['order'] ['order'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Change the order of items' ),
        '#default_value' => $cfg->get( 'order' ),
        '#description' => t ( "If it is checked you can change the sorting order of files" ) 
    ];
    
    $form['order'] ['sortorder'] = [ 
        '#type' => 'select',
        '#title' => t ( 'Default sort order' ),
        '#default_value' => $cfg->get( 'sortorder'),
        '#required' => TRUE,
        '#options' => array (
            'filename' => t ( 'Sort files by their file names.' ),
            'size' => t ( 'Sort files by their size' ),
            'date' => t ( 'Sort files by their dates.' ),
            'sub' => t ( 'Sort files by subtitles' ),
            'view' => t ( 'Sort files by number of views' ) 
        ),
        '#description' => t ( "Default sortorder.  wich property of itemst is the source of order (filename, size, dates, etc." ) 
    ];
    
    $form['order'] ['ascdesc'] = [ 
        '#type' => 'select',
        '#title' => t ( 'Sort or randomize image order' ),
        '#default_value' => $cfg->get( 'ascdesc'),
        '#required' => TRUE,
        '#options' => array (
            'asc' => t ( 'Sort items in order.' ),
            'desc' => t ( 'Sort items in reverse order.' ) 
        ),
        '#description' => t ( "Ascending, descending" ) 
    ];
    
    $form['editing'] = [ 
        '#type' => 'fieldset',
        '#title' => t ( 'Editing the list and files' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE 
    ];
    
    $form ['editing'] ['edit'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'You can edit the properties of the files (name, caption, etc, taxonomy, url) etc.' ),
        '#default_value' => $cfg->get( 'edit' ) 
    ];
    
    $form['editing'] ['delete'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'You can delete the actual file' ),
        '#default_value' => $cfg->get('delete') 
    ];
    
    if (extension_loaded ( "gd" )) {
      $gd = gd_info ();
      $gdv = "GD library: " . $gd['GD Version'];
      $gdok = TRUE;
    } else {
      $gdv .= t ( "<br>GD library not installed! " ) . t ( "You can not edit the images on the web!" );
      $gdok = False;
    }
    
    $form['editing'] ['imgedit'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'You can edit the pictures on the web' ),
        '#default_value' => $gdok,
        '#description' => $gdv 
    ];
    
    // It makes smplphotoalbum temporary folder for editing images
    if (empty ( $cfg->get("temp") )) {
      $cfg->set("temp", "public://smplptemp/")->save();
    }
    
    if ($cfg->get('imgedit')) {
      $pp =  \Drupal::service('file_system')->realpath("public://");
      $p  = str_replace("public://", $pp."/", $cfg->get("temp") );
      $p  = str_replace ( "\\", "/", $p);      
      if (! is_dir ( $p )) {
        $p = PHP_OS_FAMILY == "Windows" ? str_replace("\\","/", $p):$p;        
        $ok =  \Drupal::service("file_system")->mkdir($p,0777);
        if (!$ok) {
          \Drupal::messenger()->addMessage( t ( "I can not make Smplphotoalbum temporary folder: " ) . $cfg->get('temp'), "warning" );
        }
      }
      if (! is_writable ( $p )) {
        \Drupal::messenger()->addMessage ( t ( 'The Smplphotoalbum temporary folder not writeable and deleteable by Drupal!: ' . $cfg->get( "temp" ) ), "warning" );
      }
    }
    
    $form['editing'] ['temp'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'Simple Photoalbum temporary folder!' ),
        '#default_value' => $cfg->get( "temp"),
        '#description' => t ( "This folder has to can access from web and it has to be writeable and readable by Drupal." ),
        '#disabled' => ! $cfg->get( 'imgedit' ) 
    ];
    

    /*
     * $form['filter'] = [
     * '#type' => 'checkbox',
     * '#title' => t('Can use a filter in lists'),
     * '#default_value' => $cfg['filter'],
     * '#description' => t("If it is checked it can use a filter in lists"),
     * ];
     */
    $form['checklinks'] = [ 
        '#type' => 'fieldset',
        '#title' => t ( 'Service functions' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#description' => t ( "Check and delete the wrong links from database" ) 
    ];
 
    $form ['checklinks'] ['menu_rebuild'] = [ 
        '#type'          => 'checkbox',
        '#title'         => t ( 'Need the rebuild the pathes of menus?' ),
        '#required'      => False,
        '#default_value' => $cfg->get( 'menu_rebuild'),
        '#description'   =>t ( "Need the pathes rebuild once after the smplphotoalbum module installed or updated." ) 
    ];
    
    $form['checklinks'] ['check'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Check the link of files in the table' ),
        '#default_value' => $cfg->get( 'check'),
        '#description' => t ( "Check and delete the wrong link of files from database." ) 
    ];
    
    $database = \Drupal::database ();
    $db = $database->query ( 'SELECT count(id) AS db FROM {smplphotoalbum}' )->fetchField ();
    
    $form ['checklinks'] ['from'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'From which item starts the checking.' ),
        '#default_value' => $cfg->get('from'),
        '#description' => t ( "This number is changing in every run." . " " . t ( "The number of records: " . number_format ( $db, 0, '.', ' ' ) ) ) 
    ];
    
    $form['checklinks'] ['number_of_checking'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'Number of items chekcing in one run' ),
        '#default_value' => $cfg->get( 'number_of_checking') ,
        '#description' => t ( "If the number is too high the script stops with timeout" ) 
    ];
    /*
     * $form['keywords'] = [
     * '#type' => 'checkbox',
     * '#title' => t('Insert the caption of files into the keywords meta tag.'),
     * '#default_value' => $cfg['keywords']
     * ];
     */
    
    $form['types_settings'] = [ 
        '#type' => 'fieldset',
        '#title' => t ( 'File types Settings' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE 
    ];
    
    $form['types_settings'] ['html5_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Use HTML5 widgets for video and audio files' ),
        '#description' => t ( "checked: using HTML5 widgets for audio and video files, unchecked: use traditional links for use them. Default: checked" ),
        '#default_value' => $cfg->get( 'html5_checking') 
    ];
    $form['types_settings'] ['image_checking'] = [
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the image files' ),
        '#default_value' => $cfg->get( 'image_checking')
    ];
    $form['types_settings'] ['image_extensions'] = [
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of image files' ),
        '#default_value' => $cfg->get( 'image_extensions'),
        '#description' => t ( "This is a list of extensions of image files. Default is jpg / png / jpeg" ),
        '#attributes' => ($cfg->get( 'image_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;'
        ))
    ];
    
    $form['types_settings'] ['audio_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the audio files' ),
        '#default_value' => $cfg->get( 'audio_checking') 
    ];
    $form ['types_settings'] ['audio_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of audio files' ),
        '#default_value' => $cfg->get( 'audio_extensions'),
        '#description' => t ( "This is a list of extensions of audio files. Default is mp3" ),
        '#attributes' => ($cfg->get( 'audio_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['types_settings'] ['audiohtml5_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of audio files for HTML5 player' ),
        '#default_value' => $cfg->get( 'audiohtml5_extensions' ),
        '#description' => t ( "This is a list of extensions of audio files for HTML5 player. Default: wav mp3 ogg" ),
        '#attributes' => array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        ) 
    ];
    
    $form['types_settings'] ['video_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the video files' ),
        '#default_value' => $cfg->get('video_checking') 
    ];
    
    $form['types_settings'] ['video_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of video files' ),
        '#default_value' => $cfg ->get('video_extensions'),
        '#description' => t ( "This is a list of extensions of video files." ),
            '#attributes' => ($cfg->get( 'video_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form ['types_settings'] ['videohtml5_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of video files HTML5 player' ),
        '#default_value' => $cfg->get( 'videohtml5_extensions'),
        '#description' => t ( "This is a list of extensions of HTML5 video files." ),
        '#attributes' => array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        ) 
    ];
    
    $form['types_settings'] ['doc_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the document files' ),
        '#default_value' => $cfg->get( 'doc_checking') 
    ];
    
    $form['types_settings'] ['doc_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of doc files' ),
        '#default_value' => $cfg->get('doc_extensions'),
        '#description' => t ( "This is a list of extensions of doc files. Default only doc" ),
        '#attributes' => ($cfg->get( 'doc_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['types_settings'] ['cmp_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the compressed files' ),
        '#default_value' => $cfg->get('cmp_checking') 
    ];
    
    $form ['types_settings'] ['cmp_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of compressed files' ),
        '#default_value' => $cfg ->get('cmp_extensions'),
        '#description' => t ( "This is a list of extensions of compressed files." ),
            '#attributes' => ($cfg->get( 'cmp_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['types_settings'] ['app_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the application files' ),
        '#default_value' => $cfg->get('app_checking') 
    ];
    
    $form['types_settings'] ['app_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of application files' ),
        '#default_value' => $cfg->get( 'app_extensions'),
        '#description' => t ( "This is a list of extensions of application files." ),
        '#attributes' => ($cfg->get('app_checking')? array(): array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['types_settings'] ['oth_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the other files' ),
        '#default_value' => $cfg->get('oth_checking') 
    ];
    
    $form['types_settings'] ['oth_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of other files' ),
        '#default_value' => $cfg->get( 'oth_extensions'),
        '#description' => t ( "This is a list of extensions of other types files." ),
            '#attributes' => ($cfg->get( 'oth_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['types_settings'] ['dis_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Use the list of prohibited files' ),
        '#default_value' => $cfg->get( 'dis_checking') 
    ];
    
    $form['types_settings'] ['dis_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of prohibited files' ),
        '#default_value' => $cfg->get( 'dis_extensions'),
        '#description' => t ( "This is a list of extensions of application files." ),
            '#attributes' => ($cfg->get( 'dis_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['types_settings'] ['subtitle_change'] = [
        '#type' => 'checkbox',
        '#title' => t ( 'Change the subtitles automatically' ),
        '#default_value' => $cfg->get( 'subtitle_change'),
        '#description' => t ( "Changeable or deletable character in subtitles" )
    ];
    
    // This has to expand
    $a =[];
    $a = explode(" ",$cfg->get('image_extensions'));
    $a = array_merge($a, explode(" ",$cfg->get('audio_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('audiohtml5_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('video_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('videohtml5_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('doc_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('cmp_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('app_extensions')));
    $a = array_merge($a, explode(" ",$cfg->get('oth_extensions')));
    $changeable_strings = implode(" ",array_unique($a));
    $form['types_settings'] ['subtitle_change_text'] = [
        '#type' => 'textfield',
        '#title' => t ( 'Changeable or deletable subtitles with whitespace' ),
        '#default_value' => $changeable_strings,
        '#description' => t ( "Changeable or deletable character in subtitles" ),
        '#size' => 128,
        '#maxlength' => 1024,
        '#attributes' => array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;'
        ) 
    ];
    
    $ffmpeg = extension_loaded ( "ffmpeg" );    
    $form['types_settings'] ['ffmpeg'] = [ 
        '#type' => 'checkbox',
        '#title' => t ( 'Make preview from mpeg files. php_ffmpeg module: ' ) . ($ffmpeg ? t ( "is loaded" ) : t ( "is not loaded" )),
        '#default_value' => $cfg->get( 'ffmpeg') && $ffmpeg,
        '#description' => t ( "This not works! If loaded php_ffmpeg module you can switch to make previes from videos." ),
        '#attributes' => ($ffmpeg ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form['slideshow_settings'] = [
        '#type' => 'fieldset',
        '#title' => t ( 'Slideshow Settings' ),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE
    ];
    $form['slideshow_settings'] ['slide_checking'] = [
        '#type' => 'checkbox',
        '#title' => t ( 'Lists the image files' ),
        '#default_value' => $cfg->get( 'slide_checking')
    ];
    $form['slideshow_settings'] ['slide_extensions'] = [
        '#type' => 'textfield',
        '#title' => t ( 'List of extensions of slideshow files' ),
        '#default_value' => !empty( $cfg->get( 'slide_extensions')) ? $cfg->get( 'slide_extensions'): $cfg->get( 'image_extensions'),
        '#description' => t ( "This is a list of extensions of Slideshow files. Default is jpg / png / jpeg / gif" ),
        '#attributes' => ($cfg->get( 'slide_checking') ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;'
        ))
    ];
    
    return parent::buildForm ( $form, $form_state );
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm ( $form, $form_state );
  }
  
  /**
   *
   * {@inheritdoc}
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cfg  = $this->configFactory->getEditable('smplphotoalbum.settings');    
    $vals = $form_state->getValues ();
    $vals['root'] = $this->smpl_root($vals['root']);
    
    $cfg
      ->set("number" , $vals['number'])
      ->set('width'  , $vals['width'])
      ->set('sub'    , $vals['sub'])      
      ->set('root'   , $vals['root'])
      ->set('viewed' , $vals['viewed'])
      ->set('exif'   , $vals['exif'])
      ->set('stat'   , $vals['stat'])
      ->set('smplbox', $vals['smplbox'])      
      ->set('order'    , $vals['order'])
      ->set('sortorder', $vals['sortorder'])
//      ->set('private'  , $vals['private'])
      ->set('lazy'     , $vals['lazy'])
      
      ->set('check'    , $vals['check'])
      ->set('number_of_checking' , $vals['number_of_checking'])
      ->set('from'     , $vals['from'])      
      ->set('subtitle_change'      , $vals['subtitle_change'])
      ->set('subtitle_change_text' , $vals['subtitle_change_text'])
      ->set('image_checking'       , $vals['image_checking'])
      ->set('image_extensions'     , $vals['image_extensions'])
      ->set('audio_checking'       , $vals['audio_checking'])
      ->set('audio_extensions'     , $vals['audio_extensions'])
      ->set('audiohtml5_extensions', $vals['audiohtml5_extensions'])
      ->set('video_checking'       , $vals['video_checking'])
      ->set('video_extensions'     , $vals['video_extensions'])
      ->set('videohtml5_extensions', $vals['videohtml5_extensions'])
      ->set('html5_checking'       , $vals['html5_checking'])
      
      ->set('doc_checking'    , $vals['doc_checking'])
      ->set('doc_extensions'  , $vals['doc_extensions'])
      ->set('cmp_checking'    , $vals['cmp_checking'])
      ->set('cmp_extensions'  , $vals['cmp_extensions'])
      ->set('app_checking'    , $vals['app_checking'])
      ->set('app_extensions'  , $vals['app_extensions'])
      ->set('oth_checking'    , $vals['oth_checking'])
      ->set('oth_extensions'  , $vals['oth_extensions'])
      ->set('dis_checking'    , $vals['dis_checking'])
      ->set('dis_extensions'  , $vals['dis_extensions'])
      ->set('url'             , $vals['url'])
      ->set('button_pre'      , $vals['button_pre'])
      ->set('button_post'     , $vals['button_post'])
      ->set('edit'            , $vals['edit'])
      ->set('delete'          , $vals['delete'])
      ->set('imgedit'         , $vals['imgedit'])
      ->set('url'             , $vals['url'])
      ->set('url_target'      , $vals['url_target'])
      ->set('temp'            , $vals['temp'])
      ->set('menu_rebuild'    , $vals['menu_rebuild'])      
      ->set('ffmpeg'          , $vals['ffmpeg'])
      ->set('slide_checking'  , $vals['slide_checking'])
      ->set('slide_extensions', $vals['slide_extensions'])
      ->save();
  }
  /**
   * Check root
   * @param unknown $p
   * @return mixed
   */
  function smpl_root($p) {
    if (empty ( $p ) || strlen ( $p ) == 0) {
      $p = "public://photoalbum/";
    }
    $p = str_replace ( '\\', '/', $p );
    if (substr ( $p, -1 ) == "/") {
      $p = substr ( $p, 0, - 1 );      
    }    
    return $p;
  }
}