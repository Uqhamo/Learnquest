<?php
require_once __DIR__ . "/mail_helper.php";
if (!defined("TWOSTEP_ON")) { define("TWOSTEP_ON", true); }
function ts_hash($role, $id, $code) { return hash("sha256", "login:" . $role . ":" . $id . ":" . $code); }
function ts_trusted($pdo, $role, $id) {
    $t = $_COOKIE["lq_trust_" . $role] ?? "";
    if (!preg_match("/^[0-9a-f]{64}$/", $t)) { return false; }
    $st = $pdo->prepare("SELECT 1 FROM trusted_devices WHERE role = ? AND account_id = ? AND token_hash = ? AND expires_at > NOW()");
    $st->execute([$role, $id, hash("sha256", $t)]);
    return (bool) $st->fetch();
}
function ts_trust($pdo, $role, $id) {
    $t = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT INTO trusted_devices (role, account_id, token_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))")->execute([$role, $id, hash("sha256", $t)]);
    setcookie("lq_trust_" . $role, $t, ["expires" => time() + 2592000, "path" => "/", "httponly" => true, "samesite" => "Lax"]);
}
function ts_send($pdo, $role, $id, $email, $name) {
    $st = $pdo->prepare("SELECT (created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)) AS too_soon FROM login_codes WHERE role = ? AND account_id = ?");
    $st->execute([$role, $id]);
    $r = $st->fetch();
    if ($r && $r["too_soon"]) { return "wait"; }
    $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $pdo->prepare("DELETE FROM login_codes WHERE role = ? AND account_id = ?")->execute([$role, $id]);
    $pdo->prepare("INSERT INTO login_codes (role, account_id, code_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")->execute([$role, $id, ts_hash($role, $id, $code)]);
    $when = (new DateTime("now", new DateTimeZone("Africa/Johannesburg")))->format("j M Y H:i");
    $html = "<p>Hi " . htmlspecialchars($name) . ",</p><p>Your LearnQuest login code is:</p><p style=\"font-size:28px;font-weight:bold;letter-spacing:6px\">" . $code . "</p><p>It works for 10 minutes. Login attempt: " . $when . ". If this was not you, change your password.</p>";
    $err = null;
    if (send_mail($email, "Your LearnQuest login code", $html, $err)) { return "sent"; }
    error_log("login mail failed: " . $err);
    return "failed";
}
function ts_check($pdo, $role, $id, $code) {
    $st = $pdo->prepare("SELECT id, code_hash, attempts, (expires_at > NOW()) AS live FROM login_codes WHERE role = ? AND account_id = ?");
    $st->execute([$role, $id]);
    $r = $st->fetch();
    if (!$r || !$r["live"]) { return "expired"; }
    if ((int) $r["attempts"] >= 5) { return "locked"; }
    if (hash_equals($r["code_hash"], ts_hash($role, $id, preg_replace("/\D/", "", $code)))) { $pdo->prepare("DELETE FROM login_codes WHERE id = ?")->execute([$r["id"]]); return "ok"; }
    $pdo->prepare("UPDATE login_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$r["id"]]);
    return "wrong";
}
