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


require_once 'VCS/Spool.php';
// dependency on PEAR
require_once 'System.php';

/** This class handles Diogenes RCS operations.
 */
class Diogenes_VCS_RCS extends Diogenes_VCS_Spool {
  /** Absolute directory location for the barrel's RCS files. */
  var $rcsdir;

  /** The username */
  var $login;
  /** The constructor.
   *
   * @param caller
   * @param alias
   * @param login the current user's login
   * @param init should create this module?
   */
  function Diogenes_VCS_RCS(&$caller,$alias,$login,$init = false) {
    global $globals;
    $this->Diogenes_VCS_Spool($caller,$alias);
    $this->rcsdir = "{$globals->rcsroot}/$alias";
    $this->login = $login;

    // if we were asked to, created directories
    if ($init) {
      if (!is_dir($this->rcsdir))
        mkdir($this->rcsdir, 0700);
      if (!is_dir($this->datadir))
        mkdir($this->datadir, 0700);
    }

    // check RCS directory
    if (!is_dir($this->rcsdir) || !is_writable($this->rcsdir))
      $this->kill("'{$this->rcsdir}' is not a writable directory");

    // check spool directory
    if (!is_dir($this->datadir) || !is_writable($this->datadir))
      $this->kill("'{$this->datadir}' is not a writable directory");
  }


  /** Return the path of an RCS "item" (file or directory).
  *
  * @param parent parent directory (optional)
  * @param entry the item
  */
  function rcsPath($parent="",$entry="") {
    $this->checkPath($parent,$entry);
    return $this->rcsdir.($parent ? "/$parent": "") . ($entry ? "/$entry" : "");
  }


  /** Return the path of an RCS file (something,v).
  *
  * @param dir parent directory
  * @param file the RCS entry
  */
  function rcsFile($dir,$file) {
    return $this->rcsPath($dir,$file).",v";
  }

  
  /** Check whether a file is registered in RCS.
   *
   * @param dir parent directory
   * @param file the RCS entry
   */
  function checkFile($dir, $file) {
    return is_file($this->rcsFile($dir, $file));
  }

  
  /** Perform sanity check on an RCS directory
   *  and the corresponding checkout in the spool
   *
   * @param dir
   */
  function checkDir($dir) {
    return is_dir($this->rcsPath($dir))
        && is_writable($this->rcsPath($dir))
        && is_dir($this->spoolPath($dir))
        && is_writable($this->spoolPath($dir));
  }


  /** Do a checkout of an RCS item to a given location.
  *
  * @param dir parent directory
  * @param file the RCS entry
  * @param rev the revision to check out
  * @param output the directory to which we want to perform the checkout
  */
  function checkout($dir,$file,$rev,$output)
  {
    $this->info("RCS : checkout out $file ($rev)..");
    $rfile = $this->rcsFile($dir,$file);
    if ($this->cmdExec("co -q -r".escapeshellarg($rev)." ".escapeshellarg($rfile)." ".escapeshellarg("$output/$file")))
    {
      $this->info("RCS : Error, checkout failed!");
      $this->info($this->cmdStatus());
      return;
    }         
    return "$output/$file";
  }
  

  /** Commit an RCS item. Returns true for succes, false for an error.
  *
  * @param dir parent directory
  * @param file the RCS entry
  * @param content the contents of the new revision
  * @param message the log message for this revision
  */
  function commit($dir,$file,$content,$message="")
  {
    $this->info("RCS : checking in '$file'..");

    // check directories
    if (!$this->checkDir($dir)) {
        // error
        $this->info("RCS : Error, RCS sanity check for '$dir' failed!");
        return false;
    }

    // log commit attempt
    $this->log("rcs_commit","{$this->alias}:$dir/$file:$message");

    $sfile = $this->spoolPath($dir,$file);
    $rfile = $this->rcsFile($dir,$file);

    // if the RCS file does not exist, create it
    if (!file_exists($rfile)) {
      if ($this->cmdExec("echo '' | rcs -q -i ".escapeshellarg($rfile)))
      {
        // error
        $this->info("RCS : Error, could not initialise RCS file '$rfile'!");
        $this->info($this->cmdStatus());
        return false;
      }
    }

    // lock the spool file
    if ($this->cmdExec("co -q -l ".escapeshellarg($rfile)." ".escapeshellarg($sfile)))
    {
        // error
        $this->info("RCS : Error, could not get RCS lock on file '$file'!");
        $this->info($this->cmdStatus());
        return false;
    }
    if ($fp = fopen($sfile,"w")) {
      fwrite($fp,$content);
      fclose($fp);
      
      if ($this->cmdExec("ci -q -w".escapeshellarg($this->login). ($message ? " -m".escapeshellarg($message) : "").
             " ". escapeshellarg($sfile). " ". escapeshellarg($rfile)))
      {
        // error
        $this->info("RCS : Error, checkin failed!");
        $this->info($this->cmdStatus());        
        return false;
      }
      
      if ($this->cmdExec("co -q ".escapeshellarg($rfile)." ".escapeshellarg($sfile)))
      {
        // error
        $this->info("RCS : Error, checkout after checkin failed!");
        $this->info($this->cmdStatus());        
        return false;
      }
    }
    return true;
  }


  
 /** Make a copy of an RCS item to a given location.
  *
  * @param sdir the source directory
  * @param sfile the source RCS entry
  * @param ddir the destination directory
  * @param dfile the destination RCS entry
  */
  function copy($sdir,$sfile,$ddir,$dfile)
  {    
    $this->info("RCS : copying '$sfile' to '$ddir/$dfile'..");
    
    $spath = $this->spoolPath($sdir, $sfile);
    if (!is_file($spath)) {
      $this->info("Error: source file '$spath' does not exist!");
      return false;
    }
    if (!$this->checkDir($ddir)) 
    {
      $this->info("Error: directory '$ddir' does not exist!");
      return false;
    }
    if ($this->checkFile($ddir, $dfile))
    {
      $this->info("Error: file '$dfile' already exists in '$ddir'!");
      return false;    
    }
    return $this->commit($ddir,$dfile,
                        file_get_contents($spath),
                        "copied from '$ddir/$sfile'");
  }
   
     
  /** Delete an RCS file and its corresponding spool entry.
  *
  * @param dir parent directory
  * @param file the RCS entry
  */
  function del($dir,$file) {
    $this->info("RCS : deleting '$file'..");
    $this->log("rcs_delete","{$this->alias}:$dir/$file");
    @unlink($this->spoolPath($dir,$file));
    @unlink($this->rcsFile($dir,$file));
  }


  /** Retrieve differences between two version of a file.
   *
   * @param dir parent directory
   * @param file the RCS entry
   * @param r1 the first revision
   * @param r2 the second revision
   */
  function diff($dir,$file,$r1,$r2)
  {
    $rfile = $this->rcsFile($dir,$file);
    $this->info("RCS : diffing '$file' ($r1 to $r2)..");
    $this->cmdExec("rcsdiff  -r".escapeshellarg($r1). " -r".escapeshellarg($r2)." ".escapeshellarg($rfile));
    return $this->cmd_output;
  }


  /** Converts a Word document to HTML and commits the resulting
   *  HTML and images.
   *
   * @param dir
   * @param htmlfile
   * @param wordfile
   */
  function importWordFile($dir,$htmlfile,$wordfile)
  {
    global $globals;
    
    if (!$globals->word_import) {
      $this->info("Error : support for word import is disabled!");
      return false;
    }

    $func = "importWordFile_{$globals->word_import}";
    
    if (!method_exists($this, $func))
    {
      $this->info("Error : the utility '$globals->word_import' is not supported!");
      return false;
    }
    
    return $this->$func($dir, $htmlfile, $wordfile);
  }


  /** Converts a Word document to HTML using wvHtml and commits the resulting
   *  HTML and images.
   *
   * @param dir
   * @param htmlfile
   * @param wordfile
   */
  function importWordFile_wvHtml($dir,$htmlfile,$wordfile)
  {
    $tmphtmlfile = "importWord.html";
    if (($tmpdir = System::mktemp('-d')) == false) {
      $this->info("Error : could not create temporary directory!");
      return false;
    }

    if ($this->cmdExec("wvHtml --targetdir=".escapeshellarg($tmpdir).
           " --charset=iso-8859-15 ".
           escapeshellarg($wordfile)." ".escapeshellarg($tmphtmlfile)))
    {
      $this->info("Error : wvHtml returned an error!");
      $this->info($this->cmdStatus());
      return false;
    }

    if (!$dh = opendir($tmpdir)) {
      $this->info("Error : could not find temporary directory '$tmpdir'!");
      return false;
    }
    
    // process the files generated by wvHtml
    $ok = true;
    while (($myentry = readdir($dh)) != false) {
      if (is_file($myfile = "$tmpdir/$myentry")) {
        if ($myentry == $tmphtmlfile) {
          $ok = $ok &&
          $this->commit($dir,$htmlfile,
                        $this->importHtmlString(file_get_contents($myfile)),
                        "Word file import");
        } else {
          $ok = $ok &&
          $this->commit($dir,$myentry,file_get_contents($myfile),
                        "Word file import");
        }
      }
    }
    closedir($dh);

    return $ok;
  }


  /** Returns raw log entries for an RCS item.
   *
   * @param dir parent directory
   * @param file the RCS entry
   */
  function logEntries($dir,$file) {
    $rfile = $this->rcsFile($dir,$file);
    $this->cmdExec("rlog ".escapeshellarg($rfile));
    return $this->cmd_output;
  }


  /** Parse the log entries for an RCS item into an array.
   *
   * @param dir parent directory
   * @param file the RCS entry
   */
   function logParse($dir,$file)
   {
    // get the log, drop last 2 lines
    $lines = $this->logEntries($dir,$file);
    array_pop($lines);
    array_pop($lines);

    // split into revision, drop first block
    $revs = split("----------------------------\n", implode("\n",$lines));
    array_shift($revs);

    // parse info about the revisions
    $revinfo = array();
    foreach ($revs as $rev) {
      $myrev = array();
      $lines = explode("\n",$rev);
      preg_match("/^revision (.+)$/",array_shift($lines),$res);
      $myrev['rev'] = $res[1];
      preg_match("/^date: ([^;]+);  author: ([^;]+); .*$/",array_shift($lines),$res);
      $myrev['date'] = $res[1];
      $myrev['author'] = $res[2];
      $myrev['log'] = implode("\n",$lines);
      array_push($revinfo,$myrev);
    }
    return $revinfo;
  }


 /** Move an RCS item to a given location.
  *
  * @param sdir the source directory
  * @param sfile the source RCS entry
  * @param ddir the destination directory
  * @param dfile the destination RCS entry
  */
  function move($sdir,$sfile,$ddir,$dfile)
  {    
    $this->info("RCS : moving '$sfile' to '$ddir/$dfile'..");
    
    
    // check source files
    $spath = $this->spoolPath($sdir, $sfile);
    $srpath = $this->rcsFile($sdir, $sfile);    
            
    if (!is_file($spath)) {
      $this->info("Error: source file '$spath' does not exist!");
      return false;
    }
    
    if (!is_file($srpath)) {
      $this->info("Error: source RCS file '$srpath' does not exist!");
      return false;
    }
    
    // check destination
    $dpath = $this->spoolPath($ddir, $dfile);
    $drpath = $this->rcsFile($ddir, $dfile);    
    
    if (!$this->checkDir($ddir)) 
    {
      $this->info("Error: directory '$ddir' does not exist!");
      return false;
    }
    
    if (file_exists($dpath)) {
      $this->info("Error: file '$dfile' already exists in '$ddir'!");
      return false;    
    }
    
    if (file_exists($drpath)) {
      $this->info("Error: file '".basename($drpath)."' already exists in '$ddir'!");
      return false;    
    }
    
    if (!rename($spath, $dpath))
    {
      $this->info("Error: failed to move '".basename($spath)."' to '".basename($dpath)."' in '$ddir'!");
      return false;        
    }
    
    if (!rename($srpath, $drpath))
    {
      $this->info("Error: failed to move '".basename($srpath)."' to '".basename($drpath)."' in '$ddir'!");
      return false;        
    }

        
    //$this->log("rcs_move","{$this->alias}:$dir/$file");    
    
    return true;
  }
 
 
  /** Add a new RCS-managed directory.
   *
   * @param parent the parent directory
   * @param dir the directory to add
   */
  function newdir($parent,$dir)
  {
    @mkdir($this->rcsPath($parent,$dir),0700);
    @mkdir($this->spoolPath($parent,$dir),0700);
  }


  /** Retrieve differences between two version of a file and prepare for output.
   *
   * @param dir parent directory
   * @param file the RCS entry
   * @param r1 the first revision
   * @param r2 the second revision
   */
  function dispDiff($dir,$file,$r1,$r2)
  {
    $lns = "[0-9]+|[0-9]+,[0-9]+";

    // get diff, strip any leading comments
    $lines = $this->diff($dir,$file,$r1,$r2);    
    $line = "";
    while (!preg_match("/^($lns)([acd])($lns)/",$line))
      $line = array_shift($lines);
    array_unshift($lines,$line);
    $raw = implode("\n",$lines);

    $blocks = preg_split("/($lns)([acd])($lns)\n/",$raw,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $out = array();

    $lold = array_shift($blocks);
    while ($lold!='')
    {
      $type = array_shift($blocks);
      $lnew = array_shift($blocks);
      $diff = array_shift($blocks);

      switch ($type) {
      case 'c':
        list($a,$b) = split("---\n",$diff,2);
        break;
      case 'a':
        $a = "";
        $b = $diff;
        break;
      case 'd':
        $a = $diff;
        $b = "";
        break;
      }
      array_push($out,array($lold,$type,$lnew,$a,$b));
      $lold = array_shift($blocks);
    }
    //array_push($out,array($type,$a,$b));
    return $out;
  }


  /** Return an RCS-managed directory.
   *
   * @param dir the directory
   * @param loc the visible location for the directory
   * @param canedit can we edit files in this directory?
   */
  function dispDir($dir,$loc,$canedit) {
    $entries = array();

    if ($pdir = @opendir($this->rcsPath($dir))) {
      while ( ($file = readdir($pdir)) !== false) {
        if ( ($file != ".") && ($file != "..") )
        {
          $entry = $this->dispEntry($dir,$loc,$file,$canedit);
          if (!empty($entry)) 
            array_push($entries, $entry);
        }
      }
      closedir($pdir);
    }
    return $entries;
  }


  /** Returns an RCS "item" (file or directory).
  *
  * @param dir parent directory
  * @param loc visible location for parent directory
  * @param file the RCS "item"
  * @param canedit can we edit files this entry?
  */
  function dispEntry($dir,$loc,$file,$canedit) {
    global $globals;

    $view = $edit = $del = $size = $rev = "";

    $myitem = $this->rcsPath($dir,$file);
    
    // check the RCS entry exists
    if (!file_exists($myitem)) {
      $this->info("RCS entry '$myitem' does not exist!");
      return;
    }
    
    if (is_dir($myitem))
    {
    // this is a directory, this should not happen!
      $this->info("Unexpected directory in RCS, skipping : '$myitem'");
      return;
    }
    else if (substr($file,-2) == ",v")
    {
    // this is an RCS file
      $file = substr($file,0,-2);
          
      // check we have a working copy of this item
      $spoolitem = $this->spoolPath($dir,$file);
      if (!is_file($spoolitem)) 
      {
        $this->info("Could not find working copy '$spoolitem'!");
        $size = "";
        $icon = "";
      } else {      
        $size = $this->dispSize(filesize($spoolitem));
        $icon = $globals->icons->get_mime_icon($spoolitem);        
      }
            
      // revision info
      $myrev = array_shift($tmparr = $this->logParse($dir,$file));
      
      return array(
        "icon" => $icon,
        "file" => $file,
        "rev" => array($myrev['rev'],$rev),
        "date" => $myrev['date'],
        "author" => $myrev['author'],
        "size" => $size
      );
      
    }
    else 
    {
      $this->info("Unknown RCS entry type : '$myitem'");
      return;
    }

  }


  /** Format a file size for display.
  *
  * @param size the size, in bytes
  */
  function dispSize($size)
  {
    if ($size < 1000)
      return "$size B";
    else if ($size < 1000000)
      return floor($size/1000)." kB";
    else
      return floor($size/1000000)." MB";
  }

}

?>
