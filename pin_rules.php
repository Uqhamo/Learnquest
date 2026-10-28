<?php
function pin_problem($pin) {
    if (!preg_match("/^\d{4,6}$/", $pin)) { return "The PIN must be 4 to 6 numbers."; }
    if (preg_match("/^(\d)\\1+$/", $pin)) { return "Pick a PIN that is not all the same number."; }
    if (strpos("01234567890123456789", $pin) !== false || strpos("98765432109876543210", $pin) !== false) { return "Pick a PIN that is not in order, like 1234."; }
    return "";
}
