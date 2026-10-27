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

// Get participant info
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

// Check if already completed
if ($participant['completed_at']) {
    header('Location: results.php');
    exit;
}

$session = $db->getSession($sessionId);
$quiz = $db->getQuiz($session['quiz_id']);

// If quiz hasn't started, redirect to waiting room
if ($session['status'] !== 'active') {
    header('Location: waiting.php');
    exit;
}

// Get participant's questions
$questions = $db->getParticipantQuestions($participantId);

// Get already submitted answers
$submittedAnswers = $db->getParticipantAnswers($participantId);
$answeredQuestionIds = array_column($submittedAnswers, 'question_id');

// Calculate time remaining
$startTime = strtotime($session['started_at']);
$endTime = $startTime + $quiz['time_limit'];
$currentTime = time();
$timeRemaining = max(0, $endTime - $currentTime);

// If time is up, redirect to results
if ($timeRemaining == 0 && !$participant['completed_at']) {
    // Auto-submit and mark as complete
    $score = count(array_filter($submittedAnswers, fn($a) => $a['is_correct'] == 1));
    $db->completeParticipant($participantId, $score);
    header('Location: results.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="quiz.1.css?v=<?php echo @filemtime(__DIR__."/quiz.1.css"); ?>">
<link rel="stylesheet" href="../qspub.css">
</head>
<body data-quiz-config='<?= htmlspecialchars(json_encode(["participantId" => $participantId, "answeredQuestionIds" => $answeredQuestionIds, "questions" => $questions]), ENT_QUOTES, "UTF-8") ?>'>
    <!-- Fixed Timer -->
    <div class="fixed-timer">
        <div class="stat-label text-center">Time Remaining</div>
        <div class="timer" id="timer" data-end-time="<?= $endTime ?>">
            <?= floor($timeRemaining / 60) ?>:<?= str_pad($timeRemaining % 60, 2, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <main class="u-padding-top">
        <div class="container u-max-width-3">
            <!-- Your Color Badge -->
            <div class="card text-center" data-style="background: linear-gradient(135deg, <?= $participant['color_hex'] ?>20, white);">
                <div class="u-display">
                    <div data-style="width: 40px; height: 40px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                    </div>
                    <span class="u-font-weight"><?= $participant['color_name'] ?></span>
                </div>
            </div>

            <!-- Question Navigation -->
            <div class="card">
                <h3 class="text-center mb-2">Questions</h3>
                <div class="question-nav">
                    <?php foreach ($questions as $index => $q): ?>
                        <button class="question-nav-btn <?= $index == 0 ? 'current' : '' ?> <?= in_array($q['id'], $answeredQuestionIds) ? 'answered' : '' ?>"
                                onclick="showQuestion(<?= $index ?>)"
                                id="nav-<?= $index ?>">
                            <?= $index + 1 ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="text-center">
                    <span class="badge badge-success">● Answered</span>
                    <span class="badge badge-info">● Current</span>
                </div>
            </div>

            <!-- Questions -->
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-container" id="question-<?= $index ?>" data-style="display: <?= $index == 0 ? 'block' : 'none' ?>;">
                    <div class="question-number">
                        Question <?= $index + 1 ?> of <?= count($questions) ?>
                    </div>

                    <div class="question-text">
                        <?= htmlspecialchars($question['question_text']) ?>
                    </div>

                    <?php
                    $isAnswered = in_array($question['id'], $answeredQuestionIds);
                    $selectedAnswer = '';
                    if ($isAnswered) {
                        foreach ($submittedAnswers as $ans) {
                            if ($ans['question_id'] == $question['id']) {
                                $selectedAnswer = $ans['selected_answer'];
                                break;
                            }
                        }
                    }
                    ?>

                    <ul class="options-list">
                        <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                            <li class="option-item">
                                <button class="option-button <?= $selectedAnswer === $option ? 'selected' : '' ?>"
                                        data-question-id="<?= $question['id'] ?>"
                                        data-option="<?= $option ?>"
                                        onclick="selectAnswer(<?= $index ?>, '<?= $option ?>', <?= $question['id'] ?>)"
                                        <?= $isAnswered ? 'disabled' : '' ?>>
                                    <strong><?= $option ?>.</strong>
                                    <?= htmlspecialchars($question['option_' . strtolower($option)]) ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="flex-between mt-3">
                        <?php if ($index > 0): ?>
                            <button class="btn btn-outline" onclick="showQuestion(<?= $index - 1 ?>)">
                                ← Previous
                            </button>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ($index < count($questions) - 1): ?>
                            <button class="btn btn-primary" onclick="showQuestion(<?= $index + 1 ?>)">
                                Next →
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" onclick="finishQuiz()">
                                Finish Quiz
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script src="quiz.page.js?v=<?php echo @filemtime(__DIR__."/quiz.page.js"); ?>"></script>
<script src="../qspub-style.js"></script>
</body>
</html>
