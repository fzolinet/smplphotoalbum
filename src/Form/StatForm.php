<?php

/**
 * @file
 * Contains Drupal\smplphotoalbum\Form\MessagesForm.
 */
namespace Drupal\smplphotoalbum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

require_once \Drupal::service ( 'module_handler' )->getModule ( 'smplphotoalbum' )->getPath () . '/src/functions.php';
class StatForm extends FormBase {
  protected function getEditableConfigNames() {
    return [ 
        'smplphotoalbum.stat' 
    ];
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  public function getFormId() {
    return 'smplphotoalbum_stat';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    if (SMPLTEST) {
      drupal_flush_all_caches ();
      $con = \Drupal::database ();
      $db = $con->delete ( "watchdog" )->execute ();
      unset ( $_SESSION ["_symfony_flashes"] ["error"] );
    }
    $types = array (
        'all' => 'All',
        'app' => t ( "Application" ),
        'audio' => t ( "Audio" ),
        'cmp' => t ( 'Compressed' ),
        'doc' => t ( 'Document' ),
        'image' => t ( 'Image' ),
        'video' => t ( 'Video' ) 
    );
    
    $deftype = isset ( $_SESSION ["smpl_stat"] ["type"] ) ? $_SESSION ["smpl_stat"] ["type"] : "all";
    $form ["type"] = array (
        '#type' => 'select',
        '#title' => t ( 'Filter of types' ),
        '#options' => $types,
        '#default_value' => $deftype,
        '#required' => TRUE,
        '#attributes' => array (
            'onchange' => 'this.form.submit();' 
        ) 
    );
    
    $path = isset ( $_SESSION ['smpl_stat'] ['path'] ) ? $_SESSION ['smpl_stat'] ['path'] : "/";
    
    $form ['path'] = array (
        '#type' => 'textfield',
        '#title' => t ( 'Filter of path' ),
        '#default_value' => $path,
        '#size' => 128,
        '#maxlength' => 255,
        '#required' => False,
        '#description' => t ( "Filter of path." ),
        '#attributes' => array (
            'onchange' => 'this.form.submit();' 
        ) 
    );
    $length = isset ( $_SESSION ['smpl_stat'] ['length'] ) ? $_SESSION ['smpl_stat'] ['length'] : 100;
    
    $form ['length'] = [ 
        '#type' => 'select',
        '#title' => t ( 'Length of list' ),
        '#description' => t ( 'Length of list of images' ),
        '#options' => array (
            '10' => 10,
            '20' => 20,
            '100' => 100,
            '200' => 200,
            'All' => 'all' 
        ),
        '#default_value' => $length,
        '#required' => TRUE,
        '#attributes' => array (
            'onchange' => 'this.form.submit();' 
        ) 
    ];
    if ($length == "all")
      $length = 10000000;
    
    $form ['submit'] = array (
        '#type' => 'submit',
        '#value' => $this->t ( "Send" ),
        '#button_type' => "primary" 
    );
    
    // Pager header
    $header = array (
        'id' => array (
            'data' => ( string ) t ( 'ID' ),
            'field' => 'id' 
        ),
        'path' => array (
            'data' => ( string ) t ( 'Path' ),
            'field' => 'path' 
        ),
        'name' => array (
            'data' => ( string ) t ( 'Name' ),
            'field' => 'name' 
        ),
        'subtitle' => array (
            'data' => ( string ) t ( 'Subtitle' ),
            'field' => 'subtitle' 
        ),
        'typ' => array (
            'data' => ( string ) t ( 'Type' ),
            'field' => 'typ' 
        ),
        'viewnumber' => array (
            'data' => ( string ) t ( 'Viewnumber' ),
            'field' => 'viewnumber' 
        ),
        'link' => array (
            'data' => ( string ) t ( 'Link' ),
            'field' => 'link' 
        ) 
    );
    
    // Database query
    $con = \Drupal::database ();
    $qry = $con->select ( "smplphotoalbum", "s" );
    $qry->fields ( 's', [ 
        "id",
        "path",
        "name",
        "subtitle",
        "typ",
        "viewnumber",
        "link" 
    ] );
    if ($deftype != "all") {
      $qry->condition ( 'typ', $deftype, "LIKE" );
    }
    
    // Number of rows
    $db = $qry->countQuery ()->execute ()->fetchField ();
    
    $form ['db'] = array (
        "#type" => "label",
        "#title" => t ( "Number of rows: " ) . $db,
        '#default_value' => $db,
        '#disabled' => TRUE 
    );
    
    if (isset ( $_REQUEST ["order"] )) {
      $order = $_REQUEST ["order"];
      if (isset ( $_REQUEST ['sort'] )) {
        $sort = $_REQUEST ['sort'];
      } else {
        $sort = "asc";
      }
      $or = array (
          "id" => ( string ) t ( 'ID' ),
          "path" => ( string ) t ( 'Path' ),
          "name" => ( string ) t ( 'Name' ),
          "subtitle" => ( string ) t ( 'Sub' ),
          "typ" => ( string ) t ( 'Type' ),
          "viewnumber" => ( string ) t ( 'Viewnumber' ),
          "link" => ( string ) t ( 'Link' ) 
      );
      $id = array_search ( $order, $or );
      $header [$id] ["sort"] = $sort;
    }
    
    $tsort = $qry->extend ( 'Drupal\Core\Database\Query\TableSortExtender' )->orderByHeader ( $header );
    $pager = $tsort->extend ( 'Drupal\Core\Database\Query\PagerSelectExtender' )->limit ( $length );
    $rs = $pager->execute ();
    
    $form ['stat'] = array (
        '#markup' => t ( "Simple Photoalbum statistics" ) 
    );
    
    $rows = array ();
    foreach ( $rs as $id => $row ) {
      $rows [$row->id] = array (
          'id' => $row->id,
          'path' => $row->path,
          'name' => $row->name,
          'subtitle' => $row->subtitle,
          'typ' => $row->typ,
          'viewnumber' => $row->viewnumber,
          'link' => $row->link 
      );
    }
    $form ["stat"] ["table"] = array (
        "#type" => "tableselect",
        '#multiple' => False,
        "#header" => $header,
        "#options" => $rows,
        '#multiple' => False,
        "#empty" => $this->t ( 'No content has been found.' ) 
    );
    
    $form ['stat'] ['pager'] = array (
        '#type' => 'pager' 
    );
    
    // one record;
    $form ['record'] = [ 
        "#type" => "details",
        "#open" => True 
    ];
    $form ['record'] ["smplid"] = [ 
        "#type" => "textfield",
        "#title" => t ( "ID" ),
        "#size" => 10,
        "#maxlength" => 10,
        "#disabled" => true 
    ];
    $form ['record'] ['smplpath'] = [ 
        "#type" => "textfield",
        "#title" => t ( "Path" ),
        "#description" => t ( "The path of image. Can not change from here" ),
        "#size" => 30,
        "#maxlength" => 128,
        "#disabled" => true 
    ];
    $form ['record'] ["smplname"] = [ 
        "#type" => "textfield",
        "#title" => t ( "Filename" ),
        "#size" => 30,
        "#maxlength" => 128,
        "#description" => t ( "Filename. Can not change from here" ),
        "#disabled" => true 
    ];
    $form ['record'] ["smplsubtitle"] = [ 
        "#type" => "textfield",
        "#title" => t ( "Subscription" ),
        "#description" => t ( "Subscription of item" ) 
    ];
    
    $form ['record'] ["smpltype"] = [ 
        "#type" => "textfield",
        "#title" => t ( "Type of item" ),
        "#description" => t ( "Type of item" ),
        "#disabled" => true 
    ];
    
    $form ['record'] ["smplviewnumber"] = [ 
        "#type" => "textfield",
        "#title" => t ( "Viewnumber" ) 
    ];
    
    $form ['record'] ["smpllink"] = [ 
        "#type" => "textfield",
        "#title" => t ( "Link associated of item" ) 
    ];
    
    $form ['record'] ['modifyrecord'] = array (
        '#type' => 'button',
        '#value' => $this->t ( "Modify record" ),
        '#attribute' => array (
            'onclick="return false;"' 
        ) 
    );
    
    $form ['record'] ['ok'] = array (
        '#markup' => new \Drupal\Component\Render\FormattableMarkup ( '<div id="smpl_msg" class="js-form-item form-item js-form-type-textfield form-type-textfield">&nbsp;</div>', [ ] ) 
    );
    $js = $base_url . '/smplphotoalbum';
    
    $form ['js'] = array (
        '#markup' => new \Drupal\Component\Render\FormattableMarkup ( '<script> var smpl_ajax = "' . $js . '";</script>', [ ] ) 
    );
    
    $form ['#attached'] = array (
        'library' => array (
            'smplphotoalbum/smplphotoalbum-stat' 
        ),
        'drupalSettings' => array () 
    );
    
    return $form;
  }
  public function setMessage(array $form, FormStateInterface $form_state) {
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $length = $form_state->getValue ( "length" );
    if (! ($length == 'all' || (( int ) $length > 0))) {
      $form_state->setErrorByName ( 'length', $this->t ( 'Wrong type length parameter.' ) );
    }
    $type = $form_state->getValue ( "type" );
    if (! in_array ( $type, array (
        'all',
        'app',
        'audio',
        'cmp',
        'doc',
        'image',
        'video' 
    ) )) {
      $form_state->setErrorByName ( 'type', $this->t ( 'Wrong type items.' ) );
    }
    parent::validateForm ( $form, $form_state );
  }
  
  /**
   *
   * {@inheritdoc}
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $val = $form_state->getValues ();
    $path = $form_state->getValue ( "path" );
    $path = str_replace ( "\\", "/", $path );
    $path = substr ( $path, 0, 1 ) != "/" ? $path = "/" . $path : $path;
    $form ["path"] ['#default_value'] = $path;
    $_SESSION ['smpl_stat'] = $val;
  }
}