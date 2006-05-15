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

require_once 'Plugin/Filter.php';
require_once 'diogenes/diogenes.hermes.inc.php';

/** The MailForm plugin allows you to insert a form to send
 *  an e-mail to a fixed recipient.
*/  
class LinksMagic extends Diogenes_Plugin_Filter
{  
  /** Plugin name */
  var $name = "LinksMagic";
  
  /** Plugin description */
  var $description = "This plugin allows you to mark external and secure (HTTPS) links in your pages.";
 

  /** Constructor.
   */
  function LinksMagic()
  {
    $this->declareParam('main', 1);
    $this->declareParam('sidebar', 1);
  }

  
  /** Apply filtering to the input and return an output.
   *
   * @param $input
   */
  function filter($input)
  {
    global $page;
 
    if ($this->getParamValue('sidebar'))
    {
      array_unshift($page->head, '<link rel="stylesheet" href="'.$page->url('links-sidebar.css').'" type="text/css" />');
    }

    if ($this->getParamValue('main'))
    {
      array_unshift($page->head, '<link rel="stylesheet" href="'.$page->url('links-main.css').'" type="text/css" />');
    }

    return $input;
  }
  
}  
?>
