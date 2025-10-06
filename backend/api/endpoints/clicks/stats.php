<?php
require_once __DIR__ . '/../../helpers/db.php';

function stream_click_stats() {
    // Set headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Disable buffering for Nginx

    // Set a long execution time, essential for a persistent connection.
    set_time_limit(0);
    
    // Ensure the script terminates if the client disconnects.
    ignore_user_abort(false);

    // Disable output buffering to send data immediately.
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();

    $last_stats_json = null;
    $last_heartbeat_time = time();

    try {
        while (true) {
            // Check if the client has disconnected before proceeding.
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
            $current_stats_json = json_encode($stats);

            // Send data only if it has changed
            if ($current_stats_json !== $last_stats_json) {
                echo "event: stats_update\n";
                echo "data: " . $current_stats_json . "\n\n";
                flush();
                $last_stats_json = $current_stats_json;
            }

            // Send a heartbeat every 10 seconds to keep the connection alive
            if (time() - $last_heartbeat_time >= 10) {
                echo ": heartbeat\n\n";
                flush();
                $last_heartbeat_time = time();
            }

            // Wait for 2 seconds before the next loop to avoid overwhelming the server
            sleep(2);
        }
    } catch (Exception $e) {
        // Log any errors without breaking the client connection if possible
        error_log("SSE Error: " . $e->getMessage());
    }
}
?>