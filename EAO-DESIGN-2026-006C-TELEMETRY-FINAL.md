# EAO-DESIGN-2026-006C-TELEMETRY-FINAL
## Spesifikasi Teknis Final: Preservasi matched_id, Arsitektur Logging Tunggal & Pemisahan Intent

**Nomor Dokumen:** EAO-DESIGN-2026-006C-TELEMETRY-FINAL  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (System Architect, AI Security Reviewer, QA Engineer, Developer)  
**Dasar Mandat:** EAO-HO-006C (Penutupan Temuan Audit Bagian C)  
**Status Desain:** FINAL REFINED SPECIFICATION  
**Batasan Khusus:** Kolom `matched_id` DILARANG digunakan untuk menyisipkan metadata token atau kuota.

---

## 1. Analisis Masalah Kehilangan `matched_id` pada Audit EAO-006B

Pada audit **EAO-REP-2026-006B (Temuan Kritis 2)**, terungkap bahwa:
- Di `AiCopilot.php` baris 410, controller memanggil `$this->logInteraction()` dengan menyertakan `$matched['id']` (misal: `'action_jadwal_full'`) ke kolom `matched_id`.
- Namun di `UnifiedAiService.php` baris 446, metode `recordAuditTelemetry()` mengisi `'matched_id' => null`.
- Jika baris 410 di controller dihapus begitu saja tanpa penyesuaian di service layer, data aksi navigasi yang dihasilkan AI akan **hilang selamanya (menjadi NULL)** di tabel `ai_assistant_logs`.
- **Ketentuan Human Owner (EAO-HO-006C):**
  1. Kolom `matched_id` murni digunakan untuk **identitas navigasi aksi lokal**.
  2. Kolom `intent` **tidak boleh dicampuradukkan** dengan `matched_id`.
  3. Menghapus logging duplikat controller **tidak boleh menghilangkan data aksi penting**.

---

## 2. Finalisasi Signature & Implementasi `recordAuditTelemetry()`

Untuk menyelesaikan temuan ini secara permanen, metode telemetri pada `UnifiedAiService` disempurnakan dengan signature final:

```php
// app/Services/Ai/UnifiedAiService.php
/**
 * Mencatat telemetri audit penggunaan AI terpadu (Dual-Traceability Single Logging)
 * 
 * @param AiRequest $request Objek permintaan tersanitasi
 * @param AiResponse $response Objek respons dari provider
 * @param int|null $userId ID akun pengguna pemohon (Requester ID)
 * @param int|null $roleId Role pengguna pemohon (RBAC)
 * @param ResolvedApiKey|null $resolvedKey DTO hasil resolusi kunci
 * @param string|null $matchedId ID tindakan berizin yang dipilih AI (Action ID)
 * @param string|null $intent Nama kategori/intent operasional
 */
public function recordAuditTelemetry(
    AiRequest $request,
    AiResponse $response,
    ?int $userId = null,
    ?int $roleId = null,
    ?ResolvedApiKey $resolvedKey = null,
    ?string $matchedId = null,
    ?string $intent = null
): void {
    try {
        if ($this->db && $this->db->tableExists('ai_assistant_logs')) {
            // 1. Amankan teks kueri (dibatasi 250 karakter)
            $safeQuery = mb_substr($request->prompt, 0, 250);

            // 2. Tentukan tag akuntabilitas ganda untuk kolom source (maksimal 50 karakter)
            $telemetrySource = $resolvedKey
                ? $resolvedKey->getTelemetrySourceTag($response->provider)
                : ($response->provider ?: 'unknown');

            // 3. Tentukan nilai kolom intent (kategori operasional atau kode galat)
            $finalIntent = $intent;
            if (empty($finalIntent)) {
                $finalIntent = $response->success 
                    ? 'AI_COMPLETION' 
                    : ('ERR_' . ($response->errorCode ?: 'UNKNOWN'));
            }

            // 4. Pastikan matched_id murni memuat ID aksi navigasi lokal (tanpa token/kuota)
            $cleanMatchedId = !empty($matchedId) ? mb_substr(trim($matchedId), 0, 100) : null;

            // 5. Eksekusi single atomic insert ke database
            $this->db->table('ai_assistant_logs')->insert([
                'user_id'    => $userId,
                'role_id'    => $roleId,
                'query'      => $safeQuery,
                'intent'     => mb_substr($finalIntent, 0, 100),
                'matched_id' => $cleanMatchedId,
                'source'     => substr($telemetrySource, 0, 50),
                'ip_address' => service('request')->getIPAddress() ?? '127.0.0.1',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    } catch (\Throwable $e) {
        log_message('error', '[UnifiedAiService::recordAuditTelemetry] Gagal mencatat telemetri: ' . $e->getMessage());
    }
}
```

---

## 3. Pemisahan Tegas antara Kolom `intent` dan `matched_id`

Untuk menghindari kerancuan data pada audit manajemen:
- **Kolom `intent` (Tipe: `VARCHAR(100)`):**  
  Menyimpan klasifikasi *kategori maksud* dari interaksi tersebut.  
  *Contoh nilai:* `'Akademik'`, `'Kesiswaan'`, `'Kepegawaian'`, `'AI_NLU'`, atau `'ERR_QUOTA_EXHAUSTED'`.
- **Kolom `matched_id` (Tipe: `VARCHAR(100)`):**  
  Menyimpan secara presisi *ID tindakan navigasi berizin* yang dipilihkan oleh sistem/AI untuk dieksekusi pengguna.  
  *Contoh nilai:* `'action_jadwal_full'`, `'action_input_nilai'`, `'action_monitoring_cbt'`.  
  *Jaminan Integritas:* Kolom ini bernilai `NULL` jika permintaan tidak menghasilkan rekomendasi aksi navigasi (misal kueri obrolan umum). Dilarang keras menyisipkan format `tokens:p=...` ke kolom ini.

---

## 4. Pemetaan Jalur Logging Menyeluruh (Audit Trail Mapping)

Dengan penataan ini, seluruh alur interaksi memiliki satu jalur pencatatan audit yang jelas tanpa tumpang tindih:

| Jenis Interaksi | Pintu Masuk di `AiCopilot.php` | Komponen Pencatat | Nilai `source` | Nilai `intent` | Nilai `matched_id` | Jumlah Baris Log |
|:---|:---|:---|:---|:---|:---|:---:|
| **Direct Live Query** (Jadwal/Absen/CBT) | `tryExecuteLiveQuery()` | `AiCopilot::logInteraction()` | `'database'` | `'JADWAL_GURU'` / `'MONITORING_CBT'` | `'action_jadwal_full'` / `'action_monitoring_cbt'` | **Tepat 1 Baris** |
| **Feature Knowledge** (Panduan Fitur) | `EspataFeatureKnowledge` | `AiCopilot::logInteraction()` | `'knowledge_base'` | `'FEATURE_GUIDE'` | ID Panduan Fitur (`guide_id`) | **Tepat 1 Baris** |
| **Pemanggilan AI Sukses** (Guru Ber-Key) | `callUnifiedAiNlu()` | `UnifiedAiService::recordAuditTelemetry()` | `'groq:self:12'` / `'gemini:self:12'` | Kategori Aksi (`'Akademik'`) | ID Aksi (`'action_input_nilai'`) | **Tepat 1 Baris** |
| **Pemanggilan AI Sukses** (Fallback Admin) | `callUnifiedAiNlu()` | `UnifiedAiService::recordAuditTelemetry()` | `'groq:admin_fallback:1'` | Kategori Aksi (`'Akademik'`) | ID Aksi (`'action_input_nilai'`) | **Tepat 1 Baris** |
| **Pemanggilan AI Gagal** (Kuota Habis) | `callUnifiedAiNlu()` | `UnifiedAiService::recordAuditTelemetry()` | `'groq:admin_fallback:1'` | `'ERR_QUOTA_EXHAUSTED'` | `NULL` | **Tepat 1 Baris** |
| **Smart Local Matcher** (Fallback Non-AI) | `matchLocally()` | `AiCopilot::logInteraction()` | `'local'` | Kategori Aksi Lokal | ID Aksi Lokal | **Tepat 1 Baris** |

---

## 5. Pembuktian Eliminasi Duplikasi Tanpa Kehilangan Informasi

Pada implementasi lama:
- Request AI menghasilkan **Baris A** (dari `UnifiedAiService` dengan `source: groq`, `intent: AI_COMPLETION`, `matched_id: NULL`) dan **Baris B** (dari `AiCopilot` dengan `source: groq`, `intent: Akademik`, `matched_id: action_input_nilai`).

Pada implementasi final Fase 1C:
- Baris B dihapus. Baris A diperkaya sehingga menyimpan:
  - `user_id`: 12 (Guru Pemohon)
  - `role_id`: 3 (Guru)
  - `query`: Pertanyaan guru
  - `intent`: 'Akademik'
  - `matched_id`: 'action_input_nilai' (ID Aksi Preservasi 100%)
  - `source`: 'groq:admin_fallback:1' (Akuntabilitas Ganda Utuh)
- **Hasil:** Informasi aksi navigasi tetap tersimpan 100%, akuntabilitas pemilik kunci tersimpan 100%, dan beban baris database berkurang 50% per interaksi AI.
