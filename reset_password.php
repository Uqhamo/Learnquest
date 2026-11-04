<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/password_rules.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$email = $_SESSION["reset_email"] ?? "";
if ($email === "") { header("Location: forgot_password.php"); exit; }
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = preg_replace("/\D/", "", $_POST["code"] ?? ""); $pass = $_POST["pass"] ?? ""; $confirm = $_POST["confirm"] ?? "";
    $acc = null;
    foreach (["parent" => "parents", "teacher" => "teachers"] as $role => $table) { $st = $pdo->prepare("SELECT id, name, surname FROM " . $table . " WHERE email = ?"); $st->execute([$email]); $r = $st->fetch(); if ($r) { $acc = [$role, $table, (int) $r["id"], $r["name"], $r["surname"]]; break; } }
    if ($pass !== $confirm) { $error = "The two passwords do not match."; }
    elseif ($acc && ($pe = password_problem($pass, $acc[3], $acc[4], $email)) !== "") { $error = $pe; }
    else {
        $ok = false; $msg = "That code is not right or has expired. Ask for a new one.";
        if ($acc) {
            $st = $pdo->prepare("SELECT id, code_hash, attempts, (expires_at > NOW()) AS live FROM reset_codes WHERE role = ? AND account_id = ?");
            $st->execute([$acc[0], $acc[2]]); $c = $st->fetch();
            if ($c && $c["live"] && (int) $c["attempts"] < 5) {
                if (hash_equals($c["code_hash"], hash("sha256", "reset:" . $acc[0] . ":" . $acc[2] . ":" . $code))) {
                    $pdo->prepare("UPDATE " . $acc[1] . " SET password_hash = ? WHERE id = ?")->execute([password_hash($pass, PASSWORD_DEFAULT), $acc[2]]);
                    if ($acc[0] === "teacher") { $pdo->prepare("UPDATE teachers SET failed_logins = 0, locked_until = NULL WHERE id = ?")->execute([$acc[2]]); }
                    $pdo->prepare("DELETE FROM reset_codes WHERE id = ?")->execute([$c["id"]]);
                    $pdo->prepare("DELETE FROM trusted_devices WHERE role = ? AND account_id = ?")->execute([$acc[0], $acc[2]]);
                    unset($_SESSION["reset_email"]); header("Location: login2.php?reset=1"); exit;
                } else { $pdo->prepare("UPDATE reset_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$c["id"]]); }
            } elseif ($c && (int) $c["attempts"] >= 5) { $msg = "Too many wrong tries. Ask for a new code."; }
        }
        $error = $msg;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Reset password</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 28px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 6px; }
p { color: #5D4037; font-weight: 600; margin-bottom: 10px; text-align: center; }
label { display: block; font-weight: 700; color: #5D4037; margin: 10px 0 4px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
input.code { font-size: 1.5rem; letter-spacing: 8px; text-align: center; }
button { width: 100%; margin-top: 14px; padding: 13px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.05rem; font-weight: 800; cursor: pointer; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
.hint { color: #8D6E63; font-size: .8rem; text-align: left; }
a.back { display: block; text-align: center; margin-top: 14px; color: #6A1B9A; font-weight: 800; }
</style>
</head>
<body>
<div class="card">
<h1>Reset password</h1>
<p>If that email has an account, we sent a 6-digit code. It works for 15 minutes. Check Spam too.</p>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><label>6-digit code</label><input type="text" name="code" class="code" inputmode="numeric" maxlength="6" placeholder="000000" autocomplete="one-time-code" required><label>New password</label><input type="password" name="pass" minlength="8" autocomplete="new-password" required><p class="hint">8 or more characters with a capital letter, a number and a symbol.</p><label>Repeat the new password</label><input type="password" name="confirm" autocomplete="new-password" required><button type="submit">Change my password</button></form>
<a href="forgot_password.php" class="back">Send me a new code</a>
</div>
</body>
</html>
