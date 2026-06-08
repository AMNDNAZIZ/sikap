# Perbaikan Error 404 di Chat Siswa

## 🐛 Masalah

Di halaman chat siswa, muncul error di console browser:
```
GET http://localhost:8080/siakad/index.php/siswa/chat/clear-mentions/1 404 (Not Found)
```

## 🔍 Root Cause

### 1. Method `clearMentions` Tidak Ada di Controller

File: `app/Controllers/Siswa/Chat.php`

Controller Chat siswa tidak memiliki method `clearMentions()`, padahal JavaScript di view chat memanggil endpoint ini untuk menandai mention sebagai sudah dibaca saat user membuka room chat.

### 2. Route Tidak Terdaftar

File: `app/Config/Routes.php`

Route untuk `siswa/chat/clear-mentions/(:num)` tidak terdaftar di routes siswa, meskipun route lain seperti `chat/fetch` sudah ada.

## ✅ Solusi

### 1. Tambah Method `clearMentions` di Controller

**File:** `app/Controllers/Siswa/Chat.php`

Tambahkan method baru setelah method `cleanOldMessages()`:

```php
// 🔹 Clear mentions untuk class tertentu (AJAX)
public function clearMentions($classId)
{
    $userId = $this->currentUserId();
    if (!$userId) {
        return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
    }

    $db = \Config\Database::connect();

    // Temukan room untuk class ini
    $room = $this->roomModel->where('class_id', $classId)->first();
    if (!$room) {
        return $this->response->setJSON(['status' => 'ok', 'cleared' => 0]);
    }

    // Buat subquery: semua message id di room ini
    $sub = $db->table('chat_messages')->select('id')->where('room_id', $room['id'])->getCompiledSelect();

    // Update chat_mentions yang message_id IN (subquery) dan untuk user ini
    $builder = $db->table('chat_mentions');
    $builder->where('mentioned_user_id', $userId);
    $builder->where('is_read', 0);
    $builder->where("message_id IN ($sub)", null, false);
    $updated = $builder->update(['is_read' => 1]);

    // Hitung affected rows
    $affected = $db->affectedRows();

    return $this->response->setJSON(['status' => 'ok', 'cleared' => (int) $affected]);
}
```

**Fungsi Method:**
- Menerima parameter `$classId` (ID kelas)
- Mencari room chat berdasarkan class_id
- Menandai semua mention untuk user yang sedang login sebagai sudah dibaca (`is_read = 1`)
- Mengembalikan JSON response dengan jumlah mention yang di-clear

### 2. Tambah Route

**File:** `app/Config/Routes.php`

Tambahkan route di dalam group `siswa`:

```php
$routes->get('chat', 'Chat::index');
$routes->get('chat/room/(:num)', 'Chat::room/$1');
$routes->post('chat/send', 'Chat::send');
$routes->get('chat/fetch/(:num)', 'Chat::fetch/$1');
$routes->get('chat/clear-mentions/(:num)', 'Chat::clearMentions/$1'); // ← ADDED
```

## 📊 Flow Diagram

### Before (Error):
```
User buka chat room
  ↓
JavaScript call: GET /siswa/chat/clear-mentions/1
  ↓
Route tidak ditemukan
  ↓
404 Not Found ❌
  ↓
Mention tidak ter-clear
```

### After (Fixed):
```
User buka chat room
  ↓
JavaScript call: GET /siswa/chat/clear-mentions/1
  ↓
Route ditemukan → Chat::clearMentions(1)
  ↓
Query database: UPDATE chat_mentions SET is_read = 1
  ↓
Return JSON: {status: 'ok', cleared: 3}
  ↓
Mention badge di navbar berkurang ✅
```

## 🎯 Affected Files

1. ✅ `app/Controllers/Siswa/Chat.php` - Tambah method `clearMentions()`
2. ✅ `app/Config/Routes.php` - Tambah route `chat/clear-mentions/(:num)`

## 🧪 Testing

### Test Case 1: Clear Mentions Saat Buka Room

**Setup:**
1. User A mention User B di chat kelas
2. User B belum buka chat (ada badge notifikasi)

**Steps:**
1. Login sebagai User B (siswa)
2. Buka halaman chat kelas
3. Cek console browser (F12)

**Expected Result:**
- ✅ Tidak ada error 404
- ✅ Request berhasil: `GET /siswa/chat/clear-mentions/1` → 200 OK
- ✅ Response JSON: `{status: 'ok', cleared: X}`
- ✅ Badge mention di navbar berkurang/hilang

### Test Case 2: Tidak Ada Mention

**Setup:**
1. User tidak punya mention yang belum dibaca

**Steps:**
1. Login sebagai siswa
2. Buka halaman chat kelas
3. Cek response

**Expected Result:**
- ✅ Request berhasil: 200 OK
- ✅ Response JSON: `{status: 'ok', cleared: 0}`
- ✅ Tidak ada error

### Test Case 3: Multiple Mentions

**Setup:**
1. User A mention User B 3 kali di chat
2. User B belum buka chat

**Steps:**
1. Login sebagai User B
2. Buka halaman chat kelas
3. Cek response

**Expected Result:**
- ✅ Request berhasil: 200 OK
- ✅ Response JSON: `{status: 'ok', cleared: 3}`
- ✅ Semua 3 mention ter-clear
- ✅ Badge mention hilang

## 🔍 Debugging

### Cek Route Terdaftar

Jalankan command:
```bash
php spark routes | grep "chat/clear-mentions"
```

**Expected Output:**
```
GET     siswa/chat/clear-mentions/(:num)    Siswa\Chat::clearMentions/$1
```

### Cek Method Ada di Controller

```php
// Di controller
var_dump(method_exists($this, 'clearMentions')); // Should be true
```

### Cek Database

```sql
-- Cek mention yang belum dibaca
SELECT * FROM chat_mentions 
WHERE mentioned_user_id = [USER_ID] 
AND is_read = 0;

-- Setelah clear, should be empty atau is_read = 1
```

### Cek Console Browser

1. Buka Developer Tools (F12)
2. Tab Network
3. Filter: XHR
4. Buka chat room
5. Cari request `clear-mentions`
6. Cek status code (should be 200)
7. Cek response (should be JSON)

## 📝 Notes

### Kenapa Pakai GET bukan POST?

- Method ini hanya membaca dan update data, tidak create/delete
- Dipanggil otomatis saat buka room (tidak ada form submit)
- GET lebih simple untuk AJAX call
- Konsisten dengan endpoint lain seperti `chat/fetch`

### Kenapa Pakai `is_read = 1` bukan DELETE?

- Untuk audit trail (history mention)
- Bisa digunakan untuk statistik
- Bisa di-revert jika diperlukan
- Konsisten dengan design database

### Security

- Method mengecek `$userId` dari session
- Hanya clear mention untuk user yang sedang login
- Tidak bisa clear mention user lain
- Protected dengan auth filter di route group

## ✅ Verification Checklist

- [x] Method `clearMentions()` added to controller
- [x] Route `chat/clear-mentions/(:num)` added
- [x] No syntax errors
- [x] Method returns JSON response
- [x] Method checks user authentication
- [x] Method updates database correctly
- [x] Route accessible by siswa (role 4,5)
- [x] Console error 404 resolved

## 🔄 Related Features

Fitur ini terkait dengan:
- **Chat Mention System** - Sistem mention user di chat
- **Notification Badge** - Badge notifikasi di navbar
- **Chat Room** - Halaman chat kelas
- **Real-time Updates** - Update mention count real-time

## 📚 Reference

Method ini di-copy dari:
- `app/Controllers/Admin/Chat.php::clearMentions()`
- Dengan penyesuaian untuk context siswa

---

**Fixed:** 2026-04-04  
**Issue:** 404 error on /siswa/chat/clear-mentions/1  
**Solution:** Add clearMentions method to Siswa\Chat controller and register route
