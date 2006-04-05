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


require_once dirname(__FILE__).'/diogenes.misc.inc.php';
require_once dirname(__FILE__).'/diogenes.database.table.inc.php';

/** Generic class for editing MySQL tables.
 *
 * TODO: give an example of how to use the class
 *
 * @see DiogenesDatabaseTable
 */
class DiogenesTableEditor extends DiogenesDatabaseTable {
  /** The actions. */
  var $actions;
  /** Is the idfield automatically incremented? */
  var $autoinc;
  /** Id of the current entry. */
  var $id;
  /** Is the id field editable? */
  var $idedit;
  /** Is the id field in the summary? */
  var $idsum = true;
  /** The field that is used as the primary key */
  var $idfield;
  /** The tables we do a join on. */
  var $jtables = array();
  /** The where clause for the select */
  var $wheres = array();
  /** Prefix for all the form variables (to avoid conflicting with
   *  any parameters used by the site's pages). */
  var $prefix = "frm_";
  /** Should editing functions be disabled? */
  var $readonly = false;
  /** Maximum number of results per page, 0 for unlimited */
  var $maxrows = 0;

  /** CSS class of the table that contains the editor */
  var $table_class = "light";
  /** Extra CSS style info for the table that contains the editor */
  var $table_style = "";
  
  /** The constructor.
   *
   * @param $table   the MySQL table we are operating on
   * @param $idfield the field we use as unique id
   * @param $idedit  is the id field editable ?
   */
  function DiogenesTableEditor($table, $idfield, $idedit = false) {
    global $globals;
    if (!is_object($globals->db))
      die("\$globals->db is not an object!");

    $this->DiogenesDatabaseTable($globals->db,$table);

    $this->actions = array();

    if (!isset($this->vars[$idfield]))
      die("the field '$idfield' was not found in '$table'");
    $this->idfield = $idfield;
    $extra = new flagset($this->vars[$idfield]["extra"]);
    $this->autoinc = $extra->hasflag("auto_increment");
    $this->idedit = $idedit;

    // unless the id field is editable, remove it from the variables
    if (!$this->idedit)
      unset($this->vars[$idfield]);
  }


  /** Add an "action" (hyperlink) to be displayed next to items in the summary.
   *
   * @param text the text for the hyperlink
   * @param url  the url for the hyperlink. anything of the form %foobar% will
   *             be replaced by the value of the field 'foobar'
   */
  function addAction($text,$url)
  {
    array_push($this->actions, array("text" => $text, "url" =>$url));
  }


  /** Adds a join with another table.
   *
   * @param $name      the name of the table
   * @param $joinid    the fields on which we do a join with our "id"
   * @param $joindel   should we delete the joined entries in the table when we
                       delete an entry from $this->table?
   * @param $joinextra extra clause for the join
   *
   * @see delete_db
   */
  function add_join_table($name,$joinid,$joindel,$joinextra="") {
    $this->jtables[$name] = array("joinid" => $joinid,"joindel" => $joindel,
      "joinextra" => $joinextra);
  }


  /** EXPERIMENTAL : Add a field from another table.
   *
   * @param $table the table for which we are adding the field
   * @param $name  name of the variable (AS clause)
   * @param $field the name of the table's field
   * @param $value the default value of the field
   * @param $desc  a description
   * @param $sum   should we display this field in the table summary?
   * @param $type  type of the field : "enum", "set", "text", "textarea", "timestamp"
   */
  function add_join_field($table,$name,$field,$value,$desc,$sum,$type="text") {
    $this->vars[$name] = array("table" => $table, "field" => $field, "type" => $type,
     "value" => $value, "desc" => $desc, "sum" => $sum);
  }


  /** Add a WHERE condition to the SELECT used for listing.
   *
   * @param cond string containing the WHERE condition
   */
  function add_where_condition($cond) {
    array_push($this->wheres, $cond);
  }


  /** Deletes the specified entry from the database.
   *
   * @param $id id of the entry to be deleted
   */
  function delete_db($id) {
    if ($id != '') {
      $this->dbh->query("delete from {$this->table} where {$this->idfield}='$id'");

      // delete dependencies in the tables on which we have a join
      foreach ($this->jtables as $key => $val) {
        if ($val['joindel']) {
          $sql = "delete from {$key} where {$val['joinid']}='$id'";
          if ($val['joinextra'])
            $sql .= " and {$val['joinextra']}";
          $this->dbh->query($sql);
        }
      }
      reset($this->jtables);
    }
  }


  /** Describe a column and sets it visibility
   *
   * @param name  the name of the field
   * @param desc  the description for the field
   * @param sum   a boolean indicating whether to display the field in the summary
   * @param type  type of the field (optional)
   * @param value default value of the field (optional)
   * @param trans stores translations when it's a "set" value
   */
  function describe($name,$desc,$sum,$type='',$value='',$trans=null) {
    if (!isset($this->vars[$name]))
      die("unknown field $name");
    $this->vars[$name]["desc"] = $desc;
    $this->vars[$name]["sum"] = $sum;
    if (!empty($type))
      $this->vars[$name]["type"] = $type;
    if (!empty($value))
      $this->vars[$name]["value"] = $value;
    if (!empty($trans) and $type=="set")
      $this->vars[$name]["trans"] = $trans;
  }


  /** Describe a column and tell where to find possible values and sets it visibility
   *
   * @param name    the name of the field
   * @param desc    the description for the field
   * @param sum     a boolean indicating whether to display the field in the summary
   * @param vtable  the name of the table where are located the external values
   * @param vjoinid the $vtable field name corresponding to $this->table field $name
   * @param vfield  the $vtable field wich hold the significative and comprehensive value
   */
  function describe_join_value($name,$desc,$sum,$vtable,$vjoinid,$vfield) {
    global $globals;
    if (!isset($this->vars[$name]))
      die("unknown field $name");
    $this->vars[$name]["desc"] = $desc;
    $this->vars[$name]["sum"] = $sum;
    $this->vars[$name]["type"] = "ext";

    $this->vars[$name]["vtable"] = $vtable;
    $this->vars[$name]["vjoinid"] = $vjoinid;
    $this->vars[$name]["vfield"] = $vfield;
  }


  /** Make a field hidden and uneditable
   *
   * @param name  the name of the field
   * @param value default value of the field (optional)
   */
  function hide($name, $value = null) {
    if (!isset($this->vars[$name]))
      die("unknown field $name");

    if (!empty($value))
      $this->vars[$name]["value"] = $value;

    $this->vars[$name]["edit"] = false;
    $this->vars[$name]["show"] = false;
  }


  /** Do not display the id field in the summary
   *
   */
  function hide_id() {
    $this->idsum = false;
  }


  /** Make a field uneditable
   *
   * @param name  the name of the field
   * @param value default value of the field (optional)
   */
  function lock($name, $value = null) {
    if (!isset($this->vars[$name]))
      die("unknown field $name");
    
    if (!empty($value))
      $this->vars[$name]["value"] = $value;
    
    $this->vars[$name]["edit"] = false;
  }
  
  /** Read the selected entry from database
   *
   * @param id id of the entry we want to read
   */
  function from_db($id) {
    $sql = $this->make_select(false,$id);
    $res = $this->dbh->query($sql);

    if ($myrow = mysql_fetch_array($res)) {
      $this->id = $id;
      foreach ($this->vars as $key => $val) {
        $this->vars[$key]['value'] = $myrow[$key];
      }
      reset($this->vars);
      return true;
    } else {
      return false;
    }
  }


  /** Read the current entry's values from the $_REQUEST variable
   */
  function from_request() {
    if (isset($_REQUEST[$this->prefix.'id']))
      $this->id = clean_request($this->prefix.'id');

    foreach ($this->vars as $key => $val) {
      // if this field is editable, retrieve the value from $_REQUEST
      if ($val['edit'])
        $this->vars[$key]['value'] = clean_request($this->prefix.$key);

      // apply type-specific transformations
      switch ($val['type']) {
      case "set":
        if ( is_array($this->vars[$key]['value']) )
          $this->vars[$key]['value'] = implode(",",$this->vars[$key]['value']);
        break;
      case "timestamp":
        $this->vars[$key]['value'] = mktime($this->vars[$key]['value']);
        break;
      }
    }
    reset($this->vars);
  }


  /** Write the current entry to database.
   */
  function to_db() {
    $varlst = new flagset();
    foreach ($this->vars as $key => $val) {
      # we only want fields from our own table that are either editable or new
      if (($val['table'] == $this->table) && ($val['edit'] || !isset($this->id))) {
        switch ($val['type']) {
        case "password":
          if ($val['value'])
            $varlst->addflag("$key='".md5($val['value'])."'");
          break;
        default:
          $varlst->addflag("$key='{$val['value']}'");
        }
      }
    }
    reset($this->vars);

    if (isset($this->id)) {
      $sql = "update {$this->table} set {$varlst->value} where {$this->idfield}='{$this->id}'";
      if ($this->wheres)
        $sql .= " AND " . join(" AND ", $this->wheres);
    } else {
      if (!$this->autoinc && !$this->idedit) {
        list($this->id) = mysql_fetch_row($this->dbh->query("select MAX({$this->idfield})+1 from {$this->table}"));
        $varlst->addflag("{$this->idfield}='{$this->id}'");
      }
      $sql = "insert into {$this->table} set {$varlst->value}";
    }
    $this->dbh->query($sql);

    // retrieve the insertion id
    if ($this->idedit) { 
      $this->id = $this->vars[$this->idfield]['value'];
    } else if ($this->autoinc && !isset($this->id)) {
      $this->id = $this->dbh->insert_id();
    }
  }


  /** Returns the JOIN clause to read a field.
   *
   * @param $val the field we want to read
   */
  function make_join_flag($val) {
    if ($val['table'] == $this->table) {
      // this field is local, no join clause needed
      return "";
    } else {
      // not a local field, we need a join clause
      $tbl_key = $val['table'];
      $tbl_val = $this->jtables[$tbl_key];
      $flg = "left join $tbl_key on {$this->table}.{$this->idfield}={$tbl_key}.{$tbl_val['joinid']}";
      if ($tbl_val['joinextra'])
        $flg .= " and {$tbl_val['joinextra']}";
      return $flg;
    }
  }


  /** Create the SELECT request to display the table summary
   *  or to read an entry from the database
   *
   * @param $list boolean : are we displaying the summary?
   * @param $num  depending on $list, either the column names or the entry to display
   */
  function make_select($list,&$num) {
    if ($list) {
      $varlst = new flagset("{$this->table}.{$this->idfield}");
      $orderby = "";
      $num = $this->idsum ? 1 : 0;
    } else {
      $varlst = new flagset();
    }

    $joinlst = new flagset();
    // run over all the variables
    foreach ($this->vars as $key => $val) {
      // if we are listing the summary, we want only the variables
      // where ['sum'] is true.
      if (!$list || $val['sum']) {
        // type conversion
        if ($val['type'] == "timestamp")
          $varlst->addflag("UNIX_TIMESTAMP({$val['table']}.{$val['field']}) as $key");
	else
          $varlst->addflag("{$val['table']}.{$val['field']} as $key");

        // do we need a join clause?
	if ($flag = $this->make_join_flag($val))
	  $joinlst->addflag($flag);

	// if we are listing the summary, we want an order clause
        if ($list) {
	  if (!$orderby) $orderby = "order by $key";
    	  $num++;
  	}
      }
    }

    reset($this->vars);

    $sql = "select {$varlst->value} from {$this->table} {$joinlst->value}";

    # if we are in edit mode, add a WHERE condition
    if (!$list)
      $this->add_where_condition("{$this->table}.{$this->idfield}='$num'");

    # sum up all the WHERE conditions
    if ($this->wheres)
      $sql .= " where " . join(" AND ", $this->wheres);

    # add order by clause
    if ($list)
      $sql .= " $orderby";
   
    return $sql;
  }


  /** Set the maximum number of rows to return per page when viewing entries.
   *  By default, this is set to 0, meaning all rows are returned.
   *
   * @param maxrows   the maximum number of rows (0 means unlimited)
   */
  function set_maxrows($maxrows)
  {
    // check we were given a zero or positive number
    if ($maxrows < 0) {
      trigger_error("You cannot pass a negative number ($maxrows) to set_maxrows!");
      return;
    }
    
    $this->maxrows = $maxrows;
  }
  
  /** Process the requested action and fill out the Smarty variables for display.
   *  By default, displays the entries in the database table.
   *
   * @param page      the page that will display the editor's forms
   * @param outputvar the Smarty variable to which we should assign the output 
   * @param template  the template to use for display
   */
  function run(&$page,$outputvar='',$template='') {
    global $globals;

    $action = clean_request('action');
    $page->assign('table',$this->table);

    switch($action) {
    case "edit":
      // check we are not in read-only mode
      if ($this->readonly) die("Sorry, this table is read-only.");
	
      // if this is an existing entry, retrieve it
      if (clean_request("{$this->prefix}id") != '') {
        $this->from_db(clean_request("{$this->prefix}id"));
        $page->assign('id',$this->id);
      }
      
      // remove the uneditable fields
      #foreach ($this->vars as $key => $val) {
      #if (!$val['edit'])
      #  unset($this->vars[$key]);
      #}
      reset($this->vars);
     
      $page->assign('doedit',true);
      break;
    case "update":
      // check we are not in read-only mode
      if ($this->readonly) die("Sorry, this table is read-only.");

      $this->from_request();
      $this->to_db();
      break;
    case "del":
      // check we are not in read-only mode
      if ($this->readonly) die("Sorry, this table is read-only.");
      
      $this->delete_db(clean_request("{$this->prefix}id"));
      break;
    }

    // if we are not in editor mode, display the list
    if ($action != "edit") {
      $ncols = 0;
      $sql = $this->make_select(true,$ncols);
      $res = $this->dbh->query($sql);
      $page->assign('ncols',$ncols);

      // determine start and stop of displayed results
      $p_total = mysql_num_rows($res);
      $p_start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
      $p_stop = $this->maxrows ? min($p_total, $p_start + $this->maxrows) : $p_total;
      $counter = 0;

      while (($counter < $p_stop) and ($myarr = mysql_fetch_array($res))) {
        if ($counter >= $p_start) {
          $actions = array();
          foreach ($this->actions as $myaction) {
            $url = $myaction['url'];
            foreach ($myarr as $key=>$val)
              $url = str_replace("%$key%", $val, $url);

            array_push($actions, array($myaction['text'],$url));
          }
          $page->append('rows', array($myarr[$this->idfield], $myarr, $actions) );
        }
	$counter++;
      }
      mysql_free_result($res);

      // smarty assignements for prev / next page links
      $page->assign('p_prev', $p_start ? max($p_start - $this->maxrows, 0) : -1);
      $page->assign('p_next', ($p_stop < $p_total) ? $p_stop  : - 1 );
      $page->assign('p_total', $p_total);
     
    }

    $page->assign('vars',$this->vars);
    $page->assign('prefix',$this->prefix);
    $page->assign('idfield',$this->idfield);
    $page->assign('idsum',$this->idsum);
    $page->assign('readonly',$this->readonly);
    if ($this->readonly && ($this->actions == array()))
      $page->assign('hideactions', 1);
    $page->assign('table_class', $this->table_class);
    $page->assign('table_style', $this->table_style);
      
    // translations
    $page->assign('msg_previous_page', __("previous page"));
    $page->assign('msg_next_page', __("next page"));
    $page->assign('msg_id', __("id"));
    $page->assign('msg_action', __("action"));
    $page->assign('msg_create', __("create"));
    $page->assign('msg_delete', __("delete"));
    $page->assign('msg_edit', __("edit"));
    $page->assign('msg_new_entry', __("new entry"));
    $page->assign('msg_existing_entry', __("existing entry"));
    $page->assign('msg_no_change', __("(blank = no change)"));
    $page->assign('msg_back', __("back"));
    $page->assign('msg_confirm_delete', 
                  __("You are about to delete this entry. Do you want to proceed?"));
    $page->assign('msg_submit', __("Submit"));
    
    // if requested, assign the content to be displayed
    if (!empty($outputvar)) {
      if (empty($template))
        $template = $globals->libroot."/templates/table-editor.tpl";
      $page->assign($outputvar, $page->fetch($template));
    }
  }

}

?>
