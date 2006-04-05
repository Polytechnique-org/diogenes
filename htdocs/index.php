<?php
// include common definitions
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'Barrel/Editor.php';

$page = new $globals->toplevel;

$editor = new Diogenes_Barrel_Editor();
$editor->readonly = 1;
$editor->run($page, 'viewer_content');

$page->assign('greeting', __("Welcome to Diogenes")."!");
$page->assign('about',
  $globals->urlise(__("Welcome to the Diogenes content management system.")." ".
  __("Diogenes was developed by the webmasters of the Polytechnique.org web site.")));
$page->assign('available',__("The following barrels are currently available"));

$page->display("index.tpl");
?>
