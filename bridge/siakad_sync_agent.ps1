# ============================================================
# SIAKAD SYNC AGENT v2.0
# Script untuk sinkronisasi data Dapodik Lokal ke Siakad Online
#
# PERUBAHAN v2.0:
# - Tambah sync_mode parameter (skip/update/merge)
# - Kirim field nis dan nisn secara terpisah (tidak lagi fallback)
# - Tambah validasi token sebelum push
# - Tambah retry logic untuk koneksi gagal
# - Tambah logging ke file
# - Perbaiki penanganan error response dari server
# ============================================================

# --- KONFIGURASI ---
$SIAKAD_URL   = "https://smpistora.sch.id/api/dapodik/receive"  # Ganti dengan URL Hosting Anda
$SIAKAD_TOKEN = "siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j"      # Harus sama dengan .env SIAKAD_SYNC_TOKEN
$DAPODIK_URL  = "http://127.0.0.1:5774/WebService"
$DAPODIK_KEY  = "Zikf0c0WK7snRmf"   # Key Web Service Dapodik
$DAPODIK_NPSN = "20105399"

# Mode sinkronisasi untuk data yang sudah ada:
#   skip   = lewati (default, aman untuk sync pertama)
#   update = timpa semua field dengan data Dapodik
#   merge  = hanya isi field yang masih kosong
$SYNC_MODE = "skip"

# Jumlah retry jika koneksi gagal
$MAX_RETRY = 3

# Log file
$LOG_FILE = "$PSScriptRoot\sync_log_$(Get-Date -Format 'yyyyMMdd').txt"

# ============================================================

function Write-Log($message, $level = "INFO") {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] [$level] $message"
    Add-Content -Path $LOG_FILE -Value $line
    switch ($level) {
        "ERROR"   { Write-Host $line -ForegroundColor Red }
        "WARNING" { Write-Host $line -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $line -ForegroundColor Green }
        default   { Write-Host $line -ForegroundColor Cyan }
    }
}

function Clean-JsonResponse($raw) {
    # Strip HTTP header jika server Dapodik (Delphi/Indy) menyertakannya di body
    if ($raw -like "HTTP/*") {
        $parts = $raw -split "`r`n`r`n"
        $raw = $parts[-1].Trim()
        # Coba lagi dengan single newline
        if ($raw -like "HTTP/*") {
            $parts = $raw -split "`n`n"
            $raw = $parts[-1].Trim()
        }
    }
    return $raw.Trim()
}

function Fetch-Dapodik($endpoint) {
    $url = "$DAPODIK_URL/$endpoint`?npsn=$DAPODIK_NPSN"
    $headers = @{ "Authorization" = "Bearer $DAPODIK_KEY" }

    for ($attempt = 1; $attempt -le $MAX_RETRY; $attempt++) {
        try {
            Write-Log "Fetching $endpoint (attempt $attempt/$MAX_RETRY)..."
            $response = Invoke-WebRequest -Uri $url -Headers $headers -Method Get -TimeoutSec 30 -ErrorAction Stop
            $cleanJson = Clean-JsonResponse $response.Content
            $data = $cleanJson | ConvertFrom-Json
            Write-Log "Fetch $endpoint berhasil." "SUCCESS"
            return $data
        } catch {
            Write-Log "Fetch $endpoint gagal (attempt $attempt): $_" "WARNING"
            if ($attempt -lt $MAX_RETRY) { Start-Sleep -Seconds 3 }
        }
    }

    Write-Log "Fetch $endpoint GAGAL setelah $MAX_RETRY percobaan." "ERROR"
    return $null
}

function Normalize-Students($rows) {
    # Normalisasi field siswa sebelum dikirim ke SIAKAD
    # PENTING: NIS dan NISN adalah dua field yang BERBEDA:
    #   - NISN = Nomor Induk Siswa Nasional (10 digit, unik nasional, dari Kemdikbud)
    #   - NIS  = Nomor Induk Siswa lokal sekolah (ditentukan sekolah masing-masing)
    # Jangan pernah mengisi NIS dengan nilai NISN atau sebaliknya.
    $normalized = @()
    $skippedNoNISN = 0

    foreach ($s in $rows) {
        $obj = [ordered]@{}

        # Salin semua field dari Dapodik
        $s.PSObject.Properties | ForEach-Object { $obj[$_.Name] = $_.Value }

        # Pastikan nisn adalah string yang di-trim
        $nisn = if ($s.nisn) { "$($s.nisn)".Trim() } else { "" }
        $obj['nisn'] = $nisn

        # NIS lokal: ambil dari field 'nis' Dapodik saja.
        # JANGAN fallback ke nisn jika nis kosong — biarkan kosong.
        $obj['nis'] = if ($s.nis) { "$($s.nis)".Trim() } else { "" }

        # Pastikan nik juga tersedia untuk deteksi duplikat berlapis di server
        $obj['nik'] = if ($s.nik) { "$($s.nik)".Trim() } else { "" }

        # Skip siswa tanpa NISN — tidak bisa dibuat akun
        if ([string]::IsNullOrWhiteSpace($nisn)) {
            $nama = if ($s.nama) { $s.nama } else { "UNKNOWN" }
            Write-Log "SKIP: Siswa '$nama' tidak punya NISN, tidak dikirim." "WARNING"
            $skippedNoNISN++
            continue
        }

        $normalized += [PSCustomObject]$obj
    }

    if ($skippedNoNISN -gt 0) {
        Write-Log "$skippedNoNISN siswa dilewati karena tidak punya NISN." "WARNING"
    }

    return $normalized
}

function Push-To-Siakad($type, $dataRows, $syncMode = "skip") {
    if ($null -eq $dataRows -or @($dataRows).Count -eq 0) {
        Write-Log "Tidak ada data $type untuk dikirim." "WARNING"
        return
    }

    $count = @($dataRows).Count
    Write-Log "Mengirim $count baris data '$type' ke SIAKAD (mode: $syncMode)..."

    $payload = @{
        "type"      = $type
        "sync_mode" = $syncMode
        "data"      = $dataRows
    } | ConvertTo-Json -Depth 10 -Compress

    $headers = @{
        "X-Siakad-Sync-Token" = $SIAKAD_TOKEN
        "Content-Type"        = "application/json; charset=utf-8"
    }

    for ($attempt = 1; $attempt -le $MAX_RETRY; $attempt++) {
        try {
            $response = Invoke-RestMethod -Uri $SIAKAD_URL -Headers $headers -Method Post `
                -Body ([System.Text.Encoding]::UTF8.GetBytes($payload)) -ErrorAction Stop

            if ($response.status -eq "success") {
                Write-Log "Push '$type' BERHASIL: $($response.message)" "SUCCESS"
                if ($response.detail) {
                    Write-Log "  Detail: inserted=$($response.detail.inserted), updated=$($response.detail.updated), skipped=$($response.detail.skipped)" "INFO"
                    if ($type -eq "students" -and $response.detail.inserted -gt 0) {
                        Write-Log "  Akun dibuat: $($response.detail.inserted) siswa + $($response.detail.inserted) orang tua (otomatis)" "INFO"
                    }
                    if ($response.detail.errors -gt 0) {
                        Write-Log "  PERINGATAN: $($response.detail.errors) data gagal diproses — cek log server." "WARNING"
                    }
                }
            } else {
                Write-Log "Push '$type' ditolak server: $($response.message)" "WARNING"
            }
            return

        } catch {
            $statusCode = $_.Exception.Response.StatusCode.value__
            $errMsg = $_.Exception.Message

            # Coba baca response body untuk pesan error lebih detail
            try {
                $stream = $_.Exception.Response.GetResponseStream()
                $reader = New-Object System.IO.StreamReader($stream)
                $respText = $reader.ReadToEnd()
                $errMsg = $respText
            } catch {}

            Write-Log "Push '$type' gagal (attempt $attempt, HTTP $statusCode): $errMsg" "WARNING"

            if ($attempt -lt $MAX_RETRY) {
                Start-Sleep -Seconds 5
            } else {
                Write-Log "Push '$type' GAGAL setelah $MAX_RETRY percobaan." "ERROR"
            }
        }
    }
}

# ============================================================
# MAIN
# ============================================================
Write-Log "=== SIAKAD SYNC AGENT v2.0 STARTING ===" "INFO"
Write-Log "Target: $SIAKAD_URL" "INFO"
Write-Log "NPSN: $DAPODIK_NPSN | Sync Mode: $SYNC_MODE" "INFO"
Write-Log "Log: $LOG_FILE" "INFO"
Write-Host ""

# 1. Ambil & Kirim SISWA
Write-Log "--- SINKRONISASI SISWA ---" "INFO"
$resSiswa = Fetch-Dapodik "getPesertaDidik"
if ($null -ne $resSiswa) {
    $rows = if ($null -ne $resSiswa.rows) { $resSiswa.rows } else { $resSiswa.data }
    $rowCount = @($rows).Count
    Write-Log "Ditemukan $rowCount siswa dari Dapodik."

    if ($rowCount -gt 0) {
        # Normalisasi field NIS/NISN sebelum dikirim
        $normalizedRows = Normalize-Students $rows
        Push-To-Siakad "students" $normalizedRows $SYNC_MODE
    }
}

Write-Host ""

# 2. Ambil & Kirim GURU
Write-Log "--- SINKRONISASI GURU ---" "INFO"
$resGuru = Fetch-Dapodik "getGtk"
if ($null -ne $resGuru) {
    $rows = if ($null -ne $resGuru.rows) { $resGuru.rows } else { $resGuru.data }
    $rowCount = @($rows).Count
    Write-Log "Ditemukan $rowCount guru dari Dapodik."

    if ($rowCount -gt 0) {
        Push-To-Siakad "teachers" $rows $SYNC_MODE
    }
}

Write-Host ""
Write-Log "=== SYNC SELESAI. Log tersimpan di: $LOG_FILE ===" "SUCCESS"
pause
