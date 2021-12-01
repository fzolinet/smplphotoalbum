<?php
class ImageList{
	protected $root;
	//protected $folder;
	protected $cols; 						// number of columns
	protected $rows;						// number of rows
	protected $pagelength = 0;	// the length of a page
	protected $page       = 0;	// No. of actual page
	protected $pagenumber = 0;	// number of all pages
	protected $firstimage = 0;	// the first viewing item
	protected $width;
	protected $sort;
	protected $sortsource;
	protected $filtersource;
	protected $filtertext;
	protected $subtitle;
	protected $caption;
	protected $viewed;					// is the view number of image on?
	protected $voted;						// is the vote number of image on?
	protected $edit;
	protected $style;
	protected $exif;						// exif information of image
	protected $fb      = 0;
	protected $googlep = 0;
	protected $twitter = 0;
	protected $EntryNumber=0;		// the number of items inthe folder
	protected $ImageNumber=0;		// the number of showing item (there are hidden items)
	public $aPictures = array();
	protected $RowNumber; 			// actual row number
	protected $audio;
	protected $video;
	protected $doc;
	protected $cmp;
	protected $app;
	protected $oth;
	protected $dis;
	protected $keywords;  			// SEO captions into the keeywords meta tag
	protected $modulepath;			// path of module in filesystem

	protected $ImageArray = Array('jpg','jpeg', 'JPG',"JPEG",'png', 'bmp', 'wbmp');
	protected $DocArray;
	protected $VideoArray;
	protected $AudioArray;
	protected $CmpArray;
	protected $AppArray;
	protected $OthArray;
	protected $DisArray;
	public    $RSArray = Array();     		// list of files in the actual folder
	protected $taxonomy;
	protected $taxes;
	protected $ImgProps = array(); //the image properties
	protected $tags = '';          //keywords and descriptions metatag
	protected $ffmpeg;             // is there ffmpeg lib loaded?
	protected $mobile = false;     // the device is mobile?
	
/**
 * Make the list of objects of images
 * @param unknown $params
 */
	function __construct( &$params){
	  $Pictures = Array();
		$this->preSettings($params);
		$root         = $params['root'];
		$path         = &$params['path'];
		$this->root   = $root;
		$this->ffmpeg = extension_loaded('ffmpeg');
		//Pager data input
		//Make an Image object
		
		if(!is_dir($root.$path)){
			drupal_set_message(t("This is not a folder: ").$root.$path.t('. Set the right folder in settings of Smplphotoalbum'));
		}
		$d          = opendir($root.$path);
		$tempfolder = $root.$path.TN;
		$ok = file_prepare_directory($tempfolder, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

		if(!$ok){
			drupal_set_message(t("There is no cache folder or not writable: " .$tempfolder));
		}

		//Query of this folder
		$sql = "SELECT id, path, name, type, viewnumber, votenumber, rank, caption, subtitle, deleted, filesize, thdate, fb, googlep, twitter, url, target FROM {smplphotoalbum} WHERE path='".$path."'";
		$rs  = db_query($sql);
		$db  = $rs->rowCount();
		$ok = false;
		foreach($rs as $record){
			$entry                       = $record->name;
			$this->RSArray[$record->id]  = $record;		// The results of query as parameter of images given by address
		}

		// Reading entries in the folder
		$i = 0;
		while( $d && ($entry=@readdir($d)) != false ){
			$type = false;
			if( !is_dir($root.$path.$entry) &&
		
					(                  $this->is_image($entry)        ||
					( $this->video and $this->is_videohtml5($entry) ) ||
					( $this->video and $this->is_video($entry)      ) ||
					( $this->audio and $this->is_audiohtml5($entry) ) ||
					( $this->audio and $this->is_audio($entry)      ) ||
					( $this->doc 	 and $this->is_doc  ($entry)      ) ||
					( $this->cmp   and $this->is_cmp  ($entry)      ) ||
					( $this->app   and $this->is_app  ($entry)      ) ||
					( $this->dis and !($this->is_dis  ($entry)))
				)
			)
			{
				$deleted = false;
				
				$type      = $this->smpl_type($entry);
				$entry     = utf8_encode($entry);
				//check image in the database and check or mak thumbnail if must

				$id = $this->ChkImage2DB($entry, $type, $path);
				if(!($this->RSArray[$id]->deleted)){
					$Pictures[$this->ImageNumber] = new Image($id,
					  $this->ImgProps,
						$params,
						$entry,
						$type,
						$this->tpl
          );
          
          //Filtered images
					if($this->filter && $this->setFilter($Pictures[$this->ImageNumber] ) ){
					  unset($Pictures[$this->ImageNumber]);
						$deleted = true;
				  }else{
					  $this->ImageNumber++;
          }
				}
				$this->tags .= $this->ImgProps['subtitle'].',';

			} //if
		}	//while
		@closedir($d);

		//Change the orderof images
		if($this->change_order){
			$this->setOrder($Pictures);
		}
		$db = count($Pictures);
  	reset($Pictures);
  	$i = 0;
		foreach($Pictures AS $e){
		  if($this->firstimage <= $i && $i < $this->firstimage + $this->pagelength){
		    $this->aPictures[] = $e;
		  }
		  $i++;
		}
    
		$this->pagenumber = (int) ($i / $this->pagelength) + ($i % $this->pagelength != 0 ? 1:0);
		
		//If the page number higher than should be
		if ($this->page > $this->pagenumber) $this->page = $this->pagenumber;

		//The number of rows in actual page
		$this->RowNumber = (int) ($this->ImageNumber / $this->cols) + (($this->ImageNumber % $this->cols )>0 ? 1 : 0);

		$this->addMetaKeyword($this->tags);
		$this->addMetaDescription($this->tags);
	}

	/**
	 * Check if the image in the database
	 * If not then make a thumbnail and a a new record in it.
	 * @param unknown $name
	 * @return $id - Visszaadja, hogy milyen id-je van ennek az image-nek
	 */
	function ChkImage2DB($entry, $type, $path){
	  $i = 0;
	  $db = count($this->RSArray);
	
	  if($db > 0){
	   foreach($this->RSArray AS $id => $image){
	     if(isset($image->name ) && $image->name == $entry) break;
	     $i++;
	   }
	  }else{
	    $image = new stdClass();
	  }
	  
	  //If there is not that image in the database
	  if( ! ($i<$db)) {
      //Make a thumbnail if must
     
	    if($type == 'image' || ($this->ffmpeg && $type == 'video' && variable_get('smpl_ffmpeg') ) ){
        if(!is_readable($this->root .$this->path.TN.$entry) || !is_readable($this->root .$this->path.TN.$entry.".png")){
          $this->MakeThumbnail($entry);
        }
      }
      if(is_readable($this->root.$this->path.$entry)){
        $filesize = @filesize($this->root.$this->path.$entry);
      }else {
        $filesize = 0;
      }
      
      $id   = db_insert('smplphotoalbum')
      ->fields(array(
          'path' 			=> $path,
          'name'			=> $entry,
          'type'      => $type,
          'subtitle'  => $entry,
          'votenumber'=> 0,
          'viewnumber'=> 0,
          'rank'			=> 0,
          'caption'		=> $entry,
          'deleted'   => 0,
          'filesize'  => $filesize,
          'thdate'    => REQUEST_TIME,
          'fb'        => 0,
          'googlep'   => 0,
          'twitter'   => 0,
          'url'       => '',
          'target'    => '_blank'
      ))
      ->execute();
     
      $id = Database::getConnection()->lastInsertId();
      //Data of actual record
      $image->viewnumber = 0;
      $image->votenumber = 0;
      $image->rank       = 0;
      $image->subtitle   = $entry;
      $image->caption    = $entry;
      $image->deleted    = 0;
      $image->filesize   = $filesize;
      $image->thdate     = REQUEST_TIME;
      $image->fb         = 0;
      $image->googlep    = 0;
      $image->twitter    = 0;

      $image->url        = '';
      $image->target     = '_blank';
      $this->RSArray[$id]= $image;
      
      if($type=='image'){
        drupal_set_message(t("Image added to DB: ") .$entry.".");
      }	elseif($type=='audiohtml5'){
        drupal_set_message(t("Audio HTML5 added to DB: ") .$entry.".");
      }	elseif($type=='audio'){
        drupal_set_message(t("Audio added to DB: ") .$entry.".");
      }elseif($type=='videohtml5'){
        drupal_set_message(t("Video HTML5 added to DB: ") .$entry.".");
      } elseif($type=='video'){
        drupal_set_message(t("Video added to DB: ") .$entry.".");
      }	elseif($type=='doc'){
        drupal_set_message(t("Document added to DB: ") .$entry.".");
      }	elseif($type=='compress'){
        drupal_set_message(t("Compressed file added to DB: ") .$entry.".");
      }	elseif($type=='application'){
        drupal_set_message(t("Application added to DB: ") .$entry.".");
      }	elseif($type=='other'){
        drupal_set_message(t("Other file added to DB: ") .$entry.".");
      }
	    //It makes a thumbnail with default and gives back the id.
	  }

	  //Properties of actual Image
	  $this->ImgProps['viewnumber'] = $image->viewnumber;
	  $this->ImgProps['votenumber'] = $image->votenumber;
	  $this->ImgProps['rank'      ] = $image->rank;
	  $this->ImgProps['vrank'     ] = ($image->votenumber >0 )? (float) ($image->rank / $image->votenumber ):0.0;
	  $this->ImgProps['subtitle'  ] = $image->subtitle;
	  $this->ImgProps['caption'   ] = $image->caption;
	  $this->ImgProps['deleted'   ] = $image->deleted;
	  $this->ImgProps['thdate'    ] = $image->thdate;
	  $this->ImgProps['fb'        ] = $image->fb;
	  $this->ImgProps['googlep'   ] = $image->googlep;
	  $this->ImgProps['twitter'   ] = $image->twitter;
	  $this->ImgProps['url'       ] = $image->url;
	  $this->ImgProps['target'    ] = $image->target;
	  $update = false;
	  
	  if(empty($image->thdate) || ($image->thdate < time() )){
  	  $image->thdate = @filemtime($this->root.$this->path.$entry);
  	 // fz_die(date("Y-m-d h:i:s",$image->thdate));
	    db_select('UPDATE {smplphotoalbum} SET thdate ='.$image->thdate.' WHERE id = '.$id);
	  }
	  $this->ImgProps['datetime'  ] = $image->thdate;
	  
	  if($image->filesize == 0){
	    $image->filesize = @filesize($this->root.$this->path.$entry);
	    db_select("UPDATE {smplphotoalbum} SET filesize =".$image->filesize." WHERE id = ".$id);
	  }
	  $this->ImgProps['filesize'  ] = @filesize( $this->root.$this->path.$entry);
	  
	  if(is_readable($this->root . $this->path . TN . $entry ) ) {
	    $this->CheckThumbnail($entry, $image->thdate);
	  }elseif(is_readable($this->root . $this->path . TN . $entry . ".png") ){
	    $this->CheckThumbnail($entry.".png", $image->thdate);
	  } else {
	    $this->MakeThumbnail($entry);
	  }
	  
	  return $id;
	}

	/**
	 * Check Thumbnail time
	 */
	function CheckThumbnail($entry,$datetime){
	  $thDate = filemtime( $this->root.$this->path.TN.$entry );	//Time of Thumbnail
	  $orDate = $datetime;			//Time of original file
	
	  if($orDate > $thDate+24*60*60){						//The date of thumbnail
	
	    if(!unlink($this->root.$this->path.TN.$entry)){
	      drupal_set_message(t('Thumbnail can not delete: ') . $this->path.TN.$this->entry);
	      return;
	    }
	    $this->MakeThumbnail($entry, 'Updated thumbnail: ');					//Thumbnail updated
	  }
	}
	
	/**
	 * Update thumbnail if the original image is changed
	 */
	function MakeThumbnail($entry, $msg="New thumbnail: "){
	  global $user;
	  $lehet = ($user->uid==1 ) or user_access('edit smplphotoalbum') or user_access('administer smplphotoalbum');
	  $ok = true;
	  //If the folder not exists
	  if(!is_dir($this->root.$this->path.TN)){
      $ok =  mkdir($this->root.$this->path.TN);
	  }
	  
	  //If there is the folder it makes the new thumbnail
	  if($this->is_image($entry) || ( $this->ffmpeg && $this->is_video($entry) && extension_loaded("GD") ) ){
	    if($ok){
	      $ok = $this->NewThumbnail($entry);
	      if ($ok) {	//It was successfull
	        drupal_set_message(t($msg) . $this->path.TN.$entry);
	      }else{
	        drupal_set_message(t("Can not writeable this file: ") . $this->path.TN.$entry);
	      }
	    }else{   //The folder is not writeable
	      drupal_set_message(t("Folder of thumbnails is not writeable:") . $this->path.TN );
	    }
	  }else{
	    $source = str_replace("\\","/",realpath(dirname(__FILE__) ."/../image" ) );
	    $ext    = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
	    
	    if( $this->is_audio($entry) )	    $source .= "/audio_".$ext.".png";
	    elseif( $this->is_doc($entry) )   $source .= "/doc_"  .$ext.".png";
	    elseif( $this->is_cmp($entry) )   $source .= "/cmp_"  .$ext.".png";
	    elseif( $this->is_app($entry) )   $source .= "/app_"  .$ext.".png";
	    elseif( $this->is_video($entry))  $source .= "/video_".$ext.".png";
	    else{
	      $source .= "/other.png";
	    }
	    $ok = copy($source,$this->root.$this->path.TN.$entry.".png");
	  }
	  return $ok;
	}
	
	//Make new thumbnails from GIF, PNG or JPG | JPEG | BMP | WBMP
	function NewThumbnail($entry) {
	  //size of original big picture
	  $type = strtolower(pathinfo($entry,PATHINFO_EXTENSION));
	  $source = $this->root.$this->path.$entry;
	  $target = $this->root.$this->path.TN.$entry;
	  $ok = false;
	  	  	  
	  if ( $this->is_video($entry) ){
	    $f = new _ffmpeg($this->root, $this->path, $entry, $this->width);
	    $ok = $f->MakeThumbnail();
	    unset($f);
	  }else{
	    $imagic   = extension_loaded('imagick');
	    //fz_die($source);
	    if(false && $imagic){
	      $im = new Imagick($source);
	      //fz_die($im);
	      $dx = $im->getImageWidth();
	      $dy = $im->getImageHeight();
	      $ok = false;
	      $type = strtolower(pathinfo($entry,PATHINFO_EXTENSION));
	      if($dx > $this->width ) {
	        $im->resizeImage($this->width,$this->width * $dy / $dx,Imagick::FILTER_LANCZOS,1.1);
	        $ok = $im->writeimage($target);
	      }
	    }else{
	      $size = GetImageSize($this->root.$this->path.$entry);
	      $dx = $size[0];
	      $dy = $size[1];
	       
	      if($dx > $this->width ) {
	        //Target image
	        $dst_im = @ImageCreateTrueColor( $this->width, $this->width * $dy / $dx);
	        switch($type){
	          case 'jpg':
	          case 'JPG':
	          case 'JPEG':
	          case 'jpeg':
	            $im = ImageCreateFromJPEG ($source );
	            $a  = Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width*$dy/$dx, $dx, $dy);
	            $ok = @Imagejpeg ($dst_im, $target,80);
	            break;
	          case 'png':
	          case 'PNG':
	            $im = @ImageCreateFromPNG ($source );
	            $a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width*$dy/$dx, $dx, $dy);
	            $ok = @Imagepng ($dst_im, $target,9);
	            break;
	          case 'wbmp':
	            $im = @ImageCreateFromwbmp ($source );
	            $a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width*$dy/$dx, $dx, $dy);
	            $ok = @Imagewbmp($dst_im, $target);
	            break;
	          case 'gif':
	          case 'GIF':
	            $im = @ImageCreateFromGif ($source );
	            $a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width*$dy/$dx, $dx, $dy);
	            $ok = @imagegif($dst_im, $target);
	            break;
	          default:
	            $ok = true;
	        }
	      }
	    }
	  }
	  return $ok;
	}
	
	/**
	 * Set parameters, load templates, etc.
	 * @param array $params
	 */

	function preSettings(&$params){
		$this->path       = &$params['path'];	//
		$this->cols       = &$params['cols'];
		$this->rows       = &$params['rows'];
		$this->width      = &$params['width'];
		$_SESSION['smpl_w'] = &$params['width'];
		$this->order      = &$params['order'];
		$this->sort       = &$params['sort'];
		$this->change_order=&$params['change_order'];
		$this->filter     = &$params['filter'];
		$this->sub        = &$params['subt'];
		$this->capt       = &$params['capt'];
		$this->viewed     = &$params['viewed'];
		$this->voted      = &$params['voted'];
		$this->edit 		  = &$params['edit'];
		$this->style      = &$params['style'];
		$this->exif       = &$params['exif'];
		$this->issocial   = &$params['issocial'];
		$this->audio      = &$params['audio'];
		$this->video      = &$params['video'];
		$this->doc        = &$params['doc'];
		$this->cmp        = &$params['cmp'];
		$this->app        = &$params['app'];
		$this->oth        = &$params['oth'];
		$this->dis        = &$params['dis'];
		$this->keywords   = &$params['keywords'];
		$this->taxonomy   = &$params['taxonomy'];
		$this->taxes      = &$params['taxes'];	//This value contains the taxonomy words or when it is false there is no taxonomy page.
		$this->html5      = &$params['html5'];  //Use the html5 widgets
		$this->mobile     = &$params['mobile'];
		$this->AudioArray      = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_audio_extensions',SMPLAUDIOEXT))));
		$this->AudioHTML5Array = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_audiohtml5_extensions',SMPLHTML5AUDIOEXT))));
		$this->VideoArray      = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_video_extensions',SMPLVIDEOEXT))));
		$this->VideoHTML5Array = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_videohtml5_extensions',SMPLHTML5VIDEOEXT))));
		
		$this->DocArray   = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_doc_extensions'  ,SMPLDOCEXT))));
		
		$this->CmpArray   = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_cmp_extensions'  ,SMPLCMPEXT))));
		$this->AppArray   = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_app_extensions'  ,SMPLAPPEXT))));
		$this->OthArray   = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_oth_extensions'  ,SMPLOTHEXT))));
		$this->DisArray   = explode(' ',str_replace(',',' ',str_replace('.','',variable_get('smpl_dis_extensions'  ,SMPLDISEXT))));
		$params['types']['image']      = $this->ImageArray;
		$params['types']['video']      = $this->VideoArray;
		$params['types']['videohtml5'] = $this->VideoHTML5Array;
		$params['types']['audio']      = $this->AudioArray;
		$params['types']['audiohtml5'] = $this->AudioHTML5Array;
		$params['types']['doc']        = $this->DocArray;
		$params['types']['cmp']        = $this->CmpArray;
		$params['types']['app']        = $this->AppArray;
		$params['types']['oth']        = $this->OthArray;
		$params['types']['dis']        = $this->DisArray;
		
    if(isset($_REQUEST['smplpage'])){
      $this->page = (int) ($_REQUEST['smplpage']);
    }else {
      $this->page = 0;
    }
		$this->pagelength = $this->cols*$this->rows;
		
		//Reading templates
		$this->tpl        = array();
    $tplp = __DIR__."/../templates";
		$this->tpl['gallery'] = nldel(file_get_contents($tplp."/gallery.tpl") );
		$this->tpl['table']   = nldel(file_get_contents($tplp."/table.tpl"));
		$this->tpl['tr']      = nldel(file_get_contents($tplp."/tr.tpl"));
		$this->tpl['td']      = nldel(file_get_contents($tplp."/td.tpl"));
		
		$this->tpl['image']      = nldel(file_get_contents($tplp."/image.tpl"));
		$this->tpl['video']      = nldel(file_get_contents($tplp."/video.tpl"));
		$this->tpl['videohtml5'] = nldel(file_get_contents($tplp."/videohtml5.tpl"));
		$this->tpl['audio']      = nldel(file_get_contents($tplp."/audio.tpl"));
		$this->tpl['audiohtml5'] = nldel(file_get_contents($tplp."/audiohtml5.tpl"));
		$this->tpl['others']     = nldel(file_get_contents($tplp."/others.tpl"));
		
		$this->tpl['sub']     = nldel(file_get_contents($tplp."/sub.tpl"));
		$this->tpl['vote']    = nldel(file_get_contents($tplp."/vote.tpl"));
		$this->tpl['view']    = nldel(file_get_contents($tplp."/view.tpl"));
		$this->tpl['exif']    = nldel(file_get_contents($tplp."/exif.tpl"));
		$this->tpl['desc']    = nldel(file_get_contents($tplp."/desc.tpl"));
		$this->tpl['edit']    = nldel(file_get_contents($tplp."/edit.tpl"));
		$this->tpl['fb']      = nldel(file_get_contents($tplp."/fb.tpl"));
		$this->tpl['fsize']   = nldel(file_get_contents($tplp."/fsize.tpl"));
		$this->tpl['order']   = nldel(file_get_contents($tplp."/order.tpl"));
		$this->tpl['stat']    = nldel(file_get_contents($tplp."/stat.tpl"));
		$this->tpl['url']     = nldel(file_get_contents($tplp."/url.tpl"));
		$this->tpl['taxonomy']= nldel(file_get_contents($tplp."/taxonomy.tpl"));
		$this->tpl['pager']   = nldel(file_get_contents($tplp."/pager.tpl"));
		
		$this->tpl['mobile_table'] = nldel(file_get_contents($tplp."/mobile_table.tpl"));
		$this->tpl['mobile_div']   = nldel(file_get_contents($tplp."/mobile_div.tpl"));
		
		$this->tpl['video']        = nldel(file_get_contents($tplp."/video.tpl"));
		$this->tpl['videohtml5']   = nldel(file_get_contents($tplp."/videohtml5.tpl"));
		$this->tpl['audio']        = nldel(file_get_contents($tplp."/audio.tpl"));
		$this->tpl['audiohtml5']   = nldel(file_get_contents($tplp."/audiohtml5.tpl"));
		$this->tpl['others']       = nldel(file_get_contents($tplp."/others.tpl"));
		$this->tpl['imgeditform']  = nldel(file_get_contents($tplp."/imgeditform.tpl"));
		
		if(isset($_REQUEST[SMPLADMIN])){
			$this->firstimage = 0;
			$this->page       = 0;
			$this->pagelength = 1000;
		}else{
			$this->firstimage = $this->page * $this->pagelength;
		}
	}

   /**
	 * Write out a right table
	 */
	function Table(){
		global $user;

		$uideditok = user_access('edit smplphotoalbum') or user_access('administer smplphotoalbum');
		//start and end picture
		$db = count($this->aPictures);
		reset($this->aPictures);
		
    if($db>0){
		  $rownumber = (int)($db / $this->cols)+1;
	    $smpl_rows = '';
		  $dbi = 0;
	    for($i=0; $i<$rownumber; $i++){
	  	    $smpl_row_a = '';
	  	    $smpl_row_b = '';
	  	    
	  	    for($j=0; ( $j<$this->cols) && ($dbi < $db) ; $j++){
	  		    $aP = current($this->aPictures);

				    if(is_object($aP) ){
				      $a = "";
				      $b = "";
				      $aP->Render($uideditok,$a,$b);
				     
				      if(!$this->mobile){
				        $td          = str_replace("{{id}}", $aP->getID(), $this->tpl["td"]);
				        $smpl_row_a .= str_replace('{{smpl_td}}', $a, $td);
				        $smpl_row_b .= str_replace('{{smpl_td}}', $b, $td);
				      }else{
				        $td          = str_replace("{{id}}", $aP->getID(), $this->tpl["mobile_div"]);
				        $smpl_rows  .= str_replace('{{smpl_mobile_div}}', $a.$b, $td);
				      }
				    }else{
				      if(!$this->mobile){
					      $smpl_row_a .= str_replace("      {{smpl_td}}\n", '', $this->tpl["td"]);
					      $smpl_row_b .= str_replace("      {{smpl_td}}\n", '', $this->tpl["td"]);
					      $smpl_row_a  = str_replace('{{'.SMPLSIMPLELIST.'}}' , '', $smpl_row_a);
					      $smpl_row_b  = str_replace('{{'.SMPLSIMPLELIST.'}}' , '', $smpl_row_b);
				      }
				    }
				    next($this->aPictures);
				    $dbi++;
	  	    }
	  	    if(!$this->mobile){
	  	      $smpl_rows .= str_replace('{{smpl_row_a}}', "\n". $smpl_row_a."\n" , $this->tpl["tr"] );
	  	      $smpl_rows  = str_replace('{{smpl_row_b}}', "\n". $smpl_row_b."\n" , $smpl_rows );
	  	    }
	    }
    }else{
        $smpl_rows = t('There is no image in this folder or taxonomy');
    }
		if(!$this->mobile){
      $str = str_replace('{{smpl_rows}}', $smpl_rows, $this->tpl["table"]);
		}else{
		  $str = str_replace('{{smpl_mobile_rows}}', $smpl_rows, $this->tpl["mobile_table"]);
		}
		$str = nldel($str);
		return $str;
	}

	/**
   * Makes pager links
   */
	function PagerButtons(){
	  $button =  $this->tpl['pager'];
	  $db = 10;

	  $url = request_uri();
	  if (isset($_REQUEST['smplpage'])){
	      $this->page = smpl_clean_input($_REQUEST['smplpage']);
	      
	  }else{
	      $this->page = 0;
	  }
	 
		$str ='<div class="smpl_pager" '.($this->mobile?'style="clear: left;"':'').'>';

		//First pager //Previous pager
		if ($this->page >0 ){
			$str .= str_replace( array('{class}','{value}','{name}'), array( '','0'              , '&lsaquo;&lsaquo;&nbsp;'.t('first')), $button);
			$str .= str_replace( array('{class}','{value}','{name}'), array('' ,($this->page-1)  , '&lsaquo; '.t('prev'))              , $button);
		}

		if($this->page - (int) ($db / 2) >= 0 ){
			$start = $this->page - (int) ($db / 2);
		}else{
			$start = 0;
		}

		if( $start + $db < $this->pagenumber ){
			$end = $start + $db;
		}else{
			$end = $this->pagenumber-1;
			if($end-$db>0){
				$start = $end -$db;
			}else{
				$start=0;
			}
		}

		for($i = $start; $i <= $end; ++$i){
			if($i == $this->page){
				$str .= str_replace( array('{class}','{value}','{name}'), array('smpl_pager_current',($i)  , ($this->page+1)), $button);
			}else{
			  $str .= str_replace( array('{class}','{value}','{name}'), array('' ,($i)  , ($i+1)), $button);
			}
		}

		if ($this->page+1 < $this->pagenumber){
		  $str .= str_replace( array('{class}','{value}','{name}'), array('' ,($this->page+1),t('next').'&rsaquo;&nbsp;'), $button);
			$str .= str_replace( array('{class}','{value}','{name}'), array('' ,($this->pagenumber-1),t('last').' &raquo;&nbsp;'), $button);
		}

		$str .="</div>";
		$str = nldel($str);
		return $str;
	}

	/**
	 * Render the table of images
	 */
	function Render(){
	  global $base_path;
	  
	  $uideditok = user_access('edit smplphotoalbum') or user_access('administer smplphotoalbum');
	  
	  if(isset($_REQUEST['smpllist']) && $_REQUEST['smpllist'] == 0){
	    $_REQUEST[SMPLADMIN] = 0;
	    unset($_SESSION[SMPLADMIN]);
	  }
	 
	  if(isset($_REQUEST['smplsubcap'])  && smplphotoalbum_access() ){
	    $this->EditAllUpdate();
	  }
	
		if(isset($_REQUEST['smplcacheclear']) && smplphotoalbum_access() ){
	    $this->CacheClearAll();
	  }
		$str = $this->tpl['gallery'];

		$pager = '';
		$order = '';
		$list  = '';
		if(( (isset($_REQUEST[SMPLADMIN]) && $_REQUEST[SMPLADMIN]==1) || (isset($_SESSION[SMPLADMIN]) && $_SESSION[SMPLADMIN] == 1) ) ){
			//Admin table
			$table = $this->SimpleTable();
			$list  = $this->SimplePhotoalbum();
			if ((isset($_REQUEST[SMPLADMIN]) && $_REQUEST[SMPLADMIN]==1) ){
			  $_SESSION[SMPLADMIN] = 1;
			}
		}else{
			//Normal table
		  //Pager buttons
			if($this->pagenumber>1){
			  $pager = $this->PagerButtons();
			}

			//Change the order of images - form
		  if($this->change_order){
		    $order = $this->OrderLink();
		  }
			
			$table = $this->Table();						// table of Images
			$list  = $this->SimpleListUrl();		// link of simple list
		}
    //Buttons
		if($this->edit && $uideditok){
		    $edit_all  = $this->EditAllBtn();
		    $edit_all .= $this->CacheClearBtn();
		    $help =$this->Help();
		}else{
		    $edit_all='';
		    $help = "";
		}

		$str = str_replace('{{smpl_edit_all}}', $edit_all, $str);
		$str = str_replace('{{smpl_order}}', $order, $str);
		$str = str_replace('{{smpl_list}}' , $list,  $str);
		$str = str_replace('{{smpl_pager}}', $pager, $str);
		$str = str_replace('{{smpl_help}}' , $help, $str);
		$str = str_replace('{{smpl_table}}', $table, $str);
		$str = str_replace('{{Done}}'      , t("Done"), $str);
		$str = str_replace('{{Save}}'      , t('Save'), $str);
		$str = str_replace('{{Cancel}}'    , t('Cancel'), $str);
		$str = str_replace('{{smpl.ajax}}' , $base_path.'smplphotoalbum', $str);
		$str = str_replace('{{Subtitle}}'  , t("Subtitle"), $str);
		$str = str_replace('{{Delete}}'    , t("Delete"), $str);
		$str = str_replace('{{Trash}}'     , t("Trash"), $str);
		$str = str_replace('{{Rename}}'    , t("Rename not success"), $str);
		$str = str_replace('{{Delete_not}}', t("Image delete not success"), $str);
		$str = str_replace('{{Kill}}'      , t("Image kill not success"), $str);
		$str = str_replace('{{updown}}'    , $this->sort, $str);
		$str = str_replace('{{keywords}}'  , $this->keywords? 'true':'false', $str);
		$access = smplphotoalbum_access();
		if(!$access){
		  $str = preg_replace("#<EditForm(.*?)<\/EditForm>#imxs","",$str);
		  $str = preg_replace("#<ImgEditForm(.*?)<\/ImgEditForm>#imxs","",$str);
		}else{
		  $str = str_replace("<EditForm>"    ,"" ,$str);
		  $str = str_replace("<ImgEditForm>" ," ",$str);
		  $str = str_replace("</EditForm>"   ,"" ,$str);
		  $str = str_replace("</ImgEditForm>","" ,$str);
		  $str = str_replace('{{ImgEditForm}}'   , $this->tpl['imgeditform'], $str);
  		$str = str_replace('{{Edit}}'          , t("Edit")    , $str);
		  $str = str_replace('{{Thumbnail}}'     , t("Thumbnail"), $str);
		  $str = str_replace('{{Undo}}'          , t("Undo")    , $str);
		  $str = str_replace('{{Redo}}'          , t("Redo")    , $str);
		  $str = str_replace('{{Save}}'          , t("Save")    , $str);
		  $str = str_replace('{{Cancel}}'        , t("Cancel")  , $str);
		  //
      $str = str_replace("{{Rotate}}"        , t("Rotate")  , $str);
		  $str = str_replace("{{Nothing}}"       , t("Semmi")   , $str);
  		$str = str_replace("{{Left}}"          , t("Left")    , $str);
  		$str = str_replace("{{Right}}"         , t("Right")   , $str);
  		$str = str_replace("{{Rotate_Number}}" , t("Degree of rotate"), $str);
  		$str = str_replace("{{FlipVertical}}"  , t("Flip vertical")   , $str);
  		$str = str_replace("{{FlipHorizontal}}", t("Flip horizontal") , $str);
  		$str = str_replace("{{Crop}}"          , t("Crop") , $str);
  		$str = str_replace("{{Left top}}"      , t("Left")." ".t("Top") , $str);
  		$str = str_replace("{{Size}}"          , t("Size") , $str);
  		$str = str_replace("{{Resize}}"        , t("Resize") , $str);
  		$str = str_replace("{{Aspect ratio}}"  , t("Aspect ratio") , $str);
  		$str = str_replace("{{pixels}}"        , t("pixels") , $str);
  		$str = str_replace("{{Contrast}}"      , t("Contrast") , $str);
  		$str = str_replace("{{Brightness}}"    , t("Brightness") , $str);
  		$str = str_replace("{{Gamma}}"         , t("Gamma") , $str);
  		$str = str_replace("{{Input}}"         , t("Input") , $str);
  		$str = str_replace("{{Output}}"        , t("Output") , $str);
  		$str = str_replace("{{RGB}}"           , t("RGB") , $str);
  		$str = str_replace("{{DeNoise}}"       , t("Reduce noise"), $str);
  		$str = str_replace("{{Right bottom}}"  , t("Right")." ".t("Bottom") , $str);
  		$str = str_replace("{{404}}",$base_path.drupal_get_path('module','smplphotoalbum')."/image/loading.gif",$str);
		}
		return $str;
	}
	
	function SimplePhotoalbum(){
	  $url = request_uri();
	  $out ='
	  <div><FORM METHOD="POST">
	    <INPUT TYPE="HIDDEN" NAME="'.SMPLADMIN.'" value="0">
	    <input type="submit" class="smpl_pagebutton" value="'.t('Simple Photoalbum').'">
	  </FORM></div>';
	  return $out;
	}
	/*
	 * Simple administration link
	 */
	function SimpleListUrl(){
	  $url = request_uri();
		$out ='<div>
		    <FORM METHOD="POST">
		    <INPUT TYPE="HIDDEN" NAME="'.SMPLADMIN.'" value="1">
		    <input type="submit" class="smpl_pagebutton" value="'.t('Simple list of all items').'"></FORM></div>';
		return $out;
	}

	/*
	 * Simple table of items
	 */
  function SimpleTable(){
  	global $user;
    
  	$uideditok = user_access('edit smplphotoalbum') or user_access('administer smplphotoalbum');
  	//Change the order of images - form

		//Header of table
		$header = $this->Rowheader($uideditok);
		$rows   = array();
		$i=1;
		foreach( $this->aPictures AS $idx => $aP ){
			$rows[$i] = $aP->RowRender1($uideditok, $i,$header);
			$i++;
		}
	
		//smpl_test($header);
		$table = array(
		    'header'     => $header,
		    'rows'       => $rows,
		    'sticky'     => true,
		    'attributes' => array(),
		    'colgroups'  => array(),
		    'empty'      => t('There is no data'),
		    "caption"    => t("Simple list of items"));
		$str  = theme_table( $table);
		$str .= $this->SmplStat();
		$str .= $this->Statistics();
		return $str;
	}
	
/**
 * The header of table
 * @param string $uideditok
 * @return multitype:The multitype:string The  multitype:The  Ambigous <The, unknown, string, A, Optional>
 */
	function Rowheader($uideditok=false){
	  
	  $style = "text-align: center; padding: 2px; vertical-align: top; background-color: #DFEDF7;";
		$header[] = array('data' => t('Number')    ,'field' => t('Number')  ,"sort" => "ASC", 'align'=>"center", "style"=> $style );
		$header[] = array('data' => t('Image')     ,'field' => t('Image')   ,"sort" => "ASC", 'align'=>"center", "style"=> $style );
		
		$header[] = array('data' => t('Subtitle')  ,'field' => t('Subtitle'),"sort" => "ASC", 'align'=>"center", "style"=> $style);
		

		$header[] = array('data' => t('Url')       ,'field' => t('Url')     ,"sort" => "ASC", 'align'=>"center", "style"=> $style);

		if($this->voted){
			$header[] = array('data' => t('Voted')   ,'field' => t('Voted')   ,"sort" => "ASC", 'align'=>"center", "style"=> $style);
		}
		if($this->viewed){
			$header[] = array('data' => t('Viewed')  ,'field' => t('Viewed')  ,"sort" => "ASC", 'align'=>"center", "style"=> $style);
		}
		if($this->exif){
			$header[] = array('data' => t('Filesize'),'field' => t('Filesize'),"sort" => "ASC", 'align'=>"center", "style"=> $style);
			$header[] = array('data' => t('Date')    ,'field' => t('Date')    ,"sort" => "ASC", 'align'=>"center", "style"=> $style);
		}
		if($uideditok && $this->edit){
			$header[] = array('data' => t('Edit')    ,'field' => t('Edit')    ,"sort" => "ASC", 'align'=>"center", "style"=> $style);
		}
		return $header;
	}
	/**
	 * Statistic og actual folder
	 * @return mixed
	 */
	function Statistics(){
		$str = $this->tpl['stat'];

		$viewnumber = 0;
		$lastview   = 0;
		$lastvote   = 0;
		$votenumber = 0;
		$rank       = 0;
		$db         = 0;
		$str = str_replace('{{Items}}', t('Items'),$str);
		$str = str_replace('{{Values}}', t('Values'),$str);
		foreach($this->aPictures as $i =>$ap){
			$rank       += $ap->getRank();
			$votenumber += $ap->getVotenumber();
			$viewnumber += $ap->getOpened();
			$lv          = $ap->getLastEvent("view");
			if($lv > $lastview){
			  $lastview = $lv;
			}
			
			$lv          = $ap->getLastEvent("vote");
			if($lv > $lastvote){
			  $lastvote = $lv;
			}
			$db++;
		}
		
		$row= '<tr class="smpl_stat_row"><td class="smpl_stat_td smpl_stat_tdleft">{{Item}}</td><td class="smpl_stat_td smpl_stat_tdval">{{value}}</td></tr>';
		$viewedavg = $votedavg = $viewedavg =$viewedsum = $viewlast = $votelast = $row ;

		$viewedsum = str_replace('{{Item}}',t('Sum of viewed')    ,$row);
		$viewedsum = str_replace('{{value}}',$viewnumber, $viewedsum );

		$viewedavg = str_replace('{{Item}}',t('Average of viewed'),$row);
		$viewedavg = str_replace('{{value}}',( $db>0 ? number_format((float)($viewnumber / $db ),3,'.',''):0 ),$viewedavg );
		
		$viewlast  = str_replace('{{Item}}',t('Last view')        ,$row);
		$viewlast  = str_replace('{{value}}', date("Y.m.d", $lastview), $viewlast);
		
		$votelast  = str_replace('{{Item}}',t('Last vote')        ,$row);
		$votelast  = str_replace('{{value}}', date("Y.m.d", $lastvote), $votelast ) ;
		
		$votedsum = str_replace('{{Item}}',t('Sum of voting')     ,$row);
		$votedsum = str_replace('{{value}}',$votenumber, $votedsum);

		$votedavg = str_replace('{{Item}}',t('Average of voting') ,$row);

		$number   = $votenumber >0 ? (float)($rank / $votenumber ) : 0 ;
		
		$votedavg = str_replace('{{value}}',number_format($number,2,'.',''),$votedavg );
		
		$tbl      = $viewedsum . $viewedavg . $viewlast . $votedsum .$votelast . $votedavg;
		
		$str      = str_replace('{{statlist}}',$tbl,$str);
		return $str;
	}

	function SmplStat(){
	  $p = substr($this->path,0,1) == "/"?substr($this->path,1):$this->path;
	  $str ='<a href="admin/config/fz/smplphotoalbum/stat?smpl_path_filter='.$p.'" target="SimpleStat">'.t('Statistics').'</a>';
	  return $str;
	}
	/**
	 * Buttons with it you can edit properties all of the images
	 */
  function EditallBtn(){
     $out = '<form METHOD="GET" style="display:inline;"><input type="hidden" name="smplsubcap" value="1">';
	   $out .= '<input type="hidden" name="smplpage" value="'.$this->page.'">';
	   if(isset($_REQUEST[SMPLADMIN])){
	     $out .= '<input type="hidden" name="'.SMPLADMIN.'" value="1">';
	   }
	   $out .= '<input type="submit" id="Edit_all" type="button" name="Edit_all" class="smpl_edit_all" value="&nbsp;&nbsp;&nbsp;&nbsp;'.t('Edit all subscription/caption').'">';
	   $out .= '</form>';
	   return $out;
	}
	
	/**
	 * Change all the Subtitle and Caption Update of actual page
	 * @return string
	 */
	function EditAllUpdate(){
	  $ok = false;
	  
	  foreach($this->aPictures As $i => $ap){
	    $subtitle = $ap->getSubtitle();
	    $caption  = $ap->getCaption();
	    $id       = $ap->getID();
	    $sub1     = _smplphotoalbum_sub($id,$subtitle);
	    $caption1 = _smplphotoalbum_caption($id,$caption);
	  }
	  $this->CacheClearAll();
	}
	
  /**
  * Clear all the cache and the filed cache too;
  */
	function CacheClearAll(){
	  cache_clear_all();
	  $db = db_delete("cache_field")->condition("cid",db_like("field:node:")."%","LIKE")->execute();
	  drupal_set_message("All cache is cleared");
	}
		
	function CacheClearBtn(){
	  $out = '<form METHOD="POST" style="display:inline;"><input type="hidden" name="smplcacheclear" value="1">';
	  $out .= '<input type="hidden" name="smplpage" value="'.$this->page.'">';
	  if(isset($_REQUEST[SMPLADMIN])){
	    $out .= '<input type="hidden" name="'.SMPLADMIN.'" value="1">';
	  }
	  $out .= '<input type="submit" id="CacheClear" type="button" name="cache_clear" class="smpl_cache_clear" value="&nbsp;&nbsp;&nbsp;&nbsp;'.t('Cache clear').'">';
	  $out .= '</form>';
	  return $out;
	}

	/**
	 * Set the order of items
	 */
	function OrderLink(){
		$str = $this->tpl['order'];
		$str = str_replace('{{method}}'  , 'get'                  , $str);
		$str = str_replace('{{URI}}'     , $_SERVER['REQUEST_URI'], $str);

		if(isset($_REQUEST["smpllist"])){
			$hiddens ='<input type="hidden" name="smpllist" value="1" />';
		}else{
			$hiddens ='';
		}

		$str = str_replace('{{hiddens}}'        , $hiddens     , $str);
		$str = str_replace('{{order}}'          , t('Order')   , $str);
		$str = str_replace('{{source_of_order}}', t('Source of order'),$str);
		$str = str_replace('{{Filename}}'       , t('Filename') , $str);
		$str = str_replace('{{Sub}}'            , t('Subtitle') , $str);
		$str = str_replace('{{Size}}'           , t('Size')     , $str);
		$str = str_replace('{{Date}}'           , t('Date')     , $str);
		$str = str_replace('{{Votes}}'          , t('Votes')    , $str);
		$str = str_replace('{{Views}}'          , t('Views')    , $str);

		$str = str_replace('{{ord_filename}}', ($this->sortsource=='filename'?'selected':'selected'), $str);
		$str = str_replace('{{ord_sub}}'     , ($this->sortsource=='sub' ?'selected':'')    , $str);
		$str = str_replace('{{ord_size}}'    , ($this->sortsource=='size'?'selected':'')    , $str);
		$str = str_replace('{{ord_date}}'    , ($this->sortsource=='date'?'selected':'')    , $str);
		$str = str_replace('{{ord_votes}}'   , ($this->sortsource=='vote'?'selected':'')    , $str);
		$str = str_replace('{{ord_views}}'   , ($this->sortsource=='view'?'selected':'')    , $str);

		$str = str_replace('{{Direction_of_order}}', t('Direction of order') , $str);
		$str = str_replace('{{Ascending}}'         , t('Ascending')  , $str);
		$str = str_replace('{{Descending}}'        , t('Descending') , $str);
		$str = str_replace('{{Random}}'            , t('Random')     , $str);
		$str = str_replace('{{ord_Ascending}}'     , ($this->sort=='asc' ?'selected':'') , $str);
		$str = str_replace('{{ord_Descending}}'    , ($this->sort=='desc'?'selected':'') , $str);
		$str = str_replace('{{ord_Random}}'        , ($this->sort=='rand'?'selected':'') , $str);

		if($this->filter)
		{
			$str = str_replace('{{filterform}}'      , '' , $str);
			$str = str_replace('{{/filterform}}'     , '' , $str);
			$str = str_replace('{{Filter}}'          , t('Filter'), $str);
			$str = str_replace('{{Source_of_filter}}', t('Source of filter'), $str);
			$str = str_replace('{{fil_Filename}}'    , ($this->filtersource=='filename'?'selected':''), $str);
			$str = str_replace('{{fil_Sub}}'         , ($this->filtersource=='sub' ?'selected':'')    , $str);
			$str = str_replace('{{fil_Date}}'        , ($this->filtersource=='date'?'selected':'')    , $str);
			$str = str_replace('{{fil_Votes}}'       , ($this->filtersource=='vote'?'selected':'')    , $str);
			$str = str_replace('{{fil_Views}}'       , ($this->filtersource=='view'?'selected':'')    , $str);
			$str = str_replace('{{filtertext}}'      , (!empty($this->filtertext)? $this->filtertext: '-'), $str );
			$str = str_replace('{{Filter_send}}'     , t('Filter send'), $str );
		} else{
			$str = preg_replace('%\{\{filterform\}\}.*?\{\{/filterform\}\}%simx','',$str);
		}
		return $str;
	}
	/*
	 * Chang order of images
	*/
	function setOrder(&$Pictures){
		$this->sortsource ='sub';
		$this->sort       ='asc';

		$sortsource = isset($_REQUEST['smpl_sortsource']) && !empty($_REQUEST['smpl_sortsource']) ? $_REQUEST['smpl_sortsource'] : '';
		$sort       = isset($_REQUEST['smpl_sort'])       && !empty($_REQUEST['smpl_sort'])       ? $_REQUEST['smpl_sort']       : '';


		if(empty($sortsource) || $sortsource == "-" ){
			if( isset( $_SESSION['smpl_sortsource'] ) )
				unset($_SESSION['smpl_sortsource']);
			if( isset( $_SESSION['smpl_sort'] ) )
				unset($_SESSION['smpl_sort']);
		}else{
			$this->sortsource = smpl_clean_input($sortsource);
			$_SESSION['smpl_sortsource'] = $this->sortsource;
		}

		if(!empty($sort)){
			$this->sort = smpl_clean_input($sort);
			$_SESSION['smplsort'] = $this->sort;
		}elseif( isset($_SESSION['smpl_sort']) ){
			$this->sort = $_SESSION['smpl_sort'];
		}	else{
			if( isset( $_SESSION['smpl_sort'] ) )
				unset($_SESSION['smpl_sort']);
		}

		$GLOBALS['smpl_sort'] = $this->sort;
		switch ($this->sort){
			case 'asc':
			case 'desc':
			case 'rand':
				uasort($Pictures,'smpl_cmp');
				break;
			default:
				break;
		}
	
	/*	foreach($Pictures AS $i => $e)
		{
		  fz_t($i.": ".$e->getEntry()." ".$e->getCaption()." ".$e->getSubtitle()." ".$e->getSize());
		}*/
	}

	/*
	 * Set filter of imagelist
	*/
	function setFilter(&$aPic)
	{
		$this->filtersource ='';
		$this->filtertext = '';

    $filtersource = isset($_REQUEST['smpl_filtersource']) && !empty($_REQUEST['smpl_filtersource']) ? $_REQUEST['smpl_filtersource'] :'';
    $filtertext   = isset($_REQUEST['smpl_filtertext'])   && !empty($_REQUEST['smpl_filtertext'])   ? $_REQUEST['smpl_filtertext']   :'';

		if(empty($filtersource) || $filtersource == '-')
		{
			if( isset( $_SESSION['smpl_filtersource'] ) )
				 unset($_SESSION['smpl_filtersource']);
			if( isset( $_SESSION['smpl_filtertext'] ) )
				unset($_SESSION['smpl_filtertext']);
			return false;
		}else{
			$this->filtersource = smpl_clean_input($filtersource);
			$this->filtertext   = $filtertext;
			$_SESSION['smpl_filtersource'] = $this->filtersource;
			$_SESSION['smpl_filtertext']   = $this->filtertext;
		}
		//There is not filtertext
		if ($filtertext =="-") return false;

		if($this->filtersource !='')
		{
			switch($this->filtersource){
				case 'filename':
					if(preg_match("/".$this->filtertext."/i", $aPic->getFilename() ) === 0 ){
						return true;
					}
					break;
				case 'sub':
					if(preg_match("/".$this->filtertext."/i",$aPic->getSubtitle() ) === 0){
						return true;
					}
					break;
				case 'date':
					if(preg_match("/".$this->filtertext."/i",$aPic->getDate()) === 0 ){
						return true;
					}
					break;
				case 'vote':
					if(preg_match("/<>/i",$this->filtertext) === 0 ){
						if(	$aPic->getVoted() != (float) ($this->filtertext) ){
							return true;
						}
					}elseif(preg_match("/>=/i",$this->filtertext) === 0 ){
							if($aPic->getVoted() >= (float) ($this->filtertext) ){
								return true;
							}
					}elseif(preg_match("/>/i",$this->filtertext) === 0 ){
							if($aPic->getVoted() > (float) ($this->filtertext) ){
								return true;
							}
					}else if(preg_match("/<=/i",$this->filtertext) === 0 ){
							if($aPic->getVoted() <= (float) ($this->filtertext) ){
								return true;
							}
					}else if(preg_match("/</i",$this->filtertext) === 0 ){
							if($aPic->getVoted() < (float) ($this->filtertext) ){
								return true;
							}
					}elseif ( ( preg_match("/=/i",$this->filtertext) === 0  ||
							      ( !preg_match("/[\<|\=|\>]/i",$this->filtertext ) === 0)  ) )
					{
							if($aPic->getVoted() == (float) ($this->filtertext) ){
								return true;
							}
					}
					break;

				case 'view':
					if(preg_match("/>=/i",$this->filtertext) === 1){
							$flt = str_replace(">=",'',$this->filtertext);
							if(!($aPic->getOpened() >= (int) ($flt) ) ){
								return true;
							}
					}elseif( preg_match("/>/i",$this->filtertext) === 1 ){
							$flt = str_replace(">",'',$this->filtertext);
							if(!($aPic->getOpened() > (int) ($flt) ) ){
								return true;
							}
					}elseif(preg_match("/<=/i",$this->filtertext) === 1){
							$flt = str_replace("<=",'',$this->filtertext);
							if(!($aPic->getOpened() >= (int) ($flt) ) ){
								return true;
							}
					}elseif(preg_match("/</",$this->filtertext) === 1 ){
							$flt = str_replace("<",'',$this->filtertext);
							if( !($aPic->getOpened() < (int) ($flt) ) ) {
								return true;
							}
					}elseif ( preg_match("/=/",$this->filtertext) === 1 ) {
							$flt = str_replace("=",'',$this->filtertext);
							if($aPic->getOpened() != (int) ($flt) ){
								return true;
							}
					}elseif(preg_match("/!=/i",$this->filtertext) === 1 ||
										 preg_match("/<>/i",$this->filtertext) === 1 )
					{
							$flt = str_replace("!=",'',$this->filtertext);
							$flt = str_replace("<>",'',$flt);
							if($aPic->getOpened() == (int) ($flt) ){
								return true;
							}
					}
					break;
				default:
			}
			return false;
		}
	}
	/**
	 * Checktypest:image, Video, Audio, Doc, app, compressed, disabled, docs, other
	 * @param unknown $entry
	 */
	function is_image($entry){
		return $this->in_type($entry, $this->ImageArray);
	}
	//compressed files
	function is_cmp($entry){
	    return $this->in_type($entry, $this->CmpArray);
	}
	// application extensions
	function is_app($entry){
	    return $this->in_type($entry, $this->AppArray);
	}
  
  //disabled extensions
	function is_dis($entry){
	    return $this->in_type($entry, $this->DisArray);
	}
	
	// documents extensions
	function is_doc($entry){
		return $this->in_type($entry, $this->DocArray);
	}
  //video extensions
	function is_video($entry){
		return $this->in_type($entry, $this->VideoArray);
	}
	//videohtml5 extensions
	function is_videohtml5($entry){
	  return $this->in_type($entry, $this->VideoHTML5Array);
	}
  //audio extensions
	function is_audio($entry){
		return $this->in_type($entry, $this->AudioArray);
	}
	//audio extensions
	function is_audiohtml5($entry){
	  return $this->in_type($entry, $this->AudioHTML5Array);
	}
	//other but enabled extensions
	function is_oth($entry){
	  return $this->in_type($entry, $this->OthArray);
	}
	
	/**
	 * Is in the types
	 * @param string $entry
	 * @param unknown $types
	 * @return boolean
	 */
	function in_type($entry,$types){
		$ext = pathinfo($entry, PATHINFO_EXTENSION);
		return in_array($ext,$types);
	}
	
  /**
   * type of entry
   * @param unknown $entry
   * @return string|boolean
   */
	function smpl_type($entry){
		if(     $this->is_image($entry))      $type = "image";
		elseif( $this->is_audiohtml5($entry)) $type = "audiohtml5";
		elseif( $this->is_audio($entry))      $type = "audio";
		elseif( $this->is_videohtml5($entry)) $type = "videohtml5";
		elseif( $this->is_video($entry))      $type = "video";
		elseif( $this->is_doc($entry)  )      $type = "doc";
		elseif( $this->is_cmp($entry)  )      $type = "cmp";
		elseif( $this->is_app($entry)  )      $type = "app";
		elseif( $this->is_oth($entry)  )      $type = "oth";
		else $type = false;
		return $type;
	}
	
	/**
	 * Add html head meta keywords
	 * @param unknown $tag
	 */
	function addMetaKeyword($tag){
	  $tag = str_replace(",'--##--'",'',$tag);
	  $tag = str_replace("'",'',$tag);
	  $tag = substr($tag,0,159);
	  
	  $page_keywords = array(
	      '#type' =>'html_tag',
	      '#tag'  => 'meta',
	      '#attributes' =>array(
	          'name' =>'keywords',
	          'content' => $tag
	      )
	  );
	  drupal_add_html_head($page_keywords,'page_keywords');
	}
	
	/**
	 * Add html head meta description
	 * @param string $tag
	 * @param string $og - OpenGraph description
	 */
	function addMetaDescription($tag='',$og = false){
	  $tag = str_replace(",'--##--'",'',$tag);
	  $tag = str_replace("'",'',$tag);
	  $tag = substr($tag,0,159);
	  
	  $page_description = array(
	      '#type'       =>'html_tag',
	      '#tag'        => 'meta',
	      '#attributes' =>
	         array(
	          'name'    => 'description',
	          'content' => $tag
	        )
	  );
	  drupal_add_html_head($page_description,'page_description');
	}
	function Help(){
	  $str = '<a id="smpl_help_link">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.t('Help').'</a>
    <div id="smpl_help" class="smpl_help"><div id="smpl_help_close"></div><div id="smpl_help_content"></div></div>';
	  return $str;
	}
}