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


require_once 'diogenes.barrel.inc.php';

/** Page for administrators only.
 */
class DiogenesAdmin extends DiogenesBarrel
{
  /** The constructor. Creates an administrative page.
   *
   * @param dir the directory to operate on (optional)
   */
  function DiogenesAdmin($dir = "")
  {
    global $globals;

    $this->DiogenesBarrel();
    $this->assign('page',__("Administration"));

    // check permissions
    $this->startSession();
    if (!empty($dir)) {
      $res = $globals->db->query("select wperms from {$this->barrel->table_page} where PID='$dir'");
      if (!list($wperms) = mysql_fetch_row($res))
        $this->kill(__("Directory not found"));
      $this->checkPerms($wperms);
    } else {
      $this->checkPerms('admin');
    }
  }

  
  /** Returns the master template for the current context. 
   */
  function getTemplate()
  {
    return DiogenesPage::getTemplate('master.tpl');
  }
  
  
  /** Build the admin menu.
   */
  function makeMenu() {
    global $globals;

    // retrieve homepage PID
    $homepage = $this->barrel->getPID('');
    
    array_push($this->menu, array( 1, __("Home"), $this->urlSite("") ) );
    array_push($this->menu, array( 1, __("Admin manual"), __("http://diogenes-doc.polytechnique.org/en-admin/") ) );
    array_push($this->menu, array( 0, __("Administration"), "", 1 ) );
    array_push($this->menu, array( 1, __("Activity"), "./") );
    array_push($this->menu, array( 1, __("Options"), "options") ); 
    if ($this->barrel->hasFlag('plug'))
    {
      array_push($this->menu, array( 1, __("Plugins"), "plugins") );    
    }
    array_push($this->menu, array( 1, __("Users"), "users") );
    array_push($this->menu, array( 1, __("WebDAV"), "webdav") );
    array_push($this->menu, array( 0, __("Content"), "", 1 ) );
    array_push($this->menu, array( 1, __("Pages catalog"), "files?dir={$homepage}&amp;file={$globals->cssfile}") );
    array_push($this->menu, array( 1, __("Edit style sheet"), "edit?dir={$homepage}&amp;file={$globals->cssfile}") );
    array_push($this->menu, array( 1, __("Edit menu"), "menus") );
  }

}

?>
