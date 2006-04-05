{include file='header/head.tpl'}
{if $banner}{include file=$banner}{/if}

{include file='header/debug.tpl'}

<div id="header">
  <div class="logo">&nbsp;</div>
  <div class="titlebar">
    <div class="site">{$site}</div>
    <div class="page">{$page}</div>
  </div>
</div>

{include file='header/sidebar.tpl'}

<div id="main">
{if $greeting}<h2>{$greeting}</h2>{/if}

{include file='header/toolbar.tpl'}
{include file='header/status.tpl'}

<!-- End of Diogenes header -->
