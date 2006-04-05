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

require_once('Mail.php');
require_once('Mail/mime.php');

// {{{ class HermesMailer
/** Class for sending inline or multipart-emails.
 */
class HermesMailer extends Mail_Mime {

    // {{{ properties
    
    var $_mail;

    // }}}
    // {{{ constructor

    function HermesMailer() {
	$this->Mail_Mime("\n");
	$this->_mail =& Mail::factory('sendmail', Array('-oi'));
    }

    // }}}
    // {{{ function _correct_emails()

    /**
     * converts all : Foo Bar Baz <quux@foobar.org> into "Foo Bar Baz" <quux@foobar.org> wich is RFC compliant
     */

    function _correct_emails($email)
    {
        return preg_replace('!(^|, *)([^<"][^<"]*[^< "]) *(<[^>]*>)!', '\1"\2" \3', $email);
    }

    // }}}
    // {{{ function addTo()

    function addTo($email)
    {
        $email = $this->_correct_emails($email);
	if (isset($this->_headers['To'])) {
	    $this->_headers['To'] .= ", $email";
	} else {
	    $this->_headers['To'] = $email;
	}
    }

    // }}}
    // {{{ function addCc()

    function addCc($email)
    {
        return parent::addCc($this->_correct_emails($email));
    }

    // }}}
    // {{{ function addBcc()

    function addBcc($email)
    {
        return parent::addBcc($this->_correct_emails($email));
    }

    // }}}
    // {{{ function setFrom()

    function setFrom($email)
    {
        return parent::setFrom($this->_correct_emails($email));
    }

    // }}}
    // {{{ function addHeader()
    
    function addHeader($hdr,$val)
    {
        switch($hdr) {
            case 'From':
                $this->setFrom($val);
                break;

            case 'To':
                unset($this->_headers[$hdr]);
                $this->addTo($val);
                break;

            case 'Cc':
                unset($this->_headers[$hdr]);
                $this->addCc($val);
                break;

            case 'Bcc':
                unset($this->_headers[$hdr]);
                $this->addBcc($val);
                break;

            default:
                $this->headers(Array($hdr=>$val));
        }
    }

    // }}}
    // {{{ function send()

    function send() {
	$addrs = Array();
	foreach(Array('To', 'Cc', 'Bcc') as $hdr) {
	    if(isset($this->_headers[$hdr])) {
		require_once 'Mail/RFC822.php';
		$addrs = array_merge($addrs, Mail_RFC822::parseAddressList($this->_headers[$hdr]));
	    }
	}
	if(empty($addrs)) return false;
	
	$dests = Array();
	foreach($addrs as $a) $dests[] = "{$a->mailbox}@{$a->host}";
	
	// very important to do it in THIS order very precisely.
	$body = $this->get();
	$hdrs = $this->headers();
	return $this->_mail->send($dests, $hdrs, $body);
    }

    // }}}
}

// }}}

?>
