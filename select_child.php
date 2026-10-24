<?php
session_start();
require_once __DIR__ . "/database.php";
if (empty($_SESSION["parent_id"])) { header("Location: login2.php"); exit; }
if (isset($_POST["child_id"])) {
    $st = $pdo->prepare("SELECT id, age FROM children WHERE id = ? AND parent_id = ?");
    $st->execute([(int) $_POST["child_id"], $_SESSION["parent_id"]]);
    if ($c = $st->fetch()) { $_SESSION["child_id"] = (int) $c["id"]; $_SESSION["child_age"] = (int) $c["age"]; header("Location: dashboard.php"); exit; }
}
$st = $pdo->prepare("SELECT id, name, surname FROM children WHERE parent_id = ?");
$st->execute([$_SESSION["parent_id"]]);
$children = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Who is playing?</title></head>
<body>
<h1>Who is playing?</h1>
<?php if (!$children): ?><p>No child profiles yet.</p><?php endif; ?>
<form method="post">
<?php foreach ($children as $c): ?>
<button type="submit" name="child_id" value="<?php echo (int) $c["id"]; ?>"><?php echo htmlspecialchars($c["name"] . " " . $c["surname"]); ?></button>
<?php endforeach; ?>
</form>
<p><a href="registerchild.php">Add a child</a></p>
</body></html>
