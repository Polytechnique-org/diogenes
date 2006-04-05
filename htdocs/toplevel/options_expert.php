<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.table-editor.inc.php';

// set up the page
$page = new $globals->toplevel(true);
$page->assign("greeting",__("Global options"));
$page->toolbar(__("Mode"), array( array( __("standard"), "options.php" ), __("expert")));

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";

switch ($action) {
case "cvs-rcs":
  $page->info("converting spool from CVS to RCS..");
  $res = $globals->db->query("select alias from diogenes_site");
  while(list($site) = mysql_fetch_row($res)) {
    $page->info("processing '$site'..");
    $spool = new DiogenesSpool($page,$site);
    $page->info("-> deleting 'CVS' subdirectories from spool..");
    $goners = System::find($spool->datadir.' -type d');
    foreach($goners as $goner) {
      if (basename($goner) == "CVS")
        System::rm("-r $goner");
    }
    $page->info("-> changing files in spool to read-only..");
    $modfiles = System::find($spool->datadir.' -type f');
    foreach($modfiles as $modfile) {
      chmod($modfile,0444);
    }
  }
  $globals->updateOption("rcs","DiogenesRcs");
  break;

case "rcs-cvs":
  $page->info("converting spool from RCS to CVS..");
  if (!is_dir("{$globals->rcsroot}/CVSROOT")) {
    $page->info("-> no CVSROOT not found, running init..");
    if ($ret = shell_exec(escapeshellcmd("cvs -d".escapeshellarg($globals->rcsroot)." init")))
      $page->info($ret);
  }

  // remove current spool dirs and do a clean checkout of each site
  $res = $globals->db->query("select alias from diogenes_site");
  while(list($site) = mysql_fetch_row($res)) {
    $page->info("processing '$site'..");
    $page->info("-> removing '{$globals->spoolroot}/$site'..");
    System::rm("-r {$globals->spoolroot}/$site");
    $page->info("-> doing a checkout of module '$site'..");
    $spool = new DiogenesCvs($page,$site,$_SESSION['session']->username,true);
  }
  $globals->updateOption("rcs","DiogenesCvs");
  break;
}

$page->assign('msg_convert',__("Convert"));
$page->assign('msg_vcs',__("version control system"));

switch ($globals->rcs) {
case "DiogenesRcs":
  $page->assign('msg_current_vcs',__("You are currently using RCS as the version control system for your barrels."));
  $page->append('conversions',array("rcs-cvs", __("You can switch to a full CVS repository by clicking here.")));
  break;

case "DiogenesCvs":
  $page->assign('msg_current_vcs',__("You are currently using CVS as the version control system for your barrels."));
  $page->append('conversions',array("cvs-rcs", __("You can switch back to RCS by clicking here.")));
  break;
}


// set up the editor
$editor = new DiogenesTableEditor($globals->table_global_options,"name",true);
$editor->add_where_condition("barrel=''");
$editor->hide('barrel', '');
$editor->describe("name", __("option"), false);
$editor->describe("value", __("value"), true);

// run the editor
$editor->table_style = "width:80%";
$editor->run($page,'editor_content');

$page->display('toplevel-options_expert.tpl');
?>
