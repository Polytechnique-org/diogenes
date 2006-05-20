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

require_once 'diogenes/diogenes.misc.inc.php';

define('NODE_DUMP_BINARY', 0);
define('NODE_DUMP_TEXT', 1);
define('NODE_DUMP_NOCHILDREN', 2);
define('VAR_TYPE_NULL', 0);
define('VAR_TYPE_INT', 1);
define('VAR_TYPE_STRING', 2);
define('VAR_TYPE_ARRAY', 3);
define('VAR_TYPE_NODE', 4);

function var_encode_text($var, $level = 0, $no_children = FALSE, $tabstr = '  ', $eol = "\n")
{
  if (is_object($var) && (get_class($var) == 'Diogenes_Tree_Node')) {
    // node name
    $code = str_repeat($tabstr, $level) . "node : {$var->name}" . $eol;

    // encode this node's data
    $code .= str_repeat($tabstr, $level+1) . "* data : " . var_encode_text($var->data, $level + 2, $no_children, $tabstr, $eol). $eol;

    // encode this node's children
    if (!$no_children)
    {
      $code .= str_repeat($tabstr, $level+1) . "* children" . $eol;
      foreach ($var->children as $index => $child)
        $code .= str_repeat($tabstr, $level+2) . "index : $index" . $eol;
        $code .= var_encode_text($child, $level+2, $no_children, $tabstr, $eol);
    }
    return $code;
  } elseif (is_array($var)) {
    $arraysep = ",$eol";
    $vcode = '';
    foreach ($var as $key => $value) {
      $vcode .= str_repeat($tabstr, $level + 1);
      $vcode .= "'$key'=>".var_encode_text($value, $level + 1, $no_children, $tabstr, $eol);
      $vcode .= $arraysep;
    }
    if (substr($vcode, -strlen($arraysep)) == $arraysep)
      $vcode = substr($vcode, 0, - strlen($arraysep));

    $code = "array(".($vcode ? "$eol$vcode$eol".str_repeat($tabstr, $level)  : ""). ")";
    return $code;
  } elseif (is_scalar($var)) {
    return "'".$var."'";
  } else {
    return 'NULL';
  }
}

function var_encode_html($var, $level = 0, $no_children = FALSE)
{
  return var_encode_text($var, $level, $no_children, '&nbsp;&nbsp;', $eol = "<br/>\n");
}

function var_encode_bin($var, $no_children = FALSE)
{
  if (is_object($var) && (get_class($var) == 'Diogenes_Tree_Node')) {
    $code = pack('C', VAR_TYPE_NODE);
    $code .= var_encode_bin($var->name);
    $code .= var_encode_bin($var->data);
    if ($no_children)
      $code .= var_encode_bin(array());
    else
      $code .= var_encode_bin($var->children);
  } elseif (is_array($var)) {
    $code = pack('C', VAR_TYPE_ARRAY);
    $contents = '';
    foreach ($var as $key => $value) {
      $contents .= var_encode_bin($key);
      $contents .= var_encode_bin($value);
    }
    $code .= pack('L', strlen($contents));
    $code .= $contents;
  } elseif (is_scalar($var)) {
    $str = "$var";
    $code = pack('C', VAR_TYPE_STRING);
    $code .= pack('L', strlen($str));
    $code .= $str;
  } else {
    $code = pack('C', VAR_TYPE_NULL);
   }
   return $code;
}

function var_decode_bin(&$bin)
{
  list(,$type) = unpack('C', $bin);
  $bin = substr($bin, 1);
  if ($type == VAR_TYPE_NODE)
  {
    $name = var_decode_bin($bin);
    $data = var_decode_bin($bin);
    $children = var_decode_bin($bin);
    $var = new Diogenes_Tree_Node($data, $name, $children);
  } elseif ($type == VAR_TYPE_ARRAY)
  {
    list(,$length) = unpack('L', $bin);
    $bin = substr($bin, 4);
    $contents = substr($bin, 0, $length);
    $bin = substr($bin, $length);

    $var = array();
    while(strlen($contents)) {
      $key = var_decode_bin($contents);
      $var[$key] = var_decode_bin($contents);
    }
  } elseif ($type == VAR_TYPE_STRING) {
    list(,$length) = unpack('L', $bin);
    $bin = substr($bin, 4);
    $var = substr($bin, 0, $length);
    $bin = substr($bin, $length);
  } elseif ($type == VAR_TYPE_NULL) {
    $var = NULL;
  } else {
    trigger_error("unknown type in var_decode_bin : ". $type);
  }
  return $var;
}

/** This class describes Diogenes' plugins. 
 */
class Diogenes_Tree_Node
{
  /** Data for the current node */
  var $data;

  /** Name for the current node */
  var $name;

  /** An array of child nodes */
  var $children = array();

  /** The parent of this node */
  // var $parent;

  /** Construct a new tree node.
   */
  function Diogenes_Tree_Node($data, $name = '', $children=array())
  {
    $this->data = $data;
    $this->name = $name;
    $this->children = $children;
  }

  /** Return the specified child of this node.
   */
  function getChild($index)
  {
    return $this->children[$index];
  }

  /** Assign the specified child of this node.
   */
  function setChild($index, $node)
  {
    $this->children[$index] = $node;
  }

  /** Read a dump of a node and its children.
   */
  function readFile($filename)
  {
    $bin = file_get_contents($filename);
    $node = var_decode_bin($bin);
    if (!is_object($node) || get_class($node) != 'Diogenes_Tree_Node')
    {
      trigger_error('readFile : not a Diogenes_Tree_Node', E_USER_ERROR);
    }
    return $node;
  }

  /** Write a dump of this node and its children.
   */
  function writeFile($filename, $mode = NODE_DUMP_IO)
  {
    if (!$fp = fopen($filename, "w")) {
      trigger_error("writeFile : failed to open '$cachefile' for writing", E_USER_ERROR);
    }

    if ($mode & NODE_DUMP_TEXT) {
      $out = var_encode_text($this, $level, ($mode & NODE_DUMP_NOCHILDREN));
    } else {
      $out = var_encode_bin($this, ($mode & NODE_DUMP_NOCHILDREN));
    }
    fputs($fp, $out);

    fclose($fp);
  }

}

?>
