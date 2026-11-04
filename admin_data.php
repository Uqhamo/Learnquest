<?php
require_once __DIR__ . "/activity_helper.php";
function admin_datasets($pdo) {
    $sql = ["parents" => "SELECT id, name, surname, email, email_verified, disabled, created_at FROM parents ORDER BY id", "teachers" => "SELECT id, name, surname, school, email, email_verified, approved, disabled, created_at FROM teachers ORDER BY id", "children" => "SELECT id, name, surname, grade, age, player_code, parent_id, teacher_id, disabled, created_at FROM children ORDER BY id", "log" => "SELECT l.created_at, a.name, l.action, l.target FROM admin_log l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC"];
    $heads = ["parents" => ["ID", "Name", "Surname", "Email", "Email verified", "Locked", "Joined"], "teachers" => ["ID", "Name", "Surname", "School", "Email", "Email verified", "Approved", "Locked", "Joined"], "children" => ["ID", "Name", "Surname", "Grade", "Age", "Player ID", "Parent ID", "Teacher ID", "Locked", "Joined"], "log" => ["When", "Admin", "Action", "Target"]];
    $titles = ["progress" => "Learner progress", "parents" => "Parents", "teachers" => "Teachers", "children" => "Children", "log" => "Admin activity"];
    $names = activity_names(); $prog = [];
    foreach ($pdo->query("SELECT id, name, surname, grade FROM children ORDER BY id")->fetchAll() as $k) { foreach (activity_weeks(activity_rows($k["id"]), 12) as $w) { foreach ($w["skills"] as $s => $v) { $prog[] = [$k["name"] . " " . $k["surname"], $k["grade"], $w["start"], $names[$s] ?? ucfirst($s), $v["total"], $v["right"], ($v["total"] ? round($v["right"] / $v["total"] * 100) : 0) . "%"]; } } }
    $out = ["progress" => [$titles["progress"], ["Learner", "Grade", "Week starting", "Activity", "Questions", "Right", "Percent right"], $prog]];
    foreach ($sql as $k => $s) { $out[$k] = [$titles[$k], $heads[$k], $pdo->query($s)->fetchAll(PDO::FETCH_NUM)]; }
    return $out;
}
