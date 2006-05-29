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

define('MODE_NORMAL', 0);
define('MODE_ROOT', 1);
define('MODE_READONLY', 2);
define('MODE_NOPARAMS', 4);

/** This class provides a configuration tool for Diogenes plugins. 
 */
class Diogenes_Plugin_Editor {
  /** The alias of the barrel we are working on. */
  var $plug_barrel;

  /** The PID of the page we are working on. */
  var $plug_page;
      
  /** The write permissions of the page we are working on. */
  var $plug_page_wperms;

  /** The plugin editor mode */
  var $mode = MODE_NORMAL;
  
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

    // if the tree cache does not exits try to init it
    $cachefile = $globals->plugins->cacheFile($this->plug_barrel);
    if(!file_exists($cachefile))
    {
      $globals->plugins->compileCache($this->plug_barrel, $page);
    }
    $cache = $globals->plugins->readCache($cachefile, $this->plug_barrel);
    $available = $globals->plugins->cacheGetVisible($cache, $this->plug_barrel, $this->plug_page);

    // handle updates
    $rebuild_cache = 0;
    switch ($action) {
    case "update":
      if ($this->mode & MODE_READONLY) die("Sorry, this plugin view is read-only.");
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
          if ($plug_h->status & PLUG_SET)
          {
            $plug_h->toDatabase($this->plug_barrel, $this->plug_page, $pos);
          } else {
            $plug_h->eraseParameters($this->plug_barrel, $his->plug_page);
          }
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
      $available = $globals->plugins->cacheGetVisible($cache, $this->plug_barrel, $this->plug_page);
    }
    
    // get dump of plugins to fill out form
    $page->assign('plug_barrel', $this->plug_barrel);
    $page->assign('plug_page', $this->plug_page);
    $ro_plugs = array();
    $rw_plugs_on = array();
    $rw_plugs_off = array();
    
    /* run over the plugins and split plugins into the following categories:
     *  - read-only,
     *  - read-write/active 
     *  - read-write/inactive
     */
    foreach ($available as $plugname => $plugcache)
    {
      $plugentry = $plugcache;
      $plugentry['icon'] = $globals->icons->get_action_icon('plugins');
      $type = $plugentry['type'];

      // FIXME : the test below needs to be refined
      if (!($this->mode & MODE_ROOT) && ($plugentry['status'] & PLUG_LOCK))
      {
        $o_plugs =& $ro_plugs;
        $plugentry['readonly'] = 1;
      } else {
        if ($plugentry['status'] & PLUG_ACTIVE)
          $o_plugs =& $rw_plugs_on;
        else 
          $o_plugs =& $rw_plugs_off;
      }

      if (empty($o_plugs[$type])) {
        $o_plugs[$type] = array();
      }
      array_push($o_plugs[$type], $plugentry);
    }

    // merge the different plugin categories into a global list
    $plugs = array_merge_recursive($rw_plugs_on, $rw_plugs_off);
    $plugs = array_merge_recursive($plugs, $ro_plugs);
    $page->assign('plugins', $plugs);

    // debugging
    foreach ($plugs as $p_type => $p_entries)
    {
      $globals->plugins->log = array_merge($globals->plugins->log, $p_entries);
    }

    // values
    $page->assign('show_params', !($this->mode & MODE_NOPARAMS));
    $page->assign('readonly', ($this->mode & MODE_READONLY));
    $statusvals = array(
        PLUG_DISABLED => 'default',
        PLUG_SET | PLUG_DISABLED => 'off',
        PLUG_SET | PLUG_ACTIVE => 'on',
        PLUG_SET | PLUG_DISABLED | PLUG_LOCK => 'off (lock)',
        PLUG_SET | PLUG_ACTIVE | PLUG_LOCK => 'on (lock)',
    );
    $rwstatusvals = $statusvals;
    if (!($this->mode & MODE_ROOT)) {
      unset($rwstatusvals[PLUG_SET | PLUG_DISABLED | PLUG_LOCK]);
      unset($rwstatusvals[PLUG_SET | PLUG_ACTIVE | PLUG_LOCK]);
    }
    $page->assign('statusvals', $statusvals);
    $page->assign('rwstatusvals', $rwstatusvals);

    // translations
    $page->assign('msg_submit', __("Submit"));
    $page->assign('msg_plugedit_plugin', __("plugin"));
    $page->assign('msg_plugedit_plugins', __("plugins"));
    $page->assign('msg_plugedit_description', __("description"));
    $page->assign('msg_plugedit_parameters', __("parameters"));

    // if requested, assign the content to be displayed
    if (!empty($outputvar)) {
      $page->assign($outputvar, $page->fetch('plugin-editor.tpl'));
    }
  }

  /** Set the editor mode.
   */
  function set_mode($mode)
  {
    $this->mode = $mode;
  }
}

?>
