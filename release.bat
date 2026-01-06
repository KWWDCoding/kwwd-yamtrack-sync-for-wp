@echo off
SETLOCAL EnableDelayedExpansion

SET PLUGIN_NAME=kwwd-yamtrack-sync-for-wp
SET PLUGIN_DIR=%~dp0
SET DOWNLOADS_DIR=%USERPROFILE%\Downloads
SET VERSION_FILE=%PLUGIN_DIR%versions\versions.txt

:: 1. Read the current version
if not exist "%VERSION_FILE%" (
    echo 1.49 > "%VERSION_FILE%"
)
set /p OLD_VERSION=<%VERSION_FILE%

:: 2. Increment by 0.01 and force 2 decimal places (X.XX)
for /f "delims=" %%v in ('powershell -Command "('{0:F2}' -f ([double]%OLD_VERSION% + 0.01))"') do set VERSION=%%v

echo Incrementing version: %OLD_VERSION% --^> %VERSION%

:: 3. Update versions.txt
echo %VERSION% > "%VERSION_FILE%"

:: 4. Auto-Update the Version in the main PHP file
powershell -Command "(Get-Content '%PLUGIN_DIR%%PLUGIN_NAME%.php') -replace 'Version:\s*\d+\.\d+', 'Version: %VERSION%' | Set-Content '%PLUGIN_DIR%%PLUGIN_NAME%.php'"

:: 5. Create the Update JSON (Cleaned of 'v')
if not exist "%PLUGIN_DIR%versions" mkdir "%PLUGIN_DIR%versions"
echo { "version": "%VERSION%", "url": "https://github.com/KWWDCoding/%PLUGIN_NAME%", "package": "https://github.com/KWWDCoding/%PLUGIN_NAME%/releases/download/%VERSION%/%PLUGIN_NAME%.zip" } > "%PLUGIN_DIR%versions\update-check.json"

:: 6. Create the Clean Plugin ZIP
echo Creating Clean Plugin ZIP...
powershell -Command "$tmp='temp_dist'; if (Test-Path $tmp) { rm -r $tmp }; mkdir $tmp; cp '%PLUGIN_DIR%%PLUGIN_NAME%.php' $tmp; cp -r '%PLUGIN_DIR%includes' $tmp; if (Test-Path '%PLUGIN_DIR%README.md') { cp '%PLUGIN_DIR%README.md' $tmp }; if (Test-Path '%PLUGIN_DIR%LICENSE') { cp '%PLUGIN_DIR%LICENSE' $tmp }; Compress-Archive -Path $tmp\* -DestinationPath '%DOWNLOADS_DIR%\%PLUGIN_NAME%.zip' -Force; rm -r $tmp"

:: 7. Git Operations
echo Committing version %VERSION% to GitHub...
git add .
git commit -m "Release %VERSION%"
git push origin main

:: 8. Tagging (Removed 'v' prefix)
echo Tagging version %VERSION%...
git tag -a %VERSION% -m "Version %VERSION%"
git push origin %VERSION%

SET REPO_URL=https://github.com/KWWDCoding/%PLUGIN_NAME%
SET ZIP_DEST=%DOWNLOADS_DIR%\%PLUGIN_NAME%.zip

echo -----------------------------------------------------------
echo SUCCESS! Version is now %VERSION%
echo -----------------------------------------------------------
start %REPO_URL%/releases/new?tag=%VERSION%
explorer /select,"%ZIP_DEST%"
pause