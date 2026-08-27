<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/header.php';

if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); die('Access denied'); }

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    try {
        if ($current === '' || $new === '' || $confirm === '') {
            throw new Exception('Jaza password zote.');
        }
        if (strlen($new) < 6) {
            throw new Exception('Password mpya iwe na angalau herufi 6.');
        }
        if ($new !== $confirm) {
            throw new Exception('Password mpya na confirmation hazifanani.');
        }

        $s = $pdo->prepare('SELECT password_hash FROM users WHERE id=? AND role="admin" LIMIT 1');
        $s->execute([(int)$_SESSION['user']['id']]);
        $user = $s->fetch();

        if (!$user || !password_verify($current, $user['password_hash'])) {
            throw new Exception('Current password si sahihi.');
        }

        if (password_verify($new, $user['password_hash'])) {
            throw new Exception('Password mpya lazima iwe tofauti na ya zamani.');
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $u = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $u->execute([$hash, (int)$_SESSION['user']['id']]);
        $msg = 'Password yako imebadilishwa kikamilifu. Tumia password mpya wakati wa login inayofuata.';
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>

<h2>Badilisha Password ya Admin</h2>
<p>Badilisha password yako moja kwa moja kutoka kwenye Dashboard bila kutumia phpMyAdmin.</p>

<?php if ($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if ($err): ?><div class="alert danger"><?=e($err)?></div><?php endif; ?>

<div class="card" style="max-width:520px">
    <h3>Change Password</h3>
    <form method="post" autocomplete="off">
        <label>Current Password</label>
        <input type="password" name="current_password" required autocomplete="current-password">

        <label>New Password</label>
        <input type="password" name="new_password" required minlength="6" autocomplete="new-password">

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password">

        <button class="green" type="submit">🔐 Badilisha Password</button>
    </form>
</div>

<?php require_once __DIR__.'/../config/footer.php'; ?>
