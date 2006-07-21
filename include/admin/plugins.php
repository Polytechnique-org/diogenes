<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'Plugin/Editor.php';
require_once 'Barrel/Page.php';

$page = new DiogenesAdmin;

$bbarrel = $page->barrel;

if (!$bbarrel->hasFlag('plug')) {
  return;
}

$page->assign('post',$page->script_self());

$dir = isset($_REQUEST['plug_page']) ? $_REQUEST['plug_page'] : 0;

if ($dir != 0)
{
  $bpage = Diogenes_Barrel_Page::fromDb($bbarrel, $dir);
  $page->assign('greeting',__("Page plugins ") . " - " . ($bpage->props['location'] ? $bpage->props['location'] : __("home")) );    
  $page->toolbar(__("Page"), $bpage->make_toolbar($page));
  $wperms = $bpage->props['wperms'];
} else {
  $page->assign('greeting',__("Available plugins"));
  $wperms = '';
}

/* plugin editor */
$editor = new Diogenes_Plugin_Editor($page->alias, $dir, $wperms);
if ($dir == 0) {
  $editor->readonly = 1;
  $editor->hide_params(1);
}
$editor->run($page,'page_content');
$page->display('');
?>
