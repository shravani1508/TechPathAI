
<?php
session_start();
require_once __DIR__ . "/config/db.php";

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim(isset($_POST["email"]) ? $_POST["email"] : "");
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, password_hash
                 FROM users WHERE email = ? LIMIT 1"
            );
            $stmt->execute(array($email));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password_hash"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int) $user["id"];
                $_SESSION["user_name"] = $user["full_name"];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Incorrect email or password.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Login could not be completed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TechPath AI</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f0efff, #edf8ff);
            color: #172033;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 430px;
            background: white;
            padding: 36px;
            border-radius: 20px;
            box-shadow: 0 15px 45px #28205d18;
        }

        .logo {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #5b4bdb;
        }

        h1 {
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
            font-size: 14px;
            font-weight: bold;
            margin: 16px 0 7px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #dfe2ec;
            border-radius: 9px;
            font-size: 15px;
        }

        button {
            width: 100%;
            border: none;
            background: #5b4bdb;
            color: white;
            padding: 14px;
            border-radius: 9px;
            margin-top: 23px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #4636c4; }

        .error {
            background: #fff0f0;
            color: #a32222;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
        }

        .bottom {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #687086;
        }

        a {
            color: #5b4bdb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="logo">✦ TechPath AI</div>
        <h1>Welcome back!</h1>
        <p class="subtitle">Sign in to continue your career journey.</p>

        <?php if ($error !== ""): ?>
            <div class="error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Sign In →</button>
        </form>

        <p class="bottom">
            Don't have an account?
            <a href="register.php">Create one</a>
        </p>

        <p class="bottom"><a href="index.php">← Back to home</a></p>
    </main>
</body>
</html>