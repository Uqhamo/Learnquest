<?php
session_start()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DashBoard</title>
</head>
<body>
    <header>
        <h2>LearnQuest</h2>
        <nav>
            <a href="">Profile</a>
            <a href =""> For Parent</a>
            <a href="">Progress </a>
        </nav>
    </header>
    <main>
        <h2>Time to Play & Learn</h2>
        <p> Pick an activity to get started!</p>
        <form action="calculation.php" method="post">
            <div class = "card-grid">
        <button type="submit" name="operation" value="addition">Addition</button><br><br>
        <button type="submit" name="operation" value="subtration">Subtation</button><br><br>
        <button type="submit" name="operation">Fun Games</button>
            </div>
        </form>

    </main>
</body>
</html>