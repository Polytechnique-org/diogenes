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

// dependency on PEAR
require_once 'System.php';

/** Recursive stripslashes.
 *
 * @param $value
 */
function stripslashes_recurse($value)
{
   $value = is_array($value) ?
               array_map('stripslashes_recurse', $value) :
               stripslashes($value);
   return $value;
}


/** This class describes a Diogenes plugin. 
 */
class Diogenes_Plugin_Skel {
  /** Plugin type (object, filter) */
  var $type;
  
  /** Array of plugin parameters */
  var $params = array();
  
  /** Plugin name */
  var $name = "Plugin_Skel";
  
  /** Plugin description */  
  var $description = "Plugin skeleton";
  
  /** Plugin version */
  var $version = "0.1";

  /** Position of the plugin */
  var $pos;
  
  /** Is the plugin active ? */
  var $active = 0;
  
  
  /** Is the plugin allowed with respect to a given write permission on a page ?
   *
   * @param $wperms
   */
  function allow_wperms($wperms) 
  {
    return ($wperms != 'public');
  }


  /** Declare a plugin parameter.
   */
  function declareParam($key, $val)
  {
    $this->params[$key] = $val;
  }


  /** Return an array of parameter names.
   */
  function getParamNames()
  {
    return array_keys($this->params);
  }


  /** Return the value of a parameter of the plugin.
   */
  function getParamValue($key)
  {
    return isset($this->params[$key]) ? $this->params[$key] : '';
  }
 
 
  /** Set the value of a parameter of the plugin.
   */
  function setParamValue($key, $val)
  {
    if (isset($this->params[$key])) {
      //echo "$this->name : Calling setParamValue($key, $val)<br/>\n";
      $this->params[$key] = $val; 
    } else {
      //echo "$this->name : skipping setParamValue($key, $val)<br/>\n";
    }
  }
 
 
  /** Set plugin parameters.
   *
   * @param $params
   */
  function setParams($params)
  {
    $bits = explode("\0", $params);
    foreach ($bits as $bit)
    {
      $frags = explode("=", $bit, 2);
      $key = $frags[0];
      if (!empty($key))
      {
        $val = isset($frags[1]) ? $frags[1] : '';
        $this->setParamValue($key, $val);
      }
    }
  }
  
  
  /** Erase parameters from database.
   *
   * @param $barrel
   * @param $page
   */
  function eraseParams($barrel = '', $page = 0)
  {
    global $globals;
    
    //echo $this->name . " : eraseParams($barrel, $page)<br/>\n";
    $globals->db->query("delete from diogenes_plugin where plugin='{$this->name}' and barrel='$barrel' and page='$page'");
    
    $this->active = 0;
    unset($this->pos);
    foreach ($this->getParamNames() as $key)
    {
      //echo "$this->name : erasing param<br/>\n";
      $this->setParamValue($key, '');
    }
  }
   
    
  /** Store parameters to database.
   *
   * @param $barrel
   * @param $page
   * @param $pos   
   */
  function writeParams($barrel = '', $page = 0, $pos = 0)
  {
    global $globals;

    $this->pos = $pos;
    $this->active = 1;
    
    $params = '';
    foreach ($this->getParamNames() as $key)
    {
      $val = $this->getParamValue($key);
      //echo "$this->name : $key = $val<br/>\n";
      $params .= "$key=$val\0";     
    }        
    $globals->db->query("replace into diogenes_plugin set plugin='{$this->name}', barrel='$barrel', page='$page', pos='$pos', params='$params'");
  }
  
  
  /** Dump parameters to a table.
   */
  function dump()
  {
    $plugentr = array();

    // copy over properties
    $props = array('active', 'name', 'params', 'description', 'version', 'type', 'pos');
    foreach ($props as $prop)
    {
      if (isset($this->$prop))
      {
        $plugentr[$prop] =  stripslashes_recurse($this->$prop);
      }
    }    
    return $plugentr;
  }
  
}
  
?>
