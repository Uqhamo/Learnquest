<?php
session_start();
require_once __DIR__ . '/chances_helper.php';
require_once __DIR__ . '/round_helper.php';
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
require_once __DIR__ . '/feedback_helper.php';
require_once __DIR__ . '/progress_helper.php';

$questions = [
    ["word" => "PLANET", "options" => ["PLENAT", "PLANET", "PALNET", "PLANTE"], "hint" => "Earth is one of these.", "tip" => "PLANET is spelled P-L-A-N-E-T."],
    ["word" => "CAMERA", "options" => ["CAMERA", "CAMREA", "CAEMRA", "MACERA"], "hint" => "You use it to take photos.", "tip" => "CAMERA is spelled C-A-M-E-R-A."],
    ["word" => "ELEPHANT", "options" => ["ELEPHANT", "ELEPHNAT", "ELEHPANT", "ELPEHANT"], "hint" => "The biggest land animal, with a long trunk.", "tip" => "ELEPHANT is spelled E-L-E-P-H-A-N-T."],
    ["word" => "LIBRARY", "options" => ["LIBARRY", "LIBRARY", "LIBRYAR", "LBIRARY"], "hint" => "A place full of books you can borrow.", "tip" => "LIBRARY is spelled L-I-B-R-A-R-Y."],
    ["word" => "RIVER", "options" => ["RIVRE", "RIVER", "RVIER", "IRVER"], "hint" => "Water that flows towards the sea.", "tip" => "RIVER is spelled R-I-V-E-R."],
    ["word" => "MONKEY", "options" => ["MONKEY", "MONEKY", "MOKNEY", "MNOKEY"], "hint" => "An animal that climbs trees and likes bananas.", "tip" => "MONKEY is spelled M-O-N-K-E-Y."],
    ["word" => "PURPLE", "options" => ["PURPEL", "PURPLE", "PRUPLE", "PUPRLE"], "hint" => "A colour made by mixing red and blue.", "tip" => "PURPLE is spelled P-U-R-P-L-E."],
    ["word" => "BUTTER", "options" => ["BUTTER", "BUTETR", "BUTTRE", "BUTRET"], "hint" => "A yellow spread you put on bread.", "tip" => "BUTTER has two Ts: B-U-T-T-E-R."],
    ["word" => "MOTHER", "options" => ["MOHTER", "MOTHER", "MOTEHR", "MTOHER"], "hint" => "A parent who is a woman.", "tip" => "MOTHER is spelled M-O-T-H-E-R."],
    ["word" => "SISTER", "options" => ["SISTER", "SITSER", "SISTRE", "SSITER"], "hint" => "A girl who has the same parents as you.", "tip" => "SISTER is spelled S-I-S-T-E-R."],
    ["word" => "ANIMAL", "options" => ["ANIMAL", "AMINAL", "ANIMLA", "NAIMAL"], "hint" => "A living thing like a dog or a cat.", "tip" => "ANIMAL is spelled A-N-I-M-A-L."],
    ["word" => "FAMILY", "options" => ["FAMLIY", "FAMILY", "FIMALY", "FAMYLI"], "hint" => "The people you live with and love.", "tip" => "FAMILY is spelled F-A-M-I-L-Y."],
    ["word" => "SUMMER", "options" => ["SUMMER", "SUMEMR", "SUMMRE", "SMUMER"], "hint" => "The hottest season of the year.", "tip" => "SUMMER has two Ms in the middle: S-U-M-M-E-R."],
    ["word" => "RABBIT", "options" => ["RABIBT", "RABBIT", "RABTIB", "RBABIT"], "hint" => "A small animal with long ears that hops.", "tip" => "RABBIT has two Bs: R-A-B-B-I-T."],
    ["word" => "WINDOW", "options" => ["WINDOW", "WINODW", "WIDNOW", "WNIDOW"], "hint" => "You look through it, and it lets light into a room.", "tip" => "WINDOW is spelled W-I-N-D-O-W."],
    ["word" => "APPLE", "options" => ["APPLE", "PAPEL", "PLEAP", "APELP"], "hint" => "A fruit that can be red or green.", "tip" => "APPLE is a fruit. Its letters are A-P-P-L-E."],
    ["word" => "SCHOOL", "options" => ["SCOHOL", "SCHOOL", "SCHOLL", "SHCOOL"], "hint" => "A place where children learn.", "tip" => "SCHOOL is where learners attend lessons."],
    ["word" => "HOUSE", "options" => ["HOSUE", "HOUES", "HOUSE", "HOSEU"], "hint" => "A building where people live.", "tip" => "HOUSE is spelled H-O-U-S-E."],
    ["word" => "WATER", "options" => ["WATER", "WATRE", "WAETR", "WERTA"], "hint" => "Something we drink every day.", "tip" => "WATER is important for keeping our bodies healthy."],
    ["word" => "CHAIR", "options" => ["CHAIR", "CHIRA", "CAHIR", "CHRAI"], "hint" => "Something you sit on.", "tip" => "CHAIR is spelled C-H-A-I-R."],
    ["word" => "FRIEND", "options" => ["FREIND", "FRIEND", "FIRNED", "FRINED"], "hint" => "Someone you enjoy spending time with.", "tip" => "FRIEND is spelled F-R-I-E-N-D."],
    ["word" => "GARDEN", "options" => ["GARDEN", "GRADEN", "GANDER", "GARDEL"], "hint" => "A place where flowers or vegetables grow.", "tip" => "GARDEN is a place for growing plants."],
    ["word" => "ORANGE", "options" => ["ORNAGE", "ORANGE", "ORAGNE", "ONRAGE"], "hint" => "A citrus fruit and also a colour.", "tip" => "ORANGE is spelled O-R-A-N-G-E."],
    ["word" => "PENCIL", "options" => ["PENCIL", "PENCLI", "PINCEL", "PECNIL"], "hint" => "Something used for writing or drawing.", "tip" => "PENCIL is a writing tool that can be erased."],
    ["word" => "FLOWER", "options" => ["FLOEWR", "FLOWER", "FOWLER", "FLWOER"], "hint" => "The colourful part of many plants.", "tip" => "FLOWER is spelled F-L-O-W-E-R."]
];

$key = "unscramble_game";
if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ["score" => 0, "total" => 0, "current" => null, "scrambled" => ""];
}
$game = &$_SESSION[$key];
$message = ""; $messageClass = ""; $tip = ""; $api_feedback = "";

$advance = true;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["answer"]) && $game["current"] !== null) {
    $previous = $questions[$game["current"]];
    $answer   = (string) $_POST["answer"];
    $is_right = ($answer === $previous["word"]);
    $chance   = chance_check("unscramble", $is_right);
    $advance  = $chance["advance"];
    $seconds = microtime(true) - ($_SESSION['q_start'] ?? microtime(true));
    log_attempt('english', 'unscramble', $is_right, $seconds);
    if ($is_right) {
        $game["total"]++; $game["score"]++;
        $message = "Correct! You solved the word!"; $messageClass = "correct";
    } elseif ($chance["reveal"]) {
        $game["total"]++;
        $message = "Not quite! The correct word was " . $previous["word"] . "."; $messageClass = "wrong";
        $tip = $previous["tip"];
    } else {
        $message = "Not quite. Try again! Chances left: " . $chance["left"]; $messageClass = "wrong";
    }
    $_SESSION['unscramble_attempts'] = ($_SESSION['unscramble_attempts'] ?? 0) + 1;
    $api_feedback = get_api_feedback('unscramble', $is_right, $_SESSION['unscramble_attempts'], $answer);
    if ($advance) { $_SESSION['unscramble_attempts'] = 0; }
}

$previousIndex = $game["current"];
if ($_SERVER["REQUEST_METHOD"] !== "POST" || $advance || $previousIndex === null) {
    $available = array_keys($questions);
    if ($previousIndex !== null && count($available) > 1) { $available = array_values(array_diff($available, [$previousIndex])); }
    $game["current"] = $available[array_rand($available)];
    $_SESSION['q_start'] = microtime(true);
    chances_reset("unscramble");
    $question = $questions[$game["current"]];
    $letters = str_split($question["word"]);
    do { shuffle($letters); $scrambled = implode("", $letters); } while ($scrambled === $question["word"]);
    $game["scrambled"] = $scrambled;
} else {
    $question = $questions[$game["current"]];
    $scrambled = $game["scrambled"];
} $round_label = round_gate("unscramble", $message, $api_feedback); if (isset($_GET["finish_round"])) { round_finish("unscramble", $message, $api_feedback); } $options = $question["options"];
shuffle($options);

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Unscramble the Word</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.container { width: 100%; max-width: 600px; }
.back { display: inline-block; margin-bottom: 20px; color: #6A1B9A; font-weight: bold; text-decoration: none; }
.card { background: white; border: 4px solid #FF9800; border-radius: 25px; padding: 30px; box-shadow: 7px 7px 0 #E65100; }
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
</style><style id="fit-game">html,body{height:100%}body{min-height:100vh;padding:8px 10px !important;align-items:flex-start !important}.container{max-width:600px !important}.back{margin-bottom:6px !important;font-size:1rem !important}.card{padding:12px 18px !important;border-width:3px !important;border-radius:18px !important;box-shadow:4px 4px 0 rgba(0,0,0,.2) !important}.icon{display:none !important}h1{font-size:1.5rem !important;margin-bottom:2px !important}.instructions{font-size:.95rem !important;margin-bottom:6px !important}.score,.feedback,.ai-feedback,.tip{padding:6px 10px !important;margin-bottom:6px !important;font-size:.9rem !important;border-radius:10px !important}.question{padding:10px !important;margin-bottom:8px !important}.question h2{font-size:1rem !important;margin-bottom:2px !important}.word{font-size:clamp(1.6rem,6.5vh,2.3rem) !important;letter-spacing:6px !important}.card>div[style*="E1F5FE"]{padding:7px 10px !important;margin-bottom:8px !important;font-size:.95rem !important}.answers{gap:8px !important;grid-template-columns:1fr 1fr !important}.answer{padding:10px !important;font-size:1.15rem !important}.skip{margin-top:8px !important;padding:8px !important;font-size:1rem !important}@media (max-height:760px){.back,.instructions{display:none !important}.ai-feedback,.tip{font-size:.8rem !important}.answer{padding:8px !important}}</style>
</head>
<body>
<div class="container">
    <a href="englishgames.php" class="back">&larr; English Games</a>
    <div class="card">
        <div class="icon">&#128256;</div>
        <h1>Unscramble the Word</h1>
        <p class="instructions">Put the mixed-up letters in the correct order!</p>
        <div class="score">&#11088; Score: <?= e($game["score"]) ?> / <?= e($game["total"]) ?></div>

        <?php if ($message !== ""): ?>
            <div class="feedback <?= e($messageClass) ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($api_feedback !== ""): ?>
            <div class="ai-feedback"><strong>Learning Feedback:</strong><br><br><?= e($api_feedback) ?></div>
        <?php endif; ?>

        <?php if ($tip !== ""): ?>
            <div class="tip">&#128161; <strong>Learning Tip:</strong> <?= e($tip) ?></div>
        <?php endif; ?>

        <?php if (!empty($round_label)): ?><div style="text-align:center;font-weight:800;color:#6A1B9A;background:#F3E5F5;border-radius:12px;padding:8px;margin-bottom:12px"><?= htmlspecialchars($round_label) ?></div><?php endif; ?><div class="question">
            <h2>Unscramble these letters:</h2>
            <div class="word" style="letter-spacing:7px"><?= e($game["scrambled"]) ?></div></div><div style="background:#E1F5FE;border-left:5px solid #03A9F4;border-radius:10px;padding:12px;color:#0277BD;margin-bottom:20px">&#128161; <strong>Hint:</strong> <?= e($question["hint"]) ?>
        </div>

        <form method="POST">
            <div class="answers">
                <?php foreach ($options as $option): ?>
                    <button type="submit" name="answer" value="<?= e($option) ?>" class="answer"><?= e($option) ?></button>
                <?php endforeach; ?>
            </div>
        </form>

        <a href="unscramble.php" class="skip">Skip Word &rarr;</a><div style="display:flex;gap:10px;margin-top:14px"><a href="dashboard.php" style="flex:1;text-align:center;text-decoration:none;background:#00BCD4;color:#fff;padding:11px;border-radius:12px;font-weight:700">Menu</a><a href="unscramble.php?finish_round=1" style="flex:1;text-align:center;text-decoration:none;background:#E53935;color:#fff;padding:11px;border-radius:12px;font-weight:700">Finish &amp; See Score</a></div>
    </div>
</div>
<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var q=t(".equation");q=q.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=\s*\?/," equals what");var sc=document.querySelector(".scrambled");if(sc){q="Unscramble these letters: "+sc.innerText.trim().split("").join(", ");}var o=[];document.querySelectorAll(".answers button, #quizForm button").forEach(function(x){o.push(x.innerText.trim());});var parts=[t(".feedback, .correct, .wrong"),t(".ai-feedback"),t(".tip-box, .tip"),q||t(".question"),t(".hint")];if(o.length){parts.push("The choices are: "+o.join(", "));}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script></body>
</html>