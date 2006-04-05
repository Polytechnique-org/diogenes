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


require_once 'Barrel.php';
require_once 'Barrel/Options.php';


/** This class provides a viewer/editor for Diogenes's barrels
 */
class Diogenes_Barrel_Editor
{
  /** Is this barrel editor read only (i.e. a list) ? */
  var $readonly = 0;
  

  /** Adds a barrel to the list of barrels we display.
   * 
   * @param $page
   * @param $barrel
   */    
  function addBarrel(&$page, $barrel)
  {
    global $page;
    
    $actions = array();
    if (!$this->readonly) {
       array_push($actions, array(__("edit"), "?action=edit&amp;target={$barrel['alias']}"));
     }       
     array_push($actions, array(__("admin"), $page->urlBarrel($barrel['alias'],$barrel['vhost'],"admin/")));
     
    if (!$this->readonly) {     
      $flags = new flagset($barrel['flags']);
      if ($flags->hasFlag('plug')) {
        array_push($actions, array(__("plugins"), "plugins?plug_barrel={$barrel['alias']}"));                   
      }
      array_push($actions, array(__("delete"), "javascript:del('{$barrel['alias']}');"));
    }    
    
    
    $page->append('sites',array(
      'alias' => $barrel['alias'],
      'title' => array( $barrel['title'], $page->urlBarrel($barrel['alias'],$barrel['vhost']) ),
      'description' => $barrel['description'],
      'actions' => $actions )
    );
  }


  /** Run the Barrel editor.
   *
   * @param $page
   * @param $outputvar
   */
  function run(&$page, $outputvar = '')
  {
    global $globals;
    
    $page->assign('post',$page->script_self());
    
    $action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : "list";
    switch($action) {
      case "create":
        if ($this->readonly) die("Sorry, this barrel editor is read-only.");
        if (isset($_REQUEST["target"])) {
          Diogenes_Barrel::create($_REQUEST["target"], $page);
        }
        $action="list";
        break;
      case "update":
        if ($this->readonly) die("Sorry, this barrel editor is read-only.");
        if (isset($_REQUEST["target"])) {
          // build flags
          $flags = isset($_REQUEST['flags']) ? implode(",",array_values($_REQUEST['flags'])) : "";
          $globals->db->query("update diogenes_site set vhost='{$_REQUEST['vhost']}',flags='$flags' where alias='{$_REQUEST['target']}'");
          $opts = new Diogenes_Barrel_Options($_REQUEST['target']);
          $opts->updateOption('description', $_REQUEST['description']);
          $opts->updateOption('title', $_REQUEST['title']);
          $opts->updateOption('keywords', $_REQUEST['keywords']);
        }
        $action="list";
        break;
      case "edit":
        if ($this->readonly) die("Sorry, this barrel editor is read-only.");
        if (isset($_REQUEST["target"])) {
          $res = $globals->db->query("select alias,vhost,flags from diogenes_site where alias='{$_REQUEST['target']}'");
          if (!list($target,$vhost,$flags) = mysql_fetch_row($res)) {
            $page->info("Could not find specified site!");
            $action = "list";
            break;
          }
          mysql_free_result($res);
          $page->assign('target',$target);
          $page->assign('v_vhost',$vhost);
          $flags = new flagset($flags);
          $flag_opts = array('tpl' => __("custom templates"), 'plug' => __("plugins"));
          $page->assign('v_flag_opts', $flag_opts);
          foreach($flag_opts as $key=>$val) {
            if ($flags->hasFlag($key))
              $page->append('v_flags', $key);
          }
          $opts = new Diogenes_Barrel_Options($target);
          $page->assign('v_desc',$opts->description);
          $page->assign('v_title',$opts->title);
          $page->assign('v_keywords',$opts->keywords);
    
        }
        break;
      case "delete":
        if ($this->readonly) die("Sorry, this barrel editor is read-only.");
        if (isset($_REQUEST["target"])) {
          Diogenes_Barrel::destroy($_REQUEST["target"], $page);
        }
        $action="list";
        break;
      default:
        $action="list";
    }
    
    if ($action == "list") {
      $res = $globals->db->query("select s.alias,s.vhost,s.flags,o.name,o.value from diogenes_site as s left join diogenes_option as o on s.alias=o.barrel order by s.alias");
      $barrel = array('alias' => '');
    
      while (list($s_alias,$s_vhost,$s_flags,$o_name,$o_value) = mysql_fetch_row($res)) {
        if ($s_alias != $barrel['alias']) {
          if ($barrel['alias']) $this->addBarrel($page, $barrel);  
          $barrel = array('alias' => $s_alias,'vhost' => $s_vhost, 'flags' => $s_flags, 'title' => '','description' => '');    
        }
        $barrel[$o_name] = $o_value;  
      }
      mysql_free_result($res);
      if ($barrel['alias']) $this->addBarrel($page, $barrel);  
    } // $action == "list"
    
    // values
    $page->assign('action',$action);
    $page->assign('readonly', $this->readonly);
    
    
    // translations 
    $page->assign('msg_alias',__("alias"));
    $page->assign('msg_title',__("site"));
    $page->assign('msg_desc',__("description"));
    $page->assign('msg_keywords',__("keywords"));
    $page->assign('msg_create',__("Create a new site"));
    $page->assign('msg_create_note',__("The site alias may only contain letters, numbers and underscores."));
    $page->assign('msg_submit',__("Submit"));
    $page->assign('msg_no_barrels', __("no barrels available"));
    $page->assign('msg_vhost',__("vhost (see note)"));
    $page->assign('msg_vhost_note', __("<b>vhost note</b>: If you intend to serve this site from a virtualhost, you should enter the name of the virtualhost in this field."));
    $page->assign('msg_flags',__("flags"));
    
        // if requested, assign the content to be displayed
    if (!empty($outputvar)) {
      $page->assign($outputvar, $page->fetch('barrel-editor.tpl'));
    }
  }
}
?>
