<?php
/**
 * Exif data 
 */
namespace Drupal\smplphotoalbum\Controller;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
require_once "Xml2Assoc.php";
require_once realpath(__DIR__ . "/../../vendor/") . "/autoload.php";
class Exif{
  use StringTranslationTrait;
  private $mp;
  private $entry;
  private $type;
  private $path;
  private $p;
  private $cfg;   // configuration array
  private $verbose;
  private $exif;  // Exif information array
  private $GetID3;  // GetID3 class
  private $ext='';
  
  private $spec = [ // spec
    'album',
    'band',
    'composer',
    'genre',
    'title',
    'artist'
  ];
  private $del = [ 
    'about',
    "apple-fi",  
    "audio_signature",
    "bytes",
    "avdataoffset",
    "avdataend",
    'cbSize',
    "compression_profile",
    'datalength',
    'dataoffset',
    'dc', 
    'documentID',
    'DocumentID',    
    'dwFlags',
    'dwHeight',
    'dwInitialFrames',
    'dwLength',
    'dwMicroSecPerFrame',
    'dwMaxBytesPerSec',
    'dwPaddingGranularity',
    'dwQuality',    
    'dwRate',
    'dwSampleSize',
    'dwScale',
    'dwStreams',
    'dwSuggestedBufferSize',    
    'dwTotalFrames',    
    'encoder options',
    'encoding id',
    'eos',
    'error_correct_type',
    'error_correct_guid',
    'exif',
    'extra_field_data',
    "fileid",
    "fileid_guid",
    "filepath",
    "filename",
    "filenamepath",
    "filesize",
    "FileName",
    "FileSize",
    "FileType",
    'flags_raw',
    'framelength',
    'framenamelong',
    'framenameshort',
    "GETID3_VERSION",
    "html",
    'instanceID',  
    'modeextension', 
    'moov mvhd',
    'moov trak tkhd',
    'moov trak mdia mdhd',
    "mwg-rs",
    'noise_shaping',
    'nSamplesPerSec',
    'nAvgBytesPerSec',
    'nBlockAlign',
    'nChannels',
    "objectid",
    "objectid_guid",   
    'pcm_abs_position',   
    'page_start_offset',      
    'pdf',
    'photoshop',
    'preset used',
    'reserved',
    'reserved_1',
    'reserved_guid',
    'reserved_1_guid',
    'reserved_2',
    'RGAD_album',
    'RGAD_track',
    "SectionsFound",
    "stArea",
    "stDim",
    'stream_serialno',
    'stop_bit',
    'stream_type',
    'stream_type_guid',
    'stRef',
    'tiff',    
    'title_length',
    'toc',
    "type_specific_data",  
    'VBR_bytes',
    'VBR_frames',
    'vbr_method',
    'VBR_method',
    'vbr_scale',
    'vbv_buffer_size',
    'wBitsPerSample',
    'wFormatTag',
    'xapMM',                
    'xap',
    'xing flags row',  
  ];

  public function __construct($a, &$cfg) {  
    $this->mp    = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    $this->path       = $a ['path'];
    $this->entry      = $a ['name'];
    $this->type       = $a ['typ'];
    $this->viewnumber = $a ['viewnumber'];
    $this->subtitle   = $a ['subtitle'];

    $this->cfg   = $cfg;//
    $root        = $cfg->get ( "root" );
    $root        = str_replace( "\\", "/", \Drupal::service ( 'file_system' )->realpath ( $root ) ) . "/";
    $this->p     = $this->slash( $root . $this->path ) . $this->entry ;
    $this->ext   = strtolower( pathinfo ( $this->entry, PATHINFO_EXTENSION ) );
  }
  
  /**
   * Get Info of item
   * @return string
   */
  public function Info() {
    $this->GetID3 = new \getID3();

    if ($this->isaudio() || $this->isaudiohtml5())      $exif = $this->audio();
    elseif ($this->isvideo() || $this->isvideohtml5())  $exif = $this->video();
    elseif ($this->isimage()) $exif = $this->image();
    elseif ($this->isdoc())   $exif = $this->doc();
    elseif ($this->iscmp())   $exif = $this->comp();
    elseif ($this->isapp())   $exif = $this->app();
    elseif ($this->isoth())   $exif = $this->oth();
    else   $exif = $this->t ( 'Unknown filetype' );
    return $exif;
  }

  /**
   * Is_type
   */
  public function isapp() {
    return stripos( " " . $this->cfg->get ( "app_extensions" ), $this->ext ) > 0;
  }
  public function isaudio() {
    return stripos( " " . $this->cfg->get ( "audio_extensions" ), $this->ext ) > 0;
  }
  public function isaudiohtml5() {
    return stripos( " " . $this->cfg->get ( "audiohtml5_extensions" ), $this->ext ) > 0;
  }
  public function iscmp() {
    return stripos( " " . $this->cfg->get ( "cmp_extensions" ), $this->ext ) > 0;
  }
  public function isdoc() {
    return stripos( " " . $this->cfg->get ( "doc_extensions" ), $this->ext ) > 0;
  }
  public function isimage() {
    return stripos( " " . $this->cfg->get ( "image_extensions" ), $this->ext ) > 0;
  }
  public function isoth() {
    return stripos( " " . $this->cfg->get ( "oth_extensions" ), $this->ext ) > 0;
  }
  public function isvideo() {
    return stripos( " " . $this->cfg->get ( "video_extensions" ), $this->ext ) > 0;
  }
  public function isvideohtml5() {
    return stripos( " " . $this->cfg->get ( "videohtml5_extensions" ), $this->ext ) > 0;
  }
  
  /**
   * Application "exif"
   * 
   * @return string
   */
  function app() {
    $exif = $this->t ( "Unknown file type" );
    switch ($this->ext) {
      case 'apk' :
        $exif = $this->_apk();
        break;
      case 'jar' :
        $exif = $this->_jar();
        break;
      case 'exe' :
      case 'dll' :
        $exif = $this->_exedll();
        break;
    }
    return $exif;
  }
  
  /**
   * Exif information of APk file
   * 
   * @return array
   */
  function _apk() {
    $finfo = $this->GetID3->analyze ( $this->p );
    unset ( $finfo ['zip'] ['files'] );
    $finfo = $this->arrayflat ( $finfo );
    // Here delete the non used items
     return $this->media ( $finfo, $this->t ( "Android application file" ) );     
  }
  
  /**
   * Windows application
   * 
   * @return array
   */
  function _exedll() {
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );
    // Here delete the non used items
    return $this->media ( $finfo, $this->t ( "Windows application file" ) );
  }
  
  /**
   * Exif information of JAR file
   * 
   * @return array
   */
  function _jar() {
    $finfo = $this->GetID3->analyze ( $this->p );
    unset ( $finfo ['zip'] ['files'] );
    unset ( $finfo ['zip'] ['central_directory'] );
    unset ( $finfo ['zip'] ['entries'] );
    $finfo = $this->arrayflat ( $finfo );
    // Here delete the non used items
    return $this->media ( $finfo, $this->t ( "Android application file" ) );
  }
  
  /**
   * Audio exif
   * 
   * @return array
   */
  function audio() {
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );
    // Here delete the non used items
    if( $finfo["compression_ratio"] < 1 ){
      $comp = " ( compressed )";
    } else{
      $comp = " ( not compressed )";
    }
    return $this->media ( $finfo, $this->t ( "Audio file" ) .$comp );
  }
  
  /**
   * Video exif
   * 
   * @return string
   */
  function video() {
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );
    if( $finfo["compression_ratio"] < 1 ){
      $comp = " ( compressed )";
    } else{
      $comp = " ( not compressed )";
    }
    return $this->media( $finfo, $this->t ( "Video file" ) . $comp );
  }
  
  /**
   * Exif of compressed files
   */
  function comp() {
    switch ($this->ext) {
      case 'chm' : $exif = $this->_chm(); break;
      case 'rar' : $exif = $this->_rar(); break;
      case 'zip' :
      case '7zip':
      case '7z'  :
      case 'gz'  : $exif = $this->_zip(); break;
    }
    return $exif;
  }

  /**
   * CHM Exif information
   * @return string
   */
  function _chm() {    
    $chm = \CHMLib\CHM::Fromfile ( $this->p );
    $itsf = $chm->getITSF ();
    $oslang = $itsf->getOriginalOSLanguage ();
    $t = $itsf->getTimestamp ();
    $finfo [ 'Timestamp' ] = date ( "Y.m.d H:i:m ?", $t );
    $finfo [ "Language" ] = $oslang->getLanguageName ();
    $finfo [ "Country" ] = $oslang->getCountryName ();    
    return $this->media ( $finfo, $this->t ( "CHM compressed file" ) );    
  }

  /**
   * RAR file exif information
   * @return string
   */
  function _rar() {
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );      
    return $this->media ( $finfo, $this->t ( "Rar compressed file" ) );     
  }
  
  /**
   * ZIP file exif information
   * @return string
   */
  function _zip() {
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );    
    return $this->media ( $finfo, $this->t ( "Zip compressed file" ) );
  }
  
  /**
   * doc file exif information
   * @return string
   */
  function doc() {
    $exif = "";
    switch ($this->ext) {      
      case 'xlsx' :
      case 'xls'  :
      case 'xlt'  :
      case 'xlsm' :
      case 'xltx' :
      case 'xltm' :
      case 'ods'  :
      case 'slk'  : $exif = $this->_excel(); break;      
      case 'pptx' :
      case 'docx' : $exif = $this->_docx(); break;
      case 'odt'  :
      case 'odp'  : $exif = $this->_od(); break;
      case 'ppt'  : $exif = $this->_ppt(); break;
      case 'doc'  : $exif = $this->_doc(); break;
      case 'pdf'  : $exif = $this->_pdf(); break;
    }
    return $exif;
  }
  
  /**
   * Docx / pptx exif informations
   * @return string
   */
  function _docx() {    
    $out = "";
    if (function_exists ( "zip_open" )) {
      $out = file_get_contents ( $this->mp . "/templates/exif_docx.html.twig" );
      
      // unzip the file
      $corexml = new \Xml2Assoc ();
      $a = @($corexml->parseFile ( 'zip://' . $this->p . '#docProps/core.xml' ));
      $x = $a ['cp:coreProperties'] [0];
      
      $appxml = new \Xml2Assoc ();
      $a = @($appxml->parseFile ( 'zip://' . $this->p . '#docProps/app.xml' ));
      $y = $a ['Properties'] [0];
      $out .= '<table class="smpl_exif_table">';
      if (! isset ( $x )) {
        $x['dc:creator'][0]         = $this->t ( 'Creator' );
        $x['cp:lastModifiedBy'][0]  = $this->t ( 'Modified by ' );
        $x['dcterms:created'][0][0] = $this->t ( 'Created' );
        $x['dcterms:modified'][0][0]= $this->t ( 'Modified' );
        $x['cp:revision'][0]        = $this->t ( 'Revision' );
        $x['cp:lastPrinted'][0]     = $this->t ( 'Last printed' );
      }
      
      $out = str_replace( "{{author}}"        , $x['dc:creator'] [0], $out );
      $out = str_replace( "{{last_modify_by}}", $x['cp:lastModifiedBy'] [0], $out );
      $out = str_replace( "{{created}}"       , str_replace( [ "-","T","Z"], ["."," "], $x['dcterms:created'] [0] [0] ), $out );
      $out = str_replace( "{{lastmodified}}"  , str_replace( ["-","T","Z" ], [ "."," " ], $x['dcterms:modified'] [0] [0] ), $out );
      $out = str_replace( "{{revision}}"      , $x['cp:revision'] [0], $out );
      $out = str_replace( "{{last_printed}}"  , str_replace( [ "-","T","Z"], ["."," "], $x['cp:lastPrinted'] [0] ), $out );
      
      if (! isset ( $y )) {
        $y['Application'][0] = $this->t ( 'unknown' );
        $y['AppVersion'][0]  = $this->t ( 'unknown' );
        $y['Pages'][0]       = $this->t ( 'unknown' );
        $y['Words'][0]       = $this->t ( 'unknown' );
        $y['Characters'][0]  = $this->t ( 'unknown' );
        $y['TotalTime'][0]   = $this->t ( 'unknown' );
      }
      
      $out = str_replace( "{{application}}", $y['Application'][0], $out );
      $out = str_replace( "{{appversion}}" , $y['AppVersion'][0], $out );
      $out = str_replace( "{{pages}}"      , $y['Pages'][0], $out );
      $out = str_replace( "{{words}}"      , $y['Words'][0], $out );
      $out = str_replace( "{{characters}}" , $y['Characters'][0], $out );
      $out = str_replace( "{{total_time}}" , $y['TotalTime'][0], $out );
    } else {
      $out = $this->t ( "Sorry! There is no ZIP library in PHP! I can not open the docx files!" );
    }
    return $out;
  }
  
  /**
   * Open document Exif
   * 
   * @return string
   */
  function _od() {
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );
    $type = "";
    if (function_exists( "zip_open" )) {      
      $corexml = new \Xml2Assoc ();
      $b = ($corexml->parseFile ( 'zip://' . $this->p . '#meta.xml' ));
      $b = $b ['office:document-meta'] [0];
      $a['office:version'] = $b ['office:version'];
      $b = $b ['office:meta'];
      
      $a['meta:generator']     = $b['meta:generator'] [0];
      $a['dc:creator']         = $b['dc:creator'] [0];
      $a['meta:creation-date'] = $b['meta:creation-date'] [0];
      $a['dc:cdate']           = $b['dc:date'] [0];      
      $type = " ( text )";
    }
    return $this->media ( $finfo, $this->t ( "Open document file" ) . $type);
  }

  /**
   * Excel file properties
   * @return string
   */
  function _excel(){    
    $pinfo = pathinfo($this->p);
    $ext = strtolower($pinfo["extension"]);
    switch ($ext){
      case "xls":
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $sp = $reader->load( $this->p );
        $props = $sp->getProperties();
        $type = " ( original BIFF  )";
        break;
      case "xlsx":
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $sp = $reader->load($this->p);
        $props = $sp->getProperties();
        $type = " ( Excel '97 )";
        break;
      case "ods":
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Ods();
        $sp = $reader->load($this->p);
        $props = $sp->getProperties();
        $type = " ( Open document spreadsheet )";
        break;
      case "slk":
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Slk();
        $sp = $reader->load($this->p);
        $props = $sp->getProperties();
        $type = " ( SYLK = SYmbolic LinK )";
        break;
    }

    $finfo["creator"]       = $props->getCreator();
    $finfo["created"]       = $props->getCreated();
    $finfo["modified"]      = $props->getModified();
    $finfo["lastmodifiedby"]= $props->getLastModifiedBy();
    $finfo["title"]         = $props->getTitle();
    $finfo["description"]   = $props->getDescription();
    $finfo["subject"]       = $props->getSubject();
    $finfo["keywords"]      = $props->getKeywords();
    $finfo["category"]      = $props->getCategory();
    $finfo["company"]       = $props->getCompany();
    $finfo["manager"]       = $props->getManager();
    $finfo["hyperlinkbase"] = $props->getHyperlinkBase();
    return $this->media( $finfo, $this->t( "Excel file". $type ) );
  }
  
  /**
   * pdf file Exif
   * 
   * @return string
   */
  function _pdf(){
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile( $this->p );
    $finfo = $pdf->getDetails();    
    $finfo = $this->arrayflat ( $finfo );
    // Here delete the non used items
    return $this->media( $finfo, $this->t( "PDF file" ) );
  }
  
  /**
   * Exif of Power Point
   * @return string
   */
  function _ppt(){
    //require_once __DIR__ . '/Presentation/loader.php';
    require_once realpath(__DIR__."/../../")."/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Reader/BaseReader.php";
    return $this->media( $finfo, $this->t( 'PowerPoint 2007 Presentation file' ) );
  }
  
  /**
   * Original Word doc Exif
   * 
   * @return string
   */
  function _doc(){     
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $props = $phpWord->getDocinfo(); 
    $finfo = [];   
    $finfo["creator"]      = $props->getCreator();
    $finfo["company"]      = $props->getCompany();
    $finfo["title"]        = $props->getTitle();    
    $finfo["description"]  = $props->getDescription();
    $finfo["lastmodified"] = $props->getLastModifiedBy();
    $finfo["created"]      = $props->getCreated();
    $finfo["modified"]     = $props->getModified();
    $finfo["subject"]      = $props->getSubject();
    $finfo["keywords"]     = $props->getKeywords();
    
    return $this->media( $finfo, $this->t( 'Word 97 document - under development' ) );
  }
  
  /**
   * Helper for exif functions
   * 
   * @param string $i          
   * @param int | float $e          
   * @return string
   */
  function fx($i, $e) {
    $j = strtolower ( $i );
    switch ($j) { 
      //number float
      case "exposuretime" :
      case "brightnessvalue" :
      case "compression_ratio" :
      case "shutterspeedvalue" :
      case "aperturevalue" :
      case "pixel_aspect_ratio" :
      case "gpsspeed" :        
        $e = round ( (float) $e, 3 );
        $e = number_format ( ( float ) $e, 3, ".", " " );
        break;
      case "frame_rate" :
        $e = round ( (float)$e, 2 );
        $e = number_format ( ( float ) $e, 2, ".", " " );
        break;      
        // Big number
      case "audio_sample_rate" :
      case "altitude" :
      case "bitrate" :
      case "digitalzoomratio" :
      case "gpsaltitude" :
      case "gpsdestbearing" :
      case "gpsimgdirection" :
      case "hh" :
      case 'image_size' :
      case "latitude" :
      case "longitude" :
      case 'offset' :
      case "playtime_seconds" :
      case "resolution_x" :
      case "resolution_y" :
      case "sample_rate" :
      case "size" :
      case "time_scale" :
      case "vbr_bitrate" :
      case "videodatarate" :
      case "video_resolution_x" :
      case "video_resolution_y" :
        if( is_numeric( $e ) ){
          $e = round ( $e );
          $e = number_format ( ( float ) $e, 0, ".", " " );  
        }else{
          $e = "";
        }
        break;
      case "created":
      case 'creation_date_unix':
      case "creation_time_unix" :
      case "modified":
      case "modify_time_unix" :
      case "filedatetime" :
        $e = date ( "Y.m.d H:i:s", $e );
        break;
      case "last_modified_timestamp" :
        $e = date ( "Y.m.d H:i:s", $e );
        break;
      case "datecreated" :
      case "createdate" :
      case "modifydate" :
      case "metadatadate" :
      case "last printed" :      
      case 'dc:cdate' :
      case 'meta:creation-date' :$e = str_replace( ["-","T"], ["."," "], $e );
        break;
      default :
    }
    $i = str_replace( "_", " ", $i );
    $i = strtoupper( substr( $i, 0, 1 ) ) . substr( $i, 1 );
    return '<tr class="smpl_exif_tr"><td class="smpl_exif_td">' . $this->t ( $i ) . '</td><td>' . $e . "</td></tr>\n";
  }
  
  /**
   * Image exif
   * 
   * @return string
   */
  function image(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat ( $finfo );    
    return $this->media( $finfo, $this->t ( "Image file" ) );
  }
  
  /**
   *
   * @return string
   */
  function oth(){
    switch ($this->ext) {
      case "txt" :
        $exif = $this-> t ( "Simple ASC or UTF8 / Unicode text file" );
        break;
    }
    return $exif;
  }
  
  /**
   * Media info
   * 
   * @param array $finfo          
   * @param string $title          
   * @return mixed
   */
  function media($finfo, string $title) {
    $out = file_get_contents( __DIR__ . '/../../templates/exif_table.html.twig' );
    $exif = "";

    foreach( $this->del as $e ) {
      unset( $finfo [$e] );
    }

    foreach($finfo AS $i => $e ){
      if(is_numeric($i)){
        unset($finfo[$i]);
      }
    }
    // Numeric format
    foreach( $finfo as $i => $e ) {
      if (! is_numeric( $i ) && ! empty( trim( $e ) ) && $this->is_utf8( $e )) {
        $exif .= $this->fx( $i, $e );
      }
    }
    return str_replace( ['{{ title }}', '{{ rows }}' ], [$title, $exif], $out );
  }
  
  /* Helper methods */
  
  /**
   * Makes flat array from an array - recursive
   * 
   * @param array $in
   * @param string $parent
   * @return array[]
   */
  function arrayflat( $in ) {
    $a = [ ];
    foreach( $in as $i => $e ) {
      if ( is_array( $e ) ) { 
        if( array_search( $i, $this->spec)){
          $add[$i] = $e[0];
        }else{
          $add = $this->arrayflat ( $e );
        }
        $a = array_merge($a, $add );  
      } else {
        $a[$i] = $e;
      }
    }
    return $a;
  }  
  
  /**
   * the string is utf-8?
   * 
   * @param string $str
   * @return boolean
   */
  function is_utf8($str ='') {
    return ( bool ) preg_match ( '//u', $str );
  } 
  	/**
	 * it makes slash from backslash or double slash
	 * @param mixed $p 
	 * @return string|string[] 
	 */
	public function slash($p){
		return str_replace(["\\","//"],'/',$p);
	}
}