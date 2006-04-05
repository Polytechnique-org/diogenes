<form method="post" action="{$post}">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="dir" value="{$page_obj.PID}" />
<table class="light" style="width:70%">
<tr>
  <th colspan="2">{$msg_properties}</th>
</tr>
{if $page_obj.parent}
<tr>
  <td>{$msg_parent}</td>
  <td>
    {html_options name='pedit_parent' options=$parent_opts selected=$page_obj.parent}
  </td>
</tr>
{else}
<input type="hidden" name="pedit_parent" value="0"/>
{/if}
<tr>
  <td>{$msg_location}</td>
  <td>
    {if $page_obj.PID && $page_obj.location==''}
    <i>home</i>
    {else}
    <input type="text" name="pedit_location" value="{$page_obj.location}" />
    {/if}
  </td>
</tr>
<tr>
  <td>{$msg_title}</td>
  <td><input type="text" name="pedit_title" value="{$page_obj.title|escape}" /></td>
</tr>
<tr>
  <td>{$msg_page_template}</td>
  <td>{html_options name='pedit_template' selected=$page_obj.template options=$templates}</td>
</tr>
<tr>
  <td>{$msg_status}</td>
  <td>
    {html_options name='pedit_status' options=$status_opts selected=$page_obj.status}
  </td>
</tr>
<tr>
  <td>{$msg_read_perms}</td>
  <td>
    <select name="pedit_perms">
    {flags table=$table field='perms' selected=$page_obj.perms}
    </select>
  </td>
</tr>
<tr>
  <td>{$msg_write_perms}</td>
  <td>
    <select name="pedit_wperms">
    {flags table=$table field='wperms' selected=$page_obj.wperms}
    </select>
  </td>
</tr>


<tr>
  <td><input type="submit" value="{$msg_submit}" /></td>
  <td><input type="reset" value="{$msg_reset}" /></td>
</tr>
</table>
</form>

{if $smarty.request.dir}

{if $word}

<table class="light" style="width:70%">
<tr>
  <th>{$word}</th>
</tr>
<tr>
  <td>
    <p>
      {$wordblab}
    </p>
{if $wordfile}
    <p>
      {$wordfile} : {a lnk=$wordlnk}
    </p>
{/if}
    <p>
      {$wordsend}
    </p>
    <form name="import" method="post" action="{$post}" enctype="multipart/form-data">
    <input type="hidden" name="action" value="import" />
    <input type="hidden" name="dir" value="{$dir}" />
    <input type="file" name="wordfile" />
    <input type="submit" value="{$send}" />
    </form>
  </td>
</tr>
</table>

{/if}

{if !$wordfile}
<br/>

<table class="light" style="width:70%">
<tr>
  <th>{$html}</th>
</tr>
<tr>
  <td>
    <p>
      {$htmlblab}
    </p>
    <p>
      {$htmlstrip}
    </p>
    <form name="import" method="post" action="{$post}" enctype="multipart/form-data">
    <input type="hidden" name="action" value="import" />
    <input type="hidden" name="dir" value="{$dir}" />
    <input type="file" name="htmlfile" />
    <input type="submit" value="{$send}" />
    </form>
  </td>
</tr>
{/if}
</table>

{/if}
