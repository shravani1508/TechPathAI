
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];

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

$stmt = $pdo->prepare(
    "SELECT skill_name FROM student_skills
     WHERE user_id = ? AND career_area = ? AND knows_skill = 0
     ORDER BY skill_name"
);
$stmt->execute(array($userId, $career));

$skillsToLearn = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $skillsToLearn[] = $row["skill_name"];
}

$days = array(
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday"
);

$activities = array(
    "Learn the basics and write short notes.",
    "Practise with small examples.",
    "Complete a beginner exercise.",
    "Build a small practical task.",
    "Revise and solve practice questions.",
    "Apply the skill in a mini-project.",
    "Review your progress and revise."
);

$plan = array();

foreach ($days as $index => $day) {
    if (count($skillsToLearn) > 0) {
        $skillIndex = $index % count($skillsToLearn);
        $skill = $skillsToLearn[$skillIndex];
        $activity = $activities[$index];
    } else {
        $skill = "Career practice";
        $activity = "Revise your known skills and improve your portfolio.";
    }

    $plan[] = array(
        "day" => $day,
        "skill" => $skill,
        "activity" => $activity
    );
}

$studentName = isset($_SESSION["user_name"])
    ? $_SESSION["user_name"] : "Student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weekly Study Plan | TechPath AI</title>

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

a { color: #5b4bdb; text-decoration: none; }

main {
    max-width: 850px;
    margin: 35px auto;
    padding: 0 18px;
}

.hero {
    padding: 28px;
    border-radius: 16px;
    background: linear-gradient(120deg, #f0efff, #edf8ff);
    margin-bottom: 22px;
}

.tag {
    color: #5b4bdb;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 1px;
}

.muted { color: #687086; line-height: 1.6; }

.day {
    background: white;
    border: 1px solid #e9e9f2;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 13px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.day-number {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: #efedff;
    color: #5b4bdb;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    flex-shrink: 0;
}

.day h3 { margin: 0 0 8px; }
.day p { margin: 5px 0; line-height: 1.5; }

.tip {
    background: white;
    padding: 20px;
    border-radius: 14px;
    margin-top: 20px;
    line-height: 1.7;
}

@media (max-width: 500px) {
    .hero { padding: 22px; }
    .day { padding: 15px; }
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
        <div class="tag">YOUR PERSONAL LEARNING SCHEDULE</div>
        <h1>My Weekly Study Plan 📅</h1>

        <p class="muted">
            Hello,
            <?php echo htmlspecialchars(
                $studentName, ENT_QUOTES, "UTF-8"
            ); ?>!
            Follow this seven-day plan for your selected career.
        </p>

        <strong>
            Career: <?php echo htmlspecialchars(
                $career, ENT_QUOTES, "UTF-8"
            ); ?>
        </strong>
    </section>

    <?php foreach ($plan as $index => $item): ?>
        <section class="day">
            <div class="day-number">
                <?php echo $index + 1; ?>
            </div>

            <div>
                <h3><?php echo htmlspecialchars(
                    $item["day"], ENT_QUOTES, "UTF-8"
                ); ?></h3>

                <p>
                    <strong>Focus:</strong>
                    <?php echo htmlspecialchars(
                        $item["skill"], ENT_QUOTES, "UTF-8"
                    ); ?>
                </p>

                <p class="muted">
                    <?php echo htmlspecialchars(
                        $item["activity"], ENT_QUOTES, "UTF-8"
                    ); ?>
                </p>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="tip">
        <strong>💡 Study tip</strong>
        <p>
            Try studying for 30–60 minutes each day.
            Practise what you learn and build small projects.
            Adjust this plan to fit your college timetable.
        </p>
        <p class="muted">
            This schedule is generated using predefined rules and your
            saved skill gaps; it is not an AI-generated prediction.
        </p>
    </section>

    <p><a href="skill_gap.php">← Review my skill gaps</a></p>
    <p><a href="roadmap.php">🧭 Return to my roadmap</a></p>
</main>
</body>
</html>