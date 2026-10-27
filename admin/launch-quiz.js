/* Was an inline <script> interpolating $joinUrl and $sessionId. An external
   .js cannot run PHP, so both arrive as JSON on <body data-launch-config>. */
var CFG = JSON.parse(document.body.getAttribute("data-launch-config") || "{}");

// Generate QR code
        new QRCode(document.getElementById("qrcode"), {
            text: CFG.joinUrl,
            width: 300,
            height: 300,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Auto-refresh participant list every 3 seconds
        setInterval(function() {
            fetch('../api/get-participants.php?session=' + CFG.sessionId + '')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateParticipantList(data.participants);
                        updateParticipantCount(data.participants.length);
                    }
                })
                .catch(error => console.error('Error:', error));
        }, 3000);

        function updateParticipantCount(count) {
            document.getElementById('participantCount').textContent = count;
            document.getElementById('participantListCount').textContent = count;

            // Update start button
            const startBtn = document.getElementById('startBtn');
            startBtn.textContent = `Start Quiz (${count} participants)`;
            startBtn.disabled = count === 0;
        }

        function updateParticipantList(participants) {
            const listDiv = document.getElementById('participantList');

            if (participants.length === 0) {
                listDiv.innerHTML = '<p class="text-center">Waiting for participants to join...</p>';
                return;
            }

            let html = '';
            participants.forEach(p => {
                const joinTime = new Date(p.joined_at).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                html += `
                    <div class="participant-card" style="background-color: ${p.color_hex}20;">
                        <div class="participant-color" style="background-color: ${p.color_hex}; color: ${p.color_text};">
                            ${p.color_name.substring(0, 2).toUpperCase()}
                        </div>
                        <div class="participant-info">
                            <div class="participant-name">${p.color_name}</div>
                            <div class="participant-status">Joined ${joinTime}</div>
                        </div>
                    </div>
                `;
            });

            listDiv.innerHTML = html;
        }
