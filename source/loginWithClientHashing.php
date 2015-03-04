<?PHP
require_once("./include/membersite_config.php");

if($fgmembersite->CheckLogin())
{
	$fgmembersite->RedirectToURL("login-home.php");
}
if(isset($_POST['submitted']))
{
   if($fgmembersite->Login())
   {
        $fgmembersite->RedirectToURL("login-home.php");
   }
}

// get the CSRF token so we are ready to make authenticated requests
$CSRFtoken = $_SESSION['CSRFtoken'];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
<head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
      <title>Login</title>
      <link rel="STYLESHEET" type="text/css" href="style/fg_membersite.css" />
      <script type='text/javascript' src='scripts/pbkdf2.js'></script>
      <script type='text/javascript' src='scripts/enc-utf16-min.js'></script>
	  <script type='text/javascript' src='scripts/ajax.js'></script>
      <script type='text/javascript' src="scripts/spin.js"></script>
</head>

<style>
    #login {display:none;}
</style>

<body>

<!-- Form Code Start -->
<div id='fg_membersite'>
<div id="getSalt">
<fieldset >
<legend>Login</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>

<div><span class='error'><?php echo $fgmembersite->GetErrorMessage(); ?></span></div>
<div class='container'>
    <label for='username' >UserName*:</label><br/>
    <input type='text' name='username' id='usernamePre' value='<?php echo $fgmembersite->SafeDisplay('username') ?>' maxlength="64" onkeydown="handleUsername(event);"/><br/>
    <span id='login_username_errorloc' class='error'></span>
</div>
<div class='container'>
    <div id="getSaltSubmit" class="button submit" name='Submit' onclick="getTheirSalt();">Submit</div>
</div>
<div class='short_explanation'><a href='reset-pwd-req.php'>Forgot Password?</a></div>
<div class='short_explanation'><a href='register.php'>Need to Register?</a></div>
</fieldset>
</div>


<div id="login">
<fieldset>
<legend>Login</legend>

<input type='hidden' name='submitted' id='submitted' value='1'/>

<div class='short_explanation'>* required fields</div>

<div><span class='error'><?php echo $fgmembersite->GetErrorMessage(); ?></span></div>
<div class='container'>
    <label for='username' >UserName*:</label><br/>
    <input type='text' name='username' id='username' value='<?php echo $fgmembersite->SafeDisplay('username') ?>' maxlength="64" onkeyup="returnToUsername();"/><br/>
    <span id='login_username_errorloc' class='error'></span>
</div>
<div class='container'>
    <label for='password' >Password*:</label><br/>
    <input type='password' name='password' id='password' onkeydown="handlePassword(event)"/><br/>
    <span id='login_password_errorloc' class='error'></span>
</div>

<div class='container'>
    <div class="button submit" name='Submit' onclick="submit('login','password');">Submit</div>
</div>
<div class='short_explanation'><a href='reset-pwd-req.php'>Forgot Password?</a></div>
</fieldset>
</form>
</div>
<div id="loadInProgress"></div>

<script type='text/javascript'>

    var salt;
    document.getElementById('usernamePre').select();
    function getTheirSalt() {
        var username = document.getElementById('usernamePre').value.toLowerCase();
        var URL = "get_salt.php?username=" + username + "&preventCaching=" + new Date().getTime();
        getWithAjax(URL, function (response) {
                salt = response;
                if(salt == "BVrequired") {
                        window.location="register-browser-req.php";
                } else {
                        document.getElementById("getSalt").style.display = 'none';
                        document.getElementById("username").value = username;
                        document.getElementById("password").value = "";
                        document.getElementById("login").style.display = 'block';
                        document.getElementById('password').select();
                        document.getElementById('password').focus();
                }
        });
    }


    function returnToUsername() {
        document.getElementById("getSalt").style.display = 'block';
        document.getElementById("login").style.display = 'none';
        document.getElementById("usernamePre").value = document.getElementById('username').value;
        document.getElementById("usernamePre").focus();
    }

    function submit() {
        showSpinner();

        var form = document.createElement("form");
        form.setAttribute('method',"post");
        form.setAttribute('action',"login.php");

        var username = document.createElement("input");
        username.setAttribute('type',"text");
        username.setAttribute('name',"username");
        username.setAttribute('value',document.getElementById("username").value);

        var password = document.createElement("input");
        password.setAttribute('type',"password");
        password.setAttribute('name',"password");
        password.setAttribute('value',CryptoJS.PBKDF2(document.getElementById("password").value, salt, { keySize: 160/32, iterations: 1000 }).toString());

        var submitted = document.createElement("input");
        submitted.setAttribute('type',"text");
        submitted.setAttribute('name',"submitted");
        submitted.setAttribute('value','true');
        
        var username = document.createElement("input");
                username.setAttribute('type',"text");
                username.setAttribute('name',"CSRFtoken");
                username.setAttribute('value', <?php echo $CSRFtoken ?>;

        form.appendChild(username);
        form.appendChild(password);
        form.appendChild(submitted);
        form.style.display = 'none';

        document.getElementsByTagName('body')[0].appendChild(form);

        form.submit();
    }

    function handleUsername(event){
        if(event.keyCode == 13) {
                getTheirSalt();
        }
    }

    function handlePassword(event){
        if(event.keyCode == 13) {
                submit();
        }
    }

    function showSpinner() {
        var opts = {
                lines: 14, // The number of lines to draw
                length: 110, // The length of each line
                width: 14, // The line thickness
                radius: 40, // The radius of the inner circle
                corners: 1, // Corner roundness (0..1)
                rotate: 45, // The rotation offset
                direction: 1, // 1: clockwise, -1: counterclockwise
                color: '#009900', // #rgb or #rrggbb or array of colors
                speed: 0.5, // Rounds per second
                trail: 30, // Afterglow percentage
                shadow: false, // Whether to render a shadow
                hwaccel: true, // Whether to use hardware acceleration
                className: 'spinner', // The CSS class to assign to the spinner
                zIndex: 2e9, // The z-index (defaults to 2000000000)
                top: '50%', // Top position relative to parent
                left: '50%' // Left position relative to parent
        };
        var target = document.getElementById('loadInProgress');
        target.style.display = "block";
        var spinner = new Spinner(opts).spin();
        target.appendChild(spinner.el);
    }

window.scrollTo(0,0);
</script>
</div>
<!--
Form Code End (see html-form-guide.com for more info.)
-->

</body>
</html>