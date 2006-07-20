<?php
/*
 * Copyright (C) 2003-2005 Polytechnique.org
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
require_once 'Barrel.php';
require_once 'Barrel/Menu.php';

/** This class is used to display a page of a Diogenes barrel,
 *  that is an RCS-managed website with a virtual directory
 *  structure.
 */
class DiogenesBarrel extends DiogenesPage
{
  // barrel definition info
  
  /** The database table holding the menus */
  var $table_menu;

  /** The barrel's alias. */
  var $alias;
  
  /** The Diogenes_Barrel representing the barrel */
  var $barrel;
  
  // context info
  /** A Diogenes_Barrel_Page representing the current page */
  var $curpage;
    
  /** Information about the current location */
  var $pathinfo;
  
  /** Can the current user edit this page? */
  var $canedit = false;

  
  /** Constructs a Smarty-derived object to display contents within a barrel.
   *
   * @param $override_pathinfo 
   */
  function DiogenesBarrel($override_pathinfo = null)
  {
    global $globals;

    // call parent constructor
    $this->DiogenesPage();

    // break down PATH_INFO into site and location components
    $mypathinfo = $override_pathinfo ? $override_pathinfo : $_SERVER['PATH_INFO'];
    $this->pathinfo = $this->parsePathInfo($mypathinfo);
    if (!$this->pathinfo)
      $this->kill404("Invalid location specified!");

    // Retrieve site-wide info from database
    $this->barrel = new Diogenes_Barrel($this->pathinfo['alias']);
    if (!$this->barrel->alias)
      $this->kill404("Unknown barrel requested : {$this->pathinfo['alias']}");
      
    // Legacy
    $this->alias = $this->barrel->alias;
    $this->table_menu = $this->barrel->table_menu;
   
    // Build page head
    $this->makeHead();

    // Check the requested page exists
    $tdir = $this->pathinfo['dir'];    
    if ($tdir != 'admin')
    {
      if (!$this->pathinfo['PID'] = $this->barrel->getPID($tdir))
      {
        $this->kill404("Unknown location specified '$tdir'!");
      }
    }
  }

  /** Check the user has the right permissions.
   *  Read the user's permissions for the current site from database.
   *
   * @param level the required permissions level
   */
  function checkPerms($level) {
    global $globals;

    if ($level != "public")
      $_SESSION['session']->doAuth($this);

    $_SESSION['session']->setBarrelPerms($this->alias);

    if (!$_SESSION['session']->hasPerms($level))
      $this->kill(__("You are not authorized to view this page!"), 403);
  }


  /** Read the contents for the current location.
   */
  function doContent()
  {
    global $globals;

  // Retrieve information specific to the current page

    // enable directory index
    $file = $this->pathinfo['file'] ? $this->pathinfo['file'] : $globals->htmlfile;

    // read from Db
    if (!$bpage = Diogenes_Barrel_Page::fromDb($this->barrel, $this->pathinfo['PID']))
    {
      $this->kill404("Directory not found : '{$this->pathinfo['dir']}' ({$this->pathinfo['PID']}) !");
    }
    $this->curpage =& $bpage;
        
    // check the permissions for the current location
    if (!$this->pathinfo['file'] || $bpage->props['perms'] != 'public' || isset($_REQUEST['rev'])) {
      $this->startSession();

      // handle login/logout requests
      if (isset($_REQUEST['dologout'])) 
        $this->doLogout();
      if (isset($_REQUEST['doauth'])) 
        $this->checkPerms('auth');

      $this->checkPerms($bpage->props['perms']);
 
      // can we edit this page?
      $this->canedit = $_SESSION['session']->hasPerms($bpage->props['wperms']);
    }

  // now we can display the page
    // check the location is valid
    if (!$this->barrel->spool->checkPath($bpage->props['PID'],$file,false))
      $this->kill404("Malformed location!");

    // check that the page is 'live'
    switch ($bpage->props['status']) {
    case 0:
      break;
    case 1:
      $this->assign('page_content', "<p>".__("This page is currently under construction.")."<p>");
      $this->display('');
      exit;
    default:
      $this->assign('page_content', "<p>".__("This page is currently unavailable.")."<p>");
      $this->display('');
      exit;
    }
    
    // if necessary, do a checkout
    if (isset($_REQUEST['rev'])) {
      $rcs = $this->getRcs();
      $path = $rcs->checkout($bpage->props['PID'],$file,$_REQUEST['rev'],System::mktemp("-d"));
    } else {
      $path = $this->barrel->spool->spoolPath($bpage->props['PID'],$file);
    }

    if (!is_file($path))
      $this->kill404("File not found : $path!");

    if (!$this->pathinfo['file']) {
      // this is a page, display it within header/footer framework
      $this->doPage($path, $bpage);
    } else {
      // otherwise, we send back the raw file
      $type = get_mime_type($path);
      if (is_mime_multipart($type)) {
        $boundary = get_mime_boundary($path);
	if ($boundary) $type = "$type; boundary=\"$boundary\"";
      }
      header("Content-Type:$type");
      header("Content-Length:".filesize($path));
      header("Last-modified:".gmdate("D, d M Y H:i:s T", filemtime($path)));
      readfile($path);
    }

  }


  /** Display a page within the header/footer framework.
   *
   * @param path the path of the file
   * @param bpage a Diogenes_Barrel_Page representing the current page
   */
  function doPage($path, $bpage)
  {
    global $globals;

    $this->assign('page',stripslashes($bpage->props['title']));
   
    // load plugins
    $this->barrel->readPlugins();    
    $active_plugins = $this->barrel->loadPlugins($bpage);    

    // search for rendering pluging
    $render_plugin = '';
    foreach ($active_plugins as $plugname => $plugobj) {
      if (is_object($plugobj) && ($plugobj->type == "render")) {
        $render_plugin = $plugobj;
      }
    }

    // source page or pass it to a rendering plugin
    if (is_object($render_plugin)) {    
      $content = $render_plugin->render($path);
    } else {
      $content = file_get_contents($path);
    }    
    
    // apply plugin filtering
    foreach ($active_plugins as $plugname => $plugobj) {
      if (is_object($plugobj) && ($plugobj->type == "filter")) {
        $content = $plugobj->filter($content);
      }
    }    
    $this->assign('page_content', $content);
 
    parent::display('', $this->getTemplate($bpage->props['template']));
  }

  
  /** Return an RCS handle. */
  function getRcs()
  {
    global $globals;
    return new $globals->rcs($this,$this->alias,$_SESSION['session']->username);
  }

 
  /** Returns the master template for the current context.
   *
   * @param template
   */
  function getTemplate($template = '')
  {
    if ($template)
    {
      // we have a page-specific template, get its full path
      $tpl = $this->templatePath($template);
    } else if ($this->barrel->options->template) {
      // we have default site template, get is full path
      $tpl = $this->templatePath($this->barrel->options->template);
    } else {
      // fall back on the system-wide default template
      $tpl = parent::getTemplate();
    }
    return $tpl; 
  }
  
  
  /** Returns the available master templates. */
  function getTemplates()
  {
    // the system-wide templates
    $templates = parent::getTemplates();
    $bbarrel =& $this->barrel;
    
    // lookup templates in the template directory
    if ($bbarrel->hasFlag('tpl') && $bbarrel->options->template_dir) {
      $dir = $bbarrel->spool->spoolPath($bbarrel->options->template_dir);
      $files = System::find($dir.' -maxdepth 1 -name *.tpl');
      foreach ($files as $file)
        $templates["barrel:".basename($file)] = "[barrel] ".basename($file);
    }
    return $templates; 
  }


  /** Is the user an administrator for the current barrel ? */
  function isAdmin() {
    return isset($_SESSION['session']) && $_SESSION['session']->hasPerms('admin');
  }


  /** Build the page's "head" tag.
   */
  function makeHead() {
    global $globals;
    $bbarrel =& $this->barrel;
    
    // site name
    $this->assign('site', stripslashes($bbarrel->options->title));
    
    // meta
    array_push($this->head, '<meta name="description" content="'.stripslashes($bbarrel->options->description).'" />');
    array_push($this->head, '<meta name="keywords" content="'.stripslashes($bbarrel->options->keywords).'" />');

    // stylesheets
    $this->sheets = array();
    array_push($this->sheets, $this->url("common.css"));
    if ($bbarrel->options->menu_style == 1 || $bbarrel->options->menu_style == 2) 
      array_push($this->sheets, $this->url("phplayersmenu/{$bbarrel->options->menu_theme}/style.css"));
    array_push($this->sheets, $this->urlSite("", $globals->cssfile));
    
    // add stylesheets to head
    foreach ($this->sheets as $mysheet) {
      array_push($this->head, '<link rel="stylesheet" href="'.$mysheet.'" type="text/css" />');
    }
    // favicon
    if ($bbarrel->options->favicon)
      array_push($this->head, '<link rel="icon" href="'.$this->urlSite("", $bbarrel->options->favicon).'" type="image/png" />');

    // RSS feed
    if ($bbarrel->options->feed_enable)
      array_push($this->head, '<link rel="alternate" type="application/rss+xml" title="'.stripslashes($bbarrel->options->title).'" href="'.$this->urlSite("admin", "rss").'" />');
  }


  /** Build the barrel's menu.
   */
  function makeMenu() {
    global $globals;
    $bbarrel =& $this->barrel;
    
    // menu style & theme
    $this->assign('menustyle', $bbarrel->options->menu_style);
    $this->assign('menutheme', $bbarrel->options->menu_theme);
    
    $PID = $this->curpage->props['PID'];
    
    // build the Diogenes part of the menu
    if (!$bbarrel->options->menu_hide_diogenes) {
      array_push($this->menu,array(0,__("Home"),$this->urlSite(""), 1));
      if ($this->isLogged()) {
        array_push($this->menu, array(1,__("Logout"), "?dologout=1") );
        array_push($this->menu, array(1,__("Preferences"), $this->urlSite("admin", "prefs")));
      } else {
        array_push($this->menu, array(1,__("Login"), "?doauth=1") );
      }
    
      if ($this->isAdmin()) {
        array_push($this->menu, array(1, __("Administration"), $this->urlSite("admin")));
        if ($PID)
          array_push($this->menu, array(1, __("Page properties"), $this->urlSite("admin", "pages?dir=$PID")));
      } elseif ($this->canedit && $PID) {
        array_push($this->menu, array(0, __("Edit this page"), "", 1));
        array_push($this->menu, array(1, __("Raw editor"), $this->urlSite("admin", "edit?dir=$PID&amp;file={$globals->htmlfile}")));
        array_push($this->menu, array(1, __("HTML editor"), $this->urlSite("admin" , "compose?dir=$PID&amp;file={$globals->htmlfile}")));
      }
    }

    // if this is an error page, we need to bail out here
    if (!isset($this->table_menu))
      return;
   
    // add the user-defined part of the menu
    $bmenu = new Diogenes_Barrel_Menu($this->dbh, $this->table_menu);
    $this->menu = array_merge($this->menu, $bmenu->makeMenu($PID, $this->barrel->options->menu_min_level, array($this, 'urlSiteByPid')));
  }


  /** 
   * Break down a PATH_INFO into site, page id and file
   * Directories *must* be accessed with a final slash.
   *
   * @param path the path to parse
   */
  function parsePathInfo($path) {
    if (empty($path) || !preg_match("/^\/([^\/]+)\/((.+)\/)?([^\/]*)$/",$path,$asplit))
      return false;
    
    $split['alias'] = $asplit[1];
    $split['dir'] = isset($asplit[3]) ? $asplit[3] : "";
    $split['file'] = isset($asplit[4]) ? $asplit[4] : "";
    return $split;
  }


  /** Return the current URI.
   */
  function script_uri()
  {
    if ($this->barrel->vhost)
      return preg_replace("/^(.*)\/site(\.php)?\/{$this->alias}\/(.*)/", "/\$3",$_SERVER['REQUEST_URI']);
    else
      return $_SERVER['REQUEST_URI'];
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
    case "barrel":
      $path = $this->barrel->spool->spoolPath($this->barrel->options->template_dir, $bits[1]);
      break;
    default:
      $path = parent::templatePath($template);
    }
    return $path;
  }


  /** Returns the URL to one of the barrel's pages relative to
   *  the current location.
   *
   * @param dir
   * @param file
   */
  function urlSite($dir, $file = '') {
    global $page;
    $tosite = strlen($this->pathinfo['dir']) ? str_repeat("../",1+substr_count($this->pathinfo['dir'],"/")) : '';
    $url = $tosite . (strlen($dir) ? "$dir/" : "") . $file;
    return strlen($url) ? $url : "./";
  }


  /** Returns the URL to one of the barrel's pages relative to
   *  the current location.
   *
   * @param dir
   * @param file
   */
  function urlSiteByPid($PID, $file = '')
  {
    return $this->urlSite($this->barrel->getLocation($PID), $file);
  }

}

?>
