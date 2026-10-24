<?php
session_start();
if (empty($_SESSION['parent_id'])) { header('Location: login2.php'); exit; }
if (empty($_SESSION['child_id'])) { header('Location: select_child.php'); exit; }
require_once 'progress_helper.php';
require_once 'feedback_helper.php';

$questions = [
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
$message = "";
$api_feedback ="";
$messageClass = "";

if($_SERVER["REQUEST_METHOD"]=== "POST" && isset($_POST["answer"])){

$answer = $_POST["answer"];
$correctAnswer = $_SESSION["spelling_answer"] ?? "";
$seconds = microtime(true) - ($_SESSION['q_start'] ?? microtime(true));
log_attempt('english', 'spelling', $answer === $correctAnswer, $seconds);

  if ($answer === $correctAnswer) {
        $message = "Correct! Great spelling!";
        $messageClass = "correct";
    } else {
        $message = "Not quite. The correct spelling was: "
                 . htmlspecialchars($correctAnswer);
        $messageClass = "wrong";
    }

    $_SESSION['spelling_attempts'] = ($_SESSION['spelling_attempts'] ?? 0) + 1;

    $api_feedback = get_api_feedback(
        'spelling',
        $answer === $correctAnswer,
        $_SESSION['spelling_attempts'],
        $answer
    );

    if ($answer === $correctAnswer) {
        $_SESSION['spelling_attempts'] = 0;
    }
}

$question = $questions[array_rand($questions)];

$_SESSION["spelling_answer"] = $question["correct"];
$_SESSION['q_start'] = microtime(true);

$options = $question["options"];
shuffle($options);

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

    .wrong, .wrong-fix { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; }</style>

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
        <div class="question">
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

        <a href="spelling.php" class="skip">Skip Word &rarr;</a>

    </div>

</div>

</body>
</html>
