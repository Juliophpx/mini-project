<?php

/**
 * Database Configuration
 * env local
 *
 * Values are loaded from environment variables (.env file).
 */
return [
    'host'   => getenv('DB_HOST'),
    'port'   => getenv('DB_PORT'),
    'dbname' => getenv('DB_NAME'),
    'user'   => getenv('DB_USER'),
    'pass'   => getenv('DB_PASS'),
];

?>