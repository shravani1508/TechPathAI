
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];
$error = "";
$success = "";

$roadmaps = array(
    "Web Development" => array(
        array("HTML Fundamentals", "Learn page structure, headings, forms and semantic HTML."),
        array("CSS and Responsive Design", "Practice layouts, Flexbox, Grid and mobile-friendly pages."),
        array("JavaScript Basics", "Learn variables, functions, events and DOM manipulation."),
        array("PHP Fundamentals", "Understand forms, conditions, functions and sessions."),
        array("MySQL and Database Operations", "Practice tables, SQL queries and prepared statements."),
        array("Build a Full-Stack Project", "Build and test a complete website using PHP and MySQL.")
    ),
    "AI and Machine Learning" => array(
        array("Python Fundamentals", "Learn variables, conditions, loops, functions and data structures."),
        array("Math and Statistics Basics", "Understand averages, probability and basic statistics."),
        array("Data Analysis with Pandas", "Load, clean and explore datasets."),
        array("Machine Learning Fundamentals", "Learn supervised learning, classification and regression."),
        array("Model Evaluation", "Practice train-test splits, accuracy, precision and recall."),
        array("Build an AI/ML Project", "Train and evaluate a model on a suitable dataset.")
    ),
    "Data Analytics" => array(
        array("Excel Fundamentals", "Practice formulas, sorting, filtering and charts."),
        array("SQL Fundamentals", "Learn SELECT, WHERE, GROUP BY and JOIN."),
        array("Python for Data Analysis", "Use Python and Pandas to process data."),
        array("Data Visualization", "Create meaningful charts and dashboards."),
        array("Exploratory Data Analysis", "Find patterns and explain insights from a dataset."),
        array("Build an Analytics Project", "Analyze a real dataset and present your findings.")
    ),
    "Cybersecurity" => array(
        array("Computer Networking", "Learn IP addresses, DNS, ports and network protocols."),
        array("Linux Fundamentals", "Practice commands, files, permissions and processes."),
        array("Security Fundamentals", "Understand authentication, encryption and access control."),
        array("Web Security Basics", "Learn common web vulnerabilities in a safe lab."),
        array("Security Tools and Logs", "Practice defensive monitoring in an authorized environment."),
        array("Build a Security Lab Project", "Document a defensive security exercise in your own lab.")
    ),
    "App Development" => array(
        array("Programming Fundamentals", "Learn variables, conditions, loops and functions."),
        array("Java Fundamentals", "Practice classes, objects and basic OOP concepts."),
        array("Mobile UI Design", "Learn layouts, navigation and accessible interfaces."),
        array("App Data Storage", "Understand databases and local data persistence."),
        array("API Integration", "Learn how applications exchange data with APIs."),
        array("Build a Mobile App", "Create and test a useful application prototype.")
    )
);

$stmt = $pdo->prepare(
    "SELECT preferred_area FROM assessments WHERE user_id = ?"
);
$stmt->execute(array($userId));
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    header("Location: assessment.php");
    exit;
}

$careerArea = $assessment["preferred_area"];

if (!isset($roadmaps[$careerArea])) {
    exit("No roadmap is available for this career area yet.");
}

$steps = $roadmaps[$careerArea];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submitted = isset($_POST["completed"]) &&
        is_array($_POST["completed"])
        ? $_POST["completed"] : array();

    $validSteps = array();

    foreach ($submitted as $number) {
        $number = filter_var($number, FILTER_VALIDATE_INT);

        if ($number !== false &&
            $number >= 1 &&
            $number <= count($steps)) {
            $validSteps[] = (int) $number;
        }
    }

    try {
        $pdo->beginTransaction();

        $save = $pdo->prepare(
            "INSERT INTO roadmap_progress
            (user_id, career_area, step_number, completed)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE completed = VALUES(completed)"
        );

        for ($i = 1; $i <= count($steps); $i++) {
            $isComplete = in_array($i, $validSteps, true) ? 1 : 0;

            $save->execute(array(
                $userId,
                $careerArea,
                $i,
                $isComplete
            ));
        }

        $pdo->commit();
        $success = "Your learning progress has been saved!";
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());
        $error = "Unable to save progress. Please try again.";
    }
}

$getProgress = $pdo->prepare(
    "SELECT step_number, completed
     FROM roadmap_progress
     WHERE user_id = ? AND career_area = ?"
);
$getProgress->execute(array($userId, $careerArea));

$completedSteps = array();

while ($row = $getProgress->fetch(PDO::FETCH_ASSOC)) {
    if ((int) $row["completed"] === 1) {
        $completedSteps[] = (int) $row["step_number"];
    }
}

$totalSteps = count($steps);
$completedCount = count($completedSteps);
$percentage = (int) round(
    ($completedCount / $totalSteps) * 100
);

$studentName = isset($_SESSION["user_name"])
    ? $_SESSION["user_name"] : "Student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Learning Roadmap | TechPath AI</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f8fc;
            color: #172033;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 18px 7%;
            box-shadow: 0 2px 12px #1720330d;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: #5b4bdb;
        }

        a { color: #5b4bdb; text-decoration: none; }

        .container {
            max-width: 850px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .hero {
            background: linear-gradient(120deg, #f0efff, #edf8ff);
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 24px;
        }

        .tag {
            font-size: 12px;
            font-weight: bold;
            color: #5b4bdb;
            letter-spacing: 1px;
        }

        h1 { margin-bottom: 8px; }

        .muted { color: #687086; line-height: 1.6; }

        .progress-card, .step-card {
            background: white;
            padding: 22px;
            border-radius: 14px;
            border: 1px solid #ececf4;
            margin-bottom: 15px;
        }

        .progress-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .track {
            height: 12px;
            background: #e9e7f8;
            border-radius: 20px;
            overflow: hidden;
        }

        .fill {
            height: 100%;
            background: #5b4bdb;
            border-radius: 20px;
        }

        .step-card {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .step-card input {
            width: 20px;
            height: 20px;
            margin-top: 3px;
            accent-color: #5b4bdb;
            flex-shrink: 0;
        }

        .step-card h3 { margin: 0 0 7px; }

        .step-card p {
            margin: 0;
            color: #687086;
            line-height: 1.5;
            font-size: 14px;
        }

        .step-card.done { background: #f3fbf5; }

        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 9px;
            background: #5b4bdb;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }

        button:hover { background: #4636c4; }

        .message {
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 15px;
        }

        .success { background: #e9f9ef; color: #176b39; }
        .error { background: #fff0f0; color: #a32222; }

        .back { display: inline-block; margin-top: 20px; }

        @media (max-width: 550px) {
            .hero { padding: 22px; }
            .progress-card, .step-card { padding: 17px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">✦ TechPath AI</div>
    <a href="dashboard.php">← Dashboard</a>
</nav>

<main class="container">

    <section class="hero">
        <div class="tag">YOUR PERSONAL LEARNING PLAN</div>
        <h1>My Career Roadmap 🧭</h1>
        <p class="muted">
            Hello, <?php echo htmlspecialchars(
                $studentName, ENT_QUOTES, "UTF-8"
            ); ?>! Follow these learning steps at your own pace.
        </p>
        <strong>
            Career: <?php echo htmlspecialchars(
                $careerArea, ENT_QUOTES, "UTF-8"
            ); ?>
        </strong>
    </section>

    <?php if ($success !== ""): ?>
        <div class="message success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </div>
    <?php endif; ?>

    <section class="progress-card">
        <div class="progress-head">
            <strong>Your progress</strong>
            <strong><?php echo $percentage; ?>%</strong>
        </div>

        <div class="track">
            <div class="fill" style="width: <?php echo $percentage; ?>%;"></div>
        </div>

        <p class="muted">
            <?php echo $completedCount; ?> of
            <?php echo $totalSteps; ?> learning steps completed.
        </p>
    </section>

    <form method="POST" action="roadmap.php">
        <?php foreach ($steps as $index => $step): ?>
            <?php $number = $index + 1; ?>
            <?php $isDone = in_array(
                $number, $completedSteps, true
            ); ?>

            <label class="step-card <?php echo $isDone ? "done" : ""; ?>">
                <input
                    type="checkbox"
                    name="completed[]"
                    value="<?php echo $number; ?>"
                    <?php echo $isDone ? "checked" : ""; ?>
                >

                <div>
                    <h3>
                        Step <?php echo $number; ?>:
                        <?php echo htmlspecialchars(
                            $step[0], ENT_QUOTES, "UTF-8"
                        ); ?>
                    </h3>
                    <p><?php echo htmlspecialchars(
                        $step[1], ENT_QUOTES, "UTF-8"
                    ); ?></p>
                </div>
            </label>
        <?php endforeach; ?>

        <button type="submit">Save My Progress ✓</button>
    </form>

    <a class="back" href="assessment.php">← Review my career assessment</a>

</main>
</body>
</html>