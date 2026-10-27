/* Was an inline <script> interpolating $sessionId. An external .js cannot run
   PHP, so it arrives as JSON on <body data-monitor-config>. */
var CFG = JSON.parse(document.body.getAttribute("data-monitor-config") || "{}");

// Update timer
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

            // Auto-refresh when time is up
            if (remaining === 0) {
                setTimeout(() => location.reload(), 2000);
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Auto-refresh participants every 3 seconds
        setInterval(function() {
            fetch('../api/get-session-progress.php?session=' + CFG.sessionId + '')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('completedCount').textContent = data.completed;

                        const total = data.total;
                        const completed = data.completed;
                        const percentage = total > 0 ? (completed / total) * 100 : 0;

                        const progressBar = document.getElementById('progressBar');
                        progressBar.style.width = percentage + '%';
                        progressBar.textContent = `${completed} / ${total}`;

                        // If all completed, show option to end
                        if (completed === total && total > 0) {
                            // Could add a notification here
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 3000);
