<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: login2.php");
exit;
