<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.admin.inc.php';
require_once 'Barrel/Events.php';

$page = new DiogenesAdmin;
$bbarrel =& $page->barrel;
$events = new Diogenes_Barrel_Events($bbarrel);

// retrieve recent events
$event_arr = $events->getEvents($page);
$page->assign('events', $event_arr);

// do display
$page->assign('greeting',__("Welcome to the Diogenes backoffice"));
$page->assign('msg_date',__("date"));
$page->assign('msg_user',__("user"));
$page->assign('msg_event',__("event"));
$page->display('admin-index.tpl');
?>
