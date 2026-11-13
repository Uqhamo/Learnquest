<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
require_once __DIR__ . "/activity_helper.php";
$what = $_GET["what"] ?? "";
$titles = ["progress" => "Learner progress", "parents" => "Parents", "teachers" => "Teachers", "children" => "Children", "log" => "Admin activity"];
if (!isset($titles[$what])) { http_response_code(404); echo "Unknown report."; exit; }
$sql = ["parents" => "SELECT id, name, surname, email, email_verified, disabled, created_at FROM parents ORDER BY id", "teachers" => "SELECT id, name, surname, school, email, email_verified, approved, disabled, created_at FROM teachers ORDER BY id", "children" => "SELECT id, name, surname, grade, age, player_code, parent_id, teacher_id, disabled, created_at FROM children ORDER BY id", "log" => "SELECT l.created_at, a.name, l.action, l.target FROM admin_log l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC"];
$heads = ["parents" => ["ID", "Name", "Surname", "Email", "Email verified", "Locked", "Joined"], "teachers" => ["ID", "Name", "Surname", "School", "Email", "Email verified", "Approved", "Locked", "Joined"], "children" => ["ID", "Name", "Surname", "Grade", "Age", "Player ID", "Parent ID", "Teacher ID", "Locked", "Joined"], "log" => ["When", "Admin", "Action", "Target"], "progress" => ["Learner", "Grade", "Week starting", "Activity", "Questions", "Right", "Percent right"]];
$rows = [];
if ($what === "progress") { $names = activity_names(); foreach ($pdo->query("SELECT id, name, surname, grade FROM children ORDER BY id")->fetchAll() as $k) { foreach (activity_weeks(activity_rows($k["id"]), 12) as $w) { foreach ($w["skills"] as $s => $v) { $rows[] = [$k["name"] . " " . $k["surname"], $k["grade"], $w["start"], $names[$s] ?? ucfirst($s), $v["total"], $v["right"], ($v["total"] ? round($v["right"] / $v["total"] * 100) : 0) . "%"]; } } } }
else { $rows = $pdo->query($sql[$what])->fetchAll(PDO::FETCH_NUM); }
admin_log($pdo, "print report", $what);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LearnQuest - <?= e($titles[$what]) ?></title>
<style>
body { font-family: Arial, sans-serif; color: #222; padding: 24px; }
h1 { font-size: 1.3rem; margin-bottom: 2px; }
.sub { color: #666; font-size: .85rem; margin-bottom: 14px; }
table { width: 100%; border-collapse: collapse; font-size: .78rem; }
th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; }
th { background: #eee; }
tr { page-break-inside: avoid; }
.bar { margin-bottom: 14px; }
.bar button { padding: 8px 14px; font-weight: 700; cursor: pointer; }
@media print { .bar { display: none; } @page { margin: 12mm; } }
</style>
</head>
<body>
<div class="bar"><button onclick="window.print()">Print or save as PDF</button></div>
<h1>LearnQuest - <?= e($titles[$what]) ?></h1>
<p class="sub">Created <?= e(date("j F Y H:i")) ?> by <?= e($admin["name"]) ?>. Confidential: contains personal information. No passwords or PINs.</p>
<table><tr><?php foreach ($heads[$what] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
<?php foreach ($rows as $r): ?><tr><?php foreach ($r as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></table>
<?php if (!$rows): ?><p>No data yet.</p><?php endif; ?>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
