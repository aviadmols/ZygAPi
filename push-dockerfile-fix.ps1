# PowerShell script to push Dockerfile fix
# Run this script to update GitHub with the fixed Dockerfile

Write-Host "🔧 Pushing Dockerfile fix to GitHub..." -ForegroundColor Green

# Remove lock files if they exist
Remove-Item -Path ".git\index.lock" -ErrorAction SilentlyContinue
Remove-Item -Path ".git\HEAD.lock" -ErrorAction SilentlyContinue

# Add Dockerfile
git add Dockerfile

# Commit
git commit -m "Fix Dockerfile: Add libpq-dev for PostgreSQL extension compilation

- Added libpq-dev package required for pdo_pgsql extension
- Fixes 'Cannot find libpq-fe.h' build error"

# Push to GitHub
git push origin main

Write-Host "✅ Dockerfile fix pushed to GitHub!" -ForegroundColor Green
Write-Host "Railway will automatically rebuild with the fix." -ForegroundColor Cyan
