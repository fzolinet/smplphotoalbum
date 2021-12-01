<?php
if(!defined("TN"))					// Default Thumbnail pretag
define("TN","_tn_/");

//--------------------- define ("GD",True) --------------- Needed gd extension to make thumbnails
define('GD',(function_exists('gd_info') ? true:false));

define ("PICTURE", "P");				//Media types
define ("VIDEO"  , "V");				//Video
define ("NOPIC"  , "N");				//No Picture
define ("QTIME"  , "Q");        //Quicktime
define ("RM"     , "R");        //Real Media
define ("SOUND"  , "S");        //Sound
define ("FOLDER" , "F");        //Folder
define ('SMPLADMIN','smpllist'); // smpladmin
define ('SMPLSIMPLELIST' , 'style="display:block;vertical-align: middle;"');
//Default filetypes
//Image
define('SMPLIMGEXT','jpg png jpeg bmp wbmp gif');
//Sound
define('SMPLAUDIOEXT','wma flac fla midi mid ac3 mp3 ogg wav alac m4a aiff aac');
//HTML5Sound
define('SMPLHTML5AUDIOEXT','mp3 ogg wav');
//video
define('SMPLVIDEOEXT','avi mpeg mpg divx rm flv 3gp mov m4v asf wmv swf m4v mov qt ogg mp4 webm mkv');
define('SMPLHTML5VIDEOEXT','ogg mp4 webm');
define('SMPLDOCEXT','pdf docx doc xlsx xls pptx ppt ppsx txt'); //Document
define('SMPLCMPEXT','zip rar r## ### gz tar arj chm'); //compressed
define('SMPLAPPEXT','exe dll apk jar so'); //application
define('SMPLOTHEXT','sfv nfo bak bkp');     //Other
define('SMPLDISEXT','php html js ini install xml'); //disabled

if(!defined("__DIR__")){
	define('__DIR__',str_replace("\\","/",dirname(__FILE__) ) );
}

if(!defined("OS")){
	if(preg_match('/win/i', $_SERVER['SERVER_SOFTWARE']) == 1  ) define("OS","WIN");
	elseif(preg_match("/Linux/i",$_SERVER['SERVER_SOFTWARE']))   define("OS","LINUX");
	else                                                         define("OS","OTHER");
}

//******** Payment section 2017.06.24 *******************************************//

//Smplphotoalbum user table
define("SMPLPHOTOALBUM_USER", "smplphotoalbum_user");

//Smplphotoalbum payment history
define("SMPLPHOTOALBUM_PAYMENT", "smplphotoalbum_payment");

define('SMPLPHOTOALBUM_PAYANT', "smplphotoalbum_payant");