{if $db_trace or $call_trace or $plugins_trace}
{literal}
<script type="text/javascript">
<!--
function show_debug_trace( title, trace )
{
  _diogenes_debug = window.open("",title.value,"width=680,height=600,resizable,scrollbars=yes");
  _diogenes_debug.document.write("<HTML><HEAD>");
  _diogenes_debug.document.write("<TITLE>" + title + "</TITLE>");
  {/literal}_diogenes_debug.document.write("<link rel=\"stylesheet\" href=\"{$debug_css}\" type=\"text/css\" />");{literal}
  _diogenes_debug.document.write("</HEAD>");
  _diogenes_debug.document.write("<BODY style=\"padding: 10px;\">");
  _diogenes_debug.document.write("<h2>" + title + "</h2>");
  _diogenes_debug.document.write(trace);
  _diogenes_debug.document.write("</BODY></HTML>");
  _diogenes_debug.document.close();
}

function show_db_trace()
{
  {/literal}show_debug_trace('{$msg_debug_dbtrace}', '{$db_trace|escape:"javascript"}');{literal}
}

function show_call_trace()
{
  {/literal}show_debug_trace('{$msg_debug_calltrace}', '{$call_trace|escape:"javascript"}');{literal}
}

function show_plugins_trace()
{
  {/literal}show_debug_trace('{$msg_debug_plugins}', '{$plugins_trace|escape:"javascript"}');{literal}
}

// -->
</script>        
{/literal}
<div id="debug">
<div class="title">{$msg_debug_bar}</div>
{if $db_trace}
<div class="item" id="db-trace"><a href="javascript:show_db_trace();">{$msg_debug_dbtrace}</a></div>
{/if}
{if $call_trace}
<div class="item" id="call-trace"><a href="javascript:show_call_trace();">{$msg_debug_calltrace}</a></div>
{/if}
{if $plugins_trace}
<div class="item" id="plugins-trace"><a href="javascript:show_plugins_trace();">{$msg_debug_plugins}</a></div>
{/if}
</div>
{/if}
