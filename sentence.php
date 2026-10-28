<?php
session_start();
require_once __DIR__ . '/round_helper.php';
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
require_once 'progress_helper.php';
require_once 'feedback_helper.php';

$questions = [
    ["sentence" => "The birds ___ in the tree every morning.", "correct" => "sing", "options" => ["sing", "sings", "sung", "singing"], "tip" => "'Birds' is plural, so we use 'sing' without an s."],
    ["sentence" => "He ___ his homework after school.", "correct" => "does", "options" => ["do", "does", "doing", "done"], "tip" => "'He' is singular, so we use 'does'."],
    ["sentence" => "We ___ at home last night.", "correct" => "were", "options" => ["was", "were", "is", "be"], "tip" => "'Last night' is in the past, and 'we' goes with 'were'."],
    ["sentence" => "She ___ a beautiful song yesterday.", "correct" => "sang", "options" => ["sing", "sang", "sung", "singing"], "tip" => "'Yesterday' tells us it is in the past, so we use 'sang'."],
    ["sentence" => "I ___ a glass of water every day.", "correct" => "drink", "options" => ["drink", "drinks", "drank", "drinking"], "tip" => "'I' goes with 'drink' in the present."],
    ["sentence" => "The sun ___ in the east.", "correct" => "rises", "options" => ["rise", "rises", "rising", "rose"], "tip" => "'The sun' is singular, so we use 'rises'."],
    ["sentence" => "There ___ two apples on the table.", "correct" => "are", "options" => ["is", "are", "am", "be"], "tip" => "'Two apples' is plural, so we use 'are'."],
    ["sentence" => "He is ___ than his brother.", "correct" => "taller", "options" => ["tall", "taller", "tallest", "more tall"], "tip" => "We use 'taller' when comparing two people."],
    ["sentence" => "She is the ___ girl in the class.", "correct" => "smartest", "options" => ["smart", "smarter", "smartest", "most smart"], "tip" => "We use 'smartest' when comparing one person with the whole group."],
    ["sentence" => "I will ___ you tomorrow.", "correct" => "call", "options" => ["call", "called", "calls", "calling"], "tip" => "After 'will' we use the basic form of the verb."],
    ["sentence" => "The dog ___ the ball and brought it back.", "correct" => "caught", "options" => ["catch", "caught", "catched", "catching"], "tip" => "'Caught' is the past tense of 'catch'."],
    ["sentence" => "Please give the book to ___.", "correct" => "me", "options" => ["I", "me", "my", "mine"], "tip" => "After 'to' we use 'me', not 'I'."],
    ["sentence" => "We ___ lunch at one o'clock every day.", "correct" => "eat", "options" => ["eat", "eats", "ate", "eating"], "tip" => "'We' goes with 'eat' in the present."],
    ["sentence" => "I saw ___ elephant at the zoo.", "correct" => "an", "options" => ["a", "an", "two", "many"], "tip" => "We use 'an' before words that start with a vowel sound."],
    [
        "sentence" => "The dog ___ very fast.",
        "correct" => "runs",
        "options" => ["runs", "run", "running", "runned"],
        "tip" => "We use 'runs' because 'the dog' is singular."
    ],
    [
        "sentence" => "She ___ to school every morning.",
        "correct" => "walks",
        "options" => ["walk", "walks", "walking", "walked"],
        "tip" => "We use 'walks' with 'she' when talking about something she does regularly."
    ],
    [
        "sentence" => "The children ___ playing outside.",
        "correct" => "are",
        "options" => ["is", "am", "are", "be"],
        "tip" => "'Children' is plural, so we use 'are'."
    ],
    [
        "sentence" => "I ___ a red bicycle.",
        "correct" => "have",
        "options" => ["has", "have", "having", "had"],
        "tip" => "We normally use 'have' with 'I' in the present tense."
    ],
    [
        "sentence" => "My mother ___ delicious food.",
        "correct" => "cooks",
        "options" => ["cook", "cooks", "cooking", "cooked"],
        "tip" => "'My mother' is singular, so we use 'cooks'."
    ],
    [
        "sentence" => "We ___ happy today.",
        "correct" => "are",
        "options" => ["is", "are", "am", "be"],
        "tip" => "We use 'are' with 'we'."
    ],
    [
        "sentence" => "The cat is ___ on the chair.",
        "correct" => "sleeping",
        "options" => ["sleep", "sleeps", "sleeping", "slept"],
        "tip" => "After 'is', we can use 'sleeping' to describe what the cat is doing now."
    ],
    [
        "sentence" => "Yesterday, we ___ football.",
        "correct" => "played",
        "options" => ["play", "plays", "played", "playing"],
        "tip" => "'Yesterday' tells us the action happened in the past, so we use 'played'."
    ]
];

if (!isset($_SESSION["sentence_score"])) {
    $_SESSION["sentence_score"] = 0;
}

if (!isset($_SESSION["sentence_total"])) {
    $_SESSION["sentence_total"] = 0;
}

$message = "";
$messageClass = "";
$explanation = "";
$api_feedback ="";

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["answer"])
) {

    $answer = $_POST["answer"];

    $correctAnswer =
        $_SESSION["sentence_answer"] ?? "";

    $previousTip =
        $_SESSION["sentence_tip"] ?? "";

    $seconds = microtime(true) - ($_SESSION['q_start'] ?? microtime(true));
    log_attempt('english', 'sentence', $answer === $correctAnswer, $seconds);

    $_SESSION["sentence_total"]++;

    if ($answer === $correctAnswer) {

        $_SESSION["sentence_score"]++;

        $message = "Correct! Great job!";
        $messageClass = "correct";

        $explanation = $previousTip;

    } else {

        $message =
            "Not quite. The correct answer was: "
            . htmlspecialchars($correctAnswer);

        $messageClass = "wrong";

        $explanation = $previousTip;
    }
     $_SESSION['sentence_attempts'] =($_SESSION['sentence_attempts']?? 0)+1;
    $api_feedback = get_api_feedback(
        'sentence',
        $answer === $correctAnswer,
        $_SESSION['sentence_attempts'],
        $answer
    );
    if($answer === $correctAnswer){
        $_SESSION['sentence_attempts'] =0;
    }
}



$question =
    $questions[array_rand($questions)];

$_SESSION["sentence_answer"] =
    $question["correct"];

$_SESSION['q_start'] = microtime(true);
$_SESSION["sentence_tip"] =
    $question["tip"];

$round_label = round_gate("sentence", $message, $api_feedback); if (isset($_GET["finish_round"])) { round_finish("sentence", $message, $api_feedback); } $options = $question["options"];

shuffle($options);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>LearnQuest - Complete the Sentence</title>

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
    max-width: 600px;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    color: #6A1B9A;
    font-weight: bold;
}

.card {
    background: white;
    border: 4px solid #03A9F4;
    border-radius: 25px;
    padding: 30px;
    box-shadow: 7px 7px 0 #0277BD;
}

.icon {
    text-align: center;
    font-size: 60px;
}

h1 {
    text-align: center;
    color: #03A9F4;
    margin-bottom: 10px;
}

.instructions {
    text-align: center;
    color: #5D4037;
    font-weight: bold;
    margin-bottom: 20px;
}

.score {
    background: #E1F5FE;
    border: 2px solid #03A9F4;
    border-radius: 12px;
    padding: 10px;
    text-align: center;
    font-weight: bold;
    color: #0277BD;
    margin-bottom: 20px;
}

.question {
    background: #FFF9C4;
    border: 3px solid #FFD600;
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: #5D4037;
    margin-bottom: 20px;
}

.answers {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.answer {
    border: none;
    border-radius: 12px;
    padding: 15px;
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
    margin-bottom: 15px;
    text-align: center;
    font-weight: bold;
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

.tip {
    background: #FFF9C4;
    border-left: 6px solid #FFD600;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    color: #5D4037;
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

</style>

</head>

<body>

<div class="container">

    <a href="englishgames.php" class="back">&larr; English Games</a>

    <div class="card">

        <div class="icon">&#128221;</div>

        <h1>
            Complete the Sentence
        </h1>

        <p class="instructions">
            Choose the correct word to complete the sentence.
        </p>


        <!-- SCORE -->

        <div class="score">

            Score:

            <?php
            echo $_SESSION["sentence_score"];
            ?>

            /

            <?php
            echo $_SESSION["sentence_total"];
            ?>

        </div>


        

        <?php if ($message): ?>

            <div class="feedback <?php echo $messageClass; ?>">

                <?php echo $message; ?>

            </div>

        <?php endif; ?>


        

        <?php if ($explanation): ?>

            <div class="tip">

                 <strong>&#128161; Learning Tip:</strong>

                <?php
                echo htmlspecialchars($explanation);
                ?>

            </div>

        <?php endif; ?>


        

        <?php if (!empty($round_label)): ?><div style="text-align:center;font-weight:800;color:#6A1B9A;background:#F3E5F5;border-radius:12px;padding:8px;margin-bottom:12px"><?= htmlspecialchars($round_label) ?></div><?php endif; ?><div class="question">

            <?php
            echo htmlspecialchars(
                $question["sentence"]
            );
            ?>

        </div>


        

        <form method="POST">

            <div class="answers">

                <?php foreach ($options as $option): ?>

                    <button
                        type="submit"
                        name="answer"
                        value="<?php echo htmlspecialchars($option); ?>"
                        class="answer"
                    >

                        <?php
                        echo htmlspecialchars($option);
                        ?>

                    </button>

                <?php endforeach; ?>

            </div>

        </form>


        <a href="sentence.php" class="skip">Skip Question &rarr;</a><div style="display:flex;gap:10px;margin-top:14px"><a href="dashboard.php" style="flex:1;text-align:center;text-decoration:none;background:#00BCD4;color:#fff;padding:11px;border-radius:12px;font-weight:700">Menu</a><a href="sentence.php?finish_round=1" style="flex:1;text-align:center;text-decoration:none;background:#E53935;color:#fff;padding:11px;border-radius:12px;font-weight:700">Finish &amp; See Score</a></div>

    </div>

</div>

<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var q=t(".equation");q=q.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=\s*\?/," equals what");var sc=document.querySelector(".scrambled");if(sc){q="Unscramble these letters: "+sc.innerText.trim().split("").join(", ");}var o=[];document.querySelectorAll(".answers button, #quizForm button").forEach(function(x){o.push(x.innerText.trim());});var parts=[t(".feedback, .correct, .wrong"),t(".ai-feedback"),t(".tip-box, .tip"),q||t(".question"),t(".hint")];if(o.length){parts.push("The choices are: "+o.join(", "));}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script></body>

</html>
