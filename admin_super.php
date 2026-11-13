<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
if (empty($admin["is_super"])) { http_response_code(403); exit("Only the super admin can open this page."); }
if (empty($_SESSION["admin_csrf"])) { $_SESSION["admin_csrf"] = bin2hex(random_bytes(16)); }
$msg = ""; $err = "";
$flags = ["twostep_on" => "Login codes for parents and teachers", "signup_codes_on" => "Sign-up email codes", "approval_required" => "Admin approval for new parents and teachers", "maintenance" => "Maintenance mode (shows a notice to users)"];
$nums = ["round_length" => ["Questions per round", 3, 30], "bonus_questions" => ["Bonus questions", 0, 5], "bonus_percent" => ["Bonus needs at least this percent", 50, 100]];
function put($pdo, $k, $v) { $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([$k, (string) $v]); }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["admin_csrf"], $_POST["csrf"] ?? "")) { $err = "The form expired. Please try again."; }
    else {
        $do = $_POST["do"] ?? "";
        if ($do === "save") { foreach ($flags as $k => $l) { put($pdo, $k, isset($_POST[$k]) ? 1 : 0); } foreach ($nums as $k => $d) { $v = (int) ($_POST[$k] ?? 0); put($pdo, $k, max($d[1], min($d[2], $v))); } admin_log($pdo, "changed system settings", ""); $msg = "Settings saved."; }
        elseif ($do === "clear_codes") { foreach (["login_codes", "email_codes", "reset_codes", "admin_codes"] as $t) { try { $pdo->exec("DELETE FROM " . $t . " WHERE expires_at < NOW()"); } catch (Exception $e) { } } admin_log($pdo, "cleared expired codes", ""); $msg = "Expired codes cleared."; }
        elseif ($do === "unlock_all") { foreach (["parents", "teachers", "children"] as $t) { try { $pdo->exec("UPDATE " . $t . " SET failed_" . ($t === "children" ? "pins" : "logins") . " = 0, locked_until = NULL"); } catch (Exception $e) { } } admin_log($pdo, "unlocked all wrong-try locks", ""); $msg = "All wrong-try locks cleared."; }
    }
}
$cur = []; foreach ($pdo->query("SELECT name, value FROM settings") as $r) { $cur[$r["name"]] = $r["value"]; }
$log = $pdo->query("SELECT l.created_at, a.name, l.action, l.target FROM admin_log l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT 25")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin - Super control</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #ECEFF1; color: #263238; }
header { background: #263238; color: #fff; padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 18px; background: rgba(255,255,255,.15); }
nav a.on { background: #00897B; }
main { max-width: 860px; margin: 0 auto; padding: 22px 14px 60px; }
h1 { font-size: 1.4rem; margin-bottom: 12px; }
.box { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.box h3 { font-size: 1rem; margin-bottom: 10px; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.bad { background: #FFEBEE; border: 2px solid #E53935; color: #C62828; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
label.f { display: flex; gap: 10px; align-items: center; padding: 8px 0; border-top: 1px solid #ECEFF1; font-size: .92rem; }
label.n { display: flex; justify-content: space-between; gap: 10px; align-items: center; padding: 8px 0; border-top: 1px solid #ECEFF1; font-size: .92rem; }
label.n input { width: 80px; padding: 7px; border: 2px solid #CFD8DC; border-radius: 8px; }
button { background: #00897B; color: #fff; border: none; border-radius: 10px; padding: 11px 18px; font-weight: 800; cursor: pointer; margin: 4px 6px 0 0; }
button.red { background: #E53935; }
table { width: 100%; border-collapse: collapse; font-size: .82rem; } td { padding: 6px; border-top: 1px solid #ECEFF1; } small { color: #90A4AE; }
</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_admins.php">Admins</a><a href="admin_check.php">System check</a><a href="admin_super.php" class="on">Super control</a><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1>Super control</h1>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="box"><h3>System settings</h3><input type="hidden" name="csrf" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="do" value="save">
<?php foreach ($flags as $k => $l): ?><label class="f"><input type="checkbox" name="<?= e($k) ?>" <?= ($cur[$k] ?? "0") === "1" ? "checked" : "" ?>> <?= e($l) ?></label><?php endforeach; ?>
<?php foreach ($nums as $k => $d): ?><label class="n"><span><?= e($d[0]) ?> (<?= $d[1] ?> to <?= $d[2] ?>)</span><input type="number" name="<?= e($k) ?>" min="<?= $d[1] ?>" max="<?= $d[2] ?>" value="<?= (int) ($cur[$k] ?? 0) ?>"></label><?php endforeach; ?>
<button type="submit">Save settings</button></form>
<div class="box"><h3>Maintenance actions</h3><form method="post" style="display:inline" onsubmit="return confirm(\x27Clear expired codes?\x27)"><input type="hidden" name="csrf" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="do" value="clear_codes"><button>Clear expired codes</button></form><form method="post" style="display:inline" onsubmit="return confirm(\x27Unlock every locked account?\x27)"><input type="hidden" name="csrf" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="do" value="unlock_all"><button class="red">Unlock all wrong-try locks</button></form></div>
<div class="box"><h3>Latest admin activity</h3><table><?php foreach ($log as $r): ?><tr><td><?= e($r["created_at"]) ?></td><td><?= e($r["name"] ?? "admin") ?></td><td><?= e($r["action"]) ?> <small><?= e($r["target"]) ?></small></td></tr><?php endforeach; ?></table></div>
</main>
</body>
</html>
