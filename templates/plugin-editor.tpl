{if !$readonly}
<form name="plug_form" method="post" action="{$post}">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="plug_target" value="" />
<input type="hidden" name="plug_page" value="{$plug_page}" />
<input type="hidden" name="plug_barrel" value="{$plug_barrel}" />
{/if}

<div class="plugins">
{foreach from=$plugins key=plugtype item=plugarr}
<table class="light" style="width:80%">
<tr>
  <th colspan="2">{$plugtype} {$msg_plugedit_plugins}</th>
</tr>
{foreach from=$plugarr item=plug}
<tr class="odd">
  <td><img class="fileicon" src="{$plug.icon}"/>&nbsp;{$plug.name}&nbsp;v{$plug.version}</td>
  <td>
{if $readonly || $plug.readonly}
    {$statusvals[$plug.status]}
{else}
    <select name="{$plug.name}_status">{html_options options=$rwstatusvals selected=$plug.status}</select>
{/if}
  </td>
</tr>
<tr>
  <td><div class="description">{$plug.description}</div></td>
  <td>
{if $show_params}
    <table class="plugparams">
{foreach from=$plug.params key=key item=val}
    <tr>
      <td>{$key}</td>
      <td><input type="text" name="{$plug.name}_{$key}" value="{$val.value|escape}" size="30" /></td>
    </tr>
{/foreach}
    </table>
{else}
    &nbsp;
{/if}
  </td>
</tr>
{/foreach}
</table>
<br/>
{/foreach}
</div>

{if !$readonly}
<p>
  <input type="submit" value="{$msg_submit}" />
</p>
</form>
{/if}
