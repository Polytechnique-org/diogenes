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


/** This class handles Diogenes spool operations.
 */
class DiogenesSpool {
  /** The barrel we are running on */
  var $alias;
  /** Absolute directory location for the barrel's spool. */
  var $datadir;

  /** The last command that was executed. */
  var $cmd_call;
  
  /** The output of the last command that was executed. */
  var $cmd_output;
  
  /** The return value of the last command. */
  var $cmd_return;
  
  /** The caller. It needs to define the 3 following methods :
   * 
   * @see info, kill, log
   */
  var $caller;

  /** The constructor.
   *
   * @param caller the caller
   * @param alias the alias to work on
   */
  function DiogenesSpool(&$caller,$alias) {
    global $globals;
    $this->datadir = "{$globals->spoolroot}/$alias";
    $this->caller =& $caller;
    $this->alias = $alias;
  }

  
  /** Execute a shell command and store information about it.
   *
   * @param $cmd the command to execute
   */
  function cmdExec($cmd)
  {
    $this->cmd_call = $cmd;    
    unset($this->cmd_output);
    unset($this->cmd_return);
    
    exec($cmd, $this->cmd_output, $this->cmd_return);
    
    return $this->cmd_return;
  }
  
  
  /** Return information about the last command that was executed.
   */
  function cmdStatus()
  {
    $out  =
     "-- [ command information ] -- \n".
     "command : {$this->cmd_call}\n".
     "return value : {$this->cmd_return}\n".
     "-- [ command output ] --\n".
     implode("\n", $this->cmd_output)."\n".
     "--";
   return $out;
  }

  
  /** Report an information.
   *
   * @param msg
   */
  function info($msg) {
    $this->caller->info($msg);
  }


  /** Die with a given error message.
   *
   * @param msg
   */
  function kill($msg) {
    $this->caller->kill($msg);
  }


  /** Record an action to the log.
   *
   * @param action
   * @param data
   */
  function log($action,$data) {
    $this->caller->log($action, $data);
  }


  /** Check that a path is valid.
   *
   * @param parent parent directory
   * @param entry the item
   * @param fatal should we consider a failure to be fatal?
   */
  function checkPath($parent,$entry,$fatal = true) {
    $ret = preg_match("/^([a-zA-Z0-9\-_]+[\.,\/]?)*$/",$parent)
        && preg_match("/^([a-zA-Z0-9\-_]+[\., ]?)*$/",$entry);

    if (!$ret && $fatal)
      $this->kill("malformed path ('$parent','$entry')");
    
    return $ret;
  }


  /** Add missing tags to a Diogenes page to make it a proper HTML file
   *
   * @param html
   * @see importHtmlString
   */
  function exportHtmlString($html)
  {
    // if we have the body open & close tags, return raw file
    if (preg_match("/<body(\s[^>]*|)>(.*)<\/body>/si",$html))
      return $html;
    
    return "<html>\n<head><title>Diogenes page</title></head>\n<body>$html</body>\n</html>\n";
  }


  /** Makes a Diogenes page from a proper HTML file, that is return everything
   *  inside the "body" tags.
   *
   * @param html
   * @see exportHtmlString
   */
  function importHtmlString($html)
  {
    // If available, run tidy to clean sources
    if (function_exists('tidy_repair_string')) {
        $tidy_config = array('drop-empty-paras' => true,
                             'drop-proprietary-attributes' => true,
                             'hide-comments' => true,
                             'logical-emphasis' => true,
                             'output-xhtml' => true,
                             'replace-color' => true,
                             'join-classes'  => true,
                             'join-style' => true, 
                             'clean' => true,
                             'show-body-only' => true,
                             'alt-text' => '[ inserted by TIDY ]',
                             'break-before-br' => true,
                             'indent' => true,
                             'vertical-space' => true,
                             'wrap' => 120);
        if (function_exists('tidy_setopt')) { // Tidy 1.0
            foreach ($tidy_config as $field=>$value) {
                tidy_setopt($field, $value);
            }
            $html = tidy_repair_string($html);
        } else { // Tidy 2.0
            $html = tidy_repair_string($html, $tidy_config);
        }
    }
 
    // if we cannot find the body open & close tags, return raw file
    if (!preg_match("/<body(\s[^>]*|)>(.*)<\/body>/si",$html,$matches))
      return $html;

    return $matches[2];
  }


  /** Return the path of a spool "item" (file or directory).
   *
   * @param parent parent directory (optional)
   * @param entry the item
   */
  function spoolPath($parent="",$entry="") {
    $this->checkPath($parent,$entry);
    return $this->datadir.($parent ? "/$parent": "") . ($entry ? "/$entry" : "");
  }

}

?>
