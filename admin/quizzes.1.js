/* One inline <script>, lifted in place. */

function editQuiz(quiz) {
            document.getElementById('edit_id').value = quiz.id;
            document.getElementById('edit_name').value = quiz.name;
            document.getElementById('edit_description').value = quiz.description || '';
            document.getElementById('edit_time_limit').value = Math.floor(quiz.time_limit / 60);
            document.getElementById('edit_questions_per_quiz').value = quiz.questions_per_quiz;
            document.getElementById('edit_enabled').value = quiz.enabled;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
