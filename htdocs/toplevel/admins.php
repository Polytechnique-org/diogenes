<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';

$page = new $globals->toplevel(true);
$page->assign('post',$page->script_self());
$page->assign('greeting', __("Site administrators"));
$page->assign('msg_site',__("site"));
$page->assign('msg_admin',__("administrator"));
$page->assign('msg_actions',__("actions"));

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
switch($action) {
case "add":
  if (isset($_REQUEST['target']) && isset($_REQUEST['auth']) && isset($_REQUEST['username'])) {
    $auth = $_REQUEST['auth'];
    if ($uid = call_user_func(array($globals->session,'getUserId'),$auth,$_REQUEST['username']))
      $globals->db->query("replace into diogenes_perm set alias='{$_REQUEST['target']}',auth='$auth',uid='$uid',perms='admin'");
    else
      $page->info(__("Could not find requested user")." '{$_REQUEST['username']}'");
  }
  break;

case "demote":
  if (isset($_REQUEST['target']) && isset($_REQUEST['uid']))
      $globals->db->query("replace into diogenes_perm set alias='{$_REQUEST['target']}',auth='{$_REQUEST['auth']}',uid='{$_REQUEST['uid']}',perms='user'");
  break;

case "remove":
  if (isset($_REQUEST['target']) && isset($_REQUEST['uid']))
    $globals->db->query("delete from diogenes_perm where alias='{$_REQUEST['target']}' and auth='{$_REQUEST['auth']}' and uid='{$_REQUEST['uid']}'");
  break;
}

// add alias/username entries
$odd = false;
$res = $globals->db->query("select s.alias,s.vhost,auth,uid from diogenes_perm as p".
                           " left join diogenes_site as s on s.alias=p.alias".
                           " where p.perms='admin' order by alias,auth");
while (list($target,$vhost,$auth,$uid) = mysql_fetch_row($res)) {
  $username = call_user_func(array($globals->session,'getUsername'),$auth,$uid);
  $actions = array(
    array(__("site users"), $page->urlBarrel($target,$vhost,"admin/users")),
    array(__("demote to user"),"?action=demote&amp;target=$target&amp;auth=$auth&amp;uid=$uid"),
    array(__("remove"),"?action=remove&amp;target=$target&amp;auth=$auth&amp;uid=$uid"),
    array(__("view log"),"logger.php?logauth=$auth&amp;loguser=$username")

  );
  $page->append('entries',array($odd ? "odd" : "even",$target,$globals->tlabel[$auth],$username,$actions));
  $odd = !$odd;
}
mysql_free_result($res);

// add the sites
$res = $globals->db->query("select alias from diogenes_site");
$sites = array();
while (list($target) = mysql_fetch_row($res))
  $sites[$target]=$target;
$page->assign('sites', $sites);
mysql_free_result($res);

// auth methods
foreach ($globals->tauth as $key=>$val)
  $auths[$key]=$globals->tlabel[$key];
$page->assign('auths',$auths);

$page->display('toplevel-admins.tpl');
?>
