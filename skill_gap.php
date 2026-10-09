
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];
$success = "";
$error = "";

/* Skills required for each career */
$careerSkills = array(
    "Web Development" => array(
        "HTML", "CSS", "JavaScript", "PHP", "MySQL"
    ),
    "AI and Machine Learning" => array(
        "Python", "Statistics", "Pandas", "Machine Learning",
        "Model Evaluation"
    ),
    "Data Analytics" => array(
        "Excel", "SQL", "Python", "Data Visualization",
        "Statistics"
    ),
    "Cybersecurity" => array(
        "Networking", "Linux", "Security Fundamentals",
        "Web Security", "Python"
    ),
    "App Development" => array(
        "Java", "OOP Concepts", "Mobile UI Design",
        "Database Management", "API Integration"
    )
);

/* Get student's selected career */
$stmt = $pdo->prepare(
    "SELECT preferred_area FROM assessments WHERE user_id = ?"
);
$stmt->execute(array($userId));
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assessment) {
    header("Location: assessment.php");
    exit;
}

$career = $assessment["preferred_area"];

if (!isset($careerSkills[$career])) {
    exit("No skill analysis is available for this career.");
}

$skills = $careerSkills[$career];

/* Save student's known skills */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected = isset($_POST["known"]) &&
        is_array($_POST["known"]) ? $_POST["known"] : array();

    /* Ignore skill names not in the approved list */
    $selected = array_intersect($selected, $skills);

    try {
        $pdo->beginTransaction();

        $save = $pdo->prepare(
            "INSERT INTO student_skills
            (user_id, career_area, skill_name, knows_skill)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            knows_skill = VALUES(knows_skill)"
        );

        foreach ($skills as $skill) {
            $known = in_array($skill, $selected, true) ? 1 : 0;

            $save->execute(array(
                $userId, $career, $skill, $known
            ));
        }

        $pdo->commit();
        $success = "Your skills have been saved successfully!";
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());
        $error = "Unable to save skills. Please try again.";
    }
}

/* Load saved skills */
$get = $pdo->prepare(
    "SELECT skill_name FROM student_skills
     WHERE user_id = ? AND career_area = ? AND knows_skill = 1"
);
$get->execute(array($userId, $career));

$knownSkills = array();

while ($row = $get->fetch(PDO::FETCH_ASSOC)) {
    $knownSkills[] = $row["skill_name"];
}

$total = count($skills);
$knownCount = count($knownSkills);
$gapCount = $total - $knownCount;
$percentage = (int) round(($knownCount / $total) * 100);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Gap Analysis | TechPath AI</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f7f8fc;
            color: #20243a;
        }

        nav {
            padding: 18px 7%;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px #00000008;
        }

        .logo {
            color: #5b4bdb;
            font-size: 22px;
            font-weight: bold;
        }

        a {
            color: #5b4bdb;
            text-decoration: none;
        }

        main {
            max-width: 760px;
            margin: 35px auto;
            padding: 0 18px;
        }

        .hero, .card, .skill {
            background: white;
            border: 1px solid #e9e9f2;
            border-radius: 15px;
            padding: 22px;
            margin-bottom: 16px;
        }

        .hero {
            background: linear-gradient(120deg, #f0efff, #edf8ff);
            padding: 30px;
        }

        .tag {
            color: #5b4bdb;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .muted {
            color: #687086;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .stat {
            background: white;
            padding: 18px 10px;
            border: 1px solid #e9e9f2;
            border-radius: 12px;
            text-align: center;
        }

        .number {
            display: block;
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .track {
            height: 12px;
            background: #e8e6f7;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 12px;
        }

        .fill {
            height: 12px;
            background: #5b4bdb;
        }

        .skill {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .skill input {
            width: 20px;
            height: 20px;
            accent-color: #5b4bdb;
        }

        .skill span { flex: 1; }

        .skill em {
            font-size: 12px;
            font-style: normal;
            color: #687086;
        }

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
        }

        .message {
            padding: 13px;
            border-radius: 9px;
            margin-bottom: 15px;
        }

        .success { background: #e8f8ee; color: #176b39; }
        .error { background: #fff0f0; color: #a32222; }

        @media (max-width: 500px) {
            .stats { grid-template-columns: 1fr; }
            .hero { padding: 22px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">✦ TechPath AI</div>
    <a href="dashboard.php">← Dashboard</a>
</nav>

<main>
    <section class="hero">
        <div class="tag">KNOW YOUR SKILLS</div>
        <h1>Skill Gap Analysis 📊</h1>
        <p class="muted">
            Select the skills you already know. We will identify
            the remaining skills you can focus on learning.
        </p>
        <strong>
            Career: <?php echo htmlspecialchars(
                $career, ENT_QUOTES, "UTF-8"
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

    <section class="card">
        <h3>Your skill overview</h3>

        <div class="stats">
            <div class="stat">
                <span class="number"><?php echo $total; ?></span>
                <span class="muted">Required skills</span>
            </div>

            <div class="stat">
                <span class="number"><?php echo $knownCount; ?></span>
                <span class="muted">Skills selected</span>
            </div>

            <div class="stat">
                <span class="number"><?php echo $gapCount; ?></span>
                <span class="muted">Skills to learn</span>
            </div>
        </div>

        <p>
            <strong><?php echo $percentage; ?>% of listed skills marked as known</strong>
        </p>

        <div class="track">
            <div class="fill" style="width: <?php echo $percentage; ?>%;"></div>
        </div>
    </section>

    <form method="POST" action="skill_gap.php">
        <h2>Which skills do you already know?</h2>
        <p class="muted">
            Tick a skill only if you have some knowledge or practice in it.
        </p>

        <?php foreach ($skills as $skill): ?>
            <label class="skill">
                <input
                    type="checkbox"
                    name="known[]"
                    value="<?php echo htmlspecialchars(
                        $skill, ENT_QUOTES, "UTF-8"
                    ); ?>"
                    <?php echo in_array(
                        $skill, $knownSkills, true
                    ) ? "checked" : ""; ?>
                >

                <span><?php echo htmlspecialchars(
                    $skill, ENT_QUOTES, "UTF-8"
                ); ?></span>

                <em>
                    <?php echo in_array(
                        $skill, $knownSkills, true
                    ) ? "Marked known" : "Needs review"; ?>
                </em>
            </label>
        <?php endforeach; ?>

        <button type="submit">Analyze & Save My Skills</button>
    </form>

    <section class="card">
        <h3>🎯 Your learning priorities</h3>

        <?php if ($gapCount === 0): ?>
            <p>You've marked all listed skills as known. Keep practising them through projects and practical exercises.</p>
        <?php else: ?>
            <p class="muted">
                Based on your selections, focus on these skills next:
            </p>

            <ul>
                <?php foreach ($skills as $skill): ?>
                    <?php if (!in_array($skill, $knownSkills, true)): ?>
                        <li><?php echo htmlspecialchars(
                            $skill, ENT_QUOTES, "UTF-8"
                        ); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="muted">
            This is a rule-based self-assessment. The results depend
            on your selections and are not a professional certification.
        </p>
    </section>

    <p><a href="roadmap.php">🧭 Go to My Learning Roadmap</a></p>
</main>

</body>
</html>