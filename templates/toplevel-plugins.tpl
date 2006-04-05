{if 0 && !$plug_barrel}
<h3>{$msg_clean_database}</h3>

<form method="post" action="{$post}">
<input type="hidden" name="action" value="clean" />
<p>
  {$msg_clean_database_text}
  <input type="submit" value="{$msg_clean}" />
</p>
</form>
{/if}

<h3>{$msg_enable_disable}</h3>
<p>  
  {$msg_enable_disable_text}
</p>

{$editor_content}