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


/** This class describes Diogenes' icons.
 */
class Diogenes_Icons
{
  /** Construct an object to manipulate Diogenes's icons.
   */
  function Diogenes_Icons($theme = 'gartoon')
  {
    $this->theme = $theme;
  }
  
  
  /** Return the available icon themes.
   */
  function get_icon_themes()
  {
    $themes = array(
      'gartoon' => array(
        'action' => array( 
          'add'         => 'action-add.png',
          'delete'      => 'action-delete.png',
          'edit'        => 'action-edit.png',
          'properties'  => 'action-properties.png',
          'plugins'     => 'action-plugins.png',
          'revisions'   => 'action-revisions.png',
          'rename'      => 'action-rename.png',
          'remove'      => 'action-remove.png',        
          'update'      => 'action-update.png',        
          'view'        => 'action-view.png',
        ),
        'barrel' => array(
          'home'        => 'barrel-home.png',
          'directory'   => 'barrel-directory.png'
        ),  
        'mime' => array( 
          'audio'      => 'mime-audio.png',      
          'deb'        => 'mime-deb.png',
          'drm'        => 'mime-drm.png',
          'excel'      => 'mime-excel.png',
          'html'       => 'mime-html.png',
          'lyx'        => 'mime-lyx.png',
          'msword'     => 'mime-msword.png',
          'postscript' => 'mime-postscript.png',
          'powerpoint' => 'mime-powerpoint.png',
          'pdf'        => 'mime-pdf.png',
          'image'      => 'mime-image.png',
          'text'       => 'mime-text.png',
          'video'      => 'mime-video.png',
          'compressed' => 'mime-compressed.png',
          'unknown'    => 'mime-unknown.png',
          'xml'        => 'mime-xml.png',
        ),
        'perm' => array(
          'public'     => 'perm-public.png',
          'auth'       => 'perm-auth.png',
          'user'       => 'perm-user.png',
          'admin'      => 'perm-admin.png',
          'forbidden'  => 'perm-forbidden.png',
        )  
      )
    );
    
    return $themes;  
  }

  
  /** Return the icon associated with an action.
   *
   * @param $category the icon category
   * @param $name the icon name
   */
  function get_icon($category, $name)
  {
    global $globals;
    $maps = $this->get_icon_themes();  
      
    if (isset($maps[$this->theme][$category][$name])) {
      return $globals->rooturl . "images/".$maps[$this->theme][$category][$name];
    } else {
      trigger_error("could not find icon for '$name' in category '$category'", E_USER_WARNING);
      return false;
    }  
  }
    
  
  /** Return the icon associated with an action.
   *
   * @param $action the action we are describing
   */
  function get_action_icon($action)
  {
    return $this->get_icon('action', $action);
  }

  
  /** Return the icons associated with an array of actions
   *
   * @param $actions the actions we are describing
   */  
  function get_action_icons($actions)  
  {
    $oactions = array();
    foreach ($actions as $action)
    {
       $oaction = $action;
       if (isset($action[2]))
       {
         $oaction[2] = $this->get_action_icon($action[2]);
       }
       array_push($oactions, $oaction);
    }
    return $oactions;
  }

  
  /** Return the icon associated with a file's MIME content-type
   *
   * @param $filename the file we are describing
   */  
  function get_mime_icon($filename)
  {
    global $globals;
  
    $type = get_mime_type($filename);
    
    if (preg_match('/^application\/msword$/', $type)) {
      $itype = 'msword';
    } elseif (preg_match('/^application\/vnd\.ms-powerpoint$/', $type)) {
      $itype = 'powerpoint';               
    } elseif (preg_match('/^application\/vnd\.oma\.drm\.(content|message)$/', $type)) {
      $itype = 'drm';
    } elseif (preg_match('/^application\/(arj|x-bzip|x-bzip2|x-gzip|x-compressed|zip)$/', $type)) {
      $itype = 'compressed';      
    } elseif (preg_match('/^application\/pdf$/', $type)) {
      $itype = 'pdf';    
    } elseif (preg_match('/^application\/postscript$/', $type)) {    
      $itype = 'postscript';
    } elseif (preg_match('/^application\/x-deb$/', $type)) {    
      $itype = 'deb';      
    } elseif (preg_match('/^audio\//', $type)) {
      $itype = 'audio';
    } elseif (preg_match('/^application\/vnd\.ms-excel$/', $type)) {
      $itype = 'excel';               
    } elseif (preg_match('/^image\//', $type)) {
      $itype = 'image';
    } elseif (preg_match('/^text\/html$/', $type)) {
      $itype = 'html'; 
    } elseif (preg_match('/^text\/xml$/', $type)) {
      $itype = 'xml'; 
    } elseif (preg_match('/^text\/x-lyx$/', $type)) {    
      $itype = 'lyx';                  
    } elseif (preg_match('/^text\//', $type)) {
      $itype = 'text';
    } elseif (preg_match('/^video\//', $type)) {
      $itype = 'video';    
    } else {
      $itype = 'unknown';
    }
    
    return $this->get_icon('mime', $itype);
  }
}

?>
