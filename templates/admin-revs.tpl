{if $diff}

<div class="diff">
<table class="light">
<tr>
  <th colspan="2">differences between {$smarty.request.r1} and {$smarty.request.r2}</th>
</tr>
{foreach from=$diff item=mydiff}
<tr>
  <td>[ {$smarty.request.r1} - {$mydiff[0]} ]</td>
  <td>[ {$smarty.request.r2} - {$mydiff[2]} ]</td>
</tr>
<tr>
  <td>{diff block=$mydiff[3] op=$mydiff[1]}</td>
  <td>{diff block=$mydiff[4] op=$mydiff[1]}</td>
</tr>
{/foreach}
</table>
</div>

{else}

{literal}
<script type="text/javascript">
  <!--
  function restore( myrev ) {
    if (confirm ("You are about to restore revision " + myrev + ". Do you want to proceed?")) {
      document.operations.rev.value = myrev;
      document.operations.submit();
      return true;
    }
  }
  // -->
</script>
{/literal}

<form method="post" action="{$post}" name="operations">
<input type="hidden" name="dir" value="{$smarty.request.dir}" />
<input type="hidden" name="target" value="{$smarty.request.target}" />
<input type="hidden" name="rev" value=""/>
<input type="hidden" name="action" value="restore" />
</form>

<table class="light">
<tr>
  <th>{$msg_version}</th>
  <th>{$msg_date}</th>
  <th>{$msg_author}</th>
  <th>{$msg_log}</th>
  <th>{$msg_actions}</th>
</tr>
{counter start=0 assign=cnt print=0}
{foreach from=$entries item=entry}
<tr{if $cnt % 2} class="odd"{/if}>
  <td>{$entry[0]}</td>
  <td><small>{$entry[1]}</small></td>
  <td>{$entry[2]}</td>
  <td>{$entry[3]}</td>
  <td>
  {foreach from=$entry[4] item=myaction}
  {a lnk=$myaction class="action"}
  {/foreach}
  </td>
</tr>
{counter}
{/foreach}
</table>

{/if}
