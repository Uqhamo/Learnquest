<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Page</title>
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
 
       
        
        .star {
            position: fixed;
            font-size: 1.5rem;
            animation: float 4s ease-in-out infinite;
            opacity: 0.45;
            z-index: 0;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-14px) rotate(15deg); }
        }
 
        
        .content { position: relative; z-index: 1; text-align: center; width: 100%; max-width: 480px; }
 
        
        .logo {
            font-size: 4rem;
            display: block;
            animation: bounce 1.8s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }
 
        h1 {
            font-size: 2.8rem;
            font-weight: 900;
            background: linear-gradient(90deg, #FF4081, #FF6D00, #FFD600, #00C853, #00BCD4, #AA00FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 2px;
            filter: drop-shadow(2px 2px 0 rgba(0,0,0,0.12));
            margin-bottom: 6px;
        }
 
        .tagline {
            font-size: 1.05rem;
            color: #5D4037;
            font-weight: 600;
            margin-bottom: 10px;
        }
 
        .emoji-row {
            font-size: 1.6rem;
            letter-spacing: 8px;
            margin-bottom: 36px;
        }
 
        
        .who {
            font-size: 1.2rem;
            font-weight: 800;
            color: #5D4037;
            margin-bottom: 24px;
        }
 
        
        .card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
        }
 
        .card {
            background: #fff;
            border-radius: 22px;
            padding: 32px 16px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: transform 0.18s, box-shadow 0.18s;
            text-decoration: none;
        }
 
    
        .card.parent {
            border: 4px solid #00BCD4;
            box-shadow: 5px 5px 0 #0097A7;
        }
        .card.parent:hover {
            transform: translateY(-6px);
            box-shadow: 8px 10px 0 #0097A7;
        }
 
        
        .card.child {
            border: 4px solid #FF4081;
            box-shadow: 5px 5px 0 #C2185B;
        }
        .card.child:hover {
            transform: translateY(-6px);
            box-shadow: 8px 10px 0 #C2185B;
        }
 
        .card .card-icon {
            font-size: 3.4rem;
            animation: bounce 2s ease-in-out infinite;
        }
 
        .card.child .card-icon { animation-delay: 0.4s; }
 
        .card .card-title {
            font-size: 1.1rem;
            font-weight: 900;
            color: #1a1a2e;
        }
 
        .card .card-desc {
            font-size: 0.8rem;
            color: #888;
            font-weight: 500;
            text-align: center;
            line-height: 1.4;
        }
 
    
        .card-btn {
            width: 100%;
            padding: 13px 10px;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: transform 0.1s, filter 0.1s;
            margin-top: 4px;
        }
 
        .card-btn:hover  { filter: brightness(1.08); transform: translateY(-2px); }
        .card-btn:active { transform: translateY(2px); }
 
        .btn-parent {
            background: #00BCD4;
            color: #fff;
            border-bottom: 4px solid #0097A7;
        }
 
        .btn-child {
            background: #FF4081;
            color: #fff;
            border-bottom: 4px solid #C2185B;
        }
 
        
        .login-link {
            margin-top: 32px;
            font-size: 0.92rem;
            color: #5D4037;
            font-weight: 500;
        }
 
        .login-link a {
            color: #AA00FF;
            font-weight: 800;
            text-decoration: none;
            margin-left: 4px;
        }
 
        .login-link a:hover { text-decoration: underline; }
 
        @media (max-width: 400px) {
            .card-grid { grid-template-columns: 1fr; }
            h1 { font-size: 2rem; }
        }
    </style>
<style id="fit-welcome">html,body{height:100%}body{min-height:100vh;padding:14px 16px !important;overflow:auto !important;justify-content:center}.content{max-width:480px !important}.logo{display:none !important}.emoji-row{display:none !important}h1{font-size:2.4rem !important;margin-bottom:4px !important}.content>h2{font-size:1.1rem !important;margin:0 0 6px !important}.tagline{margin-bottom:6px !important}.who{font-size:1.1rem !important;margin-bottom:14px !important}.card-grid{gap:14px !important}.card{padding:20px 14px 18px !important;gap:9px !important;border-width:4px !important;border-radius:20px !important;box-shadow:4px 4px 0 rgba(0,0,0,.15) !important}.card .card-icon{font-size:2.6rem !important}.card .card-title{font-size:1.05rem !important}.card .card-desc{font-size:.8rem !important;line-height:1.35 !important}.card-btn{padding:11px 10px !important;font-size:.92rem !important;margin-top:3px !important}.login-link,.content>p{margin-top:12px !important;font-size:.9rem !important}@media (max-height:700px){h1{font-size:1.9rem !important}.card{padding:12px 10px !important;gap:6px !important}.card .card-desc{font-size:.72rem !important}.card-btn{padding:8px !important}}@media (max-height:600px){.card .card-desc{display:none !important}}</style></head>
<body>
    <div class="content">
        <h2>Welcome to LearnQuest</h2>
<p class="who">Who are you ?</p>
<div class="card-grid">
    <form action="register.php" method="post" style="display: contents;">
        <div class="card parent">
            <span class="card-title">I'm a Parent</span>
            <span class="card-desc">Create & Manage your child's account</span>
        <button type="submit" name="parent" class="card-btn btn-parent">Parent Sign Up</button>
        </div>
    </form>
        <form action="child_signup.php" method="get" style="display: contents;">
    <div class="card child">

            <span class="card-title">I'm a Child</span>
            <span class="card-desc">Make your own Player ID</span>
        <button type ="submit" name="child" class="card-btn btn-child">Child Sign Up</button>
</div>
    </form>
<form action="teacher_register.php" method="get" style="display: contents;"><div class="card teacher" style="grid-column: 1 / -1; border: 4px solid #4361ee; box-shadow: 5px 5px 0 #3046b8;"><span class="card-title">I'm a Teacher</span><span class="card-desc">Follow your learners progress</span><button type="submit" class="card-btn" style="background:#4361ee;color:#fff;border-bottom:4px solid #3046b8">Teacher Sign Up</button><a href="teacher_login.php" style="color:#4361ee;font-weight:800;text-decoration:none;font-size:0.9rem">Teacher log in</a></div></form></div>
    <p style="margin-top:18px"><a href="child_login.php" style="color:#C2185B;font-weight:800">Child log in</a></p><p class="login-link">Already have an account? <a href="login2.php">Log In Here</a></p>
    </div>
</body>
</html>