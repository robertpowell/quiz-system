<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
quiz_require_csrf();

require_once '../includes/config.php';
require_once '../includes/db.php';

$sessionId = isset($_GET['session']) ? intval($_GET['session']) : 0;

if ($sessionId == 0) {
    header('Location: sessions.php');
    exit;
}

$db = Database::getInstance();
$session = $db->getSession($sessionId);

if (!$session) {
    die('Session not found');
}

$quiz = $db->getQuiz($session['quiz_id']);
$participants = $db->getParticipants($sessionId);
$stats = $db->getSessionStats($sessionId);

// Sort participants by score
usort($participants, fn($a, $b) => $b['score'] - $a['score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Results - <?= htmlspecialchars($quiz['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="qsadmin.css?v=<?php echo @filemtime(__DIR__."/qsadmin.css"); ?>">
</head>
<body data-results-config='<?= htmlspecialchars(json_encode(["participants" => $participants, "quizName" => $quiz["name"], "sessionCode" => $session["session_code"], "questionsPerQuiz" => $quiz["questions_per_quiz"]]), ENT_QUOTES, "UTF-8") ?>'>
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
            <h2 class="mb-3">Session Results: <?= htmlspecialchars($quiz['name']) ?></h2>

            <!-- Session Info -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Session Code</div>
                    <div class="stat-value u-font-size">
                        <?= $session['session_code'] ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Participants</div>
                    <div class="stat-value"><?= $stats['total_participants'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value"><?= $stats['completed_participants'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Average Score</div>
                    <div class="stat-value">
                        <?= $stats['completed_participants'] > 0 ? round($stats['average_score'], 1) : 'N/A' ?>
                    </div>
                </div>

                <?php if ($stats['completed_participants'] > 0): ?>
                    <div class="stat-card">
                        <div class="stat-label">Highest Score</div>
                        <div class="stat-value"><?= $stats['highest_score'] ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Lowest Score</div>
                        <div class="stat-value"><?= $stats['lowest_score'] ?></div>
                    </div>
                <?php endif; ?>

                <div class="stat-card">
                    <div class="stat-label">Started</div>
                    <div class="stat-value u-font-size-2">
                        <?= date('M j, Y', strtotime($session['started_at'])) ?><br>
                        <?= date('g:i A', strtotime($session['started_at'])) ?>
                    </div>
                </div>

                <?php if ($session['ended_at']): ?>
                    <div class="stat-card">
                        <div class="stat-label">Ended</div>
                        <div class="stat-value u-font-size-2">
                            <?= date('M j, Y', strtotime($session['ended_at'])) ?><br>
                            <?= date('g:i A', strtotime($session['ended_at'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Leaderboard -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Final Leaderboard</h3>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Participant</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Completed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $index => $participant): ?>
                                <tr>
                                    <td>
                                        <strong class="u-font-size-3">
                                            <?php if ($index === 0 && $participant['completed_at']): ?>
                                                🥇
                                            <?php elseif ($index === 1 && $participant['completed_at']): ?>
                                                🥈
                                            <?php elseif ($index === 2 && $participant['completed_at']): ?>
                                                🥉
                                            <?php else: ?>
                                                #<?= $index + 1 ?>
                                            <?php endif; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="u-display-3">
                                            <div data-style="width: 30px; height: 30px; border-radius: 50%; background: <?= $participant['color_hex'] ?>; color: <?= $participant['color_text'] ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">
                                                <?= strtoupper(substr($participant['color_name'], 0, 2)) ?>
                                            </div>
                                            <strong><?= $participant['color_name'] ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="u-font-size-3">
                                            <?= $participant['score'] ?> / <?= $quiz['questions_per_quiz'] ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php
                                        $percentage = round(($participant['score'] / $quiz['questions_per_quiz']) * 100);
                                        $badgeClass = $percentage >= 80 ? 'badge-success' : ($percentage >= 60 ? 'badge-info' : 'badge-warning');
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $percentage ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($participant['completed_at']): ?>
                                            <?= date('g:i:s A', strtotime($participant['completed_at'])) ?>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Incomplete</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Export Options -->
            <div class="card">
                <h3 class="card-title mb-2">Export Results</h3>
                <div class="flex gap-2">
                    <button onclick="exportToCSV()" class="btn btn-primary">
                        📊 Export to CSV
                    </button>
                    <button onclick="window.print()" class="btn btn-outline">
                        🖨️ Print Results
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script src="session-results.js?v=<?php echo @filemtime(__DIR__."/session-results.js"); ?>"></script>
<script src="qsadmin-style.js?v=<?php echo @filemtime(__DIR__."/qsadmin-style.js"); ?>"></script>
</body>
</html>
