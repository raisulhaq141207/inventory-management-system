<?php
// Dipakai oleh in.php dan out.php. Variabel $type ('in' | 'out') harus sudah di-set.
require_once __DIR__ . '/../includes/auth.php';
require_role('admin', 'staff');
$pdo = db();
$errors = [];
$input = ['product_id' => '', 'quantity' => '', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $input['product_id'] = (int)($_POST['product_id'] ?? 0);
    $input['quantity']   = trim($_POST['quantity'] ?? '');
    $input['note']       = trim($_POST['note'] ?? '');

    // --- Validasi ---
    if ($input['product_id'] <= 0) $errors[] = 'Produk wajib dipilih.';
    if (!ctype_digit($input['quantity']) || (int)$input['quantity'] < 1) $errors[] = 'Jumlah harus bilangan bulat minimal 1.';
    if (mb_strlen($input['note']) > 255) $errors[] = 'Catatan maksimal 255 karakter.';

    if (!$errors) {
        $qty = (int)$input['quantity'];
        try {
            $pdo->beginTransaction();
            // Kunci baris produk agar tidak ada race condition antar request
            $st = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE');
            $st->execute([$input['product_id']]);
            $p = $st->fetch();
            if (!$p) throw new RuntimeException('Produk tidak ditemukan.');

            if ($type === 'out' && $p['stock'] < $qty) {
                throw new RuntimeException("Stok tidak cukup. Stok {$p['name']} saat ini {$p['stock']}, diminta $qty.");
            }
            $delta = $type === 'in' ? $qty : -$qty;

            $pdo->prepare('INSERT INTO stock_transactions (product_id, user_id, type, quantity, note) VALUES (?,?,?,?,?)')
                ->execute([$p['id'], current_user()['id'], $type, $qty, $input['note'] ?: null]);
            $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$delta, $p['id']]);

            $pdo->commit();
            flash(($type === 'in' ? 'Barang masuk' : 'Barang keluar') . " tercatat: {$p['name']} x $qty.");
            redirect("stock/$type.php");
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $ex->getMessage();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();   // semua perubahan dibatalkan
            $errors[] = 'Terjadi kesalahan database, transaksi dibatalkan.';
        }
    }
}

$products = $pdo->query('SELECT id, sku, name, stock FROM products ORDER BY name')->fetchAll();
$label = $type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
$title = $label;
include __DIR__ . '/../includes/header.php';
?>
<h1><?= $label ?></h1>
<div class="card form">
<?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <label>Produk</label>
  <select name="product_id"><option value="">-- pilih produk --</option>
    <?php foreach ($products as $p): ?>
    <option value="<?= $p['id'] ?>" <?= (int)$input['product_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['sku'] . ' - ' . $p['name'] . ' (stok: ' . $p['stock'] . ')') ?></option>
    <?php endforeach; ?>
  </select>
  <label>Jumlah</label><input name="quantity" value="<?= e($input['quantity']) ?>">
  <label>Catatan</label><input name="note" value="<?= e($input['note']) ?>">
  <p><button class="btn"><?= $type === 'in' ? 'Simpan Barang Masuk' : 'Simpan Barang Keluar' ?></button></p>
</form></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
