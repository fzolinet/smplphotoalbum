<?php
namespace Drupal\smplphotoalbum;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;

class SlideShow {
	protected $cfg;
	protected $root;
  protected $tpl = [];
	protected $interval = 10;
	protected $slide_checking = False;
	protected $slide_extension = array();
	protected $slide = False;
	protected $path = "";
	protected $i = 0;
	protected $id = 0;
	protected $db;
	protected $subtitle = "";
	protected $name     = "";
	protected $style    = "none";
  protected $params;
  protected $Items;
  protected $words;
	protected $modulepath ="";
	protected $serverName ="";

  function __construct( $path = "" ){
		$this->cfg  = \Drupal::config( 'smplphotoalbum.settings' );
		$this->root = $this->cfg->get( 'root' );
    if( !empty( $path ) ){
      unset( $_SESSION["slide"]);
      $_SESSION["slide"] ["path"] = $path;
			$_SESSION["slide"] ["id"]   = -1;
			$_SESSION["slide"] ["i"]		= -1;
    }
		$this->modulepath = \Drupal::service ( 'module_handler' )->getModule ( 'smplphotoalbum' )->getPath ();
		$p = realpath($this->modulepath."/templates");
		$this->tpl['slide']      = file_get_contents ( $p . "/slide.html.twig" );
		$this->tpl['slideimage'] = file_get_contents ( $p . "/slideimage.html.twig" );
  }

  /**
	* Make new slideShow 
	* Makes the session of the slideshow 
	* @param array $params 
	* @param array $Items 
	* @param mixed $tpl 
	* @return void 
	*/
  public function NewSlideShow( &$words, &$Items) {   
    $this->Items = $Items;
    $this->words = $words;
	
    $this->path  = $_SESSION["slide"]["path"];
		$this->id    = $_SESSION["slide"]["id"];		

    foreach( $Items AS $i => $Item){            
			$_SESSION["slide"] [$i] ["id"]       = (int) $Item->getId();
			$_SESSION["slide"] [$i] ['subtitle'] = $Item->getSubtitle();
      $_SESSION['slide'] [$i] ['name']     = $Item->getEntry();
    }

		// Choose randomly from actual image
    if( count( $Items ) > 0){
			$pics = $this->Pics();
			$this->i = rand(0 , count($pics));
      
			$this->id  = $pics[ $this->i ][ "id" ];

			// Set the actual datas of image      
			$_SESSION["slide"] ["i"]  = $this->i; 
			$_SESSION["slide"] ["id"] = $this->id; 
      $this->path     = $_SESSION["slide"] ["path"];
			$this->subtitle = $_SESSION["slide"] [$this->i] ['subtitle'];
      $this->name     = $_SESSION["slide"] [$this->i] ['name'];
      $this->db       = count( $pics );
    }
  }

  /**
   * Rendering a Slideshow.
	 * Itt Called from FilterSmplPhotoalbum.php
	 * 
   * @param array &$params 
   * @param array &$tpl 
   * @param string $name 
   * @param string $subtitle 
   * @param string $notes 
   * @return string|string[] 
   * @throws ContainerNotInitializedException 
   * @throws ServiceCircularReferenceException 
   * @throws ServiceNotFoundException 
   */
  function RenderSlideShow( array &$params, string $name, string $slide_subtitle, string $slide_notes){
    global $base_path, $base_url;
    $this->params   = $params;
    $this->interval = $params["interval"];
    $s = [];
		$r = [];
		$s["linksrc"] = "{{ linksrc }}";
		$r["linksrc"] = $base_url . "/smplphotoalbum/slide/";

		$s["title"] = "{{ title }}";
		$r["title"] = $name;

		$s["notes"] = "{{ notes }}";
		$r["notes"] = $slide_notes;

		$s["link"] = "{{ link }}";
		$r["link"] = $base_url . "/smplphotoalbum/slide/" . $this->id;

		$s["titleimg"] = "{{ titleimg }}";
		$r["titleimg"] = $this->name;

		$s["subtitle"] = "{{ sub }}";
		$r["subtitle"] = $slide_subtitle;

		$s["ajax"] = '{{ ajax }}';
		$r["ajax"] = $base_path . 'smplphotoalbum';

		$s["id"] = "{{ id }}";
		$r["id"] = $this->id;

		$s["interval"] = '{{ interval }}';
		$r["interval"] = $params["interval"] * 1000;

		$s["slidestyle"] = "{{ slidestyle }}";
		$r["slidestyle"] = $params["slidestyle"];

		$s["style"] = '{{ style }}';
		$r["style"] = $this->params["style"];

		$s["test"] = '{{ test }}';
		$r["test"] = $params["test"]== 1 ? 'true':'false';    

    // It makes url from actual url
		$requestUri = \Drupal::request()->getRequestUri();				
		$serverName = \Drupal::request()->server->get("SERVER_NAME");
		$this->serverName = $serverName;
		$scheme     = \Drupal::request()->server->get("REQUEST_SCHEME");		
		$port       = ":". \Drupal::request()->server->get("SERVER_PORT");

    if( ($scheme == "http"  && $port == ":80") || ( $scheme == "https" && $port == ":443") ){
			$port = "";
		} 

		$request = $scheme ."://". $serverName . $port . $requestUri;
		$request = ( explode("?", $request) )[0];
		
		$s["smplurl"]  = '{{ smplurl }}';
		$r["smplurl"] = str_replace( "&SmplSlide=1" , "", $request );
		
		$this->RenderSlideTnGet($s, $r);

		foreach( $this->words as $i => $e ) {
			$s [$i] = '{{ ' . $e . ' }}';
			$r [$i] =  $e;
		}
		$str = str_ireplace ( $s, $r, $this->tpl["slide"] );
		return $str;
  }

	/**
	 * Give back the previ | next |up |down or id image to the client
	 * @param string $cmd 
	 * @return AjaxResponse 
	 * @throws ContainerNotInitializedException 
	 * @throws ServiceCircularReferenceException 
	 * @throws ServiceNotFoundException 
	 * @throws BadRequestException 
	 */
  public function SlideGet( $cmd = ""){
  	//path of folder

		$response = new AjaxResponse();

		$pics = $this->Pics();

		$id = (int) ($this->Request("id",-1));

		// New ID by thumbnail
		if( $cmd == "click"){

			// Megadott ID-jű kép megkeresése
			$i = 0;			
			while( $i< count($pics) && $id != $pics[$i]["id"] ){
				$i++;
			} 

			if( ! ( $i < count($pics) )  ) {
				$i = 0;
			}
			$_SESSION["slide"]["i"]  = $i;
			$_SESSION["slide"]["id"] = $pics[$i] ["id"];

		} else{

			// New id by buttons left, right, up, dow			
			$i  = (int) $_SESSION["slide"]["i"];

			if( $cmd == "next" || $cmd == "down" ){
				$i++;
				if( !($i < count($pics) ) ){					
					$i = 0;				
				}		
				$_SESSION["slide"]["i"] = $i;
				$_SESSION["slide"]["id"] = $pics[$i]["id"];

			} else if($cmd == "prev" || $cmd == "up" ) {
				
				$i--;
				if( $i <= 0 ) {
					$i = count($pics)-1;
				}				
				$_SESSION["slide"]["i"] = $i;
				$_SESSION["slide"]["id"] = $pics[$i]["id"];
			}
		}

		$id = $_SESSION["slide"] ["id"];
	
    $json["id"]       = $id;
    $json["error"]    = "";
    $json['title']    = $pics[ $i ] ["name"];
    $json['subtitle'] = $pics[ $i ] ["subtitle"];
    $size             = GetImagesize(  $this->root . $_SESSION["slide"] ["path"]. $pics[ $i ] ["name"] );
    $json["width"]    = @$size[0];
    $json["height"]   = @$size[1];
    $json['ths']      = $this->SlideTnGet( $pics, $i );
    return $json;
  }

	/**
 	 * Original page thumbnails
	 * @return string 
	 */
	function RenderSlideTnGet(&$s, &$r){
		global $base_url;
		$pics = $this->Pics();
		$str = "";

		$idx = 0;
		while( $idx < count($pics) && $this->id != $pics[$idx]["id"]){
			$idx++;
		}
		
		$i = 0;
		for($j = $idx-4; $j < $idx + 5; $j++, $i++ ){			
			$s["id$i"]         = "{{ id$i }}";
			$s["linktn$i"]     = "{{ linktn$i }}";
			$s["titletn$i"]    = "{{ titletn$i }}";
			$s["tnsubtitle$i"] = "{{ slidetnsubtitle$i }}";
					
			try{
				$r["id$i"]         = $pics[$j]["id"];
				$r["linktn$i"]     = $base_url . "/smplphotoalbum/slide/" . $pics[ $j ][ "id" ] . "&tn=1";
			} catch( \Exception $e){
				fz_die( $pics );
			}
			
			$r["titletn$i"]    = $pics[$j]["name"];
			$r["tnsubtitle$i"] = $pics[$j]["subtitle"];
		}
		
		if($this->serverName == "localhost" ){
			print("<pre>");
			print_r($_SESSION["slide"]);
			print("</pre>");
		}		
	}

	/**
	 * Make Thumbnails images
	 * @param array $pics 
	 * @param int $idx 
	 * @return string 
	 */
  public function SlideTnGet( array $pics , $idx = -1){
    global $base_url;		
				
		// Searching the place of ID in array
		$start = $idx - 4;
		$end   = $idx + 4;		
		$c = count($pics);
		$ths = [];
		$j = 0;
		for($i0 = $start; $i0 <= $end; $i0++){
			if( $i0 <0 )			$i = $c + $i0 -1;
			else if($i0>$c-1)	$i = $i0 % $c;
			else 							$i = $i0;

			$ths[$j]["id"]       = $pics[ $i ]["id"];
			$ths[$j]["link"]     = $base_url . "/smplphotoalbum/slide/" . $pics[ $i ]["id"] . "&tn=1";
			$ths[$j]["title"]    = $pics[ $i ]["name"];
			$ths[$j]["subtitle"] = $pics[ $i ]["subtitle"];						
			$j++;
		}
		return $ths;		
  }

	  /**
   * Request GET / POST Parameters from Browser
   * 
   * @param mixed $key
   * @param string $method
   * @return string
   */
  function Request($key, $default = '' ){    
    return  \Drupal::request()->get($key, $default );  // $_GET / $_POST    
  }

	/**
	 * Copy to array pics from session
	 * @return array mixed
	 */
	function Pics(){
		$pics = $_SESSION["slide"];
		unset ($pics["id"], $pics["path"], $pics["i"], $pics["actual"]);
		return $pics;
	}
}