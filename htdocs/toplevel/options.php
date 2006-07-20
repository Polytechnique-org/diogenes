<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.logger-view.inc.php';
// dependency on PEAR
require_once 'System.php';

$page = new $globals->toplevel(true);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";

$rw_str_opts = array ('menu_style', 'menu_theme', 'template_dir', 'template', 'html_editor', 'word_import', 'barrel_style_sheet');
$rw_bool_opts = array('debugdatabase', 'debugplugins', 'validatepages');
$ro_opts = array ('menu_styles', 'menu_themes', 'html_editors', 'word_imports', 'style_sheets');

switch ($action) {
case "options":
  foreach ($rw_str_opts as $opt_name)
  {
    if (isset($_REQUEST[$opt_name]))
      $globals->updateOption($opt_name, $_REQUEST[$opt_name]);
  }
  foreach ($rw_bool_opts as $opt_name)
  {
    if (isset($_REQUEST[$opt_name]))
      $globals->updateOption($opt_name, $_REQUEST[$opt_name] ? 1 : 0);
  }
  break;
}


// fill out values
$all_opts = array_merge($ro_opts, $rw_str_opts, $ro_opts);
$all_opts = array_merge($all_opts, $rw_bool_opts);
foreach ($all_opts as $opt_name)
{
  if (!isset($globals->$opt_name)) {
    $page->info("warning : unknown option '$opt_name'");
  } else {
    $page->assign($opt_name, $globals->$opt_name);
  }
}
/*
if ($globals->menu_style == 1 || $globals->menu_style == 2) {
  $page->assign('menu_themes', $globals->menu_themes);
  $page->assign('menu_theme', $globals->menu_theme);
}
*/
$page->assign('templates', $page->getTemplates());

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
