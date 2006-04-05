<?php
require_once 'diogenes.common.inc.php';
require_once 'diogenes.toplevel.inc.php';
require_once 'diogenes/diogenes.logger-view.inc.php';

$page = new $globals->toplevel(true);
$page->assign('greeting', __("User activity log"));

$logview = new DiogenesLoggerView;
$logview->run($page,'page_content');
$page->display('');
?>
