<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{$site}{if $page} - {$page}{/if}</title>
{foreach from=$head item=mytag}
{$mytag}
{/foreach}
{if ($menustyle==1 or $menustyle==2)}
{include file='header/phplayersmenu.tpl'}
{/if}
</head>
<body>
