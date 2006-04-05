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

/** This class describes Diogenes' plugins. 
 */
class Diogenes_Plugins
{
  /** Array of currently loaded plugins */
  var $loaded = array();

  /** Directory that holds the plugins cache files */
  var $cachedir;
  
  /** Directory that holds the plugins */
  var $plugdir;
  
  
  /** Constructs a new holder for Diogenes plugins.
   *
   * @param $dbh
   * @param $plugdir
   * @param $cachedir   
   */
  function Diogenes_Plugins(&$dbh, $plugdir, $cachedir)
  {
    $this->dbh =& $dbh;
    $this->plugdir = $plugdir;
    $this->cachedir = $cachedir;    
  }
  
  
  /** Return the path of the cache file
   *
   * @param $barrel
   */
  function cacheFile($barrel)
  {
    $cachefile = $this->cachedir . "/" . ($barrel ? $barrel : "__diogenes__") . ".plugins";
    return $cachefile;
  }
    
  
  /** Return the cache entry for specified plugin
   *
   * @param $cache
   * @param $barrel
   * @param $page
   * @param $plugin
   */    
  function cacheGet($cache, $barrel, $page, $plugin)
  {
    foreach ($cache as $plugentry)
    {      
      if (($plugentry['plugin'] == $plugin) && ($plugentry['page'] == $page))
      {
        return $plugentry;
      }
    }
    return;     
  }

  
  /** Return the cache entry for a plugin at a specified position
   *
   * @param $cache
   * @param $barrel
   * @param $page
   * @param $pos
   */  
  function cacheGetAtPos($cache, $barrel, $page, $pos)
  {
    foreach ($cache as $plugentry)
    {      
      if (($plugentry['pos'] == $pos) && ($plugentry['page'] == $page))
      {
        return $plugentry;
      }
    }  
  }  

  
  /** List the plugins that are active in a given context
   *
   * @param $cache
   * @param $barrel
   * @param $page   
   */
  function cachedActive($cache, $barrel, $page)
  {
    $plugins = array();
    foreach ($cache as $plug)
    {
      if ($plug['page'] == $page) 
      {
        array_push($plugins, $plug);
      }
    }
    return $plugins;    
  }

    
  /** Returns an array of cache entries representing the available plugins
   *  in a given context
   *
   * @param $cache
   * @param $barrel
   * @param $page
   */  
  function cachedAvailable($cache, $barrel, $page)
  {      
    $available = array();
    foreach ($cache as $plugentry)
    {      
      $plugfile = $this->plugdir."/".$plugentry['plugin'].".php";
      if (file_exists($plugfile) and ($plugentry['page'] == 0) and (!$page or $plugentry['active']))
      {
        array_push($available, $plugentry['plugin']);
      }
    }
    return $available;    
  }
  
  
  /** Remove database references to plugins that are not available.
   */
  function clean_database(&$page)
  {
    /*
    $res = $this->dbh->query("select distinct plugin from diogenes_plugin");
    while (list($plugname) = mysql_fetch_row($res))
    {
      $page->info("examining $plugname..");
      $plug = $this->load($plugname);
      if (!is_object($plug)) {
        $page->info("plugin $plugname is broken, removing");
        $this->dbh->query("delete from diogenes_plugin where plugin='$plugname'");
      }
    }
    mysql_free_result($res);
    */
  }

    
  /** Compile plugin cache.
   *
   * @param $cachefile
   * @param $barrel
   */
  function compileCache($cachefile, $barrel)
  {
    if (!$fp = fopen($cachefile, "w")) {
      trigger_error("failed to open '$cachefile' for writing", E_USER_ERROR);
    }

    // get the list of available plugins
    $available = array();            
    if (!$barrel) {
    
      $plugfiles = System::find($this->plugdir.' -type f -name *.php');  
      foreach ($plugfiles as $file) {
        $name = basename($file);
        $name = substr($name, 0, -4);      
        array_push($available, $name);
      }      
      
    } else {
            
      $sql = "select plugin from diogenes_plugin where page=0 AND barrel=''";
      $res = $this->dbh->query($sql);
      while (list($plugin) = mysql_fetch_row($res))
      {
        array_push($available, $plugin);
      }
      mysql_free_result($res);
   }
    
/*
   echo "compile : available <pre>";
   print_r($available);
   echo "</pre>";
*/   
   // get active plugins
   $sql = "select page, pos, plugin, params from diogenes_plugin where barrel='{$barrel}' order by page, pos";
   $res = $this->dbh->query($sql);
   $active = array();
   while ($row = mysql_fetch_row($res))
   {
     $plugin = $row[2];
     if (in_array($plugin, $available)) {
       array_unshift($row, 1); 
       fputs($fp, join("\t", $row) . "\n");       
       if (!$row[1]) {
         array_push($active, $plugin);
         //echo "compileCache : adding active plugin $plugin<br/>";       
       }
     }
   }
   mysql_free_result($res);    
   
   // add inactive plugins
   foreach ($available as $plugin)
   {
     if (!in_array($plugin, $active))
     {
       //echo "compileCache : adding inactive plugin $plugin<br/>";
       $row = array(0, 0, 0, $plugin, '');
       fputs($fp, join("\t", $row) . "\n");     
     }
   }
   
   fclose($fp);
   
   //$this->log("rcs_commit","{$this->alias}:$dir/$file:$message");

  }
  
   
  /** Load the specified plugin
   *
   * @param $plugentry
   */
  function load($plugentry)
  {
    $plugin = $plugentry['plugin'];
    $plugfile = $this->plugdir."/$plugin.php";
    if (!file_exists($plugfile)) {
      trigger_error("could not find plugin file '$plugfile'", E_USER_WARNING);
      return;
    }
     
    include_once($plugfile);
  
    if (!class_exists($plugin)) {
      trigger_error("could not find class '$plugin'", E_USER_WARNING);
      return;
    }
    
    // load and register plugin
    $plug = new $plugin();
    $plug->pos = $plugentry['pos'];
    $plug->active = $plugentry['active'];
    $plug->setParams($plugentry['params']);
    $this->loaded[$plugin] =& $plug;      
    
    return $plug;
  }
    

  /** Read the compiled plugin cache
   *
   * @param $cachefile
   * @param $barrel
   */
  function readCache($cachefile, $barrel)
  {
    if (!file_exists($cachefile)) {
        return array();
    }
    
    if (!$fp = fopen($cachefile, "r")) {
      trigger_error("failed to open '$cachefile' for reading", E_USER_WARNING);
      return;
    }
    
    $plugins = array();
    while ($line = fgets($fp))
    {
      // drop end of line
      $line = substr($line, 0, -1);
      $bits = explode("\t", $line);
      $plug = array(
        'active' => $bits[0],
        'page'   => $bits[1],
        'pos'    => $bits[2],
        'plugin' => $bits[3],
        'params' => $bits[4],
      );        
      array_push($plugins, $plug);
    }
        
    fclose($fp);
    
    return $plugins;
  }

      
  /** Prepare plugins trace for output
   */    
  function trace_format()
  {
    $out = "";
    foreach ($this->loaded as $key => $val)
    {      
      $out .= '<table class="light" style="width: 100%; font-family: monospace">';
      $out .= '<tr><th colspan="2">'.$key.' v'.$val->version.'</th></tr>';
      if (isset($val->pos)) {
        $out .= '<tr><td>position</td><td>'.$val->pos.'</td></tr>';
      }
      $out .= '<tr><td>type</td><td>'.$val->type.'</td></tr>';
      $out .= '<tr><td>description</td><td>'.$val->description.'</td></tr>';
      if (empty($val->params)) {
        $out .= '<tr class="odd"><td colspan="2">parameters</td></tr>';
        foreach ($val->params as $skey => $sval) 
        {
          $out .= "<tr><td>$skey</td><td>$sval</td></tr>";
        }
      }
      $out .= "</table><br/>";
    }
    return $out;
  }
  
  
  
}

?>
