{$editor_content}

<br/>

<table class="light" style="width:80%">
<tr>
  <th>{$msg_vcs}</th>
</tr>
<tr>
  <td>{$msg_current_vcs}</td>
</tr>
{foreach from=$conversions item="conv"}
<tr>
  <td>
  <form method="post" action="{$smarty.server.PHP_SELF}">
  {$conv[1]}
  <input type="hidden" name="action" value="{$conv[0]}" />
  <input type="submit" value="{$msg_convert}" />
  </form>
  </td>
</tr>
{/foreach}
</table>