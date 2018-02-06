<?php

namespace WPCivi\Shared\Gravity\Field;

use WPCivi\Shared\Entity\Country;
use WPCivi\Shared\Entity\OptionGroup;

/**
 * Trait WPCivi\Shared\Gravity\Field\CiviCountryFieldTrait
 * Functions to populate a \GF_Field_Option field with choices from the CiviCRM country list.
 * @package WPCivi\Shared
 */
trait CiviCountryFieldTrait
{
    public $choices = [];

    /**
     * Return the title / label for this field type
     * @return string Field Title
     */
    public function get_form_editor_field_title()
    {
        $title = isset($this->type_label) ? $this->type_label : 'CiviCRM Country Select';
        return esc_attr__($title, 'wpcivi-shared');
    }

    /**
     * Get choices for frontend (!) field
     * @param string $value Current value
     * @return string Options HTML
     */
    public function get_choices($value)
    {
        $optionValues = Country::getCountries();

        if ($optionValues->count() == 0) {
            $this->placeholder = '- Country List is empty! -';
        } else {
            foreach ($optionValues as $key => $option) {
                $this->choices[$key] = ['value' => $option->id, 'text' => $option->name];
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
            'enable_enhanced_ui_setting',
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
     * Register backend hooks: not currently implemented.
     */
    public function registerBackendHooks()
    {
        return;
    }
}
