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


/** This class is used to generate the menu of a Diogenes barrel page.
 */
class Diogenes_Barrel_Menu
{
  /** Database handle */
  var $dbh;
  
  /** The database table holding the menus */
  var $table_menu;

  /** Constructs a Smarty-derived object to display contents within a barrel.
   *
   * @param $override_pathinfo 
   */
  function Diogenes_Barrel_Menu(&$dbh, $table_menu)
  {
    $this->dbh =& $dbh;
    $this->table_menu = $table_menu;
  }

  /** Delete the specified entry.
   *
   * @param $MID
   * @param $parent
   * @param $caller
   */
  function deleteEntry($MID, $parent, &$caller)
  {
    if (mysql_num_rows($this->dbh->query("SELECT MID FROM {$this->table_menu} WHERE MIDpere=$MID")) > 0) {
      $caller->info(__("The selected menu has child items, please remove them first."));
      return;
    }

    /* erase the current entry */
    $this->dbh->query("DELETE FROM {$this->table_menu} WHERE MID=$MID");

    /* renumber the other menu entries so that they are between 1 and the number of entries */
    $res = $this->dbh->query("SELECT MID FROM {$this->table_menu} WHERE MIDpere=$parent ORDER BY ordre");
    $i = 0;
    while (list($MIDtoorder) = mysql_fetch_row($res)) {
      $i++;
      $this->dbh->query("UPDATE {$this->table_menu} SET ordre=$i WHERE MID=$MIDtoorder");
    }
    mysql_free_result($res);
  }


  /** Build the user-defined part of the menu.
   *
   * @param $PID
   * @param $pid_to_url
   */
  function makeMenu($PID, $min_level, $pid_to_url)
  {
    // all menu entries from database
    $mcache = $this->menuRead();

    // try to figure out the current MID from the current PID
    // and build filiation
    $filiation = array();
    foreach ($mcache as $mid => $mentry)
    {
      if ($mentry['pid'] == $PID)
        $filiation = $this->menuToRoot($mcache, $mid, $filiation);
    }

    $menu = $this->menuRecurse($mcache, 0, $filiation, 0, $min_level, $pid_to_url);

    return $menu;
  }


  /** Return the maximum index for children of a given entry.
   *
   * @param $parent
   */
   function maxChildIndex($parent)
   {
      $res=$this->dbh->query("SELECT MAX(ordre) from {$this->table_menu} where MIDpere=$parent");
      list($maxOrdre)=mysql_fetch_row($res);
      mysql_free_result($res);
      return $maxOrdre;
   }


  /** Return the filiation to get to the root element.
   *
   * @param mcache
   * @param MID
   * @param path
   */
  function menuToRoot($mcache, $MID, $path) {
    /* add ourself to the path */
    array_push($path,$MID);

    if ($MID) {
      /* recursion */
      return $this->menuToRoot($mcache, $mcache[$MID]['parent'], $path);
    } else {
      /* termination */
      return $path;
    }
  }


  /** Recursively add menu entries
   *
   * @param mcache
   * @param MIDpere
   * @param filiation
   * @param level
   * @param min_level
   * @param pid_to_url
   */
  function menuRecurse($mcache, $MIDpere, $filiation, $level, $min_level, $pid_to_url) {
    // the produced output
    $out = array();

    foreach ($mcache[$MIDpere]['children'] as $mid)
    {
      $mentry = $mcache[$mid];
//      echo "pid : $pid, location : $location<br/>";
      $entry = htmlentities(stripslashes($mentry['title']), ENT_QUOTES, "ISO-8859-1");
      if ($mentry['pid'])
      {
        $link = call_user_func($pid_to_url, $mentry['pid']);
      } else {
        $link = $mentry['link'];
      }
      // decide whether this menu should be expanded
      $expanded = ($min_level == 0) || 
                  ($level+1 < $min_level) || 
                   in_array($mid, $filiation);
      array_push($out, array($level, $entry, $link, $expanded));
      $out = array_merge($out, $this->menuRecurse($mcache, $mid, $filiation, $level+1, $min_level, $pid_to_url));
    }

    return $out;
  }


  /** Read this barrel's menu entries from database.
   */
  function menuRead()
  {
    $menu = array();
    $menu[0]['children'] = array();
    $res = $this->dbh->query("select MID,MIDpere,title,link,PID,ordre from {$this->table_menu} order by ordre");
    while (list($mid, $parent, $title, $link, $pid, $ordre) = mysql_fetch_row($res))
    {
      $menu[$mid]['parent'] = $parent;
      $menu[$mid]['title'] = $title;
      $menu[$mid]['link'] = $link;
      $menu[$mid]['title'] = $title;
      $menu[$mid]['pid'] = $pid;
      $menu[$mid]['ordre'] = $ordre;
      if (!is_array($menu[$mid]['children']))
        $menu[$mid]['children'] = array();

      // register this entry with its parent
      if (!is_array($menu[$parent]['children']))
        $menu[$parent]['children'] = array();
      array_push($menu[$parent]['children'], $mid);
    }
    mysql_free_result($res);
    return $menu;
  }


  /**
   * Swap entries $a and $b within $parent.
   *
   * @param $parent
   * @param $a
   * @param $b
   */
  function swapEntries($parent, $a, $b)
  {
    $res = $this->dbh->query("SELECT MID from {$this->table_menu} where MIDpere=$parent and (ordre=$a or ordre=$b) ORDER BY ordre");
    /* make sure that $a <= $b */
    if ($a > $b)
    {
      $c = $a;
      $a = $b;
      $b = $c;
    }
    /* perform swap */
    list($MIDa) = mysql_fetch_row($res);
    list($MIDb) = mysql_fetch_row($res);
    mysql_free_result($res);

    $this->dbh->query("UPDATE {$this->table_menu} SET ordre=$b WHERE MID=$MIDa");
    $this->dbh->query("UPDATE {$this->table_menu} SET ordre=$a WHERE MID=$MIDb");
  }


  /**
   * Write an entry to database. If $mid is 0, a new entry is created.
   *
   * @param $mid
   * @param $props
   */
  function writeEntry($mid, $props)
  {
    if ($mid == 0) {
      $props['ordre'] = $this->maxChildIndex($props['parent']) + 1;
    }

    // build SQL string
    $nprops = array('parent' => 'MIDpere', 'ordre' => 'ordre', 'title' => 'title', 'link' => 'link', 'pid' => 'pid');
    $sprops = "";
    foreach($nprops as $prop => $dbkey)
    {
      if (isset($props[$prop]))
      {
        $val = $props[$prop];
        $sprops .= "$dbkey='$val', ";
      }
    }
    if (!$sprops)
      return;
    $sprops = substr($sprops, 0, -2);
    if ($mid == 0) {
      $this->dbh->query("INSERT INTO {$this->table_menu} SET $sprops");
      $mid = mysql_insert_id();
    } else {
      $this->dbh->query("UPDATE {$this->table_menu} SET $sprops WHERE MID=$mid");
    }
    return $mid;
  }

}

?>
