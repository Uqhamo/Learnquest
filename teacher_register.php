<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/password_rules.php";
require_once __DIR__ . "/email_rules.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $school = trim($_POST["school"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $pass = $_POST["pass"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    if ($name === "" || $surname === "" || $school === "") {
        $error = "Please fill in your name, surname and school.";
    } elseif (($ee = email_problem($email)) !== "") {
        $error = $ee;
    } elseif (($pe = password_problem($pass, $name, $surname, $email)) !== "") {
        $error = $pe;
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $st = $pdo->prepare("SELECT 1 FROM teachers WHERE email = ?");
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = "That email is already registered.";
        } else {
            $pdo->prepare("INSERT INTO teachers (name, surname, school, email, password_hash) VALUES (?, ?, ?, ?, ?)")->execute([$name, $surname, $school, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $id = (int) $pdo->lastInsertId();
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION["teacher_id"] = $id;
            header("Location: teacher_dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Teacher Sign Up</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #4361ee; border-radius: 24px; box-shadow: 6px 6px 0 #3046b8; padding: 30px; width: 100%; max-width: 440px; }
h1 { text-align: center; color: #4361ee; margin-bottom: 6px; }
.sub { text-align: center; color: #5D4037; font-weight: 600; margin-bottom: 14px; }
label { display: block; font-weight: 700; color: #5D4037; margin: 12px 0 5px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
button { width: 100%; margin-top: 20px; padding: 14px; background: #4361ee; color: #fff; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 800; cursor: pointer; border-bottom: 5px solid #3046b8; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 8px; }
.hint { color: #8D6E63; font-size: 0.8rem; margin-top: 4px; }
.links { text-align: center; margin-top: 16px; font-weight: 700; }
.links a { color: #6A1B9A; }
@media (max-width: 420px) { .row { grid-template-columns: 1fr; } }
</style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
<div class="card">
<h1>Teacher sign up</h1>
<p class="sub">Follow your learners progress in one place.</p>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<div class="row"><div><label>Name</label><input type="text" name="name" value="<?= e($_POST["name"] ?? "") ?>" required></div><div><label>Surname</label><input type="text" name="surname" value="<?= e($_POST["surname"] ?? "") ?>" required></div></div>
<label>School</label><input type="text" name="school" maxlength="100" value="<?= e($_POST["school"] ?? "") ?>" required>
<label>Email</label><input type="email" name="email" maxlength="100" value="<?= e($_POST["email"] ?? "") ?>" required>
<label>Password</label><input type="password" name="pass" minlength="8" autocomplete="new-password" required>
<p class="hint">8 or more characters with a capital letter, a number and a symbol.</p>
<label>Repeat the password</label><input type="password" name="confirm" autocomplete="new-password" required>
<button type="submit">Create teacher account</button>
</form>
<p class="links"><a href="teacher_login.php">I already have an account</a> &middot; <a href="welcome.php">Back</a></p>
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>
