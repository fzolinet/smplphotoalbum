<?php
namespace Drupal\smplphotoalbum\Controller;

use FFMpeg\FFMpeg;
use Drupal\smplphotoalbum\Controller\Lib;

class AudioEdit {
  private $id = 0;
  private $path = '';
  private $name = '';
  private $type = 'audio';
  private $ext = '';
  private $newext = '';  
  private $clipstart = 0; // beginning of clip >=0
  private $clipend = 0;   // end of clip <= duration
  private $duration = 0;  // length of original video  
  private $cfg = [];      // smpl config;
  private $root = "";     // photoalbum source folder
  private $temppath = ""; // Temporary folder
  private $public;
  private $SignFile = 0;  // It sings the long process  
  private $words = [];   // Words for messages 
    
  // Session variables
  private $rq;
  private $sess;
  private $ts = []; // Session, as array
  public  BreakMedia $bt; // BreakTime class for long process
  public  Lib $lib; // Lib class for common functions
  public  $initialParams = [ "-qscale", 0 ];
  public  $mp = ""; // smplphotoalbum module path for ffmpeg default params file

  // Video2MP4 class implementation
  function __construct(
    $id    = 0,
    $path  = '', 
    $name  = '', 
    $type  = 'audio',
    $ext   = "mp3",   // what is the extension now
  )
  {                            
    $this->id      = $id;
    $this->path    = $path;
    $this->name    = $name; 
    $this->type    = $type;   
    $this->ext     = $ext;
    $this->newext    = Lib::Request('newext', $ext );
    $this->clipstart = (float) Lib::Request('clipstart', 0 );   // clip start time >=0  
    $this->duration  = (float) (Lib::Request('duration', 0 ));  // Length of video   ; 
    $this->clipend   = (float) Lib::Request('clipend', 0 );     // clip last time <= duration 
    $this->cfg     = LIB::getConfig();
    $root          = LIB::getConfig( 'root' );
    $lang          = LIB::getConfig( 'lang' );
    $this->words   = LIB::ReadWords($lang ); // Words for messages
    $this->root    = LIB::getRoot( $root );
    $this->mp      = \Drupal::service( 'module_handler' )->getModule( 'smplphotoalbum' )->getPath();

    // temppath for copy    
    $this->temppath = LIB::getTempPath();

    $this->path = LIB::slash( $this->root . $this->path );    
    $this->ts   = LIB::getSession( "smpl" );   //Session variables of video edit    
    
    // edit folder url to edit
    $this->ts['tempurl'] = LIB::getTempUrl();

    if (!isset($this->ts["tempname"])) $this->ts["tempname"] ="";
    $this->SignFile = Lib::Sign($this->ts["tempname"]);
    
    //Percentage of conversion 
    $this->bt  = new BreakMedia( $this->temppath, $this->SignFile, 10, false, true ); 
    $this->bt->setMax(100);  
  }

  /**
   * load datas of Audio edit file and send back to javascript
   * copy the audio file into the edit area
   * This is a long process
   */
  public function Load(){
    //A file adatok betöltése az adatbázisból
    $record = ['id'=>$this->id, 'path'=>$this->path, 'name' => $this->name, 'typ'=>$this->type];
    
    $ex = new Exif( $record  );
    $ex->AudioInfo( $record );

    //Reset the session variables
    Lib::ResetEditSession("audio", $this->ts );    
    
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
    $this->ts["clipstart"]= 0;
    $this->ts["clipend"]  = $record["clipend"];      
    $this->ts["duration"] = $record["duration"];
    $this->ts['params']   = '';

    // Data of original audio file
    $this->ts["filesize"] = Lib::ShowFileSize( filesize( $this->path . $this->ts["name"] ) );
    $this->ts["modified"] = date ( "Y.m.d H:i:s",filemtime($this->path . $this->ts["name"] ) );       
    $this->ts["audiokilobitrate"] = isset($record["bitrate"]) ? (int)( $record["bitrate"] /1024 ) : 256;

    // sign url;
    $this->ts["signurl"] = LIB::SignUrl($this->ts);
    $this->ts["url"] = LIB::getTempUrl() . $this->ts["tempname"];
    
    $this->ts["ok"] = 1;

    array_map ( "unlink", glob ( $this->temppath . "*" ) );

    //Copy the videofile
    
    $source = $this->path . $this->ts["name"];    
    $dest   = $this->temppath . $this->ts["tempname"];
    
    copy ( $source, $dest );    
    
    // Default parameters of ffmpeg for the original video file
    // $audio = FFMpeg::create(['temporary_directory' => $this->temppath])->open( $dest );    
    //

    LIB::setSession( "smpl", $this->ts );  // Set the session of movie edit
    unset($record['path']); 
    
    return $this->MakeJson();    
  }
  
  /**
   * Audio conversion
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
       
    $audio = $ffmpeg->open( $this->temppath . $this->ts["tempname"] );    
 
    // clip audio if needed
    if( ($this->clipstart > 0 || $this->clipend > 0) && ($this->clipstart < $this->clipend ) ){      
       $audio->filters()
        ->clip(
          \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clipstart),
          \FFMpeg\Coordinate\TimeCode::fromSeconds($this->clipend - $this->clipstart),
        );            
    } 
     
    //Syncronize the filters after all changes
    //$audio->filters()->synchronize();    

    // The new or same extension of video after conversion
    if( $this->ext !== $this->newext && !empty($this->newext) ){ 
      $newname   = LIB::NewName( $this->ts["name"], $this->ts['idx'] , $this->newext);      
    } else{
      $newname   = LIB::NewName( $this->ts["name"], $this->ts['idx'] );
      $this->newext = $this->ext;
    }

    switch( $this->newext ){
      case "mp3":
        $format = new \FFMpeg\Format\Audio\Mp3();       
        break;
      case "flac":
        $format = new \FFMpeg\Format\Audio\Flac();
        break;
      case "ogg":
        $format = new \FFMpeg\Format\Audio\Vorbis();
        break;
      case "aac":
        $format = new \FFMpeg\Format\Audio\Aac();
        break;
      default:
        $format = new \FFMpeg\Format\Audio\Wav();
    }
    
    // Audio bitrate change if needed    
    $reqaudiokilobitrate =
    
    $kbrate = LIB::Request("audiokilobitrate", '-1');
    if( $kbrate < 1){
      if( isset( $this->ts["audiokilobitrate"] ) ) {
        if( $this->ts["audiokilobitrate"]  < 1 ) {
          $this->ts["audiokilobitrate"] = 256;      
        }
      } else{
        $this->ts["audiokilobitrate"] = 256;
      }        
    }else{      
      $this->ts["audiokilobitrate"] = $kbrate;
    }
    $format->setAudioKiloBitrate( $this->ts["audiokilobitrate"] );          
    
    //
    $defaultParams = LIB::Request("params", "");
    if( strlen($defaultParams ) > 0) {
      $defaultParams  = $this->changeFFMpegParams($defaultParams);
      $audio->setDefaultSettings($defaultParams );
    }       
    
    //Percentage of conversion       
    
    $this->bt  = new BreakMedia( 
      $this->temppath,
      $this->SignFile, 
      1000, 
      false, 
      true 
    ); 
    $this->bt->setMax(100);
    
    // You can use this callback to send a message to the user about the progress of the conversion      
    $format->on('progress', function ($audio, $format, $percentage) {
      $this->bt->Break( $percentage );
    });        

    $this->ts["msg"] = "";
    $ok = 1;

    //conversion start
    try {
      $audio->save($format, $this->temppath . $newname);                   
      $this->ts["tempname"] = $newname;
      $this->ts["url"] = LIB::getTempUrl() . $newname; 
      $this->FillTs( $this->temppath, $newname );
      LIB::setSession("smpl", $this->ts);

      $this->ts["cmd"][$this->ts["idx"] ] = $this->ts["tempname"];
      //
      if( empty( $defaultParams ) ){
        $defaultParams = $audio->getDefaultSettings(true);
      } else{
        if( is_array($defaultParams) ){
          $defaultParams = implode(" ", $defaultParams);
        }
      }
      $this->ts["params"] = $defaultParams;
      //
      $this->ts["msg"] .= "Conversion successful from '<b>$this->path . $newname</b>'.";  
    } catch (\Exception $e) {     
      if(!file_exists($this->temppath . $this->SignFile)){
        $this->ts["msg"] = "Conversion cancelled by the user!";
        $this->ts["ok"] = 1;       
      } else{
        $this->ts["msg"] = "Conversion cancelled by the user or failed because of FFMpeg problem! Error message: " . $e->getMessage(); 
        $this->ts["ok"] = -1;
      }           
    }
    
    Lib::DeleteSignFile($this->temppath, $this->SignFile); 

    return $this->MakeJson();
  }

  /**
   * Empty the first parameters from ffmpeg command  
   * @param string $params - whole ffmpeg parameter string or array of parameter strings
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
   * Save the modified audio file to the original place
   * @param $idx - the index of the converted audio file in the queue
   * @return array{id: string, msg: string} 
   */
  public function Save($idx = 0){
    
    if( $idx < 1 && $idx >= $this->ts["que"]) {
      $this->ts['msg']  = "Original file can not save or does not exist converted audio file (index: $idx >= $this->ts['que']).";
      $this->ts['ok'] = "-1";
      return $this->MakeJson(); 
    }
        
    if( !empty( $this->newext ) && $this->newext != $this->ext ) {
      $from = $from = $this->temppath . $this->ts['cmd'] [ $idx ];
      $to = $this->path . LIB::ChangeExtension( $this->name, $this->newext );
      $ok = copy ( $from, $to ); // $overwriting the original file with the converted file        
    }else{
      $from = $this->temppath . $this->ts['cmd'] [ $idx ];    
      $to = $this->path . $this->name;
      $bak = $this->path . $this->BackupFileName( $this->name, $this->path);     
      // Develop time has to backup the original file
      $ok = rename ( $to, $bak ); // backup the original file before overwriting
      $ok = $ok && copy ( $from, $to ); // $overwriting the original file with the converted file
    }
            
    if (!$ok) {
      $this->ts['msg'] = Lib::tr(
        "Error saving the: '<b>%1</b>' audio file.", 
        $this->words, 
        [ "%1" => $from ] 
      );      
      
      $this->ts['ok'] = -1;
    } else {
      $this->ts['msg'] = Lib::tr(
        "The audio file: '<b>%1</b>' was saved successfully.", 
        $this->words, 
        [ "%1" => $from ]  
      );
            
      $this->ts['ok'] = 1;
    }        
    return $this->MakeJson();    
  }
  
  /**
   * @param string $idx - index of the original file
   * @param string $newname - the new name of audio file
   * @return string[]|mixed[] 
   */
  public function SaveAs($idx = 0, $newname = "" ){
   
    if( $idx < 1 && $idx >= $this->ts["que"]) {
      $this->ts['msg']  = Lib::tr(
        "Original file can not save or does not exist converted audio file (index: %1 >= %2 ).",
        $this->words,
        ["%1" => $idx, "%2" => $this->ts['que'] ]
      );

      $this->ts['ok'] = "-1";
      return $this->MakeJson(); 
    }
    
    // Get the new name of audio file from request    
    if( empty( $newname ) ){
      $this->ts["ok"] = -1;
      $this->ts['msg']  = Lib::tr(
        "The new name of audio can not be empty!",
        $this->words
      );
      return $this->MakeJson();
    }
    
    $from = $this->temppath . $this->ts['cmd'] [ $idx ];
    $to = $this->path . $newname;
    if($newname == $this->name ) {      
      $this->ts["ok"] = -1;
      $this->ts['msg']  = Lib::tr(
        "The new name of audio can not be the same <b>'%1'</b>!",
        $this->words,
        ['%1' => $newname]
      );
      return $this->MakeJson();
    }

    if( file_exists($to )){
       $this->ts["ok"] = -1;
       $this->ts['msg']  = Lib::tr(
        "The video file (<b>'%1'</b>) exists in the original folder!", 
        $this->words,
        ['%1' => $to]
       );
       return $this->MakeJson();
    }
    
    $ok = copy ($from, $to);
    if (!$ok) {
      $this->ts['msg'] = Lib::tr(
        "Error saving the '<b>%1</b>' video file.",
        $this->words,
        ['%1' => $from]
      );
      $this->ts['ok'] = -1;
    } else {
      $this->ts['msg'] = Lib::tr(
        "'<b>%1</b>' video file saved successfully.",
        $this->words,
        ['%1' => $from]
      );

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
    $ok = Lib::DeleteSignFile($this->temppath, $this->SignFile); 
    
    // delete the temporay files
    $filename = LIB::getFilename( $this->ts["name"] );
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
    $ok = Lib::DeleteSignFile($this->temppath, $this->SignFile);  
    
    // delete the temporay files       
    array_map ( "unlink", glob ( $this->temppath . "*" ) );     

    // Stop the ffmpeg process if it is still running    
    $this->killFFMpeg(); 

    // The session has to reset for the next video edit
    LIB::ResetEditSession( "video", $this->ts) ;    
    return ["ok" => "closed" ];
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
    $ex = new Exif( $record,  true );
    $ex->AudioInfo( $record );
        
    $this->ts["id"]       = $this->id;        
    $this->ts["name"]     = $record["name"];
    $this->ts["path"]     = $record['path'];
    $this->ts["ext"]      = $this->ext;
    $this->ts["newext"]   = $this->newext;            
    $this->ts["clipstart"]= $record["clipstart"] = 0;
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