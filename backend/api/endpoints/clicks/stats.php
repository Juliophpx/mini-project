<?php
require_once __DIR__ . '/../../helpers/db.php';

function stream_click_stats() {
    // Set headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Disable buffering for Nginx

    // Set a long execution time
    set_time_limit(0);
    
    // Disable output buffering
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();

    // >>> INICIO DEL CAMBIO <<<
    // Enviar un evento de conexión inicial para abrir el stream inmediatamente
    echo "event: connected\n";
    echo "data: {\"message\": \"Connection established\"}\n\n";
    flush();
    // >>> FIN DEL CAMBIO <<<

    $last_stats = null;
    $last_heartbeat_time = time();

    try {
        while (true) {
            // Check if the client has disconnected
            if (connection_aborted()) {
                break;
            }

            // Fetch the latest click stats
            $sql = "
                SELECT 
                    u.id as user_id,
                    u.name, 
                    u.email, 
                    ubc.button_id, 
                    ubc.click_count,
                    ubc.updated_at
                FROM user_button_clicks ubc
                JOIN users u ON ubc.user_id = u.id
                ORDER BY ubc.updated_at DESC
            ";
            
            $stmt = query($sql);
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Send data only if it has changed
            if (json_encode($stats) !== json_encode($last_stats)) {
                echo "event: stats_update\n";
                echo "data: " . json_encode($stats) . "\n\n";
                flush();
                $last_stats = $stats;
            }

            // Send a heartbeat every 15 seconds to keep the connection alive
            if (time() - $last_heartbeat_time > 15) {
                echo ": heartbeat\n\n";
                flush();
                $last_heartbeat_time = time();
            }

            // Wait for 1 second before the next loop iteration
            sleep(1);
        }
    } catch (Exception $e) {
        // Log any errors without breaking the client connection if possible
        error_log("SSE Error: " . $e->getMessage());
    }
}
?>