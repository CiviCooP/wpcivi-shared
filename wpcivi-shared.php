<?php

/*
Plugin Name: WPCivi Shared
Plugin URI: https://github.com/civicoop/wpcivi-shared
Description: Shared, reusable code for WordPress + CiviCRM integration.
Version: 1.3.0
Author: CiviCooP / Kevin Levie
Author URI: https://levity.nl
License: AGPL 3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.txt
Text Domain: wpcivi
*/

/**
 * WPCivi Shared: shared code for WordPress + CiviCRM integration.
 * Load autoloader and register namespace, so other plugins can use our classes.
 * @package WPCivi\Shared
 */

require_once __DIR__ . '/src/Autoloader.php';

// Load and register autoloader
$wpciviloader = \WPCivi\Shared\Autoloader::getInstance();
$wpciviloader->register();
$wpciviloader->addNamespace('WPCivi\\Shared\\', __DIR__ . '/src/');

// Register plugin (actions/filters are defined in the Plugin class)
add_action('plugins_loaded', function() {
    $plugin = new \WPCivi\Shared\Plugin;
    $plugin->register();
}, 101);