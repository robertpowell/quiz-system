/* Was an inline <script> interpolating PHP. An external .js cannot run PHP,
   so the values now arrive as JSON on <body data-results-config>. */
var CFG = JSON.parse(document.body.getAttribute("data-results-config") || "{}");

function exportToCSV() {
            const participants = CFG.participants;
            const quizName = CFG.quizName;
            const sessionCode = CFG.sessionCode;

            let csv = 'Rank,Participant,Score,Percentage,Completed At\n';

            participants.forEach((p, index) => {
                const percentage = Math.round((p.score / CFG.questionsPerQuiz) * 100);
                const completedAt = p.completed_at || 'Incomplete';
                csv += `${index + 1},"${p.color_name}",${p.score},${percentage}%,"${completedAt}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `quiz-results-${sessionCode}.csv`;
            a.click();
        }
