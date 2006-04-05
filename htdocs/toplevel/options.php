<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.logger-view.inc.php';
// dependency on PEAR
require_once 'System.php';

$page = new $globals->toplevel(true);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";

switch ($action) {
case "options":
  $globals->updateOption("menu_style", $_REQUEST['menu_style']);
  if (isset($_REQUEST['menu_theme']))
    $globals->updateOption("menu_theme", $_REQUEST['menu_theme']);
  $globals->updateOption("template_dir", $_REQUEST['template_dir']);
  $globals->updateOption("template", $_REQUEST['template']);
  $globals->updateOption("html_editor", $_REQUEST['html_editor']);
  $globals->updateOption("word_import", $_REQUEST['word_import']);
  $globals->updateOption("debugdatabase", $_REQUEST['debugdatabase'] ? 1 : 0);
  $globals->updateOption("debugplugins", $_REQUEST['debugplugins'] ? 1 : 0);
  $globals->updateOption("validatepages", $_REQUEST['validatepages'] ? 1 : 0);
  break;
}


// fill out values
$page->assign('menu_styles', $globals->menu_styles);
$page->assign('menu_style', $globals->menu_style);
if ($globals->menu_style == 1 || $globals->menu_style == 2) {
  $page->assign('menu_themes', $globals->menu_themes);
  $page->assign('menu_theme', $globals->menu_theme);
}
$page->assign('template_dir', $globals->template_dir);
$page->assign('template', $globals->template);
$page->assign('templates', $page->getTemplates());

$page->assign('validatepages', $globals->validatepages);

$page->assign('html_editors', $globals->html_editors);
$page->assign('html_editor', $globals->html_editor);

$page->assign('word_imports', $globals->word_imports);
$page->assign('word_import', $globals->word_import);

$page->assign('debugdatabase', $globals->debugdatabase);
$page->assign('debugplugins', $globals->debugplugins);

// translations
$page->assign('greeting', __("Global options"));
$page->toolbar(__("Mode"), array( __("standard"), array(__("expert"), "options_expert.php")));
$page->assign('msg_display_options', __("display options"));
$page->assign('msg_menu_style', __("menu style"));
$page->assign('msg_menu_theme', __("menu theme"));
$page->assign('msg_site_template_dir', __("templates directory"));
$page->assign('msg_site_template', __("default template"));
$page->assign('msg_validate_pages', __("display W3C validator links for barrel pages"));
$page->assign('msg_system_options', __("system options"));
$page->assign('msg_html_editor', __("HTML editor"));
$page->assign('msg_word_import', __("Word document import"));
$page->assign('msg_debug_options', __("debugging options"));
$page->assign('msg_debug_database', __("debug database"));
$page->assign('msg_debug_plugins', __("debug plugins"));
$page->assign('msg_submit', __("Submit"));

$page->display('toplevel-options.tpl');

?>
