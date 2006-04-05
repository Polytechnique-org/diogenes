{if $canedit}
<form method="post" action="{$post}" enctype="multipart/form-data">
<input type="hidden" name="dir" value="{$smarty.request.dir}" />
<input type="hidden" name="MAX_FILE_SIZE" value="{$maxsize}" />
<table class="light"  style="width: 90%; margin-bottom: 15px;">
<tr>  
  <td><input type="radio" name="action" value="file_upload" {if !$smarty.request.action || ($smarty.request.action == "file_upload")}checked="checked" {/if}/> {$msg_import}</td>
  <td><input type="file" name="userfile" /></td>
  <td rowspan="3"><input type="submit" value="{$msg_btn_fileop}" /></td>
</tr>
<tr>
  <td>
    <input type="radio" name="action" value="fileop" {if $smarty.request.action == "fileop"}checked="checked" {/if}/>
    {$msg_copy_or_move}
  </td>
  <td>
    {html_options name='file_action' options=$fileops selected=$smarty.request.file_action}
    <select name="fileop_sfile">
{foreach from=$childfiles item=entry}
    <option value="{$entry.file}" {if $entry.file == $smarty.request.fileop_sfile}selected="selected" {/if}>{$entry.file}</option>
{/foreach}  
    </select>
    {$msg_fileop_to}
    {html_options name='fileop_ddir' options=$fileop_ddirs selected=$smarty.request.fileop_ddir}
  </td>
</tr>
<tr>  
  <td><input type="radio" name="action" value="file_create" {if $smarty.request.action == "file_create"}checked="checked" {/if}/> {$msg_create}</td>
  <td><input type="text" name="createfile" value="{$smarty.request.createfile}" /></td>
</tr>
</table>
</form>
{/if}

{include file='page-browser.tpl'}

