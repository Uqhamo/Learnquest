<?php
session_start();
require_once __DIR__ . '/chances_helper.php';
require_once __DIR__ . '/round_helper.php';
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
require_once __DIR__ . '/feedback_helper.php';
require_once __DIR__ . '/progress_helper.php';

$questions = [
    ["word" => "Up", "correct" => "Down", "options" => ["Down", "Under", "Above", "Top"], "tip" => "Up means towards a higher place, while down means towards a lower place."],
    ["word" => "Tall", "correct" => "Short", "options" => ["Long", "Short", "Big", "Thin"], "tip" => "Tall means having great height, while short means having little height."],
    ["word" => "Young", "correct" => "Old", "options" => ["Little", "Old", "Baby", "New"], "tip" => "Young means not many years of age, while old means many years of age."],
    ["word" => "Wet", "correct" => "Dry", "options" => ["Damp", "Dry", "Cold", "Soaked"], "tip" => "Wet means covered with water, while dry means with no water."],
    ["word" => "Soft", "correct" => "Hard", "options" => ["Hard", "Smooth", "Gentle", "Fluffy"], "tip" => "Soft means easy to press, while hard means firm and difficult to press."],
    ["word" => "Bright", "correct" => "Dim", "options" => ["Shiny", "Dim", "Colourful", "Clear"], "tip" => "Bright means giving a lot of light, while dim means giving only a little light."],
    ["word" => "Rich", "correct" => "Poor", "options" => ["Poor", "Wealthy", "Kind", "Happy"], "tip" => "Rich means having a lot of money, while poor means having very little."],
    ["word" => "Always", "correct" => "Never", "options" => ["Often", "Never", "Sometimes", "Usually"], "tip" => "Always means every time, while never means not at any time."],
    ["word" => "Start", "correct" => "Stop", "options" => ["Stop", "Begin", "Go", "Try"], "tip" => "Start means to begin something, while stop means to end it."],
    ["word" => "Love", "correct" => "Hate", "options" => ["Like", "Hate", "Enjoy", "Care"], "tip" => "Love means a very strong liking, while hate means a very strong dislike."],
    ["word" => "Win", "correct" => "Lose", "options" => ["Play", "Lose", "Run", "Score"], "tip" => "Win means to come first, while lose means not to win."],
    ["word" => "Buy", "correct" => "Sell", "options" => ["Pay", "Sell", "Shop", "Spend"], "tip" => "Buy means to get something by paying, while sell means to give something for money."],
    ["word" => "Push", "correct" => "Pull", "options" => ["Pull", "Move", "Shove", "Press"], "tip" => "Push means to move something away from you, while pull means to move it towards you."],
    ["word" => "Inside", "correct" => "Outside", "options" => ["Under", "Outside", "Behind", "Near"], "tip" => "Inside means within a place, while outside means not within it."],
    ["word" => "Thick", "correct" => "Thin", "options" => ["Wide", "Thin", "Short", "Heavy"], "tip" => "Thick means having a large distance from one side to the other, while thin means a small distance."],
    ["word" => "Noisy", "correct" => "Quiet", "options" => ["Loud", "Quiet", "Busy", "Angry"], "tip" => "Noisy means making a lot of sound, while quiet means making very little sound."],
    ["word" => "Easy", "correct" => "Difficult", "options" => ["Simple", "Difficult", "Fun", "Quick"], "tip" => "Easy means not needing much effort, while difficult means needing a lot of effort."],
    ["word" => "Hot", "correct" => "Cold", "options" => ["Cold", "Warm", "Sunny", "Heat"], "tip" => "Hot means having a high temperature, while cold means having a low temperature."],
    ["word" => "Happy", "correct" => "Sad", "options" => ["Excited", "Glad", "Sad", "Joyful"], "tip" => "Happy means feeling good or joyful. Sad means feeling unhappy."],
    ["word" => "Big", "correct" => "Small", "options" => ["Huge", "Tall", "Large", "Small"], "tip" => "Big describes something large, while small describes something little."],
    ["word" => "Fast", "correct" => "Slow", "options" => ["Quick", "Speedy", "Slow", "Rapid"], "tip" => "Fast means moving quickly, while slow means moving at a low speed."],
    ["word" => "Day", "correct" => "Night", "options" => ["Morning", "Night", "Sunlight", "Afternoon"], "tip" => "Day is the period of daylight, while night is the period of darkness."],
    ["word" => "Open", "correct" => "Closed", "options" => ["Wide", "Unlocked", "Closed", "Clear"], "tip" => "Open means not shut, while closed means shut."],
    ["word" => "Clean", "correct" => "Dirty", "options" => ["Neat", "Fresh", "Tidy", "Dirty"], "tip" => "Clean means free from dirt, while dirty means covered with dirt."],
    ["word" => "Early", "correct" => "Late", "options" => ["Soon", "Late", "Quick", "First"], "tip" => "Early means before the expected time, while late means after the expected time."],
    ["word" => "Strong", "correct" => "Weak", "options" => ["Powerful", "Tough", "Weak", "Brave"], "tip" => "Strong means having a lot of strength, while weak means having little strength."],
    ["word" => "Full", "correct" => "Empty", "options" => ["Filled", "Complete", "Packed", "Empty"], "tip" => "Full means containing as much as possible, while empty means containing nothing."]
];

$key = "opposites_game";
if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ["score" => 0, "total" => 0, "current" => null];
}
$game = &$_SESSION[$key];
$message = ""; $messageClass = ""; $tip = ""; $api_feedback = "";

$advance = true;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["answer"]) && $game["current"] !== null) {
    $previous = $questions[$game["current"]];
    $answer   = (string) $_POST["answer"];
    $is_right = ($answer === $previous["correct"]);
    $chance   = chance_check("opposites", $is_right);
    $advance  = $chance["advance"];
    $seconds = microtime(true) - ($_SESSION["q_start"] ?? microtime(true));
    log_attempt("english", "opposites", $is_right, $seconds);
    $tip = $previous["tip"];
    if ($is_right) {
        $game["total"]++;
        $game["score"]++;
        $message = "Correct! Excellent work!";
        $messageClass = "correct";
    } elseif ($chance["reveal"]) {
        $game["total"]++;
        $message = "Not quite! The correct answer is " . $previous["correct"] . ".";
        $messageClass = "wrong";
    } else {
        $message = "Not quite. Try again! Chances left: " . $chance["left"];
        $messageClass = "wrong";
    }
    $_SESSION["opposites_attempts"] = ($_SESSION["opposites_attempts"] ?? 0) + 1;
    $api_feedback = get_api_feedback("opposites", $is_right, $_SESSION["opposites_attempts"], $answer);
    if ($advance) { $_SESSION["opposites_attempts"] = 0; }
}

$previousIndex = $game["current"];
if ($_SERVER["REQUEST_METHOD"] !== "POST" || $advance || $previousIndex === null) {
    $available = array_keys($questions);
    if ($previousIndex !== null && count($available) > 1) {
        $available = array_values(array_diff($available, [$previousIndex]));
    }
    $game["current"] = $available[array_rand($available)];
    $_SESSION["q_start"] = microtime(true);
    chances_reset("opposites");
}

$question = $questions[$game["current"]];
$round_label = round_gate("opposites", $message, $api_feedback); if (isset($_GET["finish_round"])) { round_finish("opposites", $message, $api_feedback); } $options = $question["options"];
shuffle($options);

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Opposites Game</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.container { width: 100%; max-width: 600px; }
.back { display: inline-block; margin-bottom: 20px; color: #6A1B9A; font-weight: bold; text-decoration: none; }
.card { background: white; border: 4px solid #4CAF50; border-radius: 25px; padding: 30px; box-shadow: 7px 7px 0 #2E7D32; }
.icon { font-size: 60px; text-align: center; }
h1 { color: #4CAF50; text-align: center; margin-bottom: 10px; }
.instructions { color: #5D4037; font-weight: bold; text-align: center; margin-bottom: 20px; }
.score { background: #E8F5E9; border: 2px solid #4CAF50; border-radius: 12px; padding: 12px; text-align: center; font-weight: bold; color: #2E7D32; margin-bottom: 20px; }
.question { background: #FFF9C4; border: 3px solid #FFD600; border-radius: 15px; padding: 25px; text-align: center; margin-bottom: 20px; color: #5D4037; }
.question h2 { font-size: 1.2rem; margin-bottom: 10px; }
.word { font-size: 2rem; color: #FF4081; font-weight: bold; }
.answers { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.answer { padding: 15px; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: bold; color: white; cursor: pointer; }
.answer:nth-child(1) { background: #FF4081; }
.answer:nth-child(2) { background: #03A9F4; }
.answer:nth-child(3) { background: #4CAF50; }
.answer:nth-child(4) { background: #FF9800; }
.answer:hover { transform: scale(1.04); }
.feedback { padding: 15px; border-radius: 12px; margin-bottom: 15px; text-align: center; font-weight: bold; }
.correct { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; }
.wrong { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; }
.ai-feedback { background: #E1BEE7; border: 3px solid #9C27B0; color: #4A148C; padding: 15px; border-radius: 12px; margin-bottom: 15px; font-weight: bold; }
.tip { background: #FFF9C4; border-left: 6px solid #FFD600; border-radius: 10px; padding: 15px; margin-bottom: 20px; color: #5D4037; }
.skip { display: block; margin-top: 20px; padding: 12px; background: #9C27B0; color: white; text-align: center; text-decoration: none; border-radius: 12px; font-weight: bold; }
@media (max-width: 500px) { .answers { grid-template-columns: 1fr; } }
.hint-now{background:#E1F5FE;border-left:6px solid #03A9F4;border-radius:10px;padding:10px 14px;margin-bottom:16px;color:#0277BD;font-weight:600}.hint-now summary{cursor:pointer;font-weight:800}.hint-now div{margin-top:6px}.tipfull{background:#FFF9C4;border-left:5px solid #FFD600;border-radius:8px;padding:8px 10px;color:#5D4037}.tipnow{background:#FFF9C4;border-left:6px solid #FFD600;border-radius:10px;padding:12px 14px;margin-bottom:14px;color:#5D4037;font-weight:600}</style><style id="fit-game">html,body{height:100%}body{min-height:100vh;padding:12px 12px !important}.container{max-width:620px !important}.back{margin-bottom:10px !important;font-size:1.05rem !important}.card{padding:22px 24px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.2) !important}.icon{font-size:38px !important;margin-bottom:2px !important}h1{font-size:1.9rem !important;margin-bottom:6px !important}.instructions{font-size:1.1rem !important;margin-bottom:12px !important}.score,.feedback,.ai-feedback,.tip{padding:10px 12px !important;margin-bottom:12px !important;font-size:1.05rem !important;border-radius:12px !important}.question{padding:18px !important;margin-bottom:14px !important;font-size:clamp(1.3rem,4.6vh,1.8rem) !important}.question h2{font-size:1.3rem !important;margin-bottom:6px !important}.word{font-size:clamp(1.8rem,7vh,2.4rem) !important}.card>div[style*="E1F5FE"]{padding:10px !important;margin-bottom:12px !important;font-size:1.05rem !important}.answers{gap:12px !important;grid-template-columns:1fr 1fr !important}.answer{padding:14px !important;font-size:1.3rem !important}.skip{margin-top:14px !important;padding:11px !important;font-size:1.15rem !important}@media (max-height:760px){.icon{display:none !important}.card{padding:16px 20px !important}.answer{padding:11px !important;font-size:1.2rem !important}.ai-feedback,.tip{font-size:.92rem !important}h1{font-size:1.6rem !important}}@media (max-height:640px){.back,.instructions{display:none !important}}</style>
</head>
<body>
<div class="container">
    <a href="englishgames.php" class="back">&larr; English Games</a>
    <div class="card">
        <div class="icon">&#128260;</div>
        <h1>Opposites Game</h1>
        <p class="instructions">Choose the word that means the opposite.</p>
        <div class="score">&#11088; Score: <?= e($game["score"]) ?> / <?= e($game["total"]) ?></div>

        <?php if ($message !== ""): ?>
            <div class="feedback <?= e($messageClass) ?>"><?= e($message) ?></div>
        <?php endif; ?>
        

        <?php if ($api_feedback !== ""): ?>
            <div class="ai-feedback"><strong>Learning Feedback:</strong><br><br><?= e($api_feedback) ?></div>
        <?php endif; ?>

        

        <?php if (!empty($round_label)): ?><div style="text-align:center;font-weight:800;color:#6A1B9A;background:#F3E5F5;border-radius:12px;padding:8px;margin-bottom:12px"><?= htmlspecialchars($round_label) ?></div><?php endif; ?><div class="question">
            <h2>What is the opposite of:</h2>
            <div class="word"><?= e($question["word"]) ?>?</div>
        </div>
        <details class="hint-now"><summary>&#128161; Need a hint?</summary><div>The opposite starts with <b><?= e(strtoupper(substr($question["correct"], 0, 1))) ?></b> and has <?= strlen($question["correct"]) ?> letters.</div></details>

        <form method="POST">
            <div class="answers">
                <?php foreach ($options as $option): ?>
                    <button type="submit" name="answer" value="<?= e($option) ?>" class="answer"><?= e($option) ?></button>
                <?php endforeach; ?>
            </div>
        </form>

        <a href="opposites.php" class="skip">Skip Question &rarr;</a><div style="display:flex;gap:10px;margin-top:14px"><a href="dashboard.php" style="flex:1;text-align:center;text-decoration:none;background:#00BCD4;color:#fff;padding:11px;border-radius:12px;font-weight:700">Menu</a><a href="opposites.php?finish_round=1" style="flex:1;text-align:center;text-decoration:none;background:#E53935;color:#fff;padding:11px;border-radius:12px;font-weight:700">Finish &amp; See Score</a></div>
    </div>
</div>
<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var q=t(".equation");q=q.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=\s*\?/," equals what");var sc=document.querySelector(".scrambled");if(sc){q="Unscramble these letters: "+sc.innerText.trim().split("").join(", ");}var o=[];document.querySelectorAll(".answers button, #quizForm button").forEach(function(x){o.push(x.innerText.trim());});var parts=[t(".feedback, .correct, .wrong"),t(".ai-feedback"),t(".tip-box, .tip"),q||t(".question"),t(".hint")];if(o.length){parts.push("The choices are: "+o.join(", "));}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script></body>
</html>