<?php
session_start();
require_once __DIR__ . "/database.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = strtoupper(trim($_POST["code"] ?? "")); $code = preg_replace("/[\s_]+/", "-", $code);
    $pin = $_POST["pin"] ?? "";
    $st = $pdo->prepare("SELECT id, age, pin_hash, failed_pins, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM children WHERE player_code = ?");
    $st->execute([$code]);
    $ch = $st->fetch();
    if ($ch && $ch["is_locked"]) {
        $error = "Too many wrong tries. Please wait 5 minutes.";
    } elseif ($ch && $ch["pin_hash"] && password_verify($pin, $ch["pin_hash"])) {
        $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = NULL WHERE id = ?")->execute([$ch["id"]]);
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION["child_login"] = true;
        $_SESSION["child_id"] = (int) $ch["id"];
        $_SESSION["child_age"] = (int) $ch["age"];
        header("Location: mypage.php");
        exit;
    } else {
        if ($ch) {
            $f = (int) $ch["failed_pins"] + 1;
            if ($f >= 5) {
                $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?")->execute([$ch["id"]]);
            } else {
                $pdo->prepare("UPDATE children SET failed_pins = ? WHERE id = ?")->execute([$f, $ch["id"]]);
            }
        }
        $error = "Wrong Player ID or PIN.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Child Log In</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 30px; width: 100%; max-width: 400px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 6px; }
.sub { text-align: center; color: #5D4037; font-weight: 600; margin-bottom: 18px; }
label { display: block; font-weight: 700; color: #5D4037; margin: 12px 0 5px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1.1rem; background: #FFFDE7; }
button { width: 100%; margin-top: 20px; padding: 14px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 800; cursor: pointer; border-bottom: 5px solid #C2185B; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 8px; }
.links { text-align: center; margin-top: 16px; font-weight: 700; }
.links a { color: #6A1B9A; }
</style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
<div class="card">
    <h1>Welcome back!</h1>
    <p class="sub">Type your Player ID and PIN</p>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Player ID (like BLUE-LION-42)</label>
        <input type="text" name="code" maxlength="30" autocomplete="off" required>
        <label>PIN</label>
        <input type="password" name="pin" inputmode="numeric" maxlength="6" required>
        <button type="submit">Let's Play!</button>
    </form><div style="margin-top:16px;padding:12px;border-top:2px dashed #FFD600;text-align:center;color:#5D4037;font-weight:700"><span>Are you a member yet?</span><a href="welcome.php" style="display:block;margin-top:8px;background:#00BCD4;color:#fff;border-radius:12px;padding:11px;text-decoration:none;font-weight:800">Sign up here</a></div>
    
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>
