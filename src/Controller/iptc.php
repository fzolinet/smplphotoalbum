<?php
namespace Drupal\smplphotoalbum\Controller;

DEFINE('IPTC_OBJECT_NAME', '005');
DEFINE('IPTC_EDIT_STATUS', '007');
DEFINE('IPTC_PRIORITY', '010');
DEFINE('IPTC_CATEGORY', '015');
DEFINE('IPTC_SUPPLEMENTAL_CATEGORY', '020');
DEFINE('IPTC_FIXTURE_IDENTIFIER', '022');
DEFINE('IPTC_KEYWORDS', '025');
DEFINE('IPTC_RELEASE_DATE', '030');
DEFINE('IPTC_RELEASE_TIME', '035');
DEFINE('IPTC_SPECIAL_INSTRUCTIONS', '040');
DEFINE('IPTC_REFERENCE_SERVICE', '045');
DEFINE('IPTC_REFERENCE_DATE', '047');
DEFINE('IPTC_REFERENCE_NUMBER', '050');
DEFINE('IPTC_CREATED_DATE', '055');
DEFINE('IPTC_CREATED_TIME', '060');
DEFINE('IPTC_ORIGINATING_PROGRAM', '065');
DEFINE('IPTC_PROGRAM_VERSION', '070');
DEFINE('IPTC_OBJECT_CYCLE', '075');
DEFINE('IPTC_BYLINE', '080');
DEFINE('IPTC_BYLINE_TITLE', '085');
DEFINE('IPTC_CITY', '090');
DEFINE('IPTC_PROVINCE_STATE', '095');
DEFINE('IPTC_COUNTRY_CODE', '100');
DEFINE('IPTC_COUNTRY', '101');
DEFINE('IPTC_ORIGINAL_TRANSMISSION_REFERENCE',     '103');
DEFINE('IPTC_HEADLINE', '105');
DEFINE('IPTC_CREDIT', '110');
DEFINE('IPTC_SOURCE', '115');
DEFINE('IPTC_COPYRIGHT_STRING', '116');
DEFINE('IPTC_CAPTION', '120');
DEFINE('IPTC_LOCAL_CAPTION', '121');

class iptc {
  private $isiptc = false;
  private $file = false;
  private $iptc = array();
  
  function __construct($filename) {
    $size = getimagesize($filename,$info);
            
    $this->isiptc = isset($info["APP13"]);
    if(isset($this->isiptc) && !empty($this->isiptc)){
      $this->iptc = iptcparse($info["APP13"]);
    }              
    $this->file = $filename;
  }
        
  function set($tag, $data) {
    $this->iptc["2#".$tag] = Array( $data );
    $this->isiptc = True;
  }
        
  function get($tag) {
    return isset($this->iptc["2#".$tag]) ? $this->iptc["2#".$tag][0] : false;
  }
       
  function dump() {
    //fz_t($this->iptc);
  }
        
  function binary() {
    $iptc_new = '';
    foreach (array_keys($this->iptc) as $s) {
      $tag = str_replace("2#", "", $s);
      $iptc_new .= $this->iptc_maketag(2, $tag, $this->iptc[$s][0]);
    }       
    return $iptc_new;   
  }
        
  function iptc_maketag($rec,$dat,$val) {
    $len = strlen($val);
    if ($len < 0x8000) {
      return chr(0x1c) . chr((int) $rec) . chr((int) $dat) . 
             chr($len >> 8). chr($len & 0xff). $val;
    } else {
      return chr(0x1c) . chr((int) $rec) . chr((int) $dat) . 
             chr(0x80).chr(0x04).chr(($len >> 24) & 0xff) .
             chr(($len >> 16) & 0xff).chr(($len >> 8 ) & 0xff).
             chr(($len ) & 0xff). $val;
    }
  }   
  
  function write() {
    $mode = 0;
    $content = iptcembed($this->binary(), $this->file, $mode);  
    $db = file_put_contents($this->file, $content );    
  }   
};