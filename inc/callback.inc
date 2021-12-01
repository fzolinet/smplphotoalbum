<?php
/**
 * Callback functions call from that page tipical with ajax
 * @param int $id - smplphotoalbum.id
 */
/*
 * smplphotoalbum_view - increment the viewnumber
 */
function smplphotoalbum_view( $id=0 ){
  $sql = "SELECT viewnumber FROM {smplphotoalbum} where id= :id";
  $rs = db_query($sql, array(':id'=>$id));
  if($rs->rowCount() > 0 ){
    $viewnumber =  $rs->fetchColumn();
    _smplphotoalbum_event($id);
  }else{
    $viewnumber = 0;
  }
  echo $viewnumber;
	die();
}
/**
 *
 * @param int $id
 * @return string $viewnumber - number of opened the view
 */
function smplphotoalbum_increment_view( $path, $name ){
  $path .= substr($path,-1) !="/"? "/":"";
  $sql = "SELECT id, viewnumber FROM {smplphotoalbum} WHERE path = :path AND name =:name";
  $rs = db_query($sql, array(':path'=> $path, ':name' => $name));
  $db = $rs->rowCount();
  
  if($db<1){
    return '';
  }
  $record = $rs->fetchAssoc();
  
  $id         = $record['id'];
  $db = (int)$record['viewnumber'];
  $db++;

  db_update('smplphotoalbum')
		->fields(array('viewnumber' => $db))
		->condition('id',$id,'=')
		->execute();
		fz_die();
  _smplphotoalbum_event($id, "view");
	return $db;
}

function _smplphotoalbum_event($id, $event="view"){
  if(db_table_exists("smplphotoalbum_event") ){
    db_insert("smplphotoalbum_event")
    ->fields( array('sid'=>$id, "event"=> $event, "eventdate"=> time() ) )
    ->execute();
  }
}
/**
 * smplphotoalbum_vote - manage the vote and rank numbers
 * called from page with ajax
 * @param int $id - smplphotoalbum.id
 * @param int $rank - the given rank on the page
 * @return $out - Te new rank
 */
function smplphotoalbum_vote( $id, $rank){
	$out = _smplphotoalbum_vote($id,$rank);
	die($out);
}
/**
 *
 * @param int $id - smplphotoalbum.id
 * @param int $rank -the given rank on the page
 * @return string $out - the new rank
 */
function _smplphotoalbum_vote( $id, $rank){
	$sql = "SELECT id, votenumber, rank FROM {smplphotoalbum} where id= :id";
	$rs = db_query($sql, array(':id'=>$id));
	if($rs){
		$a = $rs->fetchAssoc();
		++$a['votenumber'];
		$newrank = (0+$a['rank']+ $rank);
		db_update('smplphotoalbum')
		->fields(array('votenumber' => $a['votenumber'],'rank'=>$newrank))
		->condition(' id',$id,'=')
		->execute();
		_smplphotoalbum_event($id,"vote");
		$out = sprintf("%02.2f",$newrank / $a['votenumber']) . " / ".$a['votenumber'];
	}else{
		$out = '-1';
	}
	return $out;
}

/**
 * Set deleted field in database
 *
 * @param int $id - smplphotoalbum.id
 * @return string $out - the new rank
 */
function smplphotoalbum_del( $id ){
	$out ='';
	if(smplphotoalbum_access()){
		$out = _smplphotoalbum_del($id);
	}
	die($out);
}

/**
 *
 * @param int $id
 * @return string
 */
function _smplphotoalbum_del( $id ){
		
  $ok = db_update('smplphotoalbum')
					->fields(array('deleted' => 1 ))
					->condition('id',$id,'=')
					->execute();
	return  ($ok>0) ? $id :'-1';
}
/**
 * Delete the image from database (WARNING!!!!!!!) and from hdd if can!
*/
function smplphotoalbum_kill($id){
	$out ='-1';
	if(smplphotoalbum_access()){
		$out = _smplphotoalbum_kill($id);
	}
	die($out);
}

function _smplphotoalbum_kill($id){
	$out = '-1';
	$sql = "SELECT id, path, name FROM {smplphotoalbum} where id= :id";
	$rs = db_query($sql, array(':id'=> $id));
	
	if($rs->rowCount()>0){
		$a = $rs->fetchAssoc();
		$path = $a["path"];

		$root = variable_get("smpl_root",'');
		$root = (substr($root,-1) == "/") ? substr($root,0,-1): $root;

		$path  = $a["path"];
		$path .= (substr($path,0,1) != "/") ? '/' : '';
		$path .= (substr($path,-1)  != "/") ? '/' : '' ;

		$name  = $a["name"];
		$tn  = $root.$path.TN.$name;
		
		//delete thumbnail if can
  	$ok = unlink($tn);
	
		//delete image if can
		$img =$root.$path.$name;
		if(file_exists($img)){
		  $ok = (unlink($img)?'1':'-1');
		}else{
		  $ok = 1;
		}
		
		if($ok == '1' ){
			$db = db_delete('smplphotoalbum')
					->condition('id',$id,'=')
					->execute();
      //if delete from taxonomy from taxonomy table
			if($db>0){
				$taxdb = db_delete('smplphotoalbum_taxonomy')
				        ->condition('sid',$id,'=')
				        ->execute();
				$out = 	'1';
			}else{
				$out = '-1';
			}
		}
	}
	return $out;
}

/**
 * Posted to Facebook
 * @param int $id
 * @param string $p
 */
function smplphotoalbum_fb($id,$p = "true"){
	$fb = variable_get("smpl_fb");
	if($fb){
	  $data = ($p =="true"?1:0);
		$num = db_update('smplphotoalbum')
					->fields(array('fb'=> $data))
					->condition('id',$id,"=")
					->execute();
	}
	die( ($p == "true")? "11":"00");
}

/**
 * Posted to google+
 * @param int $id
 * @param string $p
 */
function smplphotoalbum_googlep($id,$p = "true"){
  $googlep = variable_get("smpl_googlep");
  if($googlep){
    $data = ($p =="true"?1:0);
    $num = db_update('smplphotoalbum')
    ->fields(array('googlep'=> $data))
    ->condition('id',$id,"=")
    ->execute();
  }
  die( ($p == "true")? "11":"00");
}

/**
 * posted to twitter
 * @param int $id
 * @param string $fb
 */
function smplphotoalbum_twitterp($id,$p = "true"){
  $googlep = variable_get("smpl_twitter");
  if($googlep){
    $data = ($p =="true"?1:0);
    $num = db_update('smplphotoalbum')
    ->fields(array('twitter'=> $data))
    ->condition('id',$id,"=")
    ->execute();
  }
  die( ($p == "true")? "11":"00");
}

/**
 * simplelist of the NID
 * @param unknown $nid
 */
function smplphotoalbum_simplelist($nid){
  drupal_goto();
}

function smplphotoalbum_edithelp(){
  $out = "No Help";
  if(smplphotoalbum_access()){
    $out = _smplphotoalbum_edithelp();
  }
  die($out);
}

function _smplphotoalbum_edithelp(){
  global $language ;
  $lang_name = $language->language ;
  if($lang_name == "hu"){
    $path = drupal_get_path('module','smplphotoalbum')."/Olvassel.html";
  } else{
    $path = drupal_get_path('module','smplphotoalbum')."/Readme.html";
  }
  
  
  $out = file_get_contents($path);
  return $out;

}