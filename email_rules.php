<?php
function email_problem($email) {
    $email = trim((string) $email);
    if ($email === "") { return "Please type your email address."; }
    if (strlen($email) > 100) { return "That email address is too long."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return "That does not look like a real email address."; }
    $at = strrpos($email, "@");
    $local = strtolower(substr($email, 0, $at));
    $domain = strtolower(substr($email, $at + 1));
    if (strlen($local) < 2) { return "The part before the @ is too short."; }
    if (strpos($domain, ".") === false) { return "The email needs an ending like .com or .co.za."; }
    $parts = explode(".", $domain);
    if (!preg_match("/^[a-z]{2,24}$/", end($parts))) { return "The ending of the email (like .com) is not valid."; }
    foreach ($parts as $part) { if (!preg_match("/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/", $part)) { return "The email domain is not valid."; } }
    foreach ([$local, $parts[0]] as $bit) { if (strlen($bit) >= 3 && count(array_unique(str_split($bit))) === 1) { return "That email looks made up. Please use your real email."; } }
    $fakeLocal = ["test", "testing", "example", "fake", "none", "noemail", "asdf", "asdfgh", "qwerty", "abc", "abcd", "xyz", "sample"];
    $fakeDomain = ["test", "example", "fake", "none", "noemail", "asdf", "qwerty", "abc", "xyz", "sample", "domain"];
    if (in_array($local, $fakeLocal, true) || in_array($parts[0], $fakeDomain, true)) { return "That email looks made up. Please use your real email."; }
    $disposable = ["mailinator.com", "guerrillamail.com", "10minutemail.com", "tempmail.com", "yopmail.com", "trashmail.com", "sharklasers.com", "throwawaymail.com"];
    if (in_array($domain, $disposable, true)) { return "Please use a permanent email address, not a temporary one."; }
    $typos = ["gmial.com" => "gmail.com", "gmai.com" => "gmail.com", "gamil.com" => "gmail.com", "gmail.co" => "gmail.com", "gmaill.com" => "gmail.com", "hotmial.com" => "hotmail.com", "hotmal.com" => "hotmail.com", "yahooo.com" => "yahoo.com", "yaho.com" => "yahoo.com", "outlok.com" => "outlook.com", "outllook.com" => "outlook.com"];
    if (isset($typos[$domain])) { return "Did you mean " . $local . "@" . $typos[$domain] . "?"; }
    if (function_exists("checkdnsrr") && @checkdnsrr("gmail.com.", "MX")) { if (!@checkdnsrr($domain . ".", "MX") && !@checkdnsrr($domain . ".", "A")) { return "We could not find that email domain. Please check the spelling."; } }
    return "";
}
