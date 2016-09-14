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

/* --- LOAD AND REGISTER AUTOLOADER --- */

$wpciviloader = \WPCivi\Shared\Autoloader::getInstance();
$wpciviloader->register();
$wpciviloader->addNamespace('WPCivi\\Shared\\', __DIR__ . '/src/');


/* --- BACKEND GRAVITY FORM HANDLER --- */

add_action('admin_init', function() {
    new \WPCivi\Shared\Gravity\BackendFormHandler;
    new \WPCivi\Shared\Gravity\BackendContactLink;
}, 51);


/* --- CUSTOM ACF FIELD TYPE --- */

add_action('acf/include_field_types', function() {
    new \WPCivi\Shared\Widget\CiviWidgetACFField;
}, 51);

/* --- WELLICHT: LATER: WIDGETS --- */

/* add_action('widgets_init', function () {
        register_widget(new ContactWidget);
    });
}, 51); */
