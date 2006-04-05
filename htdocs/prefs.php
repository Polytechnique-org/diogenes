<?php
if (isset($_REQUEST['nlang'])) {
  setcookie('lang',$_REQUEST['nlang'],(time()+25920000));
  $_COOKIE['lang'] = $_REQUEST['nlang'];
}

// include common definitions
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
$page = new $globals->toplevel;
$_SESSION['session']->doAuth($page);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
$myuid = $_SESSION['session']->uid;

switch ($action) {
case "passwd":
  if ($_SESSION['session']->auth == "native") {
    $page->info(__("Changing password.."));
    $newpass = $_REQUEST['newpass'];
    $globals->db->query("update {$globals->tauth['native']} set password='$newpass' where user_id='$myuid'");
  }
  break;
}


$page->assign('md5',$page->url('md5.js'));
$page->assign('native',$_SESSION['session']->auth == "native");
$page->assign('username',$_SESSION['session']->username);
$page->assign('fullname',$_SESSION['session']->fullname);
$page->assign('langs', $globals->locales);

$page->assign('page', __("User preferences"));
$page->assign('greeting', __("Diogenes preferences"));

$page->assign('msg_myinfo' ,__("my information"));
$page->assign('msg_username' ,__("username"));
$page->assign('msg_fullname' ,__("full name"));

$page->assign('msg_lang', __("language"));
$page->assign('msg_lang_blab', __("You can select your preferred language by clicking on the appropriate language below."));

$page->assign('msg_mypassword' ,__("my password"));
$page->assign('msg_password' ,__("new password"));
$page->assign('msg_confirmation' ,__("confirmation"));

$page->assign('submit', __("Change"));

$page->display("prefs.tpl");
?>
