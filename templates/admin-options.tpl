<form method="post" action="{$post}">
<input type="hidden" name="action" value="update" />
<table class="light" style="width:80%">
<tr>
  <th colspan="2">{$msg_general_options}</th>
</tr>
<tr>
  <td>{$msg_title}</td>
  <td><input type="text" name="title" value="{$title}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_description}</td>
  <td><input type="text" name="description" value="{$description}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_keywords}</td>
  <td><input type="text" name="keywords" value="{$keywords}" size="60" /></td>
</tr>
<tr>
  <td>{$msg_favicon}</td>
  <td><input type="text" name="favicon" value="{$favicon}" size="20" /> {$msg_favicon_hint}</td>
</tr>
</table>

<br/>

<table class="light" style="width:80%">
<tr>
  <th colspan="2">{$msg_display_options}</th>
</tr>
{if $template_dirs}
<tr>
  <td>{$msg_site_template_dir}</td>
  <td>{html_options name='template_dir' selected=$template_dir options=$template_dirs}</td>
</tr>
{/if}
<tr>
  <td>{$msg_site_template}</td>
  <td>{html_options name='template' selected=$template options=$templates}</td>
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
  <td>{$msg_menu_min_level}</td>
  <td>{html_options name='menu_min_level' selected=$menu_min_level options=$menu_levels}</td>
</tr>
<tr>
  <td>{$msg_menu_hide_diogenes}</td>
  <td>{html_options name='menu_hide_diogenes' selected=$menu_hide_diogenes options=$menu_hide_diogeness}</td>
</tr>
</table>

<p>
  <input type="submit" value="{$msg_submit}" />
</p>
</form>
