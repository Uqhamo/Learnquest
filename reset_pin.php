<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/pin_rules.php";
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pid = (int) $_SESSION["parent_id"];
$error = ""; $result = null;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cid = (int) ($_POST["child_id"] ?? 0);
    $pin = $_POST["pin"] ?? "";
    $pin2 = $_POST["pin2"] ?? "";
    $st = $pdo->prepare("SELECT id, name, player_code FROM children WHERE id = ? AND parent_id = ?");
    $st->execute([$cid, $pid]);
    $ch = $st->fetch();
    if (!$ch) { $error = "Child not found."; }
    elseif (($pp = pin_problem($pin)) !== "") { $error = $pp; }
    elseif ($pin !== $pin2) { $error = "The two PINs do not match."; }
    else {
        $code = $ch["player_code"];
        if (!$code) {
            $colours = ["RED","BLUE","GREEN","YELLOW","PURPLE","PINK","ORANGE","BLACK","WHITE","BROWN"];
            $animals = ["LION","TIGER","ELEPHANT","ZEBRA","GIRAFFE","MONKEY","PANDA","RABBIT","DOLPHIN","EAGLE"];
            for ($i = 0; $i < 10 && !$code; $i++) {
                $try = $colours[array_rand($colours)] . "-" . $animals[array_rand($animals)] . "-" . random_int(10, 99);
                $s2 = $pdo->prepare("SELECT 1 FROM children WHERE player_code = ?");
                $s2->execute([$try]);
                if (!$s2->fetch()) { $code = $try; }
            }
        }
        if (!$code) { $error = "Something went wrong. Please try again."; }
        else {
            $pdo->prepare("UPDATE children SET pin_hash = ?, player_code = ?, failed_pins = 0, locked_until = NULL WHERE id = ? AND parent_id = ?")->execute([password_hash($pin, PASSWORD_DEFAULT), $code, $cid, $pid]);
            $result = ["name" => $ch["name"], "code" => $code, "pin" => $pin];
        }
    }
}
$st = $pdo->prepare("SELECT id, name, surname, player_code FROM children WHERE parent_id = ? ORDER BY name");
$st->execute([$pid]);
$children = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Reset a child PIN</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; padding: 30px 20px; }
.wrap { max-width: 560px; margin: auto; }
.back { display: inline-block; margin-bottom: 16px; color: #6A1B9A; font-weight: bold; text-decoration: none; }
h1 { text-align: center; color: #FF4081; margin-bottom: 16px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 20px; box-shadow: 5px 5px 0 #C2185B; padding: 18px; margin-bottom: 16px; }
.card h2 { color: #FF6D00; font-size: 1.1rem; }
.id { color: #8D6E63; font-weight: 700; margin-bottom: 8px; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
input { width: 100%; padding: 10px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button { margin-top: 10px; padding: 11px 16px; background: #FF4081; color: #fff; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 12px; font-weight: 700; margin-bottom: 12px; text-align: center; }
.ok b { display: block; font-size: 1.4rem; letter-spacing: 3px; color: #C2185B; }
</style>
</head>
<body>
<div class="wrap">
<a href="profile.php" class="back">&larr; Back to profile</a>
<h1>Reset a child PIN</h1>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<?php if ($result): ?><div class="ok">New login for <?= e($result["name"]) ?><b>Player ID: <?= e($result["code"]) ?></b><b>PIN: <?= e($result["pin"]) ?></b>Write these down. The PIN is shown only now.</div><?php endif; ?>
<?php if (!$children): ?><p>No children yet.</p><?php endif; ?>
<?php foreach ($children as $c): ?>
<div class="card"><h2><?= e($c["name"] . " " . $c["surname"]) ?></h2><div class="id"><?= $c["player_code"] ? "Player ID: " . e($c["player_code"]) : "No Player ID yet. One will be made." ?></div>
<form method="post"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><div class="row"><input type="password" name="pin" inputmode="numeric" maxlength="6" placeholder="New PIN" required><input type="password" name="pin2" inputmode="numeric" maxlength="6" placeholder="Repeat PIN" required></div><button type="submit">Set new PIN</button></form></div>
<?php endforeach; ?>
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>
