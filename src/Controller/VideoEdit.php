<?php
namespace Drupal\smplphotoalbum\Controller;
//require realpath(__DIR__."/../../")."/vendor/autoload.php";

use FFMpeg\FFMpeg;
;
use Drupal\smplphotoalbum\Controller\Lib;

class VideoEdit {
  private $id = 0;
  private $path = '';
  private $name = '';
  private $type = '';
  private $ext = '';
  private $newext = '';
  private $width = 1280;
  private $height = 720;
  private $framerate = 30;  
  private $gop = 2; // group of picture for framerate change
  private $clipstart = 0; //beginning of clip >=0
  private $clipend = 0; // end of clip <= duration
  private $duration = 0; // length of original video
  private $rotate = 0; // rotate angle of video, in degree, 90, 180, 270
  private $cfg;   // smpl config;
  private $root;  // photoalbum source folder
  private $temppath; // Temporary folder
  private $public;
  private $SignFile; // It sings the long process   
    
  // Session variables
  private $rq;
  private $sess;
  private $ts; // Session, as array
  public $bt; // BreakTime class for long process
  public $lib; // Lib class for common functions
  public $initialParams = [ 
    "-qscale",
    0
  ];
  public $mp;

  // Video2MP4 class implementation
  function __construct(
    $id,
    $path = '', 
    $name = '', 
    $type = "video", // what is the type now (video or notvideo)
    $ext = "mp4", // what is the extension now
    $newext = "mp4", // what is the extension after conversion
    $width = 0, // width of video
    $height= 0, // height of video
    $framerate = 30, // framerate of video
    $gop = 2, // group of pictures for framerate change
    $clipstart=0, // clip start time in seconds    
    $clipend = 0, // video length
    $rotate = 0, // rotate angle of video
    $duration = 0, // clip duration in seconds
    $oldname = '',  // old file name if rename the video
    $newname = '', // new file name if rename the video    
  )
  {

    $this->id = $id;
    $this->path = $path;
    $this->name = $name;
    $this->type = $type;
    $this->ext = $ext;
    $this->newext = ($newext == "mp4") ? "mp4" : '';
    $this->width = (int) $width;
    $this->height = (int) $height;
    $this->framerate = is_numeric($framerate) ? $framerate : false;
    $this->gop = is_numeric( $gop) ? $gop : false;    
    $this->clipstart = (float) $clipstart;
    $this->duration  = (float) $duration;    
    $this->clipend = (float) $clipend;    
    $this->rotate = $rotate;
    
    $this->cfg  = LIB::getConfig();
    $root       = LIB::getConfig( 'root' );   
    $this->root = LIB::getRoot( $root );
    $this->mp = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();
    // temppath for copy    
    $this->temppath = LIB::getTempPath();

    $this->path = LIB::slash( $this->root . $this->path );    
    $this->ts   = LIB::getSession( "smpl" );   //Session variables of video edit    

    // edit folder url to edit
    $this->ts['tempurl'] = LIB::getTempUrl();
    if (!isset($this->ts["tempname"])) $this->ts["tempname"] ="";   
    $this->SignFile = Lib::Sign($this->ts["tempname"]);
    //Percentage of conversion 
    $this->bt  = new BreakVideo( $this->temppath, $this->SignFile, 10, false, true ); 
    $this->bt->setMax(100);   
  }

  /**
   * load datas of video edit file and send back to javascript
   * copy the video file into the edit area
   * This is a long process
   */
  public function Load(){
    $con = \Drupal::database();
    $rs = $con->select( 'smplphotoalbum', 's' )->fields( 's', [
        'id', 
        'path',       
        'name',
        'typ'
    ] )->condition( 's.id', $this->id, '=' )->execute();
      
    $record = $rs->fetchAssoc();
    
    $ex = new Exif( $record, $this->cfg );
    $ex->Videoinfo( $record );

    //Reset the session variables
    Lib::ResetEditSession("video", $this->ts );    
    
    $this->ts["tempname"] = LIB::NewName( $record["name"],0 );    
    $this->ts["id"]       = $this->id;
    
    $this->ts["idx"]      = 0;
    $this->ts["que"]      = 0;
    unset( $this->ts["cmd"] );    
    $this->ts["cmd"][ 0 ] = $this->ts["tempname"];
    
    $this->ts['bkp']      = 0;
    $this->ts["name"]     = $record["name"];
    $this->ts["path"]     = $record['path'];
    $this->ts["ext"]      = $this->ext;
    $this->ts["newext"]   = $this->newext;    
    $this->ts["framerate"]= $record["framerate"];
    $this->ts["gop"]      = $record["gop"];
    $this->ts["width"]    = $record["width"];
    $this->ts["height"]   = $record["height"];
    $this->ts["clipstart"]= $record["clipstart"];
    $this->ts["clipend"]  = $record["clipend"];      
    $this->ts["duration"] = $record["duration"];
    $this->ts['params']   = '';
    // Data of original video file
    $this->ts["filesize"] = Lib::ShowFileSize( filesize( $this->path . $this->ts["name"] ) );
    $this->ts["modified"] = date ( "Y.m.d H:i:s",filemtime($this->path . $this->ts["name"] ) );   
    $this->ts["videokilobitrate"] = 0;
    $this->ts["audiokilobitrate"] = 256;

    // sign url;
    $this->ts["signurl"] = LIB::SignUrl($this->ts);
    $this->ts["url"] = LIB::getTempUrl() . $this->ts["tempname"];
    
    $this->ts["ok"] = 1;

    array_map ( "unlink", glob ( $this->temppath . "*" ) );

    //Copy the videofile
    
    $source = $this->path . $this->ts["name"];    
    $dest   = $this->temppath . $this->ts["tempname"];
    
    copy ( $source, $dest );    
    
    //Default parameters of ffmpeg for the original video file
    $video = FFMpeg::create(['temporary_directory' => $this->temppath])->open( $dest );
    $this->ts["params"] = $video->defaultSettings($this->mp."/"."ffmpeg_default_params.txt", true);
    //

    LIB::setSession( "smpl", $this->ts );  // Set the session of movie edit
    unset($record['path']); 
    
    return $this->MakeJson();    
  }
  
  /**
   * Video conversion
   * @return array{id: string, msg: string} 
  */
  public function Convert() { 
    $json = [];   
    $this->ts["idx"]++;
    if ($this->ts["idx"] > $this->ts["que"]) {
      $this->ts["que"] = $this->ts["idx"];
    }
    $ok = "1";
    
    $this->ts["signurl"] = LIB::SignUrl($this->ts);
    $this->ts["url"] = LIB::getTempUrl() . $this->ts["tempname"];
    $newname  = LIB::NewName( $this->ts["name"], $this->ts['idx'] );
    
    $ffmpeg = FFMpeg::create(['temporary_directory' => $this->temppath]);
       
    $video = $ffmpeg->open( $this->temppath . $this->ts["tempname"] );    
 
    // clip video if needed
    if( ($this->clipstart > 0 || $this->clipend > 0) && ($this->clipstart < $this->clipend ) ){      
       $video->filters()
        ->clip(
          \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clipstart),
          \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clipend - $this->clipstart),
        );            
    } 
    
    // change width or height if needed
    if($this->width >0 && $this->height >0) {
      $video->filters()->resize( new \FFMpeg\Coordinate\Dimension( $this->width, $this->height ) );
    }

    // rotate video if needed
    if( isset($this->rotate ) && in_array($this->rotate, [90, 180, 270]) ){
      switch($this->rotate){
        case 90: $rot = \FFMpeg\Filters\Video\RotateFilter::ROTATE_90; break;
        case 180: $rot = \FFMpeg\Filters\Video\RotateFilter::ROTATE_180; break;
        case 270: $rot = \FFMpeg\Filters\Video\RotateFilter::ROTATE_270; break;
      }
      $video->filters()->rotate( $rot );      
    }   

    // framerate change if needed
    if( isset($this->framerate) && 
        $this->framerate > 0 && 
        isset($this->gop) && 
        $this->gop > 0 
    ){
      $video->filters()->framerate( new \FFMpeg\Coordinate\FrameRate($this->framerate), $this->gop);
    }
    
    //Syncronize the filters after all changes
    $video->filters()->synchronize();  

    // The new or same extension of video after conversion
    if( $this->newext == "mp4" ){ 
      $newname   = LIB::NewName( $this->ts["name"], $this->ts['idx'] );      
      $info =  pathinfo($newname);
      $newname = $info["dirname"] ."/". $info['filename']. '.mp4'; 
    } else{
      $newname   = LIB::NewName( $this->ts["name"], $this->ts['idx'] );      
    }

    switch( $this->newext ){
      case "mp4":
        $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');       
        break;
      case "webm":
        $format = new \FFMpeg\Format\Video\WebM();
        break;
      case "ogg":
        $format = new \FFMpeg\Format\Video\Ogg();
        break;
      default:
        $format = new \FFMpeg\Format\Video\X264('libmp3lame', 'libx264');                
    }
    
    // Video bitrate change if needed
    $videokilobitrate = LIB::Request("videokilobitrate", 0);
    $format->setKiloBitrate( $videokilobitrate );
    $this->ts["videokilobitrate"] = $videokilobitrate;
    
    // Audio kilobitrate
    $audiokilobitrate = LIB::Request("audiokilobitrate", 256);
    $format->setAudioKiloBitrate( $audiokilobitrate );
    $this->ts["audiokilobitrate"] = $audiokilobitrate;
    //
    $defaultParams = LIB::Request("params", "");
    if( strlen($defaultParams ) > 0) {
      $defaultParams  = $this->changeFFMpegParams($defaultParams);
      $video->setDefaultSettings($defaultParams );
    }       
    
    //Percentage of conversion       
    
    $this->bt  = new BreakVideo( 
      $this->temppath,
      $this->SignFile, 
      1000, 
      false, 
      true 
    ); 
    $this->bt->setMax(100);
    
    // You can use this callback to send a message to the user about the progress of the conversion      
    $format->on('progress', function ($video, $format, $percentage) {
      $this->bt->Break( $percentage );
    });        

    $this->ts["msg"] = "";
    $ok = 1;
    try {
      $video->save($format, $this->temppath . $newname);                   
      $this->ts["tempname"] = $newname;
      $this->ts["url"] = LIB::getTempUrl() . $newname; 
      $this->FillTs( $this->temppath, $newname );
      LIB::setSession("smpl", $this->ts);

      $this->ts["cmd"][$this->ts["idx"] ] = $this->ts["tempname"];
      //
      if(empty($defaultParams)){
        $defaultParams = $video->getDefaultSettings(true);
      } else{
        if( is_array($defaultParams) ){
          $defaultParams = implode(" ", $defaultParams);
        }
      }
      $this->ts["params"] = $defaultParams;
      //
      $this->ts["msg"] .= "Conversion successful from '<b>$this->path . $newname</b>'.";  
    } catch (\Exception $e) {     
      $this->ts["msg"] = "Conversion cancelled by the user or failed because of FFMpeg problem! Error message: " . $e->getMessage(); 
      $this->ts["ok"] = -1;     
    }
    
    $this->DeleteSignFile();

    return $this->MakeJson();
  }

  /**
   * Empty the first parameters from ffmpeg command  
   * @param string $params - whole ffmpeg parameter string or array of parameter strings
   * @param string $cmd - what to do with the parameters
   * @return string | string[]
   */ 
  function changeFFMpegParams($params = ""){    
    if( is_array($params)){
      $params = implode(" ",$params);
    } else{      
      $params = str_replace( ["%20", "  ", ","], " ", $params);
      $params = trim($params);    
      $params = explode(" ",$params); 
    }
    return $params;
  }

  /**
   * Save the modified video file to the original place
   * @return array{id: string, msg: string} 
   */
  public function Save(){
    $idx = LIB::Request('idx',0);
    if( $idx < 1 && $idx >= $this->ts["que"]) {
      $this->ts['msg']  = "Original file can not save or does not exist converted video file (index: $idx >= $this->ts['que']).";
      $this->ts['ok'] = "-1";
      return $this->MakeJson(); 
    }
    
    $from = $this->temppath . $this->ts['cmd'] [ $idx ];
    $to = $this->path . $this->name;
    
    // Develop time has to backup the original file
    $bak = $this->path . $this->BackupFileName( $this->name, $this->path);     

    $ok = rename ( $to, $bak ); // backup the original file before overwriting
    $ok = $ok && copy ( $from, $to ); // $overwriting the original file with the converted file

    if (!$ok) {
      $this->ts['msg'] = "Error saving the '<b>$from</b>' video file.";
      $this->ts['ok'] = -1;
    } else {
      $this->ts['msg'] = "'<b>$from</b>' video file saved successfully.";
      $this->ts['ok'] = 1;
    }        
    return $this->MakeJson();    
  }
  
  /**
   * @param string $idx - the new name of video file
   * @param string $newname - the new name of video file
   * @return string[]|mixed[] 
   */
  public function SaveAs(){
    $idx = LIB::Request('idx',0);
    if( $idx < 1 && $idx >= $this->ts["que"]) {
      $this->ts['msg']  = "Original file can not save or does not exist converted video file (index: $idx >= $this->ts['que']).";
      $this->ts['ok'] = "-1";
      return $this->MakeJson(); 
    }
    
    // Get the new name of video file from request
    $newname= LIB::Request("newname", ""  );
    if( empty($newname) ){
      $this->ts["ok"] = -1;
      $this->ts['msg']  = "The new name of video can not be empty!";
      return $this->MakeJson();
    }
    
    $from = $this->temppath . $this->ts['cmd'] [ $idx ];
    $to = $this->path . $newname;
    if($newname == $this->name ) {      
      $this->ts["ok"] = -1;
      $this->ts['msg']  = "The new name of video can not be the same <b>'$newname'</b>!";
      return $this->MakeJson();
    }

    if( file_exists($to )){
       $this->ts["ok"] = -1;
       $this->ts['msg']  = "The video file (<b>'$to'</b>) exists in the original folder!";
       return $this->MakeJson();
    }
    
    $ok = copy ($from, $to);
    if (!$ok) {
      $this->ts['msg'] = "Error saving the '<b>$from</b>' video file.";
      $this->ts['ok'] = -1;
    } else {
      $this->ts['msg'] = "'<b>$from</b>' video file saved successfully.";
      $this->ts['ok'] = 1;
    } 
    return $this->MakeJson();
  }

  /**
   * Previous edited video in the queue
   * @return string[]|mixed[] 
   */
  public function Prev(){    
    if ( $this->ts["idx"] < 1) {
      return $this->MakeJson();
    }
    
    $this->ts["idx"]--;    
    $this->ts['tempname'] = $this->ts['cmd'] [ $this->ts['idx'] ] ;    
    $this->FillTs($this->temppath, $this->ts['tempname']);         
    $this->ts["signurl"] = LIB::SignUrl($this->ts);

    return $this->MakeJson();
  }
  
  /**
   * Next edited video in the queue
   * @return string[]|mixed[] 
  */
  public function Next(){    
    if( $this->ts["idx"] >= $this->ts["que"]) {
      return $this->MakeJson();
    }

    $this->ts["idx"]++;
    $this->ts['tempname'] = $this->ts['cmd'] [ $this->ts['idx'] ];            
    $this->FillTs($this->temppath,$this->ts['tempname']);    
    $this->ts["signurl"] = LIB::SignUrl($this->ts);

    return $this->MakeJson();    
  } 

  /** 
   * Stop the long video edit process
   */
  public function Cancel(){
    // delete the sign file  
    $ok = $this->DeleteSignFile();
    
    // delete the temporay files
    $filename = LIB::getFilename( $this->ts["name"] );   ;    
    array_map ( 'unlink', glob ( $this->temppath . $filename . "_#*" ) );       

    // Stop the ffmpeg process if it is still running    
    $ok = $this->killFFMpeg();
    return $this->MakeJson(); 
  }

  /**
   * Close the video edit, unset all the session variables and delete the temp files
   * @return array{id: string, msg: string}
   */
  public function Close(){    
    $ok = $this->DeleteSignFile();  
    
    // delete the temporay files       
    array_map ( "unlink", glob ( $this->temppath . "*" ) );     

    // Stop the ffmpeg process if it is still running    
    $this->killFFMpeg(); 

    // The session has to reset for the next video edit
    LIB::ResetEditSession( "video", $this->ts) ;    
    return ["ok" => "closed" ];
  } 

  /**
   * Delete the Sign File which sings the long process of video conversion
   * @return bool 
   */
  function DeleteSignFile(){
    $ok = true;
    if ( file_exists($this->temppath . $this->SignFile)) {      
      $ok = unlink($this->temppath . $this->SignFile);      
    }
    return $ok; 
  }
  /**
   * Stop the long process of video conversion by killing the ffmpeg process 
   * on Linux & Windows
   * @return string|false|null 
   */
  function killFFMpeg(){    
    // Leállítani az ffmpeg folyamatot, ha még futna    
    if(strtoupper(substr(PHP_OS,0,3)) == "WIN" ){
      $out = shell_exec("taskkill /F /IM ffmpeg.exe");
    } else {
      $out = shell_exec('pkill ffmpeg');
    } 
    return $out;       
  }
  
    /**
   * Fill the datas of the converted video
   * @param mixed $path 
   * @param mixed $name 
   * @return void 
   */
  function FillTs( $path, $name ){      
    $record["path"] = $path;
    $record["name"] = $name;
    $record["typ"]  = "videohtml5";
    $ex = new Exif( $record, $this->cfg, true );
    $ex->Videoinfo( $record );
        
    $this->ts["id"]       = $this->id;        
    $this->ts["name"]     = $record["name"];
    $this->ts["path"]     = $record['path'];
    $this->ts["ext"]      = $this->ext;
    $this->ts["newext"]   = $this->newext;    
    $this->ts["framerate"]= $record["framerate"];
    $this->ts["gop"]      = $record["gop"];
    $this->ts["width"]    = $record["width"];
    $this->ts["height"]   = $record["height"];
    $this->ts["clipstart"]= $record["clipstart"];
    $this->ts["clipend"]  = $record["clipend"];      
    $this->ts["duration"] = $record["duration"]; 
    $this->ts["cmd"][ $this->ts["idx"] ] = $this->ts["tempname"];
    // Data of original video file
    $this->ts["filesize"] = Lib::ShowFileSize( filesize( $path . $name ) );
    $this->ts["modified"] = date ( "Y.m.d H:i:s",filemtime($path . $name ) );   

    // sign url;
    $this->ts["signurl"] = LIB::SignUrl($this->ts);
    $this->ts["url"] = LIB::getTempUrl() . $this->ts["tempname"];    
    $this->ts["ok"] = "1";
  }

  /**
   * It makes slash from double slash or backslash
   * @param mixed $p 
   * @return string|string[] 
   */
  public function slash($p){
    $p .= substr( $p, - 1 ) != '/' ? "/" : '';    
    return str_replace( ['://','\\',"//"], [ "://", "/", "/"], $p);
  }  

  /**
   * Make a backup filename from original file
   * @param mixed $filename - filename
   * @param string $path - path
   * @return string 
   */
  public function BackupFileName( $filename, $path = "" ){  
    $bak = pathinfo($filename, PATHINFO_FILENAME)."_bak".".".pathinfo($filename, PATHINFO_EXTENSION);
    while (file_exists( $path . $bak ) ) {
      $bak = pathinfo($filename, PATHINFO_FILENAME)."_bak".rand(1000,9999).".".pathinfo($filename, PATHINFO_EXTENSION);
    }
    return $bak;
  }
 
  /**
   * Make and return json file to ajax calling
   *
   * @param string $i
   * @param string $e
   * @return string[]|mixed[]
   */
  function MakeJson($i = '', $e = '') {
    ksort($this->ts );
    $json = [];
    if ( empty( $i ) ) {            
      foreach($this->ts As $i => $e ){
        $json[ $i ] = $e;
      }
      
      if( $json["idx"] > 0 ){
        $json["prev"] = $json["idx"] - 1;
      }else{
        $json["prev"] = '';
      }
      
      if( $json["idx"] < $json["que"] ){
        $json["next"] = $json["idx"] + 1;
      }else{
        $json["next"] = "";
      }
    } else {
      $json[$i] = $e;
    }
    LIB::setSession( "smpl", $this->ts );
    return $json;
  } 
} 