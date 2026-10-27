/* Was an inline <script> interpolating PHP. Values now arrive as JSON on
   <body data-waiting-config>. */
var CFG = JSON.parse(document.body.getAttribute("data-waiting-config") || "{}");

// Check for quiz start every 2 seconds
        setInterval(function() {
            fetch('../api/check-session-status.php?session=' + CFG.sessionId + '')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'active') {
                        window.location.href = 'quiz.php';
                    }
                    if (data.participant_count) {
                        document.getElementById('participantCount').textContent = data.participant_count;
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 2000);
