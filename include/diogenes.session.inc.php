<?php
/*
 * Copyright (C) 2003-2004 Polytechnique.org
 * http://opensource.polytechnique.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once 'diogenes/diogenes.core.session.inc.php';
require_once 'diogenes/diogenes.core.logger.inc.php';

/** This class describes a Diogenes session.
 */
class DiogenesSession extends DiogenesCoreSession {
  /** full name */
  var $fullname;
  /** is this a native Diogenes account? */
  var $auth = "native";


  /** The constructor.
   */
  function DiogenesSession() {
    $this->DiogenesCoreSession();
    $this->username = "anonymous";
    $this->perms->addFlag('public');
  }


  /** Try to do a Diogenes authentication.
   *
   * @param page the calling page (by reference)
   */
  function doAuth(&$page) {
    global $globals;

    if ($this->perms->hasflag("auth"))
      return;

    /* do we have authentication tokens for auth ? */
    if (isset($_REQUEST['login']) && isset($_REQUEST['response'])) {
      // remember login for a year
      setcookie('DiogenesLogin',$_REQUEST['login'],(time()+25920000));

      // lookup user
      $res = $globals->db->query("SELECT user_id,username,password,firstname,lastname,perms FROM {$globals->tauth['native']} WHERE username='{$_REQUEST['login']}'");
      if (!list($uid,$username,$password,$firstname,$lastname,$perms) = mysql_fetch_row($res)) {
        $page->info(__("Authentication error!"));
        $this->doLogin($page);
      }
      mysql_free_result($res);

      // check response
      if ($_REQUEST['response'] != md5("{$_REQUEST['login']}:$password:{$this->challenge}"))
      {
        // log the login failure
        $logger = new DiogenesCoreLogger($uid);
        $logger->log("auth_fail",$_REQUEST['login']);
        $page->info(__("Authentication error!"));
        $this->doLogin($page);
      }

      // retrieve user info
      $this->uid = $uid;
      $this->username = $username;
      $this->firstname = $firstname;
      $this->lastname = $lastname;
      $this->fullname = $firstname . ($lastname ? " $lastname" : "");

      // create logger
      $logstr = $this->username . (empty($page->alias) ? "" : "@{$page->alias}");
      $_SESSION['log'] = new DiogenesCoreLogger($this->uid);
      $_SESSION['log']->log("auth_ok",$logstr);

      // set user permissions
      $this->perms->addFlag('auth');
      if ($perms == "admin") {
        $this->perms->addflag('root');
      }

    } else {
      $this->doLogin($page);
    }
  }


  /** Try to login for WebDAV (plain-text password).
   *
   *  Return true for success, false for failure.
   */
  function doAuthWebDAV($user,$pass)
  {
    global $globals;

    if ($this->perms->hasflag("auth"))
      return true;

    // check credentials
    $pass = md5($pass);
    $res = $globals->db->query("select user_id,username,perms from {$globals->tauth['native']} where username='$user' and password='$pass'");
    if (!list($uid,$user,$perms) = mysql_fetch_row($res))
      return false;      

    // retrieve user info
    $this->uid = $uid;
    $this->username = $user;

    // create logger
    $_SESSION['log'] = new DiogenesWebDAVLogger($this->uid,$this->auth,$this->username);

    // set user permissions
    $this->perms->addFlag('auth');
    if ($perms == "admin") {
      $this->perms->addflag('root');
    }

    return true;
  }


  /** Display login screen.
   */
  function doLogin(&$page) {
    $page->assign('greeting',__("Diogenes login"));
    $page->assign('msg_connexion', __("Connexion"));
    $page->assign('msg_password',__("password"));
    $page->assign('msg_submit',__("Submit"));
    $page->assign('msg_username', __("username"));

    if (isset($_COOKIE['DiogenesLogin']))
      $page->assign('username', $_COOKIE['DiogenesLogin']);
    $page->assign('post',htmlentities($page->script_uri()));
    $page->assign('challenge',$this->challenge);
    $page->assign('md5',$page->url("md5.js"));
    $page->display('login.tpl');
    exit;
  }


  /** Read a user's permissions for a given barrel.
   *
   * @param alias the name of the barrel
   */
  function setBarrelPerms($alias) {
    global $globals;

    // if the user is logged in, refresh his/her permissions
    if ($this->perms->hasflag('auth')) {
      if ($this->perms->hasflag('root')) {
        $this->perms->addflag('user');
        $this->perms->addflag('admin');
      } else {
        $this->perms->rmflag('user');
        $this->perms->rmflag('admin');
      }

      // read site specific permissions
      $res = $globals->db->query("select perms from diogenes_perm where alias='{$alias}'".
                         " and auth='{$this->auth}' and uid='{$this->uid}'");
      if (mysql_num_rows($res)>0) {
        $this->perms->addflag('user');
        list($tmp) = mysql_fetch_row($res);
        $this->perms->addflag($tmp);
      }
      mysql_free_result($res);
    }
  }

}

?>
