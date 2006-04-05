<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.table-editor.inc.php';

$page = new $globals->toplevel(false);
$page->assign('greeting',__("Initial setup"));

// check we are using native Diogenes authentication
if ((count($globals->tauth) != 1) or empty($globals->tauth["native"])) {
  $page->assign('page_content', __("Sorry, this feature is only available using native Diogenes authentication."));
  $page->display('');
  exit;
}

// check there are currently no admins
$res = $globals->db->query("select username from {$globals->tauth["native"]} where perms='admin'");
if (list($username) = mysql_fetch_row($res)) {
  $page->assign('page_content', __("The database is already configured."));
  $page->display('');
  exit;
}

$editor = new DiogenesTableEditor("diogenes_auth","user_id");

$editor->add_join_table("diogenes_perm","uid",true);

$editor->describe("username", __("username"), true);
$editor->describe("firstname", __("first name"), true);
$editor->describe("lastname", __("last name"), true);
$editor->describe("password", __("password"), false, "password");
$editor->describe("perms", __("permissions"), true, "set");

$editor->lock("username", "root");
$editor->lock("perms", "admin");
if (empty($_REQUEST['action']) or ($_REQUEST['action'] != 'update'))
{
  $_REQUEST['action'] = 'edit';
}

$editor->run($page,'page_content');

// check if we have completed initial setup
$res = $globals->db->query("select username from {$globals->tauth["native"]} where perms='admin'");
if (list($username) = mysql_fetch_row($res)) {
  $page->assign('page_content', __("The initial setup of the database was performed successfuly."));
}

$page->display('');
?>
