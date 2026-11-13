<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!defined("ADMIN_LOCAL_ONLY")) { define("ADMIN_LOCAL_ONLY", true); }
if (ADMIN_LOCAL_ONLY && !in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true)) { http_response_code(403); echo "Not available."; exit; }
require_once __DIR__ . "/database.php";
function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8"); }
function admin_log($pdo, $action, $target = "") { $pdo->prepare("INSERT INTO admin_log (admin_id, action, target) VALUES (?, ?, ?)")->execute([(int) ($_SESSION["admin_id"] ?? 0), $action, $target]); }
function admin_require($pdo) {
    if (empty($_SESSION["admin_id"])) { header("Location: admin_login.php"); exit; }
    $st = $pdo->prepare("SELECT id, name, is_super FROM admins WHERE id = ?");
    $st->execute([(int) $_SESSION["admin_id"]]);
    $a = $st->fetch();
    if (!$a) { unset($_SESSION["admin_id"]); header("Location: admin_login.php"); exit; }
    if (isset($_SESSION["admin_seen"]) && time() - $_SESSION["admin_seen"] > 1800) { $_SESSION = []; header("Location: admin_login.php?timeout=1"); exit; }
    $_SESSION["admin_seen"] = time();
    return $a;
}
