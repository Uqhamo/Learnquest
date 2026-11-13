<?php
session_start();
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Fun Games</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #FFF176; min-height: 100vh; padding: 30px 20px; }
.container { width: 100%; max-width: 560px; margin: auto; }
.back { display: inline-block; margin-bottom: 20px; color: #6A1B9A; font-weight: bold; text-decoration: none; }
.back:hover { text-decoration: underline; }
h1 { text-align: center; color: #FF4081; font-size: 2rem; margin-bottom: 6px; }
.subtitle { text-align: center; color: #5D4037; font-weight: bold; margin-bottom: 24px; }
.label { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #8D6E63; margin: 20px 0 10px; }
.all { display: flex; align-items: center; gap: 16px; width: 100%; background: #fff; border: 3px solid #FF6D00; border-radius: 18px; box-shadow: 5px 5px 0 #FF6D00; padding: 18px 22px; font-family: inherit; text-align: left; cursor: pointer; transition: transform 0.18s; }
.all:hover { transform: translateY(-4px); background: #FFF3E0; }
.all .icon { font-size: 2.4rem; }
.all .name { display: block; font-weight: 900; font-size: 1.1rem; color: #FF6D00; }
.all .desc { display: block; font-size: 0.8rem; color: #8D6E63; font-weight: 600; }
.mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.mini { display: flex; align-items: center; gap: 8px; background: #fff; border: 3px solid #FF4081; border-radius: 12px; padding: 9px 12px; text-decoration: none; color: #1a1a2e; font-weight: 800; font-size: 0.85rem; transition: transform 0.15s; }
.mini:hover { transform: translateY(-3px); }
.mini .icon { font-size: 1.3rem; }
.mini.sentence { border-color: #03A9F4; }
.mini.opposites { border-color: #4CAF50; }
.mini.unscramble { border-color: #FF9800; }
@media (max-width: 420px) { .mini-grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="container">
    <a href="parent_dashboard.php" class="back">&larr; Back to Dashboard</a>
    <h1>&#127922; Fun Games</h1>
    <p class="subtitle">Pick a game and have fun while you learn!</p>

    <div class="label">Maths</div>
    <form action="calculation.php" method="POST">
        <button type="submit" name="operation" value="mixed" class="all">
            <span class="icon">&#10133;&#10134;&#10006;&#65039;&#10135;</span>
            <span><span class="name">All Operations</span><span class="desc">Sums, times tables, factors, fractions and square roots all mixed. Can you handle it?</span></span>
        </button>
    </form>

    <div class="label">English</div>
    <div class="mini-grid">
        <a href="spelling.php" class="mini"><span class="icon">&#128292;</span>Spelling</a>
        <a href="sentence.php" class="mini sentence"><span class="icon">&#128221;</span>Sentences</a>
        <a href="opposites.php" class="mini opposites"><span class="icon">&#128260;</span>Opposites</a>
        <a href="unscramble.php" class="mini unscramble"><span class="icon">&#128256;</span>Unscramble</a>
    </div>
</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>