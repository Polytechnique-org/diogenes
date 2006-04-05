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


require_once 'diogenes/diogenes.core.page.inc.php';
require_once 'diogenes/diogenes.misc.inc.php';

/** This class describes a generic Diogenes page. This class
 *  is inherited to display barrel, admin or toplevel pages.
 *
 * @see DiogenesBarrel DiogenesAdmin DiogenesToplevel
 */
class DiogenesPage extends DiogenesCorePage
 {

  /** Handle to database */
  var $dbh;

  /** An array holding the contents of the 'head' tag */
  var $head = array();

  /** An array of items for the 'menu' area */
  var $menu = array();

  /** Whether we're into kill() or not */
  var $_dying = false;

  /** The constructor.
   */
  function DiogenesPage()
  {
    global $globals;
    $this->dbh =& $globals->db;

    // call parent constructor
    $this->DiogenesCorePage();

    // register Smarty functions
    $this->register_function("menu","diogenes_func_menu");

    // common Smarty assignments
    $this->assign('poweredby', $globals->urlise(__("Powered by Diogenes") . " {$globals->version}"));
    $this->assign('phplayersmenu', $this->url("phplayersmenu"));
    $this->assign_by_ref('head', $this->head);
    $this->assign_by_ref('menuitems', $this->menu);

    // debugging assignments
    $this->assign('msg_debug_bar', __("debugging"));
    $this->assign('msg_debug_calltrace', __("call trace"));
    $this->assign('msg_debug_dbtrace', __("database trace"));
    $this->assign('msg_debug_plugins', __("plugins"));
  }

 
  /** Display a Smarty template.
   *
   * @param $template the template for the current page
   * @param $master the master template
   */
  function display($template, $master = '') {
    global $globals;
    
    $this->assign('page_template', $template);
    if ($globals->debugdatabase) 
      $this->assign('db_trace',$globals->db->trace_format($this)); 
    if ($globals->debugplugins)
      $this->assign('plugins_trace',$globals->plugins->trace_format($this));
    if (($globals->debugplugins) || ($globals->debugdatabase))
      $this->assign('debug_css', $this->url("common.css"));
    $this->makeMenu();

    if (!$master)
      $master = $this->getTemplate();

    parent::display($master);
  }


  /** Perform a logout. This should destroy both the session
   *  and the logger objects.
   */
  function doLogout()
  {
    global $globals;
    $this->log('auth_logout', '');
    unset($_SESSION['log']);
    $_SESSION['session'] = new $globals->session;
  }


  /** Returns the master template for the current context. 
   */
  function getTemplate()
  {
    global $globals;

    if ($globals->template) {
      // we have a system-wide default template, get its full path
      $tpl = $this->templatePath($globals->template);
    } else {
      // fall back on the default template
      $tpl = 'master.tpl';
    }
    return $tpl;
  }


  /** Returns the available master templates. */
  function getTemplates()
  {
    global $globals;
    
    // the default template
    $templates[0] = "<default>";

    // lookup templates in the template directory
    if ($globals->template_dir && is_dir($globals->template_dir)) {
      $files = System::find($globals->template_dir.' -maxdepth 1 -name *.tpl');
      foreach ($files as $file)
        $templates["global:".basename($file)] = "[global] ".basename($file);
    }
    return $templates; 
  }


  /** Send an HTTP status header.
   *
   * @param code the HTTP status code
   */
  function httpStatus($code)
  {
    $message = array(
      400 => "HTTP/1.0 400 Bad Request",
      403 => "HTTP/1.0 403 Forbidden; Access Denied; Banned",
      404 => "HTTP/1.0 404 Not Found",
      500 => "HTTP/1.0 500 Internal Server Error",
    );
    
    if (!headers_sent())
      header(isset($message[$code]) ? $message[$code] : "HTTP/1.0 $code");
  }

  
  /** Report an information.
   *
   * @param msg
   */
  function info($msg) {
    $this->append('status',$msg);
  }


  /** Is the user logged in ? */
  function isLogged() {
    return isset($_SESSION['session']) && $_SESSION['session']->hasPerms('auth');
  }


  /** Is the user a root ("toplevel") admin ? */
  function isRoot() {
    return isset($_SESSION['session']) && $_SESSION['session']->hasPerms('root');
  }


  /** Die and display an error message.
   *
   * @param $msg the message to display
   * @param $code the HTTP status code to send
   */
  function kill($msg, $code = 500) {
    if ($this->_dying)
    {
      // We're in a loop of kills.  This is very, very bad.
      // We need to bale as quick as possible, because we can't rely on
      // *any* system code to not be the source of the kill() call.
      echo "<h1>Very fatal error: $msg</h1>\n";
      exit;
    }
    
    $this->_dying = true;
    $this->httpStatus($code);
    $this->assign('greeting', __("Diogenes error"));
    $this->assign('page', __("Error"));
    $this->assign('page_content', "<p>$msg</p>");
    $this->display('');
    exit;
  }


  /** Display the dreaded "file not found page".
   *
   * @param msg optional extra error message
   */
  function kill404($msg = "") {
    if ($msg)
      $this->info($msg);
    $this->kill( __("The requested document was not found."), 404);
  }


  /** Log an information.
   *
   * @param action
   * @param data
   */
  function log($action,$data="") {
    if (isset($_SESSION['log']) && is_object($_SESSION['log']))
      $_SESSION['log']->log($action,$data);
  }


  /** Make the menu.
   */
  function makeMenu() {
  
  }


  /** Start session handling.
   */
  function startSession() {
    global $globals;
    
    session_start();
    if (!isset($_SESSION['session']))
      $_SESSION['session'] = new $globals->session;
  }


  /** Returns the path to a given template. */
  function templatePath($template)
  {
    global $globals;
    
    $bits = split(":", $template);
    switch ($bits[0]) {
    case "global":
      $path = $globals->template_dir."/". $bits[1];
      break;
    default:
      $this->kill("Unkown template type : '$template'");
    }
    return $path;
  }


  /** Adds a toolbar to the top of the page.
   *
   * @param title
   * @param items
   */
  function toolbar($title, $items) {
    $this->append('toolbars', array('title'=>$title, 'items'=>$items));
  }


  /** Returns the URL to a Diogenes barrel.
   *
   * @param alias
   * @param vhost
   * @param rel
   */
  function urlBarrel($alias,$vhost,$rel="") {
    global $globals;
    return $vhost ? "http://$vhost/$rel" : "{$globals->rooturl}site/$alias/$rel";
  }

}


/** Displays a full menu.
 *
 * Parameters
 *  +items the menu items
 *  +style menu style (0, 1, 2)
 *  +theme menu theme
 *
 * @param params the function input
 */
function diogenes_func_menu($params)
{
  global $globals;
  
  extract($params);
  if (empty($items))
    return;

  switch($style) {
  case 1: case 2:      
    include("phplayersmenu/PHPLIB.php");
    include("phplayersmenu/layersmenu-common.inc.php");
    include("phplayersmenu/treemenu.inc.php");
    $tmp = "";
    $firstlevel = 0;
    $counter = 0;
    foreach ($items as $item) {
      $level = array_shift($item);
      // remember the level of the first entry
      if ($counter == 0)
        $firstlevel = $level;
      $dots = str_repeat(".",$level+1);
      $link = array_shift($item);
      $text = array_shift($item);
      $expanded = array_shift($item);
      $tmp .= "$dots|$link|$text||||$expanded\n";      
      $counter++;
    }

    $mid = new TreeMenu();
    $mid->setLibjsdir($globals->root."/htdocs/phplayersmenu/");
    $mid->setImgwww($globals->rooturl."phplayersmenu/$theme/");
    $mid->setMenuStructureString($tmp);
    $mid->parseStructureForMenu("diogenesmenu");
    $out = $mid->newTreeMenu("diogenesmenu");

    // this hack takes care of menus starting with 'orphan' child entries
    if (($firstlevel > 0) && ($pos = strpos($out,"<div id=\"jt1\" class=\"treemenudiv\">"))) {
      $insert = str_repeat("<div class=\"treemenudiv\">\n", $firstlevel);
      $out = substr($out,0,$pos) . $insert . substr($out,$pos);
    }
    break;
    
  case 0: default:
    $out = "<div class=\"menu\">";
    $oLevel = 0;
    $oExpanded = 1;
    foreach($items as $item) {
      $level = $item[0];
      $expanded  = isset($item[3]) ? $item[3] : 0;
      if ($oExpanded || $level <= $oLevel) {
        $out .= diogenes_func_menu_item(compact("item"));
        $oLevel = $level;
        $oExpanded = $expanded;
      }
    }
    $out .= "</div>";
    break;
  }
  return $out;
}

?>
