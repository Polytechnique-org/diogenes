<table class="light" style="width:60%">
<tr>
  <th>{$msg_lang}</th>
</tr>
<tr>
  <td>
    {$msg_lang_blab}
    <ul>
{foreach key=key item=item from=$langs}
    <li><a href="?nlang={$key}">{$item}</a></li>
{/foreach}
    </ul>
  </td>
</tr>
</table>

<p>
  <a href="{$global_prefs}">{$msg_global_prefs}</a>
</p>
