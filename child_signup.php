<?php
session_start();
require_once __DIR__ . '/database.php';
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $age = (int) ($_POST['age'] ?? 0);
    $grade = (int) ($_POST['grade'] ?? 0);
    $pin = $_POST['pin'] ?? ''; $colour = strtoupper(trim($_POST['colour'] ?? '')); $animal = strtoupper(trim($_POST['animal'] ?? '')); $colours = ['RED','BLUE','GREEN','YELLOW','PURPLE','PINK','ORANGE','BLACK','WHITE','BROWN']; $animals = ['LION','TIGER','ELEPHANT','ZEBRA','GIRAFFE','MONKEY','PANDA','RABBIT','DOLPHIN','EAGLE'];
    if ($name === '' || $surname === '' || $age < 4 || $age > 12 || $grade < 1 || $grade > 7) {
        $error = 'Please enter your name, surname, age (4 to 12) and grade (1 to 7).';
    } elseif (!preg_match('/^\d{4,6}$/', $pin)) {
        $error = 'Your PIN must be 4 to 6 numbers.'; } elseif (!in_array($colour, $colours, true) || !in_array($animal, $animals, true)) { $error = 'Please pick your favourite colour and animal.';
    } else {
        $code = '';
        for ($i = 0; $i < 10 && $code === ''; $i++) {
            $try = $colour . '-' . $animal . '-' . random_int(10, $i < 6 ? 99 : 999);
            $st = $pdo->prepare('SELECT 1 FROM children WHERE player_code = ?');
            $st->execute([$try]);
            if (!$st->fetch()) { $code = $try; }
        }
        if ($code === '') {
            $error = 'Something went wrong. Please try again.';
        } else {
            $st = $pdo->prepare('INSERT INTO children (parent_id, name, surname, age, grade, player_code, pin_hash) VALUES (NULL, ?, ?, ?, ?, ?, ?)');
            $st->execute([$name, $surname, $age, $grade, $code, password_hash($pin, PASSWORD_DEFAULT)]);
            $id = (int) $pdo->lastInsertId();
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['child_login'] = true;
            $_SESSION['child_id'] = $id;
            $_SESSION['child_age'] = $age;
            header('Location: mypage.php?welcome=1');
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
<title>LearnQuest - Child Sign Up</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 30px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 6px; }
.sub { text-align: center; color: #5D4037; font-weight: 600; margin-bottom: 18px; }
label { display: block; font-weight: 700; color: #5D4037; margin: 12px 0 5px; }
input, select { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button { width: 100%; margin-top: 20px; padding: 14px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 800; cursor: pointer; border-bottom: 5px solid #C2185B; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 8px; }
.links { text-align: center; margin-top: 16px; font-weight: 700; }
.links a { color: #6A1B9A; }
</style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
<div class="card">
    <h1>Join LearnQuest</h1>
    <p class="sub">Make your own Player ID and start learning!</p>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Your name</label>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
        <label>Your surname</label>
        <input type="text" name="surname" value="<?= e($_POST['surname'] ?? '') ?>" required>
        <label>How old are you?</label>
        <input type="number" name="age" min="4" max="12" value="<?= e($_POST['age'] ?? '') ?>" required>
        <label>Your grade</label>
        <select name="grade" required>
            <?php for ($g = 1; $g <= 7; $g++): ?>
                <option value="<?= $g ?>">Grade <?= $g ?></option>
            <?php endfor; ?>
        </select>
        <label>Your favourite colour</label><select name="colour" required><option value="">Pick one</option><option value="RED">Red</option><option value="BLUE">Blue</option><option value="GREEN">Green</option><option value="YELLOW">Yellow</option><option value="PURPLE">Purple</option><option value="PINK">Pink</option><option value="ORANGE">Orange</option><option value="BLACK">Black</option><option value="WHITE">White</option><option value="BROWN">Brown</option></select><label>Your favourite animal</label><select name="animal" required><option value="">Pick one</option><option value="LION">Lion</option><option value="TIGER">Tiger</option><option value="ELEPHANT">Elephant</option><option value="ZEBRA">Zebra</option><option value="GIRAFFE">Giraffe</option><option value="MONKEY">Monkey</option><option value="PANDA">Panda</option><option value="RABBIT">Rabbit</option><option value="DOLPHIN">Dolphin</option><option value="EAGLE">Eagle</option></select><label>Make a secret PIN (4 to 6 numbers)</label>
        <input type="password" name="pin" inputmode="numeric" maxlength="6" required>
        <button type="submit">Create my Player ID</button>
    </form>
    <p class="links"><a href="child_login.php">I already have a Player ID</a> &middot; <a href="welcome.php">Back</a></p>
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>