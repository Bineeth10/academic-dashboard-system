</main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Academic Dashboard. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Simple JavaScript for enhanced UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.type === 'submit') {
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';
                        setTimeout(() => {
                            this.style.opacity = '1';
                            this.style.pointerEvents = 'auto';
                        }, 2000);
                    }
                });
            });
            
            // Auto-hide success/error messages
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
            
            // Confirm delete actions
            const deleteButtons = document.querySelectorAll('.btn-danger');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.textContent.toLowerCase().includes('delete')) {
                        if (!confirm('Are you sure you want to delete this item?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
