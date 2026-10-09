
<?php
session_start();

// Redirect logged-in users to the dashboard
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechPath AI - Career Guidance</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
            color: #1e1b4b;
            min-height: 100vh;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 8%;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            color: #4f46e5;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 9px;
            font-weight: bold;
            transition: 0.2s;
        }

        .login {
            background: white;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }

        .register {
            background: #4f46e5;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .hero {
            max-width: 950px;
            margin: 75px auto 55px;
            padding: 20px;
            text-align: center;
        }

        .tag {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            padding: 9px 16px;
            border-radius: 25px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        h1 {
            font-size: clamp(36px, 6vw, 60px);
            line-height: 1.15;
            margin-bottom: 22px;
        }

        h1 span {
            color: #4f46e5;
        }

        .description {
            color: #64748b;
            font-size: 18px;
            line-height: 1.8;
            max-width: 700px;
            margin: 0 auto 30px;
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .hero-buttons .btn {
            padding: 15px 25px;
        }

        .features {
            max-width: 1000px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .feature {
            background: white;
            padding: 28px 22px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(30, 27, 75, 0.06);
            border: 1px solid #e0e7ff;
        }

        .icon {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .feature h3 {
            margin-bottom: 12px;
        }

        .feature p {
            color: #64748b;
            line-height: 1.7;
            font-size: 14px;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 700px) {
            nav {
                padding: 20px;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .hero {
                margin-top: 35px;
            }

            .description {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

<nav>
    <div class="logo">🚀 TechPath AI</div>

    <div class="nav-buttons">
        <a href="login.php" class="btn login">Login</a>
        <a href="register.php" class="btn register">Register</a>
    </div>
</nav>

<section class="hero">
    <div class="tag">✨ Your AI-Powered Career Companion</div>

    <h1>
        Discover Your Path.<br>
        <span>Build Your Future.</span>
    </h1>

    <p class="description">
        Discover suitable technology careers, identify skill gaps,
        follow personalized learning roadmaps, and prepare for internships
        with TechPath AI.
    </p>

    <div class="hero-buttons">
        <a href="register.php" class="btn register">Get Started →</a>
        <a href="login.php" class="btn login">Already have an account?</a>
    </div>
</section>

<section class="features">
    <div class="feature">
        <div class="icon">🤖</div>
        <h3>AI Career Advisor</h3>
        <p>Get guidance on technology careers, programming skills, projects, and internships.</p>
    </div>

    <div class="feature">
        <div class="icon">🗺️</div>
        <h3>Personalized Roadmap</h3>
        <p>Follow a structured learning path to develop skills for your chosen career.</p>
    </div>

    <div class="feature">
        <div class="icon">📊</div>
        <h3>Track Your Progress</h3>
        <p>Review your skills, identify gaps, and plan your next learning steps.</p>
    </div>
</section>

<footer>
    © 2026 TechPath AI | Empowering Students to Build Their Future
</footer>

</body>
</html>
