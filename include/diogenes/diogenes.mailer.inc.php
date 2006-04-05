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


/** Converts and HTML message into plaintext.
 *
 * @param $html the HTML code to convert
 */
function html2plain($html) {
    $text = html_entity_decode($html);
    return trim(strip_tags($text));
}


/** Converts a plaintext message into an HTML message with clickable hyperlinks.
 *
 * @param $text the plain text to convert
 */
function plain2html($text) {
    $html = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]",
                         "<a href=\"\\0\">\\0</a>", $text);
    $html = nl2br($html);
    return "<HTML><BODY>\n$html\n</BODY></HTML>";
}


/** Class for sending inline or multipart-emails.
 */
class DiogenesMailer {
    /** The header of the email. */
    var $header;
    /** The body of the email. */
    var $body;
    /** The sender of the email. */
    var $from;
    /** The recipient of the email. */
    var $to;
    /** Carbon copy for the email. */
    var $cc;
    /** Blind carbon copy for the email. */
    var $bcc;
    /** Subject of the email. */
    var $subject;
    /** The boundary used to separate the email's body parts. */
    var $boundary;
    /** Do we have a "From:" header? If none is explicitly set, just before
     *  sending it will be constructed from the sender's email address. */
    var $from_present;


    /** The constructor. Initialises header & body.
     *
     * @param from the sender of the email
     * @param to the recipient of the email
     * @param subject the subject of the email
     * @param multipart boolean indicating whether we have a multipart email
     * @param cc carbon copy for the email
     * @param bcc blind carbon copy for the email
     */
    function DiogenesMailer($from, $to, $subject, $multipart=false, $cc="", $bcc="") {
	trigger_error("DiogenesMailer class is obsolete, use HermesMailer instead !", E_USER_NOTICE);
        $this->from = $from;
        $this->from_present = false;
        $this->to = ($to == '' ? $from : $to);
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->subject = $subject;
        $this->body = "";
        $this->header = "X-Mailer: PHP/" . phpversion()."\n".
                        "Mime-Version: 1.0\n";
        if ($multipart) {
            $this->boundary="-=partie_suivante_(".uniqid("").")=-";
            $this->header .=
                "Content-Type: multipart/alternative;\n".
                "   boundary=\"{$this->boundary}\"\n";

        } else {
            $this->boundary="";
            $this->header .=
                "Content-Type: text/plain; charset=iso-8859-1\n".
                "Content-Disposition: inline\n".
                "Content-Transfer-Encoding: 8bit\n";
        }
    }


    /** Adds a part to the email's body.
     *
     * @param type the MIME type of this part (e.g. "text/plain; charset=iso-8859-1")
     * @param encoding the content encoding for this part (e.g "8bit")
     * @param value the contents of this part
     */
    function addPart($type,$encoding,$value)
    {
        if ($this->boundary) {
            $this->body.=
                "--{$this->boundary}\n".
                "Content-Type: $type\n".
                "Content-Transfer-Encoding: $encoding\n\n";
            $this->body .= "$value\n";
        } else {
            echo "<b>Erreur : addPart s'applique uniquement aux messages multipart!</b>";
        }
    }


    /** Adds a header to the email.
     *
     * @param text the contents of the header (without the final line feed)
     */
    function addHeader($text)
    {
        if (preg_match('/^From:/i', $text)) $this->from_present = true;
        $this->header .= "$text\n";
    }


    /** Adds a "text/plain" part to the email.
     *
     * @param text
     */
    function addPartText($text)
    {
        $this->addPart("text/plain; charset=iso-8859-1",
                       "8bit", $text);
    }


    /** Adds a "text/html" part to the email.
     *
     * @param html
     */
    function addPartHtml($html)
    {
        $this->addPart("text/html; charset=iso-8859-1",
                       "8bit", $html);
    }


    /** Sets the body of the email (only for inline messages!).
     *
     * @param text
     */
    function setBody($text)
    {
        if (!$this->boundary) {
            $this->body = $text;
        } else {
            die("Error : setBody only applies to inline messages!");
        }
    }


    /** Sends the email using a pipe to sendmail.
     */
    function send()
    {
        if(!$this->from_present)
            $this->header .= "From: {$this->from}\n";
        if ($this->to)
            $this->header .= "To: {$this->to}\n";
        if ($this->cc)
            $this->header .= "Cc: {$this->cc}\n";

        $this->header .= "Subject: {$this->subject}\n";
        $this->header .= "\n";

        if ($this->boundary)
            $this->body .= "--{$this->boundary}--\n";

        $fp = popen('/usr/sbin/sendmail -oi -f '.escapeshellarg($this->from).' '.escapeshellarg($this->to).' '.escapeshellarg($this->cc).' '.escapeshellarg($this->bcc),'w');
        if ($fp) {
            if(fwrite($fp, $this->header) == -1) return false;
            if(fwrite($fp, $this->body) == -1) return false;
            if (pclose($fp) == 0) return true;
        }
        return false;
    }

}

?>
