<?php
/**
 * Application Initialization
 * 
 * This file initializes the application by loading all required
 * configuration files and function libraries. Include this file
 * at the beginning of all PHP pages.
 */

// Start output buffering for better performance
ob_start();

// Load configuration files
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Load utility functions
require_once __DIR__ . '/functions/utilities.php';

// Load authentication functions
require_once __DIR__ . '/functions/auth.php';

// Load vehicle management functions
require_once __DIR__ . '/functions/vehicle.php';

// Load driver management functions (if exists)
if (file_exists(__DIR__ . '/functions/driver.php')) {
    require_once __DIR__ . '/functions/driver.php';
}

// Load email functions (if exists)
if (file_exists(__DIR__ . '/functions/email.php')) {
    require_once __DIR__ . '/functions/email.php';
}

// Load notification functions (if exists)
if (file_exists(__DIR__ . '/functions/notifications.php')) {
    require_once __DIR__ . '/functions/notifications.php';
}

// Set error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set exception handler
set_exception_handler(function($exception) {
    logError("Uncaught Exception: " . $exception->getMessage(), 'ERROR', [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    if (isDevelopment()) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error:</strong> ' . htmlspecialchars($exception->getMessage()) . '<br>';
        echo '<strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '<br>';
        echo '<strong>Line:</strong> ' . $exception->getLine();
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
    }
});

// Check session timeout
if (isLoggedIn()) {
    checkSessionTimeout();
}

/**
 * Get Asset URL
 * 
 * Returns the full URL for an asset file.
 * 
 * @param string $path Asset path relative to assets directory
 * @return string Full asset URL
 */
function asset($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Get View Path
 * 
 * Returns the full path to a view file.
 * 
 * @param string $view View name (without .php extension)
 * @return string Full view path
 */
function view($view) {
    return VIEWS_PATH . '/' . $view . '.php';
}

/**
 * Include View
 * 
 * Includes a view file with optional data.
 * 
 * @param string $view View name
 * @param array $data Data to pass to the view
 */
function includeView($view, $data = []) {
    // Extract data to make variables available in view
    extract($data);
    
    $viewPath = view($view);
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        throw new Exception("View file not found: $viewPath");
    }
}

/**
 * Redirect with Message
 * 
 * Redirects to a URL with a flash message.
 * 
 * @param string $url URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
    redirect($url);
}

/**
 * Get Flash Message
 * 
 * Retrieves and clears the flash message from session.
 * 
 * @return array|null Flash message array or null if no message
 */
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $message;
}

/**
 * Display Flash Message
 * 
 * Displays the flash message if one exists.
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        echo "<div class=\"alert alert-{$type}\">" . htmlspecialchars($message) . "</div>";
    }
}

// Auto-include common CSS and JS files
function includeCommonAssets() {
    echo '<link rel="stylesheet" href="' . asset('css/main.css') . '">';
    echo '<link rel="stylesheet" href="' . asset('css/styles.css') . '">';
    echo '<script src="' . asset('js/main.js') . '" defer></script>';
}

// Note: CSRF protection is now handled by SecurityMiddleware
// The old CSRF functions have been removed to avoid conflicts 