@echo off
echo Starting Secure VoIP Application Servers...

:: Start WebSocket server in a new window
start "WebSocket Server" cmd /k "node server.js"

:: Start HTTP server in a new window
start "HTTP Server" cmd /k "node http-server.js"

:: Wait for servers to start
echo Waiting for servers to start...
timeout /t 5 /nobreak

:: Check if servers are running
echo Checking server status...
curl -s http://localhost:3000 > nul
if %errorlevel% neq 0 (
    echo Error: Could not connect to the server.
    echo Please make sure no other application is using port 3000.
    echo Try closing any other applications and run this script again.
    pause
    exit /b 1
)

:: Open the application in default browser
echo Opening application in browser...
start http://localhost:3000

echo.
echo Servers started successfully!
echo The application should now be open in your browser.
echo Keep this window open to maintain the servers running.
echo.
echo To use the application:
echo 1. Open a second browser window to http://localhost:3000
echo 2. Click Connect in both windows
echo 3. Allow microphone access when prompted
echo.
echo Press any key to close all servers...
pause

:: Kill Node.js processes when closing
echo Closing servers...
taskkill /F /IM node.exe
echo Servers closed successfully. 