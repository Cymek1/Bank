# Run integration test for multiple COMM_MODE values.
# Usage: Open PowerShell in this folder and run: .\run_modes.ps1
$modes = @('rabbit','http','both')
$root = Split-Path -Parent $MyInvocation.MyCommand.Definition
Set-Location $root

foreach ($mode in $modes) {
  Write-Host "\n=== Testing COMM_MODE=$mode ==="
  $env:COMM_MODE = $mode
  # Recreate producer with new env
  docker compose up -d --no-deps --build --force-recreate producer
  Start-Sleep -Seconds 3
  # run the node test
  Push-Location tests
  node run_integration.js
  Pop-Location
  Write-Host "Completed test for $mode. Report at tests\report.json"
}
Write-Host "All modes tested. Remember to unset COMM_MODE or set it back to desired default."