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
require_once 'Tree/Node.php';

define('PLUG_DISABLED', 0);
define('PLUG_ACTIVE', 1);
define('PLUG_LOCK', 2);
define('PLUG_SET', 4);

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
  var $type = '';
  
  /** Array of plugin parameters */
  var $params = array();
  
  /** Plugin name */
  var $name = "Plugin_Skel";
  
  /** Plugin description */  
  var $description = "Plugin skeleton";
  
  /** Plugin version */
  var $version = "0.1";

  /** Position of the plugin */
  var $pos = 0;
  
  /** The plugin status (disabled, available, active) */
  var $status = PLUG_DISABLED;
 
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
    $this->params[$key]['value'] = $val;
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
    return isset($this->params[$key]['value']) ? $this->params[$key]['value'] : '';
  }


  /** Set the value of a parameter of the plugin.
   */
  function setParamValue($key, $val)
  {
    if (isset($this->params[$key]['value'])) {
      $this->params[$key]['value'] = $val; 
    }
  }


  /** Erase parameters from database.
   *
   * @param $barrel
   * @param $page
   */
  function eraseParameters($barrel = '', $page = 0)
  {
    global $globals;
    //echo $this->name . " : eraseParams($barrel, $page)<br/>\n";
    $globals->db->query("delete from diogenes_plugin where plugin='{$this->name}' and barrel='$barrel' and page='$page'");

    unset($this->pos);
    foreach ($this->getParamNames() as $key)
    {
      //echo "$this->name : erasing param<br/>\n";
      $this->setParamValue($key, '');
    }
  }


  /** Read parameters from an array.
    */
  function fromArray($plugentry)
  {
      $this->pos = $plugentry['pos'];
      $this->status = $plugentry['status'];
      foreach ($plugentry['params'] as $key => $val)
      {
        $this->setParamValue($key, $val['value']);
      }
  }


  /** Store parameters to database.
   *
   * @param $barrel
   * @param $page
   * @param $pos
   */
  function toDatabase($barrel = '', $page = 0, $pos = 0)
  {
    global $globals;

    $this->pos = $pos;
    $params = var_encode_bin($this->params);
    //echo "toDatabase called for '{$this->name}' in barrel '$barrel' (status : {$this->status}, params : '$params')<br/>";
    $globals->db->query("replace into diogenes_plugin set plugin='{$this->name}', status='{$this->status}', barrel='$barrel', page='$page', pos='$pos', params='$params'");
  }


  /** Dump parameters to a table.
   */
  function toArray()
  {
    $plugentr = array();

    // copy over properties
    $props = array('status', 'name', 'params', 'description', 'version', 'type', 'pos');
    foreach ($props as $prop)
    {
      $plugentr[$prop] =  stripslashes_recurse($this->$prop);
    }
    return $plugentr;
  }

}
  
?>
