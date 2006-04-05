<?php
if (empty($_REQUEST['dir']))
  exit;

require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'Barrel/Page.php';
require_once 'Barrel/File.php';

$page = new DiogenesAdmin($_REQUEST['dir']);
$bbarrel =& $page->barrel;

// retrieve all the barrel's pages
$fpages = $bbarrel->getPages();

$page->assign('post',$page->script_self());

// rcs handle
$rcs = $page->getRcs();

/* 5Mb limit */
$maxsize=5000000;

$action = (isset($_REQUEST["action"])) ? $_REQUEST["action"] : "";
$target = isset($_REQUEST["target"]) ? $_REQUEST["target"] : "";
$newname = isset($_REQUEST["newname"]) ? $_REQUEST["newname"] : "";
$fileop_sfile = isset($_REQUEST['fileop_sfile']) ? $_REQUEST['fileop_sfile'] : '';
$fileop_ddir = isset($_REQUEST['fileop_ddir']) ? $_REQUEST['fileop_ddir'] : '';

$fpage = $fpages[$_REQUEST['dir']];

// legacy
$canedit = $_SESSION['session']->hasPerms($fpage->props['wperms']);
$page->assign('canedit', $canedit);

// translations
$page->assign('msg_import',__("Import file"));
$page->assign('msg_btn_fileop',__("Submit"));
$page->assign('msg_btn_send',__("Send"));
$page->assign('msg_create',__("Create an empty file"));
$page->assign('msg_copy_or_move', __("Copy or move a file"));
$page->assign('msg_btn_create',__("Create"));
$page->assign('msg_file',__("file"));
$page->assign('msg_log',__("log"));
$page->assign('msg_version',__("version"));
$page->assign('msg_date',__("date"));
$page->assign('msg_author',__("author"));
$page->assign('msg_size',__("size"));
$page->assign('msg_actions',__("actions"));
$page->assign('msg_fileop_to', __("to"));
// for pages
$page->assign('msg_location',__("location"));
$page->assign('msg_title',__("title"));
$page->assign('msg_page_template',__("page template"));
$page->assign('msg_status',__("status"));
$page->assign('msg_access',__("access"));
$page->assign('msg_read_perms',__("read access"));
$page->assign('msg_write_perms',__("write access"));

// build navigation toolbar
$page->toolbar(__("Page"), $fpage->make_toolbar());


switch ($action) {
case "file_create":
  if (!$canedit) break;
  $createfile = isset($_REQUEST['createfile']) ? $_REQUEST['createfile'] : '';
  if ($createfile) {
    $page->info(__("Creating empty file") . " $createfile");
    if ($rcs->checkFile($fpage->props['PID'], $createfile)) {
      $page->info(__("The specified file already exists!"));
    } else {
      $rcs->commit($fpage->props['PID'], $createfile, '', "empty file creation of $createfile");
    }
  }
  break;  
    
case "file_upload":
  if (!$canedit) break;
  $userfile = $_FILES['userfile']['name'];
  if ( is_uploaded_file($_FILES['userfile']['tmp_name'])
    && (filesize($_FILES['userfile']['tmp_name']) <= $maxsize) )
  {
    $rcs->commit($fpage->props['PID'], $userfile,
                          file_get_contents($_FILES['userfile']['tmp_name']),
                          "file manager upload of $userfile" );
  } else {
    $page->info(__("Error during file transfer!"));
  }
  break;

case "file_delete":
  if (!$canedit) break;
  $page->info(__("Deleting file"). " ". $fpage->getLocation($target));
  $rcs->del($fpage->props['PID'],$target);
  break;

case "file_rename":
  if (!$canedit) break;    
  $page->info("Renaming file '$target' to '$newname'");
  $rcs->move($fpage->props['PID'], $target, $fpage->props['PID'], $newname);
  break;

case "page_delete":
  Diogenes_Barrel_Page::delete($bbarrel, $target, $page);
  $fpages = $bbarrel->getPages();
  break;
  
case "restore":
  if (!$canedit) break;
  $rev = $_REQUEST["rev"];
  $page->info("restore : $target,$rev");
  $path = $rcs->checkout($fpage->props['PID'],$target,$rev,System::mktemp("-d"));
  $contents = file_get_contents($path);
  $rcs->commit($fpage->props['PID'],$target,$contents,"restored revision $rev");
  break;
  
case "diff":
  $ffile = new Diogenes_Barrel_File($fpage, $target);
  $page->assign('greeting', __("Revision differences"). " - ". $fpage->getLocation($target));
  $page->assign('diff',$rcs->dispDiff($fpage->props['PID'], $target, $_REQUEST["r1"],$_REQUEST["r2"]));
  $page->toolbar(__("File"), $ffile->make_toolbar(true));
  $page->display('admin-revs.tpl');
  exit;
  
case "revs":
  $ffile = new Diogenes_Barrel_File($fpage, $target);
  $page->assign('greeting', __("File revisions"). " - " . $fpage->getLocation($target));

  // build urls
  $urlPage = $page->urlSite($fpage->props['location']);  
  $urlFile = $page->urlSite($fpage->props['location'], $target);
  
  // parse log entries
  $revs = $rcs->logParse($fpage->props['PID'], $target);    

  // process log entries
  $head = $revs[0]['rev'];  
  $rentries = array();
    
  $sz = count($revs);
  for ($i = 0; $i < $sz; $i++) {
    $rev = $revs[$i];
    $actions = array(array(__("view"), "$urlFile?rev={$rev['rev']}"));
    if ($target == $globals->htmlfile) {
      array_push($actions, array(__("view page"),"$urlPage?rev={$rev['rev']}"));
    }
    if ($i != ($sz-1)) {
      array_push($actions, array(__("diff to")." {$revs[$i+1]['rev']}",
        "?action=diff&amp;dir={$fpage->props['PID']}&amp;target=$target&amp;r1={$revs[$i+1]['rev']}&amp;r2={$rev['rev']}&amp;author={$rev['author']}"));
    }
    if (($i != 0) && $canedit) {
      array_push($actions, array(__("restore"),
        "javascript:restore('{$rev['rev']}');"));
    }
    array_push($rentries, array($rev['rev'],$rev['date'],
                $rev['author'],$rev['log'], $actions));
  }
  
  $page->assign('entries', $rentries);
  $page->toolbar(__("File"), $ffile->make_toolbar(true));
  $page->display('admin-revs.tpl');
  exit;

case "fileop":
  $fileop_dfile = $fileop_sfile;
  $file_action = isset($_REQUEST['file_action']) ? $_REQUEST['file_action'] : '';
  $page->info("$file_action '$fileop_sfile' to '$fileop_ddir'");
  switch ($file_action)
  {
    case "file_copy":
      $page->info("copying '$fileop_sfile' to '$fileop_ddir'");
      if ($rcs->checkFile($fileop_ddir, $fileop_dfile))
      {
        $dfext  = array_pop(explode('.', $fileop_dfile));
        $dfname = basename($fileop_dfile, '.'.$dfext);
        $fileop_dfile = $dfname."_copy.$dfext";
      }
      $rcs->copy($fpage->props['PID'], $fileop_sfile, $fileop_ddir, $fileop_dfile);      
      break;
      
    case "file_move":
      $page->info("moving '$fileop_sfile' to '$fileop_ddir'");
      $rcs->move($fpage->props['PID'], $fileop_sfile, $fileop_ddir, $fileop_dfile);      
      break;
  }
  break;
 
}

// directory listing
$page->toolbar(__("Document"), $fpage->make_doc_toolbar($rcs));
$pageloc = $bbarrel->getLocation($fpage->props['PID']);
$page->assign('greeting', __("File manager"). " - " .(strlen($pageloc) ? $pageloc : __("home")));
$page->assign('maxsize',$maxsize);

// retrieve child directories
foreach($fpages as $pkey => $pval)
{
  if ($pval->props['parent'] == $fpage->props['PID'])
  {
    $arr = $pval->props;  
    $arr['actions'] = $pval->make_actions();
    $arr['click'] = "files?dir={$pkey}";
    $arr['icon'] = $globals->icons->get_icon('barrel', strlen($arr['location']) ? 'directory' : 'home');
    $arr['iperms'] = $globals->icons->get_icon('perm', $arr['perms']); 
    $arr['iwperms'] = $globals->icons->get_icon('perm', $arr['wperms']); 
    $page->append('childpages', $arr);
  }
}

// retrieve files in directory
$tfentries = $rcs->dispDir($fpage->props['PID'], $pageloc, $canedit);
$fentries = array();
foreach ($tfentries as $fentry)
{
  $tbfile = new Diogenes_Barrel_File($fpage, $fentry['file']);
  $fentry['actions'] = $tbfile->make_actions($canedit);
  array_push($fentries, $fentry);
}
$page->assign('childfiles', $fentries);


$page->assign('fileops', array("file_copy" => __("Copy"), "file_move" => __("Move")));
$fileop_ddirs = array();
foreach($fpages as $pkey => $ppage)
{
  $pageloc = $bbarrel->getLocation($pkey);
  $fileop_ddirs[$pkey] = ( strlen($pageloc) ? "<$pageloc> " : "") . $ppage->props['title'];
}
$page->assign('fileop_ddirs', $fileop_ddirs);
$page->display('admin-files.tpl');
?>
