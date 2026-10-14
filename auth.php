<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const BASE_URL = '/inventory-app'; // ubah jika nama folder proyek berbeda

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

function require_login(): void {
    if (!current_user()) redirect('login.php');
}

/** Hanya role tertentu yang boleh. Selain itu -> 403 Forbidden. */
function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        $title = '403 Akses Ditolak';
        include __DIR__ . '/header.php';
        echo '<div class="alert error"><h2>403 - Akses Ditolak</h2>
              <p>Role Anda (<b>' . e(current_user()['role']) . '</b>) tidak diizinkan membuka halaman ini.</p>
              <a href="' . url('index.php') . '">Kembali ke Dashboard</a></div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('CSRF token tidak valid.');
    }
}

function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Link pagination sederhana yang mempertahankan query string lain. */
function page_url(int $p): string {
    $q = $_GET; $q['page'] = $p;
    return '?' . http_build_query($q);
}
