<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
require_once __DIR__ . "/pin_rules.php";
if (empty($_SESSION["admin_csrf"])) { $_SESSION["admin_csrf"] = bin2hex(random_bytes(16)); }
$msg = ""; $err = "";
$tab = in_array($_GET["tab"] ?? "", ["parents", "teachers", "children"], true) ? $_GET["tab"] : "parents";
$q = trim($_GET["q"] ?? "");
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["admin_csrf"], $_POST["csrf"] ?? "")) { $err = "The form expired. Please try again."; }
    else {
        $do = $_POST["do"] ?? ""; $id = (int) ($_POST["id"] ?? 0);
        if ($do === "approve_teacher") { $pdo->prepare("UPDATE teachers SET approved = 1 WHERE id = ?")->execute([$id]); admin_log($pdo, "approve teacher", "#" . $id); $msg = "Teacher approved."; }
        elseif ($do === "toggle_parent") { $pdo->prepare("UPDATE parents SET disabled = 1 - disabled WHERE id = ?")->execute([$id]); admin_log($pdo, "lock/unlock parent", "#" . $id); $msg = "Parent account updated."; }
        elseif ($do === "toggle_teacher") { $pdo->prepare("UPDATE teachers SET disabled = 1 - disabled WHERE id = ?")->execute([$id]); admin_log($pdo, "lock/unlock teacher", "#" . $id); $msg = "Teacher account updated."; }
        elseif ($do === "toggle_child") { $pdo->prepare("UPDATE children SET disabled = 1 - disabled WHERE id = ?")->execute([$id]); admin_log($pdo, "lock/unlock child", "#" . $id); $msg = "Child account updated."; }
        elseif ($do === "unlink_parent") { $pdo->prepare("UPDATE children SET parent_id = NULL WHERE id = ?")->execute([$id]); admin_log($pdo, "unlink parent from child", "#" . $id); $msg = "Parent unlinked."; }
        elseif ($do === "unlink_teacher") { $pdo->prepare("UPDATE children SET teacher_id = NULL WHERE id = ?")->execute([$id]); admin_log($pdo, "unlink teacher from child", "#" . $id); $msg = "Teacher unlinked."; }
        elseif ($do === "reset_pin") { $pin = $_POST["pin"] ?? ""; if (($pp = pin_problem($pin)) !== "") { $err = $pp; } else { $pdo->prepare("UPDATE children SET pin_hash = ?, failed_pins = 0, locked_until = NULL WHERE id = ?")->execute([password_hash($pin, PASSWORD_DEFAULT), $id]); admin_log($pdo, "reset child PIN", "#" . $id); $msg = "PIN changed. Tell the family the new PIN safely."; } }
        elseif ($do === "delete_parent" || $do === "delete_teacher") {
            $role = ($do === "delete_parent") ? "parent" : "teacher"; $tbl = ($role === "parent") ? "parents" : "teachers"; $col = ($role === "parent") ? "parent_id" : "teacher_id";
            $st = $pdo->prepare("SELECT name, surname, email FROM " . $tbl . " WHERE id = ?"); $st->execute([$id]); $acc = $st->fetch();
            if (!$acc || strtoupper(trim($_POST["confirm"] ?? "")) !== "DELETE") { $err = "Type DELETE to confirm."; }
            else {
                $pdo->prepare("UPDATE children SET " . $col . " = NULL WHERE " . $col . " = ?")->execute([$id]);
                $pdo->prepare("UPDATE children SET pending_" . $col . " = NULL WHERE pending_" . $col . " = ?")->execute([$id]);
                foreach (["email_codes", "login_codes", "reset_codes", "trusted_devices"] as $t) { try { $pdo->prepare("DELETE FROM " . $t . " WHERE role = ? AND account_id = ?")->execute([$role, $id]); } catch (Exception $ex) { } }
                if ($role === "parent") { try { $pdo->prepare("DELETE FROM password_resets WHERE parent_id = ?")->execute([$id]); } catch (Exception $ex) { } }
                $pdo->prepare("DELETE FROM " . $tbl . " WHERE id = ?")->execute([$id]);
                admin_log($pdo, "deleted " . $role, $acc["name"] . " " . $acc["surname"] . " (" . $acc["email"] . ")");
                $msg = "That " . $role . " account was deleted. Their children were unlinked, not deleted.";
            }
        }
        elseif ($do === "delete_child") {
            $st = $pdo->prepare("SELECT name FROM children WHERE id = ?"); $st->execute([$id]); $c = $st->fetch();
            if (!$c || strtoupper(trim($_POST["confirm"] ?? "")) !== "DELETE") { $err = "Type DELETE to confirm."; }
            else {
                try { require_once __DIR__ . "/progress_helper.php"; progress_db()->prepare("DELETE FROM attempts WHERE child_id = ?")->execute([(string) $id]); } catch (Exception $ex) { }
                try { $pdo->prepare("DELETE FROM quiz_attempts WHERE child_id = ?")->execute([$id]); } catch (Exception $ex) { }
                try { $pdo->prepare("DELETE FROM progress_reports WHERE child_id = ?")->execute([$id]); } catch (Exception $ex) { }
                $pdo->prepare("DELETE FROM children WHERE id = ?")->execute([$id]);
                admin_log($pdo, "deleted child and data", "#" . $id);
                $msg = "That child and their data were deleted.";
            }
        }
    }
}
$like = "%" . $q . "%";
if ($tab === "parents") { $st = $pdo->prepare("SELECT id, name, surname, email, email_verified, disabled, created_at, (SELECT COUNT(*) FROM children c WHERE c.parent_id = parents.id) AS kids FROM parents WHERE name LIKE ? OR surname LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT 100"); $st->execute([$like, $like, $like]); }
elseif ($tab === "teachers") { $st = $pdo->prepare("SELECT id, name, surname, email, school, email_verified, approved, disabled, created_at, (SELECT COUNT(*) FROM children c WHERE c.teacher_id = teachers.id) AS kids FROM teachers WHERE name LIKE ? OR surname LIKE ? OR email LIKE ? OR school LIKE ? ORDER BY approved ASC, id DESC LIMIT 100"); $st->execute([$like, $like, $like, $like]); }
else { $st = $pdo->prepare("SELECT c.id, c.name, c.surname, c.grade, c.age, c.player_code, c.disabled, c.created_at, c.parent_id, c.teacher_id, p.name AS pname, t.name AS tname FROM children c LEFT JOIN parents p ON p.id = c.parent_id LEFT JOIN teachers t ON t.id = c.teacher_id WHERE c.name LIKE ? OR c.surname LIKE ? OR c.player_code LIKE ? OR p.email LIKE ? ORDER BY c.id DESC LIMIT 100"); $st->execute([$like, $like, $like, $like]); }
$rows = $st->fetchAll();
function act($label, $do, $id, $cls = "", $extra = "") { return "<form method=\"post\" class=\"inl\" onsubmit=\"return confirm(\x27" . $label . "?\x27)\"><input type=\"hidden\" name=\"csrf\" value=\"" . e($_SESSION["admin_csrf"]) . "\"><input type=\"hidden\" name=\"do\" value=\"" . $do . "\"><input type=\"hidden\" name=\"id\" value=\"" . (int) $id . "\">" . $extra . "<button class=\"" . $cls . "\">" . $label . "</button></form>"; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin - Accounts</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #ECEFF1; color: #263238; }
header { background: #263238; color: #fff; padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 18px; background: rgba(255,255,255,.15); }
nav a.on { background: #00897B; }
main { max-width: 1050px; margin: 0 auto; padding: 22px 14px 50px; }
h1 { font-size: 1.4rem; margin-bottom: 12px; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.bad { background: #FFEBEE; border: 2px solid #E53935; color: #C62828; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.search { display: flex; gap: 8px; margin-bottom: 14px; }
.search input { flex: 1; padding: 10px; border: 2px solid #CFD8DC; border-radius: 10px; font-size: 1rem; }
.search button { padding: 10px 16px; background: #00897B; color: #fff; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; }
table { width: 100%; background: #fff; border-collapse: collapse; border-radius: 12px; overflow: hidden; font-size: .85rem; }
th { background: #CFD8DC; text-align: left; padding: 8px; font-size: .75rem; text-transform: uppercase; }
td { padding: 8px; border-top: 1px solid #ECEFF1; vertical-align: top; }
small { color: #90A4AE; display: block; }
.tag { display: inline-block; border-radius: 10px; padding: 1px 8px; font-size: .7rem; font-weight: 800; color: #fff; background: #78909C; }
.tag.warn { background: #FFB300; } .tag.bad2 { background: #E53935; } .tag.good { background: #43A047; }
form.inl { display: inline-block; margin: 2px 2px 2px 0; }
form.inl button { border: none; border-radius: 8px; padding: 5px 9px; font-weight: 700; font-size: .75rem; cursor: pointer; background: #546E7A; color: #fff; }
form.inl button.go { background: #00897B; } form.inl button.red { background: #E53935; }
form.inl input[type=text], form.inl input[type=password] { width: 80px; padding: 4px; border: 1px solid #B0BEC5; border-radius: 6px; font-size: .75rem; }
.wrapx { overflow-x: auto; }
</style><style id="admin-actions-css">td.acts{min-width:230px;width:260px}td.acts .ar{display:flex;align-items:center;gap:6px;padding:5px 0;border-top:1px solid #ECEFF1}td.acts .ar:first-child{border-top:none}td.acts form.inl{display:flex;align-items:center;gap:6px;margin:0;width:100%}td.acts form.inl button{min-width:104px;text-align:center}td.acts form.inl input[type=password]{width:80px;flex:0 0 auto}td.acts .ar.danger{margin-top:6px;padding-top:8px;border-top:2px solid #FFCDD2}td.acts .ar.danger button{width:100%}td.acts .ar:empty{display:none}</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_accounts.php?tab=parents" class="<?= $tab === "parents" ? "on" : "" ?>">Parents</a><a href="admin_accounts.php?tab=teachers" class="<?= $tab === "teachers" ? "on" : "" ?>">Teachers</a><a href="admin_accounts.php?tab=children" class="<?= $tab === "children" ? "on" : "" ?>">Children</a><a href="admin_admins.php">Admins</a><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1><?= e(ucfirst($tab)) ?></h1>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<form class="search" method="get"><input type="hidden" name="tab" value="<?= e($tab) ?>"><input type="text" name="q" value="<?= e($q) ?>" placeholder="Search name, email or Player ID"><button type="submit">Search</button></form>
<div class="wrapx"><table>
<?php if ($tab === "parents"): ?>
<tr><th>Name</th><th>Email</th><th>Children</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r["name"] . " " . $r["surname"]) ?></td><td><?= e($r["email"]) ?><small><?= $r["email_verified"] ? "email verified" : "not verified" ?></small></td><td><?= (int) $r["kids"] ?></td><td><?= $r["disabled"] ? "<span class=\"tag bad2\">locked</span>" : "<span class=\"tag good\">active</span>" ?></td><td><?= e(substr($r["created_at"], 0, 10)) ?></td><td><?= act($r["disabled"] ? "Unlock" : "Lock", "toggle_parent", $r["id"], $r["disabled"] ? "go" : "red") ?><?= act("Delete account", "delete_parent", $r["id"], "red", "<input type=\"text\" name=\"confirm\" placeholder=\"type DELETE\" required>") ?></td></tr><?php endforeach; ?>
<?php elseif ($tab === "teachers"): ?>
<tr><th>Name</th><th>School / email</th><th>Learners</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r["name"] . " " . $r["surname"]) ?></td><td><?= e($r["school"]) ?><small><?= e($r["email"]) ?></small></td><td><?= (int) $r["kids"] ?></td><td><?= $r["approved"] ? "<span class=\"tag good\">approved</span>" : "<span class=\"tag warn\">waiting</span>" ?> <?= $r["disabled"] ? "<span class=\"tag bad2\">locked</span>" : "" ?></td><td><?= e(substr($r["created_at"], 0, 10)) ?></td><td><?= !$r["approved"] ? act("Approve", "approve_teacher", $r["id"], "go") : "" ?><?= act($r["disabled"] ? "Unlock" : "Lock", "toggle_teacher", $r["id"], $r["disabled"] ? "go" : "red") ?><?= act("Delete account", "delete_teacher", $r["id"], "red", "<input type=\"text\" name=\"confirm\" placeholder=\"type DELETE\" required>") ?></td></tr><?php endforeach; ?>
<?php else: ?>
<tr><th>Child</th><th>Grade / age</th><th>Player ID</th><th>Linked to</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r["name"] . " " . $r["surname"]) ?><small>joined <?= e(substr($r["created_at"], 0, 10)) ?></small></td><td>Grade <?= (int) $r["grade"] ?><small>age <?= (int) $r["age"] ?></small></td><td><?= e($r["player_code"] ?: "none") ?></td><td><?= $r["pname"] ? "Parent: " . e($r["pname"]) : "no parent" ?><small><?= $r["tname"] ? "Teacher: " . e($r["tname"]) : "no teacher" ?></small></td><td><?= $r["disabled"] ? "<span class=\"tag bad2\">locked</span>" : "<span class=\"tag good\">active</span>" ?></td><td class="acts">
<div class="ar"><?= act($r["disabled"] ? "Unlock" : "Lock", "toggle_child", $r["id"], $r["disabled"] ? "go" : "red") ?></div>
<?php if ($r["parent_id"]): ?><div class="ar"><?= act("Unlink parent", "unlink_parent", $r["id"]) ?></div><?php endif; ?><?php if ($r["teacher_id"]): ?><div class="ar"><?= act("Unlink teacher", "unlink_teacher", $r["id"]) ?></div><?php endif; ?>
<div class="ar"><?= act("Set PIN", "reset_pin", $r["id"], "go", "<input type=\"password\" name=\"pin\" inputmode=\"numeric\" maxlength=\"6\" placeholder=\"new PIN\" required>") ?></div>
<div class="ar danger"><?= act("Delete data", "delete_child", $r["id"], "red", "<input type=\"text\" name=\"confirm\" placeholder=\"type DELETE\" required>") ?></div>
</td></tr><?php endforeach; ?>
<?php endif; ?>
</table></div>
<?php if (!$rows): ?><p style="margin-top:12px;color:#78909C">Nothing found.</p><?php endif; ?>
</main>
</body>
</html>
