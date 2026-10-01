<?php
session_start()
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registerchild login</title>
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
 
        
        /* Floating stars */
        .star {
            position: fixed;
            font-size: 1.4rem;
            animation: float 4s ease-in-out infinite;
            opacity: 0.45;
            z-index: 0;
            pointer-events: none;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%       { transform: translateY(-14px) rotate(15deg); }
        }
 
        /* Content */
        .content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
 
        /* Brand */
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
 
        /* Card — pink theme for child */
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 36px 32px;
            width: 100%;
            border: 4px solid #FF4081;
            box-shadow: 6px 6px 0 #C2185B, 12px 12px 0 rgba(194,24,91,0.18);
        }
 
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
 
        .card-header .badge { font-size: 2.2rem; }
 
        .card-header h2 {
            font-size: 1.4rem;
            font-weight: 900;
            color: #FF4081;
        }
 
        .card-header p {
            font-size: 0.8rem;
            color: #aaa;
            font-weight: 500;
        }
 
        /* Two-column row */
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }
 
        .field { margin-bottom: 16px; }
 
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
            border-color: #FF4081;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255,64,129,0.14);
        }
 
        .field .hint {
            font-size: 0.74rem;
            color: #bbb;
            margin-top: 4px;
        }
 
        /* Grade selector — big colourful pills */
        .grade-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #5D4037;
            margin-bottom: 10px;
        }
 
        .grade-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
 
        .grade-pills input[type="radio"] { display: none; }
 
        .grade-pills label {
            padding: 9px 16px;
            border-radius: 20px;
            border: 3px solid #FFD600;
            background: #FFFDE7;
            font-size: 0.88rem;
            font-weight: 700;
            color: #5D4037;
            cursor: pointer;
            transition: all 0.18s;
        }
 
        .grade-pills input[type="radio"]:checked + label {
            background: #FF4081;
            border-color: #C2185B;
            color: #fff;
            transform: scale(1.08);
            box-shadow: 0 3px 8px rgba(255,64,129,0.35);
        }
 
        .grade-pills label:hover {
            border-color: #FF4081;
            background: #FFF0F5;
        }
 
        /* Register button */
        .btn-register {
            width: 100%;
            padding: 15px;
            background: #FF4081;
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 800;
            cursor: pointer;
            letter-spacing: 0.5px;
            border-bottom: 5px solid #C2185B;
            transition: transform 0.1s, filter 0.1s;
        }
 
        .btn-register:hover  { filter: brightness(1.08); transform: translateY(-2px); }
        .btn-register:active { transform: translateY(3px); border-bottom: 2px solid #C2185B; }
 
        /* Back link */
        .back-text {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #888;
            font-weight: 500;
        }
 
        .back-text a {
            color: #00BCD4;
            font-weight: 800;
            text-decoration: none;
            margin-left: 4px;
        }
 
        .back-text a:hover { text-decoration: underline; }
 
        @media (max-width: 400px) {
            .field-row { grid-template-columns: 1fr; }
            .card { padding: 28px 18px; }
        }
    </style>
</head>
<body>
    <div class="content">
    <h1>LearnQuest</h1>
    <p class="tagline">Let's set up your account!</p>
    <div class="card">
    
        <div class ="card-header">
            <span class="badge"></span>
            <div>
        <h2> Chlid Register</h2>
        <p>Tell us a little about yourself!</p>
            </div>
        </div>
    <form action="dashboard.php" method="post">
       <div class="field-row">
        <div class="field">
        <label for="name">Enter your Name:</label><br>
        <input type="text" name="name" id="name" placeholder="First name"><br><br></div>

<div class="field">
        <label for="surname">Enter your Surname:</label><br>
        <input type="text" name="surname" id="surname" placeholder="Last name" required><br><br></div></div>

<div class="field">
        <label for="age">How old are you?</label><br>
        <input type="number" name = "age"  id="age" placeholder="e.g 9" min="4" max = "13" required><p class="hint">Enter your age in years</p></div>


            <span class="grade-label">What grade are you in?</span>
                <div class="grade-pills">
                    <?php for ($g = 1; $g <= 7; $g++): ?>
                        <input type="radio" id="grade<?php echo $g; ?>" name="grade" value="<?php echo $g; ?>" <?php echo $g === 4 ? 'required' : ''; ?>>
                        <label for="grade<?php echo $g; ?>">Grade <?php echo $g; ?></label>
                    <?php endfor; ?>
                </div>

        <button type="submit" name="register" class="btn-register">Let's Start Learing!</button>
    </form>
    <p class="back-text">
        Already registered?
        <a href="login2.php">Log In Here!</a>
    </p>
    </div>
    </div>

</body>
</html>