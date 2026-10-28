<?php 
session_start(); require_once __DIR__ . "/database.php"; $error = ""; if ($_SERVER["REQUEST_METHOD"] === "POST") { $u = trim($_POST["user"] ?? ""); $st = $pdo->prepare("SELECT id, password_hash FROM parents WHERE username = ? OR email = ?"); $st->execute([$u, $u]); $p = $st->fetch(); if ($p && password_verify($_POST["pass"] ?? "", $p["password_hash"])) { session_regenerate_id(true); $_SESSION["parent_id"] = (int) $p["id"]; unset($_SESSION["child_id"], $_SESSION["child_age"]); header("Location: select_child.php"); exit; } $error = "Wrong username or password."; }

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
            overflow: auto;
            position: relative;
        }
 
        
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
 
        
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 36px 32px;
            width: 100%;
            max-width: 400px;
            
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
 
        
        .emoji-row {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
            letter-spacing: 6px;
        }
        </style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
    <div class ="brand">
    <h1>LearnQuest</h1>
    <h2>Welcome Back!</h2>
    </div>
    <div class="card">
        <h2>Login</h2><?php if ($error): ?><p style="color:#C62828;text-align:center;font-weight:bold"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

    <form action="" method="post">
        <div class="field">
        <label for="user">Email:</label><br>
        <input type ="text" name = "user" id="user" placeholder="Enter your email" required><br><br>
        </div>
        <div class="field">
        <label for="pass">Password:</label><br>
        <input type="password" name="pass" id="pass" placeholder="Enter your password" required><br><br></div>
        <button type="submit" name="login" class="btn-login">Login</button><br><br>
    </form>
        <p class="signup-text" style="margin-top:0;margin-bottom:6px"><a href="forgot_password.php" style="color:#FF4081">Forgot password?</a></p><p class="signup-text" style="margin-top:0;margin-bottom:6px">Are you a teacher?<a href="teacher_login.php">Teacher log in</a></p><p class="signup-text">Not yet a member?<a href="register.php">Sign Up</a></p>
    </div>
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>
