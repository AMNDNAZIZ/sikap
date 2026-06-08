/**
 * CBT Offline Queue System
 * Handles connection loss and auto-retry
 * 
 * Usage:
 * <script src="<?= base_url('assets/js/cbt-offline-queue.js') ?>"></script>
 * <script>
 *   const queue = new CBTOfflineQueue(testId, saveUrl);
 *   queue.saveAnswer(questionId, answer);
 * </script>
 */

class CBTOfflineQueue {
    constructor(testId, saveUrl) {
        this.testId = testId;
        this.saveUrl = saveUrl;
        this.queue = [];
        this.isOnline = navigator.onLine;
        this.indicator = null;
        this.version = 1;
        this.flushTimer = null;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        this.init();
    }
    
    init() {
        // Load queue from localStorage
        this.loadQueue();
        
        // Setup event listeners
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Setup periodic flush (every 10 seconds if online)
        setInterval(() => {
            if (this.isOnline && this.queue.length > 0) {
                this.flushQueue();
            }
        }, 10000);
        
        // Flush on page unload
        window.addEventListener('beforeunload', () => {
            if (this.queue.length > 0) {
                this.saveQueue();
            }
        });
        
        // Initial flush if online and has queue
        if (this.isOnline && this.queue.length > 0) {
            setTimeout(() => this.flushQueue(), 2000);
        }
        
        console.log('[CBT-QUEUE] Initialized, queue size:', this.queue.length);
    }
    
    loadQueue() {
        try {
            const stored = localStorage.getItem(`cbt_queue_${this.testId}`);
            if (stored) {
                this.queue = JSON.parse(stored);
                console.log('[CBT-QUEUE] Loaded', this.queue.length, 'items from storage');
            }
        } catch (e) {
            console.error('[CBT-QUEUE] Failed to load queue:', e);
            this.queue = [];
        }
    }
    
    saveQueue() {
        try {
            localStorage.setItem(`cbt_queue_${this.testId}`, JSON.stringify(this.queue));
        } catch (e) {
            console.error('[CBT-QUEUE] Failed to save queue:', e);
        }
    }
    
    clearQueue() {
        this.queue = [];
        try {
            localStorage.removeItem(`cbt_queue_${this.testId}`);
        } catch (e) {
            console.error('[CBT-QUEUE] Failed to clear queue:', e);
        }
    }
    
    handleOnline() {
        console.log('[CBT-QUEUE] Connection restored');
        this.isOnline = true;
        this.hideIndicator();
        this.retryCount = 0;
        
        // Flush queue after 2 seconds (give network time to stabilize)
        setTimeout(() => this.flushQueue(), 2000);
    }
    
    handleOffline() {
        console.log('[CBT-QUEUE] Connection lost');
        this.isOnline = false;
        this.showIndicator();
    }
    
    showIndicator() {
        if (this.indicator) return;
        
        this.indicator = document.createElement('div');
        this.indicator.id = 'cbt-offline-indicator';
        this.indicator.style.cssText = `
            position: fixed;
            top: 70px;
            right: 20px;
            z-index: 9999;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            font-weight: bold;
            animation: slideIn 0.3s ease-out, pulse 2s infinite;
            min-width: 250px;
        `;
        
        this.indicator.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <div>
                    <div style="font-size: 14px;">KONEKSI TERPUTUS</div>
                    <div style="font-size: 11px; font-weight: normal; opacity: 0.9;">
                        Jawaban disimpan offline (<span id="queue-count">${this.queue.length}</span>)
                    </div>
                </div>
            </div>
        `;
        
        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(this.indicator);
    }
    
    hideIndicator() {
        if (this.indicator) {
            this.indicator.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => {
                if (this.indicator && this.indicator.parentNode) {
                    this.indicator.parentNode.removeChild(this.indicator);
                }
                this.indicator = null;
            }, 300);
        }
    }
    
    updateIndicatorCount() {
        if (this.indicator) {
            const countEl = this.indicator.querySelector('#queue-count');
            if (countEl) {
                countEl.textContent = this.queue.length;
            }
        }
    }
    
    saveAnswer(questionId, answer, isDoubtful = 0) {
        this.version++;
        
        const payload = {
            question_id: questionId,
            answer: answer,
            is_doubtful: isDoubtful,
            version: this.version,
            timestamp: Date.now()
        };
        
        // If offline, queue it
        if (!this.isOnline) {
            console.log('[CBT-QUEUE] Offline, queueing answer:', questionId);
            this.addToQueue(payload);
            this.showIndicator();
            return Promise.resolve({ status: 'queued' });
        }
        
        // Try to send
        return this.sendToServer([payload])
            .then(response => {
                console.log('[CBT-QUEUE] Answer saved:', questionId);
                return response;
            })
            .catch(err => {
                console.error('[CBT-QUEUE] Network error, queueing:', err);
                this.addToQueue(payload);
                this.showIndicator();
                return { status: 'queued', error: err.message };
            });
    }
    
    addToQueue(payload) {
        // Check if answer for this question already in queue
        const existingIndex = this.queue.findIndex(item => 
            item.question_id === payload.question_id
        );
        
        if (existingIndex >= 0) {
            // Update existing with newer version
            if (payload.version > this.queue[existingIndex].version) {
                this.queue[existingIndex] = payload;
            }
        } else {
            this.queue.push(payload);
        }
        
        this.saveQueue();
        this.updateIndicatorCount();
    }
    
    sendToServer(answers) {
        const payload = {
            test_id: this.testId,
            answers: answers
        };
        
        return fetch(this.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        });
    }
    
    flushQueue() {
        if (this.queue.length === 0) {
            return Promise.resolve();
        }
        
        if (!this.isOnline) {
            console.log('[CBT-QUEUE] Cannot flush, offline');
            return Promise.reject(new Error('Offline'));
        }
        
        console.log('[CBT-QUEUE] Flushing', this.queue.length, 'items...');
        
        const itemsToSend = [...this.queue];
        
        return this.sendToServer(itemsToSend)
            .then(data => {
                if (data.status === 'ok') {
                    console.log('[CBT-QUEUE] Flush successful');
                    this.clearQueue();
                    this.hideIndicator();
                    this.retryCount = 0;
                    return data;
                } else {
                    throw new Error(data.message || 'Server error');
                }
            })
            .catch(err => {
                console.error('[CBT-QUEUE] Flush failed:', err);
                this.retryCount++;
                
                if (this.retryCount < this.maxRetries) {
                    console.log('[CBT-QUEUE] Will retry in 5 seconds...');
                    setTimeout(() => this.flushQueue(), 5000);
                } else {
                    console.error('[CBT-QUEUE] Max retries reached, giving up');
                    this.retryCount = 0;
                }
                
                throw err;
            });
    }
    
    getQueueSize() {
        return this.queue.length;
    }
    
    hasQueuedItems() {
        return this.queue.length > 0;
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CBTOfflineQueue;
}
