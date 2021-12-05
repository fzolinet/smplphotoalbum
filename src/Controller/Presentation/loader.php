<?php
namespace PhpOffice\PhpPresentation;
require_once __DIR__.'/src/PhpPresentation/Autoloader.php';
\PhpOffice\PhpPresentation\Autoloader::register();
require_once __DIR__.'/../PhpOffice/Common/src/Common/Autoloader.php';
\PhpOffice\Common\Autoloader::register();

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Shape\RichText;
$pptReader = IOFactory::createReader('PowerPoint97');
$finfo = [];
try{
  $pptprezi = $pptReader->load($this->p);
  $prop = $pptprezi->getDocumentProperties();
  
  $finfo['title']        = $prop->getTitle();
  $finfo['creator']      = $prop->getCreator();
  $finfo['created']      = date('Y.m.d H:i:s',$prop->getCreated());
  $finfo['lastmodified'] = $prop->getLastModifiedBy();
  $finfo['modified']     = date('Y.m.d H:i:s',$prop->getModified());
  $finfo['description']  = $prop->getDescription();
  $finfo['subject']      = $prop->getSubject();
  $finfo['keywords']     = $prop->getKeywords();
  $finfo['category']     = $prop->getCategory();
  $finfo['company']      = $prop->getCompany();
}catch(\Exception $e){
  $finfo['unknown'] = t('Unknown file structure');  
}
return $finfo;