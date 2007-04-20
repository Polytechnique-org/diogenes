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


require_once 'VCS/RCS.php';

/** This class handles Diogenes CVS operations.
 */
class Diogenes_VCS_CVS extends Diogenes_VCS_RCS {
  /** CVS command options */
  var $cvsopt;

  /** Port for the pserver */
  var $port = 9000;

  /** The constructor.
   *
   * @param caller
   * @param alias
   * @param login the current user's login
   * @param init should create this module?
   */
  function Diogenes_VCS_CVS(&$caller,$alias,$login,$init = false) {
    global $globals;
    // call parent constructor
    $this->Diogenes_VCS_RCS($caller,$alias,$login,$init);

    // set CVS environment, fire up pserver
    // the pserver suicides after 5mn of inactivity
    $this->cvsopt = "-d :pserver:{$this->login}@localhost:{$this->port}{$globals->rcsroot}";
    putenv("CVS_PASSFILE={$globals->spoolroot}/.cvspass");
    if ($fp = popen(escapeshellcmd("perl ".escapeshellarg("{$globals->root}/cvs.pl")." -p ".escapeshellarg($this->port)." -r ".escapeshellarg($globals->rcsroot)." -f -m -s 300"),"r"))
      pclose($fp);

    // if asked to, do checkout of the module
    if ($init) {
      chdir($globals->spoolroot);
      if ($this->cmdExec("cvs {$this->cvsopt} co ".escapeshellarg($alias))) 
      {    
        $this->info($this->cmdStatus());    
        $this->kill("CVS : Error, checking out CVS module '$alias' failed!");
      }
    }

    // check we have a correct checkout
    if ( !is_dir($this->spoolPath("CVS"))
      || !is_writable($this->spoolPath("CVS")) )
      $this->kill("CVS : Error, checkout for CVS module '$alias' is invalid!");

  }


  /** Perform sanity check on a CVS directory
   *  and the corresponding checkout in the spool
   *
   * @param dir
   */
  function checkDir($dir) {
    return is_dir($this->rcsPath($dir))
        && is_writable($this->rcsPath($dir))
        // spool check
        && is_dir($this->spoolPath($dir))
        && is_writable($this->spoolPath($dir))
        && is_dir($this->spoolPath($dir,"CVS"))
        && is_writable($this->spoolPath($dir,"CVS"));
  }


  /** Commit a CVS item. Returns true for succes, false for an error.
  *
  * @param dir parent directory
  * @param file the CVS entry
  * @param content the contents of the new revision
  * @param message the log message for this revision
  */
  function commit($dir,$file,$content,$message="")
  {
    $this->info("CVS : checking in $file..");

    // check directories
    if (!$this->checkDir($dir)) {
        // error
        $this->info("CVS : Error, sanity check for '$dir' failed!");
        return false;
    }

    // log commit attempt
    $this->log("rcs_commit","{$this->alias}:$dir/$file:$message");

    // write to spool file
    $sfile = $this->spoolPath($dir,$file);
    $rfile = $this->rcsFile($dir,$file);

    $fp = fopen($sfile,"w");
    if (!$fp) {
      $this->info("CVS : Error, could not open spool file '$sfile' for writing!");
      return false;
    }
    fwrite($fp,$content);
    fclose($fp);

    chdir($this->spoolPath($dir));

    // if the RCS file does not exist, do a cvs add
    if (!file_exists($rfile)) {
      if ($this->cmdExec("cvs {$this->cvsopt} add ".escapeshellarg($file))) {
        // error
        $this->info("Error: could not do CVS add!");
        $this->info($this->cmdStatus());
        return false;
      }
    } else {
      // do an update to make sure we are up to date
      $this->cmdExec("cvs {$this->cvsopt} up ".escapeshellarg($file));
    }

    $this->cmdExec("cvs {$this->cvsopt} commit -m".escapeshellarg($message)." ".escapeshellarg($file));

    return true;
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
    chdir($this->spoolPath($dir));
    $this->info("CVS : diffing '$file' ($r1 to $r2)..");

    $this->cmdExec("cvs {$this->cvsopt} diff  -r".escapeshellarg($r1)." -r".escapeshellarg($r2)." ".escapeshellarg($file));
    return $this->cmd_output;
  }


 /** Returns raw log entries for a CVS item.
  *
  * @param dir parent directory
  * @param file the RCS entry
  */
  function logEntries($dir,$file) {
    chdir($this->spoolPath($dir));
    $this->cmdExec("cvs {$this->cvsopt} log ".escapeshellarg($file));
    return $this->cmd_output;
  }


  /** Add a new RCS-managed directory.
   *
   * @param parent the parent directory
   * @param dir the directory to add
   */
  function newdir($parent,$dir)
  {
    if (!$this->checkDir($parent))
      return false;

    @mkdir($this->spoolPath($parent,$dir),0700);
    chdir($this->spoolPath($parent));
    $ret = $this->cmdExec("cvs {$this->cvsopt} add ".escapeshellarg($dir));
    return $ret;
  }

}

?>
