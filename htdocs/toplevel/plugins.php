<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'Plugin/Editor.php';

$page = new $globals->toplevel(true);
$page->assign('post',$page->script_self());

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$barrel = isset($_REQUEST['plug_barrel']) ? $_REQUEST['plug_barrel'] : '';

if ($action == "clean")
{
  $globals->plugins->clean_database($page);  
}

if ($barrel)
{
  $page->assign('greeting',__("Barrel plugins") . " - $barrel");
} else {
  $page->assign('greeting',__("Global plugin settings"));
}

/* plugin editor */
$editor = new Diogenes_Plugin_Editor($barrel, 0);
$editor->hide_params(1);
$editor->run($page,'editor_content');

// if necessary, rebuild site plugin caches
if ($action == "update" && !$barrel)
{
  $res = $globals->db->query("select alias,flags from diogenes_site");
  while (list($p_alias, $p_flags) = mysql_fetch_row($res))
  {
    $flags = new flagset($p_flags);
    if ($p_alias && $flags->hasFlag('plug')) 
    {
      $page->info(sprintf( __("Rebuilding plugin cache for barrel '%s'"), $p_alias));
      $cachefile = $globals->plugins->cacheFile($p_alias);
      $globals->plugins->compileCache($cachefile, $p_alias);
    }
  }
  mysql_free_result($res);
}

// translations
$page->assign('msg_clean_database', __('Clean plugins database'));
$page->assign('msg_clean_database_text', __("If you are having problems with references to plugins that no longer exist, you can have Diogenes remove such entries from the database."));
$page->assign('msg_clean', __("Clean"));

$page->assign('msg_enable_disable', __('Enable or disable plugins'));
if ($barrel)
{
  $page->assign('msg_enable_disable_text', __("You can select the plugins you want to enable for this barrel."));
} else {
  $page->assign('msg_enable_disable_text', __("You can select the plugins you want to enable or disable globally, that is the plugins that can be used in the different barrels. Please note that for a plugin to be accessible from a barrel, you will need to activate that plugin for the barrel. To do this, from the <i>List of sites</i> click on 'plugins' next to the barrel of your choice."));
}
$page->display('toplevel-plugins.tpl');

?>
