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


require_once 'diogenes/diogenes.core.logger.inc.php';

/** Class for logging WebDAV activity.
 *
 * The difference with DiogenesCoreLogger is that we do not have
 * PHP sessions in WebDAV mode so we need a small hack to avoid creating
 * a new 'session' entry for each operation.
 */
class DiogenesWebDAVLogger extends DiogenesCoreLogger {

  /** is this a new WebDAV 'session' ? */
  var $newsession;

  /** WebDAV 'session' duration in seconds */
  var $sessionlength = 1800;

  /** The constructor, creates a new entry in the sessions table
   *
   * @param $uid the id of the logged user
   * @param $auth authentication method for the logged user
   * @param $username the username of the logged user
   * @return VOID
   */
  function DiogenesWebDAVLogger($uid,$auth,$username) {
    global $globals;

    $this->DiogenesCoreLogger($uid,'',$auth,'');

    if ($this->newsession) {
      $this->log("auth_ok","{$username}@WebDAV");
    }
  }


  /** Try to pickup an existing session, otherwise create a new entry
   * 
   * @param $uid the id of the logged user
   * @param $suid the id of the administrator who has just su'd to the user
   * @param $auth authentication method for the logged user
   * @param $sauth authentication method for the su'er
   * @return session the session id
   */
  function writeSession($uid,$suid,$auth,$sauth) {
    global $globals;

    // we look for a session with the same user, auth and browser that is less
    // than $sessionlength seconds old
    $browser = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    $stime = date("YmdHis",time()-$this->sessionlength);
    $res = $globals->db->query("select id from {$this->table_sessions} where uid='$uid' and auth='$auth' and browser='$browser' and start > $stime");
    
    if (list($session) = mysql_fetch_row($res)) {
       // we have an existing session
      $this->newsession = false;
    } else {
      // we do not have an existing session
      $this->newsession = true;
      $session = parent::writeSession($uid,$suid,$auth,$sauth);
    }
    mysql_free_result($res);
    return $session;
  }

} 

?>
