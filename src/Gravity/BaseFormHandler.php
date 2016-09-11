<?php
namespace WPCivi\Shared\Gravity;
use WPCivi\Shared\BasePlugin;

/**
 * Class Gravity\BaseFormHandler
 * Base handler for Gravity Form submissions. Most of the actual code is currently in
 * the form handlers themselves, we should refactor this further sometime.
 * @package WPCivi\Shared
 */
class BaseFormHandler extends BasePlugin
{

    /**
     * @var string $label Form handler label (defaults to class name)
     */
    protected $label;

    /**
     * BaseFormHandler constructor.
     */
    public function __construct() {
        $this->register();
    }

    /**
     * Register Gravity Form submission hooks if Gravity Forms is active,
     * and show the current handler in the form settings.
     */
    public function register()
    {
        // Check if Gravity Forms is active
        if (!$this->isPluginActive('gravityforms')) {
            return true;
        }

        // Make handlers available in form settings
        $this->registerThisHandler();

        // Register frontend actions/filters for Gravity Forms
        if(method_exists($this, 'preRender')) {
            $this->addFilter('gform_pre_render', [$this, 'preRender'], 10, 3);
            $this->addFilter('gform_pre_validation', [$this, 'preRender'], 10, 3);
            $this->addFilter('gform_pre_submission_filter', [$this, 'preRender'], 10, 3);
            // $this->addFilter('gform_admin_pre_render', [$this, 'preRender'], 10, 3);
        }
        if(method_exists($this, 'getInputValue')) {
            $this->addFilter('gform_get_input_value', [$this, 'getInputValue'], 10, 4);
        }
        if(method_exists($this, 'saveFieldValue')) {
            $this->addFilter('gform_save_field_value', [$this, 'saveFieldValue'], 10, 4);
        }
        if(method_exists($this, 'afterSubmission')) {
            $this->addAction('gform_after_submission', [$this, 'afterSubmission'], 10, 2);
        }

        // Register entries listing actions / filters
        if(method_exists($this, 'entryMeta')) {
            $this->addFilter('gform_entry_meta', [$this, 'entryMeta'], 10, 2);
        }
        if(method_exists($this, 'entriesColumn')) {
            $this->addAction('gform_entries_column', [$this, 'entriesColumn'], 10, 5);
        }
        if(method_exists($this, 'entryDetail')) {
            $this->addAction('gform_entry_detail', [$this, 'entryDetail'], 10, 2);
        }

        // Register form settings actions / filters
        if(method_exists($this, 'formSettings')) {
            $this->addAction('gform_form_settings', [$this, 'formSettings'], 10, 2);
        }
        if(method_exists($this, 'formSettingsSubmit')) {
            $this->addAction('gform_pre_form_settings_save', [$this, 'formSettingsSubmit'], 10, 1);
        }
        if(method_exists($this, 'formSettingsTooltips')) {
            $this->addFilter('gform_tooltips', [$this, 'formSettingsTooltips']);
        }
    }

    /**
     * Register this handler by adding it to the wpcivi_gravity_handlers filter.
     * This function can be overridden to hide a handler in form settings.
     */
    protected function registerThisHandler()
    {
        $this->addFilter('wpcivi_gravity_handlers', function($handlers) {
            $handlers[$this->getName()] = $this->getLabel();
            return $handlers;
        }, 10, 1);
    }

    /**
     * Return a key / value array for a form entry, with the form field label as key,
     * to make the data more accessibly - not sure if there's a better way...
     * @param mixed $entry Form Entry
     * @param mixed $form Form
     * @return array Form Data Key/Value Array
     */
    protected function getDataKVArray(&$entry, &$form) {

        $fields = [];
        $data = [];
        foreach ($form['fields'] as $field) {

            $label = strtolower(preg_replace('/[^a-zA-z0-9]/', '', $field->label));
            if (!$label) {
                $label = $field->id;
            }

            $fields[$label] = $field->id;
            $data[$label] = $entry[$field->id];

            // For checkboxes, add an entry for each option value (18.1 -> maakuwkeuze.nieuwsbrief)
            if (get_class($field) == 'GF_Field_Checkbox') {
                foreach ($field->choices as $key => $choice) {
                    $key_id = $field->inputs[$key]['id'];
                    $data[$label . '.' . $choice['value']] = $entry[$key_id];
                }
            }
        }

        return $data;
    }

    /**
     * Check if this handler class is enabled for this form.
     * @param mixed $form Form
     * @return bool Is enabled?
     */
    public function handlerIsEnabled($form)
    {
        if (!empty($form['wpcivi_form_handler'])) {
            if($this->getName() == $form['wpcivi_form_handler']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current class name.
     * @return string Class Name
     */
    protected function getName() {
        $class = new \ReflectionClass($this);
        return $class->getShortName();
    }

    /**
     * Get current class pretty name.
     * @return string Class Label
     */
    protected function getLabel() {
        if(!empty($this->label)) {
            return $this->label;
        }
        return $this->getName();
    }

}