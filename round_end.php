<?php
session_start();
require_once __DIR__ . "/round_helper.php";
if (empty($_SESSION["round_last"])) { header("Location: dashboard.php"); exit; }
$r = $_SESSION["round_last"];
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$pages = ["math" => "calculation.php", "spelling" => "spelling.php", "sentence" => "sentence.php", "opposites" => "opposites.php", "unscramble" => "unscramble.php"];
$titles = ["math" => "Maths", "spelling" => "Spelling Challenge", "sentence" => "Complete the Sentence", "opposites" => "Opposites Game", "unscramble" => "Unscramble the Word"];
$play = $pages[$r["game"]] ?? "dashboard.php";
$name = $titles[$r["game"]] ?? "Game";
$pct = $r["done"] ? (int) round($r["right"] / $r["done"] * 100) : 0;
if ($pct >= 90) { $stars = "&#11088;&#11088;&#11088;"; $line = "Superstar! You are amazing!"; }
elseif ($pct >= 70) { $stars = "&#11088;&#11088;"; $line = "Great job! Keep it up!"; }
elseif ($pct >= 50) { $stars = "&#11088;"; $line = "Good effort! A little more practice and you will shine."; }
else { $stars = "&#128170;"; $line = "Keep going! Every try makes your brain stronger."; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Round complete</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 28px; width: 100%; max-width: 440px; text-align: center; }
h1 { color: #FF4081; margin-bottom: 4px; }
.game { color: #8D6E63; font-weight: 700; margin-bottom: 8px; }
.stars { font-size: 2.4rem; margin: 6px 0; }
.big { font-size: 1.6rem; font-weight: 900; color: #2E7D32; }
.line { color: #5D4037; font-weight: 700; margin: 8px 0 12px; }
.bonus { background: #FFF9C4; border: 3px dashed #FF6D00; border-radius: 12px; padding: 8px; color: #5D4037; font-weight: 700; margin-bottom: 12px; }
.last { background: #F5F5F5; border-radius: 12px; padding: 10px; text-align: left; margin: 12px 0; color: #5D4037; font-size: .9rem; }
.ai { background: #E1BEE7; border: 3px solid #9C27B0; color: #4A148C; border-radius: 12px; padding: 10px; margin-top: 8px; font-weight: 700; }
a.btn { display: block; margin-top: 12px; padding: 14px; border-radius: 14px; color: #fff; font-weight: 800; text-decoration: none; }
a.play { background: #FF4081; }
a.menu { background: #00BCD4; }
</style>
</head>
<body>
<div class="card">
<h1>Round complete!</h1>
<div class="game"><?= e($name) ?></div>
<div class="stars"><?= $stars ?></div>
<div class="big"><?= (int) $r["right"] ?> of <?= (int) $r["done"] ?> right (<?= (int) $pct ?>%)</div>
<?php if ($r["points"] !== null): ?><div class="line"><?= (int) $r["points"] ?> points</div><?php endif; ?>
<div class="line"><?= e($line) ?></div>
<?php if ($r["bonus"]): ?><div class="bonus">You answered so well that you earned <?= (int) BONUS_QUESTIONS ?> bonus questions!</div><?php endif; ?>
<?php if ($r["message"] !== "" || $r["feedback"] !== ""): ?><div class="last"><b>Your last answer</b><br><?= e($r["message"]) ?><?php if ($r["feedback"] !== ""): ?><div class="ai"><?= e($r["feedback"]) ?></div><?php endif; ?></div><?php endif; ?>
<a class="btn play" href="<?= e($play) ?>">Play again</a>
<a class="btn menu" href="dashboard.php">Back to menu</a>
</div>
</body>
</html>
