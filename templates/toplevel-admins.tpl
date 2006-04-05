<form method="post" action="{$post}">
<table class="light">
<tr>
  <th>{$msg_site}</th>
  <th>{$msg_admin}</th>
  <th>{$msg_actions}</th>
</tr>
{foreach from=$entries item=entry}
<tr class="{$entry[0]}">
  <td>{$entry[1]}</td>
  <td><b>{$entry[3]}</b> <span class="auth">({$entry[2]})</span></td>
  <td>
  {foreach from=$entry[4] item=myaction}
  {a class="action" lnk=$myaction}
  {/foreach}
  </td>
</tr>
{/foreach}
<tr>
  <td>
  <select name="target">
  {html_options options=$sites}
  </select>
  </td>
  <td colspan="2">
    <input type="text" name="username"/>
    <select name="auth">
    {html_options options=$auths}
    </select>    
    <input type="submit" name="action" value="add">
  </td>
</tr>
</table>
</form>
