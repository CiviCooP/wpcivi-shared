<?php
namespace WPCivi\Shared;

use WPCivi\Shared\Widget\CiviWidgetACFField;
use WPCivi\Shared\Gravity\BackendFormHandler;

/**
 * Class Plugin
 * Initialises all WP plugin functionality.
 * @package WPCivi\Shared
 */
class Plugin extends BasePlugin
{

    public function register()
    {

        /* ----- BASIC GRAVITY FORM HANDLER ----- */

        $this->addAction('admin_init', function () {
            new BackendFormHandler;
        }, 1);

        /* ----- CUSTOM ACF FIELD TYPE ----- */

        $this->addAction('acf/include_field_types', function() {
            new CiviWidgetACFField;
        });

        /* Later: widgets
           add_action('widgets_init', function () {
            register_widget(new ContactWidget);
        }); */

    }
}