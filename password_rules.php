<?php
function password_problem($pass, $name = "", $surname = "", $email = "") {
    if (strlen($pass) < 8) { return "Password must be at least 8 characters."; }
    if (strlen($pass) > 72) { return "Password is too long (72 characters at most)."; }
    if (!preg_match("/[a-z]/", $pass)) { return "Add a small letter (a to z)."; }
    if (!preg_match("/[A-Z]/", $pass)) { return "Add a capital letter (A to Z)."; }
    if (!preg_match("/[0-9]/", $pass)) { return "Add a number."; }
    if (!preg_match("/[^A-Za-z0-9]/", $pass)) { return "Add a symbol, such as ! or #."; }
    if (count(array_unique(str_split($pass))) < 4) { return "Use more different characters."; }
    $low = strtolower($pass);
    foreach (["password", "12345678", "qwerty", "letmein", "welcome", "iloveyou", "admin", "learnquest"] as $bad) { if (strpos($low, $bad) !== false) { return "That password is too easy to guess."; } }
    foreach ([$name, $surname, (string) strstr((string) $email, "@", true)] as $part) { $part = strtolower(trim((string) $part)); if (strlen($part) >= 3 && strpos($low, $part) !== false) { return "Do not use your name or email in your password."; } }
    return "";
}
