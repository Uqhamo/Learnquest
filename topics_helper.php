<?php
function tg_gcd($a, $b) { return $b ? tg_gcd($b, $a % $b) : $a; }
function tg_frac($n, $d) { $g = tg_gcd($n, $d); return intdiv($n, $g) . "/" . intdiv($d, $g); }
function tg_options($correct, $cands) { $o = [(string) $correct]; foreach ($cands as $c) { $c = (string) $c; if (!in_array($c, $o, true)) { $o[] = $c; } if (count($o) === 4) { break; } } shuffle($o); return $o; }
function tg_fracs($pairs, $correct) { $c = []; foreach ($pairs as $p) { if ($p[0] >= 1 && $p[0] < $p[1]) { $c[] = tg_frac($p[0], $p[1]); } } shuffle($c); return tg_options($correct, $c); }
function tg_make_factors($level) {
    $pools = [1 => [6, 8, 10, 12, 14, 15, 16, 18, 20, 24], 2 => [24, 28, 30, 32, 36, 40, 42, 45, 48, 56], 3 => [48, 54, 60, 64, 72, 80, 84, 90, 96, 100]];
    $n = $pools[$level][array_rand($pools[$level])];
    $divs = []; $non = [];
    for ($i = 2; $i <= max(12, $n + 4); $i++) { if ($i < $n && $n % $i === 0) { $divs[] = $i; } elseif ($n % $i !== 0) { $non[] = $i; } }
    $ans = $divs[array_rand($divs)];
    shuffle($non);
    return ["text" => "Which of these numbers is a factor of " . $n . "?", "answer" => (string) $ans, "options" => tg_options($ans, array_slice($non, 0, 8))];
}
function tg_make_sqrt($level) {
    $max = [1 => 10, 2 => 15, 3 => 20][$level];
    $r = random_int(2, $max); $n = $r * $r; $c = [];
    foreach ([-3, -2, -1, 1, 2, 3] as $d) { if ($r + $d >= 1) { $c[] = $r + $d; } }
    shuffle($c);
    $text = ($level === 1 && random_int(0, 1)) ? "Which number multiplied by itself gives " . $n . "?" : "\u{221A}" . $n . " = ?";
    return ["text" => $text, "answer" => (string) $r, "options" => tg_options($r, $c)];
}
function tg_make_fraction($level) {
    $types = [1 => ["add", "of"], 2 => ["add", "sub", "of", "unlike"], 3 => ["add", "sub", "of", "unlike", "unlike"]][$level];
    $t = $types[array_rand($types)];
    if ($t === "of") {
        $ds = [2, 3, 4, 5, 10]; $d = $ds[array_rand($ds)]; $k = random_int(2, $level === 1 ? 6 : 12); $a = random_int(1, $d - 1);
        $n = $d * $k; $ans = $a * $k; $c = [];
        foreach ([-$k, $k, -2, -1, 1, 2, 3] as $x) { if ($ans + $x > 0) { $c[] = $ans + $x; } }
        shuffle($c);
        return ["text" => $a . "/" . $d . " of " . $n . " = ?", "answer" => (string) $ans, "options" => tg_options($ans, $c)];
    }
    if ($t === "add") {
        $d = random_int(4, 10); $a = random_int(1, $d - 2); $b = random_int(1, $d - 1 - $a); $s = $a + $b; $ans = tg_frac($s, $d);
        return ["text" => $a . "/" . $d . " + " . $b . "/" . $d . " = ?", "answer" => $ans, "options" => tg_fracs([[$s, $d * 2], [$s, $d + 1], [$s - 1, $d], [$s + 1, $d], [$s + 2, $d], [$s, $d + 2], [$s - 2, $d], [$a, $d], [$b, $d]], $ans)];
    }
    if ($t === "sub") {
        $d = random_int(4, 10); $a = random_int(2, $d - 1); $b = random_int(1, $a - 1); $s = $a - $b; $ans = tg_frac($s, $d);
        return ["text" => $a . "/" . $d . " - " . $b . "/" . $d . " = ?", "answer" => $ans, "options" => tg_fracs([[$s, $d * 2], [$s + 1, $d], [$s - 1, $d], [$a + $b, $d], [$s, $d + 1], [$s + 2, $d], [$a, $d], [$b, $d], [$s, $d + 2]], $ans)];
    }
    $p1 = [2, 3, 4, 5, 6]; $p2 = [2, 3, 4, 6, 8, 10]; $ok = false;
    for ($i = 0; $i < 80 && !$ok; $i++) {
        $d1 = $p1[array_rand($p1)]; $d2 = $p2[array_rand($p2)];
        if ($d1 !== $d2) { $a = random_int(1, $d1 - 1); $b = random_int(1, $d2 - 1); $n = $a * $d2 + $b * $d1; $dd = $d1 * $d2; if ($n < $dd) { $ok = true; } }
    }
    if (!$ok) { $d1 = 2; $a = 1; $d2 = 4; $b = 1; $n = $a * $d2 + $b * $d1; $dd = $d1 * $d2; }
    $ans = tg_frac($n, $dd);
    return ["text" => $a . "/" . $d1 . " + " . $b . "/" . $d2 . " = ?", "answer" => $ans, "options" => tg_fracs([[$a + $b, $d1 + $d2], [$a + $b, $d1 * $d2], [$n + 1, $dd], [$n - 1, $dd], [$n + 2, $dd], [$n, $dd + 1], [$a + $b, max($d1, $d2)], [$a, $d1], [$b, $d2]], $ans)];
}
function tg_new($topic, $level) { if ($topic === "factors") { return tg_make_factors($level); } if ($topic === "fractions") { return tg_make_fraction($level); } return tg_make_sqrt($level); }
