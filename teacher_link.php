<?php
session_start();
require_once __DIR__ . "/database.php";
if (empty($_SESSION["teacher_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$tid = (int) $_SESSION["teacher_id"];
$msg = ""; $err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "link") {
        $code = strtoupper(preg_replace("/[\s_]+/", "-", trim($_POST["code"] ?? "")));
        $pin = $_POST["pin"] ?? "";
        $st = $pdo->prepare("SELECT id, name, teacher_id, pin_hash, failed_pins, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM children WHERE player_code = ?");
        $st->execute([$code]);
        $ch = $st->fetch();
        if ($ch && $ch["is_locked"]) { $err = "Too many wrong tries. Please wait 5 minutes."; }
        elseif ($ch && $ch["pin_hash"] && password_verify($pin, $ch["pin_hash"])) {
            if ($ch["teacher_id"] && (int) $ch["teacher_id"] !== $tid) { $err = "That learner is already linked to another teacher."; }
            else { $pdo->prepare("UPDATE children SET teacher_id = ?, pending_teacher_id = NULL, failed_pins = 0, locked_until = NULL WHERE id = ?")->execute([$tid, $ch["id"]]); $msg = $ch["name"] . " is now linked to you."; }
        } else {
            if ($ch) { $f = (int) $ch["failed_pins"] + 1; if ($f >= 5) { $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?")->execute([$ch["id"]]); } else { $pdo->prepare("UPDATE children SET failed_pins = ? WHERE id = ?")->execute([$f, $ch["id"]]); } }
            $err = "Wrong Player ID or PIN.";
        }
    } elseif ($action === "approve") {
        $st = $pdo->prepare("UPDATE children SET teacher_id = ?, pending_teacher_id = NULL WHERE id = ? AND pending_teacher_id = ? AND teacher_id IS NULL");
        $st->execute([$tid, (int) ($_POST["child_id"] ?? 0), $tid]);
        $msg = $st->rowCount() ? "Learner linked to you." : "That request is no longer waiting.";
    } elseif ($action === "decline") {
        $pdo->prepare("UPDATE children SET pending_teacher_id = NULL WHERE id = ? AND pending_teacher_id = ?")->execute([(int) ($_POST["child_id"] ?? 0), $tid]);
        $msg = "Request declined.";
    } elseif ($action === "reset_pin") {
        require_once __DIR__ . "/pin_rules.php";
        $cid = (int) ($_POST["child_id"] ?? 0); $p1 = $_POST["pin1"] ?? ""; $p2 = $_POST["pin2"] ?? "";
        $tq = $pdo->prepare("SELECT approved, disabled FROM teachers WHERE id = ?"); $tq->execute([$tid]); $tr = $tq->fetch();
        $cq = $pdo->prepare("SELECT name FROM children WHERE id = ? AND teacher_id = ?"); $cq->execute([$cid, $tid]); $cr = $cq->fetch();
        if (!$tr || empty($tr["approved"]) || !empty($tr["disabled"])) { $err = "Your account cannot reset PINs."; }
        elseif (!$cr) { $err = "That learner is not linked to you."; }
        elseif (($pp = pin_problem($p1)) !== "") { $err = $pp; }
        elseif ($p1 !== $p2) { $err = "The two PINs do not match."; }
        else {
            $pdo->prepare("UPDATE children SET pin_hash = ?, failed_pins = 0, locked_until = NULL WHERE id = ? AND teacher_id = ?")->execute([password_hash($p1, PASSWORD_DEFAULT), $cid, $tid]);
            $pdo->prepare("INSERT INTO pin_reset_log (teacher_id, child_id) VALUES (?, ?)")->execute([$tid, $cid]);
            $msg = $cr["name"] . "'s PIN was changed. Tell them the new PIN in person.";
        }
    } elseif ($action === "remove") {
        $pdo->prepare("UPDATE children SET teacher_id = NULL WHERE id = ? AND teacher_id = ?")->execute([(int) ($_POST["child_id"] ?? 0), $tid]);
        $msg = "Learner removed from your list.";
    }
}
$st = $pdo->prepare("SELECT id, name, surname, age, grade, created_at FROM children WHERE pending_teacher_id = ? AND teacher_id IS NULL");
$st->execute([$tid]);
$pending = $st->fetchAll();
$st = $pdo->prepare("SELECT id, name, surname, grade FROM children WHERE teacher_id = ? ORDER BY grade, name");
$st->execute([$tid]);
$mine = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Learners</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #F3F6FB; min-height: 100vh; padding: 28px 18px; color: #263238; }
.wrap { max-width: 600px; margin: auto; }
.back { display: inline-block; margin-bottom: 14px; color: #2b3a8f; font-weight: 700; text-decoration: none; }
h1 { color: #2b3a8f; margin-bottom: 14px; }
h2 { color: #2b3a8f; font-size: 1.1rem; margin-bottom: 8px; }
.card { background: #fff; border-radius: 14px; padding: 18px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.hint { color: #607D8B; font-weight: 600; font-size: .9rem; margin-bottom: 10px; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 14px; }
.bad { background: #FFEBEE; border: 2px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 14px; }
.row { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; padding: 10px 0; border-top: 1px solid #ECEFF1; }
small { color: #78909C; font-weight: 600; display: block; }
label { display: block; font-weight: 700; margin: 10px 0 4px; }
input[type=text], input[type=password] { width: 100%; padding: 11px; border: 2px solid #C5CAE9; border-radius: 10px; font-size: 1rem; }
button { background: #4361ee; color: #fff; border: none; border-radius: 10px; padding: 9px 14px; font-weight: 700; cursor: pointer; }
button.no { background: #9E9E9E; }
button.rm { background: #E53935; }
form.inline { display: inline; }
form.stack button { margin-top: 12px; }
</style>
</head>
<body>
<div class="wrap">
<a href="teacher_dashboard.php" class="back">&larr; Back to dashboard</a>
<h1>My learners</h1>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<div class="card"><h2>Waiting for you (<?= count($pending) ?>)</h2>
<?php if (!$pending): ?><p class="hint">No requests right now. When a learner types your email on their page, they appear here.</p><?php endif; ?>
<?php foreach ($pending as $c): ?><div class="row"><div><b><?= e($c["name"] . " " . $c["surname"]) ?></b><small>Grade <?= (int) $c["grade"] ?> &middot; Age <?= (int) $c["age"] ?> &middot; Joined <?= e(substr((string) $c["created_at"], 0, 10)) ?></small></div><div><form method="post" class="inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><button type="submit">Approve</button></form> <form method="post" class="inline"><input type="hidden" name="action" value="decline"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><button type="submit" class="no">Decline</button></form></div></div><?php endforeach; ?></div>
<div class="card"><h2>Add a learner with their Player ID</h2><p class="hint">Ask the learner for their Player ID and PIN.</p><form method="post" class="stack"><input type="hidden" name="action" value="link"><label>Player ID (like BLUE-LION-42)</label><input type="text" name="code" maxlength="30" autocomplete="off" required><label>Learner PIN</label><input type="password" name="pin" inputmode="numeric" maxlength="6" required><button type="submit">Link learner</button></form></div>
<div class="card"><h2>Linked to you (<?= count($mine) ?>)</h2>
<?php if (!$mine): ?><p class="hint">No learners linked yet.</p><?php endif; ?>
<?php foreach ($mine as $c): ?><div class="row"><div><b><?= e($c["name"] . " " . $c["surname"]) ?></b><small>Grade <?= (int) $c["grade"] ?></small></div><form method="post" class="inline"><input type="hidden" name="action" value="remove"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><button type="submit" class="rm">Remove</button></form><form method="post" class="inline" style="margin-top:6px"><input type="hidden" name="action" value="reset_pin"><input type="hidden" name="child_id" value="<?= (int) $c["id"] ?>"><input type="password" name="pin1" inputmode="numeric" maxlength="6" placeholder="New PIN" required style="width:90px;padding:6px"> <input type="password" name="pin2" inputmode="numeric" maxlength="6" placeholder="Repeat" required style="width:90px;padding:6px"> <button type="submit" onclick="return confirm(String.fromCharCode(67,104,97,110,103,101,32,116,104,101,32,80,73,78,63))">Reset PIN</button></form></div><?php endforeach; ?></div>
</div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script><?php include __DIR__ . "/footer.php"; ?></body>
</html>
