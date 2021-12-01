<?php
use \ForceUTF8\Encoding;

function _smpl_icon($entry, $type, $types){
	$path_parts = pathinfo($entry);
	$ext = (isset($path_parts['extension']))? strtolower($path_parts['extension']):"";
	$str = 'unknown';
	
	switch($type){
		case 'doc'  :
			$str = in_array($ext, $types['doc'] )? 'doc_'.$ext.".png" : 'doc.png';
			break;
		case 'audiohtml5':
			$str = in_array($ext, $types['audiohtml5'] )? 'audio_'.$ext.".png" : 'audio.png';
			break;
		case 'audio':
			$str = in_array($ext, $types['audio'] )? 'audio_'.$ext.".png" : 'audio.png';
			break;
		case 'videohtml5':
		  $str = in_array($ext, $types['videohtml5'] )? 'video_'.$ext.".png" : 'video.png';
		  break;
		case 'video':
			$str = in_array($ext, $types['video'] )? 'video_'.$ext.".png" : 'video.png';
			break;
		case 'cmp':
		  $str = in_array($ext, $types['cmp'] )  ? 'cmp_'.$ext.".png" : 'cmp.png';
		  break;
		case 'app':
		  $str = in_array($ext, $types['app'] )? 'app_'.$ext.".png" : 'app.png';
		  break;
		case 'other':
		  $str = in_array($ext, $types['other'] )? 'oth_'.$ext.".png" : 'other.png';
		  break;
	}
	return $str;
}
/*
 * Random generator speed: http://www.greggdev.com/web/articles.php?id=6
 */

function smpl_randomimage(&$params){
  $ok = false;
  $n = 10;
  $root = $params["root"];
  $images = preg_split('/ /',SMPLIMGEXT);
  $sql  = "SELECT *, RAND() AS r FROM {smplphotoalbum}\n";
  $sql .= "  WHERE deleted<>1 AND path LIKE '".$params['path']."%' AND \n";
  $sql .= "  type LIKE 'image' \n";
  $sql .= " ORDER BY r \n";
  $sql .= " LIMIT ".$n;
  $qry  = db_query($sql);
 
  do {
    $result = $qry->fetchAssoc();
    $imagepath = realpath($root.$result['path'].$result['name']);
    $n--;
  } while( !file_exists( $imagepath) && 0 <= ($n--) );

	$style='class="smpl_randomimage" ';
	$root = smpl_root();

	$modulepath = $params["modulepath"];

	$str = RenderImage($result,$modulepath);
	return $str;
}
/*
 * Soremelés törlése a templatekből
 */
function nldel($s){
  return $s;
  $s = str_replace(chr(10),"",$s);
  $s = str_replace(chr(13),"",$s);
  $s = str_replace("<br/>","",$s);
  $s = str_replace("<br>","",$s);
  $s = str_ireplace(array("\n","\r"),array("",""), $s);
  return $s;
}

function RenderImage(&$par, $modulepath){
    $tplp = $modulepath."/templates";
    $str = file_get_contents($tplp."/image.tpl");
    $str = nldel($str);
    $str = str_replace('{{id}}', $par['id']   , $str);
    $str = str_replace('{{smpl_simplelist}}' , '', $str);
    
    $v    = $modulepath."/v.php?i=";
    $link = $v.$par["path"].$par["name"];	//for social network
    
    $str  = str_replace ('{{imagelink}}', $link, $str);
    $str  = str_replace ('{{imgsub}}'   , (empty($par['caption'])? $par['name']: $par['caption'] ), $str);
    //Using overbrowser setting
    $att   = ' target="_blank" ';
    $class = '';
    $overbrowser = variable_get('smpl_overbrowser',true);
    if($overbrowser or $overbrowser == 1){
        if(variable_get('smpl_overbrowser_paging',true)){
            $rnd = $par['path'];
        }else{
            $rnd = mt_rand(1,9999999);
        }
        
        $obtype= variable_get('smpl_overbrowser_type');
        if ($obtype == "cb")     $class=' colorbox';
        else if($obtype == "lb") $att = ' rel="lightbox['. $rnd .']"';
    }
            
    $str = str_replace ('{{class}}', $class, $str);
    $str = str_replace ('{{attrib}}', $att, $str);
    
    //IMG tag make
    $root = smpl_root();
    
    if(variable_get("smpl_direct")==1){
        $dr   = realpath($_SERVER["DOCUMENT_ROOT"]);
        $dr   = str_replace('\\',"/",$dr);
        $p    = str_replace($dr,"", $root );
        $path = "http://".$_SERVER["HTTP_HOST"].$p.$par["path"] .( is_readable( $root . $par["path"]. TN . $par["name"]) ? TN : '' ) . $par["name"];
        
    }else{
        //make url parameter
        $path = $v.$par["path"].( is_readable( $root . $par["path"] . TN . $par["name"] ) ? TN : '' ) . $par["name"] .'" ';
    }
    
    $st ="";
    $width = variable_get('smpl_width',150);
    $w   =  "width:".$width."px;";
    $sty = "$w $st";
    $str = str_replace('{{sty}}'   , $sty , $str);
    $str = str_replace('{{imgsrc}}', $path, $str);
    return $str;
}

/*
 * Get relative path of file
 */
/*
function _smpl_path2url($p){
	$f = str_replace('\\','/',smpl_variable_get("").$p);
	$f = substr($f,strlen($_SERVER['DOCUMENT_ROOT']))."/";
	return $f;
}
*/
function _drupal_get_root_path(){
	$fldr = getcwd();
	$f = $fldr;
	$i = 10;
	while($i>0 && !file_exists($f."/includes/bootstrap.inc")){
		chdir("..");
		$f=getcwd();
		$i--;
	}
	chdir($fldr);
	$f = str_replace('\\','/',$f);
	$f = substr($f,strlen($_SERVER['DOCUMENT_ROOT']))."/";
	return $f;
}

/*
 * Exif properties of an image
 */
function smpl_exif_read($p,$id='', $verbose =0){
	$smpl_exif = read_exif_data_raw($p,$verbose);
	$out = "";
	$exi  = _ex($smpl_exif,'Make','IFD0','Make');
	$exi .= _ex($smpl_exif,'Model','IFD0','Model');
	$exi .= _ex($smpl_exif,'Using software','IFD0','Software');
	$exi .= _ex($smpl_exif,'Orientation','IFD0','Orientation');
	$exi .= _ex($smpl_exif,'Last modify time','IFD0','DateTime');
	$exi .= _ex($smpl_exif,'Artist','IFD0','Artist');
	$exi .=	_ex($smpl_exif,'Photographed time','SubIFD','DateTimeDigitized');
	$exi .=	_ex($smpl_exif,'Exposure time','SubIFD','ExposureTime');
	$exi .=	_ex($smpl_exif,'Aperture Size','SubIFD','FNumber');
	$exi .=	_ex($smpl_exif,'Focal length','SubIFD','FocalLength');
	$exi .=	_ex($smpl_exif,'Light source','SubIFD','LightSource');
	$exi .=	_ex($smpl_exif,'Flash','SubIFD','Flash');
	$exi .=	_ex($smpl_exif,'Exif Image Width','SubIFD','ExifImageWidth');
	$exi .=	_ex($smpl_exif,'Exif Image Height','SubIFD','ExifImageHeight');
	$exi .=	_ex($smpl_exif,'Compression','IFD1','Compression');
	if(strlen($exi)>0){
		$out .= '<table class="smpl_exif_table">';
		$out .= $exi;
		$out .= '</table>';
	}
	return $out;
}

function __decode($str){
  $str = preg_replace('/[\x00-\x1F]/','',$str);
	$str = preg_replace('/[\xfe-\xff]/','',$str);
	return $str;
}

/**
 * Media EXIF adatok olvasása
 * @param unknown $p
 * @param string $id
 * @param number $verbose
 * @return string
 */
function smpl_media_read($p,$id='', $verbose =0){
  require_once(__DIR__.'/../lib/getid3/getid3/getid3.php');

// Initialize getID3 engine
	$getID3 = new getID3();

// Analyze file and store returned data in $finfo
	$finfo = $getID3->analyze($p);

	getid3_lib::CopyTagsToComments($finfo);

	$out  = "";
	$out .='<table class="smpl_exif_table">';
	$out .= smpl_fx(t('Artist'),@$finfo['comments_html']['artist'][0]); //artist
	if(isset($finfo['tags']['id3v2']['title'][0])){
		$out .= smpl_fx(t('Title') ,$finfo['tags']['id3v2']['title'][0]);
	}

	if(isset($finfo['video'])){
		$a = $finfo['video'];
		$out .= smpl_fx('<b>'.t('Video').'</b>' ,' ');
		$out .= smpl_fx(t('Data format') ,@$a['dataformat']);
		$out .= smpl_fx(t('Bitrate mode'),@$a['bitrate_mode']);
		$out .= smpl_fx(t('total frames'),@$a['total_frames']);
		$out .= smpl_fx(t('Frame rate')  ,@(int)($a['frame_rate'])."fps");
		$out .= smpl_fx(t('Length')      ,(int) (@($a['total_frames']/$a['frame_rate'])),'sec');
    $out .= smpl_fx(t('Lossless')    ,@$a['lossless'] );         // true = lossless compression; false = lossy compression
    $out .= smpl_fx(t('Resolution X'),@$a['resolution_x']);      // horizontal dimension of video/image in pixels
    $out .= smpl_fx(t('Resolution Y'),@$a['resolution_y']);     // vertical dimension of video/image in pixels
    $out .= smpl_fx(t('Codec')       ,@$a['codec']);
    $out .= smpl_fx(t('Pixel Aspect Ratio'),@$a['pixel_aspect_ratio']); // pixel display aspect ratio
	}
	
	if(isset($finfo['audio'])){
	    $a =$finfo['audio'];
	    $out .= smpl_fx('<b>'.t('Audio').'</b>' ,' ');
	    $out .= smpl_fx(t('Playtime')         ,$finfo['playtime_string'],'sec');            // playtime in minutes:seconds, formatted string
	    $out .= smpl_fx(t('Sample Rate')      ,isset($a['sample_rate'])      ? number_format($a['sample_rate'],0,"."," ")." Hz"      : 'unknown');
	    $out .= smpl_fx(t('Bitrate')          ,isset($a['bitrate'])          ? number_format($a['bitrate'],0,"."," ")." bit/s": 'unknown');
	    $out .= smpl_fx(t('Bitrate mode')     ,isset($a['bitrate_mode'])     ?
	         (($a['bitrate_mode']=="vbr")? "variable bitrate": "constant bitrate") : 'unknown'
	        );
	    $out .= smpl_fx(t('Bits per sample')  ,isset($a['bits_per_sample'])  ? $a['bits_per_sample']  : 'unknown');
	    $out .= smpl_fx(t('Channelmode')      ,isset($a['schannelmode'])     ? $a['schannelmode']     : 'unknown');
	    $out .= smpl_fx(t('Channels')         ,isset($a['channels'])         ? $a['channels']         : 'unknown');
	    $out .= smpl_fx(t('Codec')            ,isset($a['codec'])            ? $a['codec']            : 'unknown');
	    $out .= smpl_fx(t('Compression ratio'),isset($a['compression_ratio'])? sprintf("%02.3f",$a['compression_ratio']): 'unknown');
	    $out .= smpl_fx(t('Compression mode') ,isset($a['lossless'])         ? $a['lossless']         : 'unknown');
	    $out .= smpl_fx(t('Dataformat')       ,isset($a['dataformat'])       ? $a['dataformat']       : 'unknown');
	    $out .= smpl_fx(t('Encoder')          ,isset($a['encoder'])          ? $a['encoder']          : 'unknown');
	}
  $out .='</table>';
	return $out;
}

function smpl_doc_read($p,$id='', $verbose =0){
	$out ='';
	return $out;
}

/**
 * xls and xlsx file properties reading
 * @param unknown_type $p
 * @param unknown_type $id
 * @param unknown_type $verbose
 */
function smpl_excel_read($p,$id='', $verbose =0){
	include_once dirname(__FILE__).'/../lib/PHPExcel/Classes/PHPExcel.php';

	$a = pathinfo($p);
	if(strtolower($a['extension']) =='xls'){
			$oR = new PHPExcel_Reader_Excel5();
	}elseif(strtolower($a['extension']) =='xlsx'){
		$oR = new PHPExcel_Reader_Excel2007();
	}
	try{
		$o = $oR->load($p);
		$creator 			= $o->getProperties()->getCreator();
		$crDatestamp  = $o->getProperties()->getCreated();
		$crdatetime   = date('Y:m:d H:i:s',$crDatestamp);

		$modifiedBy   = $o->getProperties()->getLastModifiedBy();
		$modDatestamp = $o->getProperties()->getModified();
		$moddatetime 	= date('Y:m:d H:i:s',$modDatestamp);
		$title 				= $o->getProperties()->getTitle();
		$desc 				= $o->getProperties()->getDescription();
		$subj 				= $o->getProperties()->getSubject();
		$comp					= $o->getProperties()->getCompany();
		$manager 			= $o->getProperties()->getManager();
	}catch(Exception $e){
		$creator 			= 'unknown';
		$crDatestamp  = filemtime($p);
		//smpl_test($p);
		$crdatetime   = date('Y.m.d H:i:s',filemtime($p));

		$modifiedBy   = $crdatetime;
		$modDatestamp = $crdatetime ;
		$moddatetime 	= $crdatetime ;
		$title 				= basename($p);
		$desc 				= 'unknown';
		$subj 				= 'unknown';
		$comp					= 'unknown';
		$manager 			= 'unknown';
	}

	$out  = "\n";
	$out .= "    <table class='smpl_exif_table'>\n";
	$out .= smpl_fx(t('Creator'),$creator);
	$out .= smpl_fx(t('Created on'),$crdatetime);
	$out .= smpl_fx(t('Last Modified by'),$modifiedBy);
	$out .= smpl_fx(t('Modified on'),$moddatetime);
	$out .= smpl_fx(t('Title'),$title);
	$out .= smpl_fx(t('Title'),$desc);
	$out .= smpl_fx(t('Subject'),$subj);
	$out .= smpl_fx(t('Company'),$comp);
	$out .= smpl_fx(t('Manager'),$manager);
	$out .= "\n";
	$out .= "    </table>\n";
	return $out;
}

function smpl_pdf_read($p,$id='', $verbose =0) {
	$out ='';
	require_once('../lib/pdfparser/pdfparser.php');
	$out  = "";
	$x = smpl_getpdfdata($p);
	$out .='<table class="smpl_exif_table">';
	foreach($x AS $i => $e){
	    $out .= smpl_fx(t($i),$e);
	}
	$out .= '</table>';

	return $out;
}

/**
 * properties of a docx file
 * @param unknown $p
 * @param string $id
 * @param number $verbose
 * @return Ambigous <string, The, unknown, A, Optional>
 */
function smpl_docx_read($p,$id='', $verbose =0){
	$out ='';
	require_once(__DIR__.'/../lib/docx/docx.php');
	
	$out .= smpl_docxparser($p);
	return $out;
}

/**
 * properties of zip file
 * @param unknown $p
 * @param string $id
 * @param number $verbose
 */

function smpl_zip_read($p,$id='', $verbose =0){
    $out ="";
    if(function_exists("zip_open")){
        $zip = new ZipArchive();
        $zip->open($p);
        $out .='<table class="smpl_exif_table">';
        $out .= smpl_fx(t("Status"),$zip->getStatusString());
        $out .= smpl_fx(t("Number of files"),$zip->numFiles);
        $osize = 0;
        $csize = 0;
        for($i =0;$i<$zip->numFiles;$i++){
            $s = $zip->statIndex($i);
            $osize += $s["size"];
            $csize += $s["comp_size"];
        }
        $out .= smpl_fx(t("Compressed size"),$csize. " ".t("bytes"));
        $out .= smpl_fx(t("Original size"), $osize. " ".t("bytes"));
        
        $out .= smpl_fx(t("compress ratio"), sprintf("%5.3f",$csize / $osize));
        $out .= smpl_fx(t("Comment"),$zip->getArchiveComment());
        $zip->close();
        $out .= '</table>';
    }
    return $out;
}

/**
 * properties of rar file
 * @param unknown $p
 * @param string $id
 * @param number $verbose
 */

function smpl_rar_read($p,$id='', $verbose =0){
    $out ="";
    if(function_exists("rar_open")){
        $rar = RarArchive::open($p);
        $out .='<table class="smpl_exif_table">';
        $out .= smpl_fx(t("Status"),$rar->isBroken());
        $ets  = $rar->getEntries();
        $out .= smpl_fx(t("Number of files"),count($ets));
        $osize = 1;
        $csize = 0;
        $encrypted = false;
        foreach($ets AS $i =>$e){
            $osize += $e->getUnpackedSize();;
            $csize += $e->getPackedSize();
            $encrypted = $encrypted || $e->isEncrypted();
        }
        $out .= smpl_fx(t("Compressed size"),$csize. " ".t("bytes"));
        $out .= smpl_fx(t("Original size"), $osize. " ".t("bytes"));
        $out .= smpl_fx(t("compress ratio"), sprintf("%5.3f",$csize / $osize));
        $out .= smpl_fx(t("Status"),$encrypted ? t('encrypted'): t('not encrypted'));
        $out .= smpl_fx(t("Comment"),$rar->getComment());
        $rar->close();
        $out .= '</table>';
    }
    return $out;
}

/*
 * Helper for smpl_exif_readable function
 */
function _ex($exif,$text,$tag,$subtag){
	if(isset($exif[$tag])  && isset($exif[$tag][$subtag]))
			return smpl_fx(t($text), $exif[$tag][$subtag]);
	return '';
}

/**
 *
 * @param unknown $a
 * @param string $b
 * @param string $c
 */
function smpl_fx($a,$b='',$c=''){
	if(!empty($c)) $c = ' '.$c;
	if(empty($b)) return '';
	return '<tr class="smpl_exif_tr"><td class="smpl_exif_td">'.$a.":</td><td>".$b.$c."</td></tr>";
}

function smpl_clean_input($str='',$length= 250){
	if(strlen($str)>$length){
		$str = substr($str,0,$length);
	}
	$str = strip_tags($str);
	return $str;
}
/*
 * uasort compare function
 */
function smpl_cmp($ax,$bx){
	$smplsort = $GLOBALS['smpl_sort'];
	if(isset($_SESSION['smpl_sortsource'])){
		$sortsource = $_SESSION['smpl_sortsource'];
	}else{
		$sortsource = 'filename';
	}
	
	switch($sortsource){
		case 'filename':
			$a = $ax->getFilename();
			$b = $bx->getFilename();
			break;
		case 'sub':
			$a = $ax->getSubtitle();
			$b = $bx->getSubtitle();
		break;
		case 'size':
			$a = $ax->getSize();
			$b = $bx->getSize();
			break;
		case 'date':
			$a = $ax->getDate();
			$b = $bx->getDate();
			break;
		case 'vote':
			$a = $ax->getVoted();
			$b = $bx->getVoted();
			break;
		case 'view':
			$a = $ax->getOpened();
			$b = $bx->getOpened();
			break;
		default:
			$a = $ax->getFilename();
			$b = $bx->getFilename();
	}

	switch($sortsource){
		case 'size':
		case 'date':
		case 'vote':
		case 'view':
			if($a == $b ) $out = 0;
			else
				if($smplsort    == 'asc' ) $out =  $a< $b ? -1: 1 ;
			  elseif($smplsort== 'desc') $out =  $a> $b ? -1: 1 ;
			  else                       $out = rand(0,100) < rand(0,100) ? -1 : 1 ;
			break;
		default :
			if($smplsort=='asc'){
				$out =  strcasecmp($a,$b);
			}elseif($smplsort=='desc'){
				$out =  strcasecmp($b,$a);
			}else{
				$out = rand(0,100) < rand(0,100) ? strcasecmp($a,$b) : strcasecmp($b,$a);
			}
		}
	return $out;
}

/**
 * Add js files to smpl hptoalbum
 */
function _smplphotoalbum_add_js(&$params){
  $m  = $params["modulepath"];
  //$region = "header";
  $region = "footer";
  
  drupal_add_js($m.'/js/vote.js'      , array("scope" => $region ));
  
 drupal_add_library('system','ui');
 drupal_add_library('system', 'ui.draggable');
 if(smplphotoalbum_access() ){
   drupal_add_js($m.'/js/delete.js'    , array("scope" => $region ));
   drupal_add_js($m.'/js/kill.js'      , array("scope" => $region ));
   drupal_add_js($m.'/js/exif.js'      , array("scope" => $region ));
   drupal_add_js($m.'/js/edit.js'      , array("scope" => $region ));
   drupal_add_js($m.'/js/imgedit.js'   , array("scope" => $region ));
   drupal_add_js($m.'/js/statistics.js', array("scope" => $region ));
  }
  drupal_add_js($m.'/js/propinfo.js'   , array("scope" => $region ));
  if(smplphotoalbum_access() )
    drupal_add_js($m.'/js/taxonomy.js' , array("scope" => $region ));
      
  drupal_add_js($m.'/js/jScroll.js'    , array("scope" => $region ));

  
  if(smplphotoalbum_access() ){
    drupal_add_js($m.  '/lib/crop/scripts/jquery.imgareaselect.pack.js', array("scope" => $region ));
    drupal_add_css($m.'/lib/crop/css/jquery.imgareaselect-default.css' , array('group' => CSS_SYSTEM, 'type' => 'file','every_page'=> TRUE) );
  }
}

function _smpl_check_writeable($dir){
  return is_writable($dir);
}
/**
 * Tokens
 */
function smpl_root($p=''){
  if(empty($p) ){
    $p = variable_get('smpl_root');
  }

  if(substr($p,-1) == "/" ){
    $p = substr($p,0,-1);
    variable_set('smpl_root',$p);
  }
  $p = str_replace("__DRUPALROOT__", str_ireplace('\\','/',DRUPAL_ROOT), $p);
  $p = str_replace("__DOCUMENT_ROOT__", str_ireplace('\\','/',$_SERVER["DOCUMENT_ROOT"]), $p);

  return $p;
}

/*
 * Show filesize
 */
function smpl_filesize($size = 0){
  if($size > 1073741824){
    $s = (int) ($size / 1073741824 ).t('&nbsp;Gb');
  }else if($size > 1048576 ) {
    $s = (int) ($size / 1048576).t('&nbsp;Mb');
  }else if($size > 1024){
    $s = (int)($size / 1024).t('&nbsp;Kb');
  }else{
    $s = $size.t('&nbsp;b');
  }
  return $s;
}