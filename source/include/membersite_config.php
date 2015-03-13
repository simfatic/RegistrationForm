<?PHP
require_once("./include/fg_membersite.php");

$fgmembersite = new FGMembersite();

//Provide an addressable URL for your site here (try copying and pasting it into your web browser and it should take you to your site)
$fgmembersite->SetWebsiteName('user11.com');

//Provide the email address where you want to get notifications
$fgmembersite->SetAdminEmail('user11@user11.com');

//Provide your database login details here:
//hostname, user name, password, and database name
//note that the script will create the table (locked as registeredUsers)
//by itself on submitting register.php for the first time
$fgmembersite->InitDB(/*hostname*/'p:localhost',
                      /*username*/'prasanth',
                      /*password*/'p',
                      /*database name*/'testdb');

//This is a compromise between entirely static values and randomly generated ones that the nature of PHP has a hard time with.
$today = getdate();                      
$fgmembersite->SetRandomKey(md5($today[year].$today[yday]));                      

//How long should sessions stay valid for (in minutes)?
$fgmembersite->SetSessionLifetimeInMinutes(30);
                      
//Do you want to require additional verification (password, and browser verification if using two-factor authentication) for account administration?
$fgmembersite->EnablePasswordRequiredForAdministration(true);   

//Do you want to prevent Cross-Site Request Forgery by requiring CSRF tokens to authenticate requests?
$fgmembersite->EnableCSRFTokenRequired(true);                 
                      
//Do you want to enable two-factor authentication mode?  
$fgmembersite->EnableTwoFactorAuthenticationMode(false);

//Do you want to enable client-side password hashing?  If you do this, you must also enable Two Factor Authentication
$fgmembersite->EnableClientSidePasswordHashing(false);

//Do you want to include support for recording billing operations?
$fgmembersite->EnableTransactions(false);

$fgmembersite->SetAcceptedCreditCards(array("visa", "discover", "amex", "mastercard"));

?>