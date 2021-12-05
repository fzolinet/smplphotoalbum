<?php
namespace Drupal\smplphotoalbum\Controller;
use Drupal\smplphotoalbum\Controller\GetID3\GetID3 AS GetID3;
use Drupal\smplphotoalbum\Controller\Xml2Assoc\Xml2Assoc AS Xml2Assoc;

class Exif { 
  private $mp;
  private $entry;
  private $type;
  private $path;
  private $p;
  private $cfg;

  private $verbose;
  private $exif;
  private $GetID3;
  public function __construct($a, &$cfg)
  {    
    $this->mp     = drupal_get_path('module', 'smplphotoalbum');
    $this->path   = $a ['path'];
    $this->entry  = $a ['name'];
    $this->type   = $a ['typ'];
    $this->cfg    = $cfg;
    $root         = $cfg->get("root");    
    $root         = str_replace("\\","/", \Drupal::service('file_system')->realpath($root))."/";    
    $this->p      = str_replace(["\\","//"],["/","/"],$root . $this->path . $this->entry);
    $this->ext    = strtolower ( pathinfo ( $this->entry, PATHINFO_EXTENSION ) );
    
  }
  
  public function Info(){
    $this->checkid3();
    try{
      $this->GetID3 = new GetID3();
    }catch(Exception $e){
      $this->checkid3();
    }    
    if( $this->isaudio() ||$this->isaudiohtml5())
    $exif = $this->audio();
    elseif($this->isvideo() || $this->isvideohtml5() )
      $exif = $this->video();
    elseif( $this->isimage())
      $exif = $this->image();      
    elseif( $this->isdoc() )
      $exif = $this->doc();
    elseif( $this->iscmp() )
      $exif = $this->comp();
    elseif( $this->isapp() )
      $exif = $this->app();
    elseif( $this->isoth() )
      $exif = $this->oth();
    else{
      $exif = t('Unknown filetype');
    }      
    return $exif;
  }
  
  public function isapp(){
    return stripos(" ".$this->cfg->get("app_extensions"), $this->ext) > 0;
  }
  
  public function isaudio(){     
    return stripos(" ".$this->cfg->get("audio_extensions"), $this->ext)> 0;
  }
  
  public function isaudiohtml5(){
    return stripos(" ".$this->cfg->get("audiohtml5_extensions"), $this->ext)> 0;
  }
  
  public function iscmp(){
    return stripos(" ".$this->cfg->get("cmp_extensions"), $this->ext)> 0;
  }
  
  public function isdoc(){
    return stripos(" ".$this->cfg->get("doc_extensions"), $this->ext)> 0;
  }
  
  public function isimage(){
    return stripos(" ".$this->cfg->get("image_extensions"), $this->ext) > 0;
  }
  public function isoth(){
    return stripos(" ".$this->cfg->get("oth_extensions"), $this->ext) > 0;
  }
  
  public function isvideo(){
    return stripos(" ".$this->cfg->get("video_extensions"), $this->ext)> 0;
  }
  
  public function isvideohtml5(){
    return stripos(" ".$this->cfg->get("videohtml5_extensions"), $this->ext)> 0;
  }
  
  /**
   * Application "exif"
   * @return string
   */
  function app(){
    $exif = t ("Unknown file type");
    switch ($this->ext) {
      case 'apk':
        $exif= $this->_apk();
        break;
      case 'jar':
        $exif= $this->_jar();
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
   * @return unknown
   */
  function _apk(){
    $finfo = $this->GetID3->analyze ( $this->p );
    unset($finfo['zip']['files']);
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo,t("Android application file"));
    return $out;
  }
  
  /**
   * Windows application
   * @return unknown
   */
  function _exedll(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo,t("Windows application file"));
    return $out;
  }
    
  /**
   * Exif information of JAR file
   * @return unknown
   */
  function _jar(){
    $finfo = $this->GetID3->analyze ( $this->p );
    unset($finfo['zip']['files']);
    unset($finfo['zip']['central_directory']);
    unset($finfo['zip']['entries']);
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo,t("Android application file"));
    return $out;
  }
  
  /**
   * Audio exif
   * @return mixed
   */
  function audio(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);    
    // Here delete the non used items
    
    $out = $this->media( $finfo,t("Audio"));
    return $out;
  }
  
  /**
   * Exif of compressed files
   */
  function comp(){
    switch($this->ext){
      case 'chm':
        $exif = $this->_chm();
        break;
      case 'rar':
        $exif = $this->_rar();
        break;
      case 'zip':
        $exif = $this->_zip();
        break;
    }
    return $exif;
  }
  
  function printTree($tree, $level)
  {
    if ($tree !== null) {
      foreach ($tree->getItems() as $child) {
        echo str_repeat("\t", $level).print_r($child->getName(), 1)."\n";
        $this->printTree($child->getChildren(), $level + 1);
      }
    }
  }
  
  function _chm(){
    require __DIR__.'/CHM/CHMLib.php';
    $chm = \CHMLib\CHM::Fromfile($this->p);    
    
   // $toc = $chm->getTOC();
    $itsf = $chm->getITSF();
    $t = $itsf->getTimestamp();  
    $finfo['Timestamp']= date("Y.m.d H:i:m ?",$t);
    $oslang            = $itsf->getOriginalOSLanguage();    
    $finfo["Language"] = $oslang->getLanguageName();
    $finfo["Country"]  = $oslang->getCountryName();
   // fz_t($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo,t("CHM compressed file"));
    return $out;
  }
  
  function _rar(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    
    $out = $this->media( $finfo,t("Rar compressed file"));
    return $out;
  }

  function _zip(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo,t("Zip compressed file"));
    return $out;
  }
  
  /**
   * doc exif
   */
  function doc( ){
    $exif = "";
    switch ($this->ext) {
      
      case 'xls' :
        $exif = $this->_excel();
        break;
      case 'docx' :
      case 'xlsx' :
      case 'pptx' :
        $exif = $this->_docx();
        break;
      case 'odt':
      case 'ods':
      case 'odp':
        $exif = $this->_od();
        break;
      case 'ppt' :        
        $exif = $this->_ppt();
        break;
      case 'doc' :
        $exif = $this->_doc();
        break;
      case 'pdf' : // pfd properties
        $exif = $this->_pdf();
        break;
    }
    return $exif;
  }
  
  /**
   * Docx exif informations
   */
  
  function _docx(){
    //require_once $this->mp . '/lib/docx/xml2assoc.php';
    $out = "";
    if (function_exists ( "zip_open" )) {
      $out = file_get_contents($this->mp."/templates/exif_docx.html.twig");
      
      // unzip the file
      $corexml = new Xml2Assoc();
      $a = @($corexml->parseFile ( 'zip://' . $this->p . '#docProps/core.xml' ));
      $x = $a ['cp:coreProperties'] [0];
      
      $appxml = new Xml2Assoc ();
      $a = @($appxml->parseFile ( 'zip://' . $this->p . '#docProps/app.xml' ));
      $y = $a ['Properties'] [0];
      $out .= '<table class="smpl_exif_table">';
      if (! isset ( $x )) {
        $x ['dc:creator'] [0] = t ( 'unknown' );
        $x ['cp:lastModifiedBy'] [0] = t ( 'unknown' );
        $x ['dcterms:created'] [0] [0] = t ( 'unknown' );
        $x ['dcterms:modified'] [0] [0] = t ( 'unknown' );
        $x ['cp:revision'] [0] = t ( 'unknown' );
        $x ['cp:lastPrinted'] [0] = t ( 'unknown' );
      }
      
      $out = str_replace("{{author}}",$x ['dc:creator'] [0] ,$out);
      $out = str_replace("{{last_modify_by}}", $x['cp:lastModifiedBy'] [0],$out);
      $out = str_replace("{{created}}"       , str_replace(["-","T","Z"],["."," "],$x ['dcterms:created'] [0] [0]),$out);
      $out = str_replace("{{lastmodified}}"  , str_replace(["-","T","Z"],["."," "],$x ['dcterms:modified'] [0] [0]),$out);
      $out = str_replace("{{revision}}", $x ['cp:revision'] [0] ,$out);
      $out = str_replace("{{last_printed}}"  , str_replace(["-","T","Z"],["."," "], $x ['cp:lastPrinted'] [0]) ,$out);
      
      if (! isset ( $y )) {
        $y ['Application'] [0] = $y ['AppVersion'] [0]  =
        $y ['Pages'] [0]       = $y ['Words'] [0]       =
        $y ['Characters'] [0]  = $y ['TotalTime'] [0]   = t ( 'unknown' );
      }
      
      $out = str_replace("{{application}}", $y ['Application'] [0],$out);
      $out = str_replace("{{appversion}}", $y ['AppVersion'] [0],$out);
      $out = str_replace("{{pages}}", $y ['Pages'] [0],$out);
      $out = str_replace("{{words}}", $y ['Words'] [0],$out);
      $out = str_replace("{{characters}}", $y ['Characters'] [0],$out);
      $out = str_replace("{{total_time}}", $y ['TotalTime'] [0],$out);
    }else{
      $out = t ( "Sorry! There is not ZIP library in PHP! I can not open the docx files!" );
    }
    return $out;
  }
  
  
  /**
   * Open document Exif
   * @return unknown
   */
  function _od(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    if (function_exists ( "zip_open" )) {
      $corexml = new Xml2Assoc();
      $b = ($corexml->parseFile ( 'zip://' . $this->p . '#meta.xml' ));
      $b = $b['office:document-meta'][0];      
      $a['office:version'] = $b['office:version'];
      $b = $b['office:meta'];
      
      $a['meta:generator']     = $b['meta:generator'][0];
      $a['dc:creator']         = $b['dc:creator'][0];
      $a['meta:creation-date'] = $b['meta:creation-date'][0];
      $a['dc:cdate']           = $b['dc:date'][0];
      // $a = $this->arrayflat($a);
      
    }
    $out = $this->media( $finfo,t("Open document file"));
    return $out;
  }
  
  function _excel(){
    
    $exif = "";
    return $exif;
  }
  
  /**
   * pdf file Exif
   * @return unknown
   */
  function _pdf(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo,t("PDF file"));
    return $out;
  }
  
  /**
   * Exif of Power Point
   */
  function _ppt(){    
    require_once __DIR__.'/Presentation/loader.php';
    $out= $this->media($finfo, t('PowerPoint 2007 Presentation file'));
    return $out;
  }
  
  /**
   * Original Word doc Exif
   * @return string
   */
  function _doc(){
    require "PhpOffice/phpword/src/PhpWord/loader.php";
    $out= $this->media($finfo, t('Word 97 document - under development'));
    return $out;
  }

  
  /**
   * Helper for exif functions
   * @param unknown $i
   * @param unknown $e
   * @return string
   */
  function fx($i, $e){
    $j = strtolower($i);
    switch($j){
      case "exposuretime":
      case "brightnessvalue":
      case "compression_ratio":
      case "shutterspeedvalue":
      case "aperturevalue":
      case "pixel_aspect_ratio":
      case "gpsspeed":
        $e = round($e,3);
        $e = number_format( (float) $e,3,"."," ");
        break;
      case "frame_rate":
        $e = round($e,2);
        $e = number_format( (float) $e,2,"."," ");
        break;
      case "vbr_bitrate":
      case "latitude":
      case "longitude":
      case "digitalzoomratio":
      case "videodatarate":
      case "bitrate":
      case "playtime_seconds":
      case "video_resolution_x":
      case "video_resolution_y":
      case "resolution_x":
      case "resolution_y":
      case "audio_sample_rate":
      case "altitude":
      case "gpsaltitude":
      case "gpsdestbearing":
      case "gpsimgdirection":
        $e = round($e);
        $e = number_format( (float) $e,0,"."," ");
        break;
        //case "creation_time":
      case "creation_time_unix":
        //case "modify_time":
      case "modify_time_unix":
      case "filedatetime":
        $e = date("Y.m.d H:i:s",$e);
        break;
      case "last_modified_timestamp":
        $e = date("Y.m.d H:i:s",$e);
        break;
      case "datecreated":
      case "createdate":
      case "modifydate":
      case "metadatadate":
      case "last printed":
      case "created":
      case 'dc:cdate':
      case 'meta:creation-date':
        $e = str_replace(["-","T"],["."," "], $e);
        break;
      default:
    }
    $i = str_replace("_"," ",$i);
    $i = strtoupper(substr($i,0,1)).substr($i,1);
    return '<tr class="smpl_exif_tr"><td class="smpl_exif_td">'.t($i).'</td><td>'.$e."</td></tr>\n";
  }


  /**
   * Image exif
   * @return unknown
   */
  function image(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    
    $out = $this->media( $finfo,t("Image"));
    return $out;
  }

  /**
   *
   * @return string
   */
  function oth(){
    switch ($this->ext) {
      case "txt" :
        $exif = "Simple ASC or UTF8 / Unicode text file";
        break;
    }
    return $exif;
  }
  
  /**
   * Media info
   * @param unknown $finfo
   * @param unknown $title
   * @return mixed
   */
  function media( $finfo, $title){
    $out = file_get_contents(__DIR__ . '/../../templates/exif_table.html.twig');
    $exif ="";
    $del = [
        "filepath","filename","filenamepath","filesize" ,"FileName",
        "FileSize","FileType","html","SectionsFound","GETID3_VERSION",
        "mwg-rs","stArea", "apple-fi","stDim",'about',
        "compression_profile",
        "objectid","objectid_guid", "fileid", "fileid_guid",
        'reserved_1','reserved_1_guid', 'reserved_2','stream_type','stream_type_guid',
        'error_correct_type', 'error_correct_guid',
        'reserved','reserved_guid', "type_specific_data",
        'extra_field_data',"audio_signature",
        'instanceID', 'documentID', 'DocumentID','stRef', 'xap', 'photoshop', 'pdf', 'xapMM','tiff','exif','dc'        
    ];
    foreach($del AS $e){
      unset($finfo[$e]);
    }
   // fz_t($finfo);
    foreach($finfo AS $i => $e){
      if(!is_numeric($i) && !empty(trim($e )) &&  $this->is_utf8($e) )  {
        $exif .= $this->fx($i, $e);
      }
    }
    $out = str_replace('{{ title }}',$title,$out);
    $out = str_replace('{{ rows }}',$exif ,$out);
    return $out;
  }
  
  
  
  /**
   * Video exif
   * @return unknown
   */  
  function video(){
    $finfo = $this->GetID3->analyze ( $this->p );
    $finfo = $this->arrayflat($finfo);
    // Here delete the non used items
    $out = $this->media( $finfo, t("Video"));
    return $out;
  }


 
/* Helper methods*/
  
  /**
   * Makes flat array from an array
   * @param unknown $in
   * @return array|unknown[]
   */
  function arrayflat($in){
    $a = [];
    foreach($in AS $i => $e){
      if(is_array($e)){
        $add = $this->arrayflat($e);
        $a = array_merge($a,$add);
      }else{        
        $a[$i] = $e;
      }
    }
    return $a;
  }
  
  /**
   * GetID3 lib checking
   */
  function checkid3(){
    $p = __DIR__."/GetID3";
    $this->checkrec($p);
  }
  
  function checkrec($p){
    $d = opendir($p);
    while($entry = readdir($d) )
    {
      if($entry == "." || $entry ==".." ){
        continue;
      }
      
      if(is_dir($p."/".$entry)){
        $this->checkrec($p."/".$entry);
      }else{
        if(pathinfo($entry, PATHINFO_EXTENSION)== "php")
        {
          $str = file_get_contents($p."/".$entry);
          $str = str_replace("namespace JamesHeinrich","namespace Drupal\\smplphotoalbum\\Controller", $str);
          $str = str_replace("use JamesHeinrich","use Drupal\\smplphotoalbum\\Controller", $str);
          file_put_contents($p."/".$entry, $str);
        }
      }
    }
  }
  
  /**
   * the string is utf-8?
   * @param unknown $str
   * @return boolean
   */
  function is_utf8($str){
    return (bool) preg_match('//u', $str);
  }
    
  
}

?>