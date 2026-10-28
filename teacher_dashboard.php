<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/progress_helper.php";
if (empty($_SESSION["teacher_id"])) { header("Location: teacher_login.php"); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$tid = (int) $_SESSION["teacher_id"];
$st = $pdo->prepare("SELECT name, surname, school FROM teachers WHERE id = ?");
$st->execute([$tid]);
$teacher = $st->fetch();
if (!$teacher) { $_SESSION = []; header("Location: teacher_login.php"); exit; }
$st = $pdo->prepare("SELECT id, name, surname, age, grade, (parent_id IS NOT NULL) AS has_parent FROM children WHERE teacher_id = ? ORDER BY grade, name");
$st->execute([$tid]);
$learners = $st->fetchAll();
foreach ($learners as $i => $l) { $learners[$i]["report"] = get_progress_report((string) $l["id"]); }
$wq = $pdo->prepare("SELECT COUNT(*) FROM children WHERE pending_teacher_id = ? AND teacher_id IS NULL");
$wq->execute([$tid]);
$waiting = (int) $wq->fetchColumn();
$names = ["addition" => "Addition", "subtraction" => "Subtraction", "multiplication" => "Multiplication", "division" => "Division", "mixed" => "All Operations", "spelling" => "Spelling", "sentence" => "Sentences", "opposites" => "Opposites", "unscramble" => "Unscramble"];
$labels = ["mastered" => "Mastered", "proficient" => "Doing well", "good" => "Good", "progressing" => "Progressing", "processing" => "Taking their time", "struggling" => "Needs help", "neutral" => "New"];
function child_stats($report) { $t = 0; $c = 0; foreach ($report as $r) { $t += (int) $r["total"]; $c += $r["accuracy"] * $r["total"] / 100; } return [$t, $t ? (int) round($c / $t * 100) : 0]; }
$classQ = 0; $classC = 0; $attn = [];
foreach ($learners as $l) { foreach ($l["report"] as $r) { $classQ += (int) $r["total"]; $classC += $r["accuracy"] * $r["total"] / 100; if ($r["status"] === "struggling") { $attn[] = [$l["name"] . " " . $l["surname"], $r["skill"], $r["action"]]; } } }
$classAcc = $classQ ? (int) round($classC / $classQ * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Teacher Dashboard</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #F3F6FB; min-height: 100vh; color: #263238; }
header { background: linear-gradient(90deg, #4361ee, #3a86ff); padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; box-shadow: 0 3px 10px rgba(0,0,0,.15); }
header h2 { color: #fff; font-size: 1.4rem; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 20px; background: rgba(255,255,255,.2); }
main { max-width: 860px; margin: 0 auto; padding: 28px 18px 60px; }
.hello { font-size: 1.7rem; color: #2b3a8f; }
.sub { color: #607D8B; font-weight: 600; margin: 4px 0 18px; }
.banner { display: block; background: #FFF8E1; border: 2px solid #FFB300; color: #5D4037; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-weight: 700; text-decoration: none; }
.tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
.tile { background: #fff; border-radius: 14px; padding: 14px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.tile b { display: block; font-size: 1.6rem; color: #2b3a8f; }
.tile small { color: #78909C; font-weight: 700; }
.attn { background: #FFEBEE; border-left: 6px solid #E53935; border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; }
.attn p { margin-top: 6px; font-size: .9rem; }
.empty { background: #fff; border-radius: 14px; padding: 18px; margin-bottom: 18px; }
.learner { background: #fff; border-radius: 16px; padding: 18px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.top > div { flex: 1; min-width: 140px; }
.av { width: 46px; height: 46px; border-radius: 50%; background: #4361ee; color: #fff; font-weight: 800; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; }
.learner h2 { font-size: 1.15rem; }
small { color: #78909C; font-weight: 600; }
.shared { background: #E8EAF6; color: #3949AB; border-radius: 12px; padding: 3px 10px; font-size: .75rem; font-weight: 800; }
.stats { display: flex; gap: 18px; margin: 12px 0; color: #546E7A; }
.stats b { color: #2b3a8f; font-size: 1.2rem; }
.none { color: #90A4AE; }
.skill { --c: #9E9E9E; border-left: 6px solid var(--c); background: #FAFBFD; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; }
.skill .row { display: flex; justify-content: space-between; align-items: center; }
.chip { background: var(--c); color: #fff; border-radius: 12px; padding: 2px 10px; font-size: .75rem; font-weight: 800; }
.bar { height: 8px; background: #E0E6EC; border-radius: 5px; margin: 6px 0; overflow: hidden; }
.bar span { display: block; height: 100%; background: var(--c); }
.skill p { margin-top: 5px; font-size: .9rem; }
.skill .tip { color: #2b3a8f; font-weight: 600; }
.s-mastered, .s-proficient { --c: #00A152; }
.s-good { --c: #7CB342; }
.s-progressing { --c: #039BE5; }
.s-processing { --c: #FB8C00; }
.s-struggling { --c: #E53935; }
@media (max-width: 560px) { .tiles { grid-template-columns: 1fr 1fr; } }
</style>
</head>
<body>
<header><h2>LearnQuest Teacher</h2><nav><a href="teacher_link.php">Learners<?php if ($waiting): ?> (<?= $waiting ?>)<?php endif; ?></a><a href="logout.php">Logout</a></nav></header>
<main>
<h1 class="hello">Hello, <?= e($teacher["name"]) ?></h1>
<p class="sub"><?= e($teacher["school"]) ?> &middot; <?= count($learners) ?> learner<?= count($learners) === 1 ? "" : "s" ?> linked to you</p>
<?php if ($waiting): ?><a href="teacher_link.php" class="banner">&#128276; <?= (int) $waiting ?> learner<?= $waiting > 1 ? "s want" : " wants" ?> to link to you. Review &rarr;</a><?php endif; ?>
<div class="tiles"><div class="tile"><b><?= count($learners) ?></b><small>Learners</small></div><div class="tile"><b><?= (int) $classQ ?></b><small>Questions</small></div><div class="tile"><b><?= (int) $classAcc ?>%</b><small>Class accuracy</small></div><div class="tile"><b><?= count($attn) ?></b><small>Need attention</small></div></div>
<?php if ($attn): ?><div class="attn"><b>Needs attention</b><?php foreach ($attn as $a): ?><p><?= e($a[0]) ?> &middot; <?= e($names[$a[1]] ?? ucfirst($a[1])) ?>: <?= e($a[2]) ?></p><?php endforeach; ?></div><?php endif; ?>
<?php if (!$learners): ?><div class="empty">No learners linked yet. A learner can add you from their own page, or you can link one with their Player ID and PIN.</div><?php endif; ?>
<?php foreach ($learners as $l): list($tq, $acc) = child_stats($l["report"]); ?>
<section class="learner">
<div class="top"><span class="av"><?= e(strtoupper(substr($l["name"], 0, 1))) ?></span><div><h2><?= e($l["name"] . " " . $l["surname"]) ?></h2><small>Grade <?= (int) $l["grade"] ?> &middot; Age <?= (int) $l["age"] ?></small></div><?php if ($l["has_parent"]): ?><span class="shared">Parent also linked</span><?php endif; ?></div>
<div class="stats"><span><b><?= (int) $tq ?></b> questions</span><span><b><?= (int) $acc ?>%</b> correct</span></div>
<?php if (!$l["report"]): ?><p class="none">No results yet. Once they play, the report appears here.</p><?php endif; ?>
<?php foreach ($l["report"] as $r): ?>
<div class="skill s-<?= e($r["status"]) ?>"><div class="row"><b><?= e($names[$r["skill"]] ?? ucfirst($r["skill"])) ?></b><span class="chip"><?= e($labels[$r["status"]] ?? "New") ?></span></div><div class="bar"><span style="width: <?= (int) $r["accuracy"] ?>%"></span></div><small><?= (int) $r["accuracy"] ?>% correct in <?= (int) $r["total"] ?> questions</small><p><?= e($r["summary"]) ?></p><p class="tip">Suggestion: <?= e($r["action"]) ?></p></div>
<?php endforeach; ?>
</section>
<?php endforeach; ?>
</main>
</body>
</html>
