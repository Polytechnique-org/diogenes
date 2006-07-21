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
 
require_once 'diogenes.icons.inc.php';
require_once 'Barrel/File.php';


/** This class describes a Barrel Page.
 */
class Diogenes_Barrel_Page
{
  /** The Barrel this Page belongs to. */
  var $barrel;
  
  /** The Page's properties */
  var $props = array();
  
  
  /** Construct a Barrel Page object.
   *
   * @param $barrel
   * @param $props
   */
  function Diogenes_Barrel_Page(&$barrel, $props)
  {    
    if (!is_object($barrel)) 
    {
      trigger_error("\$barrel is not an object!", E_USER_ERROR);
    }
    
    $this->barrel =& $barrel;
    $this->props = array(
      'PID' => 0,
      'parent' => 0,
      'location' => '',
      'title' => '',
      'perms' => 'public',
      'wperms' => 'admin',
      'status' => 0,
      'template' => ''
    );
    
    if (is_array($props))
    {
      foreach (array_keys($props) as $key) 
      {
        $this->props[$key] = $props[$key];
      }
    }
    //echo "[" . $this->props['PID'] . "][". $this->props['location'] . "], parent : [".$this->props['parent']."]<br/>";    
  }
  

  /** Delete a Barrel Page.
   *
   * @param $barrel
   * @param $dir
   * @param $caller
   */
  function delete(&$barrel, $dir, &$caller)
  {     
    global $globals;
      
    $caller->info(__("Deleting page"). " $dir");    
    
    // check there are no child pages
    $res = $globals->db->query("select PID from {$barrel->table_page} where parent=$dir");
    $num = mysql_num_rows($res);
    mysql_free_result($res);  
    if ($num > 0)
    {
      $caller->info(__("Not deleting page, it has child pages!"));
      return false;
    }
    
    $rcs = $caller->getRcs();
    $globals->db->query("delete from {$barrel->table_page} where PID=$dir");
    $caller->log("page_delete","{$barrel->alias}:$dir");
    system("rm -rf ". escapeshellarg($rcs->rcsPath($dir)));
    system("rm -rf ". escapeshellarg($rcs->spoolPath($dir)));
    $barrel->compileTree();
    $barrel->readTree();
    return true;
  }
  

  /** Read a Page's properties from database.
   *
   * @param $barrel
   * @param $dir
   */
  function fromDb(&$barrel, $dir)
  {
    global $globals;
    $retval = '';
    
    $res = $globals->db->query("select * from {$barrel->table_page} where PID='$dir'");
    if ($props = mysql_fetch_assoc($res)) {
      $retval = new Diogenes_Barrel_Page($barrel, $props);
    }
    mysql_free_result($res);
    return $retval;
  }

    
  /** Returns the location of a given file
   *
   * @param $file
   */    
  function getLocation($file = '')
  {
    $dirloc = $this->barrel->getLocation($this->props['PID']);
    $floc = (strlen($dirloc) ? "$dirloc/" : '') . $file;
    return $floc;
  }
  
  
  /** Return the list of action applicable to the page
   */
  function make_actions()
  {
    global $globals;
    $props = $this->props;
    
    $actions = array(
      array( __("view"), "../". $this->getLocation(), "view" ),
      array( __("edit"), "edit?dir={$props['PID']}&amp;file=page.html", "edit"),      
      array( __("properties"), "pages?action=edit&amp;dir={$props['PID']}", "properties"),
      array( __("revisions"), "files?action=revs&amp;dir={$props['PID']}&amp;target={$globals->htmlfile}","revisions")
     );
     
    if ($this->barrel->flags->hasFlag('plug'))
    {
      array_push($actions, array( __("plugins"), "plugins?plug_page={$props['PID']}", "plugins"));
    }
    if ($props['location'] != '')
      array_push($actions, array( __("delete"), "javascript:page_delete('{$props['PID']}','{$props['location']}');","delete"));   
      
    return $globals->icons->get_action_icons($actions);
  }
  
  
  /** Build the 'Page' toolbar
   */
  function make_toolbar(&$caller)
  {
    global $globals;
    $props = $this->props;
    
    $topbar = array ();
    $from = htmlentities($caller->script_uri());
    
    if ($props['PID']) {    
      $hp = $this->barrel->getPID('');
      array_push($topbar, array(__("home"), ($props['PID'] == $hp) ? "" : "files?dir=$hp"));
      array_push($topbar, array(__("parent page"), $props['parent'] ? "files?dir=".$props['parent'] : ""));

      array_push($topbar, array( __("browse files"), "files?dir={$props['PID']}" ));
      array_push($topbar, array( __("page properties"), "pages?dir={$props['PID']}" ));
      array_push($topbar, array( __("view page"), "../". $this->getLocation()));
      array_push($topbar, array(__("add a page"), "pages?action=edit&amp;parent=".$props['PID']."&amp;from=$from") );
      if ($this->barrel->flags->hasFlag("plug")) {
        array_push($topbar, array( __("plugins"), "plugins?plug_page={$props['PID']}" ) );
      }
    }
    
    return $topbar;

  }
  
  
  /** Build the 'File' toolbar
   */
  function make_doc_toolbar(&$rcs)
  {
    global $globals;

    if ($globals->word_import && file_exists($rcs->spoolPath($this->props['PID'],$globals->wordfile)) ) {
      $bfile = new Diogenes_Barrel_File($this, $globals->wordfile);
      $toolbar = $bfile->make_toolbar(0);
    } else {
      $bfile = new Diogenes_Barrel_File($this, $globals->htmlfile);
      $toolbar = $bfile->make_toolbar(1);
    }
    return $toolbar;
  }


  /** Write the page's properties to database and returns the PID of that page.
   *
   * @param $homepage
   * @param $caller
   */
  function toDb($homepage, &$caller)
  {
    global $globals;
    
    $props = $this->props;
    // check we are not creating a publicly writable page
    // on a barrel with PHP execution enabled!
    if ($props['PID'])
    {
      $cache = $globals->plugins->readCache($this->barrel->pluginsCacheFile, $this->barrel->alias);
      $plugs_active = $globals->plugins->cachedActive($cache, $this->barrel->alias, $props['PID']);
      foreach($plugs_active as $plugentry)
      {
        $plug_h = $globals->plugins->load($plugentry);
        if (!is_object($plug_h))
        {
          $caller->info("failed to load plugin '{$plugentry['plugin']}'");
          return;
        }
        if (!$plug_h->allow_wperms($props['wperms']))
        {
          $caller->info("plugin '{$plugentry['plugin']}' is not allowed with write permissions '{$props['wperms']}'!");
          return;
        }
      }
    }
    
    // check that the location is valid
    if ($homepage) 
    {          
      // homepage
      $props['location'] = '';    
      
    } else {
    
      // check the location is well formatted
      if (!preg_match("/^[a-zA-Z0-9_\-]*$/",$props['location']))
      {
        $caller->info(__("the page location cannot contain spaces or special characters"));
        return;
      } 
      
      // check this is not a forbidden location
      if (in_array($props['location'], $globals->invalidlocations))
      {
        $caller->info(__("this location cannot be used, it is reserved by Diogenes"));
        return;
      }
      
    }
    
    // this is a new entry, initialise
    if (!$props['PID']) {
      // new entry
      $globals->db->query("insert into {$this->barrel->table_page} set location='temp'");
      $props['PID'] = mysql_insert_id();
      $caller->info(__("Creating new page")." {$props['PID']}");

      $caller->log("page_create","{$this->barrel->alias}:{$props['PID']}");
        
      // initialise the page
      $rcs = $caller->getRcs();
      $rcs->newdir("",$props['PID']);
      $rcs->commit($props['PID'],$globals->htmlfile,"");
    } else {
      $caller->log("page_props","{$this->barrel->alias}:{$props['PID']}");    
    }


    // check we have a location    
    if (!$homepage and !strlen($props['location'])) 
    {
      $props['location'] = $props['PID'];  
    }
      
        
    // check that the filiation is valid
    if ($props['parent'])
    {
      // we need to insure that the parent is not already a child of the current page
      $parent = $props['parent'];
      while ($parent)
      {
        $oldparent = $parent;
        $res = $globals->db->query("select parent from {$this->barrel->table_page} where PID=$parent");
        list($parent) = mysql_fetch_row($res);
        mysql_free_result($res);
        if ($parent == $props['PID'])
        {
          $caller->info(__("A page cannot be its own parent (page $oldparent is a child of $parent)!"));
          break;      
        }        
      }
    }
    
//    $caller->info("setting parent to {$props['parent']}");        

    // update data fields
    $sql = 
      "update {$this->barrel->table_page} set ".
        "parent='{$props['parent']}',".
        "location='{$props['location']}',".
        "title='{$props['title']}',".
        "perms='{$props['perms']}',".
        "wperms='{$props['wperms']}',".
        "status='{$props['status']}',".
        "template='{$props['template']}' ".
      "where PID='{$props['PID']}'";
    //$caller->info($sql);
    $globals->db->query($sql);
      
    // order by location
    $globals->db->query("alter table {$this->barrel->table_page} order by parent,location");
    
    // recompile tree
    $this->barrel->compileTree(); 
    $this->barrel->readTree();

    return $props['PID'];
  } 

}
 
?>
