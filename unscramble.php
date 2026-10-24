<?php
session_start();
if (empty($_SESSION['parent_id'])) { header('Location: login2.php'); exit; }
if (empty($_SESSION['child_id']))  { header('Location: select_child.php'); exit; }
require_once __DIR__ . '/feedback_helper.php';
require_once __DIR__ . '/progress_helper.php';

$questions = [
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["answer"]) && $game["current"] !== null) {
    $previous = $questions[$game["current"]];
    $answer   = (string) $_POST["answer"];
    $is_right = ($answer === $previous["word"]);
    $game["total"]++;

    $seconds = microtime(true) - ($_SESSION['q_start'] ?? microtime(true));
    log_attempt('english', 'unscramble', $is_right, $seconds);

    if ($is_right) {
        $game["score"]++;
        $message = "Correct! You solved the word!";
        $messageClass = "correct";
    } else {
        $message = "Not quite! The correct word was " . $previous["word"] . ".";
        $messageClass = "wrong";
    }
    $tip = $previous["tip"];

    $_SESSION['unscramble_attempts'] = ($_SESSION['unscramble_attempts'] ?? 0) + 1;
    $api_feedback = get_api_feedback('unscramble', $is_right, $_SESSION['unscramble_attempts'], $answer);
    if ($is_right) { $_SESSION['unscramble_attempts'] = 0; }
}

$previousIndex = $game["current"];
$available = array_keys($questions);
if ($previousIndex !== null && count($available) > 1) {
    $available = array_values(array_diff($available, [$previousIndex]));
}
$game["current"] = $available[array_rand($available)];
$_SESSION['q_start'] = microtime(true);

$question = $questions[$game["current"]];
$letters = str_split($question["word"]); do { shuffle($letters); $scrambled = implode("", $letters); } while ($scrambled === $question["word"]); $game["scrambled"] = $scrambled; $options = $question["options"];
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
</style>
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

        <div class="question">
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

        <a href="unscramble.php" class="skip">Skip Word &rarr;</a>
    </div>
</div>
</body>
</html>