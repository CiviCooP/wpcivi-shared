<?php

/*
Plugin Name: WPCivi Shared Code
Plugin URI: https://github.com/civicoop/wpcivi-shared
Description: Shared code for WordPress + CiviCRM integration, such as the WPCiviApi class.
Version: 1.1
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

// Add WPCivi\Shared namespace
$wpciviloader->addNamespace('WPCivi\Shared', __DIR__ . '/src/');

// Functies als dit zouden dan bijvoorbeeld iets moeten doen:
// $wpcivi = \WPCivi\Shared\Civi\WPCiviApi::getInstance();
