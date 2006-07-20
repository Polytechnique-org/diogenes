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


/** 
 * This class is used to handle the preferences for a barrel.
 *
 * Upon construction these preferences are read from database. It is also 
 * possible to update the value of the preferences and write the new value
 * to database.
 *
 * @see Diogenes_Barrel
 */
class Diogenes_Barrel_Options
{
  /** The barrel we are operating on */
  var $barrel;
  
  /** The table holding the options */
  var $table_option = 'diogenes_option';

  /** The site's title */
  var $title = "";
  
  /** The site's description */
  var $description = "";

  /** The relative url to the site's favicon (PNG) */
  var $favicon = "";

  /** The site's keywords */
  var $keywords = "";

  /** Hide the Diogenes-generated menu entries */
  var $menu_hide_diogenes = 0;
  
  /** Minimum menu level (0 means fully expanded) */
  var $menu_min_level = 0;

  /** Menu style (0=classical, 1=phpLayersMenu) */
  var $menu_style = 0;

  /** Menu theme */
  var $menu_theme = "gorilla";

  /** Site-wide default template */
  var $template = "";

  /** Directory that hold the site's template */
  var $template_dir = "";

  /** Enable RSS feed */
  var $feed_enable = 1;

  /** The constructor, reads the current preferences from database.
   */
  function Diogenes_Barrel_Options($alias)
  {
    $this->barrel = $alias;
    $this->readOptions(); 
  }


  /** Read options from database.
   */
  function readOptions() {
    global $globals;
    
    // we only accept options which already exist in this class
    $res = $globals->db->query("select name,value from {$this->table_option} where barrel='{$this->barrel}'");
    while (list($key,$value) = mysql_fetch_row($res)) {
      if (isset($this->$key) && ($key != "table_option"))
        $this->$key = $value;
    }
    mysql_free_result($res);
        
  }
   
 
  /** Update an option's value and write the new value to database.
   */
  function updateOption($name, $value) {
    global $globals;
    
    $this->$name = stripslashes($value);
    $globals->db->query("replace into {$this->table_option} set barrel='{$this->barrel}',name='$name',value='$value'");
  }

}

?>
