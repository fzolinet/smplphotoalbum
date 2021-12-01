<?php

/**
 * Properties of image
 * @param int $id
 */
function smplphotoalbum_exif($id){
  if (!smplphotoalbum_access() || empty($id) ){
    return '-1';
  }
  
  $str = file_get_contents(__DIR__.'/../templates/exif.tpl');
  $root = smpl_root();
  
  $record = db_select("smplphotoalbum","s")
    ->fields('s',array('id','path','name','type'))
    ->condition('id',$id,'=')
    ->execute()->fetchAssoc();
  
  $path  = $record['path'];
  $entry = $record['name'];
  $type  = $record['type'];
  $ext   = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
  
  if($type=='image'){
    $imagesize = getimagesize($root.$path.$entry);
    $fsize = $imagesize[0].'x'.$imagesize[1].' ';
  }
  
  $verbose = 0;
  $p = $root.$path.$entry;
  
  //Audio-video properties
  switch($ext){
    case 'mp3' :
    case 'ogg' :
    case 'wav' :
    case 'avi' :
    case 'mpeg':
    case 'divx':
    case 'wma' :
    case 'flac':
    case 'aac' :
    case 'ac3' :
    case 'asf' :
    case 'mov' :
    case '3gp' :
    case 'mp4' :
    case 'm4v' :
    case 'mpc' :
    case 'qt'  :
    case 'ra'  :
    case 'flv' :
    case 'rm'  :
    case 'wmv' :
    case 'voc' :
    case 'mod' :
    case 'la'  :
    case 'midi':
    case 'mid' :
      $exif = smpl_media_read($p, $id, $verbose);
      break;
    case 'xlsx':
    case 'xls' :
      $exif = smpl_excel_read($p, $id, $verbose);
      break;
    case 'docx':
    case 'pptx':
      $exif = smpl_docx_read( $p, $id, $verbose);
      break;
    case 'doc' :
      $exif = smpl_doc_read ( $p, $id, $verbose);
      break;
    case 'pdf' :  //pfd properties
      $exif = smpl_pdf_read ( $p, $id, $verbose);
      break;
    case 'zip':
    case 'jar':
    case 'apk':
      $exif = smpl_zip_read( $p, $id, $verbose);
      break;
    case 'rar':
      $exif = smpl_rar_read( $p, $id, $verbose);
      break;
    default:   //images properties
      $exif = smpl_exif_read( $p, $id, $verbose);
      if($exif ==""){
        $exif = t("Resolution").": ".$fsize;
      }
  }
  
  $str = str_replace('{{exif}}',$exif, $str);
  die($str);
  return;
}