<?php
namespace WPCivi\Shared\Widget;

use WPCivi\Shared\BasePlugin;

/**
 * Class Widget\BaseCiviWidget
 * @package WPCivi\Shared
 *
 * A basic base class to implement rendering a CiviCRM block that can be selected from Advanced Custom Fields,
 * as well as WP Widgets and by using shortcodes (TODO untested / not implemented yet!)
 *
 * Implementation: implement this class and define a render() function that returns your content.
 * We might extend this later to allow for more configurable options.
 */
class BaseCiviWidget extends BasePlugin
{

    /**
     * @var string $label Widget handler label (defaults to class name)
     */
    protected $label;

    /**
     * BaseWidget constructor.
     * @param string $label Pretty widget name
     */
    public function __construct($label = null)
    {
        $this->label = $label;
        $this->register();
    }

    /**
     * Register this handler by adding it to the wpcivi_available_widgets filter.
     */
    public function register()
    {
        $this->addFilter('wpcivi_available_widgets', function ($widgets) {
            $widgets[$this->getName()] = [
                'name' => $this->getName(),
                'label' => $this->getLabel(),
                'class' => get_class($this),
                ];
            return $widgets;
        }, 10, 1);
    }

    /**
     * Render widget and return its contents.
     * You can choose to override either this render() method or view() below.
     * @param array $params Parameters
     * @return string Widget content
     */
    public function render($params = [])
    {
        ob_start();
        $this->view($params);
        return ob_get_clean();
    }

    /**
     * Echo widget contents.
     * @param array $params Parameters
     * @return void
     */
    public function view($params = [])
    {
        echo '[WIDGET_NOT_IMPLEMENTED: ' . $this->getName() . ']';
    }

    /**
     * Call this widget statically (might be useful sometime)
     * @param array $params Parameters
     * @return string Widget content
     */
    public static function call($params = [])
    {
        $widget = new static;
        return $widget->render($params);
    }

    /**
     * Get current class name.
     * @return string Class Name
     */
    protected function getName()
    {
        $class = new \ReflectionClass($this);
        return $class->getShortName();
    }

    /**
     * Get current class pretty name.
     * @return string Class Label
     */
    protected function getLabel()
    {
        if (!empty($this->label)) {
            return $this->label;
        }
        return $this->getName();
    }

}
