<?php
session_start();
require_once __DIR__ . "/database.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$found = ""; $error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $age = (int) ($_POST["age"] ?? 0);
    $pin = $_POST["pin"] ?? "";
    if ($name === "" || $surname === "" || !ctype_digit($pin) || strlen($pin) < 4 || strlen($pin) > 6) {
        $error = "Please type your name, surname, age and your PIN.";
    } else {
        $locked = false;
        $st = $pdo->prepare("SELECT id, player_code, pin_hash, failed_pins, (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked FROM children WHERE LOWER(name) = LOWER(?) AND LOWER(surname) = LOWER(?) AND age = ? AND player_code IS NOT NULL");
        $st->execute([$name, $surname, $age]);
        foreach ($st->fetchAll() as $ch) {
            if ($ch["is_locked"]) { $locked = true; continue; }
            if ($ch["pin_hash"] && password_verify($pin, $ch["pin_hash"])) {
                $found = $ch["player_code"];
                $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = NULL WHERE id = ?")->execute([$ch["id"]]);
                break;
            }
            $f = (int) $ch["failed_pins"] + 1;
            if ($f >= 5) {
                $pdo->prepare("UPDATE children SET failed_pins = 0, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?")->execute([$ch["id"]]);
            } else {
                $pdo->prepare("UPDATE children SET failed_pins = ? WHERE id = ?")->execute([$f, $ch["id"]]);
            }
        }
        if ($found === "") { $error = $locked ? "Too many wrong tries. Please wait 5 minutes." : "We could not find a match. Check your details or ask your parent to help."; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Find my Player ID</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 30px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 6px; }
.sub { text-align: center; color: #5D4037; font-weight: 600; margin-bottom: 14px; }
label { display: block; font-weight: 700; color: #5D4037; margin: 12px 0 5px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button { width: 100%; margin-top: 20px; padding: 14px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.1rem; font-weight: 800; cursor: pointer; border-bottom: 5px solid #C2185B; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 8px; }
.ok { background: #C8E6C9; border: 3px solid #4CAF50; color: #2E7D32; border-radius: 12px; padding: 14px; font-weight: 700; text-align: center; margin-bottom: 12px; }
.ok b { display: block; font-size: 1.8rem; letter-spacing: 3px; color: #C2185B; margin: 6px 0; }
.links { text-align: center; margin-top: 16px; font-weight: 700; }
.links a { color: #6A1B9A; }
</style>
<style id="compact-fit">html,body{min-height:100vh}body{padding:12px 14px !important}.logo,.emoji-row,.stars,.star,.brand .logo{display:none !important}.brand{margin-bottom:10px !important}.brand h2{font-size:1rem !important;margin:0 !important}h1,.brand h1{font-size:1.9rem !important;margin-bottom:4px !important}.brand p,.tagline,.sub,p.sub{font-size:.95rem !important;margin:0 0 10px !important}.card{padding:22px 26px !important;border-width:4px !important;border-radius:22px !important;box-shadow:5px 5px 0 rgba(0,0,0,.18) !important;max-width:440px !important}.card h2{font-size:1.3rem !important;margin-bottom:12px !important}.card-header{margin-bottom:12px !important}label{margin:9px 0 4px !important;font-size:.92rem !important}input,select{padding:11px 13px !important;font-size:1rem !important;border-width:3px !important;border-radius:12px !important}.field,.field-row{margin-bottom:9px !important}.row{gap:11px !important}form br{display:none !important}form button[type=submit]{margin-top:14px !important;padding:13px !important;font-size:1.05rem !important}.hint,p.hint{font-size:.78rem !important;margin-top:3px !important}.err,.ok{padding:9px 12px !important;margin-bottom:9px !important;font-size:.92rem !important}.signup-text,.signin-text,.links{margin-top:12px !important;font-size:.9rem !important}@media (max-height:720px){.card{padding:14px 20px !important}h1,.brand h1{font-size:1.5rem !important}label{margin:5px 0 3px !important}input,select{padding:8px 10px !important}form button[type=submit]{margin-top:9px !important;padding:10px !important}}</style></head>
<body>
<div class="card">
    <h1>Find my Player ID</h1>
    <p class="sub">Type the details you used to sign up.</p>
    <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
    <?php if ($found): ?><div class="ok">Your Player ID is<b><?= e($found) ?></b>Write it down!</div><?php endif; ?>
    <form method="post">
        <label>Your name</label>
        <input type="text" name="name" value="<?= e($_POST["name"] ?? "") ?>" required>
        <label>Your surname</label>
        <input type="text" name="surname" value="<?= e($_POST["surname"] ?? "") ?>" required>
        <label>Your age</label>
        <input type="number" name="age" min="4" max="12" value="<?= e($_POST["age"] ?? "") ?>" required>
        <label>Your PIN</label>
        <input type="password" name="pin" inputmode="numeric" maxlength="6" required>
        <button type="submit">Find my Player ID</button>
    </form>
    <p class="links"><a href="login2.php">Back to log in</a></p>
</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
