# ============================================
# CBT Update Package Creator v2
# Creates structured update package for distribution
# ============================================
# 
# LOCALHOST ONLY - DO NOT UPLOAD TO PRODUCTION
# This script requires .dev_environment file
# 
# ============================================

param(
    [Parameter(Mandatory=$false)]
    [string]$Version,
    
    [Parameter(Mandatory=$false)]
    [string]$Title,
    
    [string]$Description = "",
    
    [switch]$Critical = $false,
    
    [switch]$Help = $false
)

# Colors
$ColorCyan = "Cyan"
$ColorGreen = "Green"
$ColorYellow = "Yellow"
$ColorRed = "Red"
$ColorWhite = "White"
$ColorMagenta = "Magenta"

# Show help
if ($Help) {
    Write-Host "===============================================================" -ForegroundColor $ColorCyan
    Write-Host "  CBT UPDATE PACKAGE CREATOR - HELP" -ForegroundColor $ColorCyan
    Write-Host "===============================================================" -ForegroundColor $ColorCyan
    Write-Host ""
    Write-Host "USAGE:" -ForegroundColor $ColorWhite
    Write-Host "  .\create_update_package_v2.ps1 -Version <version> -Title <title>" -ForegroundColor $ColorWhite
    Write-Host ""
    Write-Host "PARAMETERS:" -ForegroundColor $ColorWhite
    Write-Host "  -Version      Version number (e.g., 1.0.2)" -ForegroundColor $ColorWhite
    Write-Host "  -Title        Update title" -ForegroundColor $ColorWhite
    Write-Host "  -Description  Update description (optional)" -ForegroundColor $ColorWhite
    Write-Host "  -Critical     Mark as critical update (optional)" -ForegroundColor $ColorWhite
    Write-Host "  -Help         Show this help message" -ForegroundColor $ColorWhite
    Write-Host ""
    Write-Host "EXAMPLES:" -ForegroundColor $ColorWhite
    Write-Host "  .\create_update_package_v2.ps1 -Version `"1.0.2`" -Title `"Fix Hash Bug`"" -ForegroundColor $ColorCyan
    Write-Host "  .\create_update_package_v2.ps1 -Version `"1.0.3`" -Title `"Security Update`" -Critical" -ForegroundColor $ColorCyan
    Write-Host ""
    exit 0
}

Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  CBT UPDATE PACKAGE CREATOR" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""

# Check if running in development environment
if (!(Test-Path ".dev_environment")) {
    Write-Host "ERROR: This script can only run in DEVELOPMENT environment!" -ForegroundColor $ColorRed
    Write-Host "" -ForegroundColor $ColorRed
    Write-Host "Missing file: .dev_environment" -ForegroundColor $ColorYellow
    Write-Host "" -ForegroundColor $ColorYellow
    Write-Host "This is a safety feature to prevent running developer tools in production." -ForegroundColor $ColorWhite
    Write-Host "If you are in localhost, create the .dev_environment file first." -ForegroundColor $ColorWhite
    Write-Host ""
    exit 1
}

Write-Host "Development environment detected" -ForegroundColor $ColorGreen
Write-Host ""

# Interactive mode if parameters not provided
if ([string]::IsNullOrWhiteSpace($Version)) {
    Write-Host "INTERACTIVE MODE" -ForegroundColor $ColorMagenta
    Write-Host ""
    $Version = Read-Host "Enter version number (e.g. 1.0.2)"
}

if ([string]::IsNullOrWhiteSpace($Title)) {
    $Title = Read-Host "Enter update title"
}

if ([string]::IsNullOrWhiteSpace($Description)) {
    $Description = Read-Host "Enter description (optional, press Enter to skip)"
}

if (!$Critical) {
    $criticalInput = Read-Host "Is this a critical update? (y/n)"
    if ($criticalInput -eq 'y') {
        $Critical = $true
    }
}

Write-Host ""

# Validate version format
$versionPattern = '^\d+\.\d+\.\d+$'
if ($Version -notmatch $versionPattern) {
    Write-Host "Invalid version format! Use: X.Y.Z (e.g. 1.0.1)" -ForegroundColor $ColorRed
    exit 1
}

Write-Host "Creating update package for version: $Version" -ForegroundColor $ColorGreen
Write-Host "Title: $Title" -ForegroundColor $ColorWhite
if ($Description) {
    Write-Host "Description: $Description" -ForegroundColor $ColorWhite
}
Write-Host "Critical: $Critical" -ForegroundColor $(if ($Critical) { $ColorRed } else { $ColorWhite })
Write-Host ""

# Create updates folder structure
$updatesRoot = "updates"
$versionFolder = "$updatesRoot/v$Version"
$filesFolder = "$versionFolder/files"

Write-Host "Creating folder structure..." -ForegroundColor $ColorCyan

if (Test-Path $versionFolder) {
    Write-Host "Version folder already exists: $versionFolder" -ForegroundColor $ColorYellow
    $response = Read-Host "Overwrite? (y/n)"
    if ($response -ne 'y') {
        Write-Host "Cancelled" -ForegroundColor $ColorRed
        exit 1
    }
    Remove-Item $versionFolder -Recurse -Force
}

New-Item -ItemType Directory -Path $updatesRoot -Force | Out-Null
New-Item -ItemType Directory -Path $versionFolder -Force | Out-Null
New-Item -ItemType Directory -Path $filesFolder -Force | Out-Null

Write-Host "Created: $versionFolder" -ForegroundColor $ColorGreen
Write-Host ""

Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  SELECT FILES TO INCLUDE" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""
Write-Host "Enter file paths (relative to project root), one per line." -ForegroundColor $ColorYellow
Write-Host "Press Enter twice when done." -ForegroundColor $ColorYellow
Write-Host ""
Write-Host "Example:" -ForegroundColor $ColorCyan
Write-Host "  app/Controllers/Activate.php" -ForegroundColor $ColorCyan
Write-Host "  app/Controllers/Admin/License.php" -ForegroundColor $ColorCyan
Write-Host "  app/Config/.lic_hash" -ForegroundColor $ColorCyan
Write-Host ""

$filesToInclude = @()
$emptyCount = 0

while ($true) {
    $file = Read-Host "File path"
    if ([string]::IsNullOrWhiteSpace($file)) {
        $emptyCount++
        if ($emptyCount -ge 2) {
            break
        }
        continue
    }
    $emptyCount = 0
    
    if (Test-Path $file) {
        $filesToInclude += $file
        Write-Host "   Added: $file" -ForegroundColor $ColorGreen
    } else {
        Write-Host "   File not found: $file" -ForegroundColor $ColorYellow
        $response = Read-Host "   Add anyway? (y/n)"
        if ($response -eq 'y') {
            $filesToInclude += $file
            Write-Host "   Added: $file (will be skipped if not exists)" -ForegroundColor $ColorGreen
        }
    }
}

if ($filesToInclude.Count -eq 0) {
    Write-Host ""
    Write-Host "No files selected!" -ForegroundColor $ColorRed
    exit 1
}

Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  COPYING FILES" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""

$copiedFiles = @()
$skippedFiles = @()

foreach ($file in $filesToInclude) {
    if (Test-Path $file) {
        $destPath = Join-Path $filesFolder $file
        $destDir = Split-Path $destPath -Parent
        
        if (!(Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        
        Copy-Item -Path $file -Destination $destPath -Force
        $copiedFiles += $file
        Write-Host "   Copied: $file" -ForegroundColor $ColorGreen
    } else {
        $skippedFiles += $file
        Write-Host "   Skipped: $file (not found)" -ForegroundColor $ColorYellow
    }
}

Write-Host ""
Write-Host "Summary:" -ForegroundColor $ColorCyan
Write-Host "   Copied: $($copiedFiles.Count) files" -ForegroundColor $ColorGreen
if ($skippedFiles.Count -gt 0) {
    Write-Host "   Skipped: $($skippedFiles.Count) files" -ForegroundColor $ColorYellow
}

# Safety check for sensitive files
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  SAFETY CHECK" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""

$sensitivePatterns = @(
    "*.env*",
    ".dev_environment",
    "*generate_license_hash.php",
    "*verify_license_hash.php",
    "*diagnostic.php",
    "*debug_*.php",
    "*test_*.php",
    "*fix_*.php",
    "*create_update_package*.ps1",
    "*create_patch.ps1"
)

$foundSensitive = $false
$sensitiveFiles = @()

foreach ($file in $copiedFiles) {
    foreach ($pattern in $sensitivePatterns) {
        if ($file -like $pattern) {
            $foundSensitive = $true
            $sensitiveFiles += $file
            break
        }
    }
}

if ($foundSensitive) {
    Write-Host "WARNING: Sensitive files detected!" -ForegroundColor $ColorRed
    Write-Host ""
    foreach ($file in $sensitiveFiles) {
        Write-Host "   $file" -ForegroundColor $ColorRed
    }
    Write-Host ""
    Write-Host "These files should NOT be in production update!" -ForegroundColor $ColorYellow
    Write-Host ""
    
    $response = Read-Host "Continue anyway? (y/n)"
    if ($response -ne 'y') {
        Write-Host "Cancelled" -ForegroundColor $ColorRed
        exit 1
    }
} else {
    Write-Host "No sensitive files detected" -ForegroundColor $ColorGreen
}

# Create CHANGELOG
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  CREATING CHANGELOG" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""

$changelogPath = "$versionFolder/CHANGELOG.md"
$filesListText = ($copiedFiles | ForEach-Object { "- $_" }) -join "`n"

$changelogContent = @"
# Changelog v$Version

## $Title

**Release Date:** $(Get-Date -Format 'yyyy-MM-dd')

### Description
$Description

### Files Changed
$filesListText

### Installation Instructions
1. Backup current files
2. Extract ZIP file
3. Copy files to application root
4. Clear cache
5. Test application

---

**Version:** $Version  
**Critical:** $Critical  
**Files:** $($copiedFiles.Count)
"@

Set-Content -Path $changelogPath -Value $changelogContent -Encoding UTF8
Write-Host "Created: CHANGELOG.md" -ForegroundColor $ColorGreen

# Create manifest.json
Write-Host ""
Write-Host "Creating manifest..." -ForegroundColor $ColorCyan

$manifestPath = "$versionFolder/manifest.json"
$manifestContent = @{
    version = $Version
    title = $Title
    description = $Description
    release_date = Get-Date -Format 'yyyy-MM-dd'
    is_critical = $Critical.IsPresent
    requires_backup = $true
    requires_migration = $false
    files = $copiedFiles
    post_install = @("clear_cache", "regenerate_hash")
} | ConvertTo-Json -Depth 10

Set-Content -Path $manifestPath -Value $manifestContent -Encoding UTF8
Write-Host "Created: manifest.json" -ForegroundColor $ColorGreen

# Create ZIP
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  CREATING ZIP ARCHIVE" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""

$zipName = "update_v$Version.zip"
$zipPath = "$updatesRoot/$zipName"

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

try {
    Compress-Archive -Path "$versionFolder/*" -DestinationPath $zipPath -Force
    Write-Host "Created: $zipName" -ForegroundColor $ColorGreen
} catch {
    Write-Host "Failed to create ZIP: $_" -ForegroundColor $ColorRed
    exit 1
}

# Calculate hash
Write-Host ""
Write-Host "Calculating file hash..." -ForegroundColor $ColorCyan
$hash = (Get-FileHash -Path $zipPath -Algorithm SHA256).Hash
Write-Host "Hash: $hash" -ForegroundColor $ColorWhite

# Get file size
$fileSize = (Get-Item $zipPath).Length
$fileSizeMB = [math]::Round($fileSize / 1MB, 2)

# Create info file
$infoPath = "$updatesRoot/update_v$Version.txt"
$infoContent = @"
===============================================================
  UPDATE PACKAGE INFO - v$Version
===============================================================

Version: $Version
Title: $Title
Description: $Description
Release Date: $(Get-Date -Format 'yyyy-MM-dd')
Critical: $Critical

File: $zipName
Size: $fileSizeMB MB ($fileSize bytes)
Hash (SHA256): $hash

Files Included: $($copiedFiles.Count)
$filesListText

===============================================================
  SQL QUERY FOR DATABASE
===============================================================

INSERT INTO updates (
    version, 
    title, 
    description,
    file_name,
    file_size,
    file_hash,
    release_date,
    is_active,
    is_critical
) VALUES (
    '$Version',
    '$Title',
    '$Description',
    '$zipName',
    $fileSize,
    '$hash',
    '$(Get-Date -Format 'yyyy-MM-dd')',
    1,
    $(if ($Critical) { 1 } else { 0 })
);

===============================================================
"@

Set-Content -Path $infoPath -Value $infoContent -Encoding UTF8

# Final summary
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  UPDATE PACKAGE CREATED SUCCESSFULLY!" -ForegroundColor $ColorGreen
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""
Write-Host "Package: $zipName" -ForegroundColor $ColorWhite
Write-Host "Size: $fileSizeMB MB" -ForegroundColor $ColorWhite
Write-Host "Files: $($copiedFiles.Count)" -ForegroundColor $ColorWhite
Write-Host "Hash: $hash" -ForegroundColor $ColorWhite
Write-Host ""
Write-Host "Location:" -ForegroundColor $ColorCyan
Write-Host "   Folder: $versionFolder" -ForegroundColor $ColorWhite
Write-Host "   ZIP: $zipPath" -ForegroundColor $ColorWhite
Write-Host "   Info: $infoPath" -ForegroundColor $ColorWhite
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  NEXT STEPS" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""
Write-Host "1. Review package contents" -ForegroundColor $ColorWhite
Write-Host "2. Upload ZIP to Update Server: writable/updates/" -ForegroundColor $ColorWhite
Write-Host "3. Register in database (see $infoPath)" -ForegroundColor $ColorWhite
Write-Host "4. Test update on staging first" -ForegroundColor $ColorWhite
Write-Host "5. Deploy to production" -ForegroundColor $ColorWhite
Write-Host ""
Write-Host "Ready to deploy!" -ForegroundColor $ColorGreen
Write-Host ""
