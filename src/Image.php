<?php

namespace Drupal\smplphotoalbum;

class Image {
  protected $root; // The root of photoalbums
  protected $folder; // This photoalbum relative folder
  public $entry; // The name of file
  public $subtitle; // Actual subtitle below the image
  protected $sub = ''; // Shows the subtitle of pictures
  protected $capt; // Shows the caption
  protected $smplbox; // it helps to colorbox or any other similar module
  protected $width;
  protected $sortorder;
  protected $ascdesc;
  protected $viewed = false; // Shows the number of view?
  public $viewnumber = 0; // View number
  protected $edit = false;
  protected $lazy; // Lazy loading
  protected $exif = false;
  protected $private; // Using private filesystem
  protected $imagesize = array ();
  protected $ImageID = ''; // Image key
  public $type;
  public $filesize; // size of image;
  protected $simplelist = false; // Simple filelist
  protected $thumb; // Where is the thumbnail in database or in filesystem?
  protected $image; // The binary data of thumbnail
  protected $modulepath; // module path in filesystem
  protected $v; // viever link with parameter
  protected $getpath; // Module path from net
  public $thdate = 0;
  protected $url = false;
  protected $link = ""; // link associated with image
  protected $tpl;
  protected $types;
  protected $params;
  protected $id;
  
  // Constructor
  Function __construct($id, $Props, &$params, $entry = '', $type = 'image', $tpl) {
    global $base_url;
    $this->id = $id;
    // Data from database
    $this->viewnumber = $Props ['viewnumber'];
    $this->link       = $Props ['link'];
    $this->type       = $type;
    $this->entry      = $entry;
    $this->subtitle   = $Props ['subtitle'];
    $this->name       = $entry;
    $this->sortorder  = $params ['sortorder'];
    $this->ascdesc    = $params ['ascdesc'];
    // data from config
    $this->root       = $params ['root'];
    $this->path       = $params ['path'];
    
    $this->filesize = @filesize ( $this->root . $this->path . $entry );
    $this->thdate  = @filemtime ( $this->root . $this->path . $entry );
    $this->width   = $params ['width'];
    $this->sub     = $params ['sub'];
    $this->smplbox = $params ['smplbox']; // It helps to vie an image in a lightbox or colorbox layer
    $this->viewed  = $params ['viewed'];
    $this->edit    = $params ['edit'];
    $this->exif    = $params ['exif'];
    $this->url     = $params ['url_checking'];
    $this->html5   = $params ['html5_checking'];
    $this->lazy    = $params ['lazy'];
    
    $this->modulepath = $params ['modulepath'];
    $this->private    = $params ['private'];
    $this->v = $base_url . "/"; 
    $this->tpl = $tpl;
    $path_parts = pathinfo ( $entry );
    $this->ext = (isset ( $path_parts ['extension'] )) ? strtolower ( $path_parts ['extension'] ) : "";
  }
  
  /**
   * Render a picture and its datas
   * 
   * @param bool $uideditok          
   * @param string $a          
   * @param string $b          
   */
  function Render($uideditok) {
    switch ($this->type) {
      case 'image' :
        $str = $this->RenderImage();
        break;
      case 'videohtml5' :
        if ($this->html5)
          $str = $this->RenderHTML5Video ();
        break;
      case 'audiohtml5' :
        if ($this->html5)
          $str = $this->RenderHTML5Audio ();
        break;
      default :
        $str = $this->RenderOther ();
    }
    
    if ($this->sub) {
      $str = str_replace ( '{{ subtitle }}', (empty ( $this->subtitle ) ? $this->name : $this->subtitle), $str );
    }
    // View number of views of item
    $str = $this->ViewNumber ( $str );
    // Show link of item
    $str = $this->ShowLink ( $str );
    // The file description
    $str = $this->ShowDescription ( $str );
    
    if ($uideditok && $this->edit) {
      $str = $this->ShowEdit ( $str );
    } else {
      $str = $this->NoEdit ( $str );
    }
    
    $str = str_replace ( "{{ div_style }}", "border: 1px black solid;", $str );
    return $str;
  }
  
  /**
   * Showlink
   * 
   * @return mixed
   */
  function ShowLink($str) {
    if ($this->url && ! empty ( $this->link )) {
      $s = array ("{{ url }}","{{ smpl_url_class }}","{{ Link }}");
      $r = array ($this->link," smpl_url_show",t ( "Link" ));
    } else {
      $s = array ("{{ url }}","{{ smpl_url_class }}","{{ Link }}");
      $r = array ( ""," smpl_url_hide",t ( "Link" ));
    }
    return str_replace ( $s, $r, $str );
  }
  
  /**
   * Write view number and date of last view
   * 
   * @return mixed
   */
  function ViewNumber($str) {
    if ($this->viewed) {
      return str_replace('{{ viewnumber }}',t( "View" ).": " . $this->viewnumber,$str );
    }
    $str = str_replace( '{{ viewnumber }}', '', $str );
    return preg_replace( '/<div id="smpl_view[^<]*<\/div>/msxi', "", $str );
  }
  
  /**
   * Shows the Descriptions of file
   * 
   * @return mixed
   */
  function ShowDescription($str) {
    $s = array (
        "{{ ShowDescription }}", "{{ desc }}",
        "{{ LastModified }}", "{{ lastmodified }}",
        "{{ FileSize }}", "{{ filesize }}" 
    );
    $r = array (
        t( 'Description' ), $this->subtitle,
        t( "Last" )." ".t( "Modified" ), $this->getDate (),
        t( "File" )." ".t( 'size' ), $this->ShowFileSize () 
    );
    
    $str = str_replace ( $s, $r, $str );
    return $str;
  }
  /**
   * Show Edit buttons
   * 
   * @return string
   */
  function ShowEdit($str) {    
    if ($this->type == "image") {
      $str = str_replace ( '<!--', '', $str );
      $str = str_replace ( '-->', '', $str );
      $str = str_replace ( '{{ Image }}', t ( 'Image' ), $str );
    } else {
      $str = preg_replace ( '/<!--.*-->/', '', $str );
    }
    $str = str_replace ( '{{ Edit }}', t ( 'Edit' ), $str );
    $str = str_replace ( '{{ Delete }}', t ( 'Delete' ), $str );
    $str = str_replace ( '{{ Kill }}', t ( 'Trash' ), $str );
    return $str;
  }
  /**
   * Delete Edit buttons from template of images
   * 
   * @param unknown $str          
   */
  function NoEdit($str) {
    $str = preg_replace ( '/<button[^<]*<\/button>/msxi', "", $str );
    return $str;
  }
  /*
   * Show filesize
   */
  function ShowFileSize() {
    $size = ( int ) ($this->getSize ());
    if ($size > 1073741824) {
      $s = ( int ) ($size / 1073741824) . t ( '&nbsp;Gb' );
    } else if ($size > 1048576) {
      $s = ( int ) ($size / 1048576) . t ( '&nbsp;Mb' );
    } else if ($size > 1024) {
      $s = ( int ) ($size / 1024) . t ( '&nbsp;Kb' );
    } else {
      $s = $size . t ( '&nbsp;b' );
    }
    return $s;
  }
  
  /**
   * Render image media
   * 
   * @return string
   */
  function RenderImage() {    
    $link = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . "&n=" . $this->name;
    $linktn = $link."&tn=1";
    // template
    $s = array( "{{ id }}", "{{ link }}", "{{ title }}", "{{ linktn }}", "{{ smplbox }}", "{{ lazy }}" );
    $r = array( $this->id, $link, $this->name, $linktn, $this->smplbox, $this->lazy ? " loading='lazy' " : "" );
    $str = $this->tpl;    
    $str = str_replace ( $s, $r, $str );
    return $str;
  }
  
  /**
   * Render a non image and video item
   * 
   * @return str - string
   */
  function RenderOther() {
    // title
    $title = $this->name;
    
    // href to file
    $href = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . "&n=" . $this->name;
    $linktn = $href."&tn=1";

    // max width
    $style = ($this->width == "" ? "" : "max-width:" . $this->width . "px;");
    $s = array ("{{ id }}","{{ title }}","{{ href }}","{{ linktn }}","{{ style }}","{{ lazy }}");
    $r = array ($this->id, $title, $href, $linktn, $style, $this->lazy ? " loading='lazy' " : "" );
    $str = $this->tpl;
    $str = str_replace ( $s, $r, $str );
    return $str;
  }
  /**
   * HTML5 Video render
   */
  function RenderHTML5Video() {
    $mime = mime_content_type ( $this->root . $this->path . $this->name );    
    $src = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . "&n=" . $this->name;    
    $st = ($this->width == "" ? "" : "width:" . $this->width . "px;");
    $w = ($this->width == "" ? "" : "max-width:" . $this->width . "px;");
    $sty = "$w $st";
    
    $str = $this->tpl;
    $s = array ('{{ id }}','{{ mime }}','{{ src }}','{{ style }}','{{ class }}');
    $r = array ($this->id,$mime,$src,$sty,"" );
    $str = str_replace ( $s, $r, $str );
    return $str;
  }
  
  /**
   * Render HTML5 Audio
   * 
   * @return mixed
   */
  function RenderHTML5Audio() {
    $mime = mime_content_type ( $this->root . $this->path . $this->name );    
    $src = $this->v . "smplphotoalbum/v/" . $this->id . "?p=" . $this->path . "&n=" . $this->name;
    $style = ($this->width == "" ? "" : "width:" . $this->width . "px;");
    $s = array ("{{ id }}", "{{ src }}", "{{ mime }}", "{{ style }}", "{{ class }}", "{{ attrib }}");
    $r = array ($this->id,$src,$mime,$style,"","");
    $str = $this->tpl;
    $str = str_replace ( $s, $r, $str );
    return $str;
  }
  
  /*
   * Is deleted the picture?
   */
  function GetDeleted() {
    return $this->deleted;
  }
  function getFilename() {
    return $this->entry;
  }
  function getEntry() {
    return $this->entry;
  }
  function getDate() {
    return date ( 'Y.m.d', $this->thdate );
  }
  
  /**
   * Get number of vievs or downloads
   * 
   * @return number
   */
  function getOpened() {
    return ( int ) ($this->viewnumber);
  }
  
  /**
   * Get filesize
   */
  function getSize() {
    return $this->filesize;
  }
  
  /**
   * Get Subtitle
   */
  function getSubtitle() {
    return $this->subtitle;
  }
  
  /**
   * Get subtitle
   * 
   * @return string|unknown
   */
  function getSub() {
    return $this->sub;
  }
  
  /**
   * Get the file ID
   */
  function getID() {
    return $this->id;
  }
  /*
   * give back the time of last event
   */
}