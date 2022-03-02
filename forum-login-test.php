<?php
ini_set('display_errors',true);
error_reporting(E_ALL);

define ('IN_PHPBB',true);
$phpbb_root_path="/var/www/bbs/phpbb/";
$phpEx=substr(strchr(__FILE__,'.'),1);
include ($phpbb_root_path.'common.'.$phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

if (is_array($user->data))
{
  var_dump($user->data);
}
else
{ ?>
<form method="post" action="https://forums.onigirionegai.info/ucp.php?mode=login">
<label for="username">Username: </label> <input type="text" name="username" id="username" size="40" /><br /><br />
<label for="password">Password: </label><input type="password" name="password" id="password" size="40" /><br /><br />
<label for="autologin">Remember Me?: </label><input type="checkbox" name="autologin" id="autologin" /><br /><br />
<input type="submit" value="Log In" name="login" />
<input type="hidden" name="redirect" value="https://test.onigirionegai.info/forum-login-test.php" />
</form>
<?php }