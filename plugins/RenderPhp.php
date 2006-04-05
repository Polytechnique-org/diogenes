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

require_once 'Plugin/Render.php';

/** The RenderPhp plugin allows you to execute a file as PHP
 *  rather than return its content.
 **/
class RenderPhp extends Diogenes_Plugin_Render
{
  /** Plugin name */
  var $name = "RenderPhp";
  
  /** Plugin description */
  var $description = "This plugin allows you to execute a page as PHP code. Simply write your page as a PHP page and its output will be displayed within the template framework.";  
  
  /** Execute a given file and return the output.
   */  
  function render($file)
  {
    ob_start();
    include($file);
    $content = ob_get_contents();
    ob_end_clean(); 
         
    return $content;
 }
}

?>
