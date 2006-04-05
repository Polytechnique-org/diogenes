<script type="text/javascript" src="{$md5}"></script>
{literal}
<script type="text/javascript">
<!--
  function doChallengeResponse() {
    str = document.getElementById('main_login').value + ":" +
          MD5(document.getElementById('main_password').value) + ":" +
          document.getElementById('sub_challenge').value;

    document.getElementById('sub_response').value = MD5(str);
    document.getElementById('sub_login').value = document.getElementById('main_login').value;
    document.getElementById('main_password').value = "";
    document.getElementById('sub_form').submit();
  }
// -->
</script>
{/literal}

<form method="post" action="{$post}" onsubmit="doChallengeResponse(); return false;">
<table class="light">
<tr>
  <th colspan="2">{$msg_connexion}</th>
</tr>
<tr>
  <td>{$msg_username}</td>
  <td><input type="text" id="main_login" name="login" value="{$username}" /></td>
</tr>
<tr>
  <td>{$msg_password}</td>
  <td><input type="password" id="main_password" name="password" /></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="{$msg_submit}"/></td>
</tr>
</table>
</form>

 <!-- Set up the form with the challenge value and an empty reply value //-->
<form method="post" action="{$post}" id="sub_form">
  <input type="hidden" id="sub_challenge" name="challenge" value="{$challenge}" />
  <input type="hidden" id="sub_response" name="response" value="" />
  <input type="hidden" id="sub_login" name="login" value="" />
</form>

<script type="text/javascript">
<!--
  // Activate the appropriate input form field.
{if $username}
{literal}
  document.loginmain.password.focus();
{/literal}
{else}
{literal}
  document.loginmain.login.focus();
{/literal}
{/if}
// -->
</script>
