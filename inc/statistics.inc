<?php
function smplphotoalbum_stat(){
  global $base_path;
  $form = array();
  
  $rows = array(
      'all'   =>'All',
      'app'   => t("Application"),
      'audio' => t("Audio"),
      'cmp'   => t('Compressed'),
      'doc'   => t('Document'),
      'image' => t('Image'),
      'video' => t('Video')
  );
  
  // típus
  if(isset($_REQUEST['smpl_type_filter'] ) && ($_REQUEST['smpl_type_filter'] !='all') ) {
     $_SESSION['smpl_type_filter']= $_REQUEST['smpl_type_filter'];
  } else{
    $_SESSION['smpl_type_filter'] = 'all';
  }

 
  $js = '<script type="text/javascript" >
      var smpl  = new Array();
      smpl.ajax = "'.$base_path.'smplphotoalbum";
  </script>';
  drupal_add_js($js,'inline');
      
  $form['smpl_type_filter'] = array(
      '#type'          => 'select',
      '#title'         => t('Filter') .": ". t('Type'),
      '#description'   => t('Filter of types'),
      '#options'       => $rows,
      '#default_value' => 100,
      '#required'      => TRUE,
      '#attributes'    => array('onchange' => 'this.form.submit();'),
      '#weight'        => 100,
  );
  
  if(isset($_REQUEST['smpl_path_filter'] )) {
    if(($_REQUEST['smpl_path_filter'] !='all') ){
      $_SESSION['smpl_path_filter']= $_REQUEST['smpl_path_filter'];
    }else{
      $_SESSION['smpl_path_filter']= '';
    }
  }else{
    if(!isset($_SESSION['smpl_path_filter']) ){
      $_SESSION['smpl_path_filter'] = '';
    }
  }
    
  $form['smpl_path_filter'] = array(
      '#type'          => 'textfield',
      '#title'         => t('filter of path'),
      '#default_value' => $_SESSION['smpl_path_filter'],
      '#size'          => 128,
      '#maxlength'     => 255,
      '#required'      => False,
      '#description'   => t("The maximum number of columns in the table."),
      '#attributes'    => array('onchange' => 'this.form.submit();'),
      '#weight'        => 105,
  );
  

  $form['smpl_length'] = array(
      '#type'          => 'select',
      '#title'         => t('Length of list'),
      '#description'   => t('Length of list of images'),
      '#options'       => array('10'=>10, '20'=>20, '50' => 50, '100' => 100,'all'=>'all'),
      '#default_value' => 100,
      '#required'      => TRUE,
      '#attributes'    => array('onchange' => 'this.form.submit();'),
      '#weight'        => 110,
  );
  
  $header =array(
      'id'         => array('data' => t('ID')             , 'field' => 's.id'),
      'path'       => array('data' => t('Path')           , 'field' => 's.path'),
      'name'       => array('data' => t('Name')           , 'field' => 's.name'),
      'subtitle'   => array('data' => t('Sub')            , 'field' => 's.subtitle'),
      'type'       => array('data' => t('Type')           , 'field' => 's.type'),
      'votenumber' => array('data' => t('Votenumber')     , 'field' => 's.votenumber'),
      'viewnumber' => array('data' => t('Viewnumber')     , 'field' => 's.viewnumber'),
      'rank'       => array('data' => t('Rank')           , 'field' => 's.rank'),
      'filesize'   => array('data' => t('Filesize')       , 'field' => 's.filesize'),
      'thdate'     => array('data' => t('Thumbnail date') , 'field' => 's.thdate'),
  );
  
  $select = db_select("smplphotoalbum","s")
          ->extend('PagerDefault')
          ->extend('TableSort');

  // típus
  if(isset($_SESSION['smpl_type_filter'] ) && ($_SESSION['smpl_type_filter'] !='all') ) {
    $select->condition('s.type', $_SESSION['smpl_type_filter'] ,'=');
  }

  if(isset($_SESSION['smpl_path_filter'] ) && ($_SESSION['smpl_path_filter'] !='') ) {
    $pth = ( substr($_SESSION['smpl_path_filter'],1,1) !="/" )?"/".$_SESSION['smpl_path_filter']:$_SESSION['smpl_path_filter'];
    $pth .="%";
    $select->condition('s.path', $pth ,'LIKE');
  }
  
  $select->condition('s.deleted', 0,'=')
         ->fields('s', array('id','path', 'name', 'subtitle', 'type', 'votenumber', 'viewnumber', 'rank','filesize', 'thdate'));
    
  if(!isset($_REQUEST['smpl_length'])) {
    $select->limit(100);
  }elseif($_REQUEST['smpl_length'] == 'all'){
    $select->limit(10000000);
  }else{
    $select->limit($_REQUEST['smpl_length'] );
  }
  
  $select->orderByHeader($header);
  $rs  = $select->execute();
  
  $rowcount = $rs->rowCount();
  $id =-1;
  $logical = false;
  $rows = array();
  foreach ($rs AS $row ){
    $rows[$row->id] = array(
        'id'         => $row->id,
        'path'       => $row->path,
        'name'       => $row->name,
        'subtitle'   => $row->subtitle,
        'type'       => $row->type,
        'votenumber' => $row->votenumber,
        'viewnumber' => $row->viewnumber,
        'rank'       => $row->rank,
        'filesize'   => smpl_filesize($row->filesize),
        'thdate'     => date("Y.m.d H:i:s",$row->thdate)
    );
    
    if(!$logical) {
      $id = $row->id;
      $logical = true;
    }
  }
  
  $form['smpl_stat'] =array(
      '#title'              => t("Smplphotoalbum Statistics"),
      "#type"               => 'tableselect',
      '#header'             => $header,
      '#options'            => $rows,
      '#empty'              => t('No content available'),
      '#js_select property' => FALSE,
      '#multiple'           => FALSE,
      '#title_display'      => t("Smplphotoalbum Statistics"),
      '#weight'             => 120,
  );
  
  $form['pager'] = array(
      '#markup' => theme('pager'),
  );
 
  //id
  $form['smpl_id'] = array(
      '#type'        => 'textfield',
      '#title'       => t('Identifier'),
      '#description' => t('Identifies the file'),
      '#required'    => 'required',
      '#size'        => 20,
      '#maxlength'   => 20,
      '#default_value' => '',
      '#weight'        => 200,
  );

  $form['smpl_path'] = array(
      '#type'        => 'textfield',
      '#title'       => t('Path'),
      '#description' => t('where is the file in the Simple Photoalbum file structure'),
      '#required'    => 'required',
      '#size'        => 128,
      '#maxlength'   => 128,
      '#default_value' => '',
      '#weight'        => 210,
  );
  
  $form['smpl_name'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Filename'),
      '#description' => t('Name of file'),
      '#required'    => 'required',
      '#size'        => 128,
      '#maxlength'   => 256,
      '#default_value' =>'',
      '#weight'        => 220,
  );
  
  $form['smpl_subtitle'] =array(
      '#type'        => 'textfield',
      '#title'       => t('subtitle'),
      '#description' => t('Subtitle of file'),
      '#size'        => 128,
      '#maxlength'   => 256,
      '#default_value' =>'',
      '#weight'        => 230,
  );
  
  $form['smpl_caption'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Caption'),
      '#description' => t('Caption of file'),
      '#size'        => 64,
      '#maxlength'   => 256,
      '#default_value' => '',
      '#weight'        => 240,
  );
  
  $form['smpl_type'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Type'),
      '#description' => t('Type of file').' (image, audio, video, doc, cmp, app, etc.',
      '#required'    => 'required',
      '#size'        => 10,
      '#maxlength'   => 10,
      '#default_value' => '',
      '#weight'        => 240,
      
  );
  
  $form['smpl_votenumber'] =array(
      '#type'        => 'int',
      '#title'       => t('votenumber'),
      '#description' => t('Number of votes'),
      '#size'        => 10,
      '#maxlength'   => 10,
      '#default_value' =>0,
      '#weight'        => 250,
  );
  $form['smpl_viewnumber'] =array(
      '#type'        => 'int',
      '#title'       => t('Viewnumber'),
      '#description' => t('Number of views'),
      '#size'        => 10,
      '#maxlength'   => 10,
      '#default_value' =>0,
      '#weight'        => 260,
  );
  
  $form['smpl_rank'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Rank'),
      '#description' => t('Rank of file'),
      '#size'        => 10,
      '#maxlength'   => 10,
      '#default_value' =>'0',
      '#weight'        => 270,
  );

  $form['smpl_size'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Size'),
      '#description' => t('Size of file'),
      '#size'        => 10,
      '#maxlength'   => 10,
      '#default_value' =>'0 Byte',
      '#weight'        => 280,
  );
  
  $form['smpl_thdate'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Date of thumbnail'),
      '#description' => t('Date of thumbnail made'),
      '#size'        => 10,
      '#maxlength'   => 10,
      '#default_value' =>'',
      '#weight'        => 290,
  );
  
  $form['smpl_deleted'] =array(
      '#type'        => 'checkbox',
      '#title'       => t('Deleted').' '.t('file'),
      '#description' => t('Logical deleted. The file can not view in the lists.'),
      '#default_value' =>False,
      '#weight'        => 300,
  );
  
/*
 * twitter
 * googlep
 */
  
  $form['smpl_fb'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Facebok share'),
      '#description'   => t('It can Facebook OG:image metatag'),
      '#default_value' =>False,
      '#weight'        => 310,
  );
  
  $form['smpl_url'] =array(
      '#type'        => 'textfield',
      '#title'       => t('The URL of file'),
      '#description' => t('URL associated with file.'),
      '#size'        => 128,
      '#maxlength'   => 256,
      '#default_value' =>'',
      '#weight'        => 320,
  );
  $form['smpl_target'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Target of url'),
      '#description' => t('Target of URL in the lists'),
      '#size'        => 64,
      '#maxlength'   => 64,
      '#default_value' =>'_blank',
      '#weight'        => 320,
  );
  
  $form['smpl_price'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Price'),
      '#description' => t('The price of downloading this file. Default: 0'),
      '#size'        => 11,
      '#maxlength'   => 11,
      '#default_value' => 0,
      '#weight'        => 330,
  );
  
  $form['smpl_currency'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Currency'),
      '#description' => t('The currency of price. Default: "Ft", "€", "$"'),
      '#size'        => 3,
      '#maxlength'   => 3,
      '#default_value' => 'Ft',
      '#weight'        => 340,
  );
  
  $form['smpl_download'] =array(
      '#type'        => 'textfield',
      '#title'       => t('Download'),
      '#description' => t('Number of downloading of a user.'),
      '#size'        => 3,
      '#maxlength'   => 3,
      '#default_value' => 1,
      '#weight'        => 350,
  );
  
  $form['smpl_submit'] = array(
      '#title' => 'Submit',
      '#name'  => 'smpl_submit',
      '#value' => t('Save'),
      '#type'  => 'submit',
      '#submit'=> array('smplphotoalbum_smpl_file_save'),
      '#weight'        => 500,
  );

  $form['smpl_reset'] = array(
      '#type'  => 'submit',
      '#title' => 'Az űrlapról törli az adatokat',
      '#name'  => 'smpl_reset',
      '#value' => t('Reset'),
      '#submit'=> array('smplphotoalbum_smpl_reset'),
      '#limit_validation_errors' => array(),
      '#weight'        => 510,
      
  );
  
  $form['school_delete'] = array(
      '#type'  => 'submit',
      '#title' => 'smpl_delete',
      '#name'  => 'smpl_delete',
      '#value' => t('Delete'),
      '#submit'=> array('smplphotoalbum_smpl_file_delete'),
      '#weight'        => 520,
  );
  return $form;
}

function smplphotoalbum_record_load($id, $web = false){
  $rs = db_select('smplphotoalbum','s')
        ->fields('s',array('id','path','name','type','subtitle','votenumber','viewnumber','rank',
                   'caption','deleted','filesize','thdate','twitter','googlep','fb','url','target','price','currency','download'))
         ->condition('id',$id,'=')
         ->execute();
  $rowcount = $$rs->rowCount();
  if($rowcount >0) $row = $rs->fetchAssoc();
  
  if ($web){
    $json = json_encode($row);
    die($json);
  }
  return $row;
}