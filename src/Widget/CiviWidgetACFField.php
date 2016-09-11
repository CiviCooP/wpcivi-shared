<?php

namespace WPCivi\Shared\Widget;
use WPCivi\Shared\Civi\WPCiviException;

/**
 * Class Widget\CiviWidgetACFField
 * Implements a custom Advanced Custom Fields field type to show a Flexible Content block with content from CiviCRM.
 * Usage: implement BaseCiviWidget and it will automatically show up in ACF.
 * @package WPCivi\Shared
 */
class CiviWidgetACFField extends \acf_field
{
    /**
     * @var array Internal array of widget names/labels/classes
     */
    private $widgets = [];

    /**
     * CiviWidgetACFField constructor. Set field name, label and type.
     */
    public function __construct()
    {
        $this->name = 'wpcivi_widget_acf';
        $this->label = __('CiviCRM Widget', 'wpcivi-shared');

        $this->category = __("Relational",'acf');
        $this->defaults = [];

        parent::__construct();
    }

    /**
     * Render (echo) backend widget select field.
     * @param array $field Field
     */
    public function render_field($field)
    {
        $field = array_merge($this->defaults, $field);
        $field['type'] = 'select';

        $widgets = CiviWidgetCollection::getInstance();
        $field['choices'] = $widgets->getWidgets();
        ?>
        <select id="<?php echo str_replace(['[', ']'], ['-', ''], $field['name']); ?>"
                name="<?php echo $field['name']; ?>">
            <option value="">- <?php _e('Select Widget Type', 'wpcivi-shared'); ?></option>
            <?php foreach ($field['choices'] as $key => $widget) {
                $selected = '';
                if ((is_array($field['value']) && in_array($widget['name'], $field['value'])) || $field['value'] == $widget['name'])
                    $selected = ' selected="selected"';
                ?>
                <option value="<?php echo $widget['name']; ?>"<?php echo $selected; ?>><?php echo $widget['label']; ?></option>
            <?php } ?>
        </select>
        <?php
    }

    /**
     * Render (echo) actual widget content.
     * @param string $value Widget name
     * @param int $post_id Parent post ID
     * @param array $field Field options
     * @return string Output
     */
    function format_value($value, $post_id, $field = [])
    {
        if(empty($value)) {
            return '[WIDGET_TYPE_NOT_SET: You have to select a CiviCRM widget type!]';
        }

        try {
            $widgets = CiviWidgetCollection::getInstance();
            $wclass = $widgets->getWidgetClass($value);

            return $wclass->render($field);

        } catch(WPCiviException $e) {
            return '[WIDGET_RENDER_ERROR (' . $value . '): ' . $e->getMessage() . ']';
        }
    }

}