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


/** A class for viewing user activity.
 * Allows the examination of user sessions.  Can produce a list of sessions
 * matching by date, user, or authentication method, and can drill down to
 * a detailed list of actions performed in a session.
 *
 * TODO: give an example of how to use the class
 */
class DiogenesLoggerView {
  /** Handle to the database.
   */
  var $dbh;

  /** The constructor.
   */
  function DiogenesLoggerView()
  {
    global $globals;
    if (!is_object($globals->db))
      die("\$globals->db is not an object!");
    $this->dbh =& $globals->db;
  }


  /** Retrieves the available authentication mechanisms.
   * There are several different ways of authenticating users to diogenes,
   * including doing it ourselves, and delegating the task to an external
   * mechanism.  This method gets the list of permissable methods.
   *
   * @return assoc array of (method => label) pairs.
   * @private   
   */
  function _getAuths()
  {
    global $globals;

    // give a 'no filter' option
    $auths[0] = __("all");

    $res = $this->dbh->query("describe {$globals->table_log_sessions} auth");
    list(,$type) = mysql_fetch_row($res);
    mysql_free_result($res);
    $types = preg_split("/(enum\\('|','|'\\))/", $type, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($types as $type)
      $auths[$type] = isset($globals->tlabel[$type]) ? $globals->tlabel[$type] : $type;
    return $auths;
  }


  /** Retrieves the available days for a given year and month.
   * Obtain a list of days of the given month in the given year
   * that are within the range of dates that we have log entries for.
   *
   * @param integer year
   * @param integer month
   * @return array days in that month we have log entries covering.
   * @private
   */
  function _getDays($year,$month)
  {
    global $globals;

    // give a 'no filter' option
    $days[0] = __("all");

    if ($year && $month) {
      $day_max = Array(-1, 31,checkdate(2,29,$year) ? 29 : 28 ,31,30,31,30,31,31,30,31,30,31);
      $res = $this->dbh->query("SELECT YEAR (MAX(start)), YEAR (MIN(start)),
                                       MONTH(MAX(start)), MONTH(MIN(start)),
                                       DAYOFMONTH(MAX(start)),
                                       DAYOFMONTH(MIN(start))
                                FROM {$globals->table_log_sessions}");
      list($ymax,$ymin,$mmax,$mmin,$dmax,$dmin) = mysql_fetch_row($res);
      mysql_free_result($res);

      if (($year < $ymin) || ($year == $ymin && $month < $mmin))
      {
      	return array();
      }
      
      if (($year > $ymax) || ($year == $ymax && $month > $mmax))
      {
      	return array();
      }
      
      $min = ($year==$ymin && $month==$mmin) ? intval($dmin) : 1;
      $max = ($year==$ymax && $month==$mmax) ? intval($dmax) : $day_max[$month];

      for($i = $min; $i<=$max; $i++)
        $days[$i] = $i;
    }
    return $days;
  }


  /** Retrieves the available months for a given year.
   * Obtains a list of month numbers that are within the timeframe that
   * we have log entries for.
   *
   * @param integer year
   * @return array List of month numbers we have log info for.
   * @private
   */
  function _getMonths($year)
  {
    global $globals;

    // give a 'no filter' option
    $months[0] = __("all");

    if ($year) {
      $res = $this->dbh->query("SELECT YEAR (MAX(start)), YEAR (MIN(start)),
                                       MONTH(MAX(start)), MONTH(MIN(start))
                                FROM {$globals->table_log_sessions}");
      list($ymax,$ymin,$mmax,$mmin) = mysql_fetch_row($res);
      mysql_free_result($res);

      if (($year < $ymin) || ($year > $ymax))
      {
      	return array();
      }

      $min = $year == $ymin ? intval($mmin) : 1;
      $max = $year == $ymax ? intval($mmax) : 12;

      for($i = $min; $i<=$max; $i++)
        $months[$i] = $i;
    }
    return $months;
  }


  /** Retrieves the username for a given authentication method and user id.
   * This function caches the results of the lookups to avoid uncecessary
   * database requests.
   *
   * @return the matching username.
   * @private
   */
  function _getUsername($auth, $uid) {
    global $globals;
    static $cache;

    if (!isset($cache[$auth][$uid]))
      $cache[$auth][$uid] = call_user_func(array($globals->session,'getUsername'),$auth,$uid);
      
    return $cache[$auth][$uid];
  }


  /** Retrieves the available years.
   * Obtains a list of years that we have log entries covering.
   *
   * @return array years we have log entries for.
   * @private
   */
  function _getYears()
  {
    global $globals;

    // give a 'no filter' option
    $years[0] = __("all");

    // retrieve available years
    $res = $this->dbh->query("select YEAR(MAX(start)),YEAR(MIN(start)) FROM {$globals->table_log_sessions}");
    list($max,$min) = mysql_fetch_row($res);
    mysql_free_result($res);

    for($i = intval($min); $i<=$max; $i++)
        $years[$i] = $i;
    return $years;
  }


  /** Make a where clause to get a user's sessions.
   * Prepare the where clause request that will retrieve the sessions.
   *
   * @param $year INTEGER Only get log entries made during the given year.
   * @param $month INTEGER Only get log entries made during the given month.
   * @param $day INTEGER Only get log entries made during the given day.
   * @param $auth INTEGER Only get log entries with the given authentication type.
   * @param $uid INTEGER Only get log entries referring to the given user ID.
   *
   * @return STRING the WHERE clause of a query, including the 'WHERE' keyword
   * @private
   */
  function _makeWhere($year,$month,$day,$auth,$uid)
  {
    global $globals;

    // start constructing the "where" clause
    $where = array();

    if ($auth)
      array_push($where, "auth='$auth'");

    if ($uid)
      array_push($where, "uid='$uid'");

    // we were given at least a year
    if ($year) {
      if ($day) {
        $dmin = mktime(0, 0, 0, $month, $day, $year);
        $dmax = mktime(0, 0, 0, $month, $day+1,$year);
      } elseif ($month) {
        $dmin = mktime(0, 0, 0, $month, 1, $year);
        $dmax = mktime(0, 0, 0, $month+1, 1, $year);
      } else {
        $dmin = mktime(0, 0, 0, 1, 1, $year);
        $dmax = mktime(0, 0, 0, 1, 1, $year+1);
      }
      $where[] = "start >= " . date("Ymd000000",$dmin);
      $where[] = "start < " . date("Ymd000000", $dmax);
    }

    if (!empty($where))
      return ' WHERE ' . implode($where," AND ");
    else
      return '';
    // WE know it's totally reversed, so better use array_reverse than a SORT BY start DESC
  }


  /** Run the log viewer and fill out the Smarty variables for display.
   *
   * @param page      the page that will display the viewer's data
   * @param outputvar the Smarty variable to which we should assign the output 
   * @param template  the template to use for display
   */
  function run(&$page,$outputvar='',$template='')
  {
    global $globals;

    if (isset($_REQUEST['logsess'])) {

      // we are viewing a session
      $res=$this->dbh->query("SELECT  host,ip,browser,auth,uid,sauth,suid
                                FROM  {$globals->table_log_sessions}
                               WHERE  id =".$_REQUEST['logsess']);
      $sarr = mysql_fetch_assoc($res);
      
      $sarr['username'] = $this->_getUsername($sarr['auth'],$sarr['uid']);
      if ($sarr['suid'])
        $sarr['suer'] = $this->_getUsername($sarr['sauth'],$sarr['suid']);
      $page->assign('session',$sarr);

      $res=$this->dbh->query("SELECT  a.text,e.data,UNIX_TIMESTAMP(e.stamp) AS stamp
                               FROM  {$globals->table_log_events}  AS e
                           LEFT JOIN  {$globals->table_log_actions} AS a ON e.action=a.id
                               WHERE  e.session='{$_REQUEST['logsess']}'");
      while ($myarr = mysql_fetch_assoc($res))
        $page->append('events',$myarr);
      mysql_free_result($res);

    } else {

      // we are browsing the available sessions
      $logauth = isset($_REQUEST['logauth']) ? $_REQUEST['logauth'] : '';
      $loguser = isset($_REQUEST['loguser']) ? $_REQUEST['loguser'] : '';
      $loguid = ($logauth && $loguser)? call_user_func(array($globals->session,'getUserId'),$logauth,$_REQUEST['loguser']) : '';
      if (!$loguid) $loguser = '';

      if ($loguid) {
        $year = isset($_REQUEST['year']) ? $_REQUEST['year'] : 0;
        $month = isset($_REQUEST['month']) ? $_REQUEST['month'] : 0;
        $day = isset($_REQUEST['day']) ? $_REQUEST['day'] : 0;
      } else {
        $year = isset($_REQUEST['year']) ? $_REQUEST['year'] : date("Y");
        $month = isset($_REQUEST['month']) ? $_REQUEST['month'] : date("m");
        $day = isset($_REQUEST['day']) ? $_REQUEST['day'] : date("d");
      }

      if (!$year)  $month = 0;
      if (!$month) $day = 0;

    // smarty assignments
      // retrieve available years
      $page->assign('years',$this->_getYears());
      $page->assign('year',$year);

      // retrieve available months for the current year
      $page->assign('months',$this->_getMonths($year));
      $page->assign('month',$month);

      // retrieve available days for the current year and month
      $page->assign('days',$this->_getDays($year,$month));
      $page->assign('day',$day);

      // retrieve available auths
      $auths = $this->_getAuths();
      $page->assign('auths',$auths);

      $page->assign('logauth',$logauth);
      $page->assign('loguser',$loguser);
    // smarty assignments

      if($loguid || $year) {

        // get the requested sessions
        $where  = $this->_makeWhere($year,$month,$day,$logauth,$loguid);
        $select = "SELECT id,UNIX_TIMESTAMP(start) as start,auth,uid
                   FROM {$globals->table_log_sessions} AS s
                   $where
                  ORDER BY start DESC";
        $res = $globals->db->query($select);
       
        $sessions = array();
        while ( $mysess = mysql_fetch_assoc($res) ) {
          $mysess['username'] = $this->_getUsername($mysess['auth'],$mysess['uid']);
          // pretty label for auth method
          $mysess['lauth'] = $auths[$mysess['auth']];
          // summary of events
          $mysess['events'] = array();
          // actions
          $mysess['actions'] = array(
             array(__("view session"),"?logsess={$mysess['id']}"),
             array(__("user's log"),"?logauth={$mysess['auth']}&amp;loguser={$mysess['username']}")
          );

          $sessions[$mysess['id']] = $mysess;
        }
        mysql_free_result($res);
        array_reverse($sessions);

        // attach events
        $sql = "SELECT s.id, a.text
                FROM       {$globals->table_log_sessions} AS s
                LEFT  JOIN {$globals->table_log_events}   AS e ON(e.session=s.id)
                INNER JOIN {$globals->table_log_actions}  AS a ON(a.id=e.action)
                $where";

        $res = $globals->db->query($sql);
        while( $event = mysql_fetch_assoc($res) ) {
            array_push($sessions[$event['id']]['events'],$event['text']);
        }
        mysql_free_result($res);
        $page->assign_by_ref('sessions',$sessions);
      } else {
        $page->assign('msg_nofilters', __("Please select a year and/or a user."));
      }
    }

    // translations
    $page->assign('msg_session_properties', __("session properties"));
    $page->assign('msg_user', __("user"));
    $page->assign('msg_host', __("host"));
    $page->assign('msg_browser', __("browser"));
    $page->assign('msg_date', __("date"));
    $page->assign('msg_action', __("action"));
    $page->assign('msg_data', __("data"));
    $page->assign('msg_filter_by', __("filter by"));
    $page->assign('msg_start', __("start"));
    $page->assign('msg_summary', __("summary"));
    $page->assign('msg_actions', __("actions"));
    $page->assign('msg_year', __("year"));
    $page->assign('msg_month', __("month"));
    $page->assign('msg_day', __("day"));
    $page->assign('msg_submit', __("Submit"));

    // if requested, assign the content to be displayed
    if (!empty($outputvar)) {
      if (empty($template))
        $template = $globals->libroot."/templates/logger-view.tpl";
      $page->assign($outputvar, $page->fetch($template));
    }
  }

}

?>
