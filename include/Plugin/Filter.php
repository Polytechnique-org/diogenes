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
require_once 'Plugin/Skel.php';

/** This class describes a Diogenes filter plugin. 
 */
class Diogenes_Plugin_Filter extends Diogenes_Plugin_Skel
{
  /** Plugin name */
  var $name = "Plugin_Filter";
  
  /** Plugin description */
  var $description = "Filter plugin skeleton";
  
  /** Plugin version */
  var $version = "0.1";

  /** Plugin type : filter */
  var $type = "filter";  
  
  
  /** Apply filtering to the input and return an output.
   *
   *  The default implementation searches for a tag whose name matches
   *  the plugin name and replaces it by the output of the show() method.
   *
   * @param $input
   */
  function filter($input)
  {
    $name = $this->name;
    
    $mask = "/(\{$name(\s+[^\}]*)?\})/";
    $bits = preg_split($mask, $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $output = "";    
    while($bit = array_shift($bits)) {
      if (preg_match($mask, $bit)) {
        $argstr = array_shift($bits);
        $argbits = preg_split("/\s/", trim($argstr));
        $args = array();
        foreach($argbits as $argbit)
        {
          if (preg_match('/([a-zA-Z]+)=([\'"])(.*)\2$/', $argbit, $matches)) {
            $args[$matches[1]] = $matches[3];
          }
        }
        $output .= $this->show($args);        
      } else {
        $output .= $bit;
      }
    }
    return $output;
  }
  
  
  /** Show an instance of the plugin. This is called by filter() every time
   *  a tag representing the plugin is found.
   */
  function show()
  {
    return '';
  }
  
  
}
  
?>
