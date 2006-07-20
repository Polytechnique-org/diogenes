{if $msg_set_stylesheet}
<form name="form_stylesheet" method="post" action="{$post}">
<input type="hidden" name="action" value="set_stylesheet" />
<input type="hidden" name="file" value="{$file}" />
<input type="hidden" name="dir" value="{$dir}" />
<table class="light">
<tr>
  <td>{$msg_set_stylesheet}</td>
  <td>{html_options name='preset_style_sheet' selected=$preset_style_sheet options=$style_sheets}</td>
  <td><input type="submit" value="{$msg_replace}"/></td>
</tr>
</table>
</form>

<br/>

{/if}

<form name="modif" method="post" action="{$post}">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="file" value="{$file}" />
<input type="hidden" name="dir" value="{$dir}" />

<table class="light">
<tr>
  <th>{$source}</th>
</tr>
<tr>
  <td>
  <textarea name="file_content" rows="30" cols="80">{$file_content}</textarea>
  </td>
</tr>
<tr>
  <td>
    {$msg_log}
    <input type="text" name="message" size="50" />
    <input type="submit" value="{$submit}" />
  </td>
</tr>
</table>

</form>
