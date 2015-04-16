<?PHP /*
    Registration/Login script from HTML Form Guide
    V2.0
    This program is free software published under the
    terms of the GNU Lesser General Public License.
    http://www.gnu.org/copyleft/lesser.html
    
This program is distributed in the hope that it will be useful - WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. For updates, please visit: 
http://www.html-form-guide.com/php-form/php-registration-form.html http://www.html-form-guide.com/php-form/php-login-form.html */ 

/*

    0 Initialization
        0.1 Defining Global Variables
        0.2 Setting Global Variables
            0.2.1 FGMembersite()
            0.2.2 SetRandomKey($randkey)
            0.2.3 InitDB($host,$uname,$pwd,$database)
            0.2.4 SetAdminEmail($email)
            0.2.5 SetWebsiteName($sitename)
            0.2.6 EnableTwoFactorAuthenticationMode($do)
            0.2.7 EnableClientSidePasswordHashing($do)
            0.2.8 EnableTransactions($do)
    1 Main Operations
        1.1 Registering a User
            1.1.1 RegisterUser()
            1.1.2 ConfirmUser()
            1.1.3 ValidateRegistrationSubmission()
            1.1.4 CollectRegistrationSubmission(&$formvars)
            1.1.5 SaveToDatabase(&$formvars)
            1.1.6 SendUserConfirmationEmail(&$formvars)
            1.1.7 SendAdminIntimationEmail(&$formvars)
        1.2 Registering a Browser
            1.2.1 RegisterBrowser()
            1.2.2 SendBrowserConfirmationEmail($email, $username, $confirmcode)
            1.2.3 ConfirmBrowser()
            1.2.4 SendBrowserRegisteredConfirmation($email, $browserDescription)
            1.2.5 SendAdminIntimationOnRegComplete($email)
        1.3 Logging on a User
            1.3.1 Login()
            1.3.2 CheckLogin()
            1.3.3 ConfirmCSRFToken()
            1.3.4 AuthenticateChangeRequest()
        1.4 Logging out a user
            1.4.1 LogOut()
        1.5 Getting Session Variables // Likely unused, they're in $_SESSION
            1.5.1 UserFullName()
            1.5.2 UserName()
            1.5.3 UserEmail()
    2 Updating User Profiles
        2.1 Resetting a Password
            2.1.1 EmailResetPasswordLink()
            2.1.2 SendResetPasswordLink($email, $username)
            2.1.3 GetResetPasswordCode($email)
            2.1.4 ResetPassword($authcode, $newpassword, $confirmpassword, $csalt)
            2.1.5 NotifyOfNewPassword($email, $username)
        2.2 Changing a Password
            2.2.1 ChangePassword()
        2.3 Changing an Email Address
            2.3.1 ChangeEmailAddress()
        2.4 Changing a 'Real Name'
            2.4.1 ChangeName()
        2.5 Disabling a Browser
            2.5.1 DisableBrowser()
    3 Infrastructure
        3.1 PHP Helpers
            3.1.1 GetSelfScript()
            3.1.2 SafeDisplay($value_name)
            3.1.3 RedirectToURL($url)
            3.1.4 GetSpamTrapInputName()
            3.1.5 GetErrorMessage()
            3.1.6 HandleError($err)
            3.1.7 HandleDBError($err)
            3.1.8 GetFromAddress()
            3.1.9 GetAbsoluteURLFolder()
        3.2 MySQL Helpers
            3.2.1 DBLogin()
            3.2.2 EnsureTable()
            3.2.3 EnsureBrowserTable()
            3.2.4 EnsureTransactionTable()
            3.2.5 CreateTable()
            3.2.6 CreateBrowserTable()
            3.2.7 CreateTransactionTable()
            3.2.8 IsFieldUnique($column,$content)
        3.3 MySQL Actors
            3.3.1 MySQL Inserts
                3.3.1.1 InsertIntoDB(&$formvars)
                3.3.1.2 UpdateDBforBrowserVerification($email, $IP, $description)
            3.3.2 MySQL Updates
                3.3.2.1 DisableBrowserInDB($browser_id)
                3.3.2.2 UpdateDBRecForConfirmation($confirmcode)
                3.3.2.3 ResetUserPasswordInDB($username)
                3.3.2.4 ChangePasswordInDB($username, $newpwd)
                3.3.2.5 ChangeConfirmCodeInDB($email)
                3.3.2.6 ChangeEmailInDB($username, $email)
                3.3.2.7 ChangeNameInDB($username, $name)
                3.3.2.8 MarkUserAsHavingBillingInfoProblem($username)
                3.3.2.9 SetUserMessage($username, $message)
            3.3.3 MySQL Selects
                3.3.3.1 CheckLoginInDB($username,$password,$browserverification)
                3.3.3.2 GetUsernameFromEmail($email)
                3.3.3.4 GetEmailFromUsername($username)
                3.3.3.5 GetSaltFromUsername($username)
                3.3.3.6 GetSaltFromUsernamePublic($username, $browserverification)
                3.3.3.7 GetRegisteredBrowsersForCurrentUser()
                3.3.3.8 WhatWillNextUserIdBe()
        3.4 Billing Functions
            3.4.1 ValidateBillingInfo()
            3.4.2 billUser($username, $reason, $price)
        3.5 Transaction Loggers
            3.5.1 createTransaction($reason, $price)
            3.5.2 markTransactionPaid($transactionId, $transactionResult)
            
*/

require("./include/class.phpmailer.php"); 
require("./include/formvalidator.php"); 
require("./include/Sanitizers.php");

class FGMembersite {

    // 0 Initialization
    // 0.1 Defining Global Variables
    var $admin_email;
    var $from_address;
    
    var $username;
    var $pwd;
    var $database;
    var $connection;
    var $rand_key;
    
    var $newIterations;
    
    var $sessionLifeTime;
    
    var $error_message;
    
    var $passwordRequiredForAdministration;
    
    var $CSRFTokenRequired;
    
    var $clientSidePasswordHashing;
    
    var $twoFactorAuthMode;
    
    var $acceptedCreditCardTypes;
    
    // End Defining Global Variables
    
    // 0.2 Setting Global Variables
    function FGMembersite()
    {
        $this->newIterations = 100000;
        $this->sessionLifeTime = 1800;
    }
    
    function SetRandomKey($randkey)
    {
        $this->rand_key = $randkey;
    }
    
    function InitDB($host,$uname,$pwd,$database)
    {
        $this->db_host = $host;
        $this->username = $uname;
        $this->pwd = $pwd;
        $this->database = $database;
    }
    
    function SetAdminEmail($email)
    {
        $this->admin_email = SanitizeEmail($email);
    }
    
    function SetWebsiteName($sitename)
    {
        $this->sitename = $sitename;
    }
    
    function SetSessionLifetimeInMinutes($minutes) {
        $this->sessionLifeTime = $minutes * 60;
    }
    
    function EnablePasswordRequiredForAdministration($do)
    {
        $this->passwordRequiredForAdministration = $do;
    }
    
    function EnableTwoFactorAuthenticationMode($do) {
        $this->twoFactorAuthMode = $do;
        if ($this->clientSidePasswordHashing && $this->twoFactorAuthMode === false) {
            error_log("You cannot do this, this software would provide unnecessary confirmation of usernames if you used client-side password hashing in the manner utilized by this application without two-factor authentication");
            exit;
        }
        if ($do) {
            $this->EnsureBrowserTable();
        }
    }
    
    function EnableCSRFTokenRequired($do)
    {
        $this->CSRFTokenRequired = $do;
    }
    
    function EnableClientSidePasswordHashing($do) {
        $this->clientSidePasswordHashing = $do;
        if ($do) {
            $this->EnableTwoFactorAuthenticationMode(true);
        }
    }
    
    function EnableTransactions($do) {
        if ($do) {
            $this->EnsureTransactionTable();
        }
    }
    
    function SetAcceptedCreditCards($array) {
        $this->acceptedCreditCardTypes = $array;
    }
    
    // End Setting Global Variables
    
    // 1 Main Operations
    
    // 1.1 Registering a User
    function RegisterUser()
    {
        if(!isset($_POST['submitted']))
        {
           return false;
        }
        
        $formvars = array();
        
        if(!$this->ValidateRegistrationSubmission())
        {
            return false;
        }
        
        $this->CollectRegistrationSubmission($formvars);
    
        if(!$this->SaveToDatabase($formvars))
        {
            return false;
        }
        
        if(!$this->SendUserConfirmationEmail($formvars))
        {
            return false;
        }
        $this->SendAdminIntimationEmail($formvars);
        
        return true;
    }
    
    function ConfirmUser()
    {
        if(empty($_GET['code']))
        {
            $this->HandleError("Please provide the confirm code");
            return false;
        }
        
        if(!$this->UpdateDBRecForConfirmation(SanitizeHex($_GET['code'])))
        {
            return false;
        }
        
        return true;
    } 
    
    function ValidateRegistrationSubmission()
    {
        //This is a hidden input field. Humans won't fill this field.
        if(!empty($_POST[$this->GetSpamTrapInputName()]) )
        {
            //The proper error is not given intentionally
            $this->HandleError("Automated submission prevention: case 2 failed");
            return false;
        }
        
        $validator = new FormValidator();
        $validator->addValidation("email","email","The input for Email should be a valid email value");
        $validator->addValidation("email","req","Please fill in an Email address");
        $validator->addValidation("email","maxlen=64","Your email address is too long");
        $validator->addValidation("username","req","Please fill in Username");
        $validator->addValidation("username","alnum","Please use only letters and numbers for your Username");
        $validator->addValidation("username","maxlen=64","Please limit your username to 64 characters");
        $validator->addValidation("name","maxlen=64","Your \"real name\" is too long");
        $validator->addValidation("password","req","Please fill in Password");
        
        if(!$validator->ValidateForm())
        {
            $error='';
            $error_hash = $validator->GetErrors();
            foreach($error_hash as $inpname => $inp_err)
            {
                $error .= $inpname.':'.$inp_err."\n";
            }
            $this->HandleError($error);
            return false;
        }        
        
        return true;
    }
    
    function CollectRegistrationSubmission(&$formvars)
    {
        $formvars['name'] = SanitizeRealName($_POST['name']);
        $formvars['email'] = SanitizeEmail($_POST['email']);
        $formvars['username'] = SanitizeUsername($_POST['username']);
        $formvars['password'] = $_POST['password'];
        if ($this->clientSidePasswordHashing) {
            $formvars['salt'] = SanitizeHex($_POST['salt']);
        } else {
            $formvars['salt'] = '';
        }
    }
    
    function SaveToDatabase(&$formvars)
    {
        if(!$this->DBLogin())
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if(!$this->EnsureTable())
        {
            return false;
        }
    
        if(!$this->IsFieldUnique('email', $formvars['email']))
        {
            $this->HandleError("This email is already registered, if you did this, <a href=\"/reset-pwd-req.php\">try the password reset process.</a>");
            return false;
        }
        
        if(!$this->IsFieldUnique('username', $formvars['username']))
        {
            $this->HandleError("This username is already used. Please try another username");
            return false;
        }        
        
        if(!$this->InsertIntoDB($formvars))
        {
            $this->HandleError("Inserting to Database failed!");
            return false;
        }
        
        return true;
    }
    
    function SendUserConfirmationEmail(&$formvars)
    {
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress(SanitizeEmail($formvars['email']),SanitizeRealName($formvars['name']));
        
        $mailer->Subject = "Your registration with ".$this->sitename;
        $mailer->From = $this->GetFromAddress();
        
        $confirmcode = $formvars['confirmcode'];
        
        $confirm_url = $this->GetAbsoluteURLFolder().'confirmreg.php?code='.$confirmcode;
        
        $mailer->Body ="Hello ".SanitizeUsername($formvars['username'])."\r\n\r\n".
        "Thanks for your registration with ".$this->sitename."\r\n".
        "Please click the link below to confirm your registration.\r\n".
        "$confirm_url\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        if(!$mailer->Send())
        {
            $this->HandleError("Failed sending registration confirmation email.");
            return false;
        }
        return true;
    }
    
    function SendAdminIntimationEmail(&$formvars)
    {
        if(empty($this->admin_email))
        {
            return false;
        }
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress(SanitizeEmail($this->admin_email));
        
        $mailer->Subject = "New registration: ".SanitizeUsername($formvars['username']);
        $mailer->From = $this->GetFromAddress();
        
        $mailer->Body ="A new user registered at ".$this->sitename."\r\n".
        "Name: ".SanitizeRealName($formvars['name'])."\r\n".
        "Email address: ".SanitizeEmail($formvars['email'])."\r\n".
        "UserName: ".SanitizeUsername($formvars['username']);
        
        if(!$mailer->Send())
        {
            return false;
        }
        return true;
    }
    
    // End Registering a User
    
    // 1.2 Registering a Browser
    
    function RegisterBrowser()
    {
        if(!isset($_POST['submitted']))
        {
           return false;
        }

        $email = SanitizeEmail($_POST['email']);

        $confirmcode = $this->ChangeConfirmCodeInDB($email);
        
        if(false === $confirmcode) {
           $this->HandleError("We were unable to update your confirm code in the database, perhaps try your request again.");
           return false;
        }
        
        $username = SanitizeUsername($this->GetUsernameFromEmail($email));
        
        return $this->SendBrowserConfirmationEmail($email, $username, $confirmcode);
    }
    
    function SendBrowserConfirmationEmail($email, $username, $confirmcode)
    {
        $mailer = new PHPMailer();

        $mailer->CharSet = 'utf-8';

        $mailer->AddAddress(SanitizeEmail($email),SanitizeUsername($username));

        $mailer->Subject = "Your confirmation with ".$this->sitename;
        $mailer->From = $this->GetFromAddress();

        $confirmcode = SanitizeHex($confirmcode);

        $confirm_url = $this->GetAbsoluteURLFolder().'confirmreg.php?code='.$confirmcode;

        $browser = get_browser(null,true);
        $browserDescription = SanitizeBrowserName($browser["browser"]." on ".$browser["platform"]);

        $mailer->Body ="Hello ".SanitizeUsername($username)."\r\n\r\n".
        "An attempt has been made to access your account at ".$this->sitename." from a new Browser, ".$browserDescription." @ ".SanitizeFloat($_SERVER['REMOTE_ADDR'])."\r\n".
        "If this attempt was made by you, click the link below to confirm.\r\n".
        "$confirm_url\r\n".
        "You MUST open this link in the browser you wish to register.\r\n".
        "If you are opening this email on a different computer than you wish to register, go to this address and type the code in manually:\r\n".
        $this->sitename."/confirmreg.php and fill in ".$confirmcode."\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        if(!$mailer->Send())
        {
            $this->HandleError("Failed sending registration confirmation email.");
            return false;
        }
        return true;
    }

    function ConfirmBrowser()
    {
        if(empty($_GET['code'])||strlen($_GET['code'])!=8)
        {
            $this->HandleError("Please provide the confirm code");
            return false;
        }
        
        $email = $this->UpdateDBRecForConfirmation(SanitizeHex(strtolower($_GET['code'])));
        
        if(false === $email)
        {
            // the previous method provides its own errors
            return false;
        }
        $email = SanitizeEmail($email);

        $browser = get_browser(null,true);
        $browserDescription = SanitizeBrowserName($browser["browser"]." on ".$browser["platform"]);

        $verificationCode = $this->UpdateDBforBrowserVerification($email, SanitizeFloat($_SERVER['REMOTE_ADDR']), $browserDescription);
            
        $this->SendBrowserRegisteredConfirmation($email, $browserDescription);
        
        $this->SendAdminIntimationOnRegComplete($email);
            
        $username = $this->GetUsernameFromEmail($email);

        // this two element array is what is needed to set the browser verification cookie in the user's browser
        $returning['code'] = $verificationCode;
        $returning['username'] = $username;

        return $returning;
    }
    
    function SendBrowserRegisteredConfirmation($email, $browserDescription)
    {
        $email = SanitizeEmail($email);
        $username = $this->GetUsernameFromEmail($email);
        if(false === $username)
        {
            $this->HandleError("Unable to match email address to username");
            return false;
        }
    
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($email,$username);
        
        $mailer->Subject = "You have registered your web browser with ".$this->sitename;
        $mailer->From = $this->GetFromAddress();
        
        $mailer->Body ="Hello ".$username."\r\n\r\n".
        "Welcome! Your new browser has been registered with ".$this->sitename.".\r\n".
        "The newly registered browser is ".$browserDescription." @ ".SanitizeFloat($_SERVER['REMOTE_ADDR'])."\r\n".
        "\r\n".
        "If this was done in error, or not by you, please contact us immediately at ".$this->admin_email."\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        if(!$mailer->Send())
        {
            $this->HandleError("Failed sending user welcome email.");
            return false;
        }
        return true;
    }
    
    function SendAdminIntimationOnRegComplete($email)
    {
        $email = SanitizeEmail($email);
        $username = $this->GetUsernameFromEmail($email);
        
        if(false === $username)
        {
            // not critical
            return false;
        }
    
        if(empty($this->admin_email))
        {
            error_log("There is no admin_email value set");
            return false;
        }
        
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($this->admin_email);
        
        $mailer->Subject = "Browser Registration Completed: ".$username;
        $mailer->From = $this->GetFromAddress();
        
        $mailer->Body ="A new browser was registered for ".$this->sitename."\r\n".
        "Name: ".$username."\r\n".
        "Email address: ".$email."\r\n";
        
        if(!$mailer->Send())
        {
            error_log("Unable to send an email.");
            return false;
        }
        
        return true;
    }
    
    // End Registering a Browser
    
    // 1.3 Logging on a User
    
    function Login()
    {
        if(empty($_POST['username']))
        {
            $this->HandleError("UserName is empty!");
            return false;
        }
        
        if(empty($_POST['password']))
        {
            $this->HandleError("Password is empty!");
            return false;
        } 
        
        if(!isset($_SESSION)) {
            session_set_cookie_params(3600,'/','',true,true); // make it expire after 1 hour
            session_start();
            if ($this->CSRFTokenRequired) {
                $token = hash("sha512",mt_rand(0,mt_getrandmax()));
                $_POST['CSRFtoken'] = $_SESSION['CSRFtoken'] = $token;
            }
        }
        
        // If we are in two-factor mode we must identify a cookie named BrowserValidationUsername
        // If the user is loggin in with their email address we must first figure out what their username is
        if ($this->twoFactorAuthMode) {
            if ($_POST['username'] == SanitizeUsername($_POST['username'])) {
                $BVname = 'BrowserValidation'.SanitizeUsername($_POST['username']);
            } else if ($_POST['username'] == SanitizeEmail($_POST['username'])) {
                $BVname = 'BrowserValidation'.SanitizeUsername($this->GetUsernameFromEmail(SanitizeEmail($_POST['username'])));
            }
            if (isset($_COOKIE[$BVname])) {
                $BVvalue = $_COOKIE[$BVname];
            } else {
                $BVvalue = '';
            }
        } else {
            $BVvalue = '';
        }

        // Note that we have not sanitized their username, this is to allow them to login with their email address
        if(!$this->CheckLoginInDB($_POST['username'],$_POST['password'],SanitizeHex($BVvalue)))
        {
            http_response_code(400);
            return false;
        }
        
        // Set session variables
        $_SESSION['LAST_ACTIVITY'] = time();
        $_SESSION['CREATED'] = time();
        $_SESSION['hasPurchasedThisSession'] = false;

        return true;
    }
    
    function CheckLogin()
    {
        // Check that they at least have a session, and if not, create it
        if(!isset($_SESSION)){ 
            session_set_cookie_params(3600,'/','',true,true); // make it expire after 1 hour
            session_start(); 
        }
        
        // If they do not have a CSRF token, set that too; if we are requiring them.
        if ($this->CSRFTokenRequired) {
            if (!isset($_SESSION['CSRFtoken'])) {
                $token = hash("sha512",mt_rand(0,mt_getrandmax()));
                $_SESSION['CSRFtoken'] = $token;
            }
        }
        
        // This would mean that they are not logged in, as we set this when a user logs in
        if(empty($_SESSION['username']))
        {
            http_response_code(401);
            return false;
        }
        
        // They were properly logged in, but that was too long ago (sessionLifeTime) so they need to login again
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $this->sessionLifeTime)) {
            /* last request was more than sessionLifeTime ago*/
            session_destroy(); // destroy session data in storage
            http_response_code(401);
            return false;
        }
        
        // They are properly logged in, so let's update their session timers as appropriate.
        $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
        if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        } else if (time() - $_SESSION['CREATED'] > $this->sessionLifeTime) {
            /* session started more than sessionLifeTime ago*/
            session_regenerate_id(true); // change session ID for the current session and invalidate old session ID
            $_SESSION['CREATED'] = time(); // update creation time
        }
        
        return true;
    }
    
    function ConfirmCSRFToken() {
        if ($this->CSRFTokenRequired) {
            if (($_SESSION['CSRFtoken'] == SanitizeHex($_POST['CSRFtoken']) || $_SESSION['CSRFtoken'] == SanitizeHex($_GET['CSRFtoken']))) {
                return true;
            } else {
                $this->HandleError("Could not confirm Cross-Site Request Forgery token. Access denied.");
                return false;
            }
        }
    }
    
    function AuthenticateChangeRequest() {
        if ($this->CheckLogin() === false) {
            $this->HandleError("You are not logged-in.");
            return false;
        }
        
        if ($this->twoFactorAuthMode) {
            $BVname = 'BrowserValidation'.SanitizeUsername($_SESSION['username']);
            $BVvalue = $_COOKIE[$BVname];
        } else {
            $BVvalue = '';
        }
        
        if ($this->passwordRequiredForAdministration) {
            if (!$this->CheckLoginInDB(SanitizeUsername($_SESSION['username']), $_POST['pwd'], SanitizeHex($BVvalue))) {
                $this->HandleError("The password provided did not validate!");
                return false;
            }
        } else {
            if ($this->ConfirmCSRFToken() === false) {
                // previous method provides its own error
                return false;
            }
        }
        return true;
    }
    
    // End Logging on a User
    
    // 1.4 Logging out a user
    
    function LogOut()
    {        
        session_start();
        session_destroy(); // destroy session data in storage
    }
    
    // End Logging out a User
    
    // 1.5 Getting Session Variables (Likely Unused)
    
    function UserFullName()
    {
        return isset($_SESSION['name_of_user'])?SanitizeRealName($_SESSION['name_of_user']):'not set (error)';
    }
    
    function UserName()
    {
        return isset($_SESSION['username'])?SanitizeUsername($_SESSION['username']):'not set (error)';
    }
    
    function UserEmail()
    {
        return isset($_SESSION['email_of_user'])?SanitizeEmail($_SESSION['email_of_user']):'not set (error)';
    }
    
    // End Getting Session Variables
    
    // 2 Updating User Profiles
    
    // 2.1 Resetting a Password 
    
    function EmailResetPasswordLink()
    {
        if(empty($_POST['email']))
        {
            $this->HandleError("Email is empty!");
            return false;
        }
        
        $email = SanitizeEmail($_POST['email']);
        $username = $this->GetUsernameFromEmail($email);
        
        // If there is no user with this email address act like the reset was successful. This way we do not reveal any information unncessarily.
        if(false === $username)
        {
            return true;
        }
        
        $confirmcode = $this->ChangeConfirmCodeInDB($email);
        
        if(false === $confirmcode) {
            $this->HandleError("Sorry, something went wrong on our end.");
            return false;
        }
        
        if(!$this->SendResetPasswordLink($email, $username, $confirmcode))
        {
            $this->HandleError("Sorry, something went wrong on our end.");
            return false;
        }
        
        return true;
    }
    
    function SendResetPasswordLink($email, $username, $confirmcode)
    {
        $username = SanitizeUsername($username);
        $email = SanitizeEmail($email);
        
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($email,$username);
        
        $mailer->Subject = "Your Password Reset Request at ".$this->sitename;
        $mailer->From = $this->GetFromAddress();
        
        $link = $this->GetAbsoluteURLFolder().
        '/resetpwd.php?email='.
        urlencode($email).'&code='.
        urlencode($confirmcode);
        
        $mailer->Body ="Hello ".$username.",\r\n\r\n".
        "There was a request to reset your password at ".$this->sitename."\r\n".
        "Please click the link below to complete the request: \r\n".$link."\r\n\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        
        if(!$mailer->Send())
        {
            return false;
        }
        
        return true;
    }
    
    function ResetPassword($code, $newpassword, $csalt)
    {
        if(empty($code))
        {
            $this->HandleError("reset code is empty!");
            return false;
        }

        $code = SanitizeHex($code);

        $confirmedemail = SanitizeEmail($this->UpdateDBRecForConfirmation(SanitizeHex(strtolower($code))));
        if(false === $confirmedemail)
        {
            $this->HandleError("bad reset code");
            return false;
        }        
        
        // BUG: I'm not sure this would work; if the boolean value false was returned, would it still be the boolean value false after being Sanitized?
        $username = SanitizeUsername($this->GetUsernameFromEmail($confirmedemail));
        
        if(false === $username)
        {
            error_log("why did we generate a password reset code for a non-existent or non-mapped email address: ".$confirmedemail);
            // This is very strange, so do not let the visitor know anything is wrong; it is unlikely they are one of our users
            return true;
        }
        
        if(false === $this->ResetUserPasswordInDB($username, $csalt, $newpassword))
        {
            $this->HandleError("Error updating new password");
            return false;
        }
        
        $this->NotifyOfNewPassword($confirmedemail, $username);
        
        return true;
    }
    
    function NotifyOfNewPassword($email, $username)
    {
        $username = SanitizeUsername($username);
        $email = SanitizeEmail($email);
        
        $mailer = new PHPMailer();
        
        $mailer->CharSet = 'utf-8';
        
        $mailer->AddAddress($email,$username);
        
        $mailer->Subject = "Your ".$this->sitename." password has been updated";
        $mailer->From = $this->GetFromAddress();
        
        $mailer->Body ="Hello ".$username.",\r\n\r\n".
        "Your password has been changed. If this was not done by you, please contact us immediately at ".$this->admin_email.
        "\r\n".
        "\r\n".
        "Regards,\r\n".
        "Webmaster\r\n".
        $this->sitename;
        
        if(!$mailer->Send())
        {
            return false;
        }
        return true;
    }
    
    // End Resetting a Password 
    
    // 2.2 Changing a Password
    
    function ChangePassword()
    {
        if($this->CheckLogin() === false)
        {
            $this->HandleError("Not logged in!");
            return false;
        }
        
        if(empty($_POST['newpwd']))
        {
            $this->HandleError("New password is empty!");
            return false;
        }
        
        if ($this->AuthenticateChangeRequest() === false) {
            // previous method provides its own error
            return false;
        }
        
        // Request has been authenticated, proceed.
        $newpwd = $_POST['newpwd'];
        
        if(!$this->ChangePasswordInDB($_SESSION['username'], $newpwd))
        {
            return false;
        }
        
        $this->NotifyOfNewPassword($this->GetEmailFromUsername($username), $username);
        
        return true;
    }
    
    // End Changing a Password
    
    // 2.3 Changing an Email Address
    
    function ChangeEmailAddress() {
        if($this->CheckLogin() === false)
        {
            $this->HandleError("Not logged in!");
            return false;
        }
        
        if(empty($_POST['newemail']))
        {
            $this->HandleError("New email entry is empty!");
            return false;
        }
        
        if(md5($_POST['newemailrepeat']) != md5($_POST['newemail']))
        {
            $this->HandleError("Replacement email addresses do not match.");
            return false;
        }
        
        if ($this->AuthenticateChangeRequest() === false) {
            // previous method provides its own error
            return false;
        }
        
        // Request has been authenticated, proceed.
        $newemail = SanitizeEmail(trim($_POST['newemail']));
        if($newemail != trim($_POST['newemail'])) {
            $this->HandleError("Your password includes illegal special characters.");
            return false;
        }
        
        if(!$this->ChangeEmailInDB($_SESSION['username'], $newemail))
        {
            return false;
        }
        
        $_SESSION["email_of_user"] = $newemail;
        
        return true;
    }
    
    // End Changing an Email Address
    
    // 2.4 Changing a 'Real Name'

    function ChangeName() {
    
        if($this->CheckLogin() === false)
        {
            $this->HandleError("Not logged in!");
            return false;
        }
        
        if ($this->AuthenticateChangeRequest() === false) {
            // previous method provides its own error
            return false;
        }
        
        // Request has been authenticated, proceed.
        $newname = SanitizeNonNumericText(trim($_POST['newname']));
        // Removing the "Real Name" value is a valid request
        if(empty($_POST['newname'])) {
            $newname = "";
        }
        if(!$this->ChangeNameInDB(SanitizeUsername($_SESSION['username']), $newname))
        {
            // previous method provides its own error
            return false;
        }
    
        $_SESSION["name_of_user"] = $newname;
        return true;
    }
    
    // End Changing a 'Real Name
    
    // 2.5 Disabling a Browser

    function DisableBrowser() {
        if($this->CheckLogin() === false)
        {
            $this->HandleError("Not logged in!");
            return false;
        }
        
        $browser_id = SanitizeInteger($_POST['browserID']);

        return $this->DisableBrowserInDB($browser_id);
    }
    
    // End Disabling a Browser
    
    // End Updating User Profiles
    
    // 3 Infrastructure
    
    // 3.1 PHP Helpers
    function GetSelfScript()
    {
        return htmlentities($_SERVER['PHP_SELF']);
    }    
    
    function SafeDisplay($value_name)
    {
        if(empty($_POST[$value_name]))
        {
            return '';
        }
        
        return htmlentities($_POST[$value_name]);
    }
    
    function RedirectToURL($url)
    {
        header("Location: ".$url);

        exit;
    }
    
    function GetSpamTrapInputName()
    {
        return 'sp'.sha1('KHGdnbvsgst'.$this->rand_key);
    }
    
    function GetErrorMessage()
    {
        if(empty($this->error_message))
        {
            return '';
        }
        $errormsg = nl2br($this->error_message);
        return $errormsg;
    }
    
    function HandleError($err)
    {
        $this->error_message .= $err;
    }
    
    function HandleDBError($err)
    {
        $this->HandleError($err."\r\n mysqlerror:".mysql_error());
    }
    
    function GetFromAddress()
    {
        if(!empty($this->from_address))
        {
            return $this->from_address;
        }
        
        $host = $_SERVER['SERVER_NAME'];
        $from ="nobody@$host";
        return $from;
    } 
    
    function GetAbsoluteURLFolder()
    {
        $scriptFolder = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://';
        $scriptFolder .= $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        return $scriptFolder;
    }
    
    // End PHP Helpers
    
    // 3.2 MySQL Helpers
    function DBLogin()
    {
        // The below commeted function can be very useful for odd DB errors, it inidicates which function tried to login to the Database and writes it to the log
        // error_log(debug_backtrace()[2]["function"]);
        if(!isset($connection) || !mysqli_ping($connection)) {
            $connection = mysqli_connect($this->db_host,$this->username,$this->pwd, $this->database);
            if($connection->connect_errno)
            {
                $this->HandleDBError("Failed to connect to MySQL: (" . $connection->connect_errno . ") " . $connection->connect_error);
                return false;
            }
        }
        return $connection;
    }    
    
    function EnsureTable()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if($stmt = $connection->prepare("Show tables like 'registeredUsers'")){
            $stmt->execute();
            $stmt->bind_result($tables);
            $stmt->fetch();
            $stmt->close();
        }

        mysqli_close($connection);
 
        if(!isset($tables))
        {
            return $this->CreateTable();
        }
    
        return true;
    }
    
    function EnsureBrowserTable()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if (!$this->EnsureTable()) {
            $this->HandleError("registeredUsers table does not exist, registeredBrowsers table cannot be created.");
            return false;
        }
        
        if($stmt = $connection->prepare("Show tables like 'registeredBrowsers'")){
            $stmt->execute();
            $stmt->bind_result($tables);
            $stmt->fetch();
            $stmt->close();
        }

        mysqli_close($connection);
 
        if(!isset($tables))
        {
            return $this->CreateBrowserTable();
        }
    
        return true;
    }
    
    function EnsureTransactionTable()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if (!$this->EnsureTable()) {
            $this->HandleError("registeredUsers table does not exist, transactions table cannot be created.");
            return false;
        }
        
        if($stmt = $connection->prepare("Show tables like 'transactions'")){
            $stmt->execute();
            $stmt->bind_result($tables);
            $stmt->fetch();
            $stmt->close();
        }

        mysqli_close($connection);
 
        if(!isset($tables))
        {
            return $this->CreateTransactionTable();
        }
    
        return true;
    }
    
    function CreateTable()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
    
        if($stmt = $connection->prepare("Create Table registeredUsers (".
                "id_user INT NOT NULL AUTO_INCREMENT ,".
                "name VARCHAR( 64 ) NULL ,".
                "email VARCHAR( 64 ) NOT NULL ,".
                "lastEmail VARCHAR( 64 ) NOT NULL ,".
                "username VARCHAR( 64 ) NOT NULL ,".
                "password CHAR( 80 ) NOT NULL ,".
                "csalt CHAR( 32 ) NULL ,".
                "salt CHAR( 32 ) NOT NULL ,".
                "iterations INT UNSIGNED NOT NULL ,".
                "confirmcode VARCHAR(8) NULL ,".
                "confirmtime datetime NULL ,".
                "paymentProblem TINYINT ( 1 ) UNSIGNED NOT NULL DEFAULT 0,".
                "totalSpending DECIMAL( 11,2 ) UNSIGNED NOT NULL DEFAULT 0,".
                "credit DECIMAL( 11,2 ) UNSIGNED NOT NULL DEFAULT 0,".
                "accountOrigin TINYINT UNSIGNED NOT NULL DEFAULT 0,".
                "message VARCHAR(300) NULL ,".
                "adminMessage VARCHAR(300) NULL ,".
                "PRIMARY KEY ( id_user )".
                ")"))
        {
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error creating the table :" . $stmt->errno . ") " . $stmt->error);
                return false;
            }
            $stmt->close();
        } else
        {
            $this->HandleDBError("Malformed statement");
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }
    
    function CreateBrowserTable()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
    
        if($stmt = $connection->prepare("Create Table registeredBrowsers (".
                "id_browser INT NOT NULL AUTO_INCREMENT ,".
                "active TINYINT( 1 ) NOT NULL DEFAULT 0,".
                "secret CHAR( 80 ) NOT NULL ,".
                "id_user INT NOT NULL ,".
                "ip_address CHAR( 15 ) NOT NULL ,".
                "platform VARCHAR( 32) NOT NULL ,".
                "email VARCHAR( 64 ) NOT NULL ,".
                "time_registered timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,".
                "disabled_by  VARCHAR( 80 ) NULL ,".
                "CONSTRAINT FOREIGN KEY (id_user) REFERENCES registeredUsers (id_user),".
                "PRIMARY KEY ( id_browser )".
                ")"))
        {
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error creating the table :" . $stmt->errno . ") " . $stmt->error);
                return false;
            }
            $stmt->close();
        } else
        {
            $this->HandleDBError("Malformed statement");
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }
    
    function CreateTransactionTable()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
    
        if($stmt = $connection->prepare("Create Table transactions (".
                "skey INT NOT NULL AUTO_INCREMENT ,".
                "id_user INT NOT NULL ,".
                "reason VARCHAR(255) ,".
                "price DECIMAL(8,2) ,".
                "date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,".
                "paid TINYINT( 1 ) NOT NULL DEFAULT 0 ,".
                "paymentId VARCHAR(40) NULL,".
                "CONSTRAINT FOREIGN KEY (id_user) REFERENCES registeredUsers (id_user) ,".
                "PRIMARY KEY (skey) ".
                ")"))
        {
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error creating the table :" . $stmt->errno . ") " . $stmt->error);
                return false;
            }
            $stmt->close();
        } else
        {
            $this->HandleDBError("Malformed statement");
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }
    
    // This function only works with preconfigured fields in the database.
    // This limitation is due to the inability to set column names in prepared statements with PHP's mysqli
    function IsFieldUnique($column,$content)
    {
    
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        } 
    
        switch ($column) {
            case 'username':
                $content = SanitizeText($content);
                if($stmt = $connection->prepare("Select username from registeredUsers where username=?")){
                    $stmt->bind_param("s", $content);
                    $stmt->execute();
                    $stmt->bind_result($username);
                    $stmt->fetch();
                    $stmt->close();
                }
                break;
                
            case 'email':
                $content = SanitizeEmail($content);
                if($stmt = $connection->prepare("Select username from registeredUsers where email=?")){
                    $stmt->bind_param("s", $content);
                    $stmt->execute();
                    $stmt->bind_result($username);
                    $stmt->fetch();
                    $stmt->close();
                }
                break;
                
            case 'salt':
                $content = SanitizeHex($content);
                if($stmt = $connection->prepare("Select username from registeredUsers where salt=?")){
                    $stmt->bind_param("s", $content);
                    $stmt->execute();
                    $stmt->bind_result($username);
                    $stmt->fetch();
                    $stmt->close();
                }
                break;
                
            case 'confirmcode':
                $content = SanitizeHex($content);
                if($stmt = $connection->prepare("Select username from registeredUsers where confirmcode=?")){
                    $stmt->bind_param("s", $content);
                    $stmt->execute();
                    $stmt->bind_result($username);
                    $stmt->fetch();
                    $stmt->close();
                }
                break;
            
            default:
                error_log("We attempted to check the uniquness of currently unsupported field: $column");
                return false;
        }

        mysqli_close($connection);
 
        if($username)
        {
            return false;
        }
        
        return true;
    }
    
    // End MySQL Helpers
    
    // 3.3 MySQL Actors
    
    // 3.3.1 MySQL Inserts
    function InsertIntoDB(&$formvars)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
    
        // do not allow duplicate confirmcodes
        while (!isset($confirmcode) || !$this->IsFieldUnique('confirmcode', $confirmcode)) {
            $confirmcode = SanitizeHex(substr(bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM)), -8));
        }
        
        $formvars['confirmcode'] = $confirmcode;
        
        // do not allow duplicate salts
        while (!isset($salt) || !$this->IsFieldUnique('salt', $salt)) {
           $salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));
        }

        $protected_password = hash_pbkdf2("sha512", $formvars['password'], $salt, $this->newIterations, 80);
        
        if($stmt = $connection->prepare("Insert into registeredUsers (
                name,
                email,
                username,
                password,
                csalt,
                salt,
                iterations,
                confirmcode,
                confirmtime,
                totalSpending,
                accountOrigin
                )
                values
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"))
        {
            $stmt->bind_param("ssssssissii",
                SanitizeRealName($formvars['name']),
                SanitizeEmail($formvars['email']),
                SanitizeUsername($formvars['username']),
                SanitizeHex($protected_password),
                SanitizeHex($formvars['salt']),
                SanitizeHex($salt),
                SanitizeInteger($this->newIterations),
                SanitizeHex($confirmcode),
                date("Y:m:d H:i:s", strtotime("+3 day")),
                $a = 0,
                $b = 0
                );
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error inserting user record :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        } else
        {
            $this->HandleDBError("Malformed statement");
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }
    
    function UpdateDBforBrowserVerification($email, $IP, $description)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }

        if($stmt = $connection->prepare("Select id_user from registeredUsers where email=?")){
            $stmt->bind_param("s", SanitizeEmail($email));
            $stmt->execute();
            $stmt->bind_result($id_user);
            $stmt->fetch();
            $stmt->close();
        }

        if(!$id_user)
        {
            $this->HandleError("Could not determine your internal user id.");
            mysqli_close($connection);
            return false;
        }

        $secret = bin2hex(mcrypt_create_iv(40, MCRYPT_DEV_URANDOM));

        if($stmt = $connection->prepare("Insert into registeredBrowsers (active, secret, id_user, ip_address, platform, email) values (1,?,?,?,?,?)"))
        {
            $stmt->bind_param("sisss", SanitizeHex($secret), SanitizeInteger($id_user), SanitizeFloat($IP), SanitizeBrowserName($description), SanitizeEmail($email));
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error updating database with new browser registration :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        }
        mysqli_close($connection);
        return $secret;
    }
    
    // End MySQL Inserts
    
    // 3.3.2 MySQL Updates
    function DisableBrowserInDB($browser_id) {
        $connection = $this->DBLogin();
        
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        $BVname = 'BrowserValidation'.SanitizeUsername($_SESSION['username']);

        if($stmt = $connection->prepare("Update registeredBrowsers set active='0', disabled_by=? where id_browser=?")){
            $stmt->bind_param("si", SanitizeHex($_COOKIE[$BVname]), SanitizeInteger($browser_id));
                if (!$stmt->execute()) {
                    $this->HandleDBError("Error disabling requested browser :" . $stmt->errno . ") " . $stmt->error);
                    mysqli_close($connection);
                    return false;
                }
            $stmt->close();
        }
        
        mysqli_close($connection);
        return true;
    }
    
    // This function veririfes that the confirmcode is good, resets it to 'y', and returns results with a boolean false or the email address of the user
    function UpdateDBRecForConfirmation($confirmcode)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if($stmt = $connection->prepare("Select email, confirmtime from registeredUsers where confirmcode=?")){
            $stmt->bind_param("s", SanitizeHex($confirmcode));
            $stmt->execute();
            $stmt->bind_result($email, $confirmtime);
            $stmt->fetch();
            $stmt->close();
        }
        
        if(gettype($email) != "string")
        {
            $this->HandleError("There is no user with that confirmcode, or there are two users.". $email);
            mysqli_close($connection);
            return false;
        }
        
        if($stmt = $connection->prepare("Update registeredUsers set confirmcode='y' where email=?"))
        {
            $stmt->bind_param("s", $email);
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error updating database to reflect confirmation :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        }

        if (strtotime($confirmtime)<strtotime(date("Y:m:d H:i:s"))) {
            $this->HandleError("This confirm code expired before being used. Please try again.");
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);   
        
        return $email;
    }
    
    function ResetUserPasswordInDB($username, $csalt, $newpassword)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }

        if($stmt = $connection->prepare("Update registeredUsers set csalt=? where username=?"))
        {
            $stmt->bind_param("ss", $csalt, $username);
            if (!$stmt->execute())
            {
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        }
        
        return $this->ChangePasswordInDB($username,$newpassword);
    }
    
    function ChangePasswordInDB($username, $newpwd)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }   
        
        $haveGoodSalt = false;
        
        while (!$haveGoodSalt) {
            $salt = bin2hex(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM));  
            $haveGoodSalt = $this->IsFieldUnique("salt", $salt);
        }
        
        $protected_password = hash_pbkdf2("sha512", $newpwd, $salt, $this->newIterations, 80);
        if($stmt = $connection->prepare("Update registeredUsers set password=?, salt=?, iterations=? where username=?"))
        {
            $stmt->bind_param("ssis", SanitizeHex($protected_password), SanitizeHex($salt), SanitizeInteger($this->newIterations), SanitizeUsername($username));
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error updating the password :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        } else
        {
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }
    
    // function returns confirmcode or boolean false if failure; this function only allows a 2 hour window for confirmation by user
    function ChangeConfirmCodeInDB($email)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }

        // do not duplicate a confirmcode
        while (!isset($confirmcode) || !$this->IsFieldUnique('confirmcode', $confirmcode)) {
            $confirmcode = SanitizeHex(substr(bin2hex(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM)), -8));
        }
        
        if($stmt = $connection->prepare("Update registeredUsers set confirmcode=?, confirmtime=? where email=?"))
        {
            $stmt->bind_param("sss", SanitizeHex($confirmcode), date("Y:m:d H:i:s", strtotime("+2 hour")), SanitizeEmail($email));
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error updating the confirmation code :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        } else
        {
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return $confirmcode;
    }
    
    function ChangeEmailInDB($username, $email)
    {
        if($this->IsFieldUnique("email", $email)) {
            $connection = $this->DBLogin();
            if(!$connection)
            {
                $this->HandleError("Database login failed!");
                mysqli_close($connection);
                return false;
            }
            if($stmt = $connection->prepare("Update registeredUsers set lastEmail=email where username=?"))
            {
                $stmt->bind_param("s", SanitizeUsername($username));
                if (!$stmt->execute())
                {
                    $this->HandleDBError("Error updating the email address :" . $stmt->errno . ") " . $stmt->error);
                    mysqli_close($connection);
                    return false;
                }
                $stmt->close();
            } else
            {
                mysqli_close($connection);
                return false;
            }
            if($stmt = $connection->prepare("Update registeredUsers set email=? where username=?"))
            {
                $stmt->bind_param("ss", SanitizeEmail($email), SanitizeUsername($username));
                if (!$stmt->execute())
                {
                    $this->HandleDBError("Error updating the email address :" . $stmt->errno . ") " . $stmt->error);
                    mysqli_close($connection);
                    return false;
                }
                $stmt->close();
            } else
            {
                mysqli_close($connection);
                return false;
            }
        } else {
            $this->HandleError("New email address already exists in database.");
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }
    
    function ChangeNameInDB($username, $name)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if($stmt = $connection->prepare("Update registeredUsers set name=? where username=?"))
        {
            $stmt->bind_param("ss", SanitizeRealName($name), SanitizeUsername($username));
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error updating the real name :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        } else
        {
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }

    function MarkUserAsHavingBillingInfoProblem($username) {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        if($stmt = $connection->prepare("Update registeredUsers set paymentProblem='1' where username=?"))
        {
            $stmt->bind_param("s", SanitizeUsername($username));
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error marking account has having a billing problem :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        } else
        {
            mysqli_close($connection);
            return false;
        }
        mysqli_close($connection);
        return true;
    }

    function SetUserMessage($username, $message)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }

        if($stmt = $connection->prepare("Update registeredUsers set message=? where username=?"))
        {
            $stmt->bind_param("ss", SanitizeBrowserName($message), SanitizeUsername($username));
            if (!$stmt->execute())
            {
                $this->HandleDBError("Error updating the message :" . $stmt->errno . ") " . $stmt->error);
                mysqli_close($connection);
                return false;
            }
            $stmt->close();
        } else
        {
            mysqli_close($connection);
            return false;
        }
        
        mysqli_close($connection);
        return true;
    }
    
    // End MySQL Updates
    
    // 3.3.3 MySQL Selects
    
    function CheckLoginInDB($username,$password,$browserverification)
    {
        if ($this->CSRFTokenRequired) {
            if (!($_SESSION['CSRFtoken'] == SanitizeHex($_POST['CSRFtoken']) || $_SESSION['CSRFtoken'] == SanitizeHex($_GET['CSRFtoken']))) {
                $this->HandleError("Could not confirm Cross-Site Request Forgery token. Access denied.");
                return false;
            }
        }
        
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }           
        
        // These two steps attempt to identify which user is logging in, first by username and then by email address
        if($stmt = $connection->prepare("Select salt, iterations from registeredUsers where username=?")){
            $stmt->bind_param("s", SanitizeUsername($username));
            $stmt->execute();
            $stmt->bind_result($salt, $iterations);
            $stmt->fetch();
            $stmt->close();
        }

        if(!$salt || !$iterations)
        {
            if($stmt = $connection->prepare("Select salt, iterations, username from registeredUsers where email=?")){
                $stmt->bind_param("s", SanitizeEmail($username));
                $stmt->execute();
                $stmt->bind_result($salt, $iterations, $username);
                $stmt->fetch();
                $stmt->close();
            }
        }
        
        // neither worked, so this is not a registered user
        if(!$salt || !$iterations)
        {
            $this->HandleError("Error logging in. Username does not exist.");
            mysqli_close($connection);
            return false;
        }
        
        // Ok, we know the username now and should sanitize it for further use
        $username = SanitizeUsername($username);
        
        // This is the only place the user-provided password string is used, which is why we both did not want to sanitize it, and did not need to for the security of the site
        $pwdhash = SanitizeHex(hash_pbkdf2("sha512", $password, $salt, $iterations, 80));

        if($stmt = $connection->prepare("Select name, email, id_user, message, adminMessage, paymentProblem, credit from registeredUsers where username=? and password=?")) {
            $stmt->bind_param("ss", $username, $pwdhash);
            $stmt->execute();
            $stmt->bind_result($name, $email, $id_user, $message, $adminMessage, $paymentProblem, $credit);
            $stmt->fetch();
            $stmt->close();
        }

        if (!isset($email)) {
            $this->HandleError("Error logging in. You have entered an incorrect password.");
            mysqli_close($connection);
            return false;
        }

        // twoFactorAuthMode requires the user to login with a registeredBrowser. Without twoFactorAuthMode we are just checking that the user has confirmed their email address.
        if ($this->twoFactorAuthMode) {
            if($stmt = $connection->prepare("Select id_user, id_browser from registeredBrowsers where secret=? AND id_user=? AND active='1'")){
                $stmt->bind_param("si", SanitizeHex($browserverification), SanitizeInteger($id_user));
                $stmt->execute();
                $stmt->bind_result($found_id, $browser_id);
                $stmt->fetch();
                $stmt->close();
            }
            
            if (isset($found_id) && strcmp(SanitizeInteger($found_id),SanitizeInteger($id_user)) == 0) {
                $browserKnown = true;
            } else {
                $this->HandleError("Error logging in. You have not registered this Web Browser. <a href='/register-browser-req.php'>Perform registration here.</a>");
                mysqli_close($connection);
                return false;
            } 
        } else {
            if($stmt = $connection->prepare("Select confirmcode from registeredUsers where username=?")){
                $stmt->bind_param("s", SanitizeUsername($username));
                $stmt->execute();
                $stmt->bind_result($confirmcode);
                $stmt->fetch();
                $stmt->close();
            }
            // BUG: If a user has requested a password reset and not used it; they will be denied access here
            if ($confirmcode !== "y") {
                 $this->HandleError("You must confirm your email address before you can login, check the email address you provided during registration.");
                mysqli_close($connection);
                return false;
            }
        }
        
        if(empty($username) || empty($email))
        {
            $this->HandleError("Error logging in. The username or password does not match");
            mysqli_close($connection);
            return false;
        }

        // let's reset messages for this user; they have been collected for serving
        if($stmt = $connection->prepare("Update registeredUsers set message=NULL, adminMessage=NULL where username=? and password=?")){
            $stmt->bind_param("ss", SanitizeUsername($username), SanitizeHex($pwdhash));
            if (!$stmt->execute()) {
                // not sure what to do here; not exactly good but not critical either
            }
            $stmt->close();
        }
        
        $_SESSION['userID'] = SanitizeInteger($id_user);
        $_SESSION['username'] = SanitizeUsername($username);
        $_SESSION['email_of_user'] = SanitizeEmail($email);
        $_SESSION['name_of_user'] = SanitizeRealName($name);
        if ($this->twoFactorAuthMode) {
            $_SESSION['browserID'] = SanitizeInteger($browser_id);
        }
        $_SESSION['credit'] = SanitizeFloat($credit);

        // if we recorded a billing problem for the user that remains unresolved, let them know to fix it
        $_SESSION['problemBillingUser'] = ($paymentProblem == '1');
        if ($_SESSION['problemBillingUser'] === true) {
            $_SESSION["messageForUser"] = true;
            $_SESSION['message'] = SanitizeBrowserName("Your billing information is invalid, please update it.");
        }

        // if there are messages for the user, set them in the session
        if (!empty($message)) {
            $_SESSION["messageForUser"] = true;
            $_SESSION['message'] = SanitizeBrowserName($message);
        } else  if (!isset($_SESSION["messageForUser"])) {
            $_SESSION["messageForUser"] = false;
        }

        if (!empty($adminMessage)) {
            $_SESSION["adminMessageForUser"] = true;
            $_SESSION['adminMessage'] = SanitizeBrowserName($adminMessage);
        } else  if (!isset($_SESSION["adminMessageForUser"])) {
            $_SESSION["adminMessageForUser"] = false;
        }
        
        mysqli_close($connection);
        return true;
    }
    
    function GetUsernameFromEmail($email)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }   
        
        if($stmt = $connection->prepare("Select username from registeredUsers where email=?")){
            $stmt->bind_param("s", SanitizeEmail($email));
            $stmt->execute();
            $stmt->bind_result($username);
            $stmt->fetch();
            $stmt->close();
        }
 
        if(!$username)
        {
            $this->HandleError("There is no user with email: ".$email);
            mysqli_close($connection);
            return false;
        }
        
        mysqli_close($connection);
        return SanitizeUsername($username);
    }
    
    function GetEmailFromUsername($username)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }   
        
        if($stmt = $connection->prepare("Select email from registeredUsers where username=?")){
            $stmt->bind_param("s", SanitizeUsername($username));
            $stmt->execute();
            $stmt->bind_result($email);
            $stmt->fetch();
            $stmt->close();
        }
 
        if(!$email)
        {
            $this->HandleError("There is no user with username: ".$username);
            mysqli_close($connection);
            return false;
        }
        
        mysqli_close($connection);
        return SanitizeEmail($email);
    }
    
    function GetSaltFromUsername($username)
    {
        $connection = $this->DBLogin();
        
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }
        
        if($stmt = $connection->prepare("Select csalt from registeredUsers where username=?")){
            $stmt->bind_param("s", SanitizeUsername($username));
            $stmt->execute();
            $stmt->bind_result($csalt);
            $stmt->fetch();
            $stmt->close();
        }
        
        if(strlen($csalt) == 0)
        {
            $this->HandleError("There is no user with username: ".$username);
            mysqli_close($connection);
            return false;
        }
        
        mysqli_close($connection);
        return SanitizeHex($csalt);
    }

    // This method finds the client-side hashing salt for a user (by username) without unnecessarily sharing it with browsers not registered/trusted by that user.
    function GetSaltFromUsernamePublic($username, $browserverification)
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }

        if($stmt = $connection->prepare("Select csalt, id_user from registeredUsers where username=?")){
                $stmt->bind_param("s", SanitizeUsername($username));
                $stmt->execute();
                $stmt->bind_result($salt, $id_user);
                $stmt->fetch();
                $stmt->close();
        }

        if(isset($salt) && isset($id_user))
        {
            if($stmt = $connection->prepare("Select id_browser from registeredBrowsers where secret=? AND id_user=? AND active='1'")){
                $stmt->bind_param("si", SanitizeHex($browserverification), SanitizeInteger($id_user));
                $stmt->execute();
                $stmt->bind_result($browser_id);
                $stmt->fetch();
                $stmt->close();
            }
        }
    
        if (isset($browser_id)) {
            return SanitizeHex($salt);
        } else {
            return "BVrequired";
        }
          
    }

    // List all registered/trusted browsers for a user. Useful for providing an interface to selectively disable, or simply view, all registered browsers.
    function GetRegisteredBrowsersForCurrentUser()
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
            return false;
        }

        if($stmt = $connection->prepare("Select ip_address, id_browser, platform, secret from registeredBrowsers where id_user=? AND active='1'")){
            $stmt->bind_param("s", SanitizeInteger($_SESSION['userID']));
            $stmt->execute();
            $stmt->bind_result($temp1, $temp2, $temp3, $temp4);
            $known_browsers = array();
            while ($stmt->fetch()) {
                if (SanitizeHex($_COOKIE['BrowserValidation'.SanitizeUserName($_SESSION['username'])]) == $temp4) {
                    $known_browsers[$temp2] = $temp3." @ ".$temp1." (current)";
                } else {
                    $known_browsers[$temp2] = $temp3." @ ".$temp1;
                }
            }
            $stmt->close();
        }
    
        if(!isset($known_browsers))
        {
            $this->HandleError("Could not find browsers associatd with your userID");
            mysqli_close($connection);
            return false;
        }

        mysqli_close($connection);
        return $known_browsers;
    }

    
    function WhatWillNextUserIdBe() 
    {
        $connection = $this->DBLogin();
        if(!$connection)
        {
                $this->HandleError("Database login failed!");
                return 0;
        } else {
            if($stmt = $connection->prepare("select max(id_user) from registeredUsers;")) {
                if (!$stmt->execute()) {
                        $this->HandleDBError("Unable to execute query for determining max id_user");
                }
                $stmt->bind_result($num);
                $stmt->fetch();
                $stmt->close();
            }
            mysqli_close($connection);
            return $num+1;
        }
    }

    // End MySQL Selects
    
    // End MySQL Actors
    
    // 3.4 Billing Functions
    function ValidateBillingInfo() {
        $validator = new FormValidator();
        //$validator->addValidation("firstname","alpha_s","Your billing first name should only include letters and spaces");
        //$validator->addValidation("lastname","alpha_s","Your billing last name should only include letters and spaces");
        $validator->addValidation("credit-card","req","Please fill in a credit card number");
        $validator->addValidation("credit-card","num","Your credit card number should only include numbers");
        $validator->addValidation("credit-card","maxlen=16","Your credit card number should only be 16 digits long");
        $validator->addValidation("credit-card","minlen=15","Your credit card number should be 15 or 16 digits long");
        $validator->addValidation("credit-card-type","minlen=4","You did not specify youre credit card type");
         $validator->addValidation("monthExpires","req","Please fill in an expiration month for your credit card");
        $validator->addValidation("monthExpires","num","Please fill in the valid and future expiration month of your credit card");
        $validator->addValidation("yearExpires","req","Please fill in an expiration year for your credit card");

        if(!$validator->ValidateForm())
        {
            $error='';
            $error_hash = $validator->GetErrors();
            foreach($error_hash as $inpname => $inp_err)
            {
                $error .= $inpname.':'.$inp_err."\n";
            }
            $this->HandleError($error);
            return false;
        }

        if (!inarray(strtolower($_POST['credit-card-type']), $this->acceptedCreditCardTypes)) {
            $this->HandleError("Sorry, we do not accept that kind of credit card.");
            return false;
        }
        
        $input_time = mktime(0,0,0,SanitizeInteger($_POST['monthExpires'])+1,0,SanitizeInteger($_POST['yearExpires'])); 

        if ($input_time < time()){
            $this->HandleError("Provided expiration date has already elapsed.");
            return false;    
        }
        
        return true;
    }
    
    function billUser($username, $reason, $price) {
        error_log("Billing ".$username." $".$price." for ".$reason);        
        $_SESSION['hasPurchasedThisSession'] = true;
        
        // A fourth parameter indicates we are paying the balance on an existing, unpaid transaction
        if (func_num_args() > 3) {
            $transactionId = func_get_arg(3);
        } else {
            $transactionId = $this->createTransaction($reason, $price);
            if ($transactionId === false) {
                return [false, ""];
            }
        }
        // You might have some reason to allow unpaid transactions, you would fill that in here
        if (true) {
            $credit = SanitizeFloat($_SESSION['credit']);
            $price = SanitizeFloat($price);
            $billUserAmount = $price;
            // You may have given this user some credit; if so, consume that credit first
            if ($credit >= 0) {
                if ($credit - $price > 0) {
                    $billUserAmount = 0;
                    $credit = $credit - $price;
                    $_SESSION['credit'] = $credit;
                    $transactionResult = true;
                } else {
                    $billUserAmount = -($credit - $price);
                    $credit = $credit - ($price - $billUserAmount);
                    $_SESSION['credit'] = $credit;
                    // This is where you implement your actual billing function
                    // This will often return an unique paymentId from your payment processor
                    //$transactionResult = someAction();
                }
            } else {
                // This is where you implement your actual billing function
                // This will often return an unique paymentId from your payment processor
                //$transactionResult = someAction();
            }
            
            if ($transactionResult !== false) { // success
                $this->markTransactionPaid($transactionId, $transactionResult);
                $connection = $this->DBLogin();
                if(!$connection)
                {
                    $this->HandleError("Database login failed!");
                } else {
                    // Let's update the user's totalSpending, so we can easily see who our best customers are
                    if($stmt = $connection->prepare("Select totalSpending from registeredUsers where username=?")) {
                        $stmt->bind_param("s", SanitizeUsername($username));
                        $stmt->execute();
                        $stmt->bind_result($previousSpending);
                        $stmt->fetch();
                        $stmt->close();
                    }
                    
                    $totalSpending = $price + $previousSpending;
                    
                    if($stmt = $connection->prepare("Update registeredUsers set totalSpending=?, credit=? where username=?")) {
                        $stmt->bind_param("dds", SanitizeFloat($totalSpending), SanitizeFloat($credit), SanitizeUsername($username));
                        if (!$stmt->execute()) {
                                // not sure what to do here; not exactly good but not critical either
                        }
                        $stmt->close();
                    }
                    mysqli_close($connection);
                }
                return [true, $transactionId];
            } else { // failure
                $_SESSION['problemBillingUser'] = true;
                $this->MarkUserAsHavingBillingInfoProblem(SanitizeUsername($username));
                return [false, $transactionId];
            }
        } else {
            return [true, $transactionId];
        }
    }
    
    // End Billing Functions

    // 3.5 Transaction Loggers

    // Create an unpaid transaction
    function createTransaction($reason, $price) {

        $connection = $this->DBLogin();
        if(!$connection)
        {
           $this->HandleError("Database login failed!");
            return false;
        } else {
            if($stmt = $connection->prepare("INSERT INTO transactions (id_user, reason, price) values (?,?,?)")) {
                $stmt->bind_param("isd", SanitizeInteger($_SESSION['userID']), SanitizeNonNumericText($reason), SanitizeFloat($price));
                if (!$stmt->execute()) {
                    return false;
                }
                $transactionId = $stmt->insert_id;
                $stmt->fetch();
                $stmt->close();
                return $transactionId;
            }
            mysqli_close($connection);
        }
    }
    
    // Mark an existing transaction as having been paid
    function markTransactionPaid($transactionId, $transactionResult) {
        // remember that $transactionResult is often a unique ID from your payment processor
        $connection = $this->DBLogin();
        if(!$connection)
        {
            $this->HandleError("Database login failed!");
        } else {
            if($stmt = $connection->prepare("Update transactions set paid=1, paymentId=? where skey=?")) 
            {
                $stmt->bind_param("si", SanitizeNonNumericText($transactionResult), $transactionId);
                if (!$stmt->execute()) {
                   // not sure what to do here; not exactly good but not critical either
                }
                $stmt->close();
            }
            mysqli_close($connection);
        }
    }
    // End Transaction Loggers
}
?>
