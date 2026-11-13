<?php
session_start();
if (!empty($_SESSION['child_mode'])) { header('Location: mypage.php'); exit; }
require_once __DIR__ . '/database.php';
if (empty($_SESSION['parent_id'])) { header('Location: login2.php'); exit; }

$st = $pdo->prepare("SELECT name, surname, email, created_at FROM parents WHERE id = ?");
$st->execute([$_SESSION['parent_id']]);
$parent = $st->fetch();
if (!$parent) { header('Location: logout.php'); exit; }

$st = $pdo->prepare("SELECT id, name, surname, age, grade, player_code FROM children WHERE parent_id = ? ORDER BY name");
$st->execute([$_SESSION['parent_id']]);
$children = $st->fetchAll();

$current = (int) ($_SESSION['child_id'] ?? 0);

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
$since = $parent['created_at'] ? date('j F Y', strtotime($parent['created_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnQuest - My Profile</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', sans-serif; background: #FFF176; min-height: 100vh; padding: 30px 20px; }
.container { width: 100%; max-width: 600px; margin: auto; }
.back { display: inline-block; margin-bottom: 20px; color: #6A1B9A; font-weight: bold; text-decoration: none; }
.back:hover { text-decoration: underline; }
.card { background: #fff; border: 4px solid #FF4081; border-radius: 25px; padding: 28px; box-shadow: 7px 7px 0 #C2185B; margin-bottom: 22px; }
.avatar { width: 84px; height: 84px; border-radius: 50%; background: linear-gradient(135deg, #FF4081, #FF6D00); color: #fff; font-size: 2.2rem; font-weight: 900; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
h1 { text-align: center; color: #FF4081; font-size: 1.6rem; }
.role { text-align: center; color: #8D6E63; font-weight: 700; margin-bottom: 18px; }
.row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 2px dashed #FFE082; color: #5D4037; }
.row span:first-child { font-weight: 800; }
.row span:last-child { text-align: right; overflow-wrap: anywhere; }
h2 { color: #FF6D00; font-size: 1.2rem; margin-bottom: 12px; }
.child { display: flex; justify-content: space-between; align-items: center; gap: 10px; background: #FFFDE7; border: 3px solid #FFD600; border-radius: 14px; padding: 12px 14px; margin-bottom: 10px; color: #5D4037; }
.child.active { border-color: #00C853; background: #F1FFF6; }
.child b { color: #1a1a2e; }
.badge { background: #00C853; color: #fff; border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; font-weight: 800; white-space: nowrap; }
.empty { color: #8D6E63; }
.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
.btn { flex: 1; min-width: 130px; text-align: center; text-decoration: none; color: #fff; font-weight: 800; padding: 12px; border-radius: 12px; }
.btn.add { background: #4361ee; }
.btn.switch { background: #03A9F4; }
.btn.logout { background: #E53935; }
</style>
</head>
<body>
<div class="container">
    <a href="parent_dashboard.php" class="back">&larr; Back to Dashboard</a>

    <div class="card">
        <div class="avatar"><?= e(strtoupper(substr($parent['name'], 0, 1) . substr($parent['surname'], 0, 1))) ?></div>
        <h1><?= e($parent['name'] . ' ' . $parent['surname']) ?></h1>
        <p class="role">Parent account</p>
        <div class="row"><span>Email</span><span><?= e($parent['email']) ?></span></div>
        <?php if ($since): ?>
            <div class="row"><span>Member since</span><span><?= e($since) ?></span></div>
        <?php endif; ?>
        <div class="row"><span>Children</span><span><?= count($children) ?></span></div>
    </div>

    <div class="card">
        <h2>My Children</h2>
        <?php if (!$children): ?><p class="empty">No child profiles yet.</p><?php endif; ?>
        <?php foreach ($children as $c): ?>
            <div class="child <?= ((int) $c['id'] === $current) ? 'active' : '' ?>">
                <span><b><?= e($c['name'] . ' ' . $c['surname']) ?></b><br>Age <?= (int) $c['age'] ?> &middot; Grade <?= (int) $c['grade'] ?><?php if (!empty($c['player_code'])): ?> &middot; ID <?= e($c['player_code']) ?><?php endif; ?></span>
                <?php if ((int) $c['id'] === $current): ?><span class="badge">Playing now</span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="actions">
            <a href="registerchild.php" class="btn add">Add a child</a>
            <a href="select_child.php" class="btn switch">Switch child</a>
            <a href="reset_pin.php" class="btn switch">Reset a child PIN</a><a href="logout.php" class="btn logout">Logout</a>
        </div>
    </div>
</div>
<?php include __DIR__ . "/footer.php"; ?></body>
</html>