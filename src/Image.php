<?php
namespace Drupal\smplphotoalbum;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class Image {
	const TN = '_tn_/';
	protected $ascdesc = 'asc'; // order: asc or desc
	protected $edit = false;	
	public 		$entry = '';      // The name of file
	protected $ext = "";     //Extension of item
	public 		$filesize = 0;    // size of file;	
	protected $html5 = false;
	protected $name = "";
	protected $importance = 0; // importance of item	
	protected $icon ='';
	protected $id ='0';      	// id of item
	protected $imgedit = false;
	protected $lang = "en";
	protected $link = "";    // link associated with image
	protected $modulepath = ''; // module path in filesystem
	protected $path ='';	   // relativ path to item
	protected $smplbox ='';  // it helps to colorbox or any other similar module
	protected $sortorder ='filename';	// type of order
	protected $sub = '';     // Shows the subtitle of pictures
	public 		$subtitle =''; // Actual subtitle below the image
	protected $root ='';     // The root of photoalbums
	protected $test = false;
	public 		$thdate = 0;      //date of thumbnail
	protected $tpl = [];     //template array
	protected $translate = false;
	public    $type ='';
	protected $url = false;
	protected $v = '';         // viever link with parameter	
	protected $viewed = false; // Shows the number of view?
	public    $viewnumber = 0; // View number
	protected $width;          // Width of items
	protected $words = [];     // translations
	protected $subfolder = ''; // Sub folder
	
	use StringTranslationTrait;
	
	// Constructor
	Function __construct(
		$id, 
		$subtitle, 
		$viewnumber, 
		$link, 
		&$params, 
		&$words, 
		$entry = '', 
		$importance = 0 , 
		$type = 'image', 
		$tpl = '', 
		$subfolder = ''
	) {
		global $base_url;

		$this->ascdesc    = $params ['ascdesc'];
		$this->edit       = $params ['edit'];
		$this->entry      = $entry;		
		$this->ext        = strtolower( pathinfo( $entry, PATHINFO_EXTENSION) );						
		$this->html5      = $params ['html5_checking'];
		$this->icon       = $params ['icon'];
		$this->id         = $id;
		$this->imgedit    = $params["imgedit"];
		$this->importance = (int) ($importance);
		$this->lang       = $params["lang"];
		$this->link 			= $link;
		$this->modulepath = $params ['modulepath'];
		$this->name 			= $entry;
		$this->viewnumber = $viewnumber;
		$this->root 			= $params ['root'];			// root of photoalbums
		$this->path 			= $params ['path'];		  // folder from the root of photoalbum
		$this->subfolder  = $subfolder;						// subfolder
		$this->smplbox 		= $params ['smplbox'];	// It helps to vie an image in a lightbox or colorbox layer
		$this->sortorder 	= $params ['sortorder' ];		
		$this->sub 				= $params ['sub'];
		
		$this->subtitle   = $subtitle;								
		$this->test 			= $params ["test"];
		$p = $this->slash($this->getRoot($this->root ) . $this->path . $this->subfolder . $entry);
		$this->thdate 		= @filemtime ( $p );
		$this->filesize   = @filesize ( $p );
		$this->tpl 				= $tpl;
		$this->translate 	= isset($params["translate"] )? $params['translate']: false;
		$this->type 			= $type;
		$this->url 				= $params ['url_checking'];
		$this->v 					= $base_url . "/";
		$this->viewed 		= $params ['viewed'];		
		$this->width 			= $params ['width'];		
		$this->words 			= $words;		
	}
	
	/**
	 * Render an Item 
	 *
	 * @param bool $editok
	 * @return string
	 */
	function Render( $editok ) {
		switch ($this->type) {
			case 'image'      : $str = $this->RenderImage(); break;			
			case 'videohtml5' : $str = ($this->html5)? $this->RenderHTML5Video() : $this->RenderOther(); break;
			case 'audiohtml5' : $str = ( $this->html5 )? $this->RenderHTML5Audio(): $this->RenderOther();	break;
			case 'folder'     : $str = $this->RenderFolder();	break;
			case 'video'      : 
			case 'audio'     	:
			default : $str = $this->RenderOther();
		}
	
		$str = str_replace( "{{ icon }}", $this->icon, $str );
		if ( $this->sub ) {
			$str = str_replace( '{{ subtitle }}', ( empty ( $this->subtitle ) ? $this->name : $this->subtitle ), $str );
		}

		// View number of views of item
		$this->ViewNumber( $str );
	
		// Show link of item
		$this->ShowLink( $str );

		// The file description
		$this->ShowProperties( $str );

		// ImgEdit button
		if( $editok && $this->edit && $this->imgedit && $this->type == "image" && !in_array( $this->ext, ["avif", "xbm", "xpm"] ) )	{
			$this->ShowImageEdit( $str );
		}else{
			$this->NoImageEdit( $str );
		}

		// Edit && Delete button
		if ( $editok && $this->edit ) $this->ShowEdit( $str ) ;
		else													$this->NoEdit( $str );
		
		return str_replace( 
			[ 
				'{{ test }}', 
				"{{ div_style }}"	
			], 
			[ 
				( $this->test && strpos(" " . $this->name, "_smpl_testfile") ? 'smpl_test' :"" ) ,
				"border: 1px black solid;" 
			], 
			$str
		);		
	}
	
	/**
	 * Showlink
	 * @param string $str
	 */
	function ShowLink(string &$str) {
		if ($this->url && !empty( $this->link )) {
			$s = [
				 "<Link>",
				 "</Link>",
				 "{{ url }}", 
				 "{{ smpl_url_class }}",
				 "{{ Link }}"
			];
			$r = [
				"",
				"", 
				$this->link, 
				" smpl_url_show", 
				$this->words["Link"] 
			];
			$str = str_replace( $s, $r, $str );
		} else {
			$str = preg_replace('#<Link(.*?)Link>#imxs', "" , $str);
		}
	}
	
	/**
	 * Write view number and date of last view
	 * @param string $str
	 * @return string
	 */
	function ViewNumber(string &$str) {
		if ($this->viewed) {
			$str = str_replace( '{{ viewnumber }}', $this->t ( "View" ) . ": " . $this->viewnumber, $str );
		} else{
			$str = str_replace( '{{ viewnumber }}', '', $str );
			$str = preg_replace( '/<div id="smpl_view[^<]*<\/div>/msxi', "", $str );
		}
	}
	
	/**
	 * Shows the Properties of file
	 * @param string $str
	 * @return string
	 */
	function ShowProperties(string &$str) {
		$s = [
				"{{ ShowProperties }}",
				"{{ desc }}",
				"{{ Last modified }}",
				"{{ lastmodified }}",
				"{{ FileSize }}",
				"{{ filesize }}"
		];
		$r = [
				$this->words['Properties' ],
				"$this->entry => '$this->subtitle'",
				$this->words["Last modified" ],
				date( 'Y.m.d', $this->thdate ),
				$this->words["File size"],
				$this->ShowFileSize()
		];
		$str = str_replace( $s, $r, $str );
	}

/**
	 * Show Image Edit buttons
	 * @param string $str
	 * @return string
	 */
	function ShowImageEdit( string &$str) {
		$str = str_replace( 
			[	'<ImgEdit>',
				'</ImgEdit>', 
				'{{ Image Edit }}'
			], 
			[	'',
				'', 
				$this->words['Image Edit']
			], $str );		
	}

	/**
	 * Delete Image Edit buttons from template of items
	 * @param string $str
	 * @return string
	 */	
	function NoImageEdit(string &$str) {
		$str = preg_replace("#<ImgEdit(.*?)<\/ImgEdit>#imxs", '', $str);
	}

	/**
	 * Show Edit buttons
	 * @param string $str
	 */
	function ShowEdit( string &$str) {		
		$str = str_ireplace( [ '<Edit>','</Edit>' ], '', $str );			
		$str = str_replace( 
			[ '{{ Caption Edit }}', '{{ Delete }}' ], 
			[ $this->words['Caption Edit'], $this->words['Delete'] ], $str );		
	}

	/**
	 * Delete Edit && Delete buttons from template of items
	 * @param string &$str
	 */	
	function NoEdit(string &$str){
		$str = preg_replace("#<Edit(.*?)<\/Edit>#imxs", '', $str);
	}

	/**
	 * Get file size
	 * @return string
	 */
	function ShowFileSize() {
		$size = ( int ) $this->filesize;
		if ($size > 1073741824) {
			$s = ( int ) ( $size / 1073741824 ) . '&nbsp;Gb';
		} else if ( $size > 1048576 ) {
			$s = ( int ) ( $size / 1048576 ) . '&nbsp;Mb';
		} else if ( $size > 1024)  {
			$s = ( int ) ( $size / 1024 ) . '&nbsp;Kb';
		} else {
			$s = $size . '&nbsp;b';
		}
		return $s;
	}
	
	/**
	 * Render image media
	 *
	 * @return string
	 */
	function RenderImage() {
		$link = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . $this->subfolder. "&n=" . $this->name;
		$linktn = $link . "&tn=1";
		// template
		$s = [
				"{{ id }}",
				"{{ link }}",
				"{{ title }}",
				"{{ linktn }}",
				"{{ smplbox }}",
				"{{ importance }}"
		];
		$r = [
				$this->id,
				$link, 
				$this->name,
				$linktn,
				$this->smplbox,
				$this->Importance()
		];
		
		$str = $this->tpl;
		return str_replace( $s, $r, $str );
	}
	/**
	 * 
	 */
	function RenderFolder() {
		// href to file
		$href = $this->Request("REQUEST_URI", '','SERVER');
		$href = preg_replace("#subfolder=(.*?)(&|$)#imxs","", $href );
		$href = preg_replace("#upfolder=1#imxs","", $href);	
		$href = preg_replace("#(&+)#","&", $href);

		$add = str_contains( $href, "?") ? "&":"?";
		
		if($this->name == ".."){				
			$href .= $add . "upfolder=1";
		}else{				
			$href .= $add . "subfolder=$this->name";
		}
		$href = str_replace( "?&", "?", $href);
		$href = str_replace( "&&", "&", $href);
    
		$p = $this->slash($this->path.$this->subfolder);
		$linktn = $this->v . "smplphotoalbum/v/$this->id?p=$p&n=$this->name&tn=folder";		

		// max width
		$style = ($this->width == "" ? "" : "max-width:" . $this->width . "px;");
		
		$str = $this->tpl;

		if($this->name == ".."){
			$str = preg_replace ( "#<Edit(.*?)<\/Edit>#imxs","", $str );
			$this->subtitle = '';
		}

		$s = [
				"{{ id }}",
				"{{ title }}",
				"{{ href }}",
				"{{ linktn }}",
				"{{ subtitle }}",
				"{{ style }}",
		];
		$r = [
				$this->id,
				$this->name,
				$href,
				$linktn,
				$this->subtitle,
				$style,
		];
		return str_replace( $s, $r, $str );
	}
	
	/**
	 * Render a non image and video item
	 *
	 * @return string
	 */
	function RenderOther() {
		// href to file
		$href = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . $this->subfolder . "&n=" . $this->name;
		$linktn = $href .".png". "&tn=1";
		
		// max width
		$style = ($this->width == "" ? "" : "max-width:" . $this->width . "px;");
		$s = [
				"{{ id }}",
				"{{ title }}",
				"{{ href }}",
				"{{ linktn }}",
				"{{ subtitle }}",
				"{{ style }}",
				"{{ importance }}"
		];
		$r = [
				$this->id,
				$this->name,
				$href,
				$linktn,
				$this->subtitle ,
				$style,
				$this->Importance()
		];
		$str = $this->tpl;
		return str_replace( $s, $r, $str );
	}

	/**
	 * HTML5 Video render
	 * 
	 * @return string
	 */
	function RenderHTML5Video() {  
	  $mime = $this->getMimeType( $this->getRoot($this->root) . $this->path . $this->subfolder . $this->name );
		$src  = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . $this->subfolder . "&n=" . $this->name;
		$st   = ($this->width == "" ? "" : "width:" . $this->width . "px;");
		$w    = ($this->width == "" ? "" : "max-width:" . $this->width . "px;");
		$sty  = "$w $st";
		
		$str = $this->tpl;
		$s = [
			'{{ id }}',
			'{{ mime }}',
			'{{ src }}',
			'{{ style }}',
			'{{ class }}',
			'{{ importance }}'
		];
		$r = [
				$this->id ,
				$mime,
				$src,
				$sty,
				"",
				$this->Importance()
		];
		return str_replace( $s, $r, $str );
	}
	
	/**
	 * Render HTML5 Audio
	 *
	 * @return string
	 */
	function RenderHTML5Audio() {
	  $mime = $this->getMimeType( $this->getRoot($this->root) . $this->path . $this->subfolder . $this->name );
		$src  = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . $this->subfolder . "&n=" . $this->name;
		$sty  = ($this->width == "" ? "" : "width:" . $this->width . "px;");
		$s = [
				"{{ id }}",
				"{{ src }}",
				"{{ mime }}",
				"{{ style }}",
				"{{ title }}" ,
				"{{ class }}",
				"{{ attrib }}",
				'{{ importance }}'
		];
		
		$r = [
				$this->id ,
				$src,
				$mime,
				$sty,
				$this->subtitle,
				"",
				"",
				$this->test && strpos(" " . $this->name, "_smpl_testfile") ? 'smpl_test' :"",
				$this->Importance()
		];
		$str = $this->tpl;
		return str_replace( $s, $r, $str );
	}

	/**
	 * Border color depends of importance
	 */
	function Importance(){		
		$str = '';
		if( $this->importance > 0 ){					
			$grey  = hexdec("D1");
			$red   = $grey + (int)( ( 255-$grey ) * ( $this->importance /255 ) );
			$green = $blue = (int)( $grey *(1 - $this->importance/255));

			$str = ' style="border: 2px solid rgb('.$red.','.$green.','.$blue.');"';
		}
		return $str;
	}

	/**
	 * it makes slash from backslash or double slash
	 * @param mixed $p 
	 * @return string|string[] 
	 */
	public function slash($p){
		return str_replace(["\\","//"],'/',$p);
	}

	/**
	 * View number - Call from ImageList
	 * @return int
	 */
	public function getOpened() {
	  return ( int ) ($this->viewnumber);
	}
	 /**
   * Get the file ID - Call From ImageList
	 * @return number
   */
  public function getID() {
    return $this->id;
  }
	/**
   * Get Subtitle - Call From ImageList
	 * 
	 * @return number 
   */
  public function getSubtitle() {
    return $this->subtitle;
  }
/**
 * Get entry - call from ImageList
 * @return string
 */
	public function getEntry() {
    return $this->entry;
  }
  
  /**
   * Get hte abolute path of root
   * @param string $root
   * @return mixed
   */
  function getRoot(string $root){
    $root = str_replace ( "public://", \Drupal::service( 'file_system' )->realpath( "public://" )."\\", $root);
    $root .= substr( $root, -1 ) != '/' ? "/" : '';
    $root = str_replace("\\","/", $root);
    return $root;
  }
  
  /**
   * Get mime type
	 * @param string $p
	 * 
	 * @return string
   */
  public function getMimeType($p){
    $mime = mime_content_type ( $p);    
    if( empty($mime)){
			require_once realpath(__DIR__."/../vendor/james-heinrich/getid3/getid3/getid3.php");
	  	$GetID3 = new \GetID3();
      $info = $GetID3->analyze( $p );   
      $mime = $info['mime_type'];
    }
    return $mime;
  }	

  /**
   * Request POST/GET/SERVER parameters from Browser
   *
   * @param mixed $key
   * @return string
   */
/**
   * Request POST/GET/SERVER parameters from Browser
   *
   * @param mixed $key
   * @return string
   */
  public function Request( string $key, $default = '', $mode = "GET" ){
		$mode = strtoupper($mode);
		if( $mode == "GET" ){
			return \Drupal::request()->get($key, $default );
		} else if( $mode == "SERVER" ){	//SERVER Variable
			return \Drupal::request()->server->get($key);
		}
		return \Drupal::request()->request->get($key, $default);
  }

}