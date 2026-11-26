#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPO_ROOT=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
MANIFEST="$REPO_ROOT/SHA256SUMS"

abort_deploy() {
    printf '[SECURITY ABORT] %s\n' "$1" >&2
    exit 1
}

verify_htaccess_set() {
    base_dir="$1"
    for ht in \
        ".htaccess" \
        "assets/.htaccess" \
        "js/.htaccess" \
        "locale/.htaccess" \
        "styles/.htaccess" \
        "templates/.htaccess"
    do
        if [ ! -f "$base_dir/$ht" ]; then
            abort_deploy "File proteksi wajib hilang: $base_dir/$ht"
        fi
        if ! grep -q "Require all denied" "$base_dir/$ht"; then
            abort_deploy "Aturan 'Require all denied' hilang pada: $base_dir/$ht"
        fi
    done
}

verify_manifest_and_hashes() {
    check_dir="$1"
    label="$2"

    if [ ! -f "$MANIFEST" ] || [ ! -s "$MANIFEST" ]; then
        abort_deploy "Manifest integritas SHA256SUMS tidak ditemukan atau kosong."
    fi

    expected_files=$(awk '{print $2}' "$MANIFEST" | LC_ALL=C sort)
    actual_files=$(
        {
            for rf in .htaccess index.php README.md SmkThemePlugin.php version.xml; do
                if [ -f "$check_dir/$rf" ]; then
                    printf '%s\n' "$rf"
                fi
            done
            (
                cd "$check_dir" && find assets js locale styles templates -type f \
                    ! -name 'desktop.ini' ! -name '.DS_Store' ! -name 'Thumbs.db'
            )
        } | sed 's#^\./##' | LC_ALL=C sort
    )

    if [ "$expected_files" != "$actual_files" ]; then
        printf '[SECURITY ABORT] Daftar file pada %s tidak identik dengan SHA256SUMS!\n' "$label" >&2
        printf '--- Expected count: %s | Actual count: %s ---\n' \
            "$(printf '%s\n' "$expected_files" | wc -l | tr -d ' ')" \
            "$(printf '%s\n' "$actual_files" | wc -l | tr -d ' ')" >&2
        exit 1
    fi

    if command -v sha256sum >/dev/null 2>&1; then
        if ! (cd "$check_dir" && sha256sum -c "$MANIFEST" >/dev/null 2>&1); then
            printf '[SECURITY ABORT] Verifikasi checksum SHA-256 GAGAL pada %s! Detail:\n' "$label" >&2
            (cd "$check_dir" && sha256sum -c "$MANIFEST" || true) >&2
            exit 1
        fi
    elif command -v shasum >/dev/null 2>&1; then
        if ! (cd "$check_dir" && shasum -a 256 -c "$MANIFEST" >/dev/null 2>&1); then
            printf '[SECURITY ABORT] Verifikasi checksum SHA-256 (shasum) GAGAL pada %s!\n' "$label" >&2
            (cd "$check_dir" && shasum -a 256 -c "$MANIFEST" || true) >&2
            exit 1
        fi
    elif command -v openssl >/dev/null 2>&1; then
        while IFS= read -r line || [ -n "$line" ]; do
            [ -z "$line" ] && continue
            exp_hash=$(printf '%s' "$line" | awk '{print $1}')
            rel_file=$(printf '%s' "$line" | sed 's/^[0-9a-fA-F]*  //')
            target_path="$check_dir/$rel_file"
            if [ ! -f "$target_path" ]; then
                abort_deploy "File pada SHA256SUMS tidak ditemukan: $target_path"
            fi
            act_hash=$(openssl dgst -sha256 "$target_path" | awk '{print $NF}' | tr 'A-F' 'a-f')
            if [ "$exp_hash" != "$act_hash" ]; then
                abort_deploy "Checksum SHA-256 tidak cocok pada $rel_file (expected $exp_hash, got $act_hash)"
            fi
        done < "$MANIFEST"
    elif command -v php >/dev/null 2>&1; then
        if ! php -r '
            $manifest = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $dir = rtrim($argv[2], "/\\");
            foreach ($manifest as $line) {
                if (!preg_match("/^([0-9a-f]{64})\s\s(.+)$/i", $line, $m)) continue;
                $path = $dir . DIRECTORY_SEPARATOR . $m[2];
                if (!is_file($path) || strtolower(hash_file("sha256", $path)) !== strtolower($m[1])) {
                    fwrite(STDERR, "SHA-256 mismatch: {$m[2]}\n");
                    exit(1);
                }
            }
        ' "$MANIFEST" "$check_dir"; then
            abort_deploy "Verifikasi checksum SHA-256 (PHP) GAGAL pada $label!"
        fi
    elif command -v powershell.exe >/dev/null 2>&1; then
        m_win="$MANIFEST"
        c_win="$check_dir"
        if command -v cygpath >/dev/null 2>&1; then
            m_win=$(cygpath -w "$MANIFEST")
            c_win=$(cygpath -w "$check_dir")
        fi
        if ! env KZ_MANIFEST="$m_win" KZ_CHECKDIR="$c_win" powershell.exe -NoProfile -Command '
            $ErrorActionPreference = "Stop"
            $sha = [System.Security.Cryptography.SHA256]::Create()
            foreach ($line in [System.IO.File]::ReadAllLines($env:KZ_MANIFEST)) {
                if ([string]::IsNullOrWhiteSpace($line)) { continue }
                $exp = $line.Substring(0, 64).ToLowerInvariant()
                $rel = $line.Substring(66).Replace("/", "\")
                $fp  = Join-Path $env:KZ_CHECKDIR $rel
                if (-not (Test-Path -LiteralPath $fp)) { throw "Missing: $rel" }
                $act = ([System.BitConverter]::ToString($sha.ComputeHash([System.IO.File]::ReadAllBytes($fp)))).Replace("-", "").ToLowerInvariant()
                if ($exp -ne $act) { throw "SHA-256 mismatch: $rel" }
            }
        ' >/dev/null 2>&1; then
            abort_deploy "Verifikasi checksum SHA-256 GAGAL pada $label!"
        fi
    else
        abort_deploy "Tidak ditemukan utilitas verifikasi SHA-256 (sha256sum/shasum/openssl/php)."
    fi
}

if [ "${1:-}" = "--verify-target" ]; then
    TARGET_DIR="${2:-}"
    if [ -z "$TARGET_DIR" ] || [ ! -d "$TARGET_DIR" ]; then
        abort_deploy "Direktori target deploy tidak ditemukan: $TARGET_DIR"
    fi
    verify_htaccess_set "$TARGET_DIR"
    verify_manifest_and_hashes "$TARGET_DIR" "target public_html ($TARGET_DIR)"
    printf '[OK] Integritas pasca-deploy di %s terverifikasi 100%% cocok dengan SHA256SUMS.\n' "$TARGET_DIR"
    exit 0
fi

cd "$REPO_ROOT"

verify_htaccess_set "$REPO_ROOT"

unexpected_scripts=$(
    find . -path ./.git -prune -o -type f \( \
        -iname '*.php' -o -iname '*.php[0-9]*' -o -iname '*.phtml' -o -iname '*.phar' \
        -o -iname '*.inc' -o -iname '*.pl' -o -iname '*.py' -o -iname '*.cgi' \
        -o -iname '*.exe' -o -iname '*.dll' -o -iname '*.so' -o -iname '*.sh' -o -iname '*.ps1' \
    \) -print | sed 's#^\./##' | grep -vxE 'index\.php|SmkThemePlugin\.php|scripts/verify-deploy\.sh|scripts/update-hashes\.ps1' || true
)
if [ -n "$unexpected_scripts" ]; then
    abort_deploy "Ditemukan file skrip/eksekusi di luar whitelist resmi: $unexpected_scripts"
fi

bad_php=$(
    grep -nE '\b(eval|base64_decode|gzinflate|gzuncompress|gzdecode|str_rot13|shell_exec|passthru|proc_open|popen|system|exec|pcntl_exec|assert|create_function|move_uploaded_file|curl_exec|file_put_contents)\s*\(' \
        index.php SmkThemePlugin.php || true
)
if [ -n "$bad_php" ]; then
    abort_deploy "Terdeteksi fungsi PHP terlarang/berbahaya: $bad_php"
fi

stray_php_tags=$(
    grep -rnE '<\?(php|=)|<script[^>]+language=["'\'']?php|\{php\}|\{include_php\}' \
        assets js locale styles templates || true
)
if [ -n "$stray_php_tags" ]; then
    abort_deploy "Terdeteksi tag kode PHP liar di dalam folder aset/template: $stray_php_tags"
fi

bad_js=$(
    grep -rnE '\beval\s*\(|new\s+Function\s*\(|\batob\s*\(|document\.write\s*\(' \
        js templates || true
)
if [ -n "$bad_js" ]; then
    abort_deploy "Terdeteksi pola injeksi JS mencurigakan pada js/ atau templates/: $bad_js"
fi

leaked_secrets=$(
    grep -rnE 'kisf5786|USERNAME_CPANEL|KISI-SMK-[0-9A-F]{8}|ghp_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{20,}|BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY|smtp_password\s*=' \
        .htaccess index.php README.md SmkThemePlugin.php version.xml .cpanel.yml assets js locale styles templates || true
)
if [ -n "$leaked_secrets" ]; then
    abort_deploy "Terdeteksi potensi kredensial/token sensitif di dalam file: $leaked_secrets"
fi

verify_manifest_and_hashes "$REPO_ROOT" "repository smkTheme"

printf '[OK] Gatekeeper keamanan smkTheme LOLOS: SHA-256 valid, 0 file asing, 0 pola webshell.\n'
