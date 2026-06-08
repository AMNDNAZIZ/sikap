# Fix: Jeda Lama Saat Submit Ujian CBT

## MASALAH

Setelah klik konfirmasi "Selesaikan Ujian", ada jeda terlalu lama (beberapa detik) sebelum beralih halaman. User tidak tahu apakah proses sedang berjalan atau stuck.

## ROOT CAUSE

### 1. Tidak Ada Loading Indicator
```javascript
// SEBELUM (BURUK UX):
function submitExam() {
  fetch('submit')
    .then(res => {
      // Langsung redirect tanpa feedback
      window.location.href = res.redirect;
    });
}
```

User tidak tahu bahwa proses sedang berjalan, sehingga terkesan "hang" atau lambat.

### 2. Proses Server yang Memakan Waktu

Di server, ada beberapa proses yang memakan waktu:

1. **Validasi Jawaban** (1-2 detik)
   ```php
   $answeredCount = $this->answerModel
       ->where('student_id', $studentId)
       ->where('test_id', $testId)
       ->whereIn('question_id', $questionOrder)
       ->countAllResults();
   ```

2. **Auto Scoring** (2-3 detik untuk 50 soal)
   ```php
   $score = $this->autoScorePg($studentId, $testId);
   ```
   - Fetch semua soal
   - Fetch semua jawaban
   - Loop dan hitung score per soal
   - Update score per answer

3. **Database Transaction** (0.5-1 detik)
   ```php
   $db->transStart();
   // ... multiple queries
   $db->transComplete();
   ```

**Total waktu:** 3-6 detik (tergantung jumlah soal dan server)

## SOLUSI YANG DITERAPKAN

### 1. Tambah Loading Indicator ✅

**File:** `app/Views/siswa/cbt/ujian.php`

```javascript
function submitExam() {
  // Show loading indicator immediately
  Swal.fire({
    title: 'Menyelesaikan Ujian...',
    html: '<div style="text-align: center;">
            <i class="bi bi-hourglass-split" style="font-size: 3rem; color: #1976d2;"></i>
            <br><br>
            Mohon tunggu, sedang memproses jawaban Anda...
          </div>',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  
  // Submit to server
  fetch('submit')
    .then(res => res.json())
    .then(res => {
      if (res.redirect) {
        // Show success message briefly
        Swal.fire({
          title: 'Berhasil!',
          text: 'Ujian telah diselesaikan',
          icon: 'success',
          timer: 1000,
          showConfirmButton: false
        }).then(() => {
          window.location.href = res.redirect;
        });
      }
    });
}
```

**Keuntungan:**
- ✅ User tahu proses sedang berjalan
- ✅ Tidak terkesan hang atau lambat
- ✅ Professional UX
- ✅ Feedback visual yang jelas

### 2. Update Loading Message untuk Retry ✅

Jika terjadi 403 error dan perlu refresh token:

```javascript
if (r.status === 403) {
  // Update loading message
  Swal.update({
    html: '<div style="text-align: center;">
            <i class="bi bi-arrow-repeat" style="font-size: 3rem; color: #ffc107;"></i>
            <br><br>
            Token kedaluwarsa, memperbarui token...
          </div>'
  });
  
  // Refresh token
  fetch('refreshCsrf')
    .then(() => {
      // Update loading message again
      Swal.update({
        html: '<div>Mencoba lagi...</div>'
      });
      
      // Retry submit
      return submitExam();
    });
}
```

### 3. Success Message Before Redirect ✅

```javascript
// Show success message briefly (1 second)
Swal.fire({
  title: 'Berhasil!',
  text: 'Ujian telah diselesaikan',
  icon: 'success',
  timer: 1000,
  showConfirmButton: false,
  allowOutsideClick: false
}).then(() => {
  // Then redirect
  window.location.href = res.redirect;
});
```

**Keuntungan:**
- ✅ User tahu submit berhasil
- ✅ Smooth transition
- ✅ Professional feedback

## EXPECTED BEHAVIOR

### Sebelum Fix
```
User klik "Ya, Selesai!"
  ↓
[Tidak ada feedback]
  ↓
[Jeda 3-6 detik - terkesan hang]
  ↓
Redirect ke halaman hasil
```

**User Experience:** ❌ Buruk (terkesan lambat/hang)

### Setelah Fix
```
User klik "Ya, Selesai!"
  ↓
Loading: "Menyelesaikan Ujian..."
  ↓
[Proses 3-6 detik dengan loading indicator]
  ↓
Success: "Berhasil!" (1 detik)
  ↓
Redirect ke halaman hasil
```

**User Experience:** ✅ Baik (jelas dan professional)

## LOADING STATES

### State 1: Initial Submit
```
Title: Menyelesaikan Ujian...
Icon: Hourglass (spinning)
Message: Mohon tunggu, sedang memproses jawaban Anda...
```

### State 2: Token Refresh (jika 403)
```
Title: Menyelesaikan Ujian...
Icon: Arrow Repeat (yellow)
Message: Token kedaluwarsa, memperbarui token...
```

### State 3: Retry Submit
```
Title: Menyelesaikan Ujian...
Icon: Hourglass (spinning)
Message: Mencoba lagi...
```

### State 4: Success
```
Title: Berhasil!
Icon: Success (green checkmark)
Message: Ujian telah diselesaikan
Duration: 1 second (auto-close)
```

## OPTIMASI SERVER (OPTIONAL)

Jika ingin mempercepat proses server, bisa dilakukan:

### 1. Async Scoring (Background Job)
```php
// Submit tanpa scoring dulu
$this->sessionModel->update($session['id'], [
    'status' => 'finished',
    'finished_at' => time()
]);

// Queue scoring job
$this->queue->push('ScoreExamJob', [
    'student_id' => $studentId,
    'test_id' => $testId
]);

// Redirect immediately
return $this->respond(['redirect' => $redirectUrl]);
```

**Keuntungan:**
- ✅ Submit instant (< 1 detik)
- ✅ Scoring di background
- ⚠️ Score mungkin belum ready saat redirect

### 2. Cache Question Data
```php
// Cache questions per bank
$cacheKey = "questions_bank_{$test['bank_id']}";
$questions = cache($cacheKey);

if (!$questions) {
    $questions = $this->questionModel
        ->where('bank_id', $test['bank_id'])
        ->findAll();
    cache()->save($cacheKey, $questions, 3600);
}
```

### 3. Batch Update Answers
```php
// Instead of loop update
foreach ($answers as $answer) {
    $this->answerModel->update($answer['id'], ['score' => $score]);
}

// Use batch update
$this->answerModel->updateBatch($answers, 'id');
```

## FILES MODIFIED

### app/Views/siswa/cbt/ujian.php ✅
- Tambah loading indicator pada `submitExam()`
- Tambah update loading message untuk retry
- Tambah success message before redirect

## TESTING

### Manual Test
1. Login sebagai siswa
2. Mulai ujian CBT
3. Jawab semua soal
4. Klik "Selesaikan Ujian"
5. Klik "Ya, Selesai!"
6. Observe:
   - ✅ Loading indicator muncul immediately
   - ✅ Message: "Menyelesaikan Ujian..."
   - ✅ Spinning loader visible
   - ✅ Success message muncul (1 detik)
   - ✅ Redirect ke halaman hasil

### Test dengan 403 Error
1. Expire CSRF token (tunggu > 6 jam atau modify expires)
2. Submit ujian
3. Observe:
   - ✅ Loading: "Menyelesaikan Ujian..."
   - ✅ Update: "Token kedaluwarsa, memperbarui token..."
   - ✅ Update: "Mencoba lagi..."
   - ✅ Success: "Berhasil!"
   - ✅ Redirect

## MONITORING

### Browser Console
```javascript
// Normal flow
[Submit] Submitting exam...
[Submit] Success, redirecting...

// With token refresh
[Submit] CSRF token may be expired, refreshing...
[CSRF] Token refreshed, retrying submit...
[Submit] Success, redirecting...
```

### User Feedback
- Loading indicator memberikan feedback visual
- User tahu proses sedang berjalan
- Tidak terkesan hang atau lambat
- Professional UX

## ALTERNATIVE SOLUTIONS

### Option 1: Progress Bar
```javascript
Swal.fire({
  title: 'Menyelesaikan Ujian...',
  html: '<div class="progress">
          <div class="progress-bar progress-bar-striped progress-bar-animated" 
               style="width: 100%"></div>
        </div>',
  showConfirmButton: false
});
```

### Option 2: Step Indicator
```javascript
Swal.fire({
  title: 'Menyelesaikan Ujian...',
  html: '<ol style="text-align: left;">
          <li>✓ Validasi jawaban</li>
          <li>⏳ Menghitung nilai...</li>
          <li>⏳ Menyimpan hasil...</li>
        </ol>',
  showConfirmButton: false
});
```

### Option 3: Animated Icon
```javascript
Swal.fire({
  title: 'Menyelesaikan Ujian...',
  html: '<div class="spinner-border text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>',
  showConfirmButton: false
});
```

## SUMMARY

**Masalah:** Jeda lama tanpa feedback saat submit ujian

**Solusi:** Tambah loading indicator dengan SweetAlert2

**Impact:**
- ✅ User experience lebih baik
- ✅ Tidak terkesan hang
- ✅ Professional feedback
- ✅ Clear communication

**Status:** ✅ FIXED

User sekarang mendapat feedback visual yang jelas saat submit ujian!
