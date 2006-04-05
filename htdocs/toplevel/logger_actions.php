<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.table-editor.inc.php';

// set up the page
$page = new $globals->toplevel(true);
$page->assign("greeting",__("Logger actions"));

// set up the editor
$editor = new DiogenesTableEditor($globals->table_log_actions,"id");
$editor->add_join_table($globals->table_log_events,"action",true);
$editor->describe("text", __("title"), true);
$editor->describe("description", __("description"), true);

// run the editor
$editor->run($page,'page_content');
$page->display('');
?>
