<?php
/**
 * get pdf properties
 * @param unknown_type $filename
 * @return string
 */
function smpl_getpdfdata($filename){

	$pdfparserpath = realpath(dirname(__FILE__));
	if(stripos($_SERVER['SERVER_SOFTWARE'],'WIN')){
		$link = $pdfparserpath.'/pdfinfo.exe "'.$filename.'"';
	}else{
		$link = $pdfparserpath.'pdfinfo '.$filename;
	}

	exec($link,$a);
	foreach($a As $i => $sor){
	    $x = preg_split("/:/",$sor);
	    $b[$x[0]] = $x[1];
	}
	return $b;
}
?>
