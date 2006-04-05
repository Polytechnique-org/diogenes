{literal}
<script type="text/javascript">
  <!--
  function page_delete( mypage, myloc ) {
    if (confirm ("You are about to delete the page '" + myloc + "' and all its contents. Do you want to proceed?")) {
      document.operations.action.value = "page_delete";
      document.operations.target.value = mypage;
      document.operations.submit();
      return true;
    }
  }  
  function file_delete( mypage, myfile ) {
    if (confirm ("You are about to delete the file '" + myfile + "'. Do you want to proceed?")) {
      document.operations.action.value = "file_delete";
      document.operations.target.value = myfile;
      document.operations.submit();
      return true;
    }
  }  
  function file_rename( mypage, myfile ) {
    if ((newname = prompt ("Rename '" + myfile + "' to", myfile)) != null) {
      document.operations.action.value = "file_rename";
      document.operations.target.value = myfile;
      document.operations.newname.value = newname;
      document.operations.submit();
      return true;
    }
  }
  // -->
</script>
{/literal}

<form method="post" action="{$post}" name="operations">
<input type="hidden" name="dir" value="{$smarty.request.dir}" />
<input type="hidden" name="target" value=""/>
<input type="hidden" name="newname" value=""/>
<input type="hidden" name="action" value="" />
</form>

<table class="light" style="width:90%">
{if $childpages}
<tr>
  <th>{$msg_location}</th>
  <th>{$msg_access}</th>
  <th colspan="3">{$msg_title}</th>
  <th>{$msg_actions}</th>
</tr>
{counter start=0 assign=cnt print=0}
{foreach from=$childpages item=page}
<tr{if $cnt % 2} class="odd"{/if}>
  <td>
  <img class="fileicon" src="{$page.icon}" />
  <a href="{$page.click}">{if $page.location==''}<i>home</i>{else}{$page.location}{/if}</a>
  </td>
  <td><img class="fileicon" alt="{$msg_read_perms} : {$page.perms}" title="{$msg_read_perms} : {$page.perms}" src="{$page.iperms}" /><img class="fileicon" alt="{$msg_write_perms} : {$page.wperms}" title="{$msg_write_perms} : {$page.wperms}" src="{$page.iwperms}" /></td>  
  <td colspan="3"><b>{$page.title|escape}</b></td>
  <td>
{foreach from=$page.actions item=action}
{a lnk=$action class="action"}
{/foreach}
  </td>
</tr>
{counter}
{/foreach}
{/if}
{if $childfiles}
<tr>
  <th>{$msg_file}</th>
  <th>{$msg_version}</th>
  <th>{$msg_date}</th>
  <th>{$msg_author}</th>
  <th>{$msg_size}</th>
  <th>{$msg_actions}</th>
</tr>
{counter start=0 assign=cnt print=0}
{foreach from=$childfiles item=entry}
<tr{if $cnt % 2} class="odd"{/if}>
  <td><img class="fileicon" src="{$entry.icon}" alt="" />{a lnk=$entry.file}</td>
  <td>{a lnk=$entry.rev}</td>
  <td class="logdate">{$entry.date}</td>
  <td class="logauthor">{$entry.author}</td>
  <td class="filesize">{$entry.size}</td>
  <td>
{foreach from=$entry.actions item=lnk}
{a lnk=$lnk class="action" title=$lnk[0]}
{/foreach}
  </td>
</tr>
{counter}
{/foreach}
{/if}
</table>
