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


/** Class for handling database requests. It is a wrapper around a permanent
 *  MySQL database connection with some commonly used functions.
 */
class DiogenesDatabase {
  /** The id of the permanent database connection.
   */
  var $connect_id;
  
  /** Are we in debugging mode.
   * This variable affects whether we make detailed records of everything 
   * we do.  Useful for debugging, but I imagine it probably slows down
   * regular use a bit.
   *
   * @see $_trace_data
   */
  var $_trace = false;
  
  /** Historical trace data.
   * If our queries are being logged, all of the relevant data will end up
   * in this variable.
   *
   * @see $_trace
   */
  var $_trace_data = Array();

  /** Whether database errors are fatal.
   */
  var $_fatal = false;

  /** The numeric code of the last error that occured.
   */
  var $_errno = 0;
  
  /** The string describing the last error that occured.
   */
  var $_errstr = '';

  /** Extra info about what might have caused the error.
   */
  var $_errinfo = '';

  /** The constructor.
   *
   * @param $database The name of the database to connect to
   * @param $host The computer hosting the database server
   * @param $user The username to use to authenticate to the database server
   * @param $password The password to use in authenticating to the database server
   */
  function DiogenesDatabase($database, $host, $user, $password) {
    global $globals;
    
    // make sure that we have MySQL support, try loading it
    if (!extension_loaded('mysql') && !dl('mysql.so'))
    {
      echo "MySQL support needs to be activated in your PHP configuration!<br>\n";
      echo "Add a line with 'extension=mysql.so' in your php.ini file.\n";
      exit(1);
    }

    $this->_fatal = @$this->database_error_fatal;
        
    if(empty($user)){
      $this->connect_id=@mysql_connect();
    } else {
      $this->connect_id=@mysql_connect($host, $user, $password);
    }

    if (!$this->connect_id) {
      $this->_handleError("");
      return;
    }
        
    if (!@mysql_select_db($database,$this->connect_id))
    {
      $this->_handleError("");
      return;
    }

    // when the script exits, we close the connection to the DB
    register_shutdown_function(array(&$this, 'close'));
  }


  /** Close connection to database 
   */
  function close() {
      mysql_close($this->connect_id);
      $this->connect_id = FALSE;
  }


  /** Deactivate trace mode.
   */
  function trace_off() {
      $this->_trace = false;
  }

  
  /** Activate trace mode.
   */
  function trace_on() {
      $this->_trace = true;
  }


  /** Formats tracing information for output.
   *
   * @param $page
   * @param $template
   */
  function trace_format(&$page,$template='') {
      global $globals;
      if(empty($template))
          $template = $globals->libroot . '/templates/database-debug.tpl';
      $page->assign_by_ref('trace_data', $this->_trace_data);
      return $page->fetch($template);
  }


  /** Execute a database query.
   *
   * @param $query
   */
  function query($query) {
    if (!empty($query)) {
      
      if ($this->_trace) {
        $_res = mysql_query("EXPLAIN $query", $this->connect_id);
        $explain = Array();
        while($row = @mysql_fetch_assoc($_res)) $explain[] = $row;
        $trace_data = Array('query' => $query, 'explain' => $explain );
        @mysql_free_result($_res);
      }

      $res = mysql_query($query, $this->connect_id);
      
      if ($this->_trace) {
        $trace_data['error'] = $this->error();
        $this->_trace_data[] = $trace_data;
      }

      if (!$res)
      {
      	$this->_handleError($query);
      }

      return $res;
    }
  }


  /** Return insert_id
   */
  function insert_id()
  {
    return @mysql_insert_id($this->connect_id);
  }

  
  /** Return whether there is currently an error in effect.
   *
   * @return boolean true if error, false otherwise
   */
  function err()
  {
    return ($this->_errno != 0);
  }

  
  /** Return the last error string.
   */
  function error() {
    return $this->_errstr;
  }

  
  /** Return the last error number.
   */
  function errno() {
    return $this->_errno;
  }

  
  /** Return extra info which might help in determining the cause of the
   * previous error.
   */
  function errinfo()
  {
    return $this->_errinfo;
  }

  
  /** Forget about any errors previously raised.
   */
  function ResetError()
  {
    $this->_errno = 0;
    $this->_errstr = '';
    $this->_errinfo = '';
  }

  
  /** Return the number rows affected by the last query.
   */
  function affected_rows() {
    return @mysql_affected_rows($this->connect_id);
  }


  /** Return an array with the possibly values of a set column.
   *
   * @param $table
   * @param $column
   */
  function get_set($table,$column) {
    $res = $this->query("show columns from $table like '$column'");
    $line = mysql_fetch_assoc($res);
    $set = $line['Type'];
    $set = substr($set,5,strlen($set)-7);
    return preg_split("/','/",$set);
  }

  
  /** Handle an error in the database.
   *
   * Updates the error status information in the system, and possibly dies
   * if we're doing that sort of thing.
   *
   * @param $extras
   */
  function _handleError($extras = '')
  {
    $this->_errinfo = $extras;
    
    if ($this->connect_id)
    {
      $this->_errno = mysql_errno($this->connect_id);
      $this->_errstr = mysql_error($this->connect_id);
    }
    else
    {
      $this->_errno = mysql_errno();
      $this->_errstr = mysql_error();
    }

    if ($this->_fatal)
    {
      die(sprintf("Database error: (%i) %s\n%s\n", $this->_errno, $this->_errstr, $this->_errinfo));
    }
  }
}

?>
