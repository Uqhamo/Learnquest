<?php
require_once __DIR__ . "/mail_helper.php";
function vh_hash($role, $id, $code) { return hash("sha256", $role . ":" . $id . ":" . $code); }
function vh_send($pdo, $role, $id, $email, $name) {
    $st = $pdo->prepare("SELECT (created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)) AS too_soon FROM email_codes WHERE role = ? AND account_id = ?");
    $st->execute([$role, $id]);
    $r = $st->fetch();
    if ($r && $r["too_soon"]) { return "wait"; }
    $code = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $pdo->prepare("DELETE FROM email_codes WHERE role = ? AND account_id = ?")->execute([$role, $id]);
    $pdo->prepare("INSERT INTO email_codes (role, account_id, code_hash, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))")->execute([$role, $id, vh_hash($role, $id, $code)]);
    $html = "<p>Hi " . htmlspecialchars($name) . ",</p><p>Your LearnQuest verification code is:</p><p style=\"font-size:28px;font-weight:bold;letter-spacing:6px\">" . $code . "</p><p>It works for 15 minutes. If you did not sign up, ignore this email.</p>";
    $err = null;
    if (send_mail($email, "Your LearnQuest verification code", $html, $err)) { return "sent"; }
    error_log("verify mail failed: " . $err);
    return "failed";
}
function vh_check($pdo, $role, $id, $code) {
    $st = $pdo->prepare("SELECT id, code_hash, attempts, (expires_at > NOW()) AS live FROM email_codes WHERE role = ? AND account_id = ?");
    $st->execute([$role, $id]);
    $r = $st->fetch();
    if (!$r || !$r["live"]) { return "expired"; }
    if ((int) $r["attempts"] >= 5) { return "locked"; }
    if (hash_equals($r["code_hash"], vh_hash($role, $id, preg_replace("/\D/", "", $code)))) {
        $table = $role === "teacher" ? "teachers" : "parents";
        $pdo->prepare("UPDATE " . $table . " SET email_verified = 1 WHERE id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM email_codes WHERE id = ?")->execute([$r["id"]]);
        return "ok";
    }
    $pdo->prepare("UPDATE email_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$r["id"]]);
    return "wrong";
}
