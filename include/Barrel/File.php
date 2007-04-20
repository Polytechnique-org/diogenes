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
 

/** This class describes a Barrel File.
 */
class Diogenes_Barrel_File
{
  /** The Page this File belongs to */  
  var $page;
  
  /** File properties */
  var $props = array();
  
  /** Constructs a new File.
   *
   * @param $page
   * @param $file
   */  
  function Diogenes_Barrel_File(&$page, $file)
  {    
    if (!is_object($page)) 
    {
      trigger_error("\$page is not an object!", E_USER_ERROR);
    }
    
    $this->page =& $page;
    $this->props = array(
      'file' => $file
    );
    
    /*
    if (is_array($props))
    {
      foreach (array_keys($props) as $key) 
      {
        $this->props[$key] = $props[$key];
      }
    }*/
  }
  
  
  /** Delete a file (not implemented)
   *
   * @param $barrel
   * @param $dir
   */
  function delete(&$barrel, $dir)
  {     
    global $globals;
    
  }
  
  
  /** Return the list of action applicable to the file
   *
   * @param $canedit
   */
  function make_actions($canedit)
  {
    global $globals;

    $dir = $this->page->props['PID'];
    $file = $this->props['file'];
    
    $rev = "files?action=revs&amp;dir=$dir&amp;target=$file";
    $edit = "edit?dir=$dir&amp;file=$file";
    $del = "javascript:file_delete('$dir','$file');";
    $rename = "javascript:file_rename('$dir','$file');";
    $view = "../". $this->page->getLocation($file);            

    $actions = array();
    if ($view) array_push($actions, array(__("view"), $view, "view"));
    if ($edit && $canedit) array_push($actions, array(__("edit"), $edit, "edit"));
    if ($rev) array_push($actions, array(__("revisions"),$rev, "revisions"));
    if ($rename && $canedit) array_push($actions, array(__("rename"), $rename, "rename"));
    if ($del && $canedit) array_push($actions, array(__("delete"), $del, "delete"));
    
    return $globals->icons->get_action_icons($actions);
  }
  
      
  /** Build the 'File' toolbar
   *
   * @param $canedit
   */
  function make_toolbar($canedit)
  {
    $dir = $this->page->props['PID'];
    $file = $this->props['file'];
    global $afile;
        
    $filebar = array ();
    if ($canedit)
    {
      array_push($filebar, array( __("raw editor"), ($afile == "edit") ? "" : "edit?dir=$dir&amp;file=$file"));
      array_push($filebar, array( __("HTML editor"), ($afile == "compose") ? "" : "compose?dir=$dir&amp;file=$file"));
    }
    array_push($filebar, array( __("file revisions"), "files?action=revs&amp;dir=$dir&amp;target=$file"));
    
    return $filebar;
  }
  
}
 
?>
