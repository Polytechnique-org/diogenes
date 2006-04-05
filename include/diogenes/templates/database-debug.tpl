{foreach item=query from=$trace_data}
<table class="light" style="width: 100%; font-family: monospace">
  <tr>
    <td><strong>QUERY:</strong><br />{$query.query}</td>
  </tr>
  {if $query.error}
  <tr>
    <td><strong>ERROR:</strong><br />{$query.error}</td>
  </tr>
  {/if}
</table>
{if $query.explain}
<table class="light" style="width: 100%; font-family: monospace">
  <tr>
    {foreach key=key item=item from=$query.explain[0]}
    <th>{$key}</th>
    {/foreach}
  </tr>
  {foreach item=explain_row from=$query.explain}
  <tr>
    {foreach item=item from=$explain_row}
    <td>{$item}</td>
    {/foreach}
  </tr>
  {/foreach}
</table>
{/if}
<br />
{/foreach}
