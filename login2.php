<?php 
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
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
            overflow: hidden;
            position: relative;
        }
 
        /* Fun floating shapes in background */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: 0.25;
            z-index: 0;
        }
 
        body::before {
            width: 320px;
            height: 320px;
            background: #FF4081;
            top: -80px;
            left: -80px;
        }
 
        body::after {
            width: 280px;
            height: 280px;
            background: #00BCD4;
            bottom: -60px;
            right: -60px;
        }
 
        /* Extra decorative blobs */
        .blob1, .blob2, .blob3 {
            position: fixed;
            border-radius: 50%;
            opacity: 0.18;
            z-index: 0;
        }
 
        .blob1 {
            width: 180px; height: 180px;
            background: #76FF03;
            bottom: 60px; left: -40px;
        }
 
        .blob2 {
            width: 140px; height: 140px;
            background: #FF6D00;
            top: 40px; right: -20px;
        }
 
        .blob3 {
            width: 100px; height: 100px;
            background: #AA00FF;
            top: 50%; left: 5%;
        }
 
        /* Stars / stars decoration */
        .stars {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }
 
        .star {
            position: absolute;
            font-size: 1.4rem;
            animation: float 4s ease-in-out infinite;
            opacity: 0.5;
        }
 
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-14px) rotate(15deg); }
        }
 
        /* Brand */
        .brand {
            text-align: center;
            margin-bottom: 22px;
            position: relative;
            z-index: 1;
        }
 
        .brand .logo {
            font-size: 3.4rem;
            display: block;
            animation: bounce 1.6s ease-in-out infinite;
        }
 
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }
 
        .brand h1 {
            font-size: 2.4rem;
            font-weight: 900;
            /* Rainbow text */
            background: linear-gradient(90deg, #FF4081, #FF6D00, #FFD600, #00C853, #00BCD4, #AA00FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 2px;
            text-shadow: none;
            filter: drop-shadow(2px 2px 0px rgba(0,0,0,0.15));
        }
 
        .brand p {
            color: #5D4037;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 4px;
        }
 
        /* Card */
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 36px 32px;
            width: 100%;
            max-width: 400px;
            /* Thick fun border */
            border: 4px solid #FF4081;
            box-shadow:
                6px 6px 0px #FF6D00,
                12px 12px 0px #FFD600;
            position: relative;
            z-index: 1;
        }
 
        .card h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #FF4081;
            text-align: center;
            margin-bottom: 24px;
        }
 
        /* Fields */
        .field {
            margin-bottom: 18px;
        }
 
        .field label {
            display: block;
            font-size: 0.9rem;
            font-weight: 700;
            color: #5D4037;
            margin-bottom: 6px;
        }
 
        .field input {
            width: 100%;
            padding: 13px 16px;
            border: 3px solid #FFD600;
            border-radius: 12px;
            font-size: 1rem;
            color: #1a1a2e;
            background: #FFFDE7;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
 
        .field input:focus {
            border-color: #FF4081;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255,64,129,0.15);
        }
 
        /* Login button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: #FF4081;
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            margin-top: 6px;
            letter-spacing: 1px;
            border-bottom: 5px solid #C2185B;
            transition: transform 0.1s, border-bottom 0.1s;
        }
 
        .btn-login:hover {
            background: #F50057;
            transform: translateY(-2px);
        }
 
        .btn-login:active {
            transform: translateY(3px);
            border-bottom: 2px solid #C2185B;
        }
 
        /* Sign up */
        .signup-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.92rem;
            color: #888;
            font-weight: 500;
        }
 
        .signup-text a {
            color: #00BCD4;
            font-weight: 800;
            text-decoration: none;
            margin-left: 4px;
            font-size: 1rem;
        }
 
        .signup-text a:hover {
            color: #0097A7;
            text-decoration: underline;
        }
 
        /* Fun emoji row */
        .emoji-row {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
            letter-spacing: 6px;
        }
        </style>
</head>
<body>
    <div class ="brand">
    <h1>LearnQuest</h1>
    <h2>Welcome Back!</h2>
    </div>
    <div class="card">
        <h2>Login</h2>

    <form action="login.php" method="post">
        <div class="field">
        <label for="user">Username:</label><br>
        <input type ="text" name = "user" id="user" placeholder="Enter your username" required><br><br>
        </div>
        <div class="field">
        <label for="pass">Password:</label><br>
        <input type="password" name="pass" id="pass" placeholder="Enter your password" required><br><br></div>
        <button type="submit" name="login" class="btn-login">Login</button><br><br>
    </form>
        <p class="signup-text">Not yet a member?<a href="register.php">Sign Up</a></p>
    </div>
</body>
</html>