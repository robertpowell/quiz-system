/* One inline <script>, lifted in place. */

function editTopic(topic) {
            document.getElementById('edit_id').value = topic.id;
            document.getElementById('edit_name').value = topic.name;
            document.getElementById('edit_description').value = topic.description || '';
            document.getElementById('edit_enabled').value = topic.enabled;
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
