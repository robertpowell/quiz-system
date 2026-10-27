<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if participant is authenticated
if (!isset($_SESSION['participant_id']) || !isset($_SESSION['session_id'])) {
    header('Location: join.php');
    exit;
}

$db = Database::getInstance();
$participantId = $_SESSION['participant_id'];
$sessionId = $_SESSION['session_id'];

// Get participant and session info
$participants = $db->getParticipants($sessionId);
$participant = null;
foreach ($participants as $p) {
    if ($p['id'] == $participantId) {
        $participant = $p;
        break;
    }
}

if (!$participant) {
    header('Location: join.php');
    exit;
}

$session = $db->getSession($sessionId);
$quiz = $db->getQuiz($session['quiz_id']);

// If quiz has started, redirect to quiz page
if ($session['status'] === 'active') {
    header('Location: quiz.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting Room</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="waiting.1.css?v=<?php echo @filemtime(__DIR__."/waiting.1.css"); ?>">
<link rel="stylesheet" href="../qspub.css">
</head>
<body data-waiting-config='<?= htmlspecialchars(json_encode(["sessionId" => $sessionId]), ENT_QUOTES, "UTF-8") ?>'>
    <main>
        <div class="container u-max-width-5">
            <div class="card text-center">
                <h1 class="u-color-2">You're In! 🎉</h1>

                <div class="your-color" data-style="background-color: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>;">
                    <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                </div>

                <h2 data-style="color: <?= $participant['color_hex'] ?>;">
                    You are <?= $participant['color_name'] ?>
                </h2>

                <div class="waiting-message">
                    Waiting for quiz to start...
                </div>

                <div class="stats-grid u-grid-template-colu">
                    <div class="stat-card">
                        <div class="stat-label">Quiz</div>
                        <div class="stat-value u-font-size-9">
                            <?= htmlspecialchars($quiz['name']) ?>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Questions</div>
                        <div class="stat-value"><?= $quiz['questions_per_quiz'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Time Limit</div>
                        <div class="stat-value"><?= floor($quiz['time_limit'] / 60) ?></div>
                        <div class="stat-label">minutes</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Participants</div>
                        <div class="stat-value" id="participantCount"><?= count($participants) ?></div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Get Ready!</strong><br>
                    The quiz will start when your instructor is ready.<br>
                    Make sure you have a stable internet connection.
                </div>
            </div>
        </div>
    </main>

    <script src="waiting.page.js?v=<?php echo @filemtime(__DIR__."/waiting.page.js"); ?>"></script>
<script src="../qspub-style.js"></script>
</body>
</html>
