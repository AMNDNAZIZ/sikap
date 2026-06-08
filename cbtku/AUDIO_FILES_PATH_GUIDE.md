di folder upload# Panduan Path File Audio

## 📁 Struktur Folder Audio

### ✅ Path yang BENAR
```
/path/to/project/
└── public/                          # Document root
    └── uploads/
        └── audio/                   # ← File audio disimpan di sini
            ├── audio_123456.mp3
            ├── audio_789012.wav
            └── audio_345678.ogg
```

### ❌ Path yang SALAH
```
/path/to/project/
├── uploads/                         # ← SALAH! Tidak accessible dari web
│   └── audio/
└── public/
    └── uploads/
        └── audio/
```

---

## 🔧 Implementasi di Code

### 1. Upload Audio (Controller)

**File**: `app/Controllers/Admin/CbtBankSoal.php`

**Method**: `updateSoal()`

```php
// ✅ BENAR: Gunakan FCPATH untuk public folder
$file->move(FCPATH . 'uploads/audio', $newName);

// ❌ SALAH: Jangan gunakan ROOTPATH
// $file->move(ROOTPATH . 'uploads/audio', $newName);
```

**Kode Lengkap**:
```php
$audioFiles = ['audio_file', 'audio_a', 'audio_b', 'audio_c', 'audio_d', 'audio_e'];

foreach ($audioFiles as $field) {
    $file = $this->request->getFile($field);
    if ($file && $file->isValid() && !$file->hasMoved()) {
        // Validate file type
        if (!validate_file_type($file, $allowedAudioTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File audio harus berformat MP3, WAV, atau OGG'
            ]);
        }
        
        // Sanitize filename
        $extension = $file->getExtension();
        $newName = uniqid('audio_', true) . '.' . $extension;
        
        // ✅ Move to public/uploads/audio
        $file->move(FCPATH . 'uploads/audio', $newName);

        if ($field === 'audio_file') {
            $data['media_audio'] = $newName;
            $data['has_audio'] = 1;
        } else {
            $data[$field] = $newName;
        }
    }
}
```

---

### 2. Delete Audio (Controller)

**File**: `app/Controllers/Admin/CbtBankSoal.php`

**Method**: `deleteAudio()`

```php
// ✅ BENAR: Gunakan FCPATH
$path = FCPATH . 'uploads/audio/' . $soal[$field];

// ❌ SALAH: Jangan gunakan ROOTPATH
// $path = ROOTPATH . 'uploads/audio/' . $soal[$field];
```

**Kode Lengkap**:
```php
public function deleteAudio($questionId, $type = 'main')
{
    $soal = $this->questionModel->find($questionId);
    if (!$soal)
        return $this->response->setJSON([
            'success' => false, 
            'message' => 'Soal tidak ditemukan'
        ]);

    $field = ($type === 'main') ? 'media_audio' : 'audio_' . $type;

    if (!empty($soal[$field])) {
        // ✅ BENAR: Gunakan FCPATH
        $path = FCPATH . 'uploads/audio/' . $soal[$field];
        
        if (file_exists($path)) {
            unlink($path);
        }

        $update = [$field => null];
        if ($type === 'main') {
            $update['has_audio'] = 0;
        }
        $this->questionModel->update($questionId, $update);
    }
    
    return $this->response->setJSON([
        'success' => true, 
        'message' => 'Audio berhasil dihapus.'
    ]);
}
```

---

### 3. Display Audio (View)

**File**: `app/Views/admin/cbt/banksoal/edit_soal.php`

```php
<?php if (!empty($soal['media_audio'])): ?>
    <div class="mt-2 p-2 border rounded bg-light" id="audioPreview">
        <p class="mb-1 text-success">
            <i class="bi bi-volume-up"></i> Audio Terpasang:
        </p>
        <audio controls class="w-100 mb-2">
            <!-- ✅ BENAR: Gunakan base_url() -->
            <source src="<?= base_url('uploads/audio/' . $soal['media_audio']) ?>">
            Browser Anda tidak mendukung elemen audio.
        </audio>
        <button type="button" class="btn btn-sm btn-danger" 
                onclick="deleteAudio(<?= $soal['id'] ?>, 'main')">
            <i class="bi bi-trash"></i> Hapus Audio
        </button>
    </div>
<?php endif; ?>
```

**Audio Opsi**:
```php
<?php foreach (['a', 'b', 'c', 'd', 'e'] as $opt):
    $field = 'audio_' . $opt;
    $has = !empty($soal[$field]);
?>
    <div class="col-md-6 mb-3">
        <div class="card bg-light border">
            <div class="card-body p-2">
                <strong>Opsi <?= strtoupper($opt) ?></strong>
                <input type="file" name="<?= $field ?>" 
                       class="form-control form-control-sm mt-1" 
                       accept="audio/*">
                <?php if ($has): ?>
                    <div class="mt-2" id="audioPreview_<?= $opt ?>">
                        <audio controls class="w-100" style="height:30px;">
                            <!-- ✅ BENAR: Gunakan base_url() -->
                            <source src="<?= base_url('uploads/audio/' . $soal[$field]) ?>">
                        </audio>
                        <button type="button" 
                                class="btn btn-xs btn-outline-danger w-100 mt-1"
                                onclick="deleteAudio(<?= $soal['id'] ?>, '<?= $opt ?>')">
                            Hapus Audio <?= strtoupper($opt) ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
```

---

### 4. Backup Audio (Controller)

**File**: `app/Controllers/Admin/CbtBankSoal.php`

**Method**: `backup()`

```php
// Audio files
$audioFields = ['media_audio', 'audio_a', 'audio_b', 'audio_c', 'audio_d', 'audio_e'];

foreach ($audioFields as $field) {
    if (!empty($q[$field])) {
        // ✅ BENAR: Path relatif dari FCPATH
        $audioPath = 'uploads/audio/' . $q[$field];
        $fullAudioPath = FCPATH . $audioPath;
        
        if (file_exists($fullAudioPath)) {
            $zip->addFile($fullAudioPath, $audioPath);
        }
    }
}
```

---

## 🛠️ Commands untuk Maintenance

### 1. Check Uploads
Cek semua folder upload termasuk audio:

```bash
php spark check:uploads
```

Output:
```
📁 Audio files (uploads/audio/)
   Path: /path/to/public/uploads/audio/
   ✓ Exists
   ✓ Writable
   Files: 5
   URL: http://localhost/cbtku/uploads/audio/
```

---

### 2. Fix Upload Folders
Pindahkan file audio dari root ke public (jika ada):

```bash
php spark fix:uploadfolders
```

Output:
```
Processing: Audio files (audio)
--------------------------------------------------
  ✓ Public folder exists
  ✓ Folder is writable
  Found 3 files in root/audio
  Moving files...
    ✓ Moved: audio_123.mp3
    ✓ Moved: audio_456.wav
    ✓ Moved: audio_789.ogg
  Moved: 3 files
```

---

## 📊 Database Structure

### Table: `cbt_questions`

**Kolom Audio**:
```sql
media_audio VARCHAR(255)    -- Audio utama soal
audio_a VARCHAR(255)        -- Audio opsi A
audio_b VARCHAR(255)        -- Audio opsi B
audio_c VARCHAR(255)        -- Audio opsi C
audio_d VARCHAR(255)        -- Audio opsi D
audio_e VARCHAR(255)        -- Audio opsi E
has_audio TINYINT(1)        -- Flag ada audio (0/1)
```

**Contoh Data**:
```sql
INSERT INTO cbt_questions (
    question_text, 
    media_audio, 
    audio_a, 
    audio_b,
    has_audio
) VALUES (
    'Listen to the audio and choose the correct answer',
    'audio_67890abcdef.mp3',
    'audio_12345abcdef.mp3',
    'audio_67890ghijkl.mp3',
    1
);
```

**Cara Akses**:
- Main audio: `http://localhost/cbtku/uploads/audio/audio_67890abcdef.mp3`
- Option A: `http://localhost/cbtku/uploads/audio/audio_12345abcdef.mp3`
- Option B: `http://localhost/cbtku/uploads/audio/audio_67890ghijkl.mp3`

---

## ✅ Checklist Verifikasi

### Upload Audio
- [ ] File tersimpan di `public/uploads/audio/`
- [ ] Filename menggunakan `uniqid()` untuk keunikan
- [ ] Extension divalidasi (mp3, wav, ogg)
- [ ] File size divalidasi (max 10MB)
- [ ] Database updated dengan filename

### Display Audio
- [ ] Audio player muncul di edit soal
- [ ] Audio bisa diplay
- [ ] Tombol hapus berfungsi
- [ ] URL menggunakan `base_url()`

### Delete Audio
- [ ] File fisik terhapus dari `public/uploads/audio/`
- [ ] Database field di-set NULL
- [ ] Flag `has_audio` di-update

### Backup/Restore
- [ ] Audio files included dalam ZIP backup
- [ ] Audio files restored ke lokasi yang benar

---

## 🚨 Common Issues

### Issue 1: Audio tidak bisa diplay

**Penyebab**: File tidak ada atau path salah

**Solusi**:
```bash
# Cek apakah file ada
ls -la public/uploads/audio/

# Cek permission
chmod 775 public/uploads/audio/

# Cek URL di browser
curl -I http://localhost/cbtku/uploads/audio/audio_123.mp3
```

---

### Issue 2: Upload gagal

**Penyebab**: Folder tidak writable

**Solusi**:
```bash
# Set permission
chmod 775 public/uploads/audio/

# Atau
chmod 777 public/uploads/audio/

# Set owner (jika perlu)
chown -R www-data:www-data public/uploads/
```

---

### Issue 3: File tersimpan di root/uploads

**Penyebab**: Code menggunakan ROOTPATH

**Solusi**:
```bash
# Pindahkan file
php spark fix:uploadfolders

# Atau manual
mv uploads/audio/* public/uploads/audio/
```

---

## 📝 Best Practices

1. **Selalu gunakan FCPATH** untuk upload:
   ```php
   $file->move(FCPATH . 'uploads/audio', $filename);
   ```

2. **Selalu gunakan base_url()** untuk display:
   ```php
   <source src="<?= base_url('uploads/audio/' . $filename) ?>">
   ```

3. **Validate file type dan size**:
   ```php
   $allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
   $maxSize = 10 * 1024 * 1024; // 10MB
   ```

4. **Generate unique filename**:
   ```php
   $newName = uniqid('audio_', true) . '.' . $extension;
   ```

5. **Clean up saat delete**:
   ```php
   // Delete file fisik
   if (file_exists($path)) {
       unlink($path);
   }
   
   // Update database
   $this->questionModel->update($id, ['media_audio' => null]);
   ```

---

## 🔗 Related Files

- Controller: `app/Controllers/Admin/CbtBankSoal.php`
- View: `app/Views/admin/cbt/banksoal/edit_soal.php`
- Command: `app/Commands/CheckUploads.php`
- Command: `app/Commands/FixUploadFolders.php`

---

**Last Updated**: 2026-02-26
**Version**: 1.0
