<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';

$page = new DiogenesAdmin;
$bbarrel =& $page->barrel;
$page->assign('greeting',__("Site options"));
$page->assign('post',$page->script_self());

// handle update request
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
switch ($action) {
case "update":
  $bbarrel->options->updateOption("title", $_REQUEST['title']);
  $bbarrel->options->updateOption("description", $_REQUEST['description']);
  $bbarrel->options->updateOption("keywords", $_REQUEST['keywords']);
  $bbarrel->options->updateOption("favicon", $_REQUEST['favicon']);
  if (isset($_REQUEST['template']))
    $bbarrel->options->updateOption("template", $_REQUEST['template']);
  if (isset($_REQUEST['template_dir']))
    $bbarrel->options->updateOption("template_dir", $_REQUEST['template_dir']);
  $bbarrel->options->updateOption("menu_min_level", $_REQUEST['menu_min_level']);
  $bbarrel->options->updateOption("menu_style", $_REQUEST['menu_style']);
  if (isset($_REQUEST['menu_theme']))
    $bbarrel->options->updateOption("menu_theme", $_REQUEST['menu_theme']);
  $bbarrel->options->updateOption("menu_hide_diogenes", $_REQUEST['menu_hide_diogenes']);
  
  // log this action
  $page->log('barrel_options', $bbarrel->alias.":*");
  break;
}

// fill out values
/* general options */
$page->assign('title', $bbarrel->options->title);
$page->assign('description', $bbarrel->options->description);
$page->assign('keywords', $bbarrel->options->keywords);
$page->assign('favicon', $bbarrel->options->favicon);
/* template options */
$page->assign('template', $bbarrel->options->template);
$page->assign('templates', $page->getTemplates());
if ($bbarrel->hasFlag('tpl')) {
  $page->assign('template_dir', $bbarrel->options->template_dir);
  $res = $globals->db->query("SELECT PID,location from {$bbarrel->table_page} ORDER BY location");
  while (list($myPID,$myLocation) = mysql_fetch_row($res)) 
    $template_dirs[$myPID] = $myLocation ? $myLocation : "<home>";
  mysql_free_result($res);
  $page->assign('template_dirs', $template_dirs);
}
/* menu options */
$page->assign('menu_hide_diogeness', array(0 => __("no"), 1 => __("yes")));
$page->assign('menu_hide_diogenes', $bbarrel->options->menu_hide_diogenes);
$page->assign('menu_styles', $globals->menu_styles);
$page->assign('menu_style', $bbarrel->options->menu_style);
if ($bbarrel->options->menu_style == 1 || $bbarrel->options->menu_style == 2) {
  $page->assign('menu_themes', $globals->menu_themes);
  $page->assign('menu_theme', $bbarrel->options->menu_theme);
}
$page->assign('menu_levels',array(0=> __("fully expanded"), 1=>'1', 2=>'2', 3=>'3', 4=>'4'));
$page->assign('menu_min_level', $bbarrel->options->menu_min_level);

// translations
$page->assign('msg_general_options', __("general options"));
$page->assign('msg_title', __("title"));
$page->assign('msg_description', __("description"));
$page->assign('msg_keywords', __("keywords"));
$page->assign('msg_favicon', __("favicon"));
$page->assign('msg_favicon_hint', __("(relative url to a PNG image)"));
$page->assign('msg_display_options', __("display options"));
$page->assign('msg_site_template_dir', __("templates directory"));
$page->assign('msg_site_template', __("default template"));
$page->assign('msg_menu_hide_diogenes', __("hide Diogenes menu"));
$page->assign('msg_menu_style', __("menu style"));
$page->assign('msg_menu_theme', __("menu theme"));
$page->assign('msg_menu_min_level', __("minimum menu levels to expand"));
$page->assign('msg_submit', __("Submit"));
$page->display('admin-options.tpl');
?>
