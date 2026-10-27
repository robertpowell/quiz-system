/* Was an inline <script> whose confetti block was wrapped in a PHP
   conditional. The decision now lives in JS and the value arrives on
   <body data-results-config>. confetti.style.* assignments are direct CSSOM,
   which style-src does not govern. */
var CFG = JSON.parse(document.body.getAttribute("data-results-config") || "{}");

// Confetti animation for good scores
        if (CFG.percentage >= 80) {
        function createConfetti() {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.background = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff'][Math.floor(Math.random() * 5)];
            confetti.style.animationDelay = Math.random() * 3 + 's';
            document.body.appendChild(confetti);

            setTimeout(() => confetti.remove(), 3000);
        }

        for (let i = 0; i < 50; i++) {
            setTimeout(createConfetti, i * 50);
        }
        }

        // Auto-refresh leaderboard
        setInterval(function() {
            location.reload();
        }, 10000); // Refresh every 10 seconds
