<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'diogenes.text.inc.php';
require_once 'Barrel/Page.php';
require_once 'Barrel/File.php';

if (!isset($_REQUEST['dir']) && !isset($_REQUEST['file']))
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
switch ($action)
{
case "update":
  // update the current file with form contents
  if (isset($_REQUEST['file_content'])) {
    $page->info(__("Commiting changes to file") . " $file");
    $content = trim(stripslashes($_REQUEST['file_content']));
    $content = str_replace("\n    ","\n",$content);
    $content = phpUnprotect(htmltoXhtml($content));
    $message = empty($_REQUEST['message']) ? "updated using HTML composer" : stripslashes($_REQUEST['message']);
    $rcs->commit($dir,$file,$content,$message);
  }

  break;
}

$rev = array_shift($tmparr = $rcs->logParse($dir,$file));

// protect PHP code and XHTML tags
$rawdoc = file_get_contents($myfile);
$protdoc = xhtmlToHtml(phpProtect($rawdoc));

// smarty assignments
$page->assign('post',$page->script_self());
$page->assign('dir',$dir);
$page->assign('dirloc',$page->urlBarrel($bbarrel->alias,$bbarrel->vhost,$bpage->getLocation()));

$page->assign('file',$file);
$page->assign('file_content',chunk_split(base64_encode($protdoc)));
$page->assign('msg_log',__("log message"));

// build toolbars
$page->toolbar(__("Page"), $bpage->make_toolbar());
$page->toolbar(__("File"), $bfile->make_toolbar(true));

$auxpage = new DiogenesBarrel("/".$bbarrel->alias."/". $bpage->getLocation());

switch ($globals->html_editor) {
case "ekit":
  $page->assign('greeting', "Ekit - ". $bpage->getLocation($file)." - {$rev['rev']}");
  $page->assign('cssfiles', array_pop($auxpage->sheets));
  $page->display('admin-ekit.tpl'); 
  break;
case "kafenio": default:
  $page->assign('greeting', "Kafenio - ". $bpage->getLocation($file)." - {$rev['rev']}");
  $page->assign('cssfiles', implode(",", $auxpage->sheets));
  $page->display('admin-kafenio.tpl'); 
  break;
}
?>
