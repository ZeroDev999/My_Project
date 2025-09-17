                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js for reports -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Load notifications
        function loadNotifications() {
            fetch('<?php echo BASE_URL; ?>api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationCount = document.getElementById('notificationCount');
                    const notificationsList = document.getElementById('notificationsList');
                    const noNotifications = document.getElementById('noNotifications');
                    
                    if (data.notifications && data.notifications.length > 0) {
                        notificationCount.textContent = data.notifications.length;
                        notificationCount.style.display = 'flex';
                        
                        // Clear existing notifications
                        notificationsList.innerHTML = '<li><h6 class="dropdown-header">การแจ้งเตือน</h6></li><li><hr class="dropdown-divider"></li>';
                        
                        // Add notifications
                        data.notifications.forEach(notification => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <a class="dropdown-item" href="${notification.link || '#'}">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-${getNotificationIcon(notification.type)} text-${notification.type}"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <div class="fw-bold">${notification.title}</div>
                                            <small class="text-muted">${notification.message}</small>
                                            <br>
                                            <small class="text-muted">${formatDate(notification.created_at)}</small>
                                        </div>
                                    </div>
                                </a>
                            `;
                            notificationsList.appendChild(li);
                        });
                        
                        // Add mark all as read button
                        const markAllRead = document.createElement('li');
                        markAllRead.innerHTML = '<hr class="dropdown-divider"><li><a class="dropdown-item text-center" href="#" onclick="markAllNotificationsRead()">ทำเครื่องหมายว่าอ่านแล้วทั้งหมด</a></li>';
                        notificationsList.appendChild(markAllRead);
                    } else {
                        notificationCount.style.display = 'none';
                        noNotifications.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                });
        }

        function getNotificationIcon(type) {
            const icons = {
                'info': 'info-circle',
                'warning': 'exclamation-triangle',
                'success': 'check-circle',
                'error': 'times-circle'
            };
            return icons[type] || 'bell';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffInMinutes = Math.floor((now - date) / (1000 * 60));
            
            if (diffInMinutes < 1) return 'เมื่อสักครู่';
            if (diffInMinutes < 60) return `${diffInMinutes} นาทีที่แล้ว`;
            if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} ชั่วโมงที่แล้ว`;
            return date.toLocaleDateString('th-TH');
        }

        function markAllNotificationsRead() {
            fetch('<?php echo BASE_URL; ?>api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({action: 'mark_all_read'})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking notifications as read:', error);
            });
        }

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            
            // Refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);
        });

        // Confirm delete actions
        function confirmDelete(message = 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?') {
            return confirm(message);
        }

        // Format time duration
        function formatDuration(minutes) {
            if (minutes < 60) {
                return `${minutes} นาที`;
            } else if (minutes < 1440) {
                const hours = Math.floor(minutes / 60);
                const remainingMinutes = minutes % 60;
                return remainingMinutes > 0 ? `${hours} ชั่วโมง ${remainingMinutes} นาที` : `${hours} ชั่วโมง`;
            } else {
                const days = Math.floor(minutes / 1440);
                const remainingHours = Math.floor((minutes % 1440) / 60);
                return remainingHours > 0 ? `${days} วัน ${remainingHours} ชั่วโมง` : `${days} วัน`;
            }
        }

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(amount);
        }

        // Show loading spinner
        function showLoading(element) {
            element.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">กำลังโหลด...</span></div></div>';
        }

        // Hide loading spinner
        function hideLoading(element, content) {
            element.innerHTML = content;
        }
    </script>
</body>
</html>
