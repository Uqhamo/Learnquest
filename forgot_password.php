<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/mail_helper.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
function find_account($pdo, $email) { foreach (["parent" => "parents", "teacher" => "teachers"] as $role => $table) { $st = $pdo->prepare("SELECT id, name FROM " . $table . " WHERE email = ?"); $st->execute([$email]); $r = $st->fetch(); if ($r) { return [$role, (int) $r["id"], $r["name"]]; } } return null; }
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = strtolower(trim($_POST["email"] ?? ""));
    if ($email === "" || strpos($email, "@") === false) { $error = "Please type the email you signed up with."; }
    else {
        $a = find_account($pdo, $email);
        if ($a) {
            list($role, $id, $name) = $a;
            $st = $pdo->prepare("SELECT (created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)) AS too_soon FROM reset_codes WHERE role = ? AND account_id = ?");
            $st->execute([$role, $id]); $r = $st->fetch();
            if (!($r && $r["too_soon"])) {
                $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
                $pdo->prepare("DELETE FROM reset_codes WHERE role = ? AND account_id = ?")->execute([$role, $id]);
                $pdo->prepare("INSERT INTO reset_codes (role, account_id, code_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))")->execute([$role, $id, hash("sha256", "reset:" . $role . ":" . $id . ":" . $code)]);
                $html = "<p>Hi " . htmlspecialchars($name) . ",</p><p>Your LearnQuest password reset code is:</p><p style=\"font-size:28px;font-weight:bold;letter-spacing:6px\">" . $code . "</p><p>It works for 15 minutes. If you did not ask for this, ignore this email.</p>";
                $err = null; if (!send_mail($email, "Your LearnQuest password reset code", $html, $err)) { error_log("reset mail failed: " . $err); }
            }
        }
        $_SESSION["reset_email"] = $email;
        header("Location: reset_password.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - Forgot password</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #FFF176; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 24px; box-shadow: 6px 6px 0 #FF6D00; padding: 28px; width: 100%; max-width: 420px; }
h1 { text-align: center; color: #FF4081; margin-bottom: 6px; }
p { color: #5D4037; font-weight: 600; margin-bottom: 10px; text-align: center; }
label { display: block; font-weight: 700; color: #5D4037; margin: 10px 0 4px; }
input { width: 100%; padding: 12px; border: 3px solid #FFD600; border-radius: 12px; font-size: 1rem; background: #FFFDE7; }
button { width: 100%; margin-top: 14px; padding: 13px; background: #FF4081; color: #fff; border: none; border-radius: 14px; font-size: 1.05rem; font-weight: 800; cursor: pointer; }
.err { background: #FFCDD2; border: 3px solid #E53935; color: #C62828; border-radius: 12px; padding: 10px; font-weight: 700; margin-bottom: 10px; }
a.back { display: block; text-align: center; margin-top: 14px; color: #6A1B9A; font-weight: 800; }
</style>
</head>
<body>
<div class="card">
<h1>Forgot password?</h1>
<p>Parents and teachers: type the email you signed up with. We will email you a 6-digit code.</p>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<form method="post"><label>Email</label><input type="email" name="email" maxlength="100" required><button type="submit">Send me a code</button></form>
<p style="margin-top:12px;font-size:.85rem">Children: ask your parent or teacher to reset your PIN.</p>
<a href="login2.php" class="back">&larr; Back to log in</a>
</div>
</body>
</html>
