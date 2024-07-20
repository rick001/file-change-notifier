<?php
/*
Plugin Name: File Change Notifier
Plugin URI: https://www.techbreeze.in/plugins/file-change-notifier
Description: Monitors file changes in the WordPress installation directory and notifies the admin.
Version: 1.0
Author: Techbreeze IT Solutions
Author URI: https://www.techbreeze.in
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: file-change-notifier
*/

// Include necessary files
include plugin_dir_path(__FILE__) . 'includes/fcn-functions.php';

// Hook into WordPress init action to set up our plugin
add_action('init', 'fcn_check_file_changes');

// Schedule the file check to run every hour
if (!wp_next_scheduled('fcn_check_file_changes_hook')) {
    wp_schedule_event(time(), 'hourly', 'fcn_check_file_changes_hook');
}

add_action('fcn_check_file_changes_hook', 'fcn_check_file_changes');

// Clear the scheduled event on plugin deactivation
register_deactivation_hook(__FILE__, 'fcn_deactivate');

// Add settings page
add_action('admin_menu', 'fcn_add_settings_page');

// Save changes
add_action('admin_post_fcn_save_changes', 'fcn_save_changes');

// Enqueue styles
add_action('admin_enqueue_scripts', 'fcn_enqueue_styles');
