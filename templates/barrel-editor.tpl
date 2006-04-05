{if $action eq "edit"}

<form action="{$post}" method="post">
<input type="hidden" name="target" value="{$target}">
<input type="hidden" name="action" value="update">
<table class="light">
<tr>
  <th colspan="2">{$target}</th>
</tr>
<tr>
  <td>{$msg_title}</td>
  <td><input type="text" name="title" value="{$v_title}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_desc}</td>
  <td><input type="text" name="description" value="{$v_desc}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_keywords}</td>
  <td><input type="text" name="keywords" value="{$v_keywords}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_vhost}</td>
  <td><input type="text" name="vhost" value="{$v_vhost}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_flags}</td>
  <td>{html_checkboxes name="flags" options=$v_flag_opts selected=$v_flags}</td>
</tr>
<tr>
  <td colspan="2">
  <input type="submit" value="{$msg_submit}" />
  </td>
</tr>
</table>
</form>

<p>
 {$msg_vhost_note}
</p>

{else}

{if !$readonly}
{literal}
<script type="text/javascript">
  <!--
  function del( mysite ) {
    if (confirm ("You are about to destroy the site '" + mysite +
    "'. All data associated with it will irremediably lost! Do you want to proceed?")) {
      document.operations.action.value = "delete";
      document.operations.target.value = mysite;
      document.operations.submit();
      return true;
    }
  }
  // -->
</script>
{/literal}

<form method="post" action="{$post}" name="operations">
<input type="hidden" name="target" value=""/>
<input type="hidden" name="action" value="" />
</form>
{/if}

<table class="light" style="width:90%">
<tr>
{if !$readonly}
  <th>{$msg_alias}</th>
{/if}  
  <th>{$msg_title}</th>
  <th>{$msg_desc}</th>
  <th>&nbsp;</th>
</tr>
{if $sites}
{counter start=0 assign=cnt print=0}
{foreach from=$sites item=mysite}
<tr{if $cnt % 2} class="odd"{/if}>
{if !$readonly}
  <td>{$mysite.alias}</td>
{/if}
  <td>{a lnk=$mysite.title}</td>
  <td>{$mysite.description}</td>
  <td>
  {foreach from=$mysite.actions item=action}
  {a lnk=$action class="action"}
  {/foreach}
  </td>
</tr>{counter}
{/foreach}
{else}
<tr>
  <td colspan="{if $readonly}3{else}4{/if}"><i>{$msg_no_barrels}</i></td>
</tr>
{/if}
</table>

{if !$readonly}
<form method="post" action="{$post}">
<input type="hidden" name="action" value="create" />
<p>
  {$msg_create} : <input type="text" name="target" maxlength="16"/><input type="submit" value="create"/>
</p>
<p>
  {$msg_create_note}
</p>
</form>
{/if}

{/if}
