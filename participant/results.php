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

$session = $db->getSession($sessionId);
$quiz = $db->getQuiz($session['quiz_id']);

// Get participant's score
$answers = $db->getParticipantAnswers($participantId);
$questions = $db->getParticipantQuestions($participantId);
$score = $participant['score'];
$totalQuestions = count($questions);
$percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;

// Get all completed participants for ranking
$completedParticipants = array_filter($participants, fn($p) => $p['completed_at'] !== null);
usort($completedParticipants, fn($a, $b) => $b['score'] - $a['score']);

// Find participant's rank
$rank = 0;
foreach ($completedParticipants as $index => $p) {
    if ($p['id'] == $participantId) {
        $rank = $index + 1;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="results.1.css?v=<?php echo @filemtime(__DIR__."/results.1.css"); ?>">
<link rel="stylesheet" href="../qspub.css">
</head>
<body data-results-config='<?= htmlspecialchars(json_encode(["percentage" => $percentage]), ENT_QUOTES, "UTF-8") ?>'>
    <main>
        <div class="container u-max-width-4">
            <!-- Results Card -->
            <div class="card text-center">
                <?php if ($percentage >= 80): ?>
                    <div class="trophy">🏆</div>
                    <h1 class="u-color-3">Excellent!</h1>
                <?php elseif ($percentage >= 60): ?>
                    <div class="trophy">⭐</div>
                    <h1 class="u-color-2">Good Job!</h1>
                <?php else: ?>
                    <div class="trophy">📚</div>
                    <h1 class="u-color-4">Keep Practicing!</h1>
                <?php endif; ?>

                <div class="u-display-2">
                    <div data-style="width: 60px; height: 60px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.5rem;">
                        <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                    </div>
                    <span class="u-font-weight-2"><?= $participant['color_name'] ?></span>
                </div>

                <div class="final-score">
                    <?= $score ?> / <?= $totalQuestions ?>
                </div>

                <div class="grade grade-<?= $percentage >= 90 ? 'a' : ($percentage >= 80 ? 'b' : ($percentage >= 70 ? 'c' : 'd')) ?>">
                    <?= $percentage ?>%
                </div>

                <?php if ($rank > 0): ?>
                    <div class="u-margin">
                        <div class="stat-label">Your Rank</div>
                        <div class="u-font-size-7">
                            #<?= $rank ?>
                        </div>
                        <div class="stat-label">out of <?= count($completedParticipants) ?> completed</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Question Review -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Answers</h3>
                </div>

                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $userAnswer = null;
                    foreach ($answers as $ans) {
                        if ($ans['question_id'] == $question['id']) {
                            $userAnswer = $ans;
                            break;
                        }
                    }
                    $isCorrect = $userAnswer ? $userAnswer['is_correct'] : false;
                    ?>

                    <div data-style="padding: 1rem; margin-bottom: 1rem; border-left: 4px solid <?= $isCorrect ? 'var(--success)' : 'var(--danger)' ?>; background: <?= $isCorrect ? '#D1FAE5' : '#FEE2E2' ?>;">
                        <div class="u-font-weight-3">
                            <?= $isCorrect ? '✓' : '✗' ?> Question <?= $index + 1 ?>
                        </div>

                        <div class="u-margin-bottom">
                            <?= htmlspecialchars($question['question_text']) ?>
                        </div>

                        <?php if ($userAnswer): ?>
                            <div>
                                <strong>Your answer:</strong>
                                <?= $userAnswer['selected_answer'] ?>.
                                <?= htmlspecialchars($question['option_' . strtolower($userAnswer['selected_answer'])]) ?>
                            </div>
                        <?php else: ?>
                            <div><strong>Your answer:</strong> Not answered</div>
                        <?php endif; ?>

                        <?php if (!$isCorrect): ?>
                            <div class="u-color-5">
                                <strong>Correct answer:</strong>
                                <?= $question['correct_answer'] ?>.
                                <?= htmlspecialchars($question['option_' . strtolower($question['correct_answer'])]) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($question['explanation'])): ?>
                            <div class="u-margin-top">
                                <strong>Explanation:</strong> <?= htmlspecialchars($question['explanation']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Leaderboard -->
            <?php if (count($completedParticipants) > 1): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Leaderboard</h3>
                    </div>
                    <div class="leaderboard">
                        <?php foreach ($completedParticipants as $index => $p): ?>
                            <div class="leaderboard-item" data-style="<?= $p['id'] == $participantId ? 'background: ' . $p['color_hex'] . '20; border: 2px solid ' . $p['color_hex'] . ';' : '' ?>">
                                <div class="rank">#<?= $index + 1 ?></div>
                                <div class="leaderboard-color" data-style="background-color: <?= $p['color_hex'] ?>; color: <?= $p['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                    <?= strtoupper(substr($p['color_name'], 0, 2)) ?>
                                </div>
                                <div class="u-flex">
                                    <div class="u-font-weight-4"><?= $p['color_name'] ?></div>
                                    <div class="u-font-size-8">
                                        Completed <?= date('h:i A', strtotime($p['completed_at'])) ?>
                                    </div>
                                </div>
                                <div class="leaderboard-score">
                                    <?= $p['score'] ?>/<?= $totalQuestions ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Waiting for others -->
            <?php if (count($completedParticipants) < count($participants)): ?>
                <div class="alert alert-info">
                    Waiting for <?= count($participants) - count($completedParticipants) ?> more participant(s) to finish...
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="results.page.js?v=<?php echo @filemtime(__DIR__."/results.page.js"); ?>"></script>
<script src="../qspub-style.js"></script>
</body>
</html>
