
<?php
session_start();

require_once __DIR__ . "/config/db.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$userName = isset($_SESSION["user_name"])
    ? $_SESSION["user_name"]
    : "Student";

$totalSkills = 0;
$totalProjects = 0;
$completedTasks = 0;
$totalTasks = 0;
$progress = 0;
$careerGoal = "Explore Your Career";
$skillLevel = "Beginner";
$skillNames = array("HTML", "CSS", "JavaScript", "PHP", "MySQL");
$skillValues = array(0, 0, 0, 0, 0);

// Safely check whether a table exists
function dashboardTableExists($pdo, $table) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?"
        );
        $stmt->execute(array($table));
        return (int) $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Get actual user information
try {
    $stmt = $pdo->prepare(
        "SELECT full_name, email FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute(array($userId));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user["full_name"])) {
        $userName = $user["full_name"];
    }
} catch (Exception $e) {
    // Keep the session name if the query fails
}

// Count saved student skills
if (dashboardTableExists($pdo, "student_skills")) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM student_skills WHERE user_id = ?"
        );
        $stmt->execute(array($userId));
        $totalSkills = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        $totalSkills = 0;
    }
}

// Read assessment information if available
if (dashboardTableExists($pdo, "assessments")) {
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM assessments WHERE user_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(array($userId));
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assessment) {
            if (!empty($assessment["preferred_area"])) {
                $careerGoal = $assessment["preferred_area"];
            }

            if (!empty($assessment["skill_level"])) {
                $skillLevel = $assessment["skill_level"];
            }
        }
    } catch (Exception $e) {
        // Keep default values
    }
}

// Count roadmap progress when the table is available
if (dashboardTableExists($pdo, "roadmap_progress")) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM roadmap_progress WHERE user_id = ?"
        );
        $stmt->execute(array($userId));
        $totalTasks = (int) $stmt->fetchColumn();

        // Try to count completed rows using common column names
        $columnsStmt = $pdo->query(
            "SHOW COLUMNS FROM roadmap_progress"
        );
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

        $statusColumn = "";
        foreach (array("status", "completed", "is_completed") as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $statusColumn = $candidate;
                break;
            }
        }

        if ($statusColumn !== "") {
            if ($statusColumn === "status") {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM roadmap_progress
                     WHERE user_id = ? AND LOWER(status) = 'completed'"
                );
            } else {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM roadmap_progress
                     WHERE user_id = ? AND " . $statusColumn . " = 1"
                );
            }

            $stmt->execute(array($userId));
            $completedTasks = (int) $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        $completedTasks = 0;
        $totalTasks = 0;
    }
}

if ($totalTasks > 0) {
    $progress = (int) round(($completedTasks / $totalTasks) * 100);
}

// Count completed projects if a projects table exists
if (dashboardTableExists($pdo, "projects")) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM projects WHERE user_id = ?"
        );
        $stmt->execute(array($userId));
        $totalProjects = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        $totalProjects = 0;
    }
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TechPath AI</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">

    <style>
        :root {
            --primary: #5146e5;
            --ink: #20264b;
            --muted: #74809b;
            --line: #e5e9f5;
            --page: #f5f7ff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        a { text-decoration: none; }

        .app {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 245px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #222447, #151b35);
            color: white;
            padding: 26px 16px;
            display: flex;
            flex-direction: column;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: white;
            margin: 0 0 5px;
        }

        .logo span { color: #958aff; }

        .tagline {
            color: #c4c9e8;
            font-size: 12px;
            margin: 0 0 32px;
        }

        .nav { display: grid; gap: 8px; }

        .nav a {
            display: block;
            color: #dce2ff;
            padding: 13px 12px;
            border-radius: 11px;
            font-size: 14px;
            transition: .2s;
        }

        .nav a:hover, .nav a.active {
            background: linear-gradient(100deg, #5d5be9, #4848c7);
            color: white;
        }

        .sidebar-note {
            margin-top: auto;
            padding: 22px 8px 0;
            color: #c4c9e8;
            line-height: 1.7;
            font-size: 13px;
        }

        .main {
            flex: 1;
            min-width: 0;
            padding: 25px 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
        }

        .topbar h2 { margin: 0; font-size: 23px; }

        .subheading {
            color: var(--muted);
            margin: 6px 0 0;
            font-size: 13px;
        }

        .user-pill {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .welcome {
            background: linear-gradient(110deg, #e8e6ff, #e4f1ff 70%, #f0eaff);
            border: 1px solid #dce3ff;
            border-radius: 20px;
            padding: 27px 30px;
            margin-bottom: 22px;
        }

        .welcome h1 {
            color: #272b78;
            font-size: clamp(23px, 3vw, 31px);
            margin: 0 0 9px;
        }

        .welcome p {
            color: #56618d;
            line-height: 1.6;
            margin: 0 0 17px;
        }

        .goal {
            display: inline-block;
            background: rgba(255,255,255,.85);
            border-radius: 25px;
            padding: 10px 14px;
            color: #343b8a;
            font-size: 13px;
            font-weight: 600;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .stat {
            background: white;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 19px;
            box-shadow: 0 5px 18px rgba(41,54,104,.03);
        }

        .stat-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: #e9efff;
            color: var(--primary);
            font-size: 22px;
            margin-bottom: 12px;
        }

        .stat:nth-child(2) .stat-icon { background: #eee8ff; }
        .stat:nth-child(3) .stat-icon { background: #def8ee; color: #159d7c; }
        .stat:nth-child(4) .stat-icon { background: #fff0dc; color: #dc8a20; }

        .stat-label { color: var(--muted); font-size: 13px; }
        .stat-value { font-size: 29px; font-weight: 800; margin: 7px 0; }
        .stat small { color: var(--muted); font-size: 12px; }

        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(270px, 1fr);
            gap: 19px;
            align-items: start;
        }

        .card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 21px;
            margin-bottom: 19px;
            box-shadow: 0 5px 18px rgba(41,54,104,.025);
        }

        .card-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0 0 17px;
            font-size: 17px;
        }

        .card-title a { color: var(--primary); font-size: 12px; }

        .roadmap-step {
            display: flex;
            gap: 13px;
            align-items: flex-start;
            padding: 14px 0;
            border-bottom: 1px solid #edf0f8;
        }

        .roadmap-step:last-child { border-bottom: 0; }

        .step-number {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #eeeaff;
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .roadmap-step strong { font-size: 14px; }
        .roadmap-step p { margin: 5px 0 0; color: var(--muted); font-size: 12px; line-height: 1.5; }

        .progress-track {
            height: 9px;
            background: #e9edf7;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 13px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f8df7, #7161ef);
            border-radius: 20px;
        }

        .progress-caption {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--muted);
            margin-top: 9px;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .quick-link {
            display: block;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 13px;
            color: var(--ink);
            transition: .2s;
        }

        .quick-link:hover {
            border-color: #aaa5ff;
            background: #f8f7ff;
            transform: translateY(-2px);
        }

        .quick-link b { display: block; font-size: 14px; margin-bottom: 7px; }
        .quick-link span { color: var(--muted); font-size: 12px; line-height: 1.5; }

        .button {
            display: inline-block;
            padding: 11px 15px;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            font-size: 13px;
            font-weight: 600;
            border: 0;
            cursor: pointer;
        }

        .button:hover { background: #4036c6; }

        .footer {
            text-align: center;
            padding: 12px 0;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .sidebar { width: 205px; }
            .main { padding: 20px; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 650px) {
            .app { display: block; }
            .sidebar { width: 100%; padding: 17px; }
            .tagline, .sidebar-note { display: none; }
            .logo { margin-bottom: 15px; }
            .nav { display: flex; overflow-x: auto; }
            .nav a { white-space: nowrap; }
            .main { padding: 16px; }
            .topbar { align-items: flex-start; flex-direction: column; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .stat { padding: 14px; }
            .welcome { padding: 22px; }
            .quick-links { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
<div class="app">

    <aside class="sidebar">
        <div class="logo">🎓 TechPath <span>AI</span></div>
        <p class="tagline">Your Career. Our Guidance.</p>

        <nav class="nav">
            <a class="active" href="dashboard.php">🏠 &nbsp; Dashboard</a>
            <a href="ai_advisor.php">💬 &nbsp; AI Advisor</a>
            <a href="career_recommendation.php">🧭 &nbsp; Career Paths</a>
            <a href="assessment.php">📝 &nbsp; Career Assessment</a>
            <a href="roadmap.php">🗺️ &nbsp; Learning Roadmap</a>
            <a href="skill_gap.php">📊 &nbsp; Skill Gap Analysis</a>
            <a href="study_plan.php">📚 &nbsp; Study Plan</a>
            <a href="analytics.php">📈 &nbsp; Analytics</a>
            <a href="profile.php">👤 &nbsp; Profile</a>
            <a href="logout.php">🚪 &nbsp; Logout</a>
        </nav>

        <div class="sidebar-note">
            ✨ Small steps today,<br>
            big dreams tomorrow!
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <h2>Student Dashboard</h2>
                <p class="subheading">Your personal career growth space</p>
            </div>
            <div class="user-pill">
                👋 Hello, <?php echo h($userName); ?>
            </div>
        </header>

        <section class="welcome">
            <h1>Welcome back, <?php echo h($userName); ?>! 👋</h1>
            <p>Every skill you learn today builds your tomorrow. Keep moving forward!</p>
            <div class="goal">
                🎯 Current career interest: <?php echo h($careerGoal); ?>
            </div>
        </section>

        <section class="stats">
            <div class="stat">
                <div class="stat-icon">📚</div>
                <div class="stat-label">Skills Recorded</div>
                <div class="stat-value"><?php echo $totalSkills; ?></div>
                <small>Skills in your profile</small>
            </div>

            <div class="stat">
                <div class="stat-icon">🚀</div>
                <div class="stat-label">Projects</div>
                <div class="stat-value"><?php echo $totalProjects; ?></div>
                <small>Projects recorded in the database</small>
            </div>

            <div class="stat">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Roadmap Progress</div>
                <div class="stat-value"><?php echo $progress; ?>%</div>
                <div class="progress-track">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-icon">🌱</div>
                <div class="stat-label">Current Skill Level</div>
                <div class="stat-value" style="font-size:22px">
                    <?php echo h($skillLevel); ?>
                </div>
                <small>Based on your assessment</small>
            </div>
        </section>

        <section class="grid">
            <div>
                <div class="card">
                    <h3 class="card-title">
                        🗺️ Your Learning Roadmap
                        <a href="roadmap.php">View roadmap →</a>
                    </h3>

                    <div class="roadmap-step">
                        <div class="step-number">1</div>
                        <div>
                            <strong>Build Your Foundation</strong>
                            <p>Learn programming basics, HTML, CSS, and problem-solving.</p>
                        </div>
                    </div>

                    <div class="roadmap-step">
                        <div class="step-number">2</div>
                        <div>
                            <strong>Develop Technical Skills</strong>
                            <p>Practice JavaScript, PHP, databases, and version control.</p>
                        </div>
                    </div>

                    <div class="roadmap-step">
                        <div class="step-number">3</div>
                        <div>
                            <strong>Create Real Projects</strong>
                            <p>Build useful projects and document your work on GitHub.</p>
                        </div>
                    </div>

                    <div class="roadmap-step">
                        <div class="step-number">4</div>
                        <div>
                            <strong>Prepare for Opportunities</strong>
                            <p>Improve your resume, interview skills, and internship readiness.</p>
                        </div>
                    </div>

                    <a class="button" href="roadmap.php">Continue Learning →</a>
                </div>

                <div class="card">
                    <h3 class="card-title">⚡ Quick Access</h3>
                    <div class="quick-links">
                        <a class="quick-link" href="career_recommendation.php">
                            <b>🧭 Career Recommendations</b>
                            <span>Explore careers that match your interests.</span>
                        </a>
                        <a class="quick-link" href="skill_gap.php">
                            <b>📊 Skill Gap Analysis</b>
                            <span>Find the skills you need to improve.</span>
                        </a>
                        <a class="quick-link" href="study_plan.php">
                            <b>📅 7-Day Study Plan</b>
                            <span>Organize your learning for the week.</span>
                        </a>
                        <a class="quick-link" href="ai_advisor.php">
                            <b>🤖 AI Career Advisor</b>
                            <span>Get guidance for your next learning step.</span>
                        </a>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <h3 class="card-title">📈 Learning Progress</h3>
                    <p style="color:#74809b;font-size:13px;line-height:1.7">
                        Track your career roadmap as you complete learning activities.
                    </p>

                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?php echo $progress; ?>%"></div>
                    </div>

                    <div class="progress-caption">
                        <span><?php echo $completedTasks; ?> completed</span>
                        <span><?php echo $totalTasks; ?> recorded</span>
                    </div>

                    <?php if ($totalTasks === 0) { ?>
                        <p style="font-size:12px;color:#74809b;margin-top:15px">
                            Start your roadmap to begin tracking your progress.
                        </p>
                    <?php } ?>
                </div>

                <div class="card">
                    <h3 class="card-title">🎯 Career Goal</h3>
                    <h3 style="margin:0 0 10px;font-size:19px">
                        <?php echo h($careerGoal); ?>
                    </h3>
                    <p style="font-size:13px;line-height:1.7;color:#74809b">
                        Keep building relevant skills, completing projects, and preparing for internships.
                    </p>
                    <a class="button" href="assessment.php">Update Assessment</a>
                </div>

                <div class="card">
                    <h3 class="card-title">💡 Your Next Steps</h3>
                    <div class="roadmap-step">
                        <div class="step-number">1</div>
                        <div>
                            <strong>Review your career path</strong>
                            <p>Check your recommendations and goals.</p>
                        </div>
                    </div>
                    <div class="roadmap-step">
                        <div class="step-number">2</div>
                        <div>
                            <strong>Practice a technical skill</strong>
                            <p>Set aside time for hands-on coding.</p>
                        </div>
                    </div>
                    <div class="roadmap-step">
                        <div class="step-number">3</div>
                        <div>
                            <strong>Build your portfolio</strong>
                            <p>Keep evidence of your projects and progress.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="footer">
            © <?php echo date("Y"); ?> TechPath AI · Learn today, grow tomorrow 💜
        </footer>

    </main>
</div>
</body>
</html>
