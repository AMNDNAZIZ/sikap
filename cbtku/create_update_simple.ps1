# ============================================
# CBT Update Package Creator - SIMPLE VERSION
# Auto-detect changes from git
# ============================================
# 
# USAGE: Just run this script!
# .\create_update_simple.ps1
# 
# ============================================

param(
    [switch]$Help = $false
)

# Colors
$ColorCyan = "Cyan"
$ColorGreen = "Green"
$ColorYellow = "Yellow"
$ColorRed = "Red"
$ColorWhite = "White"

# Show help
if ($Help) {
    Write-Host "===============================================================" -ForegroundColor $ColorCyan
    Write-Host "  CBT UPDATE PACKAGE CREATOR - SIMPLE VERSION" -ForegroundColor $ColorCyan
    Write-Host "===============================================================" -ForegroundColor $ColorCyan
    Write-Host ""
    Write-Host "USAGE:" -ForegroundColor $ColorWhite
    Write-Host "  .\create_update_simple.ps1" -ForegroundColor $ColorWhite
    Write-Host ""
    Write-Host "FEATURES:" -ForegroundColor $ColorWhite
    Write-Host "  - Auto-detect version from Constants.php" -ForegroundColor $ColorWhite
    Write-Host "  - Auto-detect changed files from git" -ForegroundColor $ColorWhite
    Write-Host "  - Auto-regenerate license hash if needed" -ForegroundColor $ColorWhite
    Write-Host "  - One-click package creation" -ForegroundColor $ColorWhite
    Write-Host ""
    Write-Host "REQUIREMENTS:" -ForegroundColor $ColorWhite
    Write-Host "  - Git installed" -ForegroundColor $ColorWhite
    Write-Host "  - .dev_environment file exists" -ForegroundColor $ColorWhite
    Write-Host "  - Changes committed to git" -ForegroundColor $ColorWhite
    Write-Host ""
    exit 0
}

Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  CBT UPDATE PACKAGE CREATOR - SIMPLE VERSION" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""

# Check environment
if (!(Test-Path ".dev_environment")) {
    Write-Host "ERROR: Not in development environment!" -ForegroundColor $ColorRed
    Write-Host "Create .dev_environment file first." -ForegroundColor $ColorYellow
    exit 1
}

Write-Host "Development environment detected" -ForegroundColor $ColorGreen
Write-Host ""

# Check git
try {
    $gitCheck = git --version 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Git not found"
    }
} catch {
    Write-Host "ERROR: Git not installed or not in PATH!" -ForegroundColor $ColorRed
    Write-Host "Install git first: https://git-scm.com/download/win" -ForegroundColor $ColorYellow
    exit 1
}

Write-Host "Git detected: $gitCheck" -ForegroundColor $ColorGreen
Write-Host ""

# Auto-detect version from Constants.php
Write-Host "Auto-detecting version..." -ForegroundColor $ColorCyan
$constantsPath = "app/Config/Constants.php"

if (!(Test-Path $constantsPath)) {
    Write-Host "ERROR: Constants.php not found!" -ForegroundColor $ColorRed
    exit 1
}

$constantsContent = Get-Content $constantsPath -Raw
if ($constantsContent -match "define\('APP_VERSION',\s*'([^']+)'\)") {
    $currentVersion = $matches[1]
    Write-Host "Current version: $currentVersion" -ForegroundColor $ColorWhite
    
    # Increment patch version
    if ($currentVersion -match '^(\d+)\.(\d+)\.(\d+)$') {
        $major = [int]$matches[1]
        $minor = [int]$matches[2]
        $patch = [int]$matches[3]
        $newPatch = $patch + 1
        $newVersion = "$major.$minor.$newPatch"
        Write-Host "New version: $newVersion" -ForegroundColor $ColorGreen
    } else {
        Write-Host "ERROR: Invalid version format in Constants.php" -ForegroundColor $ColorRed
        exit 1
    }
} else {
    Write-Host "ERROR: APP_VERSION not found in Constants.php" -ForegroundColor $ColorRed
    exit 1
}

Write-Host ""

# Get changed files from git
Write-Host "Detecting changed files from git..." -ForegroundColor $ColorCyan

try {
    # Get modified and new files
    $gitStatus = git status --porcelain 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        throw "Git status failed"
    }
    
    $changedFiles = @()
    
    if ($gitStatus) {
        $gitStatus | ForEach-Object {
            $line = $_.Trim()
            if ($line -match '^[MAD\?]{1,2}\s+(.+)$') {
                $file = $matches[1].Trim()
                
                # Only include app/, public/, modules/ files
                if ($file -match '^(app|public|modules)/') {
                    # Exclude sensitive files
                    if ($file -notmatch '\.(env|md|txt)$' -and 
                        $file -notmatch 'test_' -and 
                        $file -notmatch 'debug_' -and
                        $file -notmatch 'diagnostic' -and
                        $file -notmatch 'generate_license_hash' -and
                        $file -notmatch 'verify_license_hash') {
                        $changedFiles += $file
                    }
                }
            }
        }
    }
    
    if ($changedFiles.Count -eq 0) {
        Write-Host "No changed files detected in app/, public/, or modules/" -ForegroundColor $ColorYellow
        Write-Host ""
        Write-Host "Make sure you have:" -ForegroundColor $ColorWhite
        Write-Host "1. Made changes to files" -ForegroundColor $ColorWhite
        Write-Host "2. Saved the files" -ForegroundColor $ColorWhite
        Write-Host "3. Files are in app/, public/, or modules/ folders" -ForegroundColor $ColorWhite
        Write-Host ""
        Write-Host "Or use manual mode: .\create_update_package_v2.ps1" -ForegroundColor $ColorCyan
        exit 1
    }
    
    Write-Host "Found $($changedFiles.Count) changed files:" -ForegroundColor $ColorGreen
    foreach ($file in $changedFiles) {
        Write-Host "   $file" -ForegroundColor $ColorWhite
    }
    
} catch {
    Write-Host "ERROR: Failed to get git status: $_" -ForegroundColor $ColorRed
    exit 1
}

Write-Host ""

# Check if license files changed
$licenseFilesChanged = $false
$licenseFiles = @(
    'app/Filters/LicenseFilter.php',
    'app/Controllers/Activate.php',
    'app/Controllers/Admin/License.php',
    'app/Libraries/LicenseGuard.php',
    'app/Models/LicenseModel.php',
    'app/Config/LService.php'
)

foreach ($file in $changedFiles) {
    if ($licenseFiles -contains $file) {
        $licenseFilesChanged = $true
        break
    }
}

# Auto-regenerate license hash if needed
if ($licenseFilesChanged) {
    Write-Host "License-protected files changed. Regenerating hash..." -ForegroundColor $ColorYellow
    
    try {
        $hashOutput = php generate_license_hash.php 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "License hash regenerated successfully" -ForegroundColor $ColorGreen
            
            # Add .lic_hash to changed files if not already there
            if ($changedFiles -notcontains 'app/Config/.lic_hash') {
                $changedFiles += 'app/Config/.lic_hash'
                Write-Host "Added app/Config/.lic_hash to package" -ForegroundColor $ColorGreen
            }
        } else {
            Write-Host "WARNING: Failed to regenerate license hash" -ForegroundColor $ColorYellow
            Write-Host "You may need to run: php generate_license_hash.php" -ForegroundColor $ColorYellow
        }
    } catch {
        Write-Host "WARNING: Could not regenerate license hash: $_" -ForegroundColor $ColorYellow
    }
    
    Write-Host ""
}

# Ask for update title
Write-Host "Enter update title:" -ForegroundColor $ColorCyan
$title = Read-Host "Title"

if ([string]::IsNullOrWhiteSpace($title)) {
    $title = "Update v$newVersion"
}

Write-Host ""
Write-Host "Enter description (optional, press Enter to skip):" -ForegroundColor $ColorCyan
$description = Read-Host "Description"

Write-Host ""
Write-Host "Is this a critical update? (y/n):" -ForegroundColor $ColorCyan
$criticalInput = Read-Host "Critical"
$isCritical = $criticalInput -eq 'y'

Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  CREATING UPDATE PACKAGE" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""
Write-Host "Version: $newVersion" -ForegroundColor $ColorWhite
Write-Host "Title: $title" -ForegroundColor $ColorWhite
Write-Host "Files: $($changedFiles.Count)" -ForegroundColor $ColorWhite
Write-Host "Critical: $isCritical" -ForegroundColor $ColorWhite
Write-Host ""

$response = Read-Host "Continue? (y/n)"
if ($response -ne 'y') {
    Write-Host "Cancelled" -ForegroundColor $ColorRed
    exit 1
}

Write-Host ""

# Create folder structure
$updatesRoot = "updates"
$versionFolder = "$updatesRoot/v$newVersion"
$filesFolder = "$versionFolder/files"

if (Test-Path $versionFolder) {
    Remove-Item $versionFolder -Recurse -Force
}

New-Item -ItemType Directory -Path $updatesRoot -Force | Out-Null
New-Item -ItemType Directory -Path $versionFolder -Force | Out-Null
New-Item -ItemType Directory -Path $filesFolder -Force | Out-Null

Write-Host "Created folder: $versionFolder" -ForegroundColor $ColorGreen
Write-Host ""

# Copy files
Write-Host "Copying files..." -ForegroundColor $ColorCyan
$copiedFiles = @()

foreach ($file in $changedFiles) {
    if (Test-Path $file) {
        $destPath = Join-Path $filesFolder $file
        $destDir = Split-Path $destPath -Parent
        
        if (!(Test-Path $destDir)) {
            New-Item -ItemType Directory -Path $destDir -Force | Out-Null
        }
        
        Copy-Item -Path $file -Destination $destPath -Force
        $copiedFiles += $file
        Write-Host "   Copied: $file" -ForegroundColor $ColorGreen
    }
}

Write-Host ""
Write-Host "Copied $($copiedFiles.Count) files" -ForegroundColor $ColorGreen
Write-Host ""

# Create CHANGELOG
Write-Host "Creating CHANGELOG..." -ForegroundColor $ColorCyan
$changelogPath = "$versionFolder/CHANGELOG.md"
$filesListText = ($copiedFiles | ForEach-Object { "- $_" }) -join "`n"

$changelogContent = @"
# Changelog v$newVersion

## $title

**Release Date:** $(Get-Date -Format 'yyyy-MM-dd')

### Description
$description

### Files Changed
$filesListText

### Installation Instructions
1. Backup current files
2. Extract ZIP file
3. Copy files to application root
4. Clear cache
5. Test application

---

**Version:** $newVersion  
**Critical:** $isCritical  
**Files:** $($copiedFiles.Count)
"@

Set-Content -Path $changelogPath -Value $changelogContent -Encoding UTF8
Write-Host "Created: CHANGELOG.md" -ForegroundColor $ColorGreen

# Create manifest
Write-Host "Creating manifest..." -ForegroundColor $ColorCyan
$manifestPath = "$versionFolder/manifest.json"
$manifestContent = @{
    version = $newVersion
    title = $title
    description = $description
    release_date = Get-Date -Format 'yyyy-MM-dd'
    is_critical = $isCritical
    requires_backup = $true
    requires_migration = $false
    files = $copiedFiles
    post_install = @("clear_cache", "regenerate_hash")
} | ConvertTo-Json -Depth 10

Set-Content -Path $manifestPath -Value $manifestContent -Encoding UTF8
Write-Host "Created: manifest.json" -ForegroundColor $ColorGreen

# Create ZIP
Write-Host ""
Write-Host "Creating ZIP..." -ForegroundColor $ColorCyan
$zipName = "update_v$newVersion.zip"
$zipPath = "$updatesRoot/$zipName"

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

try {
    Compress-Archive -Path "$versionFolder/*" -DestinationPath $zipPath -Force
    Write-Host "Created: $zipName" -ForegroundColor $ColorGreen
} catch {
    Write-Host "ERROR: Failed to create ZIP: $_" -ForegroundColor $ColorRed
    exit 1
}

# Calculate hash
Write-Host ""
Write-Host "Calculating hash..." -ForegroundColor $ColorCyan
$hash = (Get-FileHash -Path $zipPath -Algorithm SHA256).Hash
$fileSize = (Get-Item $zipPath).Length
$fileSizeMB = [math]::Round($fileSize / 1MB, 2)

Write-Host "Hash: $hash" -ForegroundColor $ColorWhite
Write-Host "Size: $fileSizeMB MB" -ForegroundColor $ColorWhite

# Create info file
$infoPath = "$updatesRoot/update_v$newVersion.txt"
$infoContent = @"
===============================================================
  UPDATE PACKAGE INFO - v$newVersion
===============================================================

Version: $newVersion
Title: $title
Description: $description
Release Date: $(Get-Date -Format 'yyyy-MM-dd')
Critical: $isCritical

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
    '$newVersion',
    '$title',
    '$description',
    '$zipName',
    $fileSize,
    '$hash',
    '$(Get-Date -Format 'yyyy-MM-dd')',
    1,
    $(if ($isCritical) { 1 } else { 0 })
);

===============================================================
  NEXT STEPS
===============================================================

1. Upload ZIP to Update Server:
   - Location: writable/updates/$zipName

2. Register in database:
   - Run SQL query above in phpMyAdmin

3. Test update:
   - Login as admin
   - Menu: Admin -> Updater
   - Click: "Cek Update Online"
   - Click: "Download & Install"

4. Monitor:
   - Check error logs
   - Verify all features work

===============================================================
"@

Set-Content -Path $infoPath -Value $infoContent -Encoding UTF8

# Update version in Constants.php
Write-Host ""
Write-Host "Updating version in Constants.php..." -ForegroundColor $ColorCyan

$newConstantsContent = $constantsContent -replace "define\('APP_VERSION',\s*'[^']+'\)", "define('APP_VERSION', '$newVersion')"
$newConstantsContent = $newConstantsContent -replace "define\('LAST_UPDATE',\s*'[^']*'\)", "define('LAST_UPDATE', '$(Get-Date -Format 'yyyy-MM-dd HH:mm')')"

Set-Content -Path $constantsPath -Value $newConstantsContent -Encoding UTF8
Write-Host "Updated APP_VERSION to $newVersion" -ForegroundColor $ColorGreen

# Final summary
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  UPDATE PACKAGE CREATED SUCCESSFULLY!" -ForegroundColor $ColorGreen
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""
Write-Host "Package: $zipName" -ForegroundColor $ColorWhite
Write-Host "Version: $newVersion" -ForegroundColor $ColorWhite
Write-Host "Size: $fileSizeMB MB" -ForegroundColor $ColorWhite
Write-Host "Files: $($copiedFiles.Count)" -ForegroundColor $ColorWhite
Write-Host ""
Write-Host "Location:" -ForegroundColor $ColorCyan
Write-Host "   ZIP: $zipPath" -ForegroundColor $ColorWhite
Write-Host "   Info: $infoPath" -ForegroundColor $ColorWhite
Write-Host ""
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host "  NEXT STEPS" -ForegroundColor $ColorCyan
Write-Host "===============================================================" -ForegroundColor $ColorCyan
Write-Host ""
Write-Host "1. Review: $infoPath" -ForegroundColor $ColorWhite
Write-Host "2. Upload: $zipName to Update Server" -ForegroundColor $ColorWhite
Write-Host "3. Register: Run SQL query in phpMyAdmin" -ForegroundColor $ColorWhite
Write-Host "4. Test: Update on staging first" -ForegroundColor $ColorWhite
Write-Host ""
Write-Host "Ready to deploy!" -ForegroundColor $ColorGreen
Write-Host ""
