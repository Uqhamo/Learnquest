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
if (!isset($_GET["format"])) {
    $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); };
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Download a report</title><style>body{font-family:"Segoe UI",sans-serif;background:#F3F6FB;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}.card{background:#fff;border-radius:16px;padding:26px;width:100%;max-width:420px;box-shadow:0 2px 10px rgba(0,0,0,.12)}h1{font-size:1.3rem;margin-bottom:12px;color:#00695C}label{display:block;font-weight:700;margin:12px 0 4px}select{width:100%;padding:11px;border:2px solid #CFD8DC;border-radius:10px;font-size:1rem}button{width:100%;margin-top:16px;padding:13px;background:#00897B;color:#fff;border:none;border-radius:12px;font-size:1.05rem;font-weight:800;cursor:pointer}a{display:block;text-align:center;margin-top:12px;color:#546E7A;font-weight:700}</style></head><body><div class="card"><h1>Download a report</h1><form method="get">';
    if (count($kids) > 1) { echo '<label>Which learner?</label><select name="child"><option value="0">All learners</option>'; foreach ($kids as $k) { echo '<option value="' . (int) $k["id"] . '">' . $h($k["name"] . " " . $k["surname"]) . '</option>'; } echo '</select>'; }
    echo '<label>File type</label><select name="format"><option value="csv">CSV (opens in Excel)</option><option value="print">PDF (print and save)</option></select><button type="submit">Download</button></form><a href="javascript:history.back()">Back</a></div></body></html>';
    exit;
}
$format = ($_GET["format"] ?? "csv") === "print" ? "print" : "csv";
$names = activity_names();
$rows = [];
foreach ($kids as $k) { $all = activity_rows($k["id"]); $weeks = activity_weeks($all, 12); foreach ($weeks as $w) { foreach ($w["skills"] as $s => $v) { $rows[] = [$k["name"] . " " . $k["surname"], $k["grade"], $w["start"], $names[$s] ?? ucfirst($s), $v["total"], $v["right"], $v["total"] ? round($v["right"] / $v["total"] * 100) : 0]; } } }
if ($format === "csv") {
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"learnquest-report-" . date("Y-m-d") . ".csv\"");
    $out = fopen("php://output", "w"); fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ["Learner", "Grade", "Week starting", "Activity", "Questions", "Right", "Percent right"]);
    foreach ($rows as $r) { fputcsv($out, array_map("csv_cell", $r)); }
    fclose($out); exit;
}
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
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
<div class="bar"><button onclick="window.print()">Print or save as PDF</button></div>
<h1>LearnQuest progress report</h1>
<p class="sub">Created <?= e(date("j F Y H:i")) ?>. Each week runs Monday to Sunday.</p>
<table><tr><th>Learner</th><th>Grade</th><th>Week starting</th><th>Activity</th><th>Questions</th><th>Right</th><th>Percent right</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r[0]) ?></td><td><?= (int) $r[1] ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td><td><?= (int) $r[4] ?></td><td><?= (int) $r[5] ?></td><td><?= (int) $r[6] ?>%</td></tr><?php endforeach; ?>
</table>
<?php if (!$rows): ?><p>No activity yet.</p><?php endif; ?>
</body>
</html>
