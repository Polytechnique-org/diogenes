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
require_once 'Plugin/Skel.php';


/** This class describes Diogenes' plugins. 
 */
class Diogenes_Plugins
{
  /** Directory that holds the plugins cache files */
  var $cachedir;

  /** Directory that holds the plugins */
  var $plugdir;

  /** Plugins that will be sent to trace */
  var $log = array();

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
    $p_node = $this->cacheGetPageNode($cache, $barrel, $page);
    return $p_node->data[$plugin];
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
    $p_node = $this->cacheGetPageNode($cache, $barrel, $page);
    foreach ($p_node->data as $plugname => $plugentry)
    {
      if ($plugentry['pos'] == $pos)
      {
        $plugentry['plugin'] = $plugname;
        return $plugentry;
      }
    }
  }


  /** Return the cache entry for specified plugin
   *
   * @param $cache
   * @param $barrel
   * @param $page
   * @param $plugin
   */
  function cacheGetPageNode($cache, $barrel, $page)
  {
    if ($page) {
      $p_node = $cache->getChild($page);
    } else {
      $p_node = $cache;
    }
    return $p_node;
  }


  /** List the plugins that are active in a given context
   *
   * @param $cache
   * @param $barrel
   * @param $page   
   */
  function cachedActive($cache, $barrel, $page)
  {
    $p_node = $this->cacheGetPageNode($cache, $barrel, $page);
    $plugins = array();
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
    $p_node = $this->cacheGetPageNode($cache, $barrel, $page);
    //echo "<pre>".var_encode_text($p_node)."</pre>";
    foreach ($p_node->data as $plugname => $plugentry)
    {
      $plugfile = $this->plugdir."/".$plugname.".php";
      if (file_exists($plugfile) and (!$barrel || ($plugentry['status'] != PLUG_DISABLED)))
      {
        $available[$plugname] = $plugentry;
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


  /** Build view for current level.
  */
  function compileNode($node, &$outparent, $index)
  {
    $outvals = array();
    foreach ($outparent->data as $plugin => $parentval)
    {
      //echo "Processing plugin '$plugin' for &lt;{$node->name}&gt;<br/>\n";
      $outval = '';
      if ($parentval['status'] != PLUG_DISABLED)
      {
        //echo "* plugin available/enabled at parent level<br/>\n";
        $outval = $parentval;
        if (is_array($node->data[$plugin]))
        {
          //echo "** plugin set at current level<br/>\n";
          $outval['pos'] = $node->data[$plugin]['pos'];
          $outval['params'] = $node->data[$plugin]['params'];
          if ($parentval['status'] == PLUG_AVAILABLE) {
            //echo "*** plugin status set to DB-specified value<br/>\n";
            $outval['status'] = $node->data[$plugin]['status'];
          } else {
            //echo "*** plugin forced on at parent level<br/>\n";
          }
        } else {
          //echo "** plugin unset at current level<br/>\n";
        }
      }

      // add the plugin to output
      if (is_array($outval))
      {
        $outvals[$plugin] = $outval;
      }
    }
    $outnode = new Diogenes_Tree_Node($outvals, $node->name);
    //echo "<hr/>";

    // recurse into children
    foreach ($node->children as $cindex => $child)
    {
      $this->compileNode($child, $outnode, $cindex);
    }

    // add the produced node
    $outparent->setChild($index, $outnode);
  }


  /** Compile plugin cache.
   *
   * @param $barrel
   */
  function compileCache($barrel, &$caller)
  {
    $caller->info("Recompiling " .($barrel ? "plugin cache for barrel '$barrel'" : "global plugin cache"));

    // get the list of all plugins present on the system
    $allplugins = array();
    $plugfiles = System::find($this->plugdir.' -type f -name *.php');
    foreach ($plugfiles as $file) {
      $name = basename($file);
      $name = substr($name, 0, -4);
      array_push($allplugins, $name);
    }

    $defcache = array();
    // fill initial values
    foreach ($allplugins as $plugin)
    {
      $plug_h = $this->load($plugin);
      $defcache[$plugin] = $plug_h->toArray();
    }

    // get DB values
    $dbcache = array();

    $sql_limit = $barrel ? " where barrel='{$barrel}' or barrel=''" : "";
    $sql = "select barrel, page, plugin, status, pos, params from diogenes_plugin" . $sql_limit;
    $res = $this->dbh->query($sql);
    while($row = mysql_fetch_row($res))
    {
      $c_barrel = array_shift($row);
      $c_page = array_shift($row);
      $plugin = array_shift($row);
      $plugentry = array(
        'status' => $row[0],
        'pos' => $row[1],
        'params' => ($row[2] ? var_decode_bin($row[2]) : array())
      );
      $plug_h = $this->load($plugin, $plugentry);
      //echo "Got params from DB for '$plugin', barrel '$c_barrel', page '$c_page' : ".$row[2]."<br/>";
      $dbcache[$c_barrel][$c_page][$plugin] = $plug_h->toArray();
    }
    mysql_free_result($res);

    // build the input tree
    $globals_node = new Diogenes_Tree_Node($dbcache[''][0], 'globals defaults');
    $sql_limit = $barrel ? " where alias='{$barrel}'" : " where alias!=''";
    $res = $this->dbh->query("select alias from diogenes_site" . $sql_limit);
    while(list($c_barrel) = mysql_fetch_row($res))
    {
      $barrel_node = new Diogenes_Tree_Node($dbcache[$c_barrel][0], "barrel '$c_barrel' defaults");
      $res2 = $this->dbh->query("select PID from {$c_barrel}_page");
      while(list($page) = mysql_fetch_row($res2))
      {
        $page_node = new Diogenes_Tree_Node($dbcache[$c_barrel][$page], "barrel '$c_barrel' page $page"); 
        $barrel_node->setChild($page, $page_node);
      }
      mysql_free_result($res2);
      $globals_node->setChild($c_barrel, $barrel_node);
    }
    mysql_free_result($res);

    // compile the cache
    $top_out_node = new Diogenes_Tree_Node($defcache, 'plugin defaults');
    $this->compileNode($globals_node, $top_out_node, 'globals');
    $globals_out_node = $top_out_node->getChild('globals');
    //echo "<pre>" . $top_out_node->dump() . "</pre><hr/>";

    // produce dump(s)
    if ($barrel) {
      $dump_node = $globals_out_node->getChild($barrel);
      $dump_node->writeFile($this->cachefile($barrel));
    } else {
      $globals_out_node->writeFile($this->cachefile($barrel), NODE_DUMP_NOCHILDREN);
      $globals_out_node->writeFile($this->cachefile($barrel).".txt", NODE_DUMP_NOCHILDREN | NODE_DUMP_TEXT);
      foreach ($globals_out_node->children as $c_barrel => $dump_node)
      {
        $dump_node->writeFile($this->cachefile($c_barrel));
      }
    }
  }


  /** Load the specified plugin
   *
   * @param $plugentry
   */
  function load($plugin, $plugentry = '')
  {
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
    $plug_h = new $plugin();
    if (is_array($plugentry))
      $plug_h->fromArray($plugentry);

    $plug_log = $plug_h->toArray();
    //$pluglog['name'] = 'foo';
    array_push($this->log, $plug_log);
    return $plug_h;
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

    return Diogenes_Tree_Node::readFile($cachefile);
  }


  /** Prepare plugins trace for output
   */
  function trace_format()
  {
    $out = '<table class="light" style="width: 100%; font-family: monospace">'."\n";
    $odd = 0;
    foreach ($this->log as $key => $val)
    {
      $trclass = $odd ? ' class="odd"' : '';
      $out .= "<tr><th colspan=\"2\">{$val['name']} v{$val['version']}</th></tr>\n";
      if (isset($val['pos'])) {
        $out .= "<tr><td>position</td><td>{$val['pos']}</td></tr>\n";
      }
      $out .= "<tr$trclass><td>type</td><td>{$val['type']}</td></tr>\n";
      $out .= "<tr$trclass><td>description</td><td>{$val['description']}</td></tr>\n";
      if (!empty($val['params'])) {
        $out .= "<tr$trclass><td>parameters</td><td>".var_encode_html($val['params'])."</td></tr>\n";
      }
      $odd = ($odd+1) % 2;
    }
    $out .= "</table>\n";
    return $out;
  }

}

?>
