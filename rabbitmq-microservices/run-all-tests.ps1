#!/usr/bin/env pwsh
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host "Run-all-tests: starting full integration run..."

Push-Location (Split-Path -Path $MyInvocation.MyCommand.Path -Parent)

# 1) Build and start docker-compose stack
Write-Host "Bringing up docker-compose stack..."
# remove any stray php container that may conflict with compose-managed service
$existing = docker ps -a --filter "name=bank_php" --format "{{.ID}}"
if ($existing) {
    Write-Host "Removing existing container 'bank_php' to avoid name conflicts..."
    docker rm -f bank_php | Out-Null
}

docker-compose up -d --build

# 2) Wait for MySQL to become available
Write-Host "Waiting for MySQL container to be ready..."
$max = 60
for ($i = 0; $i -lt $max; $i++) {
    try {
        docker exec bank_mysql mysqladmin ping -u bank -pbankpass | Out-Null
        Write-Host "MySQL is up"
        break
    } catch {
        Start-Sleep -Seconds 2
    }
    if ($i -eq $max-1) { throw "MySQL did not become ready in time" }
}

# 3) Ensure minimal DB tables for PHP tests
Write-Host "Ensuring minimal DB schema exists (users table)..."
docker exec -i bank_mysql mysql -u bank -pbankpass bank_db -e "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, nazwa_uzytkownika VARCHAR(255), email VARCHAR(255), haslo VARCHAR(255), stan_konta DECIMAL(15,2) DEFAULT 0, nr_konta VARCHAR(16));"

Write-Host "Ensuring accounts, event_queue and dead_letter tables exist..."
docker exec -i bank_mysql mysql -u bank -pbankpass bank_db -e "CREATE TABLE IF NOT EXISTS accounts (id VARCHAR(128) PRIMARY KEY, balance DECIMAL(15,2) DEFAULT 0, metadata JSON DEFAULT (JSON_OBJECT()), updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);"
docker exec -i bank_mysql mysql -u bank -pbankpass bank_db -e "CREATE TABLE IF NOT EXISTS event_queue (id INT AUTO_INCREMENT PRIMARY KEY, payload JSON NOT NULL, attempts INT DEFAULT 0, last_error TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);"
docker exec -i bank_mysql mysql -u bank -pbankpass bank_db -e "CREATE TABLE IF NOT EXISTS dead_letter (id INT AUTO_INCREMENT PRIMARY KEY, payload JSON NOT NULL, attempts INT DEFAULT 0, error TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);"


# 4) Wait for producer and consumer HTTP endpoints to be ready
function Wait-ForHttp([string]$url, [int]$maxSeconds = 60) {
    Write-Host "Waiting for HTTP $url"
    $end = (Get-Date).AddSeconds($maxSeconds)
    while ((Get-Date) -lt $end) {
        try {
            $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 3 -ErrorAction Stop
            if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 500) { return $true }
        } catch {
            Start-Sleep -Seconds 1
        }
    }
    return $false
}

if (-not (Wait-ForHttp -url 'http://localhost:4000/health' -maxSeconds 60)) { throw "Producer HTTP not ready" }
if (-not (Wait-ForHttp -url 'http://localhost:4001/health' -maxSeconds 60)) { throw "Consumer HTTP not ready" }
if (-not (Wait-ForHttp -url 'http://localhost:8000/' -maxSeconds 30)) { Write-Host "Warning: PHP endpoint not responding at root; continuing" }

# 5) Run microservice-only integration test
Write-Host "Running microservice-only integration test..."
Push-Location tests
if (-not (Test-Path node_modules)) { npm install --no-audit --no-fund | Out-Null }
node run_integration.js
$microExit = $LASTEXITCODE
Pop-Location

# 6) Run PHP integration test
Write-Host "Running PHP end-to-end integration test..."
$env:PHP_API = 'http://localhost:8000'
Push-Location tests
node php_integration.js
$phpExit = $LASTEXITCODE
Pop-Location

# 7) Run negative tests
Write-Host "Running negative tests..."
Push-Location tests
node negative_invalid_payload.js
$negExit1 = $LASTEXITCODE
Pop-Location

# 8) Run failure-mode tests (rabbit/mysql outage)
Write-Host "Running failure-mode tests (rabbit outage)..."
Push-Location tests
node failure_rabbit_outage.js
$failRabbit = $LASTEXITCODE
Pop-Location

Write-Host "Running failure-mode tests (mysql outage)..."
Push-Location tests
node failure_mysql_outage.js
$failMysql = $LASTEXITCODE
Pop-Location

Write-Host "micro test exit code: $microExit, php test exit code: $phpExit, negative tests exit code: $negExit1"

Write-Host "micro test exit code: $microExit, php test exit code: $phpExit"

if ($microExit -eq 0 -and $phpExit -eq 0 -and $negExit1 -eq 0 -and $failRabbit -eq 0 -and $failMysql -eq 0) {
    Write-Host "All tests passed"
    # Run report validator
    Write-Host "Validating JSON reports..."
    Push-Location tests
    if (-not (Test-Path node_modules)) { npm install --no-audit --no-fund | Out-Null }
    node validate_reports.js
    $valExit = $LASTEXITCODE
    Pop-Location
    if ($valExit -eq 0) { Write-Host "All reports valid"; Pop-Location; exit 0 } else { Write-Host "Report validation failed"; Pop-Location; exit 2 }
} else {
    Write-Host "One or more tests failed. See tests/report.json and tests/php_integration_report.json"
    Pop-Location
    exit 1
}
