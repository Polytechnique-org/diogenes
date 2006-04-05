<?php
if (preg_match("/^\/([^\/]+)\/webdav(\/.*)?$/",$_SERVER['PATH_INFO'],$tmp)) {

  // this is a WebDAV operation
  require_once 'diogenes.common.inc.php';
  require_once 'diogenes.webdav.inc.php';
  $server = new $globals->webdav;
  $server->ServeRequest();

} elseif (preg_match("/^\/([^\/]+)\/admin\/(.*)/",$_SERVER['PATH_INFO'],$tmp)) {
  $afile = $tmp[2];
  if (preg_match("/^(ekitapplet|gnu-regexp-1.1.4|kafenio-config|kafenio-icons|kafenio)\.jar$/", $afile)) {
    header("Content-Type: application/java-archive");
    header("Last-modified:".gmdate("D, d M Y H:i:s T", filemtime($afile)));
    readfile($afile);
  } else {
    // include the requested admin page
    if (!$afile) $afile = "index";    
    require("admin/$afile.php");
  }

} else {

  // post or get on a barrel file
  require_once 'diogenes.common.inc.php';
  require_once 'diogenes.barrel.inc.php';
  $page = new $globals->barrel;

  if (!$globals->validatepages)
    $page->assign("skipvalidator",1);

  $page->doContent();

}
?>
