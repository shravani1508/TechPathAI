
<?php
session_start();
require_once __DIR__ . "/config/db.php";

$errors = [];
$fullName = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
$fullName = trim(isset($_POST["full_name"]) ? $_POST["full_name"] : "");
$email = trim(isset($_POST["email"]) ? $_POST["email"] : "");
$password = isset($_POST["password"]) ? $_POST["password"] : "";
$confirmPassword = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

    if ($fullName === "" || strlen($fullName) > 100) {
        $errors[] = "Please enter your name (maximum 100 characters).";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $errors[] = "This email is already registered.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password_hash)
                     VALUES (?, ?, ?)"
                );
                $insert->execute([$fullName, $email, $hash]);

                $_SESSION["user_id"] = (int) $pdo->lastInsertId();
                $_SESSION["user_name"] = $fullName;

                header("Location: dashboard.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Registration could not be completed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | TechPath AI</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f0efff, #edf8ff);
            color: #172033;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            width: 100%;
            max-width: 460px;
            background: white;
            padding: 36px;
            border-radius: 20px;
            box-shadow: 0 15px 45px #28205d18;
        }

        .logo {
            color: #5b4bdb;
            font-size: 23px;
            font-weight: bold;
            text-align: center;
        }

        h1 {
            font-size: 27px;
            text-align: center;
            margin: 24px 0 8px;
        }

        .subtitle {
            text-align: center;
            color: #687086;
            font-size: 14px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-size: 14px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #dfe2ec;
            border-radius: 9px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #d8d2ff;
            border-color: #5b4bdb;
        }

        button {
            width: 100%;
            border: none;
            background: #5b4bdb;
            color: white;
            padding: 14px;
            margin-top: 23px;
            border-radius: 9px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #4636c4; }

        .message {
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            background: #fff0f0;
            color: #a32222;
            font-size: 14px;
        }

        .bottom {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #687086;
        }

        a { color: #5b4bdb; text-decoration: none; }
    </style>
</head>
<body>
    <main class="container">
        <div class="logo">✦ TechPath AI</div>
        <h1>Create your account</h1>
        <p class="subtitle">Start building your personalized career roadmap.</p>

        <?php foreach ($errors as $error): ?>
            <div class="message">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endforeach; ?>

        <form method="POST" action="register.php">
            <label for="full_name">Full name</label>
            <input
                id="full_name"
                type="text"
                name="full_name"
                maxlength="100"
                autocomplete="name"
                value="<?= htmlspecialchars($fullName, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                maxlength="150"
                autocomplete="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm password</label>
            <input
                id="confirm_password"
                type="password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit">Create Account →</button>
        </form>

        <p class="bottom">
            Already registered?
            <a href="login.php">Sign in</a>
        </p>
        <p class="bottom"><a href="index.php">← Back to home</a></p>
    </main>
</body>
</html>