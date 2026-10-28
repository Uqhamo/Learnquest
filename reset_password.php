<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/password_rules.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$error = ""; $done = false;
$email = trim($_POST["email"] ?? ($_SESSION["reset_email"] ?? ""));
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = preg_replace("/\D/", "", $_POST["code"] ?? "");
    $pass = $_POST["pass"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $st = $pdo->prepare("SELECT id, name, surname, email FROM parents WHERE email = ?");
    $st->execute([$email]);
    $p = $st->fetch();
    $r = null;
    if ($p) {
        $st2 = $pdo->prepare("SELECT id, token_hash, attempts FROM password_resets WHERE parent_id = ? AND expires_at > NOW()");
        $st2->execute([$p["id"]]);
        $r = $st2->fetch();
    }
    if (!$p || !$r) {
        $error = "That code is not valid or has expired. Ask for a new one.";
    } elseif ((int) $r["attempts"] >= 5) {
        $pdo->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$r["id"]]);
        $error = "Too many wrong tries. Ask for a new code.";
    } elseif (!hash_equals($r["token_hash"], hash("sha256", $p["id"] . ":" . $code))) {
        $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?")->execute([$r["id"]]);
        $error = "Wrong code. Please check it and try again.";
    } else {
        $pe = password_problem($pass, $p["name"], $p["surname"], $p["email"]);
        if ($pe !== "") { $error = $pe; }
        elseif ($pass !== $confirm) { $error = "Passwords do not match."; }
        else {
            $pdo->prepare("UPDATE parents SET password_hash = ? WHERE id = ?")->execute([password_hash($pass, PASSWORD_DEFAULT), $p["id"]]);
            $pdo->prepare("DELETE FROM password_resets WHERE parent_id = ?")->execute([$p["id"]]);
            unset($_SESSION["reset_email"]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - New password</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 30px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 8px; }
label { display: block; font-weight: 700; color: #5D4037; margin: 12px 0 5px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
input.code { font-size: 1.5rem; letter-spacing: 6px; text-align: center; }
button { width: 100%; margin-top: 16px; padding: 14px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 800; cursor: pointer; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 12px; font-weight: 700; margin-bottom: 10px; }
.hint { color: #8D6E63; font-size: 0.8rem; margin-top: 4px; }
.links { text-align: center; margin-top: 14px; font-weight: 700; }
.links a { color: #6A1B9A; }
</style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
<div class="card">
<h1>New password</h1>
<?php if ($done): ?>
<div class="ok">Your password has been changed.</div>
<p class="links"><a href="login2.php">Log in</a></p>
<?php else: ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<label>Parent email</label><input type="email" name="email" value="<?= e($email) ?>" required>
<label>6-digit code</label><input type="text" name="code" class="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
<label>New password</label><input type="password" name="pass" minlength="8" autocomplete="new-password" required>
<p class="hint">8 or more characters with a capital letter, a number and a symbol.</p>
<label>Repeat the password</label><input type="password" name="confirm" autocomplete="new-password" required>
<button type="submit">Save new password</button>
</form>
<p class="links"><a href="forgot_password.php">I need a new code</a></p>
<?php endif; ?>
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>
