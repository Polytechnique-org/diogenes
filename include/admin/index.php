<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'diogenes.icons.inc.php';

$page = new DiogenesAdmin;
$bbarrel =& $page->barrel;
$homepage = $bbarrel->getPID('');

// filename transformations
function makeFileLoc($log_file) {
  global $homepage, $bbarrel;

  if (stristr($log_file, '/') == FALSE ) {
    // this is a directory
    $mydir = $log_file;
    $myfile = '';
  } else {
    $myfile = basename($log_file);
    $mydir = dirname($log_file);
  }
  
  $myloc = $bbarrel->getLocation($mydir);    
  if ($myloc or ($mydir == $homepage))
  {
    $log_file = $myloc ? "$myloc/$myfile" : $myfile;
  }
  
  return array($log_file, $mydir, $myfile);
}


// retrieve recent events
$res = $globals->db->query("select e.action,e.stamp,e.data,a.text,s.auth,s.uid "
                  ."from {$globals->table_log_events} as e "
                  ."left join {$globals->table_log_actions} as a on e.action=a.id "
                  ."left join {$globals->table_log_sessions} as s on e.session=s.id "
                  ."where e.data like '{$bbarrel->alias}:%' "
                  ."order by stamp desc limit 0,10");
while ($myarr = mysql_fetch_array($res)) {
  $myarr['username'] = call_user_func(array($globals->session,'getUsername'),$myarr['auth'],$myarr['uid']);
  list($op_alias, $op_file) = split(":",$myarr['data']);

  switch($myarr['text']) {
  case "barrel_create":
    $myarr['icon'] = $globals->icons->get_action_icon('add');    
    $myarr['desc'] = __("site created");
    break;
    
  case "barrel_options":
    $myarr['icon'] = $globals->icons->get_action_icon('properties');    
    $myarr['desc'] = array(__("barrel options"), "options");    
    break;

  case "barrel_plugins":
    $myarr['icon'] = $globals->icons->get_action_icon('plugins');
    $myarr['desc'] = array(__("barrel plugins"), "plugins");  
    break;
        
  case "page_create":
    $myarr['icon'] = $globals->icons->get_action_icon('add');    
    list($op_file, $mydir, $myfile) = makeFileLoc($op_file);
    $myarr['desc'] = array(__("page created"), "pages?dir=$mydir");  
    break;

  case "page_delete":
    $myarr['icon'] = $globals->icons->get_action_icon('remove');
    $myarr['desc'] = __("page removed");
    break;
    
  case "page_props":
    $myarr['icon'] = $globals->icons->get_action_icon('properties');
    list($op_file, $mydir, $myfile) = makeFileLoc($op_file);
    $myarr['desc'] = array(__("page properties"), "pages?dir=$mydir");  
    break;

  case "page_plugins":
    $myarr['icon'] = $globals->icons->get_action_icon('plugins');
    list($op_file, $mydir, $myfile) = makeFileLoc($op_file);
    $myarr['desc'] = array(__("page plugins"), "plugins?plug_page=$mydir");  
    break;
            
  case "rcs_commit": 
    $myarr['icon'] = $globals->icons->get_action_icon('update');    
    list($op_file, $mydir, $myfile) = makeFileLoc($op_file);  
    $myarr['desc'] = array(__("file updated"), "files?action=revs&amp;dir=$mydir&amp;target=$myfile");        
    break;
      
  case "rcs_delete":
    $myarr['icon'] = $globals->icons->get_action_icon('delete');    
    list($op_file, $mydir, $myfile) = makeFileLoc($op_file);    
    $myarr['desc'] = __("file deleted");        
    break;
    
  }
    
  $myarr['file'] = $op_file;

  if (isset($myarr['desc']))
    $page->append('events',$myarr);
}
mysql_free_result($res);

// do display
$page->assign('greeting',__("Welcome to the Diogenes backoffice"));
$page->assign('msg_date',__("date"));
$page->assign('msg_user',__("user"));
$page->assign('msg_event',__("event"));
$page->display('admin-index.tpl');
?>
