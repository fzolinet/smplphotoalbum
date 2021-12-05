<?php
namespace PhpOffice\PhpWord;
require_once "PhpWord.php";
require_once "IOFactory.php";

$reader = IOFactory::createReader('MsDoc');
$phpWord = $reader->load($this->p);


$prop = $phpWord->getDocInfo();
$finfo['title']          = $prop->getTitle();
$finfo['subject']        = $prop->getSubject();
$finfo['creator']        = $prop->getCreator();
$finfo['keywords']       = $prop->getKeywords();
$finfo['category']       = $prop->getCategory();
$finfo['manager']        = $prop->getManager();
$finfo['created']        = Date("Y.m.d H:i:s",$prop->getCreated());
$finfo['lastmodifiedby'] = $prop->getLastModifiedBy();
$finfo['modified']       = Date("Y.m.d H:i:s",$prop->getModified());
$finfo['company']        = $prop->getCompany();
$finfo['description']    = $prop->getDescription();
$finfo['customprops']    = $prop->getCustomProperties();
//var_dump($finfo);
return $finfo;