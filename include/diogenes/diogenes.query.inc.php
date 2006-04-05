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


/**
 * A class for describing database queries and breaking down the results
 * into pages.
 */
class DiogenesQuery {
  /** The number of arguments in the current query. */
  var $nArgs;
  /** The current query string. */
  var $sQuery;

  /** The constructor.
   */
  function DiogenesQuery($sQue = "") {
    $this->sQuery = $sQue;
    $this->nArgs = 0;
  }


  /** Add an argument to the query.
   */
  function addArg($sArg, $sSep = "") {
    if ($sSep && $this->nArgs)
       $this->sQuery .= $sSep;
    $this->sQuery .= $sArg;
    $this->nArgs++;
  }


  /** Return an array holding the start and end point
   *  for the pages holding the results.
   */
  function getPages($qcount,$hitmax=0)
  {
    if (!$hitmax)
      return array(array(0,$qcount));

    $pages = array();
    $nrest = $qcount % $hitmax;
    $npages = ($qcount - $nrest) / $hitmax;

    // complete pages
    for($i = 0; $i < $npages; $i++)
      array_push($pages, array($i*$hitmax, ($i+1)*$hitmax));

    // leftovers
    if ($nrest)
      array_push($pages, array($npages*$hitmax, $npages*$hitmax+$nrest));
    return $pages;
  }


  /** Execute the query and return the result.
   */
  function getResult(&$dbh,$hitmax=0,$start=0)
  {
    $query = $this->sQuery;
    if ($hitmax) {
      $query .= " LIMIT $start, $hitmax";
    }

    return $dbh->query($query);
  }


  /** Accessor for the query string.
   */
  function getQuery() {
    return $this->sQuery;
  }

}

?>
