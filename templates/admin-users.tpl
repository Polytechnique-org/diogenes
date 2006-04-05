<h3>{$msg_users}</h3>

<form method="post" action="{$post}">
<table class="light">
<tr>
  <th>{$user}</th>
  <th>{$action}</th>
</tr>
{foreach from=$users item=myuser}
<tr>
  <td>{$myuser[0]} <span class="auth">({$myuser[1]})</span></td>
  <td>{a lnk=$myuser[2]}</td>
</tr>
{/foreach}
<tr>
  <td colspan="2">
    <input type="text" name="username"/>
    {html_options name="auth" options=$auths}
    <input type="submit" name="action" value="add">
  </td>
</tr>
</table>
</form>

<h3>{$msg_admins}</h3>

<table class="light">
<tr>
  <th>{$user}</th>
</tr>
{foreach from=$admins item=myuser}
<tr>
  <td>{$myuser[0]} <span class="auth">({$myuser[1]})</span></td>
</tr>
{/foreach}
</table>
