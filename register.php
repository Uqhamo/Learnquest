<?php session_start(); require_once __DIR__ . "/database.php"; require_once __DIR__ . "/password_rules.php"; require_once __DIR__ . "/email_rules.php"; $error = ""; $success = ""; if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) { $name = ucfirst(trim($_POST["name"] ?? "")); $surname = ucfirst(trim($_POST["surname"] ?? "")); $email = strtolower(trim($_POST["email"] ?? "")); $pass = $_POST["pass"] ?? ""; $confirm_pass = $_POST["confirm_pass"] ?? ""; if ($name === "" || $surname === "" || $email === "" || $pass === "") { $error = "Please fill in all fields."; } elseif (($ee = email_problem($email)) !== "") { $error = $ee; } elseif (($pe = password_problem($pass, $name, $surname, $email)) !== "") { $error = $pe; } elseif ($pass !== $confirm_pass) { $error = "Passwords do not match."; } else { $check = $pdo->prepare("SELECT 1 FROM parents WHERE email = ? OR username = ?"); $check->execute([$email, $email]); if ($check->fetch()) { $error = "That email is already registered."; } else { $ins = $pdo->prepare("INSERT INTO parents (name, surname, username, email, password_hash) VALUES (?, ?, ?, ?, ?)"); $ins->execute([$name, $surname, $email, $email, password_hash($pass, PASSWORD_DEFAULT)]); $newid = (int) $pdo->lastInsertId(); require_once __DIR__ . "/verify_helper.php"; vh_send($pdo, "parent", $newid, $email, $name); session_regenerate_id(true); $_SESSION["verify_pending"] = ["role" => "parent", "id" => $newid]; header("Location: verify_email.php"); exit; } } }
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
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
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
    
    <?php if ($error): ?><div style="background:#FFCDD2;border:3px solid #E53935;color:#C62828;border-radius:12px;padding:10px;font-weight:700;margin-bottom:14px"><?= htmlspecialchars($error) ?></div><?php endif; ?><form action="" method="post">
        <div class="field-row">
            <div class="field">
        <label for="name">Name:</label><br>
        <input type="text" name="name" style="text-transform:capitalize" id="name" placeholder="First name" required><br><br>
            </div>
            <div class="field">
        <label for="surname">Surname:</label><br>
        <input type="text" name="surname" style="text-transform:capitalize" id="surname" placeholder="Last Name" required><br><br>
            </div>
        </div>
<div class="field">
        <label for="email">Email:</label><br><input type="email" name="email" id="email" maxlength="100" placeholder="Your email address" required><br><br></div><div class="field"><label for="pass">Password:</label><br>
        <input type="password" name="pass" id="pass" placeholder="Create a strong password" minlength="8" autocomplete="new-password" required><br><br>
        <p class="hint">Use 8 or more characters with a capital letter, a number and a symbol</p>
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
<script id="eye-toggle">(function(){var open='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';var shut='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';document.querySelectorAll("input[type=password]").forEach(function(i){if(i.dataset.eye){return;}i.dataset.eye="1";var w=document.createElement("div");w.style.cssText="position:relative;width:100%";i.parentNode.insertBefore(w,i);w.appendChild(i);i.style.paddingRight="46px";var b=document.createElement("button");b.type="button";b.innerHTML=open;b.setAttribute("aria-label","Show or hide the password");b.title="Show or hide";b.style.cssText="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:auto;margin:0;padding:4px;border:none;border-radius:8px;background:transparent;line-height:0;cursor:pointer;box-shadow:none;border-bottom:none";b.onclick=function(){var s=i.type==="password";i.type=s?"text":"password";b.innerHTML=s?shut:open;};w.appendChild(b);});})();</script></body>
</html>