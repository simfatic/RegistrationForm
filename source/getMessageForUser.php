<?php
require("./include/membersite_config.php");

if(!$fgmembersite->CheckLogin())
{
    http_response_code(400);
    exit;
}

if($_SESSION["messageForUser"]) {
	print $_SESSION["message"];
	$_SESSION["messageForUser"] = false;
} else if($_SESSION["adminMessageForUser"]){
	print $_SESSION["adminMessage"];
        $_SESSION["adminMessageForUser"] = false;
}
?>
