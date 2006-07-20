<table class="light">
<tr>
  <th>&nbsp;</th>
  <th>{$msg_date}</th>
  <th>{$msg_user}</th>
  <th colspan="2">{$msg_event}</th>
</tr>
{foreach from=$events item=myevent}
<tr>
  <td>{if $myevent.icon}<img class="fileicon" src="{$myevent.icon}" />{else}&nbsp;{/if}</td>
  <td class="logdate">{$myevent.stamp|date_format:"%Y-%m-%d %H:%M:%S"}</td>
  <td class="logauthor">{$myevent.username}</td>
  <td>{a lnk=$myevent.desc}</td>
  <td>{$myevent.file}</td>
</tr>
{/foreach}
</table>
