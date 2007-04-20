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


require_once 'HTTP/WebDAV/Server.php';
require_once 'diogenes/diogenes.misc.inc.php';
require_once 'diogenes.webdav.logger.inc.php';
require_once 'Barrel.php';
require_once 'Barrel/Page.php';

/**
 * Filesystem access using WebDAV
 *
 */
class DiogenesWebDAV extends HTTP_WebDAV_Server
{
  /** The Diogenes_Barrel representing the barrel */
  var $barrel;

  /** Debugging log file handle */
  var $debug_handle;

  /** Capture out to log ? */
  var $debug_capture;
  
  /**
   * The constructor
   */
  function DiogenesWebDAV()
  {
    global $globals;

    // call parent constructor
    $this->HTTP_WebDAV_Server();

    // construct new session each time, there is no practical way 
    // of tracking sessions with WebDAV clients
    $_SESSION['session'] = new $globals->session;

    $this->debug_capture = $globals->debugwebdav_capture;
    
    // init debug log if necessary
    if ($globals->debugwebdav) 
    {
      $this->debugInit($globals->debugwebdav);
      register_shutdown_function(array(&$this, 'debugClose'));
    }
      
  }

  
  /** 
   * Hack to support barrels on virtualhosts.
   *
   * @param path
   * @param for_html
   */    
  function _urlencode($path, $for_html=false) {
    if ($this->barrel->vhost) {
      $path = preg_replace("/^(.*)\/site\/{$this->barrel->alias}\/(.*)/", "/\$2",$path);
    }
    return parent::_urlencode($path, $for_html);
  }

  
  /**
   * Perform authentication
   *
   * @param  type HTTP Authentication type (Basic, Digest, ...)
   * @param  user Username
   * @param  pass Password
   */
  function check_auth($type, $user, $pass)
  {
    global $globals;

    // WebDAV access is only granted for users who log in
    if (!$_SESSION['session']->doAuthWebDAV($user,$pass))
        return false;

    // we retrieve the user's permissions on the current barrel
    $_SESSION['session']->setBarrelPerms($this->barrel->alias);

    return true;
  }


  
  /**
   * Handle a request to copy a file or directory.
   *
   * @param options
   */
  function copy($options)
  {
    // we do not allow copying files
    return "403 Forbidden";
  }


  

  /** 
   * Open the debugging log file
   */
  function debugInit($debugfile)
  {
    if (empty($debugfile))
      return;
  
    // open the log file
    if ($fp = fopen($debugfile, "a")) 
    {
      $this->debug_handle = $fp;
    }
  }


  /** 
   * Log a debugging message
   */
  function debug($func, $msg)
  {
    if (isset($this->debug_handle))
    {
      $out = sprintf("[%s] %s : %s\n",date("H:i:s"), $func, $msg);
      fputs($this->debug_handle, $out);
    }
  }
 
   
  /** 
   * Close the debugging log file
   */
  function debugClose()
  {
    if (isset($this->debug_handle))
    {
      fclose($this->debug_handle);
    }
  }

  
  /**
   * Handle a request to delete a file or directory.
   *
   * @param options
   */
  function delete($options)
  {
    global $globals;
    $pathinfo = $this->parsePathInfo($options["path"]);
    
    // get the page ID and write permissions for the current path
    $pid = $this->barrel->getPID($pathinfo['dir']);
    if (!$pid || !$bpage = Diogenes_Barrel_Page::fromDb($this->barrel, $pid))
    {
      $this->debug('delete', "Could not find directory {$pathinfo['dir']}");
      return false;
    }
    
    // check permissions
    if (!$_SESSION['session']->hasPerms($bpage->props['wperms']))
    {
      $this->debug('delete', "Insufficient privileges (needed : {$bpage->props['wperms']})");
      return "403 Forbidden";
    }

    // create an RCS handle
    $rcs = $this->getRcs();
    $rcs->del($pid,$pathinfo['file']);
    return "204 No content";
  }

  
  /**
   * Return information about a file or directory.
   *
   * @param fspath path of the filesystem entry
   * @param uri the address at which the item is visible
   */
  function fileinfo($fspath, $uri)
  {
    global $globals;

    $file = array();
    $file["path"]= $uri;

    $file["props"][] = $this->mkprop("displayname", strtoupper($uri));

    $file["props"][] = $this->mkprop("creationdate", filectime($fspath));
    $file["props"][] = $this->mkprop("getlastmodified", filemtime($fspath));

    if (is_dir($fspath)) {
      $file["props"][] = $this->mkprop("getcontentlength", 0);
      $file["props"][] = $this->mkprop("resourcetype", "collection");
      $file["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");
    } else {
      $file["props"][] = $this->mkprop("resourcetype", "");
      $file["props"][] = $this->mkprop("getcontentlength", filesize($fspath));
      if (is_readable($fspath)) {
        $file["props"][] = $this->mkprop("getcontenttype", get_mime_type($fspath));
      } else {
        $file["props"][] = $this->mkprop("getcontenttype", "application/x-non-readable");
      }
    }
    return $file;
  }


  /**
   * Handle a GET request.
   *
   * @param options
   */
  function GET(&$options)
  {
    global $globals;
    $pathinfo = $this->parsePathInfo($options["path"]);
    
    // get the page ID and read permissions for the current path
    $pid = $this->barrel->getPID($pathinfo['dir']);
    if (!$pid || !$bpage = Diogenes_Barrel_Page::fromDb($this->barrel, $pid))
    {
      $this->debug('PROPFIND', "Could not find directory {$pathinfo['dir']}");
      return false;
    }
    
    // check permissions
    if (!$_SESSION['session']->hasPerms($bpage->props['perms']))
    {
      $this->debug('PROPFIND', "Insufficient privileges (needed : {$bpage->props['perms']})");
      return "403 Forbidden";
    }

    // create stream
    $fspath = $this->barrel->spool->spoolPath($pid,$pathinfo['file']);
    if (file_exists($fspath)) {             
      $options['mimetype'] = get_mime_type($fspath); 
                
      // see rfc2518, section 13.7
      // some clients seem to treat this as a reverse rule
      // requiering a Last-Modified header if the getlastmodified header was set
      $options['mtime'] = filemtime($fspath);
      $options['size'] = filesize($fspath);
            
      // TODO check permissions/result
      $options['stream'] = fopen($fspath, "r");

      return true;
    } else {
      return false;
    }               
  }

    
  /** 
   * Return an RCS handle.
   */
  function getRcs()
  {
    global $globals;
    return new $globals->rcs($this,$this->barrel->alias,$_SESSION['session']->username);
  }


  /**
   * PROPFIND method handler
   *
   * @return void
   */
  function http_PROPFIND() 
  {
    $this->debug('http_PROPFIND', "called");
    return parent::http_PROPFIND();
  }
  
        
  /** Report an information. Needed for RCS operations.
   *
   * @param msg
   *
   * @see Diogenes_VCS_RCS
   */
  function info($msg) {
    // we do nothing
  }


  /**
   * Die with a given error message. Needed for RCS operations.
   *
   * @param msg
   *
   * @see Diogenes_VCS_RCS
   */
  function kill($msg) {
    $this->http_status("400 Error");
    exit;
  }


  /**
   * Record an action to the log. Needed for RCS operations.
   *
   * @param action
   * @param data
   *
   * @see Diogenes_VCS_RCS
   */
  function log($action,$data) {
    if (isset($_SESSION['log']) && is_object($_SESSION['log']))
      $_SESSION['log']->log($action,$data);
  }

  
  /**
   * Handle an MKCOL (directory creation) request.
   *
   * @param options
   */
  function MKCOL($options)
  {
    // we do not allow directory creations
    return "403 Forbidden";
  }


  /**
   * Handle a request to move/rename a file or directory.
   *
   * @param options
   */
  function move($options)
  {
    // we do not allow moving files
    return "403 Forbidden";
  }


  /** 
   * Break down a PATH_INFO into site, page id and file for WebDAV
   * We accept a missing trailing slash after the directory name.
   *
   * @param path the path to parse
   */
  function parsePathInfo($path) {
    global $globals;

    $this->debug('parsePathInfo', "path : $path");
    if (empty($path) || !preg_match("/^\/([^\/]+)\/webdav(\/((.+)\/)?([^\/]*))?$/",$path,$asplit))
      return false;

    $split['alias'] = $asplit[1];
    $split['dir'] = isset($asplit[4]) ? $asplit[4] : "";
    $split['file'] = isset($asplit[5]) ? $asplit[5] : "";
 
    // check that what we considered as a file is not in fact a directory
    // with a missing trailing slash
    /*
    if ( empty($split['dir']) and
        !empty($split['file']) and
        (mysql_num_rows($globals->db->query("select location from {$split['alias']}_page where location='{$split['file']}'"))>0))
    {
      $split['dir'] = $split['file'];
      $split['file'] = "";
    }
    */
    return $split;
  }

/**
   * Handle a PROPFIND request.
   *
   * @param options
   * @param files
   */
  function PROPFIND($options, &$files)
  {
    global $globals;
    $pathinfo = $this->parsePathInfo($options["path"]);

    // get the page ID and read permissions for the current path
    $pid = $this->barrel->getPID($pathinfo['dir']);
    if (!$pid || !$bpage = Diogenes_Barrel_Page::fromDb($this->barrel, $pid))
    {
      $this->debug('PROPFIND', "Could not find directory {$pathinfo['dir']}");
      return false;
    }
    
    // check permissions
    if (!$_SESSION['session']->hasPerms($bpage->props['perms']))
    {
      $this->debug('PROPFIND', "Insufficient privileges (needed : {$bpage->props['perms']})");
      return "403 Forbidden";
    }
    
    // get absolute fs path to requested resource
    $fspath = $this->barrel->spool->spoolPath($pid,$pathinfo['file']);

    // sanity check
    if (!file_exists($fspath)) {
      return false;
    }

    // prepare property array
    $files["files"] = array();

    // store information for the requested path itself
    $files["files"][] = $this->fileinfo($fspath, $options["path"]);

    // information for contained resources requested?
    if (!$pathinfo['file'] && !empty($options["depth"]))  { 

      // make sure path ends with '/'
      if (substr($options["path"],-1) != "/") {
        $options["path"] .= "/";
      }

      // list the sub-directories
      $res = $globals->db->query("select PID,location from {$this->barrel->table_page} where parent='$pid'");
      while (list($dpid,$dloc) = mysql_fetch_row($res)) {
        $dpath = $this->barrel->spool->spoolPath($dpid);
        $duri = $options["path"].$dloc;
        $files["files"][] = $this->fileinfo($dpath, $duri);
      }
      mysql_free_result($res);
      
      // now list the files in the current directory
      $handle = @opendir($fspath);
      if ($handle) {
        // ok, now get all its contents
        while ($filename = readdir($handle)) {
          if ($filename != "." && $filename != "..") {
            $fpath = $this->barrel->spool->spoolPath($pid,$filename);
            $furi = $options["path"].$filename;
            if (!is_dir($fpath))
              $files["files"][] = $this->fileinfo ($fpath, $furi);
          }
        }
      }
    }

    // ok, all done
    return true;
  }

  /**
   * Handle a PROPPATCH request.
   *
   * @param options
   */
  function proppatch(&$options)
  {
    global $prefs, $tab;
     
    $msg = "";

    $path = $options["path"];

    $dir = dirname($path)."/";
    $base = basename($path);

    foreach($options["props"] as $key => $prop) {
      if($ns == "DAV:") {
        $options["props"][$key][$status] = "403 Forbidden";
      }
    }

    return "";
  }

    /**
   * Handle a PUT request.
   *
   * @param options
   */
  function PUT(&$options)
  {
    global $globals;
    $pathinfo = $this->parsePathInfo($options["path"]);

    // we do not support multipart
    if (!empty($options["ranges"]))
      return "501";
    
    // get the page ID and write permissions for the current path
    $pid = $this->barrel->getPID($pathinfo['dir']);
    if (!$pid || !$bpage = Diogenes_Barrel_Page::fromDb($this->barrel, $pid))
    {
      $this->debug('PROPFIND', "Could not find directory {$pathinfo['dir']}");
      return false;
    }
    
    // check permissions
    if (!$_SESSION['session']->hasPerms($bpage->props['wperms']))
    {
      $this->debug('PROPFIND', "Insufficient privileges (needed : {$bpage->props['wperms']})");
      return "403 Forbidden";
    }

    // create an RCS handle
    $rcs = $this->getRcs();
    $options["new"] = !file_exists($rcs->rcsFile($pid,$pathinfo['file']));

    // read content
    $content = "";    
    while (!feof($options["stream"])) {
      $content .= fread($options["stream"], 4096);
    }

    // if this is a barrel page, strip extraneous tags
    if ($pathinfo['file'] == $globals->htmlfile)
      $content = $rcs->importHtmlString($content);
  
    // perform commit
    if (!$rcs->commit($pid,$pathinfo['file'],$content,"WebDAV PUT of {$pathinfo['file']}"))
      return "400 Error";
    
    // if this is Word master document, do the HTML conversion
    if ($globals->word_import && $pathinfo['file'] == $globals->wordfile) {
      $myfile = $this->barrel->spool->spoolPath($pid,$globals->wordfile);
      $rcs->importWordFile($pid, $globals->htmlfile, $myfile);
    }
    
    return $options["new"] ? "201 Created" : "204 No Content";
  }

  
  /**
   * Serve a webdav request
   */
  function ServeRequest()
  {
    global $globals;
 
    // break down path into site and location components
    if (!($pathinfo = $this->parsePathInfo($_SERVER['PATH_INFO'])))
    {
      $this->http_status("404 not found");
      exit;
    }
        
    // Retrieve site-wide info from database    
    $this->barrel = new Diogenes_Barrel($pathinfo['alias'], $this);  
    if (!$this->barrel->alias)
    {
      $this->debug("Could not find barrel '{$pathinfo['alias']}'");
      $this->http_status("404 not found");
      exit;      
    }
    $this->debug('ServeRequest', "barrel : ".$this->barrel->alias);    
    
    // Debugging info
    $props = array( 'REQUEST_METHOD', 'REQUEST_URI', 'SCRIPT_NAME', 'PATH_INFO');
    foreach ($props as $prop) {
      $this->debug('ServeRequest', "$prop : ". $_SERVER[$prop]);
    }    

    // Here we perform some magic on the script name (on PHP 4.3.10)
    // If the script is addressed by REQUEST_URI /site/foo/bar/ :
    //  - Apache 1.x returns /site as the SCRIPT_NAME
    //  - Apache 2.x returns /site.php as the SCRIPT_NAME
    //
    // We set SCRIPT_NAME to match the REQUEST_URI minus the PATH_INFO
    //
    $_SERVER['SCRIPT_NAME'] = substr($_SERVER['REQUEST_URI'], 0, - strlen($_SERVER['PATH_INFO']));        
    $this->debug('ServeRequest', "SCRIPT_NAME(mod) : ". $_SERVER['SCRIPT_NAME']);
    
    // turn on output buffering
    ob_start();      
    
    // let the base class do all the work   
    parent::ServeRequest();
    
    // stop output buffering
    $out = ob_get_contents();
    ob_end_clean();

    // to support barrels on virtualhosts, we rewrite the URLs    
    if ($this->barrel->vhost)
    {    
      $ohref = (@$_SERVER["HTTPS"] === "on" ? "https:" : "http:");
      $ohref.= "//".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
      $ohref.= "/" . $this->barrel->alias . "/webdav/";
      $this->debug('ServeRequest', "ohref : ".$ohref);      
        
      $vhref = (@$_SERVER['HTTPS'] === "on" ? "https:" : "http:");
      $vhref.= "//".$this->barrel->vhost . "/webdav/";
      $this->debug('ServeRequest', "vhref : ".$vhref);  
      
      $out = str_replace($ohref, $vhref, $out);
      $out = str_replace('<D:displayname>/'.strtoupper($this->barrel->alias).'/WEBDAV/', '<D:displayname>/WEBDAV/', $out);
    }
    
    // send output
    echo $out;
        
    // if requested, log the output that is sent to the client
    if ($this->debug_capture)
    {
      $this->debug('ServeRequest', "out\n--- begin --\n$out--- end ---\n");    
    }    
    
  }


}

?>
