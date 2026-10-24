<?php session_start(); require_once __DIR__ . "/database.php"; $error = ""; $success = ""; if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) { $name = trim($_POST["name"] ?? ""); $surname = trim($_POST["surname"] ?? ""); $email = trim($_POST["email"] ?? ""); $pass = $_POST["pass"] ?? ""; $confirm_pass = $_POST["confirm_pass"] ?? ""; if ($name === "" || $surname === "" || $email === "" || $pass === "") { $error = "Please fill in all fields."; } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Please enter a valid email address."; } elseif (strlen($pass) < 6) { $error = "Password must be at least 6 characters."; } elseif ($pass !== $confirm_pass) { $error = "Passwords do not match."; } else { $check = $pdo->prepare("SELECT 1 FROM parents WHERE email = ? OR username = ?"); $check->execute([$email, $email]); if ($check->fetch()) { $error = "That email is already registered."; } else { $ins = $pdo->prepare("INSERT INTO parents (name, surname, username, email, password_hash) VALUES (?, ?, ?, ?, ?)"); $ins->execute([$name, $surname, $email, $email, password_hash($pass, PASSWORD_DEFAULT)]); session_regenerate_id(true); $_SESSION["parent_id"] = (int) $pdo->lastInsertId(); header("Location: registerchild.php"); exit; } } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Parent</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
 
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #FFF176;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: auto;
            position: relative;
        }
 
        
 
        
        .star {
            position: fixed;
            font-size: 1.4rem;
            animation: float 4s ease-in-out infinite;
            opacity: 0.4;
            z-index: 0;
            pointer-events: none;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-14px) rotate(15deg); }
        }
 
    
        .content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
 
        
        .logo {
            font-size: 3.2rem;
            animation: bounce 1.8s ease-in-out infinite;
            display: block;
            text-align: center;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }
 
        h1 {
            font-size: 2.2rem;
            font-weight: 900;
            background: linear-gradient(90deg, #FF4081, #FF6D00, #FFD600, #00C853, #00BCD4, #AA00FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 2px;
            filter: drop-shadow(2px 2px 0 rgba(0,0,0,0.10));
            text-align: center;
            margin-bottom: 4px;
        }
 
        .tagline {
            font-size: 0.95rem;
            color: #5D4037;
            font-weight: 600;
            text-align: center;
            margin-bottom: 22px;
        }
 
        
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 36px 32px;
            width: 100%;
            border: 4px solid #00BCD4;
            box-shadow: 6px 6px 0 #0097A7, 12px 12px 0 rgba(0,151,167,0.25);
        }
 
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }
 
        .card-header .badge {
            font-size: 2rem;
        }
 
        .card-header h2 {
            font-size: 1.4rem;
            font-weight: 900;
            color: #00BCD4;
        }
 
        .card-header p {
            font-size: 0.8rem;
            color: #aaa;
            font-weight: 500;
        }
 
        
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }
 
        .field {
            margin-bottom: 16px;
        }
 
        .field label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #5D4037;
            margin-bottom: 6px;
        }
 
        .field input {
            width: 100%;
            padding: 12px 14px;
            border: 3px solid #FFD600;
            border-radius: 12px;
            font-size: 0.97rem;
            color: #1a1a2e;
            background: #FFFDE7;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
 
        .field input:focus {
            border-color: #00BCD4;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0,188,212,0.15);
        }
 
        
        .btn-register {
            width: 100%;
            padding: 15px;
            background: #00BCD4;
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 800;
            cursor: pointer;
            letter-spacing: 0.5px;
            border-bottom: 5px solid #0097A7;
            margin-top: 6px;
            transition: transform 0.1s, filter 0.1s;
        }
 
        .btn-register:hover  { filter: brightness(1.08); transform: translateY(-2px); }
        .btn-register:active { transform: translateY(3px); border-bottom: 2px solid #0097A7; }
 
        
        .signin-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #888;
            font-weight: 500;
        }
 
        .signin-text a {
            color: #FF4081;
            font-weight: 800;
            text-decoration: none;
            margin-left: 4px;
        }
 
        .signin-text a:hover { text-decoration: underline; }
 
        
        .hint {
            font-size: 0.75rem;
            color: #aaa;
            margin-top: 4px;
        }
 
        @media (max-width: 400px) {
            .field-row { grid-template-columns: 1fr; }
            .card { padding: 28px 20px; }
        }
    </style>
    </style>
</head>
<body>
    <div class="content">
    <h1>LearnQuest</h1>
    <p class="tagline">Create your parent account</p>
    <div class="card">
        <div class="card-header">
            <div>
<h2>Parent Register</h2>
<p>Set up your account to get started</p>
            </div>
        </div>
    
    <form action="" method="post">
        <div class="field-row">
            <div class="field">
        <label for="name">Name:</label><br>
        <input type="text" name="name" id="name" placeholder="First name" required><br><br>
            </div>
            <div class="field">
        <label for="surname">Surname:</label><br>
        <input type="text" name="surname" id="surname" placeholder="Last Name" required><br><br>
            </div>
        </div>
<div class="field">
        <label for="email">Email:</label><br><input type="email" name="email" id="email" placeholder="Your email address" required><br><br></div><div class="field"><label for="pass">Password:</label><br>
        <input type="password" name="pass" id="pass" placeholder="Create a password" required><br><br>
        <p class="hint">Use at least 6 characters</p>
</div>
<div class="field">
        <label for="confirm_pass">Confirm password:</label><br>
        <input type="password" name="confirm_pass" id="confirm_pass" placeholder="Reapeat your password" required><br><br>
</div>

        <button type="submit" name="register" class="btn-register">Create Account</button>
    </form>

        <p class="signin-text">Already a member ?<a href="login2.php">Sign in</a></p>

    </div>
    </div>
</body>
</html>