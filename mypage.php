<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/progress_helper.php";
if (!empty($_SESSION["parent_id"]) && empty($_SESSION["child_mode"])) { header("Location: dashboard.php"); exit; }
if ((empty($_SESSION["child_login"]) && empty($_SESSION["child_mode"])) || empty($_SESSION["child_id"])) { unset($_SESSION["child_mode"]); header("Location: " . (!empty($_SESSION["parent_id"]) ? "select_child.php" : "login2.php")); exit; }
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$st = $pdo->prepare("SELECT name, player_code FROM children WHERE id = ?");
$st->execute([$_SESSION["child_id"]]);
$me = $st->fetch();
if (!$me) { header("Location: child_logout.php"); exit; }
$skills = [];
try {
    $s = progress_db()->prepare("SELECT subject, skill, COUNT(*) AS total, AVG(is_correct) AS acc FROM attempts WHERE child_id = ? GROUP BY subject, skill ORDER BY acc ASC");
    $s->execute([(string) $_SESSION["child_id"]]);
    $skills = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) { }
$mistakes = [];
try {
    $m = $pdo->prepare("SELECT operator, num1, num2, correct_answer, user_answer FROM quiz_attempts WHERE child_id = ? AND is_correct = 0 ORDER BY id DESC LIMIT 8");
    $m->execute([$_SESSION["child_id"]]);
    $mistakes = $m->fetchAll();
} catch (Exception $ex) { }
$signs = ["+" => "+", "-" => "-", "*" => "&times;", "/" => "&divide;"];
$names = ["addition" => "Addition", "subtraction" => "Subtraction", "multiplication" => "Multiplication", "division" => "Division", "mixed" => "All Operations", "spelling" => "Spelling", "sentence" => "Sentences", "opposites" => "Opposites", "unscramble" => "Unscramble"];
function feedback($pct) {
    if ($pct >= 85) { return ["&#11088;&#11088;&#11088;", "Superstar! Keep it up!", "#00C853"]; }
    if ($pct >= 60) { return ["&#11088;&#11088;", "Nearly there! A little more practice.", "#03A9F4"]; }
    return ["&#11088;", "Keep practising, you can do it!", "#FF6D00"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - My Page</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; padding: 24px 20px 50px; }
.wrap { width: 100%; max-width: 580px; margin: auto; }
.top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.top a { color: #6A1B9A; font-weight: 800; }
h1 { color: #FF4081; font-size: 1.7rem; }
.id { background: #fff; border: 3px dashed #FF4081; border-radius: 14px; padding: 12px; text-align: center; color: #5D4037; font-weight: 700; margin-bottom: 14px; }
.id b { color: #C2185B; font-size: 1.3rem; letter-spacing: 2px; }
.welcome { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 14px; padding: 12px; font-weight: 700; margin-bottom: 14px; }
.label { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #8D6E63; margin: 20px 0 10px; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.card { background: #fff; border: 3px solid #00C853; box-shadow: 4px 4px 0 #00C853; border-radius: 16px; padding: 18px 10px; font-family: inherit; font-size: 1rem; font-weight: 900; color: #1a1a2e; cursor: pointer; }
.card.s { border-color: #FF4081; box-shadow: 4px 4px 0 #FF4081; }
.card.m { border-color: #4361ee; box-shadow: 4px 4px 0 #4361ee; }
.card.d { border-color: #AA00FF; box-shadow: 4px 4px 0 #AA00FF; }
.fun { display: block; background: #fff; border: 3px solid #FF6D00; box-shadow: 4px 4px 0 #FF6D00; border-radius: 16px; padding: 16px; text-align: center; text-decoration: none; color: #FF6D00; font-weight: 900; margin-top: 14px; }
.rep { background: #fff; border-radius: 14px; padding: 12px 14px; margin-bottom: 10px; border-left: 8px solid #999; }
.bar { height: 10px; background: #eee; border-radius: 6px; margin: 6px 0; overflow: hidden; }
.bar span { display: block; height: 100%; }
.fix { background: #FFF9C4; border-left: 6px solid #FFD600; border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; color: #5D4037; font-weight: 600; }
.none { color: #8D6E63; font-weight: 600; }
@media (max-width: 400px) { .grid { grid-template-columns: 1fr; } }
</style>
<style id="child-nav-css">html{scroll-behavior:smooth}.label{scroll-margin-top:64px}.child-nav{position:sticky;top:0;z-index:50;display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin:-24px -20px 16px;padding:10px 12px;background:linear-gradient(90deg,#FF4081,#FF6D00);box-shadow:0 3px 10px rgba(0,0,0,.2)}.child-nav a{color:#fff;text-decoration:none;font-size:.82rem;font-weight:800;padding:7px 12px;border-radius:18px;background:rgba(255,255,255,.2);white-space:nowrap}.child-nav a:hover,.child-nav a.on{background:rgba(255,255,255,.4)}.child-nav a.exit{background:rgba(0,0,0,.25)}@media (max-width:480px){.child-nav a{font-size:.74rem;padding:6px 9px}}</style><style id="fix-menu-css">.fixwrap{position:relative;display:inline-block}.fixbtn{color:#fff;font-family:inherit;font-size:.82rem;font-weight:800;padding:7px 12px;border-radius:18px;background:rgba(255,255,255,.2);border:none;cursor:pointer;white-space:nowrap}.fixbtn:hover,.fixwrap.open .fixbtn{background:rgba(255,255,255,.4)}.fixpanel{display:none;position:absolute;top:calc(100% + 8px);left:50%;transform:translateX(-50%);width:min(340px,92vw);max-height:60vh;overflow-y:auto;background:#fff;border:3px solid #FF6D00;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.3);padding:10px;text-align:left;z-index:60}.fixwrap.open .fixpanel{display:block}.fixpanel h4{color:#FF6D00;margin:0 0 8px;font-size:.95rem}.fixpanel .fix{background:#FFF9C4;border-left:6px solid #FFD600;border-radius:10px;padding:8px 10px;margin-bottom:6px;color:#5D4037;font-weight:600;font-size:.85rem}.fixpanel .none{color:#8D6E63;font-weight:600;font-size:.85rem}</style><style id="child-chrome-css">.child-nav .brand{margin-right:auto;font-size:1rem;font-weight:900;background:rgba(255,255,255,.35);letter-spacing:.5px}.child-footer{margin:30px -20px -50px;padding:18px 16px;background:linear-gradient(90deg,#FF4081,#FF6D00);color:#fff;text-align:center}.child-footer .fbrand{font-size:1.2rem;font-weight:900;margin-bottom:6px}.child-footer .flinks{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:8px}.child-footer a{color:#fff;font-weight:800;font-size:.82rem;text-decoration:none;padding:5px 10px;border-radius:14px;background:rgba(255,255,255,.2)}.child-footer a:hover{background:rgba(255,255,255,.4)}.child-footer p{font-size:.85rem;font-weight:600;margin:2px 0}.child-footer .small{font-size:.72rem;opacity:.9}</style></head>
<body>
<nav class="child-nav"><a href="mypage.php" class="brand">LearnQuest</a><a href="mypage.php" class="on">&#127968; Home</a><a href="#report">&#11088; My Report</a><a href="my_activity.php">My Activity</a><span class="fixwrap" id="fixwrap"><button type="button" class="fixbtn" id="fixbtn">&#128295; Let's fix these</button><div class="fixpanel"><h4>Let's fix these</h4><?php if (!$mistakes): ?><p class="none">No maths mistakes to fix yet. Well done!</p><?php endif; ?><?php foreach ($mistakes as $x): ?><div class="fix"><?= (int) $x["num1"] ?> <?= $signs[$x["operator"]] ?? e($x["operator"]) ?> <?= (int) $x["num2"] ?> = <b><?= (int) $x["correct_answer"] ?></b> &nbsp;(you chose <?= (int) $x["user_answer"] ?>)</div><?php endforeach; ?></div></span><a href="child_link.php">&#128106; My parent or teacher</a><?php if (!empty($_SESSION["parent_id"])): ?><a href="unlock.php">Parent view</a><?php endif; ?><a href="child_logout.php" class="exit">Exit</a></nav><div class="wrap">
    <div class="top"><h1>Hi, <?= e($me["name"]) ?>!</h1><span><?php if (!empty($_SESSION["parent_id"])): ?> &nbsp; <?php endif; ?></span></div>
    <?php if (!empty($_GET["welcome"])): ?><div class="welcome">Welcome to LearnQuest! Write your Player ID down so you can log in next time.</div><?php endif; ?>
    <?php if (!empty($me["player_code"])): ?><div class="id">Your Player ID: <b><?= e($me["player_code"]) ?></b></div><?php endif; ?>
    <?php $pq = $pdo->prepare("SELECT parent_id FROM children WHERE id = ?"); $pq->execute([$_SESSION["child_id"]]); $hasParent = (bool) $pq->fetchColumn(); ?><div class="label" id="maths">Maths</div>
    <form action="calculation.php" method="post">
        <div class="grid">
            <button type="submit" name="operation" value="addition" class="card">&#10133; Addition</button>
            <button type="submit" name="operation" value="subtraction" class="card s">&#10134; Subtraction</button>
            <button type="submit" name="operation" value="multiplication" class="card m">&#10006;&#65039; Multiplication</button>
            <button type="submit" name="operation" value="division" class="card d">&#10135; Division</button>
        <button type="submit" name="operation" value="factors" class="card">&#128290; Factors</button><button type="submit" name="operation" value="fractions" class="card s">&#189; Fractions</button><button type="submit" name="operation" value="squareroots" class="card m">&#8730; Square roots</button></div>
    </form>
    <a href="fungames.php" class="fun">&#127922; Fun Games</a>
    <div class="label" id="report">My Report</div>
    <?php if (!$skills): ?><p class="none">Play a game and your report will show up here!</p><?php endif; ?>
    <?php foreach ($skills as $r): $pct = (int) round($r["acc"] * 100); $f = feedback($pct); ?>
        <div class="rep" style="border-color: <?= $f[2] ?>">
            <b><?= e($names[$r["skill"]] ?? ucfirst($r["skill"])) ?></b> &nbsp; <?= $f[0] ?>
            <div class="bar"><span style="width: <?= $pct ?>%; background: <?= $f[2] ?>"></span></div>
            <?= $pct ?>% right in <?= (int) $r["total"] ?> questions. <?= $f[1] ?>
        </div>
    <?php endforeach; ?>
    
</div><footer class="child-footer"><div class="fbrand">LearnQuest</div><p>Learn, play and grow, one question at a time.</p><p class="small">&copy; <?= date("Y") ?> LearnQuest. Made for children, with parents and teachers.</p></footer>
<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}function all(s){var a=[];document.querySelectorAll(s).forEach(function(x){a.push(x.innerText.replace(/\s+/g," ").trim());});return a;}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var parts=[t("h1"),t(".welcome")];var rep=all(".rep");if(rep.length){parts.push("Here is how you are doing.");parts=parts.concat(rep);}else{parts.push("Play a game and your report will show up here.");}var fix=all(".fix");if(fix.length){parts.push("Let us fix these.");fix.forEach(function(x){parts.push(x.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=/," equals "));});}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script><script>(function(){var w=document.getElementById("fixwrap"),b=document.getElementById("fixbtn");if(!w||!b){return;}b.addEventListener("click",function(e){e.stopPropagation();w.classList.toggle("open");});document.addEventListener("click",function(e){if(!w.contains(e.target)){w.classList.remove("open");}});})();</script></body>
</html>
