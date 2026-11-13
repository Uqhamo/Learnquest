<?php
function setting($pdo, $name, $default = "") { static $cache = null; if ($cache === null) { $cache = []; try { foreach ($pdo->query("SELECT name, value FROM settings") as $r) { $cache[$r["name"]] = $r["value"]; } } catch (Exception $e) { } } return $cache[$name] ?? $default; }
