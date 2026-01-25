<?php
namespace Drupal\smplphotoalbum\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\InfoParser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\smplphotoalbum\Plugin\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\smplphotoalbum\Imagelist;

class SmplphotoalbumTest {
  private $mp; //Modulepath
  private $root; //The root folder of photoalbum
  private $cfg;
  private $filename = "00_smpl_testfile.jpg";
  
  function __construct(){
    $this->cfg = \Drupal::config ( 'smplphotoalbum.settings' );
    $this->root = $this->cfg->get ( 'root' );
    $this->root = str_replace ( "public://", \Drupal::service ( 'file_system' )->realpath ( "public://" ) . "/", $this->root );
    $this->root .= substr ( $this->root , - 1 ) != '/' ? "/" : '';
    $this->mp   = \Drupal::service ( 'module_handler' )->getModule ( 'smplphotoalbum' )->getPath ();
  }
  
  function test($path="/"){
    $response = new AjaxResponse();
    if(! $this->access() || $id == - 1) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }
    $params = $_SESSION['params'];
    $msg ="";

    //---------- copy testfile to the area ----------------
    $from = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $params['modulepath']. DIRECTORY_SEPARATOR ."test/".$this->filename;
    $to   = $this->root . $params['path']."/".$this->filename;
    $copymsg ="Test: Copy of '_smpl_test.jpg' file into folder: ";
    $ok = copy($from, $to);
    if($ok){
      $copymsg .="ok";
    }else{
      $copymsg .="failed";
    }
    $msg .= "<br>-->".$copymsg;
    
    //Test: There is in the databasa
    $ImgList = new ImageList( $params );

    //Check image in DB
    $msg .= "<br>-->Test: Image in database: ".$ImgList->testImageInDB($this->filename, "image", $params['path'] );

    //Make new Thumbnail
    $source = $this->root . $this->path . $params['path'] . $this->filename;
    $thumbnail = $this->root . $this->path . $params['path'] . $this->cfg->get("TN") . $this->filename;
    //   
    $msg .= "<br>-->Test: Make new Thumbnail: ".($ImgList->testMakeNewThumbnail( $this->filename, $source, $thumbnail, 150 )?"ok":"false");
    
    //Search the new rekord in the smplphotoalbum database table
    $con = \Drupal::database ();
    $rs = $con->select( "smplphotoalbum", "s" )
      ->fields("s",[
         'id',
         'path',
         'name',
         'subtitle',
         'typ',
         "viewnumber",
         'link'
        ])
      ->condition( "name", $this->filename, "=" )
      ->execute();
    
    $a = $rs->fetchAssoc();
    //$msg .= "<br>-->".$databasemsg;
    $content = json_encode( array(
        "id"       => $a["id"],
        "filename" => $a["name"],
        "subtitle" => $a["subtitle"],
        "type"     => $a["typ"],
        "msg"      => $msg
    ) );
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }
  
  
  function endtest(){
    $response = new AjaxResponse();
    if(! $this->access() || $id == - 1) {
      $response->addCommand( new InsertCommand( '', "-1", [] ) );
      return $response;
    }
    $params = $_SESSION['params'];
    $msg ="";
    
    $from = $this->root . $params['path']."/".$this->cfg["TN"]."/".$this->filename;
    $ok = unlink($from);
    $msg .="<br>-->Test: Testfile deleting from '".$from."': ". ($ok ?"ok":"failed");
    
    $tn = $this->root . $params['path']."/".$this->cfg->get('TN').$this->filename;
    $ok = unlink($tn);
    $msg .="<br>-->Test: Testfile thumbnail deleting from '".$tn."': ". ($ok ?"ok":"failed");
    
    
    $content = json_encode( array(
        "id"       => $a["id"],
        "filename" => $a["filename"],
        "subtitle" => $a["subtitle"],
        "type"     => $a["typ"],
        "msg"      => $msg
    ) );
    $response->addCommand( new InsertCommand( '', $content, [] ) );
    return $response;
  }
  /**
   *
   * @return boolean
   */
  private function access() {
    $roles = \Drupal::currentUser()->getroles();
    return in_array( 'administrator', $roles ) ? true : false;
  }
}