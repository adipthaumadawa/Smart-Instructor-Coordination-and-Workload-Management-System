<?php /** Common Footer - no external frameworks */ ?>
    <script src="<?= app_url('assets/js/main.js') ?>"></script>
    <script>
        function confirmDelete(message) {
            if (typeof message === 'undefined' || message === null || message === '') {
                message = 'Are you sure you want to delete this item? This action cannot be undone.';
            }
            return confirm(message);
        }

        // Auto-hide success/error messages after 5 seconds (vanilla JS, no Bootstrap)
        setTimeout(function () {
            document.querySelectorAll('.message, .alert').forEach(function (el) {
                el.style.transition = 'opacity 0.4s ease';
                el.style.opacity = '0';
                setTimeout(function () {
                    if (el.parentNode) {
                        el.parentNode.removeChild(el);
                    }
                }, 400);
            });
        }, 5000);
    </script>
</body>
</html>