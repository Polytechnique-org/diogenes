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


/** class for logging user activity
 *
 */
class DiogenesCoreLogger {
  /** user id */
  var $uid;
  /** id of the session */
  var $session;
  /** list of available actions */
  var $actions;

  /** db table holding the list of actions */
  var $table_actions;
  /** db table holding the list of actions */
  var $table_events;
  /** db table holding the list of actions */
  var $table_sessions;

  /** The constructor, creates a new entry in the sessions table
   *
   * @param $uid the id of the logged user
   * @param $suid the id of the administrator who has just su'd to the user
   * @param $auth authentication method for the logged user
   * @param $sauth authentication method for the su'er
   * @return VOID
   */
  function DiogenesCoreLogger($uid,$suid='',$auth='',$sauth='') {
    global $globals;

    // read database table names from globals
    $this->table_actions = $globals->table_log_actions;
    $this->table_events = $globals->table_log_events;
    $this->table_sessions = $globals->table_log_sessions;

    // write the session entry
    $this->uid = $uid;
    $this->session = $this->writeSession($uid,$suid,$auth,$sauth);

    // retrieve available actions
    $this->actions = $this->readActions();
  }


  /** Creates a new session entry in database and return its ID.
   * 
   * @param $uid the id of the logged user
   * @param $suid the id of the administrator who has just su'd to the user
   * @param $auth authentication method for the logged user
   * @param $sauth authentication method for the su'er
   * @return session the session id
   */
  function writeSession($uid,$suid,$auth,$sauth) {
    global $globals;

    $ip = $_SERVER['REMOTE_ADDR'];
    $host = strtolower(gethostbyaddr($_SERVER['REMOTE_ADDR']));
    $browser = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    $sql = "INSERT INTO {$this->table_sessions} SET uid='$uid',host='$host',ip='$ip',browser='$browser'";
    // optional parameters
    if ($suid)
      $sql .= ",suid='$suid'";
    if ($auth)
      $sql .= ",auth='$auth'";
    if ($sauth)
      $sql .= ",sauth='$sauth'";

    $globals->db->query($sql);
    
    return $globals->db->insert_id();
  }


  /** Reads available actions from database.
   *
   * @return actions the available actions
   */
  function readActions() {
    global $globals;

    $res=$globals->db->query("SELECT id,text FROM {$this->table_actions}");
    while(list($action_id,$action_text)=mysql_fetch_row($res))
      $actions[$action_text] = $action_id;

    mysql_free_result($res);
    
    return $actions;
  }

  
  /** Logs an action and its related data.
   *
   * @param $action le type d'action
   * @param $data les données (id de liste, etc.)
   * @return VOID
   */
  function log($action,$data="") {
    global $globals;

    if (isset($this->actions[$action]))
      $globals->db->query("INSERT INTO {$this->table_events} SET session='{$this->session}',action='{$this->actions[$action]}',data='{$data}'");
    else
      echo "unknown action : $action<br />";
  }

  /** Print out the id for the current session
   * @return VOID
   */
  function debug() {
    echo "session=".$this->session."<br />";
    echo "uid=".$this->uid."<br />";
    print_r($this->actions);
    echo "<br />";
  }
}

?>
