<?php
require_once __DIR__ . "/admin_guard.php";
$admin = admin_require($pdo);
require_once __DIR__ . "/admin_data.php";
$sets = admin_datasets($pdo);
admin_log($pdo, "print everything", "pdf");
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>LearnQuest - all reports</title><style>body{font-family:Arial,sans-serif;color:#222;padding:24px}h1{font-size:1.3rem}h2{font-size:1.05rem;margin:22px 0 6px}.sub{color:#666;font-size:.85rem;margin-bottom:10px}table{width:100%;border-collapse:collapse;font-size:.75rem}th,td{border:1px solid #bbb;padding:4px 6px;text-align:left}th{background:#eee}tr{page-break-inside:avoid}.bar{margin-bottom:14px}.bar button{padding:8px 14px;font-weight:700;cursor:pointer}section{page-break-after:always}@media print{.bar{display:none}@page{margin:12mm}}</style></head><body>
<div class="bar"><button onclick="window.print()">Print or save as PDF</button></div>
<h1>LearnQuest - all reports</h1><p class="sub">Created <?= e(date("j F Y H:i")) ?> by <?= e($admin["name"]) ?>. Confidential: contains personal information. No passwords or PINs.</p>
<?php foreach ($sets as $d): ?><section><h2><?= e($d[0]) ?></h2><table><tr><?php foreach ($d[1] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr><?php foreach ($d[2] as $r): ?><tr><?php foreach ($r as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></table><?php if (!$d[2]): ?><p>No data yet.</p><?php endif; ?></section><?php endforeach; ?>
</body></html>
