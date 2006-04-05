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


require_once 'diogenes.page.inc.php';
require_once 'Barrel/Options.php';

/** This class describes a toplevel Diogenes page.
 */
class DiogenesToplevel extends DiogenesPage {

  /** The constructor.
   *
   * @param admin is this an admin page?
   */
  function DiogenesToplevel($admin = false)
  {
    // page basics
    $this->DiogenesPage();
    $this->makeHead();
    $this->assign('site',"Diogenes");  
    $this->assign('page', $admin ? __("Toplevel administration") : __("Home"));

    // start session
    $this->startSession();

    // handle logout request
    if (isset($_REQUEST['dologout'])) 
      $this->doLogout();

    // do auth
    if ($admin || isset($_REQUEST['doauth']))
      $_SESSION['session']->doAuth($this);
    
    if ($admin && !$_SESSION['session']->hasPerms("root"))
      $this->kill(__("You are not authorized to view this page!"), 403);
  }


    
  /** Build the contents of the page's "head" tag.
   */
  function makeHead()
  {
    array_push($this->head, '<meta name="description" content="Diogenes content management system" />');
    array_push($this->head, '<meta name="keywords" content="Diogenes, Polytechnique.org, Jeremy Lain&eacute;" />');
    array_push($this->head, '<link rel="stylesheet" href="'.$this->url("common.css").'" type="text/css" />');
    array_push($this->head, '<link rel="stylesheet" href="'.$this->url("toplevel.css").'" type="text/css" />');
    array_push($this->head, '<link rel="icon" href="'.$this->url("images/favicon.png").'" type="image/png" />');
  }


  /** Build the page's menu.
   */
  function makeMenu()
  {
    global $globals;
    
    // menu style & theme
    $this->assign('menustyle', $globals->menu_style);
    $this->assign('menutheme', $globals->menu_theme);

    // menu items
    array_push($this->menu, array(0,"Diogenes", "", 1));
    array_push($this->menu, array(1,__("Home"), $this->url("")));
    array_push($this->menu, array(1,__("User manual"), __("http://diogenes-doc.polytechnique.org/en-user/")) );

    if ($this->isLogged()) {
      array_push($this->menu, array(1,__("Preferences"),$this->url("prefs.php")) );
      array_push($this->menu, array(1,__("Logout"), $this->url("?dologout=1")) );
    } else {
      array_push($this->menu, array(1,__("Login"), $this->url("?doauth=1")) );
    }
    if ($this->isRoot()) {
      array_push($this->menu, array(0,__("Sites"), "", 1));
      array_push($this->menu, array(1, __("Root manual"), __("http://diogenes-doc.polytechnique.org/en-root/")) );
      array_push($this->menu, array(1,__("List of sites"), $this->url("toplevel/")) );
      array_push($this->menu, array(1,__("Administrators"),$this->url("toplevel/admins.php")) );
      array_push($this->menu, array(1,__("Global options"),$this->url("toplevel/options.php")) );
      array_push($this->menu, array(1,__("Plugins"),$this->url("toplevel/plugins.php")));      
      array_push($this->menu, array(0,__("Users"), "", 1));
      array_push($this->menu, array(1,__("User accounts"),$this->url("toplevel/accounts.php")) );
      array_push($this->menu, array(1,__("Browse user log"),$this->url("toplevel/logger.php")) );
      array_push($this->menu, array(1,__("Logger actions"),$this->url("toplevel/logger_actions.php")) );
    }
  }

}

?>
