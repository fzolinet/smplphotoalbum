<?php
/**
 * Edit the properties of image
 * called from page with ajax
 * - subtitle
 * - link of image
 * - taxonomy values
 * - Deleted (Show ? )
 * - Social
 * 	- FB
 * 	- Twitter
 * 	- Google+
 *
 * @param int id - smplpotoalbum.id
 * @param with POST or GET method: taxonomies with delimited with ","
 * @return json data
 */
function smplphotoalbum_edit($id='',$form=''){
  if(!smplphotoalbum_access() || empty( $id )){
    die('-1');
  }
  
  // Empty form
  if(!empty($form)){
    $out = _smpl_edit_form_load($id);
    die($out);
  }else{
    $out =array();
    //Subtitle change
    if(variable_get('smpl_subtitle_change',1) && isset($_REQUEST["smpl_sub"]) ){
      $sub  = trim($_REQUEST["smpl_sub"]);
      $out["sub"] = _smplphotoalbum_sub($id,$sub);
    }

    //Caption change
    if(variable_get('smpl_subtitle_change',1) && isset($_REQUEST["smpl_capt"]) ){
      $capt  = trim($_REQUEST["smpl_capt"]);
      $out["caption"] = _smplphotoalbum_caption($id,$capt);
    }

    //Link
    if(variable_get('smpl_url') && isset($_REQUEST["smpl_url"]) && !empty($_REQUEST["smpl_url"]) ){
      $url     = trim($_REQUEST['smpl_url']);
      $out["url"] = _smplphotoalbum_url( $id, $url);
    }

    //target change
    if(variable_get('smpl_url') && isset($_REQUEST['smpl_target']) && !empty($_REQUEST["smpl_target"] ) ){
      $target  = trim($_REQUEST['smpl_target']);
      $out["target"] = _smplphotoalbum_target( $id, $target);
    }

    //Delete
    if( variable_get('smpl_delete') && isset($_REQUEST['smpl_deleted'] ) && $_REQUEST['smpl_deleted'] >0){
      $out["del"] = _smplphotoalbum_del($id);
    }

    //Taxonomy change
    if( variable_get('smpl_taxonomy')    &&
        isset($_REQUEST["smpl_stid"])    &&
        isset($_REQUEST["smpl_tax"])     &&
        isset($_REQUEST["smpl_desc"])
    ){
      _smplphotoalbum_taxonomy($id, $out);
    }
  }
  $json = json_encode($out);
  die($json);
}

/**
 * Edit form load
 * @param string $id - Smplphotoalbum.id
 * @return mixed
 */
function _smpl_edit_form_load($id=''){
  $mp  = drupal_get_path('module','smplphotoalbum');
  $str = file_get_contents($mp."/templates/edit_form.tpl");

  $str = str_replace('{{id}}'         , $id             , $str);
  $str = str_replace('{{Edit}}'       , t('Edit')       , $str);
  $str = str_replace('{{Properties}}' , t('Properties') , $str);
  $str = str_replace('{{Name}}'       , t('Name')       , $str);
  $str = str_replace('{{Value}}'      , t('Value')      , $str);
  $str = str_replace('{{Filename}}'   , t('Filename')   , $str);
  $str = str_replace('{{Subtitle}}'   , t('Subtitle')   , $str);
  $str = str_replace('{{Caption}}'    , t('Caption')    , $str);
  $str = str_replace('{{Deleted}}'    , t('Deleted')    , $str);
  $str = str_replace('{{Url}}'        , t('URL')        , $str);
  $str = str_replace('{{Target}}'     , t('Target')     , $str);
  $str = str_replace('{{Properties}}' , t('Properties') , $str);
  $str = str_replace('{{Taxonomy}}'   , t('Taxonomy')   , $str);
  $str = str_replace('{{Tax_descr}}'  , t('Taxonomy')." ".t('description'), $str);
  $str = str_replace('{{Add_tax}}'    , t('Add')                 , $str);
  $str = str_replace('{{Taxestitle}}' , t('List of taxonomies')  , $str);
  $str = str_replace('{{Addtaxtitle}}', t('Add a taxonomy'), $str);
  $str = str_replace('{{Taxonomies}}' , t('Taxonomies')    , $str);
  $str = str_replace('{{Delete}}'     , t('Delete')        , $str);
  $str = str_replace('{{Edit}}'       , t('Edit')          , $str);
  $str = str_replace('{{Save}}'       , t('Save')          , $str);
  $str = str_replace('{{Cancel}}'     , t('Cancel')        , $str);

  $mach_name = variable_get('smpl_taxonomy_vocabulary_machine_name','smpl_vocabulary');
  $sql       = "SELECT id, name, subtitle, type, votenumber, viewnumber, rank, caption, deleted, url, target FROM {smplphotoalbum} where id= :1";
  $rs1       = db_query($sql, array(':1' => $id));
  $fields    = $rs1->fetchAssoc();

  $str = str_replace('{{smpl_name}}'    , $fields['name']    , $str);
  $str = str_replace('{{smpl_sub}}'     , $fields['subtitle'], $str);
  $str = str_replace('{{smpl_capt}}'    , $fields['caption'] , $str);
  $str = str_replace('{{smpl_deleted}}' , $fields['deleted'] , $str);
  $str = str_replace('{{smpl_url}}'     , $fields['url']     , $str);
  $str = str_replace('{{smpl_target}}'  , $fields['target']  , $str);

  $r   = file_get_contents($mp."/templates/tax_row.tpl");
  $r   = str_replace('{{Delete}}'      , t('Delete'),$r);
  $r   = str_replace('{{Edit}}'        , t('Edit'),$r);
  $str = str_replace('{{smpl_new_row}}', $r, $str);

  // smplphotoalbum_taxonomy.stid, taxonomy_term_data.tid
  $sqltax = "SELECT stid, tid FROM {smplphotoalbum_taxonomy} WHERE sid= :sid";
  $rs = db_query($sqltax, array(':sid' => $id) );
  $tids = array();

  foreach($rs AS $record){
    $tids[$record->stid]= $record->tid;
  }

  $terms = taxonomy_term_load_multiple($tids);

  $str_tax='';
  foreach($tids AS $stid => $tid){
    $term = $terms[$tid];
    $one_row  = $r;
    $one_row = str_replace("{{tid}}" ,$tid              , $one_row);
    $one_row = str_replace("{{stid}}",$stid             , $one_row);
    $one_row = str_replace("{{tax}}" ,$term->name       , $one_row);
    $one_row = str_replace("{{desc}}",$term->description, $one_row);
    $str_tax .= $one_row;
  }

  $rs1 = db_query("SELECT max(stid) as maxstid FROM {smplphotoalbum_taxonomy}");
  $maxstid = $rs1->fetchColumn(0);
  $maxstid = $maxstid ? $maxstid : '0';
  $str = str_replace('{{smpl_max_tax}}' , $maxstid , $str);
  $str = str_replace('{{smpl_taxonomies}}' , $str_tax , $str);
  return $str;
}

function smplphotoalbum_sub($id,$sub){
  if (!smplphotoalbum_access() || empty($id) ){
    return '-1';
  }
  return _smplphotoalbum_sub($id, $sub);
}
/**
 * Modify the subtitle in database
 * @param int $id
 * @param string $sub
 * @return string - the solution
 */
function _smplphotoalbum_sub($id,$subtitle){
  $num = 0;
  if(variable_get('smpl_subtitle_change',1)){
    $a   = variable_get('smpl_subtitle_change_text','_ .jpg .png .gif .JPG .JPEG .GIF .PNG .MP3 .FLAC .FLA .WAV .wav .mp3 .');
    $chg = preg_split("/ /", $a);
    $sub = trim(str_ireplace($chg,' ',$subtitle));
    $num = db_update('smplphotoalbum')
      ->fields(array('subtitle'=> $sub))
      ->condition('id', $id,'=')
      ->execute();
  }
  return $sub;
}


/**
 * Modify the caption in database
 * @param int $id - smplphotoalbum.id
 * @param string $capt - Caption
 * @return string - the solution
 */
function _smplphotoalbum_caption($id,$capt =''){

  if(variable_get('smpl_subtitle_change',1)){
    $a = variable_get('smpl_subtitle_change_text','_ .jpg .png .gif .JPG .JPEG .GIF .PNG .mp3 .MP3 .exe .dll');
    $chg = preg_split("/ /", $a);
    $capt = trim(str_ireplace($chg,' ',$capt));
    $num = db_update('smplphotoalbum')
      ->fields(array('caption'=> $capt))
      ->condition('id', $id,'=')
      ->execute();
  }
  return $capt;
}

/**
 * Change the url and target data of item in database
 * @param int $id - smplphotoalbum.id
 * @param string $url - the nem url
 * @param string $target - the new target
 * @return -1 if not successfull or JSON data
 */
function _smplphotoalbum_url( $id, $url=''){
  if(!preg_match('#(f|ht)tps?://#', $url) ){
    $url = "http://".$url;
  }
  
  $num = db_update('smplphotoalbum')
    ->fields(array('url'=> $url))
    ->condition('id', $id,'=')
    ->execute();
  
  if ($num <1) $url = "-1";
  return $url;
}
/**
 *
 * @param int $id
 * @param string $target
 * @return string
 */
function _smplphotoalbum_target($id, $target=''){
  $t = db_select("smplphotoalbum","s")
  ->fields("s",array("target"))
  ->condition("id",$id,"=")
  ->execute()
  ->fetchAssoc();

  if($t["target"] != $target){
    $num = db_update('smplphotoalbum')
    ->fields(array('target' => $target))
    ->condition('id', $id,'=')
    ->execute();
    if($num<1) $target = "-1";
  }

  return $target;
}

/**
 * Make new taxonomies or update the database of taxonomies
 * @param int $id
 */
function _smplphotoalbum_taxonomy($id, &$out){

  $name = variable_get('smpl_taxonomy_vocabulary_machine_name','smpl_vocabulary');

  $voc = taxonomy_vocabulary_machine_name_load($name);	//Load the vocabulary

  // Taxonomy term data tábla azonosítói
  // Ha a tid>0, => új taxonómia megy a rekordhoz, de létezik a taxonómia
  // Ha tid =0 => ilyen taxonüómia nincsen fel kell venni a taxonüómia twerm data táblába és hozzáadni az image taxonomiához
  // ha tid<0, akkor az image-ről le kell venni a taxonómiát.

  $tids  = $_REQUEST['smpl_stid'];   //Taxonomy Term data fájl adatai
  $descs = $_REQUEST['smpl_desc'];
  $taxes = $_REQUEST['smpl_tax'];

  foreach($tids AS $i => $tid){
    $desc = $descs[$i];
    $tax  = $taxes[$i];
    if(!empty($tax)){
      //delete from smplphotoalbum_taxonomy the record. Not from taxonomy_term_data table!
      if($tid < 0 ){
        _smpl_delete_tax($tid, $id);
        $out["tid"][$i] = $tid;
      }else{
        // if must add new taxonomy to taxonomy_term_data table if,
        // and modify the record in smplphotoalbum_taxonomy
        $ok   = _smpl_modify_tax($id, $tid, $tax, $desc, $voc);
        	
        $out['tid'][$i] = $tid;
      }
    } //if(!empty()
  }
}

/**
 * Return the tid from term name
 * @param string $name - taxonomy name
 * @param objects $terms - array of taxonomy objects
 * @return int - taxonomy id
 */
function _smpl_get_tid_from_name($tax, $desc, $terms){
  foreach($terms as $tid => $term){
    if($term->name == $tax && $term->description == $desc){
      return $tid;
    }
  }
  return 0;
}
/**
 * Modify an existing taxonomy record in smplphotoalbum_taxonomy table
 * @param unknown $id   - smplphotoalbum.id
 * @param unknown $tid  - smplphotoalbum_taxonomy.tid = taxonomy_term_data.tid
 * @param unknown $tax  - taxonomy_term_data.name
 * @param unknown $desc - taxonomy_term_data.description
 * @param string $voc   - taxonomy_vocabulary record
 * @return string
 */
function _smpl_modify_tax($id, $tid, $tax, $desc, &$voc){
  //Megnézzük, hogy van-e ilyen taxonómiák között.
  $terms = taxonomy_get_term_by_name($tax,$voc->machine_name);

  //if(!isset($terms[$tid])){   fz_die($tid,2); }

  //if the taxonomy exists modify the parameters if must and the changes save into taxonomy_term_data table
  if(isset($terms[$tid])){
    $change = false;
    if( $terms[$tid]->name != $tax ){
      $terms[$tid]->name  = $tax;
      $change = true;
    }

    if( $terms[$tid]->description != $desc) {
      $terms[$tid]->description  = $desc;
      $change = true;
    }

    if ($change) $status = taxonomy_term_save($terms[$tid]);   //Menteni kellett a módositott kifejezest

    //if taxonomy does not exists make new object and save into the taxonomy_term_data table
  }
  else
  {
    $qry = db_select("taxonomy_term_data",'s')
    ->fields("s", array( "tid","vid","name"))
    ->condition("vid" , $voc->vid, "=")
    ->condition("name", $tax     , "=");
    $rs  = $qry->execute();

    if($rs->rowCount()< 1){
      $term = new stdClass();
      $term->vocabulary_machine_name = $voc->name;
      $term->tid         = '';
      $term->vid         = $voc->vid;
      $term->name        = $tax;
      $term->description = $desc;
      $term->format      = 'filtered_html';
      $term->weight      = 0;
      $term->parent      =  array(0);

      $status = taxonomy_term_save($term);
      //There was a new taxonomy_term_data record

      if($status == SAVED_NEW){
        $terms = taxonomy_get_term_by_name($tax,$voc->machine_name);
        $tid   = _smpl_get_tid_from_name($tax, $desc, $terms);      //I have to get the new tid.
      }else{
        $tid = 0;
      }
    }else{
      $tid = $rs->fetchColumn(0);
    }
  }
  //Van-e ennek az image-nek ilyen tid-ű taxonomiája?
  $qry = db_select("smplphotoalbum_taxonomy",'s')
  ->fields("s", array( "tid","sid"))
  ->condition("tid", $tid, "=")
  ->condition("sid", $id, "=");
  $rs = $qry->execute();

  $ok = false;
  // insert the new record into smplphotoalbum_taxonomy table
  if($rs->rowCount() < 1){
    $row = array('tid' => $tid, 'sid'=> $id);
    $ok = drupal_write_record("smplphotoalbum_taxonomy",$row);
  }
  return $ok;
}

/**
 * Add new taxonomy to taxonomy_term_data if must and add record to smplphotoalbum_taxonomy
 */
/**
 *
 * @param unknown $tax  - taxonomy text
 * @param unknown $desc - Taxonomy description
 * @param unknown $id   - smplphotoalbum_id
 * @param unknown $voc  - vocabulary
 * @return unknown
 */

function _smpl_new_tax($tax, $desc ,$sid, &$voc){
  //Megnézzük, hogy van-e ilyen taxonómiák között.
  $terms = taxonomy_get_term_by_name($tax,$voc->machine_name);

  if(  !$terms ){					//This will be a new taxonomy
    _smpl_new_taxonomy_in_taxonomy_term_data( $voc->vid, $tax, $desc);
    $terms = taxonomy_get_term_by_name($tax,$voc->vid);
  }
  $tid = _smpl_get_tid_from_name($tax,$terms); //get taxonomy

  //select from smpl_taxonomy table
  $rs = db_select('smplphotoalbum_taxonomy','s')
  ->fields("s", array( "id","tid", "sid"))
  ->condition('tid', $tid,'=')
  ->condition('sid',$sid,'=')  //sid inner join to smplphotoalbum.id= taxonomy
  ->execute();

  if(($rs->rowCount()) ==0){
    $lastid = db_insert("smplphotoalbum_taxonomy")
    ->fields(
        array(
            'tid'=> $tid,
            'sid' => $sid
        )
    )
    ->execute();
  }else{
    $row = $rs->fetchAssoc();
    $lastid = $row['id'];
  }
  return $lastid;
}

/**
 * Delete a taxonomy fromsmplphotoalbum_taxonomy
 */
function  _smpl_delete_tax($tid, $sid){
  $tid = abs((int) $tid);
  $qry = db_delete("smplphotoalbum_taxonomy")
  ->condition('tid',$tid)
  ->condition('sid', $sid, "=");
  $db= $qry->execute();
}

/**
 *
 * @param integer $vid - Vocabulary
 * @param string $tax - taxonomy text
 * @param string $desc - description
 * @return Ambigous <Status, If, void, boolean, string>
 */
function _smpl_new_taxonomy_in_taxonomy_term_data($vid, $tax, $desc){

  $term = (object)(array('vid'=>$vid, 'taxname'=>$tax, 'description'=>$desc, 'format'=> 'filtered_html', 'weight'=>0,'parent'=>0));
  $status = taxonomy_term_save($term);
  return $status;
}

/**
 * download the list of taxonomies on the page
 * called with ajax
 * @return string
 */
function smplphotoalbum_taxes(){
  $name = variable_get('smpl_taxonomy_vocabulary_machine_name','smpl_vocabulary');
  $voc = taxonomy_vocabulary_machine_name_load($name);	//Load the vocabulary

  $sql = "SELECT tid, name, description FROM {taxonomy_term_data} WHERE vid = ".$voc->vid;
  $rs = db_query($sql);

  $str = "<option value='0'>&nbsp;</option>\n";

  foreach($rs AS $record){
    $str .= "<option value='".$record->tid."'>".$record->name."|".$record->description."</option>\n";
  }
  die($str);
}