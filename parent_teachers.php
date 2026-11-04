<?php
session_start();
require_once __DIR__ . "/database.php";
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pid = (int) $_SESSION["parent_id"];
$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "remove") {
    $st = $pdo->prepare("UPDATE children SET teacher_id = NULL WHERE id = ? AND parent_id = ?");
    $st->execute([(int) ($_POST["child_id"] ?? 0), $pid]);
    $msg = $st->rowCount() ? "That teacher can no longer see this child." : "";
}
$st = $pdo->prepare("SELECT c.id, c.name, c.surname, t.name AS tname, t.surname AS tsurname, t.school FROM children c LEFT JOIN teachers t ON t.id = c.teacher_id WHERE c.parent_id = ? ORDER BY c.name");
$st->execute([$pid]);
$rows = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Teachers</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #F3F6FB; min-height: 100vh; padding: 28px 18px; color: #263238; }
.wrap { max-width: 600px; margin: auto; }
.back { display: inline-block; margin-bottom: 14px; color: #00695C; font-weight: 700; text-decoration: none; }
h1 { color: #00695C; margin-bottom: 6px; }
.sub { color: #607D8B; font-weight: 600; margin-bottom: 16px; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 14px; }
.card { background: #fff; border-radius: 14px; padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.card small { color: #78909C; font-weight: 600; display: block; }
button { background: #E53935; color: #fff; border: none; border-radius: 10px; padding: 9px 14px; font-weight: 700; cursor: pointer; }
</style>
</head>
<body>
<div class="wrap">
<a href="dashboard.php" class="back">&larr; Back to dashboard</a>
<h1>Teachers who can see my children</h1>
<p class="sub">A linked teacher sees the same progress report you see. They never see a PIN, a Player ID or your contact details. You can remove a teacher at any time.</p>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if (!$rows): ?><p>No children yet.</p><?php endif; ?>
<?php foreach ($rows as $r): ?>
<div class="card"><div><b><?= e($r["name"] . " " . $r["surname"]) ?></b><small><?= $r["tname"] ? "Teacher: " . e($r["tname"] . " " . $r["tsurname"]) . " (" . e($r["school"]) . ")" : "No teacher linked" ?></small></div><?php if ($r["tname"]): ?><form method="post" onsubmit="return confirm(String.fromCharCode(82,101,109,111,118,101,32,116,104,105,115,32,116,101,97,99,104,101,114,63));"><input type="hidden" name="action" value="remove"><input type="hidden" name="child_id" value="<?= (int) $r["id"] ?>"><button type="submit">Remove teacher</button></form><?php endif; ?></div>
<?php endforeach; ?>
</div>
</body>
</html>
