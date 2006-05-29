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

require_once 'Barrel/Page.php';
require_once 'Barrel/Options.php';
require_once 'diogenes/diogenes.flagset.inc.php';
require_once 'Plugin/Skel.php';
 

/** This class describes a Diogenes Barrel.
 */
class Diogenes_Barrel
{
  /** The barrel's alias. */
  var $alias;

  /** The database table holding the menus */
  var $table_menu;

  /** The database table holding the pages */
  var $table_page;
    
  /** The site's flags. */
  var $flags;
  
  /** The site's options. */
  var $options;
  
  /** Cache of the homepage ID */
  var $homepage;

  /** Handle to the spool */
  var $spool;
  
  /** If the barrel is running on a virtualhost. */
  var $vhost;

  /** Cache of tree */
  var $treeCache;
  
  /** File containing the tree cache. */
  var $treeCacheFile;

  /** Cache of the plugins */
  var $pluginsCache;
    
  /** File containing the plugin cache. */
  var $pluginsCacheFile;
   
  
  /** Construct a Diogenes Barrel.
   *
   * @param $alias
   */ 
  function Diogenes_Barrel($alias)
  {
    global $globals;
    $webdav = '';
    
    // Retrieve site-wide info from database
    $res = $globals->db->query("select alias,vhost,flags from diogenes_site where alias='$alias'");
    if (!list($this->alias,$this->vhost,$flags) = mysql_fetch_row($res)) {
      return;      
    }
    mysql_free_result($res);
    
    $this->table_menu = "{$this->alias}_menu";
    $this->table_page = "{$this->alias}_page";
    $this->treeCacheFile = $globals->spoolroot."/diogenes_c/". $this->alias.".tree";
    $this->pluginsCacheFile = $globals->plugins->cacheFile($this->alias);

    $this->flags = new flagset($flags);    
    $this->options = new Diogenes_Barrel_Options($this->alias);
    $this->spool = new DiogenesSpool($this,$this->alias);
    
    $this->readTree();
  }
  
  
  /** Create a new Diogenes barrel. This creates the database, RCS and
   *  spool entries for the new barrel.
   *
   * @param $alias
   * @param $caller
   */
  function create($alias, &$caller)
  {
    global $globals;

    /* sanity check */
    if (!preg_match("/^[a-zA-Z0-9_]+$/",$alias) or
        in_array($alias, $globals->invalidaliases)) 
    {
      $caller->info("Invalid barrel name!");
      return;
    }

    $res = $globals->db->query("select alias from diogenes_site where alias='$alias'");
    if (mysql_num_rows($res) > 0) {
      $caller->info("Entry '{$alias}' already exists in table 'diogenes_site'!");
      return;
    }

    if (file_exists("{$globals->rcsroot}/$alias")) {
      $caller->info("Directory '{$globals->rcsroot}/$alias' already exists!");
      return;
    }

    if (!is_dir($globals->rcsroot) || !is_writable($globals->rcsroot)) {
      $caller->info("Directory '{$globals->rcsroot}' is not writable!");
      return;
    }

    /* log this event */
    $caller->log("barrel_create","$alias:*");

    /* create DB entry */
    $globals->db->query("insert into diogenes_site set alias='$alias'");

    $globals->db->query("CREATE TABLE {$alias}_menu ("
    . "MID int(10) unsigned NOT NULL auto_increment,"
    . "MIDpere int(10) unsigned NOT NULL,"
    . "ordre int(10) unsigned NOT NULL,"
    . "title tinytext NOT NULL,"
    . "link text NOT NULL,"
    . "PID int(10) unsigned NOT NULL,"
    . "PRIMARY KEY  (MID)"
    . ") TYPE=MyISAM;");

    $globals->db->query("CREATE TABLE {$alias}_page ("
    . "PID int(10) unsigned NOT NULL auto_increment,"
    . "parent INT( 10 ) UNSIGNED NOT NULL default '0',"
    . "location tinytext NOT NULL,"
    . "title tinytext NOT NULL,"
    . "status tinyint(1) unsigned NOT NULL default '0',"
    . "perms enum('public','auth','user','admin','forbidden') NOT NULL default 'public',"
    . "wperms enum('public','auth','user','admin','forbidden') NOT NULL default 'admin',"
    . "template varchar(255) NOT NULL,"
    . "PRIMARY KEY  (PID)"
    . ") TYPE=MyISAM;");
    
    /* set the barrel's title */
    $opt = new Diogenes_Barrel_Options($alias);
    $opt->updateOption('title',$alias);

    /* create entry for the homepage */
    $globals->db->query("insert into {$alias}_page set location='temp'");
    $homepage = mysql_insert_id();
    $globals->db->query("update {$alias}_page set location='',title='Home',perms='public' where PID='$homepage'");

    /* create home page & copy CSS template */
    $rcs = new $globals->rcs($caller,$alias,$_SESSION['session']->username,true);
    $rcs->newdir("",$homepage);
    $rcs->commit($homepage,$globals->htmlfile,"");
    $rcs->commit($homepage,$globals->cssfile,
                        file_get_contents("{$globals->root}/{$globals->cssfile}") );
  }

  
  /** Destroy a Diogenes barrel. This removes the related database, RCS
   *  and spool entries.
   *
   * @param $alias
   * @param $caller
   */
  function destroy($alias, &$caller) {
    global $globals;

    /** Sanity check */
    if (!$alias) {
      $caller->info("Empty alias supplied!");
      return;
    }

    /* log this event */
    $caller->log("barrel_delete","$alias:*");

    system(escapeshellcmd("rm -rf ".escapeshellarg("{$globals->spoolroot}/$alias")));
    system(escapeshellcmd("rm -rf ".escapeshellarg("{$globals->rcsroot}/$alias")));
    system(escapeshellcmd("rm -f ".escapeshellarg("{$globals->spoolroot}/diogenes_c/$alias.tree")));
    system(escapeshellcmd("rm -f ".escapeshellarg($globals->plugins->cacheFile($alias))));
    $globals->db->query("drop table {$alias}_menu");
    $globals->db->query("drop table {$alias}_page");
    $globals->db->query("delete from diogenes_perm where alias='$alias'");
    $globals->db->query("delete from diogenes_site where alias='$alias'");
    $globals->db->query("delete from diogenes_option where barrel='$alias'");    
    $globals->db->query("delete from diogenes_plugin where barrel='$alias'");
  }
  
    
  /** Return the location corresponding to a given page ID
   *
   * @param $PID
   */  
  function getLocation($PID)
  {
    return array_search($PID, $this->treeCache);
  }
    
  
  
  /** Return all the barrel's pages.
   */
  function getPages()
  {
    global $globals;
    $bpages = array();
    
    $res = $globals->db->query("select * from {$this->table_page}");
    while ($props = mysql_fetch_assoc($res)) {
      $bpages[$props['PID']] = new Diogenes_Barrel_Page($this, $props);
    }
    mysql_free_result($res);
    return $bpages;  
  }
  
      
  /** Return the page ID matching a given directory location
   *
   * @param $dir
   */
  function getPID($dir)
  {
    if (isset($this->treeCache[$dir]))
      return $this->treeCache[$dir];
    else
      return;
  }
  
  
  /** Check whether the barrel has a given flag
   *
   * @param $flag
   */
  function hasFlag($flag)
  {
    return $this->flags->hasFlag($flag);
  }

    
  /** Compile the directory tree
   */
  function compileTree()
  {
    global $globals;
    
    if (!$fp = fopen($this->treeCacheFile, "w")) {
      trigger_error("failed to open '{$this->treeCacheFile}' for writing", E_USER_ERROR);
      return;
    }
    
    // load all the pages
    $res = $globals->db->query("select * from {$this->table_page}");
    $tpages = array();
    while ($props = mysql_fetch_assoc($res))
    {      
      $tpage = new Diogenes_Barrel_Page($this, $props);
      $tpages[$props['PID']] = $tpage;
      if (!strlen($props['location']))
      {
        $homepage = $props['PID'];
      }
    }      
        
    // recursively build the tree, starting at the homepage 
    $str = $this->compilePageTree($tpages, $homepage, '');
    fputs($fp, $str);
    fclose($fp);    
  }
  
  
  /** Compile a single page
   */
  function compilePageTree(&$tpages, $PID, $ploc)
  {
    global $globals;
    
    $cpage = $tpages[$PID];
    $ploc = ($ploc ? "$ploc/" : "") . $cpage->props['location'];
          
    // add this page
    $out = "$ploc\t$PID\t".$cpage->props['parent']."\n";      
    
    // add children
    $res = $globals->db->query("select PID from {$this->table_page} where parent='$PID'");    
    while (list($child) = mysql_fetch_row($res))
    {           
      $out .= $this->compilePageTree($tpages, $child, $ploc);
    }      
    mysql_free_result($res);
    return $out;
  }

  
  /** Load all active plugins for the specified page.
   *
   * @param $bpage
   */
  function loadPlugins(&$bpage)
  {
    global $globals;
  
    $plugins = $globals->plugins->cacheGetActive($this->pluginsCache, $this->alias, $page);
    $loaded = array();
    foreach ($plugins as $plugname => $plugentry) 
    {
      $loaded[$plugname] =& $globals->plugins->load($plugname, $plugentry);
    }
    return $loaded;
  }
  
  
  /** Read the compiled plugin cache
   */
  function readPlugins()
  {
    global $globals;

    $this->pluginsCache = $globals->plugins->readCache($this->pluginsCacheFile, $this->alias);
  }
  
    
  /** Read the compiled directory tree
   */
  function readTree()
  {
    global $globals;
    
    // if the tree cache does not exits, try to init it
    if (!file_exists($this->treeCacheFile)) {
      $this->compileTree();
    }
    
    if (!$fp = fopen($this->treeCacheFile, "r")) {
      trigger_error("failed to open '{$this->treeCacheFile}' for reading", E_USER_ERROR);
      return;
    }
    
    $locations = array();
    while ($line = fgets($fp))
    {
      $line = substr($line, 0, -1);
      $bits = explode("\t", $line);
      list($loc, $pid, $parent) = $bits;       
      $locations[$loc] = $pid;
    }
    fclose($fp);

    $this->treeCache = $locations;    
  }
   
}
