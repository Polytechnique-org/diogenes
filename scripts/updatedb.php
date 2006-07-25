#!/usr/bin/php -q
<?php
/* Script for updating the database structure.
*/

// Initialisation
require_once("Console/Getopt.php");
require_once("diogenes/diogenes.database-creator.inc.php");

/** The DiogenesDbInit class handles database upgrades between 
 *  Diogenes versions.
 */
class DiogenesDbInit extends DiogenesDatabaseCreator
{
  /** database versions history */
  var $versions = array("0.9.9.3", "0.9.10", "0.9.12", "0.9.15", "0.9.16", "0.9.16+0.9.17pre15", "0.9.16+0.9.17pre19", "0.9.16+0.9.17pre21");

  /**
   * Upgrades the database from one version to the next
   * 
   * @param $newversion
   */
  function upgradeDb($newversion)
  {
    // pre-upgrade master tables
    $this->info("* Pre-upgrading master tables : diogenes_*");
    $this->preupgradeMaster($newversion);
  
    // upgrade barrels
    $res = $this->dbh->query("select alias from diogenes_site");
    while (list($alias) = mysql_fetch_row($res))
    {   
      $this->info("* Upgrading barrel '$alias'");    
      $this->upgradeBarrel($alias, $newversion);
    }
    mysql_free_result($res);

    // upgrade master tables
    $this->info("* Upgrading master tables : diogenes_*");
    $this->upgradeMaster($newversion);
  }

  
  /** Upgrades the master (i.e. common) tables before touching the barrel tables.
   *
   * @param $newversion
   */  
  function preupgradeMaster($newversion)
  {
    switch($newversion) {
    case "0.9.16+0.9.17pre15";
      $this->info(" - adding 'barrel' field to 'diogenes_options' table");
      $this->dbh->query("ALTER TABLE `diogenes_option` ADD `barrel` VARCHAR( 16 ) NOT NULL FIRST;");
      $this->dbh->query(
        "ALTER TABLE `diogenes_option` DROP PRIMARY KEY ,
         ADD PRIMARY KEY ( `barrel` , `name` )");
      break;
      
    default:
      break;
    }
  }

  
  /** Upgrade a barrel's tables
   *
   * @param $alias
   * @param $newversion
   */
  function upgradeBarrel($alias, $newversion)
  {
#    $this->info("Processing : {$alias}_menu, {$alias}_page and {$alias}_option");

    switch($newversion) {
    case "0.9.10":
      $this->info(" - upgrading : {$alias}_menu");
      // these field where NULL, change to NOT NULL
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `link` `link` TEXT NOT NULL");
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `ordre` `ordre` SMALLINT( 6 ) UNSIGNED NOT NULL");
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `MIDpere` `MIDpere` SMALLINT( 6 ) UNSIGNED NOT NULL");
    
      // break down old 'link' column into 'link' and 'PID'
      $this->dbh->query("ALTER TABLE `{$alias}_menu` ADD `PID` SMALLINT( 6 ) UNSIGNED NOT NULL");
      $res2 = $this->dbh->query("select MID,link from {$alias}_menu");
      while (list($MID,$link) = mysql_fetch_row($res2)) {
        switch (substr($link,0,3)) {
        case "PI:":
          $pid = substr($link,3);
          $this->dbh->query("UPDATE `{$alias}_menu` SET link='',PID='$pid' WHERE MID='$MID'");
          break;
        case "SE:":
          $adr = substr($link,3);
          $this->dbh->query("UPDATE `{$alias}_menu` SET link='$adr' WHERE MID='$MID'");
          break;
        }
      }
      mysql_free_result($res2);
    
      $this->info(" - creating : {$alias}_option");
      $this->dbh->query("CREATE TABLE `{$alias}_option` (name VARCHAR( 32 ) NOT NULL, value TEXT NOT NULL, PRIMARY KEY (`name`)) TYPE=MyISAM;");    
    
      $this->info(" - registering title, description and keywords");
      $res2 = $this->dbh->query("select title,description,keywords from diogenes_site where alias='$alias'");
      list($title,$description,$keywords) = mysql_fetch_row($res2);
      $this->dbh->query("replace into `{$alias}_option` set name='title',value='$title'");
      $this->dbh->query("replace into `{$alias}_option` set name='description',value='$description'");
      $this->dbh->query("replace into `{$alias}_option` set name='keywords',value='$keywords'");
      break;
      
    case "0.9.12":
      $this->info(" - adding 'template' field");
      $this->dbh->query("ALTER TABLE `{$alias}_page` ADD `template` VARCHAR(255) NOT NULL");
      $this->info(" - dropping 'hits' field");
      $this->dbh->query("ALTER TABLE `{$alias}_page` DROP `hits`");
      $this->info(" - replacing 'visible' field by 'status'");
      $this->dbh->query("ALTER TABLE `{$alias}_page` DROP `visible`");
      $this->dbh->query("ALTER TABLE `{$alias}_page` ADD `status` tinyint(1) unsigned NOT NULL");
      $this->info(" - modifying 'perms' and 'wperms' to add 'forbidden' access");
      $this->dbh->query("ALTER TABLE `{$alias}_page` CHANGE `perms` `perms` ENUM( 'public', 'auth', 'user', 'admin', 'forbidden' ) DEFAULT 'public' NOT NULL");
      $this->dbh->query("ALTER TABLE `{$alias}_page` CHANGE `wperms` `wperms` ENUM( 'public', 'auth', 'user', 'admin', 'forbidden' ) DEFAULT 'admin' NOT NULL");
      break;
    
    case "0.9.16":
      $this->info(" - changing page id fields to INT UNSIGNED");
      $this->dbh->query("ALTER TABLE `{$alias}_page` CHANGE `PID` `PID` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `PID` `PID` INT( 10 ) UNSIGNED NOT NULL default '0'");
      $this->info(" - changing menu id fields to INT UNSIGNED");
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `MID` `MID` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `MIDpere` `MIDpere` INT( 10 ) UNSIGNED NOT NULL default '0'");
      $this->dbh->query("ALTER TABLE `{$alias}_menu` CHANGE `ordre` `ordre` INT( 10 ) UNSIGNED NOT NULL default '0'");
      break;

    case "0.9.16+0.9.17pre15":
      $this->info(" - merging '{$alias}_option' into 'diogenes_options'");
      $res = $this->dbh->query("select name,value from `{$alias}_option`");
      while (list($o_name,$o_value) = mysql_fetch_row($res)) 
      {
        $this->dbh->query("insert into `diogenes_option` set barrel='$alias',name='$o_name',value='".addslashes($o_value)."'");
      }
      mysql_free_result($res);
      $this->info(" - dropping '{$alias}_option'");
      $this->dbh->query("drop table `{$alias}_option`");

      $this->info(" - adding 'parent' field to `{$alias}_page`");
      $this->dbh->query("ALTER TABLE `{$alias}_page` ADD `parent` INT( 10 ) UNSIGNED NOT NULL default '0' AFTER `PID`");
      $res = $this->dbh->query("select PID from `{$alias}_page` where location=''");
      list($homepage) = mysql_fetch_row($res);
      $this->dbh->query("update `{$alias}_page` set parent='$homepage' where location!=''");
    
      $this->info(" - ordering `{$alias}_page` entries by `location`");
      $this->dbh->query("ALTER TABLE `{$alias}_page` CHANGE `location` `location` VARCHAR( 255 ) NOT NULL");
      $this->dbh->query("ALTER TABLE `{$alias}_page` ORDER BY `location`");
      break;
      
    default:
      $this->info(" - no changes needed.");
      break;
    }
  }

  
  /** Upgrades the master (i.e. common) tables after the barrel tables have been updated.
   *
   * @param $newversion
   */
  function upgradeMaster($newversion)
  {
    // upgrade master tables
    switch($newversion) {
    case "0.9.10":
      $this->info(" - dropping fields : title, description, keywords");
      $this->dbh->query("ALTER TABLE `diogenes_site` DROP `title`");
      $this->dbh->query("ALTER TABLE `diogenes_site` DROP `description`");
      $this->dbh->query("ALTER TABLE `diogenes_site` DROP `keywords`");
      break;

    case "0.9.15":
      $this->info(" - adding field : email");
      $this->dbh->query("ALTER TABLE `diogenes_auth` ADD `email` VARCHAR( 255 ) NOT NULL");
      break;
      
    case "0.9.16":
      $this->info(" - changing user id fields to INT UNSIGNED");
      $this->dbh->query("ALTER TABLE `diogenes_auth` CHANGE `user_id` `user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
      $this->dbh->query("ALTER TABLE `diogenes_logsessions` CHANGE `uid` `uid` INT(10) UNSIGNED NOT NULL default '0'");
      $this->dbh->query("ALTER TABLE `diogenes_logsessions` CHANGE `suid` `suid` INT(10) UNSIGNED NOT NULL default '0'");
      $this->dbh->query("ALTER TABLE `diogenes_perm` CHANGE `uid` `uid` INT(10) UNSIGNED NOT NULL default '0'");
      break;
      
    case "0.9.16+0.9.17pre15":
      $this->info(" - creating 'diogenes_plugin' table");
      $this->dbh->query(
        "CREATE TABLE `diogenes_plugin` (
           `plugin` varchar(32) NOT NULL default '',
           `barrel` varchar(16) NOT NULL default '',
           `page` int(10) unsigned NOT NULL default '0',
           `pos` int(10) unsigned NOT NULL default '0',
           `params` text NOT NULL,
            PRIMARY KEY  (`plugin`,`barrel`,`page`),
            KEY `pos` (`pos`)
          ) TYPE=MyISAM;");
      break;

    case "0.9.16+0.9.17pre19":
      $this->info(" - changing id of `diogenes_logsessions` to INT(10) UNSIGNED");
      $this->dbh->query("ALTER TABLE `diogenes_logsessions` CHANGE `id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT");
      break;
     
    case "0.9.16+0.9.17pre21":      
      $this->dbh->query("INSERT INTO diogenes_logactions VALUES (13, 'barrel_options', 'the barrel options were updated');");
      $this->dbh->query("INSERT INTO diogenes_logactions VALUES (14, 'barrel_plugins', 'the barrel plugins were modified');");
      $this->dbh->query("INSERT INTO diogenes_logactions VALUES (15, 'page_props', 'the page properties were updated');");
      $this->dbh->query("INSERT INTO diogenes_logactions VALUES (16, 'page_plugins', 'the page plugins were modified');");
      break;

    default:
      $this->info(" - no changes needed.");
      break;
    }
  }
}

/*
 * Main routine
*/
$creator = new DiogenesDbInit("diogenes_option");
$creator->parseOptions($argv, "diogenes", "localhost", "diogenes", "");
$creator->run();

?>
