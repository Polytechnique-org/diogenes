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
require_once 'diogenes.icons.inc.php';


/** This plugin allows you to insert a directory listing with icons 
 *  and modification date.
 *
 * To make use of this plugin, insert {FileList} in your page where 
 * the file list should appear.
 */
class FileList extends Diogenes_Plugin_Filter {
  /** Plugin name */
  var $name = "FileList";
  
  /** Plugin description */
  var $description = "This plugin allows you to insert a directory listing with icons and modification date. To make use of this plugin, insert <b>{FileList}</b> in your page where the file list should appear.";
  
  /** Plugin parameters */
  var $params = array('dirbase' => "", 'urlbase' => "", 'match' => "");
    
  
  /** Prepare the output for a single file of the list.
   *
   * @param $file
   * @param $title
   * @param $url
   */
  function list_file($file,$title,$url="") {
    if (empty($url)) $url = $file;
    global $globals;
    
    /* get modification date / time */
    $modified = date ("d M Y H:m:s", filemtime($file));
  
    /* get file icon */
    $icon = $globals->icons->get_mime_icon($file);
    
    /* calculate file size */
    $size = filesize($file);
    if ($size < 1000) 
      $size = "$size B";
    elseif ($size < 1000000)
      $size = floor($size/1000)." kB";
    else
      $size = floor($size/1000000)." MB";
    
    /* figure out description */
    $show = 1;
    if (preg_match("/(i386\.changes|\.dsc)$/",$file))
      $show = 0;
    elseif (preg_match("/\.tar\.gz$/",$file)) 
      $desc = "source tarball";
    elseif (preg_match("/_(all|i386)\.deb$/",$file,$matches))
    {
      $type = $matches[1];
      $desc = "Debian package";
      if ($type == "all")
        $desc .= " [platform independant]";
      else 
        $desc .= " [$type specific]";
    }
    elseif (preg_match("/\.diff\.gz$/",$file))
      $desc = "Debian diff";
    else $desc = "&nbsp;";
    
    /* display the link */
    if ($show)
      return "<tr><td><img class=\"fileicon\" src=\"$icon\" /><a href=\"$url\">$title</a></td><td><small>$modified</small></td><td>$size</td><td>$desc</td></tr>\n";
  }
  
  
  /** Show an instance of the FileList plugin.
   *
   * @param $args
   */
  function show($args)
  {
    global $page;
    $bbarel = $page->barrel;
    
    // process arguments
    $params = array();
    foreach($this->params as $key => $val) {
      $params[$key] = isset($args[$key]) ? $args[$key] : $this->params[$key];
    }
    
   //print_r($params);
    if (empty($params['dirbase'])) {
      $params['dirbase'] = $bbarel->spool->spoolPath($page->curpage->props['PID']);
    }

    // process parameters 
    $output = '';
    $dir = $params['dirbase'];    
    if (is_dir($dir) && ($dh = opendir($dir))) {
      $output .= 
       '<table class="light">
        <tr>
          <th>'.__("file").'</th>
          <th>'.__("date").'</th>
          <th>'.__("size").'</th>
        <th>'.__("description").'</th>
        </tr>';
        
      /* get the matching files */
      while (($fname = readdir($dh)) !== false) {
        if ( is_file("$dir/$fname") 
              && preg_match('/^'.$params['match'].'/',$fname) )
          $filelist[] = $fname;
      }
      closedir($dh);
  
      /* order and display */
      if (is_array($filelist)) {
        rsort($filelist);
        while(list ($key,$val) = each($filelist)) {
          $output .= $this->list_file("$dir/$val",$val,$params['urlbase'].$val);
        }
      } else {
        $output .= '<tr><td colspan="4"><i>'.__("no files").'</i></td></tr>';
      }
      $output .= '</table>';
    }
    return $output;    
  }
}

?>
