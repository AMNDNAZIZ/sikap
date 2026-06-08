/**
 * CBT Performance Optimization Module
 * Implements debouncing and batching for answer saves
 */

class CbtPerformanceOptimizer {
    constructor(testId, saveUrl, csrfToken, csrfHash) {
        this.testId = testId;
        this.saveUrl = saveUrl;
        this.csrfToken = csrfToken;
        this.csrfHash = csrfHash;
        
        // Debounce settings
        this.debounceDelay = 1000; // 1 second delay
        this.debounceTimers = {};
        
        // Batch settings
        this.batchQueue = {};
        this.batchInterval = 3000; // Send batch every 3 seconds
        this.batchTimer = null;
        
        // Start batch processor
        this.startBatchProcessor();
    }
    
    /**
     * Debounced answer save - delays save until user stops typing/clicking
     */
    debouncedSave(questionId, answer) {
        // Clear existing timer for this question
        if (this.debounceTimers[questionId]) {
            clearTimeout(this.debounceTimers[questionId]);
        }
        
        // Add to batch queue immediately (for backup)
        this.batchQueue[questionId] = answer;
        
        // Set new timer
        this.debounceTimers[questionId] = setTimeout(() => {
            this.saveSingle(questionId, answer);
            delete this.debounceTimers[questionId];
        }, this.debounceDelay);
    }
    
    /**
     * Save single answer immediately (for critical saves)
     */
    saveSingle(questionId, answer) {
        const fd = new FormData();
        fd.append('test_id', this.testId);
        fd.append('question_id', questionId);
        if (answer !== null) fd.append('answer', answer);
        fd.append(this.csrfToken, this.csrfHash);

        fetch(this.saveUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            console.log(`[CBT] Saved Q${questionId}:`, data);
            // Remove from batch queue after successful save
            delete this.batchQueue[questionId];
        })
        .catch(err => {
            console.error(`[CBT] Save failed Q${questionId}:`, err);
            // Keep in batch queue for retry
        });
    }
    
    /**
     * Start batch processor - sends queued answers periodically
     */
    startBatchProcessor() {
        this.batchTimer = setInterval(() => {
            this.flushBatch();
        }, this.batchInterval);
    }
    
    /**
     * Flush batch queue - send all pending answers
     */
    flushBatch() {
        const queue = Object.keys(this.batchQueue);
        if (queue.length === 0) return;
        
        console.log(`[CBT] Flushing batch: ${queue.length} answers`);
        
        // Send each answer (could be optimized to bulk endpoint)
        queue.forEach(questionId => {
            const answer = this.batchQueue[questionId];
            this.saveSingle(questionId, answer);
        });
    }
    
    /**
     * Force immediate save of all pending answers
     */
    forceSaveAll() {
        // Clear all debounce timers
        Object.keys(this.debounceTimers).forEach(qid => {
            clearTimeout(this.debounceTimers[qid]);
        });
        this.debounceTimers = {};
        
        // Flush batch immediately
        this.flushBatch();
    }
    
    /**
     * Cleanup - call before page unload
     */
    cleanup() {
        if (this.batchTimer) {
            clearInterval(this.batchTimer);
        }
        this.forceSaveAll();
    }
}

// Export for use in other scripts
window.CbtPerformanceOptimizer = CbtPerformanceOptimizer;
