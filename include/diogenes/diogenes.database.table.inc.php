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


/** Class for handling operations on MySQL database tables. Upon construction, the class
 *  probes the table to read its structure.
 *
 * @see DiogenesDatabase, DiogenesTableEditor
 */
class DiogenesDatabaseTable {
  /** Handle to the database */
  var $dbh;
  /** Name of the table */
  var $table;
  /** The table's columns */
  var $vars = array();

  /** The constructor. It reads the table structure from database.
   *
   * @param dbh the handle to the database
   * @param table the name of the table
   */
  function DiogenesDatabaseTable(&$dbh,$table)
  {
    $this->dbh =& $dbh;
    $this->table = $table;

    $res = $this->dbh->query("show columns from $table");
    while (list($name,$ftype,,$key,$value,$extra) = mysql_fetch_row($res))
    {
      if (!preg_match("/^([a-z]+)(\(([^\)]*)\))?( [a-z]+)?$/",$ftype,$matches))
        die("could not parse $ftype");

      $dtype = $matches[1];
      switch($dtype) {
      case "set": case "enum":
        $type = "set";
        break;
      case "timestamp":
      case "datetime":
	$type = "timestamp";
	break;
      default:
        $type="text";
      }
      $this->vars[$name] = array("table" => $this->table, "field" => $name,
        "type" => $type, "value" => $value, "desc" => $name, "sum" => false,
        "key" => $key, "extra" => $extra, "edit" => true, "show" => true);
    }
  }

}

?>
