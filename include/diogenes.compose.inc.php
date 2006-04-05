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

/** Protect PHP code.
 */
function phpProtect($input)
{
  $splits = preg_split("/(<\?php|\?>)/",$input,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  $output = "";

  while ($block = array_shift($splits)) {
    if ($block == "<?php") {
      $code = array_shift($splits);
      $end = array_shift($splits);
      if ($end != "?>")
        die("phpProtect : parse error");
      $output .= "{PHP:".base64_encode($code).":PHP}";
    } else {
      $output .= $block;
    }
  }

  return $output;
}


/** Unprotect PHP code.
 */
function phpUnprotect($input)
{
  $splits = preg_split("/({PHP:.+:PHP})/",$input,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  $output = "";

  foreach ($splits as $block) {
    if (preg_match("/{PHP:(.+):PHP}/",$block,$match)) {
      $output .= "<?php".base64_decode($match[1])."?>";
    } else {
      $output .= $block;
    }
  }

  return $output;
}


/** Convert XHTML-compliant tags to plain HTML.
 */
function xhtmlToHtml($input)
{
  return html_accent(preg_replace("/<(br|img|input|p)( [^\/]*)?\/>/","<\$1\$2>",$input));
}


/** Restore XHTML-compliant tags.
 */
function htmlToXhtml($input)
{
  return preg_replace("/<(br|img|input)( [^>]+)?>/","<\$1\$2/>",$input);
}

?>
