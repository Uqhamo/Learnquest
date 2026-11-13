<?php
session_start();
require_once __DIR__ . "/database.php";
if (!empty($_SESSION["parent_id"]) && empty($_SESSION["child_mode"])) { header("Location: parent_dashboard.php"); exit; }
if ((empty($_SESSION["child_login"]) && empty($_SESSION["child_mode"])) || empty($_SESSION["child_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$cid = (int) $_SESSION["child_id"];
$msg = "";
$st = $pdo->prepare("SELECT c.parent_id, c.pending_parent_id, c.teacher_id, c.pending_teacher_id, p.name AS pname, t.name AS tname, t.surname AS tsurname FROM children c LEFT JOIN parents p ON p.id = c.parent_id LEFT JOIN teachers t ON t.id = c.teacher_id WHERE c.id = ?");
$st->execute([$cid]);
$me = $st->fetch();
if (!$me) { header("Location: child_logout.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $email = strtolower(trim($_POST["email"] ?? ""));
    if ($action === "parent" && !$me["parent_id"] && $email !== "") {
        $q = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
        $q->execute([$email]);
        if ($r = $q->fetch()) { $pdo->prepare("UPDATE children SET pending_parent_id = ? WHERE id = ? AND parent_id IS NULL")->execute([$r["id"], $cid]); }
        $msg = "If a parent account uses that email, your request was sent. Your parent must approve it.";
    } elseif ($action === "teacher" && !$me["teacher_id"] && $email !== "") {
        $q = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
        $q->execute([$email]);
        if ($r = $q->fetch()) { $pdo->prepare("UPDATE children SET pending_teacher_id = ? WHERE id = ? AND teacher_id IS NULL")->execute([$r["id"], $cid]); }
        $msg = "If a teacher account uses that email, your request was sent. Your teacher must approve it.";
    } elseif ($action === "cancel_parent") {
        $pdo->prepare("UPDATE children SET pending_parent_id = NULL WHERE id = ?")->execute([$cid]);
        $msg = "Your parent request was cancelled.";
    } elseif ($action === "cancel_teacher") {
        $pdo->prepare("UPDATE children SET pending_teacher_id = NULL WHERE id = ?")->execute([$cid]);
        $msg = "Your teacher request was cancelled.";
    }
    $st->closeCursor();
    $st->execute([$cid]);
    $me = $st->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - My grown-ups</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 26px; width: 100%; max-width: 440px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 10px; }
h2 { color: #FF6D00; font-size: 1.1rem; margin: 14px 0 6px; }
p { color: #5D4037; font-weight: 600; margin-bottom: 8px; }
label { display: block; font-weight: 700; color: #5D4037; margin: 8px 0 4px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button { width: 100%; margin-top: 12px; padding: 12px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1rem; font-weight: 800; cursor: pointer; }
button.no { background: #9E9E9E; }
button.teach { background: #4361ee; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 8px; }
.wait { background: #FFF9C4; border: 3px solid #FFD600; color: #5D4037; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 8px; }
a.back { display: block; text-align: center; margin-top: 16px; color: #6A1B9A; font-weight: 800; }
</style>
</head>
<body>
<div class="card">
<h1>My grown-ups</h1>
<p>Link your parent or your teacher so they can see how you are doing. They must say yes first.</p>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<h2>&#128106; My parent</h2>
<?php if ($me["parent_id"]): ?><div class="ok">You are linked to <?= e($me["pname"]) ?>.</div>
<?php elseif ($me["pending_parent_id"]): ?><div class="wait">Your request is waiting. Ask your parent to open Link child and press Approve.</div><form method="post"><input type="hidden" name="action" value="cancel_parent"><button type="submit" class="no">Cancel parent request</button></form>
<?php else: ?><form method="post"><input type="hidden" name="action" value="parent"><label>Parent email</label><input type="email" name="email" maxlength="100" required><button type="submit">Ask my parent</button></form><?php endif; ?>
<h2>&#127979; My teacher</h2>
<?php if ($me["teacher_id"]): ?><div class="ok">You are linked to <?= e($me["tname"] . " " . $me["tsurname"]) ?>.</div>
<?php elseif ($me["pending_teacher_id"]): ?><div class="wait">Your request is waiting. Ask your teacher to open Learners and press Approve.</div><form method="post"><input type="hidden" name="action" value="cancel_teacher"><button type="submit" class="no">Cancel teacher request</button></form>
<?php else: ?><form method="post"><input type="hidden" name="action" value="teacher"><label>Teacher email</label><input type="email" name="email" maxlength="100" required><button type="submit" class="teach">Ask my teacher</button></form><?php endif; ?>
<a href="mypage.php" class="back">&larr; Back to my page</a>
</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
