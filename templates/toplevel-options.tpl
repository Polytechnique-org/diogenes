<form method="post" action="{$post}">
<input type="hidden" name="action" value="options" />
<table class="light" style="width:80%">
<tr>
  <th colspan="2">{$msg_display_options}</th>
</tr>
<tr>
  <td>{$msg_menu_style}</td>
  <td>{html_options name='menu_style' selected=$menu_style options=$menu_styles}</td>
</tr>
{if $menu_themes}
<tr>
  <td>{$msg_menu_theme}</td>
  <td>{html_options name='menu_theme' selected=$menu_theme options=$menu_themes}</td>
</tr>
{/if}
<tr>
  <td>{$msg_site_template_dir}</td>
  <td><input type="text" name="template_dir" value="{$template_dir}" size="30" /></td>
</tr>
<tr>
  <td>{$msg_site_template}</td>
  <td>{html_options name='template' selected=$template options=$templates}</td>
</tr>
<tr>
  <td>{$msg_site_style_sheet}</td>
  <td>{html_options name='barrel_style_sheet' selected=$barrel_style_sheet options=$style_sheets}</td>
<tr>
  <td>{$msg_validate_pages}</td>
  <td><input type="checkbox" name="validatepages"{if $validatepages} checked="checked"{/if}/></td>
</tr>
</table>

<br/>

<table class="light" style="width:80%">
<tr>
  <th colspan="2">{$msg_system_options}</th>
</tr>
<tr>
  <td>{$msg_html_editor}</td>
  <td>{html_options name='html_editor' selected=$html_editor options=$html_editors}</td>
</tr>
<tr>
  <td>{$msg_word_import}</td>
  <td>{html_options name='word_import' selected=$word_import options=$word_imports}</td>
</tr>
</table>

<br/>

<table class="light" style="width:80%">
<tr>
  <th colspan="2">{$msg_debug_options}</th>
</tr>
<tr>
  <td>{$msg_debug_database}</td>
  <td><input type="checkbox" name="debugdatabase"{if $debugdatabase} checked="checked"{/if}/></td>
</tr>
<tr>
  <td>{$msg_debug_plugins}</td>
  <td><input type="checkbox" name="debugplugins"{if $debugplugins} checked="checked"{/if}/></td>
</tr>
</table>

<p>
  <input type="submit" value="{$msg_submit}" />
</p>

</form>

