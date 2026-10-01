@echo off
setlocal enabledelayedexpansion
title BSLA / SIA High School - Database Setup
color 0A

echo ======================================================================
echo    Biringan Science and Leadership Academy (BSLA)
echo    SIA High School Enrollment & Academic Management Portal
echo    Automated Database Importer
echo ======================================================================
echo.

set MYSQL="C:\xampp\mysql\bin\mysql.exe"
if not exist %MYSQL% (
    set MYSQL="mysql"
)

echo [1/3] Sinasuri ang MySQL sa XAMPP...
%MYSQL% -u root -e "SELECT 1;" >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    color 0C
    echo.
    echo [ERROR] Hindi ma-connect sa MySQL sa XAMPP!
    echo Siguraduhing naka-START ang Apache at MySQL sa iyong XAMPP Control Panel.
    echo.
    pause
    exit /b 1
)
echo [OK] MySQL is active and running!
echo.

echo [2/3] Ini-import ang database (104 DepEd subjects, 33 sections, 1,680 schedules)...
set SQL_FILE="%~dp0sia_highschool_complete_database.sql"

if not exist %SQL_FILE% (
    color 0C
    echo.
    echo [ERROR] Hindi mahanap ang file: sia_highschool_complete_database.sql
    echo.
    pause
    exit /b 1
)

%MYSQL% -u root --default-character-set=utf8mb4 < %SQL_FILE%
if %ERRORLEVEL% NEQ 0 (
    color 0C
    echo.
    echo [ERROR] May naging problema habang nag-i-import.
    echo Maaari ring i-import mano-mano sa phpMyAdmin: http://localhost/phpmyadmin/
    echo.
    pause
    exit /b 1
)
echo [OK] Database `sia_highschool_db` successfully imported!
echo.

echo [3/3] Ready to use!
echo ======================================================================
echo   [SUCCESS] Naka-setup na ang buong system para sa iyong laptop!
echo.
echo   Buksan ang link na ito sa iyong browser (Google Chrome / Edge):
echo   👉 http://localhost/sia-project2/
echo ======================================================================
echo.
pause