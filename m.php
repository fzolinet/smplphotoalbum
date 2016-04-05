<?php
echo "\n\n\n";
echo "*******************************\n";
echo "* Make SmplPhotoalbum installer\n";
echo "*******************************\n";
echo "\n=> Bootloader Drupal 7\n";

$fz_dir = getcwd();
chdir("../../../../");
define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR']="127.0.0.1";
require_once (DRUPAL_ROOT."./includes/bootstrap.inc");
error_reporting(E_ALL ^ E_NOTICE);
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
error_reporting(E_ALL ^ E_NOTICE);
chdir($fz_dir);
//
define("SMPL","smplphotoalbum");
$file = SMPL.".info";
echo "=> Read ".$file."\n";
$infotxt = file_get_contents($file);

$info = system_get_info("module",SMPL);
$ver  = $info["version"];
$datestamp = $info["datestamp"];

$a = preg_split("/\./",$ver);
$newver = 1+ (int)($a[2]);

$fver = "7.x-1.".$newver;
$infotxt = preg_replace('/\"7\.x\-1\..+\"/','"7.x-1.'.$newver.'"', $infotxt);

$datestamp = mktime();
$infotxt = preg_replace('/datestamp = \".+\"/','datestamp = "'.$datestamp.'"', $infotxt);
//
echo "=> Backup the old ".SMPL.".info file to ".$file.".bak\n";
rename($file,$file.".bak");

echo "=> Save new ".SMPL.".info file\n";
file_unmanaged_save_data($infotxt,$file,FILE_EXISTS_RENAME);

define("TARGET","E:\\".SMPL);

mkdir(TARGET);

print "=> Copy files into ".TARGET."\n";
exec('cmd /c xcopy *.* '.TARGET.' /S /Y /EXCLUDE:m.excl');

print "=> Make E:\\".SMPL."-$fver.tar.gz\n";
system('cmd /c .make\\tar\\bsdtar -cf E:\\'.SMPL.'-'.$fver.'.tar.gz -z '.TARGET);

print "=> Make E:\\".SMPL."-$fver.zip\n";
system('cmd /c .make\\zip\\zip -r -9 -q E:\\'.SMPL.'-'.$fver.'.zip '.TARGET);

print "=> Delete temporary files and folders: ".TARGET."\n";
file_unmanaged_delete_recursive(TARGET);

rmdir(TARGET);
echo "=> End of compiling the module: ".SMPL."\n\n";
?>