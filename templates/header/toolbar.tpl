{if $toolbars}
<ul class="toolbar">
{foreach from=$toolbars item=mybar}
<li><b>{$mybar.title}</b> : {toolbar lnk=$mybar.items}</li>
{/foreach}
</ul>
{/if}
