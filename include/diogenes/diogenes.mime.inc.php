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


/** Custom implementation of mime_content_type.
 *  It avoids problems encountered with the built-in mime_content_type such as
 *   - CSS files being returned as text/plain
 */
function get_mime_type($filename) {
  // we use the file extension for the basic file types, as file
  // sometimes gets confused
  
  $mime_map = array(
    ".ai"    => "application/postscript", 
    ".arj"   => "application/arj",
    ".asf"   => "video/x-ms-asf",
    ".asr"   => "video/x-ms-asf",
    ".asx"   => "video/x-ms-asf",
    ".au"    => "audio/basic",
    ".avi"   => "video/x-msvideo",
    ".bmp"   => "image/bmp",
    ".bz"    => "application/x-bzip",
    ".bz2"   => "application/x-bzip2",
    ".css"   => "text/css",
    ".dcf"   => "application/vnd.oma.drm.content",
    ".deb"   => "application/x-deb",
    ".dm"    => "application/vnd.oma.drm.message",
    ".doc"   => "application/msword",
    ".dot"   => "application/msword",
    ".dvi"   => "application/x-dvi",
    ".eps"   => "application/postscript",
    ".gif"   => "image/gif",
    ".gz"    => "application/x-gzip",
    ".gzip"  => "application/x-gzip",
    ".hqx"   => "application/mac-binhex40",
    ".html"  => "text/html",
    ".htm"   => "text/html",    
    ".jad"   => "text/vnd.sun.j2me.app-descriptor",
    ".jar"   => "application/java-archive",
    ".jfif"  => "image/jpeg",
    ".jpe"   => "image/jpeg",    
    ".jpeg"  => "image/jpeg",
    ".jpg"   => "image/jpeg",
    ".lyx"   => "text/x-lyx",
    ".midi"  => "audio/midi",
    ".mid"   => "audio/midi",
    ".mp2"   => "audio/mpeg",
    ".mp3"   => "audio/mpeg",
    ".mpg"   => "video/mpeg",
    ".mpe"   => "video/mpeg",
    ".mpeg"  => "video/mpeg",
    ".mov"   => "video/quicktime",
    ".pbm"   => "image/x-portable-bitmap",
    ".pdf"   => "application/pdf",    
    ".png"   => "image/x-png",        
    ".pnm"   => "image/x-portable-anymap",
    ".ppt"   => "application/vnd.ms-powerpoint",
    ".pps"   => "application/vnd.ms-powerpoint",
    ".ps"    => "application/postscript",
    ".qt"    => "video/quicktime",
    ".ra"    => "audio/x-realaudio",
    ".ram"   => "audio/x-pn-realaudio",
    ".rm"    => "audio/x-pn-realaudio",
    ".rtf"   => "application/rtf",
    ".snd"   => "audio/basic",
    ".tar"   => "application/x-tar",      
    ".tex"   => "application/x-tex",            
    ".texi"  => "application/x-texinfo",
    ".texinfo" => "application/x-texinfo",      
    ".tgz"   => "application/x-compressed",
    ".txt"   => "text/plain",
    ".wav"   => "audio/x-wav",
    ".wml"   => "text/vnd.wap.wml",
    ".xls"   => "application/vnd.ms-excel",
    ".xml"   => "text/xml",      
    ".xpm"   => "image/x-xpixmap",
    ".z"     => "application/x-compressed",
    ".zip"   => "application/zip",  
  );
  
  if (empty($mime_type)) {
    $ext = strtolower(strrchr(basename($filename), "."));
    if (isset($mime_map[$ext])) {
      $mime_type = $mime_map[$ext];
    }
  }

  // try to use 'file' to determine mimetype
  if (empty($mime_type)) {
    $fp = popen("file -i '$filename' 2>/dev/null", "r");
    $reply = fgets($fp);
    pclose($fp);

    // the reply begins with the requested filename
    if (!strncmp($reply, "$filename: ", strlen($filename)+2)) {
      $reply = substr($reply, strlen($filename)+2);
      // followed by the mime type (maybe including options)
      if (ereg("^([[:alnum:]_-]+/[[:alnum:]_-]+);?.*", $reply, $matches)) {
        $mime_type = $matches[1];
      }
    }
  }

  // if all else fails, return application/octet-stream
  if (empty($mime_type)) {
    $mime_type = "application/octet-stream";
  }

  return $mime_type;
}


/** Returns the boundary of a MIME multipart content.
 */
function get_mime_boundary($filename)
{
  $fp = fopen($filename, "rb");
  $boundary = "";
  if ($fp && (fscanf($fp, "--%s\r\n", $boundary) == 1) && strlen($boundary))
  {
    $expect = "--$boundary--\r\n";
    fseek($fp, - strlen($expect), SEEK_END);
    $got = fread($fp, strlen($expect));
    if ($got != $expect) {
      $boundary = "";
    }
  }
  fclose($fp);
  return $boundary;
}


/** Determine whether a given MIME type is multipart.
 */
function is_mime_multipart($mimetype)
{
  return (preg_match('/^application\/vnd\.oma\.drm\.message$/', $mimetype));
}

?>
