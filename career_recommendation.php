
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/db.php";

$userId = $_SESSION["user_id"];
$career = "";
$interests = "";
$skillLevel = "";
$knownSkills = array();
$recommendation = "";
$description = "";
$skillsToLearn = array();

$stmt = $pdo->prepare(
    "SELECT interests, skill_level, preferred_area
     FROM assessments
     WHERE user_id = ?"
);
$stmt->execute(array($userId));
$assessment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($assessment) {
    $career = $assessment["preferred_area"];
    $interests = $assessment["interests"];
    $skillLevel = $assessment["skill_level"];

    $stmt = $pdo->prepare(
        "SELECT skill_name
         FROM student_skills
         WHERE user_id = ?
         AND career_area = ?
         AND knows_skill = 1"
    );
    $stmt->execute(array($userId, $career));
    $knownSkills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $careerInfo = array(
        "Web Development" => array(
            "description" => "Build websites and web applications.",
            "skills" => array("HTML", "CSS", "JavaScript", "PHP", "MySQL")
        ),
        "AI and Machine Learning" => array(
            "description" => "Build intelligent systems that learn from data.",
            "skills" => array("Python", "Statistics", "Pandas", "Machine Learning")
        ),
        "Data Analytics" => array(
            "description" => "Analyze data to discover useful insights.",
            "skills" => array("Excel", "SQL", "Python", "Data Visualization")
        ),
        "Cybersecurity" => array(
            "description" => "Help protect systems, networks, and information.",
            "skills" => array("Networking", "Linux", "Security Basics", "Python")
        ),
        "App Development" => array(
            "description" => "Create applications for mobile devices.",
            "skills" => array("Programming Basics", "Java", "UI Design", "APIs")
        )
    );

    if (isset($careerInfo[$career])) {
        $recommendation = $career;
        $description = $careerInfo[$career]["description"];

        foreach ($careerInfo[$career]["skills"] as $skill) {
            if (!in_array($skill, $knownSkills)) {
                $skillsToLearn[] = $skill;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Recommendation | TechPath AI</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fc;
            color: #172554;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 28px;
            margin-bottom: 20px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
        }

        h1 { color: #4338ca; }

        .career {
            font-size: 25px;
            font-weight: bold;
            color: #4338ca;
        }

        .tag {
            display: inline-block;
            padding: 9px 13px;
            margin: 5px;
            border-radius: 20px;
            background: #e0e7ff;
            color: #3730a3;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            margin: 6px 4px 0 0;
            border-radius: 8px;
            background: #4338ca;
            color: white;
            text-decoration: none;
        }

        .muted { color: #64748b; }
    </style>
</head>
<body>
<div class="container">

    <div class="card">
        <h1>🎯 Your Career Recommendation</h1>
        <p class="muted">
            Discover a career direction based on your saved assessment.
        </p>
    </div>

    <?php if (!$assessment) { ?>

        <div class="card">
            <h2>Complete your assessment first</h2>
            <p>We need your assessment results to recommend a career path.</p>
            <a class="button" href="assessment.php">Take Assessment</a>
        </div>

    <?php } elseif (!$recommendation) { ?>

        <div class="card">
            <h2>Career information unavailable</h2>
            <p>Please update your preferred career area in your assessment.</p>
            <a class="button" href="assessment.php">Update Assessment</a>
        </div>

    <?php } else { ?>

        <div class="card">
            <h2>Recommended career direction</h2>
            <p class="career">
                <?php echo htmlspecialchars($recommendation); ?>
            </p>
            <p><?php echo htmlspecialchars($description); ?></p>

            <p><strong>Your current level:</strong>
                <?php echo htmlspecialchars($skillLevel); ?>
            </p>

            <p><strong>Your interests:</strong>
                <?php echo htmlspecialchars($interests); ?>
            </p>
        </div>

        <div class="card">
            <h2>✅ Skills you already know</h2>

            <?php if (count($knownSkills) > 0) { ?>
                <?php foreach ($knownSkills as $skill) { ?>
                    <span class="tag">
                        <?php echo htmlspecialchars($skill); ?>
                    </span>
                <?php } ?>
            <?php } else { ?>
                <p class="muted">
                    You haven't marked any skills as known for this career yet.
                </p>
            <?php } ?>
        </div>

        <div class="card">
            <h2>📚 Skills to learn next</h2>

            <?php if (count($skillsToLearn) > 0) { ?>
                <?php foreach ($skillsToLearn as $skill) { ?>
                    <span class="tag">
                        <?php echo htmlspecialchars($skill); ?>
                    </span>
                <?php } ?>
            <?php } else { ?>
                <p>You've marked all the suggested skills as known. Keep practising with projects!</p>
            <?php } ?>

            <br>
            <a class="button" href="skill_gap.php">Update My Skills</a>
            <a class="button" href="study_plan.php">View Study Plan</a>
            <a class="button" href="dashboard.php">Dashboard</a>
        </div>

    <?php } ?>

</div>
</body>
</html>