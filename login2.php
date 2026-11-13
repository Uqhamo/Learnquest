<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
require_once __DIR__ . "/database.php"; 
require_once __DIR__ . "/settings_helper.php"; 
if (setting($pdo, "maintenance", "0") === "1") {
     http_response_code(503); exit("<h2 style=\"font-family:sans-serif;
     text-align:center;margin-top:20vh\">LearnQuest is being updated. Please try again soon.</h2>"); 
     } 
require_once __DIR__ . "/database.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$error = "";
function lg_fail_msg() { return "Wrong email, Player ID or password."; }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = trim($_POST["user"] ?? ""); $pw = $_POST["pass"] ?? "";
    
if ($id !== "" && strpos($id, "@") !== false) {
    $email = strtolower($id);
    $st = $pdo->prepare("SELECT id, password_hash, email_verified, disabled, approved,failed_logins, 
    (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM parents WHERE email = ? OR username = ?");

    $st->execute([$email, $email]); $p = $st->fetch();
    if ($p && $p["is_locked"]) { $error = "Too many wrong tries. Please wait 5 minutes."; 
    }elseif($p && password_verify($pw, $p["password_hash"]) && empty($p["approved"])) {
    $error = "Your account is waiting for approval by the administrator. Please try again later."; 

    }elseif($p && password_verify($pw, $p["password_hash"]) && !empty($p["disabled"])) {
    $error = "This account is locked. Please contact the school or administrator."; 
    } elseif ($p && password_verify($pw, $p["password_hash"])) {
             
    $pdo->prepare("UPDATE parents SET failed_logins = 0, locked_until = NULL WHERE id = ?")->execute([$p["id"]]);
            
    if ((int) $p["email_verified"] === 0) { 
        $_SESSION = []; $_SESSION["verify_pending"] = ["role" => "parent", "id" => (int) $p["id"]]; 
            
        header("Location: verify_email.php"); exit; }

        require_once __DIR__ . "/twostep_helper.php"; $pid2 = (int) $p["id"];
        if (TWOSTEP_ON && !ts_trusted($pdo, "parent", $pid2)) { 
            $q = $pdo->prepare("SELECT name, email FROM parents WHERE id = ?"); 
            $q->execute([$pid2]); $r = $q->fetch(); 
            if ($r && ts_send($pdo, "parent", $pid2, $r["email"], $r["name"]) !== "failed") { 
            $_SESSION = []; $_SESSION["login_pending"] = ["role" => "parent", "id" => $pid2]; 
            header("Location: login_code.php"); exit; } }

            session_regenerate_id(true); $_SESSION["parent_id"] = $pid2; 
            unset($_SESSION["child_mode"], $_SESSION["child_login"], 
            $_SESSION["child_id"], $_SESSION["child_age"]); 
            header("Location: select_child.php"); exit;
        }
        if ($p && !$p["is_locked"] && !password_verify($pw, $p["password_hash"])) { 
            $pf = (int) $p["failed_logins"] + 1; 

            if ($pf >= 5) {
            $pdo->prepare("UPDATE parents SET failed_logins = 0, 
            locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?")->execute([$p["id"]]); 
            }else{ 
            $pdo->prepare("UPDATE parents SET failed_logins = ? WHERE id = ?")->execute([$pf, $p["id"]]); } } 
            $st = $pdo->prepare("SELECT id, password_hash, email_verified, disabled, approved, failed_logins, 
            (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM teachers WHERE email = ?");
            $st->execute([$email]); $t = $st->fetch();

            if ($t && $t["is_locked"]) { $error = "Too many wrong tries. Please wait 5 minutes."; 

            }elseif ($t && password_verify($pw, $t["password_hash"]) && empty($t["approved"])) {
             $error = "Your account is waiting for approval by the administrator. Please try again later."; 

            }elseif ($t && password_verify($pw, $t["password_hash"]) && !empty($t["disabled"])) {
             $error = "This account is locked. Please contact the administrator."; 
                 
            }elseif ($t && password_verify($pw, $t["password_hash"])) {
            $pdo->prepare("UPDATE teachers SET failed_logins = 0, locked_until = NULL WHERE id = ?")->execute([$t["id"]]);
            
            if ((int) $t["email_verified"] === 0) { 
                $_SESSION = []; $_SESSION["verify_pending"] = ["role" => "teacher", "id" => (int) $t["id"]]; 
                header("Location: verify_email.php"); exit; 
                }

            require_once __DIR__ . "/twostep_helper.php"; 
            $tid2 = (int) $t["id"];
            if (TWOSTEP_ON && !ts_trusted($pdo, "teacher", $tid2)) { 
                $q = $pdo->prepare("SELECT name, email FROM teachers WHERE id = ?"); 
                $q->execute([$tid2]); $r = $q->fetch(); 
                if ($r && ts_send($pdo, "teacher", $tid2, $r["email"], $r["name"]) !== "failed") { 
                   
                $_SESSION = []; $_SESSION["login_pending"] = ["role" => "teacher", "id" => $tid2]; 
                    
                header("Location: login_code.php"); exit; } }
                $_SESSION = []; session_regenerate_id(true); 
                $_SESSION["teacher_id"] = $tid2; 
                header("Location: teacher_dashboard.php"); 
                exit;

            }else {
            if ($t) { $f = (int) $t["failed_logins"] + 1; 
            if ($f >= 5) { $pdo->prepare("UPDATE teachers SET failed_logins = 0, locked_until = DATE_ADD(NOW(), 
            INTERVAL 5 MINUTE) WHERE id = ?")->execute([$t["id"]]); 
            } else { $pdo->prepare("UPDATE teachers SET failed_logins = ? WHERE id = ?")->execute([$f, $t["id"]]); } }
            $error = lg_fail_msg();
        }

        }elseif ($id !== "") {
        $code = strtoupper(preg_replace("/[\s_]+/", "-", $id));
        $st = $pdo->prepare("SELECT id, age, pin_hash, disabled, failed_pins, 
        (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM children WHERE player_code = ?");

        $st->execute([$code]); $ch = $st->fetch();
        if ($ch && $ch["is_locked"]) { $error = "Too many wrong tries. Please wait 5 minutes."; }
        elseif ($ch && !empty($ch["disabled"]) && $ch["pin_hash"] && password_verify($pw, $ch["pin_hash"])) { 
            $error = "This account is locked. Please ask your parent or teacher."; 
            } elseif ($ch && $ch["pin_hash"] && password_verify($pw, $ch["pin_hash"])) {
            $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = NULL WHERE id = ?")->execute([$ch["id"]]);
            $_SESSION = []; session_regenerate_id(true); 
            $_SESSION["child_login"] = true; 
            $_SESSION["child_id"] = (int) $ch["id"]; 
            $_SESSION["child_age"] = (int) $ch["age"]; 
            header("Location: mypage.php"); exit;

        } else {
            if ($ch) { $f = (int) $ch["failed_pins"] + 1; 
            if ($f >= 5) { $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = DATE_ADD(NOW(), 
            INTERVAL 5 MINUTE) WHERE id = ?")->execute([$ch["id"]]); 
            } else { $pdo->prepare("UPDATE children SET failed_pins = ? WHERE id = ?")->execute([$f, $ch["id"]]); 
            } }
            $error = lg_fail_msg();
        }
        } else { $error = lg_fail_msg(); }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Log in</title>
<style>
* { box-sizing: border-box; 
margin: 0; padding: 0; }

body { font-family: "Segoe UI", sans-serif; 
background: #FFF176; 
min-height: 100vh; 
display: flex; 
justify-content: center; 
align-items: center; 
padding: 20px; }

.card { background: #fff; 
border: 4px solid #FF4081; 
border-radius: 24px; 
box-shadow: 6px 6px 0 #FF6D00; 
padding: 28px; 
width: 100%; 
max-width: 420px; }

h1 { text-align: center; 
color: #FF4081; 
margin-bottom: 4px; }

.sub { text-align: center; 
color: #5D4037; 
font-weight: 600; 
margin-bottom: 14px; 
font-size: .9rem; }

label { display: block; 
font-weight: 700; color: #5D4037; 
margin: 12px 0 5px; }

input { width: 100%; 
padding: 12px; 
border: 3px solid #FFD600; 
border-radius: 12px; 
font-size: 1rem; 
background: #FFFDE7; 
}

button { width: 100%; 
margin-top: 18px; 
padding: 14px; 
background: #FF4081; 
color: #fff; 
border: none; 
border-radius: 14px; 
font-size: 1.1rem; 
font-weight: 800; 
cursor: pointer; 
border-bottom: 5px solid #C2185B;
 }

.err { background: #FFCDD2; 
border: 3px solid #E53935; 
color: #C62828; 
border-radius: 12px; 
padding: 10px; 
font-weight: 700; 
margin-bottom: 8px; 
}

.who { display: flex; 
gap: 6px; justify-content: center; 
margin-bottom: 10px; 
flex-wrap: wrap; }

.who span { background: #FFF9C4; 
border-radius: 14px; 
padding: 4px 10px; font-size: .75rem; 
font-weight: 800; 
color: #5D4037; }

.links { text-align: center; 
margin-top: 14px; font-weight: 700; 
font-size: .9rem; }

.links a { color: #6A1B9A; }
</style>
<style id="member-css">.member{margin-top:16px;
padding:12px;
border-top:2px dashed #FFD600;
text-align:center;
color:#5D4037;font-weight:700}

.member a{display:block;
margin-top:8px;
background:#00BCD4;
color:#fff;
border-radius:12px;
padding:11px;
text-decoration:none;
font-weight:800;
border-bottom:4px solid #0097A7}
</style></head>

<body>
<div class="card">
<h1>Welcome back!</h1>
<p class="sub">Parents and teachers use an email. Children use a Player ID.</p>
<div class="who"><span>Parent: email + password</span><span>Teacher: email + password</span>
<span>Child: Player ID + PIN</span></div>
<?php if (isset($_GET["reset"])): ?><div style="background:#C8E6C9;
border:3px solid #4CAF50;
color:#2E7D32;
border-radius:12px;
padding:10px;
font-weight:700;
margin-bottom:8px">Password changed. Please log in.</div>

<?php endif; ?>
<?php if (isset($_GET["pending"])): ?>
    <div style="background:#FFF8E1;
    border:3px solid #FFB300;
    color:#5D4037;
    border-radius:12px;
    padding:10px;
    font-weight:700;
    margin-bottom:8px">
    Your email is verified. An administrator must approve your account before you can log in.</div>
    <?php endif; ?>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><label>Email or Player ID</label>
<input type="text" name="user" autocomplete="username" required>
<label>Password or PIN</label><input type="password" name="pass" autocomplete="current-password" required>
<button type="submit">Log in</button></form>
<div class="member"><span>Are you a member yet?</span>
<a href="welcome.php">Sign up here</a></div><p class="links">
    <a href="forgot_password.php">Forgot password?</a> &middot; <a href="forgot_id.php">Forgot my Player ID</a>
</p>

</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
