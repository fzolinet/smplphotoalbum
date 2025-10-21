<?php
/**
 * Copy the default folder recursive to temporary 
 * @param $dest - Destination folder
 * @return      - Number of copied files
 */
function copyTree($dest){
    print "=> Copy files into " . TARGET . " => ";
    $tempfile = $dest."\\m.php_temp.txt";
    exec ( 'cmd /c xcopy *.* ' . $dest . ' /S /Y /EXCLUDE:m.excl > '.$tempfile );
    $f = file($tempfile);
    print $f[ count($f)-1]."\n";
    $line = explode (" ", $f[ count($f)-1] );
 
    $db = (int) $line[0]; 
    unlink(TARGET."\\temp.zip");
    return $db;
}
/**
 * Delete recursive a folder tree
 * @param string $dir - actual dir;
 * @param int $i      - number of deleted files
 */
function delTree($dir) {
    global $db;
    $files = array_diff(scandir($dir), array('.','..'));    
    foreach ($files as $file) {
        if(is_dir("$dir/$file")) {
            delTree("$dir/$file");
        }else{
            unlink("$dir/$file");
            Percent();
        }        
    }
    return rmdir($dir);
}

/**
 * 
 * Shows the percent of process
 * @param mixed $start - reset the counter
 * @return int
 */
function Percent($start = false){
    global $db;
    static $pold = 0;
    static $i = 0;
    if( $start ) $i = 1;
    $perc = (int)( $i * 100 / $db);
    if( ($perc % 10 == 0) && $perc != $pold) { //
        print chr(8).chr(8).chr(8).chr(8)."$perc %";
        $pold = $perc;
    }  
    $i++;  
    return $perc;
}

function backupCopy($module, $ver, $temp, $zip ){    
    $backupFolder = "H:/Backup/_Elo_Fejlesztes/".$module;
    if( !file_exists( $backupFolder) ){
        print " There is no backup folder on this computer\n";
        return;
    }
    $today = Date("Ymd");
    if( !file_exists( $backupFolder. "/".$today."-".$ver ) ){
        mkdir( $backupFolder. "/".$today."-".$ver);
    }        
    if(copy( $temp."/".$zip, $backupFolder ."/" . $today . "-" . $ver ."/".$zip)){
        print "successful\n";
    }
    else{
        print "not successful\n";
    };
    print "\n";
}

/**
 * FTP Put to the server 
 * ftp://www.fzolee.hu/web/fw2/sites/default/files/dl/smplphotoalbum/ fz/111Kokojumbo.
 * @param string $from - from where upload the files
 * @param string $zip - name of zip file
 * @param string $targz - name of targz file
 * @return void
 */
function ftpCopy($from, $zip, $module){    
    $from       = $from."\\".$zip;
    $ftp_target = "/web/fw2/sites/default/files/dl/".$module."/".$zip;            
    //$ftp_target = "/wwwroot/fw2/sites/default/files/dl/".$module."/".$zip;
    $server = "www.fzolee.hu";
    //$server = "localhost";
    
    $user   = "fzoleeadmin";
    $pwd    = "kqoFiXVV_9";
    $ftp    = ftp_connect($server);
    $result = ftp_login($ftp, $user, $pwd);
    if( !$result ){
        print "FTP connection problem\n";
        return;
    }
    ftp_pasv($ftp, true);

    // upload file    
    if( ftp_put($ftp, $ftp_target, $from, FTP_BINARY)){
        print "successful\n";
    }else{
        print "There was an ERROR while uploading\n";
        ftp_close($ftp);
        return;
    }       
}