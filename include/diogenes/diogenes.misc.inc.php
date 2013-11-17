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

require_once dirname(__FILE__).'/diogenes.mime.inc.php';

$lc_accent = "éèëêáàäâåãïîìíôöòóõøúùûüçñ";
$lc_plain  = "eeeeaaaaaaiiiioooooouuuucn";
$uc_accent = "ÉÈËÊÁÀÄÂÅÃÏÎÌÍÔÖÒÓÕØÚÙÛÜÇÑ";
$uc_plain  = "EEEEAAAAAAIIIIOOOOOOUUUUCN";


/** Returns the value of one or more request variables, catching
 *  the case where they do not exist.
 *
 * @param req name(s) of the request variables : a string or array strings
 */
function clean_request($req) {
  if (is_array($req)) {
    $out = array();
    foreach($req as $reqitem)
      array_push($out,(isset($_REQUEST[$reqitem])) ? $_REQUEST[$reqitem] : "");
    return $out;
  } else {
    return (isset($_REQUEST[$req])) ? $_REQUEST[$req] : "";
  }
}


/** Strips slashes off a request var.
 *
 * @param req the request variable name
 */
function strip_request($req) {
  return stripslashes(clean_request($req));
}

/** replaces accentuated characters in a string by their
 *  html counterpart
 *
 * @param $string the input string
 * @return STRING the output string
 */
function html_accent($string)  {
  global $lc_accent,$uc_accent;
  $lca = preg_split('//', $lc_accent, -1, PREG_SPLIT_NO_EMPTY);
  $uca = preg_split('//', $uc_accent, -1, PREG_SPLIT_NO_EMPTY);
  foreach($lca as $key=>$val)
    $lch[$key] = htmlentities($val, ENT_COMPAT | ENT_HTML401, "ISO-8859-1");
  foreach($uca as $key=>$val)
    $uch[$key] = htmlentities($val, ENT_COMPAT | ENT_HTML401, "ISO-8859-1");
  $newstring = str_replace($lca,$lch,$string);
  $newstring = str_replace($uca,$uch,$newstring);
  return $newstring;
}


/** replaces accentuated characters in a string by their
 *  non-accentuaded counterpart
 *
 * @param $string the input string
 * @return STRING the output string
 */
function replace_accent($string)  {
  global $lc_accent,$lc_plain,$uc_accent,$uc_plain;

  $newstring = strtr($string,$lc_accent,$lc_plain);
  $newstring = strtr($newstring,$uc_accent,$uc_plain);
  return $newstring;
}


/** remplace les caractères accentués par la regexp (caractère accentué ou caractère non accentué)
 * @param $string la chaîne de caractères
 * @return STRING la nouvelle chaîne de caractères
 * @see recherche.php
 */
function replace_accent_regexp($string)  {
  $classes_accent[] = "éèëêe";
  $classes_accent[] = "áàäâåãa";
  $classes_accent[] = "ïîìíi";
  $classes_accent[] = "ôöòóõøo";
  $classes_accent[] = "úùûüu";
  $classes_accent[] = "çc";
  $classes_accent[] = "ñn";
  $classes_accent[] = "ÉÈËÊE";
  $classes_accent[] = "ÁÀÄÂÅÃA";
  $classes_accent[] = "ÏÎÌÍI";
  $classes_accent[] = "ÔÖÒÓÕØO";
  $classes_accent[] = "ÚÙÛÜU";
  $classes_accent[] = "ÇC";
  $classes_accent[] = "ÑN";

  for ($i=0;$i<count($classes_accent);$i++)
    for ($j=0;$j<strlen($classes_accent[$i]);$j++)
        $trans[$classes_accent[$i][$j]] = '['.$classes_accent[$i].']';
  $newstring = strtr($string,$trans);
  return $newstring;
}


/** capitalises the first letters of the elements of a name
 *
 * @param $name the name to capitalise
 *
 * @return STRING the capitalised name
 */
function make_name_case($name) {
  $name = strtolower($name);
  $pieces = explode('-',$name);

  foreach ($pieces as $piece) {
    $subpieces = explode("'",$piece);
    $usubpieces="";
    foreach ($subpieces as $subpiece)
      $usubpieces[] = ucwords($subpiece);
    $upieces[] = implode("'",$usubpieces);
  }
  return implode('-',$upieces);
}

/** creates a username from a first and last name
*
* @param $prenom the firstname
* @param $nom the last name
*
* return STRING the corresponding username
*/
function make_username($prenom,$nom) {
    /* on traite le prenom */
    $prenomUS=replace_accent(trim($prenom));
    $prenomUS=stripslashes($prenomUS);

    /* on traite le nom */
    $nomUS=replace_accent(trim($nom));
    $nomUS=stripslashes($nomUS);

    // calcul du login
    $username = strtolower($prenomUS.".".$nomUS);
    $username = str_replace(" ","-",$username);
    $username = str_replace("'","",$username);
    return $username;
}

/** met les majuscules au debut de chaque atome du prénom
 * @param $prenom le prénom à formater
 * return STRING le prénom avec les majuscules
 */
function make_firstname_case($prenom) {
  $prenom = strtolower($prenom);
  $pieces = explode('-',$prenom);

  foreach ($pieces as $piece) {
    $subpieces = explode("'",$piece);
    $usubpieces="";
    foreach ($subpieces as $subpiece)
      $usubpieces[] = ucwords($subpiece);
    $upieces[] = implode("'",$usubpieces);
  }
  return implode('-',$upieces);
}


/** vérifie si une adresse email est bien formatée
 * ATTENTION, cette fonction ne doit pas être appelée sur une chaîne ayant subit un addslashes (car elle accepte le "'" qui serait alors un "\'"
 * @param $email l'adresse email a verifier
 * @return BOOL
 */
function isvalid_email($email) {
  // la rfc2822 authorise les caractères "a-z", "0-9", "!", "#", "$", "%", "&", "'", "*", "+", "-", "/", "=", "?", "^", "_", "`", "{", "|", "}", "~" aussi bien dans la partie locale que dans le domaine.
  // Pour la partie locale, on réduit cet ensemble car il n'est pas utilisé.
  // Pour le domaine, le système DNS limite à [a-z0-9.-], on y ajoute le "_" car il est parfois utilisé.
  return preg_match("/^[a-z0-9_.'+-]+@[a-z0-9._-]+\.[a-z]{2,4}$/i", $email);
}


/** genere une chaine aleatoire de 22 caracteres ou moins
 * @param $len longueur souhaitée, 22 par défaut
 * @return la chaine aleatoire qui contient les caractères [A-Za-z0-9+/]
 */
function rand_token($len = 22) {
    $len = max(2, $len);
    $len = min(50, $len);
    $fp = fopen('/dev/urandom', 'r');
    // $len * 2 is certainly an overkill,
    // but HEY, reading 40 bytes from /dev/urandom is not that slow !
    $token = fread($fp, $len * 2);
    fclose($fp);
    $token = base64_encode($token);
    $token = preg_replace("![Il10O+/]!", "", $token);
    $token = substr($token,0,$len);
    return $token;
}

/** genere une chaine aleatoire convenable pour une url
 * @param $len longueur souhaitée, 22 par défaut
 * @return la chaine aleatoire 
 */
function rand_url_id($len = 22) {
	return rand_token($len);
}


/** genere une chaine aleatoire convenable pour un mot de passe
 * @return la chaine aleatoire
 */
function rand_pass() {
	return rand_token(8);
}


/** replacement for file_get_contents functions
 */
if (!function_exists("file_get_contents")) {
  function file_get_contents($filename, $use_include_path = 0) {
    $data = ""; // just to be safe. Dunno, if this is really needed
    $file = @fopen($filename, "rb", $use_include_path);
    if ($file) {
      while (!feof($file))
        $data .= fread($file, 1024);
      fclose($file);
    }
    return $data;
 }
}

?>
