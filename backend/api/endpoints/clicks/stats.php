<?php
require_once __DIR__ . '/../../helpers/db.php';

function stream_click_stats() {
    // Set headers for Server-Sent Events
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Disable buffering for Nginx
    
    // Tell the client to reconnect after 1 second if the connection is lost.
    // This is a fallback for our time-limited script.
    echo "retry: 1000\n";

    // Set a time limit for the script, e.g., 55 seconds.
    // This prevents runaway processes on the server.
    $time_limit = 55;
    set_time_limit($time_limit + 5); // Add a small buffer
    $start_time = time();

    // Disable output buffering
    if (ob_get_level()) ob_end_flush();
    flush();

    $last_stats = null;

    try {
        while (time() - $start_time < $time_limit) {
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
            if ($current_stats_json !== json_encode($last_stats)) {
                echo "event: stats_update\n";
                echo "data: " . $current_stats_json . "\n\n";
                $last_stats = $stats;
            } else {
                // Send a comment as a heartbeat to keep the connection alive
                echo ": heartbeat\n\n";
            }

            // Flush the output buffer to send the data to the client
            if (flush() === false) {
                // If flush returns false, the client has disconnected.
                break;
            }

            // Wait for 2 seconds before the next loop iteration
            sleep(2);
        }
    } catch (Exception $e) {
        error_log("SSE Error: " . $e->getMessage());
    }
    // The script will naturally exit here when the time limit is reached.
    // The client will automatically reconnect due to the 'retry' header.
}
?>