<?php
/**
 * Home Controller
 * Handles routing and rendering for the CyberCon25 website
 */

namespace App\classes;

class Home {
    
    /**
     * Render the home page
     */
    public function index() {
        include __DIR__ . '/../../pages/home.php';
    }
    
    /**
     * Render the terms and conditions page
     */
    public function terms() {
        include __DIR__ . '/../../pages/terms.php';
    }
}
