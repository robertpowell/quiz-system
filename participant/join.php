<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['session_code']));
}

if ($code) {
    // Verify session exists
    $session = $db->getSessionByCode($code);

    if (!$session) {
        $error = 'Invalid session code';
    } elseif ($session['status'] !== 'waiting') {
        $error = 'This quiz has already started or ended';
    } else {
        // Check participant limit
        $participants = $db->getParticipants($session['id']);
        if (count($participants) >= MAX_PARTICIPANTS_PER_QUIZ) {
            $error = 'This quiz is full';
        } else {
            // Assign random color
            $usedColors = $db->getUsedColors($session['id']);
            $color = get_random_color($usedColors);

            // Create participant
            $participantId = $db->addParticipant($session['id'], $color);

            // Get quiz details
            $quiz = $db->getQuiz($session['quiz_id']);

            // Assign random questions
            $questions = $db->getRandomQuestions($quiz['id'], $quiz['questions_per_quiz']);
            $questionIds = array_column($questions, 'id');
            $db->assignQuestionsToParticipant($participantId, $questionIds);

            log_activity('quiz-participation', 'Participant joined', [
                'session_id' => $session['id'],
                'participant_id' => $participantId,
                'color' => $color['name']
            ]);

            // Store participant ID in session
            $_SESSION['participant_id'] = $participantId;
            $_SESSION['session_id'] = $session['id'];

            // Redirect to waiting room
            header('Location: waiting.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Quiz</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../qspub.css">
</head>
<body>
    <main>
        <div class="container u-max-width-2">
            <div class="card text-center">
                <h1 class="u-color">🎯 Join Quiz</h1>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="session_code">Enter Quiz Code</label>
                        <input class="u-text-align-3" type="text"
                               id="session_code"
                               name="session_code"
                               value="<?= htmlspecialchars($code) ?>"
                               maxlength="6"
                              
                               placeholder="ABC123"
                               required
                               autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Join Quiz
                    </button>
                </form>
            </div>

            <div class="card mt-3">
                <h3>How to Join</h3>
                <ol class="u-text-align-4">
                    <li>Scan the QR code displayed by your instructor, OR</li>
                    <li>Enter the 6-digit code shown on screen</li>
                    <li>Wait for the quiz to start</li>
                    <li>Answer all questions before time runs out</li>
                </ol>
            </div>

            <div class="text-center mt-3 u-font-size-6">
                This site uses essential cookies for authentication. No tracking cookies are used.
                <br><a class="u-color-2" href="../privacy.php">Privacy Policy</a>
            </div>
        </div>
    </main>

    <script src="join.1.js?v=<?php echo @filemtime(__DIR__."/join.1.js"); ?>"></script>
<script src="../qspub-style.js"></script>
</body>
</html>
