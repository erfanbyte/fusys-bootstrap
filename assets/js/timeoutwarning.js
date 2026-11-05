if (isset($_SESSION['user_id'])){

    let warningTimer;
    let logoutTimer;
    const warningTime = 3300000; // 55mins (warning shows at 30s)
    const logoutTime = 3600000;  // 60mins (logout at 60s)

    function startTimers() {
        // Clear existing timers
        clearTimeout(warningTimer);
        clearTimeout(logoutTimer);
        
        // Set new timers
        warningTimer = setTimeout(showWarning, warningTime);
        logoutTimer = setTimeout(forceLogout, logoutTime);
        
        // Optional: Ping server to update last_activity
        fetch('/fusys/auth/ping.php').catch(() => {});
    }

    function showWarning() {
        if (confirm('Your session will expire in 30 seconds. Continue?')) {
            // User wants to stay logged in - ping server and reset timers
            fetch('/fusys/auth/ping.php')
                .then(() => startTimers())
                .catch(() => forceLogout());
        }
        // If cancelled, logout will still occur automatically
    }

    function forceLogout() {
        // Clear any pending warnings
        clearTimeout(warningTimer);
        window.location.href = '/fusys/auth/login.php?timeout=1';
    }

    // Initialize timers when page loads
    document.addEventListener('DOMContentLoaded', startTimers);

    // Reset timers on any activity
    const activityEvents = ['mousemove', 'keydown', 'scroll', 'click', 'touchstart', 'mousedown'];
    activityEvents.forEach(event => {
        document.addEventListener(event, startTimers, {passive: true});
    });

    // Check session every 30 seconds (optional)
    setInterval(() => {
        fetch('/fusys/auth/check_session.php')
            .then(response => response.text())
            .then(isActive => {
                if (isActive === 'false') {
                    forceLogout();
                }
            })
            .catch(() => forceLogout());
    }, 30000);
    window.addEventListener('beforeunload', function() {
    if (isset($_SESSION['user_id'])) {
        fetch('/fusys/auth/logout.php?tab_close=1', {
            method: 'POST',
            keepalive: true // Ensure request completes even if page unloads
        });
    }
    });

}
