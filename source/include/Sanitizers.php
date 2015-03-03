<?php
    function SanitizeUsername($username) {
        return preg_replace('/[^a-z0-9]/s', "", filter_var(strtolower($username), FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }

    function SanitizeHex($key) {
        return preg_replace('/[^a-f0-9]/s', "", filter_var($key, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }

    function SanitizeInteger($number) {
        return preg_replace('/[^0-9]/s', "", filter_var($number,FILTER_SANITIZE_NUMBER_INT));
    }

    function SanitizeFloat($value) {
        return preg_replace('/[^0-9.]/s', "", filter_var($value,FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
    }

    function SanitizeText($text) {
            return preg_replace('/[^a-zA-Z0-9]/s', "", filter_var($text, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }
    
    function SanitizeNonNumericText($text) {
        return preg_replace('/[^a-zA-Z]/s', "", filter_var($text, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }

    function SanitizeRealName($text) {
        return preg_replace('/[^a-zA-Z\s+]/s', "", filter_var($text, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }

    function SanitizeBrowserName($text) {
        return preg_replace('/[^a-zA-Z0-9_\. -]/s', "", filter_var($text, FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH));
    }

    function SanitizeEmail($email) {
        return filter_var(strtolower($email), FILTER_SANITIZE_EMAIL);
    }

?>
