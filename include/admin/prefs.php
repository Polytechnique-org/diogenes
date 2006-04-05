<?php
if (isset($_REQUEST['nlang'])) {
  setcookie('lang',$_REQUEST['nlang'],(time()+25920000));
  $_COOKIE['lang'] = $_REQUEST['nlang'];
}

// include common definitions
require_once 'diogenes.common.inc.php';
require_once 'diogenes.barrel.inc.php';
$page = new $globals->barrel;
$page->startSession();
$_SESSION['session']->doAuth($page);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$myuid = $_SESSION['session']->uid;

$page->assign('langs', $globals->locales);
$page->assign('global_prefs',$page->url('prefs.php'));

$page->assign('page', __("User preferences"));
$page->assign('greeting', __("Diogenes preferences"));

$page->assign('msg_lang', __("language"));
$page->assign('msg_lang_blab', __("You can select your preferred language by clicking on the appropriate language below."));

$page->assign('submit', __("Change"));

$page->assign('msg_global_prefs', __("account preferences"));

$page->display("admin-prefs.tpl");
?>
