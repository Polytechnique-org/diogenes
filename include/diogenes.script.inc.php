<?php
/*
 * Copyright (C) 2003-2006 Polytechnique.org
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

require_once 'Barrel.php';

/** This class describes a Diogenes script.
 */
class Diogenes_Script
{
  function Diogenes_Script($alias,$login)
  {
    $this->alias = $alias;
    $this->login = $login;
    $this->barrel = new Diogenes_Barrel($alias);
    if (!$this->barrel->alias) {
        $this->kill("unknown barrel requested : $alias");
    }
  }

  function kill($msg)
  {
    echo "KILL: $msg\n";
    exit(1);
  }
  
  function info($msg)
  {
    echo "INFO: $msg\n";
  }

  function getRcs()
  {
    global $globals;
    return new $globals->rcs($this,$this->alias,$this->login);
  }
  
  function log($action, $data="")
  {
    echo "LOG: $action $data\n";
  }
}

?>
