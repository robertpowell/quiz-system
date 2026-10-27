<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
quiz_require_csrf();

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/qr-generator.php';

$db = Database::getInstance();
$qr = new QRCodeGenerator();

$quizId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($quizId == 0 && $sessionId == 0) {
    header('Location: quizzes.php');
    exit;
}

// If no session, create one
if ($sessionId == 0 && $quizId > 0) {
    $quiz = $db->getQuiz($quizId);
    if (!$quiz) {
        die('Quiz not found');
    }

    // Create new session
    $sessionId = $db->createSession($quizId);
    log_activity('quiz-launches', 'Quiz session created', [
        'quiz_id' => $quizId,
        'session_id' => $sessionId,
        'quiz_name' => $quiz['name']
    ]);

    // Redirect to session view
    header('Location: launch-quiz.php?session=' . $sessionId);
    exit;
}

// Get session and quiz details
$session = $db->getSession($sessionId);
if (!$session) {
    die('Session not found');
}

$quiz = $db->getQuiz($session['quiz_id']);
$participants = $db->getParticipants($sessionId);

// Generate join URL and QR code
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
           '://' . $_SERVER['HTTP_HOST'] .
           str_replace('/admin', '', dirname($_SERVER['SCRIPT_NAME']));
$joinUrl = $baseUrl . '/participant/join.php?code=' . $session['session_code'];

// Handle session actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die('Invalid request');
    }

    $action = $_POST['action'];

    switch ($action) {
        case 'start':
            $db->startSession($sessionId);
            log_activity('quiz-launches', 'Quiz started', [
                'session_id' => $sessionId,
                'participants' => count($participants)
            ]);
            header('Location: monitor-quiz.php?session=' . $sessionId);
            exit;

        case 'cancel':
            $db->endSession($sessionId);
            log_activity('quiz-launches', 'Quiz session cancelled', [
                'session_id' => $sessionId
            ]);
            header('Location: quizzes.php');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Launch Quiz - <?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="launch-quiz.1.css?v=<?php echo @filemtime(__DIR__."/launch-quiz.1.css"); ?>">
<link rel="stylesheet" href="qsadmin.css?v=<?php echo @filemtime(__DIR__."/qsadmin.css"); ?>">
</head>
<bodyUTF-8") ? data-launch-config='<?= htmlspecialchars(json_encode(["joinUrl" => $joinUrl, "sessionId" => $sessionId]), ENT_QUOTES, "UTF-8") ?>'>">
    <header>
        <div class="container">
            <h1>Quiz System Admin</h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="topics.php">Topics</a>
                <a href="quizzes.php">Quizzes</a>
                <a href="upload.php">Upload Quiz</a>
                <a href="sessions.php" class="active">Active Sessions</a>
                <a href="logs.php">Logs</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h2 class="mb-3">Launch Quiz: <?= htmlspecialchars($quiz['name']) ?></h2>

            <div class="stats-grid mb-3">
                <div class="stat-card">
                    <div class="stat-label">Topic</div>
                    <div class="stat-value u-font-size">
                        <?= htmlspecialchars($quiz['topic_name']) ?>
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
                    <div class="participant-count" id="participantCount"><?= count($participants) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Participant Join Information</h3>
                </div>

                <div class="qr-container">
                    <h3>Scan QR Code or Enter Code</h3>
                    <div id="qrcode"></div>
                    <div class="session-code"><?= $session['session_code'] ?></div>
                    <p>Join URL: <a href="<?= $joinUrl ?>" target="_blank"><?= $joinUrl ?></a></p>
                </div>
            </div>

            <!-- Current Participants -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Participants (<span id="participantListCount"><?= count($participants) ?></span>)</h3>
                </div>
                <div id="participantList">
                    <?php if (empty($participants)): ?>
                        <p class="text-center">Waiting for participants to join...</p>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <div class="participant-card" data-style="background-color: <?= $participant['color_hex'] ?>20;">
                                <div class="participant-color" data-style="background-color: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>;">
                                    <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                                </div>
                                <div class="participant-info">
                                    <div class="participant-name"><?= $participant['color_name'] ?></div>
                                    <div class="participant-status">Joined <?= date('h:i A', strtotime($participant['joined_at'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card">
                <div class="flex-between gap-2">
                    <form class="u-flex" method="POST"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars(quiz_csrf_token(), ENT_QUOTES); ?>">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('Cancel this quiz session?');">
                            Cancel Session
                        </button>
                    </form>

                    <form class="u-flex" method="POST"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars(quiz_csrf_token(), ENT_QUOTES); ?>">
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="btn btn-primary btn-block"
                                id="startBtn"
                                <?= count($participants) == 0 ? 'disabled' : '' ?>>
                            Start Quiz (<?= count($participants) ?> participants)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="launch-quiz.js?v=<?php echo @filemtime(__DIR__."/launch-quiz.js"); ?>"></script>
<script src="qsadmin-style.js?v=<?php echo @filemtime(__DIR__."/qsadmin-style.js"); ?>"></script>
</body>
</html>
