<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/activity_helper.php";
if (!empty($_SESSION["child_mode"])) { header("Location: mypage.php"); exit; }
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pid = (int) $_SESSION["parent_id"];
$st = $pdo->prepare("SELECT id, name, surname, grade FROM children WHERE parent_id = ? ORDER BY name");
$st->execute([$pid]);
$kids = $st->fetchAll();
$names = activity_names();
$tz = new DateTimeZone("Africa/Johannesburg");
$thisMon = (new DateTime("now", $tz))->modify("monday this week")->format("Y-m-d");
$today = (new DateTime("now", $tz))->format("Y-m-d");
$yest = (new DateTime("yesterday", $tz))->format("Y-m-d");
foreach ($kids as $i => $k) { $rows = activity_rows($k["id"]); $kids[$i]["weeks"] = activity_weeks($rows, 6); $kids[$i]["recent"] = array_slice($rows, 0, 12); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Weekly progress</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #F3F6FB; color: #263238; padding: 24px 16px 50px; }
.wrap { max-width: 760px; margin: auto; }
.back { display: inline-block; margin-bottom: 12px; color: #00695C; font-weight: 700; text-decoration: none; }
h1 { color: #00695C; margin-bottom: 4px; }
.sub { color: #607D8B; font-weight: 600; margin-bottom: 16px; }
.kid { background: #fff; border-radius: 16px; padding: 18px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.kid h2 { color: #263238; font-size: 1.2rem; margin-bottom: 10px; }
.kid h2 small { color: #78909C; font-weight: 600; font-size: .8rem; }
.kid h3 { color: #00695C; font-size: 1rem; margin: 16px 0 6px; }
.tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 12px; }
.tile { background: #F3F6FB; border-radius: 12px; padding: 10px; text-align: center; }
.tile b { display: block; font-size: 1.4rem; color: #00695C; }
.tile small { color: #78909C; font-weight: 700; }
table { width: 100%; border-collapse: collapse; font-size: .9rem; }
th { text-align: left; color: #78909C; font-size: .75rem; text-transform: uppercase; padding: 6px 8px; }
td { padding: 7px 8px; border-top: 1px solid #ECEFF1; }
tr.miss td { color: #C62828; }
tr.good td:nth-child(3) { color: #2E7D32; font-weight: 700; }
.skill { display: flex; justify-content: space-between; gap: 8px; padding: 6px 8px; border-top: 1px solid #ECEFF1; }
.none, .empty { color: #90A4AE; padding: 8px 0; }
@media (max-width: 480px) { td, th { padding: 6px 4px; font-size: .8rem; } }
</style>
</head>
<body>
<div class="wrap">
<a href="parent_dashboard.php" class="back">&larr; Back to dashboard</a>
<h1>Weekly progress and activity</h1><p style="margin:6px 0 14px"><p style="margin:6px 0 14px"><a href="download_choose.php" style="background:#00897B;color:#fff;padding:9px 16px;border-radius:10px;font-weight:800;text-decoration:none;display:inline-block">Download report for my children</a></p></p>
<p class="sub">Each week runs Monday to Sunday. The activity list shows the latest answers and whether they were right.</p>
<?php if (!$kids): ?><div class="empty">No children linked yet.</div><?php endif; ?>
<?php foreach ($kids as $k): $wl = array_values($k["weeks"]); $cur = $k["weeks"][$thisMon] ?? null; $cp = ($cur && $cur["total"]) ? (int) round($cur["right"] / $cur["total"] * 100) : 0; ?>
<section class="kid">
<h2><?= e($k["name"] . " " . $k["surname"]) ?> <small>Grade <?= (int) $k["grade"] ?></small></h2>
<?php if (!$wl): ?><p class="none">No activity yet. Once they play, it shows here.</p><?php else: ?>
<div class="tiles"><div class="tile"><b><?= $cur ? (int) $cur["total"] : 0 ?></b><small>Questions this week</small></div><div class="tile"><b><?= $cp ?>%</b><small>Right this week</small></div><div class="tile"><b><?= $cur ? count($cur["days"]) : 0 ?>/7</b><small>Days played</small></div></div>
<table><tr><th>Week of</th><th>Questions</th><th>Right</th><th>Days</th><th>Trend</th></tr>
<?php foreach ($wl as $i => $w): $p = $w["total"] ? (int) round($w["right"] / $w["total"] * 100) : 0; $prev = $wl[$i + 1] ?? null; $pp = ($prev && $prev["total"]) ? (int) round($prev["right"] / $prev["total"] * 100) : null; ?>
<tr><td><?= e(DateTime::createFromFormat("Y-m-d", $w["start"])->format("j M")) ?></td><td><?= (int) $w["total"] ?></td><td><?= $p ?>%</td><td><?= count($w["days"]) ?>/7</td><td><?= $pp === null ? "&ndash;" : (($p > $pp) ? "&#9650; +" . ($p - $pp) : (($p < $pp) ? "&#9660; " . ($p - $pp) : "&ndash;")) ?></td></tr>
<?php endforeach; ?></table>
<?php if ($cur): ?><h3>This week by skill</h3><?php foreach ($cur["skills"] as $s => $v): $sp = $v["total"] ? (int) round($v["right"] / $v["total"] * 100) : 0; ?><div class="skill"><span><?= e($names[$s] ?? ucfirst($s)) ?></span><span><?= (int) $v["right"] ?> of <?= (int) $v["total"] ?> right (<?= $sp ?>%)</span></div><?php endforeach; ?><?php endif; ?>
<h3>Recent activity</h3>
<table><tr><th>When</th><th>Activity</th><th>Result</th><th>Time</th></tr>
<?php foreach ($k["recent"] as $r): ?><tr class="<?= $r["is_correct"] ? "good" : "miss" ?>"><td><?= e(day_label($r["when"], $today, $yest)) ?></td><td><?= e($names[$r["skill"]] ?? ucfirst($r["skill"])) ?></td><td><?= $r["is_correct"] ? "&#10004; Right" : "&#10006; Wrong" ?></td><td><?= (int) round((float) $r["response_time"]) ?> s</td></tr><?php endforeach; ?></table>
<?php endif; ?>
</section>
<?php endforeach; ?>
</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
