<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pdo = db();

// Hapus produk (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM stock_transactions WHERE product_id = ?');
    $cnt->execute([$id]);
    if ($cnt->fetchColumn() > 0) {
        flash('Produk punya riwayat transaksi, tidak bisa dihapus.', 'error');
    } else {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        flash('Produk dihapus.');
    }
    redirect('products/index.php');
}

// ---- Search, filter, sorting, pagination ----
$q        = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$status   = $_GET['status'] ?? '';
$sortMap  = ['name' => 'p.name', 'sku' => 'p.sku', 'price' => 'p.price', 'stock' => 'p.stock', 'category' => 'c.name'];
$sort     = array_key_exists($_GET['sort'] ?? '', $sortMap) ? $_GET['sort'] : 'name';   // whitelist
$dir      = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
$perPage  = 5;
$page     = max(1, (int)($_GET['page'] ?? 1));

$where = []; $params = [];
if ($q !== '')      { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($category > 0)  { $where[] = 'p.category_id = ?'; $params[] = $category; }
if ($status === 'low')  $where[] = 'p.stock <= p.min_stock';
if ($status === 'safe') $where[] = 'p.stock > p.min_stock';
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id $whereSql");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$st = $pdo->prepare("SELECT p.*, c.name AS category FROM products p
                     JOIN categories c ON c.id = p.category_id $whereSql
                     ORDER BY {$sortMap[$sort]} $dir, p.id ASC LIMIT $perPage OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

function sort_link(string $col, string $label, string $cur, string $dir): string {
    $q = $_GET; $q['sort'] = $col; $q['dir'] = ($cur === $col && $dir === 'ASC') ? 'desc' : 'asc'; unset($q['page']);
    $arrow = $cur === $col ? ($dir === 'ASC' ? ' ▲' : ' ▼') : '';
    return '<a href="?' . e(http_build_query($q)) . '">' . e($label) . $arrow . '</a>';
}

$title = 'Produk';
include __DIR__ . '/../includes/header.php';
?>
<h1>Produk</h1>
<form class="filters" method="get">
  <input name="q" placeholder="Cari nama / SKU" value="<?= e($q) ?>">
  <select name="category"><option value="0">Semua kategori</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $category === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">Semua stok</option>
    <option value="low"  <?= $status === 'low'  ? 'selected' : '' ?>>Stok menipis</option>
    <option value="safe" <?= $status === 'safe' ? 'selected' : '' ?>>Stok aman</option>
  </select>
  <input type="hidden" name="sort" value="<?= e($sort) ?>"><input type="hidden" name="dir" value="<?= strtolower($dir) ?>">
  <button class="btn">Terapkan</button> <a class="btn gray" href="index.php">Reset</a>
</form>
<?php if (is_admin()): ?><p><a class="btn" href="form.php">+ Tambah Produk</a></p><?php endif; ?>

<table>
<tr>
  <th><?= sort_link('sku', 'SKU', $sort, $dir) ?></th>
  <th><?= sort_link('name', 'Nama', $sort, $dir) ?></th>
  <th><?= sort_link('category', 'Kategori', $sort, $dir) ?></th>
  <th><?= sort_link('price', 'Harga', $sort, $dir) ?></th>
  <th><?= sort_link('stock', 'Stok', $sort, $dir) ?></th>
  <th>Aksi</th>
</tr>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['category']) ?></td>
  <td>Rp <?= number_format($r['price'], 0, ',', '.') ?></td>
  <td class="<?= $r['stock'] <= $r['min_stock'] ? 'low' : '' ?>"><?= $r['stock'] ?></td>
  <td><?php if (is_admin()): ?>
    <a class="btn sm gray" href="form.php?id=<?= $r['id'] ?>">Edit</a>
    <form class="inline" method="post" onsubmit="return confirm('Hapus produk ini?')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">Hapus</button></form>
  <?php else: ?>-<?php endif; ?></td>
</tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6">Tidak ada data.</td></tr><?php endif; ?>
</table>

<div class="pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <?= $i === $page ? "<span class='cur'>$i</span>" : '<a href="' . e(page_url($i)) . "\">$i</a>" ?>
  <?php endfor; ?>
  <span>Total: <?= $total ?> produk</span>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
