<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz System</title>
    <link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="qspub.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🎯 Interactive Quiz System</h1>
        </div>
    </header>

    <main>
        <div class="container u-max-width">
            <div class="card text-center">
                <div class="u-font-size">🎓</div>
                <h2>Welcome to the Quiz System</h2>
                <p class="u-font-size-2">
                    An interactive platform for creating and taking multiple choice quizzes.
                    Perfect for classrooms, training sessions, and educational activities.
                </p>

                <div class="stats-grid u-grid-template-colu">
                    <a href="participant/join.php" class="btn btn-primary btn-block u-padding">
                        <div class="u-font-size-3">📱</div>
                        <div class="u-font-size-4">Join Quiz</div>
                        <div class="u-font-size-5">
                            Enter your code
                        </div>
                    </a>

                    <a href="admin/" class="btn btn-secondary btn-block u-padding">
                        <div class="u-font-size-3">⚙️</div>
                        <div class="u-font-size-4">Admin Panel</div>
                        <div class="u-font-size-5">
                            Manage quizzes
                        </div>
                    </a>
                </div>
            </div>

            <div class="card">
                <h3>Features</h3>
                <ul class="u-line-height">
                    <li>✅ Easy quiz management with CSV/JSON upload</li>
                    <li>✅ QR code generation for quick participant joining</li>
                    <li>✅ Random question selection from question pools</li>
                    <li>✅ Real-time progress tracking</li>
                    <li>✅ Timer-based quizzes</li>
                    <li>✅ Anonymous participation with color-coded identities</li>
                    <li>✅ Mobile-optimized interface</li>
                    <li>✅ Instant results and leaderboards</li>
                    <li>✅ Comprehensive logging system</li>
                </ul>
            </div>

            <div class="card">
                <h3>How It Works</h3>
                <div class="stats-grid u-grid-template-colu-2">
                    <div class="u-text-align">
                        <div class="u-font-size-3">1️⃣</div>
                        <h4>Create</h4>
                        <p>Upload your quiz questions via CSV or JSON</p>
                    </div>
                    <div class="u-text-align">
                        <div class="u-font-size-3">2️⃣</div>
                        <h4>Launch</h4>
                        <p>Start a session and share the QR code</p>
                    </div>
                    <div class="u-text-align">
                        <div class="u-font-size-3">3️⃣</div>
                        <h4>Play</h4>
                        <p>Participants join and answer questions</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="u-text-align-2">
        <p>Quiz System | Built with PHP, JavaScript, and SQLite</p>
    </footer>
</body>
</html>
