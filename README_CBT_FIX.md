# CBT Fix: Loading & 403 Forbidden - README

## Quick Overview

Dua masalah CBT telah diselesaikan dengan sukses:

1. ✅ **Loading lama saat mulai ujian** → Fixed dengan loading indicator & better UX
2. ✅ **Error 403 Forbidden saat save answer** → Fixed dengan CSRF token refresh system

## What Was Changed

### Files Modified (3 files):
1. `app/Config/Security.php` - Extended CSRF token expiry
2. `app/Controllers/Siswa/Cbt.php` - Added refreshCsrf endpoint
3. `app/Views/siswa/cbt/ujian.php` - Added auto-refresh & loading UI

### Lines Changed:
- Total: ~150 lines
- Added: ~120 lines
- Modified: ~30 lines

## How It Works

### Pr