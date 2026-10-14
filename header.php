<?php $u = current_user(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Inventory') ?> - Inventory App</title>
<link rel="stylesheet" href="<?= url('assets/style.css') ?>">
</head>
<body>
<?php if ($u): ?>
<nav>
  <b>📦 Inventory</b>
  <a href="<?= url('index.php') ?>">Dashboard</a>
  <a href="<?= url('products/index.php') ?>">Produk</a>
  <a href="<?= url('categories/index.php') ?>">Kategori</a>
  <a href="<?= url('stock/in.php') ?>">Barang Masuk</a>
  <a href="<?= url('stock/out.php') ?>">Barang Keluar</a>
  <a href="<?= url('stock/history.php') ?>">Riwayat</a>
  <span class="right"><?= e($u['name']) ?> <span class="badge"><?= e($u['role']) ?></span>
    <a href="<?= url('logout.php') ?>">Logout</a></span>
</nav>
<?php endif; ?>
<main>
<?php if ($f = flash()): ?>
  <div class="alert <?= e($f[1]) ?>"><?= e($f[0]) ?></div>
<?php endif; ?>
