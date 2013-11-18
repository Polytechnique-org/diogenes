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


require_once 'Smarty.class.php';

/** Base class for all of Diogenes' pages. This class is purposefully
 *  kept very generic as it is part of the Diogenes library which is
 *  also used by Lycee-vanGogh.net.
 */
class DiogenesCorePage extends Smarty {
  /** The constructor.
   */
  function DiogenesCorePage() {
    global $globals;

    // smarty definitions
    $this->Smarty();
    $this->template_dir = $globals->root."/templates";
    $this->config_dir = $globals->root."/configs";
    $this->compile_dir = $globals->spoolroot."/templates_c";

    $this->register_function("extval","diogenes_func_extval");
    $this->register_function("flags","diogenes_func_flags");
    $this->register_function("a","diogenes_func_a");
    $this->register_function("checkbox","diogenes_func_checkbox");
    $this->register_function("diff","diogenes_func_diff");
    $this->register_function("menu_item","diogenes_func_menu_item");
    $this->register_function("tag","diogenes_func_tag");
    $this->register_function("toolbar","diogenes_func_toolbar");
    $this->debugging_ctrl = true;

    // smarty assignments
    $this->assign('script_self',$this->script_self());
  }


  /** Check that $_SESSION['session'] is usable
   */
  function checkSession()
  {
    return isset($_SESSION['session']) && is_object($_SESSION['session']);
  }


  /** Return the current script location.
   */
  function script_self() {
    $url = explode("?",$this->script_uri());
    return $url[0];
  }


  /** Return the current URI.
   */
  function script_uri() {
    return $_SERVER['REQUEST_URI'];
  }


  /** Returns the URL to a page relative to current location.
   *
   * @param rel
   */
  function url($rel) {
    global $globals;

    return $globals->rooturl.$rel;
  }

}


/** Displays an anchor tag.
 *
 *  Parameters:
 *   +lnk an array containing([href],text,icon)
 *   +class
 *
 * @param params the function input
 */
function diogenes_func_a($params)
{
  extract($params);
  if (empty($lnk))
    return;

  if (is_array($lnk)) {
    $text = $lnk[0];
    $href = $lnk[1];
    $icon = isset($lnk[2]) ? $lnk[2] : '';
  } else {
    $text = $lnk;
  }
  
  if (empty($href) && empty($class))
    $class = "empty";

  // we have either an href or a class
  return "<a"
         .( empty($class) ? "" : " class=\"$class\"")
         .( empty($href) ? "" : " href=\"$href\"")
         .">"
         .( empty($icon) ? $text : "<img src=\"$icon\" alt=\"$text\" title=\"$text\" />" )
         ."</a>";
}


/** Displays a checkbox.
 *
 * @param params the function input
 */
function diogenes_func_checkbox($params)
{
  extract($params);

  if (empty($name)) $name = "";
  if (empty($value)) $value = 1;
  $checked = (!empty($checked));

  return "<input type=\"checkbox\"".
         ($name ? " name=\"$name\"" : "").
         ($checked ? " checked=\"checked\"" : "").
         " value=\"$value\" />";
}


/** Format some diff lines for output.
 *
 * @param params
 */
function diogenes_func_diff($params)
{
  extract($params);

  if (empty($block) || empty($op))
    return;
  $lines=explode("\n",$block);
  $out=$out2="";
  foreach($lines as $line) {
    switch(substr($line,0,2)) {
    case "> ":
      if ($op == "a")
        $class = "add";
      else
        $class = "change";
      break;
    case "< ":
      if ($op == "d")
        $class = "delete";
      else
        $class = "change";
      break;
    default:
      $class = "other";
    }
    // strip 2 leading chars
    $line = substr($line,2);
    // if necessary, drop trailing newline char
    if (substr($line,-1) == "\n")
      $line = substr($line,0,-1);

    if (isset($old)) {
      if ($old != $class)
        $out .= "</div>";
      else
        $out .= "<br />\n";
    }
    if ($line) {
      if (!isset($old) || ($old != $class))
        $out .= "<div class=\"$class\">";
      $out .= htmlentities($line, ENT_COMPAT | ENT_HTML401, "ISO-8859-1");
    }
    $old = $class;
  }
  return $out;
}


/** Displays a set of external values from a database
 *
 * @params a set of options read from a database
 */
function diogenes_func_extval($params) {
  global $globals,$diogenes_db_cache;
  if(empty($diogenes_db_cache)) $diogenes_db_cache = Array();

  extract($params);
  if(empty($table) | empty($field) | empty($vtable) | empty($vjoinid) | empty($vfield))
    return;

  $cache_id = "$vtable,$vjoinid,$vfield";

  if(empty($diogenes_db_cache[$cache_id])) {
    $res = $globals->db->query("select $vjoinid,$vfield from $vtable order by $vfield");
    $diogenes_db_cache[$cache_id] = Array();
    while(list($id,$val) = mysql_fetch_row($res))
      $diogenes_db_cache[$cache_id][$id] = $val;
  }
  
  $html_out = "";
  // if we have a name, display opening select tag
  if (isset($value))
    return $diogenes_db_cache[$cache_id][$value];

  if(empty($name))
    return;
    
  $html_out .= "<select name=\"$name\">\n";
  foreach($diogenes_db_cache[$cache_id] as $id=>$val)
    $html_out .= "  <option value=\"$id\"".
                    ($selected==$id ? " selected=\"selected\"":"")
                    .">".htmlentities($val, ENT_COMPAT | ENT_HTML401, "ISO-8859-1")."</option>\n";
  $html_out .= "</select>\n";

  return $html_out;
}

/** Displays a set of options read from a database.
 *
 * @param params the function input
 */
function diogenes_func_flags($params)
{
  global $globals;
  extract($params);

  if (empty($table) | empty($field))
    return;
  if (empty($selected))
    $selected = "";

  $res = $globals->db->query("show columns from $table like '$field'");
  $set = mysql_fetch_row($res);
  $set = $set[1];

  // examine the type of field
  if (substr($set,0,5)=="enum(") {
    $multi = false;
    $set = substr($set,5);
  } else if (substr($set,0,4) == "set(") {
    $multi = true;
    $set = substr($set,4);
  } else {
    return "field neither set nor enum";
  }

  $html_out = "";
  // if we have a name, display opening select tag
  if (!empty($name))
    $html_out .= "<select name=\"$name".($multi ? "[]\" multiple=\"multiple\"" : "\"").">\n";

  $set = ereg_replace('\)$', '', $set);
  $set = explode(',', $set);
  for ($vals = explode(',', $selected); list(, $k) = each($vals);) {
    $vset[$k] = 1;
  }

  $countset = count($set);
  for($j=0; $j < $countset; $j++) {
    $subset = substr($set[$j], 1, -1);
    // Removes automatic MySQL escape format
    $subset = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $subset));
    $html_out .= "<option value=\"$subset\""
               . ((isset($vset[$subset]) && $vset[$subset]) ? " selected=\"selected\"" : "")
               . ">".(isset($trans) ? $trans[$subset] : htmlspecialchars($subset, ENT_COMPAT | ENT_HTML401, "ISO-8859-1"))."</option>\n";
  }

  // if we have a name, display closing select tag
  if (!empty($name))
    $html_out .= "</select>\n";

  return $html_out;
}


/** Displays a menu item.
 *
 *  Parameters:
 *   +item a menu item, that is an array (item_level, item_link, item_text)
 *
 * @param params the function input
 */
function diogenes_func_menu_item($params)
{
  extract($params);
  if (empty($item))
    return;

  $level = array_shift($item);
  if ($level == 0)
    $class = "top";
  else
    $class = ($level % 2) ? "odd" : "even";

  // process link
  $lnk = array( $item[0] );
  if ( isset($item[1]) )
    array_push($lnk, $item[1]);
  
  $margin = $level * 20;
  return "<div class=\"item\" style=\"margin-left: {$margin}px\">".
         diogenes_func_a(array("lnk"=>$lnk,"class"=>$class))."</div>";

}


/** Displays a generic XHTML tag.
 *
 * Parameters
 *  +tag : the type of tag (required)
 *  +props : the tag's properties (optional)
 *  +content : the tag's contents (optional)
 *
 *  OR
 *
 *  +item : associative array containing (tag, props, content)
 */
function diogenes_func_tag($params)
{
  extract($params);

  if (isset($item) && is_array($item))
    extract($item);

  if (empty($tag))
    return;
  
  $out = "<$tag";

  if (is_array($props)) {
    foreach($props as $key=>$val)
      $out .= " $key=\"$val\"";
  }
  
  $out .= empty($content) ? " />" : ">$content</$tag>";

  return $out;
}


/** Displays a toolbar from a collection of links.
 *
 *  Parameters:
 *   +lnk     a link or an array of links
 *   +class   the CSS class for the links
 *
 * @param params the function input
 */
function diogenes_func_toolbar($params)
{
  extract($params);
  if (empty($lnk))
    return;

  if (!is_array($lnk))
    $lnk = array($lnk);

  // the separator
  $sep = "&nbsp;|&nbsp;";
  $out = $sep;
  foreach($lnk as $mylnk) {
    if (empty($class))
      $out .= diogenes_func_a(array("lnk"=>$mylnk));
    else
      $out .= diogenes_func_a(array("lnk"=>$mylnk,"class"=>$class));
    $out .= $sep;
  }
  return $out;
}

?>
