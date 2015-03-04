<?PHP
require_once("./include/membersite_config.php");

if(!$fgmembersite->CheckLogin())
{
    $fgmembersite->RedirectToURL("login.php");
    exit;
}

if(isset($_POST['submitted']))
{
   if($fgmembersite->ChangePassword())
   {
        $fgmembersite->RedirectToURL("changed-pwd.html");
   }
}

// get the CSRF token so we are ready to make authenticated requests
$CSRFtoken = $_SESSION['CSRFtoken'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title>Change password</title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
      <script type='text/javascript' src='scripts/gen_validatorv31.js'></script>
      <link rel="STYLESHEET" type="text/css" href="style/pwdwidget.css" />
      <script src="scripts/pwdwidget.js" type="text/javascript"></script>       
      <script type='text/javascript' src='scripts/pbkdf2.js'></script>
      <script type='text/javascript' src='scripts/enc-utf16-min.js'></script>
</head>
<body>

<!-- Form Code Start -->
<div id='fg_membersite'>
<form id='changepwd' action='<?php echo $fgmembersite->GetSelfScript(); ?>' method='post' accept-charset='UTF-8'>
<fieldset >
<legend>Change Password</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>

<div><span class='error'><?php echo $fgmembersite->GetErrorMessage(); ?></span></div>
<div class='container'>
    <label for='pwd' >Old Password*:</label><br/>
    <div class='pwdwidgetdiv' id='oldpwddiv' ></div><br/>
    <noscript>
    <input type='password' name='pwd' id='pwd' maxlength="80" />
    </noscript>    
    <span id='changepwd_oldpwd_errorloc' class='error'></span>
</div>

<div class='container'>
    <label for='newpwd' >New Password*:</label><br/>
    <div class='pwdwidgetdiv' id='newpwddiv' ></div>
    <noscript>
    <input type='password' name='newpwd' id='newpwd' onkeydown="handlePassword(event)"/><br/>
    </noscript>
    <span id='changepwd_newpwd_errorloc' class='error'></span>
</div>

<input type='hidden' name='CSRFtoken' value='<?php echo $CSRFtoken ?>' />

<br/><br/><br/>
<div class='container'>
    <div class="submit" id="submit" name='Submit' onclick="submit();">Submit</div>
</div>

</fieldset>
</form>
<!-- client-side Form Validations:
Uses the excellent form validation script from JavaScript-coder.com-->

<script type='text/javascript'>
// <![CDATA[
    var pwdwidget = new PasswordWidget('oldpwddiv','pwd');
    pwdwidget.enableGenerate = false;
    pwdwidget.enableShowStrength=false;
    pwdwidget.enableShowStrengthStr =false;
    pwdwidget.MakePWDWidget();
    
    var pwdwidget = new PasswordWidget('newpwddiv','newpwd');
    pwdwidget.MakePWDWidget();
    
    
    var frmvalidator  = new Validator("changepwd");
    frmvalidator.EnableOnPageErrorDisplay();
    frmvalidator.EnableMsgsTogether();

    frmvalidator.addValidation("pwd","req","Please provide your old password");
    
    frmvalidator.addValidation("newpwd","req","Please provide your new password");
    
    function submit() {
    <?php
        if($fgmembersite->clientSidePasswordHashing)
        {?>
            salt = '<?php echo bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));?>';
            document.forms['changepwd'].salt.value = salt;
            document.forms['changepwd'].password.value = CryptoJS.PBKDF2(document.getElementById("password").value, salt, { keySize: 160/32, iterations: 1000 }).toString();
        <?php }
    ?>
        document.forms['changepwd'].submit();
    }
    
    function handlePassword(event){
        if(event.keyCode == 13) {
            submit();
        }
    }	

// ]]>
</script>

<p>
<a href='login-home.php'>Home</a>
</p>

</div>
<!--
Form Code End (see html-form-guide.com for more info.)
-->

</body>
</html>