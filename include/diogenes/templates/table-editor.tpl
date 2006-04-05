{if !$doedit}
{if !$readonly}
{literal}
<script language="javascript" type="text/javascript">
  <!--
  function del( myid ) {
    if (confirm ("{/literal}{$msg_confirm_delete}{literal}")) {
      document.operations.action.value = "del";
      document.operations.{/literal}{$prefix}{literal}id.value = myid;
      document.operations.submit();
      return true;
    }
  }
  function edit( myid ) {
    document.operations.action.value = "edit";
    document.operations.{/literal}{$prefix}{literal}id.value = myid;
    document.operations.submit();
    return true;
  }
  // -->
</script>
{/literal}
{/if}

<form method="post" action="{$smarty.server.PHP_SELF}" name="operations">
<input type="hidden" name="action" value="" />
<input type="hidden" name="{$prefix}id" value="" />
</form>

<table class="{$table_class}"{if $table_style} style="{$table_style}"{/if}>
<tr>
  {if $idsum}<th>{$msg_id}</th>{/if}
  {foreach from=$vars item=myval}
  {if $myval.sum}<th>{$myval.desc}</th>{/if}
  {/foreach}
{if !$hideactions}
  <th>{$msg_action}</th>
{/if}
</tr>
{if !$readonly}
<tr>
  <td colspan="{$ncols}"><strong>{$msg_new_entry}</strong></td>
  <td>
    <a class="action" href="javascript:edit('');">{$msg_create}</a>
  </td>
</tr>
{/if}
{counter start=1 assign=cnt print=0}
{foreach from=$rows item=myrow}{assign var="myarr" value=$myrow[1]}
<tr{if $cnt % 2} class="odd"{/if}>
{if $idsum}  <td>{$myrow[0]}</td>{/if}
{foreach from=$vars key=mykey item=myval}
{if $myval.sum}
  <td>
  {if $myval.type=="timestamp"}
  <small>{$myarr.$mykey|date_format:"%Y-%m-%d %H:%M:%S"}</small>
  {elseif $myval.type=="set" and $myval.trans}
  {$myval.trans[$myval.value]}
  {elseif $myval.type=="ext"}
  {extval table=$table field=$mykey value=$myarr.$mykey vtable=$myval.vtable vjoinid=$myval.vjoinid vfield=$myval.vfield}
  {else}
  {$myarr.$mykey}
  {/if}
  </td>
{/if}
{/foreach}
{if !$hideactions}
  <td>
{if !$readonly}
    <a class="action" href="javascript:edit('{$myrow[0]}');">{$msg_edit}</a>
    <a class="action" href="javascript:del('{$myrow[0]}');">{$msg_delete}</a>
{/if}
{foreach from=$myrow[2] item=myaction}
    {a class="action" lnk=$myaction}
{/foreach}
  </td>
{/if}
</tr>
{counter}
{/foreach}
</table>

{if ($p_prev > -1) || ($p_next > -1)}
<p class="pagenavigation">
{if $p_prev > -1}<a href="?start={$p_prev}">{$msg_previous_page}</a>&nbsp;{/if}
{if $p_next > -1}<a href="?start={$p_next}">{$msg_next_page}</a>{/if}
</p>
{/if}

{else}

<form method="post" action="{$smarty.server.PHP_SELF}">
<input type="hidden" name="action" value="update">
{if $id!=''}
<input type="hidden" name="{$prefix}id" value="{$id}">
{/if}
<table class="light">
<tr>
  <th colspan="2">
  {if $id!=''}{$msg_existing_entry} {$id}
  {else}{$msg_new_entry}{/if}
  </th>
</tr>
{foreach from=$vars key=mykey item=myval}
{if $myval.show}
<tr>
  <td>
    <strong>{$myval.desc}</strong>
    {if $myval.type=="password"}<br /><em>{$msg_no_change}</em>{/if}
  </td>
  <td>
{if $myval.edit}  
{if $myval.type=="textarea"}
    <textarea name="{$prefix}{$mykey}" rows="10" cols="70">{$myval.value|escape}</textarea>
{elseif $myval.type=="set"}
    {if $myval.trans}
    {flags table=$table field=$mykey name="$prefix$mykey" selected=$myval.trans[$myval.value] trans=$myval.trans}
    {else}
    {flags table=$table field=$mykey name="$prefix$mykey" selected=$myval.value}
    {/if}
{elseif $myval.type=="ext"}
    {extval table=$table field=$mykey name="$prefix$mykey" vtable=$myval.vtable vjoinid=$myval.vjoinid vfield=$myval.vfield selected=$myval.value}
{elseif $myval.type=="timestamp"}
    <input type="text" name="{$prefix}{$mykey}" value="{$myval.value|date_format:"%Y-%m-%d %H:%M:%S"}" />
{elseif $myval.type=="password"}
    <input type="password" name="{$prefix}{$mykey}" size="40" />
{else}
    <input type="{$myval.type}" name="{$prefix}{$mykey}" size="40" value="{$myval.value|escape}" />
{/if}
{else}
    {$myval.value|escape}
{/if}
  </td>
</tr>
{/if}
{/foreach}
</table>

<p class="center">
  <input type="submit" value="{$msg_submit}" />
</p>

</form>

<p>
  <a href="{$smarty.server.PHP_SELF}">{$msg_back}</a>
</p>

{/if}
