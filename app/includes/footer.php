            </main>
            </div>
            <?php if (isLoggedIn()): ?>
            <?php endif; ?>

            <script>
                // Auto-hide flash messages
                setTimeout(function() {
                    const alerts = document.querySelectorAll('.alert');
                    alerts.forEach(alert => {
                        alert.style.transition = 'opacity 0.5s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 500);
                    });
                }, 5000);

                // Confirmation for delete actions
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('btn-danger') || e.target.closest('.btn-danger')) {
                        if (!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                            e.preventDefault();
                        }
                    }
                });

                // Auto-refresh for real-time data
                setInterval(function() {
                    const currentPage = window.location.pathname.split('/').pop();
                    if (['dashboard.php', 'stock.php'].includes(currentPage)) {
                        // Refresh page every 2 minutes for real-time data
                        // location.reload();
                    }
                }, 120000);
            </script>
            </body>

            </html>