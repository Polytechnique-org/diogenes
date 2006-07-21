<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'Barrel/Page.php';
require_once 'Barrel/File.php';

if (empty($_REQUEST['dir']) && empty($_REQUEST['file']))
  exit;

// retrieve directory & file info
$dir = $_REQUEST["dir"];
$file = $_REQUEST["file"];
$page = new DiogenesAdmin($dir);

$bbarrel = $page->barrel;
$bpage = Diogenes_Barrel_Page::fromDb($bbarrel, $dir);
$bfile = new Diogenes_Barrel_File($bpage, $file);

// rcs handle
$rcs = $page->getRcs();

// file location & url
$mydir = $bbarrel->spool->spoolPath($dir);
$myfile = $bbarrel->spool->spoolPath($dir,$file);

// process requested action
$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : "";
switch($action)
{
case "set_stylesheet":
  if (isset($_REQUEST['preset_style_sheet'])) {
    $sheet = $_REQUEST['preset_style_sheet'];
    $page->info(__("Copying style sheet") . " $sheet.css");
    $rcs->commit($dir,$file,file_get_contents("{$globals->root}/styles/$sheet.css"), "replaced by $sheet.css");
  }
  break;
 
case "update":
  // update the current file with form contents
  if (isset($_REQUEST['file_content'])) {
    $page->info(__("Commiting changes to file") . " $file");
    $message = empty($_REQUEST['message']) ? "updated using editor" : stripslashes($_REQUEST['message']);
    $rcs->commit($dir,$file,stripslashes($_REQUEST['file_content']),$message);
  }

  break;
}

$rev = array_shift($tmparr = $rcs->logParse($dir,$file));

$page->assign('greeting', __("File editor")." - ". $bpage->getLocation($file). " - {$rev['rev']}");
$page->assign('post',$page->script_self());
$page->assign('dir',$dir);
$page->assign('file',$file);
$page->assign('source',__("File source"));
$page->assign('msg_log',__("log message"));
$page->assign('file_content',htmlspecialchars(file_get_contents($myfile), ENT_NOQUOTES));
$page->assign('submit',__("Submit"));

// menu for stylesheet replacement
if ($file == $globals->cssfile) {
   $page->assign('style_sheets', $globals->style_sheets);
   $page->assign('preset_style_sheet', $globals->barrel_style_sheet);
   $page->assign('msg_set_stylesheet', __("replace by a preset style sheet"));
   $page->assign('msg_replace',__("Replace"));
}

// top toolbar
$page->toolbar(__("Page"), $bpage->make_toolbar($page));
$page->toolbar(__("File"), $bfile->make_toolbar(true));
$page->display('admin-edit.tpl');
?>
