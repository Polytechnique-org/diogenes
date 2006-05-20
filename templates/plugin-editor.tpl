{if !$readonly}
{literal}
<script type="text/javascript">
  <!--
  function move_up( myplug ) {
    document.plug_form.action.value = "move_up";
    document.plug_form.plug_target.value = myplug;
    document.plug_form.submit();
    return true;
  }
  function move_down( myplug ) {
    document.plug_form.action.value = "move_down";
    document.plug_form.plug_target.value = myplug;
    document.plug_form.submit();
    return true;
  }
  // -->
</script>
{/literal}

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
{if $plug.readonly}
    status {$plug.status}
{else}
    <select name="{$plug.name}_status">{html_options options=$statusvals selected=$plug.status}</select>
    <a class="action"{if $plug.move_up}href="javascript:move_up('{$plug.name}');"{/if}>{$msg_move_up}</a>&nbsp;<a class="action"{if $plug.move_down}href="javascript:move_down('{$plug.name}');"{/if}>{$msg_move_down}</a>
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
      <td><input type="text" name="{$plug.name}_{$key}" value="{$val|escape}" size="30" /></td>
    </tr>
{/foreach}
    </table>
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
