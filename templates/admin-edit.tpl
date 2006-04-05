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
