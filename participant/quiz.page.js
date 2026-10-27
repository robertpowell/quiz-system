/* Was an inline <script> interpolating PHP. Values now arrive as JSON on
   <body data-quiz-config>. */
var CFG = JSON.parse(document.body.getAttribute("data-quiz-config") || "{}");

const questions = CFG.questions;
        const answeredQuestions = new Set(CFG.answeredQuestionIds);
        let currentQuestion = 0;

        // Timer
        function updateTimer() {
            const timerEl = document.getElementById('timer');
            const endTime = parseInt(timerEl.dataset.endTime);
            const now = Math.floor(Date.now() / 1000);
            const remaining = Math.max(0, endTime - now);

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;

            timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            // Change color based on time
            timerEl.className = 'timer';
            if (remaining < 60) {
                timerEl.classList.add('danger');
            } else if (remaining < 180) {
                timerEl.classList.add('warning');
            }

            // Auto-submit when time is up
            if (remaining === 0) {
                alert('Time is up! Submitting your quiz...');
                window.location.href = 'results.php';
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Navigation
        function showQuestion(index) {
            // Hide current question
            document.getElementById(`question-${currentQuestion}`).style.display = 'none';
            document.getElementById(`nav-${currentQuestion}`).classList.remove('current');

            // Show new question
            currentQuestion = index;
            const questionEl = document.getElementById(`question-${currentQuestion}`);
            questionEl.style.display = 'block';
            document.getElementById(`nav-${currentQuestion}`).classList.add('current');

            // Scroll to question section
            questionEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Answer selection
        function selectAnswer(questionIndex, option, questionId) {
            if (answeredQuestions.has(questionId)) {
                return; // Already answered
            }

            // Submit answer
            fetch('../api/submit-answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    participant_id: CFG.participantId,
                    question_id: questionId,
                    selected_answer: option
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    answeredQuestions.add(questionId);

                    // Mark as answered in UI
                    document.getElementById(`nav-${questionIndex}`).classList.add('answered');

                    // Disable all options for this question
                    const buttons = document.querySelectorAll(`[data-question-id="${questionId}"]`);
                    buttons.forEach(btn => {
                        btn.disabled = true;
                        if (btn.dataset.option === option) {
                            btn.classList.add('selected');
                        }
                    });

                    // Auto-advance to next question
                    setTimeout(() => {
                        if (questionIndex < questions.length - 1) {
                            showQuestion(questionIndex + 1);
                        }
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit answer. Please try again.');
            });
        }

        function finishQuiz() {
            const unanswered = questions.length - answeredQuestions.size;

            if (unanswered > 0) {
                if (!confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to finish?`)) {
                    return;
                }
            }

            // Mark as complete
            fetch('../api/complete-quiz.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    participant_id: CFG.participantId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'results.php';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit quiz. Please try again.');
            });
        }

        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function(e) {
            if (answeredQuestions.size < questions.length) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
