<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';

$page = new DiogenesAdmin;

$page->assign('greeting',__("WebDAV"));

if ($globals->checkRootUrl())
{
  $page->assign('msg_webdav',__("You can access your barrel's files using WebDAV by pointing your WebDAV client to the following address."));
  $page->assign('url_webdav', $page->urlBarrel($page->alias, $page->barrel->vhost, "webdav/"));
} else {
  $page->assign('msg_webdav', __("In order to access your barrel's files using WebDAV, please ask your Diogenes administrator to enable this feature in the Diogenes configuration file."));

}

$page->display('admin-webdav.tpl');

?>
