<table class="light" style="width:60%">
<tr>
  <th colspan="2">{$msg_myinfo}</th>
</tr>
<tr>
  <td><b>{$msg_username}</b></td>
  <td>{$username}</td>
</tr>
<tr>
  <td><b>{$msg_fullname}</b></td>
  <td>{$fullname}</td>
</tr>
</table>

<br/>

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

{if $native}
<script type="text/javascript" src="{$md5}"></script>
{literal}
<script type="text/javascript">
  <!--
  function changePass() {
    if (document.passwd.password1.value == "") {
      alert('Password cannot be empty!');
    } else if (document.passwd.password1.value != document.passwd.password2.value) {
      alert('Password mismatch!');
    } else {
      document.passwd_enc.newpass.value = MD5(document.passwd.password1.value);
      document.passwd_enc.submit();
    }
  }
  // -->
</script>
{/literal}

<form method="post" name="passwd_enc" action="{$smarty.server.PHP_SELF}">
<input type="hidden" name="action" value="passwd" />
<input type="hidden" name="newpass" value="" />
</form>

<form method="post" name="passwd" action="{$smarty.server.PHP_SELF}" onsubmit="changePass(); return false;">

<table class="light" style="width:60%">
  <tr>
    <th colspan=2>{$msg_mypassword}</th>
  </tr>
  <tr>
      <td><b>{$msg_password}</b></td>
      <td><input type="password" name="password1" /></td>

  </tr>
  <tr>
      <td><b>{$msg_confirmation}</b></td>
      <td><input type="password" name="password2" /></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input type="submit" value="{$submit}"></td>
  </tr>

</table>
</form>

{/if}
