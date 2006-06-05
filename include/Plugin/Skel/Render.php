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

/** This class describes a Diogenes rendering plugin. 
 */
class Diogenes_Plugin_Skel_Render extends Diogenes_Plugin_Skel
{
  var $name = "Plugin_Render";
  var $description = "Rendering plugin skeleton";
  var $version = "0.1";

  /* types : render, filter */
  var $type = "render";  

  
  /** Render a given file and return the output.
   *
   *  The default implementation simply dumps the contents of the file.
   */
  function render($file)
  {
    if (file_exists($file)) {
      return file_get_contents($file);
    }
  }
  
}
  
?>
