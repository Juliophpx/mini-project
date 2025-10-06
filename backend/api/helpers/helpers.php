<?php


function get_user_level_type($user_id) {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT level, type FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('User not found');
    }
    return $user; // ['level' => ..., 'type' => ...]
}


?>
