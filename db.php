<?php
// ============================================================
//  Dad-a-Base — Database Connection
//  Replace YOUR_PASSWORD_HERE with your actual database password
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'tobyjhmw_dadabase');
define('DB_USER', 'tobyjhmw_dadabasedad');
define('DB_PASS', 'YOUR_PASSWORD_HERE');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Check your credentials in db.php.');
}
?>
