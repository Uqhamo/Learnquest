<?php
session_start()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registerchild login</title>
</head>
<body>
    <h1>LearnQuest</h1>
    <form action="dashboard.php" method="post">
        <h2>Register</h2>
        <label>Enter your Name:</label><br>
        <input type="text" name="name"><br><br>

        <label>Enter your Surname:</label><br>
        <input type="text" name="surname"><br><br>

        <label>How old are you:</label><br>
        <input type="number" name = "age"><br><br>

        <label>Enter your Grade:</label><br>
        <input type ="number" name="grade"><br><br>

        <button type="submit" name="register">Click here</button>
    </form>
</body>
</html>