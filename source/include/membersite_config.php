<?PHP
require_once("./include/fg_membersite.php");

$fgmembersite = new FGMembersite();

//Provide your site name here
$fgmembersite->SetWebsiteName('user11.com');

//Provide the email address where you want to get notifications
$fgmembersite->SetAdminEmail('user11@user11.com');

//Provide your database login details here:
//hostname, user name, password, database name and table name
//note that the script will create the table (for example, fgusers in this case)
//by itself on submitting register.php for the first time
$fgmembersite->InitDB(/*hostname*/'p:localhost',
                      /*username*/'prasanth',
                      /*password*/'p',
                      /*database name*/'testdb');

$today = getdate();                      
$fgmembersite->SetRandomKey(md5($today[year].$today[yday]));                      
                      
//Do you want to require additional verification (password, and browser verification if using two-factor authentication) for account administration?
$fgmembersite->EnablePasswordRequiredForAdministration(true);                      
                      
//Do you want to enable two-factor authentication mode?  
$fgmembersite->EnableTwoFactorAuthenticationMode(false);

//Do you want to enable client-side password hashing?  If you do this, you must also enable Two Factor Authentication
$fgmembersite->EnableClientSidePasswordHashing(false);

//Do you want to include support for recording billing operations?
$fgmembersite->EnableTransactions(false);

$fgmembersite->SetAcceptedCreditCards(array("visa", "discover", "amex", "mastercard"));

?>