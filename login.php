<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) redirect('index.php');

$errors = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '') $errors[] = 'Username wajib diisi.';
    if ($password === '') $errors[] = 'Password wajib diisi.';
    if (!$errors) {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $user = $st->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'],
                                 'username' => $user['username'], 'role' => $user['role']];
            redirect('index.php');
        }
        $errors[] = 'Username atau password salah.';
    }
}
$title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="margin:60px auto">
  <h2>Login</h2>
  <?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Username</label><input name="username" value="<?= e($username) ?>">
    <label>Password</label><input type="password" name="password">
    <p><button class="btn">Masuk</button></p>
  </form>
  <small>Demo: admin / admin123 &middot; staff / staff123</small>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
