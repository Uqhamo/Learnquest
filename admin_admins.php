<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo); if (empty($admin["is_super"])) { http_response_code(403); exit("Only the super admin can manage admins."); }
require_once __DIR__ . "/email_rules.php";
if (empty($_SESSION["admin_csrf"])) { $_SESSION["admin_csrf"] = bin2hex(random_bytes(16)); }
$msg = ""; $err = ""; $temp = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["admin_csrf"], $_POST["csrf"] ?? "")) { $err = "The form expired. Please try again."; }
    else {
        $do = $_POST["do"] ?? ""; $id = (int) ($_POST["id"] ?? 0);
        if ($do === "add") {
            $name = trim($_POST["name"] ?? ""); $email = strtolower(trim($_POST["email"] ?? ""));
            if ($name === "") { $err = "Please type a name."; }
            elseif (($ee = email_problem($email)) !== "") { $err = $ee; }
            else {
                $st = $pdo->prepare("SELECT 1 FROM admins WHERE email = ?"); $st->execute([$email]);
                if ($st->fetch()) { $err = "That email is already an admin."; }
                else {
                    $alpha = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789"; $temp = "";
                    for ($i = 0; $i < 12; $i++) { $temp .= $alpha[random_int(0, strlen($alpha) - 1)]; }
                    $temp .= "#7";
                    $pdo->prepare("INSERT INTO admins (name, email, password_hash, must_change) VALUES (?, ?, ?, 1)")->execute([$name, $email, password_hash($temp, PASSWORD_DEFAULT)]);
                    admin_log($pdo, "added admin", $name . " (" . $email . ")");
                    $msg = "Admin added. Give them the temporary password below. It is shown only once.";
                }
            }
        }
        elseif ($do !== "add" && (int) $pdo->query("SELECT is_super FROM admins WHERE id = " . (int) $id)->fetchColumn() === 1) { $err = "The super admin cannot be changed here."; } elseif ($id === (int) $admin["id"]) { $err = "You cannot do that to your own account."; }
        elseif ($do === "toggle") { $pdo->prepare("UPDATE admins SET disabled = 1 - disabled WHERE id = ?")->execute([$id]); admin_log($pdo, "lock/unlock admin", "#" . $id); $msg = "Admin updated."; }
        elseif ($do === "delete") {
            $n = (int) $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
            if ($n <= 1) { $err = "You cannot delete the last admin."; }
            else { $pdo->prepare("DELETE FROM admin_codes WHERE admin_id = ?")->execute([$id]); $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]); admin_log($pdo, "deleted admin", "#" . $id); $msg = "Admin removed."; }
        }
    }
}
$rows = $pdo->query("SELECT id, name, email, disabled, must_change, created_at FROM admins ORDER BY id")->fetchAll();
function aact($label, $do, $id, $cls = "") { return "<form method=\"post\" class=\"inl\" onsubmit=\"return confirm(\x27" . $label . "?\x27)\"><input type=\"hidden\" name=\"csrf\" value=\"" . e($_SESSION["admin_csrf"]) . "\"><input type=\"hidden\" name=\"do\" value=\"" . $do . "\"><input type=\"hidden\" name=\"id\" value=\"" . (int) $id . "\"><button class=\"" . $cls . "\">" . $label . "</button></form>"; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin - Admins</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #ECEFF1; color: #263238; }
header { background: #263238; color: #fff; padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 18px; background: rgba(255,255,255,.15); }
nav a.on { background: #00897B; }
main { max-width: 900px; margin: 0 auto; padding: 22px 14px 50px; }
h1 { font-size: 1.4rem; margin-bottom: 12px; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.bad { background: #FFEBEE; border: 2px solid #E53935; color: #C62828; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.temp { background: #FFF8E1; border: 2px dashed #FFB300; border-radius: 10px; padding: 12px; margin-bottom: 12px; font-size: 1.3rem; font-weight: 800; letter-spacing: 2px; text-align: center; }
.box { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.box h3 { margin-bottom: 10px; font-size: 1rem; }
.box input { padding: 10px; border: 2px solid #CFD8DC; border-radius: 10px; font-size: 1rem; margin: 0 6px 8px 0; }
.box button.add { background: #00897B; color: #fff; border: none; border-radius: 10px; padding: 11px 18px; font-weight: 800; cursor: pointer; }
table { width: 100%; border-collapse: collapse; font-size: .88rem; }
th { text-align: left; background: #CFD8DC; padding: 8px; font-size: .75rem; text-transform: uppercase; }
td { padding: 8px; border-top: 1px solid #ECEFF1; }
.tag { display: inline-block; border-radius: 10px; padding: 1px 9px; font-size: .72rem; font-weight: 800; color: #fff; background: #43A047; } .tag.bad2 { background: #E53935; } .tag.warn { background: #FFB300; }
form.inl { display: inline-block; margin: 2px; }
form.inl button { border: none; border-radius: 8px; padding: 6px 11px; font-weight: 700; font-size: .78rem; cursor: pointer; background: #546E7A; color: #fff; }
form.inl button.red { background: #E53935; } form.inl button.go { background: #00897B; }
</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_accounts.php?tab=parents">Parents</a><a href="admin_accounts.php?tab=teachers">Teachers</a><a href="admin_accounts.php?tab=children">Children</a><a href="admin_backup.php">Backups</a><a href="admin_admins.php" class="on">Admins</a><a href="admin_links.php">Link requests</a><?php if (!empty($admin["is_super"])): ?><a href="admin_check.php">System check</a><?php endif; ?><?php if (!empty($admin["is_super"])): ?><?php endif; ?><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1>Admins</h1>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($temp): ?><div class="temp"><?= e($temp) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<div class="box"><h3>Add an admin</h3><form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="do" value="add"><input type="text" name="name" placeholder="Full name" required><input type="email" name="email" placeholder="Real email (receives the login code)" required><button type="submit" class="add">Add admin</button></form></div>
<div class="box"><table><tr><th>Name</th><th>Email</th><th>Status</th><th>Added</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r["name"]) ?><?= (int) $r["id"] === (int) $admin["id"] ? " (you)" : "" ?></td><td><?= e($r["email"]) ?></td><td><?= $r["disabled"] ? "<span class=\"tag bad2\">locked</span>" : "<span class=\"tag\">active</span>" ?> <?= $r["must_change"] ? "<span class=\"tag warn\">must change password</span>" : "" ?></td><td><?= e(substr($r["created_at"], 0, 10)) ?></td><td><?php if ((int) $r["id"] !== (int) $admin["id"]): ?><?= aact($r["disabled"] ? "Unlock" : "Lock", "toggle", $r["id"], $r["disabled"] ? "go" : "red") ?><?= aact("Delete admin", "delete", $r["id"], "red") ?><?php endif; ?></td></tr><?php endforeach; ?></table></div>
</main>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
