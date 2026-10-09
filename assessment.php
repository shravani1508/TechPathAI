
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];
$errors = array();
$success = "";
$interests = array();
$skillLevel = "";
$preferredArea = "";

$allowedInterests = array(
    "Problem Solving",
    "Designing",
    "Data Analysis",
    "Artificial Intelligence",
    "Building Websites",
    "Cybersecurity"
);

$allowedLevels = array("Beginner", "Intermediate", "Advanced");

$allowedAreas = array(
    "Web Development",
    "AI and Machine Learning",
    "Data Analytics",
    "Cybersecurity",
    "App Development"
);

$careerRecommendations = array(
    "Web Development" => array(
        "title" => "Web Developer",
        "skills" => "HTML, CSS, JavaScript, PHP and MySQL"
    ),
    "AI and Machine Learning" => array(
        "title" => "AI / ML Engineer",
        "skills" => "Python, statistics, data analysis and machine learning"
    ),
    "Data Analytics" => array(
        "title" => "Data Analyst",
        "skills" => "SQL, Python, Excel and data visualization"
    ),
    "Cybersecurity" => array(
        "title" => "Cybersecurity Analyst",
        "skills" => "Networking, Linux, security fundamentals and Python"
    ),
    "App Development" => array(
        "title" => "Application Developer",
        "skills" => "Java or Kotlin, programming fundamentals and databases"
    )
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rawInterests = isset($_POST["interests"]) &&
        is_array($_POST["interests"])
        ? $_POST["interests"] : array();

    foreach ($rawInterests as $interest) {
        if (in_array($interest, $allowedInterests, true) &&
            !in_array($interest, $interests, true)) {
            $interests[] = $interest;
        }
    }

    $skillLevel = isset($_POST["skill_level"])
        ? $_POST["skill_level"] : "";

    $preferredArea = isset($_POST["preferred_area"])
        ? $_POST["preferred_area"] : "";

    if (count($interests) === 0) {
        $errors[] = "Please select at least one interest.";
    }

    if (!in_array($skillLevel, $allowedLevels, true)) {
        $errors[] = "Please select a valid skill level.";
    }

    if (!in_array($preferredArea, $allowedAreas, true)) {
        $errors[] = "Please select a valid career area.";
    }

    if (empty($errors)) {
        $interestText = implode(", ", $interests);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO assessments
                (user_id, interests, skill_level, preferred_area)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                interests = VALUES(interests),
                skill_level = VALUES(skill_level),
                preferred_area = VALUES(preferred_area)"
            );

            $stmt->execute(array(
                $userId,
                $interestText,
                $skillLevel,
                $preferredArea
            ));

            $success = "Your assessment has been saved successfully!";

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Unable to save your assessment. Please try again.";
        }
    }
}

$studentName = isset($_SESSION["user_name"])
    ? $_SESSION["user_name"] : "Student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Assessment | TechPath AI</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f8fc;
            color: #172033;
        }

        nav {
            background: white;
            padding: 18px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px #1720330d;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #5b4bdb;
        }

        nav a {
            color: #5b4bdb;
            text-decoration: none;
        }

        .container {
            max-width: 760px;
            margin: 40px auto;
            padding: 0 18px;
        }

        .card {
            background: white;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 10px 35px #28205d0c;
            border: 1px solid #ececf4;
        }

        .tag {
            color: #5b4bdb;
            font-weight: bold;
            font-size: 13px;
        }

        h1 { margin-bottom: 8px; }

        .intro {
            color: #687086;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .question {
            margin-top: 26px;
        }

        .question h3 {
            font-size: 17px;
            margin-bottom: 12px;
        }

        .hint {
            color: #687086;
            font-size: 13px;
        }

        .option {
            display: block;
            background: #fafaff;
            border: 1px solid #e4e3f2;
            padding: 13px;
            margin: 9px 0;
            border-radius: 9px;
            cursor: pointer;
        }

        .option input {
            margin-right: 9px;
            accent-color: #5b4bdb;
        }

        select {
            width: 100%;
            padding: 13px;
            border: 1px solid #dfe2ec;
            border-radius: 9px;
            background: white;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 9px;
            margin-top: 28px;
            background: #5b4bdb;
            color: white;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover { background: #4636c4; }

        .message {
            padding: 14px;
            border-radius: 9px;
            margin: 12px 0;
        }

        .error {
            background: #fff0f0;
            color: #a32222;
        }

        .success {
            background: #e9f9ef;
            color: #176b39;
        }

        .recommendation {
            background: #f1efff;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .recommendation h3 {
            color: #5140c8;
            margin-top: 0;
        }

        .back {
            display: inline-block;
            margin-top: 22px;
            color: #5b4bdb;
            text-decoration: none;
        }

        @media (max-width: 550px) {
            .card { padding: 22px; }
            .container { margin: 22px auto; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">✦ TechPath AI</div>
    <a href="dashboard.php">← Dashboard</a>
</nav>

<main class="container">
    <div class="card">
        <div class="tag">PERSONALIZED CAREER PLANNING</div>

        <h1>Career Assessment 🎯</h1>

        <p class="intro">
            Hello, <?php echo htmlspecialchars(
                $studentName, ENT_QUOTES, "UTF-8"
            ); ?>!
            Tell us about your interests and skills.
            We'll suggest a technology career to explore.
        </p>

        <?php foreach ($errors as $error): ?>
            <div class="message error">
                <?php echo htmlspecialchars(
                    $error, ENT_QUOTES, "UTF-8"
                ); ?>
            </div>
        <?php endforeach; ?>

        <?php if ($success !== ""): ?>
            <div class="message success">
                <?php echo htmlspecialchars(
                    $success, ENT_QUOTES, "UTF-8"
                ); ?>
            </div>

            <div class="recommendation">
                <h3>Your suggested career</h3>
                <p><strong>
                    <?php echo htmlspecialchars(
                        $careerRecommendations[$preferredArea]["title"],
                        ENT_QUOTES, "UTF-8"
                    ); ?>
                </strong></p>

                <p>
                    Recommended skills to learn:
                    <?php echo htmlspecialchars(
                        $careerRecommendations[$preferredArea]["skills"],
                        ENT_QUOTES, "UTF-8"
                    ); ?>
                </p>

                <p class="hint">
                    This is an initial suggestion based on your selected
                    career area, not a prediction of job success.
                </p>
            </div>
        <?php endif; ?>

        <form method="POST" action="assessment.php">

            <div class="question">
                <h3>1. What are your interests?</h3>
                <p class="hint">Select all that apply.</p>

                <?php foreach ($allowedInterests as $interest): ?>
                    <label class="option">
                        <input
                            type="checkbox"
                            name="interests[]"
                            value="<?php echo htmlspecialchars(
                                $interest, ENT_QUOTES, "UTF-8"
                            ); ?>"
                            <?php echo in_array(
                                $interest, $interests, true
                            ) ? "checked" : ""; ?>
                        >
                        <?php echo htmlspecialchars(
                            $interest, ENT_QUOTES, "UTF-8"
                        ); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="question">
                <h3>2. What is your current programming skill level?</h3>

                <select name="skill_level" required>
                    <option value="">-- Select skill level --</option>
                    <?php foreach ($allowedLevels as $level): ?>
                        <option
                            value="<?php echo $level; ?>"
                            <?php echo $skillLevel === $level
                                ? "selected" : ""; ?>
                        >
                            <?php echo $level; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="question">
                <h3>3. Which career area interests you most?</h3>

                <select name="preferred_area" required>
                    <option value="">-- Select career area --</option>
                    <?php foreach ($allowedAreas as $area): ?>
                        <option
                            value="<?php echo htmlspecialchars(
                                $area, ENT_QUOTES, "UTF-8"
                            ); ?>"
                            <?php echo $preferredArea === $area
                                ? "selected" : ""; ?>
                        >
                            <?php echo htmlspecialchars(
                                $area, ENT_QUOTES, "UTF-8"
                            ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Save Assessment & Get Suggestion →</button>
        </form>

        <a class="back" href="dashboard.php">← Return to dashboard</a>
    </div>
</main>

</body>
</html>