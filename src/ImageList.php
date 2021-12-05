<?php

namespace Drupal\smplphotoalbum;

use Drupal\Core\Database\Database;
use \Drupal\Core\File\FileSystemInterface;

require_once 'functions.php';
/**
 * @file
 * Main Images object
 */
class ImageList {
  protected $root;
  protected $number; // Length of a page
  protected $page = 0; // No. of actual page
  protected $pagenumber = 0; // number of all pages
  protected $firstimage = 0; // the first viewing item
  protected $numitems;
  protected $sumviews;
  protected $width;
  protected $subtitle;
  protected $caption;
  protected $order = false; // sorting the items
  protected $sortorder = 'filename'; // source of compare
  protected $ascdesc = "asc"; // sorting order ascending or descending
  protected $viewed; // is the view number of image on?
  protected $lazy; // Lazy loading enable / disable
  protected $stat; // statistics
  protected $private; // Using private file system
                      // the imtems send th Controller function
  protected $edit;
  protected $smplbox; // It helps to shos the image in a lightbox or colorbox
  protected $exif; // exif information of image
  protected $fb = 0;
  protected $googlep = 0;
  protected $twitter = 0;
  protected $ImgNumber = 0; // the number of showing item (there are hidden items)
  public $aPictures = array ();
  protected $audio;
  protected $video;
  protected $doc;
  protected $cmp;
  protected $app;
  protected $oth;
  protected $dis;
  protected $keywords; // SEO captions into the keeywords meta tag
  protected $modulepath; // path of module in filesystem
  protected $ImageArray = Array (
      'jpg',
      'jpeg',
      'JPG',
      "JPEG",
      'png',
      'bmp',
      'wbmp' 
  );
  protected $DocArray;
  protected $VideoArray;
  protected $AudioArray;
  protected $CmpArray;
  protected $AppArray;
  protected $OthArray;
  protected $DisArray;
  public $RSArray = Array (); // list of files in the actual folder
  protected $ImgProps = array (); // the image properties
  protected $tags = ''; // keywords and descriptions metatag
  protected $ffmpeg; // is there ffmpeg lib loaded?
  protected $con;
  protected $title ='';
  protected $user; // current user
  protected $tpl = array (); // array of templates
  protected $params;
  protected $words = array (
      "Edit", "Save", "Done", "Cancel", "Delete", "Rename", "Kill",
      "Subtitle", "Trash", "Undo", "Redo", "Left",
      "Right", "Size", "Thumbnail", "Rotate", "Nothing", "Crop",
      "Original", "cropped", "Resize", "Left top", "Right bottom",
      "Degree of rotate", "Flip", "Flip vertically", "Flip horizontally",
      "Original size", "Constrain aspect ratio", "pixels",
      "Color / Lightning", "Contrast", "Brightness", "Gamma",
      "RGB", "Input", "Output", "Detail", "Sharpen", "Denoise",
      "Smooth", "Emboss", "Gaussian blur", "Convolution", 'Grayscale',
      "viewnumber", "viewlast", "fsize", "exif", "Help",
      "Cache Clear", "Can not delete", "Name", "Value", "Properties", "Url",
      "Filename", "Close", "Ascending", "Descending", "Date", "Views", "Type",
      "Edit All", "Previous", "Next"
  );
  //Slideshow
  protected $slide_checking = False;
  protected $slide_extension = array();
  protected $slide = False;
  protected $slide_path = "";
  protected $slide_i    = 0;
  protected $slide_id   = 0;
  protected $slide_subtitle  = "";
  protected $slide_title = "";
  protected $interval = 5000;
  protected $style    = "none";
  
  /**
   * Make the list of objects of images
   * 
   * @param unknown $params          
   */
  function __construct(&$params) {
    $this->user       = \Drupal::currentUser();   
    $Pictures         = Array ();
    $this->params     = $params;
    $this->preSettings( $params );
    $root             = $params ['root'];
    $this->root       = $params ['root'];
    $path             = $params ['path'];
    $this->slide_path = $path;
    if(!empty($params['interval'])){
      $this->interval = $params['interval'];
    }
    
    if(!empty($params['style'])){
      $this->style = $params['style'];
    }
    
    //unset($_SESSION['slide'][$this->slide_path]);
    if($this->slide){
      $_SESSION['slide'][$this->slide_path]['i'] = 0;
    }

    // Make an Image object
    if (! is_dir ( $root . $path )) {
      \Drupal::messenger()->addMessage( t ( "This is not a folder: " ) . $root . $path . t ( '. Set the right folder in settings of Smplphotoalbum' ) );
    }
    
    $tempfolder = $root . $path . TN;
    $ok = \Drupal::service("file_system")->prepareDirectory ( $tempfolder, FileSystemInterface::CREATE_DIRECTORY|FileSystemInterface::MODIFY_PERMISSIONS );
    
    if (! $ok) {
      \Drupal::messenger()->addMessage( t( "There is no cache folder or not writable: " . $tempfolder ) );
    }
    
    // make thumbnail folders if not exists
    if (! is_dir( $this->root . $path . TN )) {
      mkdir( $this->root . $path . TN );
    }
    // Query of this folder
    
    $this->con = \Drupal::database();
    $sql = "SELECT id, path, name, typ, viewnumber, subtitle, `link` FROM {smplphotoalbum} WHERE path='" . $path . "'";
    $rs = $this->con->query ( $sql );
    $rs->allowRowCount = TRUE;
    $db = $rs->rowCount ();
    
    foreach ( $rs as $record ) {
      $this->RSArray[$record->name] = $record; // The results of query as parameter of images given by address
    }
    // Reading entries in the folder
    $entries = scandir ( $root . $path, SCANDIR_SORT_NONE );
    unset ( $entries[array_search ( '.', $entries )] );
    unset ( $entries[array_search ( '..', $entries )] );
    unset ( $entries[array_search ( str_replace ( "/", "", TN ), $entries )] );
    // loop for the directory
    $db = 0;
    
    foreach ( $entries as $entry ) {      
      if ($this->isimage($entry) || 
         ($this->video && $this->isvideo( $entry ) )      ||
         ($this->video && $this->isvideohtml5( $entry ) ) || 
         ($this->audio && $this->isaudiohtml5( $entry ) ) || 
         ($this->audio && $this->isaudio( $entry ) )      || 
         ($this->doc && $this->isdoc ( $entry ) )         || 
         ($this->cmp && $this->iscmp ( $entry ) )         || 
         ($this->app && $this->isapp ( $entry ) )         || 
         ($this->dis && !( $this->isdis ( $entry ) ) ) 
      ) {
        $entry = utf8_encode ( $entry );
        $type = $this->type ( $entry );        
        // check image in the database and check or makes thumbnail if must
        $id = $this->ChkImage2DB ( $entry, $type, $path );
        
        if ($this->isimage( $entry )){          
          $tpl = $this->tpl["image"];
        }
        elseif ($this->isvideohtml5( $entry )){
          $tpl = $this->tpl["videohtml5"];
        }
        elseif ($this->isaudiohtml5( $entry )){
          $tpl = $this->tpl["audiohtml5"];
        }
        else{
          $tpl = $this->tpl["other"];
        }

        $Pictures [$db] = new Image ( $id, $this->ImgProps, $params, $entry, $type, $tpl );
        $db++;
      } // if
    } // foreach

    // order by
    if ($this->order && $this->sortorder != "-") {
      if ($this->ascdesc != "desc") {
        switch (substr ( $this->sortorder, 0, 2 )) {
          case 'fi' :
            usort ( $Pictures, function ($a, $b) {
              return strcmp ( $a->entry, $b->entry );
            } );
            break;
          case 'su' :
            usort ( $Pictures, function ($a, $b) {
              return strcmp ( $a->subtitle, $b->subtitle );
            } );
            break;
          case 'si' :
            usort ( $Pictures, function ($a, $b) {
              return ($a->filesize < $b->filesize ? - 1 : 1);
            } );
            break;
          case 'da' :
            usort ( $Pictures, function ($a, $b) {
              return ($a->thdate < $b->thdate ? - 1 : 1);
            } );
            break;
          case 'vi' :
            usort ( $Pictures, function ($a, $b) {
              return ($a->viewnumber < $b->viewnumber ? - 1 : 1);
            } );
            break;
          case 'ty':
            usort ( $Pictures, function ($a, $b) {
              return strcmp ($a->type , $b->type );
            } );
            break;
        }
      } else {
        switch (substr ( $this->sortorder, 0, 2 )) {
          case 'fi' :
            usort( $Pictures, function ($a, $b) {
              return strcmp ( $b->entry, $a->entry );
            } );
            break;
          case 'su' :
            usort( $Pictures, function ($a, $b) {
              return strcmp ( $b->subtitle, $a->subtitle );
            } );
            break;
          case 'si' :
            usort( $Pictures, function ($a, $b) {
              return ($a->filesize > $b->filesize ? - 1 : 1);
            } );
            break;
          case 'da' :
            usort( $Pictures, function ($a, $b) {
              return ($a->thdate > $b->thdate ? - 1 : 1);
            } );
            break;
          case 'vi' :
            usort( $Pictures, function ($a, $b) {
              return ($a->viewnumber > $b->viewnumber ? - 1 : 1);
            } );
            break;
          case 'ty' :
            usort( $Pictures, function ($a, $b) {
              return strcmp($b->type, $a->type);
            } );
            break;
        }
      }
    }
    
    if ($this->stat) {
      $this->numitems = count ( $Pictures );
      $this->sumviews = 0;
      foreach ( $Pictures as $p ) {
        $this->sumviews += $p->getOpened ();
      }
    }    
    $j = 0;
    for($i = 0; $i < $db; $i++) {
      if($_SESSION['slide'][$this->slide_path]){
        $_SESSION['slide'][$this->slide_path]['img'][$i]['id']       = $Pictures[$i]->getId();
        $_SESSION['slide'][$this->slide_path]['img'][$i]['subtitle'] = $Pictures[$i]->getSubtitle();
        $_SESSION['slide'][$this->slide_path]['img'][$i]['title']    = $Pictures[$i]->getEntry();
      }
      if ($this->firstimage <= $i && $i < $this->firstimage + $this->number) {
        $this->aPictures[$j] = $Pictures[$i];        
        $j++;
      }
    }
    //Slideshow
    if($this->slide){
      $sli = rand(0, $db-1);
      $this->slide_i    = $_SESSION['slide'][$this->slide_path]['i']= $sli;
     
      $this->slide_id       = $Pictures[$this->slide_i ]->getId();
      $this->slide_subtitle = $Pictures[$this->slide_i ]->getSubtitle();
      $this->slide_title    = $Pictures[$this->slide_i ]->getEntry();
      $this->slide_db       = $db;
      $this->slide_path     = $path;      
    }

    $this->pagenumber = ( int ) ($db / $this->number) + ($db % $this->number != 0 ? 1 : 0);
    // If the page number higher than should be
    if ($this->page > $this->pagenumber) {
      $this->page = $this->pagenumber;
    }
  }
  
  /**
   * Check if the image in the database
   * If not then make a thumbnail and a a new record in it.
   * 
   * @param unknown $name          
   * @return $id - return the id of this imagek
   */
  function ChkImage2DB($entry, $type, $path) {
    $source = $this->root . $this->path . $entry;
    $thumbnail = $this->root . $this->path . TN . $entry;
    $filetime = @filemtime ( $source );
    
    if (! isset ( $this->RSArray [$entry] )) {
      $id = $this->con->insert ( 'smplphotoalbum' )->fields ( array (
          'path' => $path,
          'name' => $entry,
          'typ' => $type,
          'viewnumber' => 0,
          'subtitle' => $entry,
          'link' => '' 
      ) )->execute ();
      
      $this->RSArray [$entry] = array (
          'id' => $id,
          'path' => $path,
          'name' => $entry,
          'typ' => $type,
          'viewnumber' => 0,
          'subtitle' => $entry,
          'link' => "" 
      );
      $msg = '';
      if ($type == 'image') {
        $msg = t ( "Image added to DB:" );
        \Drupal::messenger()->addMessage( " " . $msg . "." );
        $this->MakeThumbnail ( $entry, $source, $thumbnail );
      } else {
        switch ($type) {
          case 'audio' :
            $msg = t ( "Audio added to DB:" );
            break;
          case 'audiohtml5' :
            $msg = t ( "Audio HTML5 added to DB:" );
            break;
          case 'video' :
            $msg = t ( "Video added to DB:" );
            break;
          case 'videohtml5' :
            $msg = t ( "Video HTML5 added to DB:" );
            break;
          case 'doc' :
            $msg = t ( "Document added to DB:" );
            break;
          case 'compress' :
            $msg = t ( "Compressed file added to DB:" );
            break;
          case 'application' :
            $msg = t ( "Application added to DB:" );
            break;
          case 'oth' :
            $msg = t ( "Other file added to DB:" );
            break;
        }
        \Drupal::messenger()->addMessage( " " . $msg . "." );
        $this->MakeThumbnail ( $entry, $source, $thumbnail . ".png" );
      }
    } else { // isset RSArray[entry]
      if ($type == "image") {
        if (is_readable ( $thumbnail )) {
          $thDate = @filemtime ( $thumbnail );
          $up = ($filetime > $thDate);
          if ($up) {
            unlink ( $thumbnail );
            $this->MakeThumbnail ( $entry, $source, $thumbnail, "Thumbnail update:" );
          }
        } else {
          $this->MakeThumbnail ( $entry, $source, $thumbnail, "Thumbnail update:" );
        }
      } else {
        if (is_readable ( $thumbnail . ".png" )) {
          $thDate = @filemtime ( $thumbnail . ".png" );
          if ($filetime > $thDate) {
            unlink ( $thumbnail . ".png" );
            $this->MakeThumbnail ( $entry, $source, $thumbnail . ".png", "Thumbnail update:" );
          }
        } else {
          $this->MakeThumbnail ( $entry, $source, $thumbnail . ".png", "Thumbnail update:" );
        }
      }
      $id = $this->RSArray [$entry]->id;
    }
    
    // Properties of actual Image
    $this->ImgProps ['viewnumber'] = isset($this->RSArray [$entry]->viewnumber)?$this->RSArray [$entry]->viewnumber:0;
    $this->ImgProps ['subtitle']   = isset($this->RSArray [$entry]->subtitle)?$this->RSArray [$entry]->subtitle:'';
    $this->ImgProps ['link']       = isset($this->RSArray [$entry]->link)?$this->RSArray [$entry]->link:'';
    return $id;
  }
  
  /**
   * Update thumbnail if the original image is changed
   */
  function MakeThumbnail($entry, $source, $thumbnail, $msg = "New thumbnail: ") {
    if (! $this->access ())
      return true;
    $ext = strtolower ( pathinfo ( $entry, PATHINFO_EXTENSION ) );
    // Make new thumbnails from GIF, PNG or JPG | JPEG | BMP | WBMP
    if ($this->isimage ( $entry )) {
      $size = GetImageSize ( $this->root . $this->path . $entry );
      $dx = $size [0];
      $dy = $size [1];
      
      if ($dx > $this->width) {
        // Target image
        $dst_im = @ImageCreateTrueColor ( $this->width, $this->width * $dy / $dx );
        switch ($ext) {
          case 'jpg' :
          case 'jpeg' :
            $im = @imagecreatefromjpeg ( $source );
            $a = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
            $ok = @Imagejpeg ( $dst_im, $thumbnail, 70 );
            break;
          case 'png' :
            $im = @imagecreatefrompng ( $source );
            $a = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
            $ok = @imagepng ( $dst_im, $thumbnail, 9 );
            break;
          case 'wbmp' :
            $im = @imagecreatefromwbmp ( $source );
            $a = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
            $ok = @imagewbmp ( $dst_im, $thumbnail );
            break;
          case 'gif' :
            $im = @imagecreatefromgif ( $source );
            $a = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
            $ok = @imagegif ( $dst_im, $thumbnail );
            break;
          case 'bmp' :
            $im = @imagecreatefrombmp ( $source );
            $a = @imagecopyresized ( $dst_im, $im, 0, 0, 0, 0, $this->width, $this->width * $dy / $dx, $dx, $dy );
            $ok = @imagebmp ( $dst_im, $thumbnail );
            break;
          default :
            $ok = true;
        }
      } else {
        $ok = copy ( $source, $thumbnail );
      }
    } else {
      if ($this->isaudio ( $entry ))
        $source = $this->modulepath . "/image/audio_" . $ext . ".png";
        elseif ($this->isdoc ( $entry ))
        $source = $this->modulepath . "/image/doc_" . $ext . ".png";
        elseif ($this->iscmp ( $entry ))
        $source = $this->modulepath . "/image/cmp_" . $ext . ".png";
        elseif ($this->isapp ( $entry ))
        $source = $this->modulepath . "/image/app_" . $ext . ".png";
        elseif ($this->isvideo ( $entry ))
        $source = $this->modulepath . "/image/video_" . $ext . ".png";
      else
        $source = $this->modulepath . "/image/other.png";
      $ok = copy( $source, $thumbnail );
    }
    if ($ok)
      $msg = t ( $msg ) . $thumbnail;
    else
      $msg = t ( "Can not writeable thumbnail: " ) . $thumbnail;
      \Drupal::messenger()->addMessage( $msg );
    return $ok;
  }
  
  /**
   * Set parameters, load templates, etc.
   * 
   * @param array $params          
   */
  function preSettings(&$params) {    
    $this->modulepath = $params ['modulepath'];
    $this->path       = $params ['path'];
    $this->width      = (int) $params ['width'];
    $this->number     = ( int ) ($params ['number']);
    $this->order     = $params ["order"];
    $this->sortorder = $params ["sortorder"];
    $this->ascdesc   = $params ["ascdesc"];
    $this->sub       = $params ['sub'];    
    $this->smplbox   = $params ['smplbox'];
    $this->viewed    = $params ['viewed'];
    $this->edit      = $params ['edit'];
    $this->exif      = $params ['exif'];
    $this->stat      = $params ['stat'];
    $this->private   = $params ['private'];
    $this->audio     = $params ['audio_checking'];    
    $this->video     = $params ['video_checking'];
    $this->doc       = $params ['doc_checking'];
    $this->cmp       = $params ['cmp_checking'];
    $this->app       = $params ['app_checking'];
    $this->oth       = $params ['oth_checking'];
    $this->dis       = $params ['dis_checking'];
    $this->html5     = $params ['html5_checking']; // Use the html5 widgets
    $this->url       = $params ['url_checking'];
    $this->lazy      = $params ['lazy'];
    $this->title     = isset($params ['title'])? $params['title']: '';
    $this->slide_checking = $params ['slide_checking']; 
    $this->slide     = $params['slide'] && $params['slide_checking'];
    
    if (isset ( $_REQUEST ['smplpage'] )) {
      $this->page = ( int ) ($_REQUEST ['smplpage']);
    } else {
      $this->page = 0;
    }

    // read twig templates
    $tpl = realpath ($this->modulepath ."/templates" );    
    $this->tpl = array();
    $this->tpl['smplphotoalbum'] = file_get_contents( $tpl . "/smplphotoalbum.html.twig" );
    // Reading templates
    
    $this->tpl['image']        = file_get_contents( $tpl . "/image.html.twig" );
    $this->tpl['videohtml5']   = file_get_contents( $tpl . "/videohtml5.html.twig" );
    $this->tpl['audiohtml5']   = file_get_contents( $tpl . "/audiohtml5.html.twig" );
    $this->tpl['other']        = file_get_contents( $tpl . "/other.html.twig" );
    $this->tpl['pagerbuttons'] = file_get_contents( $tpl . "/pagerbuttons.html.twig" );
    $this->tpl['sortorder']    = file_get_contents( $tpl . "/sortorder.html.twig" );
    $this->tpl['stat']         = file_get_contents( $tpl . "/stat.html.twig" );
    if($this->slide){
      $this->tpl['slide']      = file_get_contents( $tpl . "/slide.html.twig" );
      $this->tpl['slideimage'] = file_get_contents( $tpl . "/slideimage.html.twig" );
    }
    
    if (isset ( $_REQUEST [SMPLADMIN] )) {
      $this->firstimage = 0;
      $this->page = 0;
      $this->pagelength = 1000;
    } else {
      $this->firstimage = $this->page * $this->number;
    }
  }
  /**
   * Render the table of images
   */
  function Render() {
    global $base_path;
    
    if (isset ( $_REQUEST ['smplcacheclear'] ) && $uideditok) {
      $this->CacheClearAll ();
    }
    
    // Load smpl template
    $str = $this->tpl["smplphotoalbum"];
    
    $access = $this->access();
    
    if (! $access) {
      $str = preg_replace( "#<EditForm(.*?)<\/EditForm>#imxs", "", $str );
      $str = preg_replace( "#<ImgEditForm(.*?)<\/ImgEditForm>#imxs", "", $str );
    } else {
      $str = str_replace( array("<EditForm>", "</EditForm>", "<ImgEditForm>", "</ImgEditForm>" ), "", $str );
      $str = str_replace ( "{{ ImgEditDefault }}", $base_path . $this->modulepath . "/image/404.png", $str );
    }
    if(!empty($this->title)){
      $this->title = '<h2 class="smpl_title">'.$this->title.'<h2>';
    }
    $str = str_replace('{{ title }}', $this->title, $str);
    // order
    if ($this->order) {
      $str = str_replace( "{{ sortorder }}", $this->SortOrder(), $str );
    } else {
      $str = str_replace( "{{ sortorder }}", "", $str );
    }
    
    // statistics
    if ($this->stat)
      $str = str_replace( "{{ statistics }}", $this->Statistics (), $str );
    else {
      $str = str_replace( "{{ statistics }}", "", $str );
    }
    // Link tor statistics
    if (smplphotoalbum_access ()) {
      $str = str_replace( "{{ linktostat }}", '<br /><a href="admin/config/smplphotoalbum/stat" target="_blank">' . t ( "Link to statistics" ) . '</a>', $str );
    } else {
      $str = str_replace( "{{ linktostat }})", "", $str );
    }
    $s = array ();
    $r = array ();

    foreach( $this->words as $e ) {
      $s[] = '{{ ' . $e . ' }}';
      $r[] = ( string ) (t ( $e ));
    }
    $str = str_ireplace ( '{{ METHOD }}', SMPLTEST ? "GET" : "POST", $str );
    $s[] = '{{ smpl.ajax }}';
    $r[] = $base_path . 'smplphotoalbum';
    $s[] = "{{ jquery }}";
    $r[] = $base_path;
    $s[] = "{{ modulepath }}";
    $r[] = $base_path . $this->modulepath;
    $s[] = "{{ smplbox }}";
    $r[] = $this->smplbox;
    
    $str = str_ireplace( $s, $r, $str );
    $str = str_ireplace( "{{ CacheClearThisPage }}", $this->page, $str );
    //
    $Pager = $this->PagerButtons ();
    $Table = $this->Table ();
    
    $str = str_ireplace( "{{ pager }}", $Pager, $str );
    $str = str_ireplace( "{{ table }}", $Table, $str );
    return $str;
  }
  /**
   * SlideShow
   * @return string
   */
  function Slideshow(){
    global $base_url, $base_path;
    $str = $this->tpl["slide"];
    
    $s[] = "{{ linksrc }}";
    $r[] = $base_url . "/smplphotoalbum/slide/";
    
    $s[] = "{{ link }}";
    $r[] = $base_url . "/smplphotoalbum/slide/" . $this->slide_id;

    $s[] = "{{ lazy }}";
    $r[] = $this->lazy ? " loading='lazy' " : ""; 
    
    $s[] = "{{ title }}";
    $r[] = $this->slide_title;
    
    $s[] = "{{ sub }}";
    $r[] = $this->slide_subtitle;
    
    $s[] = '{{ ajax }}';
    $r[] = $base_path . 'smplphotoalbum';
    
    $s[] = '{{ path }}';
    $r[] = $this->slide_path;
    
    $s[] = "{{ id }}";
    $r[] = $this->slide_id;
    
    $s[] = '{{ i }}';
    $r[] = $this->slide_i;
    
    $s[] = '{{ ajax }}';
    $r[] = $base_path . 'smplphotoalbum';

    $s[] = '{{ interval }}';
    $r[] = $this->interval;
    
    $s[] = '{{ style }}';
    $r[] = $this->style;
    
    $s[] = '{{ thumbnails }}';
    $r[] = $this->SlideThumbnails();
    
    foreach( $this->words as $e ) {
      $s[] = '{{ ' . $e . ' }}';
      $r[] = ( string ) (t ( $e ));
    }    
    $str = str_ireplace($s,$r,$str);
    return $str;
  }
  
  /**
   * SlideThumbnails
   * @return string|mixed
   */
  function SlideThumbnails(){
    global $base_url;
    $i = $this->slide_i;
    $str ="";

    $pics = $_SESSION['slide'][$this->slide_path];
    $db = count($pics['img']);

    for($j=-3; $j < 4; $j++){
      $idx = ($i+$j) % $db;
      $s = array("{{ id }}", "{{ linktn }}","{{ titletn }}", "{{ slidetnsubtitle }}");
      $r = array (
          $idx, 
          $base_url . "/smplphotoalbum/slide/" . $pics['img'][$idx]["id"]. "&tn=1",
          $pics['img'][$idx]["title"],
          $pics['img'][$idx]["title"]
      );
      
      $str .= str_replace($s, $r, $this->tpl["slideimage"]);
    }
    return $str; 
  }
  /**
   * Write out a right table
   */
  function Table() {
    $uideditok = $this->access ();
    // start and end picture
    $db = count( $this->aPictures );
    
    $str = "";
    if ($db == 0) {
      return $str;
    }
    
    for($i = 0; $i < $db; $i ++) {
      $str .= $this->aPictures[$i]->Render( $uideditok );
    }
    return $str;
  }
  
  /**
   * Sort order
   * 
   * @return str - string
   */
  function SortOrder() {
    $str = $this->tpl ["sortorder"];
    $str = str_replace ( "{{ " . $this->sortorder . " }}", ' selected="selected"', $str );
    $str = str_replace ( array (
        "{{ - }}", "{{ filename }}", "{{ subtitle }}", "{{ size }}", "{{ date }}", "{{ views }}" 
    ), "", $str );
    if ($this->ascdesc != 'desc') {
      $str = str_replace ( "{{ asc }}", ' selected="selected"', $str );
      $str = str_replace ( "{{ desc }}", "", $str );
    } else {
      $str = str_replace ( "{{ desc }}", ' selected="selected"', $str );
      $str = str_replace ( "{{ asc }}", "", $str );
    }
    return $str;
  }
  /**
   * Makes pager links
   */
  function PagerButtons() {
    if ($this->pagenumber < 2)
      return "";
    
    // Actual page
    $this->page = (isset ( $_REQUEST ['smplpage'] )) ? ( int ) ($_REQUEST ['smplpage']) : 0;
    if ($this->page < 0)
      $this->page = 0;
    if ($this->page > $this->pagenumber - 1)
      $this->page = $this->pagenumber - 1;
    
    // Make string
    $button = $this->tpl ['pagerbuttons'];
    $str = " <div class=\"smpl_pager\">\n";
    
    if ($this->pagenumber > 5) {
      // First pager
      if ($this->page > 0) {
        $str .= str_replace ( array (
            '{{ value }}',
            '{{ class }}',
            '{{ name }}',
            "{{ disabled }}" 
        ), array (
            '0',
            '',
            '&laquo;&nbsp;' . t ( 'First' ),
            "" 
        ), $button );
      }
      // -5 page
      if ($this->page > 5) {
        $str .= str_replace ( array (
            '{{ value }}',
            '{{ class }}',
            '{{ name }}',
            "{{ disabled }}" 
        ), array (
            $this->page - 5,
            '',
            '&lsaquo;&nbsp;' . t ( 'Page' ),
            "" 
        ), $button );
      }
    }
    // if page
    if ($this->pagenumber < 6) {
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
      $str .= str_replace ( array (
          "{{ class }}",
          "{{ value }}",
          "{{ name }}",
          "{{ disabled }}" 
      ), array (
          $cl,
          $i,
          $i + 1,
          $d 
      ), $button );
    }
    if ($this->pagenumber > 5) {
      // -5 page
      if ($this->page < $this->pagenumber - 5) {
        $str .= str_replace ( array (
            '{{ value }}',
            '{{ class }}',
            '{{ name }}',
            "{{ disabled }}" 
        ), array (
            $this->page + 5,
            '',
            t ( 'Page' ) . '&nbsp;&rsaquo;',
            "" 
        ), $button );
      }
      // last page
      if ($this->page < $this->pagenumber - 1) {
        $str .= str_replace ( array (
            '{{ class }}',
            '{{ value }}',
            '{{ name }}',
            "{{ disabled }}" 
        ), array (
            '',
            ($this->pagenumber - 1),
            t ( 'Last' ) . '&nbsp;&raquo;&nbsp;',
            "" 
        ), $button );
      }
    }
    $str .= "  </div>\n";
    return $str;
  }
  
  /**
   * Statistic og actual folder
   * 
   * @return mixed
   */
  function Statistics() {
    $str = $this->tpl ["stat"];
    $s = array (
        '{{ Statistics }}',
        '{{ Dataname }}',
        "{{ Value }}",
        "{{ Number of items }}",
        "{{ Number of views }}",
        "{{ Average of views }}" 
    );
    $r = Array (
        t ( 'Statistics' ),
        t ( 'Name of data of this node' ),
        t ( 'Value' ),
        t ( 'Number of items' ),
        t ( 'Number of views' ),
        t ( 'Average of views' ) 
    );
    $str = str_replace ( $s, $r, $str );
    if ($this->numitems > 0) {
      $avgviews = round ( $this->sumviews / $this->numitems, 1 );
    } else {
      $avgviews = t ( "There is no items" );
    }
    $str = str_replace ( array (
        '{{ numitems }}',
        '{{ sumviews }}',
        '{{ avgviews }}' 
    ), array (
        $this->numitems,
        $this->sumviews,
        $avgviews 
    ), $str );
    return $str;
  }
  function SmplStat() {
    $p = substr ( $this->path, 0, 1 ) == "/" ? substr ( $this->path, 1 ) : $this->path;
    $str = '<a href="admin/config/fz/smplphotoalbum/stat?smpl_path_filter=' . $p . '" target="SimpleStat">' . t ( 'Statistics' ) . '</a>';
    return $str;
  }
  function Help() {
    $str = '<a id="smpl_help_link">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . t ( 'Help' ) . '</a>
    <div id="smpl_help" class="smpl_help"><div id="smpl_help_close"></div><div id="smpl_help_content"></div></div>';
    return $str;
  }
  
  /**
   * check user access
   * @return boolean
   */
  function access() {
    return $this->user->id () == 1 || ($this->user->hasPermission ( "administer smplphotoalbum" ) || $this->user->hasPermission ( "edit smplphotoalbum" ));
  }
  
  public function isaudio( $entry){
     return stripos(" ".$this->params["audio_extensions"], pathinfo($entry, PATHINFO_EXTENSION))> 0;
  }
  
  public function isaudiohtml5( $entry){
    return stripos(" ".$this->params["audiohtml5_extensions"], pathinfo($entry, PATHINFO_EXTENSION))> 0;
  }
  
  public function isvideo( $entry){
    return stripos(" ".$this->params["video_extensions"], pathinfo($entry, PATHINFO_EXTENSION))> 0;
  }
  
  public function isvideohtml5( $entry){
    return stripos(" ".$this->params["videohtml5_extensions"], pathinfo($entry, PATHINFO_EXTENSION))> 0;
  }
  public function isdoc( $entry){
    return stripos(" ".$this->params["doc_extensions"], pathinfo($entry, PATHINFO_EXTENSION))> 0;
  }
  
  public function iscmp( $entry){
    return stripos(" ".$this->params["cmp_extensions"], pathinfo($entry, PATHINFO_EXTENSION))> 0;
  }
  
  public function isapp( $entry){
    return stripos(" ".$this->params["app_extensions"], pathinfo($entry, PATHINFO_EXTENSION)) > 0;
  }
  
  public function isoth($entry){
    return stripos(" ".$this->params["oth_extensions"], pathinfo($entry, PATHINFO_EXTENSION)) > 0;
  }
  
  public function isimage( $entry){    
    return stripos(" ".$this->params["image_extensions"], pathinfo($entry, PATHINFO_EXTENSION)) > 0;
  }
  public function isdis( $entry){
    return stripos(" ".$this->params["dis_extensions"], pathinfo($entry, PATHINFO_EXTENSION)) > 0;
  }
  
  function type($entry){
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
  
}