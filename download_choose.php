<?php
session_start();
require_once __DIR__ . "/database.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
$kids = []; $role = ""; $back = "login2.php";
if (!empty($_SESSION["parent_id"]) && empty($_SESSION["child_mode"])) { $role = "parent"; $back = "family_activity.php"; $st = $pdo->prepare("SELECT id, name, surname FROM children WHERE parent_id = ? ORDER BY name"); $st->execute([$_SESSION["parent_id"]]); $kids = $st->fetchAll(); }
elseif (!empty($_SESSION["teacher_id"])) { $role = "teacher"; $back = "teacher_dashboard.php"; $st = $pdo->prepare("SELECT id, name, surname FROM children WHERE teacher_id = ? ORDER BY grade, name"); $st->execute([$_SESSION["teacher_id"]]); $kids = $st->fetchAll(); }
if (!$role) { header("Location: login2.php"); exit; }
$who = $role === "parent" ? "for my children" : "for my learners";
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Download a report</title><style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:"Segoe UI",sans-serif;background:#F3F6FB;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;color:#263238}.card{background:#fff;border-radius:16px;padding:26px;width:100%;max-width:440px;box-shadow:0 2px 10px rgba(0,0,0,.12)}h1{font-size:1.3rem;color:#00695C;margin-bottom:4px}p.sub{color:#607D8B;font-size:.9rem;margin-bottom:12px}label{display:block;font-weight:700;margin:14px 0 6px}select{width:100%;padding:12px;border:2px solid #CFD8DC;border-radius:10px;font-size:1rem}.opt{display:flex;gap:10px;align-items:flex-start;border:2px solid #CFD8DC;border-radius:10px;padding:10px;margin-bottom:8px;cursor:pointer}.opt input{margin-top:4px}.opt small{display:block;color:#78909C}button{width:100%;margin-top:18px;padding:13px;background:#00897B;color:#fff;border:none;border-radius:12px;font-size:1.05rem;font-weight:800;cursor:pointer}a{display:block;text-align:center;margin-top:14px;color:#546E7A;font-weight:700;text-decoration:none}</style></head><body><div class="card"><h1>Download a report <?= e($who) ?></h1><p class="sub">Includes what needs attention and progress in each activity.</p><form method="get" action="download_report.php">
<?php if (count($kids) > 1): ?><label>Which learner?</label><select name="child"><option value="0">All learners</option><?php foreach ($kids as $k): ?><option value="<?= (int) $k["id"] ?>"><?= e($k["name"] . " " . $k["surname"]) ?></option><?php endforeach; ?></select><?php endif; ?>
<label>File type</label>
<label class="opt"><input type="radio" name="format" value="csv" checked><span><b>CSV</b><small>Opens in Excel or Google Sheets</small></span></label>
<label class="opt"><input type="radio" name="format" value="doc"><span><b>Word (.doc)</b><small>Opens in Microsoft Word</small></span></label>
<label class="opt"><input type="radio" name="format" value="print"><span><b>PDF</b><small>Opens a print page, then choose Save as PDF</small></span></label>
<button type="submit">Download</button></form><a href="<?= e($back) ?>">&larr; Back</a></div><?php include __DIR__ . "/footer.php"; ?></body></html>
