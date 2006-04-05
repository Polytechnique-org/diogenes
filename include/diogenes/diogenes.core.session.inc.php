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

require_once dirname(__FILE__).'/diogenes.core.logger.inc.php';
require_once dirname(__FILE__).'/diogenes.flagset.inc.php';

/** cache of user <=> id matches */
$diogenes_core_usercache = array();

/** This class describes a Diogenes session.
 */
class DiogenesCoreSession {
  /** authentication challenge */
  var $challenge;

  /** unique user id */
  var $uid;
  /** username (login) */
  var $username;
  /** permissions */
  var $perms;
  
  /** The constructor.
   */
  function DiogenesCoreSession()
  {
    $this->challenge = md5(uniqid(rand(), 1));
    $this->perms = new flagset("");
  }


  /** Does the user have a given permission level.
   *
   * @param level
   */
  function hasPerms($level)
  {
    return $this->perms->hasflag($level);
  }


  /** Perform authentication. This needs to be overriden to do
   *  anything useful.
   *
   * @param page the calling page (by reference)
   */
  function doAuth(&$page) {
    global $globals;
    echo "DiogenesCoreSession::doAuth needs to be overriden";
    exit;

    // if we are already autentified, return
    if ($this->perms->hasflag("auth"))
      return;

    // do we have authentication tokens for auth ?
    if (isset($_REQUEST['some_token_needed_for_auth'])) {
      // here goes the authentication code
    } else {
      $this->doLogin($page);
    }
  }


  /** Display login screen. Needs to be overriden!
   *
   * @param page the page asking for authentication
   */
  function doLogin(&$page) {
    echo "DiogenesCoreSession::doLogin needs to be overriden";
    exit;
  }


  /** Returns the user id associated with a given username.
   *  We use caching to avoid unnecessary database requests.
   *
   *  Actual lookup is performed by the lookupUserId function.
   *
   * @param $auth the authentication method
   * @param $username the username to look up
   *
   * @see DiogenesLoggerView
   * @see lookupUserId
   */
  function getUserId($auth,$username) {
    global $diogenes_core_usercache, $globals;

    if (isset($diogenes_core_usercache[$auth]) and ($uid = array_search($username, $diogenes_core_usercache[$auth])))
    {
      
      // retrieve the result from cache
      return $uid;
      
    } else {
    
      // lookup the user id in database
      $uid = call_user_func(array($globals->session,'lookupUserId'),$auth,$username);
            
      // cache this result
      $diogenes_core_usercache[$auth][$uid] = $username;      
      return $uid;
    }    
    
  }


  /** Returns the username associated with a given user id.
   *  We use caching to avoid unnecessary database requests.
   *
   *  Actual lookup is performed by the lookupUsername function.
   *
   * @param $auth the authentication method
   * @param $uid the username to look up
   *
   * @see DiogenesLoggerView
   * @see lookupUsername
   */
  function getUsername($auth,$uid) {
    global $diogenes_core_usercache, $globals;

    if (isset($diogenes_core_usercache[$auth][$uid])) {
      
      // retrieve result from cache
      return $diogenes_core_usercache[$auth][$uid];
      
    } else {
      
      // lookup the user id in database
      $username = call_user_func(array($globals->session,'lookupUsername'),$auth,$uid);
    
      // cache this result
      $diogenes_core_usercache[$auth][$uid] = $username;
      
      return $username;
    }
        
  }
  

  /** Look up the user id associated with a given username.
   *
   * @param $auth the authentication method
   * @param $username the username to look up
   *
   * @see DiogenesLoggerView
   */
  function lookupUserId($auth, $username)
  {
    global $globals;
    
    $res = $globals->db->query("select user_id from {$globals->tauth[$auth]} where username='$username'");
    list($uid) = mysql_fetch_row($res);
    mysql_free_result($res);
    
    return $uid;
  }

  
  /** Looks up the username associated with a given user id.
   *
   * @param $auth the authentication method
   * @param $uid the username to look up
   *
   * @see DiogenesLoggerView
   */      
  function lookupUsername($auth, $uid)
  {
    global $globals;
   
    $res = $globals->db->query("select username from {$globals->tauth[$auth]} where user_id='$uid'");
    list($username) = mysql_fetch_row($res);
    mysql_free_result($res);    
    
    return $username;    
  }
  
}

?>
