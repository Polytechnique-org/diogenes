{if $smarty.request.logsess}

<table class="light">
<tr>
  <th colspan="2">{$msg_session_properties}</th>
</tr>
<tr>
  <td><strong>{$msg_user}</strong></td>
  <td>{$session.username} {if $session.suer}(suid by {$session.suer}){/if} [<a href="?logauth={$session.auth}&amp;loguser={$session.username}">user's log</a>]</td>
</tr>
<tr>
  <td><strong>{$msg_host}</strong></td>
  <td>{$session.host} / {$session.ip}</td>
</tr>
<tr>
  <td><strong>{$msg_browser}</strong></td>
  <td>{$session.browser}</td>
</tr>
</table>

<br />

<table class="light">
<tr>
  <th>{$msg_date}</th>
  <th>{$msg_action}</th>
  <th>{$msg_data}</th>
</tr>
{foreach from=$events item=myevent}
<tr>
  <td class="logdate">{$myevent.stamp|date_format:"%Y-%m-%d %H:%M:%S"}</td>
  <td class="logmain">{$myevent.text}</td>
  <td>{$myevent.data|escape}</td>
</tr>
{/foreach}
</table>


{else}

<form name="filter" method="post" action="{$smarty.server.PHP_SELF}">
<table class="light">
<tr>
  <th colspan="2">{$msg_filter_by}</th>
</tr>
<tr>
  <td><strong>{$msg_date}</strong></td>
  <td>
    {$msg_year}
    <select name="year" onChange="this.form.submit()">
      {html_options options=$years selected=$year}
    </select>
    &nbsp;{$msg_month}
    <select name="month" onChange="this.form.submit()">
      {html_options options=$months selected=$month}
    </select>
    &nbsp;{$msg_day}
    <select name="day" onChange="this.form.submit()">
      {html_options options=$days selected=$day}
    </select>
  </td>
</tr>
<tr>
  <td><strong>{$msg_user}</strong></td>
  <td>
    <input type="text" name="loguser" value="{$loguser}" />
    {html_options name="logauth" options=$auths selected=$logauth}
    <input type="submit" value="{$msg_submit}" />
  </td>
</tr>
</table>

</form>

<p/>

<table class="light">
<tr>
  <th>{$msg_start}</th>
  <th>{$msg_user}</th>
  <th>{$msg_summary}</th>
  <th>{$msg_actions}</th>
</tr>
{counter start=0 assign=cnt print=0}
{foreach from=$sessions item=mysess}
<tr{if $cnt % 2} class="odd"{/if}>
  <td class="logdate">{$mysess.start|date_format:"%Y-%m-%d %H:%M:%S"}</td>
  <td class="logmain">{$mysess.username} <span class="auth">({$mysess.lauth})</span></td>
  <td class="logevents">
    {foreach from=$mysess.events item=myevent}{$myevent} {/foreach}
  </td>
  <td>
{foreach from=$mysess.actions item=myaction}
    {a class="action" lnk=$myaction}
{/foreach}
  </td>
</tr>
{counter}
{/foreach}
{if $msg_nofilters}
<tr>
  <td>{$msg_nofilters}</td>
</tr>
{/if}
</table>

{/if}
