
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/db.php";

$userId = (int) $_SESSION["user_id"];

/* Student name */
$studentName = isset($_SESSION["user_name"])
    ? $_SESSION["user_name"] : "Student";

/* Get career assessment */
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

/* Roadmap progress: each current roadmap has 6 steps */
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS completed_count
     FROM roadmap_progress
     WHERE user_id = ?
     AND career_area = ?
     AND completed = 1"
);
$stmt->execute(array($userId, $career));
$roadmapResult = $stmt->fetch(PDO::FETCH_ASSOC);

$completedSteps = (int) $roadmapResult["completed_count"];
$totalSteps = 6;

if ($completedSteps > $totalSteps) {
    $completedSteps = $totalSteps;
}

$roadmapPercentage = (int) round(
    ($completedSteps / $totalSteps) * 100
);

/* Skill analysis */
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total_skills,
            SUM(CASE WHEN knows_skill = 1 THEN 1 ELSE 0 END)
                AS known_skills
     FROM student_skills
     WHERE user_id = ? AND career_area = ?"
);
$stmt->execute(array($userId, $career));
$skillResult = $stmt->fetch(PDO::FETCH_ASSOC);

$totalSkills = (int) $skillResult["total_skills"];
$knownSkills = (int) $skillResult["known_skills"];
$gapSkills = $totalSkills - $knownSkills;

$skillPercentage = 0;

if ($totalSkills > 0) {
    $skillPercentage = (int) round(
        ($knownSkills / $totalSkills) * 100
    );
}

/* Get skills to learn */
$stmt = $pdo->prepare(
    "SELECT skill_name FROM student_skills
     WHERE user_id = ?
     AND career_area = ?
     AND knows_skill = 0
     ORDER BY skill_name"
);
$stmt->execute(array($userId, $career));

$skillsToLearn = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $skillsToLearn[] = $row["skill_name"];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Progress Analytics | TechPath AI</title>

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

        a {
            text-decoration: none;
            color: #5b4bdb;
        }

        main {
            max-width: 1050px;
            margin: 32px auto;
            padding: 0 18px;
        }

        .hero {
            background: linear-gradient(120deg, #efedff, #eaf7ff);
            padding: 30px;
            border-radius: 18px;
            margin-bottom: 22px;
        }

        .tag {
            color: #5b4bdb;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .muted {
            color: #687086;
            line-height: 1.6;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .card {
            background: white;
            padding: 22px;
            border: 1px solid #e8e9f2;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
            margin: 10px 0;
            color: #5b4bdb;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            border-radius: 20px;
            background: #e9e7f7;
            overflow: hidden;
            margin: 12px 0;
        }

        .progress-fill {
            height: 100%;
            background: #5b4bdb;
            border-radius: 20px;
        }

        .green { background: #27976b; }
        .purple { background: #5b4bdb; }

        .skill-row { margin: 18px 0; }

        .skill-heading {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .skill-heading span:last-child {
            color: #687086;
            font-size: 14px;
        }

        .recommendation {
            padding: 14px;
            background: #f2f0ff;
            border-radius: 10px;
            margin: 10px 0;
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .links a {
            display: inline-block;
            padding: 12px 16px;
            background: #5b4bdb;
            color: white;
            border-radius: 9px;
            font-weight: bold;
        }

        .links a.secondary {
            background: white;
            border: 1px solid #dcd9f6;
            color: #5b4bdb;
        }

        @media (max-width: 700px) {
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
        <div class="tag">YOUR PERSONAL PROGRESS REPORT</div>

        <h1>Progress Analytics 📊</h1>

        <p class="muted">
            Welcome,
            <?php echo htmlspecialchars(
                $studentName, ENT_QUOTES, "UTF-8"
            ); ?>!
            Review your learning progress and decide what to learn next.
        </p>

        <strong>
            Career goal:
            <?php echo htmlspecialchars(
                $career, ENT_QUOTES, "UTF-8"
            ); ?>
        </strong>
    </section>

    <section class="stats">

        <div class="card">
            <div class="muted">Roadmap completed</div>

            <div class="stat-number">
                <?php echo $roadmapPercentage; ?>%
            </div>

            <div class="progress-track">
                <div class="progress-fill"
                     style="width: <?php echo $roadmapPercentage; ?>%;">
                </div>
            </div>

            <p class="muted">
                <?php echo $completedSteps; ?> of
                <?php echo $totalSteps; ?> steps completed
            </p>
        </div>

        <div class="card">
            <div class="muted">Skills marked as known</div>

            <div class="stat-number">
                <?php echo $knownSkills; ?>
            </div>

            <p class="muted">
                Out of <?php echo $totalSkills; ?> skills assessed
            </p>
        </div>

        <div class="card">
            <div class="muted">Skills to learn</div>

            <div class="stat-number">
                <?php echo $gapSkills; ?>
            </div>

            <p class="muted">
                <?php
                if ($totalSkills == 0) {
                    echo "Complete your skill gap analysis first.";
                } elseif ($gapSkills == 0) {
                    echo "All listed skills are marked as known.";
                } else {
                    echo "Keep practising your missing skills.";
                }
                ?>
            </p>
        </div>

    </section>

    <section class="card">
        <h2>Learning Progress</h2>

        <div class="skill-row">
            <div class="skill-heading">
                <strong>Career roadmap</strong>
                <span><?php echo $roadmapPercentage; ?>%</span>
            </div>

            <div class="progress-track">
                <div class="progress-fill"
                     style="width: <?php echo $roadmapPercentage; ?>%;">
                </div>
            </div>
        </div>

        <div class="skill-row">
            <div class="skill-heading">
                <strong>Skills marked as known</strong>
                <span>
                    <?php
                    if ($totalSkills > 0) {
                        echo $skillPercentage . "%";
                    } else {
                        echo "Not assessed";
                    }
                    ?>
                </span>
            </div>

            <div class="progress-track">
                <div class="progress-fill green"
                     style="width: <?php echo $skillPercentage; ?>%;">
                </div>
            </div>
        </div>

        <p class="muted">
            These indicators use your saved roadmap and skill assessment.
            They measure recorded activity, not professional job readiness.
        </p>
    </section>

    <section class="card">
        <h2>Recommended Next Steps 🎯</h2>

        <?php if ($totalSkills == 0): ?>

            <div class="recommendation">
                Complete your skill gap analysis to identify skills
                you need to learn.
            </div>

        <?php elseif ($gapSkills > 0): ?>

            <p class="muted">
                Focus on these skills you marked as not yet known:
            </p>

            <?php foreach ($skillsToLearn as $skill): ?>
                <div class="recommendation">
                    📘 <?php echo htmlspecialchars(
                        $skill, ENT_QUOTES, "UTF-8"
                    ); ?>
                </div>
            <?php endforeach; ?>

        <?php else: ?>

            <div class="recommendation">
                🎉 You have marked all listed skills as known.
                Practise them through projects and review your roadmap.
            </div>

        <?php endif; ?>

        <?php if ($completedSteps < $totalSteps): ?>
            <div class="recommendation">
                🧭 Continue your roadmap:
                <?php echo $totalSteps - $completedSteps; ?>
                steps remain.
            </div>
        <?php else: ?>
            <div class="recommendation">
                🎉 You have completed all recorded roadmap steps.
                Consider building a portfolio project.
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Continue Learning</h2>

        <div class="links">
            <a href="roadmap.php">🧭 Career Roadmap</a>
            <a href="skill_gap.php" class="secondary">
                📊 Skill Gap Analysis
            </a>
            <a href="study_plan.php" class="secondary">
                📅 Weekly Study Plan
            </a>
            <a href="assessment.php" class="secondary">
                🎯 Career Assessment
            </a>
        </div>
    </section>

</main>
</body>
</html>