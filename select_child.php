<?php
session_start();
require_once __DIR__ . "/database.php";
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
if (!empty($_SESSION["child_mode"]) && !empty($_SESSION["child_id"])) { header("Location: mypage.php"); exit; } unset($_SESSION["child_mode"]);
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
if (isset($_POST["child_id"])) {
    $st = $pdo->prepare("SELECT id, age FROM children WHERE id = ? AND parent_id = ?");
    $st->execute([(int) $_POST["child_id"], $_SESSION["parent_id"]]);
    if ($c = $st->fetch()) { session_regenerate_id(true); $_SESSION["child_id"] = (int) $c["id"]; $_SESSION["child_age"] = (int) $c["age"]; $_SESSION["child_mode"] = true; header("Location: mypage.php"); exit; }
}
$st = $pdo->prepare("SELECT name, surname FROM parents WHERE id = ?");
$st->execute([$_SESSION["parent_id"]]);
$parent = $st->fetch();
$st = $pdo->prepare("SELECT id, name, surname FROM children WHERE parent_id = ? ORDER BY name");
$st->execute([$_SESSION["parent_id"]]);
$children = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Who is playing?</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; padding: 30px 20px; }
.wrap { max-width: 560px; margin: auto; }
h1 { text-align: center; color: #FF4081; margin-bottom: 22px; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.tile { display: flex; align-items: center; gap: 12px; background: #fff; border: 3px solid #FF4081; box-shadow: 4px 4px 0 #C2185B; border-radius: 18px; padding: 16px; font-family: inherit; font-size: 1.05rem; font-weight: 900; color: #1a1a2e; cursor: pointer; text-decoration: none; text-align: left; }
.tile.par { border-color: #00BCD4; box-shadow: 4px 4px 0 #0097A7; }
.tile:hover { transform: translateY(-4px); }
.av { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #FF4081, #FF6D00); color: #fff; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
small { color: #8D6E63; font-weight: 600; }
.links { text-align: center; margin-top: 22px; font-weight: 700; }
.links a { color: #6A1B9A; }
@media (max-width: 420px) { .grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="wrap">
<h1>Who is playing?</h1>
<form method="post"><div class="grid">
<a href="parent_dashboard.php" class="tile par"><span class="av"><?= e(strtoupper(substr($parent["name"], 0, 1))) ?></span><span>Parent<br><small>Reports and settings</small></span></a>
<?php foreach ($children as $c): ?>
<button type="submit" name="child_id" value="<?= (int) $c["id"] ?>" class="tile"><span class="av"><?= e(strtoupper(substr($c["name"], 0, 1))) ?></span><span><?= e($c["name"]) ?></span></button>
<?php endforeach; ?>
</div></form>
<p class="links"><a href="child_signup.php">Add a child</a> &middot; <a href="logout.php">Logout</a></p>
</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
