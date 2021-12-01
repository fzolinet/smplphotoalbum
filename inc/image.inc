<?php
class Image {
	protected $root;			  //The root of photoalbums
	protected $folder;			//This photoalbum relative folder
	protected $entry;				//The name of file
	protected $entry_old;		//Not UTF-8 compatible name
	protected $subtitle;   	//Actual subtitle below the image
	protected $sub='';      //Shows the subtitle of pictures
	protected $capt;        //Shows the caption
	protected $caption;			//The Caption: When mouse over the picture the caption up in little box.
	protected $width;
	protected $voted      = false;    // Shows the number of voting
	protected $votenumber = 0;        // Vote number
	protected $viewed     = false;    // Shows the number of view?
	protected $viewnumber = 0;        // View number
	protected $rank       = 0;        // Rank;
	protected $vrank      = 0.0;
	protected $edit       = false;
	protected $exif       = false;
	protected $fb         = 0;   // FB enable
	protected $googlep    = 0;   // Google+ enabled
	protected $twitter    = 0;   // Twitter enabled
		
	protected $imagesize  = array();
	protected $ImageID = '';      //Image key
	protected $type;
	protected $datetime;
	protected $filesize;				   //size of image;
	protected $simplelist = false; //Simple filelist
	protected $thumb;						   // Where is the thumbnail in database or in filesystem?
	protected $image;						   //The binary data of thumbnail
	protected $isvideo;
	protected $isaudio;
	protected $modulepath;				// module path in filesystem
	protected $v;                 // viever link with parameter
	protected $getpath;						// Module path from net
	protected $thdate = 0;
	//protected $link="";
	protected $url ="";			      // link associated with image
	protected $target ="_blank";  // target associated with image_url
	protected $tpl;
	protected $taxonomy;
	protected $types;
	protected $params;
	protected $id;
	protected $deleted;
	protected $mobile=false;

	// Constructor
	Function __construct($id, $Props, &$params, $entry='', $type='image', &$tpl){
	  $this->id = $id;
		
		$this->viewnumber = $Props['viewnumber'];
		$this->votenumber = $Props['votenumber'];
		$this->rank       = $Props['rank'];
		$this->vrank      = $Props['vrank'];
		$this->subtitle   = $Props['subtitle'];
		$this->caption    = $Props['caption'];
		$this->deleted    = $Props['deleted'];
		$this->filesize   = $Props['filesize'];
		$this->thdate     = $Props['thdate'];
		
		$this->url        = $Props['url'];
		$this->target     = $Props['target'];
		$this->root       = $params['root'];
		$this->path       = $params['path'];
		$this->width      = $params['width'];
		$this->sub        = $params['sub'];
		$this->capt       = $params['capt'];
		$this->voted      = $params['voted'];
		$this->viewed     = $params['viewed'];
		$this->edit       = $params['edit'];
		$this->exif       = $params['exif'];
		$this->style      = $params['style'];
		$this->fb         = $params['fb'] && $Props['fb'];
		$this->googlep    = $params['googlep'];
		$this->twitter    = $params['twitter'];
		
		$this->taxonomy   = $params['taxonomy'];
		$this->types      = $params['types'];
		$this->html5      = $params['html5'];
		
		$this->entry      = $entry;
		$this->name       = $entry;
		
		$this->type       = $type;
		$this->modulepath = $params['modulepath'];
		$this->v          = $params['v'];
		$this->simplelist = isset($_POST['smpllist']) ? true : false;
		$this->tpl        = $tpl;

		$path_parts       = pathinfo($entry);
		$this->ext        = (isset($path_parts['extension']))? strtolower($path_parts['extension']):"";
		$this->mobile     = $params["mobile"];
	}
	
  /**
	 * Render a picture and its datas
   * @param bool $uideditok
	 * @param string $a
	 * @param string $b
	 */
	function Render($uideditok, &$a, &$b){
	  if ($this->type == 'image') {
			$a .= $this->RenderImage();
		}else if($this->html5 && $this->type == 'videohtml5'){
			$a .= $this->RenderHTML5Video();
			
		}else if($this->type == 'video'){
			$a .= $this->RenderVideo();
			
		}else if($this->html5 && $this->type == 'audiohtml5'){
		  $a .= $this->RenderHTML5Audio();
		  
		} else if($this->type == 'audio'){
		  $a .= $this->RenderAudio();
		  
		} else{
		  $a .= $this->RenderOthers();
		}
		
		if($this->sub)     
		    $b .= $this->ShowSubtitle();
    
		$b .= $this->ShowUrl();

    if($this->voted )	          
        $b .= $this->ShowVoteNumber();
		if($this->viewed)	          
		    $b .= $this->ShowViewNumber();
		
		$b .= $this->ShowDescription();
		
		if($this->taxonomy)	          
		    $b .= $this->ShowTaxonomy();
		if($uideditok && $this->edit) 
		    $b .= $this->ShowEdit();		
		if($this->fb && $this->type == 'image')
        $b .= $this->ShowFB();
	}

	/**
	 * Render am item and datas of items
	 * @param bool $uideditok
	 * @param int $no
	 * @return string
	 */
	function RowRender($uideditok,$no){
		global $user;
		$str  = '<td class="smpl_td">'.($no+1).'.</td>';
		$str .= '<td class="smpl_td">';

		if ($this->type == 'image') {
			$str = $this->RenderImage();
		}else if($this->type == 'html5video'){
		  $str = $this->RenderHTML5Video();
		  
		}else if($this->type == 'video'){
			$str = $this->RenderVideo();
			
		}else if($this->type == 'html5audio'){
		  $str = $this->RenderHTML5Audio();
		  
		} else if($this->type == 'audio'){
		  $str = $this->RenderAudio();
		  
		} else{
		  $str = $this->RenderOthers();
		  
		}

		$str .= '</td>';
		//smpl_test($this->params['url']);
		if($this->subtitle) $str .= '<td class="smpl_td">'.$this->ShowSubtitle(true).'</td>';
		if($this->url) 			$str .= '<td class="smpl_td">'.$this->ShowUrl(true).'</td>';
		if($this->voted)	  $str .= '<td class="smpl_td">'.$this->ShowVoteNumber(true).'</td>';
		if($this->viewed)		$str .= '<td class="smpl_td">'.$this->ShowViewNumber(true).'</td>';
		if($this->exif){
			$str .= '<td class="smpl_td">'. $this->ShowFileSize(true).'</td>';
			$str .= '<td class="smpl_td">'. $this->ShowDate(true).'</td>';
		}
		if($uideditok && $this->edit)			$str .= '<td class="smpl_td">'.$this->ShowEdit().'</td>';
		return $str;
	}
	
  /**
   * Render an image in a row of simple table
   * @param unknown $uideditok
   * @param unknown $i
   * @param unknown $header
   */
	function Rowrender1($uideditok,$i, &$header){
	  $name = '';
		$row[]=($this->id).".";
		
		if ($this->type == 'image') {
			$row[] = $this->RenderImage();
			
		}else if($this->type == 'html5video'){
		 $row[] = $this->RenderHTML5Video();
		 
		}else if($this->type == 'video'){
		  $row[] = $this->RenderVideo();
		  
		}else if($this->type == 'html5audio'){
		  $row[] = $this->RenderHTML5Audio();
		  
		} else if($this->type == 'audio'){
		  $row[] = $this->RenderAudio();
		  
		} else{
		  $row[] = $this->RenderOthers();
		  
		}

		//smpl_test($this->params['url']);
		if($this->sub){
			$row[]= $this->ShowSubtitle(true);
		}

		$row[]= $this->ShowUrl(true);

		if($this->voted){
			$row[]= $this->ShowVoteNumber(true);
		}
		if($this->viewed){
			$row[]= $this->ShowViewNumber(true);
		}
		
		if($this->exif){
			$row[]=   $this->ShowFileSize(true);
			$row[]=   $this->ShowDate(true);
			//	$str .= $this->ShowPropInfo();
		}
		if($uideditok && $this->edit){
			$row[]=   $this->ShowEdit();
		}
		return $row;
	}

	/**
	 * Show subtitle from database
	 * @return mixed
	 */
	function ShowSubtitle(){
		$str = $this->tpl["sub"];
		$str = str_replace('{{id}}',$this->id,$str);
		$str = str_replace('{{smpl_sub}}',(empty($this->subtitle)? $this->name : $this->subtitle ),$str);
		return $str;
	}

	/**
	 * Showlink
	 * @return mixed
	 */
	function ShowUrl(){
		$str = $this->tpl['url'];
		$str = str_replace('{{id}}'   , $this->id, $str);
		$str = str_replace('{{image_url}}', $this->url   , $str);
		$str = str_replace('{{target}}'   , $this->target, $str);
		$str = str_replace('{{Link}}'     , t('Link')    , $str);
		$str = str_replace('{{visible}}'  , ( !empty($this->url)?'block':'none'),$str);
		return $str;
	}

	/**
	 * Write out voting number and last voting event
	 * @return mixed
	 */
	function ShowVoteNumber(){
		$str = $this->tpl["vote"];

    $str = str_replace('{{vote}}',t('Vote'),$str);
    
		if($this->votenumber>0){
			$v = sprintf("%02.2f",$this->rank / $this->votenumber). " / ".$this->votenumber;
		}else{
			$v = t(' - ');
		}
		$str = str_replace('{{smpl_votenumber}}' , $v,$str);
		$str = str_replace('{{votelast}}'        , t("Last vote").": ".$this->getLastEvent("vote",True) ,$str);
		$str = str_replace('{{id}}'              , $this->id, $str);
		$str = str_replace('{{modulpath}}'       , $this->modulepath, $str);
		return $str;
	}

	/**
	 * Write view number and date of last view
	 * @return mixed
	 */
	function ShowViewNumber(){
		$str = $this->tpl["view"];
		$str = str_replace('{{viewnumber}}'      , t("View").": ".$this->viewnumber,$str);
		$str = str_replace('{{viewlast}}'        , t("Last view").": ".$this->getLastEvent("view",True) ,$str);
		$str = str_replace('{{id}}'              , $this->id, $str);
		return $str;
	}

	/**
	 * Social sharing metatag & FB picture
	 * @return string
	 */
	function ShowFB(){
		global $base_url;

		if( $this->fb) {
		  $link = $this->v.$this->path.$this->name;
			$og_image = array(
					'#type' => 'html_tag',
			    '#tag'  => 'meta',
					'#attributes' => array(
							"property" => "og:image",
							"content" => $link,
					),
			);
      // to write out the og:description meta tag
			$stored_head = drupal_add_html_head($og_image,'smpl_image_'.$this->id);
		}
		$str = $this->tpl["fb"];
		$str = str_replace('{{id}}' , $this->id, $str);
		$str = str_replace('{{fbshare}}', t('FB'), $str);
		return $str;
	}

	/**
	 * Show Edit buttons
	 * @return string
	 */
	function ShowEdit(){
		$str = $this->tpl["edit"];
		$str = str_replace('{{id}}', $this->id, $str);
		if($this->type=="image"){
		  $str = str_replace('<!--', '', $str);
		  $str = str_replace('-->', '', $str);
		  $str = str_replace('{{imgedit}}', t('Image').' '.t('edit'), $str);
		}else{
		  $str = preg_replace('/<!--.*-->/', '', $str);
		}
		$str = str_replace('{{edit}}'   , t('Caption').' / '.t('subscription').' '.t('edit')   , $str);
		$str = str_replace('{{delete}}' , t('Delete') , $str);
		$str = str_replace('{{trash}}'  , t('Trash')  , $str);
		return $str;
	}

	/*
	 * Show filesize
	 */
	function ShowFileSize($table = false){
	  $str = $this->tpl['fsize'];
		if($this->simplelist ){
		    $str = str_replace('{{size}}:'   , '', $str);
		}else{
		    $str = str_replace('{{size}}'   , t('Size'), $str);
		}

		$size = (int) ($this->getSize());
		if($size > 1073741824){
		  $s = (int) ($size / 1073741824 ).t('&nbsp;Gb');
	  }else if($size > 1048576 ) {
		  $s = (int) ($size / 1048576).t('&nbsp;Mb');
		}else if($size > 1024){
		  $s = (int)($size / 1024).t('&nbsp;Kb');
		}else{
		  $s = $size.t('&nbsp;b');
		}
		$str = str_replace('{{fsize}}'  , $s, $str);
		$str = str_replace('{{id}}' , $this->id, $str);
		return $str;
	}
	
	/*
	 * Show Date of file
	 */
	function ShowDate(){
	  if($this->simplelist){
	      $str  ="<div class='smpl_date'>\n";
	  }else{
	      $str  ="<div class='smpl_date'>".t("Last modified").":\n";
	  }
		$str .="  <div id='Date".$this->id."' class='smpl_date_text'>".$this->getDate()."</div>\n";
		$str .="</div>\n\n";
		return $str;
	}
/**
 * Shows the Descriptions of file
 * @return mixed
 */
	function ShowDescription(){
		$str = $this->tpl['desc'];
		
		$str = str_replace('{{ShowDescription}}', t('Description')             , $str);
		$str = str_replace('{{id}}'             , str_replace("'","",$this->id), $str);
		$str = str_replace('{{desc}}'           , $this->caption               , $str);
		$str = str_replace('{{LastModified}}'   , t('Last modified time')      , $str);
		$str = str_replace('{{lastmodified}}'   , $this->ShowDate()            , $str);
		$str = str_replace('{{FileSize}}'       , t('Size of file')            , $str);
		$str = str_replace('{{filesize}}'       , $this->ShowFileSize()        , $str);
		return $str;
	}
/**
 *  Show taxonomy
 * @return unknown
 */
	function ShowTaxonomy(){
	  global $base_path;
		$str = $this->tpl['taxonomy'];

		$name = variable_get('smpl_taxonomy_vocabulary_machine_name','smpl_vocabulary');
		$sql  = "SELECT v.name, v.description FROM {taxonomy_term_data} AS v ";
		$sql .= "INNER JOIN {smplphotoalbum_taxonomy} AS s ON v.tid = s.tid WHERE s.sid = :sid";
		$rs = db_query($sql, array(':sid' => $this->id));
		
		$links = '';
		$def   = $base_path.'smplphotoalbum/taxonomy/';
		while ($row = $rs->fetchAssoc()){
			$links .='<a href= "'.$def.$row['name'].'" title="'.$row['description'].'" >'.$row['name'].'</a>,&nbsp;';
		}

		$voc = taxonomy_vocabulary_machine_name_load($name);	//Load the vocabulary
		$str = str_replace('{{links}}',$links,$str);
		$sty = 'style="max-width:'.$this->width.'px;"';
		$str = str_replace('{{style}}',$sty, $str);
		return $str;
	}
	/**
	 * Check Thumbnail time
	 */
	function CheckThumbnail(){
		$thDate = filemtime ( $this->root.$this->path.TN.$this->entry);	//Time of Thumbnail
		$orDate = $this->thdate;			//Time of original file

		if($orDate > $thDate+24*60*60){						//The date of thumbnail

			if(!unlink($this->root.$this->path.TN.$this->entry)){
				drupal_set_message(t('Thumbnail can not delete: ') . $this->path.TN.$this->entry);
				return;
			}
			$msg = t('Update thumbnail: ');
			$this->MakeThumbnail($msg);					//Thumbnail updated
		}
	}

	/**
	 * Update thumbnail if the original image is changed
	 */
	function MakeThumbnail($msg = ''){
		global $user;
		$lehet = ($user->uid==1 ) or user_access('edit smplphotoalbum') or user_access('administer smplphotoalbum');
		$ok = false;

		if(is_dir($this->root.$this->path.TN)){
			if ($this->NewThumbnail()) {	//It was successfull
				if($msg ==''){
					$msg = t("New thumbnail: ");
				}
				drupal_set_message($msg . $this->path.TN.$this->entry);
				$ok = true;
			}else{
				if($lehet) drupal_set_message(t("Can not writeable this file: ") . $this->path.TN.$this->entry);
			}
		}else{
			if ($lehet) drupal_set_message(t("Folder of thumbnails is not writeable:") . $this->path.TN );
		}
		return $ok;
	}

	//Make new thumbnails from GIF, PNG or JPG | JPEG | BMP | WBMP
	function NewThumbnail() {
		//size of original big picture
		$size = GetImageSize($this->root.$this->path.$this->entry);
		$dx = $size[0];
		$dy = $size[1];

		$ok = false;
		$type = strtolower(pathinfo($this->entry,PATHINFO_EXTENSION));
		if($dx > $this->width ) {
			//Target image
			$dst_im = @ImageCreateTrueColor( $this->width, $this->width * $dy / $dx);
			$source = $this->root.$this->path.$this->entry;
			$target = $this->root.$this->path.TN.$this->entry;
			switch($type){
				case 'jpg':
				case 'jpeg':
					$im = ImageCreateFromJPEG ($source );
					$a  = Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width*$dy/$dx, $dx, $dy);
					$ok = @Imagejpeg ($dst_im, $target,80);
					break;
				case 'png':
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
					$im = @ImageCreateFromGif ($source );
					$a  = @Imagecopyresized ($dst_im, $im, 0, 0, 0, 0, $this->width, $this->width*$dy/$dx, $dx, $dy);
					$ok = @imagegif($dst_im, $target);
					break;
				default:
					$ok = true;
			}
		}
		return $ok;
	}
	/**
	 * RenderOther typeof media
	 * @return string
	 */
	function RenderImage(){
		$str  = $this->tpl["image"];
		$str  = str_replace('{{id}}'              , $this->id   , $str);

		//a tag href parameter
		$link = $this->v.$this->path.$this->name;
		$str  = str_replace ('{{imagelink}}', $link, $str);
		// Subtitle of image
		$str  = str_replace ('{{imgsub}}'   , (empty($this->caption)? $this->name : $this->caption ), $str);

		//Using overbrowser setting
		$overbrowser = variable_get('overbrowser',true);

		//Lightbox, colorbox vagy _blank target
		$att   = ' target="_blank" ';
		$class = '';
		if($overbrowser or $overbrowser == 1){
			if(variable_get('smpl_overbrowser_paging',true)){
				$rnd = $this->path;
			}else{
				$rnd = mt_rand(1,9999999);
			}
			
			$obtype= variable_get('smpl_overbrowser_type');
      if ($obtype == "cb"){
        $class=' colorbox';
      }else if($obtype == "lb"){
        $att = ' rel="lightbox['. $rnd .']"';
      }
		}
		
		$str = str_replace ('{{class}}', $class, $str);
		$str = str_replace ('{{attrib}}', $att, $str);

		//IMG tag make
		if(is_readable( $this->root . $this->path . TN . $this->name )){
		  $path = $this->v.$this->path. TN . $this->name;
		}else{
		  $path = $this->v.$this->path.$this->name;
		}
  	$str = str_replace('{{imgsrc}}', $path."&tn=1", $str);
		
		$st ="";
		if(!empty($this->style)){
		  $st =($this->fb) ? "border-color: blue;" :  "border-color: #AAAAAA";
		}
		
		//IMG tag style
		if(!$this->mobile){
		  $w   = ($this->width ==""? "" : "width:".$this->width."px;");
		  $w   = ($this->simplelist ? 'width: 88px;' : $w);
		  $sty = "$w $st;";
		  $str = str_replace('{{smpl_img}}',"smpl_img", $str);
		  $str = str_replace('{{sty}}'   , $sty , $str);
		}else{
		  $w   = ($this->simplelist ? 'width: 88px;' : "");
		  $sty = "$w $st";
		  $str = str_replace('{{smpl_img}}',"smpl_img_mobile", $str);
		  $str = str_replace('{{sty}}'   , $sty , $str);
		}
		return $str;
	}

/**
 * Render an non image item
 * @return str - string
 */
	function RenderVideo(){
		$str = $this->tpl["video"];
		$str = str_replace('{{id}}'             , $this->id                       , $str);
		// target="_blank"
		$str = str_replace ('{{attrib}}'        , '', $str);
		//Subtitle
		$str = str_replace ('{{imgsub}}'        , (empty($this->caption)? $this->name : $this->caption ), $str);
		
		//<a href="{{imagelink}}">
		$link   = $this->v.$this->path.$this->name;	//for social network
		$str    = str_replace ('{{imagelink}}', $link, $str);
		
		if(is_readable( $this->root . $this->path . TN . $this->name.".png" )){
		  $imgsrc = $this->v.$this->path. TN . $this->name.".png&tn=1";   //Ha van preview a filmhez
		}else{
      $imgsrc = $this->modulepath.'/image/'._smpl_icon($this->name, $this->type, $this->types);
		}
		$str    = str_replace ('{{imgsrc}}', $imgsrc, $str);

		$st ="";
		if(!empty($this->style)){
			$st = "border-color: #AAA;";
		}

		$w   = ($this->width ==""? "" : "max-width:".$this->width."px;");
		$sty = "$w $st";
		$str = str_replace('{{sty}}'    , $sty, $str);
		$str = str_replace ('{{class}}' , "",   $str);
		$str = str_replace ('{{attrib}}', "",   $str);
		return $str;
	}
	
	/**
	 * HTML5 Video render
	 */
	function RenderHTML5Video(){
	  $str = $this->tpl["videohtml5"];
	  $str = str_replace('{{id}}'             , $this->id                       , $str);
	  $link   = $this->v.$this->path.$this->name;	//for social network
	  $str    = str_replace ('{{videosrc}}', $link, $str);
	  $mime   = file_get_mimetype($this->name);
	  $str    = str_replace ('{{mime}}', $mime, $str);

	  //IMG tag style
	  $st   = ($this->width ==""? "" : "width:".$this->width."px;");
	  $st   = $this->simplelist ? 'width: 88px;':$st;
	  
	  $w   = ($this->width ==""? "" : "max-width:".$this->width."px;");
	  $sty = "$w $st";
	  
	  $str = str_replace('{{sty}}'    ,$sty, $str);
	  $str = str_replace ('{{class}}' ,  "", $str);
	  $str = str_replace ('{{attrib}}',  "", $str);
	  return $str;
	}
  /**
   * Render audio
   * @return mixed
   */
	function RenderAudio(){
	  $str = $this->tpl["audio"];
	  $str = str_replace('{{id}}'             , $this->id                       , $str);
	  // target="_blank"
	  $str = str_replace ('{{attrib}}'        , 'target="_blank"', $str);
	  //Subtitle
	  $str = str_replace ('{{imgsub}}'        , (empty($this->caption)? $this->name : $this->caption ), $str);
	  
	  //<a href="{{imagelink}}">
	  $link   = $this->v.$this->path.$this->name;	//for social network
	  $str    = str_replace ('{{imagelink}}', $link, $str);
	  
	  if(is_readable( $this->root . $this->path . TN . $this->name.".png" )){
	    $imgsrc = $this->v.$this->path. TN . $this->name.".png&tn=1";   //Ha van preview a filmhez
	  }else{
	    $imgsrc = $this->modulepath.'/image/'._smpl_icon($this->name, $this->type, $this->types);
	  }
	  $str    = str_replace ('{{imgsrc}}', $imgsrc, $str);
	  
	  $st ="";
	  if(!empty($this->style)){
	    $st = "border-color: #AAA;";
	  }
	  
	  $w   = ($this->width ==""? "" : "width:".$this->width."px;");
	  $sty = $w." ".$st.";";
	  $str = str_replace('{{sty}}'    ,$sty, $str);
	  $str = str_replace ('{{class}}' ,  "", $str);
	  $str = str_replace ('{{attrib}}',  "", $str);
	  return $str;
	}
	/**
	 * Render HTML5 Audio
	 * @return mixed
	 */
	function RenderHTML5Audio(){
	  $str = $this->tpl["audiohtml5"];
	  $str = str_replace('{{id}}'             , $this->id                       , $str);
	  $link   = $this->v.$this->path.$this->name;	//for social network
	  $str    = str_replace ('{{audiosrc}}', $link, $str);
	  $mime   = file_get_mimetype($this->name);
	  $str    = str_replace ('{{mime}}', $mime, $str);
	  //IMG tag style
	  $sty   = ($this->width ==""? "" : "width:".$this->width."px;");
	  if($this->simplelist){
	    $sty  = 'width: 88px;';
	  }
	   
	  $str = str_replace('{{sty}}'   , $sty , $str);
	  $str = str_replace ('{{class}}', "", $str);
	  $str = str_replace ('{{attrib}}', "", $str);
	  return $str;
	}
	/**
	 * Render a non image and video item
	 * @return str - string
	 */
	function RenderOthers(){
	  $str = $this->tpl["image"];
	  $str = str_replace('{{id}}'             , $this->id                       , $str);
	  // target="_blank"
	  $str = str_replace ('{{attrib}}'        , 'target="_blank"', $str);
	  //Subtitle
	  $str = str_replace ('{{imgsub}}'        , (empty($this->caption)? $this->name : $this->caption ), $str);
	
	  //<a href="{{imagelink}}">
	  $link   = $this->v.$this->path.$this->name;	//for social network
	  $str    = str_replace ('{{imagelink}}', $link, $str);
	
	  if(is_readable( $this->root . $this->path . TN . $this->name.".png" )){
	    $imgsrc = $this->v.$this->path. TN . $this->name.".png&tn=1";   //Ha van preview a filmhez
	  }else{
	    $imgsrc = $this->modulepath.'/image/'._smpl_icon($this->name, $this->type, $this->types);
	  }
	  $str    = str_replace ('{{imgsrc}}', $imgsrc, $str);
	
	  $st ="";
	  if(!empty($this->style)){
	    $st = "border-color: #AAA;";
	  }
	
	  $w   = ($this->width ==""? "" : "max-width:".$this->width."px;");
	  $sty = $w." ".$st.";";
	  $str = str_replace('{{sty}}'    , $sty , $str);
	  return $str;
	}
	/*
	 * Is deleted the picture?
	 */
	function GetDeleted(){
	  return $this->deleted;
	}
	/*
	 *
	 */
	function getFilename(){
	  return $this->entry;
	}
	
	function getEntry(){
	  return $this->entry;
	}
	function getDate(){
	  return date('Y.m.d',$this->thdate);
	}
	
 /**
  * Average of votes
  * @return number
  */
	function getVoted(){
	  return (float)($this->vrank);
	}
	/**
	 * Get rank
	 * @return number
	 */
	function getRank(){
	  return (float)($this->rank);
	}
	/**
	 * Get the number of votes
	 * @return number|unknown
	 */
	function getVotenumber(){
	  return $this->votenumber;
	}
	
	/**
	 * Get number of vievs or downloads
	 * @return number
	 */
	function getOpened(){
	  return (int) ($this->viewnumber);
	}
	/**
	 * Get date of last view
	 * @return The
	 */
	function getLastView(){
	  $out = t("unknown");
	  $last = $this->getLastEvent();
	  if ($last > 0){
	    $out = date("Y.m.d", $last);
	  }
	  return $out;
	}
	
	/**
	 * Date of last vote
	 * @return The
	 */
	function getLastVote(){
	  $out  = t("unknown");
	  $last = $this->getLastEvent("vote");
	  if ($last > 0){
	    $out = date("Y.m.d", $last);
	  }
	  return $out;
	}
	
	/**
	 * Get filesize
	 */
	function getSize(){
	  return  $this->filesize;
	}
	/**
	 * Get Caption
	 * @return unknown
	 */
	function getCaption(){
	   return $this->caption;
	}
	/**
	 * Get Subtitle
	 */
	function getSubtitle(){
	  return $this->subtitle;
	}
	
	/**
	 * Get subtitle
	 * @return string|unknown
	 */
	function getSub(){
	   return $this->sub;
	}
	
	/**
	 * Get the file ID
	 */
	function getID(){
	  return $this->id;
	}
  /*
   * give back the time of last event
   */
	function getLastEvent($event = "view",$date=false){
	  $out = t("Unknown");
    if(!db_table_exists("smplphotoalbum_event") )
      return '';
    $sql = "SELECT eventdate FROM {smplphotoalbum_event} WHERE sid = :id AND event = :event ORDER BY eventdate DESC";
    $rs = db_query($sql, array(':id'=> $this->id, ':event' => $event) );
    $db = (int)($rs->rowCount());
    if($db<1){
      return $out;
    }
    
    $record = $rs->fetchAssoc();
    if($date){
      $out = date('Y.m.d',(int)($record['eventdate']));
    } else{
      $out = (int)($record['eventdate']);
    }
    return $out;
	}

}