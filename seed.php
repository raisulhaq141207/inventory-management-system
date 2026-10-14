<?php
// Jalankan SETELAH schema.sql:  php database/seed.php
// Membuat akun awal dengan password ter-hash (bcrypt).
require __DIR__ . '/../config/database.php';
$pdo = db();
$users = [
  ['Administrator', 'admin', 'admin123', 'admin'],
  ['Staff Gudang',  'staff', 'staff123', 'staff'],
];
$st = $pdo->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?,?,?,?)
                     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)');
foreach ($users as [$n, $u, $p, $r]) {
  $st->execute([$n, $u, password_hash($p, PASSWORD_DEFAULT), $r]);
  echo "User $u ($r) siap.\n";
}
