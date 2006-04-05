<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'Barrel/Editor.php';

$page = new $globals->toplevel(true);

$editor = new Diogenes_Barrel_Editor();
$editor->run($page, 'sites_content');

if (!$globals->checkRootUrl())
{
  $page->assign('msg_information', __('Warning : in order to make use of virtual hosts or WebDAV, you need to set $globals->rooturl to a full URL starting with http:// or https:// in your Diogenes configuration file.'));
}

$page->assign('greeting',__("List of sites"));
$page->display('toplevel-sites.tpl');
?>
