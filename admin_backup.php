<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
if (empty($_SESSION["admin_csrf"])) { $_SESSION["admin_csrf"] = bin2hex(random_bytes(16)); }
$dir = "C:/LearnQuestBackups"; $script = $dir . "/backup.ps1";
$msg = ""; $err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["admin_csrf"], $_POST["csrf"] ?? "")) { $err = "The form expired. Please try again."; }
    elseif (!file_exists($script)) { $err = "backup.ps1 was not found in " . $dir; }
    elseif (!function_exists("exec")) { $err = "PHP has exec() switched off, so the button cannot run the backup."; }
    else {
        set_time_limit(300); $out = []; $code = 0;
        exec("powershell -NoProfile -ExecutionPolicy Bypass -File \"" . $script . "\" 2>&1", $out, $code);
        if (strpos(implode(" ", $out), "BACKUP OK") !== false) { admin_log($pdo, "backup now", "ok"); $msg = "Backup finished."; }
        else { admin_log($pdo, "backup now", "failed"); $err = "The backup failed. Is MySQL running? " . substr(trim(implode(" ", $out)), 0, 200); }
    }
}
function fmt($b) { if ($b <= 0) { return "0 KB"; } if ($b < 1048576) { return round($b / 1024) . " KB"; } return round($b / 1048576, 1) . " MB"; }
$backups = [];
if (is_dir($dir)) {
    foreach (scandir($dir) as $d) {
        if (substr($d, 0, 1) === "." || !is_dir($dir . "/" . $d)) { continue; }
        $p = $dir . "/" . $d;
        $sql = file_exists($p . "/learnquest.sql") ? filesize($p . "/learnquest.sql") : 0;
        $ans = file_exists($p . "/learnquest-answers.db") ? filesize($p . "/learnquest-answers.db") : 0;
        $zip = file_exists($p . "/code.zip") ? filesize($p . "/code.zip") : 0;
        $backups[] = ["name" => $d, "sql" => $sql, "ans" => $ans, "zip" => $zip, "ok" => $sql > 0];
    }
}
rsort($backups);
$last = $backups ? $backups[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin - Backups</title>
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
.box { background: #fff; border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.box p { color: #607D8B; font-size: .9rem; margin-bottom: 10px; }
button { background: #00897B; color: #fff; border: none; border-radius: 10px; padding: 11px 18px; font-weight: 800; font-size: 1rem; cursor: pointer; }
table { width: 100%; border-collapse: collapse; font-size: .88rem; }
th { text-align: left; background: #CFD8DC; padding: 8px; font-size: .75rem; text-transform: uppercase; }
td { padding: 8px; border-top: 1px solid #ECEFF1; }
.tag { display: inline-block; border-radius: 10px; padding: 1px 9px; font-size: .72rem; font-weight: 800; color: #fff; }
.good { background: #43A047; } .fail { background: #E53935; }
</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_accounts.php?tab=parents">Parents</a><a href="admin_accounts.php?tab=teachers">Teachers</a><a href="admin_accounts.php?tab=children">Children</a><a href="admin_backup.php" class="on">Backups</a><a href="admin_links.php">Link requests</a><?php if (!empty($admin["is_super"])): ?><a href="admin_admins.php">Admins</a><a href="admin_check.php">System check</a><?php endif; ?><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1>Backups</h1>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="bad"><?= e($err) ?></div><?php endif; ?>
<div class="box"><p>Last backup: <b><?= $last ? e($last["name"]) . ($last["ok"] ? " (worked)" : " (failed)") : "none yet" ?></b>. A backup saves the database, the answers file and a copy of the code. It can take up to a minute.</p>
<form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Backing up...';"><input type="hidden" name="csrf" value="<?= e($_SESSION["admin_csrf"]) ?>"><button type="submit">Back up now</button></form></div>
<div class="box"><table><tr><th>Backup (date and time)</th><th>Status</th><th>Database</th><th>Answers</th><th>Code</th></tr>
<?php foreach ($backups as $b): ?><tr><td><?= e($b["name"]) ?></td><td><?= $b["ok"] ? "<span class=\"tag good\">worked</span>" : "<span class=\"tag fail\">failed</span>" ?></td><td><?= e(fmt($b["sql"])) ?></td><td><?= e(fmt($b["ans"])) ?></td><td><?= e(fmt($b["zip"])) ?></td></tr><?php endforeach; ?></table>

</html>