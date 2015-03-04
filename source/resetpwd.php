<?PHP
require_once("./include/membersite_config.php");

$attemptedReset = false;
$success = false;
if(isset($_POST['submitted']))
{
	$attemptedReset = true;
	if($fgmembersite->ResetPassword(SanitizeEmail($_POST['email']), SanitizeHex($_POST['code']), $_POST['password'], SanitizeHex($_POST['confirmpassword']), SanitizeHex($_POST['salt'])))
	{
	    $success=true;
	} 
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title>Reset Password</title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
      <script type='text/javascript' src='scripts/pbkdf2.js'></script>
      <script type='text/javascript' src='scripts/enc-utf16-min.js'></script>
</head>
<body>
<div id='fg_membersite'>
<?php
		if($success){
		?>
		<h2>Password Reset Successful</h2>
		<p>You have successfully reset your password.</p>
		<div class='container'>
                    <div class="button accept submit" id="login" name='login' onclick="window.location.replace('/login.php');">Login</div>
                </div>
		<?php
		} else if (!$attemptedReset) {
		?>
        <fieldset>
		<legend><h2>Provide a New Password</h2></legend>
		<form name="resetpwd" id="resetpwd" action='<?php echo $fgmembersite->GetSelfScript(); ?>' method='post'>
		<div class='container'>
		    <label for='password' >New Password:</label>
		    <input type='password' name='password' id='password' onkeydown="handlePassword(event)"/>
		    <div id='register_password_errorloc' class='error' style='clear:both'></div>
		</div>
		<div style="display:none;">
		    <input type='password' name='salt' id='salt' />
		</div>
		<div style="display:none;">
                    <input type='password' name='code' id='code' value="<?php echo $_GET['code']?>"/>
                </div>
		<div style="display:none;">
                    <input type='password' name='email' id='email' value="<?php echo $_GET['email']?>" />
                </div>
		<div style="display:none;">
                    <input type='password' name='submitted' id='submitted' value="true" />
                </div>
		<div class='container'>
		    <div class="submit" id="submit" name='Submit' onclick="submit();">Submit</div>
		</div>
		</form>
        </fieldset>
		<?php
		} else {
		?>
		<h2>Error</h2>
		<span class='error'><?php echo $fgmembersite->GetErrorMessage(); ?></span>
		<?php
		}
		?>
</div>

<script>

    function submit() {
    <?php
        if($fgmembersite->clientSidePasswordHashing)
        {?>
            salt = '<?php echo bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));?>';
            document.forms['resetpwd'].salt.value = salt;
            document.forms['resetpwd'].password.value = CryptoJS.PBKDF2(document.getElementById("password").value, salt, { keySize: 160/32, iterations: 1000 }).toString();
        <?php }
    ?>
        document.forms['resetpwd'].submit();
    }

    function handlePassword(event){
        if(event.keyCode == 13) {
            submit();
        }
    }	

</script>

</body>
</html>