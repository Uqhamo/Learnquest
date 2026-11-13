<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/child_options.php';

function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$error = '';
$new   = null;

// Show the Player ID and PIN once, after a parent has added a child
if (isset($_GET['done']) && !empty($_SESSION['new_child'])) {
    $new = $_SESSION['new_child'];
    unset($_SESSION['new_child']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $age     = (int) ($_POST['age'] ?? 0);
    $grade   = (int) ($_POST['grade'] ?? 0);
    $pin     = $_POST['pin'] ?? '';
    $colour  = strtoupper(trim($_POST['colour'] ?? ''));
    $animal  = strtoupper(trim($_POST['animal'] ?? ''));

    if ($name === '' || $surname === '') {
        $error = 'Please enter your name and surname.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($surname) < 2) {
        $error = 'Your name and surname must be at least 2 letters.';
    } elseif ($age < 4 || $age > 12) {
        $error = 'Your age must be between 4 and 12.';
    } elseif ($grade < 1 || $grade > 7) {
        $error = 'Your grade must be between 1 and 7.';
    } elseif (!preg_match('/^\d{4,6}$/', $pin)) {
        $error = 'Your PIN must be 4 to 6 numbers.';
    } elseif (!in_array($colour, $colours, true) || !in_array($animal, $animals, true)) {
        $error = 'Please pick your favourite colour and animal.';
    } else {
        // Build a unique Player ID such as BLUE-LION-42
        $code = '';
        for ($i = 0; $i < 10 && $code === ''; $i++) {
            $try = $colour . '-' . $animal . '-' . random_int(10, $i < 6 ? 99 : 999);
            $st  = $pdo->prepare('SELECT 1 FROM children WHERE player_code = ?');
            $st->execute([$try]);
            if (!$st->fetch()) {
                $code = $try;
            }
        }

        if ($code === '') {
            $error = 'Something went wrong. Please try again.';
        } else {
            $by_parent = !empty($_SESSION['parent_id']) ? (int) $_SESSION['parent_id'] : null;

            $st = $pdo->prepare(
                'INSERT INTO children (parent_id, name, surname, age, grade, player_code, pin_hash)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $by_parent,
                $name,
                $surname,
                $age,
                $grade,
                $code,
                password_hash($pin, PASSWORD_DEFAULT),
            ]);
            $id = (int) $pdo->lastInsertId();

            if ($by_parent) {
                // A parent added the child: stay logged in and show the details once
                $_SESSION['new_child'] = ['name' => $name, 'code' => $code, 'pin' => $pin];
                header('Location: child_signup.php?done=1');
                exit;
            }

            // A child signed up alone: start their own session
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['child_login'] = true;
            $_SESSION['child_id']    = $id;
            $_SESSION['child_age']   = $age;
            header('Location: mypage.php?welcome=1');
            exit;
        }
    }
}

// Values to put back in the form after an error (the PIN is never refilled)
$v_grade  = (int) ($_POST['grade'] ?? 0);
$v_colour = strtoupper($_POST['colour'] ?? '');
$v_animal = strtoupper($_POST['animal'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnQuest - Child Sign Up</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #FFF176;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 14px;
        }

        .newchild {
            width: 100%;
            max-width: 440px;
            margin: 0 auto 12px;
            padding: 14px;
            background: #C8E6C9;
            border: 3px solid #4CAF50;
            border-radius: 14px;
            color: #2E7D32;
            text-align: center;
            font-weight: 700;
        }

        .newchild .big { font-size: 1.4rem; }

        .card {
            background: #fff;
            border: 4px solid #FF4081;
            border-radius: 22px;
            box-shadow: 5px 5px 0 rgba(0, 0, 0, 0.18);
            padding: 22px 26px;
            width: 100%;
            max-width: 440px;
        }

        h1 {
            text-align: center;
            color: #FF4081;
            font-size: 1.9rem;
            margin-bottom: 4px;
        }

        .sub {
            text-align: center;
            color: #5D4037;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        label {
            display: block;
            font-weight: 700;
            font-size: 0.92rem;
            color: #5D4037;
            margin: 9px 0 4px;
        }

        input, select {
            width: 100%;
            padding: 11px 13px;
            border: 3px solid #FFD600;
            border-radius: 12px;
            font-size: 1rem;
            background: #FFFDE7;
        }

        button[type=submit] {
            width: 100%;
            margin-top: 14px;
            padding: 13px;
            background: #FF4081;
            color: #fff;
            border: none;
            border-bottom: 5px solid #C2185B;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 800;
            cursor: pointer;
        }

        .err {
            background: #FFCDD2;
            border: 3px solid #E53935;
            color: #C62828;
            border-radius: 12px;
            padding: 9px 12px;
            margin-bottom: 9px;
            font-weight: 700;
            font-size: 0.92rem;
        }

        .links {
            text-align: center;
            margin-top: 12px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .links a { color: #6A1B9A; }

        @media (max-height: 720px) {
            .card { padding: 14px 20px; }
            h1 { font-size: 1.5rem; }
            label { margin: 5px 0 3px; }
            input, select { padding: 8px 10px; }
            button[type=submit] { margin-top: 9px; padding: 10px; }
        }
    </style>
</head>
<body>
<?php if (!empty($new)): ?>
    <div class="newchild">
        Child added: <?= e($new['name']) ?><br>
        Player ID: <span class="big"><?= e($new['code']) ?></span><br>
        PIN: <span class="big"><?= e($new['pin']) ?></span><br>
        <small>Write these down. The PIN is shown only once.</small>
    </div>
<?php endif; ?>

    <div class="card">
        <h1>Join LearnQuest</h1>
        <p class="sub">Make your own Player ID and start learning!</p>

        <?php if ($error): ?>
            <div class="err"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="name">Your name</label>
            <input type="text" name="name" id="name" minlength="2"
                   title="At least 2 letters"
                   value="<?= e($_POST['name'] ?? '') ?>" required>

            <label for="surname">Your surname</label>
            <input type="text" name="surname" id="surname" minlength="2"
                   title="At least 2 letters"
                   value="<?= e($_POST['surname'] ?? '') ?>" required>

            <label for="age">How old are you?</label>
            <input type="number" name="age" id="age" min="4" max="12"
                   value="<?= e($_POST['age'] ?? '') ?>" required>

            <label for="grade">Your grade</label>
            <select name="grade" id="grade" required>
                <?php for ($g = 1; $g <= 7; $g++): ?>
                    <option value="<?= $g ?>" <?= $v_grade === $g ? 'selected' : '' ?>>Grade <?= $g ?></option>
                <?php endfor; ?>
            </select>

            <label for="colour">Your favourite colour</label>
            <select name="colour" id="colour" required>
                <option value="">Pick one</option>
                <?php foreach ($colours as $c): ?>
                    <option value="<?= e($c) ?>" <?= $v_colour === $c ? 'selected' : '' ?>>
                        <?= e(ucfirst(strtolower($c))) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="animal">Your favourite animal</label>
            <select name="animal" id="animal" required>
                <option value="">Pick an animal</option>
                <?php foreach ($animals as $an): ?>
                    <option value="<?= e($an) ?>" <?= $v_animal === $an ? 'selected' : '' ?>>
                        <?= e(ucfirst(strtolower($an))) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="pin">Make a secret PIN (4 to 6 numbers)</label>
            <input type="password" name="pin" id="pin" inputmode="numeric"
                   maxlength="6" autocomplete="new-password" required>

            <button type="submit">Create my Player ID</button>
        </form>

        <p class="links">
            <a href="login2.php">I already have a Player ID</a> &middot;
            <a href="welcome.php">Back</a>
        </p>
    </div>

    <script id="eye-toggle">
    (function () {
        "use strict";

        var eyeOpen =
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" ' +
            'stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>' +
            '<circle cx="12" cy="12" r="3"/></svg>';

        var eyeShut =
            '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" ' +
            'stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
            '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 ' +
            '5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 ' +
            '3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>' +
            '<line x1="1" y1="1" x2="23" y2="23"/></svg>';

        document.querySelectorAll("input[type=password]").forEach(function (input) {
            if (input.dataset.eye) { return; }
            input.dataset.eye = "1";

            var wrapper = document.createElement("div");
            wrapper.style.cssText = "position:relative;width:100%";
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            input.style.paddingRight = "46px";

            var button = document.createElement("button");
            button.type = "button";
            button.innerHTML = eyeOpen;
            button.setAttribute("aria-label", "Show or hide the PIN");
            button.title = "Show or hide";
            button.style.cssText =
                "position:absolute;right:8px;top:50%;transform:translateY(-50%);" +
                "width:auto;margin:0;padding:4px;border:none;border-radius:8px;" +
                "background:transparent;line-height:0;cursor:pointer;box-shadow:none";

            button.onclick = function () {
                var hidden = input.type === "password";
                input.type = hidden ? "text" : "password";
                button.innerHTML = hidden ? eyeShut : eyeOpen;
            };

            wrapper.appendChild(button);
        });
    })();
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>