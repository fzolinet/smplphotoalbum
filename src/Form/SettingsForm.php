<?php

/**
 * @file
 * Contains Drupal\smplphotoalbum\Form\MessagesForm.
 */
namespace Drupal\smplphotoalbum\Form;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\File;
require_once realpath(__DIR__."/../../")."/vendor/autoload.php";

class SettingsForm extends ConfigFormBase {
  protected $aliasManager;
  private $moduleHandler;
  protected $pathValidator;
  protected $requestContext;

  private Connection $con;
  protected Config $cfg;
  /**
   * Class constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory, 
    TypedConfigManagerInterface $typedConfigManager,    
  ) {
    parent::__construct ( $config_factory, $typedConfigManager );
    $this->con = \Drupal::database ();
  }
  
  /**
   *
   * {@inheritdoc}
   *
   */
  public static function create(ContainerInterface $container) {
    return new static (
        $container->get('config.factory'),
        $container->get('config.typed'),
    );
  }
  
  /**
   *
   * {@inheritdoc}
   *
   */
  protected function getEditableConfigNames() {
    return [ 'smplphotoalbum.settings' ];
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
    $cfg = $this->config( 'smplphotoalbum.settings' );

    // If I want to config this server then I have to use state!
    $form = [ ];

    /**
     * Default settings parameters
     */
    $form ["default"] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Default settings' ),
        '#open' => TRUE,        
    ];
    
    $form ["default"]["description"] =[
        '#type' => 'item',
        '#title' => $this->t ( 'Description' ),
        '#description' => $this->t ( "You can set the default settings of Simple Photoalbum." ),
    ];

    $form ["default"] ['test'] = [
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Test the Simple photoalbum' ),
        '#default_value' => $cfg->get( 'test' ),
        '#description' => $this->t ( "Enable or disable test module of Simple Photoalbum" )
    ];
    
    $form ["default"] ['number'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'Maximum item on one page' ),
        '#default_value' => $cfg->get( 'number' ),
        '#size' => 2,
        '#maxlength' => 2,
        '#description' => $this->t ( "The maximum number of item on one page." ) 
    ];
    
    $form ["default"] ['width'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'Maximum width of thumbnail (in pixels)' ),
        '#default_value' => $cfg->get( 'width' ),
        '#size' => 11,
        '#maxlength' => 11,
        '#description' => $this->t ( "Set the maximum width of thumbnails." ) 
    ];
    
    $form ["default"] ['root'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'Root folder of galleries' ),
        '#required' => TRUE,
        '#default_value' => $this->smpl_root ( $cfg->get( "root" ) ),
        '#description' => $this->t ( 'The root of photogalleries, somewhere in the public filesystem.' ) 
    ];
   
    $form["default"] ["method"] = [
      "#type"  => 'select',
      '#title' => $this->t("The type of method of form"),
      '#default_value' => $cfg->get('method'),
      '#required' => true, 
      '#options' =>
      [
        'GET' => 'GET Method',
        'POST' => 'POST method'
      ],
      '#description' => $this->t ( "The Type of method of form (POST / GET), default POST)" ),
    ];
  
    $form ["default"] ['icon'] = [ 
        '#type' => 'select',
        '#title' => $this->t ( 'Icons of module' ),
        '#default_value' => $cfg->get( 'icon' ),
        '#required' => TRUE,
        '#options' => array (
            '_bw' => $this->t ( 'Black & White icons' ),
            '_col' => $this->t ( 'Color icons' ) 
        ),
        '#description' => $this->t ( "Style of icons" ) 
    ];

    /**
     * Viewing parameters
     */
    $form ['view'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Viewing options' ),
        '#open' => FALSE,        
    ];
    
    $form ['view'] ['description'] = [ 
        '#type' => 'item',
        '#title' => $this->t ( 'Description' ),
        '#description' => $this->t ( "You can set the viewing options of Simple Photoalbum." ), 
    ];

    $form ['view'] ['langswitch'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Show the language switcher' ),
        '#default_value' => $cfg->get( 'langswitch' ),
        '#description' => $this->t ( "If it is true show the language switcher" ) 
    ];

    $form ['view'] ['lang'] = [ 
        '#type' => 'select',
        '#title' => $this->t ( 'Choose language' ),
        '#options' => [
            'en' => $this->t ( 'English' ),
            'hu' => $this->t ( 'Hungarian' ),
            'de' => $this->t ( 'German' ),
            'fr' => $this->t ( 'French' ),
            'es' => $this->t ( 'Spanish' )
        ],
        '#default_value' => $cfg->get( 'lang' ),
        '#description' => $this->t ( "Choose language from list" ) 
    ];
    $form ['view'] ['lazy'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lazy loading of images' ),
        '#default_value' => $cfg->get( 'lazy' ),
        '#description' => $this->t ( "Enable or disable lazy loading" ) 
    ];

    $form ['view'] ['sub'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Show the subtitles of files' ),
        '#default_value' => $cfg->get( 'sub' ),
        '#description' => $this->t ( "If it is true output the subtitle of files if exists, othervise output the name of file" ) 
    ];
    
    $form ['view'] ['viewed'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Show the number of views of files' ),
        '#default_value' => $cfg->get( 'viewed' ),
        '#description' => $this->t ( "Output the number of views of files" ) 
    ];
    
    $getID3 = new \getID3();
        
    $form ['view'] ['getid3path'] = [
        '#type' => 'text',
        '#title' => $this->t ( 'Show the EXIF informations of items' ),
        '#default_value' => $cfg->get( 'exif' ),
        '#description' => $this->t ( "Output the width and height of the images and other properties of items" )
    ];
    
    $form ['view'] ['exif'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Show the EXIF informations of items' ),
        '#default_value' => $cfg->get( 'exif' ),
        '#description' => $this->t ( "Output the width and height of the images and other properties of items" ) 
    ];
    
    $form ['view'] ['url_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Show the URL of items' ),
        '#default_value' => $cfg->get( 'url_checking' ),
        '#description' => $this->t ( "If exists is outputs the url of items and open it in new _blank window." ) 
    ];
    
    $form ['view'] ['url_target'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'What is the default target of link' ),
        '#default_value' => $cfg->get( 'url_target' ),
        '#required' => false,
        '#description' => $this->t ( "The content of link shows in this window." ),
        '#attributes' => array (
            ( $cfg->get( 'url_checking' ) ? "'readonly' => 'readonly'" : "" ),
            'style' => 'background-color:#DDD;' 
        ) 
    ];

    $form ['view'] ['stat'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Show the statistics of folders' ),
        '#default_value' => $cfg->get( 'stat' ),
        '#description' => $this->t ( "Shows the statistics of folders." ) 
    ];
    
    /**
     * Filter & order settings
     */
    $form ['filterorder'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Filtering & Ordering options' ),
        '#open' => FALSE,        
    ];

    $form['filterorder']['description'] = [
        '#type' => 'item',
        '#title' => $this->t ( 'Description' ),        
        '#description' => $this->t ( "The user can filter items and change the order of viewing list!" ) 
    ];

    $form ['filterorder'] ['filter'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Filter the images' ),
        '#default_value' => $cfg->get( 'filter' ),
        '#description' => $this->t ( "The user can filter items in the actual path in name or subscription!" ) 
    ];

    $form['filterorder']['important'] =[
        '#type' => 'checkbox',
        '#title' => $this->t('Important items always on the top'),
        '#default_value' => $cfg->get('important'),
        '#description' => $this->t("If an item is important it is always on the top of the list.")
    ];
    
    $form ['filterorder'] ['order'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Change the order of items' ),
        '#default_value' => $cfg->get( 'order' ),
        '#description' => $this->t ( "If it is checked you can change the sorting order of files" ) 
    ];
    
    $form ['filterorder'] ['sortorder'] = [ 
        '#type' => 'select',
        '#title' => $this->t ( 'Default sort order' ),
        '#default_value' => $cfg->get( 'sortorder' ),
        '#required' => TRUE,
        '#options' => array (
            'filename' => $this->t ( 'Sort files by their file names.' ),
            'importance' => $this->t ( 'Sort files by their importance' ),
            'size' => $this->t ( 'Sort files by their size' ),
            'date' => $this->t ( 'Sort files by their dates.' ),
            'sub' => $this->t ( 'Sort files by subtitles' ),
            'view' => $this->t ( 'Sort files by number of views' ) 
        ),
        '#description' => $this->t ( "Default sortorder. Wich property of itemst is the source of order (filename, size, dates, etc." ) 
    ];    
    
    $form ['filterorder'] ['ascdesc'] = [ 
        '#type' => 'select',
        '#title' => $this->t ( 'Sort or randomize image order' ),
        '#default_value' => $cfg->get( 'ascdesc' ),
        '#required' => TRUE,
        '#options' => array (
            'asc' => $this->t ( 'Sort items in order.' ),
            'desc' => $this->t ( 'Sort items in reverse order.' ) 
        ),
        '#description' => $this->t ( "Ascending, descending" ) 
    ];
 
    /**
     * Editing settings
     */
    $form ['edit'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Editing the list of items and properties of files' ),
        '#open' => FALSE
    ];

    $form ['edit']['description'] = [ 
        '#type' => 'item',
        '#title' => $this->t ( 'Description' ),
        '#description' => $this->t ( "You can edit the properties of the files (name, caption, etc, taxonomy, url) and you can delete the actual file. You can upload new files to the server." ),        
    ];

    $form ['edit'] ['edit'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'You can edit the properties of the files (name, caption, etc, taxonomy, url) etc.' ),
        '#default_value' => $cfg->get( 'edit' ) ,        
    ];
    
    $form ['edit'] ['delete'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'You can delete the actual file' ),
        '#default_value' => $cfg->get( 'delete' ) 
    ];

    $form['edit'] ['upload'] = [
        '#type' => 'checkbox',
        '#title' => $this->t ('Uploading enable files to the server.'),
        '#default_value' => $cfg->get( 'upload' ),
        '#description' => $this->t ( "Uploading to the server is potentionally, possible dangerous!!!" ),
    ];

    $form['edit'] ['folders']= [
        '#type' => 'checkbox',
        '#title' => $this->t ('Use the multilevel folders in the list on the server.'),
        '#default_value' => $cfg->get( 'folders' ),
        '#description' => $this->t ( "If it is checked you can use multilevel folders in the lists. Other words more levels of galleries are visible." ),
    ];
    
    // Graphic driver are exist
    $gdopt = array ();
    if (extension_loaded ( "gd" )) {
      $gd = gd_info ();
      $gdv = "GD library: " . $gd ['GD Version'];
      $gdok = TRUE;
      $gdopt ['gd'] = 'GD2 library';
    } else {
      $gdv = $this->t ( "<br>GD library not installed!</br>" ) . $this->t ( "You can not edit the images on the web with GD!" );
      $gdok = False;
    }
    
    if (extension_loaded ( "imagick" )) {
      $imagick = \Imagick::getVersion ();
      $imagickv = "Imagick library: " . $imagick ["versionString"];
      $imagickok = true;
      $gdopt ['imagick'] = 'Imagick library';
    } else {
      $imagickv = $this->t ( "<br>Imagick library not installed! " ) . $this->t ( "You can not edit images on the web with Imagick!" );
      $imagickok = False;
    }
 
    /**
     * images Editing functions
     */
    $form['imgedit'] = [
        '#type' => 'details',
        '#title' => $this->t ( 'Editing images' ),
        '#collapsible' => TRUE,
        '#collapsed' => FALSe,        
    ];

    $form['imgedit']['description'] = [
        '#type' => 'item',
        '#title' => $this->t ( 'Description' ),
        '#description' => $this->t ( "You can edit the images on the web if you have GD or Imagick library. You can choose which library do you want to use for editing images on the web. The GD library is more common, but the Imagick library is more powerful and faster than GD."  )
    ];

    // you can edit images
    $form ['imgedit'] ['imgedit'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'You can edit the pictures on the web' ),
        '#default_value' => $gdok || $imagickok,
        '#description' => $gdv . " or " . $imagickv 
    ];

    // choose graphic driver from exists
    $form ['imgedit'] ['graphicdrv'] = [ 
        '#type' => 'radios',
        '#title' => $this->t ( 'Choose graphic driver for editing images on the web!' ),
        '#default_value' => $cfg->get( 'graphicdrv' ),
        '#options' => $gdopt,
        '#description' => $this->t ( 'Usable graphic driver for editing images' ) . ': <br><ul><li>' . $gdv . "</li><li>" . $imagickv . '</li></ul>',
        '#attributes' => ($gdok || $imagickok) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        ),
        '#disabled' => ! ($gdok || $imagickok) 
    ];

    // jpeg default compression
    $jpeg = $cfg->get( 'jpeg' );
    if (! isset ( $jpeg ) || empty ( $jpeg )) {
      $jpeg = - 1;
    }
    
    $form ['imgedit'] ['jpeg'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'JPG, JPEG Quality' ),
        '#default_value' => $jpeg,
        '#size' => 10,
        '#maxlength' => 10,
        '#description' => $this->t ( "The JPEG quality when save the edited image: PHP default: -1; 0 (worst quality, smallest file ..100 (best quality, biggest file)." ) 
    ];

    // png default compression
    $png = $cfg->get( 'png' );
    if (! isset ( $png ) || empty ( $png )) {
      $png = - 1;
    }
    $form ['imgedit'] ['png'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'PNG Quality' ),
        '#default_value' => $png,
        '#size' => 10,
        '#maxlength' => 10,
        '#description' => $this->t ( "The PNG quality when save the edited image: PHP default: -1; 0 (no compression, biggest file) ..9 (max compression, smallest file)." ) 
    ];
    
    // It makes smplphotoalbum temporary folder for editing images
    $temp =$cfg->get( "temp" );
    
    if ( empty ( $temp )) {
      $temp = "public://smplptemp/";
    }

    if ($cfg->get( 'imgedit' )) {
      $pp = \Drupal::service ( 'file_system' )->realpath ( "public://" );
      $p = str_replace ( "public://", $pp . "/", $cfg->get( "temp" ) );
      $p = str_replace ( "\\", "/", $p );
      if (! is_dir ( $p )) {
        $p = PHP_OS_FAMILY == "Windows" ? str_replace ( "\\", "/", $p ) : $p;
        $ok = \Drupal::service ( "file_system" )->mkdir ( $p, 0777 );
        if (! $ok) {
          \Drupal::messenger ()->addMessage ( $this->t( "I can not make Smplphotoalbum temporary folder: " ) . $temp , "warning" );
        }
      }
      if (! is_writable ( $p )) {
        \Drupal::messenger ()->addMessage ( $this->t( 'The Smplphotoalbum temporary folder not writeable and deleteable by Drupal!: ' . $temp ), "warning" );
      }
    }
    
    //Default Temporary folder
    $form ['imgedit'] ['temp'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'Simple Photoalbum temporary folder!' ),
        '#default_value' => $temp,
        '#description' => $this->t ( "This folder has to can access from web and it has to be writeable and readable by Drupal." ),
        '#disabled' => ! $cfg->get( 'imgedit' ) 
    ];

    $form ['imgedit'] ['autoclose'] = [
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Save changed image and  autoclose th window!' ),
        '#default_value' => $cfg->get( "autoclose" ),
        '#description' => $this->t ( "After save the modified image the window close automatically." ),
        '#disabled' => ! $cfg->get( 'imgedit' )
    ];

    /**
     * Watermark settings
     */
    $form ['wm'] = [
        '#type' => 'details',
        '#title' => $this->t ( 'Watermark settings' ),
        '#open' => FALSE,
    ];
    
    $form["wm"]["description"] = [
        "#type" => "item",
        "#title"=> $this->t("Description"),
        '#description' => $this->t (  "You can write watermark on images on the fly Watermark to protect against theft the images." ),
    ];

    $form['wm']['wm'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Watermark using in show the images'),
        '#default_value' => $cfg->get('wm'),
        '#description' => $this->t ( "When shows images use watermark." ),
    ];
    
    // default watermark path    
    $defaultwm = "smplphotoalbum://images/watermark.jpg";
    $wm = $cfg->get( "wmpath" );
    if(!empty($wm)){
      $defaultwm = $wm;
    }

    $form['wm'] ['wmpath'] = [
        '#type' => 'textfield',
        '#title' => $this->t ( 'Default watermark path' ),
        '#default_value' => $defaultwm,
        '#description' => $this->t ( "Default watermark path from public folder (beginning of public://) or from module (beginning of smplphotoalbum://images/) of Simple Photoalbum." ),
        '#disabled' => ! $cfg->get( 'imgedit' ) 
    ]; 
  
    // default watermark alpha
    $wmalpha = 10;
    if(!empty($cfg->get( 'wmalpha' ) ) ){
      $wmalpha =$cfg->get( 'wmalpha' );
    }
    $form['wm'] ['wmalpha'] = [
        '#type' => 'number',
        '#title' => $this->t ( 'Default watermark alpha value' ),
        '#min'   => 0,
        '#max'   => 100,
        '#step'  => 1,        
        '#default_value' => $wmalpha,
        '#description' => $this->t ( "Default watermark alpha value 0..100%" ),
        '#disabled' => ! $cfg->get( 'imgedit' )
    ];
    
    $copyright = $cfg->get( "copyright" );    
    if(empty($copyright)){
      $copyright = "PiQasso Group: http://www.fzolee.hu";
    }

    $form['wm'] ['copyright'] = [
        '#type' => 'textfield',
        '#title' => $this->t ( 'Default copyright text' ),
        '#default_value' => $copyright, 
        '#description' => $this->t ( "Default watermark text. You can write it into the items ( image, audio file) of Simple Photoalbum." ),
        '#disabled' => ! $cfg->get( 'imgedit' )
    ];
    
    $author = $cfg->get( "author" );
    if(empty($author)){
      $author = "PiQasso";
    }

    $form['wm'] ['author'] = [
        '#type' => 'textfield',
        '#title' => $this->t ( 'Default Author text' ),
        '#default_value' => $author,
        '#description' => $this->t ( "Default watermark text. You can write it into the items ( image, audio file) of Simple Photoalbum." ),
        '#disabled' => ! $cfg->get( 'imgedit' )
    ];
  
    /**
     * Service settings
     */
    $form['services'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Service functions' ),
        '#open' => FALSE,        
    ];

    $form["services"]["description"] = [
        "#type" => "item",
        "#title"=> $this->t("Description"),
        '#description' => $this->t (  "Service functions of Simple photoalbum. You can check and delete the orphane or duplicate records from database." ),
    ];

    $form ['services'] ['menu_rebuild'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Need the rebuild the pathes of menus?' ),
        '#required' => False,
        '#default_value' => $cfg->get( 'menu_rebuild' ),
        '#description' => $this->t ( "Need the pathes rebuild once after the smplphotoalbum module installed or updated." ) 
    ];
    
    // SERVICE FUNCTION
    $check = $cfg->get( 'check' );
    $from  = $cfg->get( 'from' );
    $number_of_checking = $cfg->get( 'number_of_checking' );
    if ($check) {
      $this->removeduplicate ();
    }
      
    $db = $this->con->query ( 'SELECT count(id) AS db FROM {smplphotoalbum}' )->fetchField ();
    if( $number_of_checking >$db ){
        $number_of_checking = $db;
    }
    
    if ( $check ) {
      if ($from + $number_of_checking > $db) {
        $number_of_checking = $db - $from-1;
      }
      $from = $this->checkrecords ( $from, $number_of_checking, $cfg->get( 'root' ) );
      $cfg->set ( 'check', False );
    }
    
    $form ['services'] ['check'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Check the orphane or duplicate records from database of smplphotoalbum' ),
        '#default_value' => $check,
        '#description' => $this->t ( "Check and delete the orphane records from database." ) 
    ];
    
    $form ['services'] ['from'] = [ 
        '#type' => 'number',
        '#title' => $this->t ( 'From which item starts the checking.' ),
        '#default_value' => $from,
        '#min' => 0,
        '#max' => $db,
        '#description' => $this->t ( "This number is changing in every run." . " " . $this->t ( "The number of records: " . number_format ( $db, 0, '.', ' ' ) ) ) 
    ];
    
    $form ['services'] ['number_of_checking'] = [ 
        '#type' => 'number',
        '#title' => $this->t ( 'Number of items chekcing in one run' ),
        '#default_value' => $number_of_checking,
        '#min' => 1,
        '#max' => (100 > $db ? 100 : $db),
        '#description' => $this->t ( "If the number is too high the script stops with timeout" ) 
    ];
      
    $form ['types_settings'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'File types settings' ),
        '#open' => FALSE,        
    ];

    $form["types_settings"]["description"] = [
        "#type" => "item",
        "#title"=> $this->t("Description"),
        '#description' => $this->t (  "Used filetypes of Simple photoalbum. You can change the list of filetypes, but be careful! If you change the list of filetypes, the existing files will not be listed in the galleries." ),
    ];
    
    $form ['types_settings'] ['html5_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Use HTML5 widgets for video and audio files' ),
        '#description' => $this->t ( "checked: using HTML5 widgets for audio and video files, unchecked: use traditional links for use them. Default: checked" ),
        '#default_value' => $cfg->get( 'html5_checking' ) 
    ];

    $form ['types_settings'] ['image_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the image files' ),
        '#default_value' => $cfg->get( 'image_checking' ) 
    ];

    $form ['types_settings'] ['image_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of image files' ),
        '#default_value' => $cfg->get( 'image_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of image files. Default is jpg / png / jpeg" ),
        '#attributes' => ($cfg->get( 'image_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form ['types_settings'] ['audio_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the audio files' ),
        '#default_value' => $cfg->get( 'audio_checking' ) 
    ];

    $form ['types_settings'] ['audio_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of audio files' ),
        '#default_value' => $cfg->get( 'audio_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of audio files. Default is mp3" ),
        '#attributes' => ($cfg->get( 'audio_checking' ) ? array () : array (                        
        )) 
    ];
    
    $form ['types_settings'] ['audiohtml5_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of audio files for HTML5 player' ),
        '#default_value' => $cfg->get( 'audiohtml5_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of audio files for HTML5 player. Default: wav mp3 ogg" ),
        '#attributes' => array (             
        ) 
    ];
    
    $form ['types_settings'] ['video_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the video files' ),
        '#default_value' => $cfg->get( 'video_checking' ) 
    ];
    
    $form ['types_settings'] ['video_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of video files' ),
        '#default_value' => $cfg->get( 'video_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of video files." ),        
    ];
    
    $form ['types_settings'] ['videohtml5_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of video files HTML5 player' ),
        '#default_value' => $cfg->get( 'videohtml5_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of HTML5 video files." ),        
    ];

    $form ['types_settings'] ['doc_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the document files' ),
        '#default_value' => $cfg->get( 'doc_checking' ) 
    ];
    
    $form ['types_settings'] ['doc_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of doc files' ),
        '#default_value' => $cfg->get( 'doc_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of doc files. Default only doc" ),
        '#attributes' => $cfg->get( 'doc_checking' ) ? []: [
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        ] 
    ];
    
    $form ['types_settings'] ['cmp_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the compressed files' ),
        '#default_value' => $cfg->get( 'cmp_checking' ) 
    ];
    
    $form ['types_settings'] ['cmp_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of compressed files' ),
        '#default_value' => $cfg->get( 'cmp_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of compressed files." ),
        '#attributes' => ($cfg->get( 'cmp_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
     
    $form ['types_settings'] ['app_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the application files' ),
        '#default_value' => $cfg->get( 'app_checking' ) 
    ];
    
    $form ['types_settings'] ['app_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of application files' ),
        '#default_value' => $cfg->get( 'app_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of application files." ),
        '#attributes' => ($cfg->get( 'app_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form ['types_settings'] ['oth_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Lists the other files' ),
        '#default_value' => $cfg->get( 'oth_checking' ) 
    ];
    
    $form ['types_settings'] ['oth_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of other files' ),
        '#default_value' => $cfg->get( 'oth_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of other types files." ),
        '#attributes' => ($cfg->get( 'oth_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form ['types_settings'] ['dis_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Use the list of prohibited files' ),
        '#default_value' => $cfg->get( 'dis_checking' ) 
    ];
    
    $form ['types_settings'] ['dis_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of prohibited files' ),
        '#default_value' => $cfg->get( 'dis_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of application files." ),
        '#attributes' => ($cfg->get( 'dis_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];
    
    $form ['types_settings'] ['subtitle_change'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Change the subtitles automatically' ),
        '#default_value' => $cfg->get( 'subtitle_change' ),
        '#description' => $this->t ( "Changeable or deletable character in subtitles" ) 
    ];
    
    // This has to expand
    $a = [ ];

    $a = array_merge ( 
        explode ( " ", $cfg->get( 'image_extensions' ) ), 
        explode ( " ", $cfg->get( 'audio_extensions' ) ),
        explode ( " ", $cfg->get( 'audiohtml5_extensions' ) ),
        explode ( " ", $cfg->get( 'video_extensions' ) ),
        explode ( " ", $cfg->get( 'videohtml5_extensions' ) ),
        explode ( " ", $cfg->get( 'doc_extensions' ) ),
        explode ( " ", $cfg->get( 'cmp_extensions' ) ),
        explode ( " ", $cfg->get( 'app_extensions' ) ),
        explode ( " ", $cfg->get( 'oth_extensions' ) ) 
    );
    
    $changeable_strings = implode ( " ", array_unique ( $a ) );
    $form ['types_settings'] ['subtitle_change_text'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'Changeable or deletable subtitles with whitespace' ),
        '#default_value' => $changeable_strings,
        '#description' => $this->t ( "Changeable or deletable character in subtitles" ),
        '#size' => 128,
        '#maxlength' => 1024,
        '#attributes' => array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        ) 
    ];
       
    $form ['slideshow_settings'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Slideshow Settings' ),
        '#open' => FALSE,
        '#description' => $this->t ( "Slideshow settings for images" )
    ];

    $form ['slideshow_settings']['description'] = [ 
        '#type' => 'item',
        '#title' => $this->t ( 'Description' ),        
        '#description' => $this->t (  "Slideshow setting for images. "),
    ];

    $form ['slideshow_settings'] ['slide_checking'] = [ 
        '#type' => 'checkbox',
        '#title' => $this->t ( 'Slide the images' ),
        '#default_value' => $cfg->get( 'slide_checking' ) 
    ];

    $form ['slideshow_settings'] ['slide_extensions'] = [ 
        '#type' => 'textfield',
        '#title' => $this->t ( 'List of extensions of slideshow files' ),
        '#default_value' => ! empty ( $cfg->get( 'slide_extensions' ) ) ? $cfg->get( 'slide_extensions' ) : $cfg->get( 'image_extensions' ),
        '#description' => $this->t ( "This is a list of extensions of Slideshow files. Default is jpg / png / jpeg / gif / webp" ),
        '#attributes' => ($cfg->get( 'slide_checking' ) ? array () : array (
            'readonly' => 'readonly',
            'style' => 'background-color:#DDD;' 
        )) 
    ];

    $form ['slideshow_settings'] ['slstyle'] = [
        '#type' => 'textfield',
        '#title' => $this->t ( 'Default styles of slideshow image' ),
        '#default_value' => ! empty ( $cfg->get( 'slstyle' ) ) ? $cfg->get( 'slstyle' ) : "height: 100%;max-width: 100%;",
        '#description' => $this->t ( "This is the default styles of image of slideshow" ),
    ];
    
    /**
     * Image Recognition with AI
     */    
    $form ['AI_settings'] = [ 
        '#type' => 'details',
        '#title' => $this->t ( 'Image Recognition with AI' ),
        '#open' => FALSE,
        '#description' => $this->t ( "Image recognition with AI. You can use GEMINI client for image recognition." )
    ];

    // Is there installed the right services
    $curl = extension_loaded("curl");    
    $AI   = $curl;    
    $aigemini   = $cfg->get('aigemini');    
    $form["AI_settings"] ["aigemini"] = [
        "#type" => 'checkbox',
        '#title' => $this->t("You can use image recognition with GEMINI client"),
        '#default_value' => $aigemini,
        '#description'   => "Image recognition with GEMINI client",         
    ];

    /**
     * FFMPEG Settings - tested in windows & Linux is alpha
     */
    $form['ffmpeg_settings'] = [
        '#type' => 'details',
        '#title' => $this->t ( 'FFMPEG settings for video and audio editing and conversion if ffmpeg is installed' ),
        '#open' => FALSE,
    ];

    $form["ffmpeg_settings"]["description"] = [
        "#type" => "item",
        "#title"=> $this->t("Description"),
        '#description' => $this->t (  "FFMPEG is a free open-source software project that produces libraries and programs for handling multimedia data. " .
            "FFMPEG can decode, encode, transcode, mux, demux, stream, filter and play pretty much anything that humans and machines have created. "),
    ];
    
    if(version_compare(PHP_VERSION, '8.2.0') >= 0 ){
        $php_ok = true;    
    } else{
        $php_ok = false;
    }

    $ffmpeg_path  = $cfg->get("ffmpeg_path");
    $ffmpeg_path  = empty($ffmpeg_path) ? "" : $ffmpeg_path;

    $ffprobe_path = $cfg->get("ffprobe_path");
    $ffprobe_path = empty($ffprobe_path) ? "" : $ffprobe_path;
    
    if(PHP_OS == "WINNT"){
        $os = "Windows System.";
        if ( empty( $ffmpeg_path ) )  $ffmpeg_path  = "C:\\ffmpeg\\bin\\ffmpeg.exe";
        if ( empty( $ffprobe_path ) ) $ffprobe_path  = "C:\\ffmpeg\\bin\\ffprobe.exe";
        
    } else if(PHP_OS == "Linux" ){
        $os = "Linux system.";
        $ffmpeg_path   = "/usr/bin/ffmpeg";
        $ffprobe_path  = "/usr/bin/ffprobe";

    } else{
        $os = PHP_OS." System.";
        $ffmpeg_path = "";          
        $ffprobe_path = "";
    }
    $ffmpeg_path  = str_replace("\\", "/", $ffmpeg_path);
    $ffprobe_path = str_replace("\\", "/", $ffprobe_path);

    $form['ffmpeg_settings']['ffmpeg_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t ( 'FFMPEG path with exec progam' ),
        '#default_value' => $ffmpeg_path,
        '#description' => t("Write the full path of ffmpeg executable. Example: /usr/bin/ffmpeg or c:\Users\[username]\AppData\Local\Microsoft\WinGet\Links\\ffmpeg.exe" ),
    ];

    $form['ffmpeg_settings']['ffprobe_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t ( 'FFPROBE path with exec progam' ),
        '#default_value' => $ffprobe_path,
        '#description' => t("Write the full path of ffprobe executable. Example: /usr/bin/ffprobe or c:\Users\[username]\AppData\Local\Microsoft\WinGet\Links\\ffprobe.exe" ),
    ];

    $ffmpeg_installed = @(file_exists( $ffmpeg_path ) && is_readable( $ffmpeg_path ) && is_executable( $ffmpeg_path ) ? true : false);
    $ffprobe_installed = @(file_exists( $ffprobe_path ) && is_readable( $ffprobe_path ) && is_executable( $ffprobe_path ) ? true : false);

    if(PHP_OS == "WINNT") {
        $os = "Windows System."; 
    }else if(PHP_OS == "Linux" ){
        $os = "Linux system.";  
    } else{
        $os = "Your system is ".PHP_OS.". I can not check the FFMPEG installation on this system. Please check it manually and write the path of ffmpeg && ffprobe executables into the FFMPEG path field.";
    }
    $os .= " FFMPEG " . ( $ffmpeg_installed ? "is installed." : "is <strong>not installed or not readable or not executable</strong>. " );
    $os .= " FFPROBE ". ( $ffprobe_installed ? "is installed." : "is <strong>not installed or not readable or not executable</strong>. " );  
    $os .= " PHP version ".PHP_VERSION. ( $php_ok ? " is OK." : " is not OK. PHP 8.2 or higher is required for FFMPEG." );      
    
    $form["ffmpeg_settings"]["installed"] = [
        "#type" => "item",
        "#title"=> $this->t("FFMPEG installation check"),
        '#description' => $this->t (  $os ),
    ];

    $ffmpeg = $cfg->get("ffmpeg");
    $form['ffmpeg_settings']['ffmpeg']=[
        '#type' => 'checkbox',
        '#title' => $this->t ( $os ),
        '#default_value' => ( $ffmpeg && $ffmpeg_installed && $ffprobe_installed && $php_ok),
        '#description' => $this->t ( "If the checking is ok the FFMPEG can be use on the server" ),
        '#attributes' => ( $ffmpeg_installed && $ffprobe_installed && $php_ok) ? [] :
            [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ]
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
    $cfg = $this->configFactory->getEditable ( 'smplphotoalbum.settings' );    
    //Delete the key from smplphotoalbum.settings
    //$this->configFactory->getEditable ( 'smplphotoalbum.settings' )->clear("slidestyle");
    $vals = $form_state->getValues();

    $vals ['root'] = $this->smpl_root ( $vals ['root'] );

    $cfg->set( "number", $vals ['number'] )
        ->set( 'width', $vals ['width'] )
        ->set( 'sub', $vals ['sub'] )
        ->set( 'root', $vals ['root'] )
        ->set( 'viewed', $vals ['viewed'] )
        ->set( 'exif', $vals ['exif'] )
        ->set( 'stat', $vals ['stat'] )        
        ->set( 'order', $vals ['order'] )
        ->set( 'sortorder', $vals ['sortorder'] )
        ->set( 'filter', $vals ['filter'] )
        ->set( 'lazy', $vals ['lazy'] )
        ->set( 'check', $vals ['check'] )
        ->set( 'number_of_checking', $vals ['number_of_checking'] )
        ->set( 'from', $vals ['from'] )
        ->set( 'subtitle_change', $vals ['subtitle_change'] )
        ->set( 'subtitle_change_text', $vals ['subtitle_change_text'] )
        ->set( 'image_checking', $vals ['image_checking'] )
        ->set( 'image_extensions', $vals ['image_extensions'] )
        ->set( 'audio_checking', $vals ['audio_checking'] )
        ->set( 'audio_extensions', $vals ['audio_extensions'] )
        ->set( 'audiohtml5_extensions', $vals ['audiohtml5_extensions'] )
        ->set( 'video_checking', $vals ['video_checking'] )
        ->set( 'video_extensions', $vals ['video_extensions'] )
        ->set( 'videohtml5_extensions', $vals ['videohtml5_extensions'] )
        ->set( 'html5_checking', $vals ['html5_checking'] )
        ->set( 'doc_checking', $vals ['doc_checking'] )
        ->set( 'doc_extensions', $vals ['doc_extensions'] )
        ->set( 'cmp_checking', $vals ['cmp_checking'] )
        ->set( 'cmp_extensions', $vals ['cmp_extensions'] )
        ->set( 'app_checking', $vals ['app_checking'] )
        ->set( 'app_extensions', $vals ['app_extensions'] )
        ->set( 'oth_checking', $vals ['oth_checking'] )
        ->set( 'oth_extensions', $vals ['oth_extensions'] )
        ->set( 'dis_checking', $vals ['dis_checking'] )
        ->set( 'dis_extensions', $vals ['dis_extensions'] )        
        ->set( 'method', $vals ['method'] )
        // Editing list and properties of items
        ->set( 'edit', $vals ['edit'] )
        ->set( 'jpeg', $vals ['jpeg'] )->set ( 'png', $vals ['png'] )
        ->set( 'delete', $vals['delete'] )
        ->set( 'upload', $vals['upload'])
        ->set( 'folders', $vals['folders'])
        // Image editing
        ->set( 'imgedit', $vals ['imgedit'] )
        ->set( 'graphicdrv', $vals ['graphicdrv'] )
        ->set( 'url_checking', $vals ['url_checking'] )
        ->set( 'url_target', $vals ['url_target'] )
        ->set( 'temp', $vals ['temp'] )
        ->set( 'autoclose', $vals ['autoclose'] )
        //Watermark settings
        ->set( 'wm', $vals ["wm"])
        ->set( 'wmpath', $vals['wmpath'])
        ->set( 'wmalpha', $vals['wmalpha'])
        ->set( 'copyright', $vals['copyright'])
        ->set( 'author', $vals['author'])
        //Services
        ->set( 'menu_rebuild', $vals ['menu_rebuild'] ) 
        //slide settings
        ->set( 'slide_checking', $vals ['slide_checking'] )
        ->set( 'slide_extensions', $vals ['slide_extensions'] )
        ->set( 'slstyle', $vals ['slstyle'] )        
        ->set( 'icon', $vals ['icon'] )
        ->set( 'test', $vals ['test'] )
         // AI settings        
        ->set( 'aigemini', $vals['aigemini'] )
        ->set( 'important', $vals['important'] )        
        ->set( 'langswitch', $vals['langswitch'] )
        ->set( 'lang', $vals['lang'] )
        //ffmpeg settings
        ->set( 'ffmpeg', $vals['ffmpeg'] )
        ->set( 'ffmpeg_path', $vals['ffmpeg_path'] )
        ->set( 'ffprobe_path', $vals['ffprobe_path'] )        
        ->save ();
  }
  /**
   * Check root
   * 
   * @param string $p          
   * @return mixed
   */
  function smpl_root($p) {
    if (empty ( $p ) || strlen ( $p ) == 0) {
      $p = "public://photoalbum/";
    }
    $p = str_replace ( '\\', '/', $p );
    if (substr ( $p, - 1 ) == "/") {
      $p = substr ( $p, 0, - 1 );
    }
    return $p;
  }
  /**
   *
   * @param int $from          
   * @param int $number          
   * @return int
   */
  private function checkrecords($from, $number, string $root) {
    // database record number
    $db = $this->con->query ( 'SELECT count(id) AS db FROM {smplphotoalbum}' )->fetchField ();
    
    // real root of smplphotoalbum
    $realroot = \Drupal::service ( 'file_system' )->realpath ( $root );
    
    $del = '';
    
    for( $i = $from; $i < $from + $number && $i < $db; $i++ ) {
      $qry = $this->con->query ( 'SELECT id, path + name AS filepath FROM {smplphotoalbum} LIMIT ' . $i . ',1' );
      $rec = $qry->fetchAssoc ();
      
      if ( !file_exists ( $root . $rec ['filepath'] )) {
        $del .= ',' . $rec ['id'];
      }
    }
    $ok = $this->con->query ( 'DELETE FROM {smplphotoalbum} WHERE id in (-1' . $del . ');' );
    $from += $i;
    return $from;
  }
  
  /**
   * Remove the duplicate records from smplphotoalbum table
   */
  private function removeduplicate() {
    $sql = 'DELETE t1 FROM {smplphotoalbum} t1
            INNER JOIN {smplphotoalbum} t2
            WHERE t1.id <> t2.id AND
            t1.path + t1.name = t2.path + t2.name;
           ';
    $this->con->query ( $sql );
  }
}