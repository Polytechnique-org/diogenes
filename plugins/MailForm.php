<?php 
/*
 * Copyright (C) 2003-2005 Polytechnique.org
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

require_once 'Plugin/Filter.php';
require_once 'diogenes/diogenes.hermes.inc.php';

/** The MailForm plugin allows you to insert a form to send
 *  an e-mail to a fixed recipient.
 *  
 * To make use of this plugin, insert {MailForm}in your page
 * where the mail form should appear.
 */
class MailForm extends Diogenes_Plugin_Filter
{  
  /** Plugin name */
  var $name = "MailForm";
  
  /** Plugin description */
  var $description = "This plugin allows you to insert a form to send an e-mail to a fixed recipient. To make use of this plugin, insert <b>{MailForm}</b> in your page where the mail form should appear.";
  
  /** Plugin parameters */
  var $params = array('email' => '', 'title' => '', 'subject_tag' => '[web form] ');

  /** Show an instance of the MailForm plugin.
   */
  function show()
  {
    global $page;

    // get params
    $to_email = $this->params['email'];
    $form_title = $this->params['title'];
    
    if (!isvalid_email($to_email)) {
      return '<p>You must specify a valid e-mail in the "email" parameter to make use of the MailForm plugin.<p>';
    }
        
    // get input
    $action = clean_request('action');
    $from = clean_request('from');
    
    $refer = strip_request('refer');
    if (!$refer) 
      $refer = $_SERVER['HTTP_REFERER'];
    $message = strip_request('message');
    $subject = strip_request('subject');
    
    $showform=0;
    $output = '';
    switch($action) {
    case "mail":
      if ((!$subject)||(!$message)||(!$from)) {
        $output .= '<p align="center"><strong>Missing fields !</strong></p>';
        $showform=1;
        break;
      }
    
      if (!isvalid_email($from)) {
        $output .= '<p align="center"><strong>Invalid email address !</strong></p>';
        $showform=1;
        break;
      }
      
      $mymail = new HermesMailer();
      $mymail->setFrom($from);
      $mymail->setSubject($this->params['subject_tag'].$subject);
      $mymail->addTo($to_email);
      $mymail->setTxtBody($message);
      $mymail->send();
    
      $output .= '<p align="center"><strong>Message sent !</strong></p>';
      if ($refer!="") {
        $output .= '<p align="center">To return to referring web page click <a href="'.$refer.'">here</a></p>';
      }
      break;
    default:
      $showform=1;
    }
        
    if ($showform) {
      $output .=
    '<br/>
    
    <form action="'.$page->script_uri().'" method="post">
    
    <table class="light">
      <tr>
        <th colspan="2">'.$form_title.'</a></th>
      </tr>
      <tr>
        <td>'.__("from").'</td>
        <td><input type="text" name="from" size="68" value="'.$from.'" /></td>
      </tr>
      <tr>
        <td>'.__("subject").'</td>
        <td><input type="text" name="subject" size="68" value="'.$subject.'" /></td>
      </tr>
      <tr>
        <td>'.__("message").'</td>
        <td><textarea name="message" rows="10" cols="70">'.$message.'</textarea></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          <input type="hidden" name="action" value="mail" />
          <input type="hidden" name="refer" value="'.$refer.'" />
          <input type="submit" value="'.__("Send").'"/>
        </td>
      </tr>
    </table>
    
    </form>';
    }
    return $output;
  }
}
?>
