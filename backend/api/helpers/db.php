<?php
function get_db_connection() {
    static $db = null;
    
    if ($db === null) {
        $config = require __DIR__ . '/../config/db_config.php';
        $db = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", 
            $config['user'], 
            $config['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    return $db;
}

function query($sql, $params = []) {
    $db = get_db_connection();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
?>
