<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
if (empty($_SESSION["admin_csrf"])) { $_SESSION["admin_csrf"] = bin2hex(random_bytes(16)); }
$msg = ""; $err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["admin_csrf"], $_POST["csrf"] ?? "")) { $err = "The form expired. Please try again."; }
    else {
        $do = $_POST["do"] ?? ""; $id = (int) ($_POST["id"] ?? 0);
        if ($do === "approve_parent") { $st = $pdo->prepare("UPDATE children SET parent_id = pending_parent_id, pending_parent_id = NULL WHERE id = ? AND pending_parent_id IS NOT NULL AND parent_id IS NULL"); $st->execute([$id]); admin_log($pdo, "approved parent link", "child #" . $id); $msg = $st->rowCount() ? "Parent link approved." : "That request is no longer waiting."; }
        elseif ($do === "approve_teacher") { $st = $pdo->prepare("UPDATE children SET teacher_id = pending_teacher_id, pending_teacher_id = NULL WHERE id = ? AND pending_teacher_id IS NOT NULL AND teacher_id IS NULL"); $st->execute([$id]); admin_log($pdo, "approved teacher link", "child #" . $id); $msg = $st->rowCount() ? "Teacher link approved." : "That request is no longer waiting."; }
        elseif ($do === "decline_parent") { $pdo->prepare("UPDATE children SET pending_parent_id = NULL WHERE id = ?")->execute([$id]); admin_log($pdo, "declined parent link", "child #" . $id); $msg = "Parent request declined."; }
        elseif ($do === "decline_teacher") { $pdo->prepare("UPDATE children SET pending_teacher_id = NULL WHERE id = ?")->execute([$id]); admin_log($pdo, "declined teacher link", "child #" . $id); $msg = "Teacher request declined."; }
    }
}
$rows = $pdo->query("SELECT c.id, c.name, c.surname, c.grade, c.age, c.pending_parent_id, c.pending_teacher_id, p.name AS pname, p.surname AS psurname, p.email AS pemail, t.name AS tname, t.surname AS tsurname, t.school AS tschool FROM children c LEFT JOIN parents p ON p.id = c.pending_parent_id LEFT JOIN teachers t ON t.id = c.pending_teacher_id WHERE (c.pending_parent_id IS NOT NULL AND c.parent_id IS NULL) OR (c.pending_teacher_id IS NOT NULL AND c.teacher_id IS NULL) ORDER BY c.id DESC")->fetchAll();
function lact($label, $do, $id, $cls = "") { return "<form method=\"post\" class=\"inl\" onsubmit=\"return confirm(\x27" . $label . "?\x27)\"><input type=\"hidden\" name=\"csrf\" value=\"" . e($_SESSION["admin_csrf"]) . "\"><input type=\"hidden\" name=\"do\" value=\"" . $do . "\"><input type=\"hidden\" name=\"id\" value=\"" . (int) $id . "\"><button class=\"" . $cls . "\">" . $label . "</button></form>"; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin - Link requests</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #ECEFF1; color: #263238; }
header { background: #263238; color: #fff; padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 18px; background: rgba(255,255,255,.15); }
nav a.on { background: #00897B; }
main { max-width: 960px; margin: 0 auto; padding: 22px 14px 50px; }
h1 { font-size: 1.4rem; margin-bottom: 4px; }
.sub { color: #607D8B; margin-bottom: 14px; font-size: .9rem; }
.ok { background: #E8F5E9; border: 2px solid #4CAF50; color: #2E7D32; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
.bad { background: #FFEBEE; border: 2px solid #E53935; color: #C62828; border-radius: 10px; padding: 10px; font-weight: 700; margin-bottom: 12px; }
table { width: 100%; background: #fff; border-collapse: collapse; border-radius: 12px; overflow: hidden; font-size: .88rem; }
th { background: #CFD8DC; text-align: left; padding: 8px; font-size: .75rem; text-transform: uppercase; }
td { padding: 10px 8px; border-top: 1px solid #ECEFF1; vertical-align: top; }
small { color: #90A4AE; display: block; }
.req { margin-bottom: 8px; }
form.inl { display: inline-block; margin: 2px 2px 2px 0; }
form.inl button { border: none; border-radius: 8px; padding: 6px 12px; font-weight: 700; font-size: .78rem; cursor: pointer; background: #546E7A; color: #fff; }
form.inl button.go { background: #00897B; } form.inl button.red { background: #E53935; }
</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_accounts.php?tab=parents">Parents</a><a href="admin_accounts.php?tab=teachers">Teachers</a><a href="admin_accounts.php?tab=children">Children</a><a href="admin_links.php" class="on">Link requests</a><?php if (!empty($admin["is_super"])): ?><a href="admin_admins.php">Admins</a><a href="admin_check.php">System check</a><?php endif; ?><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1>Link requests</h1>
<p class="sub">Learners who asked to link to a parent or teacher. Approving makes the link.</p>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<table><tr><th>Learner</th><th>Requests</th></tr>
<?php foreach ($rows as $r): ?><tr><td><b><?= e($r["name"] . " " . $r["surname"]) ?></b><small>Grade <?= (int) $r["grade"] ?>, age <?= (int) $r["age"] ?></small></td><td>
<?php if ($r["pending_parent_id"]): ?><div class="req">Wants parent: <b><?= e($r["pname"] . " " . $r["psurname"]) ?></b> <small><?= e($r["pemail"]) ?></small><?= lact("Approve", "approve_parent", $r["id"], "go") ?><?= lact("Decline", "decline_parent", $r["id"], "red") ?></div><?php endif; ?>
<?php if ($r["pending_teacher_id"]): ?><div class="req">Wants teacher: <b><?= e($r["tname"] . " " . $r["tsurname"]) ?></b> <small><?= e($r["tschool"]) ?></small><?= lact("Approve", "approve_teacher", $r["id"], "go") ?><?= lact("Decline", "decline_teacher", $r["id"], "red") ?></div><?php endif; ?>
</td></tr><?php endforeach; ?></table>
<?php if (!$rows): ?><p style="margin-top:12px;color:#78909C">No link requests waiting.</p><?php endif; ?>
</main>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>
