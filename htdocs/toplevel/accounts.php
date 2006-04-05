<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.table-editor.inc.php';

$page = new $globals->toplevel(true);
$page->assign('greeting',__("User accounts"));

$editor = new DiogenesTableEditor("diogenes_auth","user_id");

$editor->add_join_table("diogenes_perm","uid",true);

$editor->describe("username", __("username"), true);
$editor->describe("firstname", __("first name"), true);
$editor->describe("lastname", __("last name"), true);
$editor->describe("password", __("password"), false, "password");
$editor->describe("perms", __("permissions"), true, "set");

$editor->addAction(__("view log"), "logger.php?logauth=native&amp;loguser=%username%");
$editor->run($page,'page_content');
$page->display('');
?>
