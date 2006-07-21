#!/usr/bin/php
<?php
ini_set("include_path", "/etc/diogenes:/usr/share/diogenes/include:/usr/share/php");
require_once("diogenes.common.inc.php");
require_once("diogenes.script.inc.php");
require_once("System.php");
require_once("Barrel.php");

/** Import a single Perl POD file.
 *
 * @param $caller
 * @param $pod
 * @param $docdir
 * @param $docbase
 * @param $template
 */
function importPod(&$caller, $pod, $docdir, $docbase, $template = '')
{
  global $globals;
  $barrel =& $caller->barrel;

  $pid = $barrel->makePath($docdir, $caller);
  $page = Diogenes_Barrel_Page::fromDb($barrel, $pid);
  if (!$page->props['PID']) {
    echo "failed to get Page $pid\n";
    exit(1);
  }

  # produce HTML from POD
  $pod = realpath($pod);
  if (($tmpdir = System::mktemp('-d')) == false) {
    $this->kill("Error : could not create temporary directory!");
  }
  $content = shell_exec("cd $tmpdir && pod2html --htmlroot=FOODOCBASE --infile=".escapeshellarg($pod));
  $content = str_replace('<hr />', '', $content);
  $content = preg_replace('/FOODOCBASE(.*).html/', "/$docbase$1/", $content);
  
  # extract title
  if (preg_match("/<title>(.*)<\/title>/si", $content, $matches))
  {
    $page->props['title'] = addslashes($matches[1]);
    if ($template)
      $page->props['template'] = $template;
    $page->toDb(0, $caller);
  }
 
  # strip un-needed info
  $rcs = $caller->getRcs();
  $content = $rcs->importHtmlString($content); 
  if (preg_match("/<h1><a name=\"synopsis\">.*/si", $content, $matches))
    $content = $matches[0];

  $content = str_replace("h1>", "h2>", $content);
  $rcs->commit($pid,$globals->htmlfile,$content,"automatic import");
}


/** Import a set of Perl POD files.
 *
 * @param $caller
 * @param $docarray
 * @param $docbase
 * @param $template
 */
function importPods(&$caller, $docarray, $docbase, $template = '')
{
  foreach ($docarray as $pod => $docdir)
  {
    importPod($caller, $pod, $docdir, $docbase, $template);
  }
}


/** Print program usage and exit.
 */
function usage()
{
  echo "Usage : pod2diogenes.php 
  exit(1);
}


function main()
{
  $alias = "umts_tools";
  $script = new Diogenes_Script($alias, "sharky");
  $podmap = array();
  importPods($script, $docs, "docs", "barrel:pod.tpl");
}

main();
?>
