<?php
namespace Drupal\smplphotoalbum\Controller;
require realpath(__DIR__."/../../")."/vendor/autoload.php";

use FFMpeg\FFMpeg;

class VideoEdit {
  private $id = 0;
  private $path = '';
  private $name = '';
  private $type = '';
  private $width = 1280;
  private $height = 720;
  private $framerate = 30;
  private $clipstart = 0; //beginning of clip >=0
  private $clipend = 0; // end of clip <= duration
  private $duration = 0; // length of original video
  private $cfg;   // smpl config;
  private $root;  // photoalbum source folder
  private $temppath; // Temporary folder
  private $public;
  private $sign; // It sings the long process
  
  // Session variables
  private $rq;
  private $sess;
  private $ts;


  // Video2MP4 class implementation
  function __construct(
    $id,
    $path = '', 
    $name = '', 
    $type = "video", // what is the type now (video or notvideo)
    $ext = "mp4", // what is the extension now
    $width = 0, // width of video
    $height= 0, // height of video
    $framerate=30, // framerate of video
    $clipstart=0, // clip start time in seconds    
    $clipend = 0, // video length
    $duration = 0, // clip duration in seconds
    $oldname = '',  // old file name if rename the video
    $newname = '', // new file name if rename the video    
  )
  {
    $this->id = $id;
    $this->path = $path;
    $this->name = $name;
    $this->type = $type;
    $this->width = $width;
    $this->height = $height;
    $this->framerate = $framerate; 
    $this->clipstart = $clipstart;
    $this->duration  = $duration;
    $this->clipend = $clipend;    
    
    $this->cfg    = \Drupal::config( 'smplphotoalbum.settings' );
    $this->public = \Drupal::service( 'file_system' )->realpath( "public://" );
    $root = $this->cfg->get( 'root' );   
    $temp = $this->cfg->get( 'temp' ); 
    
    $this->root = str_replace( "public://", $this->public . "/", $root ); 
    $this->root .= substr( $this->root, - 1 ) != '/' ? "/" : '';
    $this->temppath = str_replace( "public://", $this->public."/", $temp);        
    $this->path = $this->slash( $this->root . $this->path);

    $this->rq   = \Drupal::request();
    $this->sess = $this->rq->getSession ();
    $this->ts   = $this->sess->get( "smpl" );   //Session variables of video edit     
    //
    $t = $this->cfg->get( 'temp' );
    $this->temppath = str_replace( "\\", "/", \Drupal::service ( 'file_system' )->realpath ( $t ) );
    $this->temppath .= substr( $this->temppath, - 1 ) != '/' ? "/" : '';

    // tempurl to edit
    $this->ts ['tempurl'] = \Drupal::service( 'file_url_generator' )->generateAbsoluteString ( $t );
    $this->ts ['tempurl'] .= substr( $this->ts['tempurl'], - 1 ) != '/' ? "/" : '';

  }

  /**
   * load datas of video edit file and send back to javascript
   * copy the video file into the edit area
   * This is a long process
   */
  public function load(){
    $con = \Drupal::database();
    $record = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id', 
        'path',       
        'name',            
    ] )->condition( 's.id', $this->id, '=' )->execute();
        
    $a = $record->fetchAssoc();
    
    $ex = new Exif( $a, $this->cfg );
    $ex->Videoinfo( $a );
    unset($a['path']);
    return $a;
  }

  /**
   * Copy video file from original place to temp place
   * @return array{id: string, msg: string} 
   */

  public function copy2edit(){
    $from = $this->path . $this->name;
    
    $to   = $this->temp;
    
    $ok = (copy($from, $to))? "1" : "-1";

    if($ok) $msg = "Video copied to edit area.";
    else $msg = "Video copied problem";
    return ["id" => $ok, "msg" => $msg];
  }


  public function convert() {
        
    $ok = "1";
    $msg = "Conversion successful from '$this->path' to MP4 format.";

    $ffmpeg = FFMpeg::create();
    
    $video = $ffmpeg->open($this->path);
    
    $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');
    if($this->width >0 && $this->height >0) {
      $video
        ->filters()
        ->resize(new \FFMpeg\Coordinate\Dimension($this->width, $this->height))
        ->synchronize();     
    }
  
    
    if( $this->clipstart>0 && $this->clipend > 0 ){      
      $clip = $video->clip(
        \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clipstart),
        \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clipend - $this->clipstart),
      );
    }    

    $filename = pathinfo($this->path, PATHINFO_FILENAME) . '.mp4';
    $path = pathinfo($this->path, PATHINFO_DIRNAME);
    
    $video ->save($format, $path."/".$filename);
    
    return ["id" => $ok, "msg" => $msg];
  }

  /**
   * Save the modified video file to the original place
   * @return array{id: string, msg: string} 
   */
  public function save(){
    $ok = "1";
    $msg = "Video edit parameters saved.";
    return ["id" => $ok, "msg" => $msg];
  }

  public function saveas(){
    $ok = "1";
    $msg = "Video edit parameters saved as new file.";
    return ["id" => $ok, "msg" => $msg];
  }

  public function edit(){
    $ok = "1";
    return ["id" => $ok, "msg" => "Video edit started."];
  }

  public function undo(){
    $ok = "1";
    return ["id" => $ok, "msg" => "Video edit undone."];
  }

  public function redo(){
    $ok = "1";
    return ["id" => $ok, "msg" => "Video edit redone."];
  } 

  public function cancel(){
    $ok = "1";
    return ["id" => $ok, "msg" => "Video edit cancelled."];
  }

  public function close(){
    $ok = "1";
    return ["id" => $ok, "msg" => "Video edit closed."];
  } 

   /**
   * It makes slash from double slash or backslash
   * @param mixed $p 
   * @return string|string[] 
   */
  public function slash($p){
    return str_replace(['\\',"//"],"/", $p);
  }

  /**
   * Make a new name of temporary file
   *
   * @param string $name
   * @param number $i
   * @return string
   */
  private function NewName($name = "", $i = 0) {
    $p = pathinfo ( $name );
    $name = $p ["filename"] . "_temp_" . $i;
    $ext  = $p ["extension"];
    return $name . "." . $ext;
  }

  /**
   * Make a sign file into the temporary folder
   * @return string
   */
  function Sign() {
    $p = pathinfo ( $this->ts ["tempname"], PATHINFO_FILENAME ) ."_sign.txt";
    return $p;
  }

  function SignUrl(){    
    return $this->ts['tempurl'] . $this->sign();
  }

} 