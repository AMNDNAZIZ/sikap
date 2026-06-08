/**
 * Session Keep-Alive Script
 * 
 * Automatically pings the server to keep the session alive
 * when user is actively working (typing, clicking, etc.)
 */

(function() {
    'use strict';
    
    // Configuration
    const PING_INTERVAL = 5 * 60 * 1000; // 5 minutes
    const IDLE_TIMEOUT = 10 * 60 * 1000; // 10 minutes of inactivity before stopping pings
    const PING_URL = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/ping-session';
    
    let lastActivity = Date.now();
    let pingTimer = null;
    let isActive = true;
    
    // Track user activity
    const activityEvents = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
    
    function updateActivity() {
        lastActivity = Date.now();
        if (!isActive) {
            isActive = true;
            startPinging();
        }
    }
    
    // Add activity listeners
    activityEvents.forEach(event => {
        document.addEventListener(event, updateActivity, { passive: true });
    });
    
    // Ping function
    function pingSession() {
        // Check if user has been idle
        const idleTime = Date.now() - lastActivity;
        
        if (idleTime > IDLE_TIMEOUT) {
            // User is idle, stop pinging
            isActive = false;
            stopPinging();
            return;
        }
        
        // Send ping to keep session alive
        fetch(PING_URL, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        }).catch(err => {
            console.warn('Session ping failed:', err);
        });
    }
    
    // Start pinging
    function startPinging() {
        if (pingTimer) return; // Already running
        
        pingTimer = setInterval(pingSession, PING_INTERVAL);
        console.log('Session keep-alive started');
    }
    
    // Stop pinging
    function stopPinging() {
        if (pingTimer) {
            clearInterval(pingTimer);
            pingTimer = null;
            console.log('Session keep-alive stopped (user idle)');
        }
    }
    
    // Start on page load
    startPinging();
    
    // Stop on page unload
    window.addEventListener('beforeunload', stopPinging);
    
})();
