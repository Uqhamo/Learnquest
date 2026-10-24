<?php
session_start();
if (empty($_SESSION['parent_id'])) { header('Location: login2.php'); exit; }
if (empty($_SESSION['child_id'])) { header('Location: select_child.php'); exit; }
require_once 'progress_helper.php';
require_once __DIR__ . "/database.php"; $family = get_family_report($pdo, $_SESSION["parent_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DashBoard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
 
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #FFF176;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
 
        
 
        /* Floating stars */
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
 
        
        header {
            background: linear-gradient(90deg, #FF4081, #FF6D00);
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
 
        .header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
 
        .header-logo {
            font-size: 1.8rem;
            animation: bounce 2s ease-in-out infinite;
        }
 
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-6px); }
        }
 
        header h2 {
            font-size: 1.6rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: 1px;
        }
 
        nav {
            display: flex;
            gap: 6px;
        }
 
        nav a {
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 20px;
            background: rgba(255,255,255,0.2);
            transition: background 0.2s;
        }
 
        nav a:hover {
            background: rgba(255,255,255,0.38);
        }
 
        
        main {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 48px 20px 60px;
        }
 
        .greeting {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(90deg, #FF4081, #FF6D00, #AA00FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            text-align: center;
        }
 
        .subtitle {
            font-size: 1rem;
            color: #5D4037;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
        }
 
        .emoji-row {
            font-size: 1.5rem;
            letter-spacing: 6px;
            margin-bottom: 36px;
        }
 
        
        .section-label {
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8D6E63;
            width: 100%;
            max-width: 560px;
            text-align: left;
            margin-bottom: 12px;
        }
 
    
        .card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            width: 100%;
            max-width: 560px;
            margin-bottom: 18px;
        }
 
        
        .card-btn {
            background: #fff;
            border-radius: 20px;
            padding: 30px 14px 24px;
            font-size: 1rem;
            font-weight: 800;
            color: #1a1a2e;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: transform 0.18s, box-shadow 0.18s;
            border: 3px solid transparent;
        }
 
        .card-btn .icon  { font-size: 2.4rem; }
        .card-btn .label { font-size: 1rem; font-weight: 900; }
        .card-btn .desc  { font-size: 0.75rem; color: #999; font-weight: 500; text-align: center; }
 
        
        .card-btn.addition {
            border-color: #00C853;
            box-shadow: 4px 4px 0 #00C853;
        }
        .card-btn.addition:hover {
            background: #F1FFF6;
            transform: translateY(-5px);
            box-shadow: 6px 8px 0 #00C853;
        }
 
        .card-btn.subtraction {
            border-color: #FF4081;
            box-shadow: 4px 4px 0 #FF4081;
        }
        .card-btn.subtraction:hover {
            background: #FFF0F5;
            transform: translateY(-5px);
            box-shadow: 6px 8px 0 #FF4081;
        }
 
        .card-btn.multiplication {
            border-color: #4361ee;
            box-shadow: 4px 4px 0 #4361ee;
        }
        .card-btn.multiplication:hover {
            background: #EEF0FF;
            transform: translateY(-5px);
            box-shadow: 6px 8px 0 #4361ee;
        }
 
        .card-btn.division {
            border-color: #AA00FF;
            box-shadow: 4px 4px 0 #AA00FF;
        }
        .card-btn.division:hover {
            background: #F9ECFF;
            transform: translateY(-5px);
            box-shadow: 6px 8px 0 #AA00FF;
        }
 
        .card-btn:active { transform: translateY(2px); }
 
        
        .games-btn {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border: 3px solid #FF6D00;
            box-shadow: 5px 5px 0 #FF6D00;
            border-radius: 20px;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            transition: transform 0.18s, box-shadow 0.18s;
            font-family: 'Segoe UI', sans-serif;
            text-align: left;
        }
 
        .games-btn:hover {
            background: #FFF3E0;
            transform: translateY(-5px);
            box-shadow: 7px 9px 0 #FF6D00;
        }
 
        .games-btn:active { transform: translateY(2px); }
 
        .games-btn .icon  { font-size: 3rem; flex-shrink: 0; }
        .games-btn .info  { display: flex; flex-direction: column; gap: 4px; }
        .games-btn .label { font-size: 1.15rem; font-weight: 900; color: #FF6D00; }
        .games-btn .desc  { font-size: 0.82rem; color: #999; font-weight: 500; }
 
        @media (max-width: 400px) {
            .card-grid { grid-template-columns: 1fr; }
            .greeting  { font-size: 1.5rem; }
        }
    .progress-card { background: #fff; border-radius: 15px; border-left: 8px solid #999; padding: 15px; margin: 12px 0; width: 100%; max-width: 560px; }
.progress-card.mastered, .progress-card.proficient { border-color: #00C853; }
.progress-card.processing { border-color: #FF6D00; }
.progress-card.progressing { border-color: #03A9F4; }
.progress-card.struggling { border-color: #E53935; }
.modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:100;align-items:center;justify-content:center;padding:20px}.modal.open{display:flex}.modal-box{background:#FFF176;border-radius:20px;padding:24px;max-width:600px;width:100%;max-height:85vh;overflow-y:auto;position:relative}.modal-box h2{color:#FF6D00;margin-bottom:12px}.modal-close{position:absolute;top:12px;right:14px;border:none;background:#FF4081;color:#fff;border-radius:50%;width:32px;height:32px;cursor:pointer;font-weight:bold}.child-name{color:#C2185B;margin:16px 0 4px}</style>
</head>
<body>
    <header>
        <div class="header-brand">
        <h2>LearnQuest</h2>
        </div>
        <nav>
            <a href="">Profile</a>
            <a href =""> For Parent</a>
            <a href="#" onclick="openProgress();return false;">Progress</a>
        <a href="select_child.php">Switch child</a><a href="logout.php">Logout</a></nav>
    </header>
    <main>
        <h2 class="greeting">Time to Play & Learn</h2>
        <p class="subtitle"> Pick an activity to get started!</p>
        <div class="section-label">Choose your Maths Challenge</div>
        <form action="calculation.php" method="post">
           
        <div class = "card-grid">
        <button type="submit" name="operation" value="addition" class="card-btn addition
        ">
        <span class="label">Addition</span>
        <span class="desc">Numbers gettting bigger!</span>
    </button><br><br>
        <button type="submit" name="operation" value="subtration" class="card-btn subtraction">
            <span class="label" >Subtraction</span>
            <span class="desc">Taking numbers away!</span>
        </button><br><br>
        <button type="submit" name="operation" value= "games" class="games-btn">
            <div class="info">
       <span  class="label"> Fun Gamess</span>
       <span class="desc">All operations mixed Can you handle it?</span>
            </div>
       </button>
        
        </form>

    <div id="progressModal" class="modal"><div class="modal-box"><button type="button" class="modal-close" onclick="closeProgress()">X</button><h2>My Children</h2><?php if (!$family): ?><p>No child profiles yet.</p><?php endif; ?><?php foreach ($family as $ch): ?><h3 class="child-name"><?php echo htmlspecialchars($ch["name"] . " " . $ch["surname"]); ?> (Grade <?php echo (int) $ch["grade"]; ?>)</h3><?php if (!$ch["report"]): ?><p>No results yet.</p><?php endif; ?><?php foreach ($ch["report"] as $r): ?><div class="progress-card <?php echo htmlspecialchars($r["status"]); ?>"><strong><?php echo htmlspecialchars(ucfirst($r["skill"])); ?></strong> (<?php echo htmlspecialchars(ucfirst($r["subject"])); ?>)<br><?php echo (int) $r["accuracy"]; ?>% correct over <?php echo (int) $r["total"]; ?> questions<p><?php echo htmlspecialchars($r["summary"]); ?></p><p><em><?php echo htmlspecialchars($r["action"]); ?></em></p></div><?php endforeach; ?><?php endforeach; ?></div></div></main>
<script>function openProgress(){document.getElementById("progressModal").classList.add("open");}function closeProgress(){document.getElementById("progressModal").classList.remove("open");}document.getElementById("progressModal").addEventListener("click",function(e){if(e.target===this){closeProgress();}});</script></body>

</html>

