<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
require_once __DIR__ . "/progress_helper.php";
require_once __DIR__ . "/topics_helper.php";
if (!function_exists("e")) { function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); } }
if (!defined("TG_LENGTH")) { define("TG_LENGTH", 10); }
if (!defined("TG_BONUS")) { define("TG_BONUS", 2); }
if (!defined("TG_PERCENT")) { define("TG_PERCENT", 80); }
function tg_feedback($topic, $ok, $tries, $given) {
    if (!function_exists("curl_init")) { return "Feedback service is currently unavailable."; }
    $ch = curl_init("http://127.0.0.1:8000/api/v1/feedback");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(["child_id" => (string) ($_SESSION["child_id"] ?? "0"), "age" => (int) ($_SESSION["child_age"] ?? 8), "subject" => "Mathematics", "topic" => $topic, "is_correct" => $ok, "attempts_count" => $tries, "selected_answer" => $given]), CURLOPT_HTTPHEADER => ["Content-Type: application/json", "Accept: application/json"], CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 5]);
    $r = curl_exec($ch); curl_close($ch);
    if ($r === false) { return "Feedback service is currently unavailable."; }
    $j = json_decode($r, true);
    return $j["feedback"]["message"] ?? "Feedback could not be generated.";
}
function tg_end($message, $api) {
    $t = (isset($_SESSION["tg"]) && is_array($_SESSION["tg"])) ? $_SESSION["tg"] : ["done" => 0, "right" => 0, "score" => 0, "bonus" => false];
    $_SESSION["round_last"] = ["game" => "math", "done" => (int) $t["done"], "right" => (int) $t["right"], "bonus" => !empty($t["bonus"]), "points" => (int) $t["score"], "message" => (string) $message, "feedback" => (string) $api];
    unset($_SESSION["tg"]);
    header("Location: round_end.php"); exit;
}
$topics = ["factors" => "Factors", "fractions" => "Fractions", "squareroots" => "Square roots"];
$tips = ["factors" => "A factor divides the number exactly, with nothing left over. Try dividing the number by each choice.", "fractions" => "Look at the bottom numbers first. If they match, work with the top numbers. If they do not, make them match.", "squareroots" => "A square root is the number that, multiplied by itself, gives the number inside the square root sign."];
$topic = $_SESSION["tg_topic"] ?? "";
if (isset($_POST["operation"]) && isset($topics[$_POST["operation"]])) { $topic = $_POST["operation"]; $_SESSION["tg_topic"] = $topic; unset($_SESSION["tg"]); }
if (!isset($topics[$topic])) { $op = $_SESSION["operation"] ?? ""; $topic = isset($topics[$op]) ? $op : "factors"; $_SESSION["tg_topic"] = $topic; }
$age = (int) ($_SESSION["child_age"] ?? 8);
$level = $age <= 8 ? 1 : ($age <= 10 ? 2 : 3);
if (!isset($_SESSION["tg"]) || !is_array($_SESSION["tg"])) { $_SESSION["tg"] = ["done" => 0, "right" => 0, "score" => 0, "bonus" => false, "q" => null, "tries" => 0, "skips" => 0, "start" => microtime(true)]; }
if (isset($_GET["finish"])) { tg_end("", ""); }
$tg = &$_SESSION["tg"];
$message = ""; $mclass = ""; $tip = ""; $api = ""; $skipmsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user_answer"]) && $tg["q"] !== null) {
    $given = (string) $_POST["user_answer"];
    $ok = ($given === $tg["q"]["answer"]);
    log_attempt("math", $topic, $ok, microtime(true) - (float) $tg["start"]);
    $tg["tries"]++;
    $tries = $tg["tries"]; $advance = false; $showfb = false;
    if ($ok) {
        $points = max(1, 4 - $tries); $tg["score"] += $points; $tg["right"]++; $tg["done"]++;
        $message = "Correct! Well done. +" . $points . " points"; $mclass = "correct"; $advance = true; $showfb = true;
    } else {
        $mclass = "wrong"; $tip = $tips[$topic];
        if ($tries >= 3) { $tg["done"]++; $advance = true; $showfb = true; $message = "The correct answer was " . $tg["q"]["answer"] . "."; }
        else { $message = "Not quite. You chose " . $given . ". Chances left: " . (3 - $tries); }
    }
    if ($showfb) { $api = tg_feedback($topic, $ok, $tries, $given); }
    if ($advance) {
        $tg["q"] = null; $tg["tries"] = 0; $tg["skips"] = 0;
        if ($tg["done"] >= TG_LENGTH && !$tg["bonus"] && ($tg["right"] * 100 / $tg["done"]) >= TG_PERCENT) { $tg["bonus"] = true; }
        if ($tg["done"] >= TG_LENGTH + ($tg["bonus"] ? TG_BONUS : 0)) { tg_end($message, $api); }
    }
}
if (isset($_GET["skip"]) && $tg["q"] !== null) {
    if ($tg["skips"] < 2) { $nudges = ["You can do this! Take a breath, look again and pick an answer. Every try makes your brain stronger.", "So close to cracking it! Even grown-ups make mistakes. Try one more time. If it still feels too hard, you can skip next."]; $skipmsg = $nudges[$tg["skips"]]; $tg["skips"]++; }
    else { $tg["q"] = null; $tg["skips"] = 0; $tg["tries"] = 0; }
}
if ($tg["q"] === null) { $tg["q"] = tg_new($topic, $level); $tg["start"] = microtime(true); }
$q = $tg["q"];
$label = $tg["done"] < TG_LENGTH ? "Question " . ($tg["done"] + 1) . " of " . TG_LENGTH : "Bonus question " . ($tg["done"] - TG_LENGTH + 1) . " of " . TG_BONUS . ". You earned it!";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - <?= e($topics[$topic]) ?></title>
<link rel="stylesheet" href="calculation.css">
<style>.qlabel{text-align:center;font-weight:800;color:#6A1B9A;background:#F3E5F5;border-radius:12px;padding:6px;margin-bottom:8px}.equation{line-height:1.3}</style>
<style id="tg-menu-css">.action-row{display:flex;gap:10px;margin-top:10px}.action-row .btn{flex:1;margin-top:0}.menu-btn{background:#00BCD4 !important}</style></head>
<body>
<div class="content"><div class="card">

<div class="qlabel"><?= e($label) ?></div>
<?php if ($message !== ""): ?><div class="<?= e($mclass) ?>"><?= e($message) ?></div><?php endif; ?>
<?php if ($skipmsg !== ""): ?><div class="motivate">&#127775; <?= e($skipmsg) ?></div><?php endif; ?>
<?php if ($api !== ""): ?><div class="ai-feedback"><strong>Learning Feedback:</strong><br><br><?= e($api) ?></div><?php endif; ?>
<?php if ($tip !== ""): ?><div class="tip-box">&#128161; <?= e($tip) ?></div><?php endif; ?>
<div class="score-box">Score: <?= (int) $tg["score"] ?> points &middot; <?= (int) $tg["right"] ?> of <?= (int) $tg["done"] ?> right</div>
<h2><?= e($topics[$topic]) ?></h2>
<div class="equation"><?= e($q["text"]) ?></div>
<form id="quizForm" method="POST" action="topics_game.php">
<?php foreach ($q["options"] as $o): ?><button type="submit" name="user_answer" value="<?= e($o) ?>" class="btn"><?= e($o) ?></button><?php endforeach; ?>
</form>
<div class="action-row"><a href="topics_game.php?skip=1" class="btn next-btn">Skip Question</a><a href="dashboard.php" class="btn menu-btn">Menu</a></div>
<a href="topics_game.php?finish=1" class="btn exit-btn">Finish &amp; See Score</a>
</div></div>
</body>
</html>
