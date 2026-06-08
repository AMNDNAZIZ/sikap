# Testing Checklist: CBT Loading & 403 Fix

## Pre-Testing Setup

- [ ] Backup database
- [ ] Clear browser cache
- [ ] Clear session cookies
- [ ] Open browser console (F12)
- [ ] Enable network tab logging

---

## Test 1: Loading UX Improvement

### Scenario: Mulai Ujian Baru
**Steps:**
1. Login sebagai siswa
2. Buka halaman daftar ujian
3. Klik "Mulai Ujian"
4. Observe loading behavior

**Expected Results:**
- [ ] Button berubah menjadi "Memuat..."
- [ ] Loading spinner muncul
- [ ] Text "Memuat soal ujian..." tampil
- [ ] Button disabled (tidak bisa double-click)
- [ ] Fullscreen mode aktif dalam < 2 detik
- [ ] Loading overlay hilang
- [ ] Soal pertama tampil

**Console Logs:**
```
(No errors expected)
```

---

## Test 2: CSRF Token Configuration

### Scenario: Verify Token Expiry
**Steps:**
1. Run: `php test_cbt_csrf_refresh.php`
2. Check output

**Expected Results:**
- [ ] Token expires: 21600 seconds (6 hours)
- [ ] Regenerate: Yes
- [ ] Protection: cookie
- [ ] Max exam duration: 6 hours
- [ ] ✅ Token expiry is sufficient

**Output:**
```
=== CBT CSRF Token Refresh Test ===
1. CSRF Configuration:
   - Expires: 21600 seconds (6 hours)
   ✅ Token expiry is sufficient for long exams
```

---

## Test 3: Short Exam (30 minutes)

### Scenario: Normal Exam Duration
**Steps:**
1. Create test with duration: 30 minutes
2. Start exam as student
3. Answer 5 questions
4. Wait 5 minutes
5. Answer 5 more questions
6. Submit exam

**Expected Results:**
- [ ] All answers saved successfully
- [ ] No 403 errors in console
- [ ] No CSRF refresh triggered (not needed)
- [ ] Exam submitted successfully
- [ ] Score calculated correctly

**Console Logs:**
```
[Save] Answer saved for question 1
[Save] Answer saved for question 2
...
(No CSRF refresh - exam too short)
```

---

## Test 4: Medium Exam (2 hours)

### Scenario: Exam Longer Than Old Token Expiry
**Steps:**
1. Create test with duration: 120 minutes
2. Start exam as student
3. Answer questions at intervals:
   - 0:00 - Answer Q1-5
   - 0:30 - Answer Q6-10
   - 1:00 - Answer Q11-15 (should trigger refresh)
   - 1:30 - Answer Q16-20
   - 2:00 - Submit exam

**Expected Results:**
- [ ] All answers saved successfully
- [ ] CSRF refresh at 60 minutes
- [ ] CSRF refresh at 120 minutes
- [ ] No 403 errors
- [ ] Exam submitted successfully

**Console Logs:**
```
[CSRF] Token refreshed successfully (at 60 min)
[Save] Answer saved for question 11
[CSRF] Token refreshed successfully (at 120 min)
[Save] Answer saved for question 20
```

---

## Test 5: Long Exam (4 hours)

### Scenario: Very Long Exam
**Steps:**
1. Create test with duration: 240 minutes
2. Start exam as student
3. Answer questions every 30 minutes
4. Monitor console for CSRF refreshes

**Expected Results:**
- [ ] CSRF refresh at: 60, 120, 180, 240 minutes
- [ ] All saves successful throughout exam
- [ ] No 403 errors at any point
- [ ] Exam submitted successfully

**Console Logs:**
```
[CSRF] Token refreshed successfully (60 min)
[CSRF] Token refreshed successfully (120 min)
[CSRF] Token refreshed successfully (180 min)
[CSRF] Token refreshed successfully (240 min)
```

---

## Test 6: 403 Error Recovery

### Scenario: Simulate Token Expiry
**Steps:**
1. Start exam
2. Open browser DevTools
3. Go to Application > Cookies
4. Delete CSRF cookie manually
5. Try to save an answer
6. Observe auto-recovery

**Expected Results:**
- [ ] Save fails with 403 initially
- [ ] Console shows: "[Save] HTTP Error: 403"
- [ ] Console shows: "[CSRF] Token may be expired, refreshing..."
- [ ] Token refreshed automatically
- [ ] Save retried successfully
- [ ] Answer saved on second attempt

**Console Logs:**
```
[Save] HTTP Error: 403
[CSRF] Token may be expired, refreshing...
[CSRF] Token refreshed after 403 error
[Save] Answer saved for question 5 (retry)
```

---

## Test 7: Network Failure Handling

### Scenario: Offline/Network Error
**Steps:**
1. Start exam
2. Answer 5 questions (should save)
3. Disable network (DevTools > Network > Offline)
4. Answer 5 more questions
5. Re-enable network
6. Observe behavior

**Expected Results:**
- [ ] First 5 answers saved
- [ ] Console shows network errors for next 5
- [ ] No crashes or freezes
- [ ] When network restored, can continue
- [ ] Can submit exam

**Console Logs:**
```
[Save] Answer saved for question 1
Network Failure: Failed to fetch
Network Failure: Failed to fetch
[Save] Answer saved for question 6 (after reconnect)
```

---

## Test 8: Multiple Students Simultaneously

### Scenario: Load Testing
**Steps:**
1. Create exam for multiple students
2. Have 5+ students start exam simultaneously
3. All answer questions at same time
4. Monitor server logs

**Expected Results:**
- [ ] All students can start exam
- [ ] All saves successful for all students
- [ ] No CSRF conflicts between students
- [ ] Server logs show individual refreshes
- [ ] No performance degradation

**Server Logs:**
```
[INFO] [CBT::refreshCsrf] Token refreshed for student 1, test 10
[INFO] [CBT::refreshCsrf] Token refreshed for student 2, test 10
[INFO] [CBT::refreshCsrf] Token refreshed for student 3, test 10
```

---

## Test 9: Browser Compatibility

### Scenario: Cross-Browser Testing
**Test on:**
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Edge (latest)
- [ ] Safari (if available)
- [ ] Mobile Chrome
- [ ] Mobile Safari

**Expected Results:**
- [ ] Loading spinner works on all browsers
- [ ] Fullscreen works on all browsers
- [ ] CSRF refresh works on all browsers
- [ ] No console errors on any browser

---

## Test 10: Edge Cases

### Scenario A: Start Exam, Close Browser, Resume
**Steps:**
1. Start exam
2. Answer 5 questions
3. Close browser completely
4. Reopen and resume exam
5. Continue answering

**Expected Results:**
- [ ] Can resume exam
- [ ] Previous answers preserved
- [ ] CSRF token still valid or refreshed
- [ ] Can continue and submit

### Scenario B: Token Expires During Submit
**Steps:**
1. Start exam
2. Wait until near token expiry (5h 59min)
3. Try to submit exam

**Expected Results:**
- [ ] Submit triggers token refresh
- [ ] Submit succeeds
- [ ] Exam finalized correctly

### Scenario C: Rapid Answer Changes
**Steps:**
1. Start exam
2. Rapidly change answer 10 times in 5 seconds
3. Observe save behavior

**Expected Results:**
- [ ] All saves queued properly
- [ ] No race conditions
- [ ] Final answer is correct
- [ ] No duplicate saves

---

## Performance Metrics

### Measure:
- [ ] Time to fullscreen: < 2 seconds
- [ ] Save answer latency: < 500ms
- [ ] Token refresh latency: < 300ms
- [ ] Page load time: < 3 seconds
- [ ] Memory usage: Stable over 4 hours

### Tools:
- Chrome DevTools Performance tab
- Network tab (timing)
- Memory profiler

---

## Rollback Criteria

**Rollback if:**
- [ ] > 5% of saves fail with 403
- [ ] Token refresh causes exam interruption
- [ ] Performance degradation > 20%
- [ ] Any data loss occurs
- [ ] Critical security issue found

---

## Sign-Off

### Tested By:
- Name: _______________
- Date: _______________
- Environment: _______________

### Results:
- [ ] All tests passed
- [ ] Minor issues (documented below)
- [ ] Major issues (rollback required)

### Issues Found:
```
(Document any issues here)
```

### Approved for Production:
- [ ] Yes
- [ ] No (reason: _______________)

---

## Post-Deployment Monitoring

### Week 1:
- [ ] Monitor error logs daily
- [ ] Check CSRF refresh success rate
- [ ] Verify no 403 errors reported
- [ ] Review user feedback

### Week 2-4:
- [ ] Weekly log review
- [ ] Performance metrics check
- [ ] User satisfaction survey

### Metrics to Track:
- Total exams conducted
- Average exam duration
- CSRF refresh count
- 403 error count (should be 0)
- Save success rate (should be 100%)
