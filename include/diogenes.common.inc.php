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

  // define Diogenes core classes
  require_once 'diogenes.globals.inc.php';
  require_once 'diogenes/diogenes.database.inc.php';
  require_once 'diogenes.session.inc.php';
  require_once 'diogenes.cvs.inc.php';
  require_once 'Plugins.php';

  // get custom definitions
  $globals = new DiogenesGlobals();
  require_once 'diogenes.config.inc.php';

  // location of the Diogenes library
  if (!isset($globals->libroot))
    $globals->libroot = $globals->root."/lib";

  // connect to database
  $globals->dbconnect();

  // read extra options from database
  $globals->readOptions();

  // set up the plugin holder
  $globals->plugins = new Diogenes_Plugins($globals->db, $globals->root . "/plugins", $globals->spoolroot . "/diogenes_c");

  // set up the icons
  $globals->icons = new DiogenesIcons();

  // do we want to debug database calls ?
  if ($globals->debugdatabase)
    $globals->db->trace_on();
  
  // environment variable for gettext
  bindtextdomain("diogenes", "{$globals->root}/locale");
  textdomain("diogenes");
  if (isset($_COOKIE['lang'])) {
    putenv("LANG={$_COOKIE['lang']}");
    setlocale(LC_ALL,$_COOKIE['lang']);
  }

?>
