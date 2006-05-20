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


/** This class provides a configuration tool for Diogenes plugins. 
 */
class Diogenes_Plugin_Editor {
  /** Should we show plugins parameters? */
  var $show_params = 1;
  
  /** Should editing functions be disabled? */
  var $readonly = false;

  /** The alias of the barrel we are working on. */
  var $plug_barrel;

  /** The PID of the page we are working on. */
  var $plug_page;
      
  /** The write permissions of the page we are working on. */
  var $plug_page_wperms;
  
  /** Construct a new plugin editor.
   *
   * @param $plug_barrel
   * @param $plug_page
   * @param $plug_page_wperms
   */
  function Diogenes_Plugin_Editor($plug_barrel, $plug_page, $plug_page_wperms = '')
  {
    $this->plug_barrel = $plug_barrel;
    $this->plug_page = $plug_page;
    $this->plug_page_wperms = $plug_page_wperms;
  }


  /** Run the plugin editor.
   *
   * @param $page
   * @param $outputvar
   */
  function run(&$page, $outputvar = '')
  {
    global $globals;

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';    
    $target = isset($_REQUEST['plug_target']) ? $_REQUEST['plug_target'] : '';

    // load all available plugins
    $cachefile = $globals->plugins->cacheFile($this->plug_barrel);

    // if the tree cache does not exits try to init it
    if(!file_exists($cachefile))
    {
      $globals->plugins->compileCache($this->plug_barrel, $page);
    }
    $cache = $globals->plugins->readCache($cachefile, $this->plug_barrel);
    $available = $globals->plugins->cachedAvailable($cache, $this->plug_barrel, $this->plug_page);

    // handle updates
    $rebuild_cache = 0;
    switch ($action) {
    case "move_up": case "move_down":
      if ($this->readonly) die("Sorry, this plugin view is read-only.");

      $delta = ($action == "move_down") ? 1 : -1;
      $page->info("moving plugin '$target'..");
      $plugcache_a = $globals->plugins->cacheGet($cache, $this->plug_barrel, $this->plug_page, $target);
      $plug_a =& $globals->plugins->load($plugcache_a['name'], $plugcache_a);

      if (is_object($plug_a) && ($plug_a->active)) {
        $old_pos = $plug_a->pos;
        $plugcache_b = $globals->plugins->cacheGetAtPos($cache, $this->plug_barrel, $this->plug_page, $old_pos + $delta);

        if (is_array($plugcache_b))
        {
          $plug_b =& $globals->plugins->load($plugcache_b['name'], $plugcache_b);

          // swap the current plugin and the next plugin
          if (is_object($plug_b) && ($plug_b->active)) 
          {
            $plug_a->toDatabase($this->plug_barrel, $this->plug_page, $old_pos + $delta);
            $plug_b->toDatabase($this->plug_barrel, $this->plug_page, $old_pos);
          }
        }
      }
      $rebuild_cache = 1;
      break;

    case "update":
      if ($this->readonly) die("Sorry, this plugin view is read-only.");
      foreach ($available as $plugin => $plugentry)
      {
        // check we have a valid cache entry
        if (!is_array($plugentry)) {
          $page->info("could not find plugin '$plugin' in cache for barrel '{$this->plug_barrel}'");
          return;
        }

        $plug_h =& $globals->plugins->load($plugin, $plugentry);
        if (!is_object($plug_h)) {
          $page->info("could not load plugin '$plugin' in cache for barrel '{$this->plug_barrel}'");
          return;
        }

        if ($pos !== false) {
          // check the plugin is allowed in the current context
          if ($this->plug_barrel and $this->plug_page) {
            $wperms = $this->plug_page_wperms;

            // $page->info("checking plugin '$plugin' vs. write permissions '$wperms'..");
            if (!$plug_h->allow_wperms($wperms))
            {
              $page->info("plugin '$plugin' is not allowed with write permissions '$wperms'!");
              break;
            }
          }

          // retrieve parameters from REQUEST
          if (isset($_REQUEST[$plug_h->name."_status"])) 
	  {
            $plug_h->status = $_REQUEST[$plug_h->name."_status"];
          }
          foreach ($plug_h->getParamNames() as $key)
          {
            if (isset($_REQUEST[$plug_h->name."_".$key])) {
              $plug_h->setParamValue($key, $_REQUEST[$plug_h->name."_".$key]);
            }
          }

          // write parameters to database
          $plug_h->toDatabase($this->plug_barrel, $this->plug_page, $pos);
          $rebuild_cache = 1;
        }
      }
      break;
    }

    // if necessary, rebuild the plugin cache
    if ($rebuild_cache)
    {
      // log this action
      if ($this->plug_barrel)
      { 
        if ($this->plug_page)
        {
          $page->log('page_plugins', $this->plug_barrel.":".$this->plug_page);
        } else {
          $page->log('barrel_plugins', $this->plug_barrel.":*");
        }
      }
      // rebuild plugin cache
      $globals->plugins->compileCache($this->plug_barrel, $page);
      $cache = $globals->plugins->readCache($cachefile, $this->plug_barrel);
      $available = $globals->plugins->cachedAvailable($cache, $this->plug_barrel, $this->plug_page);
    }
    
    // get dump of plugins to fill out form
    $page->assign('plug_barrel', $this->plug_barrel);
    $page->assign('plug_page', $this->plug_page);
    $plugs = array();
    
    // start by adding the active plugins
    foreach ($available as $plugname => $plugcache)
    {
      if ($plugcache['status'] == PLUG_ACTIVE)
      {
         $plugentry = $plugcache;
         $plugentry['icon'] = $globals->icons->get_action_icon('plugins');
         $type = $plugentry['type'];
         if (!empty($plugs[$type])) {
            $plugentry['move_up'] = 1;
            $last = count($plugs[$type]) - 1;
            $plugs[$type][$last]['move_down'] = 1;
          } else {
            $plugs[$type] = array();
          }
          array_push($plugs[$type], $plugentry);
      }
    }

    // next we add the inactive plugins
    if (!$this->readonly)
    {
      foreach ($available as $plugname => $plugcache)
      {
        if ( ($plugcache['status'] == PLUG_AVAILABLE) ||
             (($plugcache['status'] == PLUG_DISABLED) && !$this->plug_barrel) )
        {
          $plugentry = $plugcache;
          $plugentry['icon'] = $globals->icons->get_action_icon('plugins');
          $type = $plugentry['type'];
          if (empty($plugs[$type])) {
            $plugs[$type] = array();
          }
          array_push($plugs[$type], $plugentry);
        }
      }
    }

    $page->assign('plugins', $plugs);

    // values
    $page->assign('show_params', $this->show_params);
    $page->assign('readonly',$this->readonly);
    $statusvals = array(0 => 'disabled', '1' => 'available', 2 => 'enabled');
    $page->assign('statusvals', $statusvals);

    // translations    
    $page->assign('msg_submit', __("Submit"));
    $page->assign('msg_plugedit_plugin', __("plugin"));    
    $page->assign('msg_plugedit_plugins', __("plugins"));
    $page->assign('msg_plugedit_description', __("description"));
    $page->assign('msg_plugedit_parameters', __("parameters"));
    $page->assign('msg_move_up', __("move up"));
    $page->assign('msg_move_down', __("move down"));

    // if requested, assign the content to be displayed
    if (!empty($outputvar)) {
      $page->assign($outputvar, $page->fetch('plugin-editor.tpl'));
    }
  }
  
  
  /** Do not display plugin parameters.
   *
   * @param $hide boolean
   */
  function hide_params($hide)
  {
    $this->show_params = !$hide;
  }
  
}

?>
