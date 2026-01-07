<?php
/**
 * Revenue Calculator Theme Functions - FIXED AJAX VERSION
 */

// Theme setup
function revenue_calculator_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'revenue_calculator_setup');

// Enqueue scripts and styles
function revenue_calculator_scripts() {
    // CSS
    wp_enqueue_style('revenue-calculator-style', get_stylesheet_uri());
    wp_enqueue_style('calculator-css', get_template_directory_uri() . '/css/calculator.css', array(), '1.0.2');
    
    // JavaScript
    wp_enqueue_script('jquery');
    wp_enqueue_script('calculator-js', get_template_directory_uri() . '/js/calculator.js', array('jquery'), '1.0.2', true);
    wp_enqueue_script('ai-assistant-js', get_template_directory_uri() . '/js/ai-assistant.js', array('jquery'), '1.0.2', true);
    
    // Localize script for AJAX
    wp_localize_script('calculator-js', 'calculator_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('calculator_ajax_nonce')
    ));
    
    wp_localize_script('ai-assistant-js', 'ai_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_assistant_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'revenue_calculator_scripts');

// Include AJAX handlers
require_once get_template_directory() . '/inc/ajax-handlers.php';

// Register AJAX actions
add_action('wp_ajax_save_calculation', 'save_calculation');
add_action('wp_ajax_nopriv_save_calculation', 'save_calculation');
add_action('wp_ajax_export_spreadsheet', 'export_spreadsheet');
add_action('wp_ajax_nopriv_export_spreadsheet', 'export_spreadsheet');
add_action('wp_ajax_ai_assistant', 'ai_assistant_handler');
add_action('wp_ajax_nopriv_ai_assistant', 'ai_assistant_handler');

// Enable debug mode for testing (remove in production)
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Create uploads directory on theme activation
function revenue_calculator_activate() {
    $upload_dir = wp_upload_dir();
    $exports_dir = $upload_dir['basedir'] . '/revenue-calculator-exports';
    
    if (!file_exists($exports_dir)) {
        wp_mkdir_p($exports_dir);
        // Add .htaccess to protect the directory
        $htaccess = $exports_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\nDeny from all");
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'revenue_calculator_activate');

// Clean up old export files daily
function revenue_calculator_cleanup_exports() {
    $upload_dir = wp_upload_dir();
    $exports_dir = $upload_dir['basedir'] . '/revenue-calculator-exports';
    
    if (file_exists($exports_dir)) {
        $files = glob($exports_dir . '/*.{csv,pdf,txt}', GLOB_BRACE);
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                // Delete files older than 24 hours
                if ($now - filemtime($file) >= 86400) {
                    unlink($file);
                }
            }
        }
    }
}
add_action('wp', 'revenue_calculator_cleanup_exports');