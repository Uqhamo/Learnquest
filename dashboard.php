<?php
session_start();
if (!empty($_SESSION["guest"]) && empty($_SESSION["parent_id"])) { header("Location: play.php"); exit; }
if (!empty($_SESSION["child_login"]) && empty($_SESSION["parent_id"])) { header("Location: mypage.php"); exit; }
if (!empty($_SESSION["child_mode"])) { header("Location: mypage.php"); exit; }
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/progress_helper.php";
if (file_exists(__DIR__ . "/quiz_helper.php")) { require_once __DIR__ . "/quiz_helper.php"; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pid = (int) $_SESSION["parent_id"];
$st = $pdo->prepare("SELECT name FROM parents WHERE id = ?");
$st->execute([$pid]);
$parent = $st->fetch();
$family = get_family_report($pdo, $pid);
$wq = $pdo->prepare("SELECT COUNT(*) FROM children WHERE pending_parent_id = ? AND parent_id IS NULL");
$wq->execute([$pid]);
$waiting = (int) $wq->fetchColumn();
$names = ["addition" => "Addition", "subtraction" => "Subtraction", "multiplication" => "Multiplication", "division" => "Division", "mixed" => "All Operations", "spelling" => "Spelling", "sentence" => "Sentences", "opposites" => "Opposites", "unscramble" => "Unscramble"];
$labels = ["mastered" => "Mastered", "proficient" => "Doing well", "good" => "Good", "progressing" => "Progressing", "processing" => "Taking their time", "struggling" => "Needs help", "neutral" => "New"];
function child_stats($report) { $t = 0; $c = 0; foreach ($report as $r) { $t += (int) $r["total"]; $c += $r["accuracy"] * $r["total"] / 100; } return [$t, $t ? (int) round($c / $t * 100) : 0]; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Parent Dashboard</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #F3F6FB; min-height: 100vh; color: #263238; }
header { background: linear-gradient(90deg, #00897B, #00ACC1); padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; box-shadow: 0 3px 10px rgba(0,0,0,.15); }
header h2 { color: #fff; font-size: 1.4rem; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 20px; background: rgba(255,255,255,.2); }
nav a:hover { background: rgba(255,255,255,.38); }
main { max-width: 820px; margin: 0 auto; padding: 28px 18px 60px; }
.hello { font-size: 1.7rem; color: #00695C; }
.sub { color: #607D8B; font-weight: 600; margin: 4px 0 18px; }
.banner { display: block; background: #FFF8E1; border: 2px solid #FFB300; color: #5D4037; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; font-weight: 700; text-decoration: none; }
.attn { background: #FFEBEE; border-left: 6px solid #E53935; border-radius: 12px; padding: 12px 14px; margin-bottom: 18px; }
.attn p { margin-top: 6px; font-size: .9rem; }
.empty { background: #fff; border-radius: 14px; padding: 18px; margin-bottom: 18px; }
.child { background: #fff; border-radius: 16px; padding: 18px; margin-bottom: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.top > div { flex: 1; min-width: 160px; }
.av { width: 46px; height: 46px; border-radius: 50%; background: #00ACC1; color: #fff; font-weight: 800; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; }
.child h2 { font-size: 1.15rem; color: #263238; }
small { color: #78909C; font-weight: 600; }
.play { background: #00897B; color: #fff; border: none; border-radius: 10px; padding: 9px 14px; font-weight: 700; cursor: pointer; }
.stats { display: flex; gap: 18px; margin: 12px 0; color: #546E7A; }
.stats b { color: #00695C; font-size: 1.2rem; }
.none { color: #90A4AE; }
.skill { --c: #9E9E9E; border-left: 6px solid var(--c); background: #FAFBFD; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; }
.skill .row { display: flex; justify-content: space-between; align-items: center; }
.chip { background: var(--c); color: #fff; border-radius: 12px; padding: 2px 10px; font-size: .75rem; font-weight: 800; }
.bar { height: 8px; background: #E0E6EC; border-radius: 5px; margin: 6px 0; overflow: hidden; }
.bar span { display: block; height: 100%; background: var(--c); }
.skill p { margin-top: 5px; font-size: .9rem; }
.skill .tip { color: #00695C; font-weight: 600; }
.s-mastered, .s-proficient { --c: #00A152; }
.s-good { --c: #7CB342; }
.s-progressing { --c: #039BE5; }
.s-processing { --c: #FB8C00; }
.s-struggling { --c: #E53935; }
.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
.actions a { flex: 1; min-width: 140px; text-align: center; text-decoration: none; background: #fff; color: #00695C; font-weight: 800; border: 2px solid #00ACC1; border-radius: 12px; padding: 12px; }
</style>
</head>
<body>
<header><h2>LearnQuest</h2><nav><a href="profile.php">Profile</a><a href="link_child.php">Link child<?php if ($waiting): ?> (<?= $waiting ?>)<?php endif; ?></a><a href="reset_pin.php">Reset PIN</a><a href="select_child.php">Who is playing</a><a href="logout.php">Logout</a></nav></header>
<main>
<h1 class="hello">Hello, <?= e($parent["name"]) ?></h1>
<p class="sub">Here is how your children are doing.</p>
<?php if ($waiting): ?><a href="link_child.php" class="banner">&#128276; <?= (int) $waiting ?> child<?= $waiting > 1 ? "ren want" : " wants" ?> to link to you. Review &rarr;</a><?php endif; ?>
<?php $attn = []; foreach ($family as $ch) { foreach ($ch["report"] as $r) { if ($r["status"] === "struggling") { $attn[] = [$ch["name"], $r["skill"], $r["action"]]; } } } ?>
<?php if ($attn): ?><div class="attn"><b>Needs attention</b><?php foreach ($attn as $a): ?><p><?= e($a[0]) ?> &middot; <?= e($names[$a[1]] ?? ucfirst($a[1])) ?>: <?= e($a[2]) ?></p><?php endforeach; ?></div><?php endif; ?>
<?php if (!$family): ?><div class="empty">No children yet. <a href="registerchild.php">Add your first child</a> or <a href="link_child.php">link a child who signed up on their own</a>.</div><?php endif; ?>
<?php foreach ($family as $ch): list($tq, $acc) = child_stats($ch["report"]); ?>
<section class="child">
<div class="top"><span class="av"><?= e(strtoupper(substr($ch["name"], 0, 1))) ?></span><div><h2><?= e($ch["name"] . " " . $ch["surname"]) ?></h2><small>Grade <?= (int) $ch["grade"] ?> &middot; Age <?= (int) $ch["age"] ?><?php if (!empty($ch["player_code"])): ?> &middot; Player ID <?= e($ch["player_code"]) ?><?php endif; ?></small></div>
<form method="post" action="select_child.php"><input type="hidden" name="child_id" value="<?= (int) $ch["id"] ?>"><button type="submit" class="play">Let <?= e($ch["name"]) ?> play</button></form></div>
<div class="stats"><span><b><?= (int) $tq ?></b> questions</span><span><b><?= (int) $acc ?>%</b> correct</span></div>
<?php if (!$ch["report"]): ?><p class="none">No results yet. Once they play, the report appears here.</p><?php endif; ?>
<?php foreach ($ch["report"] as $r): ?>
<div class="skill s-<?= e($r["status"]) ?>"><div class="row"><b><?= e($names[$r["skill"]] ?? ucfirst($r["skill"])) ?></b><span class="chip"><?= e($labels[$r["status"]] ?? "New") ?></span></div><div class="bar"><span style="width: <?= (int) $r["accuracy"] ?>%"></span></div><small><?= (int) $r["accuracy"] ?>% correct in <?= (int) $r["total"] ?> questions</small><p><?= e($r["summary"]) ?></p><p class="tip">Try this: <?= e($r["action"]) ?></p></div>
<?php endforeach; ?>
</section>
<?php endforeach; ?>
<div class="actions"><a href="registerchild.php">Add a child</a><a href="link_child.php">Link a child</a><a href="reset_pin.php">Reset a PIN</a><a href="profile.php">My profile</a></div>
</main>
</body>
</html>
