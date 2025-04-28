<?php
/**
 * CodeIgniter PHP Built-in Server Routing Script
 * 
 * This script acts as a router for PHP's built-in server
 * It serves static files directly and routes all other requests through index.php
 */

// Check if running under PHP's built-in server
if (php_sapi_name() === 'cli-server') {
    // Get the requested URI
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    
    // Serve static files directly
    $file = __DIR__ . $uri;
    if (is_file($file)) {
        // Return false to let the server handle the file directly
        return false;
    }
    
    // For all other requests, route through index.php
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    include __DIR__ . '/index.php';
}
?>