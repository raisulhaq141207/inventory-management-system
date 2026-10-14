<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');   // staff -> 403
$pdo = db();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$data = ['category_id' => '', 'sku' => '', 'name' => '', 'price' => '', 'stock' => '0', 'min_stock' => '5'];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $data = $st->fetch() ?: null;
    if (!$data) { http_response_code(404); exit('Produk tidak ditemukan.'); }
}
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data['category_id'] = (int)($_POST['category_id'] ?? 0);
    $data['sku']  = strtoupper(trim($_POST['sku'] ?? ''));
    $data['name'] = trim($_POST['name'] ?? '');
    $data['price'] = trim($_POST['price'] ?? '');
    $data['min_stock'] = trim($_POST['min_stock'] ?? '');
    if (!$id) $data['stock'] = trim($_POST['stock'] ?? '');   // stok hanya bisa diisi saat produk baru

    $catOk = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $catOk->execute([$data['category_id']]);
    if (!$catOk->fetchColumn()) $errors[] = 'Kategori tidak valid.';
    if ($data['sku'] === '' || !preg_match('/^[A-Z0-9\-]{3,30}$/', $data['sku'])) $errors[] = 'SKU wajib 3-30 karakter (huruf/angka/-).';
    if ($data['name'] === '') $errors[] = 'Nama produk wajib diisi.';
    if (!is_numeric($data['price']) || $data['price'] < 0) $errors[] = 'Harga harus angka >= 0.';
    if (!ctype_digit((string)$data['min_stock'])) $errors[] = 'Stok minimum harus bilangan bulat >= 0.';
    if (!$id && !ctype_digit((string)$data['stock'])) $errors[] = 'Stok awal harus bilangan bulat >= 0.';

    if (!$errors) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?');
        $dup->execute([$data['sku'], $id]);
        if ($dup->fetchColumn() > 0) $errors[] = 'SKU sudah dipakai produk lain.';
    }
    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE products SET category_id=?, sku=?, name=?, price=?, min_stock=? WHERE id=?')
                ->execute([$data['category_id'], $data['sku'], $data['name'], $data['price'], $data['min_stock'], $id]);
        } else {
            $pdo->prepare('INSERT INTO products (category_id, sku, name, price, stock, min_stock) VALUES (?,?,?,?,?,?)')
                ->execute([$data['category_id'], $data['sku'], $data['name'], $data['price'], $data['stock'], $data['min_stock']]);
        }
        flash('Produk disimpan.');
        redirect('products/index.php');
    }
}
$title = $id ? 'Edit Produk' : 'Tambah Produk';
include __DIR__ . '/../includes/header.php';
?>
<h1><?= e($title) ?></h1>
<div class="card form">
<?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <label>Kategori</label>
  <select name="category_id"><option value="">-- pilih --</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$data['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <label>SKU</label><input name="sku" value="<?= e($data['sku']) ?>">
  <label>Nama Produk</label><input name="name" value="<?= e($data['name']) ?>">
  <label>Harga (Rp)</label><input name="price" value="<?= e($data['price']) ?>">
  <?php if (!$id): ?><label>Stok Awal</label><input name="stock" value="<?= e($data['stock']) ?>">
  <?php else: ?><p><small>Stok saat ini: <b><?= (int)$data['stock'] ?></b> (ubah lewat Barang Masuk/Keluar)</small></p><?php endif; ?>
  <label>Stok Minimum</label><input name="min_stock" value="<?= e($data['min_stock']) ?>">
  <p><button class="btn">Simpan</button> <a class="btn gray" href="index.php">Batal</a></p>
</form></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
