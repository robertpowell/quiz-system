/* One inline <script>, lifted in place. */

// Auto-uppercase input
        document.getElementById('session_code').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
