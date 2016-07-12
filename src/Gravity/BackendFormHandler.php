<?php
namespace WPCivi\Shared\Gravity;

/**
 * Class Gravity\BackendFormHandler
 * Backend form handling for Gravity Forms. Adds an option to the form settings to pick a handler.
 * Handlers are added by calling a filter hook that is registered for each handler in BaseFormHandler.
 * @package WPCivi\Shared
 */
class BackendFormHandler extends BaseFormHandler
{

    /**
     * Add custom options to the form admin.
     * @param array $settings Settings
     * @param mixed $form Form
     * @return mixed Settings
     */
    public function formSettings($settings, $form)
    {
        $settings['CiviCRM Integration'] = [];
        $wpcivi_form_handler = rgar($form, 'wpcivi_form_handler');

        $fhtml = "<tr>
                  <th><label for='wpcivi_form_handler'>WPCivi Custom Form Handler</label> " .
                  gform_tooltip('wpcivi_form_handler', '', true) . "
                  </th><th>
                  <select name='wpcivi_form_handler' id='wpcivi_form_handler'>
                  <option value=''>-none-</option>
                 ";

        foreach ($this->getHandlers() as $handler) {
            $fhtml .= "<option value='{$handler}'" . ($handler == $wpcivi_form_handler ? " selected" : "") . ">{$handler}</option>\n";
        }

        $fhtml .= "</select>
                   </th></tr>";

        $settings['CiviCRM Integration']['wpcivi_form_handler'] = $fhtml;
        return $settings;
    }

    /**
     * Save custom options in the form admin.
     * @param mixed $form Form
     * @return mixed Settings
     */
    public function formSettingsSubmit($form)
    {
        $form['wpcivi_form_handler'] = rgpost('wpcivi_form_handler');
        return $form;
    }

    /**
     * Register form admin tooltips.
     * @param array $tooltips Tooltips
     * @return array Tooltips
     */
    public function formSettingsTooltips($tooltips = [])
    {
        $tooltips['wpcivi_form_handler'] = '<h6>WPCivi Form Handler</h6> Select a custom form handler class to integrate this form with CiviCRM. The available classes are defined in the WPCivi WordPress plugins.';
        return $tooltips;
    }

    /**
     * Get form handler classes that will be available in the Gravity form settings.
     * @return array Form Handlers
     */
    protected function getHandlers()
    {
        return apply_filters('wpcivi_gravity_handlers', []);
    }

    /**
     * Override BaseFormHandler function so this class doesn't show up in form settings.
     */
    protected function registerThisHandler()
    {
        return;
    }

}