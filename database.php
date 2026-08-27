<?php
$host='localhost'; $db='restaurant_pos'; $user='root'; $pass='';
try {
    $pdo=new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4",$user,$pass,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);

    // Automatic schema migration for installations that were created with an older POS version.
    // This prevents errors such as: Unknown column 'stock_qty'.
    $columnExists = function(string $table, string $column) use ($pdo): bool {
        $q=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $q->execute([$table,$column]);
        return (int)$q->fetchColumn() > 0;
    };

    $tableExists = function(string $table) use ($pdo): bool {
        $q=$pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
        $q->execute([$table]);
        return (int)$q->fetchColumn() > 0;
    };

    // Products migration
    if (!$columnExists('products','cost_price')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN cost_price DECIMAL(12,2) NOT NULL DEFAULT 0");
    }
    if (!$columnExists('products','stock_qty')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock_qty DECIMAL(12,2) NOT NULL DEFAULT 0");
    }
    if (!$columnExists('products','reorder_level')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN reorder_level DECIMAL(12,2) NOT NULL DEFAULT 0");
    }

    // Bills migration
    if ($tableExists('bills')) {
        if (!$columnExists('bills','table_id')) $pdo->exec("ALTER TABLE bills ADD COLUMN table_id INT NULL");
        if (!$columnExists('bills','discount')) $pdo->exec("ALTER TABLE bills ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0");
        if (!$columnExists('bills','service_charge')) $pdo->exec("ALTER TABLE bills ADD COLUMN service_charge DECIMAL(12,2) NOT NULL DEFAULT 0");
        if (!$columnExists('bills','notes')) $pdo->exec("ALTER TABLE bills ADD COLUMN notes VARCHAR(255) NULL");
    }

    // Create supporting V2 tables when they do not exist.
    $pdo->exec("CREATE TABLE IF NOT EXISTS restaurant_tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_no VARCHAR(30) UNIQUE NOT NULL,
        seats INT NOT NULL DEFAULT 4,
        status ENUM('AVAILABLE','OCCUPIED') DEFAULT 'AVAILABLE',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        movement_type ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
        quantity DECIMAL(12,2) NOT NULL,
        note VARCHAR(255) NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_date DATE NOT NULL,
        category VARCHAR(80) NOT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Give existing products a usable opening stock only when their stock is zero.
    // This does not overwrite stock already entered by the restaurant.
    if ($tableExists('products')) {
        $pdo->exec("UPDATE products SET stock_qty=100 WHERE stock_qty=0");
    }

} catch(PDOException $e){
    die('Database connection failed: '.htmlspecialchars($e->getMessage()));
} catch(Throwable $e){
    die('System migration failed: '.htmlspecialchars($e->getMessage()));
}
?>
