<?php

session_start();
require_once __DIR__ . '/round_helper.php';
if (empty($_SESSION["parent_id"]) && empty($_SESSION["child_login"]) && empty($_SESSION["guest"])) { header("Location: login2.php"); exit; }
if (empty($_SESSION["child_id"]) && empty($_SESSION["guest"])) { header("Location: select_child.php"); exit; }
require_once 'progress_helper.php';
require_once __DIR__ . '/quiz_helper.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);





if (isset($_POST['operation'])) {
    $_SESSION['operation'] = $_POST['operation'];
    $_SESSION['calc_score'] = 0;
    $_SESSION['calc_right'] = 0;
    $_SESSION['calc_done'] = 0;

}
    $_SESSION['calc_score'] = $_SESSION['calc_score'] ?? 0;
    $_SESSION['calc_right'] = $_SESSION['calc_right'] ?? 0;
    $_SESSION['calc_done'] = $_SESSION['calc_done'] ?? 0;


$operation = $_SESSION['operation'] ?? 'addition';
if (in_array($operation, ['factors', 'fractions', 'squareroots'], true)) { require __DIR__ . '/topics_game.php'; exit; }

$skip_msg = ""; $skip_ok = false; if (isset($_GET["skip"]) && isset($_SESSION["current_q"])) { $sk = (int) ($_SESSION["skip_tries"] ?? 0); if ($sk < 2) { $_SESSION["skip_tries"] = $sk + 1; $nudges = ["You can do this! Take a breath, look at the numbers again and pick an answer. Every try makes your brain stronger.", "So close to cracking it! Even grown-ups make mistakes. Try one more time. If it still feels too hard, you can skip next."]; $skip_msg = $nudges[$sk]; } else { $skip_ok = true; } } $generate_new = !isset($_SESSION['current_q']) || $skip_ok || isset($_POST['operation']);
$show_feedback = false;


$child_id = $_SESSION['child_id'] ?? "1";
$child_age = $_SESSION['child_age'] ?? 8;



$message = "";
$message_class = "";
$show_correct = false;
$correct_choice = 0;
$tip = "";
$api_feedback = "";




if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['user_answer'])
) {

    $user_choice = (int) $_POST['user_answer'];

    // Get the correct answer from the previous question
    $correct_choice =
        (int) ($_SESSION['correct_answer'] ?? 0);

    // Compare answers
    $is_correct =
        ($user_choice === $correct_choice);
 $seconds = microtime(true) - ($_SESSION['q_start'] ?? microtime(true));
    log_attempt('math', $operation, $is_correct, $seconds);
    $q = $_SESSION['current_q'] ?? [];
    save_quiz_attempt($_SESSION['child_id'], $operation, $q['operator'] ?? ($_SESSION['last_operator'] ?? ''), $q['num1'] ?? 0, $q['num2'] ?? 0, $correct_choice, $user_choice, $is_correct);

    /* =====================================================
       ATTEMPT COUNTER
       ===================================================== */

    if (!isset($_SESSION['attempts_count'])) {
        $_SESSION['attempts_count'] = 0;
    }

    $_SESSION['attempts_count']++;

    $attempts_count =
        $_SESSION['attempts_count'];


    /* =====================================================
       NORMAL FEEDBACK
       ===================================================== */

     if ($is_correct) {
        $points = max(1,4 - $attempts_count);
    $_SESSION['calc_score'] += $points;
    $_SESSION['calc_right']++;
    $_SESSION['calc_done']++;



        $message = "Correct! Well done. +" . $points . " points";
        $message_class = "correct";
        $show_feedback = true;
        $generate_new = true;
        $_SESSION['attempts_count'] = 0;

    } else {

        $message = "The answer is wrong. You chose <strong>"
                 . $user_choice . "</strong>.";
        $message_class = "wrong";

        $last_op = $_SESSION['last_operator'] ?? '+';

        $tips = [
            '+' => "Tip: Addition means combining two numbers together.",
            '-' => "Tip: Subtraction means taking one number away from another.",
            '*' => "Tip: Multiplication means adding the same number multiple times.",
            '/' => "Tip: Division means splitting a number into equal groups."
        ];

        $tip = $tips[$last_op] ?? "";

        if ($attempts_count >= 3) {
            $_SESSION['calc_done']++;
            $show_correct = true;
            $show_feedback = true;
            $generate_new = true;
            $_SESSION['attempts_count'] = 0;
        }
    }

    /* =====================================================
       5. FASTAPI FEEDBACK
       ===================================================== */
if ($show_feedback) {
    $api_url =
        "http://127.0.0.1:8000/api/v1/feedback";


    $data = [

        "child_id" =>
            (string) $child_id,

        "age" =>
            (int) $child_age,

        "subject" =>
            "Mathematics",

        "topic" =>
            $operation,

        "is_correct" =>
            $is_correct,

        "attempts_count" =>
            $attempts_count,

        "selected_answer" =>
            (string) $user_choice

    ];


    $json_data =
        json_encode($data);


    /* =====================================================
       SEND DATA TO FASTAPI
       ===================================================== */

    if (function_exists('curl_init')) {

        $ch =
            curl_init($api_url);


        curl_setopt_array(
            $ch,
            [

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_POST =>
                    true,

                CURLOPT_POSTFIELDS =>
                    $json_data,

                CURLOPT_HTTPHEADER =>
                    [
                        "Content-Type: application/json",
                        "Accept: application/json"
                    ],

                CURLOPT_CONNECTTIMEOUT =>
                    2,

                CURLOPT_TIMEOUT =>
                    5

            ]
        );


        $response =
            curl_exec($ch);


        /* Check for cURL errors */

        if ($response === false) {

            $api_feedback =
                "Feedback service is currently unavailable.";

        } else {

            $result =
                json_decode($response, true);


            if (
                isset(
                    $result['feedback']['message']
                )
            ) {

                $api_feedback =
                    $result['feedback']['message'];

            } else {

                $api_feedback =
                    "Feedback could not be generated.";
            }
        }


        curl_close($ch);

    } else {

        $api_feedback =
            "Feedback service is currently unavailable.";
    }
}}
$round_label = round_gate("math", $message, $api_feedback); if ($generate_new) {
    
$_SESSION['q_start'] = microtime(true); $_SESSION["skip_tries"] = 0;
/* =========================================================
   6. CHOOSE THE OPERATOR
   ========================================================= */

if ($operation === 'addition') {

    $operator = '+';

} elseif ($operation === 'subtraction') {

    $operator = '-';

} elseif ($operation === 'multiplication') {

    $operator = '*';

} elseif ($operation === 'division') {

    $operator = '/';

} else {

    $all_ops =
        ['+', '-', '*', '/'];

    $operator =
        $all_ops[array_rand($all_ops)];
}


/* 
   7. GENERATE A NEW QUESTION
   */


/* ADDITION */

if ($operator === '+') {

    $num1 = rand(1, 100);

    $num2 = rand(1, 100);

    $correct =
        $num1 + $num2;
}


/* SUBTRACTION */

elseif ($operator === '-') {

    $num1 = rand(1, 100);

    $num2 = rand(1, $num1);

    $correct =
        $num1 - $num2;
}


/* MULTIPLICATION */

elseif ($operator === '*') {

    $num1 = rand(2, 12);

    $num2 = rand(2, 12);

    $correct =
        $num1 * $num2;
}


/* DIVISION */

else {

    $num2 = rand(2, 12);

    $num1 =
        $num2 * rand(1, 12);

    $correct =
        (int) ($num1 / $num2);
}


/* =========================================================
   8. SAVE THE NEW ANSWER
   ========================================================= */

$_SESSION['correct_answer'] =
    $correct;

$_SESSION['last_operator'] =
    $operator;


/* =========================================================
   9. CREATE MULTIPLE-CHOICE OPTIONS
   ========================================================= */

$spread =
    ($operator === '*') ? 20 : 10;


$options =
    [$correct];


while (count($options) < 4) {

    $wrong =
        $correct + rand(
            -$spread,
            $spread
        );


    if (
        $wrong !== $correct
        && $wrong >= 0
        && !in_array(
            $wrong,
            $options,
            true
        )
    ) {

        $options[] =
            $wrong;
    }
}


shuffle($options);
$_SESSION['attempts_count'] = 0;
 $_SESSION['current_q'] = compact('operator', 'num1', 'num2', 'correct', 'options');

} else {
    extract($_SESSION['current_q']);
}
if (isset($_GET["exit"])) { require_once __DIR__ . "/round_helper.php"; round_finish("math", $message, $api_feedback); }
if (isset($_GET['exit'])) {
    $final_score = (int) $_SESSION['calc_score'];
    $final_right = (int) $_SESSION['calc_right'];
    $final_done  = (int) $_SESSION['calc_done'];
    unset($_SESSION['current_q'], $_SESSION['attempts_count']);
    $_SESSION['calc_score'] = 0;
    $_SESSION['calc_right'] = 0;
    $_SESSION['calc_done']  = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Round complete</title>
    <link rel="stylesheet" href="calculation.css">
</head>
<body>
<div class="content"><div class="card">
    <h2>Well done!</h2>
    <div class="score-box">
        Score: <?php echo $final_score; ?> points<br>
        <?php echo $final_right; ?> of <?php echo $final_done; ?> questions right
    </div>
    <a href="dashboard.php" class="btn next-btn">Back to Menu</a>
</div></div>
<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var q=t(".equation");q=q.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=\s*\?/," equals what");var sc=document.querySelector(".scrambled");if(sc){q="Unscramble these letters: "+sc.innerText.trim().split("").join(", ");}var o=[];document.querySelectorAll(".answers button, #quizForm button").forEach(function(x){o.push(x.innerText.trim());});var parts=[t(".feedback, .correct, .wrong"),t(".ai-feedback"),t(".tip-box, .tip"),q||t(".question"),t(".hint")];if(o.length){parts.push("The choices are: "+o.join(", "));}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script></body>
</html>
<?php
    exit;
}

$signs = ['+' => '+', '-' => '-', '*' => '&times;', '/' => '&divide;'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest Mathematics Quiz</title>
    <link rel="stylesheet" href="calculation.css">
</head>
<body>
<div class="content">
    <div class="card">

        

        <?php if ($message): ?>
            <div class="<?php echo htmlspecialchars($message_class); ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($api_feedback): ?>
            <div class="ai-feedback"><strong>Learning Feedback:</strong><br><br><?php echo htmlspecialchars($api_feedback); ?></div>
        <?php endif; ?>

        <?php if ($show_correct): ?>
            <div class="correct-reveal">The correct answer was: <strong><?php echo $correct_choice; ?></strong></div>
        <?php endif; ?>

        <?php if ($tip): ?>
            <div class="tip-box"><?php echo htmlspecialchars($tip); ?></div>
        <?php endif; ?>

        <div class="score-box">
            Score: <?php echo (int) $_SESSION['calc_score']; ?> points
            &middot; <?php echo (int) $_SESSION['calc_right']; ?> of <?php echo (int) $_SESSION['calc_done']; ?> right
        </div>

        <?php if ($skip_msg): ?><div class="motivate">&#127775; <?= htmlspecialchars($skip_msg) ?></div><?php endif; ?><?php if (!empty($round_label)): ?><div style="text-align:center;font-weight:800;color:#6A1B9A;background:#F3E5F5;border-radius:12px;padding:8px;margin-bottom:12px"><?= htmlspecialchars($round_label) ?></div><?php endif; ?><h2>Solve the Equation</h2>
        <div class="equation"><?php echo $num1 . ' ' . $signs[$operator] . ' ' . $num2 . ' = ?'; ?></div>
        <br>

        <form id="quizForm" method="POST" action="calculation.php">
            <?php foreach ($options as $option): ?>
                <button type="submit" name="user_answer" value="<?php echo (int) $option; ?>" class="btn"><?php echo (int) $option; ?></button>
            <?php endforeach; ?>
        </form>
        <br>

        <div class="action-row"><a href="calculation.php?skip=1" class="btn next-btn"><?= ((int) ($_SESSION["skip_tries"] ?? 0)) >= 2 ? "Skip anyway" : "Skip Question" ?></a><a href="dashboard.php" class="btn menu-btn">Menu</a></div>
        <a href="calculation.php?exit=1" class="btn exit-btn">Finish &amp; See Score</a>

    </div>
</div>
<script>(function(){if(!("speechSynthesis" in window)){return;}var b=document.createElement("button");b.type="button";b.textContent="\uD83D\uDD0A Read aloud";b.style.cssText="position:fixed;right:14px;bottom:14px;z-index:60;border:none;border-radius:24px;background:#03A9F4;color:#fff;font-weight:800;padding:12px 16px;cursor:pointer;box-shadow:0 3px 8px rgba(0,0,0,.25)";function t(s){var e=document.querySelector(s);return e?e.innerText.replace(/\s+/g," ").trim():"";}b.onclick=function(){if(speechSynthesis.speaking){speechSynthesis.cancel();return;}var q=t(".equation");q=q.replace(/\u00D7/g," times ").replace(/\u00F7/g," divided by ").replace(/\+/g," plus ").replace(/ - /g," minus ").replace(/=\s*\?/," equals what");var sc=document.querySelector(".scrambled");if(sc){q="Unscramble these letters: "+sc.innerText.trim().split("").join(", ");}var o=[];document.querySelectorAll(".answers button, #quizForm button").forEach(function(x){o.push(x.innerText.trim());});var parts=[t(".feedback, .correct, .wrong"),t(".ai-feedback"),t(".tip-box, .tip"),q||t(".question"),t(".hint")];if(o.length){parts.push("The choices are: "+o.join(", "));}var u=new SpeechSynthesisUtterance(parts.filter(Boolean).join(". "));u.lang="en-ZA";u.rate=0.9;speechSynthesis.speak(u);};document.body.appendChild(b);})();</script></body>
</html>