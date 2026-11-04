<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
require_once __DIR__ . "/activity_helper.php";
function cc($v) { $v = (string) $v; if ($v !== "" && strpos("=+-@", $v[0]) !== false) { $v = "\x27" . $v; } return $v; }
$what = $_GET["what"] ?? "";
$sets = ["parents" => ["SELECT id, name, surname, email, email_verified, disabled, created_at FROM parents ORDER BY id", ["ID", "Name", "Surname", "Email", "Email verified", "Locked", "Joined"]], "teachers" => ["SELECT id, name, surname, school, email, email_verified, approved, disabled, created_at FROM teachers ORDER BY id", ["ID", "Name", "Surname", "School", "Email", "Email verified", "Approved", "Locked", "Joined"]], "children" => ["SELECT id, name, surname, grade, age, player_code, parent_id, teacher_id, disabled, created_at FROM children ORDER BY id", ["ID", "Name", "Surname", "Grade", "Age", "Player ID", "Parent ID", "Teacher ID", "Locked", "Joined"]], "log" => ["SELECT l.created_at, a.name, l.action, l.target FROM admin_log l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC", ["When", "Admin", "Action", "Target"]]];
if (!isset($sets[$what]) && $what !== "progress") { http_response_code(404); echo "Unknown report."; exit; }
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"learnquest-" . $what . "-" . date("Y-m-d") . ".csv\"");
$out = fopen("php://output", "w"); fwrite($out, "\xEF\xBB\xBF");
if ($what === "progress") {
    fputcsv($out, ["Learner", "Grade", "Week starting", "Activity", "Questions", "Right", "Percent right"]);
    $names = activity_names();
    foreach ($pdo->query("SELECT id, name, surname, grade FROM children ORDER BY id")->fetchAll() as $k) { foreach (activity_weeks(activity_rows($k["id"]), 12) as $w) { foreach ($w["skills"] as $s => $v) { fputcsv($out, array_map("cc", [$k["name"] . " " . $k["surname"], $k["grade"], $w["start"], $names[$s] ?? ucfirst($s), $v["total"], $v["right"], $v["total"] ? round($v["right"] / $v["total"] * 100) : 0])); } } }
} else {
    fputcsv($out, $sets[$what][1]);
    foreach ($pdo->query($sets[$what][0])->fetchAll(PDO::FETCH_NUM) as $r) { fputcsv($out, array_map("cc", $r)); }
}
fclose($out);
admin_log($pdo, "download report", $what);
exit;
