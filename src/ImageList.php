<?php
namespace Drupal\smplphotoalbum;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\smplphotoalbum\Image;
use Drupal\smplphotoalbum\SlideShow;

require_once realpath(__DIR__."/../")."/vendor/autoload.php";
/**
 * @file
 * Main Images object
 */
class ImageList {
	const SMPLADMIN = 'smpllist';
	const SMPLTEST = false;
	const TN = '_tn_/';
	public $Items = [];
	protected $ascdesc = "asc"; // sorting order ascending or descending
	protected $author    = "PiQasso Group";
	protected $caption;
	protected $con; // Drupal database connection string
	protected $copyright = "PiQasso Group";
	protected $edit = false;
	protected $upload = False;
	protected $access = true;
	protected $graphicdrv = "gd";
	protected $filter = "";
	protected $firstimage = 0; // the first viewing item
	protected $method = "post";
	protected $icon = "_col";
	//protected $ImageArray = [ 'avif', 'jpg', 'jpeg', 'JPG', "JPEG", 'png', 'bmp', 'xbm', 'xpm', 'wbmp', 'webp' ];
	protected $imgedit = false;
	protected $ImgNumber = 0;  	// the number of showing item (there are hidden items)
	protected $ImgProps = [];  	// the image properties
	protected $keywords  = ''; 	// SEO captions into the keywords meta tag
	protected $lang = "en";    	// Actual language
	protected $modulepath =''; 	// path of module in filesystem
	protected $number;         	// Length of a page
	protected $numitems = 0;
	protected $order = false;  	// sorting the items
	protected $path = "";
	protected $page = 0;       	// No. of actual page
	protected $pagenumber = 0; 	// number of all pages
	protected $pagelength = 1;	//length of a page
	protected $params;         	// copy of params (maybe not the best choice)
	protected $private;       	// Using private file system
	protected $request;
	protected $requestUri = '';
	protected $root = "";     	// root folder of photoalbum	
	protected $sess = '';  			//Drupal session handling
	protected $smplbox = "smplbox"; // It helps to shows the image in a lightbox or colorbox
	protected $sortorder = 'filename'; // source of compare
	protected $stat = '';     	// statistics
	protected $sub = true;			// Enable/disable the subtitles
	protected $sumviews = 0;
	protected $tags = '';    	// keywords and descriptions metatag
	protected $title = '';   	// Title of page
	protected $notes = '';		// Notes of page
	protected $tpl = [];     	// array of templates
	protected $html5;

	//translating
	protected $translate = false;
	protected $url = '';
	protected $user;         	// current user
	protected $viewed = 0;   	// is the view number of image on?
	protected $width = 0;		 	// Width of items
	protected $wmpath  = ""; 	// watermark
	protected $wmalpha = 10;	// watermark alpha
	protected $words = [];
	// Types
	protected $app = 1;			//view the types
	protected $audio = 1;
	protected $cmp = 1;
	protected $dis = 1;
	protected $doc = 1;
	protected $oth = 1;
	protected $video = 1;

	// Slideshow
	protected $Slide;
	protected $interval = 10;
	protected $slide_checking = False;
	protected $slide_extension = array();
	protected $slide = False;
	protected $slide_path = "";
	protected $slide_i = 0;
	protected $slide_id = 0;
	protected $slide_db;
	protected $slide_subtitle = "";
	protected $slide_title = "";
	protected $slidestyle = "";
	protected $style = "none";
	
	use StringTranslationTrait;
	/**
	 * Make the list of objects of images
	 *
	 * @param array $params
	 */
	function __construct(&$params) {		
		$this->user 	= \Drupal::currentUser();

		$this->request= \Drupal::request();
		$this->sess   = \Drupal::request()->getSession();	
		$this->con    = \Drupal::database();
		
		//$Items            = [];
		$this->params     = $params;
		$this->preSettings( $params );		
		$root             = $this->root;
		$path             = $this->path;
		$this->slide_path	= $this->path;
		$this->access     = $this->RightAccess();

		// If method comes from page
		if( isset( $params["method"] ) ){
			$method = strtolower( $params["method"] );
			if ( $method == "post" || $method == "get" ) {
				$this->method = $method;
			}
		}

		// Make an Image object
		if (!is_dir ( $root . $path )) {
			\Drupal::messenger()->addMessage( 
				$this->t( "Set the right folder in settings of Smplphotoalbum. This is not a folder: " ) . $root . $path.'"'
			);
		}
		
		$tempfolder = $this->slash( $root . $path . self::TN);

		$ok = \Drupal::service ( "file_system" )->prepareDirectory ( $tempfolder, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS );

		if (! $ok) {
			\Drupal::messenger()->addMessage(  "There is no cache folder or not writable: " . $tempfolder );
		}

		// Make thumbnail folders if not exists
		if (! is_dir ( $this->root . $path . self::TN )) {
			mkdir ( $this->root . $path . self::TN );
		}

		//Thumbnails refresh
		if( $this->Request('SmplThumbnails')  && $this->access ) {
			$this->RefreshFolder($path);
		}
		
		//Upload file enabled		
		if( $this->upload && $this->Request("SmplUploadSubmit") && $this->access ){
			$this->Upload();
		}

		// filter enabled
		if ( isset ( $this->params['filter'] ) && $this->params['filter'] ) {
			$this->filter = $this->Request('smpl_filter',"" );
		}
		
		//------------------- Query dynamic -----------
		$query = $this->con->select('{smplphotoalbum}', 's');
		$query -> fields('s', [ 'id', 'path', 'name', 'subtitle', 'typ', 'viewnumber', 'link', 'size', 'modified', 'importance' ]);				

		//WHERE  path = 
		$pathCond = $query->condition( 'path', $path, "=" );		

		// where type in[]
		if( $this->slide ){
			$wherein[] ='image';
		}else{		
			$wherein[] = 'image';			
			if( $this->video ) {
				$wherein[] = 'video';
				$wherein[] = 'videohtml5';			
			}			
			if( $this->audio ){
				$wherein[] = 'audio';
				$wherein[] = 'audiohtml5';			
			} 
			if( $this->doc ) $wherein[] = 'doc';
			if( $this->cmp ) $wherein[] = 'cmp';
			if( $this->app ) $wherein[] = 'app';
			if( $this->dis ) $wherein[] = 'dis';
			$wherein[] = '--';
		};

		$typeCond = $query->condition( 'typ', $wherein, "IN" );
		
		// Filter
		if( !empty( $this->params["filter"]) && !empty( $this->filter ) ){
			$fltrCond = $query->orConditionGroup()
				->condition( 'name'    , "%$this->filter%", "LIKE" )
				->condition( 'subtitle', "%$this->filter%", "LIKE" );

			$query->condition( $fltrCond );	
		}
		
		// 
		$cntQuery = $query->countQuery();
		$all = (int) ( $cntQuery->execute()->fetchField());	
		$this->pagenumber = ( int ) ( $all / $this->number ) + ( ( $all % $this->number )>0 ? 1 : 0 );	
		
		// If the page number higher than should be
		if ($this->page > $this->pagenumber) {
			$this->page = $this->pagenumber-1;
		}
		if($this->page <0 ) $this->page = 0;
		
		// ORDER BY
		$order = "";
		
		if ( $this->order && $this->sortorder != "-") {

			switch (substr ( $this->sortorder, 0, 2 )) {
				case 'filename':
				case 'fi' : $order = "name"; break;
				case 'im' : $order = "importance"; break;
				case 'su' : $order = "subtitle"; break;
				case 'vi' : $order = "viewnumber"; break;
				case 'ty' : $order = "typ"; break;
				case 'si' : $order = "size"; break;
				case 'da' : $order = "modified"; break;
			}			
			$query->orderBy( $order, $this->ascdesc );			
		}

		//LIMIT
		if( $this->page < 0 ) {
			$this->page = 0;
		}

		$query->range( (int) ( $this->page * $this->number ), $this->number);

		$rs = $query->execute();	
	
//---------------------------------------
		$RSArray = $rs->fetchAllAssoc('id');
		// number of records		
		$db = 0;
		foreach( $RSArray AS $id => $RS ){
			if ( !file_exists( $this->slash( $this->root . $this->path . $RS->name ) ) ){
				unset( $RSArray[$id] );
				continue;
			}
			$type = $RS->typ;
			if ( $type == "image" )					$tpl = $this->tpl ["image"];
			elseif ( $type == "video" )			$tpl = $this->tpl ["video"];
			elseif ( $type == "videohtml5" || $type == "video" ) $tpl = $this->tpl ["videohtml5"];
			elseif ( $type == "audio" )			$tpl = $this->tpl ["audio"];
			elseif ( $type == "audiohtml5") $tpl = $this->tpl ["audiohtml5"];
			else														$tpl = $this->tpl ["other"];
			
			$this->Items [$db] = new Image (
				$id,
				$RS->subtitle,
				$RS->viewnumber,
				$RS->link,
				$params,
				$this->words,
				$RS->name,
				$RS->importance,
				$type,
				$tpl
			);
			$db++;
		}
		
		//statistics of this page
		if ($this->stat) {
			$this->numitems = count ($this->Items);
			$this->sumviews = 0;
			foreach ( $this->Items AS $i => $Item ) {
				$this->sumviews += $Item->getOpened();
			}
		}

		// Save in the session the serial number of image 
		if ($this->slide) {
			$this->Slide = new SlideShow( $path );
			$this->Slide->NewSlideShow( $this->words, $this->Items );
			//-----------------------------------------		
		}					
	}

	/**
	 * Set parameters, load templates, etc.
	 *
	 * @param array $params
	 */
	function preSettings(&$params) {
		$this->modulepath= $params['modulepath'];
		$this->root      = $this->getRoot( $params['root'] );
		$this->path      = $params['path'];
		$this->width     = ( int ) $params['width'];
		$this->number    = ( int ) ($params['number']);
		$this->order     = $params["order"];
		$this->sortorder = $params["sortorder"];
		$this->ascdesc   = $params["ascdesc"];
		$this->sub       = $params['sub'];
		$this->smplbox   = $params['smplbox'];
		$this->viewed    = $params['viewed'];
		$this->edit      = $params['edit'];
		$this->stat      = $params['stat'];
		$this->private   = $params['private'];
		
		$this->edit      = $params["edit"];		// Edit 
		$this->imgedit   = $params["imgedit"];	// Image Edit
		$this->wmpath    = $params["wmpath"];	// Watermark		
		$this->upload    = $params['upload'];	// Upload enabled | disabled

		//checking types of items
		$this->audio = $params['audio_checking'];
		$this->video = $params['video_checking'];
		$this->doc   = $params['doc_checking'];
		$this->cmp   = $params['cmp_checking'];
		$this->app   = $params['app_checking'];
		$this->oth   = $params['oth_checking'];
		$this->dis   = $params['dis_checking'];
		$this->html5 = $params['html5_checking']; // Use the html5 widgets
		$this->url   = $params['url_checking'];
		$this->title = isset ( $params['title'] ) && !empty( $params[ 'title' ] ) ? '<h2 class="smpl_title">' . $params['title'] . '</h2>' : '';
		$this->notes = isset ( $params['notes'] ) && !empty( $params[ 'notes' ] ) ? '<div class="smpl_notes">' . $params['notes'] . '</div>' : '';

		// Slideshow
		if( $this->Request("SmplSlide", false) ){
			$params['slide'] = true;
		}
	  $this->slide_checking = $params['slide_checking'];		
		$this->slide          = $params['slide'] && $params['slide_checking'];
		$this->slidestyle     = $params['slidestyle'];
		$this->interval       = $params['interval'];

		// icon color or black & white
		$this->graphicdrv = strtolower(trim( $params['graphicdrv'] ));
		$this->icon       = strtolower(trim( $params['icon'] ));

		// Paging
		$smpl_page = $this->Request("smpl_page", "" );
		if( !empty( $smpl_page ) ){
	   	$this->page = (int) $smpl_page;
			if( $this->page < 0 ) {
				$this->page = 0;
			}
	   	header('X-Drupal-Cache: MISS');
	   	header('X-Drupal-Dynamic-Cache: MISS');
	  } else{
	  	$this->page = 0;
	  }

	  // read the text
		$this->ReadWords();

		// Load templates
		$this->LoadTpls();

		if ($this->Request(self::SMPLADMIN) ) {
	   	$this->firstimage = 0;
		  $this->page = 0;
		  $this->pagelength = 1000;
	  } else {
	   	$this->firstimage = $this->page * $this->number;
	  }
	}

	/**
	 * Get hte abolute path of root
	 * @param string $root
	 * @return mixed
	 */
	function getRoot(string $root){
	  $root = str_replace ( "public://", \Drupal::service( 'file_system' )->realpath( "public://" )."/", $root)."/";	  
	  return $this->slash( $root );
	}

	/**
	 * Load templates
	 * @return
	 */
	function LoadTpls(){
		$p = realpath ( $this->modulepath . "/templates" );
		$this->tpl['smplphotoalbum'] = file_get_contents ( $p . "/smplphotoalbum.html.twig" );
		$this->tpl['image']        = file_get_contents ( $p . "/image.html.twig" );
		$this->tpl['videohtml5']   = file_get_contents ( $p . "/videohtml5.html.twig" );
		$this->tpl['video']        = file_get_contents ( $p . "/video.html.twig" );
		$this->tpl['audiohtml5']   = file_get_contents ( $p . "/audiohtml5.html.twig" );
		$this->tpl['audio']        = file_get_contents ( $p . "/audio.html.twig" );
		$this->tpl['other']        = file_get_contents ( $p . "/other.html.twig" );		
	  $this->tpl['pagerbuttons'] = file_get_contents ( $p . "/pagerbuttons.html.twig" );
	  $this->tpl['sortorder']    = file_get_contents ( $p . "/sortorder.html.twig" );
	  $this->tpl['stat']         = file_get_contents ( $p . "/stat.html.twig" );
		$this->tpl["js"]           = file_get_contents ($p  . "/smpl_js.html.twig");
	  if ($this->slide) {
	   	$this->tpl['slide']      = file_get_contents ( $p . "/slide.html.twig" );
	   	$this->tpl['slideimage'] = file_get_contents ( $p . "/slideimage.html.twig" );			
	  }

		if( $this->access ){
			$this->tpl["editform"] = file_get_contents ( $p . "/editform.html.twig" );
			$this->tpl["imgeditform"] = file_get_contents ( $p . "/imgeditform.html.twig" );
			$this->tpl["uploadform"] = file_get_contents ( $p . "/uploadform.html.twig" );
		}else {
			$this->tpl["editform"] = "";
			$this->tpl["imgeditform"] = "";
			$this->tpl["uploadform"] = "";
		}
	}

	/**
 	 * Reads the words of translating
	 * @return
 	 */
	function ReadWords(){
		$this->translate  = strtolower( trim( $this->params['translate'] ) );
		if( $this->translate ){
			$words = file( $this->modulepath ."/translate/translate_" . strtolower(trim( $this->params['lang'] )).".txt", FILE_IGNORE_NEW_LINES );
		}else{
			$words = file( $this->modulepath ."/translate/translate.txt", FILE_IGNORE_NEW_LINES );
		}
		$words = str_replace( "_"," ", $words);
		foreach($words AS $e){
			$e = trim( $e );
			if( strpos( ' '.$e, ';' ) > 0 ){
				continue;
			}
			$a = explode( "=", $e );

			if( count( $a ) == 1 ) {
				$this->words[ $e ] = $e;
			}else{
				$this->words[ trim( $a[0] ) ] = trim( $a[1] );
			}
		}
	}

	/**
	 *  File upload
	 * 
	 */
	function Upload(){
		$smpl_uid   = $this->Request("smpl_uid", "", "POST");
		$smpl_uname = $this->Request("smpl_uname", "", "POST");
		$smpl_usub  = $this->Request("smpl_usub" , "", "POST");
		$smpl_utype = $this->Request("smpl_utype", "", "POST");
		$smpl_ulink = $this->Request("smpl_ulink", "", "POST");
		$smpl_utime = $this->Request("smpl_utime", "", "POST");
		$smpl_usize = $this->Request("smpl_usize", "", "POST");
		$smpl_uimportance = $this->Request("smpl_uimportance", "", "POST");

		$filename   = $_FILES[ 'smpl_uname']['name'];
		$tmpname    = $_FILES[ 'smpl_uname']['tmp_name'];
		$size       = $_FILES[ 'smpl_uname']['size'];
		$ok         = ($_FILES[ 'smpl_uname']['error'] === 0);
		 		
		if( !($extok = stripos( $this->extensionstring("all") , pathinfo ( $filename , PATHINFO_EXTENSION ) ) > 0) ){
			\Drupal::messenger()->addMessage( "Can not upload this file '$smpl_uname' is not enabled file type!", 'warning' );
			return false;
		}

		if( $size > ini_parse_quantity( ini_get('post_max_size') ) ){
			\Drupal::messenger()->addMessage( "Can not upload this file '$smpl_uname', because the size is too big!", 'warning' );
			$ok = false;
		}

		$uri = str_replace("//","/", $this->root . $this->path );
		
		if( file_exists ($uri ."/".$filename)){
			\Drupal::messenger()->addMessage( "Can not upload this file '$filename' to this place: '$this->path' because the file exists!", 'warning' );
			$ok = false;
		}

		$ok = move_uploaded_file( $tmpname, $uri."/".$filename );
		if( !$ok ){
			\Drupal::messenger()->addMessage( "Can not upload this file '$filename'. Maybe the application not enough rights to this place: '$this->path' or other problems!", 'warning' );
		}

		//Save uploaded data into database
		if( $ok ){
			$ok = $this->InsertNewFile( $this->path, $filename, $smpl_usub, $smpl_utype, $smpl_ulink, $smpl_usize, $smpl_utime, (int) ($smpl_uimportance) );
			if($ok){
				\Drupal::messenger()->addMessage( " '$filename' added into database", 'notice' );
			}else{
				\Drupal::messenger()->addMessage( " There was '$filename' in the database table in this folder!", 'warning' );
			}
		}
		
		$this->RefreshFolder($this->path);
		return $ok;
	}
/**
 * Insert new record of item into the table 
 */
	function InsertNewfile( $path ="/", $name = "", $sub = "", $type ="", $link = "", $size = 0, $tim = 0, $importance = 0){
		$tim .= "#";
		$tim = str_replace(".#","", $tim);
		$tim = str_replace([' ','.'],['','-'],$tim);
		$date = date_create($tim);
		$timstamp = date_timestamp_get($date); 
		try{
			$last_id = $query = $this->con->Insert('smplphotoalbum')
				->fields([
					'path'     => $path,
					'name'     => $name,
					'typ'      => $type,
					'subtitle' => $sub,
					'link'     => $link,
					'size'     => $size,
					'modified' => $timstamp,
					'importance' => $importance
				])->execute();
		}	catch( \Throwable $e ){
			$last_id = 0;
		}	
		return $last_id > 0;
	}

	/**
	 * Refresh the actual folder
	 *
	 * @param string $path - actual path
	 * @return void
	 */
	function RefreshFolder( $path ) {		
		
		//Load every items and delete folders
		$names = scandir( $this->root . $path );		
		unset ( $names [array_search ( '.', $names )] );
		unset ( $names [array_search ( '..', $names )] );
		$tn = str_replace("/","",self::TN);
		unset ( $names [array_search ( str_replace( "/", "", self::TN ), $names )] );		

		//
		foreach( $names AS $i => $name ){
			$msg = $this->ChkItem2DB( $name, $path);
			\Drupal::messenger ()->addMessage ( $msg , "status");
		}

		// database names into array
		$sql = "SELECT `id`, `name` FROM {smplphotoalbum} WHERE `path`= :path; ";
		$rs = $this->con->query( $sql, [':path' => $path] );
		$dbnames = $rs->fetchAllAssoc('id');

		// Delete orphan row from database
		$sql = "DELETE FROM {smplphotoalbum} WHERE path= :path AND id = :id";		
		foreach($dbnames AS $id => $dbname){			
			if( !array_search( $dbname->name, $names ) ){
				$this->con->query($sql, [":path" => $path, ":id" => $id ]);
				\Drupal::messenger ()->addMessage ( "Delete from database: '$dbname->name'", "status");
			}
		}

		if(empty($msg)){
			\Drupal::messenger ()->addMessage ("There was nothing change", "status");
		}		 
	}

	/**
	 * Check Item is in the databasa
	 *  
 	 * @param string $name name of source item
	 * @param string $path path of source item	 
	 * @return string;
   */
	function ChkItem2DB( $name, $path ){				
		$msg = "";
		// Thumbnails
		$source = $this->slash( $this->root . $this->path . $name);
		$thumbnail = $this->slash( $this->root . $this->path . self::TN . $name );
		
		$msg .= $this->CheckThumbnail($name, $source, $thumbnail);
		
		// Refresh other data 
		$sql = "SELECT `name`, `size`, `modified` FROM {smplphotoalbum} WHERE `path` = :path AND `name` = :name";
		$e = [":path" => $path, ":name" => $name];
		$rs = $this->con->query($sql, $e);
		$row = $rs->fetchAll();
		$db = count($row);

		// Update
		if( $db > 0 )
		{			
			$sql = "UPDATE {smplphotoalbum} SET ";			
			$s = '';			
			$size = filesize( $source );			
			if( empty( $row[0]->size) || (int)( $row[0]->size) != $size){
				$s = "`size` = ".$size;
			}
			
			$t = "";
			$time = filemtime( $source );			
			if(empty($row[0]->modified) || ((int) $row[0]->modified) != $time){
				$t = ( !empty($s)? ", ": "" ) . "`modified` = ".$time;
			}

			if( !empty($s) || !empty($t) ){
				$sql .= $s . $t . " WHERE `path`=:path AND `name`= :name;";
				$this->con->query( $sql, $e );
				$msg .= "Datas of '".$name."' updated";
			}			
		}elseif ($db == 0 ){
			$type = $this->type( $name );
			$size = filesize( $source );
			$time = filemtime( $source );
			$qry = $this->con->insert("smplphotoalbum")
				->fields([
						'path',
						'name',
						'typ',
						'viewnumber',
						'subtitle',
						'link',
						'size',
						'modified',						
				])
				->values([
					'path' =>$path,
					'name' => $name,
					'typ' => $type,
					'viewnumber' => 0,
					'subtitle' => $name,
					'link' => '',
					'size' => $size,
					'modified' => $time
				]);			
			$x = $qry->execute();		
			$msg .= "'$name' ($type) added to DB";
		}
		return $msg;
	}

	function CheckThumbnail( $name, $source, $thumbnail ){
		
		$msg ="";
		if( !$this->isimage( $name ) ){
			$thumbnail .= ".png";
		}
		
		if( !file_exists( $thumbnail ) ){	

			$this->MakeThumbnail(	$name, $source, $thumbnail, $msg);
			$msg = "New thumbnail '$name'";

		}elseif( filemtime($thumbnail) < filemtime($source)){

			unlink($thumbnail);
			$this->MakeThumbnail(	$name, $source, $thumbnail, $msg);
			$msg ="Refreshed thumbnail '$name'";

		}
		return $msg;
	}
	/**
	 * Update thumbnail if the original image is changed
	 * @param string $name - name of item
	 * @param string $source - source path
	 * @param string $thumbnail - thumbnail
	 * @param string $msg - message	 
	 * @return boolean
	 */
	function MakeThumbnail($name, $source, $thumbnail, &$msg ) {
		static $db = 0;
		if (! $this->access ) return true;

		$ext = strtolower ( pathinfo ( $name, PATHINFO_EXTENSION ) );
		// Make new thumbnails from GIF, PNG or JPG | JPEG | BMP | WBMP | WEBP | XPM | XBM | AVIF
		if ($this->isimage ( $name )) {			
			$size = GetImageSize ( $this->root . $this->path . $name );
			if($size !== false){
				$dx = $size [0];
				$dy = $size [1];
			} else{
				$dx = $this->width;
				$dy = 3 * $this->width / 4;
			}

			if ($dx > $this->width) {
				// Target image
				$dst_im = @ImageCreateTrueColor ( $this->width, $this->width * $dy / $dx );
				switch ($ext) {
					case 'avif':
						$im = @imagecreatefromavif( $source );
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imageavif( $dst_im, $thumbnail , -1, -1) && $a;
						break;

					case 'bmp' :
						$im = @imagecreatefrombmp ( $source );
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imagebmp ( $dst_im, $thumbnail ) && $a;
						break;

					case 'gif' :
						$im = @imagecreatefromgif ( $source );
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imagegif ( $dst_im, $thumbnail ) && $a;
						break;

					case 'jpg' :
					case 'jpeg' :
						$im = @imagecreatefromjpeg ( $source );
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @Imagejpeg ( $dst_im, $thumbnail, 70 ) && $a;
						break;

					case 'png' :
						$im = @imagecreatefrompng ( $source );
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imagepng ( $dst_im, $thumbnail, 9 ) && $a;
						break;

					case 'wbmp' :
						$im = @imagecreatefromwbmp ( $source );
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imagewbmp ( $dst_im, $thumbnail ) && $a;
						break;

					case 'webp':
						$im = @imagecreatefromwebp($source);
						$a  = @imagecopyresized($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imagewebp( $dst_im, $thumbnail ) && $a;
						break;

					case 'xbm':
						$im = imagecreatefromxbm($source);
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @imagexbm( $dst_im, $thumbnail ) && $a;
						break;

					case 'xpm':
						$im = imagecreatefromxpm($source);
						$a  = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
						$ok = @Imagejpeg ( $dst_im, $thumbnail, 100 ) && $a;
						break;
					default :
						$ok = true;
				}
			} else {
				$ok = @copy ( $source, $thumbnail );
			}

		} else {			
			if ($this->isaudio ( $name ) || $this->isaudiohtml5($name))
				$source = $this->modulepath . "/image/audio_" . $ext . ".png";
			elseif ($this->isdoc ( $name ))
				$source = $this->modulepath . "/image/doc_" . $ext . ".png";
			elseif ($this->iscmp ( $name ))
				$source = $this->modulepath . "/image/cmp_" . $ext . ".png";
			elseif ($this->isapp ( $name ))
				$source = $this->modulepath . "/image/app_" . $ext . ".png";
			elseif ($this->isvideo ( $name ) || $this->isvideohtml5($name ) )
				$source = $this->modulepath . "/image/video_" . $ext . ".png";
			else
				$source = $this->modulepath . "/image/other.png";
			
			$ok = @copy ( $source, $thumbnail );
		}

		// Message
		if (!$ok) {
			$msg = "Can not writeable thumbnail: " . $thumbnail;
			\Drupal::messenger ()->addMessage ( $msg, 'warning' );
		}
		return $ok;
	}

	/**
	 * Render the table of images
	 */
	function Render() {
		global $base_path;
				
		if( isset( $_SESSION['_symfony_flashes']['status'] ) &&
				count( $_SESSION['_symfony_flashes']['status'] ) > 5
		){
			$msgs = $_SESSION['_symfony_flashes']['status'];
			$thumbnail = $this->slash( $this->root . $this->path . self::TN );
			$dbnew = 0;
			$dbupdate = 0;
			foreach($msgs As $i => $msg){
				if( strpos( " ".$msg, 'Make thumbnail' ) > 0 ) {
					unset( $_SESSION['_symfony_flashes'] ['status'] [$i]) ;
					$dbnew++;
				}
				if( strpos(" ".$msg, 'Thumbnail update' )>0 ) {
					unset( $_SESSION['_symfony_flashes'] ['status'] [$i] );
					$dbupdate++;
				}
			}

			if( $this->access && $dbnew > 0 ){
				\Drupal::messenger()->addMessage("Make '$dbnew' thumbnail(s) in: ". $thumbnail);
			}
			if( $this->access && $dbupdate > 0){
				\Drupal::messenger()->addMessage("Update '$dbupdate' thumbnail(s) in: ". $thumbnail);
			}
		}

		//cache clear
		if ( $this->access && $this->Request( 'SmplCacheClear') && $this->access)  {
			$this->CacheClear();
		}

		// Load smpl template		
		$strjs = "\n<script>".$this->tpl["js"]."</script>\n";
		
		$imgeditform = $this->Request("imgeditform","''");
		$id = $this->Request("id",-1);

		if(extension_loaded("Imagick")){
			$gv = \Imagick::getVersion();
			$imagick = $gv["versionString"];
			$ver = [];
			preg_match('/ImageMagick ([0-9]+\.[0-9]+\.[0-9]+)/', $imagick, $ver);			
		} else{
			$ver[1] = '';
		}

		$strjs = str_replace(
			[ 
				"{{ base_path }}",
				"{{ wmpath }}",				
				"{{ imagickversion }}",
				"{{ imgeditform }}",
				"{{ id }}",				
				"{{ extimages }}",
				"{{ extaudio }}",
				"{{ extaudiohtml5 }}",
				"{{ extvideo }}",
				"{{ extvideohtml5 }}",
				"{{ extapplication }}",
				"{{ extcompressed }}",
				"{{ extdocument }}",
				"{{ extother }}",				
				"{{ extensions }}",
				"{{ maxsize }}"
			],
			[
				$base_path,
				$this->wmpath, 				
				$ver[1], 
				$imgeditform, 
				$id,
				
				"'".$this->extensionstring("image")."'",
				"'".$this->extensionstring("audio")."'",
				"'".$this->extensionstring("audiohtml5")."'",
				"'".$this->extensionstring("video")."'",
				"'".$this->extensionstring("videohtml5")."'",
				"'".$this->extensionstring("app")."'",
				"'".$this->extensionstring("cmp")."'",
				"'".$this->extensionstring("doc")."'",
				"'".$this->extensionstring("oth")."'",				
				"'".$this->extensionstring("all")."'",
				ini_parse_quantity( ini_get('post_max_size') )
			],  
			$strjs 
		);

		$strjs = str_replace([""],[],$strjs);

		$strjs = str_replace(
			[],
			[],
			$strjs
		);

		$str = $this->tpl["smplphotoalbum"];
		
		if($this->params["test"] || $this->params["method"] == "GET"){
			$this->method = "get";
		}
		$str = str_ireplace("{{ method }}", $this->method, $str);

		//Egyedi oldalak
		$origin = ($this->method == "POST" ) ? "./?".mt_rand() : "";
		$str    = str_replace("{{ action }}", $origin, $str);

		if ( $this->access ) {
			$str = str_replace( 
							[ "{{ EditForm }}", 
								"{{ ImgEditForm }}", 
								"{{ UploadForm }}",
								"{{ ImgEditDefault }}",
								"{{ method }}"
							],
			        [ $this->tpl["editform"], 
							  $this->tpl["imgeditform"], 
								$this->upload ? $this->tpl["uploadform"]: '',
								$base_path . $this->modulepath . "/image/404.png",
								$this->method
							], $str
						);										
			
			//Test environment
			$this->TestModify($str);
			
			//IMGEdit teszt
			if( $this->Request("imgedit") ){
				$id = $this->Request("id",1);
			}

		} else {
			$str = str_replace ( ["{{ EditForm }}", "{{ ImgEditForm }}", "{{ UploadForm }}"],"", $str );
			$str = preg_replace ( "#<Test(.*?)<\/Test>#imxs","", $str );
		}

		$str = str_replace(
			[
				"{{ module }}",
				"{{ title }}",
				"{{ notes }}",
				"{{ sortorder }}"
			],
			[
				$this->modulepath,
				$this->title,
				$this->notes,
				$this->order ? $this->SortOrdered () : ""
			], $str);

		// Statistics
		$this->Statist( $str );

		// Search / Filter
		$this->SearchFilter( $str );

		// UploadButton
		$this->UploadButton( $str );

		// Graphic driver
		$this->GraphicDriver( $str );

		//Autoclose
		$this->AutoClose( $str );

		// Watermark
    $this->Watermark( $str );

		//Recognition
		$this->Recognition( $str );

		$str = str_replace("{{ Constrain_aspect_ratio }}", $this->words['Constrain aspect ratio'], $str);
		$s = [ ];
		$r = [ ];

		foreach ( $this->words as $i => $e ) {
			$s[] = '{{ ' . $i . ' }}';
			$r[] = ( string ) ( $e );
		}

		$s[] = '{{ ajax }}';
		$r[] = $base_path . 'smplphotoalbum';
		$s[] = "{{ jquery }}";
		$r[] = $base_path;
		$s[] = "{{ modulepath }}";
		$r[] = $base_path . $this->modulepath;
		$s[] = "{{ smplbox }}";
		$r[] = $this->smplbox;

		$s[] = "{{ icon }}";
		$r[] = $this->icon;

		$str = str_ireplace ( $s, $r, $str );
		//
		$Pager = $this->PagerButtons ();
		$Table = $this->Table ();

		$str = str_ireplace ( "{{ pager }}", $Pager, $str );
		$str = str_ireplace ( "{{ table }}", $Table, $str );
		$str .= $strjs;
		return $str;
	}

	/**
	 * Is there recognition icon on the Edit window
	 * @param mixed $str 	 
	 */
	function Recognition( string &$str ){
		$ai = ( $this->params['aiclarifai'] || $this->params['aigemini'] );		
		if( $ai ){
			$str = str_replace( [ "<ai>","</ai>" ], "", $str );
			$str = str_replace( "{{ AI_recognition }}", $this->words["AI recognition"], $str);
			$ai_info = $this->params['aigemini'] ? $this->words["AI image recognition Gemini client"] : $this->words["AI image recognition Clarifai client"];
			$str = str_replace( "{{ AI_recognition_info }}", $ai_info, $str);
		} else{
			$str = preg_replace( "#<ai(.*?)<\/ai>#imxs", "", $str );

		}		
	}
	
	/**
	 * Statistics of actual path
	 * @param string &$str
	 */
	function Statist(string &$str){
	  if ($this->stat)
	    $str = str_replace( "{{ statistics }}", $this->Statistics(), $str );
	  else {
	    $str = str_replace( "{{ statistics }}", "", $str );
	  }
	  // Link tor statistics
	  if ($this->smplphotoalbum_access ()) {
	    $str = str_replace( "{{ linktostat }}", '<div><a href="admin/config/smplphotoalbum/stat" target="_blank">' . $this->words["Link to statistics" ] . '</a></div>', $str );
	  } else {
	    $str = str_replace( "{{ linktostat }}", "", $str );
	  }	  
	}

  /**
   * Search filter
   * @param string &$str
   */
	function SearchFilter(string &$str){
	  if (isset ( $this->params ['filter'] ) && $this->params ['filter']) {
	    $str = str_replace(
				[
					"<Filter>",
					"</Filter>",
					"{{ FilterValue }}"
				],
				[
					'',
					'',
					( !empty($this->filter ) ? $this->filter : "" )
				]
			, $str );
	  } else {
	    $str = preg_replace ( "#<Filter(.*?)<\/Filter>#imxs", "", $str );
	  }	  
	}

	/**
	 * Upload button views or not
	 * @param string &$str
	 */
	function UploadButton(string &$str){
		if( !$this->upload ){
			$str = preg_replace("#<UploadButton(.*?)<\/UploadButton>#imxs", "", $str );
		}else{
			$str = str_replace(['<UploadButton>','</UploadButton>'],'', $str);
		}		
	}

	/**
	 * Testing code from the main page
	 * @param string $str
	 * @return string
	 */
	function TestModify( string &$str ){
		if( $this->params['test'] ){
			$str = str_replace( ['<Test>','</Test>'], '', $str );
		}else{
			$str = preg_replace ( "#<Test(.*?)<\/Test>#imxs",'', $str );
		}
	}

	/**
	 * Graphic driver
	 * @param string $str
	 */
	function GraphicDriver(string &$str){
		$gv  =  gd_info();
		$str = str_replace(
			[
				"{{ graphicdrv }}",
				"{{ glogo }}",
				'{{ GDVersion }}'
			],
			[
				$this->graphicdrv,
				$this->modulepath."/image/",
				"GD Version ".$gv["GD Version"]
			], $str);

	  if(extension_loaded("Imagick")){
	    $gv      = \Imagick::getVersion();
	    $imagick = $gv["versionString"];
	    $str     = str_replace('{{ ImagickVersion }}', $imagick, $str);
	    $ver     = [];
	    preg_match('/ImageMagick ([0-9]+\.[0-9]+\.[0-9]+)/', $imagick, $ver);
	    $str = str_replace( [ '{{ ImgVer }}',	"{{ greadonly }}" ], [ $ver[1],'' ], $str);
	  } else{
	    $str = str_replace( [ '{{ ImagickVersion }}', '{{ greadonly }}' ], [ '', 'readonly="readonly"' ], $str);
	  }

	  if($this->graphicdrv == "gd"){
	    $str = str_replace( [ '{{ gdselected }}', '{{ imagickselected }}' ], [ "selected","" ], $str);
	  }else{
	    $str = str_replace( [ '{{ gdselected }}', '{{ imagickselected }}' ], [ "", "selected" ], $str);
	  }
	}

	/**
	 * Add watermark to image
	 * @param string $str
	 * @return mixed
	 */
	function Watermark(string &$str){
	  if($this->params["wm"]){
	    $s = [
	        "{{ wm }}",
	        "{{ wmpath }}",
	        "{{ wmalpha }}",
	        "{{ copyright }}",
	        "{{ author }}",
					"{{ vauthor }}",
					"{{ vcopyright }}"
	    ];
	    $r = [
	        "true",
	        $this->params["wmpath"],
	        $this->params["wmalpha"],
	        $this->params["copyright"],
	        $this->params["author"],
					$this->params["copyright"],
	        $this->params["author"]
	    ];

	  }else{
	    $s = [
	        "{{ wm }}",
	        "{{ wmpath }}",
	        "{{ wmalpha }}",
	        "{{ copyright }}",
	        "{{ author }}",
					"{{ vcopyright }}",
	        "{{ vauthor }}"
	    ];
	    $r = [
	        'false',
	        '',
					'',
	        '',
	        '',
					'',
					''
	    ];
	  }
	 $str = str_replace($s, $r, $str);	 
	}

  /**
   *
   * @param string $str
   */
	function Autoclose(string &$str){
	  if($this->params["autoclose"]){
	    $str = str_replace("{{ autoclose }}","checked='checked'", $str);
	  }else{
	    $str = str_replace("{{ autoclose }}","", $str);
	  }	  
	}

	// Clear the caches of drupal
	function CacheClear(){
		global $databases;
		$cacheclear = [
				'cache_access_policy',
				'cache_bootstrap',
				'cache_config',
				'cache_container',
				'cache_data',
				'cache_default',
				'cache_discovery',
				'cache_dynamic_page_cache',
				'cache_entity',
				'cache_menu',
				'cache_page',
				'cache_render',
				'cache_toolbar',
		];

		foreach ( $cacheclear AS $x ) {
			try{
				$qry = $this->con->truncate($x)->execute();
				\Drupal::messenger()->addMessage("Clear the '$x' table was successful");
			} catch( \Exception $e){
				\Drupal::messenger()->addMessage($e->getMessage());
			}
		}
	}
	/**
	 * SlideShow
	 *
	 * @return string
	 */
	function Slideshow() {		
		return $this->Slide->RenderSlideShow(
			$this->params, 			
			$this->title,
			$this->slide_subtitle,
			$this->notes,
		);				
	}

	/**
	 * SlideThumbnails
	 *
	 * @return string|mixed
	 */
	function SlideThumbnails() {
		global $base_url;
		$i = $this->slide_id;
		$str = "";

		$pics = $_SESSION ['slide'];
		unset($pics["path"]);

		$db  = count ( $pics );
		if( $db < 10 ){
			$mi = 1;
			$mid = floor( $db / 2 );
			$ma = $db;
		} else{
			$mi = 1;
			$mid = 5;
			$ma = 10;
		}
		
		$i = $mi;
		foreach($pics AS $idx => $e){		
			$s = [
				"{{ id }}",
				"{{ linktn }}",
				"{{ titletn }}",
				"{{ slidetnsubtitle }}"
			];

			$r = [
				$idx,
				$base_url . "/smplphotoalbum/slide/" . $idx . "&tn=1",
				$e["title"],
				$e["subtitle"]
			];

			if( $i == $mid ){
				$s[] = "{{ tnmiddle }}";
				$r[] = "smplslidetn_middle";
			}else{
				$s[] = "{{ tnmiddle }}";
				$r[] = "";
			}
			$str .= str_replace( $s, $r, $this->tpl["slideimage"] );
			$i++;
			if($i == $ma) break ;
		}
		return $str;
	}

	/**
	 * Write out a right table
	 * @return string
	 */
	function Table() {
		$db = count ( $this->Items );

		$str = "";
		if ($db == 0) {
			return $str;
		}

		foreach($this->Items AS $i => $Item) {
			$str .= $Item->Render ( $this->access );
		}
		return $str;
	}
	/**
	 * SortOrderfunction
	 *
	 * @return string
	 */
	private function SortOrdered() {
		$str = $this->tpl ["sortorder"];
		$str = str_replace( 
			"{{ " . $this->sortorder . " }}", 
			' selected="selected"', 
			$str 
		);
		$str = str_replace( [
				"{{ - }}",
				"{{ filename }}",
				"{{ subtitle }}",
				"{{ type }}",
				"{{ size }}",
				"{{ date }}",
				"{{ views }}"
			], "", 
		$str );

		if ($this->ascdesc != 'desc') {
			$str = str_replace( "{{ asc }}", ' selected="selected"', $str );
			$str = str_replace( "{{ desc }}", "", $str );
		} else {
			$str = str_replace( "{{ desc }}", ' selected="selected"', $str );
			$str = str_replace( "{{ asc }}", "", $str );
		}
		return $str;
	}

	/**
	 * Makes pager links
	 */
	function PagerButtons() {		
		if ($this->pagenumber < 2)
			return "";

		// Make string
		$button = $this->tpl ['pagerbuttons'];
		$str = "\n<div class=\"smpl_pager\">\n";

		if ($this->pagenumber > 5) {
			// First pager
			if ($this->page > 0) {
				$str .= str_replace(
					['{{ val }}',	'{{ class }}', '{{ name }}', "{{ disabled }}"],
					['0', '', '&laquo;&nbsp;' . 'First' ,"" ], $button
				);
			}
			// -5 page
			if ($this->page > 5) {
				$str .= str_replace(
					[ '{{ val }}'  , '{{ class }}', '{{ name }}', "{{ disabled }}"],
					[ $this->page - 5, ''           , '&lsaquo;&nbsp;' . 'Prev', "" ],
					$button
				);
			}
		}
		// if page
		if ( $this->pagenumber < 6 ) {
			$start = 0;
			$end = $this->pagenumber - 1;
		} else {
			$start = $this->page - 2;
			$end = $this->page + 2;
			// beginning of pager buttons
			if ($start < 0) {
				$end += - $start;
				$start = 0;
			}
			// end of pager buttons
			if ($end > $this->pagenumber - 1) {
				$start -= ($end - ($this->pagenumber - 1));
				$end = $this->pagenumber - 1;
			}
		}

		for($i = $start; $i <= $end; $i ++) {
			if ($i == $this->page) {
				$cl = ' smpl_pager_current';
				$d = "disabled='disabled'";
			} else {
				$cl = '';
				$d = "";
			}
			$str .= str_replace(
						["{{ class }}" , "{{ val }}" , "{{ name }}" , "{{ disabled }}"],
						[ $cl          ,       $i      , $i + 1       , $d],
						$button
					);
		}
		if ($this->pagenumber > 5) {
			// -5 page
			if ($this->page < $this->pagenumber - 5) {
				$str .= str_replace(
							['{{ val }}','{{ class }}','{{ name }}',"{{ disabled }}"],
							[ $this->page + 5,'', 'Next'. '&nbsp;&rsaquo;', "" ],
							$button
						);
			}
			// last page
			if ($this->page < $this->pagenumber - 1) {
				$str .= str_replace(
							[ '{{ class }}', '{{ val }}', '{{ name }}', "{{ disabled }}" ],
							[ '', ($this->pagenumber - 1), 'Last' . '&nbsp;&raquo;&nbsp;', ""],
							$button
						);
			}
		}
		$str .= "</div>\n";
		
		return $str;
	}

	/**
	 * Statistic of actual folder
	 *
	 * @return string
	 */
	function Statistics() {
		$str = $this->tpl ["stat"];
		$s = [
				'{{ Statistics }}',
				'{{ Dataname }}',
				"{{ Value }}",
				"{{ Number of items }}",
				"{{ Number of views }}",
				"{{ Average of views }}"
			];
		$r = [
				$this->words['Statistics'],
				$this->words['Name of data of this node'],
				$this->words['Value'],
				$this->words['Number of items'],
				$this->words['Number of views'],
				$this->words['Average of views']
			];
		$str = str_replace( $s, $r, $str );
		if ($this->numitems > 0) {
			$avgviews = round ( $this->sumviews / $this->numitems, 1 );
		} else {
			$avgviews = "There is no items";
		}
		$str = str_replace( array(
				'{{ numitems }}',
				'{{ sumviews }}',
				'{{ avgviews }}'
		), array(
				$this->numitems,
				$this->sumviews,
				$avgviews
		), $str );
		return $str;
	}

	function SmplStat() {
		$p = substr ( $this->path, 0, 1 ) == "/" ? substr ( $this->path, 1 ) : $this->path;
		$str = '<a href="admin/config/fz/smplphotoalbum/stat?smpl_path_filter=' . $p . '" target="SimpleStat">Statistics</a>';
		return $str;
	}

	/**
	 * check user access
	 *
	 * @return boolean
	 */
	function RightAccess() {
		return $this->user->id () == 1 || ($this->user->hasPermission ( "administer smplphotoalbum" ) || $this->user->hasPermission ( "edit smplphotoalbum" ));
	}

	/**
	 * Check the type of item
	 * @return boolean
	 */
	public function isaudio($entry) {
		return stripos( " " . $this->params ["audio_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function isaudiohtml5($entry) {
		return stripos( " " . $this->params ["audiohtml5_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function isvideo($entry) {
		return stripos( " " . $this->params ["video_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function isvideohtml5($entry) {
		return stripos( $this->params ["videohtml5_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function isdoc($entry) {
		return stripos( $this->params ["doc_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function iscmp($entry) {
		return stripos($this->params ["cmp_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function isapp($entry) {
		return stripos( $this->params ["app_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	public function isoth($entry) {
		return stripos($this->params ["oth_extensions"]." ", pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}
	
	public function isimage($entry) {
		if( version_compare(PHP_VERSION , "8.1.0" >= 0 ) ){
			return stripos( $this->params ["image_extensions"], pathinfo ( $entry, PATHINFO_EXTENSION )." ") > 0;
		}
		$ext = str_ireplace("avif", "", $this->params ["image_extensions"]);
		return stripos( $ext, pathinfo ( $entry, PATHINFO_EXTENSION )." " ) > 0;
	}

	public function isdis($entry) {
		return stripos( $this->params ["dis_extensions"], pathinfo ( $entry, PATHINFO_EXTENSION ) ) > 0;
	}

		/**
	 * Extensions string
	 */
	function extensionstring( $p = "", $ar = false ){		
		if ( $p == "all" ){
			$exts = 
			  $this->extensionstring("image").
				$this->extensionstring("audio").
				$this->extensionstring("audiohtml5").
				$this->extensionstring("video").
				$this->extensionstring("videohtml5").
				$this->extensionstring("app").
				$this->extensionstring("cmp").
				$this->extensionstring("doc").
				$this->extensionstring("oth");

			if( $ar ){
				$exts = trim( $exts );
				$exts = str_replace("  "," ",$exts);				
				$earray = [];
				$earray = explode(" ",$exts);
				return $earray;
			}				
			return $exts;
		}
		return $this->params[ $p."_extensions" ];
	}

	function type($entry) {
		if ($this->isimage ( $entry ))
			$type = "image";
		elseif ($this->isaudiohtml5 ( $entry ))
			$type = "audiohtml5";
		elseif ($this->isaudio ( $entry ))
			$type = "audio";
		elseif ($this->isvideohtml5 ( $entry ))
			$type = "videohtml5";
		elseif ($this->isvideo ( $entry ))
			$type = "video";
		elseif ($this->isdoc ( $entry ))
			$type = "doc";
		elseif ($this->iscmp ( $entry ))
			$type = "cmp";
		elseif ($this->isapp ( $entry ))
			$type = "app";
		elseif ($this->isoth ( $entry ))
			$type = "oth";
		elseif ($this->isdis ( $entry ))
			$type = "dis";
		else
			$type = "dis";
		return $type;
	}

	/**
	 * check current user is administrator;
	 */
	function smplphotoalbum_access() {
		$roles = \Drupal::currentUser()->getroles();
		return in_array( 'administrator', $roles ) ? true : false;
	}

	/**
	 * Test ChkImage in database
	 * @param string $entry
	 * @param string $type
	 * @param string $path
	 * @return string
	 */
	public function testImageInDB(string $entry, string $type, string $path){
		$id = $this->ChkItem2DB ( $entry, $path );
		return ($id > 0 ? 'ok, id: '.$id : 'false');
	}

	/**
	 * Test Make new Thumbnail
	 * @param string $entry
	 * @param string $source
	 * @param string $thumbnail
	 * @param string $text
	 * @return string
	 */
	public function testMakeNewThumbnail(string $entry, string $source, string $thumbnail, $width = 150){
		$msg = "Test: Make Thumbnail: ";
		return $this->MakeThumbnail ( $entry, $source, $thumbnail, $msg );
	}

  /**
   * Request POST/GET parameters from Browser
   *
   * @param mixed $key
   * @return string
   */
  function Request( string $key, $default = '', $METHOD = "GET" ){
		if($METHOD == "GET"){
			return \Drupal::request()->get($key, $default );
		}
		return \Drupal::request()->request->get($key, $default);
  }

	public function getSlide(){
		return $this->slide;
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