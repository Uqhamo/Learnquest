<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
function n($pdo, $sql) { return (int) $pdo->query($sql)->fetchColumn(); }
$t = ["Parents" => n($pdo, "SELECT COUNT(*) FROM parents"), "Teachers" => n($pdo, "SELECT COUNT(*) FROM teachers"), "Children" => n($pdo, "SELECT COUNT(*) FROM children"), "Linked children" => n($pdo, "SELECT COUNT(*) FROM children WHERE parent_id IS NOT NULL OR teacher_id IS NOT NULL")];
$waiting = n($pdo, "SELECT COUNT(*) FROM teachers WHERE approved = 0");
$answers = 0;
try { require_once __DIR__ . "/progress_helper.php"; $answers = (int) progress_db()->query("SELECT COUNT(*) FROM attempts")->fetchColumn(); } catch (Exception $ex) { }
$recentP = $pdo->query("SELECT name, surname, email, created_at FROM parents ORDER BY id DESC LIMIT 5")->fetchAll();
$recentT = $pdo->query("SELECT name, surname, email, school, approved, created_at FROM teachers ORDER BY id DESC LIMIT 5")->fetchAll();
$recentC = $pdo->query("SELECT name, grade, created_at FROM children ORDER BY id DESC LIMIT 5")->fetchAll();
$log = $pdo->query("SELECT l.action, l.target, l.created_at, a.name FROM admin_log l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest Admin</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", sans-serif; background: #ECEFF1; color: #263238; }
header { background: #263238; color: #fff; padding: 14px 24px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; align-items: center; }
nav { display: flex; flex-wrap: wrap; gap: 6px; }
nav a { color: #fff; text-decoration: none; font-size: .85rem; font-weight: 700; padding: 8px 14px; border-radius: 18px; background: rgba(255,255,255,.15); }
main { max-width: 980px; margin: 0 auto; padding: 24px 16px 50px; }
h1 { font-size: 1.5rem; margin-bottom: 14px; }
.banner { display: block; background: #FFF8E1; border: 2px solid #FFB300; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-weight: 700; color: #5D4037; text-decoration: none; }
.tiles { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 18px; }
.tile { background: #fff; border-radius: 12px; padding: 14px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.tile b { display: block; font-size: 1.7rem; color: #00695C; }
.tile small { color: #78909C; font-weight: 700; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.box { background: #fff; border-radius: 12px; padding: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
.box h3 { font-size: 1rem; margin-bottom: 8px; color: #37474F; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
td { padding: 6px 4px; border-top: 1px solid #ECEFF1; }
small { color: #90A4AE; }
.badge { background: #FFB300; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: .7rem; font-weight: 800; }
@media (max-width: 760px) { .tiles { grid-template-columns: 1fr 1fr; } .grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<header><strong>LearnQuest Admin</strong><nav><a href="admin_dashboard.php">Home</a><a href="admin_accounts.php?tab=parents">Parents</a><a href="admin_accounts.php?tab=teachers">Teachers</a><a href="admin_accounts.php?tab=children">Children</a><a href="admin_backup.php">Backups</a><a href="admin_admins.php">Admins</a><a href="admin_logout.php">Log out (<?= e($admin["name"]) ?>)</a></nav></header>
<main>
<h1>Overview</h1>
<?php if ($waiting): ?><a href="admin_accounts.php?tab=teachers" class="banner"><?= (int) $waiting ?> teacher<?= $waiting > 1 ? "s are" : " is" ?> waiting for approval. Review them &rarr;</a><?php endif; ?>
<div class="tiles"><?php foreach ($t as $k => $v): ?><div class="tile"><b><?= (int) $v ?></b><small><?= e($k) ?></small></div><?php endforeach; ?><div class="tile"><b><?= (int) $answers ?></b><small>Answers logged</small></div></div>
<div class="box" style="margin-bottom:14px"><h3>Download a report</h3><p style="margin-bottom:8px;color:#607D8B;font-size:.85rem">Choose the report and the file type. Contains no passwords or PINs.</p><form id="dlform" method="get" action="admin_export.php" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end"><label style="font-size:.8rem;font-weight:700">Report<br><select name="what" style="padding:9px;border:2px solid #CFD8DC;border-radius:8px"><option value="progress">Learner progress</option><option value="parents">Parents</option><option value="teachers">Teachers</option><option value="children">Children</option><option value="log">Admin activity</option></select></label><label style="font-size:.8rem;font-weight:700">File type<br><select id="dlfmt" style="padding:9px;border:2px solid #CFD8DC;border-radius:8px"><option value="csv">CSV (opens in Excel)</option><option value="pdf">PDF (print and save)</option></select></label><button type="submit" style="background:#00897B;color:#fff;border:none;border-radius:10px;padding:11px 18px;font-weight:800;cursor:pointer">Download</button></form><div style="margin-top:12px;padding-top:10px;border-top:1px dashed #CFD8DC"><b style="font-size:.85rem">Everything at once:</b> <a class="dl" href="admin_print_all.php" target="_blank">All reports (one PDF)</a></div></div><script>document.getElementById("dlform").addEventListener("submit",function(){var p=document.getElementById("dlfmt").value==="pdf";this.action=p?"admin_print.php":"admin_export.php";this.target=p?"_blank":"_self";});</script><div class="grid">
<div class="box"><h3>Parents</h3><table><?php foreach ($recentP as $r): ?><tr><td><?= e($r["name"] . " " . $r["surname"]) ?><br><small><?= e($r["email"]) ?></small></td><td><small><?= e(substr($r["created_at"], 0, 10)) ?></small></td></tr><?php endforeach; ?></table></div>
<div class="box"><h3>Teachers</h3><table><?php foreach ($recentT as $r): ?><tr><td><?= e($r["name"] . " " . $r["surname"]) ?> <?php if (!$r["approved"]): ?><span class="badge">waiting</span><?php endif; ?><br><small><?= e($r["school"]) ?></small></td><td><small><?= e(substr($r["created_at"], 0, 10)) ?></small></td></tr><?php endforeach; ?></table></div>
<div class="box"><h3>Children</h3><table><?php foreach ($recentC as $r): ?><tr><td><?= e($r["name"]) ?> <small>Grade <?= (int) $r["grade"] ?></small></td><td><small><?= e(substr($r["created_at"], 0, 10)) ?></small></td></tr><?php endforeach; ?></table></div>
<div class="box"><h3>Admin activity</h3><table><?php foreach ($log as $r): ?><tr><td><?= e($r["name"] ?? "admin") ?>: <?= e($r["action"]) ?> <small><?= e($r["target"]) ?></small></td><td><small><?= e($r["created_at"]) ?></small></td></tr><?php endforeach; ?></table></div>
</div>
</main>
</body>
</html>
