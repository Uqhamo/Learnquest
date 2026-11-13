<?php
session_start();
require_once __DIR__ . "/database.php";
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pid = (int) $_SESSION["parent_id"];
$msg = ""; $err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "link") {
        $code = strtoupper(preg_replace("/[\s_]+/", "-", trim($_POST["code"] ?? "")));
        $pin = $_POST["pin"] ?? "";
        $st = $pdo->prepare("SELECT id, name, parent_id, pin_hash, failed_pins, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM children WHERE player_code = ?");
        $st->execute([$code]);
        $ch = $st->fetch();
        if ($ch && $ch["is_locked"]) { $err = "Too many wrong tries. Please wait 5 minutes."; }
        elseif ($ch && $ch["pin_hash"] && password_verify($pin, $ch["pin_hash"])) {
            if ($ch["parent_id"] && (int) $ch["parent_id"] !== $pid) { $err = "That child is already linked to another parent."; }
            else { $pdo->prepare("UPDATE children SET parent_id = ?, pending_parent_id = NULL, failed_pins = 0, locked_until = NULL WHERE id = ?")->execute([$pid, $ch["id"]]); $msg = $ch["name"] . " is now linked to your account."; }
        } else {
            if ($ch) { $f = (int) $ch["failed_pins"] + 1; if ($f >= 5) { $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?")->execute([$ch["id"]]); } else { $pdo->prepare("UPDATE children SET failed_pins = ? WHERE id = ?")->execute([$f, $ch["id"]]); } }
            $err = "Wrong Player ID or PIN.";
        }
    } elseif ($action === "approve") {
        $st = $pdo->prepare("UPDATE children SET parent_id = ?, pending_parent_id = NULL WHERE id = ? AND pending_parent_id = ? AND parent_id IS NULL");
        $st->execute([$pid, (int) ($_POST["child_id"] ?? 0), $pid]);
        $msg = $st->rowCount() ? "Child linked to your account." : "That request is no longer waiting.";
    } elseif ($action === "decline") {
        $pdo->prepare("UPDATE children SET pending_parent_id = NULL WHERE id = ? AND pending_parent_id = ?")->execute([(int) ($_POST["child_id"] ?? 0), $pid]);
        $msg = "Request declined.";
    }
}
$st = $pdo->prepare("SELECT id, name, surname, age, grade, player_code, created_at FROM children WHERE pending_parent_id = ? AND parent_id IS NULL");
$st->execute([$pid]);
$pending = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Link a Child</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; padding: 30px 20px; }
.wrap { width: 100%; max-width: 560px; margin: auto; }
.back { display: inline-block; margin-bottom: 18px; color: #6A1B9A; font-weight: bold; text-decoration: none; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 22px; box-shadow: 6px 6px 0 #C2185B; padding: 24px; margin-bottom: 20px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 16px; }
h2 { color: #FF6D00; font-size: 1.15rem; margin-bottom: 8px; }
p.hint { color: #8D6E63; font-weight: 600; margin-bottom: 12px; font-size: 0.9rem; }
label { display: block; font-weight: 700; color: #5D4037; margin: 10px 0 4px; }
input { width: 100%; padding: 11px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button { margin-top: 14px; padding: 12px 18px; border: none; border-radius: 12px; color: #fff; font-weight: 800; cursor: pointer; background: #FF4081; font-size: 1rem; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 14px; }
.bad { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 14px; }
.req { background: #FFF9C4; border: 3px solid #FFD600; border-radius: 12px; padding: 12px; margin-bottom: 10px; color: #5D4037; }
.req form { display: inline; }
.req button { margin-top: 8px; padding: 8px 14px; }
.no { background: #9E9E9E; }
</style>
</head>
<body>
<div class="wrap">
<a href="parent_dashboard.php" class="back">&larr; Back to Dashboard</a>
<h1>Link a Child</h1>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<div class="card">
<h2>Children waiting for you (<?= count($pending) ?>)</h2>
<?php if (!$pending): ?><p class="hint">No requests right now. When a child signs up on their own and types your email, they appear here.</p><?php endif; ?>
<?php foreach ($pending as $c): ?>
<div class="req"><b><?= e($c["name"] . " " . $c["surname"]) ?></b> &middot; Age <?= (int) $c["age"] ?> &middot; Grade <?= (int) $c["grade"] ?><br>Player ID: <?= e($c["player_code"]) ?> &middot; Joined <?= e(substr((string) $c["created_at"], 0, 10)) ?><br>
<form method="post"><input type="hidden" name="action" value="approve"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><button type="submit">Approve</button></form> 
<form method="post"><input type="hidden" name="action" value="decline"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><button type="submit" class="no">Decline</button></form></div>
<?php endforeach; ?>
</div>
<div class="card">
<h2>Link with a Player ID</h2>
<p class="hint">Type your child Player ID and PIN. Once linked, you see their full progress under Progress.</p>
<form method="post"><input type="hidden" name="action" value="link"><label>Player ID (like BLUE-LION-42)</label><input type="text" name="code" maxlength="30" autocomplete="off" required><label>Child PIN</label><input type="password" name="pin" inputmode="numeric" maxlength="6" required><button type="submit">Link child</button></form>
</div>
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script><?php include __DIR__ . "/footer.php"; ?></body>
</html>
