<?php
/**
 * Admin interface for Simple Photoalbum
 */
function smplphotoalbum_admin_settings()
{
  global $base_url;
  $check = variable_get('check',False);
  //Akarjuk-e ellenorizni, hogy az adatbazisban a linkek es a kepek jok-e?
  if($check){
    _smplphotoalbum_check_links();
  }

  $form = array();
  
  $form['smpl_cols'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Maximum number of table columns'),
      '#default_value' => variable_get('smpl_cols', 3),
      '#size'          => 2,
      '#maxlength'     => 2,
      '#description'   => t("The maximum number of columns in the table.")
  );
  
  $mobile = module_exists('mobile_detect') || module_exists('browscap');
  $form['smpl_mobile'] = array (
      '#type'          => 'checkbox',
      '#title'         => t('Change the number of columns if the device is mobile.'),
      '#default_value' => ($mobile ? variable_get('smpl_mobile', false): false ),
      '#disabled'      => (!$mobile ? 'disabled' : ''),
      '#description'   => t("It uses when installed the `<a href='https://www.drupal.org/project/mobile_detect' target='_blank'>Mobile Detect</a>` or `<a href='https://www.drupal.org/project/browscap' target='_blank'>Browscap</a>` module."),
  );
  
  $form['smpl_rows'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Maximum number of table rows'),
      '#default_value' => variable_get('smpl_rows', 5),
      '#size'          => 2,
      '#maxlength'     => 2,
      '#description'   => t("The maximum number of rows in the table.")
  );
  
  $form['smpl_width'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Maximum width of thumbnail (in pixels)'),
      '#default_value' => variable_get('smpl_width', 150),
      '#size'          => 11,
      '#maxlength'     => 11,
      '#description'   => t("Set the maximum width of thumbnails.")
  );
  
  $form['smpl_class'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Class of thumbnails'),
      '#default_value' => variable_get('smpl_class', 'smpl_image'),
      '#description'   => t("Class of thumbnails of simple photoalbum. You can use in the CSS file to format the table.")
  );
  
  $p = smpl_root();
  $form['smpl_root'] = array(
      '#type' => 'textfield',
      '#title' => t('Root folder of galleries'),
      '#required' => TRUE,
      '#default_value' => $p,
      '#description' => t('The root of photogalleries, somewhere in the filesystem.<br/>
                         If you use __DRUPALROOT__ then use the install folder of drupal in the path. <br/>
                         if you use __DOCUMENT_ROOT__ then use the $_SERVER["DOCUMENT_ROOT"] variable in the path.'),
  );
  
  $directaccess = variable_get('smpl_directaccess', FALSE);
  $form['smpl_directaccess'] = array(
    		'#type' => 'checkbox',
    		'#title' => t('Disable access direct the folder tree of Simple photoalbum?'),
    		'#required' => False,
    		'#default_value' => $directaccess,
    		'#description' => t("Disable access direct the root folder and the tree of folders of simple photoalbum with .htaccess file."),
  );
    
  $htaccess= "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006\nOptions None\nOptions +FollowSymLinks";
  $form['smpl_htaccess'] = array(
      '#type' => 'textarea',
      '#title' => t('To prevent the root folder of Simple photoalbum this text put into the .htaccess file.'),
      '#default_value' =>variable_get('smpl_htaccess', $htaccess),
      '#description' => t("To prevent the direct access the root folder of simple photoalbum put this text into the .htaccess file and copy it into the folder of simple photoalbum root folder."),
  );
  
  $form['smpl_rebuild'] = array(
      '#type' => 'checkbox',
      '#title' => t('Need the rebuild the pathes of menus?'),
      '#required' => False,
      '#default_value' => variable_get('menu_rebuild_needed', TRUE),
      '#description' => t("Need the pathes rebuild once after the smplphotoalbum module installed or updated."),
  );
  
  $form['smpl_direct'] = array(
      '#type' => 'checkbox',
      '#title' => t('Direct access of files '),
      '#required' => FALSE,
      '#default_value' => variable_get('smpl_direct', FALSE),
      '#description' => t("Direct access to files in the filesystem."),
  );

  $form['smpl_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Shows the url of associated with image?'),
      '#required' => False,
      '#default_value' => variable_get('smpl_url', false),
      '#description' => t("When it is on shows a button and click on it in a window shows the content of url."),
  );
  
  if(variable_get('smpl_url', false) ){
    $form['smpl_url_window'] = array(
        '#type' => 'select',
        '#title' => t('What is the default target of link'),
        '#default_value' => variable_get('smpl_url_target', '_blank'),
        '#required' => false,
        '#options' =>
        array('_blank'=> t('New blank window (_blank).'),
            '_self'   => t('The same window (_self).'),
            '_parent' => t('The calling window (_parent)'),
            'name'    => t('New window with a name: default(smplphotoalbum)'),
            'vote'    => t('Sort images by average of votes'),
            'view'    => t('Sort images by number of views'),
        ),
        '#description' => t("The content of link shows in this window."),
    );
  }else{
    $form['smpl_url_window']= array(
        '#type' => 'textfield',
        '#title' => t('What is the default target of link'),
        '#default_value' => variable_get('smpl_url_target', '_blank'),
        '#required' => false,
        '#description' => t("The content of link shows in this window."),
        '#attributes'  => array('readonly' => 'readonly'),
    );
  }
  
  $x = module_exists("taxonomy");
  if(!$x ){
    drupal_set_message(t('The Taxonomy module is not installed or disabled, install it and enable it and Simple Photoalbum can use the taxonomy albums'),'warning');
    variable_set('smpl_taxonomy',False );
  }
  
  $form['smpl_taxonomy'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable using the taxonomy Photoalbums?'),
      '#required' => False,
      '#default_value' => variable_get('smpl_taxonomy', false),
      '#description' => t("When it is on you can use photogalleries with taxonomy sentences. Using it with: [smpl|taxonomy => taxonomy name]"),
      '#disabled'    => !$x,
      '#attributes'  => (!$x ? array('readonly' => 'readonly'): array() ),
  );
    
  $form['smpl_taxonomy_vocabulary_machine_name'] = array(
      '#type' => 'textfield',
      '#title' => t('The vocabulary of taxonomy - machine name'),
      '#required' => False,
      '#default_value' => variable_get('smpl_taxonomy_vocabulary_machine_name', false),
      '#description' => t("The vocabulary of taxonomy - machine name. Don't use space in name"),
      '#attributes'  => (!variable_get('smpl_taxonomy', false) ? array('readonly' => 'readonly'): array() ),
  );
  
  $form['smpl_taxonomy_vocabulary_name'] = array(
      '#type' => 'textfield',
      '#title' => t('The vocabulary of taxonomy - human readable name'),
      '#required' => False,
      '#default_value' => variable_get('smpl_taxonomy_vocabulary_name', false),
      '#description' => t("The vocabulary of taxonomy - human readable name. You can use any characters"),
      '#attributes'  => (!variable_get('smpl_taxonomy', false) ? array('readonly' => 'readonly'): array() ),
  );
  
  //Social network share
  $form['smpl_social'] = array(
      '#type' => 'fieldset',
      '#title' => t('Social network sharing'),
      '#collapsible' => true,
      '#collapsed' => true,
      '#description' => t("You enable/disable the share for the social networks"),
  );
  
  $form['smpl_social']['fb'] = array(
      '#type' => 'checkbox',
      '#title' => t('OpenGraph metatag for Facebook sharing'),
      '#default_value' => variable_get('smpl_fb', false),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => t("If set it can use OpenGraph protocol for the share to Facebook. It can set by hand or the first picture will be that one."),
  );

  $form['smpl_social']['twitter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Metatag for Twitter sharing'),
      '#default_value' => variable_get('smpl_twitter', false),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => t("Experimental! If set it can use the Twitter protocol to share the side. It can set by hand or the first picture will be that one."),
  );

  $form['smpl_social']['googlep'] = array(
      '#type' => 'checkbox',
      '#title' => t('Metatag for Google+ sharing'),
      '#default_value' => variable_get('smpl_googlep', false),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => t("Experimental! If set it can use the Google+ protocol to share the side. It can set by hand or the first picture will be that one."),
  );
  
  $form['view'] = array(
      '#type' => 'fieldset',
      '#title' => t('Viewing options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
  );
  
  $form['view']['smpl_viewed'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the number of views of files'),
      '#default_value' => variable_get('smpl_viewed', TRUE),
      '#description' => t("Output the number of views of files"),
  );

  $form['view']['smpl_voted'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the number of votes of files'),
      '#default_value' => variable_get('smpl_voted', TRUE),
      '#description' => t("Output the number of votes of files"),
  );

  $form['view']['smpl_rank'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the rank of files'),
      '#default_value' => variable_get('smpl_rank', TRUE),
      '#description' => t("Output the rank of files"),
  );

  $form['view']['smpl_exif'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the EXIF informations of files'),
      '#default_value' => variable_get('smpl_exif', TRUE),
      '#description' => t("Output the width and height of the file"),
  );
  
  $form['view']['smpl_sub'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the subtitles of files'),
      '#default_value' => variable_get('smpl_sub', TRUE),
      '#description' => t("If it is true output the subtitle of files if exists, othervise output the name of file"),
  );

  $form['view']['smpl_capt'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the caption of files'),
      '#default_value' => variable_get('smpl_capt', TRUE),
      '#description' => t("Output the name of files in title attribute of file"),
  );

  $form['view']['smpl_url'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the URL of files'),
      '#default_value' => variable_get('smpl_url', TRUE),
      '#description' => t("Output the url of files"),
  );
  $form['editing'] = array(
      '#type' => 'fieldset',
      '#title' => t('Editing the list and files'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
  );

  $form['editing']['smpl_edit'] = array(
      '#type' => 'checkbox',
      '#title' => t('You can edit the properties of the files (name, caption, etc, taxonomy, url) etc.'),
      '#default_value' => variable_get('smpl_edit', TRUE)
  );

  $form['editing']['smpl_delete'] = array(
      '#type' => 'checkbox',
      '#title' => t('You can delete the actual file'),
      '#default_value' => variable_get('smpl_delete', TRUE)
  );
  
  $imv = "<br>Image Magic library: ";
  if(extension_loaded("imagick")){
    $im   = new Imagick();
    $v    = $im->getVersion();
    $imv .= "<b>".$v['versionString']."</b>";
  }else{
    $imv .= "Not installed";
  }

  $gdv = "<br>GD library: ";
  if(extension_loaded("GD")){
    $gd   = gd_info();
    $gdv .= $gd['GD Version'];
  }else{
    $gdv .= "Not installed";
  }

  $form['editing']['smpl_imgedit'] = array(
    		'#type' => 'checkbox',
    		'#title' => t('You can edit the pictures on the web'),
    		'#default_value' => variable_get('smpl_imgedit', False),
      '#description'=> t('You can use the installed server side graphic manipulation system: ').$imv.$gdv
  );
  
  if( variable_get('smpl_imgedit', False) ){
    $p = str_replace("\\","/", DRUPAL_ROOT). "/" . variable_get("smpl_temp", variable_get("file_public_path") ."/smplphotoalbum/");
    if( !is_dir($p) ){
      $ok = drupal_mkdir($p,0777);
      if(!$ok){
        drupal_set_message( t("I can not make Smplphotoalbum temporary folder:") . variable_get("smpl_temp"),"warning");
      }else{

        if( ! is_writable($p) ){
          drupal_set_message( t('The Smplphotoalbum temporary folder not writeable and deleteable by Drupal!'), "warning");
        }
      }
    }else{
      if( ! _smpl_check_writeable($p) ){
        drupal_set_message( t('The Smplphotoalbum temporary folder not writeable and deleteable by Drupal!'), "warning");
      }
    }
  }
  
  $form['editing']['smpl_temp'] = array(
      '#type' => 'textfield',
      '#title' => t('Simple Photoalbum temporary folder!'),
      '#default_value' => variable_get('smpl_temp', variable_get("files_public_path")."/smplphotoalbum/"),
      '#description' => t("This folder has to can access from web and it has to be writeable and readable by Drupal."),
      '#disabled' => ! variable_get('smpl_imgedit', False),
  );

  $cb = module_exists('colorbox');
  if($cb){
    $form['colorbox'] = array(
        '#type'        => 'fieldset',
        '#title'       => t('Colorbox settings'),
        '#collapsible' => TRUE,
        '#collapsed'   => TRUE,
    );
    
    $form['colorbox']['smpl_overbrowser'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Colorbox overlay browser'),
        '#default_value' => variable_get('smpl_overbrowser', TRUE),
        '#description'   => t('Do you want to use the Colorbox2 overlay browser (if installed of course).'),
    );

    $form['colorbox']['smpl_overbrowser_paging'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Colorbox overlay browser paging'),
        '#default_value' => variable_get('smpl_overbrowser_paging', true),
        '#description'   => t('Do you want to use Lightbox2 paging (must be installed Lightbox2).'),
    );

    $form['colorbox']['smpl_overbrowser_video'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Lightbox2 overlay browser for video'),
        '#default_value' => variable_get('smpl_overbrowser_video', true),
        '#description'   => t('Do you want to use Lightbox2 for videos (must be installed Lightbox2).'),
    );
  }
  
  $lb = (!$cb) && module_exists('lightbox2');

  if($lb){
    $form['lightbox2'] = array(
        '#type'        => 'fieldset',
        '#title'       => t('Lightbox2 settings'),
        '#collapsible' => TRUE,
        '#collapsed'   => TRUE,
    );

    $form['lightbox2']['smpl_overbrowser'] = array(
        '#type' => 'checkbox',
        '#title' => t('Lightbox2 overlay browser'),
        '#default_value' => variable_get('smpl_overbrowser', true),
        '#description' => t('Do you want to use the LightBox2 overlay browser (if installed of course).'),
    );

    $form['lightbox2']['smpl_overbrowser_paging'] = array(
        '#type' => 'checkbox',
        '#title' => t('Lightbox2 overlay browser paging'),
        '#default_value' => variable_get('smpl_overbrowser_paging', true),
        '#description' => t('Do you want to use Lightbox2 paging (must be installed Lightbox2).'),
    );

    $form['lightbox2']['smpl_overbrowser_video'] = array(
        '#type' => 'checkbox',
        '#title' => t('Lightbox2 overlay browser for video'),
        '#default_value' => variable_get('smpl_overbrowser_video', true),
        '#description' => t('Do you want to use Lightbox2 for videos (must be installed Lightbox2).'),
    );
  }
  
  variable_set('smpl_overbrowser', $lb || $cb );
  if($cb){
    variable_set('smpl_overbrowser_type',"cb");
  }else if($lb){
    variable_set('smpl_overbrowser_type',"lb");
  }else{
    variable_set('smpl_overbrowser_type',"");
  }
  
  $form['smpl_change_order'] = array(
      '#type' => 'checkbox',
      '#title' => t('change the images order'),
      '#default_value' => variable_get('smpl_change_order', TRUE),
      '#description' => t("If it is checked can change the sorting order of files"),
  );

  $form['smpl_order'] = array(
      '#type' => 'select',
      '#title' => t('Sort or randomize image order'),
      '#default_value' => variable_get('smpl_order', 'asc'),
      '#required' => TRUE,
      '#options' =>
      array('unsort'=> t('Unsorted'),
            'asc'  => t('Sort images by their file names alphabetically.'),
            'desc' => t('Sort images in reverse order by their file names alphabetically.'),
            'rand' => t('The order of your gallery images will always be randomized (on each page load or cache refresh).'),
      ),
      '#description' => t("Ascending, descending, random order or unsorted"),
  );

  $form['smpl_sort']= array(
      '#type' => 'select',
      '#title' => t('Which property of images the source of sorting (filename, etc...)'),
      '#default_value' => variable_get('smpl_sort', 'filename'),
      '#required' => TRUE,
      '#options' =>
      array('filename'=> t('Sort files by their file names.'),
            'size'    => t('Sort files by their size'),
            'date'    => t('Sort files by their dates.'),
            'caption' => t('Sort files by captions'),
            'vote'    => t('Sort files by average of votes'),
            'view'    => t('Sort files by number of views'),
      ),
      '#description' => t("Source of sorting"),
  );

  $form['smpl_filter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Can use a filter in lists'),
      '#default_value' => variable_get('smpl_filter', false),
      '#description' => t("If it is checked it can use a filter in lists"),
  );

  $form['checklinks'] = array(
      '#type' => 'fieldset',
      '#title' => t('Check and delete the wrong links from database'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
  );

  $form['checklinks']['smpl_check'] = array(
      '#type' => 'checkbox',
      '#title' => t('Check the link of files in the table'),
      '#default_value' => variable_get('smpl_check', 0),
      '#description' => t("Check and delete the wrong link of files from database."),
  );

  $db= db_query('SELECT count(id) AS db FROM {smplphotoalbum}')->fetchField();

  $form['checklinks']['smpl_from'] = array(
      '#type' => 'textfield',
      '#title' => t('From which item starts the checking.'),
      '#default_value' => variable_get('smpl_from', 0),
      '#description' => t("This number is changing in every run."." ". t("The number of records: ".number_format($db,0,'.',' ')))
  );

  $form['checklinks']['smpl_number_of_checking'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of items chekcing in one run'),
      '#default_value' => variable_get('smpl_number_of_checking', 2000),
      '#description' => t("If the number is too high the script stops with timeout"),
  );

  $form['smpl_keywords'] = array(
      '#type' => 'checkbox',
      '#title' => t('Insert the caption of files into the keywords meta tag.'),
      '#default_value' => variable_get('smpl_keywords',0)
  );

  $form['smpl_subtitle_change'] = array(
      '#type' => 'checkbox',
      '#title' => t('Change the subtitles automatically'),
      '#default_value' => variable_get('smpl_subtitle_change',1),
      '#description' => t("Changeable or deletable character in subtitles")
  );
  
  //Ezt itt ki kellene bővíteni!!!!
  $changeable_strings = variable_get('smpl_subtitle_change_text', '_ .jpg .png .exe .apk -');

  if(variable_get('smpl_audio_checking',0)){
    $astr = explode(' ',variable_get('smpl_audio_extensions', SMPLAUDIOEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }

  if(variable_get('smpl_audio_checking',0)){
    $astr = explode(' ',variable_get('smpl_audiohtml5_extensions', SMPLHTML5AUDIOEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }
  
  if(variable_get('smpl_video_checking', 0)){
    $astr = explode(' ',variable_get('smpl_video_extensions', SMPLVIDEOEXT));
    foreach($astr AS $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }
  if(variable_get('smpl_video_checking', 0)){
    $astr = explode(' ',variable_get('smpl_video_extensions', SMPLHTML5VIDEOEXT));
    foreach($astr AS $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }
  if(variable_get('smpl_doc_checking', 0)){
    $astr = explode(' ',variable_get('smpl_doc_extensions', SMPLDOCEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }

  if(variable_get('smpl_cmp_checking', 0)){
    $astr = explode(' ',variable_get('smpl_cmp_extensions', SMPLCMPEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }

  if(variable_get('smpl_app_checking', 0)){
    $astr = explode(' ',variable_get('smpl_app_extensions', SMPLAPPEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }

  if(variable_get('smpl_oth_checking', 0)){
    $astr = explode(' ',variable_get('smpl_oth_extensions', SMPLOTHEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }
   
  if(variable_get('smpl_dis_checking', 0)){
    $astr = explode(' ',variable_get('smpl_dis_extensions', SMPLDISEXT));
    foreach($astr as $i => $str ){
      if(!preg_match('/\.'.$str.'/i', $changeable_strings)){
        $changeable_strings .= ' .'.$str;
      }
    }
  }
  
  $form['smpl_subtitle_change_text'] = array(
      '#type'  => 'textfield',
      '#title' => t('Changeable or deletable subtitles with whitespace'),
      '#default_value' => $changeable_strings,
      '#description' => t("Changeable or deletable character in subtitles"),
      '#size' => 128,
      '#maxlength' => 1024,
  );


  $form['types_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('File types Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
  );
  
  $form['types_settings']['smpl_html5'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use HTML5 widgets for video and audio files'),
      '#description'   => t("checked: using HTML5 widgets for audio and video files, unchecked: use traditional links for use them. Default: checked"),
      '#default_value' => variable_get('smpl_html5', 1)
  );
  
  
  $form['types_settings']['smpl_audio_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lists the audio files'),
      '#default_value' => variable_get('smpl_audio_checking',0)
  );
  
  $form['types_settings']['smpl_audio_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of audio files'),
      '#default_value' => variable_get('smpl_audio_extensions', SMPLAUDIOEXT),
      '#description' => t("This is a list of extensions of audio files. Default is mp3"),
      '#attributes' => ( variable_get('smpl_audio_checking',0) ? array():array('readonly' => 'readonly')),
  );
  
  $form['types_settings']['smpl_audiohtml5_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of audio files for HTML5 player'),
      '#default_value' => variable_get('smpl_audiohtml5_extensions', SMPLHTML5AUDIOEXT),
      '#description'   => t("This is a list of extensions of audio files for HTML5 player. Default: wav mp3 ogg"),
      '#attributes'    => array('readonly' => 'readonly'),
  );

  $form['types_settings']['smpl_video_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lists the video files'),
      '#default_value' => variable_get('smpl_video_checking', 0)
  );
  
  $form['types_settings']['smpl_video_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of video files'),
      '#default_value' => variable_get('smpl_video_extensions', SMPLVIDEOEXT),
      '#description' => t("This is a list of extensions of video files."),
      '#attributes' => ( variable_get('smpl_video_checking',0) ?array():array('readonly' => 'readonly')),
  );
 
  $form['types_settings']['smpl_videohtml5_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of video files HTML5 player'),
      '#default_value' => variable_get('smpl_videohtml5_extensions', SMPLHTML5VIDEOEXT),
      '#description' => t("This is a list of extensions of HTML5 video files."),
      '#attributes' => array('readonly' => 'readonly'),
  );
  
  $form['types_settings']['smpl_doc_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lists the document files'),
      '#default_value' => variable_get('smpl_doc_checking', 0)
  );

  $form['types_settings']['smpl_doc_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of doc files'),
      '#default_value' => variable_get('smpl_doc_extensions', SMPLDOCEXT),
      '#description' => t("This is a list of extensions of doc files. Default only doc"),
      '#attributes' => ( variable_get('smpl_doc_checking',1) ?array():array('readonly' => 'readonly')),
  );
  
  $form['types_settings']['smpl_cmp_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lists the compressed files'),
      '#default_value' => variable_get('smpl_cmp_checking', 0)
  );

  $form['types_settings']['smpl_cmp_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of compressed files'),
      '#default_value' => variable_get('smpl_cmp_extensions', SMPLCMPEXT),
      '#description' => t("This is a list of extensions of compressed files."),
      '#attributes' => ( variable_get('smpl_cmp_checking',0) ?array():array('readonly' => 'readonly')),
  );
  
  $form['types_settings']['smpl_app_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lists the application files'),
      '#default_value' => variable_get('smpl_app_checking', 0)
  );

  $form['types_settings']['smpl_app_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of application files'),
      '#default_value' => variable_get('app_extensions', SMPLAPPEXT),
      '#description' => t("This is a list of extensions of application files."),
      '#attributes' => ( variable_get('smpl_app_checking',0) ?array():array('readonly' => 'readonly')),
  );
  
  $form['types_settings']['smpl_oth_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Lists the other files'),
      '#default_value' => variable_get('smpl_oth_checking', 0)
  );

  $form['types_settings']['smpl_oth_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of other files'),
      '#default_value' => variable_get('smpl_oth_extensions', SMPLOTHEXT),
      '#description' => t("This is a list of extensions of other types files."),
      '#attributes' => ( variable_get('smpl_oth_checking',0) ?array():array('readonly' => 'readonly')),
  );

  $form['types_settings']['smpl_dis_checking'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the list of prohibited files'),
      '#default_value' =>variable_get('smpl_dis_checking', 0)
  );

  $form['types_settings']['smpl_dis_extensions'] = array(
      '#type' => 'textfield',
      '#title' => t('List of extensions of prohibited files'),
      '#default_value' => variable_get('smpl_dis_extensions', SMPLDISEXT),
      '#description' => t("This is a list of extensions of application files."),
      '#attributes' => ( variable_get('smpl_dis_checking',0) ?array():array('readonly' => 'readonly')),
  );
  
  $ffmpeg = extension_loaded("ffmpeg");

  $form['types_settings']['smpl_ffmpeg_preview'] = array(
      '#type' => 'checkbox',
      '#title' => t('Make preview from mpeg files. php_ffmpeg module: '). ($ffmpeg ? t("is loaded"):t("is not loaded")),
      '#default_value' => variable_get('smpl_ffmpeg_preview', $ffmpeg && true),
      '#description' => t("If loaded php_ffmpeg module you can switch to make previes from videos."),
      '#attributes' => ( $ffmpeg ? array() : array('readonly' => 'readonly')),
  );
    
  $form = system_settings_form($form);
  
  return $form;
}
