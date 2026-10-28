<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/email_rules.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$local = in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true);
$done = false; $error = ""; $code = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    if (($ee = email_problem($email)) !== "") {
        $error = $ee;
    } else {
        $st = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
        $st->execute([$email]);
        $p = $st->fetch();
        if ($p) {
            $pdo->prepare("DELETE FROM password_resets WHERE parent_id = ?")->execute([$p["id"]]);
            $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
            $pdo->prepare("INSERT INTO password_resets (parent_id, token_hash, expires_at, attempts) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0)")->execute([$p["id"], hash("sha256", $p["id"] . ":" . $code)]);
            @mail($email, "Your LearnQuest reset code", "Your reset code is " . $code . ". It works for 15 minutes.", "From: no-reply@learnquest.local");
            $_SESSION["reset_email"] = $email;
        }
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Forgot password</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 30px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 8px; }
p.sub { text-align: center; color: #5D4037; font-weight: 600; margin-bottom: 14px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button, a.btn { display: block; width: 100%; margin-top: 16px; padding: 14px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 800; cursor: pointer; text-align: center; text-decoration: none; }
a.btn.green { background: #00C853; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 12px; font-weight: 700; margin-bottom: 10px; }
.code { background: #FFF9C4; border: 3px dashed #FF6D00; border-radius: 14px; padding: 14px; text-align: center; color: #5D4037; font-weight: 700; margin-bottom: 10px; }
.code b { display: block; font-size: 2.4rem; letter-spacing: 8px; color: #C2185B; margin: 6px 0; }
.code small { display: block; font-weight: 600; color: #8D6E63; }
.links { text-align: center; margin-top: 14px; font-weight: 700; }
.links a { color: #6A1B9A; }
</style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
<div class="card">
<h1>Forgot password?</h1>
<p class="sub">Type your parent email to get a reset code.</p>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<?php if ($done): ?>
<div class="ok">If that email has an account, a 6-digit code was made. It works for 15 minutes.</div>
<?php if ($code !== "" && $local): ?><div class="code">Your reset code<b><?= e($code) ?></b><small>Shown here because you are on this computer. On a live site it would only be emailed.</small></div><?php endif; ?>
<a href="reset_password.php" class="btn green">Reset my password</a>
<?php else: ?>
<form method="post"><input type="email" name="email" placeholder="Parent email" required><button type="submit">Get reset code</button></form>
<?php endif; ?>
<p class="links"><a href="login2.php">Back to log in</a></p>
</div>
</body>
</html>
