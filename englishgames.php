<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnQuest - English Games</title>

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
            padding: 30px 20px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: auto;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6A1B9A;
            font-weight: bold;
            text-decoration: none;
            background: white;
            padding: 10px 18px;
            border-radius: 12px;
        }

        .back-link:hover {
            transform: scale(1.05);
        }

        .logo {
            text-align: center;
            font-size: 65px;
        }

        h1 {
            text-align: center;
            font-size: 2.5rem;
            color: #FF4081;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #5D4037;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .games {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .game-card {
            background: white;
            padding: 30px 20px;
            border-radius: 22px;
            text-align: center;
            box-shadow: 6px 6px 0 #C2185B;
            border: 4px solid #FF4081;
            transition: 0.2s;
        }

        .game-card:hover {
            transform: translateY(-5px);
        }

        .game-icon {
            font-size: 55px;
            margin-bottom: 10px;
        }

        .game-card h2 {
            color: #FF4081;
            margin-bottom: 10px;
        }

        .game-card p {
            color: #5D4037;
            margin-bottom: 20px;
            min-height: 45px;
        }

        .play-btn {
            display: inline-block;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 12px;
            color: white;
            font-weight: bold;
            font-size: 1rem;
        }

        .spelling {
            background: #FF4081;
        }

        .sentence {
            background: #03A9F4;
        }

        .opposites {
            background: #4CAF50;
        }

        .unscramble {
            background: #FF9800;
        }

        @media (max-width: 650px) {
            .games {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <a href="dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

    <div class="logo">LearnQuest</div>

    <h1>Fun English Games</h1>

    <p class="subtitle">
        Learn English while having fun!
    </p>

    <div class="games">

        
        <div class="game-card">

            <div class="game-icon">🔤</div>

            <h2>Spelling Challenge</h2>

            <p>
                Can you choose the correctly spelled word?
            </p>

            <a href="spelling.php"
               class="play-btn spelling">
                PLAY 
            </a>

        </div>


        
        <div class="game-card">

            <div class="game-icon"></div>

            <h2>Complete the Sentence</h2>

            <p>
                Choose the correct word to complete the sentence.
            </p>

            <a href="sentence.php"
               class="play-btn sentence">
                PLAY 
            </a>

        </div>


        <!-- OPPOSITES -->
        <div class="game-card">

            <div class="game-icon">🔄</div>

            <h2>Opposites Game</h2>

            <p>
                Find the word that means the opposite.
            </p>

            <a href="opposites.php"
               class="play-btn opposites">
                PLAY 
            </a>

        </div>


        
        <div class="game-card">

            <div class="game-icon">🔀</div>

            <h2>Unscramble the Word</h2>

            <p>
                Put the mixed-up letters in the correct order.
            </p>

            <a href="unscramble.php"
               class="play-btn unscramble">
                PLAY 
            </a>

        </div>

    </div>

</div>

</body>
</html>