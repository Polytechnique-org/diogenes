<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';

$page = new DiogenesAdmin;

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
switch($action) {
case "add":
  if (isset($_REQUEST['auth']) && isset($_REQUEST['username'])) {
    $auth = $_REQUEST['auth'];
    if ($uid = call_user_func(array($globals->session,'getUserId'),$auth,$_REQUEST['username']))
      $globals->db->query("insert into diogenes_perm set alias='{$page->alias}',auth='$auth',uid='$uid',perms='user'");
    else
      $page->info(__("Could not find requested user")." '{$_REQUEST['username']}'");
  }
  break;
case "remove":
  if (isset($_REQUEST['auth']) && isset($_REQUEST['uid']))
    $globals->db->query("delete from diogenes_perm where alias='{$page->alias}' and auth='{$_REQUEST['auth']}' and uid='{$_REQUEST['uid']}' and perms='user'");
  break;
}

$page->assign('greeting',__("Users administration"));
$page->assign('msg_users',__("Registered users"));
$page->assign('msg_admins',__("Administrators"));
$page->assign('post',$page->script_self());
$page->assign('user',__("user"));
$page->assign('action',__("action"));

// retrieve the list of users
$res = $globals->db->query("select uid,auth from diogenes_perm where alias='{$page->alias}' and perms='user'");
while (list($uid,$auth) = mysql_fetch_row($res)) {
  $username = call_user_func(array($globals->session,'getUsername'),$auth,$uid);
  $page->append('users',array($username,$globals->tlabel[$auth],array(__("remove"),"?action=remove&amp;auth=$auth&amp;uid=$uid")));
}
mysql_free_result($res);

// retrieve the list of admins
$res = $globals->db->query("select uid,auth from diogenes_perm where alias='{$page->alias}' and perms='admin'");
while (list($uid,$auth) = mysql_fetch_row($res)) {
  $username = call_user_func(array($globals->session,'getUsername'),$auth,$uid);
  $page->append('admins',array($username,$globals->tlabel[$auth]));
}
mysql_free_result($res);

// auth methods
foreach ($globals->tauth as $key=>$val)
  $auths[$key]=$globals->tlabel[$key];
$page->assign('auths',$auths);  
$page->display('admin-users.tpl');

?>
