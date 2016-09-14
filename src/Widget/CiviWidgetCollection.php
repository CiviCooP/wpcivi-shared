<?php
namespace WPCivi\Shared\Widget;

/**
 * Class Widget\CiviWidgetCollection
 * Fetches and stores an array of widget names/labels/classes for use by ACFField/Widget/etc classes.
 */
class CiviWidgetCollection
{

    /**
     * @var self $instance Widget collection instance
     */
    private static $instance;

    /**
     * @var array[] $widgets Internal array of widget names/labels/classes
     */
    private $widgets = [];

    /**
     * Get class instance
     * @return static Instance
     */
    public static function getInstance()
    {
        if (!is_object(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Get a list of widgets (based on filter declarations in BaseCiviWidget).
     * @return array Array of widgets
     */
    public function getWidgets()
    {
        if(empty($this->widgets)) {
            $this->widgets = apply_filters('wpcivi_available_widgets', []);
        }
        return $this->widgets;
    }

    /**
     * Get a single widget by name
     * @param string $name Widget Name
     * @return array|null Array of single widget settings
     */
    public function getWidget($name)
    {
        $widgets = $this->getWidgets();
        if(!empty($name) && array_key_exists($name, $widgets)) {
            return $widgets[$name];
        }
        return null;
    }

    /**
     * Get a single widget class
     * @param string $name Widget Name
     * @return BaseCiviWidget|null Single widget object
     */
    public function getWidgetClass($name)
    {
        $widgets = $this->getWidgets();
        if(!empty($name) && array_key_exists($name, $widgets)) {
            $widget = new $widgets[$name]['class'];
            return $widget;
        }
        return null;
    }

}
