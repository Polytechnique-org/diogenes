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

require_once 'Plugin/Skel/Filter.php';
require_once 'classTextile.php';
require_once 'diogenes.text.inc.php';

/** The TextileMarkup plugin allows you to render Textile markup into XHTML.
 */
class TextileMarkup extends Diogenes_Plugin_Skel_Filter
{
  /** Plugin name */
  var $name = "TextileMarkup";
  
  /** Plugin description */
  var $description = "This plugin allows you to render Textile markup into XHTML. To get started, take a look at some <a href=\"http://www.textism.com/tools/textile/\">sample Textile markup pages</a>. You can protect HTML code from this plugin by enclosing it between &lt;protect&gt; and &lt;/protect&gt; tags.";
  
  
  /** Is the plugin allowed with respect to a given write permission on a page ?
   *
   * @param $wperms
   */
  function allow_wperms($wperms) 
  {
    return 1;
  }
  
  
  /** Take the Wiki markup and return XHTML.
   *
   * @param $input
   */  
  function filter($input)
  {
    $textile = new Textile();
    $data = htmlProtectFromTextism($input);
    $data = $textile->TextileThis($data);
    return htmlUnprotectFromTextism($data);
  }
  
}

?>
