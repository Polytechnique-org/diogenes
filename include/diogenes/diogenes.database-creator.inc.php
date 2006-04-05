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


require_once dirname(__FILE__).'/diogenes.database.inc.php';
 
 
/** This class is used to create, update and remove databases
 */
class DiogenesDatabaseCreator {
  /** Do we show information messages? */
  var $opt_info = true;
  
  /** Do we show debugging info? */
  var $opt_debug = false;

  /** table containing options */
  var $opt_table;
  
  /** database versions history */
  var $versions = array();
  
  /**
   * Initialisation
   *
   * @param $opt_table
   */
  function DiogenesDatabaseCreator($opt_table)
  {
    $this->opt_table = $opt_table;
  }

    
  /**
   * Connect to the database
   */
  function connect()
  {
    // debugging info
    $this->debug("host : ".$this->dbhost);
    $this->debug("user : ".$this->dbuser);
    $this->debug("pass : ".(($this->dbpass != "") ? "true" : "false"));
    $this->debug("database : ".$this->dbdb);

    $this->dbh = new DiogenesDatabase($this->dbdb, $this->dbhost, $this->dbuser, $this->dbpass);
    
    if (!$this->dbh->connect_id) {
      $this->error("Unable to connect to the database!");
    }
    
    return $this->dbh->connect_id;
  }

  
  /**
   * Displays a debugging message.
   *
   * @param $msg   
   */
  function debug($msg)
  {
    if ($this->opt_debug)
      echo "D: $msg\n";
  }
   
   
  /**
   * Displays an info message.
   *
   * @param $msg
   */
  function info($msg)
  {
    if ($this->opt_info)
      echo "I: $msg\n";
  }


  /**
   * Displays an error message.
   *
   * @param $msg
   */
  function error($msg)
  {
    echo "E: $msg\n";
  }


  /**
   * Upgrade the database from one version to the next
   *
   * @param $newversion
   */
  function upgradeDb($newversion)
  {
    $this->info("updrade to $newversion");
  }


  /**
   * Retrieve the current database version
   */
  function getVersion()
  {
    $res = $this->dbh->query("SELECT value FROM {$this->opt_table} WHERE name='dbversion'");
    if (list($dbversion) = mysql_fetch_row($res)) {
      mysql_free_result($res);
    } else {
      $dbversion = $this->versions[0];
    }
    return $dbversion;
  }


  /**
   * Set the current database version
   *
   * @param $newversion   
   */
  function setVersion($newversion)
  {
    $this->dbh->query("REPLACE INTO {$this->opt_table} SET name='dbversion',value='$newversion'");
  }

  
  /**
   *  Parse command line options
   * 
   * @param $argv
   * @param $dbdb
   * @param $dbhost
   * @param $dbuser
   * @param $dbpass
   */
  function parseOptions($argv, $dbdb, $dbhost, $dbuser, $dbpass)
  {
    // set default options
    $this->dbdb = $dbdb;
    $this->dbhost = $dbhost;
    $this->dbuser = $dbuser;
    $this->dbpass = $dbpass;
    
    // parse options
    $script = basename($argv[0]);
    $opts = Console_GetOpt::getopt($argv, "d:hp:qs:u:v");

    if ( PEAR::isError($opts) ) {
      echo $opts->getMessage();
      $this->syntax($script);
      exit(1);
    } else {
      $opts = $opts[0];
      foreach ( $opts as $opt) {
        switch ($opt[0]) {
        case "h":
          $this->syntax($script);
          exit(0);
        case "q":
          $this->opt_info = false;
          $this->opt_debug = false;
           break;
        case "d":
          $this->dbdb = $opt[1];
          break;
        case "u":
          $this->dbuser = $opt[1];
          break;
        case "v":
          $this->opt_info = true;
          $this->opt_debug = true;
          break;
        case "s":
          $this->dbhost = $opt[1];
          break;
        case "p":
          $this->dbpass = $opt[1];
          break;
        }
      }      
    }
    
  }


  /**
   * Displays program usage.
   */
  function syntax($script)
  {    
    echo 
    "\nSyntax\n".
    "  $script [options]\n\n".
    "Options\n".
    " -h display this help message\n".
    " -q quiet mode\n".
    " -v verbose mode\n\n".
    " -d database\n".
    " -s host\n".
    " -u user\n".    
    " -p password\n\n";
  }


  /**
   * Main routine
   */
  function run()
  {
    if (!$this->connect()) {
      exit(1);
    }
    
    $versions = $this->versions;
    $dbversion = $this->getVersion();
    $this->info("Current database version is $dbversion");
    
    // check we know the current database version
    if (!in_array($dbversion, $versions)) {
      $this->error("Unknown database format version '$dbversion'");
      exit(1);
    }

    // runs the successive updates
    $from = array_search($dbversion, $versions);
    $to = sizeof($versions)-1;
    
    for($pos = $from; $pos < $to; $pos++) {
      $oldversion = $versions[$pos];
      $newversion = $versions[$pos+1];
      $this->info("Upgrading from DB format '$oldversion' to '$newversion'");
      
      $this->upgradeDb($newversion);
      $this->setVersion($newversion);
    }
    
  }

}

?>
