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


/** This class describes Diogenes' global settings.
 */
class DiogenesCoreGlobals {
  /** Absolute directory location of the Diogenes library */
  var $libroot;
  /** Absolute directory location of Diogenes root. */
  var $root;
  /** The Diogenes root URL */
  var $rooturl;

  /** The database handler. */
  var $db;

  /** The database. */
  var $dbdb = "diogenes";
  /** The database server. */
  var $dbhost = "localhost";
  /** The user to access the database. */
  var $dbuser = "diogenes";
  /** The password to connect to the database. */
  var $dbpwd;

  /** The database table holding the logger actions */
  var $table_log_actions = "diogenes_logactions";
  /** The database table holding the logger events */
  var $table_log_events = "diogenes_logevents";
  /** The database table holding the logger sessions */
  var $table_log_sessions = "diogenes_logsessions";
  /** The class to use for session handling. */
  var $session = 'DiogenesCoreSession';
  /** The function to call for translation. */
  var $gettext = 'gettext';


  /** Connect to the database server.
   */
  function dbconnect()
  {
    $db = new DiogenesDatabase($this->dbdb, $this->dbhost, $this->dbuser, $this->dbpwd);
    if (!$db->connect_id)
      die("Could not connect to database (".mysql_error().")");
    $this->db = $db;
  }

  /** Add some automatic hyperlinks to a text.
   *
   * @param in the text to beautify
   */
  function urlise($in)
  {
    $out = str_replace("Polytechnique.org","<a href=\"http://www.polytechnique.org/\">Polytechnique.org</a>",$in);
    $out = str_replace("Diogenes","<a href=\"http://opensource.polytechnique.org/diogenes/\">Diogenes</a>",$out);
    return $out;
  }

}


/** Translation function.
 *
 * @param msg the message to translate
 */
function __($msg)
{
  global $globals;
  $func = $globals->gettext;

  if (function_exists($func))
    return $func($msg);
  else
    return $msg;
}


?>
