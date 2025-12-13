<?php
/**
 * CyberCon25 - Main Entry Point
 * Cybersecurity Conference Website
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Load composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Get the requested page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Route to appropriate controller
use App\classes\Home;

$homeController = new Home();

switch ($page) {
    case 'home':
        $homeController->index();
        break;
    
    case 'terms':
        $homeController->terms();
        break;
    
    default:
        $homeController->index();
        break;
}
