<?php

/**
 * @file
 * Contains Drupal\smplphotoalbum\Form\MessagesForm.
 */
namespace Drupal\smplphotoalbum\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;

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
    $types = array (
        'all' => 'All',
        'app' => $this->t ( "Application" ),
        'audio' => $this->t ( "Audio" ),
        'cmp' => $this->t ( 'Compressed' ),
        'doc' => $this->t ( 'Document' ),
        'image' => $this->t ( 'Image' ),
        'video' => $this->t ( 'Video' ) 
    );
    
    $deftype = isset ( $_SESSION ["smpl_stat"] ["type"] ) ? $_SESSION ["smpl_stat"] ["type"] : "all";
    $form ["type"] = array (
        '#type' => 'select',
        '#title' => $this->t ( 'Filter of types' ),
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
        '#title' => $this->t ( 'Filter of path' ),
        '#default_value' => $path,
        '#size' => 128,
        '#maxlength' => 255,
        '#required' => False,
        '#description' => $this->t ( "Filter of path." ),
        '#attributes' => array (
            'onchange' => 'this.form.submit();' 
        ) 
    );
    $length = isset ( $_SESSION ['smpl_stat'] ['length'] ) ? $_SESSION ['smpl_stat'] ['length'] : 100;
    
    $form ['length'] = [ 
        '#type' => 'select',
        '#title' => $this->t ( 'Length of list' ),
        '#description' => $this->t ( 'Length of list of images' ),
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
            'data' => ( string ) $this->t ( 'ID' ),
            'field' => 'id' 
        ),
        'path' => array (
            'data' => ( string ) $this->t ( 'Path' ),
            'field' => 'path' 
        ),
        'name' => array (
            'data' => ( string ) $this->t ( 'Name' ),
            'field' => 'name' 
        ),
        'subtitle' => array (
            'data' => ( string ) $this->t ( 'Subtitle' ),
            'field' => 'subtitle' 
        ),
        'typ' => array (
            'data' => ( string ) $this->t ( 'Type' ),
            'field' => 'typ' 
        ),
        'viewnumber' => array (
            'data' => ( string ) $this->t ( 'Viewnumber' ),
            'field' => 'viewnumber' 
        ),
        'link' => array (
            'data' => ( string ) $this->t ( 'Link' ),
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
        "#title" => $this->t ( "Number of rows: " ) . $db,
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
          "id" => ( string ) $this->t ( 'ID' ),
          "path" => ( string ) $this->t ( 'Path' ),
          "name" => ( string ) $this->t ( 'Name' ),
          "subtitle" => ( string ) $this->t ( 'Sub' ),
          "typ" => ( string ) $this->t ( 'Type' ),
          "viewnumber" => ( string ) $this->t ( 'Viewnumber' ),
          "link" => ( string ) $this->t ( 'Link' ) 
      );
      $id = array_search ( $order, $or );
      $header [$id] ["sort"] = $sort;
    }
    
    $qry = $qry->extend ( TableSortExtender::class )-> orderByHeader ( $header );
    $qry = $qry->extend ( PagerSelectExtender::class )->limit ( $length );
    $rs = $qry->execute ();
    
    $form ['stat'] = array (
        '#markup' => $this->t ( "Simple Photoalbum statistics" ) 
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
        "#title" => $this->t ( "ID" ),
        "#size" => 10,
        "#maxlength" => 10,
        "#disabled" => true 
    ];
    $form ['record'] ['smplpath'] = [ 
        "#type" => "textfield",
        "#title" => $this->t ( "Path" ),
        "#description" => $this->t ( "The path of image. Can not change from here" ),
        "#size" => 30,
        "#maxlength" => 128,
        "#disabled" => true 
    ];
    $form ['record'] ["smplname"] = [ 
        "#type" => "textfield",
        "#title" => $this->t ( "Filename" ),
        "#size" => 30,
        "#maxlength" => 128,
        "#description" => $this->t ( "Filename. Can not change from here" ),
        "#disabled" => true 
    ];
    $form ['record'] ["smplsubtitle"] = [ 
        "#type" => "textfield",
        "#title" => $this->t ( "Subscription" ),
        "#description" => $this->t ( "Subscription of item" ) 
    ];
    
    $form ['record'] ["smpltype"] = [ 
        "#type" => "textfield",
        "#title" => $this->t ( "Type of item" ),
        "#description" => $this->t ( "Type of item" ),
        "#disabled" => true 
    ];
    
    $form ['record'] ["smplviewnumber"] = [ 
        "#type" => "textfield",
        "#title" => $this->t ( "Viewnumber" ) 
    ];
    
    $form ['record'] ["smpllink"] = [ 
        "#type" => "textfield",
        "#title" => $this->t ( "Link associated of item" ) 
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