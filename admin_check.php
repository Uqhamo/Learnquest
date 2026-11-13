<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
if (empty($admin["is_super"])) { http_response_code(403); exit("Only the super admin can run the system check."); }
if (empty($_SESSION["admin_csrf"])) { $_SESSION["admin_csrf"] = bin2hex(random_bytes(16)); }
$res = []; $ran = false;
function add(&$res, $group, $name, $ok, $detail, $warn = false) { $res[] = [$group, $name, $ok ? ($warn ? "warn" : "ok") : "bad", $detail]; }
if ($_SERVER["REQUEST_METHOD"] === "POST" && hash_equals($_SESSION["admin_csrf"], $_POST["csrf"] ?? "")) {
    $ran = true; $root = __DIR__;
    add($res, "Services", "Web server and PHP", true, "PHP " . PHP_VERSION);
    foreach (["pdo_mysql", "pdo_sqlite", "curl", "openssl", "mbstring"] as $x) { add($res, "Services", "PHP extension " . $x, extension_loaded($x), extension_loaded($x) ? "loaded" : "missing"); }
    try { $v = $pdo->query("SELECT VERSION()")->fetchColumn(); add($res, "Services", "MySQL connection", true, "version " . $v); } catch (Exception $e) { add($res, "Services", "MySQL connection", false, "failed"); }
    $ch = curl_init("http://127.0.0.1:8000/docs"); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 3]); curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); add($res, "Services", "Feedback API (port 8000)", $code === 200, $code === 200 ? "running" : "not reachable, start uvicorn");
    $need = ["parents", "teachers", "children", "admins", "admin_codes", "admin_log", "email_codes", "login_codes", "reset_codes", "trusted_devices", "quiz_attempts", "progress_reports", "pin_reset_log"];
    foreach ($need as $t) { try { $n = (int) $pdo->query("SELECT COUNT(*) FROM `" . $t . "`")->fetchColumn(); add($res, "Database", "Table " . $t, true, $n . " rows"); } catch (Exception $e) { add($res, "Database", "Table " . $t, false, "missing"); } }
    foreach ([["parents", "approved"], ["parents", "disabled"], ["teachers", "approved"], ["teachers", "disabled"], ["children", "disabled"], ["children", "teacher_id"], ["admins", "is_super"]] as $p) { try { $m = $pdo->query("SHOW COLUMNS FROM " . $p[0] . " LIKE \"" . $p[1] . "\"")->fetch(); add($res, "Database", "Column " . $p[0] . "." . $p[1], (bool) $m, $m ? "present" : "missing"); } catch (Exception $e) { add($res, "Database", "Column " . $p[0] . "." . $p[1], false, "table missing"); } }
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = \"FOREIGN KEY\"")->fetchColumn(); add($res, "Database", "Foreign keys", $n > 0, $n . " defined"); } catch (Exception $e) { add($res, "Database", "Foreign keys", false, "cannot read"); }
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND ENGINE <> \"InnoDB\"")->fetchColumn(); add($res, "Database", "All tables InnoDB", $n === 0, $n === 0 ? "yes" : $n . " not InnoDB"); } catch (Exception $e) { }
    try { require_once __DIR__ . "/progress_helper.php"; $n = (int) progress_db()->query("SELECT COUNT(*) FROM attempts")->fetchColumn(); add($res, "Database", "SQLite answers log", true, $n . " answers"); } catch (Exception $e) { add($res, "Database", "SQLite answers log", false, "cannot open"); }
    foreach (["welcome.php", "login2.php", "register.php", "child_signup.php", "teacher_register.php", "parent_dashboard.php", "teacher_dashboard.php", "mypage.php", "calculation.php", "topics_game.php", "spelling.php", "sentence.php", "opposites.php", "unscramble.php", "round_end.php", "admin_dashboard.php", "footer.php", "mail_helper.php", "progress_helper.php", "database.php"] as $f) { $ok = file_exists($root . "/" . $f); $lint = ""; add($res, "Files", $f, $ok, $ok ? "present" : "MISSING"); }
    $miss = []; foreach (glob($root . "/*.php") as $f) { $src = @file_get_contents($f); if ($src === false) { continue; } if (preg_match_all('/href="([a-z_0-9]+\.php)/', $src, $mm)) { foreach ($mm[1] as $l) { if (!file_exists($root . "/" . $l) && !in_array($l, $miss, true)) { $miss[] = $l; } } } }
    add($res, "Files", "Broken page links", !$miss, $miss ? implode(", ", $miss) : "none found");
    $bom = []; foreach (glob($root . "/*.php") as $f) { $h = @file_get_contents($f, false, null, 0, 3); if ($h === "\xEF\xBB\xBF") { $bom[] = basename($f); } } add($res, "Files", "Hidden BOM marker in PHP files", !$bom, $bom ? implode(", ", $bom) : "none");
    add($res, "Security", "Password hashing available", defined("PASSWORD_DEFAULT"), "password_hash");
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM parents WHERE password_hash NOT LIKE \"\$2y\$%\" AND password_hash NOT LIKE \"\$argon%\"")->fetchColumn() + (int) $pdo->query("SELECT COUNT(*) FROM teachers WHERE password_hash NOT LIKE \"\$2y\$%\" AND password_hash NOT LIKE \"\$argon%\"")->fetchColumn(); add($res, "Security", "All stored passwords are hashed", $n === 0, $n === 0 ? "yes" : $n . " not hashed"); } catch (Exception $e) { }
    add($res, "Security", "Admin pages local only", !defined("ADMIN_LOCAL_ONLY") || ADMIN_LOCAL_ONLY, "ADMIN_LOCAL_ONLY is " . (ADMIN_LOCAL_ONLY ? "on" : "off"), !ADMIN_LOCAL_ONLY);
    add($res, "Security", "mail_config.php present", file_exists($root . "/mail_config.php"), "needed to send codes");
    $gi = @file_get_contents($root . "/.gitignore"); add($res, "Security", "Secrets ignored by Git", $gi !== false && strpos($gi, "mail_config.php") !== false && strpos($gi, "learnquest.db") !== false, "mail_config.php and learnquest.db");
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM admins WHERE is_super = 1")->fetchColumn(); add($res, "Security", "Super admin exists", $n >= 1, $n . " super admin(s)"); } catch (Exception $e) { }
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM parents WHERE locked_until > NOW()")->fetchColumn() + (int) $pdo->query("SELECT COUNT(*) FROM children WHERE locked_until > NOW()")->fetchColumn() + (int) $pdo->query("SELECT COUNT(*) FROM teachers WHERE locked_until > NOW()")->fetchColumn(); add($res, "Security", "Accounts locked by wrong tries now", true, $n . " locked", $n > 0); } catch (Exception $e) { }
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM parents WHERE approved = 0")->fetchColumn() + (int) $pdo->query("SELECT COUNT(*) FROM teachers WHERE approved = 0")->fetchColumn(); add($res, "Security", "Accounts waiting for approval", true, $n . " waiting", $n > 0); } catch (Exception $e) { }
    $cfgok = file_exists($root . "/mail_config.php") && file_exists($root . "/phpmailer/src/PHPMailer.php"); add($res, "Email", "PHPMailer and settings", $cfgok, $cfgok ? "present" : "missing");
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM login_codes WHERE expires_at < NOW()")->fetchColumn() + (int) $pdo->query("SELECT COUNT(*) FROM email_codes WHERE expires_at < NOW()")->fetchColumn(); add($res, "Email", "Expired codes lying around", true, $n . " expired", $n > 20); } catch (Exception $e) { }
    $bd = "C:/LearnQuestBackups"; $dirs = is_dir($bd) ? array_values(array_filter(scandir($bd), function ($d) use ($bd) { return $d[0] !== "." && is_dir($bd . "/" . $d); })) : []; rsort($dirs);
    if ($dirs) { $p = $bd . "/" . $dirs[0]; $sz = file_exists($p . "/learnquest.sql") ? filesize($p . "/learnquest.sql") : 0; $age = (time() - filemtime($p)) / 3600; add($res, "Backup", "Latest backup", $sz > 0, $dirs[0] . ", database " . round($sz / 1024) . " KB"); add($res, "Backup", "Backup age", $age < 36, round($age) . " hours old", $age >= 36); add($res, "Backup", "Backups kept", true, count($dirs) . " folders"); } else { add($res, "Backup", "Latest backup", false, "none found"); }
    $od = getenv("OneDrive") ? getenv("OneDrive") . "/LearnQuestBackups" : ""; add($res, "Backup", "OneDrive copy", $od !== "" && is_dir($od), $od !== "" && is_dir($od) ? "folder exists" : "not found (Apache may not see OneDrive)", true);
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM children WHERE parent_id IS NULL AND teacher_id IS NULL")->fetchColumn(); add($res, "Data health", "Children with no adult linked", true, $n . " children", $n > 0); } catch (Exception $e) { }
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM children WHERE pin_hash IS NULL OR pin_hash = \"\"")->fetchColumn(); add($res, "Data health", "Children without a PIN", $n === 0, $n . " found"); } catch (Exception $e) { }
    try { $n = (int) $pdo->query("SELECT COUNT(*) FROM (SELECT player_code FROM children WHERE player_code IS NOT NULL GROUP BY player_code HAVING COUNT(*) > 1) d")->fetchColumn(); add($res, "Data health", "Duplicate Player IDs", $n === 0, $n . " duplicates"); } catch (Exception $e) { }
    admin_log($pdo, "ran system check", count($res) . " checks");
}
$bad = 0; $warn = 0; foreach ($res as $r) { if ($r[2] === "bad") { $bad++; } if ($r[2] === "warn") { $warn++; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin - System check</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #ECEFF1; color: #263238; }
header { background: #263238; color: #fff; padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 18px; background: rgba(255,255,255,.15); }
nav a.on { background: #00897B; }
main { max-width: 900px; margin: 0 auto; padding: 22px 14px 60px; }
h1 { font-size: 1.4rem; margin-bottom: 6px; }
.sub { color: #607D8B; margin-bottom: 14px; font-size: .9rem; }
button.run { background: #00897B; color: #fff; border: none; border-radius: 10px; padding: 12px 22px; font-weight: 800; font-size: 1rem; cursor: pointer; }
.sum { display: flex; gap: 10px; margin: 16px 0; flex-wrap: wrap; }
.sum div { background: #fff; border-radius: 12px; padding: 12px 18px; font-weight: 800; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.sum .g { color: #2E7D32; } .sum .a { color: #EF6C00; } .sum .r { color: #C62828; }
h2 { font-size: 1rem; margin: 18px 0 6px; color: #37474F; }
.row { display: flex; justify-content: space-between; gap: 10px; background: #fff; border-left: 6px solid #43A047; border-radius: 8px; padding: 8px 12px; margin-bottom: 4px; font-size: .88rem; }
.row.warn { border-color: #FFB300; } .row.bad { border-color: #E53935; background: #FFEBEE; }
.row small { color: #78909C; text-align: right; }
</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_accounts.php?tab=parents">Parents</a><a href="admin_accounts.php?tab=teachers">Teachers</a><a href="admin_accounts.php?tab=children">Children</a><a href="admin_admins.php">Admins</a><a href="admin_check.php" class="on">System check</a><?php if (!empty($admin["is_super"])): ?><?php endif; ?><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1>System check</h1>
<p class="sub">Tests the services, database, files, security, email, backups and data health. It only reads, and changes nothing.</p>
<form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION["admin_csrf"]) ?>"><button class="run" type="submit"><?= $ran ? "Run again" : "Run the check" ?></button></form>
<?php if ($ran): ?><div class="sum"><div class="g"><?= count($res) - $bad - $warn ?> OK</div><div class="a"><?= $warn ?> to watch</div><div class="r"><?= $bad ?> failing</div></div>
<?php $cur = ""; foreach ($res as $r): if ($r[0] !== $cur) { $cur = $r[0]; echo "<h2>" . e($cur) . "</h2>"; } ?><div class="row <?= e($r[2]) ?>"><span><?= e($r[1]) ?></span><small><?= e($r[3]) ?></small></div><?php endforeach; ?><?php endif; ?>
</main>
</body>
</html>
