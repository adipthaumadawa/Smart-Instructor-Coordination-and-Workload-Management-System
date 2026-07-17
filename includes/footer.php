<?php /** Common Footer */ ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('assets/js/main.js') ?>"></script>
    <script>
        function confirmDelete(message = 'Are you sure you want to delete this item? This action cannot be undone.') {
            return confirm(message);
        }
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                try { new bootstrap.Alert(alert).close(); } catch(e) {}
            });
        }, 5000);
    </script>
</body>
</html>