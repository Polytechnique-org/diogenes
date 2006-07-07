<?php
/*
 * Copyright (C) 2003-2006 Polytechnique.org
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

/** Protect a text block and encode it as base64.
 */
function textProtectTag($tag_open, $tag_close, $prot_open, $prot_close, $input)
{
  $splits = preg_split("/($tag_open|$tag_close)/",$input,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  
  $output = "";
  $depth = 0;
  while ($block = array_shift($splits)) {
    if (preg_match("/^$tag_open$/", $block)) {
      if ($depth == 0) {
        $save = "";
      }
      $save .= $block;
      $depth++;
    } else if ($depth > 0) {
      $save .= $block;
      if (preg_match("/^$tag_close$/", $block))
      {
        $depth--;
        if ($depth == 0)
        {
           $output .= $prot_open.base64_encode($save).$prot_close;
           $save = "";
        }
      }
    } else {
      $output .= $block;
    }
  }

  return $output;
}


/** Unprotect base64 blocks.
 */
function textUnprotectTags($prot_open, $prot_close, $input)
{
  $splits = preg_split("/($prot_open.+$prot_close)/",$input,-1,PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
  $output = "";

  foreach ($splits as $block) {
    if (preg_match("/^$prot_open(.+)$prot_close$/", $block, $match)) {
      $output .= base64_decode($match[1]);
    } else {
      $output .= $block;
    }
  }

  return $output;
}


/** Protect HTML code from Textism.
 */
function htmlProtectFromTextism($input)
{
  return textProtectTag("<protect>", "<\/protect>", "{NOP:", ":NOP}", $input);
}


/** Restore HTML code that was protected from Textism.
 */
function htmlUnprotectFromTextism($input)
{
  $input = textUnprotectTags("<p>\s*{NOP:", ":NOP}\s*<\/p>", $input);
  $input = textUnprotectTags("{NOP:", ":NOP}", $input);
  return preg_replace('/<\/?protect>/', "", $input);
}


/** Protect PHP code.
 */
function phpProtect($input)
{
  return textProtectTag("<\?php", "\?>", "{PHP:", ":PHP}", $input);
}


/** Unprotect PHP code.
 */
function phpUnprotect($input)
{
  return textUnprotectTags("{PHP:", ":PHP}", $input);
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
