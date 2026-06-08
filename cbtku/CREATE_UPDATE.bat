@echo off
REM ============================================
REM CBT Update Package Creator - ONE CLICK
REM ============================================

echo.
echo ===============================================================
echo   CBT UPDATE PACKAGE CREATOR - ONE CLICK
echo ===============================================================
echo.

REM Check .dev_environment
if not exist ".dev_environment" (
    echo ERROR: Not in development environment!
    echo Create .dev_environment file first.
    echo.
    pause
    exit /b 1
)

echo Development environment detected
echo.

REM Run PowerShell script
PowerShell -ExecutionPolicy Bypass -File .\create_update_simple.ps1

echo.
pause
