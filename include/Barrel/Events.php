<?php
/*
 * Copyright (C) 2003-2006 Polytechnique.org
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

define('EVENT_FLAG_NONE', 0);
define('EVENT_FLAG_PUBLIC', 1);

/** This class is used to generate the menu of a Diogenes barrel page.
 */
class Diogenes_Barrel_Events
{
  /** The barrel we are watching */
  var $barrel;

  /** Constructor.
   */
  function Diogenes_Barrel_Events(&$barrel)
  {
    $this->barrel = $barrel;
  }


  /** Filename transformations.
   */
  function makeFileLoc($log_file, &$caller)
  {
    $homepage = $this->barrel->getPID('');
    if (stristr($log_file, '/') == FALSE ) {
      // this is a directory
      $mydir = $log_file;
      $myfile = '';
    } else {
      $myfile = basename($log_file);
      $mydir = dirname($log_file);
    }

    $myloc = $this->barrel->getLocation($mydir);
    if ($myloc or ($mydir == $homepage))
    {
      $log_file = $myloc ? "$myloc/$myfile" : $myfile;
      $url_loc = $myloc ? "$myloc/" : '';
      $link = $caller->urlBarrel($this->barrel->alias, $this->barrel->vhost, $url_loc);
    } else {
      $link = '';
    }
  
    return array($log_file, $mydir, $myfile, $link);
  }

  /** Retrieve recent events.
   */
  function getEvents($caller)
  {
    global $globals;
    $events = array();
    $res = $globals->db->query("select e.action,e.stamp,e.data,a.text,s.auth,s.uid "
                  ."from {$globals->table_log_events} as e "
                  ."left join {$globals->table_log_actions} as a on e.action=a.id "
                  ."left join {$globals->table_log_sessions} as s on e.session=s.id "
                  ."where e.data like '{$this->barrel->alias}:%' "
                  ."order by stamp desc limit 0,10");
    while ($myarr = mysql_fetch_assoc($res)) {
      $myarr['author'] = call_user_func(array($globals->session,'getUsername'),$myarr['auth'],$myarr['uid']);
      $myarr['flags'] = EVENT_FLAG_NONE;
      list($op_alias, $op_file) = split(":",$myarr['data']);

      switch($myarr['text']) {
      case "barrel_create":
        $myarr['title'] = __("site created");
        $myarr['icon'] = $globals->icons->get_action_icon('add');    
        break;
    
      case "barrel_options":
        $myarr['title'] = __("barrel options");
        $myarr['icon'] = $globals->icons->get_action_icon('properties');    
        break;
  
      case "barrel_plugins":
        $myarr['title'] = __("barrel plugins");
        $myarr['icon'] = $globals->icons->get_action_icon('plugins');
	$myarr['link_admin'] = "plugins";
        break;
        
      case "page_create":
        $myarr['title'] = __("page created");
        $myarr['icon'] = $globals->icons->get_action_icon('add');    
        list($op_file, $myarr['dir'], $myarr['file'], $myarr['link']) = $this->makeFileLoc($op_file, $caller);
        $myarr['link_admin'] = "pages?dir={$myarr['dir']}";
        $myarr['flags'] |= EVENT_FLAG_PUBLIC;
        break;

      case "page_delete":
        $myarr['title'] = __("page removed");
        $myarr['icon'] = $globals->icons->get_action_icon('remove');
        break;
    
      case "page_props":
        $myarr['title'] = __("page properties");
        $myarr['icon'] = $globals->icons->get_action_icon('properties');
        list($op_file, $myarr['dir'], $myarr['file']) = $this->makeFileLoc($op_file, $caller);
        $myarr['link_admin'] = "pages?dir={$myarr['dir']}";
        break;
  
      case "page_plugins":
        $myarr['title'] = __("page plugins");
        $myarr['icon'] = $globals->icons->get_action_icon('plugins');
        list($op_file, $myarr['dir'], $myarr['file']) = $this->makeFileLoc($op_file, $caller);
        $myarr['link_admin'] = "plugins?plug_page={$myarr['dir']}";
        break;

      case "rcs_commit": 
        $myarr['title'] = __("file updated");
        $myarr['icon'] = $globals->icons->get_action_icon('update');    
        list($op_file, $myarr['dir'], $myarr['file'], $myarr['link']) = $this->makeFileLoc($op_file, $caller);  
        $myarr['link_admin'] = "files?action=revs&amp;dir={$myarr['dir']}&amp;target={$myarr['file']}";
        $myarr['flags'] |= EVENT_FLAG_PUBLIC;

        break;
      
      case "rcs_delete":
        $myarr['title'] = __("file deleted");        
        $myarr['icon'] = $globals->icons->get_action_icon('delete');    
        list($op_file, $myarr['dir'], $myarr['file']) = $this->makeFileLoc($op_file, $caller);    
        break;
      }
      $myarr['opfile'] = $op_file;
 
      if (isset($myarr['title']))
        array_push($events, $myarr);
    }
    mysql_free_result($res);
    return $events;
  }
}

?>
