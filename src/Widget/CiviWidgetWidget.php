<?php
namespace WPCivi\Jourcoop\Widget;

/**
 * Class Widget\CiviWidgetWidget
 * TODO This is a class that would implement \WP_Widget similar to what we did in CiviWidgetACFField,
 * TODO so we could also show our CiviWidgets in a sidebar, for instance.
 * @package WPCivi\Shared
 */
class CiviWidgetWidget extends \WP_Widget
{

//    /**
//     * ContactListWidget constructor.
//     */
//    public function __construct()
//    {
//        parent::__construct('wpcivi_widget_widget', __('CiviCRM Widget', 'wpcivi-shared'), [
//            'classname'   => 'wpcivi_widget_widget',
//            'description' => 'Displays a block with content from CiviCRM.',
//        ]);
//    }
//
//    /**
//     * Output the content of the widget.
//     * @param array $params
//     * @param array $instance
//     */
//    public function widget($params, $instance = [])
//    {
//        // Merge instance options and widget level overrides
//        $args = array_merge($instance, $params);
//
//        if(isset($params['widget'])) {
//            echo apply_filters('wpcivi_widget_render_' . $params['widget'], '');
//        } else {
//            echo '[WIDGET_UNDEFINED:' . $params['widget'] . ']';
//        }
//
//    }
//
//    /**
//     * Output the content of the widget (called statically from the shortcode method).
//     * @param array $args
//     */
//    public static function show($args)
//    {
//        $widget = new static;
//        $widget->widget($args);
//    }
//
//    /**
//     * Output the options form on admin.
//     * @param array $instance Widget instance *settings array*
//     * @return void
//     */
//    public function form($instance)
//    {
//        $this->view('ContactWidget/Admin', [
//            'type' => (!empty($instance['type']) ? $instance['type'] : null),
//        ]);
//    }
//
//    /**
//     * Update widget options.
//     * @param array $new_instance The widget instance to be saved
//     * @param array $old_instance The widget instance prior to update
//     * @return array $instance
//     */
//    public function update($new_instance, $old_instance)
//    {
//        // Processes widget options to be saved.
//        $instance['type'] = !empty($new_instance['type']) ? $new_instance['type'] : null;
//        return $instance;
//    }
//
//    /**
//     * Get a template file so we don't have to use inline HTML in a PHP class - it's 2016
//     * @param string $name Template name
//     * @param array $params Variables to pass to the template
//     * @return void
//     */
//    protected function view($name, $params = [])
//    {
//        $templatePath = plugin_dir_path(__FILE__) . '/templates/Widget/' . $name . '.php';
//        if (!file_exists($templatePath)) {
//            _e('[TPL_ERROR]');
//        }
//
//        extract($params);
//        include $templatePath;
//    }
}

///**
// * Deliver the widget as a shortcode.
// *
// * @param array $atts The shortcode attributes provided
// *                    Available attributes include:
// *                    - title string The widget title (default: "Upcoming Events"),
// *                    - summary bool 1 = display the summary,
// *                    - limit int The number of events (default: 5),
// *                    - alllink bool 1 = display "view all",
// *                    - wtheme string The widget theme (default: "stripe"),
// *                    - divider string The location field delimiter (default comma),
// *                    - city bool 1 = display event city,
// *                    - state string display event state/province:
// *                        'abbreviate' - abbreviation
// *                        'full' - full name
// *                        'none' (default) - display nothing
// *                    - country bool 1 = display event country,
// *                    - admin_type string display type:
// *                        'simple' (default) - use settings above for title, summary, etc.
// *                        'custom' - use custom_display and custom_filter
// *                    - custom_display string JSON of custom display options (see documentation).
// *                    - custom_filter string JSON of custom filter options (see documentation).
// *                    All booleans default to false; any value makes them true.
// *
// * @return string The widget to drop into the post body.
// */
//function civievent_widget_shortcode($atts)
//{
//    $widget = new civievent_Widget(true);
//    $defaults = $widget->_defaultWidgetParams;
//
//    // Taking care of those who take things literally.
//    if (is_array($atts)) {
//        foreach ($atts as $k => $v) {
//            if ('false' === $v) {
//                $atts[$k] = false;
//            }
//        }
//    }
//
//    foreach ($defaults as $param => $default) {
//        if (!empty($atts[$param])) {
//            $defaults[$param] = (false === $default) ? true : $atts[$param];
//        }
//    }
//    $widgetAtts = [];
//    return $widget->widget($widgetAtts, $defaults);
//}
//
//add_shortcode('civievent_widget', 'civievent_widget_shortcode');
