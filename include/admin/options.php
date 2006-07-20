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
  $opt_names = array ('title', 'description', 'keywords', 'favicon', 'template', 'template_dir', 'menu_min_level', 'menu_style', 'menu_theme', 'menu_hide_diogenes', 'feed_enable');
  foreach ($opt_names as $opt_name)
  {
    if (isset($_REQUEST[$opt_name]))
      $bbarrel->options->updateOption($opt_name, $_REQUEST[$opt_name]);
  }
  
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

/* RSS feed options */
$page->assign('feed_enable_vals', array(0 => __("no"), 1 => __("yes")));
$page->assign('feed_enable', $bbarrel->options->feed_enable);

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
$page->assign('msg_feed_options', __("RSS feed options"));
$page->assign('msg_feed_enable', __("enable RSS feed"));
$page->assign('msg_submit', __("Submit"));
$page->display('admin-options.tpl');
?>
