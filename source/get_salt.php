<?PHP
require("./include/membersite_config.php");

if ($_GET['username'] == SanitizeUsername($_GET['username'])) {
    $username = SanitizeUsername($_GET['username']);
    $BVname = 'BrowserValidation'.$username;
} else if ($_GET['username'] == SanitizeEmail($_GET['username'])) {
    $username = SanitizeUsername($fgmembersite->GetUsernameFromEmail(SanitizeEmail($_GET['username'])));
    $BVname = 'BrowserValidation'.$username;
}

if(isset($_COOKIE[$BVname])) {
	echo $fgmembersite->GetSaltFromUsernamePublic($username, $_COOKIE[$BVname]);
} else {
	echo 'BVrequired';
}
?>
