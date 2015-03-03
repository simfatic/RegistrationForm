<?PHP
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
require("./include/membersite_config.php");
if($fgmembersite->clientSidePasswordHashing)
{
	include("loginWithClientHashing.php");
} else {
    include("loginNoClientHashing.php");
}

?>