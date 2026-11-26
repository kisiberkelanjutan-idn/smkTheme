# ==============================================================================
# Kisi Berkelanjutan (smkTheme) — Local LF Normalizer & SHA256SUMS Generator
# Jalankan setiap kali selesai mengubah file tema sebelum git commit & push:
#   powershell -ExecutionPolicy Bypass -File .\scripts\update-hashes.ps1
# ==============================================================================

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot

$textExtensions = @('.php', '.tpl', '.js', '.css', '.svg', '.xml', '.po', '.md', '.yml', '.yaml', '.sh', '.ps1', '.htaccess', '')

# 1. Normalisasi LF & hapus UTF-8 BOM pada seluruh file kerja (kecuali .git & desktop.ini)
$allRepoFiles = Get-ChildItem -Path $repoRoot -Recurse -Force -File | Where-Object {
    $_.FullName -notmatch '\\\.git\\' -and $_.Name -notin @('desktop.ini', '.DS_Store', 'Thumbs.db', 'SHA256SUMS')
}

foreach ($f in $allRepoFiles) {
    $isHtaccess = ($f.Name -eq '.htaccess')
    if ($isHtaccess -or ($f.Extension.ToLowerInvariant() -in $textExtensions)) {
        $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
        $startIdx = 0
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $startIdx = 3
        }
        $content = [System.Text.Encoding]::UTF8.GetString($bytes, $startIdx, $bytes.Length - $startIdx)
        $lfContent = $content -replace "`r`n", "`n" -replace "`r", "`n"
        $outBytes = (New-Object System.Text.UTF8Encoding($false)).GetBytes($lfContent)
        if ($startIdx -gt 0 -or $content -cne $lfContent) {
            [System.IO.File]::WriteAllBytes($f.FullName, $outBytes)
            Write-Host "[Normalized LF] $($f.FullName.Substring($repoRoot.Length + 1))"
        }
    }
}

# 2. Kumpulkan daftar file yang di-deploy ke public_html
$deployRelPaths = [System.Collections.Generic.List[string]]::new()
foreach ($rootFile in @('index.php', 'SmkThemePlugin.php', 'version.xml')) {
    $full = Join-Path $repoRoot $rootFile
    if (-not (Test-Path $full)) {
        throw "File wajib tidak ditemukan: $rootFile"
    }
    $deployRelPaths.Add($rootFile)
}

foreach ($dirName in @('assets', 'js', 'locale', 'styles', 'templates')) {
    $subDir = Join-Path $repoRoot $dirName
    Get-ChildItem -Path $subDir -Recurse -Force -File | Where-Object {
        $_.Name -notin @('desktop.ini', '.DS_Store', 'Thumbs.db')
    } | ForEach-Object {
        $rel = $_.FullName.Substring($repoRoot.Length + 1).Replace('\', '/')
        $deployRelPaths.Add($rel)
    }
}

# Urutkan secara byte-ordinal (identik dengan LC_ALL=C sort di Linux)
$sortedPaths = @($deployRelPaths)
[System.Array]::Sort($sortedPaths, [System.StringComparer]::Ordinal)

# 3. Hitung SHA-256 dan tulis SHA256SUMS dalam format standar GNU sha256sum (LF)
$sha256 = [System.Security.Cryptography.SHA256]::Create()
$lines = foreach ($rel in $sortedPaths) {
    $full = Join-Path $repoRoot ($rel.Replace('/', '\'))
    $fileBytes = [System.IO.File]::ReadAllBytes($full)
    $hashHex = ([System.BitConverter]::ToString($sha256.ComputeHash($fileBytes))).Replace('-', '').ToLowerInvariant()
    "$hashHex  $rel"
}
$sha256.Dispose()

$manifestPath = Join-Path $repoRoot 'SHA256SUMS'
$manifestText = ($lines -join "`n") + "`n"
[System.IO.File]::WriteAllBytes($manifestPath, (New-Object System.Text.UTF8Encoding($false)).GetBytes($manifestText))

Write-Host "[OK] SHA256SUMS berhasil diperbarui untuk $($sortedPaths.Count) file deploy."
