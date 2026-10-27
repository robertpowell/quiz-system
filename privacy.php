<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Quiz System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="privacy.1.css?v=<?php echo @filemtime(__DIR__."/privacy.1.css"); ?>">
<link rel="stylesheet" href="qspub.css">
</head>
<body>
    <main>
        <div class="privacy-container">
            <div class="card">
                <div class="privacy-header">
                    <h1>Privacy Policy</h1>
                    <p>Last updated: January 2025</p>
                </div>

                <div class="section">
                    <h2>Overview</h2>
                    <p>This quiz system is designed for interactive learning and assessment. We are committed to protecting your privacy and being transparent about how we handle data.</p>
                </div>

                <div class="section">
                    <h2>Cookies We Use</h2>
                    <p>This site uses only <span class="highlight">essential cookies</span> that are strictly necessary for the site to function:</p>
                    <ul>
                        <li><strong>Session Cookie</strong> - Used to track your participation during a quiz session. This allows you to navigate between questions and maintain your progress. The cookie is temporary and is deleted when you close your browser.</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>What We Don't Do</h2>
                    <ul>
                        <li>We do <strong>not</strong> use analytics or tracking cookies</li>
                        <li>We do <strong>not</strong> use advertising cookies</li>
                        <li>We do <strong>not</strong> share any data with third parties</li>
                        <li>We do <strong>not</strong> track your browsing activity</li>
                    </ul>
                </div>

                <div class="section">
                    <h2>Data Collected During Quizzes</h2>
                    <p>When you participate in a quiz, we collect:</p>
                    <ul>
                        <li>Your assigned colour identifier (no personal name required)</li>
                        <li>Your answers to quiz questions</li>
                        <li>Your score and completion time</li>
                    </ul>
                    <p>This data is used solely for the quiz session and displaying results. No personal identifying information is required to participate.</p>
                </div>

                <div class="section">
                    <h2>Data Retention</h2>
                    <p>Quiz session data is retained only for the duration needed by the quiz administrator. Sessions can be deleted at any time by the administrator. No long-term personal data is stored.</p>
                </div>

                <div class="section">
                    <h2>How I Manage the Content on This Site</h2>
                    <p>I can delete or change the content of this site and these Terms at any time, without notifying you or having any legal responsibility towards you. I'll always do my best to update the site so that the content is correct, however, I won't be liable to you for any losses you suffer from your reliance on any outdated materials.</p>
                </div>

                <div class="section">
                    <h2>Which Jurisdiction Governs These Terms?</h2>
                    <p>These Terms are governed by the laws of England and Wales and are subject to the exclusive jurisdiction of the courts of England and Wales.</p>
                </div>

                <div class="section">
                    <h2>Contact</h2>
                    <p>If you have any questions about this privacy policy, please contact the quiz administrator.</p>
                </div>

                <a href="participant/join.php" class="btn btn-primary back-link">Back to Join Quiz</a>
            </div>
        </div>
    </main>
</body>
</html>
