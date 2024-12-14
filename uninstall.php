<?php
/**
 * File Name: uninstall.php
 * Version: 1.0.0
 * Description: Cleans up all options set by the Maintenance Mode Plugin upon uninstallation.
 */

// Exit if accessed directly.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// List of options to delete.
$options = [
    'enable_maintenance_mode',
    'maintenance_start',
    'maintenance_end',
    'enable_time_check',
    'maintenance_message',
    'maintenance_timezone',
];

// Delete each option.
foreach ($options as $option) {
    delete_option($option);
}
