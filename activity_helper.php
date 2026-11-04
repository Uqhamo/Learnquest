<?php
require_once __DIR__ . "/progress_helper.php";
function activity_names() { return ["addition" => "Addition", "subtraction" => "Subtraction", "multiplication" => "Multiplication", "division" => "Division", "mixed" => "All Operations", "factors" => "Factors", "fractions" => "Fractions", "squareroots" => "Square roots", "spelling" => "Spelling", "sentence" => "Sentences", "opposites" => "Opposites", "unscramble" => "Unscramble"]; }
function activity_rows($child_id, $limit = 3000) {
    try {
        $st = progress_db()->prepare("SELECT subject, skill, is_correct, response_time, created_at FROM attempts WHERE child_id = ? ORDER BY id DESC LIMIT " . (int) $limit);
        $st->execute([(string) $child_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
    $utc = new DateTimeZone("UTC"); $tz = new DateTimeZone("Africa/Johannesburg");
    foreach ($rows as $i => $r) { $d = new DateTime($r["created_at"], $utc); $d->setTimezone($tz); $rows[$i]["when"] = $d; }
    return $rows;
}
function activity_weeks($rows, $max = 6) {
    $weeks = [];
    foreach ($rows as $r) {
        $mon = (clone $r["when"])->modify("monday this week")->format("Y-m-d");
        if (!isset($weeks[$mon])) { $weeks[$mon] = ["start" => $mon, "total" => 0, "right" => 0, "days" => [], "skills" => []]; }
        $weeks[$mon]["total"]++; $weeks[$mon]["right"] += (int) $r["is_correct"];
        $weeks[$mon]["days"][$r["when"]->format("Y-m-d")] = true;
        $s = $r["skill"];
        if (!isset($weeks[$mon]["skills"][$s])) { $weeks[$mon]["skills"][$s] = ["total" => 0, "right" => 0]; }
        $weeks[$mon]["skills"][$s]["total"]++; $weeks[$mon]["skills"][$s]["right"] += (int) $r["is_correct"];
    }
    krsort($weeks);
    return array_slice($weeks, 0, $max, true);
}
function day_label($when, $today, $yest) { $d = $when->format("Y-m-d"); if ($d === $today) { return "Today " . $when->format("H:i"); } if ($d === $yest) { return "Yesterday " . $when->format("H:i"); } return $when->format("j M H:i"); }
