<?php
session_start();
require_once __DIR__ . "/database.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$hasParent = !empty($_SESSION["parent_id"]);
$error = "";
if (!empty($_SESSION["unlock_until"]) && $_SESSION["unlock_until"] > time()) { $error = "Too many wrong tries. Please wait 5 minutes."; }
elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pass = $_POST["pass"] ?? ""; $ok = false;
    if ($hasParent) {
        $st = $pdo->prepare("SELECT password_hash FROM parents WHERE id = ?"); $st->execute([$_SESSION["parent_id"]]); $p = $st->fetch();
        if ($p && password_verify($pass, $p["password_hash"])) { unset($_SESSION["child_mode"], $_SESSION["child_id"], $_SESSION["child_age"], $_SESSION["child_login"]); session_regenerate_id(true); header("Location: parent_dashboard.php"); exit; }
    } else {
        $email = strtolower(trim($_POST["email"] ?? ""));
        $st = $pdo->prepare("SELECT id, password_hash, approved, disabled FROM parents WHERE email = ?"); $st->execute([$email]); $p = $st->fetch();
        if ($p && password_verify($pass, $p["password_hash"]) && !empty($p["approved"]) && empty($p["disabled"])) { $_SESSION = []; session_regenerate_id(true); $_SESSION["parent_id"] = (int) $p["id"]; header("Location: parent_dashboard.php"); exit; }
        $st = $pdo->prepare("SELECT id, password_hash, approved, disabled FROM teachers WHERE email = ?"); $st->execute([$email]); $t = $st->fetch();
        if ($t && password_verify($pass, $t["password_hash"]) && !empty($t["approved"]) && empty($t["disabled"])) { $_SESSION = []; session_regenerate_id(true); $_SESSION["teacher_id"] = (int) $t["id"]; header("Location: teacher_dashboard.php"); exit; }
    }
    $_SESSION["unlock_fails"] = ($_SESSION["unlock_fails"] ?? 0) + 1;
    if ($_SESSION["unlock_fails"] >= 5) { $_SESSION["unlock_fails"] = 0; $_SESSION["unlock_until"] = time() + 300; }
    $error = "Wrong email or password.";
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>LearnQuest - Parent or teacher</title><style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:"Segoe UI",sans-serif;background:#FFF176;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}.card{background:#fff;border:4px solid #FF4081;border-radius:24px;box-shadow:6px 6px 0 #FF6D00;padding:28px;width:100%;max-width:400px}h1{text-align:center;color:#FF4081;margin-bottom:6px}p{text-align:center;color:#5D4037;font-weight:600;margin-bottom:10px}label{display:block;font-weight:700;color:#5D4037;margin:10px 0 4px}input{width:100%;padding:12px;border:3px solid #FFD600;border-radius:12px;font-size:1rem;background:#FFFDE7}button{width:100%;margin-top:14px;padding:13px;background:#FF4081;color:#fff;border:none;border-radius:14px;font-size:1.05rem;font-weight:800;cursor:pointer}.err{background:#FFCDD2;border:3px solid #E53935;color:#C62828;border-radius:12px;padding:10px;font-weight:700;margin-bottom:10px}a{display:block;text-align:center;margin-top:14px;color:#6A1B9A;font-weight:800}</style></head><body><div class="card"><h1>Parent or teacher</h1><p>Type your password to continue.</p>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?php if (!$hasParent): ?><label>Email</label><input type="email" name="email" required><?php endif; ?><label>Password</label><input type="password" name="pass" autocomplete="current-password" required><button type="submit">Continue</button></form>
<a href="mypage.php">&larr; Back to the child page</a></div></body></html>
