<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pdo = db();
$type = in_array($_GET['type'] ?? '', ['in', 'out'], true) ? $_GET['type'] : '';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$where = $type ? 'WHERE t.type = ?' : '';
$params = $type ? [$type] : [];

$c = $pdo->prepare("SELECT COUNT(*) FROM stock_transactions t $where");
$c->execute($params);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$st = $pdo->prepare("SELECT t.*, p.name AS product, p.sku, u.name AS user FROM stock_transactions t
    JOIN products p ON p.id=t.product_id JOIN users u ON u.id=t.user_id $where
    ORDER BY t.id DESC LIMIT $perPage OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();

$title = 'Riwayat Transaksi';
include __DIR__ . '/../includes/header.php';
?>
<h1>Riwayat Transaksi</h1>
<form class="filters" method="get">
  <select name="type"><option value="">Semua</option>
    <option value="in" <?= $type === 'in' ? 'selected' : '' ?>>Masuk</option>
    <option value="out" <?= $type === 'out' ? 'selected' : '' ?>>Keluar</option></select>
  <button class="btn">Filter</button>
</form>
<table>
<tr><th>Waktu</th><th>SKU</th><th>Produk</th><th>Tipe</th><th>Qty</th><th>Catatan</th><th>Oleh</th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['created_at']) ?></td><td><?= e($r['sku']) ?></td><td><?= e($r['product']) ?></td>
<td class="<?= e($r['type']) ?>"><?= $r['type'] === 'in' ? 'Masuk' : 'Keluar' ?></td><td><?= $r['quantity'] ?></td>
<td><?= e($r['note']) ?></td><td><?= e($r['user']) ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7">Belum ada transaksi.</td></tr><?php endif; ?>
</table>
<div class="pager">
<?php for ($i = 1; $i <= $pages; $i++): ?>
  <?= $i === $page ? "<span class='cur'>$i</span>" : '<a href="' . e(page_url($i)) . "\">$i</a>" ?>
<?php endfor; ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
