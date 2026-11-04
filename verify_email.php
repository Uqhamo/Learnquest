<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/verify_helper.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pend = $_SESSION["verify_pending"] ?? null;
if (!$pend) { header("Location: welcome.php"); exit; }
$role = $pend["role"]; $id = (int) $pend["id"];
$table = $role === "teacher" ? "teachers" : "parents";
$st = $pdo->prepare("SELECT name, email, email_verified FROM " . $table . " WHERE id = ?");
$st->execute([$id]);
$acc = $st->fetch();
if (!$acc) { unset($_SESSION["verify_pending"]); header("Location: welcome.php"); exit; }
$masked = substr($acc["email"], 0, 2) . "***" . substr($acc["email"], strpos($acc["email"], "@"));
$msg = ""; $err = "";
function vh_done($role, $id) { unset($_SESSION["verify_pending"]); session_regenerate_id(true); if ($role === "teacher") { $_SESSION["teacher_id"] = $id; header("Location: teacher_dashboard.php"); } else { $_SESSION["parent_id"] = $id; header("Location: select_child.php"); } exit; }
if ((int) $acc["email_verified"] === 1) { vh_done($role, $id); }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (($_POST["action"] ?? "") === "resend") {
        $r = vh_send($pdo, $role, $id, $acc["email"], $acc["name"]);
        if ($r === "sent") { $msg = "A new code was sent. Check your inbox and Spam."; }
        elseif ($r === "wait") { $err = "Please wait a minute before asking for another code."; }
        else { $err = "We could not send the email. Please try again later."; }
    } else {
        $r = vh_check($pdo, $role, $id, $_POST["code"] ?? "");
        if ($r === "ok") { vh_done($role, $id); }
        elseif ($r === "wrong") { $err = "That code is not right. Please try again."; }
        elseif ($r === "locked") { $err = "Too many wrong tries. Please ask for a new code."; }
        else { $err = "That code has expired. Please ask for a new one."; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Verify your email</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 28px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 8px; }
p { color: #5D4037; font-weight: 600; margin-bottom: 10px; text-align: center; }
input.code { width: 100%; padding: 14px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1.6rem; letter-spacing: 8px; text-align: center; background: #FFFDE7; }
button { width: 100%; margin-top: 12px; padding: 13px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.05rem; font-weight: 800; cursor: pointer; }
button.alt { background: #4361ee; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
a.back { display: block; text-align: center; margin-top: 14px; color: #6A1B9A; font-weight: 800; }
</style>
</head>
<body>
<div class="card">
<h1>Check your email</h1>
<p>We sent a 6-digit code to <b><?= e($masked) ?></b>. It works for 15 minutes. Check your Spam folder too.</p>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
<form method="post"><input type="text" name="code" class="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="000000" required><button type="submit">Verify</button></form>
<form method="post"><input type="hidden" name="action" value="resend"><button type="submit" class="alt">Send me a new code</button></form>
<a href="welcome.php" class="back">&larr; Back</a>
</div>
</body>
</html>
