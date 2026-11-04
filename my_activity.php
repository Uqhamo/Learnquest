<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/activity_helper.php";
if ((empty($_SESSION["child_login"]) && empty($_SESSION["child_mode"])) || empty($_SESSION["child_id"])) { header("Location: login2.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$cid = (int) $_SESSION["child_id"];
$st = $pdo->prepare("SELECT name FROM children WHERE id = ?");
$st->execute([$cid]);
$me = $st->fetch();
if (!$me) { header("Location: child_logout.php"); exit; }
$names = activity_names();
$rows = activity_rows($cid);
$weeks = activity_weeks($rows, 6);
$tz = new DateTimeZone("Africa/Johannesburg");
$thisMon = (new DateTime("now", $tz))->modify("monday this week")->format("Y-m-d");
$today = (new DateTime("now", $tz))->format("Y-m-d");
$yest = (new DateTime("yesterday", $tz))->format("Y-m-d");
$cur = $weeks[$thisMon] ?? ["start" => $thisMon, "total" => 0, "right" => 0, "days" => [], "skills" => []];
$curPct = $cur["total"] ? (int) round($cur["right"] / $cur["total"] * 100) : 0;
$recent = array_slice($rows, 0, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - My Activity</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; 
background: #FFF176;
 min-height: 100vh; 
 padding: 20px 16px 40px; }
.wrap { max-width: 560px; margin: auto; }
.back { display: inline-block; 
margin-bottom: 12px; color: #6A1B9A; 
font-weight: 800; 
text-decoration: none; }
h1 { color: #FF4081; margin-bottom: 12px; }
h2 { color: #FF6D00; font-size: 1.1rem; margin: 18px 0 8px; }
.tiles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.tile { background: #fff; border: 3px solid #FF4081; border-radius: 14px; padding: 12px 6px; text-align: center; }
.tile b { display: block; font-size: 1.5rem; color: #2E7D32; }
.tile small { color: #8D6E63; font-weight: 700; }
.wk { background: #fff; border-radius: 12px; padding: 10px 12px; margin-bottom: 8px; color: #5D4037; }
.bar { height: 10px; background: #eee; border-radius: 6px; margin-top: 6px; overflow: hidden; }
.bar span { display: block; height: 100%; background: #00C853; }
.log { background: #fff; border-left: 6px solid #00C853; border-radius: 10px; padding: 8px 12px; margin-bottom: 6px; color: #5D4037; }
.log.miss { border-color: #E53935; }
.log small { display: block; color: #8D6E63; font-weight: 600; }
</style>
</head>
<body>
<div class="wrap">
<a href="mypage.php" class="back">&larr; Back to my page</a>
<h1>My activity</h1>
<p style="margin:6px 0 14px"><a href="download_report.php" style="background:#FF4081;color:#fff;padding:8px 14px;border-radius:10px;font-weight:800;text-decoration:none">Download my report</a></p>
<div class="tiles"><div class="tile"><b><?= (int) $cur["total"] ?></b><small>Questions this week</small></div><div class="tile"><b><?= $curPct ?>%</b><small>Right</small></div><div class="tile"><b><?= count($cur["days"]) ?>/7</b><small>Days played</small></div></div>
<h2>Week by week</h2>
<?php if (!$weeks): ?><p>Nothing yet. Play a game and your weeks will show here.</p><?php endif; ?>
<?php foreach ($weeks as $w): $p = $w["total"] ? (int) round($w["right"] / $w["total"] * 100) : 0; ?>
<div class="wk"><b>Week of <?= e(DateTime::createFromFormat("Y-m-d", $w["start"])->format("j M")) ?></b> &middot; <?= (int) $w["total"] ?> questions &middot; <?= $p ?>% right<div class="bar"><span style="width: <?= $p ?>%"></span></div></div>
<?php endforeach; ?>
<h2>What I did lately</h2>
<?php foreach ($recent as $r): ?><div class="log <?= $r["is_correct"] ? "good" : "miss" ?>"><?= $r["is_correct"] ? "&#10004;" : "&#10006;" ?> <b><?= e($names[$r["skill"]] ?? ucfirst($r["skill"])) ?></b><small><?= e(day_label($r["when"], $today, $yest)) ?></small></div><?php endforeach; ?>
</div>
</body>
</html>
