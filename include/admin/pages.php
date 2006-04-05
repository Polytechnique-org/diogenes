<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'Barrel.php';
require_once 'Barrel/Page.php';

$page = new DiogenesAdmin;
$bbarrel =& $page->barrel;

// rcs handle
$rcs = $page->getRcs();

// translations
$page->assign('msg_submit',__("Submit"));
$page->assign('msg_reset',__("Reset"));
$page->assign('msg_properties',__("Page properties"));
$page->assign('msg_parent',__("parent page"));
$page->assign('msg_location',__("location"));
$page->assign('msg_title',__("title"));
$page->assign('msg_page_template',__("page template"));
$page->assign('msg_status',__("status"));
$page->assign('msg_access',__("access"));
$page->assign('msg_read_perms',__("read access"));
$page->assign('msg_write_perms',__("write access"));
$page->assign('msg_actions',__("actions"));
$page->assign('msg_add_page', array(__("Add a page"),"?action=edit") );

$page->assign('post', $page->script_self());
$page->assign('table', $bbarrel->table_page);

$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : "";
$target = isset($_REQUEST["target"]) ? $_REQUEST["target"] : "";

if (isset($_REQUEST["dir"])) {
  $dir = $_REQUEST["dir"];
  if (!$action) $action="edit";
} else {
  $dir = 0;
}


/* add or update a page */
switch ($action) {
case "import":

  // if needed, import a file
  if ($globals->word_import && isset($_FILES['wordfile']) && is_uploaded_file($_FILES['wordfile']['tmp_name'])) {
    $userfile = strtolower($_FILES['wordfile']['name']);
    if (!substr($userfile,-4,4) == ".doc") {
      $page->info(__("Document name does not end in .doc"));
    } else {
      $mydir = $bbarrel->spool->spoolPath($dir);
      $page->info(__("Checking in Word file") . " $userfile");
      $rcs->commit($dir, $globals->wordfile,
                          file_get_contents($_FILES['wordfile']['tmp_name']),
                          "Word file update $userfile" );

      $page->info(__("Importing Word file") . " $userfile");
      $rcs->importWordFile($dir, $globals->htmlfile, $_FILES['wordfile']['tmp_name']);
    }
  } else if (isset($_FILES['htmlfile']) && is_uploaded_file($_FILES['htmlfile']['tmp_name'])) {
    $userfile = strtolower($_FILES['htmlfile']['name']);
    if ( (substr($userfile,-4,4) == ".htm") || (substr($userfile,-5,5) == ".html") ) {
      $page->info(__("Importing HTML file") . " $userfile");
      $rcs->commit( $dir, $globals->htmlfile,
                 $rcs->importHtmlString(file_get_contents($_FILES['htmlfile']['tmp_name'])),
                 "html file import of $userfile" );
    } else {
      $page->info(__("Raw file import") . " $userfile");
      $rcs->commit( $dir, $globals->htmlfile,
                 file_get_contents($_FILES['htmlfile']['tmp_name']),
                 "raw file import of $userfile" );
    }
  }
  break;

case "update":
    // page ID
    $props['PID'] = $dir;

    // page location
    if (isset($_REQUEST['pedit_location'])) {
      $homepage = 0;
      $props['location'] = $_REQUEST['pedit_' . 'location'];
    } else {
      $homepage = 1;
      $props['location'] = '';
    }
    
    // other properties
    $kprops = array('parent', 'title', 'perms', 'wperms', 'status', 'template');    
    foreach ($kprops as $key) {
      $props[$key] = $_REQUEST['pedit_' . $key];
    }    
    
    $bpage = new Diogenes_Barrel_Page($bbarrel, $props);
    $bpage->toDb($homepage, $page);
    break;
    
   
case "page_delete":
    Diogenes_Barrel_Page::delete($bbarrel, $target, $page);
    break;
}

// retrieve all the barrel's page
$bpages = $bbarrel->getPages();


if ($dir) 
{
  $bpage = Diogenes_Barrel_Page::fromDb($bbarrel, $dir);
} else {
  $tparent = empty($_REQUEST['parent']) ? $bbarrel->getPID('') : $_REQUEST['parent']; 
  //$page->info("parent $tparent");
  $bpage = new Diogenes_Barrel_Page($bbarrel, array('parent' => $tparent));
}
 
// "Page" toolbar  
//if (isset($bpage->props['PID'])) {
if ($dir != 0) {
  $page->toolbar(__("Page"), $bpage->make_toolbar());
  $page->toolbar(__("File"), $bpage->make_doc_toolbar($rcs));
}
  
// retrieve suitable parents for the current page
if (!$bpage->props['PID'] || strlen($bpage->props['location']))
{
  $parent_all = array();
  foreach($bpages as $pkey => $pval)
  {
    if (!$dir or (($pval->props['PID'] != $dir) and ($pval->props['parent'] != $dir)))
    {
      $parent_all[$pkey] = $pval;
    }
  }

  $parent_opts = array();        
  foreach (array_keys($parent_all) as $pkey)
  {
    $ppage = $parent_all[$pkey];
    if (!$ppage->props['parent']  or isset($parent_all[$ppage->props['parent']]))
    {
      $pageloc = $bbarrel->getLocation($pkey);
      $parent_opts[$pkey] = ( strlen($pageloc) ? "<$pageloc> " : "") . $ppage->props['title'];
    }
  }      
  $page->assign('parent_opts', $parent_opts);    
} 
 
// messages
$gtitle = "";
if ($dir) 
{
  $gtitle = $bbarrel->getLocation($dir);
  if (!$gtitle) $gtitle = __("home");
}
$page->assign('greeting',__("Page manager") . ($gtitle ? " - $gtitle" : "") );

$page->assign('html',__("Import HTML"));
$page->assign('htmlblab',__("You can replace the current page's contents by uploading an HTML file below."));
$page->assign('htmlstrip',__("If the file name ends with .htm or .html, anything outside the &lt;body&gt;&lt;/body&gt; pair will be stripped."));
$page->assign('send',__("Send"));

$page->assign('dir',$dir);
$page->assign('page_obj', $bpage->props);  
$page->assign('status_opts',array(0=>__("visible"), 1=>__("under construction"), 2=>__("disabled"), 3=>__("archived")));
$page->assign('templates',$page->getTemplates());
  
if ($globals->word_import) {

  $page->assign('word',__("Import a Word document"));

  if (file_exists($bbarrel->spool->spoolPath($dir,$globals->wordfile)) )
  {
    $page->assign('wordblab', __("This page's master document is currently a Word document."));
    $page->assign('wordsend', __("You can upload a new version of the Word document below."));
    $page->assign('wordfile', __("You can get the current version of the file here"));
    $page->assign('wordlnk', array($globals->wordfile,$page->urlSite((strlen($bpage->props['location']) ? $bpage->props['location'].'/' : '') . $globals->wordfile)) );
  } else {
    $page->assign('wordblab', __("If you wish, you can set this page's content from a Word document."));
    $page->assign('wordsend', __("To do so, simply upload the Word document below."));
  }
}

$page->display('page-properties.tpl');
?>
