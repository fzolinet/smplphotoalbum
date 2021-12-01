<?php
function smpl_is_mobile(){
  $is_mobile = false;
  if(module_exists('mobile_detect') )
  {
    $mb        = new Mobile_Detect();
    $is_mobile = (boolean)($mb->isMobile());
  }else if(module_exists("browscap")){
    $properties = browscap_get_browser();
    $is_mobile  =  ($properties['ismobiledevice']=== true);
  }
  return $is_mobile;
}

/*
 * Tablet
 *
 * $properties: Array
(
    [comment] => Android Browser 4.0
    [browser] => Android
    [browser_maker] => Google Inc
    [version] => 4.0
    [majorver] => 4
    [minorver] => 0
    [platform] => Android
    [ismobiledevice] => true
    [istablet] => false
    [crawler] => false
    [device_type] => Mobile Phone
    [device_pointing_method] => touchscreen
    [parent] => Android Browser 4.0
    [useragent] => Mozilla/5.0 (Linux; U; Android 4.2.2; hu-hu; xTAB-100qcr Build/xTAB-100 QCR) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Safari/534.30
    [browser_name_pattern] => Mozilla/5.0 (*Linux*Android?4.2* Build/*)*applewebkit*(*khtml*like*gecko*)*Version/4.0*Safari*
)

IOS iPhone
$properties: Array
(
    [comment] => Mobile Safari 10.0
    [browser] => Safari
    [browser_maker] => Apple Inc
    [version] => 10.0
    [majorver] => 10
    [minorver] => 0
    [platform] => iOS
    [ismobiledevice] => true
    [istablet] => false
    [crawler] => false
    [device_type] => Mobile Device
    [device_pointing_method] => touchscreen
    [parent] => Mobile Safari 10.0
    [useragent] => Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_2 like Mac OS X) AppleWebKit/603.2.4 (KHTML, like Gecko) Version/10.0 Mobile/14F89 Safari/602.1
    [browser_name_pattern] => Mozilla/5.0 (*CPU iPhone OS 10?3* like Mac OS X*)*applewebkit*(*khtml*like*gecko*)*Version/10.0*Safari/*
)

Android 4.4.4
$properties: Array
(
[comment] => Android WebView 4.0
[browser] => Android WebView
[browser_maker] => Google Inc
[version] => 4.0
[majorver] => 4
[minorver] => 0
[platform] => Android
[ismobiledevice] => true
[istablet] => false
[crawler] => false
[device_type] => Mobile Phone
[device_pointing_method] => touchscreen
[parent] => Android WebView 4.0
[useragent] => Mozilla/5.0 (Linux; Android 4.4.4; One M8 Build/KTU84Q) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/33.0.0.0 Mobile Safari/537.36
[browser_name_pattern] => Mozilla/5.0 (*Linux*Android?4.4* Build/*) applewebkit* (*khtml*like*gecko*) Version/4.0*Chrome*Safari*
)
 * */