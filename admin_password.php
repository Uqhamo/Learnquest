<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
require_once __DIR__ . "/password_rules.php";
$err = ""; $ok = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cur = $_POST["cur"] ?? ""; $new = $_POST["new"] ?? ""; $rep = $_POST["rep"] ?? "";
    $st = $pdo->prepare("SELECT password_hash, email FROM admins WHERE id = ?"); $st->execute([$admin["id"]]); $r = $st->fetch();
    if (!password_verify($cur, $r["password_hash"])) { $err = "Your current password is wrong."; }
    elseif ($new !== $rep) { $err = "The new passwords do not match."; }
    elseif (strlen($new) < 12) { $err = "Use at least 12 characters."; }
    elseif (($p = password_problem($new, $admin["name"], "", $r["email"])) !== "") { $err = $p; }
    else { $pdo->prepare("UPDATE admins SET password_hash = ?, must_change = 0 WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $admin["id"]]); admin_log($pdo, "changed own password", ""); header("Location: admin_dashboard.php"); exit; }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Change password</title><style>body{font-family:"Segoe UI",sans-serif;background:#263238;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}.card{background:#fff;border-radius:18px;padding:28px;width:100%;max-width:400px}h1{margin-bottom:10px;color:#263238}label{display:block;font-weight:700;margin:10px 0 4px}input{width:100%;padding:12px;border:2px solid #CFD8DC;border-radius:10px;font-size:1rem}button{width:100%;margin-top:14px;padding:13px;background:#00897B;color:#fff;border:none;border-radius:12px;font-size:1.05rem;font-weight:800;cursor:pointer}.err{background:#FFEBEE;border:2px solid #E53935;color:#C62828;border-radius:10px;padding:10px;font-weight:700;margin-bottom:10px}</style></head><body><div class="card"><h1>Change your password</h1><?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?><form method="post"><label>Current password</label><input type="password" name="cur" required><label>New password (12+ characters)</label><input type="password" name="new" required><label>Repeat new password</label><input type="password" name="rep" required><button type="submit">Save</button></form></div></body></html>
