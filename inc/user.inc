<?php
/*
 * this file is used for Payment system and handles the users
 */
/**
 * Betölti az oldalt használó aktuális felhasználót a Sessionbe
 */

class SmplPhotoalbumUser{
  var $uid;             // User ID
  var $name;            // User név
  var $mail;             // User email címe
  var $sid;
  var $hostname;         //From where call
  var $roles;            //is he/she smplphotoalbum user?
  var $money;            //How many money has on his/her account;
  var $currency = "Ft";
 
  function __construct($uid = 0)
  {
    global $user;
    fz_t($user);
    if ($uid == 0){
      $this->uid      = $uid;
    }else{
      $this->uid      = $user->uid;
    }
   
    $this->hostname = $user->hostname;
    
    if($this->uid > 0 )
    {
      $this->name     = $user->name;
      $this->roles    = $user->roles;
      if(in_array('smplphotoalbum user',$this->roles))
      {
        //Betöltöm az aktuális pénz állapotot az smplphotoalbum user táblából
        $row = db_select('smplphotoalbum_user','u')
               ->fields('u', array('money'))
               ->condition('uid',$this->uid,'=')
               ->execute();
        $this->money = $row['money'];
      }
    }else{
      $this->name     = "Anonymous";
      $this->roles[]  = array("smplphotoalbum user");
      $this->money    = 0;
    }
    fz_t($this);
  }
  
  function getUid()
  {
    return $this->uid;
  }
  
  /**
   * Betölti az aktuálisan bejelentkezett user adatait
   */
  function Load()
  {
    
  }
  
  /**
   * Növeli az aktuális user számláján lévő összeget
   */
  function MoneyAdd($money=0)
  {
    $this->money += $money;
    if($this->uid > 0)
    {
      db_update("smplphotoalbum_user")
        ->fields(array('money' => $this->money))
        ->condition('uid',$this->uid,'=')
        ->execute();
    }
  }
  
  /**
   * A kifizetett összeggel csökkenti a user számláján lévő egyenleget
   */
  function MoneyPayment($money = 0){
    $this->money -= $money;
   
    if($this->uid > 0)
    {
      db_update("smplphotoalbum_user")
        ->fields(array('money' => $this->money))
        ->condition('uid',$this->uid,'=')
        ->execute();
    }
  }
  
  /**
   * Megjeleníti az aktuálisan bejelentkezett smőplphotoalbum user adatait.
   */
  function View(){
    
  }
  
  /**
   * Módosítja az aktuálisan bejelentkezett smplphotoalbum user adatait
   * Azanonymous user adatait nem lehet módosítani
   */
  function Modify(){
  
  }
  
  /**
   * Töröl egy smplphotoalbum felhasználót a saját táblából (egyúttal Drupal felhasználót is)
   * @param unknown $account
   */
  function Delete(){
  
  }
}

function smplphotoalbum_user_load(){
  global $user, $smpluser;
return;
  if($_SERVER['SERVER_NAME'] == "www.fzolee.hu")
  {
    return;
  }
  if(!isset($smpluser) || empty($smpluser)){
    $smpluser = new SmplPhotoalbumUser();
  }
  fz_t($smpluser);
  drupal_session_start();
  fz_t($_SESSION['smplphotoalbum_user']);
  if(!isset($_SESSION['smplphotoalbum_user']) ) {
    $_SESSION['smplphotoalbum_user'] = (array) ($smpluser);
  }

  //Ha az aktuálisan bejelentkezett user más, mint a Session user, akkor
  if( $_SESSION['smplphotoalbum_user']['uid'] = $user->uid){
    $_SESSION['smplphotoalbum_user'] = (array) ($smpluser);
  }
}


/**
 * Új smplphotoalbumfelhasználót hoz létre (Aki egy Drupal user is smplphotoalbum profillal)
 * @param unknown $edit
 * @param unknown $account
 * @param unknown $category
 */
function smplphotoalbum_user_insert(&$edit, $account, $category){
  
}




