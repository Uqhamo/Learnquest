<?php 
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
</head>
<body>
    <?php
    ?>
    <h1>LearnQuest</h1>
    <h2>Welcome Back!</h2>
    <form action="login.php" method="post">
        <h2>Login</h2>
        <label>Username:</label><br>
        <input type ="text" name = "user"><br><br>
        <label>Password:</label><br>
        <input type="password" name="pass"><br><br>
        <button type="submit" name="login">Login</button><br><br>
        <p>Not yet a member?<a href="register.php">Sign Up</a></p>
    </form>
</body>
</html>