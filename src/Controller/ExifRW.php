<?php
namespace Drupal\smplphotoalbum\Controller;

/**
 * Read and Write Exif/Copyright info from a jpeg (or TIFF) file
 * @author fz
 *
 */
class ExifRW{
  /**
   * Exif read copyright information
   * @param string $src
   * @return string
   */  
  function read($src =""){
    $exif = exif_read_data($src);
    if($exif === False){
      return "";
    }
    if(isset($exif['COMPUTED']['Copyright'])){
      return $exif['COMPUTED']['Copyright'];
    }
    return "";
  }
  
  function write($src, $wmtext){    
    $iptc = new iptc($src);    
    $iptc->set(IPTC_COPYRIGHT_STRING, $wmtext);
    $iptc->write();
  }
}