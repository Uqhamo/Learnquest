<?php
if (!defined("MAX_TRIES")) { define("MAX_TRIES", 3); }
function chance_check($game, $is_right) {
    $k = "tries_" . $game;
    $t = (int) ($_SESSION[$k] ?? 0);
    if ($is_right) { $_SESSION[$k] = 0; return ["advance" => true, "left" => 0, "reveal" => false]; }
    $t++;
    if ($t >= MAX_TRIES) { $_SESSION[$k] = 0; return ["advance" => true, "left" => 0, "reveal" => true]; }
    $_SESSION[$k] = $t;
    return ["advance" => false, "left" => MAX_TRIES - $t, "reveal" => false];
}
function chances_reset($game) { $_SESSION["tries_" . $game] = 0; }
