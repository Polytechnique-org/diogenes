{literal}
<script type="text/javascript">
<!--
function doUpdate()
{
  document.modif.file_content.value = document.Kafenio.getDocumentBody();
  document.modif.submit();
}
-->
</script>
{/literal}

<APPLET CODEBASE="." ARCHIVE="kafenio.jar,gnu-regexp-1.1.4.jar,kafenio-config.jar,kafenio-icons.jar" CODE="de.xeinfach.kafenio.KafenioApplet.class" NAME="Kafenio" WIDTH="800" HEIGHT="600" MAYSCRIPT>
	<PARAM NAME="BASEURL" VALUE="{$dirloc}">
	<PARAM NAME="CODEBASE" VALUE=".">
	<PARAM NAME="CODE" VALUE="de.xeinfach.kafenio.KafenioApplet.class">
	<PARAM NAME="ARCHIVE" VALUE="kafenio.jar,gnu-regexp-1.1.4.jar,kafenio-config.jar,kafenio-icons.jar">

	<PARAM NAME="NAME" VALUE="Kafenio">
	<PARAM NAME="TYPE" VALUE="application/x-java-applet;version=1.3">
	<PARAM NAME="SCRIPTABLE" VALUE="true">
	<PARAM NAME="BASE64" VALUE="true">
	<PARAM NAME="STYLESHEET" VALUE="{$cssfiles}">
	<PARAM NAME="LANGCODE" VALUE="en">
	<PARAM NAME="LANGCOUNTRY" VALUE="US">
	<PARAM NAME="TOOLBAR" VALUE="true">
	<PARAM NAME="TOOLBAR2" VALUE="true">

	<PARAM NAME="MENUBAR" VALUE="true">
	<PARAM NAME="SOURCEVIEW" VALUE="false">
	<PARAM NAME="MENUICONS" VALUE="true">
	<!-- all available toolbar items: NEW,OPEN,SAVE,CUT,COPY,PASTE,UNDO,REDO,FIND,BOLD,ITALIC,UNDERLINE,STRIKE,SUPERSCRIPT,SUBSCRIPT,ULIST,OLIST,CLEARFORMATS,INSERTCHARACTER,ANCHOR,VIEWSOURCE,STYLESELECT,LEFT,CENTER,RIGHT,JUSTIFY,DEINDENT,INDENT,IMAGE,COLOR,TABLE,SAVECONTENT,DETACHFRAME,SEPARATOR -->
	<PARAM NAME="BUTTONS" VALUE="CUT,COPY,PASTE,SEPARATOR,BOLD,ITALIC,UNDERLINE,SEPARATOR,LEFT,CENTER,RIGHT,justify,SEPARATOR,STYLESELECT,DETACHFRAME">	
	<PARAM NAME="BUTTONS2" VALUE="ULIST,OLIST,SEPARATOR,UNDO,REDO,SEPARATOR,DEINDENT,INDENT,SEPARATOR,ANCHOR,SEPARATOR,IMAGE,SEPARATOR,CLEARFORMATS,SEPARATOR,VIEWSOURCE,SEPARATOR,STRIKE,SUPERSCRIPT,SUBSCRIPT,INSERTCHARACTER,SEPARATOR,FIND,COLOR,TABLE,SEPARATOR,SAVECONTENT">
	<!-- all available menuitems: <PARAM NAME="MENUITEMS" VALUE="FILE,EDIT,VIEW,FONT,FORMAT,INSERT,TABLE,FORMS,SEARCH,TOOLS,HELP,DEBUG"> -->
	<PARAM NAME="MENUITEMS" VALUE="EDIT,VIEW,FONT,FORMAT,INSERT,TABLE,FORMS,SEARCH,TOOLS,HELP">
<!--	<PARAM NAME="SERVLETURL" VALUE="http://www.xeinfach.de/media/projects/kafenio/demo/getDemoData.php"> -->

<!--	<PARAM NAME="IMAGEDIR" VALUE="{$dirloc}"> -->
<!--	<PARAM NAME="FILEDIR" VALUE="{$dirloc}"> -->
	<PARAM NAME="SERVLETMODE" VALUE="cgi">
	<PARAM NAME="SYSTEMID" VALUE="">
<!--	<PARAM NAME="POSTCONTENTURL" VALUE="http://www.xeinfach.de/media/projects/kafenio/demo/postTester.php"> -->
	<PARAM NAME="CONTENTPARAMETER" VALUE="mycontent">
	<PARAM NAME="OUTPUTMODE" VALUE="normal">
	<PARAM NAME="BGCOLOR" VALUE="#FFFFFF">
	<PARAM NAME="DEBUG" VALUE="true">

	<PARAM NAME="DOCUMENT" VALUE="{$file_content}">
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
