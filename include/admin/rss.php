<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.barrel.inc.php';
require_once 'Barrel/Events.php';
$page = new $globals->barrel;
$events = new Diogenes_Barrel_Events($page->barrel);

// assignments
$page->assign('version', $globals->version);
$page->assign('site_title', stripslashes($page->barrel->options->title));
$page->assign('site_link', $page->urlBarrel($page->barrel->alias, $page->barrel->vhost, ''));

// retrieve recent events
$event_arr = $events->getEvents($page);
foreach($event_arr as $event)
{
  //$page->assign('events', $event_arr);
  if ($event['flags'] & EVENT_FLAG_PUBLIC)
  {
    $item = $event;
    $item['date'] = gmstrftime("%a, %d %b %Y %T %Z", strtotime($event['stamp']));
    $item['title'] .= " : ". $item['opfile'];
    $page->append('items', $item);
  }
}
header("Content-Type: application/rss+xml");
$page->display('', 'admin-rss.tpl');
?>
