<?php
/**
 * substitute
 */

/**
 * Ez vegzi [smpl|.....] tag behelyettesiteset.
 * Ez vegzi az alabbi taxonomy alapu behelyettesiteseket:
 *  [smplt|.....|taxes=>music, dance, ...] - A taxonomia kifejezesek felsorolva (,vel elvalasztva)
 * Egy lapon tobb elem is lehet
 * @param string - $str - A helyettesítendő szöveg. ebben kell keresni a tokent
 * $
 * @return mixed
 */
function smplphotoalbum_substitute($str, $filter, $format, $langcode) {
  global $base_root;
  static $r = 0;
  //A modul parametereinek inicializálása
  $params = array();
  _smpl_params_init($params);
  $ml = array();
  $mt = array();
  $mr = array();
  $is_smplfilter  =  preg_match("/\[smpl\|[^]]*\]/smx"   , $str, $ml); //Gallery from folder
  $is_smpltfilter =  preg_match("/\[smplt\|[^]]*\]/smx"  , $str, $mt); //taxomony gallery
  $is_rand        =  preg_match("/\[smplr\|[^]]*\]/smx"  , $str, $mr); //Random picture

  if( ! (( $is_smplfilter  !== 0) || ( $is_smpltfilter !== 0 && $params['taxonomy']) || ( $is_rand !== 0) ) ) {
    return $str;
  }
  
  # width =
  #
  # [smpl|path/to/gallery_folder|count of columns|count of rows|width of pictures|sort|order|caption|filter]
  # [smplt|path/to/gallery_folder|count of columns|count of rows|width of pictures|sort|order|caption|filter|taxonomies]
  # [smplr|path/to/gallery_folder|width of pictures] - random
  # [smplr] - from every path
  # no slashes around this path!
  
  //List of allowed parameters
  if($is_rand > 0 ){              //Random picture
   $db = $is_rand;
   $match = $mr[0];
  }else if($is_smplfilter > 0 ){
    $db = $is_smplfilter;
    $match = $ml[0];
  }else if($is_smpltfilter > 0 ){ //Ha taxonomy => hány darab filter kifejezés van (csak 1
    $db = $is_smpltfilter;
    $match = $mt[0];
    $params['taxes'] = array();   // if there is taxonomy page the taxonomia go into an array
    $params['taxes'][] = 'teszt';
  }
  //clean the match
  $match = strip_tags($match);
  $match = preg_replace('/\[/','', $match);
  $match = preg_replace('/\]/','', $match);

  // Modify the params with the actual attributes
  _smpl_params_change($match, $params);
  $smpltag = md5($match);

 //Language setting if must
  $lang = '';

  //smplphotoalbum_root checking
  $msg = _smpl_root_checking($params['root']);
  if(strlen($msg)>0 ){
    return ;
  }
  
  _smplphotoalbum_add_js($params);
  //Behelyettesítés
  if($is_rand){
    return preg_replace(
        "/\[smplr\|[^]]*\]/smx",
        smpl_randomimage($params),
        $str
    );
  }
  
  $cid = drupal_get_path_alias();
  $cid .= isset($_REQUEST['smplpage'])? "|smplpage|".$_REQUEST['smplpage']:"";
  $cid .= isset($_REQUEST['smpllist']) ? "|smpllist":"";

  //Makes Gallery from cache if there is
  $out = &drupal_static(__FUNCTION__);
  if(!isset($out)){
    if(false && $cache = cache_get( $cid,"cache_smplphotoalbum") ){
      $out    = $cache->data;
    } else{
      if($params['taxes'] !== false){
        $ImgList = new ImageListTax( $params );
        $out     = $ImgList->Render();
      }else{
        $ImgList = new ImageList($params);
        $out     = $ImgList->Render();
      }
      $out = nldel($out);
      cache_set($cid, $out,"cache_smplphotoalbum");
    }
  }
  $x = &drupal_static("smpl_text",$out);
  //Makes the first gallery
  if($params['taxes'] !== false){
    $taxes = &drupal_static('smpl_taxes', TRUE);
    $str   = preg_replace("/\[smplt\|[^]]*\]/simx", $out, $str, 1);
  }else{
    $taxes = &drupal_static('smpl_taxes', FALSE);
    $str   = preg_replace("/\[smpl\|[^]]*\]/simx" , $out, $str, 1);
  }
  return $str;
}

/**
 * Shows the table of images of a taxonomy
 * @return void|unknown|mixed|string
 */
function smplphotoalbum_taxonomy($tax = ''){
  die();
  $params = array();
  _smpl_params_init($params);

  //Ha van taxonomia oldal
  if(!$params['taxonomy']){
    drupal_set_message(t("There is not taxonomy enabled"));
    return t("There is not taxonomy enabled");
  }

  if(empty($tax)){
    drupal_set_message(t('There is no taxonomy parameter. The right syntax is: smplphotoalbum/taxonomy/&lt;').t('taxonomy term')."&gt;");
    return '';
  }
  $params['taxes'][0] = $tax;

  
  //Language setting if must
  $lang = '';

  //smplphotoalbum_root reading and checking
  $msg = _smpl_root_checking($params["root"]);
  if(strlen($msg)>0 ){
    return ;
  }

  _smplphotoalbum_add_js($params);
  //Load the image data from directory to an array jpg,jpeg, gif, bmp ...
 
  $ImgList = new ImageListTax( $params );
  $out     = $ImgList->Render();
  $x       = &drupal_static("smpl_text",$out);
  $taxes   = &drupal_static('smpl_taxes', TRUE);
  return $out;
}

/**
 * Initialize the substitute parameters
 * @param unknown $params
 */
function _smpl_params_init(&$params){
  global $base_url;
  $mp = drupal_get_path('module','smplphotoalbum');
  //Előző verzió update
  if($a = variable_get('smpl', false)){
    foreach($a AS $i => $e){
      variable_set("smpl_".$i, $e);
    }
    variable_del('smpl');
  }
  
  $params = array (
      'path'=>'',
      'cols'         => variable_get('smpl_cols',4),
      'rows'         => variable_get('smpl_rows',4),
      'width'        => variable_get('smpl_width',150),
      'mobile'       => false,
      'order'        => variable_get('smpl_order','asc'),
      'sort'         => variable_get('smpl_sort','filename'),
      'sub'          => variable_get("smpl_sub",1),
      'capt'         => variable_get("smpl_capt",1),
      'viewed'       => variable_get("smpl_viewed",1),
      'opened'       => variable_get("smpl_opened",1),
      'voted'        => variable_get("smpl_voted",1),
      'edit'         => variable_get("smpl_edit",1),
      'style'        => variable_get("smpl_class",''),
      'exif'         => variable_get("smpl_exif",1),
      'keywords'     => variable_get("smpl_keywords",0),
      'audio'        => variable_get('smpl_audio_checking'),
      'video'        => variable_get('smpl_video_checking'),
      'doc'          => variable_get('smpl_doc_checking'),
      'cmp'          => variable_get('smpl_cmp_checking'),
      'app'          => variable_get('smpl_app_checking'),
      'style'        => '',
      
      "fb"           => variable_get('smpl_fb'),
      "twitter"      => variable_get('smpl_twitter'),
      "googlep"      => variable_get('smpl_googlep'),
      
      'change_order' => variable_get('smpl_change_order', false),
      'filter'       => variable_get('smpl_filter'      , false),
      'url'          => variable_get('smpl_url'         , false),
      'target'       => variable_get('smpl_url_target'  , '_blank'),
      'taxonomy'     => variable_get('smpl_taxonomy'    , true),
      'taxes'        => false,
      'random'       => variable_get('smpl_random'      , true),
      'modulepath'   => $mp,
      'v'            => $base_url."/".$mp."/v.php?i=",
      'html5'        => variable_get('smpl_html5', true),
      'audiohtml5'   => variable_get('smpl_audiohtml5_extensions', SMPLHTML5AUDIOEXT),
      'videohtml5'   => variable_get('smpl_videohtml5_extensions', SMPLHTML5VIDEOEXT),
      'root'         => smpl_root()
  );
}

/**
 * change the actual parameters
 * @param unknown $params
 */
function _smpl_params_change($match, &$params){
  $match = str_replace(array("=&gt;", "&nbsp;",":"),array("=>", "","=>"), $match);
  $props = explode("|", $match);
 
  unset($props[0]);
  
  //Trim all of values of array
  foreach($props As $i =>$e){
    $props[$i] = trim($e);
  }
     
  $f = $props[1];
  unset($props[1]);
  if( $f <>'') 	$f = (substr($f,0,1) != '/'?'/' : '') . $f. ( substr($f,-1,1) =='/' ? '' :'/');
  else $f .= '/';
  
  $params['path'] = $f;
  //It works on all the parameters
  foreach($props as $val){
    //new parameter format: name => value.
    $tmp = preg_split('/=>/x', $val);
    $p   = strtolower(trim($tmp[0]));
    $v   = strtolower(trim($tmp[1]));
    if ( $v == "false") $v = false; 
    elseif ($v == "true") $v = true;
    
    switch($p){
      case 'cols'    : $params['cols']    = $v; break;
      case 'rows'    : $params['rows']    = $v; break;
      case 'width'   : $params['width']   = $v; break;
      case 'order'   : $params['order']   = $v; break;
      case 'sort'    : $params['sort']    = $v; break;
      case 'sub'     : $params['sub']     = $v; break;
      case 'capt'    : $params['capt']    = $v; break;
      case 'view'    :
      case 'viewed'  : $params['viewed']  = $v; break;
      case 'vote'    :
      case 'voted'   : $params['voted']   = $v; break;
      case 'edit'    : $params['edit']    = $v; break;
      case 'exif'    : $params['exif']    = $v; break;
      case 'keywords': $params['keywords']= $v; break;
      case 'audio'   : $params['audio']   = $v; break;
      case 'video'   : $params['video']   = $v; break;
      case 'doc'     : $params['doc']     = $v; break;
      case 'cmp'     : $params['cmp']     = $v; break;
      case 'app'     : $params['app']     = $v; break;
      case 'issocial': $params['issocial']= $v; break;     //Facebook share button
      case 'style'   : $params['style']   = $v; break;
      //7.x-1.7
      case 'change_order': $params['change_order'] = ( in_array($v, array(true, 'true','True', 'TRUE', 1, '1') ) ? true : false ) ; break;
      case 'filter'  : $params['filter']           = ( in_array($v, array(true, 'true','True', 'TRUE', 1, '1') ) ? true : false ) ; break;
      //7.x-v1.8
      case 'url '    : $params['url']              = ( in_array($v, array(true, 'true','True', 'TRUE', 1, '1') ) ? true : false ) ; break;
      case 'target'  : $params['target']           = $v; break;
      //7.x-v2.0
      case 'taxes':		//fill $params['taxes'] array with parameters...
        if(isset($params['taxonomy']) && $params['taxonomy']){
          $t = preg_split('/,/',$v);
          foreach($t AS $i => $e){
            $params['taxes'][$i] = trim($e);
          }
        }
        break;
      //7.x-v2.01
      case 'html5'   :
        $h = array(true, 'true','True', 'TRUE', 1, '1', 'on');
        $params['html5'] = (in_array($v, $h, True )? 1:0);
        break;
        //7.x-v.2.02
      case 'mobile'  :
        $h = array(true, 'true','True', 'TRUE', 1, '1');
        $params['mobile'] = (in_array($v, $h, True )? true : false); break;
    }
  }
  
  if(variable_get('smpl_mobile')){
    $params['mobile'] = smpl_is_mobile();
  }
}

/**
 * Delete the orphaned entries from database
 *
 */
function _smplphotoalbum_check_links() {
  variable_set('smplphotoalbum_check',False) ;
  $root  = smpl_root();
  $from  = variable_get('smpl_from',0);
  $count = variable_get("smpl_number_of_checking",2000);
      
  $sql= "SELECT * FROM {smplphotoalbum}";
  $rs = db_query_range($sql,$from,$count);
  $db = 0;
  $i = 0;
  while($row = $rs->fetchAssoc($rs)){
    $file= $root."/".$row["path"]."/".$row["name"];
    if(!file_exists($file)){
      $rs1 = db_delete("smplphotoalbum")->condition('id',$row['id'],'=');
      $db += $rs1->execute();
    }
    
    if( ($i % 100) == 0 ){
      drupal_set_message(t('Entries: checked:'). $i. t(', deleted from database sum: ').$db, "status");
    }
    $i++;
    $from++;
    variable_set('smpl_from',$from);
  }
}

/**
 * The root checking
 * @param unknown $root
 */
function _smpl_root_checking(&$root){
  $msg  ='';
  $msg .= (strlen($root)<1) ? t('The main folder of Simple Photoalbum has to set! Please fix it in /admin/config/fz/smplphotoalbum!'):'';
  $msg .= ($root == "/" || substr($root, 2)==":/" || substr($root, 2)==":\\" )? t('Are you sure, the main folder of Simple Photoalbum is equal the server root?'):'';
  $msg .= substr($root,-1,1) == "/" ? t("Simple Photoalbum Main folder must not end with '/'"):'';
  $msg .= !is_dir($root) ? 'There is not the smplphotoalbum root folder':'';

  if(strlen($msg)>0 ){
    $msg = $msg.t(' Please fix it in /admin/config/fz/smplphotoalbum');
    drupal_set_message($msg,'error');
    watchdog('Simple Photoalbum',$msg);
  }
  return $msg;
}

/**
 * Alowed parameters using in filter
 * @return multitype:string
 */
function smplphotoalbum_get_allowed_params(){
  $allowed_params = array(
      'path',        // path/to/your/gallery/folder/without/wrapping/slashes|
      'cols',        // If not set, the value set at /admin/settings/smplphotoalbum is used.
      'rows',
      // Width of individual thumbs. Height is calculated automatically, except in the case of square thumbs, where this will also be the height of the thumbs.
      // If not set, the value set at /admin/settings/smplphotoalbum is used.
      'width',
      // 'asc', 'desc' or 'random' , 'unsort' order.
      'order',
      // sort means images are sorted witch propertie, for exaple name
      'sort',
      'sub',          // Subscription
      'caption',      // Caption
      'viewed',       // Viewed
      'voted',        // Voted
      'edit',         //
      'exif',         // exif info
      'keywords',     // taxonomie keywords
      // true or false
      'audio',
      'video',
      'doc',
      'cmp',
      'app',
      'change_order',
      'filter',
      'url',
      'target',
      'mobile'
  );
  if(variable_get('smpl_taxonomy',false)) $allowed_params['taxonomy'] ='';

  return $allowed_params;
}
