{literal}
<script type="text/javascript">
<!--
function doUpdate()
{
  document.modif.file_content.value = document.Ekit.getDocumentBody();
  document.modif.submit();
}
-->
</script>
{/literal}


<APPLET CODEBASE="." CODE="com.hexidec.ekit.EkitApplet.class" ARCHIVE="ekitapplet.jar" NAME="Ekit" WIDTH="800" HEIGHT="600" MAYSCRIPT>
	<PARAM NAME="codebase" VALUE=".">
	<PARAM NAME="code" VALUE="com.hexidec.ekit.EkitApplet.class">
	<PARAM NAME="archive" VALUE="ekitapplet.jar">
	<PARAM NAME="name" VALUE="Ekit">
	<PARAM NAME="type" VALUE="application/x-java-applet;version=1.4">
	<PARAM NAME="scriptable" VALUE="true">
	<PARAM NAME="BASEURL" VALUE="{$dirloc}">
	<PARAM NAME="DOCUMENT" VALUE="{$file_content}">
	<PARAM NAME="BASE64" VALUE="true">
	<PARAM NAME="STYLESHEET" VALUE="{$cssfiles}">
	<PARAM NAME="LANGCODE" VALUE="en">
	<PARAM NAME="LANGCOUNTRY" VALUE="US">
	<PARAM NAME="TOOLBAR" VALUE="true">
	<PARAM NAME="SOURCEVIEW" VALUE="false">
	<PARAM NAME="EXCLUSIVE" VALUE="true">
	<PARAM NAME="MENUICONS" VALUE="true">
	<PARAM NAME="MENU_EDIT" VALUE="true">
	<PARAM NAME="MENU_VIEW" VALUE="true">
	<PARAM NAME="MENU_FONT" VALUE="true">
	<PARAM NAME="MENU_FORMAT" VALUE="true">
	<PARAM NAME="MENU_INSERT" VALUE="true">
	<PARAM NAME="MENU_TABLE" VALUE="true">
	<PARAM NAME="MENU_FORMS" VALUE="true">
	<PARAM NAME="MENU_SEARCH" VALUE="true">
	<PARAM NAME="MENU_TOOLS" VALUE="true">
	<PARAM NAME="MENU_HELP" VALUE="true">
</APPLET>


<form name="modif" method="post" action="{$post}">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="file" value="{$file}" />
<input type="hidden" name="dir" value="{$dir}" />
<input type="hidden" name="file_content" value="" />

<table class="light" style="width:800px">
<tr>
  <td>
    {$msg_log}
    <input type="text" name="message" size="50" />
    <input type="button" value="update" onClick="doUpdate();" />
  </td>
</tr>
</table>

</form>
