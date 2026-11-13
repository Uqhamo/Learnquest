<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/activity_helper.php";
function csv_cell($v) { $v = (string) $v; if ($v !== "" && strpos("=+-@", $v[0]) !== false) { $v = "'" . $v; } return $v; }
function allowed_children($pdo) {
    if (!empty($_SESSION["parent_id"]) && empty($_SESSION["child_mode"])) { $st = $pdo->prepare("SELECT id, name, surname, grade FROM children WHERE parent_id = ? ORDER BY name"); $st->execute([$_SESSION["parent_id"]]); return [$st->fetchAll(), "parent"]; }
    if (!empty($_SESSION["teacher_id"])) { $st = $pdo->prepare("SELECT id, name, surname, grade FROM children WHERE teacher_id = ? ORDER BY grade, name"); $st->execute([$_SESSION["teacher_id"]]); return [$st->fetchAll(), "teacher"]; }
    if ((!empty($_SESSION["child_login"]) || !empty($_SESSION["child_mode"])) && !empty($_SESSION["child_id"])) { $st = $pdo->prepare("SELECT id, name, surname, grade FROM children WHERE id = ?"); $st->execute([$_SESSION["child_id"]]); return [$st->fetchAll(), "child"]; }
    return [[], ""];
}
list($kids, $role) = allowed_children($pdo);
if (!$role) { header("Location: login2.php"); exit; }
$only = isset($_GET["child"]) ? (int) $_GET["child"] : 0;
if ($only) { $kids = array_values(array_filter($kids, function ($k) use ($only) { return (int) $k["id"] === $only; })); }
if (!$kids) { http_response_code(404); echo "No report available."; exit; }
$format = in_array($_GET["format"] ?? "csv", ["csv", "print", "doc"], true) ? $_GET["format"] : "csv";
$names = activity_names();
$rows = [];
foreach ($kids as $k) { $all = activity_rows($k["id"]); $weeks = activity_weeks($all, 12); foreach ($weeks as $w) { foreach ($w["skills"] as $s => $v) { $rows[] = [$k["name"] . " " . $k["surname"], $k["grade"], $w["start"], $names[$s] ?? ucfirst($s), $v["total"], $v["right"], $v["total"] ? round($v["right"] / $v["total"] * 100) : 0]; } } }
$who = $role === "parent" ? "for my children" : ($role === "teacher" ? "for my learners" : "for me");
$labels = ["mastered" => "Mastered", "proficient" => "Doing well", "good" => "Good", "progressing" => "Progressing", "processing" => "Taking their time", "struggling" => "Needs help", "neutral" => "New"];
$prog = []; $attn = [];
foreach ($kids as $k) { try { $rep = get_progress_report((string) $k["id"]); } catch (Exception $ex) { $rep = []; } foreach ($rep as $r) { $nm = $k["name"] . " " . $k["surname"]; $sk = $names[$r["skill"]] ?? ucfirst($r["skill"]); $prog[] = [$nm, $k["grade"], $sk, (int) $r["total"], (int) $r["accuracy"], $labels[$r["status"]] ?? "New", $r["summary"] ?? "", $r["action"] ?? ""]; if ($r["status"] === "struggling") { $attn[] = [$nm, $sk, (int) $r["accuracy"], $r["action"] ?? ""]; } } }
if ($format === "csv") {
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"learnquest-report-" . date("Y-m-d") . ".csv\"");
    $out = fopen("php://output", "w"); fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ["Report " . $who . " - " . date("Y-m-d")]); fputcsv($out, []);
    fputcsv($out, ["NEEDS ATTENTION"]); fputcsv($out, ["Learner", "Activity", "Percent right", "Suggestion"]);
    foreach ($attn as $r) { fputcsv($out, array_map("csv_cell", $r)); } if (!$attn) { fputcsv($out, ["Nothing needs attention"]); } fputcsv($out, []);
    fputcsv($out, ["PROGRESS BY ACTIVITY"]); fputcsv($out, ["Learner", "Grade", "Activity", "Questions", "Percent right", "Status", "Summary", "Suggestion"]);
    foreach ($prog as $r) { fputcsv($out, array_map("csv_cell", $r)); } fputcsv($out, []);
    fputcsv($out, ["WEEKLY PROGRESS"]);
    fputcsv($out, ["Learner", "Grade", "Week starting", "Activity", "Questions", "Right", "Percent right"]);
    foreach ($rows as $r) { fputcsv($out, array_map("csv_cell", $r)); }
    fclose($out); exit;
}
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
if ($format === "doc") { header("Content-Type: application/msword; charset=UTF-8"); header("Content-Disposition: attachment; filename=\"learnquest-report-" . date("Y-m-d") . ".doc\""); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LearnQuest report</title>
<style>
body { font-family: Arial, sans-serif; color: #222; padding: 24px; }
h1 { font-size: 1.4rem; margin-bottom: 2px; }
.sub { color: #666; margin-bottom: 16px; font-size: .9rem; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
th, td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; }
th { background: #eee; }
.bar { margin-bottom: 14px; }
.bar button { padding: 8px 14px; font-weight: 700; cursor: pointer; }
@media print { .bar { display: none; } }
</style>
</head>
<body>
<?php if ($format === "print"): ?><div class="bar"><button onclick="window.print()">Print or save as PDF</button></div><?php endif; ?>
<h1>LearnQuest progress report <?= e($who) ?></h1>
<p class="sub">Created <?= e(date("j F Y H:i")) ?>. Each week runs Monday to Sunday.</p>
<h2>Needs attention</h2><?php if (!$attn): ?><p>Nothing needs attention right now.</p><?php else: ?><table><tr><th>Learner</th><th>Activity</th><th>Percent right</th><th>Suggestion</th></tr><?php foreach ($attn as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= e($r[1]) ?></td><td><?= (int) $r[2] ?>%</td><td><?= e($r[3]) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
<h2>Progress by activity</h2><table><tr><th>Learner</th><th>Grade</th><th>Activity</th><th>Questions</th><th>Percent right</th><th>Status</th><th>Summary</th><th>Suggestion</th></tr><?php foreach ($prog as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= (int) $r[1] ?></td><td><?= e($r[2]) ?></td><td><?= (int) $r[3] ?></td><td><?= (int) $r[4] ?>%</td><td><?= e($r[5]) ?></td><td><?= e($r[6]) ?></td><td><?= e($r[7]) ?></td></tr><?php endforeach; ?></table><?php if (!$prog): ?><p>No activity yet.</p><?php endif; ?>
<h2>Weekly progress</h2>
<table><tr><th>Learner</th><th>Grade</th><th>Week starting</th><th>Activity</th><th>Questions</th><th>Right</th><th>Percent right</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= (int) $r[1] ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td><td><?= (int) $r[4] ?></td><td><?= (int) $r[5] ?></td><td><?= (int) $r[6] ?>%</td></tr><?php endforeach; ?>
</table>
<?php if (!$rows): ?><p>No activity yet.</p><?php endif; ?>
</body>
</html>
