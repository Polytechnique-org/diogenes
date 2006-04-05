<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';

$page = new DiogenesAdmin;
$bbarrel =& $page->barrel;

// the id of the parent menu
$MIDpere = isset($_REQUEST['MIDpere']) ? $_REQUEST['MIDpere'] : 0;

/**
 * This function swaps entries $a and $b within $parent.
 */
function swapentries($parent,$a,$b,$table_menu)
{
  global $globals;
  $res = $globals->db->query("SELECT MID from $table_menu where MIDpere=$parent and (ordre=$a or ordre=$b) ORDER BY ordre");
  /* make sure that $a <= $b */
  if ($a > $b)
  {
    $c = $a;
    $a = $b;
    $b = $c;
  }
  /* perform swap */
  list($MIDa) = mysql_fetch_row($res);
  list($MIDb) = mysql_fetch_row($res);
  mysql_free_result($res);

  $globals->db->query("UPDATE $table_menu SET ordre=$b WHERE MID=$MIDa");
  $globals->db->query("UPDATE $table_menu SET ordre=$a WHERE MID=$MIDb");
}


//// start constructing the page

$page->assign('greeting',__("The site's menus"));
$action = isset($_REQUEST['action']) ? $_REQUEST["action"] : "";
switch ($action) {

/* we want to erase the current entry */
case "supprimer":
  $MID = $_REQUEST['MID'];
  if (mysql_num_rows($globals->db->query("SELECT MID FROM {$bbarrel->table_menu} WHERE MIDpere=$MID")) > 0) {
    $page->info(__("The selected menu has child items, please remove them first."));
    break;
  }

  /* erase the current entry */
  $globals->db->query("DELETE FROM {$bbarrel->table_menu} WHERE MID=$MID");

  /* renumber the other menu entries so that they are between 1 and the number of entries */
  $res = $globals->db->query("SELECT MID FROM {$bbarrel->table_menu} WHERE MIDpere=$MIDpere ORDER BY ordre");
  $i = 0;
  while (list($MIDtoorder) = mysql_fetch_array($res)) {
    $i++;
    $globals->db->query("UPDATE {$bbarrel->table_menu} SET ordre=$i WHERE MID=$MIDtoorder");
  }
  mysql_free_result($res);
  break;

/* bring an entry up in the menu */
case "remonter":
  $ordre = $_REQUEST['ordre'];
  swapentries($MIDpere,$ordre-1,$ordre,$bbarrel->table_menu);
  break;

/* push an entry down in the menu */
case "descendre":
  $ordre = $_REQUEST['ordre'];
  swapentries($MIDpere,$ordre,$ordre+1,$bbarrel->table_menu);
  break;

/* create or update a menu entry */
case "modifier":
    $typelink = $_REQUEST['typelink'];
    switch ($typelink) {
    case "boutonPI" :
      $pid = isset($_REQUEST['PIvaleur']) ? $_REQUEST['PIvaleur'] : 0;
      $link = "";
      break;
    case "boutonSE" :
      $pid = 0;
      $link = $_REQUEST['SEvaleur'];
      break;
    default:
      $pid = 0;
      $link = "";
    }
    $MID = $_REQUEST['MID'];
    $title = $_REQUEST['title'];
    if ($MID == 0) {
      $res=$globals->db->query("SELECT MAX(ordre) from {$bbarrel->table_menu} where MIDpere=$MIDpere");
      list($ordre) = mysql_fetch_row($res);
      $ordre++;
      $globals->db->query("INSERT INTO {$bbarrel->table_menu} SET MIDpere='$MIDpere',ordre='$ordre',title='$title',link='$link',pid='$pid'");
      $MID = mysql_insert_id();
    } else {
      $globals->db->query("UPDATE {$bbarrel->table_menu} SET title='$title',link='$link',pid='$pid' WHERE MID=$MID");
    }

    break;

/* display the form to edit an entry */
case "editer":
  // initialisation
  $link = "";
  $title = "";
  $pid = 0;
  $MID = isset($_REQUEST['MID']) ? $_REQUEST['MID'] : 0;

  // if this is an existing entry, retrieve data
  if ($MID) {
    $res = $globals->db->query("SELECT link,title,pid FROM {$bbarrel->table_menu} WHERE MID=$MID");
    list($link, $title, $pid) = mysql_fetch_row($res);
    mysql_free_result($res);
  }

  // fill out form data
  $chk_pi = ($pid > 0);
  $chk_se = ($link != "");
  $chk_z = !$chk_pi && !$chk_se;
  $chk = " checked=\"checked\"";

  $page->assign('post',$page->script_self());
  $page->assign('MID', $MID);
  $page->assign('MIDpere',$_REQUEST['MIDpere']);
  $page->assign('title',stripslashes($title));
  $page->assign('chk_z',($chk_z ? $chk : ""));
  $page->assign('chk_pi',($chk_pi ? $chk : ""));
  $page->assign('chk_se',($chk_se ? $chk : ""));
  $page->assign('SEvaleur', $link ? $link : "http://");
  $page->assign('page_sel',$pid);

  // retrieve all the barrel's pages
  $fpages = $bbarrel->getPages();
  $page_opts = array();
  foreach($fpages as $pkey => $ppage)
  {
    $pageloc = $bbarrel->getLocation($pkey);
    $page_opts[$pkey] = ( strlen($pageloc) ? "<$pageloc> " : "") . $ppage->props['title'];
  }
  $page->assign('page_opts', $page_opts);

  $res = $globals->db->query("SELECT PID,title from {$bbarrel->table_page} ORDER BY title");
  while (list($myPID,$myTITLE) = mysql_fetch_row($res)) {
    //$pageloc = $bbarrel->getLocation($pkey);
    //$page_opts[$pkey] = ( strlen($pageloc) ? "<$pageloc> " : "") . $ppage->props['title'];  
    $page->append('page_values',$myPID);
    $page->append('page_names',stripslashes($myTITLE));
  }
  mysql_free_result($res);

  $page->assign('doedit',1);

  // translations
  $page->assign('msg_prop',__("menu entry properties"));
  $page->assign('msg_title',__("entry title"));
  $page->assign('msg_type',__("type of link"));
  $page->assign('msg_type_z',__("none"));
  $page->assign('msg_type_pi',__("internal link"));
  $page->assign('msg_type_se',__("external link"));

  $page->assign('submit',__("Submit"));
  $page->display('admin-menus.tpl');
  exit;
  break;
}

// get the maximum order
$res=$globals->db->query("SELECT MAX(ordre) from {$bbarrel->table_menu} where MIDpere=$MIDpere");
list($maxOrdre)=mysql_fetch_row($res);
mysql_free_result($res);

// retrieve the entries
$res = $globals->db->query("SELECT m.MID,m.ordre,m.title,m.link,m.PID,p.title ".
                       "from {$bbarrel->table_menu} as m ".
                       "left join {$bbarrel->table_page} as p on m.PID=p.PID ".
                       "where MIDpere=$MIDpere order by ordre");
while (list($MID,$ordre,$title,$link,$PID,$ptitle) = mysql_fetch_row($res)) {
  $clickup="?action=remonter&amp;MIDpere=$MIDpere&amp;ordre=$ordre";
  $clickdown="?action=descendre&amp;MIDpere=$MIDpere&amp;ordre=$ordre";

  // do we offer an "up" link ?
  $up = ($ordre != 1) ? array(__("move up"),$clickup) : _("move up");

  // do we offer a "down" link ?
  $down = ($ordre != $maxOrdre) ? array(__("move down"),$clickdown) : $down = __("move down");

  $edit = array(__("edit"), "?action=editer&amp;MIDpere=$MIDpere&amp;MID=$MID");
  $del = array(__("delete"), "?action=supprimer&amp;MIDpere=$MIDpere&amp;MID=$MID");
  
  // describe the current link
  if ($PID) {
    $descr = "<a href=\"pages?dir=$PID\">$ptitle</a>";
  } elseif ($link) {
    $descr = "<a href=\"$link\">[ext] $link</a>";
  } else {
    $descr = "none";
  }

  // smarty assignments
  $page->append('entries',
          array($MID, array(stripslashes($title), "?MIDpere=$MID"),
          stripslashes($descr),
          $edit,$del,$up,$down));
}
mysql_free_result($res);

$filiation = $page->menuToRoot($MIDpere,array());
$menubar = array();
foreach($filiation as $mykey=>$myval) {
  if ($myval == 0) {
    $blab = "<i>home</i>";
  } else {
    $res = $globals->db->query("SELECT title FROM {$bbarrel->table_menu} WHERE MID='$myval'");
    list($blab) = mysql_fetch_row($res);
    $blab = stripslashes($blab);
    mysql_free_result($res);
  }
  array_unshift($menubar,$mykey ? array($blab,"?MIDpere=$myval") : array($blab));
}
$page->assign('menubar',$menubar);
$page->assign('script',$page->script_self());
$page->assign('MIDpere',$MIDpere);

// translations
$page->assign('msg_ext',__("External links are denoted with the [ext] prefix."));
$page->assign('msg_menu',__("menu name"));
$page->assign('msg_link',__("link"));
$page->assign('msg_actions',__("actions"));
$page->assign('msg_menubar',__("Menu"));
$page->assign('submit',__("Add new entry"));
$page->display('admin-menus.tpl');
?>
