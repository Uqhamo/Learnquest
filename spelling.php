<?php
session_start();

require_once __DIR__ . '/chances_helper.php';
require_once __DIR__ . '/round_helper.php';
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (isset($_GET["finish_round"])) { round_finish("spelling", "", ""); }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
require_once 'progress_helper.php';
require_once 'feedback_helper.php';

$questions = [
    ["correct" => "Family", "options" => ["Famly", "Family", "Familey", "Fammily"]],
    ["correct" => "Mother", "options" => ["Mohter", "Mother", "Muther", "Mothar"]],
    ["correct" => "Father", "options" => ["Fahter", "Fathar", "Father", "Fadher"]],
    ["correct" => "Sister", "options" => ["Sistar", "Sister", "Sisster", "Sistter"]],
    ["correct" => "Brother", "options" => ["Brothar", "Brother", "Bruther", "Brotter"]],
    ["correct" => "Yellow", "options" => ["Yelow", "Yellow", "Yelloe", "Yellaw"]],
    ["correct" => "Purple", "options" => ["Perple", "Purpel", "Purple", "Purpul"]],
    ["correct" => "Elephant", "options" => ["Elefant", "Elephant", "Elephent", "Eliphant"]],
    ["correct" => "Monkey", "options" => ["Monky", "Monkey", "Munkey", "Monkay"]],
    ["correct" => "Rabbit", "options" => ["Rabit", "Rabbit", "Rabbat", "Rabbitt"]],
    ["correct" => "Library", "options" => ["Libary", "Library", "Liberry", "Librery"]],
    ["correct" => "Window", "options" => ["Windoe", "Window", "Windew", "Winndow"]],
    ["correct" => "Computer", "options" => ["Compuer", "Computer", "Computor", "Compter"]],
    ["correct" => "Mountain", "options" => ["Montain", "Mountain", "Mountin", "Mounttain"]],
    ["correct" => "Different", "options" => ["Diferent", "Different", "Diffrent", "Differant"]],
    ["correct" => "Question", "options" => ["Questoin", "Queston", "Question", "Quesstion"]],
    ["correct" => "Answer", "options" => ["Anser", "Answer", "Anwser", "Answar"]],
    ["correct" => "Together", "options" => ["Togather", "Togeter", "Together", "Togethr"]],
    ["correct"=>"Beautiful",
    "options"=>["Beautifull", "Beautiful", "Beutiful", "Beautifal"]


],
  [
        "correct" => "School",
        "options" => ["Skool", "School", "Schol", "Schoole"]
    ],
    [
        "correct" => "Friend",
        "options" => ["Freind", "Frend", "Friend", "Friand"]
    ],
    [
        "correct" => "Because",
        "options" => ["Becouse", "Because", "Becaus", "Becauce"]
    ],
    [
        "correct" => "Happy",
        "options" => ["Hapy", "Happey", "Happy", "Happi"]
    ],
    [
        "correct" => "Animal",
        "options" => ["Annimel", "Animal", "Anemal", "Animall"]
    ],
    [
        "correct" => "Teacher",
        "options" => ["Teacher", "Techer", "Teachar", "Teatcher"]
    ]

];
$message = ""; $messageClass = ""; $api_feedback = "";
$advance = true;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["answer"]) && isset($_SESSION["spelling_answer"])) {
    $answer = (string) $_POST["answer"];
    $correctAnswer = $_SESSION["spelling_answer"];
    $is_right = ($answer === $correctAnswer);
    $chance = chance_check("spelling", $is_right);
    $advance = $chance["advance"];
    $seconds = microtime(true) - ($_SESSION['q_start'] ?? microtime(true));
    log_attempt('english', 'spelling', $is_right, $seconds);
    if ($is_right) {
        $_SESSION["spelling_total"] = ($_SESSION["spelling_total"] ?? 0) + 1;
        $_SESSION["spelling_score"] = ($_SESSION["spelling_score"] ?? 0) + 1;
        $message = "Correct! Great spelling!"; $messageClass = "correct";
    } elseif ($chance["reveal"]) {
        $_SESSION["spelling_total"] = ($_SESSION["spelling_total"] ?? 0) + 1;
        $message = "Not quite. The correct spelling was: " . htmlspecialchars($correctAnswer); $messageClass = "wrong";
    } else {
        $message = "Not quite. Try again! Chances left: " . $chance["left"]; $messageClass = "wrong";
    }
    $_SESSION['spelling_attempts'] = ($_SESSION['spelling_attempts'] ?? 0) + 1;
    $api_feedback = get_api_feedback('spelling', $is_right, $_SESSION['spelling_attempts'], $answer);
    if ($advance) { $_SESSION['spelling_attempts'] = 0; }
}
if ($_SERVER["REQUEST_METHOD"] !== "POST" || $advance || !isset($_SESSION["spelling_current"])) {
    $idx = array_rand($questions);
    if (isset($_SESSION["spelling_current"]) && count($questions) > 1) { while ($idx === $_SESSION["spelling_current"]) { $idx = array_rand($questions); } }
    $_SESSION["spelling_current"] = $idx;
    $_SESSION["spelling_answer"] = $questions[$idx]["correct"];
    $_SESSION['q_start'] = microtime(true);
    chances_reset("spelling");
}
$question = $questions[$_SESSION["spelling_current"]];
$options = $question["options"];
shuffle($options);
$round_label = round_gate("spelling", $message, $api_feedback);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>LearnQuest - Spelling Challenge</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #FFF176;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 550px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            font-weight: bold;
            color: #6A1B9A;
        }

        .card {
            background: white;
            border: 4px solid #FF4081;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 7px 7px 0 #C2185B;
        }

        .icon {
            font-size: 60px;
            text-align: center;
        }

        h1 {
            text-align: center;
            color: #FF4081;
            margin-bottom: 10px;
        }

        .instructions {
            text-align: center;
            color: #5D4037;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .question {
            background: #FFF9C4;
            border: 3px solid #FFD600;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .answers {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .answer {
            border: none;
            padding: 15px;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
        }

        .answer:nth-child(1) {
            background: #FF4081;
        }

        .answer:nth-child(2) {
            background: #03A9F4;
        }

        .answer:nth-child(3) {
            background: #4CAF50;
        }

        .answer:nth-child(4) {
            background: #FF9800;
        }

        .answer:hover {
            transform: scale(1.04);
        }

        .feedback {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }

        .correct {
            background: #C8E6C9;
            border: 3px solid #4CAF50;
            color: #2E7D32;
        }
/*
        .wrong {
            background: #FFCDD2;
            border: 3px solid #E53935;
            color: #C62828;
        }*/
            .ai-feedback {
    background: #E1BEE7;
    border: 3px solid #9C27B0;
    color: #4A148C;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: bold;
}

        .skip {
            display: block;
            margin-top: 20px;
            text-align: center;
            text-decoration: none;
            background: #9C27B0;
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
        }

        @media(max-width: 500px) {
            .answers {
                grid-template-columns: 1fr;
            }
        }

    .wrong, .wrong-fix { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; }</style><style id="fit-game">html,body{height:100%}body{min-height:100vh;padding:12px 12px !important}.container{max-width:620px !important}.back{margin-bottom:10px !important;font-size:1.05rem !important}.card{padding:22px 24px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.2) !important}.icon{font-size:38px !important;margin-bottom:2px !important}h1{font-size:1.9rem !important;margin-bottom:6px !important}.instructions{font-size:1.1rem !important;margin-bottom:12px !important}.score,.feedback,.ai-feedback,.tip{padding:10px 12px !important;margin-bottom:12px !important;font-size:1.05rem !important;border-radius:12px !important}.question{padding:18px !important;margin-bottom:14px !important;font-size:clamp(1.3rem,4.6vh,1.8rem) !important}.question h2{font-size:1.3rem !important;margin-bottom:6px !important}.word{font-size:clamp(1.8rem,7vh,2.4rem) !important}.card>div[style*="E1F5FE"]{padding:10px !important;margin-bottom:12px !important;font-size:1.05rem !important}.answers{gap:12px !important;grid-template-columns:1fr 1fr !important}.answer{padding:14px !important;font-size:1.3rem !important}.skip{margin-top:14px !important;padding:11px !important;font-size:1.15rem !important}@media (max-height:760px){.icon{display:none !important}.card{padding:16px 20px !important}.answer{padding:11px !important;font-size:1.2rem !important}.ai-feedback,.tip{font-size:.92rem !important}h1{font-size:1.6rem !important}}@media (max-height:640px){.back,.instructions{display:none !important}}</style><style id="wrong-css">.wrong{background:#FFCDD2 !important;border:3px solid #E53935 !important;color:#C62828 !important}.correct{background:#C8E6C9 !important;border:3px solid #4CAF50 !important;color:#2E7D32 !important}</style>

</head>

<body>

<div class="container">

    <a href="englishgames.php" class="back">&larr; English Games</a>

    <div class="card">

        <div class="icon">&#128292;</div>

        <h1>Spelling Challenge</h1>

        <p class="instructions">
            Choose the correctly spelled word.
        </p>

        <?php if ($message): ?>

            <div class="feedback <?php echo $messageClass; ?>">
                <?php echo $message; ?>
            </div>

        <?php endif; ?>

<?php if ($api_feedback): ?>
    <div class="ai-feedback">
        <strong>Learning Feedback:</strong><br><br>
        <?php echo htmlspecialchars($api_feedback); ?>
    </div>
<?php endif; ?>
        <?php if (!empty($round_label)): ?><div style="text-align:center;font-weight:800;color:#6A1B9A;background:#F3E5F5;border-radius:12px;padding:8px;margin-bottom:12px"><?= htmlspecialchars($round_label) ?></div><?php endif; ?><div class="question">
            Which word is spelled correctly?
        </div>

        <form method="POST">

            <div class="answers">

                <?php foreach ($options as $option): ?>

                    <button
                        type="submit"
                        name="answer"
                        value="<?php echo htmlspecialchars($option); ?>"
                        class="answer">

                        <?php echo htmlspecialchars($option); ?>

                    </button>

                <?php endforeach; ?>

            </div>

        </form>

        <a href="spelling.php" class="skip">Skip Word &rarr;</a><div style="display:flex;gap:10px;margin-top:14px"><a href="dashboard.php" style="flex:1;text-align:center;text-decoration:none;background:#00BCD4;color:#fff;padding:11px;border-radius:12px;font-weight:700">Menu</a><a href="spelling.php?finish_round=1" style="flex:1;text-align:center;text-decoration:none;background:#E53935;color:#fff;padding:11px;border-radius:12px;font-weight:700">Finish &amp; See Score</a></div>

    </div>

</div>

<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var q=t(".equation");q=q.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=\s*\?/," equals what");var sc=document.querySelector(".scrambled");if(sc){q="Unscramble these letters: "+sc.innerText.trim().split("").join(", ");}var o=[];document.querySelectorAll(".answers button, #quizForm button").forEach(function(x){o.push(x.innerText.trim());});var parts=[t(".feedback, .correct, .wrong"),t(".ai-feedback"),t(".tip-box, .tip"),q||t(".question"),t(".hint")];if(o.length){parts.push("The choices are: "+o.join(", "));}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script></body>
</html>
