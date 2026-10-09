
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];
$message = "";
$error = "";

/* Load student account details */
$stmt = $pdo->prepare(
    "SELECT id, full_name AS name, email
     FROM users WHERE id = ?"
);
$stmt->execute(array($userId));
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* Update name */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newName = isset($_POST["name"])
        ? trim($_POST["name"]) : "";

    if ($newName === "" || strlen($newName) > 100) {
        $error = "Please enter a name of up to 100 characters.";
    } else {
        $update = $pdo->prepare(
            "UPDATE users SET full_name = ? WHERE id = ?"
        );
        $update->execute(array($newName, $userId));

        $_SESSION["user_name"] = $newName;
        $user["name"] = $newName;
        $message = "Your profile name has been updated.";
    }
}

/* Load career assessment */
$stmt = $pdo->prepare(
    "SELECT preferred_area, skill_level, interests
     FROM assessments WHERE user_id = ?"
);
$stmt->execute(array($userId));
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

/* Load roadmap progress */
$completedSteps = 0;

if ($assessment) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM roadmap_progress
         WHERE user_id = ? AND career_area = ? AND completed = 1"
    );
    $stmt->execute(array($userId, $assessment["preferred_area"]));
    $completedSteps = (int) $stmt->fetchColumn();
}

$displayName = htmlspecialchars(
    $user["name"], ENT_QUOTES, "UTF-8"
);
$displayEmail = htmlspecialchars(
    $user["email"], ENT_QUOTES, "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile | TechPath AI</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f6f7fc;
    color: #20243a;
}

nav {
    background: white;
    padding: 18px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 12px #00000008;
}

.logo {
    color: #5b4bdb;
    font-size: 22px;
    font-weight: bold;
}

a { color: #5b4bdb; text-decoration: none; }

main {
    max-width: 800px;
    margin: 35px auto;
    padding: 0 18px;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 16px;
    border: 1px solid #e7e8f1;
    margin-bottom: 20px;
}

.profile-head {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 20px;
}

.avatar {
    width: 75px;
    height: 75px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #efedff;
    color: #5b4bdb;
    font-size: 30px;
    font-weight: bold;
}

.muted { color: #687086; line-height: 1.6; }

.detail {
    padding: 14px 0;
    border-bottom: 1px solid #eeeeF5;
}

.detail:last-child { border-bottom: none; }

.detail-label {
    font-size: 13px;
    color: #687086;
    margin-bottom: 6px;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d8d9e5;
    border-radius: 8px;
    font-size: 15px;
    margin: 8px 0 15px;
}

button, .button-link {
    display: inline-block;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    background: #5b4bdb;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.button-link { margin: 4px 5px 4px 0; }

.secondary {
    background: white;
    color: #5b4bdb;
    border: 1px solid #d8d4fa;
}

.message {
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.success { background: #e9f9ef; color: #176b39; }
.error { background: #fff0f0; color: #a32222; }

@media (max-width: 500px) {
    .card { padding: 18px; }
    .profile-head { align-items: flex-start; }
}
</style>
</head>

<body>
<nav>
    <div class="logo">✦ TechPath AI</div>
    <a href="dashboard.php">← Dashboard</a>
</nav>

<main>
    <h1>My Student Profile 👤</h1>
    <p class="muted">Manage your account and view your career journey.</p>

    <?php if ($message !== ""): ?>
        <div class="message success">
            <?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <div class="profile-head">
            <div class="avatar">
                <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
            <div>
                <h2><?php echo $displayName; ?></h2>
                <p class="muted">TechPath AI Student</p>
            </div>
        </div>

        <div class="detail">
            <div class="detail-label">Email address</div>
            <strong><?php echo $displayEmail; ?></strong>
        </div>

        <div class="detail">
            <div class="detail-label">Account ID</div>
            <strong><?php echo (int) $user["id"]; ?></strong>
        </div>
    </section>

    <section class="card">
        <h2>Edit Profile Name</h2>
        <form method="POST" action="profile.php">
            <label for="name">Full name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="100"
                required
                value="<?php echo $displayName; ?>"
            >
            <button type="submit">Save Changes</button>
        </form>
        <p class="muted">
            Your email address is displayed but cannot be changed here.
        </p>
    </section>

    <section class="card">
        <h2>My Career Information 🎯</h2>

        <?php if ($assessment): ?>
            <div class="detail">
                <div class="detail-label">Chosen career</div>
                <strong><?php echo htmlspecialchars(
                    $assessment["preferred_area"], ENT_QUOTES, "UTF-8"
                ); ?></strong>
            </div>

            <div class="detail">
                <div class="detail-label">Current skill level</div>
                <strong><?php echo htmlspecialchars(
                    $assessment["skill_level"], ENT_QUOTES, "UTF-8"
                ); ?></strong>
            </div>

            <div class="detail">
                <div class="detail-label">Interests</div>
                <strong><?php echo htmlspecialchars(
                    $assessment["interests"], ENT_QUOTES, "UTF-8"
                ); ?></strong>
            </div>

            <div class="detail">
                <div class="detail-label">Completed roadmap steps</div>
                <strong><?php echo $completedSteps; ?></strong>
            </div>

            <p>
                <a class="button-link" href="analytics.php">
                    View My Analytics
                </a>
                <a class="button-link secondary" href="assessment.php">
                    Update Assessment
                </a>
            </p>
        <?php else: ?>
            <p class="muted">
                You haven't completed your career assessment yet.
            </p>
            <a class="button-link" href="assessment.php">
                Take Career Assessment
            </a>
        <?php endif; ?>
    </section>

    <p><a href="dashboard.php">← Return to Dashboard</a></p>
</main>
</body>
</html>