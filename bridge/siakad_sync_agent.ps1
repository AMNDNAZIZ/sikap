# SIAKAD SYNC AGENT v1.0
# Script untuk sinkronisasi data Dapodik Lokal ke Siakad Online

# --- KONFIGURASI ---
$SIAKAD_URL = "https://smpistora.sch.id/api/dapodik/receive" # Ganti dengan URL Hosting Anda
$SIAKAD_TOKEN = "siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j"
$DAPODIK_URL = "http://127.0.0.1:5774/WebService"
$DAPODIK_KEY = "Zikf0c0WK7snRmf" # Masukkan Key Web Service Dapodik Anda
$DAPODIK_NPSN = "20105399"

# -----------------
Write-Host "--- SIAKAD SYNC AGENT STARTING ---" -ForegroundColor Cyan

function Clean-JsonResponse($raw) {
    if ($raw -like "HTTP/*") {
        $parts = $raw -split "`r`n`r`n"
        return $parts[-1].Trim()
    }
    return $raw.Trim()
}

function Fetch-Dapodik($endpoint) {
    $url = "$DAPODIK_URL/$endpoint`?npsn=$DAPODIK_NPSN"
    $headers = @{ "Authorization" = "Bearer $DAPODIK_KEY" }
    
    try {
        $response = Invoke-WebRequest -Uri $url -Headers $headers -Method Get -TimeoutSec 10 -ErrorAction Stop
        $cleanJson = Clean-JsonResponse $response.Content
        $data = $cleanJson | ConvertFrom-Json
        return $data
    } catch {
        Write-Host "FAILED to fetch from Dapodik ($endpoint): $_" -ForegroundColor Red
        return $null
    }
}

function Push-To-Siakad($type, $dataRows) {
    if ($null -eq $dataRows) { return }
    
    $payload = @{
        "type" = $type
        "data" = $dataRows
    } | ConvertTo-Json -Depth 5
    
    $headers = @{ 
        "X-Siakad-Sync-Token" = $SIAKAD_TOKEN
        "Content-Type" = "application/json"
    }

    try {
        $response = Invoke-RestMethod -Uri $SIAKAD_URL -Headers $headers -Method Post -Body $payload -ErrorAction Stop
        Write-Host "SUCCESS: $($response.message)" -ForegroundColor Green
    } catch {
        $errorBody = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($errorBody)
        $respText = $reader.ReadToEnd()
        Write-Host "FAILED to push to Siakad: $respText" -ForegroundColor Red
    }
}

# 1. Ambil & Kirim SISWA
Write-Host "Penarikan data SISWA..." -ForegroundColor Yellow
$resSiswa = Fetch-Dapodik "getPesertaDidik"
if ($null -ne $resSiswa) {
    $rows = if ($null -ne $resSiswa.rows) { $resSiswa.rows } else { $resSiswa.data }
    Write-Host "Ditemukan $(@($rows).Count) siswa. Mengirim ke Online..."
    Push-To-Siakad "students" $rows
}

# 2. Ambil & Kirim GURU
Write-Host "`nPenarikan data GURU..." -ForegroundColor Yellow
$resGuru = Fetch-Dapodik "getGtk"
if ($null -ne $resGuru) {
    $rows = if ($null -ne $resGuru.rows) { $resGuru.rows } else { $resGuru.data }
    Write-Host "Ditemukan $(@($rows).Count) guru. Mengirim ke Online..."
    Push-To-Siakad "teachers" $rows
}

Write-Host "`n--- SYNC PROCESS COMPLETED ---" -ForegroundColor Cyan
pause
