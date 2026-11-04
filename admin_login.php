<?php
require_once __DIR__ . "/admin_guard.php";
require_once __DIR__ . "/mail_helper.php";
$error = ""; $info = ""; $stage = isset($_SESSION["admin_pending"]) ? "code" : "password";
if (isset($_GET["timeout"])) { $info = "You were signed out after 30 minutes. Please sign in again."; }
function ah($id, $code) { return hash("sha256", "admin:" . $id . ":" . $code); }
function admin_send($pdo, $id, $email, $name) {
    $st = $pdo->prepare("SELECT (created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)) AS too_soon FROM admin_codes WHERE admin_id = ?");
    $st->execute([$id]);
    $r = $st->fetch();
    if ($r && $r["too_soon"]) { return "wait"; }
    $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $pdo->prepare("DELETE FROM admin_codes WHERE admin_id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO admin_codes (admin_id, code_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")->execute([$id, ah($id, $code)]);
    $err = null;
    $html = "<p>Hi " . htmlspecialchars($name) . ",</p><p>Your LearnQuest ADMIN code is:</p><p style=\"font-size:28px;font-weight:bold;letter-spacing:6px\">" . $code . "</p><p>It works for 10 minutes. If this was not you, change your admin password.</p>";
    return send_mail($email, "LearnQuest admin login code", $html, $err) ? "sent" : "failed";
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["email"])) {
        $email = strtolower(trim($_POST["email"]));
        $st = $pdo->prepare("SELECT id, name, email, password_hash, disabled, failed_logins, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM admins WHERE email = ?");
        $st->execute([$email]);
        $a = $st->fetch();
        if ($a && $a["is_locked"]) { $error = "Too many wrong tries. Please wait 15 minutes."; } elseif ($a && !empty($a["disabled"])) { $error = "This admin account is locked."; }
        elseif ($a && password_verify($_POST["pass"] ?? "", $a["password_hash"])) {
            $pdo->prepare("UPDATE admins SET failed_logins = 0, locked_until = NULL WHERE id = ?")->execute([$a["id"]]);
            $r = admin_send($pdo, (int) $a["id"], $a["email"], $a["name"]);
            if ($r === "failed") { $error = "The code email could not be sent. Check the mail settings."; }
            else { $_SESSION = []; session_regenerate_id(true); $_SESSION["admin_pending"] = (int) $a["id"]; header("Location: admin_login.php"); exit; }
        } else {
            if ($a) { $f = (int) $a["failed_logins"] + 1; if ($f >= 5) { $pdo->prepare("UPDATE admins SET failed_logins = 0, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?")->execute([$a["id"]]); } else { $pdo->prepare("UPDATE admins SET failed_logins = ? WHERE id = ?")->execute([$f, $a["id"]]); } }
            $error = "Wrong email or password.";
        }
    } elseif (isset($_SESSION["admin_pending"])) {
        $aid = (int) $_SESSION["admin_pending"];
        if (($_POST["action"] ?? "") === "resend") {
            $s = $pdo->prepare("SELECT name, email FROM admins WHERE id = ?"); $s->execute([$aid]); $a = $s->fetch();
            $r = $a ? admin_send($pdo, $aid, $a["email"], $a["name"]) : "failed";
            if ($r === "sent") { $info = "A new code was sent."; } elseif ($r === "wait") { $error = "Please wait a minute."; } else { $error = "Could not send the email."; }
        } else {
            $s = $pdo->prepare("SELECT id, code_hash, attempts, (expires_at > NOW()) AS live FROM admin_codes WHERE admin_id = ?"); $s->execute([$aid]); $c = $s->fetch();
            if (!$c || !$c["live"]) { $error = "That code has expired. Ask for a new one."; }
            elseif ((int) $c["attempts"] >= 5) { $error = "Too many wrong tries. Ask for a new code."; }
            elseif (hash_equals($c["code_hash"], ah($aid, preg_replace("/\D/", "", $_POST["code"] ?? "")))) {
                $pdo->prepare("DELETE FROM admin_codes WHERE id = ?")->execute([$c["id"]]);
                unset($_SESSION["admin_pending"]); session_regenerate_id(true);
                $_SESSION["admin_id"] = $aid; $_SESSION["admin_seen"] = time();
                admin_log($pdo, "login", "");
                header("Location: admin_dashboard.php"); exit;
            } else { $pdo->prepare("UPDATE admin_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$c["id"]]); $error = "That code is not right."; }
        }
    }
    $stage = isset($_SESSION["admin_pending"]) ? "code" : "password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #263238; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border-radius: 18px; padding: 28px; width: 100%; max-width: 400px; box-shadow: 0 8px 24px rgba(0,0,0,.4); }
h1 { text-align: center; color: #263238; margin-bottom: 4px; }
p.sub { text-align: center; color: #607D8B; font-weight: 600; margin-bottom: 14px; font-size: .9rem; }
label { display: block; font-weight: 700; color: #37474F; margin: 10px 0 4px; }
input { width: 100%; padding: 12px; border: 2px solid #CFD8DC; border-radius: 10px; font-size: 1rem; }
input.code { font-size: 1.6rem; letter-spacing: 8px; text-align: center; }
button { width: 100%; margin-top: 14px; padding: 13px; background: #00897B; color: #fff; border: none; border-radius: 12px; font-size: 1.05rem; font-weight: 800; cursor: pointer; }
button.alt { background: #546E7A; }
.err { background: #FFEBEE; border: 2px solid #E53935; color: #C62828; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
</style>
</head>
<body>
<div class="card">
<h1>Admin</h1>
<p class="sub">LearnQuest administration</p>
<?php if ($info): ?><div class="ok"><?= e($info) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<?php if ($stage === "password"): ?>
<form method="post"><label>Email</label><input type="email" name="email" autocomplete="username" required><label>Password</label><input type="password" name="pass" autocomplete="current-password" required><button type="submit">Continue</button></form>
<?php else: ?>
<p class="sub">We emailed you a 6-digit code.</p>
<form method="post"><input type="text" name="code" class="code" inputmode="numeric" maxlength="6" placeholder="000000" autocomplete="one-time-code" required><button type="submit">Sign in</button></form>
<form method="post"><input type="hidden" name="action" value="resend"><button type="submit" class="alt">Send a new code</button></form>
<?php endif; ?>
</div>
</body>
</html>
