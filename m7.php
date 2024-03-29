<?php
echo "\n\n\n";
echo "*******************************\n";
echo "* Make SmplPhotoalbum installer\n";
echo "*******************************\n";
echo "\n=> Bootloader Drupal 7\n";

$fz_dir = getcwd();
while(!file_exists("fz.drupal")){
  chdir("../");
}
define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR']="127.0.0.1";
echo "==> Bootstrap\n";
require_once (DRUPAL_ROOT."./includes/bootstrap.inc");
error_reporting(E_ALL ^ E_NOTICE);
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
error_reporting(E_ALL ^ E_NOTICE);
chdir($fz_dir);
//
define("SMPL","smplphotoalbum");
$file    = SMPL.".info";
echo "=> Read ".$file."\n";
$infotxt = file_get_contents($file);

$info      = system_get_info("module",SMPL);
$ver       = $info["version"];
$datestamp = $info["datestamp"];

$a = preg_split("/\./",$ver);
$newver = 1+ (int)($a[2]);

$fver = "7.x-2.".$newver;
$infotxt = preg_replace('/\"7\.x\-1\..+\"/','"7.x-2.'.$newver.'"', $infotxt);

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
system('xcopy *.* '.TARGET.' /S /Y /EXCLUDE:m.excl');
die();
$smplgz =   SMPL.'-'.$fver.'.tar.gz';
$smplzip =  SMPL.'-'.$fver.'.zip';

print "=> Make E:\\".SMPL."-$fver.tar.gz\n";
system('cmd /c .make\\tar\\bsdtar -cf E:\\'.$smplgz.' -z '.TARGET);

print "=> Make E:\\".SMPL."-$fver.zip\n";
system('cmd /c .make\\zip\\zip -r -9 -q E:\\'.$smplzip.' '.TARGET);

print "=> Delete temporary files and folders: ".TARGET."\n";
file_unmanaged_delete_recursive(TARGET);

rmdir(TARGET);
$dest = 'H:\\Backup\\_Elo_Fejlesztes\\SmplPhotoalbum\\'.date('Ymd')."\\";
print "=> Make Backup area: ".$dest;
mkdir($dest);

print "=> Copy E:\\".$smplgz." => Backup area\n";
system('cmd /c copy E:\\'.$smplgz.' '.$dest);


print "=> Copy E:\\".$smplzip." => Backup area\n";
system('cmd /c copy E:\\'.$smplzip.' '.$dest);

print "=> Make Zip on Backup area\n";
system('cmd /c .make\\zip\\zip -r -9 -q '.$dest.SMPL.' .');

print "=> Copy files => ftp://www.fzolee.hu/web/download/smplphotoalbum/ \n";

// set up basic connection
$conn_id = ftp_connect("www.fzolee.hu");

$login_result = ftp_login($conn_id, 'fz', '11kokojumbo');

if($login_result)
{
  ftp_pasv($conn_id,true);

  // upload a file
  if (ftp_put($conn_id, '/web/download/smplphotoalbum/'.$smplgz, "E:\\".$smplgz, FTP_BINARY)) {
    echo "  => Successfully uploaded ".$smplgz."\n";
  } else {
    echo "  => There was a problem while uploading ".$smplgz."\n";
  }

  if (ftp_put($conn_id, '/web/download/smplphotoalbum/'.$smplzip, "E:\\".$smplzip, FTP_BINARY)) {
    echo "  => successfully uploaded ".$smplzip."\n";
  } else {
    echo "  => There was a problem while uploading ".$smplzip."\n";
  }
  // close the connection
  ftp_close($conn_id);
}
echo "=> End of compiling the module: ".SMPL."\n\n";
?>