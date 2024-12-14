<?php
/**
 * Plugin Name:        Maintenance Mode Plugin
 * Plugin URI:         https://octahexa.com/
 * Description:        A plugin to manage maintenance mode with admin settings, time-based checks, and manual overrides.
 * Version:            2.3.2
 * Author:             octahexa
 * Author URI:         https://octahexa.com
 * Text Domain:        maintenance-mode-plugin
 * Domain Path:        /languages
 * Requires PHP:       7.4
 * License:            GPLv2 or later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Last Modified:      2024-12-07
 * GitHub Plugin URI:  https://github.com/WPSpeedExpert/simple-maintenance-mode
 * GitHub Branch:      main
 */

// Manual Override: Set this to `false` to completely disable maintenance mode.
$enable_maintenance_mode = true;

if (!$enable_maintenance_mode) {
    return; // Maintenance mode manually disabled.
}

/**
 * Initialize Default Settings for Maintenance Mode
 */
add_action('after_switch_theme', function () {
    if (!get_option('enable_maintenance_mode')) {
        update_option('enable_maintenance_mode', false);
    }
    if (!get_option('maintenance_start')) {
        update_option('maintenance_start', '2024-11-29 18:00');
    }
    if (!get_option('maintenance_end')) {
        update_option('maintenance_end', '2024-11-29 19:00');
    }
    if (!get_option('maintenance_message')) {
        update_option('maintenance_message', '<h1>We\'ll be back soon!</h1><p>The site is currently undergoing scheduled maintenance.</p>');
    }
    if (!get_option('maintenance_timezone')) {
        update_option('maintenance_timezone', 'Europe/Amsterdam');
    }
    if (!get_option('enable_time_check')) {
        update_option('enable_time_check', false);
    }
});

/**
 * Enforce Maintenance Mode
 */
add_action('init', 'bg_maintenance_mode');
function bg_maintenance_mode() {
    // Emergency login override
    if (isset($_GET['emergency-login']) && '1' === $_GET['emergency-login']) {
        return; // Allow login.
    }

    if (!get_option('enable_maintenance_mode', false)) {
        return; // Maintenance mode disabled via admin.
    }

    $timezone_string = get_option('maintenance_timezone', 'Europe/Amsterdam');
    try {
        $timezone = new DateTimeZone($timezone_string);
    } catch (Exception $e) {
        $timezone = new DateTimeZone('UTC'); // Fallback to UTC.
    }

    $maintenance_start = get_option('maintenance_start', '2024-11-29 18:00');
    $maintenance_end   = get_option('maintenance_end', '2024-11-29 19:00');

    $start_time = new DateTime($maintenance_start, $timezone);
    $end_time   = new DateTime($maintenance_end, $timezone);

    if (get_option('enable_time_check', false)) {
        $current_time = new DateTime('now', $timezone);
        if ($current_time < $start_time || $current_time > $end_time) {
            return;
        }
    }

    if (current_user_can('administrator')) {
        return; // Allow administrators.
    }

    $default_message = '<h1>We\'ll be back soon!</h1><p>The site is currently undergoing scheduled maintenance.</p>';
    $maintenance_message = get_option('maintenance_message', $default_message);

    header('Retry-After: 3600');
    wp_die(
        $maintenance_message,
        'Maintenance Mode',
        ['response' => 503]
    );
}

/**
 * Admin Settings Page
 */
function render_maintenance_mode_settings() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Maintenance Mode Settings', 'maintenance-mode-plugin'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('maintenance_mode_settings');
            do_settings_sections('maintenance-mode');
            submit_button();
            ?>
        </form>
        <hr>
        <h2><?php esc_html_e('Emergency Login', 'maintenance-mode-plugin'); ?></h2>
        <p><?php esc_html_e('Use the URL below for emergency access during maintenance:', 'maintenance-mode-plugin'); ?></p>
        <p><code><?php echo esc_url(home_url('?emergency-login=1')); ?></code></p>
    </div>
    <?php
}

/**
 * Register Plugin Settings
 */
add_action('admin_init', function () {
    register_setting('maintenance_mode_settings', 'enable_maintenance_mode', ['type' => 'boolean', 'default' => false]);
    register_setting('maintenance_mode_settings', 'maintenance_start', ['type' => 'string', 'default' => '2024-11-29 18:00']);
    register_setting('maintenance_mode_settings', 'maintenance_end', ['type' => 'string', 'default' => '2024-11-29 19:00']);
    register_setting('maintenance_mode_settings', 'enable_time_check', ['type' => 'boolean', 'default' => false]);
    register_setting('maintenance_mode_settings', 'maintenance_message', ['type' => 'string', 'default' => '<h1>We\'ll be back soon!</h1><p>The site is currently undergoing scheduled maintenance.</p>']);
    register_setting('maintenance_mode_settings', 'maintenance_timezone', ['type' => 'string', 'default' => 'Europe/Amsterdam']);

    add_settings_section('general_section', __('General Settings', 'maintenance-mode-plugin'), function () {
        echo '<p>' . esc_html__('Configure maintenance mode settings.', 'maintenance-mode-plugin') . '</p>';
    }, 'maintenance-mode');

    add_settings_field('enable_maintenance_mode', __('Enable Maintenance Mode', 'maintenance-mode-plugin'), function () {
        echo '<input type="checkbox" id="enable_maintenance_mode" name="enable_maintenance_mode" value="1" ' . checked(1, get_option('enable_maintenance_mode'), false) . ' />';
    }, 'maintenance-mode', 'general_section');

    add_settings_field('maintenance_start', __('Maintenance Start Time', 'maintenance-mode-plugin'), function () {
        echo '<input type="datetime-local" id="maintenance_start" name="maintenance_start" value="' . esc_attr(get_option('maintenance_start')) . '" />';
    }, 'maintenance-mode', 'general_section');

    add_settings_field('maintenance_end', __('Maintenance End Time', 'maintenance-mode-plugin'), function () {
        echo '<input type="datetime-local" id="maintenance_end" name="maintenance_end" value="' . esc_attr(get_option('maintenance_end')) . '" />';
    }, 'maintenance-mode', 'general_section');

    add_settings_field('enable_time_check', __('Enable Time-Based Maintenance', 'maintenance-mode-plugin'), function () {
        echo '<input type="checkbox" id="enable_time_check" name="enable_time_check" value="1" ' . checked(1, get_option('enable_time_check'), false) . ' />';
    }, 'maintenance-mode', 'general_section');

    add_settings_field('maintenance_message', __('Custom Maintenance Message', 'maintenance-mode-plugin'), function () {
        echo '<textarea id="maintenance_message" name="maintenance_message" rows="3" cols="50">' . esc_textarea(get_option('maintenance_message')) . '</textarea>';
    }, 'maintenance-mode', 'general_section');

    add_settings_field('maintenance_timezone', __('Timezone', 'maintenance-mode-plugin'), function () {
        $timezones = DateTimeZone::listIdentifiers();
        echo '<select id="maintenance_timezone" name="maintenance_timezone">';
        foreach ($timezones as $timezone) {
            echo '<option value="' . esc_attr($timezone) . '" ' . selected(get_option('maintenance_timezone', 'Europe/Amsterdam'), $timezone, false) . '>' . esc_html($timezone) . '</option>';
        }
        echo '</select>';
    }, 'maintenance-mode', 'general_section');
});

/**
 * Add Plugin Menu to Admin Dashboard
 */
add_action('admin_menu', function () {
    add_options_page(
        __('Maintenance Mode Settings', 'maintenance-mode-plugin'),
        __('Maintenance Mode', 'maintenance-mode-plugin'),
        'manage_options',
        'maintenance-mode',
        'render_maintenance_mode_settings'
    );
});
