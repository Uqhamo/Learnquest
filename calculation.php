<?php

session_start();
if (empty($_SESSION['parent_id'])) { header('Location: login2.php'); exit; }
if (empty($_SESSION['child_id'])) { header('Location: select_child.php'); exit; }
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

$generate_new = !isset($_SESSION['current_q']) || isset($_GET['skip']) || isset($_POST['operation']);
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
if ($generate_new) {
    
$_SESSION['q_start'] = microtime(true);
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
</body>
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

        <h2>Solve the Equation</h2>
        <div class="equation"><?php echo $num1 . ' ' . $signs[$operator] . ' ' . $num2 . ' = ?'; ?></div>
        <br>

        <form id="quizForm" method="POST" action="calculation.php">
            <?php foreach ($options as $option): ?>
                <button type="submit" name="user_answer" value="<?php echo (int) $option; ?>" class="btn"><?php echo (int) $option; ?></button>
            <?php endforeach; ?>
        </form>
        <br>

        <div class="action-row"><a href="calculation.php?skip=1" class="btn next-btn">Skip Question</a><a href="dashboard.php" class="btn menu-btn">Menu</a></div>
        <a href="calculation.php?exit=1" class="btn exit-btn">Finish &amp; See Score</a>

    </div>
</div>
</body>
</html>