<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/header.php';

if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

$error = '';
$success = '';

// Add / edit / deactivate / restore products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            if ($name === '' || $category === '' || $price <= 0) throw new Exception('Jaza jina, aina na bei sahihi.');
            $pdo->prepare('INSERT INTO products(name,category,price,active) VALUES(?,?,?,1)')->execute([$name,$category,$price]);
            $success = 'Bidhaa imeongezwa kikamilifu.';
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            if ($id <= 0 || $name === '' || $category === '' || $price <= 0) throw new Exception('Taarifa za bidhaa si sahihi.');
            $pdo->prepare('UPDATE products SET name=?, category=?, price=? WHERE id=?')->execute([$name,$category,$price,$id]);
            $success = 'Bidhaa imehaririwa kikamilifu.';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            // Soft delete preserves old bills and receipts.
            $pdo->prepare('UPDATE products SET active=0 WHERE id=?')->execute([$id]);
            $success = 'Bidhaa imeondolewa kwenye mauzo.';
        } elseif ($action === 'restore') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE products SET active=1 WHERE id=?')->execute([$id]);
            $success = 'Bidhaa imerudishwa kwenye mauzo.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$products = $pdo->query('SELECT * FROM products ORDER BY active DESC, category, name')->fetchAll();
?>
<h2>Usimamizi wa Bidhaa</h2>
<p>Admin anaweza kuongeza, kuhariri bei/jina/aina, au kuondoa bidhaa kwenye mauzo. Kuondoa ni <b>soft delete</b> ili bills za zamani zibaki salama.</p>

<?php if ($error): ?><div class="alert danger"><?=e($error)?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?=e($success)?></div><?php endif; ?>

<div class="card">
    <h3>+ Ongeza Bidhaa Mpya</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="grid">
            <div><label>Jina la Bidhaa</label><input name="name" required placeholder="Mfano: Chips Mayai"></div>
            <div><label>Aina</label><input name="category" required placeholder="Food / Drinks"></div>
            <div><label>Bei (TSh)</label><input type="number" step="0.01" min="0.01" name="price" required placeholder="5000"></div>
        </div>
        <button class="green">Ongeza Bidhaa</button>
    </form>
</div>

<br>
<table>
    <tr><th>#</th><th>Bidhaa</th><th>Aina</th><th>Bei</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><?=e($p['id'])?></td>
        <td><?=e($p['name'])?></td>
        <td><?=e($p['category'])?></td>
        <td>TSh <?=number_format($p['price'],2)?></td>
        <td><?= $p['active'] ? '<span class="badge green-badge">ACTIVE</span>' : '<span class="badge red-badge">INACTIVE</span>' ?></td>
        <td>
            <button type="button" class="btn gray" onclick='editProduct(<?=json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)'>Hariri</button>
            <?php if ($p['active']): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Una uhakika unataka kuondoa bidhaa hii kwenye mauzo?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$p['id']?>">
                <button class="btn red">Futa</button>
            </form>
            <?php else: ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="restore"><input type="hidden" name="id" value="<?=$p['id']?>">
                <button class="btn green">Rudisha</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<div id="editBox" class="card" style="display:none;margin-top:20px">
    <h3>Hariri Bidhaa</h3>
    <form method="post">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit_id">
        <div class="grid">
            <div><label>Jina</label><input name="name" id="edit_name" required></div>
            <div><label>Aina</label><input name="category" id="edit_category" required></div>
            <div><label>Bei (TSh)</label><input type="number" step="0.01" min="0.01" name="price" id="edit_price" required></div>
        </div>
        <button class="green">Hifadhi Mabadiliko</button>
        <button type="button" class="btn gray" onclick="document.getElementById('editBox').style.display='none'">Funga</button>
    </form>
</div>

<script>
function editProduct(p){
    document.getElementById('editBox').style.display='block';
    document.getElementById('edit_id').value=p.id;
    document.getElementById('edit_name').value=p.name;
    document.getElementById('edit_category').value=p.category;
    document.getElementById('edit_price').value=p.price;
    window.scrollTo({top:document.getElementById('editBox').offsetTop-20,behavior:'smooth'});
}
</script>
<?php require_once __DIR__.'/../config/footer.php'; ?>
