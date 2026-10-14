<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$totalProducts   = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalStock      = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM products')->fetchColumn();
$stockValue      = (float)$pdo->query('SELECT COALESCE(SUM(stock*price),0) FROM products')->fetchColumn();
$lowStock = $pdo->query('SELECT sku,name,stock,min_stock FROM products WHERE stock <= min_stock ORDER BY stock ASC LIMIT 10')->fetchAll();
$recent = $pdo->query('SELECT t.*, p.name AS product, u.name AS user FROM stock_transactions t
                       JOIN products p ON p.id=t.product_id JOIN users u ON u.id=t.user_id
                       ORDER BY t.id DESC LIMIT 5')->fetchAll();

$title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<h1>Dashboard</h1>
<div class="grid">
  <div class="card"><div>Total Produk</div><div class="stat"><?= $totalProducts ?></div></div>
  <div class="card"><div>Total Kategori</div><div class="stat"><?= $totalCategories ?></div></div>
  <div class="card"><div>Total Stok</div><div class="stat"><?= number_format($totalStock) ?></div></div>
  <div class="card"><div>Nilai Stok</div><div class="stat">Rp <?= number_format($stockValue, 0, ',', '.') ?></div></div>
</div>

<div class="card"><h3>⚠️ Stok Menipis</h3>
<?php if (!$lowStock): ?><p>Semua stok aman.</p><?php else: ?>
<table><tr><th>SKU</th><th>Produk</th><th>Stok</th><th>Minimum</th></tr>
<?php foreach ($lowStock as $r): ?>
<tr><td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td><td class="low"><?= $r['stock'] ?></td><td><?= $r['min_stock'] ?></td></tr>
<?php endforeach; ?></table><?php endif; ?></div>

<div class="card"><h3>Transaksi Terbaru</h3>
<table><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Oleh</th></tr>
<?php foreach ($recent as $r): ?>
<tr><td><?= e($r['created_at']) ?></td><td><?= e($r['product']) ?></td>
<td class="<?= e($r['type']) ?>"><?= $r['type'] === 'in' ? 'Masuk' : 'Keluar' ?></td>
<td><?= $r['quantity'] ?></td><td><?= e($r['user']) ?></td></tr>
<?php endforeach; if (!$recent): ?><tr><td colspan="5">Belum ada transaksi.</td></tr><?php endif; ?></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
