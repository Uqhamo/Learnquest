<?php
if (!defined("ROUND_LENGTH")) { define("ROUND_LENGTH", 10); }
if (!defined("BONUS_QUESTIONS")) { define("BONUS_QUESTIONS", 2); }
if (!defined("BONUS_PERCENT")) { define("BONUS_PERCENT", 80); }
function round_counts($game) {
    switch ($game) {
        case "math": return [(int) ($_SESSION["calc_done"] ?? 0), (int) ($_SESSION["calc_right"] ?? 0)];
        case "spelling": return [(int) ($_SESSION["spelling_total"] ?? 0), (int) ($_SESSION["spelling_score"] ?? 0)];
        case "sentence": return [(int) ($_SESSION["sentence_total"] ?? 0), (int) ($_SESSION["sentence_score"] ?? 0)];
        case "opposites": return [(int) ($_SESSION["opposites_game"]["total"] ?? 0), (int) ($_SESSION["opposites_game"]["score"] ?? 0)];
        case "unscramble": return [(int) ($_SESSION["unscramble_game"]["total"] ?? 0), (int) ($_SESSION["unscramble_game"]["score"] ?? 0)];
    }
    return [0, 0];
}
function round_reset($game) {
    switch ($game) {
        case "math": $_SESSION["calc_score"] = 0; $_SESSION["calc_right"] = 0; $_SESSION["calc_done"] = 0; unset($_SESSION["current_q"], $_SESSION["attempts_count"], $_SESSION["skip_tries"]); break;
        case "spelling": $_SESSION["spelling_total"] = 0; $_SESSION["spelling_score"] = 0; $_SESSION["spelling_attempts"] = 0; break;
        case "sentence": $_SESSION["sentence_total"] = 0; $_SESSION["sentence_score"] = 0; break;
        case "opposites": $_SESSION["opposites_game"] = ["score" => 0, "total" => 0, "current" => null]; break;
        case "unscramble": $_SESSION["unscramble_game"] = ["score" => 0, "total" => 0, "current" => null, "scrambled" => ""]; break;
    }
    unset($_SESSION["round_" . $game]);
}
function round_gate($game, $message = "", $feedback = "") {
    list($done, $right) = round_counts($game);
    $k = "round_" . $game;
    if (!isset($_SESSION[$k]) || !is_array($_SESSION[$k])) { $_SESSION[$k] = ["bonus" => false]; }
    if ($done >= ROUND_LENGTH && empty($_SESSION[$k]["bonus"]) && $done > 0 && ($right * 100 / $done) >= BONUS_PERCENT) { $_SESSION[$k]["bonus"] = true; }
    $bonus = !empty($_SESSION[$k]["bonus"]);
    $limit = ROUND_LENGTH + ($bonus ? BONUS_QUESTIONS : 0);
    if ($done >= $limit) {
        $points = ($game === "math") ? (int) ($_SESSION["calc_score"] ?? 0) : null;
        $_SESSION["round_last"] = ["game" => $game, "done" => $done, "right" => $right, "bonus" => $bonus, "points" => $points, "message" => html_entity_decode(strip_tags((string) $message), ENT_QUOTES, "UTF-8"), "feedback" => (string) $feedback];
        round_reset($game);
        header("Location: round_end.php");
        exit;
    }
    if ($done >= ROUND_LENGTH) { return "Bonus question " . ($done - ROUND_LENGTH + 1) . " of " . BONUS_QUESTIONS . ". You earned it!"; }
    return "Question " . ($done + 1) . " of " . ROUND_LENGTH;
}
function round_finish($game, $message = "", $feedback = "") { list($done, $right) = round_counts($game); $_SESSION["round_last"] = ["game" => $game, "done" => $done, "right" => $right, "bonus" => !empty($_SESSION["round_" . $game]["bonus"]), "points" => ($game === "math") ? (int) ($_SESSION["calc_score"] ?? 0) : null, "message" => html_entity_decode(strip_tags((string) $message), ENT_QUOTES, "UTF-8"), "feedback" => (string) $feedback]; round_reset($game); header("Location: round_end.php"); exit; }
