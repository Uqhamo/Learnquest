<?php
session_start();
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/password_rules.php";
require_once __DIR__ . "/email_rules.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {
    $name         = ucfirst(trim($_POST["name"] ?? ""));
    $surname      = ucfirst(trim($_POST["surname"] ?? ""));
    $email        = strtolower(trim($_POST["email"] ?? ""));
    $pass         = $_POST["pass"] ?? "";
    $confirm_pass = $_POST["confirm_pass"] ?? "";

    if ($name === "" || $surname === "" || $email === "" || $pass === "") {
        $error = "Please fill in all fields.";
    } elseif (mb_strlen($name) < 2 || mb_strlen($surname) < 2) { $error = "Your name and surname must be at least 2 letters."; } elseif (($ee = email_problem($email)) !== "") {
        $error = $ee;
    } elseif (($pe = password_problem($pass, $name, $surname, $email)) !== "") {
        $error = $pe;
    } elseif ($pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        $check = $pdo->prepare("SELECT 1 FROM parents WHERE email = ? OR username = ?");
        $check->execute([$email, $email]);

        if ($check->fetch()) {
            $error = "That email is already registered.";
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO parents (name, surname, username, email, password_hash)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $ins->execute([
                $name,
                $surname,
                $email,
                $email,
                password_hash($pass, PASSWORD_DEFAULT)
            ]);
            $newid = (int) $pdo->lastInsertId();

            require_once __DIR__ . "/verify_helper.php";
            vh_send($pdo, "parent", $newid, $email, $name);

            session_regenerate_id(true);
            $_SESSION["verify_pending"] = ["role" => "parent", "id" => $newid];

            header("Location: verify_email.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Parent</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #FFF176;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 12px 14px;
            overflow: auto;
        }

        .content {
            width: 100%;
            max-width: 460px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-size: 1.9rem;
            font-weight: 900;
            background: linear-gradient(90deg, #FF4081, #FF6D00, #FFD600, #00C853, #00BCD4, #AA00FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 4px;
        }

        .tagline {
            font-size: 0.95rem;
            color: #5D4037;
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }

        .card {
            background: #fff;
            border: 4px solid #00BCD4;
            border-radius: 22px;
            padding: 22px 26px;
            width: 100%;
            max-width: 440px;
            box-shadow: 5px 5px 0 rgba(0, 0, 0, 0.18);
        }

        .card-header { margin-bottom: 12px; }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 900;
            color: #00BCD4;
            margin-bottom: 12px;
        }

        .card-header p {
            font-size: 0.8rem;
            color: #aaa;
            font-weight: 500;
        }

        .error-box {
            background: #FFCDD2;
            border: 3px solid #E53935;
            color: #C62828;
            border-radius: 12px;
            padding: 10px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 9px;
        }

        .field { margin-bottom: 9px; }

        .field label {
            display: block;
            font-size: 0.92rem;
            font-weight: 700;
            color: #5D4037;
            margin: 9px 0 4px;
        }

        .field input {
            width: 100%;
            padding: 11px 13px;
            border: 3px solid #FFD600;
            border-radius: 12px;
            font-size: 1rem;
            color: #1a1a2e;
            background: #FFFDE7;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .field input:focus {
            border-color: #00BCD4;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.15);
        }

        .hint {
            font-size: 0.78rem;
            color: #aaa;
            margin-top: 3px;
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            margin-top: 14px;
            background: #00BCD4;
            color: #fff;
            border: none;
            border-bottom: 5px solid #0097A7;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: transform 0.1s, filter 0.1s;
        }

        .btn-register:hover  { filter: brightness(1.08); transform: translateY(-2px); }
        .btn-register:active { transform: translateY(3px); border-bottom: 2px solid #0097A7; }

        .signin-text {
            text-align: center;
            margin-top: 12px;
            font-size: 0.9rem;
            color: #888;
            font-weight: 500;
        }

        .signin-text a {
            color: #FF4081;
            font-weight: 800;
            text-decoration: none;
            margin-left: 4px;
        }

        .signin-text a:hover { text-decoration: underline; }

        @media (max-width: 400px) {
            .field-row { grid-template-columns: 1fr; }
            .card { padding: 20px 16px; }
        }

        @media (max-height: 720px) {
            .card { padding: 14px 20px; }
            h1 { font-size: 1.5rem; }
            .field label { margin: 5px 0 3px; }
            .field input { padding: 8px 10px; }
            .btn-register { margin-top: 9px; padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="content">
        <h1>LearnQuest</h1>
        <p class="tagline">Create your parent account</p>

        <div class="card">
            <div class="card-header">
                <h2>Parent Register</h2>
                <p>Set up your account to get started</p>
            </div>

            <?php if ($error): ?>
                <div class="error-box"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="field-row">
                    <div class="field">
                        <label for="name">Name:</label>
                        <input type="text" name="name" id="name"
                               style="text-transform:capitalize"
                               placeholder="First name"
                               value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>
                    <div class="field">
                        <label for="surname">Surname:</label>
                        <input type="text" name="surname" id="surname"
                               style="text-transform:capitalize"
                               placeholder="Last Name"
                               value="<?= htmlspecialchars($_POST['surname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>
                </div>

                <div class="field">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email"
                           maxlength="100"
                           placeholder="Your email address"
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>

                <div class="field">
                    <label for="pass">Password:</label>
                    <input type="password" name="pass" id="pass"
                           placeholder="Create a strong password"
                           minlength="8"
                           autocomplete="new-password"
                           required>
                    <p class="hint">Use 8 or more characters with a capital letter, a number and a symbol</p>
                </div>

                <div class="field">
                    <label for="confirm_pass">Confirm password:</label>
                    <input type="password" name="confirm_pass" id="confirm_pass"
                           placeholder="Repeat your password"
                           required>
                </div>

                <button type="submit" name="register" class="btn-register">Create Account</button>
            </form>

            <p class="signin-text">Already a member?<a href="login2.php">Sign in</a></p>
        </div>
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
            button.setAttribute("aria-label", "Show or hide the password");
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
</body>
</html>