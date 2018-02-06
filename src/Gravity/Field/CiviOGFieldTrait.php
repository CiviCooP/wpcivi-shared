<?php
namespace WPCivi\Shared\Gravity\Field;

use WPCivi\Shared\Entity\OptionGroup;
use WPCivi\Shared\Entity\OptionValue;

/**
 * Trait WPCivi\Shared\Gravity\Field\CiviOGFieldTrait
 * Functions to populate a \GF_Field_Option field with choices from a CiviCRM OptionGroup.
 * @package WPCivi\Shared
 */
trait CiviOGFieldTrait
{
    public $choices = [];

    /**
     * Return the title / label for this field type
     * @return string Field Title
     */
    public function get_form_editor_field_title()
    {
        $title = isset($this->type_label) ? $this->type_label : 'Choice from CiviCRM OG';
        return esc_attr__($title, 'wpcivi-shared');
    }

    /**
     * Get choices for frontend (!) field
     * @param string $value Current value
     * @return string Options HTML
     */
    public function get_choices($value)
    {
        if (!isset($this->wpcivi_optiongroup) || empty($this->wpcivi_optiongroup)) {
            $this->placeholder = '- OptionGroup not set! -';
        } else {
            $optionValues = OptionValue::getOptionValues($this->wpcivi_optiongroup);

            if ($optionValues->count() == 0) {
                $this->placeholder = '- OptionGroup is empty! -';
            } else {
                if(empty($this->placeholder)) {
                    $this->placeholder = '- Select -';
                }

                foreach ($optionValues as $option) {
                    $this->choices[] = ['value' => $option->value, 'text' => $option->label];
                }
            }
        }

        return \GFCommon::get_select_choices($this, $value);
    }

    /**
     * Add a button to the admin form editor for this field type.
     * @return array Button Options
     */
    public function get_form_editor_button()
    {
        return [
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title(),
        ];
    }

    /**
     * Define which Gravity standard settings will be shown in the form editor.
     * @return array Field Settings
     */
    public function get_form_editor_field_settings()
    {
        return [
            'label_setting',
            'description_setting',
            'default_value_setting',
            'visibility_setting',
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'label_setting',
            'label_placement_setting',
            'sub_label_placement_setting',
            'admin_label_setting',
            'rules_setting',
            'duplicate_setting',
            'placeholder_setting',
            'css_class_setting',
        ];
    }

    /**
     * Register backend hooks to show a 'Select OptionGroup' setting in the form editor.
     */
    public function registerBackendHooks()
    {
        add_action('gform_field_standard_settings', [$this, 'echoFieldStandardSettings'], 10, 2);
        add_action('gform_editor_js', [$this, 'echoFieldEditorJs'], 10, 0);
        add_action('gform_tooltips', [$this, 'addTooltips'], 10, 1);
    }

    /**
     * HTML block for the admin form editor: adds a selectbox to set the CiviCRM OptionGroup.
     * @param int $position Position
     * @param int $form_id Form ID
     */
    public function echoFieldStandardSettings($position, $form_id)
    {
        if ($position == 25) {
            $optionGroups = OptionGroup::getCustomOptionGroups();
            ?>
            <li class="<?= $this->ogField; ?>_setting field_setting">
                <label for="<?= $this->ogField; ?>" class="section_label">
                    <?php esc_html_e('Use CiviCRM Option Group', 'wpcivi-shared'); ?>
                    <?php gform_tooltip($this->type) ?>
                </label>
                <select id="<?= $this->ogField; ?>" name="<?= $this->ogField; ?>"
                        onchange="SetFieldProperty('<?= $this->ogField; ?>', jQuery(this).val())">
                    <option value="">- Select OptionGroup -</option>
                    <?php foreach ($optionGroups as $og): ?>
                        <option value="<?= $og->name; ?>"><?= $og->title; ?></option>
                    <?php endforeach; ?>
                </select>
            </li>
            <?php
        }
    }

    /**
     * Admin form editor JS: we need to display and update our custom field ourselves (I know).
     */
    public function echoFieldEditorJs()
    {
        ?>
        <script type="text/javascript">
            fieldSettings['<?=$this->type;?>'] += ', .<?=$this->ogField;?>_setting';
            jQuery(document).bind('gform_load_field_settings', function (event, field, form) {
                jQuery('#<?=$this->ogField;?>').val(field.<?=$this->ogField;?>);
            });
        </script>
        <?php
    }

    /**
     * Add admin tooltip.
     * @param string[] $tooltips
     * @return string[]
     */
    public function addTooltips($tooltips)
    {
        $tooltips[$this->type] = "<h6>Option Group</h6>Select an OptionGroup from CiviCRM that should be used to populate this selectbox.";
        return $tooltips;
    }

}