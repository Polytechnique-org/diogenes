{if $doedit}

{* we are in editor mode *}
<form method="post" action="{$post}">
<input type="hidden" name="action" value="modifier" />
<input type="hidden" name="MID" value="{$MID}" />
<input type="hidden" name="MIDpere" value="{$MIDpere}" />

<table class="light">
<tr>
  <th colspan="3">{$msg_prop}</th>
</tr>
<tr>
  <td>{$msg_title}</td>
  <td colspan="2">
    <input type="text" name="title" value="{$title|escape}" size="30" maxlength="255" />
  </td>
</tr>
<tr>
  <td>{$msg_type}</td>
  <td colspan="2">
    <input type="radio" name="typelink" value="boutonZ" {$chk_z}>
    {$msg_type_z}
  </td>
<tr>
  <td>&nbsp;</td>
  <td>
    <input type="radio" name="typelink" value="boutonPI" {$chk_pi}>
    {$msg_type_pi}
  </td>
  <td>
    <select name="PIvaleur">
    {html_options options=$page_opts selected=$page_sel}
    </select>
  </td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
    <input type="radio" name="typelink" value="boutonSE" {$chk_se}>
    {$msg_type_se}
  </td>
  <td>
    <input type="text" name="SEvaleur" value="{$SEvaleur}" size='30' maxlength='65535'>
  </td>
</tr>
<tr>
  <td colspan="3">
  <input type="submit" value="{$submit}">
  </th>
</tr>
</table>

</form>

{else}

{* we are viewing the list *}

<p>{$msg_ext}</p>

<p><b>{$msg_menubar}</b> : {toolbar lnk=$menubar}</p>

<table class="light">
<tr>
  <th>&nbsp;</th>
  <th>{$msg_menu}</th>
  <th>{$msg_link}</th>
  <th colspan="2">{$msg_actions}</th>
</tr>
{counter start=0 assign=cnt print=0}
{foreach from=$entries item=entry}
<tr{if $cnt % 2} class="odd"{/if}>
  <td><u>{$entry[0]}</u></td>
  <td>{a lnk=$entry[1]}</td>
  <td>{$entry[2]}</td>
  <td>
    {a lnk=$entry[3] class='action'}
    {a lnk=$entry[4] class='action'}
  </td>
  <td>
    {a lnk=$entry[5] class='action'}
    {a lnk=$entry[6] class='action'}
  </td>
</tr>
{counter}
{/foreach}
</table>

<br/>

<form method="get" action="{$script}" name="formulaire">
<input type="hidden" name="action" value="editer" />
<input type="hidden" name="MIDpere" value="{$MIDpere}" />
<input type="submit" value="{$submit}">
</form>

{/if}
