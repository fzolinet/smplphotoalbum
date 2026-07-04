<?php

namespace Drupal\smplphotoalbum\Controller;
use Gemini;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;

Class AIGemini{
  private $p = "";
  private $cmd = "";
  private $words = [];

  public function __construct( string $path, string $cmd, array &$words ){
    $this->p = $path;
    $this->cmd = $cmd;
    $this->words = &$words;
  }

  /**
   * With google Gemini client
   * API key details
   * API Key: AIzaSyCIiBe91lTFiO1ioBU4iNBf3ChUW3iTFck
   * Name: Default Gemini API Key
   * Project name: projects/818815699870
   * Project number:818815699870
   * @return string
   */
  public function process(){    
    $id = "-1";
    $msg = "";     
    // Determine MIME type
    $ext = strtolower( pathinfo( $this->p, PATHINFO_EXTENSION) );    
    switch( $ext ){
      case 'jpg':
      case 'jpeg':
        $mimeType = MimeType::IMAGE_JPEG;
        break;
      case 'png':
        $mimeType = MimeType::IMAGE_PNG;
        break;
      case 'webp':
        $mimeType = MimeType::IMAGE_WEBP;
        break;
      case 'heic':
        $mimeType = MimeType::IMAGE_HEIC;
        break;
      case 'heif':
        $mimeType = MimeType::IMAGE_HEIF;
        break;
      default:
        $mimeType = 'unknown';
        break;
    }    

    if( $mimeType->value == 'unknown' ){      
      return ["ok" => -1, "msg" => $this->words["Unknown image type for AI recognition!"]];
    }

    $bytes = file_get_contents($this->p);

    if(empty( $bytes ) ){      
      return ["ok" => -1, "msg" => $this->words["The file is empty!"]]; 
    }

    if( $this->cmd == "recognition" ){
      
      $ask = $this->words["What is on the picture?"];

    } else if( $this->cmd == "check"){
      
      $ask = $this->words["Is there this image AI generated?"];

    } else{
      return ["ok" => -1, "msg" => $this->words["Unknown AI command!"]];
    }

    $yourAPIKey = "AIzaSyCIiBe91lTFiO1ioBU4iNBf3ChUW3iTFck";
    $client = Gemini::client( $yourAPIKey );  
    $result = $client
      ->generativeModel(model: 'gemini-2.5-flash')
      ->generateContent([
          $ask,
          new Blob( mimeType: $mimeType, data: base64_encode( $bytes ) )
        ]);    
    $msg = $result->text();    
    return ["ok" => 1, "msg" => $msg]; 
  }
}